<?php
/**
 * Mentor Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class Mentor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create or update mentor profile
     */
    public function saveProfile($userId, $data) {
        Logger::debug("Saving mentor profile", ['user_id' => $userId]);
        
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
            $result = $stmt->execute([
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
            if ($result) {
                Logger::info("Mentor profile updated", ['user_id' => $userId]);
            }
            return $result;
        } else {
            // Insert
            $stmt = $this->db->prepare(
                "INSERT INTO mentor_profiles 
                 (user_id, alumni_id, graduation_year, programme_level, practice_area, 
                  current_position, company, expertise, interests, language, location, 
                  bio, max_mentees) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $result = $stmt->execute([
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
            if ($result) {
                Logger::info("Mentor profile created", ['user_id' => $userId]);
            }
            return $result;
        }
    }
    
    /**
     * Get mentor profile
     */
    public function getProfile($userId) {
        $stmt = $this->db->prepare(
            "SELECT u.id AS user_id, u.first_name, u.last_name, u.email,
                    mp.user_id, mp.alumni_id, mp.graduation_year, mp.programme_level,
                    mp.practice_area, mp.current_position, mp.company, mp.expertise,
                    mp.interests, mp.language, mp.location, mp.bio, mp.max_mentees,
                    mp.current_mentees, mp.is_verified, mp.verification_date,
                    mp.created_at, mp.updated_at
             FROM users u
             INNER JOIN mentor_profiles mp ON u.id = mp.user_id
             WHERE u.id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all available mentors (for matching)
     */
    public function getAvailableMentors($practiceArea = null) {
        $sql = "SELECT u.id AS user_id, u.first_name, u.last_name, u.email, 
                       mp.user_id, mp.alumni_id, mp.graduation_year, mp.programme_level, 
                       mp.practice_area, mp.current_position, mp.company, mp.expertise, 
                       mp.interests, mp.language, mp.location, mp.bio, mp.max_mentees, 
                       mp.current_mentees, mp.is_verified, mp.verification_date, 
                       mp.created_at, mp.updated_at
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
        // Only show users who have mentor profiles (exclude users who only have mentee profiles)
        $sql = "SELECT u.id AS user_id, u.first_name, u.last_name, u.email, u.status, 
                       mp.user_id, mp.alumni_id, mp.graduation_year, mp.programme_level, 
                       mp.practice_area, mp.current_position, mp.company, mp.expertise, 
                       mp.interests, mp.language, mp.location, mp.bio, mp.max_mentees, 
                       mp.current_mentees, mp.is_verified, mp.verification_date, 
                       mp.created_at, mp.updated_at
                FROM users u 
                INNER JOIN mentor_profiles mp ON u.id = mp.user_id
                WHERE 1=1";
        
        if ($filter === 'verified') {
            $sql .= " AND mp.is_verified = 1";
        } elseif ($filter === 'pending') {
            $sql .= " AND mp.is_verified = 0";
        }
        
        $sql .= " ORDER BY mp.is_verified ASC, mp.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Verify a mentor
     */
    public function verifyMentor($userId) {
        Logger::debug("Verifying mentor", ['user_id' => $userId]);
        $stmt = $this->db->prepare(
            "UPDATE mentor_profiles 
             SET is_verified = 1, verification_date = NOW() 
             WHERE user_id = ?"
        );
        $result = $stmt->execute([$userId]);
        if ($result) {
            Logger::info("Mentor verified successfully", ['user_id' => $userId]);
        }
        return $result;
    }
    
    /**
     * Unverify a mentor
     */
    public function unverifyMentor($userId) {
        Logger::debug("Unverifying mentor", ['user_id' => $userId]);
        $stmt = $this->db->prepare(
            "UPDATE mentor_profiles 
             SET is_verified = 0, verification_date = NULL 
             WHERE user_id = ?"
        );
        $result = $stmt->execute([$userId]);
        if ($result) {
            Logger::info("Mentor unverified", ['user_id' => $userId]);
        }
        return $result;
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
