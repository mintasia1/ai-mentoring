<?php
/**
 * Mentor Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';

class Mentor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create or update mentor profile
     */
    public function saveProfile($userId, $data) {
        // Check if profile exists
        $stmt = $this->db->prepare("SELECT id FROM mentor_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $this->db->prepare(
                "UPDATE mentor_profiles SET 
                 alumni_id = ?, graduation_year = ?, programme_level = ?, 
                 practice_area = ?, current_position = ?, company = ?, 
                 expertise = ?, interests = ?, language = ?, location = ?, 
                 bio = ?, max_mentees = ? 
                 WHERE user_id = ?"
            );
            return $stmt->execute([
                $data['alumni_id'] ?? null,
                $data['graduation_year'] ?? null,
                $data['programme_level'],
                $data['practice_area'],
                $data['current_position'] ?? null,
                $data['company'] ?? null,
                $data['expertise'] ?? null,
                $data['interests'] ?? null,
                $data['language'] ?? null,
                $data['location'] ?? null,
                $data['bio'] ?? null,
                $data['max_mentees'] ?? MAX_MENTEES_PER_MENTOR,
                $userId
            ]);
        } else {
            // Insert
            $stmt = $this->db->prepare(
                "INSERT INTO mentor_profiles 
                 (user_id, alumni_id, graduation_year, programme_level, practice_area, 
                  current_position, company, expertise, interests, language, location, 
                  bio, max_mentees) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([
                $userId,
                $data['alumni_id'] ?? null,
                $data['graduation_year'] ?? null,
                $data['programme_level'],
                $data['practice_area'],
                $data['current_position'] ?? null,
                $data['company'] ?? null,
                $data['expertise'] ?? null,
                $data['interests'] ?? null,
                $data['language'] ?? null,
                $data['location'] ?? null,
                $data['bio'] ?? null,
                $data['max_mentees'] ?? MAX_MENTEES_PER_MENTOR
            ]);
        }
    }
    
    /**
     * Get mentor profile
     */
    public function getProfile($userId) {
        $stmt = $this->db->prepare("SELECT * FROM mentor_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all available mentors (for matching)
     */
    public function getAvailableMentors($practiceArea = null) {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, mp.* 
                FROM users u 
                INNER JOIN mentor_profiles mp ON u.id = mp.user_id 
                WHERE u.status = 'active' AND mp.is_verified = 1 
                AND mp.current_mentees < mp.max_mentees";
        
        if ($practiceArea) {
            $sql .= " AND mp.practice_area = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$practiceArea]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Check if mentor has capacity
     */
    public function hasCapacity($userId) {
        $stmt = $this->db->prepare(
            "SELECT current_mentees, max_mentees FROM mentor_profiles WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        return $profile && $profile['current_mentees'] < $profile['max_mentees'];
    }
    
    /**
     * Update mentee count
     */
    public function updateMenteeCount($userId) {
        $stmt = $this->db->prepare(
            "UPDATE mentor_profiles SET current_mentees = (
                SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'active'
            ) WHERE user_id = ?"
        );
        return $stmt->execute([$userId, $userId]);
    }
}
?>
