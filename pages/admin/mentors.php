<?php
/**
 * Admin - Verify Mentors
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/AuditLog.php';

Auth::requireRole(['admin', 'super_admin']);

$pageTitle = 'Verify Mentors';
$currentUserId = Auth::getCurrentUserId();
$mentorClass = new Mentor();
$userClass = new User();

$message = '';
$messageType = '';

// Handle verification POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
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

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'verified'])) {
    $filter = 'all';
}

// Fetch mentors based on filter
$mentors = $mentorClass->getAllMentors($filter);
$stats = $mentorClass->getStatistics();

include __DIR__ . '/../../includes/header.php';
?>

<h2>Verify Mentors</h2>

<div class="card">
    <a href="/pages/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<div class="card">
    <h3>Statistics</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $stats['total']; ?></h2>
            <p style="margin: 5px 0 0 0;">Total Mentors</p>
        </div>
        <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $stats['verified']; ?></h2>
            <p style="margin: 5px 0 0 0;">Verified</p>
        </div>
        <div style="background: #f39c12; color: white; padding: 20px; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: white;"><?php echo $stats['pending']; ?></h2>
            <p style="margin: 5px 0 0 0;">Pending</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Filter Mentors</h3>
    <div style="margin-top: 15px;">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? '' : 'btn-secondary'; ?>">Show All</a>
        <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? '' : 'btn-secondary'; ?>">Pending Only</a>
        <a href="?filter=verified" class="btn <?php echo $filter === 'verified' ? '' : 'btn-secondary'; ?>">Verified Only</a>
    </div>
</div>

<div class="card">
    <h3>Mentor Profiles</h3>
    <?php if (empty($mentors)): ?>
        <p>No mentors found matching the selected filter.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Practice Area</th>
                    <th>Graduation Year</th>
                    <th>Current Position</th>
                    <th>Status</th>
                    <th>Verification Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mentors as $mentor): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['email']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['practice_area']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['graduation_year'] ?? 'N/A'); ?></td>
                    <td>
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
                    </td>
                    <td>
                        <?php if ($mentor['is_verified']): ?>
                            <span class="badge badge-success">Verified</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $mentor['verification_date'] ? date('Y-m-d H:i', strtotime($mentor['verification_date'])) : 'N/A'; ?>
                    </td>
                    <td>
                        <button onclick="toggleDetails(<?php echo $mentor['user_id']; ?>)" class="btn btn-secondary" style="margin-right: 5px;">View Details</button>
                        <?php if ($mentor['is_verified']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unverify">
                                <input type="hidden" name="user_id" value="<?php echo $mentor['user_id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to revoke verification for this mentor?')">Unverify</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="verify">
                                <input type="hidden" name="user_id" value="<?php echo $mentor['user_id']; ?>">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to verify this mentor?')">Verify</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="details-<?php echo $mentor['user_id']; ?>" style="display: none;">
                    <td colspan="8" style="background: #f8f9fa; padding: 20px;">
                        <h4>Profile Details</h4>
                        <p><strong>Alumni ID:</strong> <?php echo htmlspecialchars($mentor['alumni_id'] ?? 'N/A'); ?></p>
                        <p><strong>Programme Level:</strong> <?php echo PROGRAMME_LEVELS[$mentor['programme_level']] ?? $mentor['programme_level']; ?></p>
                        <p><strong>Expertise:</strong> <?php echo htmlspecialchars($mentor['expertise'] ?? 'N/A'); ?></p>
                        <p><strong>Interests:</strong> <?php echo htmlspecialchars($mentor['interests'] ?? 'N/A'); ?></p>
                        <p><strong>Language:</strong> <?php echo htmlspecialchars($mentor['language'] ?? 'N/A'); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($mentor['location'] ?? 'N/A'); ?></p>
                        <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($mentor['bio'] ?? 'N/A')); ?></p>
                        <p><strong>Max Mentees:</strong> <?php echo $mentor['max_mentees']; ?></p>
                        <p><strong>Current Mentees:</strong> <?php echo $mentor['current_mentees']; ?></p>
                        <p><strong>Profile Created:</strong> <?php echo date('Y-m-d H:i', strtotime($mentor['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($mentor['updated_at'])); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function toggleDetails(userId) {
    var detailsRow = document.getElementById('details-' + userId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
