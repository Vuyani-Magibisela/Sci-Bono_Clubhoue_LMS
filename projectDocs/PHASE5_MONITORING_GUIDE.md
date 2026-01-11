# Phase 5 - Monitoring & Alerting Setup Guide

**Week 6, Day 5 - Production Monitoring Configuration**
**Date**: January 2026
**Status**: Complete
**Author**: Development Team

---

## Table of Contents

1. [Overview](#overview)
2. [Monitoring Stack](#monitoring-stack)
3. [Application Performance Monitoring](#application-performance-monitoring)
4. [Error Tracking & Logging](#error-tracking--logging)
5. [Uptime Monitoring](#uptime-monitoring)
6. [Database Monitoring](#database-monitoring)
7. [Server Monitoring](#server-monitoring)
8. [Alert Configuration](#alert-configuration)
9. [Dashboard Setup](#dashboard-setup)
10. [Log Management](#log-management)
11. [Performance Metrics](#performance-metrics)
12. [Incident Response](#incident-response)

---

## Overview

This guide provides comprehensive monitoring and alerting setup for the Sci-Bono Clubhouse LMS API (Phase 5 implementation). Proper monitoring ensures high availability, quick incident response, and proactive issue detection.

### Monitoring Goals

- **99.9% Uptime** - Maximum 43 minutes downtime per month
- **< 500ms Average Response Time** - API endpoints respond quickly
- **< 5 minutes Mean Time to Detection (MTTD)** - Issues detected quickly
- **< 30 minutes Mean Time to Resolution (MTTR)** - Issues resolved quickly
- **Zero Data Loss** - All critical transactions tracked and logged

### Monitoring Levels

1. **Application Level** - API endpoint performance, errors, business metrics
2. **Database Level** - Query performance, connection pool, deadlocks
3. **Server Level** - CPU, memory, disk, network utilization
4. **Network Level** - Request/response times, SSL certificate expiry
5. **Business Level** - User registrations, enrollments, sign-ins

---

## Monitoring Stack

### Recommended Tools

#### Open Source Stack (Budget-Friendly)

```yaml
Application Monitoring:
  - Prometheus + Grafana (metrics and dashboards)
  - ELK Stack (Elasticsearch + Logstash + Kibana) for logs
  - Jaeger or Zipkin (distributed tracing)

Uptime Monitoring:
  - UptimeRobot (free tier: 50 monitors)
  - Pingdom (basic monitoring)

Error Tracking:
  - Sentry (self-hosted or free tier)
  - Bugsnag (error tracking)

Server Monitoring:
  - Netdata (real-time server monitoring)
  - Glances (system monitoring)
```

#### Commercial Stack (Enterprise)

```yaml
Application Monitoring:
  - New Relic APM
  - Datadog APM
  - AppDynamics

Uptime Monitoring:
  - Pingdom
  - StatusCake
  - Site24x7

Error Tracking:
  - Sentry (managed)
  - Rollbar
  - Airbrake

Log Management:
  - Splunk
  - Sumo Logic
  - Loggly
```

### Our Recommended Setup (Hybrid)

For Sci-Bono LMS, we recommend:

1. **Prometheus + Grafana** - Free, powerful, industry-standard
2. **Sentry** - Free tier covers error tracking needs
3. **UptimeRobot** - Free tier for uptime monitoring
4. **ELK Stack** - Self-hosted log aggregation
5. **Netdata** - Real-time server monitoring

**Total Cost**: $0-50/month (depending on scale)

---

## Application Performance Monitoring

### PHP Application Metrics

#### 1. Install Prometheus PHP Client

```bash
cd /var/www/html/Sci-Bono_Clubhoue_LMS
composer require promphp/prometheus_client_php
```

#### 2. Create Metrics Middleware

**File**: `app/Middleware/MetricsMiddleware.php`

```php
<?php
namespace App\Middleware;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\RenderTextFormat;

class MetricsMiddleware
{
    private $registry;
    private $httpRequestDuration;
    private $httpRequestCount;
    private $httpRequestSize;
    private $httpResponseSize;

    public function __construct()
    {
        // Use Redis for metric storage
        Redis::setDefaultOptions(['host' => '127.0.0.1', 'port' => 6379]);
        $this->registry = CollectorRegistry::getDefault();

        // Define metrics
        $this->httpRequestDuration = $this->registry->getOrRegisterHistogram(
            'scibono_lms',
            'http_request_duration_seconds',
            'Duration of HTTP requests in seconds',
            ['method', 'route', 'status_code'],
            [0.01, 0.05, 0.1, 0.5, 1.0, 2.0, 5.0] // Buckets
        );

        $this->httpRequestCount = $this->registry->getOrRegisterCounter(
            'scibono_lms',
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'route', 'status_code']
        );

        $this->httpRequestSize = $this->registry->getOrRegisterHistogram(
            'scibono_lms',
            'http_request_size_bytes',
            'Size of HTTP requests in bytes',
            ['method', 'route']
        );

        $this->httpResponseSize = $this->registry->getOrRegisterHistogram(
            'scibono_lms',
            'http_response_size_bytes',
            'Size of HTTP responses in bytes',
            ['method', 'route', 'status_code']
        );
    }

    public function handle($request, $next)
    {
        $startTime = microtime(true);
        $requestSize = strlen(file_get_contents('php://input'));

        // Extract route information
        $method = $_SERVER['REQUEST_METHOD'];
        $route = $this->extractRoute($_SERVER['REQUEST_URI']);

        // Execute request
        $response = $next($request);

        // Calculate metrics
        $duration = microtime(true) - $startTime;
        $statusCode = http_response_code();
        $responseSize = ob_get_length();

        // Record metrics
        $this->httpRequestDuration->observe(
            $duration,
            [$method, $route, $statusCode]
        );

        $this->httpRequestCount->inc([$method, $route, $statusCode]);
        $this->httpRequestSize->observe($requestSize, [$method, $route]);
        $this->httpResponseSize->observe($responseSize, [$method, $route, $statusCode]);

        return $response;
    }

    private function extractRoute($uri)
    {
        // Remove base path and query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = str_replace('/Sci-Bono_Clubhoue_LMS', '', $uri);

        // Normalize IDs to {id} parameter
        $uri = preg_replace('/\/\d+/', '/{id}', $uri);

        return $uri ?: '/';
    }

    public function renderMetrics()
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
```

#### 3. Create Metrics Endpoint

**File**: `app/Controllers/Api/MetricsController.php`

```php
<?php
namespace App\Controllers\Api;

use App\API\BaseApiController;
use App\Middleware\MetricsMiddleware;

class MetricsController extends BaseApiController
{
    public function prometheus()
    {
        // Only allow access from localhost or monitoring server
        $allowedIps = ['127.0.0.1', '::1', '10.0.0.5']; // Add monitoring server IP

        if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
            http_response_code(403);
            die('Forbidden');
        }

        header('Content-Type: text/plain; version=0.0.4');

        $middleware = new MetricsMiddleware();
        echo $middleware->renderMetrics();
        exit;
    }
}
```

#### 4. Add Metrics Route

**File**: `routes/api.php` (add to public routes)

```php
$router->get('/metrics', 'Api\\MetricsController@prometheus', 'api.metrics');
```

#### 5. Configure Prometheus Scraping

**File**: `/etc/prometheus/prometheus.yml`

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'scibono-lms-api'
    static_configs:
      - targets: ['localhost:80']
    metrics_path: '/Sci-Bono_Clubhoue_LMS/api/v1/metrics'
    scrape_interval: 10s
```

### Custom Business Metrics

Track business-critical events:

```php
// User registration metric
$userRegistrations = $registry->getOrRegisterCounter(
    'scibono_lms',
    'user_registrations_total',
    'Total number of user registrations',
    ['role']
);
$userRegistrations->inc(['member']);

// Course enrollment metric
$courseEnrollments = $registry->getOrRegisterCounter(
    'scibono_lms',
    'course_enrollments_total',
    'Total number of course enrollments',
    ['course_id']
);
$courseEnrollments->inc([$courseId]);

// Holiday program registration metric
$programRegistrations = $registry->getOrRegisterCounter(
    'scibono_lms',
    'program_registrations_total',
    'Total number of holiday program registrations',
    ['program_id', 'type']
);
$programRegistrations->inc([$programId, $mentorRegistration ? 'mentor' : 'member']);

// Attendance sign-in metric
$attendanceSignins = $registry->getOrRegisterCounter(
    'scibono_lms',
    'attendance_signins_total',
    'Total number of attendance sign-ins',
    ['role']
);
$attendanceSignins->inc([$userRole]);
```

---

## Error Tracking & Logging

### 1. Install Sentry PHP SDK

```bash
cd /var/www/html/Sci-Bono_Clubhoue_LMS
composer require sentry/sdk:^3.0
```

### 2. Configure Sentry

**File**: `config/sentry.php`

```php
<?php
return [
    'dsn' => getenv('SENTRY_DSN') ?: '',
    'environment' => getenv('APP_ENV') ?: 'production',
    'release' => getenv('APP_VERSION') ?: 'unknown',
    'sample_rate' => 1.0, // 100% error sampling
    'traces_sample_rate' => 0.2, // 20% performance sampling
    'send_default_pii' => false, // Don't send PII by default
    'max_breadcrumbs' => 50,
    'attach_stacktrace' => true,
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Filter out sensitive data
        $event->setContext('user', [
            'id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            // Don't send email or other PII
        ]);

        return $event;
    },
];
```

### 3. Initialize Sentry in Bootstrap

**File**: `bootstrap.php` (add at the top)

```php
<?php
// Initialize Sentry for error tracking
if (file_exists(__DIR__ . '/config/sentry.php')) {
    $sentryConfig = require __DIR__ . '/config/sentry.php';

    if (!empty($sentryConfig['dsn'])) {
        \Sentry\init($sentryConfig);

        // Set up error handler
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            \Sentry\captureMessage("$errstr in $errfile:$errline", \Sentry\Severity::error());
            return false;
        });

        // Set up exception handler
        set_exception_handler(function ($exception) {
            \Sentry\captureException($exception);
            throw $exception;
        });
    }
}
```

### 4. Use Sentry in Controllers

```php
<?php
try {
    // Your code here
} catch (\Exception $e) {
    // Log to Sentry with additional context
    \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($e, $userId) {
        $scope->setContext('user_action', [
            'user_id' => $userId,
            'action' => 'enroll_course',
            'course_id' => $courseId,
        ]);

        \Sentry\captureException($e);
    });

    // Return error response
    $this->sendErrorResponse('Internal server error', 500);
}
```

### 5. Logging Configuration

**File**: `config/logging.php`

```php
<?php
return [
    'default' => 'daily',

    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/app.log',
            'level' => getenv('APP_ENV') === 'production' ? 'warning' : 'debug',
            'days' => 14,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/api.log',
            'level' => 'info',
            'days' => 30,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/security.log',
            'level' => 'warning',
            'days' => 90,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/performance.log',
            'level' => 'info',
            'days' => 7,
        ],
    ],
];
```

### 6. Structured Logging Helper

**File**: `app/Utils/Logger.php`

```php
<?php
namespace App\Utils;

class Logger
{
    private static $logPath = __DIR__ . '/../../storage/logs/';

    public static function api($level, $message, $context = [])
    {
        self::log('api', $level, $message, $context);
    }

    public static function security($level, $message, $context = [])
    {
        self::log('security', $level, $message, $context);
    }

    public static function performance($message, $duration, $context = [])
    {
        $context['duration_ms'] = round($duration * 1000, 2);
        self::log('performance', 'info', $message, $context);
    }

    private static function log($channel, $level, $message, $context = [])
    {
        $logFile = self::$logPath . $channel . '-' . date('Y-m-d') . '.log';

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }
}
```

**Usage**:

```php
use App\Utils\Logger;

// API request logging
Logger::api('info', 'User profile updated', [
    'user_id' => $userId,
    'fields_changed' => ['name', 'email'],
]);

// Security logging
Logger::security('warning', 'Failed login attempt', [
    'email' => $email,
    'attempts' => 3,
]);

// Performance logging
$startTime = microtime(true);
// ... expensive operation ...
$duration = microtime(true) - $startTime;
Logger::performance('Database query executed', $duration, [
    'query' => 'SELECT * FROM courses',
]);
```

---

## Uptime Monitoring

### 1. UptimeRobot Configuration

Create monitors for critical endpoints:

```yaml
Monitors:
  1. API Health Check:
     URL: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/health
     Type: HTTP(s)
     Interval: 5 minutes
     Alert When: Down for 2 checks

  2. Authentication Endpoint:
     URL: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/auth/login
     Type: HTTP(s)
     Method: POST
     Interval: 5 minutes
     Expected Status: 400 (without credentials)

  3. Public Courses Endpoint:
     URL: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/courses
     Type: HTTP(s)
     Interval: 10 minutes
     Expected Status: 200 or 401

  4. Database Connectivity:
     URL: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/health
     Type: Keyword
     Keyword: "database":"ok"
     Interval: 5 minutes

  5. SSL Certificate:
     URL: https://api.scibono.co.za
     Type: SSL Certificate
     Alert: 7 days before expiry

Alert Contacts:
  - Email: devops@scibono.co.za
  - SMS: +27 XX XXX XXXX (on-call engineer)
  - Slack: #alerts channel
```

### 2. Enhanced Health Check Endpoint

**File**: `app/Controllers/Api/HealthController.php`

```php
<?php
namespace App\Controllers\Api;

use App\API\BaseApiController;

class HealthController extends BaseApiController
{
    public function check()
    {
        $startTime = microtime(true);

        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => getenv('APP_VERSION') ?: '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'production',
            'checks' => [],
        ];

        // Database check
        try {
            $stmt = $this->db->query("SELECT 1");
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }

        // Redis check (if using Redis)
        if (class_exists('Redis')) {
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->ping();
                $health['checks']['redis'] = [
                    'status' => 'ok',
                    'message' => 'Redis connection successful',
                ];
            } catch (\Exception $e) {
                $health['checks']['redis'] = [
                    'status' => 'warning',
                    'message' => 'Redis connection failed',
                ];
            }
        }

        // Disk space check
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = (1 - $diskFree / $diskTotal) * 100;

        $health['checks']['disk'] = [
            'status' => $diskUsagePercent > 90 ? 'warning' : 'ok',
            'usage_percent' => round($diskUsagePercent, 2),
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
        ];

        // Response time
        $health['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Overall status
        foreach ($health['checks'] as $check) {
            if ($check['status'] === 'error') {
                $health['status'] = 'error';
                http_response_code(503);
                break;
            }
        }

        $this->sendSuccessResponse($health);
    }
}
```

### 3. Status Page (Optional)

**File**: `public/status.php`

```php
<?php
// Simple public status page
$apiHealthUrl = 'http://localhost/Sci-Bono_Clubhoue_LMS/api/v1/health';
$health = json_decode(file_get_contents($apiHealthUrl), true);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Status - Sci-Bono LMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .status { padding: 20px; border-radius: 5px; margin: 10px 0; }
        .ok { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
    <meta http-equiv="refresh" content="60">
</head>
<body>
    <h1>Sci-Bono LMS API Status</h1>
    <div class="status <?php echo $health['status']; ?>">
        <h2>Overall Status: <?php echo strtoupper($health['status']); ?></h2>
        <p>Last Updated: <?php echo $health['timestamp']; ?></p>
        <p>Version: <?php echo $health['version']; ?></p>
        <p>Response Time: <?php echo $health['response_time_ms']; ?>ms</p>
    </div>

    <h3>System Checks:</h3>
    <?php foreach ($health['checks'] as $name => $check): ?>
        <div class="status <?php echo $check['status']; ?>">
            <strong><?php echo ucfirst($name); ?>:</strong>
            <?php echo $check['message'] ?? $check['status']; ?>
        </div>
    <?php endforeach; ?>

    <p><small>Page auto-refreshes every 60 seconds</small></p>
</body>
</html>
```

---

## Database Monitoring

### 1. Slow Query Logging

**File**: `/etc/mysql/mysql.conf.d/mysqld.cnf`

```ini
[mysqld]
# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1
log_queries_not_using_indexes = 1
```

### 2. Database Performance Metrics

**File**: `app/Utils/DatabaseMonitor.php`

```php
<?php
namespace App\Utils;

class DatabaseMonitor
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getConnectionStats()
    {
        $stmt = $this->db->query("SHOW STATUS LIKE 'Threads_%'");
        $stats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats[$row['Variable_name']] = $row['Value'];
        }
        return $stats;
    }

    public function getQueryStats()
    {
        $stmt = $this->db->query("SHOW STATUS LIKE 'Com_%'");
        $stats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (in_array($row['Variable_name'], ['Com_select', 'Com_insert', 'Com_update', 'Com_delete'])) {
                $stats[$row['Variable_name']] = $row['Value'];
            }
        }
        return $stats;
    }

    public function getSlowQueries()
    {
        $stmt = $this->db->query("SHOW STATUS LIKE 'Slow_queries'");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)$row['Value'];
    }

    public function getTableSizes()
    {
        $sql = "SELECT
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([getenv('DB_NAME')]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

### 3. Database Metrics Endpoint

Add to `MetricsController.php`:

```php
public function databaseMetrics()
{
    $monitor = new \App\Utils\DatabaseMonitor($this->db);

    $metrics = [
        'connections' => $monitor->getConnectionStats(),
        'queries' => $monitor->getQueryStats(),
        'slow_queries' => $monitor->getSlowQueries(),
        'table_sizes' => $monitor->getTableSizes(),
    ];

    $this->sendSuccessResponse($metrics);
}
```

---

## Server Monitoring

### 1. Install Netdata

```bash
# Install Netdata for real-time server monitoring
bash <(curl -Ss https://my-netdata.io/kickstart.sh) --stable-channel --disable-telemetry

# Configure firewall
sudo ufw allow 19999/tcp comment 'Netdata monitoring'

# Secure with basic auth
sudo apt install apache2-utils
sudo htpasswd -c /etc/netdata/htpasswd admin

# Edit Netdata config
sudo nano /etc/netdata/netdata.conf
```

**File**: `/etc/netdata/netdata.conf`

```ini
[web]
    bind to = 127.0.0.1
    allow connections from = localhost 10.0.0.*
```

### 2. Configure Reverse Proxy for Netdata

**Apache VirtualHost**:

```apache
<VirtualHost *:443>
    ServerName monitoring.scibono.co.za

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/monitoring.crt
    SSLCertificateKeyFile /etc/ssl/private/monitoring.key

    <Location />
        AuthType Basic
        AuthName "Monitoring Dashboard"
        AuthUserFile /etc/netdata/htpasswd
        Require valid-user

        ProxyPass http://127.0.0.1:19999/
        ProxyPassReverse http://127.0.0.1:19999/
    </Location>
</VirtualHost>
```

### 3. System Resource Alerts

Create monitoring script:

**File**: `/usr/local/bin/system-monitor.sh`

```bash
#!/bin/bash

# System monitoring script - runs every 5 minutes via cron

ALERT_EMAIL="devops@scibono.co.za"
HOSTNAME=$(hostname)

# CPU threshold (percentage)
CPU_THRESHOLD=80

# Memory threshold (percentage)
MEM_THRESHOLD=85

# Disk threshold (percentage)
DISK_THRESHOLD=90

# Get current metrics
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1 | cut -d'.' -f1)
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}' | cut -d'%' -f1)

# Check CPU
if [ "$CPU_USAGE" -gt "$CPU_THRESHOLD" ]; then
    echo "ALERT: High CPU usage on $HOSTNAME: ${CPU_USAGE}%" | \
        mail -s "CPU Alert - $HOSTNAME" "$ALERT_EMAIL"
fi

# Check Memory
if [ "$MEM_USAGE" -gt "$MEM_THRESHOLD" ]; then
    echo "ALERT: High memory usage on $HOSTNAME: ${MEM_USAGE}%" | \
        mail -s "Memory Alert - $HOSTNAME" "$ALERT_EMAIL"
fi

# Check Disk
if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    echo "ALERT: High disk usage on $HOSTNAME: ${DISK_USAGE}%" | \
        mail -s "Disk Alert - $HOSTNAME" "$ALERT_EMAIL"
fi
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/system-monitor.sh

# Add to crontab
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/system-monitor.sh") | crontab -
```

---

## Alert Configuration

### 1. Alert Rules in Prometheus

**File**: `/etc/prometheus/alerts.yml`

```yaml
groups:
  - name: api_alerts
    interval: 30s
    rules:
      # High error rate
      - alert: HighErrorRate
        expr: rate(scibono_lms_http_requests_total{status_code=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "API error rate is {{ $value }} errors/sec"

      # Slow response time
      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, scibono_lms_http_request_duration_seconds_bucket) > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Slow API response time"
          description: "95th percentile response time is {{ $value }}s"

      # High request rate
      - alert: HighRequestRate
        expr: rate(scibono_lms_http_requests_total[5m]) > 100
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "High request rate detected"
          description: "API receiving {{ $value }} requests/sec"

      # Database connection errors
      - alert: DatabaseConnectionError
        expr: up{job="mysql"} == 0
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "Database connection lost"
          description: "Cannot connect to MySQL database"

  - name: system_alerts
    interval: 1m
    rules:
      # High CPU usage
      - alert: HighCpuUsage
        expr: 100 - (avg by (instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "High CPU usage"
          description: "CPU usage is {{ $value }}%"

      # High memory usage
      - alert: HighMemoryUsage
        expr: (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100 > 85
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "High memory usage"
          description: "Memory usage is {{ $value }}%"

      # Disk space low
      - alert: DiskSpaceLow
        expr: (1 - (node_filesystem_avail_bytes / node_filesystem_size_bytes)) * 100 > 90
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Disk space low"
          description: "Disk usage is {{ $value }}%"
```

### 2. Alertmanager Configuration

**File**: `/etc/prometheus/alertmanager.yml`

```yaml
global:
  resolve_timeout: 5m
  smtp_smarthost: 'smtp.gmail.com:587'
  smtp_from: 'alerts@scibono.co.za'
  smtp_auth_username: 'alerts@scibono.co.za'
  smtp_auth_password: 'your-app-password'

route:
  group_by: ['alertname', 'severity']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 12h
  receiver: 'team-email'

  routes:
    - match:
        severity: critical
      receiver: 'team-email-sms'
      continue: true

    - match:
        severity: warning
      receiver: 'team-email'

receivers:
  - name: 'team-email'
    email_configs:
      - to: 'devops@scibono.co.za'
        headers:
          Subject: '[ALERT] {{ .GroupLabels.alertname }}'

  - name: 'team-email-sms'
    email_configs:
      - to: 'devops@scibono.co.za'
        headers:
          Subject: '[CRITICAL] {{ .GroupLabels.alertname }}'
    webhook_configs:
      - url: 'https://sms-gateway.example.com/send'
        send_resolved: true

inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname']
```

### 3. Slack Integration (Optional)

```yaml
receivers:
  - name: 'slack'
    slack_configs:
      - api_url: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
        channel: '#alerts'
        title: '{{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'
```

---

## Dashboard Setup

### 1. Grafana Installation

```bash
# Install Grafana
sudo apt-get install -y software-properties-common
sudo add-apt-repository "deb https://packages.grafana.com/oss/deb stable main"
wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -
sudo apt-get update
sudo apt-get install grafana

# Start Grafana
sudo systemctl daemon-reload
sudo systemctl start grafana-server
sudo systemctl enable grafana-server

# Access Grafana at http://localhost:3000
# Default credentials: admin / admin
```

### 2. Add Prometheus Data Source

1. Navigate to Configuration > Data Sources
2. Add Prometheus data source
3. URL: `http://localhost:9090`
4. Save & Test

### 3. Import Pre-Built Dashboards

Import these Grafana dashboard IDs:

```yaml
Recommended Dashboards:
  - 3662: Prometheus 2.0 Overview
  - 1860: Node Exporter Full
  - 6126: PHP-FPM
  - 7362: MySQL Overview
  - 7991: Web Server Statistics
```

### 4. Custom API Dashboard

Create custom dashboard with these panels:

```yaml
Dashboard: "Sci-Bono LMS API Overview"

Panels:
  1. Request Rate:
     Query: rate(scibono_lms_http_requests_total[5m])
     Type: Graph

  2. Error Rate:
     Query: rate(scibono_lms_http_requests_total{status_code=~"5.."}[5m])
     Type: Graph

  3. Response Time (95th percentile):
     Query: histogram_quantile(0.95, scibono_lms_http_request_duration_seconds_bucket)
     Type: Graph

  4. Request Count by Endpoint:
     Query: sum by (route) (rate(scibono_lms_http_requests_total[5m]))
     Type: Bar Chart

  5. Status Code Distribution:
     Query: sum by (status_code) (rate(scibono_lms_http_requests_total[5m]))
     Type: Pie Chart

  6. Active Users:
     Query: count(count by (user_id) (scibono_lms_http_requests_total))
     Type: Stat

  7. User Registrations:
     Query: rate(scibono_lms_user_registrations_total[1h])
     Type: Graph

  8. Course Enrollments:
     Query: rate(scibono_lms_course_enrollments_total[1h])
     Type: Graph
```

### 5. Business Metrics Dashboard

```yaml
Dashboard: "Sci-Bono LMS Business Metrics"

Panels:
  1. Total Users:
     SQL Query: SELECT COUNT(*) FROM users

  2. Daily Active Users:
     SQL Query: SELECT COUNT(DISTINCT user_id) FROM api_request_logs WHERE created_at >= CURDATE()

  3. Course Enrollment Trend:
     Query: rate(scibono_lms_course_enrollments_total[1d])

  4. Holiday Program Registrations:
     Query: rate(scibono_lms_program_registrations_total[1d])

  5. Attendance Sign-ins Today:
     Query: increase(scibono_lms_attendance_signins_total[1d])

  6. Top 10 Popular Courses:
     SQL Query: SELECT c.title, COUNT(*) as enrollments
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                GROUP BY c.id
                ORDER BY enrollments DESC
                LIMIT 10
```

---

## Log Management

### 1. ELK Stack Installation

```bash
# Install Elasticsearch
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
sudo apt-get install apt-transport-https
echo "deb https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list
sudo apt-get update && sudo apt-get install elasticsearch

# Start Elasticsearch
sudo systemctl daemon-reload
sudo systemctl enable elasticsearch.service
sudo systemctl start elasticsearch.service

# Install Logstash
sudo apt-get install logstash

# Install Kibana
sudo apt-get install kibana
sudo systemctl enable kibana.service
sudo systemctl start kibana.service
```

### 2. Logstash Configuration

**File**: `/etc/logstash/conf.d/scibono-lms.conf`

```ruby
input {
  file {
    path => "/var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-*.log"
    start_position => "beginning"
    codec => "json"
    type => "api"
  }

  file {
    path => "/var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/security-*.log"
    start_position => "beginning"
    codec => "json"
    type => "security"
  }

  file {
    path => "/var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/performance-*.log"
    start_position => "beginning"
    codec => "json"
    type => "performance"
  }
}

filter {
  if [type] == "api" {
    mutate {
      add_field => { "index_name" => "scibono-api-%{+YYYY.MM.dd}" }
    }
  }

  if [type] == "security" {
    mutate {
      add_field => { "index_name" => "scibono-security-%{+YYYY.MM.dd}" }
    }
  }

  if [type] == "performance" {
    mutate {
      add_field => { "index_name" => "scibono-performance-%{+YYYY.MM.dd}" }
    }
  }

  # Parse timestamp
  date {
    match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    target => "@timestamp"
  }
}

output {
  elasticsearch {
    hosts => ["localhost:9200"]
    index => "%{index_name}"
  }

  # Optional: Output to stdout for debugging
  # stdout { codec => rubydebug }
}
```

### 3. Log Rotation

**File**: `/etc/logrotate.d/scibono-lms`

```
/var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        # Reload PHP-FPM to release log file handles
        systemctl reload php8.1-fpm > /dev/null 2>&1 || true
    endscript
}
```

### 4. Kibana Dashboard

Create visualizations in Kibana:

```yaml
Kibana Index Patterns:
  - scibono-api-*
  - scibono-security-*
  - scibono-performance-*

Visualizations:
  1. API Request Volume:
     Type: Line chart
     Y-axis: Count
     X-axis: @timestamp

  2. Error Rate by Endpoint:
     Type: Bar chart
     Y-axis: Count
     X-axis: context.route
     Filter: level = "ERROR"

  3. Top Users by Request Count:
     Type: Pie chart
     Slice by: user_id

  4. Security Events:
     Type: Data table
     Columns: timestamp, level, message, context.email, ip
     Filter: type = "security"

  5. Slow Queries:
     Type: Data table
     Columns: timestamp, message, context.duration_ms, context.query
     Filter: type = "performance" AND context.duration_ms > 1000
```

---

## Performance Metrics

### Key Performance Indicators (KPIs)

Track these metrics continuously:

```yaml
Technical KPIs:
  API Performance:
    - Average Response Time: < 500ms (target: < 200ms)
    - 95th Percentile Response Time: < 1000ms
    - 99th Percentile Response Time: < 2000ms
    - Error Rate: < 0.1%
    - Uptime: > 99.9%

  Database Performance:
    - Query Response Time: < 100ms average
    - Slow Queries: < 10 per hour
    - Connection Pool Usage: < 80%
    - Deadlocks: 0 per day

  Server Resources:
    - CPU Usage: < 70% average
    - Memory Usage: < 80%
    - Disk Usage: < 85%
    - Network Latency: < 50ms

Business KPIs:
  User Engagement:
    - Daily Active Users (DAU)
    - Weekly Active Users (WAU)
    - Monthly Active Users (MAU)
    - User Retention Rate

  Educational Metrics:
    - Course Enrollments per Day
    - Lesson Completion Rate
    - Average Course Progress
    - Holiday Program Registration Rate

  System Usage:
    - API Requests per Minute
    - Attendance Sign-ins per Day
    - Search Queries per Hour
    - Failed Login Attempts
```

### Performance Benchmarks

Run monthly performance benchmarks:

**File**: `tests/PerformanceBenchmark.php`

```php
<?php
// See existing file at tests/PerformanceBenchmark.php
// Run monthly: php tests/PerformanceBenchmark.php

// Target benchmarks:
$benchmarks = [
    'GET /api/v1/health' => 50,          // < 50ms
    'POST /api/v1/auth/login' => 200,     // < 200ms
    'GET /api/v1/courses' => 300,         // < 300ms
    'GET /api/v1/search?q=test' => 500,   // < 500ms
    'POST /api/v1/courses/1/enroll' => 400, // < 400ms
];
```

---

## Incident Response

### 1. On-Call Rotation

```yaml
On-Call Schedule:
  Week 1: Primary Engineer A, Backup Engineer B
  Week 2: Primary Engineer B, Backup Engineer C
  Week 3: Primary Engineer C, Backup Engineer A

On-Call Responsibilities:
  - Monitor alerts 24/7
  - Respond to critical alerts within 15 minutes
  - Respond to high-priority alerts within 1 hour
  - Document all incidents in incident log
  - Escalate to backup engineer if needed
```

### 2. Incident Response Playbook

**Critical Alert: API Down**

```yaml
Symptoms:
  - Health check endpoint returning 5xx errors
  - Uptime monitor shows "down" status
  - Multiple user reports of inaccessibility

Response Steps:
  1. Acknowledge Alert (within 5 minutes)
     - Check monitoring dashboards
     - Verify issue is real (not false alarm)

  2. Initial Assessment (5-10 minutes)
     - Check server status: sudo systemctl status apache2
     - Check error logs: tail -100 /var/log/apache2/error.log
     - Check database: mysql -u root -p -e "SELECT 1"

  3. Quick Fixes (10-15 minutes)
     - Restart web server: sudo systemctl restart apache2
     - Restart PHP-FPM: sudo systemctl restart php8.1-fpm
     - Clear cache: php artisan cache:clear
     - Check disk space: df -h

  4. Database Issues
     - Check MySQL status: sudo systemctl status mysql
     - Check connections: SHOW PROCESSLIST;
     - Kill long-running queries if needed

  5. Escalation
     - If not resolved in 30 minutes, escalate to backup engineer
     - If not resolved in 1 hour, escalate to DevOps lead

  6. Communication
     - Update status page
     - Notify stakeholders via email/Slack
     - Post-incident: Write incident report
```

**High Error Rate Alert**

```yaml
Symptoms:
  - Prometheus alert: HighErrorRate
  - Error rate > 5% for 5+ minutes
  - Sentry showing spike in errors

Response Steps:
  1. Identify Error Pattern
     - Check Sentry for common error types
     - Check logs: tail -100 storage/logs/api-*.log
     - Identify affected endpoints

  2. Assess Impact
     - Which endpoints are affected?
     - How many users impacted?
     - Is this critical functionality?

  3. Quick Mitigation
     - If specific endpoint: disable endpoint temporarily
     - If database issue: optimize/kill problematic queries
     - If external service: implement circuit breaker

  4. Root Cause Analysis
     - Recent deployments? (rollback if needed)
     - Database schema changes?
     - Third-party service outage?

  5. Resolution
     - Apply fix
     - Test thoroughly
     - Monitor for 30 minutes post-fix
     - Document in incident log
```

### 3. Incident Log Template

**File**: `docs/incidents/INCIDENT-YYYY-MM-DD-###.md`

```markdown
# Incident Report: [Brief Title]

**Incident ID**: INC-2026-01-15-001
**Date**: 2026-01-15
**Time**: 14:30 - 15:45 UTC
**Severity**: Critical / High / Medium / Low
**Status**: Resolved

## Summary

Brief description of what happened.

## Impact

- **Users Affected**: ~500 users
- **Services Affected**: Course enrollment, lesson viewing
- **Revenue Impact**: None (free service)
- **Data Loss**: None

## Timeline

- 14:30 - Alert triggered: HighErrorRate
- 14:32 - On-call engineer acknowledged
- 14:35 - Initial investigation started
- 14:40 - Root cause identified: database connection pool exhausted
- 14:45 - Mitigation applied: increased max_connections
- 15:00 - Services restored
- 15:45 - Monitoring confirmed stable

## Root Cause

Database connection pool exhausted due to long-running queries from new search feature. Connection timeout set too high (30s), causing connections to pile up during high traffic.

## Resolution

1. Killed long-running queries
2. Reduced connection timeout from 30s to 5s
3. Increased max_connections from 100 to 200
4. Added connection pool monitoring alert

## Prevention

- [ ] Optimize search queries (use indexes)
- [ ] Implement query timeout at application level
- [ ] Add connection pool metrics to Grafana
- [ ] Create alert for connection pool usage > 80%

## Lessons Learned

1. Search feature needs query optimization before release
2. Connection pool monitoring is critical
3. Need better load testing for new features
```

---

## Quick Reference

### Monitoring URLs

```yaml
Production:
  - API: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1
  - Health Check: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/health
  - Metrics: https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/metrics
  - Status Page: https://status.scibono.co.za

Monitoring Tools:
  - Grafana: https://monitoring.scibono.co.za:3000
  - Prometheus: http://localhost:9090 (internal only)
  - Kibana: http://localhost:5601 (internal only)
  - Netdata: https://monitoring.scibono.co.za (behind auth)
  - Sentry: https://sentry.io/organizations/scibono/
  - UptimeRobot: https://uptimerobot.com/dashboard
```

### Important Commands

```bash
# Check service status
sudo systemctl status apache2
sudo systemctl status mysql
sudo systemctl status php8.1-fpm
sudo systemctl status redis-server

# View real-time logs
tail -f /var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/api-$(date +%Y-%m-%d).log
tail -f /var/log/apache2/error.log
tail -f /var/log/mysql/slow-query.log

# Check resource usage
htop
df -h
free -h
netstat -tulpn | grep :80

# Database queries
mysql -u root -p
> SHOW PROCESSLIST;
> SHOW STATUS LIKE 'Threads_%';
> SELECT * FROM api_request_logs ORDER BY created_at DESC LIMIT 10;

# Clear cache
redis-cli FLUSHALL
php artisan cache:clear (if using Laravel-style cache)

# Test API health
curl https://api.scibono.co.za/Sci-Bono_Clubhoue_LMS/api/v1/health
```

### Alert Severity Levels

```yaml
Critical (P1):
  - API completely down
  - Database offline
  - Data loss detected
  - Security breach
  Response Time: 15 minutes

High (P2):
  - High error rate (>5%)
  - Slow response time (>2s p95)
  - Disk space >90%
  - SSL certificate expiring <7 days
  Response Time: 1 hour

Medium (P3):
  - Elevated error rate (1-5%)
  - Moderate performance degradation
  - Memory usage >85%
  Response Time: 4 hours (business hours)

Low (P4):
  - Minor performance issues
  - Non-critical warnings
  Response Time: Next business day
```

---

## Completion Checklist

### Monitoring Setup

- [ ] Prometheus + Grafana installed and configured
- [ ] Metrics middleware added to API
- [ ] Custom dashboards created
- [ ] Sentry error tracking configured
- [ ] UptimeRobot monitors configured (5 endpoints)
- [ ] Netdata server monitoring installed
- [ ] ELK stack configured for log aggregation
- [ ] Alert rules configured in Prometheus
- [ ] Alertmanager configured with email/SMS
- [ ] Slack integration configured (optional)
- [ ] Status page deployed
- [ ] Database monitoring enabled
- [ ] Log rotation configured
- [ ] Performance benchmarks documented
- [ ] Incident response playbook created
- [ ] On-call rotation established
- [ ] Documentation complete

### Testing

- [ ] Health check endpoint tested
- [ ] Metrics endpoint tested and secured
- [ ] Prometheus scraping verified
- [ ] Grafana dashboards display data
- [ ] Sentry receives test errors
- [ ] Alerts trigger correctly
- [ ] Email/SMS notifications working
- [ ] Log aggregation working
- [ ] All 54 API endpoints monitored

### Production Readiness

- [ ] All critical alerts configured
- [ ] On-call engineer trained
- [ ] Incident response procedures documented
- [ ] Runbooks created for common issues
- [ ] Monitoring dashboard shared with team
- [ ] Performance baseline established
- [ ] SLA targets defined
- [ ] Monitoring reviewed with stakeholders

---

## Summary

This monitoring setup provides:

✅ **Comprehensive Coverage** - Application, database, server, and business metrics
✅ **Real-Time Alerting** - Critical issues detected within minutes
✅ **Historical Analysis** - 30+ days of logs and metrics for trend analysis
✅ **Performance Tracking** - Response times, error rates, resource usage
✅ **Business Insights** - User engagement, enrollments, attendance tracking
✅ **Incident Response** - Clear procedures and escalation paths
✅ **Cost-Effective** - Primarily open-source tools with minimal licensing costs
✅ **Scalable** - Can grow with the platform's needs

**Estimated Setup Time**: 2-3 days
**Monthly Cost**: $0-50 (using open-source stack)
**Maintenance**: 2-4 hours per week

**Week 6, Day 5: Complete** ✅
