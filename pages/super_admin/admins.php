<?php
/**
 * Super Admin - Manage Admins
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';

Auth::requireRole('super_admin');

$pageTitle = 'Manage Admins';
$userClass = new User();

$admins = $userClass->getAllUsers('admin', 100, 0);
$superAdmins = $userClass->getAllUsers('super_admin', 100, 0);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Administrators</h2>

<div class="card">
    <a href="/pages/super_admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<div class="card">
    <h3>Super Administrators</h3>
    <?php if (empty($superAdmins)): ?>
        <p>No super administrators found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($superAdmins as $admin): ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                        <?php if ($admin['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars($admin['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Administrators</h3>
    <?php if (empty($admins)): ?>
        <p>No administrators found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                        <?php if ($admin['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo htmlspecialchars($admin['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
