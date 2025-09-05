<?php

namespace App\Services;

require_once __DIR__ . '/../Utils/Logger.php';

use App\Utils\Logger;
use Exception;

/**
 * Performance Monitoring Service
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * Comprehensive performance monitoring with metrics collection,
 * real-time tracking, and alerting capabilities
 */
class PerformanceMonitor
{
    private static $instance = null;
    private $db;
    private $startTime;
    private $metrics = [];
    private $config = [];
    private $isEnabled = true;
    
    // Metric types
    const METRIC_API_REQUEST = 'api_request';
    const METRIC_DATABASE_QUERY = 'database_query';
    const METRIC_MEMORY_USAGE = 'memory_usage';
    const METRIC_ERROR_RATE = 'error_rate';
    const METRIC_CUSTOM = 'custom';
    
    // Alert levels
    const ALERT_INFO = 'info';
    const ALERT_WARNING = 'warning';
    const ALERT_CRITICAL = 'critical';
    
    private function __construct($db = null)
    {
        $this->db = $db;
        $this->startTime = microtime(true);
        $this->loadConfiguration();
        $this->initializeDatabase();
        $this->startSystemMonitoring();
        
        // Register shutdown handler for cleanup
        register_shutdown_function([$this, 'onShutdown']);
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($db = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }
    
    /**
     * Load monitoring configuration
     */
    private function loadConfiguration()
    {
        $this->config = [
            'enabled' => getenv('PERFORMANCE_MONITORING') !== 'false',
            'sample_rate' => (float)(getenv('PERFORMANCE_SAMPLE_RATE') ?: 1.0),
            'retention_days' => (int)(getenv('PERFORMANCE_RETENTION_DAYS') ?: 30),
            'thresholds' => [
                'response_time_warning' => (float)(getenv('RESPONSE_TIME_WARNING') ?: 1.0),
                'response_time_critical' => (float)(getenv('RESPONSE_TIME_CRITICAL') ?: 3.0),
                'memory_usage_warning' => (int)(getenv('MEMORY_WARNING_MB') ?: 100),
                'memory_usage_critical' => (int)(getenv('MEMORY_CRITICAL_MB') ?: 200),
                'error_rate_warning' => (float)(getenv('ERROR_RATE_WARNING') ?: 0.05),
                'error_rate_critical' => (float)(getenv('ERROR_RATE_CRITICAL') ?: 0.10),
                'db_query_slow' => (float)(getenv('DB_QUERY_SLOW_SECONDS') ?: 0.5)
            ],
            'alerts' => [
                'enabled' => getenv('PERFORMANCE_ALERTS') !== 'false',
                'email' => getenv('ALERT_EMAIL') ?: '',
                'webhook_url' => getenv('ALERT_WEBHOOK_URL') ?: ''
            ]
        ];
        
        $this->isEnabled = $this->config['enabled'];
    }
    
    /**
     * Initialize database tables for metrics
     */
    private function initializeDatabase()
    {
        if (!$this->db || !$this->isEnabled) {
            return;
        }
        
        try {
            // Create performance_metrics table
            $sql = "CREATE TABLE IF NOT EXISTS performance_metrics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                metric_type VARCHAR(50) NOT NULL,
                metric_name VARCHAR(100) NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                unit VARCHAR(20) DEFAULT 'ms',
                context JSON,
                endpoint VARCHAR(500),
                user_id INT,
                session_id VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_metric_type (metric_type),
                INDEX idx_metric_name (metric_name),
                INDEX idx_timestamp (timestamp),
                INDEX idx_endpoint (endpoint),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB";
            
            $this->db->query($sql);
            
            // Create performance_alerts table
            $sql = "CREATE TABLE IF NOT EXISTS performance_alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                alert_type VARCHAR(20) NOT NULL,
                alert_level VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                context JSON,
                threshold_value DECIMAL(15,6),
                actual_value DECIMAL(15,6),
                is_resolved BOOLEAN DEFAULT FALSE,
                resolved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_alert_type (alert_type),
                INDEX idx_alert_level (alert_level),
                INDEX idx_is_resolved (is_resolved),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB";
            
            $this->db->query($sql);
            
            // Create performance_summary table for aggregated metrics
            $sql = "CREATE TABLE IF NOT EXISTS performance_summary (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                hour TINYINT NOT NULL,
                metric_type VARCHAR(50) NOT NULL,
                metric_name VARCHAR(100) NOT NULL,
                count_total INT DEFAULT 0,
                value_min DECIMAL(15,6),
                value_max DECIMAL(15,6),
                value_avg DECIMAL(15,6),
                value_p50 DECIMAL(15,6),
                value_p95 DECIMAL(15,6),
                value_p99 DECIMAL(15,6),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_summary (date, hour, metric_type, metric_name),
                INDEX idx_date_hour (date, hour),
                INDEX idx_metric_type (metric_type),
                INDEX idx_metric_name (metric_name)
            ) ENGINE=InnoDB";
            
            $this->db->query($sql);
            
        } catch (Exception $e) {
            Logger::error('Performance monitoring database initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->isEnabled = false;
        }
    }
    
    /**
     * Start system-level monitoring
     */
    private function startSystemMonitoring()
    {
        if (!$this->isEnabled) {
            return;
        }
        
        // Monitor initial memory usage
        $this->recordMetric(self::METRIC_MEMORY_USAGE, 'initial_memory', memory_get_usage(true), 'bytes');
        
        // Set up periodic monitoring (if running in long-lived process)
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGALRM, [$this, 'onPeriodicCheck']);
            pcntl_alarm(60); // Check every minute
        }
    }
    
    /**
     * Record a performance metric
     */
    public function recordMetric($type, $name, $value, $unit = 'ms', $context = [])
    {
        if (!$this->isEnabled || !$this->shouldSample()) {
            return;
        }
        
        $metric = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'unit' => $unit,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
        
        // Add request context if available
        if (isset($_SERVER['REQUEST_URI'])) {
            $metric['endpoint'] = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $metric['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $metric['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Store metric in memory for batch processing
        $this->metrics[] = $metric;
        
        // Check thresholds and trigger alerts
        $this->checkThresholds($type, $name, $value, $context);
        
        // Store to database if batch size reached
        if (count($this->metrics) >= 10) {
            $this->flushMetrics();
        }
    }
    
    /**
     * Start timing a specific operation
     */
    public function startTimer($name, $context = [])
    {
        if (!$this->isEnabled) {
            return null;
        }
        
        $timerId = uniqid($name . '_', true);
        $this->metrics['timers'][$timerId] = [
            'name' => $name,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context
        ];
        
        return $timerId;
    }
    
    /**
     * Stop timing and record metric
     */
    public function stopTimer($timerId, $additionalContext = [])
    {
        if (!$this->isEnabled || !isset($this->metrics['timers'][$timerId])) {
            return null;
        }
        
        $timer = $this->metrics['timers'][$timerId];
        $duration = (microtime(true) - $timer['start_time']) * 1000; // Convert to ms
        $memoryUsed = memory_get_usage(true) - $timer['start_memory'];
        
        $context = array_merge($timer['context'], $additionalContext, [
            'memory_used' => $memoryUsed
        ]);
        
        $this->recordMetric(self::METRIC_CUSTOM, $timer['name'], $duration, 'ms', $context);
        
        unset($this->metrics['timers'][$timerId]);
        
        return $duration;
    }
    
    /**
     * Monitor API request performance
     */
    public function monitorApiRequest($endpoint, $method, $responseCode, $startTime = null)
    {
        if (!$this->isEnabled) {
            return;
        }
        
        $endTime = microtime(true);
        $startTime = $startTime ?: $this->startTime;
        $duration = ($endTime - $startTime) * 1000;
        
        $context = [
            'method' => $method,
            'response_code' => $responseCode,
            'endpoint' => $endpoint
        ];
        
        $this->recordMetric(self::METRIC_API_REQUEST, 'response_time', $duration, 'ms', $context);
        
        // Record response code metrics
        $this->recordMetric(self::METRIC_API_REQUEST, 'response_code_' . $responseCode, 1, 'count', $context);
        
        // Track error rates
        if ($responseCode >= 400) {
            $this->recordMetric(self::METRIC_ERROR_RATE, 'api_error', 1, 'count', $context);
        } else {
            $this->recordMetric(self::METRIC_ERROR_RATE, 'api_success', 1, 'count', $context);
        }
    }
    
    /**
     * Monitor database query performance
     */
    public function monitorDatabaseQuery($query, $duration, $context = [])
    {
        if (!$this->isEnabled) {
            return;
        }
        
        $queryType = $this->getQueryType($query);
        $context = array_merge($context, [
            'query_type' => $queryType,
            'query_hash' => md5($query)
        ]);
        
        $this->recordMetric(self::METRIC_DATABASE_QUERY, 'execution_time', $duration, 'ms', $context);
        
        // Record slow queries
        if ($duration > $this->config['thresholds']['db_query_slow'] * 1000) {
            $this->recordMetric(self::METRIC_DATABASE_QUERY, 'slow_query', $duration, 'ms', array_merge($context, [
                'query_preview' => substr($query, 0, 200)
            ]));
        }
        
        // Count queries by type
        $this->recordMetric(self::METRIC_DATABASE_QUERY, $queryType . '_count', 1, 'count', $context);
    }
    
    /**
     * Monitor memory usage
     */
    public function monitorMemoryUsage()
    {
        if (!$this->isEnabled) {
            return;
        }
        
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $this->recordMetric(self::METRIC_MEMORY_USAGE, 'current', $memoryUsage, 'bytes');
        $this->recordMetric(self::METRIC_MEMORY_USAGE, 'peak', $memoryPeak, 'bytes');
        
        if ($memoryLimit > 0) {
            $usagePercent = ($memoryUsage / $memoryLimit) * 100;
            $this->recordMetric(self::METRIC_MEMORY_USAGE, 'usage_percent', $usagePercent, 'percent');
        }
    }
    
    /**
     * Record custom metric
     */
    public function recordCustomMetric($name, $value, $unit = 'count', $context = [])
    {
        $this->recordMetric(self::METRIC_CUSTOM, $name, $value, $unit, $context);
    }
    
    /**
     * Get performance metrics for dashboard
     */
    public function getMetrics($timeRange = '1h', $metricTypes = null)
    {
        if (!$this->db) {
            return [];
        }
        
        $whereClause = "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$timeRange})";
        
        if ($metricTypes) {
            $types = is_array($metricTypes) ? $metricTypes : [$metricTypes];
            $typesList = "'" . implode("','", $types) . "'";
            $whereClause .= " AND metric_type IN ($typesList)";
        }
        
        $sql = "
            SELECT 
                metric_type,
                metric_name,
                COUNT(*) as count,
                MIN(value) as min_value,
                MAX(value) as max_value,
                AVG(value) as avg_value,
                unit
            FROM performance_metrics 
            {$whereClause}
            GROUP BY metric_type, metric_name, unit
            ORDER BY metric_type, avg_value DESC
        ";
        
        $result = $this->db->query($sql);
        $metrics = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $metrics[] = $row;
            }
        }
        
        return $metrics;
    }
    
    /**
     * Get performance alerts
     */
    public function getAlerts($resolved = false, $limit = 50)
    {
        if (!$this->db) {
            return [];
        }
        
        $whereClause = $resolved ? "WHERE is_resolved = TRUE" : "WHERE is_resolved = FALSE";
        
        $sql = "
            SELECT * FROM performance_alerts 
            {$whereClause}
            ORDER BY created_at DESC 
            LIMIT {$limit}
        ";
        
        $result = $this->db->query($sql);
        $alerts = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $alerts[] = $row;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get performance summary for dashboard
     */
    public function getPerformanceSummary($timeRange = '24h')
    {
        if (!$this->db) {
            return [];
        }
        
        // Get API performance summary
        $apiMetrics = $this->getMetrics($timeRange, self::METRIC_API_REQUEST);
        
        // Get error rates
        $errorRate = $this->calculateErrorRate($timeRange);
        
        // Get memory usage trend
        $memoryTrend = $this->getMemoryTrend($timeRange);
        
        // Get slow queries
        $slowQueries = $this->getSlowQueries($timeRange);
        
        return [
            'api_performance' => $apiMetrics,
            'error_rate' => $errorRate,
            'memory_trend' => $memoryTrend,
            'slow_queries' => $slowQueries,
            'alert_count' => count($this->getAlerts(false)),
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Check performance thresholds and trigger alerts
     */
    private function checkThresholds($type, $name, $value, $context)
    {
        if (!$this->config['alerts']['enabled']) {
            return;
        }
        
        $alerts = [];
        
        // API response time alerts
        if ($type === self::METRIC_API_REQUEST && $name === 'response_time') {
            if ($value > $this->config['thresholds']['response_time_critical'] * 1000) {
                $alerts[] = [
                    'level' => self::ALERT_CRITICAL,
                    'message' => "Critical: API response time {$value}ms exceeds threshold",
                    'threshold' => $this->config['thresholds']['response_time_critical'] * 1000,
                    'actual' => $value
                ];
            } elseif ($value > $this->config['thresholds']['response_time_warning'] * 1000) {
                $alerts[] = [
                    'level' => self::ALERT_WARNING,
                    'message' => "Warning: API response time {$value}ms exceeds threshold",
                    'threshold' => $this->config['thresholds']['response_time_warning'] * 1000,
                    'actual' => $value
                ];
            }
        }
        
        // Memory usage alerts
        if ($type === self::METRIC_MEMORY_USAGE && $name === 'current') {
            $valueMB = $value / (1024 * 1024);
            if ($valueMB > $this->config['thresholds']['memory_usage_critical']) {
                $alerts[] = [
                    'level' => self::ALERT_CRITICAL,
                    'message' => "Critical: Memory usage {$valueMB}MB exceeds threshold",
                    'threshold' => $this->config['thresholds']['memory_usage_critical'],
                    'actual' => $valueMB
                ];
            } elseif ($valueMB > $this->config['thresholds']['memory_usage_warning']) {
                $alerts[] = [
                    'level' => self::ALERT_WARNING,
                    'message' => "Warning: Memory usage {$valueMB}MB exceeds threshold",
                    'threshold' => $this->config['thresholds']['memory_usage_warning'],
                    'actual' => $valueMB
                ];
            }
        }
        
        // Slow query alerts
        if ($type === self::METRIC_DATABASE_QUERY && $name === 'slow_query') {
            $alerts[] = [
                'level' => self::ALERT_WARNING,
                'message' => "Slow database query detected: {$value}ms",
                'threshold' => $this->config['thresholds']['db_query_slow'] * 1000,
                'actual' => $value
            ];
        }
        
        // Store and send alerts
        foreach ($alerts as $alert) {
            $this->triggerAlert($type, $alert['level'], $alert['message'], $context, $alert['threshold'], $alert['actual']);
        }
    }
    
    /**
     * Trigger performance alert
     */
    private function triggerAlert($type, $level, $message, $context, $threshold = null, $actual = null)
    {
        try {
            // Store alert in database
            if ($this->db) {
                $stmt = $this->db->prepare("
                    INSERT INTO performance_alerts 
                    (alert_type, alert_level, message, context, threshold_value, actual_value) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $contextJson = json_encode($context);
                $stmt->bind_param('ssssdd', $type, $level, $message, $contextJson, $threshold, $actual);
                $stmt->execute();
            }
            
            // Log alert
            Logger::error("Performance Alert [$level]", [
                'type' => $type,
                'message' => $message,
                'threshold' => $threshold,
                'actual' => $actual,
                'context' => $context
            ]);
            
            // Send alert notifications
            $this->sendAlertNotification($level, $message, $context);
            
        } catch (Exception $e) {
            Logger::error('Failed to trigger performance alert', [
                'error' => $e->getMessage(),
                'original_alert' => $message
            ]);
        }
    }
    
    /**
     * Send alert notification
     */
    private function sendAlertNotification($level, $message, $context)
    {
        // Email notification
        if (!empty($this->config['alerts']['email'])) {
            $this->sendEmailAlert($level, $message, $context);
        }
        
        // Webhook notification
        if (!empty($this->config['alerts']['webhook_url'])) {
            $this->sendWebhookAlert($level, $message, $context);
        }
    }
    
    /**
     * Send email alert
     */
    private function sendEmailAlert($level, $message, $context)
    {
        $subject = "Sci-Bono LMS Performance Alert [$level]";
        $body = "Performance Alert Details:\n\n";
        $body .= "Level: $level\n";
        $body .= "Message: $message\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $body .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        
        // Basic email sending (replace with proper email service in production)
        mail($this->config['alerts']['email'], $subject, $body);
    }
    
    /**
     * Send webhook alert
     */
    private function sendWebhookAlert($level, $message, $context)
    {
        $payload = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('c'),
            'context' => $context,
            'service' => 'Sci-Bono LMS Performance Monitor'
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($payload)
            ]
        ];
        
        $context = stream_context_create($options);
        @file_get_contents($this->config['alerts']['webhook_url'], false, $context);
    }
    
    /**
     * Calculate error rate
     */
    private function calculateErrorRate($timeRange)
    {
        if (!$this->db) {
            return 0;
        }
        
        $sql = "
            SELECT 
                SUM(CASE WHEN metric_name LIKE 'response_code_4%' OR metric_name LIKE 'response_code_5%' THEN value ELSE 0 END) as errors,
                SUM(value) as total
            FROM performance_metrics 
            WHERE metric_type = ? 
            AND metric_name LIKE 'response_code_%'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL {$timeRange})
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', self::METRIC_API_REQUEST);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row && $row['total'] > 0) {
            return ($row['errors'] / $row['total']) * 100;
        }
        
        return 0;
    }
    
    /**
     * Get memory usage trend
     */
    private function getMemoryTrend($timeRange)
    {
        if (!$this->db) {
            return [];
        }
        
        $sql = "
            SELECT 
                DATE_FORMAT(timestamp, '%H:%i') as time,
                AVG(value) as avg_memory
            FROM performance_metrics 
            WHERE metric_type = ? 
            AND metric_name = 'current'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL {$timeRange})
            GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i')
            ORDER BY timestamp
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', self::METRIC_MEMORY_USAGE);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $trend = [];
        
        while ($row = $result->fetch_assoc()) {
            $trend[] = [
                'time' => $row['time'],
                'memory_mb' => round($row['avg_memory'] / (1024 * 1024), 2)
            ];
        }
        
        return $trend;
    }
    
    /**
     * Get slow queries
     */
    private function getSlowQueries($timeRange)
    {
        if (!$this->db) {
            return [];
        }
        
        $sql = "
            SELECT 
                value as duration,
                context,
                timestamp
            FROM performance_metrics 
            WHERE metric_type = ? 
            AND metric_name = 'slow_query'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL {$timeRange})
            ORDER BY value DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', self::METRIC_DATABASE_QUERY);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $queries = [];
        
        while ($row = $result->fetch_assoc()) {
            $context = json_decode($row['context'], true);
            $queries[] = [
                'duration' => $row['duration'],
                'query_type' => $context['query_type'] ?? 'unknown',
                'query_preview' => $context['query_preview'] ?? '',
                'timestamp' => $row['timestamp']
            ];
        }
        
        return $queries;
    }
    
    /**
     * Flush metrics to database
     */
    public function flushMetrics()
    {
        if (!$this->db || empty($this->metrics) || !is_array($this->metrics)) {
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO performance_metrics 
            (metric_type, metric_name, value, unit, context, endpoint, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))
        ");
        
        foreach ($this->metrics as $metric) {
            if (!is_array($metric) || !isset($metric['type'])) {
                continue;
            }
            
            $contextJson = json_encode($metric['context'] ?? []);
            $endpoint = $metric['endpoint'] ?? null;
            $ipAddress = $metric['ip_address'] ?? null;
            $userAgent = $metric['user_agent'] ?? null;
            
            $stmt->bind_param(
                'ssdsssssd',
                $metric['type'],
                $metric['name'],
                $metric['value'],
                $metric['unit'],
                $contextJson,
                $endpoint,
                $ipAddress,
                $userAgent,
                $metric['timestamp']
            );
            
            try {
                $stmt->execute();
            } catch (Exception $e) {
                Logger::error('Failed to store performance metric', [
                    'error' => $e->getMessage(),
                    'metric' => $metric
                ]);
            }
        }
        
        // Clear processed metrics
        $this->metrics = array_filter($this->metrics, function($key) {
            return $key === 'timers';
        }, ARRAY_FILTER_USE_KEY);
    }
    
    /**
     * Determine if we should sample this metric
     */
    private function shouldSample()
    {
        return mt_rand() / mt_getrandmax() < $this->config['sample_rate'];
    }
    
    /**
     * Get query type from SQL
     */
    private function getQueryType($query)
    {
        $query = trim(strtoupper($query));
        
        if (strpos($query, 'SELECT') === 0) return 'SELECT';
        if (strpos($query, 'INSERT') === 0) return 'INSERT';
        if (strpos($query, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($query, 'DELETE') === 0) return 'DELETE';
        if (strpos($query, 'CREATE') === 0) return 'CREATE';
        if (strpos($query, 'ALTER') === 0) return 'ALTER';
        if (strpos($query, 'DROP') === 0) return 'DROP';
        
        return 'OTHER';
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit)
    {
        if ($limit === '-1') return -1;
        
        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Periodic check handler
     */
    public function onPeriodicCheck()
    {
        $this->monitorMemoryUsage();
        $this->flushMetrics();
        
        // Schedule next check
        if (function_exists('pcntl_alarm')) {
            pcntl_alarm(60);
        }
    }
    
    /**
     * Shutdown handler
     */
    public function onShutdown()
    {
        try {
            // Record final memory usage
            $this->monitorMemoryUsage();
            
            // Record total execution time
            $totalTime = (microtime(true) - $this->startTime) * 1000;
            $this->recordMetric(self::METRIC_CUSTOM, 'total_execution_time', $totalTime, 'ms');
            
            // Flush remaining metrics
            $this->flushMetrics();
            
        } catch (Exception $e) {
            // Silently handle shutdown errors to avoid interfering with response
            error_log('Performance monitor shutdown error: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up old metrics
     */
    public function cleanupOldMetrics()
    {
        if (!$this->db) {
            return;
        }
        
        $retentionDays = $this->config['retention_days'];
        
        try {
            // Delete old metrics
            $stmt = $this->db->prepare("DELETE FROM performance_metrics WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param('i', $retentionDays);
            $stmt->execute();
            
            // Delete old alerts
            $stmt = $this->db->prepare("DELETE FROM performance_alerts WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param('i', $retentionDays);
            $stmt->execute();
            
            Logger::info('Performance metrics cleanup completed', [
                'retention_days' => $retentionDays
            ]);
            
        } catch (Exception $e) {
            Logger::error('Performance metrics cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}