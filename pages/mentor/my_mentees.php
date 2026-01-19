<?php
/**
 * Mentor My Mentees Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentorship.php';

Auth::requirePageAccess('mentor_pages');

$pageTitle = 'My Mentees';
$userId = Auth::getCurrentUserId();

$mentorshipClass = new Mentorship();
$activeMentorships = $mentorshipClass->getActiveMentorships($userId, 'mentor');

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$totalMentees = count($activeMentorships);
$totalPages = ceil($totalMentees / $perPage);
$offset = ($page - 1) * $perPage;
$mentees = array_slice($activeMentorships, $offset, $perPage);

include __DIR__ . '/../../includes/header.php';
?>

<h2>My Mentees</h2>

<div class="card">
    <a href="/pages/mentor/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<div class="card">
    <h3>Active Mentorships (<?php echo $totalMentees; ?>)</h3>
    
    <?php if (empty($mentees)): ?>
        <p>You currently have no active mentees.</p>
        <p>Pending requests will appear on your <a href="/pages/mentor/requests.php">Requests page</a>.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mentee</th>
                    <th>Email</th>
                    <th>Programme Level</th>
                    <th>Year of Study</th>
                    <th>Start Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mentees as $mentee): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($mentee['email']); ?></td>
                    <td><?php echo PROGRAMME_LEVELS[$mentee['programme_level']] ?? $mentee['programme_level']; ?></td>
                    <td><?php echo htmlspecialchars($mentee['year_of_study'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($mentee['start_date'])); ?></td>
                    <td>
                        <button type="button" onclick="toggleDetails(<?php echo $mentee['id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em; margin-right: 5px;">View Details</button>
                        <?php if (file_exists(__DIR__ . '/workspace.php')): ?>
                        <a href="/pages/mentor/workspace.php?id=<?php echo $mentee['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.9em;">Workspace</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="details-<?php echo $mentee['id']; ?>" style="display: none;">
                    <td colspan="6" style="background: #f8f9fa; padding: 20px;">
                        <h4>Mentee Profile</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <div>
                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($mentee['student_id'] ?? 'N/A'); ?></p>
                                <p><strong>Programme Level:</strong> <?php echo PROGRAMME_LEVELS[$mentee['programme_level']] ?? $mentee['programme_level']; ?></p>
                                <p><strong>Year of Study:</strong> <?php echo htmlspecialchars($mentee['year_of_study'] ?? 'N/A'); ?></p>
                                <p><strong>Practice Area Preference:</strong> <?php echo htmlspecialchars($mentee['practice_area_preference'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p><strong>Language Preference:</strong> <?php echo htmlspecialchars($mentee['language_preference'] ?? 'N/A'); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($mentee['location'] ?? 'N/A'); ?></p>
                                <p><strong>Interests:</strong> <?php echo htmlspecialchars($mentee['interests'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <?php if ($mentee['goals']): ?>
                            <p><strong>Goals:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentee['goals'])); ?></p>
                        <?php endif; ?>
                        <?php if ($mentee['bio']): ?>
                            <p><strong>Bio:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($mentee['bio'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Mentorship Started:</strong> <?php echo date('F d, Y', strtotime($mentee['start_date'])); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetails(menteeId) {
    var detailsRow = document.getElementById('details-' + menteeId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
