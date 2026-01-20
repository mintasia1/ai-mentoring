<?php
/**
 * Spam Protection Class
 * Rate limiting and honeypot protection
 * CUHK Law E-Mentoring Platform
 */

class SpamProtection {
    /**
     * Check if user is rate-limited for a specific action
     * @param string $action The action being performed (e.g., 'login', 'register')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if allowed, false if rate-limited
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool {
        $key = 'rate_limit_' . $action;
        $ip = self::getClientIP();
        $identifier = $key . '_' . hash('sha256', $ip);
        
        if (!isset($_SESSION[$identifier])) {
            $_SESSION[$identifier] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = &$_SESSION[$identifier];
        
        // Reset if time window passed
        if ((time() - $data['first_attempt']) > $timeWindow) {
            $data['attempts'] = 0;
            $data['first_attempt'] = time();
        }
        
        // Check if rate limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            $timeLeft = $timeWindow - (time() - $data['first_attempt']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Record an attempt for rate limiting
     * @param string $action The action being performed
     */
    public static function recordAttempt(string $action): void {
        $key = 'rate_limit_' . $action;
        $ip = self::getClientIP();
        $identifier = $key . '_' . hash('sha256', $ip);
        
        if (!isset($_SESSION[$identifier])) {
            $_SESSION[$identifier] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $_SESSION[$identifier]['attempts']++;
    }
    
    /**
     * Get remaining time for rate limit
     * @param string $action The action being checked
     * @param int $timeWindow Time window in seconds
     * @return int Seconds remaining until rate limit resets
     */
    public static function getRateLimitTimeLeft(string $action, int $timeWindow = 300): int {
        $key = 'rate_limit_' . $action;
        $ip = self::getClientIP();
        $identifier = $key . '_' . hash('sha256', $ip);
        
        if (!isset($_SESSION[$identifier])) {
            return 0;
        }
        
        $timeLeft = $timeWindow - (time() - $_SESSION[$identifier]['first_attempt']);
        return max(0, $timeLeft);
    }
    
    /**
     * Check honeypot field (should be empty)
     * @param string $fieldName Name of honeypot field
     * @return bool True if passed (field empty), false if failed
     */
    public static function checkHoneypot(string $fieldName = 'website'): bool {
        return empty($_POST[$fieldName] ?? '');
    }
    
    /**
     * Get honeypot field HTML
     * @param string $fieldName Name of honeypot field
     * @return string HTML for honeypot field
     */
    public static function getHoneypotField(string $fieldName = 'website'): string {
        return '<div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
            <label for="' . htmlspecialchars($fieldName) . '">Leave this field empty</label>
            <input type="text" id="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($fieldName) . '" value="" tabindex="-1" autocomplete="off">
        </div>';
    }
    
    /**
     * Get client IP address
     * @return string Client IP address
     */
    private static function getClientIP(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return $ip;
    }
}
