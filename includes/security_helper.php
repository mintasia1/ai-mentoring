<?php
/**
 * Security Helper Functions
 * Provides convenient functions for CSRF protection and spam prevention
 * CUHK Law E-Mentoring Platform
 */

/**
 * Validate CSRF token from POST request
 * @return bool True if valid, false otherwise
 */
function validateCSRF(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return CSRFToken::validate($token);
}

/**
 * Validate all security checks (CSRF + honeypot)
 * @param array &$errors Array to append error messages to
 * @return bool True if all checks passed
 */
function validateFormSecurity(array &$errors = []): bool {
    if (!validateCSRF()) {
        $errors[] = 'Invalid request. Please try again.';
        return false;
    }
    
    if (!SpamProtection::checkHoneypot()) {
        $errors[] = 'Invalid request. Please try again.';
        return false;
    }
    
    return true;
}

/**
 * Check rate limit for an action
 * @param string $action Action name
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @param array &$errors Array to append error messages to
 * @return bool True if allowed, false if rate-limited
 */
function checkRateLimit(string $action, int $maxAttempts, int $timeWindow, array &$errors = []): bool {
    if (!SpamProtection::checkRateLimit($action, $maxAttempts, $timeWindow)) {
        $errors[] = 'Invalid request. Please try again.';
        return false;
    }
    return true;
}
