<?php
/**
 * Audit Log Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';

class AuditLog {
    /**
     * Log an action
     */
    public static function log($userId, $action, $entityType = null, $entityId = null, $details = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt = $db->prepare(
                "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ipAddress]);
            
            return true;
        } catch(PDOException $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get logs for a user
     */
    public static function getUserLogs($userId, $limit = 50) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all logs (for super admin)
     */
    public static function getAllLogs($limit = 100, $offset = 0) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT al.*, u.email, u.first_name, u.last_name 
             FROM audit_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC 
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of logs
     */
    public static function getTotal() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs");
        $result = $stmt->fetch();
        return $result['count'];
    }
}
