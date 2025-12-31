# Security Remediation Guide - GitGuardian Incident #24006667

## Critical Security Issue Detected

**Date:** December 31, 2025
**Incident ID:** #24006667
**Severity:** HIGH
**Status:** TRIGGERED
**Type:** Generic High Entropy Secret

## What Was Exposed

GitGuardian detected sensitive data in the following files that were committed to the public repository:

1. **Log Files** (`storage/logs/*.log`)
   - Session tokens
   - API keys (high entropy secrets)
   - User credentials in authentication logs
   - Internal system paths
   - Database connection details

2. **Environment File** (`.env`)
   - Database credentials
   - API keys
   - Secret keys
   - Third-party service credentials

3. **Exposed File:** `storage/logs/app-2025-12-29.log` (Commit: 145f616)

## Immediate Actions Required

### Step 1: Stop Further Exposure

✅ **COMPLETED** - Created comprehensive `.gitignore` file

The new `.gitignore` file now prevents:
- Environment files (`.env`, `.env.*`)
- Log files (`storage/logs/*.log`)
- Cache files (`storage/cache/*`)
- Backup files (`*.sql`, `*.backup`)
- Sensitive uploads
- IDE configuration files
- Temporary files

### Step 2: Remove Sensitive Files from Git Tracking

Run these commands **immediately**:

```bash
# Navigate to project directory
cd /var/www/html/Sci-Bono_Clubhoue_LMS

# 1. Remove .env from git tracking (but keep local file)
git rm --cached .env

# 2. Remove all log files from git tracking (but keep local files)
git rm --cached storage/logs/*.log

# 3. Remove cache files from git tracking
git rm --cached -r storage/cache/

# 4. Verify what will be removed
git status

# 5. Commit the changes
git add .gitignore
git commit -m "security: Add .gitignore and remove sensitive files from tracking

- Add comprehensive .gitignore file
- Remove .env file from git tracking
- Remove all log files from git tracking (14 files)
- Remove cache files from git tracking
- Prevent future exposure of secrets and API keys

Refs: GitGuardian Incident #24006667"
```

### Step 3: Remove Sensitive Data from Git History (CRITICAL!)

**WARNING:** This will rewrite git history. Coordinate with all team members.

The sensitive data is already in git history (commit 145f616 and others). Simply removing files from tracking is NOT enough - you must purge the history.

#### Option A: Using BFG Repo-Cleaner (Recommended)

```bash
# 1. Install BFG Repo-Cleaner
# Download from: https://rtyley.github.io/bfg-repo-cleaner/
# Or use package manager:
brew install bfg  # macOS
# or
sudo apt-get install bfg  # Ubuntu/Debian

# 2. Create a fresh clone for safety
cd /var/www/html
git clone --mirror Sci-Bono_Clubhoue_LMS Sci-Bono_Clubhoue_LMS-mirror
cd Sci-Bono_Clubhoue_LMS-mirror

# 3. Remove sensitive files from all commits
bfg --delete-files "*.log" --no-blob-protection
bfg --delete-files ".env" --no-blob-protection

# 4. Clean up the repository
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# 5. Force push to remote (COORDINATE WITH TEAM FIRST!)
git push --force
```

#### Option B: Using git filter-branch

```bash
# WARNING: This is slower but doesn't require additional tools

cd /var/www/html/Sci-Bono_Clubhoue_LMS

# 1. Remove all .log files from history
git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch storage/logs/*.log' \
  --prune-empty --tag-name-filter cat -- --all

# 2. Remove .env file from history
git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch .env' \
  --prune-empty --tag-name-filter cat -- --all

# 3. Clean up
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# 4. Force push (COORDINATE WITH TEAM FIRST!)
git push --force --all
git push --force --tags
```

#### Option C: Using git filter-repo (Modern Recommended)

```bash
# Install git filter-repo
pip install git-filter-repo

cd /var/www/html/Sci-Bono_Clubhoue_LMS

# 1. Create a paths-to-remove.txt file
cat > paths-to-remove.txt <<EOF
storage/logs/
.env
EOF

# 2. Run filter-repo
git filter-repo --invert-paths --paths-from-file paths-to-remove.txt

# 3. Add remote back (filter-repo removes it)
git remote add origin <your-repository-url>

# 4. Force push (COORDINATE WITH TEAM FIRST!)
git push --force --all
git push --force --tags
```

### Step 4: Rotate All Exposed Credentials (MANDATORY!)

All secrets in the exposed files must be considered compromised. You MUST rotate:

1. **Database Credentials**
   - Change MySQL/MariaDB passwords
   - Update connection strings
   - Update `.env` file (which is now ignored)

2. **API Keys**
   - Regenerate all API keys exposed in logs
   - Update third-party service credentials
   - Revoke old keys immediately

3. **Session Secrets**
   - Change `APP_KEY` or session secret
   - This will invalidate all existing sessions (users will be logged out)

4. **Other Secrets**
   - JWT signing keys
   - OAuth client secrets
   - Encryption keys
   - Any other credentials in logs

### Step 5: Update Application Configuration

After rotation, update your `.env` file (which is now safely ignored):

```bash
# Edit .env with new credentials
nano .env

# Verify .env is ignored
git status  # Should NOT show .env

# If .env appears in git status, you made a mistake!
```

### Step 6: Verify Security

```bash
# 1. Check that sensitive files are no longer tracked
git ls-files | grep -E "\.env$|\.log$"
# Should return NOTHING

# 2. Check .gitignore is working
echo "test" > storage/logs/test.log
git status  # Should NOT show test.log
rm storage/logs/test.log

# 3. Verify .env is ignored
touch .env.test
git status  # Should NOT show .env.test
rm .env.test
```

## Long-term Prevention Measures

### 1. Pre-commit Hooks

Install a pre-commit hook to prevent committing secrets:

```bash
# Install gitleaks for secret scanning
brew install gitleaks  # macOS
# or
wget https://github.com/gitleaks/gitleaks/releases/download/v8.18.1/gitleaks_8.18.1_linux_x64.tar.gz
tar -xzf gitleaks_8.18.1_linux_x64.tar.gz
sudo mv gitleaks /usr/local/bin/

# Create pre-commit hook
cat > .git/hooks/pre-commit <<'EOF'
#!/bin/bash
# Run gitleaks to detect secrets before commit

gitleaks protect --staged --verbose --redact

if [ $? -ne 0 ]; then
    echo "❌ COMMIT BLOCKED: Secrets detected!"
    echo "Please remove secrets before committing."
    exit 1
fi
EOF

chmod +x .git/hooks/pre-commit
```

### 2. Environment File Template

Keep `.env.example` updated as a template:

```bash
# Copy current .env structure to .env.example (WITHOUT real values)
cp .env .env.example

# Edit .env.example and replace all real values with placeholders
nano .env.example
# Replace real values with:
# DB_PASSWORD=your_password_here
# API_KEY=your_api_key_here
# etc.

# Commit .env.example (this is safe)
git add .env.example
git commit -m "docs: Update .env.example template"
```

### 3. Logging Best Practices

Update your logging configuration to prevent logging sensitive data:

**app/Config/logging.php** (or wherever logging is configured):

```php
<?php
/**
 * Logging Configuration
 * SECURITY: Never log sensitive data!
 */

class Logger {
    // Sensitive keys that should NEVER be logged
    private static $sensitiveKeys = [
        'password',
        'token',
        'secret',
        'api_key',
        'apikey',
        'session_token',
        'session_id',
        'csrf_token',
        'credit_card',
        'ssn',
        'private_key',
        'auth_token',
    ];

    /**
     * Sanitize data before logging
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $lowerKey = strtolower($key);
                foreach (self::$sensitiveKeys as $sensitiveKey) {
                    if (strpos($lowerKey, $sensitiveKey) !== false) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
                if (is_array($value)) {
                    $data[$key] = self::sanitize($value);
                }
            }
        }
        return $data;
    }

    /**
     * Log message with automatic sanitization
     */
    public static function log($level, $message, $context = []) {
        $sanitizedContext = self::sanitize($context);
        // Your existing logging logic here
        error_log("[$level] $message " . json_encode($sanitizedContext));
    }
}
```

### 4. Code Review Checklist

Before committing, always check:

- [ ] No `.env` files being committed
- [ ] No log files being committed
- [ ] No hardcoded credentials in code
- [ ] No API keys in code
- [ ] No database passwords in code
- [ ] No session tokens in code
- [ ] `.gitignore` is up to date
- [ ] Only `.env.example` is committed (with placeholder values)

### 5. GitHub Security Settings

If using GitHub:

1. **Enable Secret Scanning:**
   - Go to Settings → Security → Code security and analysis
   - Enable "Secret scanning"
   - Enable "Push protection"

2. **Enable Dependabot:**
   - Enable "Dependabot alerts"
   - Enable "Dependabot security updates"

3. **Add GitGuardian Integration:**
   - Already enabled (as shown by the alert)
   - Keep monitoring for future incidents

### 6. Team Education

Educate all team members on:
- Never commit `.env` files
- Never commit log files
- Use `.env.example` for documentation
- Always check `git status` before committing
- Use `git diff --cached` to review changes before committing
- Understand what `.gitignore` does

## Monitoring and Verification

### Daily Checks

```bash
# Check for accidentally committed secrets
git ls-files | grep -E "\.env$|\.log$|\.backup$|\.sql$"

# Should return NOTHING (or only .env.example)
```

### Weekly Audit

```bash
# Use gitleaks to scan entire repository
gitleaks detect --source . --verbose --redact

# Should report 0 leaks
```

## Incident Response Checklist

When secrets are exposed:

- [x] 1. Create `.gitignore` file ✅ DONE
- [ ] 2. Remove files from git tracking
- [ ] 3. Remove files from git history
- [ ] 4. Force push to remote (coordinate with team)
- [ ] 5. Rotate ALL exposed credentials
- [ ] 6. Update `.env` with new credentials
- [ ] 7. Notify team members
- [ ] 8. Monitor for unauthorized access
- [ ] 9. Review access logs for breaches
- [ ] 10. Install pre-commit hooks
- [ ] 11. Update security documentation
- [ ] 12. Mark GitGuardian incident as resolved

## Emergency Contacts

If you suspect credentials have been used maliciously:

1. **Immediately revoke all exposed credentials**
2. **Check database access logs for unauthorized queries**
3. **Check application logs for suspicious activity**
4. **Consider taking affected services offline temporarily**
5. **Contact your security team or IT administrator**

## Additional Resources

- [GitGuardian Documentation](https://docs.gitguardian.com/)
- [GitHub Secret Scanning](https://docs.github.com/en/code-security/secret-scanning)
- [BFG Repo-Cleaner](https://rtyley.github.io/bfg-repo-cleaner/)
- [Gitleaks](https://github.com/gitleaks/gitleaks)
- [OWASP Secrets Management](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)

## Questions?

If you need help with remediation:
1. Review this guide carefully
2. Test commands on a backup/clone first
3. Coordinate with team before force-pushing
4. Document all actions taken

---

**Last Updated:** December 31, 2025
**Incident:** GitGuardian #24006667
**Status:** Remediation in progress
