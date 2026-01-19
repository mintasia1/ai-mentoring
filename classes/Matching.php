<?php
/**
 * Matching Algorithm Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mentor.php';

class Matching {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Calculate matching score between mentee and mentor
     */
    public function calculateMatchScore($menteeId, $mentorId) {
        // Get mentee profile
        $stmt = $this->db->prepare("SELECT * FROM mentee_profiles WHERE user_id = ?");
        $stmt->execute([$menteeId]);
        $mentee = $stmt->fetch();
        
        // Get mentor profile
        $stmt = $this->db->prepare("SELECT * FROM mentor_profiles WHERE user_id = ?");
        $stmt->execute([$mentorId]);
        $mentor = $stmt->fetch();
        
        if (!$mentee || !$mentor) {
            return 0;
        }
        
        $score = 0;
        $weights = [
            'practice_area' => 40,  // Mandatory hard filter
            'programme' => 20,
            'interests' => 15,
            'location' => 15,
            'language' => 10
        ];
        
        // Practice area match (mandatory)
        $practiceAreaMatch = false;
        if ($mentee['practice_area_preference'] && 
            $mentee['practice_area_preference'] === $mentor['practice_area']) {
            $score += $weights['practice_area'];
            $practiceAreaMatch = true;
        }
        
        // Programme level match
        $programmeMatch = false;
        if ($mentee['programme_level'] === $mentor['programme_level']) {
            $score += $weights['programme'];
            $programmeMatch = true;
        }
        
        // Interests similarity
        $interestScore = 0;
        if ($mentee['interests'] && $mentor['interests']) {
            $interestScore = $this->calculateTextSimilarity(
                $mentee['interests'], 
                $mentor['interests']
            );
            $score += $interestScore * $weights['interests'];
        }
        
        // Location match
        $locationMatch = false;
        if ($mentee['location'] && $mentor['location'] && 
            strtolower($mentee['location']) === strtolower($mentor['location'])) {
            $score += $weights['location'];
            $locationMatch = true;
        }
        
        // Language match
        $languageMatch = false;
        if ($mentee['language_preference'] && $mentor['language'] && 
            strtolower($mentee['language_preference']) === strtolower($mentor['language'])) {
            $score += $weights['language'];
            $languageMatch = true;
        }
        
        // Save matching score
        $this->saveMatchingScore(
            $menteeId, 
            $mentorId, 
            $practiceAreaMatch, 
            $programmeMatch, 
            $interestScore, 
            $locationMatch, 
            $languageMatch, 
            $score
        );
        
        return $score;
    }
    
    /**
     * Calculate text similarity (simple word overlap)
     */
    private function calculateTextSimilarity($text1, $text2) {
        $words1 = array_map('strtolower', preg_split('/\W+/', $text1));
        $words2 = array_map('strtolower', preg_split('/\W+/', $text2));
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        if (count($union) === 0) return 0;
        
        return count($intersection) / count($union);
    }
    
    /**
     * Save matching score to database
     */
    private function saveMatchingScore($menteeId, $mentorId, $practiceAreaMatch, 
                                       $programmeMatch, $interestScore, $locationMatch, 
                                       $languageMatch, $totalScore) {
        // Check if score exists
        $stmt = $this->db->prepare(
            "SELECT id FROM matching_scores WHERE mentee_id = ? AND mentor_id = ?"
        );
        $stmt->execute([$menteeId, $mentorId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $this->db->prepare(
                "UPDATE matching_scores SET 
                 practice_area_match = ?, programme_match = ?, interest_score = ?, 
                 location_match = ?, language_match = ?, total_score = ?, 
                 calculated_at = NOW() 
                 WHERE mentee_id = ? AND mentor_id = ?"
            );
            $stmt->execute([
                $practiceAreaMatch, $programmeMatch, $interestScore, 
                $locationMatch, $languageMatch, $totalScore,
                $menteeId, $mentorId
            ]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO matching_scores 
                 (mentee_id, mentor_id, practice_area_match, programme_match, 
                  interest_score, location_match, language_match, total_score) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $menteeId, $mentorId, $practiceAreaMatch, $programmeMatch, 
                $interestScore, $locationMatch, $languageMatch, $totalScore
            ]);
        }
    }
    
    /**
     * Get recommended mentors for a mentee
     */
    public function getRecommendedMentors($menteeId, $limit = 10) {
        // Get mentee's practice area preference
        $stmt = $this->db->prepare(
            "SELECT practice_area_preference FROM mentee_profiles WHERE user_id = ?"
        );
        $stmt->execute([$menteeId]);
        $mentee = $stmt->fetch();
        
        // Get available mentors
        $mentorClass = new Mentor();
        $mentors = $mentorClass->getAvailableMentors($mentee['practice_area_preference']);
        
        // Calculate scores for all mentors
        $scoredMentors = [];
        foreach ($mentors as $mentor) {
            $score = $this->calculateMatchScore($menteeId, $mentor['user_id']);
            $mentor['match_score'] = $score;
            $scoredMentors[] = $mentor;
        }
        
        // Sort by score
        usort($scoredMentors, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($scoredMentors, 0, $limit);
    }
}
