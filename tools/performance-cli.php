<?php
/**
 * Performance Monitoring CLI Tool
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * Command-line tool for performance monitoring maintenance,
 * analytics, and system health checks
 */

require_once __DIR__ . '/../app/Services/PerformanceMonitor.php';
require_once __DIR__ . '/../app/Utils/Logger.php';
require_once __DIR__ . '/../server.php';

use App\Services\PerformanceMonitor;
use App\Utils\Logger;

class PerformanceCliTool
{
    private $db;
    private $performanceMonitor;
    private $commands = [];
    
    public function __construct()
    {
        global $mysqli;
        $this->db = $mysqli;
        $this->performanceMonitor = PerformanceMonitor::getInstance($this->db);
        
        $this->registerCommands();
    }
    
    /**
     * Register available CLI commands
     */
    private function registerCommands()
    {
        $this->commands = [
            'status' => [
                'description' => 'Show current performance monitoring status',
                'method' => 'showStatus'
            ],
            'metrics' => [
                'description' => 'Display performance metrics summary',
                'method' => 'showMetrics',
                'options' => ['--range=<timerange>', '--type=<metric_type>']
            ],
            'alerts' => [
                'description' => 'Manage performance alerts',
                'method' => 'manageAlerts',
                'options' => ['--show', '--resolve=<id>', '--cleanup']
            ],
            'cleanup' => [
                'description' => 'Clean up old performance data',
                'method' => 'cleanupData',
                'options' => ['--days=<retention_days>', '--confirm']
            ],
            'analyze' => [
                'description' => 'Analyze performance trends and generate reports',
                'method' => 'analyzePerformance',
                'options' => ['--range=<timerange>', '--output=<file>']
            ],
            'healthcheck' => [
                'description' => 'Perform comprehensive system health check',
                'method' => 'healthCheck'
            ],
            'benchmark' => [
                'description' => 'Run performance benchmark tests',
                'method' => 'runBenchmark',
                'options' => ['--type=<test_type>', '--iterations=<count>']
            ],
            'export' => [
                'description' => 'Export performance data',
                'method' => 'exportData',
                'options' => ['--format=<json|csv>', '--range=<timerange>', '--output=<file>']
            ],
            'optimize' => [
                'description' => 'Optimize performance monitoring database',
                'method' => 'optimizeDatabase'
            ],
            'monitor' => [
                'description' => 'Start real-time monitoring dashboard',
                'method' => 'realtimeMonitor',
                'options' => ['--interval=<seconds>']
            ]
        ];
    }
    
    /**
     * Run CLI command
     */
    public function run($argv)
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return;
        }
        
        $command = $argv[1];
        $options = $this->parseOptions(array_slice($argv, 2));
        
        if ($command === 'help' || $command === '--help' || $command === '-h') {
            $this->showHelp();
            return;
        }
        
        if (!isset($this->commands[$command])) {
            $this->error("Unknown command: $command");
            $this->showHelp();
            return;
        }
        
        $method = $this->commands[$command]['method'];
        $this->$method($options);
    }
    
    /**
     * Show help information
     */
    private function showHelp()
    {
        $this->info("Sci-Bono LMS Performance Monitoring CLI Tool");
        $this->info("===============================================");
        $this->info("");
        $this->info("Usage: php performance-cli.php <command> [options]");
        $this->info("");
        $this->info("Available Commands:");
        
        foreach ($this->commands as $command => $details) {
            $this->info(sprintf("  %-12s %s", $command, $details['description']));
            if (!empty($details['options'])) {
                foreach ($details['options'] as $option) {
                    $this->info(sprintf("               %s", $option));
                }
            }
            $this->info("");
        }
        
        $this->info("Examples:");
        $this->info("  php performance-cli.php status");
        $this->info("  php performance-cli.php metrics --range=24h --type=api_request");
        $this->info("  php performance-cli.php alerts --show");
        $this->info("  php performance-cli.php cleanup --days=30 --confirm");
        $this->info("  php performance-cli.php export --format=json --range=7d --output=metrics.json");
    }
    
    /**
     * Show current monitoring status
     */
    private function showStatus($options)
    {
        $this->info("Performance Monitoring Status");
        $this->info("============================");
        
        // Check database connection
        $dbStatus = $this->db ? "✓ Connected" : "✗ Disconnected";
        $this->info("Database: $dbStatus");
        
        // Check if monitoring is enabled
        $enabled = getenv('PERFORMANCE_MONITORING') !== 'false';
        $monitoringStatus = $enabled ? "✓ Enabled" : "✗ Disabled";
        $this->info("Monitoring: $monitoringStatus");
        
        // Check recent activity
        if ($this->db) {
            $result = $this->db->query("SELECT COUNT(*) as count FROM performance_metrics WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $recentMetrics = $result ? $result->fetch_assoc()['count'] : 0;
            $this->info("Recent metrics (1h): $recentMetrics");
            
            $result = $this->db->query("SELECT COUNT(*) as count FROM performance_alerts WHERE is_resolved = FALSE");
            $activeAlerts = $result ? $result->fetch_assoc()['count'] : 0;
            $this->info("Active alerts: $activeAlerts");
        }
        
        // System information
        $this->info("");
        $this->info("System Information:");
        $this->info("PHP Version: " . PHP_VERSION);
        $this->info("Memory Usage: " . $this->formatBytes(memory_get_usage(true)));
        $this->info("Memory Limit: " . ini_get('memory_limit'));
        
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $this->info("System Load: " . implode(', ', array_map(function($l) { return round($l, 2); }, $load)));
        }
    }
    
    /**
     * Show performance metrics
     */
    private function showMetrics($options)
    {
        $range = $options['range'] ?? '24h';
        $type = $options['type'] ?? null;
        
        $this->info("Performance Metrics ($range)");
        $this->info("===========================");
        
        $metrics = $this->performanceMonitor->getMetrics($range, $type);
        
        if (empty($metrics)) {
            $this->warning("No metrics found for the specified range and type");
            return;
        }
        
        // Group metrics by type
        $groupedMetrics = [];
        foreach ($metrics as $metric) {
            $groupedMetrics[$metric['metric_type']][] = $metric;
        }
        
        foreach ($groupedMetrics as $metricType => $typeMetrics) {
            $this->info("");
            $this->info(strtoupper($metricType) . " Metrics:");
            $this->info(str_repeat("-", 60));
            
            printf("%-25s %8s %10s %10s %10s %6s\n", "Name", "Count", "Min", "Max", "Avg", "Unit");
            $this->info(str_repeat("-", 60));
            
            foreach ($typeMetrics as $metric) {
                printf("%-25s %8d %10.2f %10.2f %10.2f %6s\n",
                    $metric['metric_name'],
                    $metric['count'],
                    $metric['min_value'],
                    $metric['max_value'],
                    $metric['avg_value'],
                    $metric['unit']
                );
            }
        }
    }
    
    /**
     * Manage performance alerts
     */
    private function manageAlerts($options)
    {
        if (isset($options['show'])) {
            $this->showAlerts();
        } elseif (isset($options['resolve'])) {
            $this->resolveAlert($options['resolve']);
        } elseif (isset($options['cleanup'])) {
            $this->cleanupResolvedAlerts();
        } else {
            $this->showAlerts();
        }
    }
    
    /**
     * Show active alerts
     */
    private function showAlerts()
    {
        $this->info("Performance Alerts");
        $this->info("==================");
        
        $alerts = $this->performanceMonitor->getAlerts(false, 100);
        
        if (empty($alerts)) {
            $this->success("No active alerts found! 🎉");
            return;
        }
        
        printf("%-4s %-10s %-10s %-50s %-20s\n", "ID", "Type", "Level", "Message", "Created");
        $this->info(str_repeat("-", 100));
        
        foreach ($alerts as $alert) {
            printf("%-4d %-10s %-10s %-50s %-20s\n",
                $alert['id'],
                $alert['alert_type'],
                $alert['alert_level'],
                substr($alert['message'], 0, 47) . (strlen($alert['message']) > 47 ? '...' : ''),
                $alert['created_at']
            );
        }
        
        $this->info("");
        $this->info("Use --resolve=<id> to resolve an alert");
    }
    
    /**
     * Resolve specific alert
     */
    private function resolveAlert($alertId)
    {
        $stmt = $this->db->prepare("UPDATE performance_alerts SET is_resolved = TRUE, resolved_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $alertId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $this->success("Alert $alertId has been resolved");
        } else {
            $this->error("Alert $alertId not found or already resolved");
        }
    }
    
    /**
     * Clean up resolved alerts
     */
    private function cleanupResolvedAlerts()
    {
        $result = $this->db->query("DELETE FROM performance_alerts WHERE is_resolved = TRUE AND resolved_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        
        if ($result) {
            $deleted = $this->db->affected_rows;
            $this->success("Cleaned up $deleted resolved alerts");
        } else {
            $this->error("Failed to clean up resolved alerts");
        }
    }
    
    /**
     * Clean up old performance data
     */
    private function cleanupData($options)
    {
        $days = (int)($options['days'] ?? 30);
        $confirm = isset($options['confirm']);
        
        if (!$confirm) {
            $this->warning("This will delete performance data older than $days days");
            $this->warning("Use --confirm to proceed");
            return;
        }
        
        $this->info("Cleaning up performance data older than $days days...");
        
        // Count records to be deleted
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM performance_metrics WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($count > 0) {
            $this->performanceMonitor->cleanupOldMetrics();
            $this->success("Deleted $count old performance records");
        } else {
            $this->info("No old records found to delete");
        }
    }
    
    /**
     * Analyze performance trends
     */
    private function analyzePerformance($options)
    {
        $range = $options['range'] ?? '7d';
        $outputFile = $options['output'] ?? null;
        
        $this->info("Analyzing Performance Trends ($range)");
        $this->info("=====================================");
        
        $analysis = [
            'summary' => $this->performanceMonitor->getPerformanceSummary($range),
            'trends' => $this->calculateTrends($range),
            'recommendations' => $this->generateRecommendations($range)
        ];
        
        // Display analysis
        $this->displayAnalysis($analysis);
        
        // Save to file if requested
        if ($outputFile) {
            file_put_contents($outputFile, json_encode($analysis, JSON_PRETTY_PRINT));
            $this->success("Analysis saved to $outputFile");
        }
    }
    
    /**
     * Perform comprehensive health check
     */
    private function healthCheck($options)
    {
        $this->info("System Health Check");
        $this->info("==================");
        
        $checks = [
            'database' => $this->checkDatabase(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace(),
            'performance_metrics' => $this->checkPerformanceMetrics(),
            'error_rate' => $this->checkErrorRate(),
            'response_times' => $this->checkResponseTimes()
        ];
        
        $overallHealth = 'healthy';
        foreach ($checks as $check => $result) {
            $status = $result['status'];
            $message = $result['message'];
            
            if ($status === 'critical') {
                $this->error("$check: $message");
                $overallHealth = 'critical';
            } elseif ($status === 'warning') {
                $this->warning("$check: $message");
                if ($overallHealth === 'healthy') {
                    $overallHealth = 'warning';
                }
            } else {
                $this->success("$check: $message");
            }
        }
        
        $this->info("");
        $this->info("Overall Health: " . strtoupper($overallHealth));
        
        return $overallHealth === 'healthy';
    }
    
    /**
     * Run performance benchmarks
     */
    private function runBenchmark($options)
    {
        $type = $options['type'] ?? 'all';
        $iterations = (int)($options['iterations'] ?? 100);
        
        $this->info("Running Performance Benchmarks");
        $this->info("=============================");
        
        $benchmarks = [];
        
        if ($type === 'all' || $type === 'database') {
            $benchmarks['database'] = $this->benchmarkDatabase($iterations);
        }
        
        if ($type === 'all' || $type === 'memory') {
            $benchmarks['memory'] = $this->benchmarkMemory($iterations);
        }
        
        if ($type === 'all' || $type === 'computation') {
            $benchmarks['computation'] = $this->benchmarkComputation($iterations);
        }
        
        $this->displayBenchmarkResults($benchmarks);
    }
    
    /**
     * Export performance data
     */
    private function exportData($options)
    {
        $format = $options['format'] ?? 'json';
        $range = $options['range'] ?? '24h';
        $outputFile = $options['output'] ?? 'performance-export-' . date('Y-m-d-H-i-s') . '.' . $format;
        
        $this->info("Exporting performance data ($format format, $range range)");
        
        $data = [
            'metadata' => [
                'exported_at' => date('c'),
                'time_range' => $range,
                'format' => $format
            ],
            'metrics' => $this->performanceMonitor->getMetrics($range),
            'alerts' => $this->performanceMonitor->getAlerts(false, 1000),
            'summary' => $this->performanceMonitor->getPerformanceSummary($range)
        ];
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($data, $outputFile);
                break;
            default:
                file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
                break;
        }
        
        $this->success("Data exported to $outputFile");
    }
    
    /**
     * Optimize performance monitoring database
     */
    private function optimizeDatabase($options)
    {
        $this->info("Optimizing Performance Database");
        $this->info("==============================");
        
        $tables = ['performance_metrics', 'performance_alerts', 'performance_summary'];
        
        foreach ($tables as $table) {
            $this->info("Optimizing table: $table");
            $result = $this->db->query("OPTIMIZE TABLE $table");
            
            if ($result) {
                $this->success("✓ $table optimized");
            } else {
                $this->error("✗ Failed to optimize $table");
            }
        }
        
        // Update table statistics
        $this->info("Updating table statistics...");
        foreach ($tables as $table) {
            $this->db->query("ANALYZE TABLE $table");
        }
        
        $this->success("Database optimization completed");
    }
    
    /**
     * Start real-time monitoring
     */
    private function realtimeMonitor($options)
    {
        $interval = (int)($options['interval'] ?? 5);
        
        $this->info("Starting Real-time Performance Monitor");
        $this->info("Refresh interval: {$interval} seconds");
        $this->info("Press Ctrl+C to stop");
        $this->info("");
        
        while (true) {
            $this->clearScreen();
            $this->displayRealtimeMetrics();
            sleep($interval);
        }
    }
    
    // Helper methods
    
    private function parseOptions($args)
    {
        $options = [];
        
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                if (strpos($arg, '=') !== false) {
                    list($key, $value) = explode('=', substr($arg, 2), 2);
                    $options[$key] = $value;
                } else {
                    $options[substr($arg, 2)] = true;
                }
            }
        }
        
        return $options;
    }
    
    private function info($message)
    {
        echo "[INFO] $message\n";
    }
    
    private function success($message)
    {
        echo "\033[32m[SUCCESS]\033[0m $message\n";
    }
    
    private function warning($message)
    {
        echo "\033[33m[WARNING]\033[0m $message\n";
    }
    
    private function error($message)
    {
        echo "\033[31m[ERROR]\033[0m $message\n";
    }
    
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function clearScreen()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
    
    private function displayRealtimeMetrics()
    {
        $summary = $this->performanceMonitor->getPerformanceSummary('1h');
        
        $this->info("=== Real-time Performance Monitor ===");
        $this->info("Time: " . date('Y-m-d H:i:s'));
        $this->info("");
        
        // Memory usage
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $this->info("Memory Usage: " . $this->formatBytes($memUsage) . " / Peak: " . $this->formatBytes($memPeak));
        
        // Error rate
        $errorRate = $summary['error_rate'] ?? 0;
        $this->info("Error Rate: " . round($errorRate, 2) . "%");
        
        // Active alerts
        $alertCount = $summary['alert_count'] ?? 0;
        $this->info("Active Alerts: $alertCount");
        
        $this->info("");
    }
    
    private function calculateTrends($range)
    {
        // Implementation for trend calculation
        return ['trend' => 'stable'];
    }
    
    private function generateRecommendations($range)
    {
        // Implementation for generating recommendations
        return ['recommendations' => []];
    }
    
    private function displayAnalysis($analysis)
    {
        // Display analysis results
        $this->info("Analysis completed");
    }
    
    private function checkDatabase()
    {
        if (!$this->db) {
            return ['status' => 'critical', 'message' => 'Database connection failed'];
        }
        return ['status' => 'ok', 'message' => 'Database connection healthy'];
    }
    
    private function checkMemory()
    {
        $usage = memory_get_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($limit > 0) {
            $percent = ($usage / $limit) * 100;
            if ($percent > 90) {
                return ['status' => 'critical', 'message' => "Memory usage critical: {$percent}%"];
            } elseif ($percent > 75) {
                return ['status' => 'warning', 'message' => "Memory usage high: {$percent}%"];
            }
        }
        
        return ['status' => 'ok', 'message' => 'Memory usage normal'];
    }
    
    private function checkDiskSpace()
    {
        if (function_exists('disk_free_space')) {
            $free = disk_free_space('./');
            $total = disk_total_space('./');
            $percent = (($total - $free) / $total) * 100;
            
            if ($percent > 90) {
                return ['status' => 'critical', 'message' => "Disk usage critical: {$percent}%"];
            } elseif ($percent > 80) {
                return ['status' => 'warning', 'message' => "Disk usage high: {$percent}%"];
            }
        }
        
        return ['status' => 'ok', 'message' => 'Disk space adequate'];
    }
    
    private function checkPerformanceMetrics()
    {
        $metrics = $this->performanceMonitor->getMetrics('1h');
        
        if (empty($metrics)) {
            return ['status' => 'warning', 'message' => 'No recent performance metrics'];
        }
        
        return ['status' => 'ok', 'message' => count($metrics) . ' metric types collected'];
    }
    
    private function checkErrorRate()
    {
        $summary = $this->performanceMonitor->getPerformanceSummary('1h');
        $errorRate = $summary['error_rate'] ?? 0;
        
        if ($errorRate > 10) {
            return ['status' => 'critical', 'message' => "Error rate critical: {$errorRate}%"];
        } elseif ($errorRate > 5) {
            return ['status' => 'warning', 'message' => "Error rate elevated: {$errorRate}%"];
        }
        
        return ['status' => 'ok', 'message' => "Error rate normal: {$errorRate}%"];
    }
    
    private function checkResponseTimes()
    {
        $metrics = $this->performanceMonitor->getMetrics('1h', 'api_request');
        
        foreach ($metrics as $metric) {
            if ($metric['metric_name'] === 'response_time' && $metric['avg_value'] > 3000) {
                return ['status' => 'warning', 'message' => 'Average response time high: ' . $metric['avg_value'] . 'ms'];
            }
        }
        
        return ['status' => 'ok', 'message' => 'Response times normal'];
    }
    
    private function benchmarkDatabase($iterations)
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query("SELECT 1");
        }
        
        $duration = microtime(true) - $startTime;
        
        return [
            'iterations' => $iterations,
            'total_time' => $duration,
            'avg_time' => $duration / $iterations,
            'queries_per_second' => $iterations / $duration
        ];
    }
    
    private function benchmarkMemory($iterations)
    {
        $startMemory = memory_get_usage();
        $data = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $data[] = str_repeat('x', 1000);
        }
        
        $endMemory = memory_get_usage();
        unset($data);
        
        return [
            'memory_allocated' => $endMemory - $startMemory,
            'per_iteration' => ($endMemory - $startMemory) / $iterations
        ];
    }
    
    private function benchmarkComputation($iterations)
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $result = 0;
            for ($j = 0; $j < 1000; $j++) {
                $result += sin($j) * cos($j);
            }
        }
        
        $duration = microtime(true) - $startTime;
        
        return [
            'iterations' => $iterations,
            'total_time' => $duration,
            'avg_time' => $duration / $iterations
        ];
    }
    
    private function displayBenchmarkResults($benchmarks)
    {
        foreach ($benchmarks as $type => $results) {
            $this->info("$type Benchmark Results:");
            foreach ($results as $key => $value) {
                $this->info("  $key: $value");
            }
            $this->info("");
        }
    }
    
    private function exportToCsv($data, $filename)
    {
        $fp = fopen($filename, 'w');
        
        // Export metrics
        fputcsv($fp, ['Type', 'Metric', 'Name', 'Count', 'Min', 'Max', 'Avg', 'Unit']);
        foreach ($data['metrics'] as $metric) {
            fputcsv($fp, [
                $metric['metric_type'],
                $metric['metric_name'],
                $metric['count'],
                $metric['min_value'],
                $metric['max_value'],
                $metric['avg_value'],
                $metric['unit']
            ]);
        }
        
        fclose($fp);
    }
    
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
}

// Run the CLI tool
if (php_sapi_name() === 'cli') {
    $tool = new PerformanceCliTool();
    $tool->run($argv);
} else {
    echo "This tool must be run from the command line.\n";
    exit(1);
}