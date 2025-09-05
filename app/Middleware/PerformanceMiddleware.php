<?php

namespace App\Middleware;

require_once __DIR__ . '/../Services/PerformanceMonitor.php';
require_once __DIR__ . '/../Utils/Logger.php';

use App\Services\PerformanceMonitor;
use App\Utils\Logger;
use Exception;

/**
 * Performance Monitoring Middleware
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * Automatically monitors API performance, database queries,
 * and system metrics for all requests
 */
class PerformanceMiddleware
{
    private $performanceMonitor;
    private $startTime;
    private $startMemory;
    private $queryMonitor;
    
    public function __construct($db = null)
    {
        $this->performanceMonitor = PerformanceMonitor::getInstance($db);
        $this->queryMonitor = new DatabaseQueryMonitor($db);
    }
    
    /**
     * Handle incoming request (before processing)
     */
    public function before()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        // Record request start
        $this->performanceMonitor->recordCustomMetric('request_started', 1, 'count', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        // Start database query monitoring
        $this->queryMonitor->startMonitoring();
        
        // Monitor initial system state
        $this->recordSystemMetrics('before_request');
    }
    
    /**
     * Handle response (after processing)
     */
    public function after($responseCode = 200, $responseData = null)
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = ($endTime - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $this->startMemory;
        
        $endpoint = $this->getEndpoint();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        
        // Monitor API request performance
        $this->performanceMonitor->monitorApiRequest($endpoint, $method, $responseCode, $this->startTime);
        
        // Record memory usage for this request
        $this->performanceMonitor->recordMetric(
            PerformanceMonitor::METRIC_MEMORY_USAGE,
            'request_memory_usage',
            $memoryUsed,
            'bytes',
            [
                'endpoint' => $endpoint,
                'method' => $method,
                'response_code' => $responseCode
            ]
        );
        
        // Stop database query monitoring and get results
        $queryStats = $this->queryMonitor->stopMonitoring();
        
        // Record database performance metrics
        if ($queryStats['total_queries'] > 0) {
            $this->performanceMonitor->recordCustomMetric(
                'database_queries_per_request',
                $queryStats['total_queries'],
                'count',
                [
                    'endpoint' => $endpoint,
                    'total_duration' => $queryStats['total_duration'],
                    'slow_queries' => $queryStats['slow_queries']
                ]
            );
        }
        
        // Record response size if available
        if ($responseData && is_string($responseData)) {
            $responseSize = strlen($responseData);
            $this->performanceMonitor->recordCustomMetric(
                'response_size',
                $responseSize,
                'bytes',
                ['endpoint' => $endpoint]
            );
        }
        
        // Monitor final system state
        $this->recordSystemMetrics('after_request');
        
        // Log performance summary for slow requests
        if ($duration > 1000) { // Requests taking more than 1 second
            Logger::warning('Slow API request detected', [
                'endpoint' => $endpoint,
                'method' => $method,
                'duration_ms' => $duration,
                'memory_used_mb' => round($memoryUsed / (1024 * 1024), 2),
                'database_queries' => $queryStats['total_queries'],
                'response_code' => $responseCode
            ]);
        }
    }
    
    /**
     * Handle errors and exceptions
     */
    public function onError($exception, $endpoint = null)
    {
        $endpoint = $endpoint ?: $this->getEndpoint();
        
        // Record error metrics
        $this->performanceMonitor->recordMetric(
            PerformanceMonitor::METRIC_ERROR_RATE,
            'exception',
            1,
            'count',
            [
                'endpoint' => $endpoint,
                'exception_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]
        );
        
        // Log error with performance context
        Logger::error('API request error with performance context', [
            'endpoint' => $endpoint,
            'exception' => $exception->getMessage(),
            'duration_ms' => $this->startTime ? (microtime(true) - $this->startTime) * 1000 : null,
            'memory_used_mb' => $this->startMemory ? round((memory_get_usage(true) - $this->startMemory) / (1024 * 1024), 2) : null,
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Still call the after method to record partial performance data
        $this->after(500);
    }
    
    /**
     * Record system-level metrics
     */
    private function recordSystemMetrics($phase)
    {
        // Memory usage
        $this->performanceMonitor->monitorMemoryUsage();
        
        // CPU load (if available)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $this->performanceMonitor->recordCustomMetric(
                    'cpu_load_1min',
                    $load[0],
                    'load',
                    ['phase' => $phase]
                );
            }
        }
        
        // Disk I/O (basic check)
        if ($phase === 'before_request') {
            $this->diskReadStart = $this->getDiskReadBytes();
        } elseif ($phase === 'after_request' && isset($this->diskReadStart)) {
            $diskReadEnd = $this->getDiskReadBytes();
            if ($diskReadEnd !== null && $this->diskReadStart !== null) {
                $diskRead = $diskReadEnd - $this->diskReadStart;
                if ($diskRead > 0) {
                    $this->performanceMonitor->recordCustomMetric(
                        'disk_read_bytes',
                        $diskRead,
                        'bytes'
                    );
                }
            }
        }
        
        // Connection counts (if available)
        if (function_exists('apache_get_modules') && in_array('mod_status', apache_get_modules())) {
            $this->recordApacheMetrics();
        }
    }
    
    /**
     * Get current endpoint from request
     */
    private function getEndpoint()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query parameters
        $uri = strtok($uri, '?');
        
        // Normalize common patterns
        $uri = preg_replace('/\/\d+/', '/{id}', $uri); // Replace numeric IDs
        $uri = preg_replace('/\/[a-f0-9\-]{36}/', '/{uuid}', $uri); // Replace UUIDs
        
        return $uri;
    }
    
    /**
     * Get disk read bytes (Linux only)
     */
    private function getDiskReadBytes()
    {
        if (!file_exists('/proc/self/io')) {
            return null;
        }
        
        $content = file_get_contents('/proc/self/io');
        if (preg_match('/read_bytes:\s*(\d+)/', $content, $matches)) {
            return (int)$matches[1];
        }
        
        return null;
    }
    
    /**
     * Record Apache-specific metrics
     */
    private function recordApacheMetrics()
    {
        // This would require mod_status to be enabled
        // Implementation depends on server configuration
        $this->performanceMonitor->recordCustomMetric(
            'apache_connections',
            1,
            'count',
            ['source' => 'estimated']
        );
    }
}

/**
 * Database Query Performance Monitor
 */
class DatabaseQueryMonitor
{
    private $db;
    private $originalQuery;
    private $queries = [];
    private $isMonitoring = false;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Start monitoring database queries
     */
    public function startMonitoring()
    {
        if (!$this->db || $this->isMonitoring) {
            return;
        }
        
        $this->isMonitoring = true;
        $this->queries = [];
        
        // Hook into mysqli query method using a wrapper
        $this->setupQueryHook();
    }
    
    /**
     * Stop monitoring and return statistics
     */
    public function stopMonitoring()
    {
        $this->isMonitoring = false;
        
        $stats = [
            'total_queries' => count($this->queries),
            'total_duration' => array_sum(array_column($this->queries, 'duration')),
            'slow_queries' => count(array_filter($this->queries, function($q) { return $q['duration'] > 500; })),
            'query_types' => $this->getQueryTypeBreakdown()
        ];
        
        // Send query data to performance monitor
        $performanceMonitor = PerformanceMonitor::getInstance();
        foreach ($this->queries as $query) {
            $performanceMonitor->monitorDatabaseQuery(
                $query['sql'],
                $query['duration'],
                [
                    'affected_rows' => $query['affected_rows'] ?? 0,
                    'query_type' => $query['type']
                ]
            );
        }
        
        return $stats;
    }
    
    /**
     * Record a database query
     */
    public function recordQuery($sql, $duration, $affectedRows = null)
    {
        if (!$this->isMonitoring) {
            return;
        }
        
        $this->queries[] = [
            'sql' => $sql,
            'duration' => $duration,
            'affected_rows' => $affectedRows,
            'type' => $this->getQueryType($sql),
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * Set up query monitoring hook
     */
    private function setupQueryHook()
    {
        // Note: This is a simplified approach
        // In a production environment, you might want to use a proper
        // database proxy or override the database connection class
        
        if (method_exists($this->db, 'set_charset')) {
            // Store original query method reference
            $this->originalQuery = [$this->db, 'query'];
        }
    }
    
    /**
     * Get query type breakdown
     */
    private function getQueryTypeBreakdown()
    {
        $breakdown = [];
        
        foreach ($this->queries as $query) {
            $type = $query['type'];
            if (!isset($breakdown[$type])) {
                $breakdown[$type] = ['count' => 0, 'total_duration' => 0];
            }
            $breakdown[$type]['count']++;
            $breakdown[$type]['total_duration'] += $query['duration'];
        }
        
        return $breakdown;
    }
    
    /**
     * Determine query type from SQL
     */
    private function getQueryType($sql)
    {
        $sql = trim(strtoupper($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        if (strpos($sql, 'CREATE') === 0) return 'CREATE';
        if (strpos($sql, 'ALTER') === 0) return 'ALTER';
        if (strpos($sql, 'DROP') === 0) return 'DROP';
        
        return 'OTHER';
    }
}

/**
 * Global performance monitoring functions
 */

/**
 * Initialize performance monitoring for the current request
 */
function initPerformanceMonitoring($db = null) 
{
    $GLOBALS['_performance_middleware'] = new PerformanceMiddleware($db);
    $GLOBALS['_performance_middleware']->before();
    
    // Set up error handler
    set_exception_handler(function($exception) {
        if (isset($GLOBALS['_performance_middleware'])) {
            $GLOBALS['_performance_middleware']->onError($exception);
        }
        
        // Re-throw the exception
        throw $exception;
    });
    
    // Set up shutdown handler
    register_shutdown_function(function() {
        if (isset($GLOBALS['_performance_middleware'])) {
            $error = error_get_last();
            $responseCode = http_response_code() ?: 200;
            
            if ($error && $error['type'] === E_ERROR) {
                $responseCode = 500;
            }
            
            $GLOBALS['_performance_middleware']->after($responseCode);
        }
    });
}

/**
 * Manually finish performance monitoring
 */
function finishPerformanceMonitoring($responseCode = 200, $responseData = null) 
{
    if (isset($GLOBALS['_performance_middleware'])) {
        $GLOBALS['_performance_middleware']->after($responseCode, $responseData);
    }
}

/**
 * Record custom performance metric
 */
function recordPerformanceMetric($name, $value, $unit = 'count', $context = []) 
{
    $monitor = PerformanceMonitor::getInstance();
    $monitor->recordCustomMetric($name, $value, $unit, $context);
}

/**
 * Start performance timer
 */
function startPerformanceTimer($name, $context = []) 
{
    $monitor = PerformanceMonitor::getInstance();
    return $monitor->startTimer($name, $context);
}

/**
 * Stop performance timer
 */
function stopPerformanceTimer($timerId, $context = []) 
{
    $monitor = PerformanceMonitor::getInstance();
    return $monitor->stopTimer($timerId, $context);
}

/**
 * Enhanced database query wrapper with performance monitoring
 */
function performanceAwareMysqliQuery($mysqli, $sql) 
{
    $startTime = microtime(true);
    $result = $mysqli->query($sql);
    $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
    
    // Record query performance
    if (isset($GLOBALS['_performance_middleware'])) {
        $queryMonitor = new DatabaseQueryMonitor($mysqli);
        $affectedRows = $result ? $mysqli->affected_rows : null;
        $queryMonitor->recordQuery($sql, $duration, $affectedRows);
    }
    
    return $result;
}