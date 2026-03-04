<?php
/**
 * OpenAI Service
 * Provides embedding generation and GPT-powered match explanations.
 * Embeddings are cached in the profile row to minimise API calls.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class OpenAIService {

    private $db;

    // Column names that hold the cache per table
    private static $profileTables = [
        'mentor' => 'mentor_profiles',
        'mentee' => 'mentee_profiles',
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Return the embedding vector for $text, using DB cache when fresh.
     *
     * @param string $text  The text to embed.
     * @param int    $userId  The user whose profile owns this embedding.
     * @param string $type  'mentor' or 'mentee'
     * @return float[]|null  Array of floats, or null on failure.
     */
    public function getEmbedding(string $text, int $userId, string $type): ?array {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Try cache first
        $cached = $this->loadCachedEmbedding($userId, $type);
        if ($cached !== null) {
            return $cached;
        }

        // Fetch from OpenAI
        $vector = $this->fetchEmbeddingFromAPI($text);
        if ($vector === null) {
            Logger::warning('OpenAIService: embedding API call failed', ['user_id' => $userId, 'type' => $type]);
            return null;
        }

        // Persist to cache
        $this->saveCachedEmbedding($userId, $type, $vector);

        return $vector;
    }

    /**
     * Cosine similarity between two equal-length float vectors.
     * Returns a value in [0, 1]; 1 = identical direction.
     *
     * @param float[] $a
     * @param float[] $b
     */
    public static function cosineSimilarity(array $a, array $b): float {
        if (count($a) !== count($b) || count($a) === 0) {
            return 0.0;
        }

        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $valA) {
            $valB   = $b[$i];
            $dot   += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        $denom = sqrt($normA) * sqrt($normB);
        if ($denom == 0.0) {
            return 0.0;
        }

        return (float) ($dot / $denom);
    }

    /**
     * Ask GPT to write a 2-3 sentence explanation of why this pair is a good match.
     * Returns an empty string on failure (never throws).
     *
     * @param array $menteeProfile  Associative array of relevant mentee fields.
     * @param array $mentorProfile  Associative array of relevant mentor fields.
     * @param float $score          The total match score (0-100).
     */
    public function explainMatch(array $menteeProfile, array $mentorProfile, float $score): string {
        if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
            return '';
        }

        $systemPrompt = 'You are a helpful assistant for a university law mentoring platform. '
            . 'Given a mentee and mentor profile, write 2-3 clear, encouraging sentences explaining '
            . 'why they are a good match. Focus on shared interests, practice area alignment, '
            . 'and complementary goals. Be specific but concise.';

        $userContent = sprintf(
            "Mentee: Programme=%s, Practice area preference=%s, Interests=%s, Goals=%s, Mentoring style=%s\n"
            . "Mentor: Programme=%s, Practice area=%s, Expertise=%s, Interests=%s, Mentoring style=%s\n"
            . "Match score: %.0f/100",
            $menteeProfile['programme_level']        ?? 'N/A',
            $menteeProfile['practice_area_preference'] ?? 'N/A',
            $menteeProfile['interests']              ?? 'N/A',
            $menteeProfile['goals']                  ?? 'N/A',
            $menteeProfile['mentoring_style']        ?? 'N/A',
            $mentorProfile['programme_level']        ?? 'N/A',
            $mentorProfile['practice_area']          ?? 'N/A',
            $mentorProfile['expertise']              ?? 'N/A',
            $mentorProfile['interests']              ?? 'N/A',
            $mentorProfile['mentoring_style']        ?? 'N/A',
            $score
        );

        $payload = [
            'model'      => defined('OPENAI_CHAT_MODEL') ? OPENAI_CHAT_MODEL : 'gpt-4o-mini',
            'max_tokens' => 150,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userContent],
            ],
        ];

        $response = $this->callOpenAI('/chat/completions', $payload);
        if ($response === null) {
            return '';
        }

        return trim($response['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Invalidate the embedding cache for a user so it is regenerated on next scoring.
     * Call this whenever a user's profile is updated.
     *
     * @param int    $userId
     * @param string $type  'mentor' or 'mentee'
     */
    public function invalidateCache(int $userId, string $type): void {
        $table = self::$profileTables[$type] ?? null;
        if ($table === null) return;

        $stmt = $this->db->prepare(
            "UPDATE $table SET embedding_cache = NULL, embedding_cached_at = NULL WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    /**
     * Fetch (or return cached) embedding for an arbitrary text string.
     * Uses a static in-memory array — NOT stored in the DB.
     * Intended for fixed / short strings like programme level descriptions.
     *
     * @return float[]|null
     */
    public function getEmbeddingForText(string $text): ?array {
        static $cache = [];
        $key = md5($text);
        if (!isset($cache[$key])) {
            $cache[$key] = $this->fetchEmbeddingFromAPI($text);
        }
        return $cache[$key];
    }

    /**
     * Ask GPT-4o-mini to rate the geographic proximity of two locations (0.0 – 1.0).
     * The prompt is calibrated for Hong Kong district-level granularity.
     * Results are cached in a static array for the duration of the request.
     *
     * Scale used in the prompt:
     *   1.00 – same district / street
     *   0.85 – adjacent HK districts, same region
     *   0.65 – cross-harbour (HK Island ↔ Kowloon)
     *   0.45 – HK urban area ↔ New Territories
     *   0.20 – different cities in the same country
     *   0.05 – different countries / continents
     *
     * @return float  Value in [0,1]; returns 0.5 (neutral) on API failure.
     */
    public function assessLocationProximity(string $locationA, string $locationB): float {
        static $cache = [];

        // Normalise key so (A,B) == (B,A)
        $pair = implode('||', array_map('strtolower', [min($locationA, $locationB), max($locationA, $locationB)]));
        if (isset($cache[$pair])) {
            return $cache[$pair];
        }

        if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
            return 0.5;
        }

        $system = 'You are a geographic proximity assistant for a Hong Kong university mentoring platform. '
            . 'Rate location proximity from 0.0 to 1.0 using approximate transit/walking time. '
            . 'Scale: same district/street=1.0, adjacent HK districts=0.85, '
            . 'cross-harbour (HK Island to Kowloon)=0.65, HK urban to New Territories=0.45, '
            . 'different cities same country=0.20, different countries=0.05. '
            . 'Reply with ONLY a single decimal number, nothing else.';

        $user = "Location A: {$locationA}\nLocation B: {$locationB}";

        $payload = [
            'model'       => defined('OPENAI_CHAT_MODEL') ? OPENAI_CHAT_MODEL : 'gpt-4o-mini',
            'max_tokens'  => 5,
            'temperature' => 0,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
        ];

        $response = $this->callOpenAI('/chat/completions', $payload);
        $raw      = trim($response['choices'][0]['message']['content'] ?? '');
        $score    = is_numeric($raw) ? max(0.0, min(1.0, (float) $raw)) : 0.5;

        Logger::debug('OpenAIService: location proximity scored', [
            'locationA' => $locationA, 'locationB' => $locationB, 'score' => $score,
        ]);

        $cache[$pair] = $score;
        return $score;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Load embedding from DB cache; returns null if absent or stale.
     */
    private function loadCachedEmbedding(int $userId, string $type): ?array {
        $table = self::$profileTables[$type] ?? null;
        if ($table === null) return null;

        $ttl  = defined('EMBEDDING_CACHE_TTL') ? (int) EMBEDDING_CACHE_TTL : 86400;
        $stmt = $this->db->prepare(
            "SELECT embedding_cache, embedding_cached_at FROM $table WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || empty($row['embedding_cache']) || empty($row['embedding_cached_at'])) {
            return null;
        }

        $age = time() - strtotime($row['embedding_cached_at']);
        if ($age > $ttl) {
            return null; // stale
        }

        $vector = json_decode($row['embedding_cache'], true);
        if (!is_array($vector)) {
            return null;
        }

        return $vector;
    }

    /**
     * Persist embedding vector to the profile's cache columns.
     */
    private function saveCachedEmbedding(int $userId, string $type, array $vector): void {
        $table = self::$profileTables[$type] ?? null;
        if ($table === null) return;

        $stmt = $this->db->prepare(
            "UPDATE $table SET embedding_cache = ?, embedding_cached_at = NOW() WHERE user_id = ?"
        );
        $stmt->execute([json_encode($vector), $userId]);
    }

    /**
     * Call the OpenAI Embeddings API and return the vector.
     *
     * @return float[]|null
     */
    private function fetchEmbeddingFromAPI(string $text): ?array {
        $model   = defined('OPENAI_EMBEDDING_MODEL') ? OPENAI_EMBEDDING_MODEL : 'text-embedding-3-small';
        $payload = ['model' => $model, 'input' => $text];

        $response = $this->callOpenAI('/embeddings', $payload);
        if ($response === null) {
            return null;
        }

        return $response['data'][0]['embedding'] ?? null;
    }

    /**
     * Generic OpenAI HTTP call via curl.
     *
     * @param string $endpoint  e.g. '/embeddings' or '/chat/completions'
     * @param array  $payload
     * @return array|null  Decoded JSON response, or null on error.
     */
    private function callOpenAI(string $endpoint, array $payload): ?array {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        if ($apiKey === '') {
            Logger::warning('OpenAIService: OPENAI_API_KEY is not set');
            return null;
        }

        $url  = 'https://api.openai.com/v1' . $endpoint;
        $body = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            Logger::error('OpenAIService: curl error', ['error' => $curlErr, 'endpoint' => $endpoint]);
            return null;
        }

        $decoded = json_decode($raw, true);

        if ($httpCode !== 200 || !is_array($decoded)) {
            Logger::error('OpenAIService: API error', [
                'http_code' => $httpCode,
                'endpoint'  => $endpoint,
                'response'  => substr($raw, 0, 300),
            ]);
            return null;
        }

        return $decoded;
    }
}
