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

Auth::requirePageAccess('admin_pages');

$pageTitle = 'Manage Mentors';
$currentUserId = Auth::getCurrentUserId();
$mentorClass = new Mentor();
$userClass = new User();

$message = '';
$messageType = '';

// Handle individual verify/unverify actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id']) && !isset($_POST['batch_action'])) {
    $action = $_POST['action'];
    $userId = intval($_POST['user_id']);
    
    // Validate user_id exists and is a mentor
    $user = $userClass->getUserById($userId);
    if ($user && $user['role'] === 'mentor') {
        if ($action === 'verify') {
            if ($mentorClass->verifyMentor($userId)) {
                $message = 'Mentor successfully verified!';
                $messageType = 'success';
                AuditLog::log($currentUserId, 'mentor_verified', 'mentor_profiles', $userId, "Verified mentor: {$user['first_name']} {$user['last_name']}");
            } else {
                $message = 'Failed to verify mentor';
                $messageType = 'error';
            }
        } elseif ($action === 'unverify') {
            if ($mentorClass->unverifyMentor($userId)) {
                $message = 'Mentor verification revoked';
                $messageType = 'success';
                AuditLog::log($currentUserId, 'mentor_unverified', 'mentor_profiles', $userId, "Unverified mentor: {$user['first_name']} {$user['last_name']}");
            } else {
                $message = 'Failed to unverify mentor';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Invalid user ID or user is not a mentor';
        $messageType = 'error';
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

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'verified'])) {
    $filter = 'all';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Get mentors with full profile info and apply filter
$mentors = $mentorClass->getAllMentors($filter);
$stats = $mentorClass->getStatistics();
$totalMentors = count($mentors);
$totalPages = ceil($totalMentors / $perPage);

// Apply pagination to results
$mentors = array_slice($mentors, $offset, $perPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Mentors</h2>

<div class="card">
    <a href="/pages/<?php echo Auth::getCurrentUserRole(); ?>/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
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
                <h2 style="margin: 0; color: white;"><?php echo $stats['total']; ?></h2>
                <p style="margin: 5px 0 0 0;">Total Mentors</p>
            </div>
        </a>
        <a href="?filter=verified" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'verified' ? '#229954' : '#27ae60'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#229954'" onmouseout="this.style.background='<?php echo $filter === 'verified' ? '#229954' : '#27ae60'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $stats['verified']; ?></h2>
                <p style="margin: 5px 0 0 0;">Verified</p>
            </div>
        </a>
        <a href="?filter=pending" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'pending' ? '#d68910' : '#f39c12'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#d68910'" onmouseout="this.style.background='<?php echo $filter === 'pending' ? '#d68910' : '#f39c12'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $stats['pending']; ?></h2>
                <p style="margin: 5px 0 0 0;">Pending</p>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <h3>Mentor Profiles
        <?php if ($filter !== 'all'): ?>
            - <?php echo ucfirst($filter); ?>
        <?php endif; ?>
    </h3>
    
    <?php if (empty($mentors)): ?>
        <p>No mentors found.</p>
    <?php else: ?>
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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Practice Area</th>
                        <th>Verification Status</th>
                        <th>User Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentors as $mentor): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $mentor['id']; ?>" class="user-checkbox">
                        </td>
                        <td><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($mentor['email']); ?></td>
                        <td><?php echo htmlspecialchars($mentor['practice_area']); ?></td>
                        <td>
                            <?php if ($mentor['is_verified']): ?>
                                <span class="badge badge-success">Verified</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($mentor['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($mentor['status'] === 'disabled'): ?>
                                <span class="badge" style="background: #ffcccc; color: #c00;">Disabled</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($mentor['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" onclick="toggleDetails(<?php echo $mentor['id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em; margin-right: 5px;">View Details</button>
                            <?php if ($mentor['is_verified']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unverify">
                                    <input type="hidden" name="user_id" value="<?php echo $mentor['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.9em;" onclick="return confirm('Are you sure you want to revoke verification for this mentor?')">Unverify</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="user_id" value="<?php echo $mentor['id']; ?>">
                                    <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 0.9em;" onclick="return confirm('Are you sure you want to verify this mentor?')">Verify</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $mentor['id']; ?>" style="display: none;">
                        <td colspan="7" style="background: #f8f9fa; padding: 20px;">
                            <h4>Profile Details</h4>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                <div>
                                    <p><strong>Alumni ID:</strong> <?php echo htmlspecialchars($mentor['alumni_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Programme Level:</strong> <?php echo PROGRAMME_LEVELS[$mentor['programme_level']] ?? $mentor['programme_level']; ?></p>
                                    <p><strong>Graduation Year:</strong> <?php echo htmlspecialchars($mentor['graduation_year'] ?? 'N/A'); ?></p>
                                    <p><strong>Current Position:</strong> 
                                        <?php 
                                        if ($mentor['current_position']) {
                                            echo htmlspecialchars($mentor['current_position']);
                                            if ($mentor['company']) {
                                                echo ' at ' . htmlspecialchars($mentor['company']);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Expertise:</strong> <?php echo htmlspecialchars($mentor['expertise'] ?? 'N/A'); ?></p>
                                    <p><strong>Interests:</strong> <?php echo htmlspecialchars($mentor['interests'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p><strong>Language:</strong> <?php echo htmlspecialchars($mentor['language'] ?? 'N/A'); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($mentor['location'] ?? 'N/A'); ?></p>
                                    <p><strong>Max Mentees:</strong> <?php echo $mentor['max_mentees']; ?></p>
                                    <p><strong>Current Mentees:</strong> <?php echo $mentor['current_mentees']; ?></p>
                                    <p><strong>Profile Created:</strong> <?php echo date('Y-m-d H:i', strtotime($mentor['created_at'])); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($mentor['updated_at'])); ?></p>
                                    <?php if ($mentor['verification_date']): ?>
                                        <p><strong>Verification Date:</strong> <?php echo date('Y-m-d H:i', strtotime($mentor['verification_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p><strong>Bio:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentor['bio'] ?? 'N/A')); ?></p>
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
 
