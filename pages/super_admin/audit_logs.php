<?php
/**
 * Super Admin - Audit Logs
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole('super_admin');

$pageTitle = 'Audit Logs';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$logs = AuditLog::getAllLogs($perPage, $offset);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Audit Logs</h2>

<div class="card">
    <a href="/pages/super_admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<div class="card">
    <?php if (empty($logs)): ?>
        <p>No audit logs found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
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
        
        <div style="margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">← Previous</a>
            <?php endif; ?>
            <span style="margin: 0 20px;">Page <?php echo $page; ?></span>
            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next →</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
