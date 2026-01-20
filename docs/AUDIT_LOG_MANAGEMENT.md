# Audit Log Management System

## Overview

The CUHK Law E-Mentoring Platform implements a hybrid archival system for audit logs to prevent database bloat while maintaining compliance and security monitoring capabilities.

## Priority Levels

The system uses 4 priority levels to categorize audit log entries:

### 1. Low Priority (Gray)
**Examples:**
- Profile views
- Page navigation
- Search queries
- General browsing

**Retention:** 90 days in active table, 2 years in archive

### 2. Normal Priority (Blue)
**Examples:**
- Profile updates
- Mentorship request submissions
- Message sending
- Goal creation

**Retention:** 90 days in active table, 2 years in archive

### 3. High Priority (Orange)
**Examples:**
- User verification by admin
- Role assignments
- Admin actions (enable/disable users)
- Mentorship status changes

**Retention:** 90 days in active table, 2 years in archive

### 4. Security-Critical (Red)
**Examples:**
- Authentication failures
- Role changes
- User deletions
- Permission violations
- Suspicious activity
- CSRF/rate limit violations

**Retention:** **INDEFINITE** (never archived or deleted)

## Architecture

### Tables

1. **audit_logs** - Active logs (last 90 days + all security-critical)
2. **audit_logs_archive** - Archived logs (90 days to 2 years old, excludes security-critical)

### Archival Strategy

**Option A+B Hybrid (Implemented):**
- Archive logs older than 90 days (except security-critical) to `audit_logs_archive`
- Delete archived logs older than 2 years (except security-critical)
- Keep all security-critical logs indefinitely in `audit_logs` table
- Run monthly via cron job

## Implementation

### 1. Database Migration

Run the migration to add priority column and create archive table:

```sql
mysql -u username -p database_name < database/migrations/add_audit_log_priority_and_archive.sql
```

### 2. Logging with Priority

Update your code to specify priority when logging actions:

```php
// Low priority - general user action
AuditLog::log($userId, 'profile_viewed', 'user', $profileId, null, AuditLog::PRIORITY_LOW);

// Normal priority - standard operation
AuditLog::log($userId, 'profile_updated', 'profile', $profileId, 'Updated bio', AuditLog::PRIORITY_NORMAL);

// High priority - admin action
AuditLog::log($adminId, 'user_verified', 'mentor', $mentorId, null, AuditLog::PRIORITY_HIGH);

// Security-critical - security event
AuditLog::log($userId, 'login_failed', 'auth', null, 'Invalid password', AuditLog::PRIORITY_CRITICAL);
```

### 3. Automated Archival (Cron Job)

Set up a monthly cron job to run the archival script:

```bash
# Edit crontab
crontab -e

# Add this line (runs on 1st of month at 2 AM)
0 2 1 * * /usr/bin/php /path/to/ai-mentoring/scripts/archive_audit_logs.php >> /var/log/audit_archive.log 2>&1
```

Or run manually:

```bash
php scripts/archive_audit_logs.php
```

### 4. Manual Archival via Code

You can also trigger archival programmatically:

```php
// Archive logs older than 90 days
$archivedCount = AuditLog::archiveOldLogs(90);

// Delete archived logs older than 2 years
$deletedCount = AuditLog::deleteOldArchived(2);
```

## Usage in Audit Log Viewer

### Filtering by Priority

Navigate to `/pages/super_admin/audit_logs.php` and use the filter buttons:

- **All Logs** - View all audit log entries
- **Low Priority** - General user actions (gray badge)
- **Normal Priority** - Standard operations (blue badge)
- **High Priority** - Admin actions (orange badge)
- **Security Critical** - Security events (red badge)

### Viewing Active vs Archived Logs

- **Active Logs** - Last 90 days + all security-critical events
- **Archived Logs** - 90 days to 2 years old (excludes security-critical)

## Statistics and Monitoring

### View Statistics

```php
// Get statistics for active logs
$stats = AuditLog::getStatistics('active');

// Get statistics for archived logs
$stats = AuditLog::getStatistics('archive');

// Get total count by priority
$criticalCount = AuditLog::getTotal(AuditLog::PRIORITY_CRITICAL, 'active');
```

### Database Size Management

Expected database size with 10,000 mentees, 5,000 mentors, 100 admins:

**Before Archival:**
- ~1.5-2GB per year (without management)
- Could grow to 10GB+ over 5+ years

**With Hybrid Archival:**
- Active table: ~200-500MB (90 days + security-critical)
- Archive table: ~500MB-1GB (90 days to 2 years)
- Total managed size: ~700MB-1.5GB
- **80-90% reduction in active table size**

## Best Practices

### 1. Consistent Priority Assignment

**Low Priority:**
- Read-only operations
- Navigation
- Viewing content

**Normal Priority:**
- Create/update operations
- User-initiated actions
- Standard workflow

**High Priority:**
- Administrative actions
- User management
- Critical operations

**Security-Critical:**
- Authentication events
- Authorization failures
- Data deletion
- Role/permission changes
- Suspicious patterns

### 2. Monitoring Security-Critical Logs

Set up alerts for:
- Multiple login failures from same IP
- Unexpected role changes
- User deletions
- Permission violations

### 3. Regular Review

- Monthly: Review security-critical logs
- Quarterly: Audit high-priority actions
- Annually: Review archival process effectiveness

### 4. Backup Strategy

```bash
# Backup before archival
mysqldump -u username -p database_name audit_logs > backup_audit_logs_$(date +%Y%m%d).sql
mysqldump -u username -p database_name audit_logs_archive > backup_audit_archive_$(date +%Y%m%d).sql
```

## Performance Optimization

### Indexes

The following indexes are automatically created:
- `idx_priority` - Fast filtering by priority level
- `idx_created` - Fast date-based queries
- `idx_user` - Fast user-specific lookups
- `idx_action` - Fast action type filtering

### Partitioning (Optional for Large Scale)

For deployments with millions of logs, consider table partitioning:

```sql
ALTER TABLE audit_logs_archive
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION pFuture VALUES LESS THAN MAXVALUE
);
```

## Troubleshooting

### Issue: Archival script fails

**Solution:** Check database connection and permissions

```bash
php scripts/archive_audit_logs.php
# Check error output
```

### Issue: Archive table grows too large

**Solution:** Reduce retention period or increase deletion frequency

```php
// Delete archives older than 1 year instead of 2
AuditLog::deleteOldArchived(1);
```

### Issue: Too many security-critical logs

**Solution:** Review and reclassify logs that shouldn't be critical

## Future Enhancements

- **Export to external log service** (Splunk, ELK, CloudWatch)
- **Automated anomaly detection** for security-critical events
- **Dashboard with charts** showing log trends
- **Email alerts** for high-frequency security events
- **Compressed archive storage** for older logs

## Support

For questions or issues:
1. Check application logs: `/log/php_error.log`
2. Review audit archive logs: Check cron job output
3. Contact system administrator

---

Last Updated: 2026-01-20
Version: 1.0.0
