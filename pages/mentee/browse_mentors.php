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
require_once __DIR__ . '/../../classes/Database.php';

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

<h2>Browse Mentors</h2>
<div class="alert alert-info">
    <p>Mentors are sorted by AI-powered compatibility based on your profile.</p>
    <p><strong>About Match Score:</strong> Each factor is scored 0–100% and weighted:</p>
    <ul style="margin: 10px 0 0 20px;">
        <li><strong>Practice Area (35%):</strong> OpenAI semantic embedding — compares your preferred area against the mentor's practice area</li>
        <li><strong>Interests &amp; Goals (25%):</strong> OpenAI semantic embedding — your interests, goals &amp; expectations vs mentor's expertise, interests &amp; bio</li>
        <li><strong>Programme Level (15%):</strong> OpenAI semantic embedding on descriptive labels — captures knowledge-level hierarchy (e.g. LLM is closer to PhD than JD)</li>
        <li><strong>Location (10%):</strong> GPT-4o-mini HK district-aware proximity — same district scores higher than cross-harbour or New Territories</li>
        <li><strong>Language (10%):</strong> Exact match on language preference</li>
        <li><strong>Mentoring Style (5%):</strong> Mentor offering "All styles" matches any mentee; otherwise exact match required</li>
    </ul>
    <p>🟢 Excellent Match ≥80% &nbsp; 🟡 Good Match 60–79% &nbsp; ⚪ Partial Match &lt;60%</p>
    <p style="margin-top:6px;font-size:0.9rem;color:#555;">💡 Click a mentor's name for full profile details including mentoring style and interests.</p>
</div>

<?php
// Display error messages
if (isset($_GET['error'])):
    $errorMessages = [
        'invalid_mentor' => 'Invalid mentor selected.',
        'mentor_not_found' => 'Mentor not found.',
        'mentor_not_verified' => 'This mentor is not verified.',
        'mentor_no_capacity' => 'This mentor has no available capacity.',
        'request_pending' => 'You already have a pending request with this mentor.',
        'rematch_limit' => 'You have used all your re-match opportunities and cannot send further requests.'
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
                        <?php
                            $score = round($mentor['match_score'], 1);
                            if ($score >= 80) {
                                $badgeColor = '#27ae60'; $matchLabel = '🟢 Excellent';
                            } elseif ($score >= 60) {
                                $badgeColor = '#f39c12'; $matchLabel = '🟡 Good';
                            } else {
                                $badgeColor = '#95a5a6'; $matchLabel = '⚪ Partial';
                            }
                        ?>
                        <span style="display:inline-block;padding:3px 8px;border-radius:12px;background:<?php echo $badgeColor; ?>;color:#fff;font-size:0.85rem;font-weight:600;" title="Compatibility score based on AI semantic matching">
                            <?php echo $score; ?>%
                        </span>
                        <div style="font-size:0.75rem;color:#666;margin-top:2px;"><?php echo $matchLabel; ?></div>
                        <?php
                            // Show cached AI explanation if available
                            $stmt = Database::getInstance()->getConnection()->prepare(
                                "SELECT ai_explanation FROM matching_scores WHERE mentee_id = ? AND mentor_id = ? AND ai_explanation IS NOT NULL"
                            );
                            $stmt->execute([$userId, $mentor['user_id']]);
                            $aiRow = $stmt->fetch();
                            if ($aiRow && $aiRow['ai_explanation']):
                        ?>
                        <details style="margin-top:4px;font-size:0.8rem;">
                            <summary style="cursor:pointer;color:#3498db;">Why this match?</summary>
                            <p style="margin:6px 0 0;color:#555;line-height:1.4;"><?php echo htmlspecialchars($aiRow['ai_explanation']); ?></p>
                        </details>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="#" onclick="showMentorProfile(<?php echo $mentor['user_id']; ?>); return false;"
                           style="font-weight:600;color:#2c3e50;text-decoration:underline dotted;cursor:pointer;">
                            <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                        </a>
                    </td>
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

    <!-- Mentor Profile Modal -->
    <div id="mentorProfileModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1100;overflow-y:auto;">
        <div style="background:#fff;max-width:620px;margin:50px auto;padding:30px;border-radius:8px;position:relative;">
            <button onclick="document.getElementById('mentorProfileModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#555;">&times;</button>
            <h3 id="mpName" style="margin-top:0;"></h3>
            <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
                <tr><th style="text-align:left;padding:6px 10px;width:38%;color:#555;font-weight:600;border-bottom:1px solid #eee;">Practice Area</th><td id="mpPracticeArea" style="padding:6px 10px;border-bottom:1px solid #eee;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Programme Level</th><td id="mpProgramme" style="padding:6px 10px;border-bottom:1px solid #eee;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Mentoring Style</th><td id="mpMentoringStyle" style="padding:6px 10px;border-bottom:1px solid #eee;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Expertise</th><td id="mpExpertise" style="padding:6px 10px;border-bottom:1px solid #eee;white-space:pre-wrap;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Interests</th><td id="mpInterests" style="padding:6px 10px;border-bottom:1px solid #eee;white-space:pre-wrap;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Bio</th><td id="mpBio" style="padding:6px 10px;border-bottom:1px solid #eee;white-space:pre-wrap;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Current Position</th><td id="mpPosition" style="padding:6px 10px;border-bottom:1px solid #eee;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;border-bottom:1px solid #eee;">Location</th><td id="mpLocation" style="padding:6px 10px;border-bottom:1px solid #eee;"></td></tr>
                <tr><th style="text-align:left;padding:6px 10px;color:#555;font-weight:600;">Availability</th><td id="mpAvailability" style="padding:6px 10px;"></td></tr>
            </table>
            <div style="margin-top:20px;text-align:right;" id="mpRequestBtnWrapper"></div>
        </div>
    </div>

    <!-- Mentor data for JS -->
    <script>
    var mentorData = <?php
        $mentorMap = [];
        $styleLabels = defined('MENTORING_STYLES') ? MENTORING_STYLES : [];
        $progLabels  = defined('PROGRAMME_LEVELS') ? PROGRAMME_LEVELS : [];
        foreach ($allRecommendedMentors as $m) {
            $styleKey = $m['mentoring_style'] ?? 'all';
            $mentorMap[$m['user_id']] = [
                'name'           => $m['first_name'] . ' ' . $m['last_name'],
                'practice_area'  => $m['practice_area'] ?? '',
                'programme'      => $progLabels[$m['programme_level']] ?? $m['programme_level'] ?? '',
                'mentoring_style'=> $styleLabels[$styleKey] ?? ucfirst(str_replace('_', ' ', $styleKey)),
                'expertise'      => $m['expertise'] ?? '',
                'interests'      => $m['interests'] ?? '',
                'bio'            => $m['bio'] ?? '',
                'position'       => trim(($m['current_position'] ?? '') . ($m['company'] ? ' @ ' . $m['company'] : '')),
                'location'       => $m['location'] ?? '',
                'current_mentees'=> $m['current_mentees'],
                'max_mentees'    => $m['max_mentees'],
                'pending'        => in_array($m['user_id'], $pendingMentorIds),
            ];
        }
        echo json_encode($mentorMap, JSON_HEX_TAG | JSON_HEX_QUOT);
    ?>;

    function showMentorProfile(mentorId) {
        var m = mentorData[mentorId];
        if (!m) return;
        document.getElementById('mpName').textContent         = m.name;
        document.getElementById('mpPracticeArea').textContent = m.practice_area  || '—';
        document.getElementById('mpProgramme').textContent    = m.programme       || '—';
        document.getElementById('mpMentoringStyle').textContent = m.mentoring_style || '—';
        document.getElementById('mpExpertise').textContent    = m.expertise       || '—';
        document.getElementById('mpInterests').textContent    = m.interests       || '—';
        document.getElementById('mpBio').textContent          = m.bio             || '—';
        document.getElementById('mpPosition').textContent     = m.position        || '—';
        document.getElementById('mpLocation').textContent     = m.location        || '—';
        document.getElementById('mpAvailability').textContent = m.current_mentees + ' / ' + m.max_mentees + ' mentees';
        var btn = document.getElementById('mpRequestBtnWrapper');
        if (m.pending) {
            btn.innerHTML = '<span class="badge badge-warning">Request Pending</span>';
        } else {
            btn.innerHTML = '<button class="btn" onclick="document.getElementById(\'mentorProfileModal\').style.display=\'none\';openSendRequestModal(' + mentorId + ',\'' + m.name.replace(/'/g,"\\'"  ) + '\')">Send Request</button>';
        }
        document.getElementById('mentorProfileModal').style.display = 'block';
    }

    document.getElementById('mentorProfileModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    </script>

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
 
