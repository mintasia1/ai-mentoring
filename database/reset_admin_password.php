<?php
/**
 * Password Reset Script for Admin User
 * CUHK Law E-Mentoring Platform
 * 
 * This script resets the admin password to 'admin123'
 * Run this script from command line: php database/reset_admin_password.php
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

echo "CUHK Law E-Mentoring Platform - Admin Password Reset\n";
echo "=====================================================\n\n";

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line for security reasons.\n");
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to database: " . DB_NAME . "\n\n";
    
    // Find admin user
    $stmt = $conn->prepare("SELECT id, email, role FROM users WHERE email = 'admin@cuhk.edu.hk'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "✗ Error: Admin user (admin@cuhk.edu.hk) not found!\n";
        echo "\nCreating admin user...\n";
        
        // Create admin user
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO users (email, password_hash, role, first_name, last_name) 
             VALUES ('admin@cuhk.edu.hk', ?, 'super_admin', 'System', 'Admin')"
        );
        $stmt->execute([$passwordHash]);
        
        echo "✓ Admin user created successfully!\n";
    } else {
        echo "Found admin user:\n";
        echo "  ID: " . $admin['id'] . "\n";
        echo "  Email: " . $admin['email'] . "\n";
        echo "  Role: " . $admin['role'] . "\n\n";
        
        // Generate new password hash
        $newPassword = 'wTuGGy(E$!W~M,jJGO{';
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@cuhk.edu.hk'");
        $stmt->execute([$passwordHash]);
        
        echo "✓ Password reset successfully!\n";
    }
    
    echo "\n";
    echo "=======================================\n";
    echo "Password reset completed successfully!\n";
    echo "=======================================\n\n";
    
    echo "Login Credentials:\n";
    echo "-----------------\n";
    echo "Email: admin@cuhk.edu.hk\n";
    echo "Password: admin123\n\n";
    
    echo "⚠️  IMPORTANT: Change this password after login!\n\n";
    
    // Verify the password works
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = 'admin@cuhk.edu.hk'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && password_verify('admin123', $result['password_hash'])) {
        echo "✓ Password verification: SUCCESS\n";
    } else {
        echo "✗ Password verification: FAILED (please contact support)\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. Database credentials in config/config.php\n";
    echo "2. Database '" . DB_NAME . "' exists\n";
    echo "3. MySQL server is running\n\n";
    exit(1);
}
?>
