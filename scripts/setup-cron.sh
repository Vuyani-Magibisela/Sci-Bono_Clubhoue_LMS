#!/bin/bash

# Sci-Bono LMS Cron Jobs Setup Script
# Sets up automated tasks for production environment

set -e

# Configuration
APP_ROOT="/var/www/html/Sci-Bono_Clubhoue_LMS"
CRON_USER="www-data"
CRON_FILE="/etc/cron.d/sci-bono-lms"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Check if running as root (required for cron setup)
check_permissions() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root to set up system cron jobs"
        exit 1
    fi
}

# Create cron file
create_cron_file() {
    log "Creating cron configuration file..."
    
    cat > "$CRON_FILE" << 'EOF'
# Sci-Bono LMS Automated Tasks
# Managed by setup-cron.sh - DO NOT EDIT MANUALLY

# Set environment variables
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=admin@sci-bono-lms.com

# Daily backup at 2:00 AM
0 2 * * * root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/backup.sh full >> /var/log/cron-backup.log 2>&1

# Hourly database backup (during business hours)
0 9-17 * * 1-5 root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/backup.sh database >> /var/log/cron-backup-hourly.log 2>&1

# Health monitoring every 5 minutes
*/5 * * * * root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/monitor.sh check >> /var/log/cron-monitor.log 2>&1

# Weekly cleanup of old backups (Sunday at 3:00 AM)
0 3 * * 0 root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/backup.sh cleanup >> /var/log/cron-cleanup.log 2>&1

# Clear application cache daily at 1:00 AM
0 1 * * * www-data docker exec sci-bono-lms-cache redis-cli FLUSHDB >> /var/log/cron-cache.log 2>&1

# Clear PHP OPcache daily at 1:05 AM
5 1 * * * www-data docker exec sci-bono-lms-app php -r "opcache_reset();" >> /var/log/cron-opcache.log 2>&1

# Clean up temporary files daily at 1:10 AM
10 1 * * * root find /tmp -name "*sci-bono*" -type f -mtime +1 -delete >> /var/log/cron-cleanup.log 2>&1

# Rotate logs weekly (Monday at 4:00 AM)
0 4 * * 1 root /usr/sbin/logrotate /etc/logrotate.d/sci-bono-lms >> /var/log/cron-logrotate.log 2>&1

# Update SSL certificates check (daily at 6:00 AM)
0 6 * * * root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/monitor.sh check | grep -i ssl >> /var/log/cron-ssl.log 2>&1

# Database optimization (weekly on Sunday at 5:00 AM)
0 5 * * 0 root docker exec sci-bono-lms-db mysqlcheck -u root -p${DB_ROOT_PASSWORD} --auto-repair --optimize --all-databases >> /var/log/cron-db-optimize.log 2>&1

# Generate weekly status report (Monday at 8:00 AM)
0 8 * * 1 root /var/www/html/Sci-Bono_Clubhoue_LMS/scripts/monitor.sh report > /var/log/weekly-status-report-$(date +\%Y\%m\%d).log 2>&1

# Clean up old log files (monthly on 1st day at 2:30 AM)
30 2 1 * * root find /var/log -name "*sci-bono*" -type f -mtime +90 -delete >> /var/log/cron-log-cleanup.log 2>&1

# Docker container health check (every 10 minutes)
*/10 * * * * root docker ps --filter "name=sci-bono-lms" --format "table {{.Names}}\t{{.Status}}" | grep -v "Up" && echo "Container issue detected at $(date)" >> /var/log/cron-docker-health.log || true

# Update performance metrics (every 15 minutes)
*/15 * * * * www-data curl -s http://localhost/app/Controllers/PerformanceDashboardController.php?action=update_metrics >> /var/log/cron-performance.log 2>&1

# Session cleanup (every 2 hours)
0 */2 * * * www-data find /var/www/html/Sci-Bono_Clubhoue_LMS/storage/sessions -name "sess_*" -mtime +1 -delete >> /var/log/cron-sessions.log 2>&1

EOF

    # Set proper permissions
    chmod 644 "$CRON_FILE"
    chown root:root "$CRON_FILE"
    
    log "Cron configuration file created: $CRON_FILE"
}

# Create log rotation configuration
create_logrotate_config() {
    log "Creating log rotation configuration..."
    
    cat > "/etc/logrotate.d/sci-bono-lms" << EOF
/var/www/html/Sci-Bono_Clubhoue_LMS/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        # Reload services if needed
        systemctl reload apache2 > /dev/null 2>&1 || true
        docker exec sci-bono-lms-app apache2ctl graceful > /dev/null 2>&1 || true
    endscript
}

/var/log/cron-*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 root root
}

/var/log/sci-bono-lms-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 root root
}
EOF

    log "Log rotation configuration created"
}

# Create monitoring thresholds configuration
create_monitoring_config() {
    log "Creating monitoring configuration..."
    
    mkdir -p "$APP_ROOT/config"
    
    cat > "$APP_ROOT/config/monitoring-thresholds.conf" << EOF
# Sci-Bono LMS Monitoring Thresholds Configuration
# Adjust these values based on your system requirements

# System Resource Thresholds
CPU_THRESHOLD=80          # CPU usage percentage
MEMORY_THRESHOLD=85       # Memory usage percentage
DISK_THRESHOLD=90         # Disk usage percentage

# Application Performance Thresholds
RESPONSE_TIME_THRESHOLD=5000    # Response time in milliseconds
ERROR_RATE_THRESHOLD=5          # Error rate percentage per hour

# Database Thresholds
DB_CONNECTION_TIMEOUT=10        # Database connection timeout in seconds
DB_QUERY_TIMEOUT=30            # Database query timeout in seconds

# Redis Thresholds
REDIS_MEMORY_THRESHOLD=100      # Redis memory usage in MB
REDIS_CONNECTION_TIMEOUT=5      # Redis connection timeout in seconds

# SSL Certificate Thresholds
SSL_EXPIRY_WARNING_DAYS=30     # Days before certificate expiry to warn

# Backup Thresholds
BACKUP_MAX_AGE_HOURS=25        # Maximum age of backup in hours

# Alert Settings
ALERT_EMAIL="admin@sci-bono-lms.com"
ALERT_COOLDOWN=3600            # Seconds between duplicate alerts
EOF

    chown www-data:www-data "$APP_ROOT/config/monitoring-thresholds.conf"
    chmod 644 "$APP_ROOT/config/monitoring-thresholds.conf"
    
    log "Monitoring configuration created: $APP_ROOT/config/monitoring-thresholds.conf"
}

# Create maintenance scripts directory
create_maintenance_scripts() {
    log "Creating maintenance scripts directory..."
    
    mkdir -p "$APP_ROOT/scripts/maintenance"
    
    # Create database maintenance script
    cat > "$APP_ROOT/scripts/maintenance/database-maintenance.sh" << 'EOF'
#!/bin/bash
# Database maintenance script
set -e

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting database maintenance..."

# Check tables
docker exec sci-bono-lms-db mysqlcheck -u root -p"$DB_ROOT_PASSWORD" --check --all-databases

# Repair if needed
docker exec sci-bono-lms-db mysqlcheck -u root -p"$DB_ROOT_PASSWORD" --auto-repair --all-databases

# Optimize tables
docker exec sci-bono-lms-db mysqlcheck -u root -p"$DB_ROOT_PASSWORD" --optimize --all-databases

log "Database maintenance completed"
EOF

    # Create cache maintenance script
    cat > "$APP_ROOT/scripts/maintenance/cache-maintenance.sh" << 'EOF'
#!/bin/bash
# Cache maintenance script
set -e

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting cache maintenance..."

# Clear Redis cache
if docker ps | grep -q "sci-bono-lms-cache"; then
    docker exec sci-bono-lms-cache redis-cli INFO memory
    docker exec sci-bono-lms-cache redis-cli FLUSHDB
    log "Redis cache cleared"
fi

# Clear OPcache
if docker ps | grep -q "sci-bono-lms-app"; then
    docker exec sci-bono-lms-app php -r "opcache_reset(); echo 'OPcache cleared\n';"
fi

# Clear application cache files
find "$APP_ROOT/storage/cache" -name "*.cache" -mtime +1 -delete 2>/dev/null || true

log "Cache maintenance completed"
EOF

    # Make scripts executable
    chmod +x "$APP_ROOT/scripts/maintenance/"*.sh
    chown -R www-data:www-data "$APP_ROOT/scripts/maintenance"
    
    log "Maintenance scripts created in $APP_ROOT/scripts/maintenance/"
}

# Test cron configuration
test_cron_setup() {
    log "Testing cron configuration..."
    
    # Check if cron service is running
    if ! systemctl is-active --quiet cron; then
        warning "Cron service is not running. Starting..."
        systemctl start cron
        systemctl enable cron
    fi
    
    # Test cron syntax
    if crontab -T "$CRON_FILE" 2>/dev/null; then
        log "✓ Cron file syntax is valid"
    else
        error "✗ Cron file syntax error"
        return 1
    fi
    
    # Check if scripts are executable
    local scripts=(
        "$APP_ROOT/scripts/backup.sh"
        "$APP_ROOT/scripts/monitor.sh"
    )
    
    for script in "${scripts[@]}"; do
        if [ -x "$script" ]; then
            log "✓ Script is executable: $script"
        else
            error "✗ Script is not executable: $script"
            chmod +x "$script"
            log "Made script executable: $script"
        fi
    done
    
    # Test log directories
    local log_dirs=(
        "/var/log"
        "$APP_ROOT/logs"
        "$APP_ROOT/storage/logs"
    )
    
    for dir in "${log_dirs[@]}"; do
        if [ -w "$dir" ]; then
            log "✓ Log directory is writable: $dir"
        else
            warning "Log directory is not writable: $dir"
            mkdir -p "$dir"
            chown www-data:www-data "$dir"
            log "Created and fixed permissions for: $dir"
        fi
    done
    
    log "Cron configuration test completed"
}

# List current cron jobs
list_cron_jobs() {
    log "Current Sci-Bono LMS cron jobs:"
    
    if [ -f "$CRON_FILE" ]; then
        echo ""
        grep -v "^#" "$CRON_FILE" | grep -v "^$" | while read -r line; do
            if [[ $line =~ ^[A-Z] ]]; then
                echo "  Environment: $line"
            else
                echo "  Job: $line"
            fi
        done
        echo ""
    else
        warning "No cron file found: $CRON_FILE"
    fi
    
    # Show active cron status
    if systemctl is-active --quiet cron; then
        log "✓ Cron service is active"
    else
        warning "✗ Cron service is not active"
    fi
    
    # Show recent cron logs
    if [ -f "/var/log/syslog" ]; then
        echo ""
        log "Recent cron activity:"
        grep "sci-bono-lms" /var/log/syslog | tail -5 || echo "  No recent activity found"
    fi
}

# Remove all cron jobs
remove_cron_jobs() {
    warning "Removing Sci-Bono LMS cron jobs..."
    
    if [ -f "$CRON_FILE" ]; then
        rm -f "$CRON_FILE"
        log "Removed cron file: $CRON_FILE"
    fi
    
    # Remove log rotation config
    if [ -f "/etc/logrotate.d/sci-bono-lms" ]; then
        rm -f "/etc/logrotate.d/sci-bono-lms"
        log "Removed log rotation config"
    fi
    
    # Reload cron
    systemctl reload cron 2>/dev/null || service cron reload 2>/dev/null || true
    
    log "Cron jobs removed successfully"
}

# Main setup function
setup_cron_jobs() {
    log "Setting up Sci-Bono LMS cron jobs..."
    
    check_permissions
    create_cron_file
    create_logrotate_config
    create_monitoring_config
    create_maintenance_scripts
    
    # Reload cron daemon
    systemctl reload cron 2>/dev/null || service cron reload 2>/dev/null || true
    
    test_cron_setup
    
    log "✅ Cron jobs setup completed successfully!"
    log "Run '$0 list' to see all configured jobs"
}

# Parse command line arguments
case "${1:-setup}" in
    setup)
        setup_cron_jobs
        ;;
    list)
        list_cron_jobs
        ;;
    test)
        test_cron_setup
        ;;
    remove)
        remove_cron_jobs
        ;;
    reload)
        log "Reloading cron daemon..."
        systemctl reload cron 2>/dev/null || service cron reload 2>/dev/null || true
        log "Cron daemon reloaded"
        ;;
    *)
        echo "Usage: $0 {setup|list|test|remove|reload}"
        echo ""
        echo "  setup  - Setup all cron jobs (default)"
        echo "  list   - List current cron jobs"
        echo "  test   - Test cron configuration"
        echo "  remove - Remove all cron jobs"
        echo "  reload - Reload cron daemon"
        exit 1
        ;;
esac