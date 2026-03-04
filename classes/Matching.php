<?php
/**
 * Matching Algorithm Class — v2 (AI-enhanced)
 * CUHK Law E-Mentoring Platform
 *
 * Scoring breakdown (100 pts total):
 *   Practice Area         35 pts  — OpenAI embedding cosine similarity (semantic)
 *   Interests + Goals     25 pts  — OpenAI embedding cosine similarity
 *   Programme Level       15 pts  — OpenAI embedding cosine similarity on expanded descriptions
 *                                   (captures knowledge-level hierarchy; fallback: compatibility matrix)
 *   Location              10 pts  — GPT-4o-mini HK district-aware proximity score
 *                                   (same district=10, cross-harbour≈6.5, NT≈4.5, abroad≈0.5)
 *   Language              10 pts  — Exact string match (case-insensitive)
 *   Mentoring Style        5 pts  — Exact match (or either side chose "all")
 *
 * When AI_MATCHING_ENABLED=false or OpenAI is unavailable, falls back to
 * the original Jaccard keyword-overlap similarity for text fields.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mentor.php';
require_once __DIR__ . '/OpenAIService.php';
require_once __DIR__ . '/Logger.php';

class Matching {
    private $db;
    private $ai;

    // Score weights (must sum to 100)
    private const WEIGHTS = [
        'practice_area'   => 35,
        'interests_goals' => 25,
        'programme'       => 15,
        'location'        => 10,
        'language'        => 10,
        'mentoring_style' =>  5,
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ai = new OpenAIService();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Calculate (and persist) the matching score between a mentee and a mentor.
     *
     * @return float  Total score 0-100.
     */
    public function calculateMatchScore(int $menteeId, int $mentorId): float {
        $mentee = $this->getMenteeProfile($menteeId);
        $mentor = $this->getMentorProfile($mentorId);

        if (!$mentee || !$mentor) {
            return 0.0;
        }

        $useAI = defined('AI_MATCHING_ENABLED') && AI_MATCHING_ENABLED && defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '';

        // ── Practice Area ────────────────────────────────────────────────────
        $practiceAreaMatch  = false;
        $practiceAreaPoints = 0.0;

        if ($mentee['practice_area_preference'] && $mentor['practice_area']) {
            if (strtolower($mentee['practice_area_preference']) === strtolower($mentor['practice_area'])) {
                $practiceAreaMatch  = true;
                $practiceAreaPoints = self::WEIGHTS['practice_area'];
            } else {
                $sim = $useAI
                    ? $this->embeddingSimilarity($mentee['practice_area_preference'], $mentor['practice_area'], $menteeId, $mentorId, 'practice_area')
                    : $this->keywordSimilarity($mentee['practice_area_preference'], $mentor['practice_area']);

                $practiceAreaPoints = $sim * self::WEIGHTS['practice_area'];
                $practiceAreaMatch  = ($sim >= 0.6);
            }
        }

        // ── Interests + Goals vs Expertise + Interests ────────────────────────
        $interestScore = 0.0;
        $menteeText    = trim(($mentee['interests'] ?? '') . ' ' . ($mentee['goals'] ?? '') . ' ' . ($mentee['expectations'] ?? ''));
        $mentorText    = trim(($mentor['expertise'] ?? '') . ' ' . ($mentor['interests'] ?? '') . ' ' . ($mentor['bio'] ?? ''));

        if ($menteeText !== '' && $mentorText !== '') {
            $sim = $useAI
                ? $this->embeddingSimilarity($menteeText, $mentorText, $menteeId, $mentorId, 'interests')
                : $this->keywordSimilarity($menteeText, $mentorText);

            $interestScore = $sim; // 0-1; multiplied by weight when saving
        }

        // ── Programme Level ──────────────────────────────────────────────────
        $programmeSim   = 0.0;
        $programmeMatch = false;
        if ($mentee['programme_level'] && $mentor['programme_level']) {
            if (strtolower($mentee['programme_level']) === strtolower($mentor['programme_level'])) {
                $programmeSim   = 1.0;
                $programmeMatch = true;
            } else {
                $programmeSim   = $this->programmeSimilarity($mentee['programme_level'], $mentor['programme_level'], $useAI);
                $programmeMatch = ($programmeSim >= 0.6);
            }
        }
        $programmePoints = $programmeSim * self::WEIGHTS['programme'];

        // ── Location ─────────────────────────────────────────────────────────
        $locationSim   = 0.0;
        $locationMatch = false;
        if ($mentee['location'] && $mentor['location']) {
            if (strtolower(trim($mentee['location'])) === strtolower(trim($mentor['location']))) {
                $locationSim   = 1.0;
                $locationMatch = true;
            } else {
                $locationSim   = $this->locationProximitySim($mentee['location'], $mentor['location'], $useAI);
                $locationMatch = ($locationSim >= 0.5);
            }
        }
        $locationPoints = $locationSim * self::WEIGHTS['location'];

        // ── Language ─────────────────────────────────────────────────────────
        $languageMatch = false;
        if ($mentee['language_preference'] && $mentor['language']) {
            $languageMatch = (strtolower(trim($mentee['language_preference'])) === strtolower(trim($mentor['language'])));
        }

        // ── Mentoring Style ──────────────────────────────────────────────────
        // Rule: if mentor offers 'all' styles → match any mentee style.
        // Otherwise exact match is required (mentor-led: a mentor who only
        // offers career_advice should not match a mentee wanting skill_building).
        $mentoringStyleMatch = false;
        $menteeStyle = $mentee['mentoring_style'] ?? 'all';
        $mentorStyle = $mentor['mentoring_style'] ?? 'all';
        if ($menteeStyle === 'all' || $mentorStyle === 'all' || $menteeStyle === $mentorStyle) {
            $mentoringStyleMatch = true;
        }

        // ── Total Score ──────────────────────────────────────────────────────
        $total = $practiceAreaPoints
            + ($interestScore * self::WEIGHTS['interests_goals'])
            + $programmePoints
            + $locationPoints
            + ($languageMatch       ? self::WEIGHTS['language']        : 0)
            + ($mentoringStyleMatch ? self::WEIGHTS['mentoring_style'] : 0);

        $total = round(min(100.0, $total), 2);

        // ── Persist ──────────────────────────────────────────────────────────
        $this->saveMatchingScore(
            $menteeId, $mentorId,
            $practiceAreaMatch, $programmeMatch, $interestScore,
            $locationMatch, $languageMatch, $mentoringStyleMatch,
            $total
        );

        Logger::debug('Matching score calculated', [
            'mentee_id' => $menteeId, 'mentor_id' => $mentorId,
            'total' => $total, 'ai_enabled' => $useAI,
        ]);

        return $total;
    }

    /**
     * Get or generate an AI explanation for why two profiles are a good match.
     * The result is cached in matching_scores.ai_explanation.
     */
    public function getAIExplanation(int $menteeId, int $mentorId): string {
        // Return cached value if present
        $stmt = $this->db->prepare(
            "SELECT ai_explanation, total_score FROM matching_scores WHERE mentee_id = ? AND mentor_id = ?"
        );
        $stmt->execute([$menteeId, $mentorId]);
        $row = $stmt->fetch();

        if ($row && !empty($row['ai_explanation'])) {
            return $row['ai_explanation'];
        }

        // Generate via GPT
        $mentee = $this->getMenteeProfile($menteeId);
        $mentor = $this->getMentorProfile($mentorId);

        if (!$mentee || !$mentor) return '';

        $score       = $row['total_score'] ?? $this->calculateMatchScore($menteeId, $mentorId);
        $explanation = $this->ai->explainMatch($mentee, $mentor, (float) $score);

        if ($explanation !== '') {
            $this->db->prepare(
                "UPDATE matching_scores SET ai_explanation = ? WHERE mentee_id = ? AND mentor_id = ?"
            )->execute([$explanation, $menteeId, $mentorId]);
        }

        return $explanation;
    }

    /**
     * Recompute scores for every mentee-mentor combination currently in matching_scores.
     * Also scores any mentee that has no scores yet against all available mentors.
     * Returns the number of pairs (re)scored.
     */
    public function rebuildAllScores(): int {
        Logger::info('Matching::rebuildAllScores started');

        // Collect all mentee IDs
        $menteeIds = $this->db->query(
            "SELECT DISTINCT user_id FROM mentee_profiles"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $mentorClass = new Mentor();
        $count = 0;

        foreach ($menteeIds as $menteeId) {
            $mentee  = $this->getMenteeProfile((int) $menteeId);
            $mentors = $mentorClass->getAvailableMentors($mentee['practice_area_preference'] ?? null);

            foreach ($mentors as $mentor) {
                $this->calculateMatchScore((int) $menteeId, (int) $mentor['user_id']);
                $count++;
            }
        }

        Logger::info('Matching::rebuildAllScores completed', ['pairs_scored' => $count]);
        return $count;
    }

    /**
     * Invalidate the embedding cache for a user after their profile changes.
     * Call from Mentee::saveProfile() and Mentor::saveProfile().
     */
    public function invalidateEmbeddingCache(int $userId, string $type): void {
        $this->ai->invalidateCache($userId, $type);
    }

    /**
     * Get recommended mentors for a mentee, sorted by match score descending.
     */
    public function getRecommendedMentors(int $menteeId, int $limit = 10): array {
        $mentee      = $this->getMenteeProfile($menteeId);
        $mentorClass = new Mentor();
        $mentors     = $mentorClass->getAvailableMentors($mentee['practice_area_preference'] ?? null);

        $scoredMentors = [];
        foreach ($mentors as $mentor) {
            $score             = $this->calculateMatchScore($menteeId, (int) $mentor['user_id']);
            $mentor['match_score'] = $score;
            $scoredMentors[]   = $mentor;
        }

        usort($scoredMentors, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return array_slice($scoredMentors, 0, $limit);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Semantic similarity between two programme levels (0-1).
     * Expands short codes to descriptive strings before embedding so the model
     * captures the knowledge-level hierarchy (PhD > LLM > LLB ≈ JD).
     * Falls back to a hardcoded compatibility matrix when AI is disabled.
     */
    private function programmeSimilarity(string $levelA, string $levelB, bool $useAI): float {
        static $descriptions = [
            'JD'    => 'Juris Doctor professional law degree practical legal training common law',
            'LLB'   => 'Bachelor of Laws undergraduate law degree foundational legal studies',
            'LLM'   => 'Master of Laws postgraduate specialised advanced law degree',
            'PhD'   => 'Doctor of Philosophy doctoral legal research academic scholarship',
            'Other' => 'Other legal or professional qualification continuing education',
        ];

        if ($useAI) {
            $textA = $descriptions[$levelA] ?? $levelA;
            $textB = $descriptions[$levelB] ?? $levelB;
            $vecA  = $this->ai->getEmbeddingForText($textA);
            $vecB  = $this->ai->getEmbeddingForText($textB);
            if ($vecA !== null && $vecB !== null) {
                return OpenAIService::cosineSimilarity($vecA, $vecB);
            }
        }

        // Fallback: static compatibility matrix (symmetric)
        $matrix = [
            'JD'  => ['JD' => 1.0, 'LLB' => 0.85, 'LLM' => 0.55, 'PhD' => 0.35, 'Other' => 0.30],
            'LLB' => ['JD' => 0.85, 'LLB' => 1.0, 'LLM' => 0.60, 'PhD' => 0.40, 'Other' => 0.30],
            'LLM' => ['JD' => 0.55, 'LLB' => 0.60, 'LLM' => 1.0, 'PhD' => 0.75, 'Other' => 0.40],
            'PhD' => ['JD' => 0.35, 'LLB' => 0.40, 'LLM' => 0.75, 'PhD' => 1.0, 'Other' => 0.35],
            'Other' => ['JD' => 0.30, 'LLB' => 0.30, 'LLM' => 0.40, 'PhD' => 0.35, 'Other' => 1.0],
        ];
        return $matrix[$levelA][$levelB] ?? 0.3;
    }

    /**
     * Geographic proximity score (0-1) between two location strings.
     * Uses GPT-4o-mini with a HK district-calibrated prompt.
     * Falls back to keyword similarity when AI is disabled.
     */
    private function locationProximitySim(string $locA, string $locB, bool $useAI): float {
        if ($useAI) {
            return $this->ai->assessLocationProximity($locA, $locB);
        }
        return $this->keywordSimilarity($locA, $locB);
    }

    /**
     * Semantic similarity via OpenAI embeddings (0-1).
     * Falls back to keyword similarity on API failure.
     */
    private function getMenteeProfile(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM mentee_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    private function getMentorProfile(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM mentor_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Semantic similarity via OpenAI embeddings (0-1).
     * Falls back to keyword similarity on API failure.
     */
    private function embeddingSimilarity(
        string $textA, string $textB,
        int $menteeId, int $mentorId,
        string $context
    ): float {
        // For practice area comparison we pass the mentee userId for both embeddings
        // since we want to embed the raw text, not cache per-user. We use 'mentee'
        // type for $textA and 'mentor' type for $textB to keep caches separate.
        $vecA = $this->ai->getEmbedding($textA, $menteeId, 'mentee');
        $vecB = $this->ai->getEmbedding($textB, $mentorId, 'mentor');

        if ($vecA === null || $vecB === null) {
            Logger::warning('Matching: embedding unavailable, falling back to keyword similarity', [
                'mentee_id' => $menteeId, 'mentor_id' => $mentorId, 'context' => $context,
            ]);
            return $this->keywordSimilarity($textA, $textB);
        }

        return OpenAIService::cosineSimilarity($vecA, $vecB);
    }

    /**
     * Jaccard keyword overlap similarity (legacy fallback, 0-1).
     */
    private function keywordSimilarity(string $text1, string $text2): float {
        $words1 = array_filter(array_map('strtolower', preg_split('/\W+/', $text1)));
        $words2 = array_filter(array_map('strtolower', preg_split('/\W+/', $text2)));

        if (empty($words1) || empty($words2)) return 0.0;

        $intersection = array_intersect($words1, $words2);
        $union        = array_unique(array_merge($words1, $words2));

        return count($union) === 0 ? 0.0 : count($intersection) / count($union);
    }

    /**
     * Persist (insert or update) matching score row.
     */
    private function saveMatchingScore(
        int $menteeId, int $mentorId,
        bool $practiceAreaMatch, bool $programmeMatch, float $interestScore,
        bool $locationMatch, bool $languageMatch, bool $mentoringStyleMatch,
        float $totalScore
    ): void {
        $useAI = defined('AI_MATCHING_ENABLED') && AI_MATCHING_ENABLED;

        $stmt = $this->db->prepare(
            "SELECT id FROM matching_scores WHERE mentee_id = ? AND mentor_id = ?"
        );
        $stmt->execute([$menteeId, $mentorId]);
        $exists = $stmt->fetch();

        $version = $useAI ? 'v2-ai' : 'v2-keyword';

        if ($exists) {
            $this->db->prepare(
                "UPDATE matching_scores SET
                    practice_area_match = ?, programme_match = ?, interest_score = ?,
                    location_match = ?, language_match = ?, mentoring_style_match = ?,
                    total_score = ?, algorithm_version = ?, calculated_at = NOW()
                 WHERE mentee_id = ? AND mentor_id = ?"
            )->execute([
                $practiceAreaMatch, $programmeMatch, $interestScore,
                $locationMatch, $languageMatch, $mentoringStyleMatch,
                $totalScore, $version,
                $menteeId, $mentorId,
            ]);
        } else {
            $this->db->prepare(
                "INSERT INTO matching_scores
                    (mentee_id, mentor_id, practice_area_match, programme_match, interest_score,
                     location_match, language_match, mentoring_style_match, total_score, algorithm_version)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $menteeId, $mentorId, $practiceAreaMatch, $programmeMatch, $interestScore,
                $locationMatch, $languageMatch, $mentoringStyleMatch, $totalScore, $version,
            ]);
        }
    }
}
