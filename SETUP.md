# CUHK Law E-Mentoring Platform - MVP

A comprehensive mentoring platform connecting CUHK Law students (mentees) with alumni (mentors).

## Project Overview

This platform digitizes the mentorship matching process between Alumni (Mentors) and Students (Mentees) at CUHK Law School.

### User Roles

- **Mentees (Students)**: Create profiles, browse mentors, request connections, track goals
- **Mentors (Alumni)**: Create profiles, set capacity, accept/decline requests
- **Admin**: Manage users, oversee matches, handle disputes
- **Super Admin**: System configuration, admin management, audit logs

### Core Features

1. **Smart Matching Algorithm**: Multi-factor matching based on:
   - Practice Area (Mandatory hard filter)
   - Shared Interests/Goals
   - Programme Level (e.g., JD to JD)
   - Language/Communication style
   - Location

2. **Re-Match Policy**: Automatic unlock of one (1) re-match opportunity if a match fails

3. **Communication Workspace**: Asynchronous notes and goals tracking (no real-time chat)

4. **Identity Verification**: Integration ready for CUHK Alumni data

## Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PDO PHP extension

### Step 1: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE cuhk_ementoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the schema:
```bash
mysql -u root -p cuhk_ementoring < database/schema.sql
```

3. Update database credentials in `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cuhk_ementoring');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Step 2: Web Server Configuration

#### Apache

Ensure `mod_rewrite` is enabled and `.htaccess` file is created:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx

Add to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Step 3: File Permissions

Ensure proper permissions:
```bash
chmod 755 -R /path/to/ai-mentoring
chmod 777 -R /path/to/ai-mentoring/assets
```

### Step 4: Access the Application

Navigate to your web server URL, e.g., `http://localhost/`

## Default Credentials

**Super Admin:**
- Email: admin@cuhk.edu.hk
- Password: admin123

**⚠️ IMPORTANT**: Change the default admin password immediately after first login.

## File Structure

```
ai-mentoring/
├── classes/              # PHP classes
│   ├── Auth.php         # Authentication & session management
│   ├── Database.php     # Database connection
│   ├── User.php         # User management
│   ├── Mentee.php       # Mentee functionality
│   ├── Mentor.php       # Mentor functionality
│   ├── Matching.php     # Smart matching algorithm
│   ├── Mentorship.php   # Mentorship management
│   ├── Workspace.php    # Communication workspace
│   └── AuditLog.php     # Audit logging
├── config/              # Configuration files
│   └── config.php       # Application configuration
├── database/            # Database files
│   └── schema.sql       # Database schema
├── includes/            # Common includes
│   ├── header.php       # Page header
│   └── footer.php       # Page footer
├── pages/               # Application pages
│   ├── mentee/         # Mentee pages
│   ├── mentor/         # Mentor pages
│   ├── admin/          # Admin pages
│   ├── super_admin/    # Super admin pages
│   ├── login.php       # Login page
│   ├── register.php    # Registration page
│   └── logout.php      # Logout handler
├── assets/              # Static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Images
├── index.php           # Main entry point
├── ztest.php           # Test file (displays current time)
└── README.md           # This file
```

## Usage

### For Mentees (Students)

1. Register as a Mentee
2. Complete your profile with interests, goals, and practice area preferences
3. Browse recommended mentors based on smart matching
4. Send connection requests to mentors
5. Once accepted, use the workspace to communicate

### For Mentors (Alumni)

1. Register as a Mentor
2. Complete your profile with expertise and capacity settings
3. Wait for admin verification
4. Review and respond to mentee requests
5. Use workspace to guide your mentees

### For Admins

1. Login with admin credentials
2. Manage users and verify mentor profiles
3. Oversee mentorship matches
4. Handle disputes and issues

### For Super Admins

1. Login with super admin credentials
2. Manage admin accounts
3. Configure system settings
4. Review audit logs

## Security Features

- Password hashing with bcrypt
- Session management
- SQL injection protection via prepared statements
- XSS protection with output escaping
- Role-based access control
- Audit logging for all major actions

## Matching Algorithm

The smart matching algorithm calculates compatibility scores based on:

- **Practice Area** (40 points) - Mandatory hard filter
- **Programme Level** (20 points) - Same programme level
- **Interests** (15 points) - Text similarity analysis
- **Location** (15 points) - Same location
- **Language** (10 points) - Same language preference

Total possible score: 100 points

## Re-Match Policy

- If a mentor declines a request, the mentee automatically receives 1 re-match opportunity
- Total re-match limit: 1 per mentee
- Re-match count is tracked in the mentee profile

## API Endpoints (Future Enhancement)

The platform is designed with separation of concerns to easily add RESTful API endpoints for:
- Mobile applications
- Third-party integrations
- Alumni data synchronization

## Troubleshooting

### Database Connection Issues
- Verify credentials in `config/config.php`
- Check MySQL service is running
- Ensure database exists and schema is imported

### Session Issues
- Check PHP session directory is writable
- Verify `session.save_path` in php.ini

### Permission Issues
- Ensure web server has read access to all files
- Check directory permissions for uploads/logs

## Development Notes

- PHP follows PSR-12 coding standards
- Database uses UTF-8 encoding
- Timezone set to Asia/Hong_Kong
- Session lifetime: 1 hour (configurable)

## Future Enhancements

- Real-time notifications
- Email notifications
- Advanced reporting and analytics
- Mobile responsive design improvements
- File upload for profiles
- Calendar integration
- Video conferencing integration

## Support

For support or questions, please contact the CUHK Law School IT department.

## License

Copyright © 2026 CUHK Law School. All rights reserved.
