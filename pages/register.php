<?php
/**
 * Registration Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/CSRFToken.php';
require_once __DIR__ . '/../classes/SpamProtection.php';
require_once __DIR__ . '/../classes/Logger.php';

$pageTitle = 'Register - ' . APP_NAME;
$bodyClass = 'register';
$error = '';
$success = '';

if (Auth::isLoggedIn()) {
    $role = Auth::getCurrentUserRole();
    header("Location: /pages/$role/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Logger::debug("Registration attempt", ['email' => trim($_POST['email'] ?? ''), 'role' => $_POST['role'] ?? '']);
    
    // CSRF Protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFToken::validate($csrfToken)) {
        $error = 'Invalid request. Please try again.';
        Logger::warning("CSRF validation failed on registration", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    // Honeypot check
    elseif (!SpamProtection::checkHoneypot()) {
        $error = 'Invalid request. Please try again.';
        Logger::warning("Honeypot triggered on registration", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    // Rate limiting check
    elseif (!SpamProtection::checkRateLimit('register', RATE_LIMIT_REGISTER, RATE_LIMIT_WINDOW)) {
        $error = 'Invalid request. Please try again.';
        Logger::warning("Rate limit exceeded on registration", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        
        if (empty($email) || empty($password) || empty($role) || empty($firstName) || empty($lastName)) {
            $error = 'All fields are required';
            SpamProtection::recordAttempt('register');
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
            SpamProtection::recordAttempt('register');
        } elseif (!in_array($role, ['mentee', 'mentor'])) {
            $error = 'Invalid role selected';
            SpamProtection::recordAttempt('register');
        } else {
            $auth = new Auth();
            $result = $auth->register($email, $password, $role, $firstName, $lastName);
            
            if ($result['success']) {
                $success = 'Registration successful! Please login.';
                Logger::info("User registered successfully", ['email' => $email, 'role' => $role, 'user_id' => $result['user_id']]);
                CSRFToken::destroy(); // Destroy token on success
            } else {
                $error = $result['message'];
                Logger::warning("Registration failed", ['email' => $email, 'reason' => $result['message']]);
                SpamProtection::recordAttempt('register');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>Register</h2>
    
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
            <label for="role">I am a:</label>
            <select id="role" name="role" required>
                <option value="">Select role...</option>
                <option value="mentee">Student (Mentee)</option>
                <option value="mentor">Alumni (Mentor)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <p style="margin-top: 20px;">
        Already have an account? <a href="/pages/login.php">Login here</a>
    </p>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>
 
