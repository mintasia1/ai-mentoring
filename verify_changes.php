#!/usr/bin/env php
<?php
/**
 * Quick verification script for navigation simplification changes
 * Run with: php verify_changes.php
 */

echo "=== Navigation Simplification Verification ===\n\n";

$baseDir = __DIR__;
$errors = [];
$warnings = [];
$success = [];

// Check that all modified files exist and are valid PHP
$filesToCheck = [
    'pages/admin/dashboard.php',
    'pages/admin/manage_mentors.php',
    'pages/admin/manage_mentees.php',
    'pages/super_admin/dashboard.php',
    'pages/super_admin/admins.php',
    'pages/super_admin/manage_users.php',
    'classes/Mentee.php'
];

echo "1. Checking file existence and syntax...\n";
foreach ($filesToCheck as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (!file_exists($fullPath)) {
        $errors[] = "Missing file: $file";
        continue;
    }
    
    // Check PHP syntax
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $return);
    if ($return !== 0) {
        $errors[] = "Syntax error in $file: " . implode("\n", $output);
    } else {
        $success[] = "✓ $file";
    }
}

echo "\n2. Checking for required code patterns...\n";

// Check admin dashboard has correct auth
$adminDashboard = file_get_contents($baseDir . '/pages/admin/dashboard.php');
if (strpos($adminDashboard, "Auth::requireRole(['admin', 'super_admin'])") !== false) {
    $success[] = "✓ Admin dashboard has correct authorization";
} else {
    $errors[] = "Admin dashboard missing correct authorization";
}

// Check if Quick Actions were removed
if (strpos($adminDashboard, 'Quick Actions') !== false) {
    $warnings[] = "Quick Actions section still present in admin dashboard";
} else {
    $success[] = "✓ Quick Actions removed from admin dashboard";
}

// Check manage_mentors has statistics
$manageMentors = file_get_contents($baseDir . '/pages/admin/manage_mentors.php');
if (strpos($manageMentors, '<h3>Statistics</h3>') !== false) {
    $success[] = "✓ Statistics section exists in manage_mentors.php";
} else {
    $errors[] = "Statistics section missing in manage_mentors.php";
}

// Check for filter support
if (strpos($manageMentors, "\$filter = \$_GET['filter']") !== false) {
    $success[] = "✓ Filter support implemented in manage_mentors.php";
} else {
    $errors[] = "Filter support missing in manage_mentors.php";
}

// Check for toggleDetails function
if (strpos($manageMentors, 'function toggleDetails') !== false) {
    $success[] = "✓ View Details toggle function exists";
} else {
    $errors[] = "View Details toggle function missing";
}

// Check Mentee class has new methods
$menteeClass = file_get_contents($baseDir . '/classes/Mentee.php');
if (strpos($menteeClass, 'public function getAllMentees()') !== false) {
    $success[] = "✓ Mentee::getAllMentees() method exists";
} else {
    $errors[] = "Mentee::getAllMentees() method missing";
}

if (strpos($menteeClass, 'public function getStatistics()') !== false) {
    $success[] = "✓ Mentee::getStatistics() method exists";
} else {
    $errors[] = "Mentee::getStatistics() method missing";
}

// Check super admin dashboard has clickable stats
$superAdminDash = file_get_contents($baseDir . '/pages/super_admin/dashboard.php');
if (strpos($superAdminDash, 'manage_users.php') !== false) {
    $success[] = "✓ Super admin dashboard links to manage_users.php";
} else {
    $warnings[] = "Super admin dashboard may not link to manage_users.php";
}

// Check manage_users.php exists
if (file_exists($baseDir . '/pages/super_admin/manage_users.php')) {
    $success[] = "✓ New manage_users.php page created";
} else {
    $errors[] = "New manage_users.php page is missing";
}

// Check deprecated file still exists (for backward compatibility during transition)
if (file_exists($baseDir . '/pages/admin/mentors.php')) {
    $warnings[] = "Old mentors.php still exists (can be removed after verification)";
} else {
    $success[] = "✓ Old mentors.php has been removed";
}

echo "\n3. Results Summary:\n";
echo str_repeat("=", 50) . "\n";

if (!empty($success)) {
    echo "\n✓ PASSED (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ All checks passed! Changes are ready for testing.\n\n";
echo "Next steps:\n";
echo "1. Deploy to test environment\n";
echo "2. Test with Super Admin account\n";
echo "3. Test all navigation flows\n";
echo "4. Verify filters work correctly\n";
echo "5. Test batch actions\n";
echo "6. Review MIGRATION.md for complete testing checklist\n\n";

exit(0);
