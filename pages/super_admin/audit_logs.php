<?php
/**
 * Super Admin - Audit Logs with Priority Filtering
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requirePageAccess('super_admin_pages');

$pageTitle = 'Audit Logs';
$bodyClass = 'super-admin-audit-logs';

// Get filter parameters
$priority = isset($_GET['priority']) && in_array($_GET['priority'], ['low', 'normal', 'high', 'security-critical', 'all']) ? $_GET['priority'] : 'all';
$priorityFilter = ($priority === 'all') ? null : $priority;

$source = isset($_GET['source']) && in_array($_GET['source'], ['active', 'archive']) ? $_GET['source'] : 'active';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? min(100, max(10, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $perPage;

// Get logs and statistics
$logs = AuditLog::getAllLogs($perPage, $offset, $priorityFilter, $source);
$totalLogs = AuditLog::getTotal($priorityFilter, $source);
$totalPages = ceil($totalLogs / $perPage);
$stats = AuditLog::getStatistics($source);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Audit Logs - <?php echo ucfirst($source); ?></h2>

<div class="card">
    <a href="/pages/super_admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<!-- Priority Filter Buttons -->
<div class="card">
    <h3>Filter by Priority Level</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
        <a href="?priority=all&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>" 
           class="btn <?php echo $priority === 'all' ? 'btn-primary' : 'btn-secondary'; ?>"
           style="<?php if ($priority === 'all') echo 'background-color: #0d6efd; color: white;'; ?>">
            📊 All Logs (<?php echo $totalLogs; ?>)
        </a>
        
        <?php
        $priorityLevels = [
            AuditLog::PRIORITY_LOW,
            AuditLog::PRIORITY_NORMAL,
            AuditLog::PRIORITY_HIGH,
            AuditLog::PRIORITY_CRITICAL
        ];
        
        foreach ($priorityLevels as $level) {
            $info = AuditLog::getPriorityInfo($level);
            $count = 0;
            foreach ($stats as $stat) {
                if ($stat['priority'] === $level) {
                    $count = $stat['count'];
                    break;
                }
            }
            
            $isActive = ($priority === $level);
            $bgColor = $isActive ? $info['bg_color'] : '#f8f9fa';
            $borderColor = $info['color'];
            $textColor = $isActive ? $info['color'] : '#6c757d';
            ?>
            <a href="?priority=<?php echo $level; ?>&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>" 
               class="btn <?php echo $isActive ? 'btn-primary' : 'btn-secondary'; ?>"
               style="background-color: <?php echo $bgColor; ?>; 
                      border: 2px solid <?php echo $borderColor; ?>; 
                      color: <?php echo $textColor; ?>; 
                      font-weight: <?php echo $isActive ? 'bold' : 'normal'; ?>;"
               title="<?php echo htmlspecialchars($info['description']); ?>">
                <?php echo htmlspecialchars($info['label']); ?> (<?php echo $count; ?>)
            </a>
        <?php } ?>
    </div>
    
    <!-- Source Toggle (Active vs Archive) -->
    <div style="display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
        <strong>View:</strong>
        <a href="?priority=<?php echo $priority; ?>&source=active&per_page=<?php echo $perPage; ?>" 
           class="btn btn-sm <?php echo $source === 'active' ? 'btn-success' : 'btn-secondary'; ?>">
            📂 Active Logs (Last 90 days)
        </a>
        <a href="?priority=<?php echo $priority; ?>&source=archive&per_page=<?php echo $perPage; ?>" 
           class="btn btn-sm <?php echo $source === 'archive' ? 'btn-info' : 'btn-secondary'; ?>">
            📦 Archived Logs (Older than 90 days)
        </a>
    </div>
    
    <!-- Per Page Selector -->
    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
        <label for="perPageSelect"><strong>Rows per page:</strong></label>
        <select id="perPageSelect" onchange="window.location.href='?priority=<?php echo $priority; ?>&source=<?php echo $source; ?>&per_page=' + this.value" style="margin-left: 10px; padding: 5px;">
            <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
            <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
        </select>
        <span style="margin-left: 20px; color: #6c757d;">
            Showing <?php echo min($offset + 1, $totalLogs); ?>-<?php echo min($offset + $perPage, $totalLogs); ?> of <?php echo $totalLogs; ?> logs
        </span>
    </div>
</div>

<!-- Priority Legend -->
<div class="card" style="background-color: #f8f9fa;">
    <h4>Priority Level Descriptions:</h4>
    <ul style="list-style: none; padding: 0;">
        <?php foreach ($priorityLevels as $level) {
            $info = AuditLog::getPriorityInfo($level);
            ?>
            <li style="margin: 8px 0; padding: 8px; background-color: white; border-left: 4px solid <?php echo $info['color']; ?>; border-radius: 3px;">
                <strong style="color: <?php echo $info['color']; ?>;"><?php echo $info['label']; ?>:</strong>
                <span style="color: #6c757d;"><?php echo $info['description']; ?></span>
            </li>
        <?php } ?>
    </ul>
</div>

<div class="card">
    <?php if (empty($logs)): ?>
        <p>No audit logs found for the selected filters.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Priority</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): 
                    $priorityInfo = AuditLog::getPriorityInfo($log['priority']);
                ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                    <td>
                        <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; 
                                     background-color: <?php echo $priorityInfo['bg_color']; ?>; 
                                     color: <?php echo $priorityInfo['color']; ?>; 
                                     font-weight: bold; font-size: 0.85em;"
                              title="<?php echo htmlspecialchars($priorityInfo['description']); ?>">
                            <?php echo strtoupper($log['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        if ($log['email']) {
                            echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
                        } else {
                            echo 'System';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                    <td><?php echo htmlspecialchars($log['entity_type'] ?? 'N/A'); ?></td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <?php if ($page > 1): ?>
                    <a href="?priority=<?php echo $priority; ?>&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>&page=1" class="btn btn-secondary">« First</a>
                    <a href="?priority=<?php echo $priority; ?>&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">← Previous</a>
                <?php endif; ?>
            </div>
            
            <div>
                <span style="margin: 0 15px; font-weight: bold;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            </div>
            
            <div>
                <?php if ($page < $totalPages): ?>
                    <a href="?priority=<?php echo $priority; ?>&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next →</a>
                    <a href="?priority=<?php echo $priority; ?>&source=<?php echo $source; ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $totalPages; ?>" class="btn btn-secondary">Last »</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>

