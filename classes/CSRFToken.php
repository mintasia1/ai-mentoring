<?php
/**
 * CSRF Token Protection Class
 * Uses HMAC for secure token generation
 * CUHK Law E-Mentoring Platform
 */

class CSRFToken {
    private static $tokenLifetime = 3600; // 1 hour
    
    /**
     * Generate a new CSRF token
     * @return string The generated token
     */
    public static function generate(): string {
        if (!isset($_SESSION['csrf_token_timestamp']) || 
            (time() - $_SESSION['csrf_token_timestamp']) > self::$tokenLifetime) {
            
            // Generate random data
            $randomData = bin2hex(random_bytes(32));
            $timestamp = time();
            
            // Create HMAC-based token
            $token = hash_hmac('sha256', $randomData . $timestamp, CSRF_SECRET_KEY);
            
            // Store in session
            $_SESSION['csrf_token'] = $token;
            $_SESSION['csrf_token_timestamp'] = $timestamp;
            $_SESSION['csrf_token_data'] = $randomData;
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_timestamp']) ||
            !isset($_SESSION['csrf_token_data'])) {
            return false;
        }
        
        // Check if token expired
        if ((time() - $_SESSION['csrf_token_timestamp']) > self::$tokenLifetime) {
            self::destroy();
            return false;
        }
        
        // Validate token matches
        $expectedToken = hash_hmac(
            'sha256', 
            $_SESSION['csrf_token_data'] . $_SESSION['csrf_token_timestamp'], 
            CSRF_SECRET_KEY
        );
        
        // Use timing-safe comparison
        return hash_equals($expectedToken, $token);
    }
    
    /**
     * Get hidden input field HTML
     * @return string HTML for hidden input
     */
    public static function getField(): string {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Destroy current token
     */
    public static function destroy(): void {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_timestamp']);
        unset($_SESSION['csrf_token_data']);
    }
}
