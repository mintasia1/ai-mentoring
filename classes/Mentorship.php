<?php
/**
 * Mentorship Management Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mentor.php';
require_once __DIR__ . '/Mentee.php';
require_once __DIR__ . '/AuditLog.php';

class Mentorship {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create mentorship request
     */
    public function createRequest($menteeId, $mentorId, $message = null) {
        // Check if mentee already has active request to this mentor
        $stmt = $this->db->prepare(
            "SELECT id FROM mentorship_requests 
             WHERE mentee_id = ? AND mentor_id = ? AND status = 'pending'"
        );
        $stmt->execute([$menteeId, $mentorId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Request already pending'];
        }
        
        // Check if mentor has capacity
        $mentorClass = new Mentor();
        if (!$mentorClass->hasCapacity($mentorId)) {
            return ['success' => false, 'message' => 'Mentor has reached maximum capacity'];
        }
        
        // Create request
        $stmt = $this->db->prepare(
            "INSERT INTO mentorship_requests (mentee_id, mentor_id, message) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$menteeId, $mentorId, $message]);
        $requestId = $this->db->lastInsertId();
        
        AuditLog::log($menteeId, 'request_created', 'mentorship_requests', $requestId, 
                      "Request sent to mentor ID: $mentorId");
        
        return ['success' => true, 'request_id' => $requestId, 'message' => 'Request sent successfully'];
    }
    
    /**
     * Get mentorship requests for a mentor
     */
    public function getMentorRequests($mentorId, $status = null) {
        $sql = "SELECT mr.*, u.first_name, u.last_name, u.email, mp.* 
                FROM mentorship_requests mr 
                INNER JOIN users u ON mr.mentee_id = u.id 
                LEFT JOIN mentee_profiles mp ON u.id = mp.user_id 
                WHERE mr.mentor_id = ?";
        
        if ($status) {
            $sql .= " AND mr.status = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY mr.requested_at DESC");
            $stmt->execute([$mentorId, $status]);
        } else {
            $stmt = $this->db->prepare($sql . " ORDER BY mr.requested_at DESC");
            $stmt->execute([$mentorId]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get mentorship requests from a mentee
     */
    public function getMenteeRequests($menteeId, $status = null) {
        $sql = "SELECT mr.*, u.first_name, u.last_name, u.email, mp.* 
                FROM mentorship_requests mr 
                INNER JOIN users u ON mr.mentor_id = u.id 
                LEFT JOIN mentor_profiles mp ON u.id = mp.user_id 
                WHERE mr.mentee_id = ?";
        
        if ($status) {
            $sql .= " AND mr.status = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY mr.requested_at DESC");
            $stmt->execute([$menteeId, $status]);
        } else {
            $stmt = $this->db->prepare($sql . " ORDER BY mr.requested_at DESC");
            $stmt->execute([$menteeId]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Accept mentorship request
     */
    public function acceptRequest($requestId, $mentorId, $response = null) {
        // Get request
        $stmt = $this->db->prepare("SELECT * FROM mentorship_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request || $request['mentor_id'] != $mentorId) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        if ($request['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Request already processed'];
        }
        
        // Update request
        $stmt = $this->db->prepare(
            "UPDATE mentorship_requests 
             SET status = 'accepted', mentor_response = ?, responded_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$response, $requestId]);
        
        // Create mentorship
        $stmt = $this->db->prepare(
            "INSERT INTO mentorships (mentee_id, mentor_id, request_id) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$request['mentee_id'], $mentorId, $requestId]);
        
        // Update mentor mentee count
        $mentorClass = new Mentor();
        $mentorClass->updateMenteeCount($mentorId);
        
        AuditLog::log($mentorId, 'request_accepted', 'mentorship_requests', $requestId, 
                      "Accepted request from mentee ID: {$request['mentee_id']}");
        
        return ['success' => true, 'message' => 'Request accepted'];
    }
    
    /**
     * Decline mentorship request
     */
    public function declineRequest($requestId, $mentorId, $response = null) {
        // Get request
        $stmt = $this->db->prepare("SELECT * FROM mentorship_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request || $request['mentor_id'] != $mentorId) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        if ($request['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Request already processed'];
        }
        
        // Update request
        $stmt = $this->db->prepare(
            "UPDATE mentorship_requests 
             SET status = 'declined', mentor_response = ?, responded_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$response, $requestId]);
        
        // Grant rematch opportunity
        $menteeClass = new Mentee();
        if ($menteeClass->canRequestRematch($request['mentee_id'])) {
            $menteeClass->incrementRematchCount($request['mentee_id']);
        }
        
        AuditLog::log($mentorId, 'request_declined', 'mentorship_requests', $requestId, 
                      "Declined request from mentee ID: {$request['mentee_id']}");
        
        return ['success' => true, 'message' => 'Request declined'];
    }
    
    /**
     * Get active mentorships for a user
     */
    public function getActiveMentorships($userId, $role) {
        if ($role === 'mentee') {
            $sql = "SELECT m.*, u.first_name, u.last_name, u.email, mp.* 
                    FROM mentorships m 
                    INNER JOIN users u ON m.mentor_id = u.id 
                    LEFT JOIN mentor_profiles mp ON u.id = mp.user_id 
                    WHERE m.mentee_id = ? AND m.status = 'active'";
        } else {
            $sql = "SELECT m.*, u.first_name, u.last_name, u.email, mp.* 
                    FROM mentorships m 
                    INNER JOIN users u ON m.mentee_id = u.id 
                    LEFT JOIN mentee_profiles mp ON u.id = mp.user_id 
                    WHERE m.mentor_id = ? AND m.status = 'active'";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
