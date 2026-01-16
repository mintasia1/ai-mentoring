<?php
/**
 * Admin Dashboard
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';

Auth::requireRole('admin');

$pageTitle = 'Admin Dashboard';
$userId = Auth::getCurrentUserId();

$userClass = new User();

// Get statistics
$totalMentees = $userClass->countUsers('mentee');
$totalMentors = $userClass->countUsers('mentor');

include __DIR__ . '/../../includes/header.php';
?>

<h2>Admin Dashboard</h2>

<div class="card">
    <h3>System Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $totalMentees; ?></h2>
            <p style="margin: 5px 0 0 0;">Mentees</p>
        </div>
        <div style="background: #f39c12; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $totalMentors; ?></h2>
            <p style="margin: 5px 0 0 0;">Mentors</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Quick Actions</h3>
    <a href="/pages/admin/users.php" class="btn">Manage Users</a>
    <a href="/pages/admin/mentors.php" class="btn btn-secondary">Verify Mentors</a>
    <a href="/pages/admin/matches.php" class="btn btn-secondary">View Matches</a>
</div>

<div class="card">
    <h3>Recent Users</h3>
    <?php
    $recentUsers = $userClass->getAllUsers(null, 10, 0);
    if (empty($recentUsers)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($user['role']); ?></span></td>
                    <td>
                        <?php if ($user['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars($user['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
