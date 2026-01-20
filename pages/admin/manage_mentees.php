<?php
/**
 * Admin - Manage Mentees
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Mentee.php';
require_once __DIR__ . '/../../classes/AuditLog.php';
require_once __DIR__ . '/../../classes/Logger.php';
require_once __DIR__ . '/../../classes/CSRFToken.php';

Auth::requirePageAccess('admin_pages');

$pageTitle = 'Manage Mentees';
$currentUserId = Auth::getCurrentUserId();
$menteeClass = new Mentee();
$userClass = new User();

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
    
    if (empty($action)) {
        $message = 'Please select an action';
        $messageType = 'error';
    } elseif (!is_array($selectedUsers) || empty($selectedUsers)) {
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
                Logger::warning("Batch action attempted on non-existent user", ['user_id' => $userId, 'action' => $action]);
                continue;
            }
            
            // Verify user has a mentee profile
            // Since they were displayed on this page (from INNER JOIN with mentee_profiles),
            // they should have a mentee profile, but we double-check for safety
            $menteeProfile = $menteeClass->getProfile($userId);
            if (!$menteeProfile) {
                $failCount++;
                Logger::warning("Batch action attempted on user without mentee profile", ['user_id' => $userId, 'action' => $action, 'role' => $user['role']]);
                continue;
            }
            
            switch ($action) {
                case 'reset_password':
                    $tempPassword = bin2hex(random_bytes(8));
                    if ($userClass->resetPassword($userId, $tempPassword)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'password_reset', 'users', $userId, "Password reset for mentee: {$user['email']}");
                        // In production, send email with temp password
                    } else {
                        $failCount++;
                        Logger::error("Failed to reset password for mentee", ['user_id' => $userId, 'email' => $user['email']]);
                    }
                    break;
                    
                case 'change_role':
                    $newRole = $_POST['new_role'] ?? '';
                    if (empty($newRole)) {
                        $failCount++;
                        Logger::warning("Change role attempted without specifying new role", ['user_id' => $userId]);
                    } elseif (in_array($newRole, ['mentee', 'mentor', 'admin']) && $userClass->changeRole($userId, $newRole)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'role_changed', 'users', $userId, "Role changed from mentee to {$newRole}: {$user['email']}");
                    } else {
                        $failCount++;
                        Logger::error("Failed to change role for mentee", ['user_id' => $userId, 'email' => $user['email'], 'new_role' => $newRole]);
                    }
                    break;
                    
                case 'disable':
                    if ($userClass->disableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_disabled', 'users', $userId, "Disabled mentee: {$user['email']}");
                    } else {
                        $failCount++;
                        Logger::error("Failed to disable mentee", ['user_id' => $userId, 'email' => $user['email']]);
                    }
                    break;
                    
                case 'enable':
                    if ($userClass->enableUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_enabled', 'users', $userId, "Enabled mentee: {$user['email']}");
                    } else {
                        $failCount++;
                        Logger::error("Failed to enable mentee", ['user_id' => $userId, 'email' => $user['email']]);
                    }
                    break;
                    
                case 'delete':
                    if ($userClass->deleteUser($userId)) {
                        $successCount++;
                        AuditLog::log($currentUserId, 'user_deleted', 'users', $userId, "Deleted mentee: {$user['email']}");
                    } else {
                        $failCount++;
                        Logger::error("Failed to delete mentee", ['user_id' => $userId, 'email' => $user['email']]);
                    }
                    break;
                    
                default:
                    $failCount++;
                    Logger::warning("Unknown batch action attempted", ['action' => $action, 'user_id' => $userId]);
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
if (!in_array($filter, ['all', 'active', 'disabled'])) {
    $filter = 'all';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($perPage, [10, 25, 50, 100])) {
    $perPage = 10;
}
$offset = ($page - 1) * $perPage;

// Get mentees with full profile info
$allMentees = $menteeClass->getAllMentees();

// Apply filter
if ($filter === 'active') {
    $mentees = array_filter($allMentees, function($m) { return $m['status'] === 'active'; });
} elseif ($filter === 'disabled') {
    $mentees = array_filter($allMentees, function($m) { return $m['status'] === 'disabled'; });
} else {
    $mentees = $allMentees;
}

$stats = $menteeClass->getStatistics();
$totalMentees = count($mentees);
$totalPages = ceil($totalMentees / $perPage);

// Apply pagination to results
$mentees = array_slice($mentees, $offset, $perPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Mentees</h2>

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
            <div style="background: <?php echo $filter === 'all' ? '#229954' : '#27ae60'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#229954'" onmouseout="this.style.background='<?php echo $filter === 'all' ? '#229954' : '#27ae60'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $stats['total']; ?></h2>
                <p style="margin: 5px 0 0 0;">Total Mentees</p>
            </div>
        </a>
        <a href="?filter=active" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'active' ? '#2980b9' : '#3498db'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#2980b9'" onmouseout="this.style.background='<?php echo $filter === 'active' ? '#2980b9' : '#3498db'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $stats['active']; ?></h2>
                <p style="margin: 5px 0 0 0;">Active</p>
            </div>
        </a>
        <a href="?filter=disabled" style="text-decoration: none;">
            <div style="background: <?php echo $filter === 'disabled' ? '#c0392b' : '#e74c3c'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#c0392b'" onmouseout="this.style.background='<?php echo $filter === 'disabled' ? '#c0392b' : '#e74c3c'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $stats['disabled']; ?></h2>
                <p style="margin: 5px 0 0 0;">Disabled</p>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;">Mentee Profiles
            <?php if ($filter !== 'all'): ?>
                - <?php echo ucfirst($filter); ?>
            <?php endif; ?>
        </h3>
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="perPage" style="margin: 0;">Rows:</label>
            <select id="perPage" onchange="changePerPage(this.value)" style="padding: 5px;">
                <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
    </div>
    
    <?php if (empty($mentees)): ?>
        <p>No mentees found.</p>
    <?php else: ?>
        <?php if ($totalPages > 1): ?>
        <div style="margin-bottom: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
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
                </select>
                
                <button type="submit" class="btn" onclick="return confirmBatchAction()">Apply</button>
            </div>
            
            <table id="menteesTable">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        </th>
                        <th onclick="sortTable(1)" style="cursor: pointer;">Name ▲▼</th>
                        <th onclick="sortTable(2)" style="cursor: pointer;">Email ▲▼</th>
                        <th onclick="sortTable(3)" style="cursor: pointer;">Programme Level ▲▼</th>
                        <th onclick="sortTable(4)" style="cursor: pointer;">Status ▲▼</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentees as $mentee): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $mentee['user_id']; ?>" class="user-checkbox">
                        </td>
                        <td><?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($mentee['email']); ?></td>
                        <td><?php echo PROGRAMME_LEVELS[$mentee['programme_level']] ?? $mentee['programme_level']; ?></td>
                        <td>
                            <?php if ($mentee['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($mentee['status'] === 'disabled'): ?>
                                <span class="badge" style="background: #ffcccc; color: #c00;">Disabled</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($mentee['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" onclick="toggleDetails(<?php echo $mentee['user_id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;">View Details</button>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $mentee['user_id']; ?>" style="display: none;">
                        <td colspan="6" style="background: #f8f9fa; padding: 20px;">
                            <h4>Profile Details</h4>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                <div>
                                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($mentee['student_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Year of Study:</strong> <?php echo htmlspecialchars($mentee['year_of_study'] ?? 'N/A'); ?></p>
                                    <p><strong>Interests:</strong> <?php echo htmlspecialchars($mentee['interests'] ?? 'N/A'); ?></p>
                                    <p><strong>Practice Area Preference:</strong> <?php echo htmlspecialchars($mentee['practice_area_preference'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p><strong>Language Preference:</strong> <?php echo htmlspecialchars($mentee['language_preference'] ?? 'N/A'); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($mentee['location'] ?? 'N/A'); ?></p>
                                    <p><strong>Rematch Count:</strong> <?php echo $mentee['rematch_count']; ?></p>
                                    <p><strong>Profile Created:</strong> <?php echo date('Y-m-d H:i', strtotime($mentee['created_at'])); ?></p>
                                </div>
                            </div>
                            <p><strong>Goals:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentee['goals'] ?? 'N/A')); ?></p>
                            <p><strong>Bio:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentee['bio'] ?? 'N/A')); ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', '1');
    params.set('per_page', perPage);
    window.location.href = '?' + params.toString();
}

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

document.getElementById('batchAction').addEventListener('change', function() {
    const newRoleSelect = document.getElementById('newRole');
    if (this.value === 'change_role') {
        newRoleSelect.style.display = 'inline-block';
    } else {
        newRoleSelect.style.display = 'none';
    }
});

function sortTable(columnIndex) {
    const table = document.getElementById('menteesTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.id || !row.id.startsWith('details-'));
    
    // Determine sort direction
    const isAscending = table.dataset.sortDirection !== 'asc';
    table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        
        if (isAscending) {
            return aText.localeCompare(bText);
        } else {
            return bText.localeCompare(aText);
        }
    });
    
    // Re-append sorted rows
    rows.forEach(row => {
        tbody.appendChild(row);
        // Move the details row along with the main row
        const menteeId = row.querySelector('.user-checkbox')?.value;
        if (menteeId) {
            const detailsRow = document.getElementById('details-' + menteeId);
            if (detailsRow) {
                tbody.appendChild(detailsRow);
            }
        }
    });
}
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
