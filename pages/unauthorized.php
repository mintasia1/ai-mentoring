<?php
/**
 * Unauthorized Access Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

$pageTitle = 'Unauthorized - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Unauthorized Access</h2>
    <p>You do not have permission to access this page.</p>
    <a href="/index.php" class="btn">Go to Home</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
