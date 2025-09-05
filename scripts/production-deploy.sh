#!/bin/bash

# Sci-Bono LMS Production Deployment Script
# This script handles the complete production deployment process

set -e

# Configuration
PROJECT_NAME="sci-bono-lms"
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/html/Sci-Bono_Clubhoue_LMS"
BACKUP_PATH="/var/backups/sci-bono-lms"
LOG_FILE="/var/log/deploy-$(date +%Y%m%d-%H%M%S).log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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

# Check if running as root
check_permissions() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root for security reasons"
        exit 1
    fi
}

# Create necessary directories
setup_directories() {
    log "Setting up deployment directories..."
    
    sudo mkdir -p "$BACKUP_PATH"
    sudo mkdir -p "/var/sci-bono-data/mysql"
    sudo mkdir -p "/var/sci-bono-data/redis"
    sudo mkdir -p "$DEPLOY_PATH/logs"
    sudo mkdir -p "$DEPLOY_PATH/storage/cache"
    sudo mkdir -p "$DEPLOY_PATH/storage/sessions"
    sudo mkdir -p "$DEPLOY_PATH/storage/logs"
    
    # Set proper permissions
    sudo chown -R www-data:www-data "$DEPLOY_PATH/storage"
    sudo chown -R www-data:www-data "$DEPLOY_PATH/public/assets/uploads"
    sudo chown -R www-data:www-data "$DEPLOY_PATH/logs"
    
    sudo chmod -R 755 "$DEPLOY_PATH/storage"
    sudo chmod -R 755 "$DEPLOY_PATH/public/assets/uploads"
    sudo chmod -R 755 "$DEPLOY_PATH/logs"
}

# Backup current installation
backup_current() {
    log "Creating backup of current installation..."
    
    BACKUP_DATE=$(date +%Y%m%d-%H%M%S)
    BACKUP_DIR="$BACKUP_PATH/backup-$BACKUP_DATE"
    
    sudo mkdir -p "$BACKUP_DIR"
    
    # Backup application files
    if [ -d "$DEPLOY_PATH" ]; then
        sudo tar -czf "$BACKUP_DIR/application.tar.gz" -C "$(dirname $DEPLOY_PATH)" "$(basename $DEPLOY_PATH)"
        log "Application files backed up to $BACKUP_DIR/application.tar.gz"
    fi
    
    # Backup database
    if docker ps | grep -q "sci-bono-lms-db"; then
        docker exec sci-bono-lms-db mysqldump -u root -p"${DB_ROOT_PASSWORD}" --all-databases > "$BACKUP_DIR/database.sql"
        log "Database backed up to $BACKUP_DIR/database.sql"
    fi
    
    # Clean old backups (keep last 5)
    sudo find "$BACKUP_PATH" -type d -name "backup-*" | head -n -5 | sudo xargs rm -rf
    log "Old backups cleaned up"
}

# Check system requirements
check_requirements() {
    log "Checking system requirements..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
        exit 1
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed"
        exit 1
    fi
    
    # Check available disk space (require at least 5GB)
    AVAILABLE_SPACE=$(df "$DEPLOY_PATH" | awk 'NR==2 {print $4}')
    REQUIRED_SPACE=5242880  # 5GB in KB
    
    if [ "$AVAILABLE_SPACE" -lt "$REQUIRED_SPACE" ]; then
        error "Insufficient disk space. Required: 5GB, Available: $(($AVAILABLE_SPACE/1024/1024))GB"
        exit 1
    fi
    
    log "System requirements check passed"
}

# Update environment configuration
setup_environment() {
    log "Setting up production environment..."
    
    if [ ! -f "$DEPLOY_PATH/.env" ]; then
        if [ -f "$DEPLOY_PATH/.env.production" ]; then
            cp "$DEPLOY_PATH/.env.production" "$DEPLOY_PATH/.env"
            warning "Created .env from .env.production template. Please update with actual production values!"
        else
            error "No environment configuration found. Please create .env file."
            exit 1
        fi
    fi
    
    # Validate critical environment variables
    source "$DEPLOY_PATH/.env"
    
    if [ -z "$DB_PASSWORD" ] || [ "$DB_PASSWORD" = "CHANGE_THIS_IN_PRODUCTION" ]; then
        error "DB_PASSWORD must be set in .env file"
        exit 1
    fi
    
    if [ -z "$DB_ROOT_PASSWORD" ] || [ "$DB_ROOT_PASSWORD" = "CHANGE_THIS_ROOT_PASSWORD_IN_PRODUCTION" ]; then
        error "DB_ROOT_PASSWORD must be set in .env file"
        exit 1
    fi
    
    log "Environment configuration validated"
}

# Install/Update SSL certificates
setup_ssl() {
    log "Setting up SSL certificates..."
    
    SSL_DIR="$DEPLOY_PATH/docker/ssl"
    sudo mkdir -p "$SSL_DIR"
    
    if [ ! -f "$SSL_DIR/sci-bono-lms.crt" ]; then
        warning "SSL certificate not found. Generating self-signed certificate for testing..."
        
        sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout "$SSL_DIR/sci-bono-lms.key" \
            -out "$SSL_DIR/sci-bono-lms.crt" \
            -subj "/C=ZA/ST=Gauteng/L=Johannesburg/O=Sci-Bono/CN=sci-bono-lms.com"
        
        warning "Self-signed certificate generated. Replace with proper SSL certificate for production!"
    fi
    
    # Set proper permissions
    sudo chmod 600 "$SSL_DIR/sci-bono-lms.key"
    sudo chmod 644 "$SSL_DIR/sci-bono-lms.crt"
}

# Deploy application
deploy_application() {
    log "Deploying application..."
    
    cd "$DEPLOY_PATH"
    
    # Pull latest images
    docker-compose -f docker-compose.prod.yml pull
    
    # Stop existing containers
    docker-compose -f docker-compose.prod.yml down
    
    # Start new containers
    docker-compose -f docker-compose.prod.yml up -d
    
    # Wait for services to be ready
    log "Waiting for services to start..."
    sleep 30
    
    # Check service health
    check_service_health
    
    log "Application deployment completed"
}

# Check service health
check_service_health() {
    log "Checking service health..."
    
    # Check application container
    if ! docker ps | grep -q "sci-bono-lms-app"; then
        error "Application container is not running"
        exit 1
    fi
    
    # Check database container
    if ! docker ps | grep -q "sci-bono-lms-db"; then
        error "Database container is not running"
        exit 1
    fi
    
    # Check application response
    MAX_ATTEMPTS=12
    ATTEMPT=1
    
    while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
        if curl -s -f http://localhost/health > /dev/null; then
            log "Application is responding to health checks"
            return 0
        fi
        
        info "Attempt $ATTEMPT/$MAX_ATTEMPTS: Waiting for application to respond..."
        sleep 10
        ATTEMPT=$((ATTEMPT + 1))
    done
    
    error "Application is not responding after $MAX_ATTEMPTS attempts"
    exit 1
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    # Check if migration files exist
    if [ -d "$DEPLOY_PATH/database/migrations" ]; then
        docker exec sci-bono-lms-app php cli/database.php migrate
        log "Database migrations completed"
    else
        warning "No migration files found, skipping migrations"
    fi
}

# Update file permissions
fix_permissions() {
    log "Fixing file permissions..."
    
    sudo chown -R www-data:www-data "$DEPLOY_PATH/storage"
    sudo chown -R www-data:www-data "$DEPLOY_PATH/public/assets/uploads"
    sudo chown -R www-data:www-data "$DEPLOY_PATH/logs"
    
    sudo chmod -R 755 "$DEPLOY_PATH/storage"
    sudo chmod -R 755 "$DEPLOY_PATH/public/assets/uploads"
    sudo chmod -R 755 "$DEPLOY_PATH/logs"
    
    # Make scripts executable
    sudo chmod +x "$DEPLOY_PATH/scripts/"*.sh
    
    log "File permissions updated"
}

# Clear caches
clear_caches() {
    log "Clearing application caches..."
    
    # Clear OPcache
    docker exec sci-bono-lms-app php -r "opcache_reset();"
    
    # Clear Redis cache
    if docker ps | grep -q "sci-bono-lms-cache"; then
        docker exec sci-bono-lms-cache redis-cli FLUSHDB
    fi
    
    # Clear application cache files
    sudo rm -rf "$DEPLOY_PATH/storage/cache/*"
    
    log "Caches cleared"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Create monitoring directories
    sudo mkdir -p "/var/log/prometheus"
    sudo mkdir -p "/var/lib/prometheus"
    
    # Set permissions for monitoring
    sudo chown -R 65534:65534 "/var/lib/prometheus"
    sudo chown -R 65534:65534 "/var/log/prometheus"
    
    log "Monitoring setup completed"
}

# Post-deployment tests
run_post_deployment_tests() {
    log "Running post-deployment tests..."
    
    # Test application endpoints
    ENDPOINTS=(
        "http://localhost/health"
        "http://localhost/api/health"
        "http://localhost/"
    )
    
    for endpoint in "${ENDPOINTS[@]}"; do
        if curl -s -f "$endpoint" > /dev/null; then
            info "✓ $endpoint is responding"
        else
            warning "✗ $endpoint is not responding"
        fi
    done
    
    # Test database connection
    if docker exec sci-bono-lms-app php -r "
        require_once '/var/www/html/server.php';
        if (\$connection->ping()) {
            echo 'Database connection successful';
            exit(0);
        } else {
            echo 'Database connection failed';
            exit(1);
        }
    "; then
        info "✓ Database connection test passed"
    else
        warning "✗ Database connection test failed"
    fi
    
    log "Post-deployment tests completed"
}

# Cleanup function
cleanup() {
    log "Cleaning up temporary files..."
    
    # Remove unused Docker images
    docker image prune -f
    
    # Clean up temporary files
    sudo find /tmp -name "*sci-bono*" -type f -mtime +1 -delete 2>/dev/null || true
    
    log "Cleanup completed"
}

# Rollback function
rollback() {
    error "Deployment failed. Starting rollback process..."
    
    # Stop current containers
    docker-compose -f docker-compose.prod.yml down
    
    # Restore from latest backup
    LATEST_BACKUP=$(sudo ls -t "$BACKUP_PATH" | head -n1)
    if [ -n "$LATEST_BACKUP" ]; then
        log "Restoring from backup: $LATEST_BACKUP"
        # Add rollback logic here
        warning "Manual rollback required. Latest backup: $BACKUP_PATH/$LATEST_BACKUP"
    fi
    
    exit 1
}

# Main deployment function
main() {
    log "Starting Sci-Bono LMS production deployment..."
    
    # Set trap for cleanup on exit or error
    trap cleanup EXIT
    trap rollback ERR
    
    check_permissions
    check_requirements
    setup_directories
    backup_current
    setup_environment
    setup_ssl
    deploy_application
    run_migrations
    fix_permissions
    clear_caches
    setup_monitoring
    run_post_deployment_tests
    
    log "🎉 Deployment completed successfully!"
    log "Application is available at: https://sci-bono-lms.com"
    log "Monitoring dashboard: http://localhost:9090"
    log "Deployment log saved to: $LOG_FILE"
}

# Parse command line arguments
case "${1:-deploy}" in
    deploy)
        main
        ;;
    backup)
        check_permissions
        backup_current
        ;;
    rollback)
        rollback
        ;;
    health)
        check_service_health
        ;;
    logs)
        docker-compose -f docker-compose.prod.yml logs -f
        ;;
    *)
        echo "Usage: $0 {deploy|backup|rollback|health|logs}"
        exit 1
        ;;
esac