<?php
/**
 * Database Setup Script
 * CUHK Law E-Mentoring Platform
 * 
 * This script helps set up the database and create the admin user.
 * Run this script from command line: php database/setup.php
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

echo "CUHK Law E-Mentoring Platform - Database Setup\n";
echo "===============================================\n\n";

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line for security reasons.\n");
}

// Connect to MySQL server (without selecting database first)
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '" . DB_NAME . "' created/verified\n";
    
    // Select the database
    $conn->exec("USE " . DB_NAME);
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die("✗ Error: schema.sql file not found!\n");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split SQL file by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
            } catch (PDOException $e) {
                // Ignore duplicate key errors for INSERT
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Database schema loaded successfully\n";
    
    // Verify admin user exists
    $stmt = $conn->prepare("SELECT email, role FROM users WHERE role = 'super_admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✓ Super admin account verified: " . $admin['email'] . "\n";
    } else {
        echo "✗ Warning: No super admin account found!\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Database setup completed successfully!\n";
    echo "========================================\n\n";
    
    echo "Default Login Credentials:\n";
    echo "-------------------------\n";
    echo "Email: admin@cuhk.edu.hk\n";
    echo "Password: admin123\n\n";
    
    echo "⚠️  IMPORTANT: Change the default password after first login!\n\n";
    
    echo "Next steps:\n";
    echo "1. Configure your web server to point to this directory\n";
    echo "2. Access the application at: " . APP_URL . "\n";
    echo "3. Login with the credentials above\n";
    echo "4. Change the admin password immediately\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    echo "Common issues:\n";
    echo "1. Check database credentials in config/config.php\n";
    echo "2. Ensure MySQL server is running\n";
    echo "3. Verify the database user has CREATE DATABASE privileges\n\n";
    exit(1);
}
