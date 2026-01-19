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

Auth::requirePageAccess('mentor_pages');

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
        <a href="/pages/mentor/complete_profile.php" class="btn" style="margin-left: 10px;">Complete Profile</a>
    </div>
<?php elseif (!$profile['is_verified']): ?>
    <div class="alert alert-info">
        Your profile is pending verification. You will be able to accept mentees once verified.
    </div>
<?php endif; ?>

<div class="card">
    <h3>My Statistics</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="/pages/mentor/my_mentees.php" style="text-decoration: none;">
            <div style="background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <?php if ($profile): ?>
                    <h2 style="margin: 0; color: white;"><?php echo $profile['current_mentees']; ?> / <?php echo $profile['max_mentees']; ?></h2>
                    <p style="margin: 5px 0 0 0;">Current Mentees</p>
                <?php else: ?>
                    <h2 style="margin: 0; color: white;">0</h2>
                    <p style="margin: 5px 0 0 0;">Current Mentees</p>
                <?php endif; ?>
            </div>
        </a>
        <a href="/pages/mentor/requests.php" style="text-decoration: none;">
            <div style="background: #f39c12; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo count($pendingRequests); ?></h2>
                <p style="margin: 5px 0 0 0;">Pending Requests</p>
            </div>
        </a>
        <a href="/pages/mentor/my_mentees.php" style="text-decoration: none;">
            <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo count($activeMentorships); ?></h2>
                <p style="margin: 5px 0 0 0;">Active Mentorships</p>
            </div>
        </a>
        <?php if ($profile): ?>
        <a href="/pages/mentor/complete_profile.php" style="text-decoration: none;">
            <div style="background: <?php echo $profile['is_verified'] ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $profile['is_verified'] ? '✓' : '⚠'; ?></h2>
                <p style="margin: 5px 0 0 0;">Verification: <?php echo $profile['is_verified'] ? 'Verified' : 'Pending'; ?></p>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (count($pendingRequests) > 0): ?>
<div class="card">
    <h3>Pending Requests</h3>
    <table>
        <thead>
            <tr>
                <th>Mentee</th>
                <th>Programme</th>
                <th>Requested</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $displayRequests = array_slice($pendingRequests, 0, 5);
            foreach ($displayRequests as $request): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                <td><?php echo PROGRAMME_LEVELS[$request['programme_level']] ?? $request['programme_level']; ?></td>
                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                <td><a href="/pages/mentor/requests.php" class="btn">Review</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($pendingRequests) > 5): ?>
        <p style="margin-top: 10px; text-align: center;">
            <a href="/pages/mentor/requests.php" class="btn btn-secondary">View All <?php echo count($pendingRequests); ?> Requests</a>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

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
            <?php 
            $displayMentorships = array_slice($activeMentorships, 0, 5);
            foreach ($displayMentorships as $mentorship): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($mentorship['first_name'] . ' ' . $mentorship['last_name']); ?></td>
                <td><?php echo PROGRAMME_LEVELS[$mentorship['programme_level']] ?? $mentorship['programme_level']; ?></td>
                <td><?php echo date('M d, Y', strtotime($mentorship['start_date'])); ?></td>
                <td><a href="/pages/mentor/workspace.php?id=<?php echo $mentorship['id']; ?>" class="btn">Workspace</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($activeMentorships) > 5): ?>
        <p style="margin-top: 10px; text-align: center;">
            <a href="/pages/mentor/my_mentees.php" class="btn btn-secondary">View All <?php echo count($activeMentorships); ?> Mentees</a>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . "/../../includes/footer.php"; ?>
 