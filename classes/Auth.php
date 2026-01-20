<?php
/**
 * Authentication Class
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLog.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Register a new user
     */
    public function register($email, $password, $role, $firstName, $lastName) {
        // Validate password strength
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (email, password_hash, role, first_name, last_name) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$email, $passwordHash, $role, $firstName, $lastName]);
            
            $userId = $this->db->lastInsertId();
            
            // Log the registration
            AuditLog::log($userId, 'user_registered', 'users', $userId, "User registered with role: $role");
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $stmt = $this->db->prepare(
            "SELECT id, email, password_hash, role, first_name, last_name, status 
             FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Start session
        $this->startSession($user);
        
        // Log the login
        AuditLog::log($user['id'], 'user_login', 'users', $user['id'], 'User logged in');
        
        return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
    }
    
    /**
     * Start user session
     */
    private function startSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            
            // Set secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_start();
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            AuditLog::log($_SESSION['user_id'], 'user_logout', 'users', $_SESSION['user_id'], 'User logged out');
        }
        
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Check session timeout (30 minutes of inactivity)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
            self::logout();
            return false;
        }
        
        // Update last activity time
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::getCurrentUserRole() === $role;
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /pages/login.php');
            exit();
        }
    }
    
    /**
     * Require specific role(s)
     * @param string|array $role Single role or array of allowed roles
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        // Support both single role and array of roles
        if (is_array($role)) {
            $currentRole = self::getCurrentUserRole();
            if (!in_array($currentRole, $role)) {
                header('Location: /pages/unauthorized.php');
                exit();
            }
        } else {
            if (!self::hasRole($role)) {
                header('Location: /pages/unauthorized.php');
                exit();
            }
        }
    }
    
    /**
     * Require access to a specific page type based on RBAC configuration
     * @param string $pageType Page type from ROLE_PERMISSIONS (e.g., 'mentee_pages', 'mentor_pages', 'admin_pages', 'super_admin_pages')
     */
    public static function requirePageAccess($pageType) {
        self::requireLogin();
        
        // Get allowed roles from config
        if (!defined('ROLE_PERMISSIONS') || !isset(ROLE_PERMISSIONS[$pageType])) {
            // Fallback: if config not found, deny access
            header('Location: /pages/unauthorized.php');
            exit();
        }
        
        $allowedRoles = ROLE_PERMISSIONS[$pageType];
        $currentRole = self::getCurrentUserRole();
        
        if (!in_array($currentRole, $allowedRoles)) {
            header('Location: /pages/unauthorized.php');
            exit();
        }
    }
}
