<?php
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: #2c3e50; color: white; padding: 1rem 0; }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.5rem; }
        header h1, header h1 a { color: white; }
        nav a { color: white; text-decoration: none; margin-left: 20px; }
        nav a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { min-height: 100px; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        table tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/index.php"><?php echo APP_NAME; ?></a></h1>
            <nav>
                <?php if ($isLoggedIn):
                    <?php if ($currentRole === 'mentee'):
                        <a href="/pages/mentee/dashboard.php">Dashboard</a>
                        <a href="/pages/mentee/browse_mentors.php">Browse Mentors</a>
                        <a href="/pages/mentee/my_requests.php">My Requests</a>
                    <?php elseif ($currentRole === 'mentor'):
                        <a href="/pages/mentor/dashboard.php">Dashboard</a>
                        <a href="/pages/mentor/requests.php">Requests</a>
                        <a href="/pages/mentor/my_mentees.php">My Mentees</a>
                    <?php elseif ($currentRole === 'admin'):
                        <a href="/pages/admin/dashboard.php">Dashboard</a>
                        <a href="/pages/admin/users.php">Users</a>
                        <a href="/pages/admin/matches.php">Matches</a>
                    <?php elseif ($currentRole === 'super_admin'):
                        <a href="/pages/super_admin/dashboard.php">Dashboard</a>
                        <a href="/pages/super_admin/admins.php">Admins</a>
                        <a href="/pages/super_admin/audit_logs.php">Audit Logs</a>
                    <?php endif;
                    <a href="/pages/logout.php">Logout</a>
                <?php else:
                    <a href="/index.php">Home</a>
                    <a href="/pages/login.php">Login</a>
                    <a href="/pages/register.php">Register</a>
                <?php endif;
            </nav>
        </div>
    </header>
    <div class="container">
