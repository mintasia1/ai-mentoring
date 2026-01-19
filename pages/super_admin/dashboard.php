<?php
/**
 * Super Admin Dashboard
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requirePageAccess('super_admin_pages');

$pageTitle = 'Super Admin Dashboard';
$userId = Auth::getCurrentUserId();

$userClass = new User();

// Get statistics
$totalUsers = $userClass->countUsers();
$totalMentees = $userClass->countUsers('mentee');
$totalMentors = $userClass->countUsers('mentor');
$totalAdmins = $userClass->countUsers('admin');
$totalSuperAdmins = $userClass->countUsers('super_admin');

// Get pagination parameters
$auditPage = isset($_GET['audit_page']) ? max(1, intval($_GET['audit_page'])) : 1;
$auditPerPage = isset($_GET['audit_per_page']) ? intval($_GET['audit_per_page']) : 10;
if (!in_array($auditPerPage, [10, 25, 50, 100])) {
    $auditPerPage = 10;
}
$auditOffset = ($auditPage - 1) * $auditPerPage;

// Get recent audit logs with pagination
$recentLogs = AuditLog::getAllLogs($auditPerPage, $auditOffset);
$totalAuditLogs = AuditLog::getTotal();
$totalAuditPages = ceil($totalAuditLogs / $auditPerPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Super Admin Dashboard</h2>

<div class="card">
    <h3>System Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="/pages/super_admin/manage_users.php" style="text-decoration: none;">
            <div style="background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $totalUsers; ?></h2>
                <p style="margin: 5px 0 0 0;">Total Users</p>
            </div>
        </a>
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
        <a href="/pages/super_admin/admins.php?filter=admin" style="text-decoration: none;">
            <div style="background: #9b59b6; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $totalAdmins; ?></h2>
                <p style="margin: 5px 0 0 0;">Admins</p>
            </div>
        </a>
        <a href="/pages/super_admin/admins.php?filter=super_admin" style="text-decoration: none;">
            <div style="background: #e74c3c; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $totalSuperAdmins; ?></h2>
                <p style="margin: 5px 0 0 0;">Super Admins</p>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;">Recent Activity (Audit Logs)</h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="auditPerPage" style="margin: 0;">Rows:</label>
            <select id="auditPerPage" onchange="changeAuditPerPage(this.value)" style="padding: 5px;">
                <option value="10" <?php echo $auditPerPage == 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $auditPerPage == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $auditPerPage == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $auditPerPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
    </div>
    
    <?php if ($totalAuditPages > 1): ?>
    <div style="margin-bottom: 15px; text-align: center;">
        <?php if ($auditPage > 1): ?>
            <a href="?audit_page=<?php echo $auditPage - 1; ?>&audit_per_page=<?php echo $auditPerPage; ?>" class="btn btn-secondary" style="padding: 5px 10px;">« Previous</a>
        <?php endif; ?>
        
        <span style="margin: 0 15px;">Page <?php echo $auditPage; ?> of <?php echo $totalAuditPages; ?></span>
        
        <?php if ($auditPage < $totalAuditPages): ?>
            <a href="?audit_page=<?php echo $auditPage + 1; ?>&audit_per_page=<?php echo $auditPerPage; ?>" class="btn btn-secondary" style="padding: 5px 10px;">Next »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
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
        
        <?php if ($totalAuditPages > 1): ?>
        <div style="margin-top: 15px; text-align: center;">
            <?php if ($auditPage > 1): ?>
                <a href="?audit_page=<?php echo $auditPage - 1; ?>&audit_per_page=<?php echo $auditPerPage; ?>" class="btn btn-secondary" style="padding: 5px 10px;">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $auditPage; ?> of <?php echo $totalAuditPages; ?></span>
            
            <?php if ($auditPage < $totalAuditPages): ?>
                <a href="?audit_page=<?php echo $auditPage + 1; ?>&audit_per_page=<?php echo $auditPerPage; ?>" class="btn btn-secondary" style="padding: 5px 10px;">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <a href="/pages/super_admin/audit_logs.php" class="btn btn-secondary" style="margin-top: 10px;">View All Logs</a>
    <?php endif; ?>
</div>

<script>
function changeAuditPerPage(perPage) {
    window.location.href = '?audit_page=1&audit_per_page=' + perPage;
}
</script>

<div class="card">
    <h3>System Information</h3>
    <p><strong>Application:</strong> <?php echo APP_NAME; ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
