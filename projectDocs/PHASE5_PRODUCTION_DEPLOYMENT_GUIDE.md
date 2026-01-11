# Phase 5 Production Deployment Guide

**Last Updated**: January 11, 2026
**Version**: 1.0
**Target Environment**: Production Server (Ubuntu 20.04/22.04 LTS)

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Requirements](#server-requirements)
3. [Environment Configuration](#environment-configuration)
4. [Database Setup](#database-setup)
5. [Web Server Configuration](#web-server-configuration)
6. [SSL/TLS Configuration](#ssltls-configuration)
7. [Security Hardening](#security-hardening)
8. [Deployment Steps](#deployment-steps)
9. [Post-Deployment Verification](#post-deployment-verification)
10. [Rollback Procedure](#rollback-procedure)
11. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

### Code Readiness

- [ ] All Phase 5 Week 1-5 code merged to main branch
- [ ] Security audit completed and recommendations implemented
- [ ] All tests passing (135+ integration/unit tests)
- [ ] Database migrations tested in staging environment
- [ ] API documentation up to date (OpenAPI specs)
- [ ] Error handling tested (4xx, 5xx responses)
- [ ] Rate limiting configured and tested
- [ ] CORS origins configured for production domains

### Infrastructure Readiness

- [ ] Production server provisioned (Ubuntu 20.04/22.04 LTS)
- [ ] Domain name configured (DNS A records)
- [ ] SSL certificate obtained (Let's Encrypt or commercial)
- [ ] Database server configured (MySQL 8.0 / MariaDB 10.5+)
- [ ] Redis server configured (optional, for distributed rate limiting)
- [ ] Email service configured (SMTP credentials)
- [ ] Backup system configured (daily database + file backups)
- [ ] Monitoring tools installed (APM, uptime monitoring)

### Team Readiness

- [ ] Deployment runbook reviewed by team
- [ ] Rollback procedure tested
- [ ] On-call schedule established
- [ ] Incident response plan documented
- [ ] Stakeholders notified of deployment window

---

## Server Requirements

### Minimum Requirements

| Component | Requirement |
|-----------|-------------|
| **OS** | Ubuntu 20.04/22.04 LTS or Debian 11+ |
| **CPU** | 2 vCPUs (4 vCPUs recommended) |
| **RAM** | 4GB (8GB recommended) |
| **Storage** | 20GB SSD (50GB recommended) |
| **PHP** | 8.0+ (8.1 recommended) |
| **MySQL** | 8.0+ or MariaDB 10.5+ |
| **Web Server** | Apache 2.4+ or Nginx 1.18+ |
| **Redis** | 6.0+ (optional, recommended for multi-server) |

### Required PHP Extensions

```bash
# Install required PHP extensions
sudo apt update
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql \
    php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip \
    php8.1-gd php8.1-intl php8.1-bcmath php8.1-redis
```

### Required Software

```bash
# Install web server (Apache)
sudo apt install -y apache2 libapache2-mod-php8.1

# OR Nginx
sudo apt install -y nginx php8.1-fpm

# Install MySQL
sudo apt install -y mysql-server

# Install Redis (optional)
sudo apt install -y redis-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-apache
```

---

## Environment Configuration

### 1. Create Production .env File

```bash
# Navigate to application root
cd /var/www/html/Sci-Bono_Clubhoue_LMS

# Create .env from template
cp .env.example .env

# Edit production values
sudo nano .env
```

### 2. Production .env Configuration

```ini
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.scibono.co.za
APP_NAME="Sci-Bono LMS API"

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=scibono_lms_production
DB_USER=scibono_api_user
DB_PASSWORD=STRONG_RANDOM_PASSWORD_HERE
DB_CHARSET=utf8mb4

# JWT Configuration
JWT_SECRET=GENERATE_STRONG_SECRET_HERE_64_CHARS_MIN
JWT_ACCESS_TOKEN_EXPIRY=3600
JWT_REFRESH_TOKEN_EXPIRY=604800
JWT_ALGORITHM=HS256

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://app.scibono.co.za,https://www.scibono.co.za
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,X-CSRF-Token
CORS_ALLOW_CREDENTIALS=true

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
RATE_LIMIT_REDIS_HOST=localhost
RATE_LIMIT_REDIS_PORT=6379

# Email Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.YOUR_SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@scibono.co.za
MAIL_FROM_NAME="Sci-Bono LMS"

# File Upload
UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,docx,pptx,mp4
UPLOAD_PATH=/var/www/uploads/scibono-lms

# Logging
LOG_LEVEL=error
LOG_PATH=/var/log/scibono-lms
LOG_MAX_FILES=30

# Session
SESSION_LIFETIME=7200
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Strict

# Security
CSRF_TOKEN_EXPIRY=3600
PASSWORD_MIN_LENGTH=8
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900
```

### 3. Generate Strong Secrets

```bash
# Generate JWT secret (64 characters minimum)
openssl rand -hex 64

# Generate CSRF token secret
openssl rand -hex 32

# Generate database password
openssl rand -base64 32
```

### 4. Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/Sci-Bono_Clubhoue_LMS

# Set directory permissions
sudo find /var/www/html/Sci-Bono_Clubhoue_LMS -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/Sci-Bono_Clubhoue_LMS -type f -exec chmod 644 {} \;

# Secure .env file
sudo chmod 600 /var/www/html/Sci-Bono_Clubhoue_LMS/.env

# Create writable directories
sudo mkdir -p /var/www/uploads/scibono-lms
sudo mkdir -p /var/log/scibono-lms
sudo chown -R www-data:www-data /var/www/uploads/scibono-lms
sudo chown -R www-data:www-data /var/log/scibono-lms
sudo chmod 755 /var/www/uploads/scibono-lms
sudo chmod 755 /var/log/scibono-lms
```

---

## Database Setup

### 1. Create Production Database

```bash
# Login to MySQL
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE scibono_lms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create API user
CREATE USER 'scibono_api_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- Grant privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON scibono_lms_production.* TO 'scibono_api_user'@'localhost';

-- Grant schema modification (for migrations)
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON scibono_lms_production.* TO 'scibono_api_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify
SHOW GRANTS FOR 'scibono_api_user'@'localhost';

-- Exit
EXIT;
```

### 2. Run Database Migrations

```bash
# Navigate to project
cd /var/www/html/Sci-Bono_Clubhoue_LMS

# Run migrations
php database/run_migration.php up

# Verify migrations
mysql -u scibono_api_user -p scibono_lms_production -e "SHOW TABLES;"

# Expected tables:
# - users
# - courses
# - course_sections
# - course_lessons (or lessons)
# - enrollments
# - lesson_progress
# - holiday_programs
# - holiday_program_attendees
# - holiday_program_workshops
# - holiday_workshop_enrollment
# - token_blacklist
# - api_request_logs
```

### 3. Seed Production Data (Optional)

```bash
# If you have production seed data
php database/Seeder.php

# Or import from SQL dump
mysql -u scibono_api_user -p scibono_lms_production < database/seeds/production_data.sql
```

### 4. Database Optimization

```sql
-- Add indexes for performance
USE scibono_lms_production;

-- Courses table
CREATE INDEX idx_courses_published ON courses(is_published, status);
CREATE INDEX idx_courses_featured ON courses(is_featured);
CREATE INDEX idx_courses_category ON courses(category);

-- Enrollments table
CREATE INDEX idx_enrollments_user ON enrollments(user_id, status);
CREATE INDEX idx_enrollments_course ON enrollments(course_id);

-- Lessons table
CREATE INDEX idx_lessons_section ON course_lessons(section_id, order_number);

-- Lesson progress table
CREATE INDEX idx_progress_user ON lesson_progress(user_id, status);
CREATE INDEX idx_progress_lesson ON lesson_progress(lesson_id);

-- Token blacklist
CREATE INDEX idx_blacklist_jti ON token_blacklist(token_jti);
CREATE INDEX idx_blacklist_expires ON token_blacklist(expires_at);

-- API request logs
CREATE INDEX idx_api_logs_endpoint ON api_request_logs(endpoint, created_at);
CREATE INDEX idx_api_logs_user ON api_request_logs(user_id, created_at);
```

---

## Web Server Configuration

### Apache 2.4 Configuration

#### 1. Create Virtual Host

```bash
# Create vhost configuration
sudo nano /etc/apache2/sites-available/scibono-lms-api.conf
```

```apache
<VirtualHost *:80>
    ServerName api.scibono.co.za
    ServerAdmin admin@scibono.co.za
    DocumentRoot /var/www/html/Sci-Bono_Clubhoue_LMS/public

    <Directory /var/www/html/Sci-Bono_Clubhoue_LMS/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable .htaccess
        <IfModule mod_rewrite.c>
            RewriteEngine On
        </IfModule>
    </Directory>

    # Deny access to sensitive files
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    <DirectoryMatch "/\.git">
        Require all denied
    </DirectoryMatch>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/scibono-lms-api-error.log
    CustomLog ${APACHE_LOG_DIR}/scibono-lms-api-access.log combined
</VirtualHost>
```

#### 2. Enable Apache Modules

```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo a2enmod expires

# Enable site
sudo a2ensite scibono-lms-api.conf

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### Nginx Configuration (Alternative)

#### 1. Create Server Block

```bash
sudo nano /etc/nginx/sites-available/scibono-lms-api
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.scibono.co.za;
    root /var/www/html/Sci-Bono_Clubhoue_LMS/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/scibono-lms-api-access.log;
    error_log /var/log/nginx/scibono-lms-api-error.log;

    # Hide sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ^/(\.env|\.git|database|tests|projectDocs) {
        deny all;
    }

    # PHP handling
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to PHP files in uploads directory
    location ~* /uploads/.*.php$ {
        deny all;
    }

    # Client max body size
    client_max_body_size 10M;
}
```

#### 2. Enable Site

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/scibono-lms-api /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## SSL/TLS Configuration

### Option 1: Let's Encrypt (Free)

```bash
# Install Certbot (if not installed)
sudo apt install -y certbot python3-certbot-apache

# Obtain certificate (Apache)
sudo certbot --apache -d api.scibono.co.za

# OR for Nginx
sudo certbot --nginx -d api.scibono.co.za

# Auto-renewal is configured automatically
# Test renewal
sudo certbot renew --dry-run
```

### Option 2: Commercial SSL Certificate

```bash
# Generate CSR
openssl req -new -newkey rsa:2048 -nodes \
    -keyout scibono-api.key \
    -out scibono-api.csr

# Submit CSR to certificate authority
# After receiving certificate, install it

# Apache
sudo nano /etc/apache2/sites-available/scibono-lms-api-le-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName api.scibono.co.za
    DocumentRoot /var/www/html/Sci-Bono_Clubhoue_LMS/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/scibono-api.crt
    SSLCertificateKeyFile /etc/ssl/private/scibono-api.key
    SSLCertificateChainFile /etc/ssl/certs/scibono-api-chain.crt

    # Strong SSL configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on

    # ... rest of configuration
</VirtualHost>
```

### Redirect HTTP to HTTPS

```apache
# Apache
<VirtualHost *:80>
    ServerName api.scibono.co.za
    Redirect permanent / https://api.scibono.co.za/
</VirtualHost>
```

```nginx
# Nginx
server {
    listen 80;
    server_name api.scibono.co.za;
    return 301 https://$server_name$request_uri;
}
```

---

## Security Hardening

### 1. PHP Configuration

```bash
sudo nano /etc/php/8.1/apache2/php.ini
```

```ini
# Production settings
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

# Security
expose_php = Off
allow_url_fopen = On
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
max_execution_time = 30
max_input_time = 60
memory_limit = 256M

# File uploads
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20
post_max_size = 10M

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1
```

### 2. Firewall Configuration

```bash
# Enable UFW firewall
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Deny MySQL from external
sudo ufw deny 3306/tcp

# Check status
sudo ufw status verbose
```

### 3. Fail2Ban Configuration

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create Apache jail
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/*error.log
maxretry = 5
bantime = 3600

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/*error.log
maxretry = 6
bantime = 3600
```

```bash
# Restart Fail2Ban
sudo systemctl restart fail2ban
```

### 4. Database Security

```sql
-- Remove test database
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Disallow root login remotely
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Flush privileges
FLUSH PRIVILEGES;
```

### 5. File Permissions Audit

```bash
# Ensure no world-writable files
find /var/www/html/Sci-Bono_Clubhoue_LMS -type f -perm -002 -ls

# Ensure no world-writable directories (except uploads)
find /var/www/html/Sci-Bono_Clubhoue_LMS -type d -perm -002 ! -path "*/uploads/*" -ls

# Check .env permissions
ls -la /var/www/html/Sci-Bono_Clubhoue_LMS/.env
# Should show: -rw------- (600)
```

---

## Deployment Steps

### Step 1: Backup Current System

```bash
# Backup database
sudo mysqldump -u root -p scibono_lms_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
sudo tar -czf /backups/scibono-lms-backup-$(date +%Y%m%d).tar.gz /var/www/html/Sci-Bono_Clubhoue_LMS

# Backup web server config
sudo cp /etc/apache2/sites-available/scibono-lms-api.conf /backups/
```

### Step 2: Pull Latest Code

```bash
cd /var/www/html/Sci-Bono_Clubhoue_LMS

# If using Git
git fetch origin
git checkout main
git pull origin main

# If using rsync from build server
rsync -avz --exclude='.git' --exclude='node_modules' \
    build-server:/path/to/code/ /var/www/html/Sci-Bono_Clubhoue_LMS/
```

### Step 3: Install Dependencies

```bash
# Install Composer dependencies (production only)
composer install --no-dev --optimize-autoloader

# Clear any cached autoload files
composer dump-autoload --optimize
```

### Step 4: Run Database Migrations

```bash
# Backup database first!
php database/run_migration.php status
php database/run_migration.php up

# Verify migrations
php database/run_migration.php status
```

### Step 5: Clear Caches

```bash
# Clear PHP opcache
sudo systemctl reload php8.1-fpm  # For Nginx
sudo systemctl reload apache2     # For Apache

# Clear Redis cache (if using)
redis-cli FLUSHDB

# Clear application caches
rm -rf /var/www/html/Sci-Bono_Clubhoue_LMS/.dashboard-cache.json
```

### Step 6: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/html/Sci-Bono_Clubhoue_LMS
sudo chmod 600 /var/www/html/Sci-Bono_Clubhoue_LMS/.env
```

### Step 7: Restart Services

```bash
# Restart web server
sudo systemctl restart apache2  # or nginx

# Restart PHP-FPM (if using Nginx)
sudo systemctl restart php8.1-fpm

# Restart Redis (if using)
sudo systemctl restart redis-server
```

---

## Post-Deployment Verification

### 1. Health Check

```bash
# Check API health endpoint
curl https://api.scibono.co.za/api/v1/health

# Expected response:
# {"success":true,"data":{"status":"healthy","timestamp":"2026-01-11T12:00:00Z"},"message":"API is healthy"}
```

### 2. Authentication Test

```bash
# Test login
curl -X POST https://api.scibono.co.za/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@scibono.co.za","password":"testpassword"}'

# Should return JWT tokens
```

### 3. HTTPS Verification

```bash
# Check SSL certificate
openssl s_client -connect api.scibono.co.za:443 -servername api.scibono.co.za

# Check SSL rating
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=api.scibono.co.za
```

### 4. Security Headers Check

```bash
# Check security headers
curl -I https://api.scibono.co.za/api/v1/health

# Verify presence of:
# X-Content-Type-Options: nosniff
# X-Frame-Options: DENY
# X-XSS-Protection: 1; mode=block
# Strict-Transport-Security: max-age=31536000
```

### 5. Database Connection Test

```bash
# Test database connectivity
php -r "
\$conn = new mysqli('localhost', 'scibono_api_user', 'PASSWORD', 'scibono_lms_production');
if (\$conn->connect_error) {
    echo 'Connection failed: ' . \$conn->connect_error;
} else {
    echo 'Database connection successful';
}
"
```

### 6. Endpoint Smoke Tests

```bash
# Public endpoints
curl https://api.scibono.co.za/api/v1/courses/featured
curl https://api.scibono.co.za/api/v1/categories
curl https://api.scibono.co.za/api/v1/search?q=programming

# Authenticated endpoints (with token)
TOKEN="your_jwt_token_here"
curl -H "Authorization: Bearer $TOKEN" https://api.scibono.co.za/api/v1/user/courses
```

### 7. Error Logging Test

```bash
# Trigger a 404 error
curl https://api.scibono.co.za/api/v1/nonexistent

# Check error logs
sudo tail -f /var/log/scibono-lms/error.log
```

### 8. Performance Check

```bash
# Test response times
time curl https://api.scibono.co.za/api/v1/courses

# Should be < 200ms for most endpoints
```

---

## Rollback Procedure

### If Deployment Fails

#### Option 1: Rollback Code

```bash
# Restore from Git
git reset --hard PREVIOUS_COMMIT_HASH
sudo systemctl restart apache2

# Or restore from backup
sudo rm -rf /var/www/html/Sci-Bono_Clubhoue_LMS
sudo tar -xzf /backups/scibono-lms-backup-YYYYMMDD.tar.gz -C /
sudo systemctl restart apache2
```

#### Option 2: Rollback Database

```bash
# Rollback migrations
php database/run_migration.php rollback

# Or restore from backup
mysql -u root -p scibono_lms_production < backup_YYYYMMDD_HHMMSS.sql
```

#### Option 3: Full Rollback

```bash
# 1. Restore code
sudo tar -xzf /backups/scibono-lms-backup-YYYYMMDD.tar.gz -C /

# 2. Restore database
mysql -u root -p scibono_lms_production < backup_YYYYMMDD_HHMMSS.sql

# 3. Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# 4. Verify
curl https://api.scibono.co.za/api/v1/health
```

---

## Troubleshooting

### Issue: 500 Internal Server Error

```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/scibono-lms-api-error.log

# Check PHP error logs
sudo tail -f /var/log/php/error.log

# Common causes:
# - Incorrect file permissions
# - Database connection error
# - Missing PHP extensions
# - Syntax errors in code
```

### Issue: Database Connection Failed

```bash
# Test MySQL connection
mysql -u scibono_api_user -p scibono_lms_production

# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in .env
cat /var/www/html/Sci-Bono_Clubhoue_LMS/.env | grep DB_
```

### Issue: CORS Errors

```bash
# Check CORS configuration in .env
cat /var/www/html/Sci-Bono_Clubhoue_LMS/.env | grep CORS_

# Verify allowed origins include your frontend domain
# CORS_ALLOWED_ORIGINS=https://app.scibono.co.za
```

### Issue: SSL Certificate Error

```bash
# Check certificate validity
openssl x509 -in /etc/letsencrypt/live/api.scibono.co.za/fullchain.pem -text -noout

# Renew Let's Encrypt certificate
sudo certbot renew

# Check Apache SSL configuration
sudo apache2ctl -S
```

### Issue: High Response Times

```bash
# Enable MySQL slow query log
sudo nano /etc/mysql/my.cnf

# Add:
# slow_query_log = 1
# slow_query_log_file = /var/log/mysql/slow-query.log
# long_query_time = 1

# Restart MySQL
sudo systemctl restart mysql

# Check slow queries
sudo tail -f /var/log/mysql/slow-query.log
```

---

## Deployment Checklist Summary

### Pre-Deployment

- [ ] Code tested in staging
- [ ] Database migrations tested
- [ ] Security audit completed
- [ ] Backups created
- [ ] Team notified

### Configuration

- [ ] .env file configured
- [ ] Database created and user granted
- [ ] Web server vhost configured
- [ ] SSL certificate installed
- [ ] Firewall rules configured
- [ ] File permissions set

### Deployment

- [ ] Code deployed
- [ ] Dependencies installed
- [ ] Migrations run
- [ ] Caches cleared
- [ ] Services restarted

### Verification

- [ ] Health check passing
- [ ] Authentication working
- [ ] HTTPS enforced
- [ ] Security headers present
- [ ] Database connected
- [ ] Endpoints responding
- [ ] Error logging working
- [ ] Performance acceptable

### Post-Deployment

- [ ] Monitoring configured
- [ ] Alerts configured
- [ ] Documentation updated
- [ ] Team trained
- [ ] Stakeholders notified

---

## Support Contacts

**Technical Lead**: [Name] - [email@scibono.co.za]
**DevOps**: [Name] - [email@scibono.co.za]
**On-Call**: [Phone Number]

**Emergency Rollback Authority**: Technical Lead

---

**Document Version**: 1.0
**Last Updated**: January 11, 2026
**Next Review**: After first production deployment
