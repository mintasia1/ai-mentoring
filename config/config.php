<?php
require_once('database.php');

// Application settings
define('APP_NAME', 'CUHK Law E-Mentoring Platform');
define('APP_URL', 'https://ai-mentoring.mint-client.com');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_NAME', 'cuhk_ementoring_session');

// Timezone
date_default_timezone_set('Asia/Hong_Kong');

// Mentorship settings
define('MAX_MENTEES_PER_MENTOR', 3);
define('REMATCH_LIMIT', 1);

// Practice areas
define('PRACTICE_AREAS', [
    'Corporate Law',
    'Criminal Law',
    'Family Law',
    'Intellectual Property',
    'International Law',
    'Litigation',
    'Tax Law',
    'Real Estate Law',
    'Labor and Employment',
    'Environmental Law',
    'Other'
]);

// Programme levels
define('PROGRAMME_LEVELS', [
    'JD' => 'Juris Doctor',
    'LLB' => 'Bachelor of Laws',
    'LLM' => 'Master of Laws',
    'PhD' => 'Doctor of Philosophy',
    'Other' => 'Other'
]);

// Role-based Access Control (RBAC)
// Define which roles can access which page types
// Hierarchy: Super Admin → Admin → Mentor → Mentee
define('ROLE_PERMISSIONS', [
    'mentee_pages' => ['mentee', 'mentor', 'admin', 'super_admin'],
    'mentor_pages' => ['mentor', 'admin', 'super_admin'],
    'admin_pages' => ['admin', 'super_admin'],
    'super_admin_pages' => ['super_admin']
]);

// Input validation patterns
define('OTHER_INPUT_PATTERN', '[A-Za-z0-9\s.,;:!?\(\)\-<>]*');
define('OTHER_INPUT_DESCRIPTION', 'Only alphabets, numbers, and punctuation allowed (no double quotes)');

// Logging settings
// LOGGING_ENABLED controls whether logging is active
// Set to false to completely disable all logging
define('LOGGING_ENABLED', true); // Default: true (logging enabled)

// LOG_LEVEL determines which messages are logged (only applies when LOGGING_ENABLED is true)
// Available levels (in order of severity):
//   'ERROR'   - Only errors (most restrictive)
//   'WARNING' - Errors + warnings
//   'INFO'    - Errors + warnings + info messages
//   'DEBUG'   - All messages including debug (least restrictive)
// 
// Examples:
//   define('LOG_LEVEL', 'ERROR');   // Only log errors
//   define('LOG_LEVEL', 'WARNING'); // Log errors and warnings
//   define('LOG_LEVEL', 'INFO');    // Log errors, warnings, and info
//   define('LOG_LEVEL', 'DEBUG');   // Log everything including debug messages
//
// To turn off logging completely:
//   define('LOGGING_ENABLED', false);
define('LOG_LEVEL', 'WARNING'); // Default: log errors and warnings
