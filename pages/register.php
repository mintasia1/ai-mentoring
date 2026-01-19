<?php
/**
 * Registration Page
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

$pageTitle = 'Register - ' . APP_NAME;
$error = '';
$success = '';

if (Auth::isLoggedIn()) {
    $role = Auth::getCurrentUserRole();
    header("Location: /pages/$role/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    
    if (empty($email) || empty($password) || empty($role) || empty($firstName) || empty($lastName)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!in_array($role, ['mentee', 'mentor'])) {
        $error = 'Invalid role selected';
    } else {
        $auth = new Auth();
        $result = $auth->register($email, $password, $role, $firstName, $lastName);
        
        if ($result['success']) {
            $success = 'Registration successful! Please login.';
        } else {
            $error = $result['message'];
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
 
