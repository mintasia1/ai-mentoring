<?php
/**
 * Logout Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /pages/login.php?logged_out=1');
exit();
?>
