<?php
/**
 * Super Admin - Manage Admins
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole('super_admin');

$pageTitle = 'Manage Admins';
$userClass = new User();
$currentUserId = Auth::getCurrentUserId();

$message = '';
$messageType = '';

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Validation
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address';
        $messageType = 'error';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $message = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        $messageType = 'error';
    } elseif (!in_array($role, ['admin', 'super_admin'])) {
        $message = 'Invalid role selected';
        $messageType = 'error';
    } else {
        $userId = $userClass->createUser($email, $password, $role, $firstName, $lastName);
        if ($userId) {
            $message = "Successfully created new {$role}: {$email}";
            $messageType = 'success';
            AuditLog::log($currentUserId, 'admin_created', 'users', $userId, "Created new {$role}: {$email}");
        } else {
            $message = 'Failed to create admin. Email may already exist.';
            $messageType = 'error';
        }
    }
}

// Handle batch actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_action'], $_POST['selected_users'])) {
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
            
            if (!$user || !in_array($user['role'], ['admin', 'super_admin'])) {
                $failCount++;
                continue;
            }
            
            switch ($action) {
                case 'reset_password':
                    $tempPassword = bin2hex(random_bytes(8));
                    if ($userClass->resetPassword($userId, $tempPassword)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'password_reset', 'users', $userId, "Password reset for {$user['role']}: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'disable':
                    if ($userClass->disableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_disabled', 'users', $userId, "Disabled {$user['role']}: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'enable':
                    if ($userClass->enableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_enabled', 'users', $userId, "Enabled {$user['role']}: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'delete':
                    if ($userClass->deleteUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_deleted', 'users', $userId, "Deleted {$user['role']}: {$user['email']}");
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

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'admin', 'super_admin'])) {
    $filter = 'all';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Get administrators based on filter
if ($filter === 'admin') {
    $allAdmins = $userClass->getAllUsers('admin', 200, 0);
} elseif ($filter === 'super_admin') {
    $allAdmins = $userClass->getAllUsers('super_admin', 200, 0);
} else {
    $admins = $userClass->getAllUsers('admin', 200, 0);
    $superAdmins = $userClass->getAllUsers('super_admin', 200, 0);
    $allAdmins = array_merge($superAdmins, $admins);
}

$totalAdmins = count($allAdmins);
$totalPages = ceil($totalAdmins / $perPage);

// Apply pagination
$allAdmins = array_slice($allAdmins, $offset, $perPage);

// Calculate statistics
$statsAdmins = $userClass->countUsers('admin');
$statsSuperAdmins = $userClass->countUsers('super_admin');
$statsTotal = $statsAdmins + $statsSuperAdmins;

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Administrators</h2>

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
            <div style="background: <?php echo $filter === 'all' ? '#7d3c98' : '#8e44ad'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#7d3c98'" onmouseout="this.style.background='<?php echo $filter === 'all' ? '#7d3c98' : '#8e44ad'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $statsTotal; ?></h2>
                <p style="margin: 5px 0 0 0;">Total Administrators</p>
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
    <h3>Create New Administrator</h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="create_admin">
        
        <div class="form-group">
            <label for="email">Email: *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password: *</label>
            <input type="password" id="password" name="password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
            <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name: *</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name: *</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="role">Role: *</label>
            <select id="role" name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
            </select>
        </div>
        
        <button type="submit" class="btn">Create Administrator</button>
    </form>
</div>

<div class="card">
    <h3>Administrator List
        <?php if ($filter !== 'all'): ?>
            - <?php echo $filter === 'admin' ? 'Admins' : 'Super Admins'; ?>
        <?php endif; ?>
    </h3>
    
    <?php if (empty($allAdmins)): ?>
        <p>No administrators found.</p>
    <?php else: ?>
        <form method="POST" id="batchForm">
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                <select name="batch_action" id="batchAction" class="form-control" style="width: auto;">
                    <option value="">Batch Actions</option>
                    <option value="reset_password">Reset Password</option>
                    <option value="disable">Disable</option>
                    <option value="enable">Enable</option>
                    <option value="delete">Delete</option>
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
                    <?php foreach ($allAdmins as $admin): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $admin['id']; ?>" class="user-checkbox">
                        </td>
                        <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <?php if ($admin['role'] === 'super_admin'): ?>
                                <span class="badge" style="background: #e74c3c; color: white;">Super Admin</span>
                            <?php else: ?>
                                <span class="badge" style="background: #9b59b6; color: white;">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($admin['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($admin['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                        <td>
                            <button type="button" onclick="toggleDetails(<?php echo $admin['id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;">View Details</button>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $admin['id']; ?>" style="display: none;">
                        <td colspan="7" style="background: #f8f9fa; padding: 20px;">
                            <h4>User Details</h4>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($admin['role'], '_'))); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($admin['status'])); ?></p>
                            <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></p>
                            <p><strong>Last Login:</strong> <?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></p>
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
    
    const actionText = {
        'reset_password': 'reset passwords for',
        'disable': 'disable',
        'enable': 'enable',
        'delete': 'DELETE'
    };
    
    return confirm(`Are you sure you want to ${actionText[action] || action} ${checked} user(s)?`);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php';
