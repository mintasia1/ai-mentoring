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

Auth::requirePageAccess('mentee_pages');

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
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="/pages/mentee/browse_mentors.php" style="text-decoration: none;">
            <div style="background: #27ae60; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo count($activeMentorships); ?></h2>
                <p style="margin: 5px 0 0 0;">Active Mentorships</p>
            </div>
        </a>
        <a href="/pages/mentee/my_requests.php" style="text-decoration: none;">
            <div style="background: #f39c12; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo count($pendingRequests); ?></h2>
                <p style="margin: 5px 0 0 0;">Pending Requests</p>
            </div>
        </a>
        <?php if ($profile): ?>
        <a href="/pages/mentee/profile.php" style="text-decoration: none;">
            <div style="background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <h2 style="margin: 0; color: white;"><?php echo $profile['rematch_count']; ?> / <?php echo REMATCH_LIMIT; ?></h2>
                <p style="margin: 5px 0 0 0;">Re-match Opportunities</p>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (count($pendingRequests) > 0): ?>
<div class="card">
    <h3>My Pending Requests</h3>
    <table>
        <thead>
            <tr>
                <th>Mentor</th>
                <th>Practice Area</th>
                <th>Requested</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $displayRequests = array_slice($pendingRequests, 0, 5);
            foreach ($displayRequests as $request): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                <td><?php echo htmlspecialchars($request['practice_area'] ?? 'N/A'); ?></td>
                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                <td><span class="badge badge-warning">Pending</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($pendingRequests) > 5): ?>
        <p style="margin-top: 10px; text-align: center;">
            <a href="/pages/mentee/my_requests.php" class="btn btn-secondary">View All <?php echo count($pendingRequests); ?> Requests</a>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

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
            <?php 
            $displayMentorships = array_slice($activeMentorships, 0, 5);
            foreach ($displayMentorships as $mentorship): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($mentorship['first_name'] . ' ' . $mentorship['last_name']); ?></td>
                <td><?php echo htmlspecialchars($mentorship['practice_area'] ?? 'N/A'); ?></td>
                <td><?php echo date('M d, Y', strtotime($mentorship['start_date'])); ?></td>
                <td><a href="/pages/mentee/workspace.php?id=<?php echo $mentorship['id']; ?>" class="btn">Workspace</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($activeMentorships) > 5): ?>
        <p style="margin-top: 10px; text-align: center;">
            <a href="/pages/mentee/browse_mentors.php" class="btn btn-secondary">View All Active Mentorships</a>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
