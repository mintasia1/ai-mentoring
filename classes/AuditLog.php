<?php
/**
 * Audit Log Class
 * CUHK Law E-Mentoring Platform
 * 
 * Priority Levels:
 * - low: General user actions (profile views, searches)
 * - normal: Standard operations (profile updates, requests)
 * - high: Administrative actions (user management, verifications)
 * - security-critical: Security events (auth failures, role changes, deletions)
 */

require_once __DIR__ . '/Database.php';

class AuditLog {
    // Priority level constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'security-critical';
    
    /**
     * Log an action with priority level
     */
    public static function log($userId, $action, $entityType = null, $entityId = null, $details = null, $priority = self::PRIORITY_NORMAL) {
        try {
            $db = Database::getInstance()->getConnection();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt = $db->prepare(
                "INSERT INTO audit_logs (user_id, action, priority, entity_type, entity_id, details, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $action, $priority, $entityType, $entityId, $details, $ipAddress]);
            
            return true;
        } catch(PDOException $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get logs for a user with optional priority filter
     */
    public static function getUserLogs($userId, $limit = 50, $priority = null) {
        $db = Database::getInstance()->getConnection();
        
        if ($priority) {
            $stmt = $db->prepare(
                "SELECT * FROM audit_logs WHERE user_id = ? AND priority = ? ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->execute([$userId, $priority, $limit]);
        } else {
            $stmt = $db->prepare(
                "SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->execute([$userId, $limit]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get all logs with optional priority filter and source (active or archive)
     */
    public static function getAllLogs($limit = 100, $offset = 0, $priority = null, $source = 'active') {
        $db = Database::getInstance()->getConnection();
        $table = ($source === 'archive') ? 'audit_logs_archive' : 'audit_logs';
        
        if ($priority) {
            $stmt = $db->prepare(
                "SELECT al.*, u.email, u.first_name, u.last_name 
                 FROM $table al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 WHERE al.priority = ?
                 ORDER BY al.created_at DESC 
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$priority, $limit, $offset]);
        } else {
            $stmt = $db->prepare(
                "SELECT al.*, u.email, u.first_name, u.last_name 
                 FROM $table al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 ORDER BY al.created_at DESC 
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of logs with optional priority filter and source
     */
    public static function getTotal($priority = null, $source = 'active') {
        $db = Database::getInstance()->getConnection();
        $table = ($source === 'archive') ? 'audit_logs_archive' : 'audit_logs';
        
        if ($priority) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE priority = ?");
            $stmt->execute([$priority]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        }
        
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Get statistics by priority
     */
    public static function getStatistics($source = 'active') {
        $db = Database::getInstance()->getConnection();
        $table = ($source === 'archive') ? 'audit_logs_archive' : 'audit_logs';
        
        $stmt = $db->query("
            SELECT 
                priority,
                COUNT(*) as count,
                MIN(created_at) as oldest,
                MAX(created_at) as newest
            FROM $table
            GROUP BY priority
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Archive old logs (called by cron script)
     */
    public static function archiveOldLogs($daysOld = 90) {
        $db = Database::getInstance()->getConnection();
        $archiveDate = date('Y-m-d H:i:s', strtotime("-$daysOld days"));
        
        try {
            $db->beginTransaction();
            
            // Archive logs older than specified days (exclude security-critical)
            $stmt = $db->prepare("
                INSERT INTO audit_logs_archive (user_id, action, priority, entity_type, entity_id, details, ip_address, created_at)
                SELECT user_id, action, priority, entity_type, entity_id, details, ip_address, created_at
                FROM audit_logs
                WHERE created_at < ? AND priority != ?
            ");
            $stmt->execute([$archiveDate, self::PRIORITY_CRITICAL]);
            $archivedCount = $stmt->rowCount();
            
            // Delete archived logs from main table
            if ($archivedCount > 0) {
                $deleteStmt = $db->prepare("
                    DELETE FROM audit_logs
                    WHERE created_at < ? AND priority != ?
                ");
                $deleteStmt->execute([$archiveDate, self::PRIORITY_CRITICAL]);
            }
            
            $db->commit();
            return $archivedCount;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Audit log archival failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old archived logs (called by cron script)
     */
    public static function deleteOldArchived($yearsOld = 2) {
        $db = Database::getInstance()->getConnection();
        $deleteDate = date('Y-m-d H:i:s', strtotime("-$yearsOld years"));
        
        try {
            $stmt = $db->prepare("
                DELETE FROM audit_logs_archive
                WHERE created_at < ? AND priority != ?
            ");
            $stmt->execute([$deleteDate, self::PRIORITY_CRITICAL]);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Audit log deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get priority level description and color
     */
    public static function getPriorityInfo($priority) {
        $info = [
            self::PRIORITY_LOW => [
                'label' => 'Low Priority',
                'description' => 'General user actions: profile views, searches, navigation',
                'color' => '#6c757d', // gray
                'bg_color' => '#e9ecef'
            ],
            self::PRIORITY_NORMAL => [
                'label' => 'Normal Priority',
                'description' => 'Standard operations: profile updates, requests, messages',
                'color' => '#0d6efd', // blue
                'bg_color' => '#cfe2ff'
            ],
            self::PRIORITY_HIGH => [
                'label' => 'High Priority',
                'description' => 'Administrative actions: user management, verifications, role changes',
                'color' => '#fd7e14', // orange
                'bg_color' => '#ffe5d0'
            ],
            self::PRIORITY_CRITICAL => [
                'label' => 'Security Critical',
                'description' => 'Security events: auth failures, permission changes, deletions, suspicious activity',
                'color' => '#dc3545', // red
                'bg_color' => '#f8d7da'
            ]
        ];
        
        return $info[$priority] ?? $info[self::PRIORITY_NORMAL];
    }
}
