#!/usr/bin/env php
<?php
/**
 * Audit Log Archival Script
 * CUHK Law E-Mentoring Platform
 * 
 * This script should be run as a cron job (monthly recommended)
 * Archives audit logs older than 90 days to audit_logs_archive table
 * Deletes archived logs older than 2 years
 * Keeps security-critical logs indefinitely
 * 
 * Cron schedule (monthly on 1st at 2am):
 * 0 2 1 * * /usr/bin/php /path/to/archive_audit_logs.php >> /path/to/archive.log 2>&1
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Logger.php';

echo "[" . date('Y-m-d H:i:s') . "] Audit log archival process started\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // 1. Archive logs older than 90 days (exclude security-critical)
    $archiveDate = date('Y-m-d H:i:s', strtotime('-90 days'));
    
    $archiveStmt = $db->prepare("
        INSERT INTO audit_logs_archive (user_id, action, priority, entity_type, entity_id, details, ip_address, created_at)
        SELECT user_id, action, priority, entity_type, entity_id, details, ip_address, created_at
        FROM audit_logs
        WHERE created_at < ? AND priority != 'security-critical'
    ");
    $archiveStmt->execute([$archiveDate]);
    $archivedCount = $archiveStmt->rowCount();
    
    echo "[" . date('Y-m-d H:i:s') . "] Archived $archivedCount logs older than 90 days\n";
    Logger::info("Archived $archivedCount audit logs older than 90 days");
    
    // 2. Delete archived logs from main table
    if ($archivedCount > 0) {
        $deleteStmt = $db->prepare("
            DELETE FROM audit_logs
            WHERE created_at < ? AND priority != 'security-critical'
        ");
        $deleteStmt->execute([$archiveDate]);
        echo "[" . date('Y-m-d H:i:s') . "] Removed $archivedCount logs from active table\n";
    }
    
    // 3. Delete logs from archive older than 2 years
    $deleteDate = date('Y-m-d H:i:s', strtotime('-2 years'));
    
    $deleteArchiveStmt = $db->prepare("
        DELETE FROM audit_logs_archive
        WHERE created_at < ? AND priority != 'security-critical'
    ");
    $deleteArchiveStmt->execute([$deleteDate]);
    $deletedCount = $deleteArchiveStmt->rowCount();
    
    echo "[" . date('Y-m-d H:i:s') . "] Deleted $deletedCount archived logs older than 2 years\n";
    Logger::info("Deleted $deletedCount archived audit logs older than 2 years");
    
    // 4. Get statistics
    $stats = $db->query("
        SELECT 
            'active' as table_name,
            COUNT(*) as total_count,
            SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN priority = 'normal' THEN 1 ELSE 0 END) as normal_count,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count,
            SUM(CASE WHEN priority = 'security-critical' THEN 1 ELSE 0 END) as critical_count
        FROM audit_logs
        UNION ALL
        SELECT 
            'archive' as table_name,
            COUNT(*) as total_count,
            SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN priority = 'normal' THEN 1 ELSE 0 END) as normal_count,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count,
            SUM(CASE WHEN priority = 'security-critical' THEN 1 ELSE 0 END) as critical_count
        FROM audit_logs_archive
    ")->fetchAll();
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Current Statistics:\n";
    foreach ($stats as $stat) {
        echo sprintf(
            "  %s: %d total (Low: %d, Normal: %d, High: %d, Critical: %d)\n",
            ucfirst($stat['table_name']),
            $stat['total_count'],
            $stat['low_count'],
            $stat['normal_count'],
            $stat['high_count'],
            $stat['critical_count']
        );
    }
    
    // Commit transaction
    $db->commit();
    
    echo "[" . date('Y-m-d H:i:s') . "] Audit log archival completed successfully\n";
    Logger::info("Audit log archival completed: Archived $archivedCount, Deleted $deletedCount old archives");
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    Logger::error("Audit log archival failed: " . $e->getMessage());
    exit(1);
}

exit(0);
