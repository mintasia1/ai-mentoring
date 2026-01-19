<?php
/**
 * Admin - Manage Mentors
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole(['admin', 'super_admin']);

$pageTitle = 'Manage Mentors';
$currentUserId = Auth::getCurrentUserId();
$mentorClass = new Mentor();
$userClass = new User();

$message = '';
$messageType = '';

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
            
            if (!$user || $user['role'] !== 'mentor') {
                $failCount++;
                continue;
            }
            
            switch ($action) {
                case 'verify':
                    if ($mentorClass->verifyMentor($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'mentor_verified', 'mentor_profiles', $userId, "Batch verified mentor: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'reset_password':
                    $tempPassword = bin2hex(random_bytes(8));
                    if ($userClass->resetPassword($userId, $tempPassword)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'password_reset', 'users', $userId, "Password reset for mentor: {$user['email']}");
                        // In production, send email with temp password
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'change_role':
                    $newRole = $_POST['new_role'] ?? '';
                    if (in_array($newRole, ['mentee', 'mentor', 'admin']) && $userClass->changeRole($userId, $newRole)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'role_changed', 'users', $userId, "Role changed from mentor to {$newRole}: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'disable':
                    if ($userClass->disableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_disabled', 'users', $userId, "Disabled mentor: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'enable':
                    if ($userClass->enableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_enabled', 'users', $userId, "Enabled mentor: {$user['email']}");
                    } else {
                        $failCount++;
                    }
                    break;
                    
                case 'delete':
                    if ($userClass->deleteUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_deleted', 'users', $userId, "Deleted mentor: {$user['email']}");
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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Get mentors
$mentors = $mentorClass->getMentorsWithUserInfo($perPage, $offset);
$totalMentors = $userClass->countUsers('mentor');
$totalPages = ceil($totalMentors / $perPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Mentors</h2>

<div class="card">
    <a href="/pages/<?php echo Auth::getCurrentUserRole(); ?>/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($message):
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message);
    </div>
<?php endif;

<div class="card">
    <h3>Mentors (<?php echo $totalMentors; ?> total)</h3>
    
    <?php if (empty($mentors)):
        <p>No mentors found.</p>
    <?php else:
        <form method="POST" id="batchForm">
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                <select name="batch_action" id="batchAction" class="form-control" style="width: auto;">
                    <option value="">Batch Actions</option>
                    <option value="verify">Verify</option>
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
                </select>
                
                <button type="submit" class="btn" onclick="return confirmBatchAction()">Apply</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        </th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Verified</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentors as $mentor):
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $mentor['user_id']; ?>" class="user-checkbox">
                        </td>
                        <td>
                            <a href="/pages/mentor/dashboard.php?view_user=<?php echo $mentor['user_id']; ?>">
                                <?php echo htmlspecialchars($mentor['email']);
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></td>
                        <td>
                            <?php if ($mentor['is_verified']):
                                <span class="badge" style="background: #27ae60; color: white;">Verified</span>
                            <?php else:
                                <span class="badge" style="background: #f39c12; color: white;">Pending</span>
                            <?php endif;
                        </td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($mentor['role']); ?></span></td>
                        <td>
                            <?php if ($mentor['status'] === 'active'):
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($mentor['status'] === 'disabled'):
                                <span class="badge" style="background: #ffcccc; color: #c00;">Disabled</span>
                            <?php else:
                                <span class="badge badge-warning"><?php echo htmlspecialchars($mentor['status']); ?></span>
                            <?php endif;
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;" 
                                    onclick="window.location.href='/pages/admin/mentors.php'">View</button>
                        </td>
                    </tr>
                    <?php endforeach;
                </tbody>
            </table>
        </form>
        
        <?php if ($totalPages > 1):
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1):
                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif;
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages):
                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next »</a>
            <?php endif;
        </div>
        <?php endif;
    <?php endif;
</div>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
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
        'verify': 'verify',
        'reset_password': 'reset passwords for',
        'change_role': 'change roles for',
        'disable': 'disable',
        'enable': 'enable',
        'delete': 'DELETE'
    };
    
    return confirm(`Are you sure you want to ${actionText[action] || action} ${checked} user(s)?`);
}
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
