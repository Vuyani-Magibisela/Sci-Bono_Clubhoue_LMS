#!/bin/bash

# Sci-Bono LMS Production Deployment Script
# Phase 7: API Development & Testing - Production Deployment

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DEPLOYMENT_ENV="${DEPLOYMENT_ENV:-production}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/sci-bono-lms}"
LOG_FILE="/var/log/sci-bono-lms-deployment.log"

# Default values
DRY_RUN=false
SKIP_BACKUP=false
SKIP_TESTS=false
FORCE_DEPLOY=false
ROLLBACK_VERSION=""

echo -e "${BLUE}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Sci-Bono LMS Production Deployment                       ║${NC}"
echo -e "${BLUE}║                       Phase 7: API Development & Testing                    ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print status messages
print_status() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] [INFO]${NC} $1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [INFO] $1" >> "$LOG_FILE"
}

print_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] [WARNING]${NC} $1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [WARNING] $1" >> "$LOG_FILE"
}

print_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR]${NC} $1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $1" >> "$LOG_FILE"
}

# Function to show help
show_help() {
    echo "Sci-Bono LMS Deployment Script"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --env=ENV             Deployment environment (production, staging, testing)"
    echo "  --dry-run             Show what would be done without executing"
    echo "  --skip-backup         Skip database backup"
    echo "  --skip-tests          Skip running tests"
    echo "  --force               Force deployment even if tests fail"
    echo "  --rollback=VERSION    Rollback to specific version"
    echo "  --help                Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Standard production deployment"
    echo "  $0 --env=staging                     # Deploy to staging"
    echo "  $0 --dry-run                         # Dry run to see what would happen"
    echo "  $0 --rollback=v1.2.3                # Rollback to version v1.2.3"
    echo "  $0 --skip-tests --force              # Force deploy without tests"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --env=*)
            DEPLOYMENT_ENV="${1#*=}"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --skip-tests)
            SKIP_TESTS=true
            shift
            ;;
        --force)
            FORCE_DEPLOY=true
            shift
            ;;
        --rollback=*)
            ROLLBACK_VERSION="${1#*=}"
            shift
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking deployment prerequisites..."
    
    # Check if running as appropriate user
    if [ "$EUID" -eq 0 ]; then
        print_warning "Running as root. Consider using a dedicated deployment user."
    fi
    
    # Check required commands
    local required_commands=("docker" "docker-compose" "git" "curl" "mysql")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            print_error "Required command '$cmd' not found"
            exit 1
        fi
    done
    
    # Check Docker daemon
    if ! docker info &> /dev/null; then
        print_error "Docker daemon is not running"
        exit 1
    fi
    
    # Check disk space
    local available_space=$(df / | tail -1 | awk '{print $4}')
    local required_space=1048576  # 1GB in KB
    
    if [ "$available_space" -lt "$required_space" ]; then
        print_error "Insufficient disk space. Required: 1GB, Available: $(($available_space/1024))MB"
        exit 1
    fi
    
    # Check if secrets files exist
    local secrets_dir="$PROJECT_ROOT/secrets"
    local required_secrets=("db_password.txt" "mysql_root_password.txt")
    
    for secret in "${required_secrets[@]}"; do
        if [ ! -f "$secrets_dir/$secret" ]; then
            print_error "Missing secret file: $secrets_dir/$secret"
            exit 1
        fi
    done
    
    print_status "Prerequisites check passed"
}

# Function to create backup
create_backup() {
    if [ "$SKIP_BACKUP" = true ]; then
        print_warning "Skipping backup as requested"
        return 0
    fi
    
    print_status "Creating deployment backup..."
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="sci-bono-lms-backup-$backup_timestamp"
    local backup_path="$BACKUP_DIR/$backup_name"
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would create backup at: $backup_path"
        return 0
    fi
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Backup database
    print_status "Backing up database..."
    local db_password=$(cat "$PROJECT_ROOT/secrets/db_password.txt")
    
    if docker-compose -f "$PROJECT_ROOT/docker-compose.yml" ps database | grep -q "Up"; then
        docker-compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T database \
            mysqldump -u sci_bono_user -p"$db_password" sci_bono_lms > "$backup_path/database.sql"
    else
        print_warning "Database container not running, skipping database backup"
    fi
    
    # Backup application files
    print_status "Backing up application files..."
    rsync -av --exclude='logs/' --exclude='storage/cache/' --exclude='vendor/' \
        "$PROJECT_ROOT/" "$backup_path/app/" &> /dev/null
    
    # Backup Docker volumes
    print_status "Backing up Docker volumes..."
    docker run --rm -v sci-bono_clubhoue_lms_db-data:/data -v "$backup_path:/backup" \
        alpine tar czf /backup/db-data.tar.gz -C /data . &> /dev/null || true
    
    # Create backup manifest
    echo "Backup created: $(date)" > "$backup_path/backup.manifest"
    echo "Environment: $DEPLOYMENT_ENV" >> "$backup_path/backup.manifest"
    echo "Git commit: $(git -C "$PROJECT_ROOT" rev-parse HEAD 2>/dev/null || echo 'unknown')" >> "$backup_path/backup.manifest"
    
    # Compress backup
    cd "$BACKUP_DIR"
    tar czf "${backup_name}.tar.gz" "$backup_name"
    rm -rf "$backup_name"
    
    print_status "Backup created: ${backup_name}.tar.gz"
    
    # Clean old backups (keep last 10)
    ls -t "$BACKUP_DIR"/sci-bono-lms-backup-*.tar.gz | tail -n +11 | xargs -r rm -f
    
    export BACKUP_FILE="${backup_name}.tar.gz"
}

# Function to run tests
run_tests() {
    if [ "$SKIP_TESTS" = true ]; then
        print_warning "Skipping tests as requested"
        return 0
    fi
    
    print_status "Running deployment tests..."
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would run test suite"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    # Run PHP tests
    print_status "Running PHP tests..."
    if ! php tests/run-tests.php --suite=all --stop-on-failure; then
        print_error "PHP tests failed"
        if [ "$FORCE_DEPLOY" = false ]; then
            exit 1
        else
            print_warning "Continuing deployment despite test failures (--force used)"
        fi
    fi
    
    # Run API tests
    print_status "Running API tests..."
    if ! php tests/run-tests.php --suite=api --stop-on-failure; then
        print_error "API tests failed"
        if [ "$FORCE_DEPLOY" = false ]; then
            exit 1
        else
            print_warning "Continuing deployment despite API test failures (--force used)"
        fi
    fi
    
    # Performance baseline test
    print_status "Running performance baseline tests..."
    if ! php tools/performance-cli.php benchmark --type=all --iterations=10; then
        print_warning "Performance baseline tests failed (non-critical)"
    fi
    
    print_status "All tests passed"
}

# Function to deploy application
deploy_application() {
    print_status "Deploying application..."
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would deploy application using Docker Compose"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    # Set environment variables
    export DEPLOYMENT_ENV="$DEPLOYMENT_ENV"
    export APP_ENV="$DEPLOYMENT_ENV"
    
    # Pull latest images
    print_status "Pulling Docker images..."
    docker-compose pull
    
    # Build custom images
    print_status "Building application image..."
    docker-compose build --no-cache app
    
    # Stop existing services
    print_status "Stopping existing services..."
    docker-compose down --remove-orphans
    
    # Start services
    print_status "Starting services..."
    docker-compose up -d
    
    # Wait for services to be ready
    print_status "Waiting for services to start..."
    sleep 30
    
    # Check service health
    local max_attempts=12
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -sf http://localhost/app/Controllers/PerformanceDashboardController.php?action=health &> /dev/null; then
            print_status "Application is healthy"
            break
        else
            print_status "Waiting for application to be ready... ($attempt/$max_attempts)"
            sleep 10
            ((attempt++))
        fi
    done
    
    if [ $attempt -gt $max_attempts ]; then
        print_error "Application failed to start properly"
        print_status "Checking container logs..."
        docker-compose logs --tail=50 app
        exit 1
    fi
}

# Function to run database migrations
run_migrations() {
    print_status "Running database migrations..."
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would run database migrations"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    # Check if migration files exist
    if [ -d "database/migrations" ]; then
        # Run migrations through the application container
        docker-compose exec -T app php tools/migrate.php --env="$DEPLOYMENT_ENV"
    else
        print_warning "No migration directory found, skipping migrations"
    fi
}

# Function to update configurations
update_configurations() {
    print_status "Updating configurations..."
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would update configurations"
        return 0
    fi
    
    # Clear application cache
    docker-compose exec -T app php -r "
        if (function_exists('opcache_reset')) {
            opcache_reset();
            echo 'OPcache reset\n';
        }
    "
    
    # Clear session files
    docker-compose exec -T app find /var/www/html/storage/sessions -type f -name 'sess_*' -mtime +1 -delete || true
    
    # Update performance monitoring
    docker-compose exec -T app php tools/performance-cli.php optimize
    
    print_status "Configurations updated"
}

# Function to verify deployment
verify_deployment() {
    print_status "Verifying deployment..."
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would verify deployment"
        return 0
    fi
    
    local verification_failed=false
    
    # Check web server response
    print_status "Checking web server..."
    if ! curl -sf http://localhost/ &> /dev/null; then
        print_error "Web server not responding"
        verification_failed=true
    fi
    
    # Check API endpoints
    print_status "Checking API endpoints..."
    if ! curl -sf http://localhost/app/Controllers/PerformanceDashboardController.php?action=health &> /dev/null; then
        print_error "Health check endpoint not responding"
        verification_failed=true
    fi
    
    # Check database connection
    print_status "Checking database connection..."
    if ! docker-compose exec -T database mysql -u sci_bono_user -p"$(cat secrets/db_password.txt)" -e "SELECT 1" sci_bono_lms &> /dev/null; then
        print_error "Database connection failed"
        verification_failed=true
    fi
    
    # Check Redis connection
    print_status "Checking Redis connection..."
    if ! docker-compose exec -T redis redis-cli ping &> /dev/null; then
        print_error "Redis connection failed"
        verification_failed=true
    fi
    
    # Run post-deployment tests
    print_status "Running post-deployment verification tests..."
    if ! docker-compose exec -T app php tools/performance-cli.php healthcheck; then
        print_error "Health check failed"
        verification_failed=true
    fi
    
    if [ "$verification_failed" = true ]; then
        print_error "Deployment verification failed"
        exit 1
    fi
    
    print_status "Deployment verification successful"
}

# Function to perform rollback
perform_rollback() {
    if [ -z "$ROLLBACK_VERSION" ]; then
        return 0
    fi
    
    print_status "Performing rollback to version: $ROLLBACK_VERSION"
    
    if [ "$DRY_RUN" = true ]; then
        print_status "[DRY RUN] Would rollback to version: $ROLLBACK_VERSION"
        return 0
    fi
    
    # Find backup file
    local backup_file="$BACKUP_DIR/sci-bono-lms-backup-$ROLLBACK_VERSION.tar.gz"
    
    if [ ! -f "$backup_file" ]; then
        # Try to find backup by partial match
        backup_file=$(ls "$BACKUP_DIR"/sci-bono-lms-backup-*"$ROLLBACK_VERSION"*.tar.gz 2>/dev/null | head -1)
        
        if [ -z "$backup_file" ]; then
            print_error "Rollback backup not found: $ROLLBACK_VERSION"
            exit 1
        fi
    fi
    
    print_status "Using backup file: $backup_file"
    
    # Stop services
    docker-compose down --remove-orphans
    
    # Extract backup
    cd "$BACKUP_DIR"
    tar xzf "$(basename "$backup_file")"
    
    # Restore application files
    local backup_dir=$(basename "$backup_file" .tar.gz)
    rsync -av "$backup_dir/app/" "$PROJECT_ROOT/"
    
    # Restore database
    if [ -f "$backup_dir/database.sql" ]; then
        print_status "Restoring database..."
        docker-compose up -d database
        sleep 10
        
        local db_password=$(cat "$PROJECT_ROOT/secrets/db_password.txt")
        docker-compose exec -T database mysql -u sci_bono_user -p"$db_password" sci_bono_lms < "$backup_dir/database.sql"
    fi
    
    # Start services
    docker-compose up -d
    
    # Clean up
    rm -rf "$backup_dir"
    
    print_status "Rollback completed successfully"
}

# Function to send notifications
send_notifications() {
    local status="$1"
    local message="$2"
    
    # Send email notification if configured
    if [ -n "${NOTIFICATION_EMAIL:-}" ]; then
        echo "$message" | mail -s "Sci-Bono LMS Deployment $status" "$NOTIFICATION_EMAIL" || true
    fi
    
    # Send Slack notification if configured
    if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"Sci-Bono LMS Deployment $status: $message\"}" \
            "$SLACK_WEBHOOK_URL" || true
    fi
}

# Function to cleanup
cleanup() {
    print_status "Performing cleanup..."
    
    # Remove old Docker images
    docker image prune -f --filter "until=24h" || true
    
    # Remove old log files
    find /var/log -name "*sci-bono*" -type f -mtime +7 -delete 2>/dev/null || true
    
    print_status "Cleanup completed"
}

# Main deployment function
main() {
    local start_time=$(date +%s)
    
    print_status "Starting deployment process..."
    print_status "Environment: $DEPLOYMENT_ENV"
    print_status "Dry run: $DRY_RUN"
    
    # Create log directory
    mkdir -p "$(dirname "$LOG_FILE")"
    
    try {
        # Check if rollback is requested
        if [ -n "$ROLLBACK_VERSION" ]; then
            perform_rollback
            send_notifications "SUCCESS" "Rollback to $ROLLBACK_VERSION completed successfully"
            return 0
        fi
        
        # Run deployment steps
        check_prerequisites
        create_backup
        run_tests
        deploy_application
        run_migrations
        update_configurations
        verify_deployment
        cleanup
        
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        print_status "Deployment completed successfully in ${duration} seconds"
        
        # Send success notification
        send_notifications "SUCCESS" "Deployment to $DEPLOYMENT_ENV completed successfully in ${duration} seconds"
        
    } catch {
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        print_error "Deployment failed after ${duration} seconds"
        
        # Send failure notification
        send_notifications "FAILED" "Deployment to $DEPLOYMENT_ENV failed after ${duration} seconds. Check logs for details."
        
        exit 1
    }
}

# Error handling
try() {
    [[ $- = *e* ]]; SAVED_OPT_E=$?
    set +e
}

catch() {
    export exception_code=$?
    (( SAVED_OPT_E )) && set +e
    return $exception_code
}

# Trap for cleanup on exit
trap 'cleanup; exit' INT TERM EXIT

# Run main function
main "$@"