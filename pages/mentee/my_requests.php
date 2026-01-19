<?php
/**
 * My Requests - Mentee
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mentorship.php';

Auth::requirePageAccess('mentee_pages');

$pageTitle = 'My Requests';
$userId = Auth::getCurrentUserId();

$mentorshipClass = new Mentorship();

// Get all requests with optional status filter
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter === 'all') {
    $allRequests = $mentorshipClass->getMenteeRequests($userId);
} else {
    $allRequests = $mentorshipClass->getMenteeRequests($userId, $statusFilter);
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$totalRequests = count($allRequests);
$totalPages = ceil($totalRequests / $perPage);
$offset = ($page - 1) * $perPage;
$requests = array_slice($allRequests, $offset, $perPage);

// Count by status
$pendingCount = count(array_filter($allRequests, fn($r) => $r['status'] === 'pending'));
$acceptedCount = count(array_filter($allRequests, fn($r) => $r['status'] === 'accepted'));
$declinedCount = count(array_filter($allRequests, fn($r) => $r['status'] === 'declined'));

include __DIR__ . '/../../includes/header.php';
?>

<h2>My Mentorship Requests</h2>

<div class="card">
    <a href="/pages/mentee/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    <a href="/pages/mentee/browse_mentors.php" class="btn">Browse Mentors</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'request_sent'): ?>
    <div class="alert alert-success">
        Your mentorship request has been sent successfully!
    </div>
<?php endif; ?>

<div class="card">
    <h3>Request Status</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
        <a href="?status=all" style="text-decoration: none;">
            <div style="background: <?php echo $statusFilter === 'all' ? '#2980b9' : '#3498db'; ?>; color: white; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#2980b9'" onmouseout="this.style.background='<?php echo $statusFilter === 'all' ? '#2980b9' : '#3498db'; ?>'">
                <h2 style="margin: 0; color: white;"><?php echo $totalRequests; ?></h2>
                <p style="margin: 5px 0 0 0;">All Requests</p>
            </div>
        </a>
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
    </div>
</div>

<div class="card">
    <h3>My Requests 
        <?php if ($statusFilter !== 'all'): ?>
            - <?php echo ucfirst($statusFilter); ?>
        <?php endif; ?>
    </h3>
    
    <?php if (empty($requests)): ?>
        <p>No requests found.</p>
        <a href="/pages/mentee/browse_mentors.php" class="btn" style="margin-top: 10px;">Browse Mentors</a>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mentor</th>
                    <th>Practice Area</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['practice_area'] ?? 'N/A'); ?></td>
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
                        <?php if ($request['status'] !== 'pending' && $request['mentor_response']): ?>
                            <?php echo htmlspecialchars(substr($request['mentor_response'], 0, 50)); ?>
                            <?php if (strlen($request['mentor_response']) > 50): ?>...<?php endif; ?>
                        <?php else: ?>
                            -
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

<?php include __DIR__ . "/../../includes/footer.php"; ?>
