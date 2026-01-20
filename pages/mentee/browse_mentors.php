<?php
/**
 * Browse Mentors Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Matching.php';
require_once __DIR__ . '/../../classes/Mentee.php';
require_once __DIR__ . '/../../classes/Mentorship.php';
require_once __DIR__ . '/../../classes/CSRFToken.php';
require_once __DIR__ . '/../../classes/Logger.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'Browse Mentors';
$bodyClass = 'mentee-browse-mentors';
$userId = Auth::getCurrentUserId();

Logger::debug("Browse mentors page accessed", ['user_id' => $userId]);

$menteeClass = new Mentee();
$profile = $menteeClass->getProfile($userId);

if (!$profile) {
    header('Location: /pages/mentee/profile.php?error=complete_profile');
    exit();
}

// Get all pending requests for this mentee
$mentorshipClass = new Mentorship();
$pendingRequests = $mentorshipClass->getMenteeRequests($userId, 'pending');
$pendingMentorIds = array_column($pendingRequests, 'mentor_id');

$matchingClass = new Matching();
$allRecommendedMentors = $matchingClass->getRecommendedMentors($userId, 100); // Get more for pagination

// Pagination
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
$perPage = min(max($perPage, 10), 100); // Between 10 and 100
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max($page, 1);

$totalMentors = count($allRecommendedMentors);
$totalPages = ceil($totalMentors / $perPage);
$page = min($page, max($totalPages, 1));

$offset = ($page - 1) * $perPage;
$recommendedMentors = array_slice($allRecommendedMentors, $offset, $perPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>Browse Mentors
    <span style="cursor: help; margin-left: 10px;" title="Match Score: Mentors are ranked by compatibility based on Practice Area (40%), Programme Level (20%), Interests (15%), Location (15%), and Language (10%). Only verified mentors in your preferred practice area are shown.">ℹ️</span>
</h2>

<?php
// Display error messages
if (isset($_GET['error'])):
    $errorMessages = [
        'invalid_mentor' => 'Invalid mentor selected.',
        'mentor_not_found' => 'Mentor not found.',
        'mentor_not_verified' => 'This mentor is not verified.',
        'mentor_no_capacity' => 'This mentor has no available capacity.',
        'request_pending' => 'You already have a pending request with this mentor.'
    ];
    $errorMsg = $errorMessages[$_GET['error']] ?? 'An error occurred.';
?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($errorMsg); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'request_sent'): ?>
    <div class="alert alert-success">
        Your mentorship request has been sent successfully!
    </div>
<?php endif; ?>

<?php if (empty($allRecommendedMentors)): ?>
    <div class="card">
        <p>No available mentors found matching your criteria at this time. Please check back later.</p>
    </div>
<?php else: ?>
    <div class="card">
        <!-- Pagination Controls Top -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="perPageTop">Show:</label>
                <select id="perPageTop" onchange="window.location.href='?per_page='+this.value" style="padding: 5px;">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <span>entries</span>
            </div>
            <div>
                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalMentors); ?> of <?php echo $totalMentors; ?> mentors
            </div>
        </div>

        <!-- Mentors Table -->
        <table id="mentorsTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)" style="cursor: pointer;">Match Score ↕</th>
                    <th onclick="sortTable(1)" style="cursor: pointer;">Name ↕</th>
                    <th onclick="sortTable(2)" style="cursor: pointer;">Practice Area ↕</th>
                    <th onclick="sortTable(3)" style="cursor: pointer;">Programme Level ↕</th>
                    <th onclick="sortTable(4)" style="cursor: pointer;">Position / Company ↕</th>
                    <th onclick="sortTable(5)" style="cursor: pointer;">Location ↕</th>
                    <th onclick="sortTable(6)" style="cursor: pointer;">Availability ↕</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recommendedMentors as $mentor): ?>
                <tr>
                    <td>
                        <span class="badge badge-info" title="Compatibility score based on practice area (40%), programme (20%), interests (15%), location (15%), and language (10%)">
                            <?php echo round($mentor['match_score'], 1); ?>%
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['practice_area']); ?></td>
                    <td><?php echo PROGRAMME_LEVELS[$mentor['programme_level']] ?? $mentor['programme_level']; ?></td>
                    <td>
                        <?php if ($mentor['current_position']): ?>
                            <?php echo htmlspecialchars($mentor['current_position']); ?>
                            <?php if ($mentor['company']): ?> @ <?php echo htmlspecialchars($mentor['company']); ?><?php endif; ?>
                        <?php else: ?>
                            <span style="color: #999;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $mentor['location'] ? htmlspecialchars($mentor['location']) : '<span style="color: #999;">N/A</span>'; ?></td>
                    <td><?php echo $mentor['current_mentees']; ?> / <?php echo $mentor['max_mentees']; ?></td>
                    <td>
                        <?php if (in_array($mentor['user_id'], $pendingMentorIds)): ?>
                            <span class="badge badge-warning">Pending Approval</span>
                        <?php else: ?>
                            <button onclick="openSendRequestModal(<?php echo $mentor['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($mentor['first_name'] . ' ' . $mentor['last_name'])); ?>')" class="btn">Send Request</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Controls Bottom -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="perPageBottom">Show:</label>
                <select id="perPageBottom" onchange="window.location.href='?per_page='+this.value" style="padding: 5px;">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <span>entries</span>
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">First</a>
                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>
                <span style="margin: 0 10px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Next</a>
                    <a href="?page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Last</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Send Request Modal -->
    <div id="sendRequestModal" class="modal">
        <div class="modal-content">
            <span onclick="closeSendRequestModal()" class="modal-close">&times;</span>
            <h3 style="margin-top: 0;">Send Mentorship Request</h3>
            <p>Send a mentorship request to <strong id="mentorNameDisplay"></strong></p>
            <form id="sendRequestForm" method="POST" action="/pages/mentee/send_request.php">
                <?php echo CSRFToken::getField(); ?>
                <input type="hidden" name="mentor_id" id="modal_mentor_id" value="">
                <div class="form-group">
                    <label for="modal_message">Message (Optional):</label>
                    <textarea id="modal_message" name="message" rows="4" maxlength="500" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Introduce yourself and explain why you'd like to connect with this mentor..."></textarea>
                    <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">Maximum 500 characters</p>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn" style="margin-right: 10px;">Send Request</button>
                    <button type="button" onclick="closeSendRequestModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/browse-mentors.js"></script>
<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
