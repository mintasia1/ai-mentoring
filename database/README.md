# Database Setup Scripts

This directory contains the database schema and helper scripts for the CUHK Law E-Mentoring Platform.

## Files

### schema.sql
Complete MySQL database schema with all tables and default admin account.

**Default Admin Credentials:**
- Email: admin@cuhk.edu.hk
- Password: admin123

### setup.php
Automated database setup script that:
- Creates the database if it doesn't exist
- Imports all tables from schema.sql
- Creates the default admin account
- Verifies the setup

**Usage:**
```bash
php database/setup.php
```

**Prerequisites:**
- Update database credentials in `config/config.php` first
- Ensure MySQL server is running
- PHP CLI must be available

### reset_admin_password.php
Password reset utility for the admin account.

Use this script if:
- You forgot the admin password
- You're getting "Invalid email or password" error
- You need to reset to the default password

**Usage:**
```bash
php database/reset_admin_password.php
```

This will:
- Reset admin password to 'admin123'
- Verify the password works
- Display login credentials

## Quick Start

1. **Configure database settings** in `config/config.php`

2. **Run automated setup:**
   ```bash
   php database/setup.php
   ```

3. **Access the application** and login with:
   - Email: admin@cuhk.edu.hk
   - Password: admin123

4. **Change the password** immediately after first login!

## Troubleshooting

### "Invalid email or password" error

Run the password reset script:
```bash
php database/reset_admin_password.php
```

### Database connection errors

1. Check credentials in `config/config.php`
2. Verify MySQL service is running:
   ```bash
   sudo service mysql status
   ```
3. Test database connection:
   ```bash
   mysql -u your_username -p
   ```

### Permission errors

Ensure the database user has sufficient privileges:
```sql
GRANT ALL PRIVILEGES ON cuhk_ementoring.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
```

## Manual Setup Alternative

If you prefer manual setup:

1. Create database:
   ```bash
   mysql -u root -p -e "CREATE DATABASE cuhk_ementoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. Import schema:
   ```bash
   mysql -u root -p cuhk_ementoring < database/schema.sql
   ```

3. Verify admin user:
   ```bash
   mysql -u root -p cuhk_ementoring -e "SELECT email, role FROM users WHERE role='super_admin';"
   ```

## Security Notes

⚠️ **Important Security Reminders:**

1. **Change default password immediately** after first login
2. **Never use default credentials** in production
3. **Use strong passwords** (minimum 12 characters, mixed case, numbers, symbols)
4. **Keep database credentials secure** - never commit them to version control
5. **Regular backups** - set up automated database backups

## Support

For more information, see:
- Main documentation: `SETUP.md` in project root
- Security guidelines: `SECURITY.md` in project root
