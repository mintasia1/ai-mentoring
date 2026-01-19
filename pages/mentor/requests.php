<?php
/**
 * Mentor Requests Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentorship.php';
require_once __DIR__ . '/../../classes/Mentor.php';

Auth::requirePageAccess('mentor_pages');

$pageTitle = 'Mentorship Requests';
$userId = Auth::getCurrentUserId();

$mentorshipClass = new Mentorship();
$mentorClass = new Mentor();

$message = '';
$messageType = '';

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $response = trim($_POST['response'] ?? '');
    
    // Verify the request belongs to this mentor
    $stmt = $this->db->prepare("SELECT mentor_id FROM mentorship_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $requestCheck = $stmt->fetch();
    
    if (!$requestCheck || $requestCheck['mentor_id'] != $userId) {
        $message = 'Invalid request or unauthorized access';
        $messageType = 'error';
    } elseif ($action === 'accept') {
        $result = $mentorshipClass->acceptRequest($requestId, $userId, $response);
        if ($result['success']) {
            $message = 'Request accepted successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif ($action === 'decline') {
        $result = $mentorshipClass->declineRequest($requestId, $userId, $response);
        if ($result['success']) {
            $message = 'Request declined';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Get all requests with optional status filter
$statusFilter = $_GET['status'] ?? 'pending';
if ($statusFilter === 'all') {
    $allRequests = $mentorshipClass->getMentorRequests($userId);
} else {
    $allRequests = $mentorshipClass->getMentorRequests($userId, $statusFilter);
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$totalRequests = count($allRequests);
$totalPages = ceil($totalRequests / $perPage);
$offset = ($page - 1) * $perPage;
$requests = array_slice($allRequests, $offset, $perPage);

// Count by status
$pendingCount = count($mentorshipClass->getMentorRequests($userId, 'pending'));
$acceptedCount = count($mentorshipClass->getMentorRequests($userId, 'accepted'));
$declinedCount = count($mentorshipClass->getMentorRequests($userId, 'declined'));

include __DIR__ . '/../../includes/header.php';
?>

<h2>Mentorship Requests</h2>

<div class="card">
    <a href="/pages/mentor/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Request Status</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="?status=pending" style="text-decoration: none;">
            <div style="background: <?php echo $statusFilter === 'pending' ? '#d68910' : '#f39c12'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#d68910'" onmouseout="this.style.background='<?php echo $statusFilter === 'pending' ? '#d68910' : '#f39c12'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $pendingCount; ?></h2>
                <p style="margin: 5px 0 0 0;">Pending</p>
            </div>
        </a>
        <a href="?status=accepted" style="text-decoration: none;">
            <div style="background: <?php echo $statusFilter === 'accepted' ? '#229954' : '#27ae60'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#229954'" onmouseout="this.style.background='<?php echo $statusFilter === 'accepted' ? '#229954' : '#27ae60'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $acceptedCount; ?></h2>
                <p style="margin: 5px 0 0 0;">Accepted</p>
            </div>
        </a>
        <a href="?status=declined" style="text-decoration: none;">
            <div style="background: <?php echo $statusFilter === 'declined' ? '#c0392b' : '#e74c3c'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#c0392b'" onmouseout="this.style.background='<?php echo $statusFilter === 'declined' ? '#c0392b' : '#e74c3c'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $declinedCount; ?></h2>
                <p style="margin: 5px 0 0 0;">Declined</p>
            </div>
        </a>
        <a href="?status=all" style="text-decoration: none;">
            <div style="background: <?php echo $statusFilter === 'all' ? '#2980b9' : '#3498db'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#2980b9'" onmouseout="this.style.background='<?php echo $statusFilter === 'all' ? '#2980b9' : '#3498db'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $pendingCount + $acceptedCount + $declinedCount; ?></h2>
                <p style="margin: 5px 0 0 0;">All Requests</p>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <h3>Requests 
        <?php if ($statusFilter !== 'all'): ?>
            - <?php echo ucfirst($statusFilter); ?>
        <?php endif; ?>
    </h3>
    
    <?php if (empty($requests)): ?>
        <p>No requests found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mentee</th>
                    <th>Programme Level</th>
                    <th>Practice Area</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                    <td><?php echo PROGRAMME_LEVELS[$request['programme_level']] ?? $request['programme_level']; ?></td>
                    <td><?php echo htmlspecialchars($request['practice_area_preference'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php elseif ($request['status'] === 'accepted'): ?>
                            <span class="badge badge-success">Accepted</span>
                        <?php elseif ($request['status'] === 'declined'): ?>
                            <span class="badge" style="background: #ffcccc; color: #c00;">Declined</span>
                        <?php else: ?>
                            <span class="badge"><?php echo htmlspecialchars($request['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <button type="button" onclick='showRequestModal(<?php echo $request['id']; ?>, <?php echo json_encode($request['first_name'] . ' ' . $request['last_name']); ?>, <?php echo json_encode($request['message'] ?? ''); ?>)' class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;">Review</button>
                        <?php else: ?>
                            <button type="button" onclick="toggleDetails(<?php echo $request['id']; ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9em;">View Details</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="details-<?php echo $request['id']; ?>" style="display: none;">
                    <td colspan="6" style="background: #f8f9fa; padding: 20px;">
                        <h4>Request Details</h4>
                        <?php if ($request['message']): ?>
                            <p><strong>Message from Mentee:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($request['message'])); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Mentee Profile:</strong></p>
                        <div style="margin-left: 20px;">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($request['student_id'] ?? 'N/A'); ?></p>
                            <p><strong>Year of Study:</strong> <?php echo htmlspecialchars($request['year_of_study'] ?? 'N/A'); ?></p>
                            <p><strong>Interests:</strong> <?php echo htmlspecialchars($request['interests'] ?? 'N/A'); ?></p>
                            <?php if ($request['goals']): ?>
                                <p><strong>Goals:</strong></p>
                                <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($request['goals'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($request['mentor_response']): ?>
                            <p><strong>Your Response:</strong></p>
                            <p style="margin-left: 20px;"><?php echo nl2br(htmlspecialchars($request['mentor_response'])); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">« Previous</a>
            <?php endif; ?>
            
            <span style="margin: 0 15px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Request Review Modal -->
<div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 8px;">
        <h3>Review Mentorship Request</h3>
        <p><strong>Mentee:</strong> <span id="modalMenteeName"></span></p>
        <div id="modalMessage" style="display: none;">
            <p><strong>Message:</strong></p>
            <p id="modalMessageText" style="margin-left: 20px; background: #f8f9fa; padding: 15px; border-radius: 5px;"></p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="request_id" id="modalRequestId">
            <div class="form-group">
                <label for="response">Response Message (Optional)</label>
                <textarea id="response" name="response" maxlength="500" placeholder="Add a personal message to your response..."></textarea>
                <p style="font-size: 0.9rem; color: #666;">Maximum 500 characters</p>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="action" value="decline" class="btn btn-danger" onclick="return confirm('Are you sure you want to decline this request?')">Decline</button>
                <button type="submit" name="action" value="accept" class="btn btn-success" onclick="return confirm('Are you sure you want to accept this request?')">Accept</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDetails(requestId) {
    var detailsRow = document.getElementById('details-' + requestId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}

function showRequestModal(requestId, menteeName, message) {
    document.getElementById('modalRequestId').value = requestId;
    document.getElementById('modalMenteeName').textContent = menteeName;
    
    if (message && message.trim() !== '') {
        document.getElementById('modalMessage').style.display = 'block';
        document.getElementById('modalMessageText').textContent = message;
    } else {
        document.getElementById('modalMessage').style.display = 'none';
    }
    
    document.getElementById('requestModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('requestModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('requestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
