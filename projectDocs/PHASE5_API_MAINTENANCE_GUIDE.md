# Phase 5 - API Maintenance Guide

**Ongoing Maintenance & Operations Guide**
**Date**: January 2026
**Status**: Complete
**Author**: Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [Daily Maintenance Tasks](#daily-maintenance-tasks)
3. [Weekly Maintenance Tasks](#weekly-maintenance-tasks)
4. [Monthly Maintenance Tasks](#monthly-maintenance-tasks)
5. [Database Maintenance](#database-maintenance)
6. [Performance Optimization](#performance-optimization)
7. [Security Updates](#security-updates)
8. [Backup & Recovery](#backup--recovery)
9. [Troubleshooting Guide](#troubleshooting-guide)
10. [Common Issues & Solutions](#common-issues--solutions)
11. [API Version Management](#api-version-management)
12. [User Management](#user-management)
13. [Data Cleanup](#data-cleanup)
14. [Scaling Considerations](#scaling-considerations)

---

## Overview

This guide provides comprehensive maintenance procedures for the Sci-Bono Clubhouse LMS API (Phase 5). Regular maintenance ensures optimal performance, security, and reliability.

### Maintenance Goals

- **Zero Downtime** - Perform maintenance without service interruption
- **Optimal Performance** - Maintain <500ms average response time
- **Data Integrity** - Ensure data consistency and prevent corruption
- **Security** - Keep all systems patched and secure
- **Reliability** - Maintain 99.9% uptime SLA

### Maintenance Windows

```yaml
Production Maintenance Windows:
  Weekly: Sunday 02:00-04:00 SAST (low traffic period)
  Emergency: Anytime (with stakeholder notification)

Staging Environment:
  Anytime (no restrictions)

Development Environment:
  Anytime (no restrictions)
```

### Maintenance Responsibilities

```yaml
DevOps Team:
  - Server maintenance
  - Database optimization
  - Backup management
  - Security updates
  - Performance monitoring

Development Team:
  - Code optimization
  - Bug fixes
  - Feature updates
  - API documentation
  - Testing

Database Administrator:
  - Query optimization
  - Index management
  - Data archiving
  - Backup verification
```

---

## Daily Maintenance Tasks

### 1. Monitor System Health

**Time**: Every morning (09:00 SAST)
**Duration**: 15 minutes

```bash
#!/bin/bash
# daily-health-check.sh

echo "========== Daily Health Check =========="
echo "Date: $(date)"
echo ""

# 1. Check API health
echo "1. API Health Check..."
curl -s https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/health | jq '.'

# 2. Check disk space
echo ""
echo "2. Disk Space..."
df -h / | tail -1

# 3. Check memory usage
echo ""
echo "3. Memory Usage..."
free -h | grep Mem

# 4. Check CPU load
echo ""
echo "4. CPU Load..."
uptime

# 5. Check database connections
echo ""
echo "5. Database Connections..."
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null

# 6. Check for errors in last 24 hours
echo ""
echo "6. Recent Errors..."
ERROR_COUNT=$(grep -c "ERROR" /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-$(date +%Y-%m-%d).log 2>/dev/null || echo "0")
echo "Errors in last 24h: $ERROR_COUNT"

# 7. Check uptime
echo ""
echo "7. System Uptime..."
uptime

# 8. Check SSL certificate expiry
echo ""
echo "8. SSL Certificate..."
echo | openssl s_client -servername api.scibono.co.za -connect api.scibono.co.za:443 2>/dev/null | \
    openssl x509 -noout -dates | grep "notAfter"

echo ""
echo "========== Health Check Complete =========="
```

**Action Items**:
- Review health check output
- Investigate any errors or warnings
- Check Grafana dashboards for anomalies
- Review overnight alerts

### 2. Review Error Logs

**Time**: Morning (09:30 SAST)
**Duration**: 10 minutes

```bash
# Check yesterday's error log
tail -100 /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-$(date -d "yesterday" +%Y-%m-%d).log | grep ERROR

# Check security log
tail -50 /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/security-$(date +%Y-%m-%d).log

# Check Apache error log
sudo tail -50 /var/log/apache2/error.log
```

**Look For**:
- Repeated error patterns
- Failed authentication attempts
- Database connection errors
- File upload failures
- API rate limit violations

### 3. Monitor Performance Metrics

**Time**: Morning (10:00 SAST)
**Duration**: 10 minutes

**Check in Grafana**:
1. API request rate (last 24h)
2. Error rate (last 24h)
3. Average response time
4. Database query performance
5. Active users

**Thresholds to Watch**:
```yaml
Critical Thresholds:
  - Error rate > 1%
  - Average response time > 1s
  - Database queries > 100ms average
  - CPU usage > 80%
  - Memory usage > 90%
  - Disk usage > 85%
```

### 4. Verify Backups

**Time**: Morning (10:30 SAST)
**Duration**: 5 minutes

```bash
# Check last backup timestamp
ls -lh /backups/mysql/ | tail -5

# Verify backup size (should be consistent)
du -sh /backups/mysql/backup-$(date +%Y-%m-%d)*.sql.gz

# Check backup log
tail -20 /var/log/backup.log
```

---

## Weekly Maintenance Tasks

### 1. Database Optimization

**Time**: Sunday 02:00 SAST
**Duration**: 30-60 minutes

```bash
#!/bin/bash
# weekly-db-optimize.sh

echo "Starting database optimization..."

# Optimize all tables
mysql -u root -p$MYSQL_ROOT_PASSWORD accounts -e "
    SELECT CONCAT('OPTIMIZE TABLE ', table_name, ';')
    FROM information_schema.tables
    WHERE table_schema='accounts' AND table_type='BASE TABLE';
" | grep OPTIMIZE | mysql -u root -p$MYSQL_ROOT_PASSWORD accounts

# Analyze tables
mysql -u root -p$MYSQL_ROOT_PASSWORD accounts -e "
    SELECT CONCAT('ANALYZE TABLE ', table_name, ';')
    FROM information_schema.tables
    WHERE table_schema='accounts' AND table_type='BASE TABLE';
" | grep ANALYZE | mysql -u root -p$MYSQL_ROOT_PASSWORD accounts

# Check and repair tables
mysql -u root -p$MYSQL_ROOT_PASSWORD accounts -e "
    SELECT CONCAT('CHECK TABLE ', table_name, ';')
    FROM information_schema.tables
    WHERE table_schema='accounts' AND table_type='BASE TABLE';
" | grep CHECK | mysql -u root -p$MYSQL_ROOT_PASSWORD accounts

echo "Database optimization complete."
```

### 2. Log Rotation & Cleanup

**Time**: Sunday 02:30 SAST
**Duration**: 10 minutes

```bash
#!/bin/bash
# weekly-log-cleanup.sh

echo "Starting log cleanup..."

# Compress logs older than 7 days
find /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/ -name "*.log" -mtime +7 -exec gzip {} \;

# Delete compressed logs older than 30 days
find /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/ -name "*.log.gz" -mtime +30 -delete

# Delete Apache logs older than 30 days
find /var/log/apache2/ -name "*.log.*" -mtime +30 -delete

# Delete MySQL slow query logs older than 14 days
find /var/log/mysql/ -name "slow-query.log.*" -mtime +14 -delete

echo "Log cleanup complete."
```

### 3. Security Scan

**Time**: Sunday 03:00 SAST
**Duration**: 20 minutes

```bash
#!/bin/bash
# weekly-security-scan.sh

echo "Starting security scan..."

# Check for outdated packages
echo "1. Checking for security updates..."
sudo apt update
sudo apt list --upgradable

# Scan for rootkits (if rkhunter installed)
if command -v rkhunter &> /dev/null; then
    echo "2. Running rootkit scan..."
    sudo rkhunter --check --skip-keypress
fi

# Check for suspicious login attempts
echo "3. Checking failed login attempts..."
sudo grep "Failed password" /var/log/auth.log | tail -20

# Check for unusual file modifications
echo "4. Checking recent file modifications..."
find /var/www/html/Sci-Bono_Clubhoue_LMS -type f -mtime -7 -not -path "*/storage/*" -ls

# Check open ports
echo "5. Checking open ports..."
sudo netstat -tulpn | grep LISTEN

echo "Security scan complete."
```

### 4. Performance Review

**Time**: Monday 09:00 SAST
**Duration**: 30 minutes

**Review Last Week**:
1. Total API requests
2. Error rate trend
3. Slowest endpoints (top 10)
4. Most accessed endpoints (top 10)
5. Peak traffic times
6. User growth
7. Course enrollment trends

**Run Performance Report**:

```bash
# Generate weekly performance report
php /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/weekly-report.php
```

**File**: `scripts/weekly-report.php`

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$startDate = date('Y-m-d', strtotime('-7 days'));
$endDate = date('Y-m-d');

echo "===== Weekly Performance Report =====\n";
echo "Period: $startDate to $endDate\n\n";

// Total API requests
$sql = "SELECT COUNT(*) as total FROM api_request_logs WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total API Requests: " . number_format($result['total']) . "\n";

// Error rate
$sql = "SELECT
        SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as errors,
        COUNT(*) as total
        FROM api_request_logs
        WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$errorRate = ($result['errors'] / $result['total']) * 100;
echo "Error Rate: " . number_format($errorRate, 2) . "%\n";

// Slowest endpoints
$sql = "SELECT endpoint, AVG(response_time_ms) as avg_time
        FROM api_request_logs
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY endpoint
        ORDER BY avg_time DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
echo "\nSlowest Endpoints:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['endpoint'] . ": " . round($row['avg_time']) . "ms\n";
}

// Most accessed endpoints
$sql = "SELECT endpoint, COUNT(*) as requests
        FROM api_request_logs
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY endpoint
        ORDER BY requests DESC
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
echo "\nMost Accessed Endpoints:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['endpoint'] . ": " . number_format($row['requests']) . " requests\n";
}

// User registrations
$sql = "SELECT COUNT(*) as new_users FROM users WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nNew User Registrations: " . number_format($result['new_users']) . "\n";

// Course enrollments
$sql = "SELECT COUNT(*) as enrollments FROM enrollments WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Course Enrollments: " . number_format($result['enrollments']) . "\n";

echo "\n===== End of Report =====\n";
```

### 5. Update API Documentation

**Time**: Weekly as needed
**Duration**: Variable

```bash
# Regenerate OpenAPI documentation
php /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/generate-openapi.php

# Verify documentation is accessible
curl -s https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/openapi.json | jq '.info'
```

---

## Monthly Maintenance Tasks

### 1. Security Updates

**Time**: First Sunday of month, 02:00 SAST
**Duration**: 1-2 hours

```bash
#!/bin/bash
# monthly-security-updates.sh

echo "Starting monthly security updates..."

# Update package lists
sudo apt update

# Upgrade security packages only
sudo apt upgrade -y

# Update PHP packages
sudo apt install --only-upgrade php8.1*

# Update Composer dependencies
cd /var/www/html/Sci-Bono_Clubhoue_LMS
composer update --prefer-stable --no-dev

# Check for vulnerable dependencies
composer audit

# Restart services
sudo systemctl restart apache2
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql

echo "Security updates complete."
```

**Post-Update Checks**:
1. Test critical API endpoints
2. Review error logs for 24 hours
3. Monitor performance metrics
4. Verify SSL certificate still valid

### 2. Full Backup Verification

**Time**: First Sunday of month, 03:00 SAST
**Duration**: 1 hour

```bash
#!/bin/bash
# monthly-backup-verification.sh

echo "Starting backup verification..."

# Create test restore directory
TEST_DIR="/tmp/backup-test-$(date +%Y%m%d)"
mkdir -p $TEST_DIR

# Copy latest backup
LATEST_BACKUP=$(ls -t /backups/mysql/backup-*.sql.gz | head -1)
cp $LATEST_BACKUP $TEST_DIR/

# Extract backup
cd $TEST_DIR
gunzip *.sql.gz

# Create test database
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE IF NOT EXISTS backup_test;"

# Restore to test database
mysql -u root -p$MYSQL_ROOT_PASSWORD backup_test < *.sql

# Verify table count
ORIGINAL_TABLES=$(mysql -u root -p$MYSQL_ROOT_PASSWORD accounts -e "SHOW TABLES;" | wc -l)
RESTORED_TABLES=$(mysql -u root -p$MYSQL_ROOT_PASSWORD backup_test -e "SHOW TABLES;" | wc -l)

echo "Original tables: $ORIGINAL_TABLES"
echo "Restored tables: $RESTORED_TABLES"

if [ "$ORIGINAL_TABLES" -eq "$RESTORED_TABLES" ]; then
    echo "✅ Backup verification successful!"
else
    echo "❌ Backup verification failed! Table count mismatch."
    exit 1
fi

# Cleanup
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DROP DATABASE backup_test;"
rm -rf $TEST_DIR

echo "Backup verification complete."
```

### 3. Database Index Review

**Time**: First Sunday of month, 04:00 SAST
**Duration**: 30 minutes

```sql
-- Check unused indexes
SELECT
    s.table_name,
    s.index_name,
    s.cardinality
FROM information_schema.statistics s
LEFT JOIN information_schema.key_column_usage k
    ON s.table_name = k.table_name
    AND s.index_name = k.constraint_name
WHERE s.table_schema = 'accounts'
    AND k.constraint_name IS NULL
    AND s.index_name != 'PRIMARY'
ORDER BY s.table_name, s.index_name;

-- Check missing indexes (slow queries without indexes)
SELECT
    SUBSTRING_INDEX(SUBSTRING_INDEX(query, 'FROM ', -1), ' ', 1) as table_name,
    COUNT(*) as query_count,
    AVG(query_time) as avg_time
FROM mysql.slow_log
WHERE query NOT LIKE '%INDEX%'
GROUP BY table_name
ORDER BY avg_time DESC
LIMIT 20;

-- Check duplicate indexes
SELECT
    a.table_name,
    a.index_name as index1,
    b.index_name as index2,
    a.column_name
FROM information_schema.statistics a
JOIN information_schema.statistics b
    ON a.table_name = b.table_name
    AND a.column_name = b.column_name
    AND a.index_name < b.index_name
WHERE a.table_schema = 'accounts'
ORDER BY a.table_name;
```

### 4. Data Archiving

**Time**: Last Sunday of month, 02:00 SAST
**Duration**: 1 hour

```sql
-- Archive old API request logs (> 90 days)
CREATE TABLE IF NOT EXISTS api_request_logs_archive LIKE api_request_logs;

INSERT INTO api_request_logs_archive
SELECT * FROM api_request_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM api_request_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Archive old token blacklist entries (> 30 days)
DELETE FROM token_blacklist
WHERE blacklisted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Archive old password reset tokens (> 7 days)
DELETE FROM users
WHERE password_reset_token IS NOT NULL
    AND password_reset_expires < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Optimize affected tables
OPTIMIZE TABLE api_request_logs;
OPTIMIZE TABLE token_blacklist;
OPTIMIZE TABLE users;
```

### 5. Capacity Planning Review

**Time**: Last Monday of month
**Duration**: 1 hour

**Review Growth Metrics**:

```sql
-- User growth trend
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as new_users,
    SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as total_users
FROM users
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month;

-- Course enrollment trend
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as enrollments
FROM enrollments
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month;

-- API request trend
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as requests,
    AVG(response_time_ms) as avg_response_time
FROM api_request_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month;

-- Database size trend
SELECT
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'accounts'
GROUP BY table_schema;
```

**Capacity Planning Questions**:
1. Will current server handle 2x user growth?
2. Database size growing faster than expected?
3. Need to add more disk space?
4. API response times trending upward?
5. Need to scale horizontally (add servers)?

---

## Database Maintenance

### Regular Database Tasks

#### 1. Check Database Size

```sql
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'accounts'
ORDER BY (data_length + index_length) DESC;
```

#### 2. Identify Long-Running Queries

```sql
-- Check current queries
SELECT
    id,
    user,
    host,
    db,
    command,
    time,
    state,
    LEFT(info, 100) as query
FROM information_schema.processlist
WHERE command != 'Sleep'
    AND time > 5
ORDER BY time DESC;

-- Kill long-running query (if needed)
-- KILL <process_id>;
```

#### 3. Check Table Fragmentation

```sql
SELECT
    table_name,
    ROUND(data_free / 1024 / 1024, 2) AS fragmentation_mb,
    ROUND((data_free / (data_length + index_length + data_free)) * 100, 2) AS fragmentation_percent
FROM information_schema.tables
WHERE table_schema = 'accounts'
    AND data_free > 0
ORDER BY fragmentation_mb DESC;
```

#### 4. Optimize Fragmented Tables

```sql
-- Optimize tables with >20% fragmentation
OPTIMIZE TABLE courses;
OPTIMIZE TABLE enrollments;
OPTIMIZE TABLE users;
OPTIMIZE TABLE api_request_logs;
```

#### 5. Update Table Statistics

```sql
-- Update statistics for query optimizer
ANALYZE TABLE courses;
ANALYZE TABLE enrollments;
ANALYZE TABLE lessons;
ANALYZE TABLE users;
ANALYZE TABLE holiday_programs;
```

### Database Performance Tuning

#### MySQL Configuration Recommendations

**File**: `/etc/mysql/mysql.conf.d/mysqld.cnf`

```ini
[mysqld]
# Connection settings
max_connections = 200
max_connect_errors = 1000
connect_timeout = 10
wait_timeout = 600
interactive_timeout = 600

# Buffer pool (adjust based on RAM)
innodb_buffer_pool_size = 2G  # 70% of available RAM
innodb_buffer_pool_instances = 8
innodb_log_file_size = 512M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2

# Query cache (deprecated in MySQL 8.0)
# query_cache_type = 1
# query_cache_size = 128M

# Tmp tables
tmp_table_size = 64M
max_heap_table_size = 64M

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1
log_queries_not_using_indexes = 1

# Performance schema
performance_schema = ON
```

---

## Performance Optimization

### 1. Identify Slow Endpoints

```bash
# Analyze slow endpoints from logs
grep "response_time_ms" /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-*.log | \
    jq -r 'select(.response_time_ms > 1000) | "\(.endpoint): \(.response_time_ms)ms"' | \
    sort | uniq -c | sort -rn | head -20
```

### 2. Optimize Slow Queries

**Identify slow queries**:

```sql
-- Top 20 slowest queries
SELECT
    SUBSTRING(sql_text, 1, 100) as query,
    COUNT(*) as executions,
    AVG(timer_wait)/1000000000 as avg_time_ms,
    MAX(timer_wait)/1000000000 as max_time_ms
FROM performance_schema.events_statements_history_long
WHERE sql_text IS NOT NULL
GROUP BY SUBSTRING(sql_text, 1, 100)
ORDER BY avg_time_ms DESC
LIMIT 20;
```

**Common optimization techniques**:

```sql
-- Add missing indexes
ALTER TABLE enrollments ADD INDEX idx_user_course (user_id, course_id);
ALTER TABLE lessons ADD INDEX idx_course_order (course_id, order_number);
ALTER TABLE api_request_logs ADD INDEX idx_created_at (created_at);

-- Add composite indexes for common queries
ALTER TABLE courses ADD INDEX idx_published_featured (is_published, is_featured);
ALTER TABLE holiday_programs ADD INDEX idx_dates_open (start_date, end_date, registration_open);
```

### 3. Implement Caching

**File**: `app/Utils/CacheHelper.php` (already exists)

```php
<?php
namespace App\Utils;

class CacheHelper
{
    private static $redis = null;
    private static $enabled = true;

    public static function get($key)
    {
        if (!self::$enabled || !self::init()) {
            return null;
        }

        $value = self::$redis->get($key);
        return $value ? json_decode($value, true) : null;
    }

    public static function set($key, $value, $ttl = 3600)
    {
        if (!self::$enabled || !self::init()) {
            return false;
        }

        return self::$redis->setex($key, $ttl, json_encode($value));
    }

    public static function delete($key)
    {
        if (!self::$enabled || !self::init()) {
            return false;
        }

        return self::$redis->del($key);
    }

    private static function init()
    {
        if (self::$redis !== null) {
            return true;
        }

        try {
            self::$redis = new \Redis();
            self::$redis->connect('127.0.0.1', 6379);
            return true;
        } catch (\Exception $e) {
            self::$enabled = false;
            error_log("Redis connection failed: " . $e->getMessage());
            return false;
        }
    }
}
```

**Cache popular endpoints**:

```php
// Example: Cache featured courses
$cacheKey = 'courses:featured';
$courses = CacheHelper::get($cacheKey);

if ($courses === null) {
    // Fetch from database
    $courses = $this->courseModel->getFeaturedCourses();

    // Cache for 1 hour
    CacheHelper::set($cacheKey, $courses, 3600);
}

return $courses;
```

### 4. Enable OPcache

**File**: `/etc/php/8.1/fpm/conf.d/10-opcache.ini`

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=0
opcache.validate_timestamps=1
```

```bash
# Restart PHP-FPM to apply
sudo systemctl restart php8.1-fpm
```

### 5. Optimize Images and Assets

```bash
# Install image optimization tools
sudo apt install optipng jpegoptim

# Optimize existing images
find /var/www/html/Sci-Bono_Clubhoue_LMS/public/assets/uploads -name "*.jpg" -exec jpegoptim --max=85 {} \;
find /var/www/html/Sci-Bono_Clubhoue_LMS/public/assets/uploads -name "*.png" -exec optipng -o2 {} \;
```

---

## Security Updates

### 1. PHP Security Updates

```bash
# Check current PHP version
php -v

# Update to latest patch version
sudo apt update
sudo apt install --only-upgrade php8.1 php8.1-fpm php8.1-cli

# Verify update
php -v

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart apache2
```

### 2. Composer Dependency Updates

```bash
cd /var/www/html/Sci-Bono_Clubhoue_LMS

# Check for outdated packages
composer outdated

# Check for security vulnerabilities
composer audit

# Update dependencies (test in staging first!)
composer update --prefer-stable --no-dev

# Clear autoload cache
composer dump-autoload -o
```

### 3. MySQL Security Updates

```bash
# Update MySQL
sudo apt update
sudo apt install --only-upgrade mysql-server

# Run security script (if first time)
sudo mysql_secure_installation

# Restart MySQL
sudo systemctl restart mysql
```

### 4. SSL Certificate Renewal

```bash
# Check certificate expiry
sudo certbot certificates

# Renew certificates (auto-renews if <30 days)
sudo certbot renew

# Test renewal process (dry run)
sudo certbot renew --dry-run

# Restart Apache to load new certificate
sudo systemctl reload apache2
```

### 5. Security Audit

Run monthly security audit:

```bash
# Check for weak passwords (example)
mysql -u root -p accounts -e "
    SELECT id, email
    FROM users
    WHERE LENGTH(password) < 60  -- bcrypt hashes are 60 chars
    LIMIT 10;
"

# Check for inactive admin accounts
mysql -u root -p accounts -e "
    SELECT id, email, role, last_login
    FROM users
    WHERE role = 'admin'
        AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 90 DAY));
"

# Check file permissions
find /var/www/html/Sci-Bono_Clubhoue_LMS -type f -perm /o+w -ls
find /var/www/html/Sci-Bono_Clubhoue_LMS -type d -perm /o+w -ls
```

---

## Backup & Recovery

### Automated Daily Backups

**File**: `/usr/local/bin/backup-database.sh`

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/backups/mysql"
DB_NAME="accounts"
DB_USER="root"
DB_PASS="$MYSQL_ROOT_PASSWORD"
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Generate filename with timestamp
FILENAME="backup-$(date +%Y-%m-%d_%H%M%S).sql"

# Dump database
mysqldump -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    $DB_NAME > $BACKUP_DIR/$FILENAME

# Compress backup
gzip $BACKUP_DIR/$FILENAME

# Delete old backups
find $BACKUP_DIR -name "backup-*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Log backup
echo "$(date): Database backup completed - $FILENAME.gz" >> /var/log/backup.log
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-database.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-database.sh") | crontab -
```

### Database Restore Procedure

```bash
#!/bin/bash
# restore-database.sh

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: ./restore-database.sh <backup-file.sql.gz>"
    exit 1
fi

echo "⚠️  WARNING: This will overwrite the current database!"
read -p "Are you sure? (yes/no): " -r
if [[ ! $REPLY =~ ^yes$ ]]; then
    echo "Restore cancelled."
    exit 0
fi

echo "Extracting backup..."
gunzip -c $BACKUP_FILE > /tmp/restore.sql

echo "Restoring database..."
mysql -u root -p$MYSQL_ROOT_PASSWORD accounts < /tmp/restore.sql

echo "Cleaning up..."
rm /tmp/restore.sql

echo "✅ Database restored successfully!"
```

### Application Files Backup

```bash
#!/bin/bash
# backup-files.sh

BACKUP_DIR="/backups/files"
APP_DIR="/var/www/html/Sci-Bono_Clubhoue_LMS"

mkdir -p $BACKUP_DIR

# Backup uploaded files and configuration
tar -czf $BACKUP_DIR/files-$(date +%Y-%m-%d).tar.gz \
    $APP_DIR/public/assets/uploads \
    $APP_DIR/.env \
    $APP_DIR/config

# Delete old backups (> 30 days)
find $BACKUP_DIR -name "files-*.tar.gz" -mtime +30 -delete

echo "$(date): Files backup completed" >> /var/log/backup.log
```

---

## Troubleshooting Guide

### API Returns 500 Error

**Symptoms**: API endpoints returning HTTP 500

**Diagnosis**:
```bash
# Check Apache error log
sudo tail -50 /var/log/apache2/error.log

# Check application error log
tail -50 /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-$(date +%Y-%m-%d).log

# Check PHP-FPM error log
sudo tail -50 /var/log/php8.1-fpm.log
```

**Common Causes**:
1. Database connection failure
2. PHP syntax error
3. Missing file permissions
4. Out of memory
5. Missing dependency

**Solutions**:
```bash
# Test database connection
mysql -u root -p accounts -e "SELECT 1"

# Check PHP syntax
php -l /var/www/html/Sci-Bono_Clubhoue_LMS/bootstrap.php

# Fix file permissions
sudo chown -R www-data:www-data /var/www/html/Sci-Bono_Clubhoue_LMS/storage
sudo chmod -R 755 /var/www/html/Sci-Bono_Clubhoue_LMS/storage

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart apache2
```

### Slow API Response

**Symptoms**: API response time > 2 seconds

**Diagnosis**:
```bash
# Check slow query log
sudo tail -50 /var/log/mysql/slow-query.log

# Check performance log
tail -50 /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/performance-$(date +%Y-%m-%d).log | \
    jq 'select(.context.duration_ms > 1000)'

# Check server load
htop
```

**Common Causes**:
1. Unoptimized database queries
2. Missing indexes
3. Large result sets
4. No caching
5. High server load

**Solutions**:
```sql
-- Add missing indexes
SHOW INDEX FROM enrollments;
ALTER TABLE enrollments ADD INDEX idx_user_course (user_id, course_id);

-- Optimize queries
EXPLAIN SELECT * FROM courses WHERE is_published = 1;

-- Optimize tables
OPTIMIZE TABLE courses;
```

### Authentication Failures

**Symptoms**: Users cannot log in

**Diagnosis**:
```bash
# Check authentication logs
grep "login" /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/security-$(date +%Y-%m-%d).log

# Check database connectivity
mysql -u root -p accounts -e "SELECT COUNT(*) FROM users"

# Check session storage
ls -la /var/lib/php/sessions/
```

**Common Causes**:
1. Incorrect password hash
2. Session storage full
3. Database connection issue
4. CSRF token mismatch

**Solutions**:
```bash
# Clear session storage
sudo rm /var/lib/php/sessions/sess_*

# Test password hash
php -r "var_dump(password_verify('test123', '\$2y\$10\$...'));"

# Check session permissions
sudo chown -R www-data:www-data /var/lib/php/sessions
sudo chmod 700 /var/lib/php/sessions
```

### Database Connection Errors

**Symptoms**: "Could not connect to database"

**Diagnosis**:
```bash
# Check MySQL status
sudo systemctl status mysql

# Check MySQL connections
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_%';"

# Check MySQL error log
sudo tail -50 /var/log/mysql/error.log
```

**Common Causes**:
1. MySQL service down
2. Too many connections
3. Incorrect credentials
4. Firewall blocking

**Solutions**:
```bash
# Restart MySQL
sudo systemctl restart mysql

# Increase max connections
mysql -u root -p -e "SET GLOBAL max_connections = 200;"

# Kill idle connections
mysql -u root -p -e "SHOW PROCESSLIST;" | grep Sleep | awk '{print "KILL "$1";"}'
```

---

## Common Issues & Solutions

### Issue: High Memory Usage

**Solution**:
```bash
# Identify memory hogs
ps aux --sort=-%mem | head -10

# Restart Apache/PHP-FPM to clear memory
sudo systemctl restart php8.1-fpm
sudo systemctl restart apache2

# Reduce PHP memory limit if needed
# Edit /etc/php/8.1/fpm/php.ini
# memory_limit = 256M (reduce to 128M)
```

### Issue: Disk Space Full

**Solution**:
```bash
# Check disk usage
df -h

# Find large files
sudo du -sh /var/log/* | sort -rh | head -10
sudo du -sh /var/www/html/Sci-Bono_Clubhoue_LMS/storage/* | sort -rh

# Clean up logs
sudo find /var/log -name "*.log.*" -mtime +7 -delete
sudo journalctl --vacuum-time=7d

# Clean up old backups
find /backups -name "*.gz" -mtime +30 -delete
```

### Issue: SSL Certificate Expired

**Solution**:
```bash
# Check expiry
echo | openssl s_client -servername api.scibono.co.za -connect api.scibono.co.za:443 2>/dev/null | \
    openssl x509 -noout -dates

# Renew certificate
sudo certbot renew --force-renewal

# Reload Apache
sudo systemctl reload apache2
```

### Issue: Rate Limiting Not Working

**Solution**:
```php
// Check rate limit table
mysql -u root -p accounts -e "SELECT * FROM rate_limits ORDER BY updated_at DESC LIMIT 10;"

// Reset rate limit for user
mysql -u root -p accounts -e "DELETE FROM rate_limits WHERE identifier = 'user:123';"

// Adjust rate limit threshold in middleware
// Edit app/Middleware/RateLimitMiddleware.php
```

---

## API Version Management

### Current API Versions

```yaml
v1 (Current):
  Status: Stable
  Prefix: /api/v1
  Endpoints: 54
  Deprecated: None
  Sunset Date: TBD

v2 (Future):
  Status: Not yet implemented
  Planned: Q3 2026
```

### Adding New API Version

```bash
# Create new route file
cp routes/api.php routes/api_v2.php

# Update prefix in api_v2.php
# Change all: 'prefix' => 'api/v1' to 'prefix' => 'api/v2'

# Include in main routing
# Edit public/index.php to include routes/api_v2.php
```

### Deprecating API Endpoints

```php
// Add deprecation header to old endpoints
header('Deprecation: Sun, 01 Jan 2027 00:00:00 GMT');
header('Sunset: Sun, 01 Jul 2027 00:00:00 GMT');
header('Link: <https://api.scibono.co.za/api/v2/new-endpoint>; rel="successor-version"');
```

---

## User Management

### Create Admin User

```sql
-- Create admin user via SQL
INSERT INTO users (name, surname, email, password, role, status, created_at)
VALUES (
    'Admin',
    'User',
    'admin@scibono.co.za',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin',
    'active',
    NOW()
);
```

### Reset User Password

```sql
-- Reset password for user (password: newpassword123)
UPDATE users
SET password = '$2y$10$0H7EtV7gL4tY8H0N8M1ZNuqYGqO6H7N8M1ZNuqYGqO6H7N8M1ZNuq'
WHERE email = 'user@example.com';
```

### Deactivate User Account

```sql
-- Deactivate user
UPDATE users
SET status = 'inactive'
WHERE email = 'user@example.com';

-- Blacklist user's tokens
INSERT INTO token_blacklist (token, user_id, blacklisted_at)
SELECT token, user_id, NOW()
FROM api_tokens
WHERE user_id = (SELECT id FROM users WHERE email = 'user@example.com');
```

### Bulk User Import

```php
// scripts/import-users.php
<?php
require_once __DIR__ . '/../bootstrap.php';

$csvFile = $argv[1] ?? 'users.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile\n");
}

$csv = array_map('str_getcsv', file($csvFile));
$headers = array_shift($csv);

foreach ($csv as $row) {
    $user = array_combine($headers, $row);

    $sql = "INSERT INTO users (name, surname, email, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $user['name'],
        $user['surname'],
        $user['email'],
        password_hash('default123', PASSWORD_BCRYPT),
        $user['role'] ?? 'member'
    ]);

    echo "Imported: {$user['email']}\n";
}

echo "Import complete!\n";
```

---

## Data Cleanup

### Remove Orphaned Records

```sql
-- Find enrollments for deleted courses
SELECT e.* FROM enrollments e
LEFT JOIN courses c ON e.course_id = c.id
WHERE c.id IS NULL;

-- Delete orphaned enrollments
DELETE e FROM enrollments e
LEFT JOIN courses c ON e.course_id = c.id
WHERE c.id IS NULL;

-- Find lessons for deleted courses
DELETE l FROM lessons l
LEFT JOIN courses c ON l.course_id = c.id
WHERE c.id IS NULL;
```

### Clean Up Old Sessions

```bash
# Delete session files older than 24 hours
find /var/lib/php/sessions -type f -mtime +1 -delete
```

### Clean Up Expired Tokens

```sql
-- Delete expired password reset tokens
UPDATE users
SET password_reset_token = NULL,
    password_reset_expires = NULL
WHERE password_reset_expires < NOW();

-- Delete old blacklisted tokens (> 30 days)
DELETE FROM token_blacklist
WHERE blacklisted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## Scaling Considerations

### Horizontal Scaling (Multiple Servers)

**When to scale horizontally**:
- CPU usage consistently > 80%
- Request rate > 1000 req/sec
- Database connections maxed out
- Response time degrading

**Architecture**:
```
Internet
    |
Load Balancer (HAProxy/Nginx)
    |
    ├── App Server 1
    ├── App Server 2
    └── App Server 3
         |
    Database Server (Master)
         |
    Database Server (Replica)
```

**Load Balancer Configuration** (Nginx):

```nginx
upstream scibono_api {
    least_conn;
    server 10.0.0.10:80 weight=1;
    server 10.0.0.11:80 weight=1;
    server 10.0.0.12:80 weight=1;
}

server {
    listen 443 ssl http2;
    server_name api.scibono.co.za;

    ssl_certificate /etc/ssl/certs/scibono.crt;
    ssl_certificate_key /etc/ssl/private/scibono.key;

    location / {
        proxy_pass http://scibono_api;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Database Scaling

**Read Replicas**:
```php
// Master for writes
$masterDb = new PDO('mysql:host=10.0.0.20;dbname=accounts', 'user', 'pass');

// Replica for reads
$replicaDb = new PDO('mysql:host=10.0.0.21;dbname=accounts', 'user', 'pass');

// Route queries appropriately
if ($operation === 'read') {
    $stmt = $replicaDb->prepare($sql);
} else {
    $stmt = $masterDb->prepare($sql);
}
```

### Caching Strategy

**Multi-Level Caching**:
```
1. Browser Cache (static assets)
2. CDN (images, CSS, JS)
3. Redis (API responses, sessions)
4. OPcache (PHP bytecode)
5. MySQL Query Cache (deprecated in 8.0)
```

---

## Completion Checklist

### Daily Tasks
- [ ] Health check review
- [ ] Error log review
- [ ] Performance metrics check
- [ ] Backup verification

### Weekly Tasks
- [ ] Database optimization
- [ ] Log rotation and cleanup
- [ ] Security scan
- [ ] Performance report
- [ ] Documentation updates

### Monthly Tasks
- [ ] Security updates
- [ ] Full backup verification
- [ ] Index review
- [ ] Data archiving
- [ ] Capacity planning

### Quarterly Tasks
- [ ] Major version updates
- [ ] Security audit
- [ ] Performance benchmarking
- [ ] Disaster recovery drill
- [ ] Documentation review

---

## Summary

This maintenance guide provides:

✅ **Structured Maintenance** - Daily, weekly, and monthly tasks clearly defined
✅ **Comprehensive Coverage** - Database, security, performance, backups
✅ **Troubleshooting Guides** - Common issues with solutions
✅ **Automation Scripts** - Ready-to-use bash and SQL scripts
✅ **Performance Optimization** - Query optimization, caching, indexing
✅ **Scalability Planning** - Guidance for horizontal and vertical scaling
✅ **User Management** - Admin tasks, password resets, bulk operations
✅ **Security Best Practices** - Regular updates, audits, monitoring

**Key Maintenance Metrics**:
- Uptime: 99.9%
- Response Time: <500ms average
- Error Rate: <0.1%
- Backup Success: 100%
- Security Updates: Within 7 days of release

**Maintenance Team**: DevOps + Development + DBA
**Estimated Time**: 5-10 hours per week (depending on scale)

---

**Phase 5 API Maintenance Guide: Complete** ✅
