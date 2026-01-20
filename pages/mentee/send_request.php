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
require_once __DIR__ . '/../../classes/CSRFToken.php';
require_once __DIR__ . '/../../classes/Logger.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'Send Mentorship Request';
$bodyClass = 'mentee-send-request';
$userId = Auth::getCurrentUserId();

// Get mentor ID from POST (modal) or GET (direct access)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mentorId = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : 0;
} else {
    $mentorId = isset($_GET['mentor_id']) ? intval($_GET['mentor_id']) : 0;
}

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

// Handle form submission (both GET and POST from modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFToken::validate($_POST['csrf_token'] ?? '')) {
        Logger::warning("CSRF validation failed on send request", ['user_id' => $userId]);
        header('Location: /pages/mentee/browse_mentors.php?error=invalid_request');
        exit();
    }
    
    $requestMessage = trim($_POST['message'] ?? '');
    $postMentorId = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : $mentorId;
    
    Logger::debug("Processing mentorship request", ['mentee_id' => $userId, 'mentor_id' => $postMentorId, 'message_length' => strlen($requestMessage)]);
    
    // Validate mentor ID
    if (!$postMentorId) {
        Logger::warning("Invalid mentor ID in request", ['user_id' => $userId]);
        header('Location: /pages/mentee/browse_mentors.php?error=invalid_mentor');
        exit();
    }
    
    // Re-validate mentor
    $mentorProfile = $mentorClass->getProfile($postMentorId);
    if (!$mentorProfile || !$mentorProfile['is_verified'] || !$mentorClass->hasCapacity($postMentorId)) {
        Logger::warning("Invalid mentor in request submission", ['user_id' => $userId, 'mentor_id' => $postMentorId]);
        header('Location: /pages/mentee/browse_mentors.php?error=invalid_mentor');
        exit();
    }
    
    $mentorshipClass = new Mentorship();
    $result = $mentorshipClass->createRequest($userId, $postMentorId, $requestMessage);
    
    if ($result['success']) {
        Logger::info("Mentorship request created successfully", ['mentee_id' => $userId, 'mentor_id' => $postMentorId, 'request_id' => $result['request_id'] ?? 'unknown']);
        header('Location: /pages/mentee/browse_mentors.php?success=request_sent');
        exit();
    } else {
        Logger::warning("Mentorship request failed", ['mentee_id' => $userId, 'mentor_id' => $postMentorId, 'reason' => $result['message']]);
        // Redirect with error
        if (strpos($result['message'], 'already pending') !== false) {
            header('Location: /pages/mentee/browse_mentors.php?error=request_pending');
        } else {
            header('Location: /pages/mentee/browse_mentors.php?error=mentor_no_capacity');
        }
        exit();
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
        <?php echo CSRFToken::getField(); ?>
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
