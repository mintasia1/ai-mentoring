<?php
/**
 * Database Configuration
 * CUHK Law E-Mentoring Platform
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'cuhk_ementoring');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'CUHK Law E-Mentoring Platform');
define('APP_URL', 'http://localhost');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_NAME', 'cuhk_ementoring_session');

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

// Timezone
date_default_timezone_set('Asia/Hong_Kong');
?>
