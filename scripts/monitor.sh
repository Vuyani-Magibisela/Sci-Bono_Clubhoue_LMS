#!/bin/bash

# Sci-Bono LMS Monitoring Script
# System health monitoring and alerting

set -e

# Configuration
APP_ROOT="/var/www/html/Sci-Bono_Clubhoue_LMS"
LOG_FILE="/var/log/monitor-$(date +%Y%m%d).log"
ALERT_EMAIL="admin@sci-bono-lms.com"
THRESHOLDS_CONFIG="$APP_ROOT/config/monitoring-thresholds.conf"

# Default thresholds (can be overridden by config file)
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
DISK_THRESHOLD=90
RESPONSE_TIME_THRESHOLD=5000  # milliseconds
ERROR_RATE_THRESHOLD=5        # percentage

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Load configuration if exists
if [ -f "$THRESHOLDS_CONFIG" ]; then
    source "$THRESHOLDS_CONFIG"
fi

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

# Send alert notification
send_alert() {
    local severity="$1"
    local message="$2"
    local metric="$3"
    local value="$4"
    local threshold="$5"
    
    local subject="[$severity] Sci-Bono LMS Alert - $metric"
    local body="Alert Details:
    
Severity: $severity
Metric: $metric
Current Value: $value
Threshold: $threshold
Time: $(date)
Server: $(hostname)

Message: $message

System Status:
CPU Usage: $(get_cpu_usage)%
Memory Usage: $(get_memory_usage)%
Disk Usage: $(get_disk_usage)%
Load Average: $(uptime | awk -F'load average:' '{ print $2 }')"

    # Send email alert if mail is available
    if command -v mail >/dev/null 2>&1 && [ -n "$ALERT_EMAIL" ]; then
        echo "$body" | mail -s "$subject" "$ALERT_EMAIL"
        log "Alert sent to $ALERT_EMAIL: $message"
    fi
    
    # Write to system log
    logger -t "sci-bono-lms-monitor" "$severity: $message"
    
    # Create alert file for external monitoring systems
    echo "$(date)|$severity|$metric|$value|$threshold|$message" >> /var/log/sci-bono-lms-alerts.log
}

# Get CPU usage percentage
get_cpu_usage() {
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | awk -F'%' '{print $1}')
    if [ -z "$cpu_usage" ]; then
        # Fallback method
        cpu_usage=$(vmstat 1 2 | tail -1 | awk '{print 100-$15}')
    fi
    printf "%.0f" "$cpu_usage"
}

# Get memory usage percentage
get_memory_usage() {
    local mem_info=$(free | grep Mem)
    local total=$(echo $mem_info | awk '{print $2}')
    local used=$(echo $mem_info | awk '{print $3}')
    local usage=$((used * 100 / total))
    echo "$usage"
}

# Get disk usage percentage
get_disk_usage() {
    local disk_usage=$(df "$APP_ROOT" | awk 'NR==2 {print $(NF-1)}' | sed 's/%//')
    echo "$disk_usage"
}

# Check Docker container status
check_docker_containers() {
    info "Checking Docker container status..."
    
    local containers=("sci-bono-lms-app" "sci-bono-lms-db" "sci-bono-lms-cache")
    local failed_containers=()
    
    for container in "${containers[@]}"; do
        if docker ps --format "table {{.Names}}" | grep -q "$container"; then
            info "✓ Container $container is running"
            
            # Check container health if health check is available
            local health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no-health-check")
            if [ "$health" = "healthy" ] || [ "$health" = "no-health-check" ]; then
                info "✓ Container $container is healthy"
            else
                warning "Container $container health status: $health"
                failed_containers+=("$container")
            fi
        else
            error "✗ Container $container is not running"
            failed_containers+=("$container")
        fi
    done
    
    if [ ${#failed_containers[@]} -gt 0 ]; then
        send_alert "CRITICAL" "Docker containers not running: ${failed_containers[*]}" "Docker" "${#failed_containers[@]}" "0"
        return 1
    fi
    
    return 0
}

# Check application response time
check_response_time() {
    info "Checking application response time..."
    
    local endpoints=(
        "http://localhost/health"
        "http://localhost/"
        "http://localhost/api/health"
    )
    
    local total_time=0
    local failed_endpoints=()
    
    for endpoint in "${endpoints[@]}"; do
        local response_time=$(curl -o /dev/null -s -w '%{time_total}' --connect-timeout 10 --max-time 30 "$endpoint" || echo "999")
        local response_time_ms=$(echo "$response_time * 1000" | bc -l | cut -d. -f1)
        
        if [ "$response_time_ms" -lt "$RESPONSE_TIME_THRESHOLD" ] && [ "$response_time_ms" -ne 999000 ]; then
            info "✓ $endpoint responds in ${response_time_ms}ms"
            total_time=$((total_time + response_time_ms))
        else
            error "✗ $endpoint is slow or unreachable (${response_time_ms}ms)"
            failed_endpoints+=("$endpoint")
        fi
    done
    
    if [ ${#failed_endpoints[@]} -gt 0 ]; then
        send_alert "CRITICAL" "Slow/unreachable endpoints: ${failed_endpoints[*]}" "Response Time" "$response_time_ms ms" "$RESPONSE_TIME_THRESHOLD ms"
        return 1
    fi
    
    return 0
}

# Check database connectivity
check_database() {
    info "Checking database connectivity..."
    
    if ! docker exec sci-bono-lms-db mysqladmin ping -h localhost --silent; then
        error "✗ Database is not responding"
        send_alert "CRITICAL" "Database is not responding" "Database" "DOWN" "UP"
        return 1
    fi
    
    info "✓ Database is responding"
    
    # Check database performance
    local query_time=$(docker exec sci-bono-lms-db mysql -e "SELECT BENCHMARK(1000000, MD5('test'));" 2>&1 | grep -o "[0-9.]\+ sec" | head -1 | cut -d' ' -f1 || echo "999")
    if (( $(echo "$query_time > 5" | bc -l) )); then
        warning "Database performance is slow: ${query_time}s"
        send_alert "WARNING" "Database performance is degraded" "Database Performance" "${query_time}s" "5s"
    fi
    
    return 0
}

# Check Redis cache
check_redis() {
    info "Checking Redis cache..."
    
    if ! docker exec sci-bono-lms-cache redis-cli ping | grep -q "PONG"; then
        error "✗ Redis is not responding"
        send_alert "CRITICAL" "Redis cache is not responding" "Redis" "DOWN" "UP"
        return 1
    fi
    
    info "✓ Redis is responding"
    
    # Check Redis memory usage
    local redis_memory=$(docker exec sci-bono-lms-cache redis-cli info memory | grep "used_memory_human" | cut -d: -f2 | tr -d '\r')
    info "Redis memory usage: $redis_memory"
    
    return 0
}

# Check system resources
check_system_resources() {
    info "Checking system resources..."
    
    # Check CPU usage
    local cpu_usage=$(get_cpu_usage)
    if [ "$cpu_usage" -gt "$CPU_THRESHOLD" ]; then
        warning "High CPU usage: ${cpu_usage}%"
        send_alert "WARNING" "High CPU usage detected" "CPU Usage" "${cpu_usage}%" "${CPU_THRESHOLD}%"
    else
        info "✓ CPU usage: ${cpu_usage}%"
    fi
    
    # Check memory usage
    local memory_usage=$(get_memory_usage)
    if [ "$memory_usage" -gt "$MEMORY_THRESHOLD" ]; then
        warning "High memory usage: ${memory_usage}%"
        send_alert "WARNING" "High memory usage detected" "Memory Usage" "${memory_usage}%" "${MEMORY_THRESHOLD}%"
    else
        info "✓ Memory usage: ${memory_usage}%"
    fi
    
    # Check disk usage
    local disk_usage=$(get_disk_usage)
    if [ "$disk_usage" -gt "$DISK_THRESHOLD" ]; then
        warning "High disk usage: ${disk_usage}%"
        send_alert "WARNING" "High disk usage detected" "Disk Usage" "${disk_usage}%" "${DISK_THRESHOLD}%"
    else
        info "✓ Disk usage: ${disk_usage}%"
    fi
    
    # Check load average
    local load_average=$(uptime | awk -F'load average:' '{ print $2 }' | awk '{print $1}' | sed 's/,//')
    local cpu_cores=$(nproc)
    local load_percentage=$(echo "$load_average * 100 / $cpu_cores" | bc -l | cut -d. -f1)
    
    if [ "$load_percentage" -gt 100 ]; then
        warning "High system load: $load_average (${load_percentage}% of $cpu_cores cores)"
        send_alert "WARNING" "High system load detected" "Load Average" "$load_average" "$cpu_cores"
    else
        info "✓ Load average: $load_average (${load_percentage}% of $cpu_cores cores)"
    fi
}

# Check log files for errors
check_error_logs() {
    info "Checking for recent errors in logs..."
    
    local log_files=(
        "$APP_ROOT/logs/php_errors.log"
        "$APP_ROOT/logs/apache_error.log"
        "/var/log/nginx/error.log"
    )
    
    local error_count=0
    local recent_errors=()
    
    for log_file in "${log_files[@]}"; do
        if [ -f "$log_file" ]; then
            # Count errors from last hour
            local hourly_errors=$(find "$log_file" -mmin -60 -exec grep -c "ERROR\|CRITICAL\|Fatal" {} \; 2>/dev/null | head -1 || echo "0")
            if [ "$hourly_errors" -gt 10 ]; then
                warning "High error rate in $log_file: $hourly_errors errors in last hour"
                error_count=$((error_count + hourly_errors))
                recent_errors+=("$log_file: $hourly_errors errors")
            fi
        fi
    done
    
    if [ "$error_count" -gt 50 ]; then
        send_alert "WARNING" "High error rate detected in logs" "Error Rate" "$error_count/hour" "50/hour"
    elif [ ${#recent_errors[@]} -gt 0 ]; then
        info "Recent errors found: ${recent_errors[*]}"
    else
        info "✓ No significant errors found in recent logs"
    fi
}

# Check SSL certificate expiration
check_ssl_certificates() {
    info "Checking SSL certificate expiration..."
    
    local cert_file="/etc/ssl/certs/sci-bono-lms.crt"
    
    if [ -f "$cert_file" ]; then
        local expiry_date=$(openssl x509 -in "$cert_file" -noout -enddate | cut -d= -f2)
        local expiry_timestamp=$(date -d "$expiry_date" +%s)
        local current_timestamp=$(date +%s)
        local days_until_expiry=$(( (expiry_timestamp - current_timestamp) / 86400 ))
        
        if [ "$days_until_expiry" -lt 30 ]; then
            warning "SSL certificate expires in $days_until_expiry days"
            send_alert "WARNING" "SSL certificate expiring soon" "SSL Certificate" "$days_until_expiry days" "30 days"
        else
            info "✓ SSL certificate is valid for $days_until_expiry more days"
        fi
    else
        warning "SSL certificate file not found: $cert_file"
    fi
}

# Check backup status
check_backup_status() {
    info "Checking backup status..."
    
    local backup_dir="/var/backups/sci-bono-lms"
    local latest_backup=$(find "$backup_dir" -type d -name "backup-*" 2>/dev/null | sort | tail -1)
    
    if [ -n "$latest_backup" ]; then
        local backup_age_hours=$(( ($(date +%s) - $(stat -f %m "$latest_backup" 2>/dev/null || stat -c %Y "$latest_backup")) / 3600 ))
        
        if [ "$backup_age_hours" -gt 25 ]; then  # Should backup daily
            warning "Last backup is $backup_age_hours hours old"
            send_alert "WARNING" "Backup is overdue" "Backup Age" "$backup_age_hours hours" "24 hours"
        else
            info "✓ Recent backup found: $(basename "$latest_backup") ($backup_age_hours hours ago)"
        fi
    else
        error "No backups found in $backup_dir"
        send_alert "CRITICAL" "No backups found" "Backup Status" "MISSING" "PRESENT"
    fi
}

# Generate status report
generate_status_report() {
    local timestamp=$(date)
    local report_file="/tmp/sci-bono-lms-status-$(date +%Y%m%d-%H%M%S).txt"
    
    cat > "$report_file" << EOF
Sci-Bono LMS System Status Report
================================
Generated: $timestamp
Server: $(hostname)

System Resources:
- CPU Usage: $(get_cpu_usage)%
- Memory Usage: $(get_memory_usage)%
- Disk Usage: $(get_disk_usage)%
- Load Average: $(uptime | awk -F'load average:' '{ print $2 }')
- Uptime: $(uptime -p 2>/dev/null || uptime | awk '{print $3,$4}')

Docker Containers:
$(docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep sci-bono-lms || echo "No containers found")

Application Health:
$(curl -s http://localhost/health || echo "Health check endpoint not responding")

Database Status:
$(docker exec sci-bono-lms-db mysqladmin status 2>/dev/null || echo "Database not accessible")

Recent Log Summary:
$(tail -20 "$LOG_FILE" 2>/dev/null || echo "No recent logs available")
EOF

    log "Status report generated: $report_file"
    echo "$report_file"
}

# Main monitoring function
run_health_check() {
    log "Starting Sci-Bono LMS health check..."
    
    local checks_passed=0
    local checks_failed=0
    
    # Run all checks
    if check_docker_containers; then
        checks_passed=$((checks_passed + 1))
    else
        checks_failed=$((checks_failed + 1))
    fi
    
    if check_response_time; then
        checks_passed=$((checks_passed + 1))
    else
        checks_failed=$((checks_failed + 1))
    fi
    
    if check_database; then
        checks_passed=$((checks_passed + 1))
    else
        checks_failed=$((checks_failed + 1))
    fi
    
    if check_redis; then
        checks_passed=$((checks_passed + 1))
    else
        checks_failed=$((checks_failed + 1))
    fi
    
    check_system_resources  # This doesn't return failure, just warnings
    checks_passed=$((checks_passed + 1))
    
    check_error_logs  # This doesn't return failure, just warnings
    checks_passed=$((checks_passed + 1))
    
    check_ssl_certificates  # This doesn't return failure, just warnings
    checks_passed=$((checks_passed + 1))
    
    check_backup_status  # This doesn't return failure, just warnings
    checks_passed=$((checks_passed + 1))
    
    # Summary
    local total_checks=$((checks_passed + checks_failed))
    log "Health check completed: $checks_passed/$total_checks checks passed"
    
    if [ "$checks_failed" -gt 0 ]; then
        error "$checks_failed critical checks failed"
        return 1
    else
        log "✅ All critical checks passed"
        return 0
    fi
}

# Parse command line arguments
case "${1:-check}" in
    check)
        run_health_check
        ;;
    report)
        report_file=$(generate_status_report)
        cat "$report_file"
        ;;
    alerts)
        if [ -f "/var/log/sci-bono-lms-alerts.log" ]; then
            echo "Recent alerts:"
            tail -20 "/var/log/sci-bono-lms-alerts.log"
        else
            echo "No alerts logged"
        fi
        ;;
    test-alert)
        send_alert "TEST" "This is a test alert" "Test Metric" "Test Value" "Test Threshold"
        echo "Test alert sent"
        ;;
    *)
        echo "Usage: $0 {check|report|alerts|test-alert}"
        echo ""
        echo "  check      - Run health checks (default)"
        echo "  report     - Generate detailed status report"
        echo "  alerts     - Show recent alerts"
        echo "  test-alert - Send a test alert"
        exit 1
        ;;
esac