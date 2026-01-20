-- Migration: Add priority to audit logs and create archive table
-- Date: 2026-01-20
-- Purpose: Implement hybrid archival system for audit log management

-- Add priority column to existing audit_logs table
ALTER TABLE audit_logs 
ADD COLUMN priority ENUM('low', 'normal', 'high', 'security-critical') DEFAULT 'normal' AFTER action,
ADD INDEX idx_priority (priority);

-- Create audit_logs_archive table (same structure as audit_logs)
CREATE TABLE IF NOT EXISTS audit_logs_archive (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT,
   action VARCHAR(255) NOT NULL,
   priority ENUM('low', 'normal', 'high', 'security-critical') DEFAULT 'normal',
   entity_type VARCHAR(50),
   entity_id INT,
   details TEXT,
   ip_address VARCHAR(45),
   created_at TIMESTAMP NOT NULL,
   archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
   INDEX idx_user (user_id),
   INDEX idx_action (action),
   INDEX idx_priority (priority),
   INDEX idx_created (created_at),
   INDEX idx_archived (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment explaining priority levels
ALTER TABLE audit_logs COMMENT = 'Active audit logs. Low/normal: user actions. High: admin actions. Security-critical: auth failures, permission changes, deletions';
ALTER TABLE audit_logs_archive COMMENT = 'Archived audit logs older than 90 days (except security-critical which are kept indefinitely)';
