# Deployment Guide - SiteGround

This guide explains how to deploy the CUHK Law E-Mentoring Platform to SiteGround hosting.

## Quick Start

The repository includes an automated GitHub Actions workflow that deploys to SiteGround when you push to the `main` branch.

## Prerequisites

1. Active SiteGround hosting account
2. SSH access enabled in your SiteGround account
3. GitHub repository with Actions enabled

## Step-by-Step Setup

### 1. Enable SSH Access on SiteGround

1. Log in to SiteGround cPanel
2. Navigate to **Site Tools → DevOps → SSH Keys Manager**
3. If SSH is not enabled, click "Enable SSH"

### 2. Generate SSH Key Pair

On your local machine:

```bash
# Generate new SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-deploy@yourproject.com" -f ~/.ssh/siteground_deploy

# This creates two files:
# - ~/.ssh/siteground_deploy (private key)
# - ~/.ssh/siteground_deploy.pub (public key)
```

### 3. Add Public Key to SiteGround

1. Copy your public key:
   ```bash
   cat ~/.ssh/siteground_deploy.pub
   ```

2. In SiteGround cPanel → SSH Keys Manager:
   - Click "Import Key"
   - Paste your public key
   - Click "Import"
   - Click "Authorize" on the newly added key

### 4. Configure GitHub Secrets

Go to your GitHub repository → **Settings → Secrets and variables → Actions**

Add the following secrets:

| Secret Name | Value | Example |
|------------|-------|---------|
| `SG_SERVER` | Your SiteGround server hostname | `your-site.com` or `sgXXX.siteground.com` |
| `SG_PORT` | SSH port (SiteGround uses 18765) | `18765` |
| `SG_USER` | Your SiteGround SSH username | `uXXXXXXX` or your account username |
| `SG_SSH_PRIVATE_KEY` | Your private key content | Copy from `~/.ssh/siteground_deploy` |
| `SG_SSH_PASSPHRASE` | Passphrase (if you set one) | Leave empty if no passphrase |

**To get your private key:**
```bash
cat ~/.ssh/siteground_deploy
```
Copy the ENTIRE output including `-----BEGIN OPENSSH PRIVATE KEY-----` and `-----END OPENSSH PRIVATE KEY-----`

**To find your SiteGround username:**
- In cPanel, it's usually shown at the top right
- Or in Site Tools → DevOps → SSH Keys Manager
- Format is usually `uXXXXXXX` or your domain username

### 5. Test the Deployment

1. Make a small change to any file:
   ```bash
   echo "# Deployment test" >> README.md
   ```

2. Commit and push to main:
   ```bash
   git add .
   git commit -m "Test SiteGround deployment"
   git push origin main
   ```

3. Monitor deployment:
   - Go to GitHub → **Actions** tab
   - Click on the latest workflow run
   - Watch the deployment progress in real-time

### 6. Verify Deployment

After successful deployment:

1. SSH into your SiteGround server to verify:
   ```bash
   ssh -p 18765 your-username@your-server.com
   ls -la ~/public_html/
   ```

2. Access your application:
   ```
   https://your-domain.com
   ```

## Deployment Workflow

The automated workflow (`.github/workflows/deploy.yml`) does the following:

1. **Triggers** on every push to `main` branch
2. **Checks out** the latest code
3. **Sets up** SSH authentication
4. **Syncs files** to SiteGround using rsync
5. **Excludes** unnecessary files (.git, .github, node_modules, logs)
6. **Cleans up** SSH keys for security

## Configuration Files on Server

After first deployment, you may need to configure:

### Update Database Credentials

1. SSH into your SiteGround server
2. Edit `~/public_html/config/config.php`:
   ```bash
   nano ~/public_html/config/config.php
   ```

3. Update database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

### Setup Database

Run the setup script via SSH:
```bash
cd ~/public_html
php database/setup.php
```

Or import manually:
```bash
mysql -u your_user -p your_database < database/schema.sql
```

## File Permissions

SiteGround usually sets correct permissions automatically. If you encounter issues:

```bash
# Connect via SSH
ssh -p 18765 your-username@your-server.com

# Set correct permissions
cd ~/public_html
chmod 755 .
chmod 644 *.php
chmod 755 pages
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 644 config/config.php
```

## Troubleshooting

### Deployment Fails - Authentication Error

**Problem**: SSH authentication failed

**Solutions**:
1. Verify private key is correctly copied to GitHub Secrets (include headers)
2. Ensure public key is authorized in SiteGround
3. Check SSH username is correct
4. Verify SSH port is 18765

### Deployment Fails - Connection Timeout

**Problem**: Cannot connect to server

**Solutions**:
1. Verify SG_SERVER hostname is correct
2. Check if SSH is enabled in SiteGround
3. Try connecting manually:
   ```bash
   ssh -p 18765 username@server -v
   ```

### Files Not Updating on Server

**Problem**: Deployment succeeds but files don't change

**Solutions**:
1. Check deployment target path in workflow file
2. Verify you're pushing to `main` branch
3. Check SiteGround disk space
4. Review deployment logs in GitHub Actions

### Application Shows Errors After Deployment

**Problem**: Application errors after successful deployment

**Solutions**:
1. Check database credentials in `config/config.php`
2. Verify database exists and is accessible
3. Run database setup script
4. Check PHP error logs in cPanel

### Permission Denied Errors

**Problem**: Cannot write files or access directories

**Solutions**:
1. Check file ownership on server
2. Verify directory permissions (755 for directories, 644 for files)
3. Check SiteGround account permissions

## Manual Deployment (Fallback)

If automated deployment fails, deploy manually:

```bash
# Using rsync
rsync -avz --delete \
  -e "ssh -p 18765" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='.env' \
  ./ username@server.com:~/public_html/

# Using SCP
scp -P 18765 -r ./* username@server.com:~/public_html/

# Using FTP
# Use FileZilla or cPanel File Manager
```

## Security Recommendations

1. **Never commit secrets** to the repository
2. **Rotate SSH keys** every 90 days
3. **Use strong passwords** for database
4. **Enable HTTPS** on your domain
5. **Keep software updated** regularly
6. **Monitor deployment logs** for suspicious activity
7. **Backup database** before deployments

## SiteGround-Specific Tips

### Using MySQL

- Access via cPanel → MySQL Databases
- Create database and user
- Note the database host (usually `localhost`)
- Use phpMyAdmin for database management

### PHP Version

- Set in cPanel → PHP Manager
- Use PHP 7.4 or higher
- Enable required extensions (PDO, mysqli)

### SSL Certificate

- Free Let's Encrypt SSL in SiteGround
- Enable in cPanel → SSL Manager
- Force HTTPS in .htaccess

### Caching

- Enable SiteGround caching for better performance
- Clear cache after deployments
- Use SuperCacher in cPanel

## Post-Deployment Checklist

After successful deployment:

- [ ] Verify application loads correctly
- [ ] Test database connection
- [ ] Login with admin credentials
- [ ] Check all user roles work
- [ ] Verify SSL certificate is active
- [ ] Test core functionality
- [ ] Check error logs
- [ ] Monitor performance

## Getting Help

- **SiteGround Support**: https://www.siteground.com/kb/
- **GitHub Actions**: https://docs.github.com/en/actions
- **Application Issues**: Check `SETUP.md` and `SECURITY.md`

## Useful Links

- [SiteGround SSH Guide](https://www.siteground.com/kb/how-to-use-ssh/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [PHP on SiteGround](https://www.siteground.com/kb/php-version/)
