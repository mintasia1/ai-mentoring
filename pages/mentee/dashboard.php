<?php
/**
 * Mentee Dashboard
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Mentee.php';
require_once __DIR__ . '/../../classes/Mentorship.php';

Auth::requireRole('mentee');

$pageTitle = 'Mentee Dashboard';
$userId = Auth::getCurrentUserId();

$userClass = new User();
$menteeClass = new Mentee();
$mentorshipClass = new Mentorship();

$user = $userClass->getUserProfile($userId);
$profile = $user['profile'] ?? null;
$activeMentorships = $mentorshipClass->getActiveMentorships($userId, 'mentee');
$pendingRequests = $mentorshipClass->getMenteeRequests($userId, 'pending');

include __DIR__ . '/../../includes/header.php';
?>

<h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>

<?php if (!$profile): ?>
    <div class="alert alert-info">
        Please complete your profile to start browsing mentors.
        <a href="/pages/mentee/profile.php" class="btn" style="margin-left: 10px;">Complete Profile</a>
    </div>
<?php endif; ?>

<div class="card">
    <h3>My Statistics</h3>
    <p><strong>Active Mentorships:</strong> <?php echo count($activeMentorships); ?></p>
    <p><strong>Pending Requests:</strong> <?php echo count($pendingRequests); ?></p>
    <?php if ($profile): ?>
        <p><strong>Re-match Opportunities Used:</strong> <?php echo $profile['rematch_count']; ?> / <?php echo REMATCH_LIMIT; ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Quick Actions</h3>
    <a href="/pages/mentee/browse_mentors.php" class="btn">Browse Mentors</a>
    <a href="/pages/mentee/my_requests.php" class="btn btn-secondary">View My Requests</a>
    <a href="/pages/mentee/profile.php" class="btn btn-secondary">Edit Profile</a>
</div>

<?php if (!empty($activeMentorships)): ?>
<div class="card">
    <h3>My Active Mentorships</h3>
    <table>
        <thead>
            <tr>
                <th>Mentor</th>
                <th>Practice Area</th>
                <th>Start Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeMentorships as $mentorship): ?>
            <tr>
                <td><?php echo htmlspecialchars($mentorship['first_name'] . ' ' . $mentorship['last_name']); ?></td>
                <td><?php echo htmlspecialchars($mentorship['practice_area'] ?? 'N/A'); ?></td>
                <td><?php echo date('M d, Y', strtotime($mentorship['start_date'])); ?></td>
                <td><a href="/pages/mentee/workspace.php?id=<?php echo $mentorship['id']; ?>" class="btn">Workspace</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
