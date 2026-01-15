<?php
/**
 * Test Page - CUHK Law E-Mentoring Platform
 * Displays current time and system information
 */

// Set timezone
date_default_timezone_set('Asia/Hong_Kong');

// Display current time
echo "CUHK Law E-Mentoring Platform - Test Page\n";
echo "==========================================\n\n";
echo "Current time: " . date("Y-m-d H:i:s") . "\n";
echo "Timezone: Asia/Hong_Kong\n";
echo "Date: " . date("l, F j, Y") . "\n";
echo "Time: " . date("g:i:s A") . "\n\n";

// System information
echo "System Information:\n";
echo "-------------------\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Platform: " . PHP_OS . "\n\n";

// Application info
echo "Application: CUHK Law E-Mentoring Platform MVP\n";
echo "Version: 1.0.0\n";
echo "Status: Development\n\n";

echo "For setup instructions, see SETUP.md\n";
echo "Access the application at /index.php\n";
?>

