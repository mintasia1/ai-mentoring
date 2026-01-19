<?php
/**
 * Super Admin Dashboard
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole('super_admin');

$pageTitle = 'Super Admin Dashboard';
$userId = Auth::getCurrentUserId();

$userClass = new User();

// Get statistics
$totalUsers = $userClass->countUsers();
$totalMentees = $userClass->countUsers('mentee');
$totalMentors = $userClass->countUsers('mentor');
$totalAdmins = $userClass->countUsers('admin');
$totalSuperAdmins = $userClass->countUsers('super_admin');

// Get recent audit logs
$recentLogs = AuditLog::getAllLogs(20);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Super Admin Dashboard</h2>

<div class="card">
    <h3>System Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $totalUsers; ?></h2>
            <p style="margin: 5px 0 0 0;">Total Users</p>
        </div>
        <a href="/pages/admin/manage_mentees.php" style="text-decoration: none;">
            <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $totalMentees; ?></h2>
                <p style="margin: 5px 0 0 0;">Mentees</p>
            </div>
        </a>
        <a href="/pages/admin/manage_mentors.php" style="text-decoration: none;">
            <div style="background: #f39c12; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $totalMentors; ?></h2>
                <p style="margin: 5px 0 0 0;">Mentors</p>
            </div>
        </a>
        <div style="background: #9b59b6; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $totalAdmins; ?></h2>
            <p style="margin: 5px 0 0 0;">Admins</p>
        </div>
        <div style="background: #e74c3c; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $totalSuperAdmins; ?></h2>
            <p style="margin: 5px 0 0 0;">Super Admins</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Quick Actions</h3>
    <a href="/pages/super_admin/users.php" class="btn">Manage All Users</a>
    <a href="/pages/super_admin/admins.php" class="btn btn-secondary">Manage Admins</a>
    <a href="/pages/super_admin/audit_logs.php" class="btn btn-secondary">View Audit Logs</a>
    <a href="/pages/super_admin/system_config.php" class="btn btn-secondary">System Configuration</a>
</div>

<div class="card">
    <h3>Recent Activity (Audit Logs)</h3>
    <?php if (empty($recentLogs)): ?>
        <p>No recent activity.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
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
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 50)); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="/pages/super_admin/audit_logs.php" class="btn btn-secondary" style="margin-top: 10px;">View All Logs</a>
    <?php endif; ?>
</div>

<div class="card">
    <h3>System Information</h3>
    <p><strong>Application:</strong> <?php echo APP_NAME; ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
    <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
