# Security Considerations for Production Deployment

This MVP contains several intentional development shortcuts that MUST be addressed before production deployment:

## Critical Security Issues to Fix

### 1. Database Credentials (config/config.php)
**Current State**: Empty password, hardcoded credentials
**Action Required**:
- Use environment variables for database credentials
- Never commit production credentials to version control
- Example:
```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

### 2. Default Admin Credentials (database/schema.sql)
**Current State**: Hardcoded admin@cuhk.edu.hk / admin123
**Action Required**:
- Remove hardcoded credentials from schema
- Create setup script that forces password change on first login
- Implement password complexity requirements
- Add multi-factor authentication for admin accounts

### 3. Session Timeout Enforcement
**Current State**: SESSION_LIFETIME constant defined but not enforced
**Action Required**:
- Implement session timeout check in Auth class
- Add automatic logout after inactivity
- Example implementation needed in Auth::isLoggedIn():
```php
if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        return false;
    }
}
```

### 4. Additional Security Enhancements Needed

#### HTTPS Enforcement
- Force HTTPS in production
- Set secure cookie flags
```php
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,  // HTTPS only
    'httponly' => true, // No JavaScript access
    'samesite' => 'Strict'
]);
```

#### CSRF Protection
- Implement CSRF tokens for all forms
- Validate tokens on form submission

#### Rate Limiting
- Add login attempt rate limiting
- Implement IP-based blocking for brute force attempts

#### Password Policy
- Enforce strong password requirements
- Implement password expiration
- Add password history to prevent reuse

#### Input Validation
- Add comprehensive input validation
- Implement whitelist-based validation
- Sanitize all user inputs

#### SQL Injection
- Currently using prepared statements (GOOD)
- Continue using PDO prepared statements for all queries

#### XSS Protection
- Continue using htmlspecialchars() for output
- Add Content Security Policy headers
- Implement output encoding

#### File Upload Security
- If implementing file uploads:
  - Validate file types
  - Scan for malware
  - Store outside web root
  - Generate random filenames

#### Logging & Monitoring
- Log all security events
- Monitor failed login attempts
- Set up alerts for suspicious activity
- Regular audit log review

#### Email Verification
- Implement email verification on registration
- Add password reset functionality with secure tokens
- Send notifications for security events

#### Database Security
- Use separate database user with minimal privileges
- Enable MySQL query logging
- Regular backup strategy
- Encrypt sensitive data at rest

## Development vs Production

### Development (Current MVP)
- ✅ Empty database password for local setup
- ✅ Hardcoded admin credentials for testing
- ✅ Basic session management
- ✅ Error messages show details

### Production Requirements
- ❌ Strong database credentials
- ❌ Setup wizard for admin creation
- ❌ Comprehensive session security
- ❌ Generic error messages
- ❌ HTTPS enforcement
- ❌ CSRF protection
- ❌ Rate limiting
- ❌ Email verification
- ❌ Audit log monitoring
- ❌ Regular security updates

## Compliance Considerations

### GDPR/Privacy
- Add privacy policy
- Implement data export functionality
- Add data deletion capabilities
- User consent management
- Data retention policies

### Audit Requirements
- Currently: Basic audit logging implemented
- Needed: Comprehensive logging of all data access
- Implement log retention and archival

## Testing Requirements

Before production deployment:
1. Security penetration testing
2. Code security audit
3. Dependency vulnerability scanning
4. Load testing
5. Backup and recovery testing
6. Disaster recovery plan

## Update Strategy

- Keep PHP and dependencies updated
- Monitor security advisories
- Regular security patches
- Automated vulnerability scanning

## Notes

This MVP is designed for development and demonstration purposes. The security shortcuts taken are intentional to simplify initial setup and testing. All items in this document should be addressed before deploying to a production environment with real user data.
