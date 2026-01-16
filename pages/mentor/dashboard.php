<?php
/**
 * Mentor Dashboard
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/Mentorship.php';

Auth::requireRole('mentor');

$pageTitle = 'Mentor Dashboard';
$userId = Auth::getCurrentUserId();

$userClass = new User();
$mentorClass = new Mentor();
$mentorshipClass = new Mentorship();

$user = $userClass->getUserProfile($userId);
$profile = $user['profile'] ?? null;
$activeMentorships = $mentorshipClass->getActiveMentorships($userId, 'mentor');
$pendingRequests = $mentorshipClass->getMentorRequests($userId, 'pending');

include __DIR__ . '/../../includes/header.php';
?>

<h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>

<?php if (!$profile): ?>
    <div class="alert alert-info">
        Please complete your profile to start accepting mentees.
        <a href="/pages/mentor/profile.php" class="btn" style="margin-left: 10px;">Complete Profile</a>
    </div>
<?php elseif (!$profile['is_verified']): ?>
    <div class="alert alert-info">
        Your profile is pending verification. You will be able to accept mentees once verified.
    </div>
<?php endif; ?>

<div class="card">
    <h3>My Statistics</h3>
    <?php if ($profile): ?>
        <p><strong>Current Mentees:</strong> <?php echo $profile['current_mentees']; ?> / <?php echo $profile['max_mentees']; ?></p>
        <p><strong>Verification Status:</strong> 
            <?php if ($profile['is_verified']): ?>
                <span class="badge badge-success">Verified</span>
            <?php else: ?>
                <span class="badge badge-warning">Pending</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <p><strong>Pending Requests:</strong> <?php echo count($pendingRequests); ?></p>
    <p><strong>Active Mentorships:</strong> <?php echo count($activeMentorships); ?></p>
</div>

<?php if (count($pendingRequests) > 0): ?>
<div class="card">
    <h3>Pending Requests</h3>
    <p>You have <?php echo count($pendingRequests); ?> pending request(s).</p>
    <a href="/pages/mentor/requests.php" class="btn">Review Requests</a>
</div>
<?php endif; ?>

<div class="card">
    <h3>Quick Actions</h3>
    <a href="/pages/mentor/requests.php" class="btn">View Requests</a>
    <a href="/pages/mentor/my_mentees.php" class="btn btn-secondary">My Mentees</a>
    <a href="/pages/mentor/profile.php" class="btn btn-secondary">Edit Profile</a>
</div>

<?php if (!empty($activeMentorships)): ?>
<div class="card">
    <h3>My Active Mentees</h3>
    <table>
        <thead>
            <tr>
                <th>Mentee</th>
                <th>Programme</th>
                <th>Start Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeMentorships as $mentorship): ?>
            <tr>
                <td><?php echo htmlspecialchars($mentorship['first_name'] . ' ' . $mentorship['last_name']); ?></td>
                <td><?php echo PROGRAMME_LEVELS[$mentorship['programme_level']] ?? $mentorship['programme_level']; ?></td>
                <td><?php echo date('M d, Y', strtotime($mentorship['start_date'])); ?></td>
                <td><a href="/pages/mentor/workspace.php?id=<?php echo $mentorship['id']; ?>" class="btn">Workspace</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>