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
    
    /**
     * Get all mentors with profiles (for admin)
     */
    public function getAllMentors($filter = 'all') {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.status, mp.* 
                FROM users u 
                INNER JOIN mentor_profiles mp ON u.id = mp.user_id";
        
        if ($filter === 'verified') {
            $sql .= " WHERE mp.is_verified = 1";
        } elseif ($filter === 'pending') {
            $sql .= " WHERE mp.is_verified = 0";
        }
        
        $sql .= " ORDER BY mp.is_verified ASC, mp.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Verify a mentor
     */
    public function verifyMentor($userId) {
        $stmt = $this->db->prepare(
            "UPDATE mentor_profiles 
             SET is_verified = 1, verification_date = NOW() 
             WHERE user_id = ?"
        );
        return $stmt->execute([$userId]);
    }
    
    /**
     * Unverify a mentor
     */
    public function unverifyMentor($userId) {
        $stmt = $this->db->prepare(
            "UPDATE mentor_profiles 
             SET is_verified = 0, verification_date = NULL 
             WHERE user_id = ?"
        );
        return $stmt->execute([$userId]);
    }
    
    /**
     * Get mentor statistics
     */
    public function getStatistics() {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as pending
             FROM mentor_profiles mp
             INNER JOIN users u ON mp.user_id = u.id
             WHERE u.status = 'active'"
        );
        return $stmt->fetch();
    }
    
    /**
     * Get mentors with user info (for admin management)
     */
    public function getMentorsWithUserInfo($limit = 30, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.status, u.role,
                    mp.is_verified, mp.verification_date, mp.created_at, mp.updated_at
             FROM users u 
             INNER JOIN mentor_profiles mp ON u.id = mp.user_id 
             ORDER BY mp.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
