<?php
/**
 * Browse Mentors Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Matching.php';
require_once __DIR__ . '/../../classes/Mentee.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'Browse Mentors';
$userId = Auth::getCurrentUserId();

$menteeClass = new Mentee();
$profile = $menteeClass->getProfile($userId);

if (!$profile) {
    header('Location: /pages/mentee/profile.php?error=complete_profile');
    exit();
}

$matchingClass = new Matching();
$recommendedMentors = $matchingClass->getRecommendedMentors($userId, 20);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Browse Mentors</h2>

<div class="alert alert-info">
    <strong>About Match Score:</strong> Mentors are ranked by compatibility with your profile based on multiple factors:
    <ul style="margin: 10px 0 0 20px;">
        <li><strong>Practice Area (40%):</strong> Mandatory match with your preference</li>
        <li><strong>Programme Level (20%):</strong> Alignment with your educational background</li>
        <li><strong>Interests (15%):</strong> Similarity in professional interests</li>
        <li><strong>Location (15%):</strong> Geographic proximity</li>
        <li><strong>Language (10%):</strong> Language preference match</li>
    </ul>
    Higher scores indicate better compatibility. Practice area match is mandatory - only mentors in your preferred area are shown.
</div>

<?php if (empty($recommendedMentors)): ?>
    <div class="card">
        <p>No available mentors found matching your criteria at this time. Please check back later.</p>
    </div>
<?php else: ?>
    <?php foreach ($recommendedMentors as $mentor): ?>
    <div class="card">
        <h3><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h3>
        <p><strong>Practice Area:</strong> <?php echo htmlspecialchars($mentor['practice_area']); ?></p>
        <p><strong>Programme Level:</strong> <?php echo PROGRAMME_LEVELS[$mentor['programme_level']] ?? $mentor['programme_level']; ?></p>
        <?php if ($mentor['current_position']): ?>
            <p><strong>Current Position:</strong> <?php echo htmlspecialchars($mentor['current_position']); ?>
            <?php if ($mentor['company']): ?> at <?php echo htmlspecialchars($mentor['company']); ?><?php endif; ?></p>
        <?php endif; ?>
        <?php if ($mentor['location']): ?>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($mentor['location']); ?></p>
        <?php endif; ?>
        <?php if ($mentor['bio']): ?>
            <p><strong>Bio:</strong> <?php echo htmlspecialchars(substr($mentor['bio'], 0, 200)); ?>...</p>
        <?php endif; ?>
        <p><strong>Availability:</strong> <?php echo $mentor['current_mentees']; ?> / <?php echo $mentor['max_mentees']; ?> mentees</p>
        <p><strong>Match Score:</strong> <span class="badge badge-info" title="Compatibility score based on practice area, programme, interests, location, and language"><?php echo round($mentor['match_score'], 1); ?>%</span></p>
        <a href="/pages/mentee/send_request.php?mentor_id=<?php echo $mentor['user_id']; ?>" class="btn">Send Request</a>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
