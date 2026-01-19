# GitHub Actions Deployment

This directory contains the GitHub Actions workflow for automated deployment to SiteGround.

## Workflow: deploy.yml

Automatically deploys the application to SiteGround when code is pushed to the `copilot/add-ztest-page` branch or manually triggered.

### Deployment Triggers

The deployment workflow triggers automatically in these scenarios:

1. **Push to copilot/add-ztest-page branch** - When Copilot merges changes into this branch and pushes them
2. **Manual dispatch** - Can be manually triggered from GitHub Actions tab

### How Copilot Triggers Deployment

When Copilot merges code into `copilot/add-ztest-page`:
1. Copilot uses `report_progress` or commits changes to the branch
2. Changes are automatically pushed to `origin/copilot/add-ztest-page`
3. GitHub Actions detects the push event
4. Deploy workflow runs automatically

You can also manually trigger the deployment:
1. Go to GitHub Actions tab
2. Select "Deploy to SiteGround" workflow
3. Click "Run workflow"
4. Select branch `copilot/add-ztest-page`
5. Optionally add a reason for deployment
6. Click "Run workflow" button

### Required GitHub Secrets

Configure these secrets in your repository settings (`Settings > Secrets and variables > Actions`):

1. **SG_SERVER** - Your SiteGround server hostname (e.g., `example.com` or `server.siteground.com`)
2. **SG_PORT** - SSH port (usually `18765` for SiteGround)
3. **SG_USER** - Your SiteGround SSH username
4. **SG_SSH_PRIVATE_KEY** - Your SSH private key content
5. **SG_SSH_PASSPHRASE** - SSH key passphrase (if your key has one)

### How It Works

1. **Trigger**: Automatically runs when code is pushed to `main` branch
2. **Setup**: Configures SSH authentication with your private key
3. **Deploy**: Uses `rsync` to sync files to your SiteGround server
4. **Cleanup**: Removes the SSH key from the runner for security

### Deployment Details

**Target Directory**: `~/public_html/` on your SiteGround server

**Deployment Branch**: `copilot/add-ztest-page` (configured for testing deployments)

**Excluded Files/Directories**:
- `.git` - Git repository data
- `.github` - GitHub Actions workflows
- `node_modules` - Node.js dependencies
- `.env` - Environment variables
- `*.log` - Log files

### Setting Up SSH Access

#### 1. Generate SSH Key Pair (if you don't have one)

```bash
ssh-keygen -t rsa -b 4096 -C "github-actions@yourproject.com"
```

#### 2. Add Public Key to SiteGround

1. Log in to SiteGround cPanel
2. Go to "SSH Keys Manager"
3. Import or paste your public key (`id_rsa.pub`)
4. Authorize the key

#### 3. Add Private Key to GitHub Secrets

1. Copy your private key content:
   ```bash
   cat ~/.ssh/id_rsa
   ```
2. Go to GitHub repository → Settings → Secrets and variables → Actions
3. Create new secret `SG_SSH_PRIVATE_KEY`
4. Paste the entire private key content (including headers)

### Testing the Deployment

1. Make a small change to any file
2. Commit and push to `copilot/add-ztest-page` branch:
   ```bash
   git add .
   git commit -m "Test deployment"
   git push origin copilot/add-ztest-page
   ```
3. Go to GitHub → Actions tab
4. Watch the deployment progress

**Note**: When Copilot makes changes, it automatically commits and pushes to trigger deployment.

### Troubleshooting

**Authentication Failed**
- Verify SSH key is correctly added to GitHub Secrets
- Ensure public key is authorized in SiteGround
- Check SSH port is correct (usually 18765 for SiteGround)

**Connection Timeout**
- Verify SG_SERVER hostname is correct
- Check SG_PORT matches your SiteGround SSH port
- Ensure SSH access is enabled in SiteGround

**Permission Denied**
- Verify SG_USER matches your SiteGround SSH username
- Check file permissions on the server
- Ensure the deployment path exists

**rsync Errors**
- Check target directory path (`~/public_html/`)
- Verify disk space on server
- Review excluded files/directories

### Manual Deployment (Alternative)

If automated deployment fails, you can deploy manually:

```bash
rsync -avz --delete \
  -e "ssh -p 18765" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  ./ username@server.siteground.com:~/public_html/
```

### Security Best Practices

1. **Never commit SSH keys** to the repository
2. **Use SSH keys** instead of passwords
3. **Rotate keys regularly** (every 90 days recommended)
4. **Limit key permissions** on the server
5. **Monitor deployment logs** for suspicious activity

### Customization

To modify the deployment behavior, edit `.github/workflows/deploy.yml`:

- **Change target directory**: Modify the rsync destination path
- **Add exclusions**: Add more `--exclude` flags
- **Pre/post deployment tasks**: Add additional steps
- **Deploy to multiple servers**: Duplicate the deploy step

### Support

For SiteGround-specific SSH setup:
- https://www.siteground.com/kb/how-to-use-ssh/

For GitHub Actions:
- https://docs.github.com/en/actions
