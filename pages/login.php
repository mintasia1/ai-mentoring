<?php
/**
 * Login Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/CSRFToken.php';
require_once __DIR__ . '/../classes/SpamProtection.php';

$pageTitle = 'Login - ' . APP_NAME;
$error = '';
$success = '';

if (Auth::isLoggedIn()) {
    $role = Auth::getCurrentUserRole();
    header("Location: /pages/$role/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFToken::validate($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    }
    // Honeypot check
    elseif (!SpamProtection::checkHoneypot()) {
        $error = 'Invalid request. Please try again.';
    }
    // Rate limiting check
    elseif (!SpamProtection::checkRateLimit('login', RATE_LIMIT_LOGIN, RATE_LIMIT_WINDOW)) {
        $error = 'Invalid request. Please try again.';
    }
    else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please provide email and password';
            SpamProtection::recordAttempt('login');
        } else {
            $auth = new Auth();
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                // Success - destroy token and redirect
                CSRFToken::destroy();
                $role = $result['user']['role'];
                header("Location: /pages/$role/dashboard.php");
                exit();
            } else {
                $error = $result['message'];
                SpamProtection::recordAttempt('login');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>Login</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRFToken::getField(); ?>
        <?php echo SpamProtection::getHoneypotField(); ?>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <p style="margin-top: 20px;">
        Don't have an account? <a href="/pages/register.php">Register here</a>
    </p>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
