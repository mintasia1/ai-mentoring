<?php
// Set no-cache headers to prevent caching of sensitive pages
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$isLoggedIn = Auth::isLoggedIn();
$currentRole = Auth::getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/index.php"><?php echo APP_NAME; ?></a></h1>
            <nav>
                <?php if ($isLoggedIn): ?>
                    <?php if ($currentRole === 'mentee'): ?>
                        <a href="/pages/mentee/dashboard.php">Dashboard</a>
                        <a href="/pages/mentee/browse_mentors.php">Browse Mentors</a>
                        <a href="/pages/mentee/my_requests.php">My Requests</a>
                    <?php elseif ($currentRole === 'mentor'): ?>
                        <a href="/pages/mentor/dashboard.php">Dashboard</a>
                        <a href="/pages/mentor/requests.php">Requests</a>
                        <a href="/pages/mentor/my_mentees.php">My Mentees</a>
                    <?php elseif ($currentRole === 'admin'): ?>
                        <a href="/pages/admin/dashboard.php">Dashboard</a>
                        <a href="/pages/admin/users.php">Users</a>
                        <a href="/pages/admin/matches.php">Matches</a>
                    <?php elseif ($currentRole === 'super_admin'): ?>
                        <a href="/pages/super_admin/dashboard.php">Dashboard</a>
                        <a href="/pages/super_admin/admins.php">Admins</a>
                        <a href="/pages/super_admin/audit_logs.php">Audit Logs</a>
                    <?php endif; ?>
                    <a href="/pages/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/index.php">Home</a>
                    <a href="/pages/login.php">Login</a>
                    <a href="/pages/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="container">
