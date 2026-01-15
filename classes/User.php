<?php
/**
 * User Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->db->prepare(
            "SELECT id, email, role, first_name, last_name, status, created_at, last_login 
             FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get user profile based on role
     */
    public function getUserProfile($userId) {
        $user = $this->getUserById($userId);
        if (!$user) return null;
        
        if ($user['role'] === 'mentee') {
            $stmt = $this->db->prepare("SELECT * FROM mentee_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            $user['profile'] = $profile;
        } elseif ($user['role'] === 'mentor') {
            $stmt = $this->db->prepare("SELECT * FROM mentor_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            $user['profile'] = $profile;
        }
        
        return $user;
    }
    
    /**
     * Update user basic info
     */
    public function updateUser($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'status'];
        $updates = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get all users (for admin)
     */
    public function getAllUsers($role = null, $limit = 50, $offset = 0) {
        if ($role) {
            $stmt = $this->db->prepare(
                "SELECT id, email, role, first_name, last_name, status, created_at, last_login 
                 FROM users WHERE role = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
            );
            $stmt->execute([$role, $limit, $offset]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, email, role, first_name, last_name, status, created_at, last_login 
                 FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * Count users
     */
    public function countUsers($role = null) {
        if ($role) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        }
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>
