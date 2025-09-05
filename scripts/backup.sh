#!/bin/bash

# Sci-Bono LMS Backup Script
# Automated backup solution for production environment

set -e

# Configuration
BACKUP_ROOT="/var/backups/sci-bono-lms"
APP_ROOT="/var/www/html/Sci-Bono_Clubhoue_LMS"
RETENTION_DAYS=30
LOG_FILE="/var/log/backup-$(date +%Y%m%d-%H%M%S).log"
NOTIFICATION_EMAIL="admin@sci-bono-lms.com"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}" | tee -a "$LOG_FILE"
}

# Check if running with proper permissions
check_permissions() {
    if [[ $EUID -eq 0 ]]; then
        warning "Running as root - ensure backup directory permissions are correct"
    fi
    
    if [ ! -w "$(dirname "$BACKUP_ROOT")" ]; then
        error "Cannot write to backup directory: $BACKUP_ROOT"
        exit 1
    fi
}

# Create backup directory structure
setup_backup_dir() {
    local backup_date=$(date +%Y%m%d-%H%M%S)
    BACKUP_DIR="$BACKUP_ROOT/backup-$backup_date"
    
    mkdir -p "$BACKUP_DIR"/{database,application,config,uploads,logs}
    log "Created backup directory: $BACKUP_DIR"
}

# Backup database
backup_database() {
    log "Starting database backup..."
    
    # Load environment variables
    if [ -f "$APP_ROOT/.env" ]; then
        source "$APP_ROOT/.env"
    else
        error "Environment file not found: $APP_ROOT/.env"
        exit 1
    fi
    
    # Check if database container is running
    if ! docker ps | grep -q "sci-bono-lms-db"; then
        error "Database container is not running"
        exit 1
    fi
    
    # Create database backup
    local db_backup_file="$BACKUP_DIR/database/accounts-$(date +%Y%m%d-%H%M%S).sql"
    
    if docker exec sci-bono-lms-db mysqldump \
        -u root \
        -p"$DB_ROOT_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --all-databases > "$db_backup_file"; then
        
        # Compress the backup
        gzip "$db_backup_file"
        log "Database backup completed: ${db_backup_file}.gz"
        
        # Verify backup integrity
        if gunzip -t "${db_backup_file}.gz"; then
            log "Database backup integrity verified"
        else
            error "Database backup integrity check failed"
            exit 1
        fi
    else
        error "Database backup failed"
        exit 1
    fi
}

# Backup application files
backup_application() {
    log "Starting application backup..."
    
    # Files to exclude from backup
    local exclude_patterns=(
        "--exclude=storage/cache/*"
        "--exclude=storage/sessions/*"
        "--exclude=storage/logs/*"
        "--exclude=logs/*"
        "--exclude=vendor/*"
        "--exclude=node_modules/*"
        "--exclude=.git/*"
        "--exclude=tests/*"
        "--exclude=*.log"
    )
    
    # Create application backup
    local app_backup_file="$BACKUP_DIR/application/app-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    if tar -czf "$app_backup_file" \
        "${exclude_patterns[@]}" \
        -C "$(dirname "$APP_ROOT")" \
        "$(basename "$APP_ROOT")"; then
        
        log "Application backup completed: $app_backup_file"
        
        # Verify backup
        if tar -tzf "$app_backup_file" > /dev/null; then
            log "Application backup integrity verified"
        else
            error "Application backup integrity check failed"
            exit 1
        fi
    else
        error "Application backup failed"
        exit 1
    fi
}

# Backup configuration files
backup_config() {
    log "Starting configuration backup..."
    
    local config_backup_file="$BACKUP_DIR/config/config-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    # Config files to backup
    local config_files=(
        "$APP_ROOT/.env"
        "$APP_ROOT/docker-compose.prod.yml"
        "$APP_ROOT/docker/"
        "/etc/ssl/certs/sci-bono-lms*"
        "/etc/cron.d/sci-bono-lms"
    )
    
    # Create temporary directory for config files
    local temp_config_dir="/tmp/config-backup-$$"
    mkdir -p "$temp_config_dir"
    
    # Copy config files that exist
    for file in "${config_files[@]}"; do
        if [ -e "$file" ]; then
            cp -r "$file" "$temp_config_dir/"
        fi
    done
    
    # Create config backup
    if tar -czf "$config_backup_file" -C "/tmp" "config-backup-$$"; then
        log "Configuration backup completed: $config_backup_file"
    else
        error "Configuration backup failed"
        exit 1
    fi
    
    # Cleanup temp directory
    rm -rf "$temp_config_dir"
}

# Backup uploaded files
backup_uploads() {
    log "Starting uploads backup..."
    
    local uploads_dir="$APP_ROOT/public/assets/uploads"
    
    if [ ! -d "$uploads_dir" ]; then
        warning "Uploads directory not found: $uploads_dir"
        return 0
    fi
    
    local uploads_backup_file="$BACKUP_DIR/uploads/uploads-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    if tar -czf "$uploads_backup_file" -C "$uploads_dir" .; then
        log "Uploads backup completed: $uploads_backup_file"
    else
        error "Uploads backup failed"
        exit 1
    fi
}

# Backup log files
backup_logs() {
    log "Starting logs backup..."
    
    local logs_dir="$APP_ROOT/logs"
    
    if [ ! -d "$logs_dir" ]; then
        warning "Logs directory not found: $logs_dir"
        return 0
    fi
    
    local logs_backup_file="$BACKUP_DIR/logs/logs-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    # Only backup logs from the last 7 days
    if find "$logs_dir" -name "*.log" -mtime -7 | tar -czf "$logs_backup_file" -T -; then
        log "Logs backup completed: $logs_backup_file"
    else
        warning "Logs backup completed with warnings"
    fi
}

# Create backup manifest
create_manifest() {
    log "Creating backup manifest..."
    
    local manifest_file="$BACKUP_DIR/MANIFEST.txt"
    
    cat > "$manifest_file" << EOF
Sci-Bono LMS Backup Manifest
============================
Backup Date: $(date)
Backup Directory: $BACKUP_DIR
Server: $(hostname)
Application Version: $(git -C "$APP_ROOT" describe --tags --always 2>/dev/null || echo "unknown")

Files Included:
$(find "$BACKUP_DIR" -type f -exec basename {} \; | sort)

Directory Sizes:
$(du -sh "$BACKUP_DIR"/* 2>/dev/null)

Total Backup Size:
$(du -sh "$BACKUP_DIR")

Checksums (SHA256):
$(find "$BACKUP_DIR" -type f -name "*.tar.gz" -o -name "*.sql.gz" | xargs sha256sum)
EOF

    log "Backup manifest created: $manifest_file"
}

# Clean old backups
cleanup_old_backups() {
    log "Cleaning up old backups (older than $RETENTION_DAYS days)..."
    
    local deleted_count=0
    
    while read -r backup_dir; do
        if [ -n "$backup_dir" ] && [ -d "$backup_dir" ]; then
            info "Removing old backup: $backup_dir"
            rm -rf "$backup_dir"
            deleted_count=$((deleted_count + 1))
        fi
    done < <(find "$BACKUP_ROOT" -type d -name "backup-*" -mtime +$RETENTION_DAYS)
    
    log "Cleaned up $deleted_count old backups"
}

# Verify backup completeness
verify_backup() {
    log "Verifying backup completeness..."
    
    local required_files=(
        "database/*.sql.gz"
        "application/*.tar.gz"
        "config/*.tar.gz"
        "MANIFEST.txt"
    )
    
    local missing_files=0
    
    for pattern in "${required_files[@]}"; do
        if ! ls "$BACKUP_DIR"/$pattern >/dev/null 2>&1; then
            error "Missing backup component: $pattern"
            missing_files=$((missing_files + 1))
        fi
    done
    
    if [ $missing_files -eq 0 ]; then
        log "Backup verification successful - all components present"
    else
        error "Backup verification failed - $missing_files components missing"
        exit 1
    fi
    
    # Check backup size (should be at least 10MB for a meaningful backup)
    local backup_size=$(du -s "$BACKUP_DIR" | cut -f1)
    if [ "$backup_size" -lt 10240 ]; then  # 10MB in KB
        warning "Backup size seems small: $(du -sh "$BACKUP_DIR" | cut -f1)"
    fi
}

# Send notification
send_notification() {
    local status="$1"
    local backup_size=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1 || echo "unknown")
    
    if command -v mail >/dev/null 2>&1 && [ -n "$NOTIFICATION_EMAIL" ]; then
        local subject="Sci-Bono LMS Backup $status - $(date)"
        local body="Backup $status
        
Backup Directory: $BACKUP_DIR
Backup Size: $backup_size
Server: $(hostname)
Date: $(date)

Log file: $LOG_FILE

$(tail -20 "$LOG_FILE" 2>/dev/null || echo "No log available")"

        echo "$body" | mail -s "$subject" "$NOTIFICATION_EMAIL"
        log "Notification sent to $NOTIFICATION_EMAIL"
    fi
}

# Main backup function
main() {
    log "Starting Sci-Bono LMS backup process..."
    
    # Trap to handle errors and send notifications
    trap 'error "Backup failed with exit code $?"; send_notification "FAILED"; exit 1' ERR
    
    check_permissions
    setup_backup_dir
    backup_database
    backup_application
    backup_config
    backup_uploads
    backup_logs
    create_manifest
    verify_backup
    cleanup_old_backups
    
    log "✅ Backup completed successfully!"
    log "Backup location: $BACKUP_DIR"
    log "Total backup size: $(du -sh "$BACKUP_DIR" | cut -f1)"
    
    send_notification "SUCCESS"
}

# Parse command line arguments
case "${1:-full}" in
    full)
        main
        ;;
    database)
        check_permissions
        setup_backup_dir
        backup_database
        create_manifest
        log "Database-only backup completed: $BACKUP_DIR"
        ;;
    files)
        check_permissions
        setup_backup_dir
        backup_application
        backup_uploads
        backup_logs
        create_manifest
        log "Files-only backup completed: $BACKUP_DIR"
        ;;
    config)
        check_permissions
        setup_backup_dir
        backup_config
        create_manifest
        log "Configuration-only backup completed: $BACKUP_DIR"
        ;;
    cleanup)
        cleanup_old_backups
        ;;
    verify)
        if [ -z "$2" ]; then
            error "Usage: $0 verify <backup-directory>"
            exit 1
        fi
        BACKUP_DIR="$2"
        verify_backup
        ;;
    *)
        echo "Usage: $0 {full|database|files|config|cleanup|verify <backup-dir>}"
        echo ""
        echo "  full     - Complete backup (default)"
        echo "  database - Database only"
        echo "  files    - Application files, uploads, and logs only"
        echo "  config   - Configuration files only"
        echo "  cleanup  - Remove old backups"
        echo "  verify   - Verify existing backup"
        exit 1
        ;;
esac