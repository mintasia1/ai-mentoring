# Role-Based Access Control (RBAC) Documentation

> **Note**: This RBAC system was merged from `copilot/simplify-mentors-navigation` branch into `copilot/add-ztest-page` branch.

## Overview

The CUHK Law E-Mentoring Platform implements a centralized Role-Based Access Control (RBAC) system to manage page access permissions. This system makes it easier to maintain and update role permissions across the application.

## Configuration

Role permissions are defined in `config/config.php` using the `ROLE_PERMISSIONS` constant:

```php
define('ROLE_PERMISSIONS', [
    'mentee_pages' => ['mentee', 'mentor', 'admin', 'super_admin'],
    'mentor_pages' => ['mentor', 'admin', 'super_admin'],
    'admin_pages' => ['admin', 'super_admin'],
    'super_admin_pages' => ['super_admin']
]);
```

## Role Hierarchy

The system implements the following hierarchy:

```
Super Admin → Admin → Mentor → Mentee
```

This means:
- **Super Admin** can access all pages (super_admin, admin, mentor, and mentee pages)
- **Admin** can access admin, mentor, and mentee pages
- **Mentor** can access mentor and mentee pages
- **Mentee** can access only mentee pages

## Usage

### In Page Files

Instead of using the old `Auth::requireRole()` method with hardcoded arrays, use the new `Auth::requirePageAccess()` method:

```php
// Old way (hardcoded, difficult to maintain)
Auth::requireRole(['mentee', 'mentor', 'admin', 'super_admin']);

// New way (centralized configuration)
Auth::requirePageAccess('mentee_pages');
```

### Page Type Categories

Four page type categories are defined:

1. **mentee_pages** - Pages accessible by mentees and above
   - Examples: mentee dashboard, mentee profile, browse mentors
   
2. **mentor_pages** - Pages accessible by mentors and above
   - Examples: mentor dashboard, complete profile
   
3. **admin_pages** - Pages accessible by admins and above
   - Examples: admin dashboard, manage mentors, manage mentees
   
4. **super_admin_pages** - Pages accessible only by super admins
   - Examples: super admin dashboard, manage admins, audit logs, manage all users

## Benefits

1. **Centralized Management**: All role permissions are defined in one place (`config/config.php`)
2. **Easy Updates**: Changing permissions for a page type updates all pages of that type
3. **Consistency**: Ensures consistent access control across the application
4. **Maintainability**: Reduces code duplication and makes the codebase easier to maintain
5. **Documentation**: Self-documenting through the configuration structure

## Implementation Details

### Auth Class Method

The `Auth::requirePageAccess()` method is defined in `classes/Auth.php`:

```php
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
```

### Files Updated

The following files were updated to use the new RBAC system:

**Mentee Pages:**
- `pages/mentee/dashboard.php`
- `pages/mentee/profile.php`
- `pages/mentee/browse_mentors.php`

**Mentor Pages:**
- `pages/mentor/dashboard.php`
- `pages/mentor/complete_profile.php`

**Admin Pages:**
- `pages/admin/dashboard.php`
- `pages/admin/manage_mentors.php`
- `pages/admin/manage_mentees.php`
- `pages/admin/mentors.php`

**Super Admin Pages:**
- `pages/super_admin/dashboard.php`
- `pages/super_admin/admins.php`
- `pages/super_admin/manage_users.php`
- `pages/super_admin/audit_logs.php`

## Adding New Page Types

To add a new page type category:

1. Add the page type to `ROLE_PERMISSIONS` in `config/config.php`:
   ```php
   define('ROLE_PERMISSIONS', [
       // ... existing entries
       'new_page_type' => ['allowed_role1', 'allowed_role2']
   ]);
   ```

2. Use it in your page files:
   ```php
   Auth::requirePageAccess('new_page_type');
   ```

## Migration from Old System

Pages were migrated from:
```php
Auth::requireRole(['role1', 'role2', 'role3']);
```

To:
```php
Auth::requirePageAccess('page_type');
```

This change:
- Removes hardcoded role arrays from individual pages
- Centralizes permission management
- Makes future updates easier

## Security Considerations

- The system denies access by default if the page type is not defined in `ROLE_PERMISSIONS`
- All access checks require the user to be logged in first
- Unauthorized access attempts redirect to `/pages/unauthorized.php`

## Best Practices

1. Always use `requirePageAccess()` for new pages instead of `requireRole()`
2. Group pages by their logical access level (mentee, mentor, admin, super_admin)
3. Update `ROLE_PERMISSIONS` in config.php when access requirements change
4. Document any custom page types in this file

---

**Last Updated**: January 19, 2026  
**Version**: 1.0
