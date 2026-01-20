<?php
/**
 * Super Admin - Manage All Users
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/AuditLog.php';
require_once __DIR__ . '/../../classes/CSRFToken.php';

Auth::requirePageAccess('super_admin_pages');

$pageTitle = 'Manage All Users';
$userClass = new User();
$currentUserId = Auth::getCurrentUserId();

$message = '';
$messageType = '';

// Handle batch actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_action'], $_POST['selected_users'])) {
    if (!CSRFToken::validate($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['batch_action'];
        $selectedUsers = $_POST['selected_users'];
    
    if (!is_array($selectedUsers) || empty($selectedUsers)) {
        $message = 'No users selected';
        $messageType = 'error';
    } else {
        $successCount = 0;
        $failCount = 0;
        
        foreach ($selectedUsers as $userId) {
            $userId = intval($userId);
            $user = $userClass->getUserById($userId);
            
            if (!$user) {
                $failCount++;
                continue;
            }
            
            switch ($action) {
                case 'reset_password':
                    $tempPassword = bin2hex(random_bytes(8));
                    if ($userClass->resetPassword($userId, $tempPassword)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'password_reset', 'users', $userId, "Password reset for user: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'change_role':
                    $newRole = $_POST['new_role'] ?? '';
                    if (in_array($newRole, ['mentee', 'mentor', 'admin', 'super_admin']) && $userClass->changeRole($userId, $newRole)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'role_changed', 'users', $userId, "Role changed to {$newRole}: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'disable':
                    if ($userClass->disableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_disabled', 'users', $userId, "Disabled user: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'enable':
                    if ($userClass->enableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_enabled', 'users', $userId, "Enabled user: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'delete':
                    if ($userClass->deleteUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_deleted', 'users', $userId, "Deleted user: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                default:
                    $failCount++;
            }
        }
        
        if ($successCount > 0) {
            $message = "Successfully processed {$successCount} user(s)";
            $messageType = 'success';
        }
        if ($failCount > 0) {
            $message .= ($message ? '. ' : '') . "Failed to process {$failCount} user(s)";
            $messageType = $successCount > 0 ? 'warning' : 'error';
        }
    }
    }
}

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'mentee', 'mentor', 'admin', 'super_admin'])) {
    $filter = 'all';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Get users based on filter
if ($filter === 'all') {
    $users = $userClass->getAllUsers(null, $perPage, $offset);
    $totalUsers = $userClass->countUsers();
} else {
    $users = $userClass->getAllUsers($filter, $perPage, $offset);
    $totalUsers = $userClass->countUsers($filter);
}

$totalPages = ceil($totalUsers / $perPage);

// Calculate statistics
$statsTotal = $userClass->countUsers();
$statsMentees = $userClass->countUsers('mentee');
$statsMentors = $userClass->countUsers('mentor');
$statsAdmins = $userClass->countUsers('admin');
$statsSuperAdmins = $userClass->countUsers('super_admin');

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage All Users</h2>

<div class="card">
    <a href="/pages/super_admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Statistics</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="?filter=all" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'all' ? '#2980b9' : '#3498db'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#2980b9'" onmouseout="this.style.background='<?php echo $filter === 'all' ? '#2980b9' : '#3498db'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsTotal; ?></h2>
                <p style="margin: 5px 0 0 0;">All Users</p>
            </div>
        </a>
        <a href="?filter=mentee" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'mentee' ? '#229954' : '#27ae60'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#229954'" onmouseout="this.style.background='<?php echo $filter === 'mentee' ? '#229954' : '#27ae60'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsMentees; ?></h2>
                <p style="margin: 5px 0 0 0;">Mentees</p>
            </div>
        </a>
        <a href="?filter=mentor" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'mentor' ? '#d68910' : '#f39c12'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#d68910'" onmouseout="this.style.background='<?php echo $filter === 'mentor' ? '#d68910' : '#f39c12'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsMentors; ?></h2>
                <p style="margin: 5px 0 0 0;">Mentors</p>
            </div>
        </a>
        <a href="?filter=admin" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'admin' ? '#7d3c98' : '#9b59b6'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#7d3c98'" onmouseout="this.style.background='<?php echo $filter === 'admin' ? '#7d3c98' : '#9b59b6'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsAdmins; ?></h2>
                <p style="margin: 5px 0 0 0;">Admins</p>
            </div>
        </a>
        <a href="?filter=super_admin" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'super_admin' ? '#c0392b' : '#e74c3c'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#c0392b'" onmouseout="this.style.background='<?php echo $filter === 'super_admin' ? '#c0392b' : '#e74c3c'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsSuperAdmins; ?></h2>
                <p style="margin: 5px 0 0 0;">Super Admins</p>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <h3>User List
        <?php if ($filter !== 'all'): ?>
            - <?php echo ucwords(str_replace('_', ' ', $filter)); ?>
        <?php endif; ?>
    </h3>
    
    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <form method="POST" id="batchForm">
            <?php echo CSRFToken::getField(); ?>
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                <select name="batch_action" id="batchAction" class="form-control" style="width: auto;">
                    <option value="">Batch Actions</option>
                    <option value="reset_password">Reset Password</option>
                    <option value="change_role">Change Role</option>
                    <option value="disable">Disable</option>
                    <option value="enable">Enable</option>
                    <option value="delete">Delete</option>
                </select>
                
                <select name="new_role" id="newRole" class="form-control" style="width: auto; display: none;">
                    <option value="">Select Role</option>
                    <option value="mentee">Mentee</option>
                    <option value="mentor">Mentor</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
                
                <button type="submit" class="btn" onclick="return confirmBatchAction()">Apply</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        </th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" class="user-checkbox">
                        </td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php 
                            $roleColors = [
                                'mentee' => '#27ae60',
                                'mentor' => '#f39c12',
                                'admin' => '#9b59b6',
                                'super_admin' => '#e74c3c'
                            ];
                            $bgColor = $roleColors[$user['role']] ?? '#3498db';
                            ?>
                            <span class="badge" style="background: <?php echo $bgColor; ?>; color: white;">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($user['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td>
                            <button type="button" onclick="toggleDetails(<?php echo $user['id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;">View Details</button>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $user['id']; ?>" style="display: none;">
                        <td colspan="7" style="background: #f8f9fa; padding: 20px;">
                            <h4>User Details</h4>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($user['status'])); ?></p>
                            <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></p>
                            <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function toggleDetails(userId) {
    var detailsRow = document.getElementById('details-' + userId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}

document.getElementById('batchAction').addEventListener('change', function() {
    const newRoleSelect = document.getElementById('newRole');
    if (this.value === 'change_role') {
        newRoleSelect.style.display = 'inline-block';
    } else {
        newRoleSelect.style.display = 'none';
    }
});

function confirmBatchAction() {
    const action = document.getElementById('batchAction').value;
    const checked = document.querySelectorAll('.user-checkbox:checked').length;
    
    if (!action) {
        alert('Please select an action');
        return false;
    }
    
    if (checked === 0) {
        alert('Please select at least one user');
        return false;
    }
    
    if (action === 'change_role') {
        const newRole = document.getElementById('newRole').value;
        if (!newRole) {
            alert('Please select a new role');
            return false;
        }
    }
    
    const actionText = {
        'reset_password': 'reset passwords for',
        'change_role': 'change roles for',
        'disable': 'disable',
        'enable': 'enable',
        'delete': 'DELETE'
    };
    
    return confirm(`Are you sure you want to ${actionText[action] || action} ${checked} user(s)?`);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
