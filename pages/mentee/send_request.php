<?php
/**
 * Send Mentorship Request
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentorship.php';
require_once __DIR__ . '/../../classes/Mentor.php';
require_once __DIR__ . '/../../classes/Mentee.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'Send Mentorship Request';
$userId = Auth::getCurrentUserId();

// Get mentor ID from query string
$mentorId = isset($_GET['mentor_id']) ? intval($_GET['mentor_id']) : 0;

if (!$mentorId) {
    header('Location: /pages/mentee/browse_mentors.php?error=invalid_mentor');
    exit();
}

$mentorClass = new Mentor();
$mentorProfile = $mentorClass->getProfile($mentorId);

if (!$mentorProfile) {
    header('Location: /pages/mentee/browse_mentors.php?error=mentor_not_found');
    exit();
}

// Check if mentor is verified and active
if (!$mentorProfile['is_verified']) {
    header('Location: /pages/mentee/browse_mentors.php?error=mentor_not_verified');
    exit();
}

// Check if mentor has capacity
if (!$mentorClass->hasCapacity($mentorId)) {
    header('Location: /pages/mentee/browse_mentors.php?error=mentor_no_capacity');
    exit();
}

$menteeClass = new Mentee();
$menteeProfile = $menteeClass->getProfile($userId);

if (!$menteeProfile) {
    header('Location: /pages/mentee/profile.php?error=complete_profile');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $requestMessage = trim($_POST['message'] ?? '');
    
    $mentorshipClass = new Mentorship();
    $result = $mentorshipClass->createRequest($userId, $mentorId, $requestMessage);
    
    if ($result['success']) {
        header('Location: /pages/mentee/my_requests.php?success=request_sent');
        exit();
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h2>Send Mentorship Request</h2>

<div class="card">
    <a href="/pages/mentee/browse_mentors.php" class="btn btn-secondary">← Back to Browse Mentors</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Mentor Profile</h3>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($mentorProfile['first_name'] . ' ' . $mentorProfile['last_name']); ?></p>
    <p><strong>Practice Area:</strong> <?php echo htmlspecialchars($mentorProfile['practice_area']); ?></p>
    <p><strong>Programme Level:</strong> <?php echo PROGRAMME_LEVELS[$mentorProfile['programme_level']] ?? $mentorProfile['programme_level']; ?></p>
    <?php if ($mentorProfile['current_position']): ?>
        <p><strong>Current Position:</strong> <?php echo htmlspecialchars($mentorProfile['current_position']); ?>
        <?php if ($mentorProfile['company']): ?> at <?php echo htmlspecialchars($mentorProfile['company']); ?><?php endif; ?></p>
    <?php endif; ?>
    <?php if ($mentorProfile['location']): ?>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($mentorProfile['location']); ?></p>
    <?php endif; ?>
    <?php if ($mentorProfile['bio']): ?>
        <p><strong>Bio:</strong></p>
        <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentorProfile['bio'])); ?></p>
    <?php endif; ?>
    <p><strong>Availability:</strong> <?php echo $mentorProfile['current_mentees']; ?> / <?php echo $mentorProfile['max_mentees']; ?> mentees</p>
</div>

<div class="card">
    <h3>Send Your Request</h3>
    <form method="POST">
        <div class="form-group">
            <label for="message">Message to Mentor (Optional)</label>
            <textarea 
                id="message" 
                name="message" 
                maxlength="500"
                placeholder="Introduce yourself and explain why you'd like this mentor to guide you..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            <p style="font-size: 0.9rem; color: #666;">Maximum 500 characters</p>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="/pages/mentee/browse_mentors.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="send_request" class="btn btn-success">Send Request</button>
        </div>
    </form>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
