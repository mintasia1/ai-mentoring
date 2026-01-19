<?php
/**
 * Mentee Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';

class Mentee {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create or update mentee profile
     */
    public function saveProfile($userId, $data) {
        // Check if profile exists
        $stmt = $this->db->prepare("SELECT id FROM mentee_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $this->db->prepare(
                "UPDATE mentee_profiles SET 
                 student_id = ?, programme_level = ?, year_of_study = ?, 
                 interests = ?, goals = ?, practice_area_preference = ?, 
                 language_preference = ?, location = ?, bio = ? 
                 WHERE user_id = ?"
            );
            return $stmt->execute([
                $data['student_id'] ?? null,
                $data['programme_level'],
                $data['year_of_study'] ?? null,
                $data['interests'] ?? null,
                $data['goals'] ?? null,
                $data['practice_area_preference'] ?? null,
                $data['language_preference'] ?? null,
                $data['location'] ?? null,
                $data['bio'] ?? null,
                $userId
            ]);
        } else {
            // Insert
            $stmt = $this->db->prepare(
                "INSERT INTO mentee_profiles 
                 (user_id, student_id, programme_level, year_of_study, interests, goals, 
                  practice_area_preference, language_preference, location, bio) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([
                $userId,
                $data['student_id'] ?? null,
                $data['programme_level'],
                $data['year_of_study'] ?? null,
                $data['interests'] ?? null,
                $data['goals'] ?? null,
                $data['practice_area_preference'] ?? null,
                $data['language_preference'] ?? null,
                $data['location'] ?? null,
                $data['bio'] ?? null
            ]);
        }
    }
    
    /**
     * Get mentee profile
     */
    public function getProfile($userId) {
        $stmt = $this->db->prepare("SELECT * FROM mentee_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get rematch count
     */
    public function getRematchCount($userId) {
        $stmt = $this->db->prepare("SELECT rematch_count FROM mentee_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['rematch_count'] : 0;
    }
    
    /**
     * Increment rematch count
     */
    public function incrementRematchCount($userId) {
        $stmt = $this->db->prepare(
            "UPDATE mentee_profiles SET rematch_count = rematch_count + 1 WHERE user_id = ?"
        );
        return $stmt->execute([$userId]);
    }
    
    /**
     * Check if can request rematch
     */
    public function canRequestRematch($userId) {
        $count = $this->getRematchCount($userId);
        return $count < REMATCH_LIMIT;
    }
    
    /**
     * Get mentees with user info (for admin management)
     */
    public function getMenteesWithUserInfo($limit = 30, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.status, u.role,
                    mp.created_at, mp.updated_at
             FROM users u 
             INNER JOIN mentee_profiles mp ON u.id = mp.user_id 
             ORDER BY mp.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all mentees with full profile info
     */
    public function getAllMentees() {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.status, mp.* 
                FROM users u 
                INNER JOIN mentee_profiles mp ON u.id = mp.user_id 
                WHERE u.status = 'active'
                ORDER BY mp.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get mentee statistics
     */
    public function getStatistics() {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN u.status = 'disabled' THEN 1 ELSE 0 END) as disabled
             FROM mentee_profiles mp
             INNER JOIN users u ON mp.user_id = u.id"
        );
        return $stmt->fetch();
    }
}
