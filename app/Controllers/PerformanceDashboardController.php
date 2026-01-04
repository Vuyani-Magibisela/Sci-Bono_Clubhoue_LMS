<?php
/**
 * Performance Dashboard Controller
 *
 * Provides web interface for viewing performance metrics,
 * alerts, and system health information.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 4
 *
 * @package App\Controllers
 * @since Phase 7: API Development & Testing
 */

namespace App\Controllers;

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/PerformanceMonitor.php';
require_once __DIR__ . '/../Utils/Logger.php';

use App\Services\PerformanceMonitor;
use App\Utils\Logger;
use Exception;

class PerformanceDashboardController extends \BaseController
{
    private $performanceMonitor;

    public function __construct($conn, $config = null)
    {
        parent::__construct($conn, $config);
        $this->performanceMonitor = PerformanceMonitor::getInstance($this->conn);
    }

    /**
     * Display main performance dashboard
     * Requires admin or manager role
     */
    public function index()
    {
        // Require admin or manager role
        $this->requireRole(['admin', 'manager']);

        try {
            $timeRange = $_GET['range'] ?? '24h';
            $summary = $this->performanceMonitor->getPerformanceSummary($timeRange);
            $alerts = $this->performanceMonitor->getAlerts(false, 10);

            $this->logAction('view_performance_dashboard', [
                'time_range' => $timeRange,
                'alert_count' => count($alerts)
            ]);

            $this->renderDashboard($summary, $alerts, $timeRange);

        } catch (Exception $e) {
            $this->logger->error('Performance dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->renderError('Failed to load performance dashboard');
        }
    }

    /**
     * API endpoint for real-time metrics
     * Modern method with role protection and activity logging
     */
    public function getMetricsApi()
    {
        // Require admin or manager role
        $this->requireRole(['admin', 'manager']);

        try {
            $timeRange = $this->input('range', '1h');
            $metricType = $this->input('type', null);

            $metrics = $this->performanceMonitor->getMetrics($timeRange, $metricType);
            $summary = $this->performanceMonitor->getPerformanceSummary($timeRange);

            $this->logAction('fetch_performance_metrics', [
                'time_range' => $timeRange,
                'metric_type' => $metricType,
                'metric_count' => count($metrics)
            ]);

            $response = [
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'summary' => $summary,
                    'timestamp' => date('c')
                ]
            ];

            $this->jsonResponse($response);

        } catch (Exception $e) {
            $this->logger->error('Failed to fetch metrics', [
                'error' => $e->getMessage(),
                'time_range' => $timeRange ?? 'unknown'
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to fetch metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint for alerts
     * Modern method with role protection
     */
    public function getAlertsApi()
    {
        // Require admin or manager role
        $this->requireRole(['admin', 'manager']);

        try {
            $resolved = isset($_GET['resolved']) ? (bool)$_GET['resolved'] : false;
            $limit = (int)($this->input('limit', 50));

            $alerts = $this->performanceMonitor->getAlerts($resolved, $limit);

            $this->logAction('fetch_performance_alerts', [
                'resolved' => $resolved,
                'limit' => $limit,
                'alert_count' => count($alerts)
            ]);

            $this->jsonResponse([
                'success' => true,
                'data' => $alerts,
                'timestamp' => date('c')
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to fetch alerts', [
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to fetch alerts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve alert
     * Modern method with CSRF protection and role-based access
     */
    public function resolveAlert()
    {
        // Require admin or manager role
        $this->requireRole(['admin', 'manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Method not allowed'
            ], 405);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in resolve alert", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $this->jsonResponse([
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ], 403);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $alertId = $input['alert_id'] ?? null;

            if (!$alertId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Alert ID is required'
                ], 400);
                return;
            }

            $stmt = $this->conn->prepare("UPDATE performance_alerts SET is_resolved = TRUE, resolved_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $alertId);
            $result = $stmt->execute();

            if ($result) {
                $this->logAction('resolve_performance_alert', [
                    'alert_id' => $alertId,
                    'success' => true
                ]);

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alert resolved successfully'
                ]);
            } else {
                throw new Exception('Failed to update alert');
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to resolve alert', [
                'alert_id' => $alertId ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to resolve alert',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export performance data
     * Modern method with role protection and activity logging
     */
    public function exportData()
    {
        // Require admin or manager role
        $this->requireRole(['admin', 'manager']);

        try {
            $format = $this->input('format', 'json');
            $timeRange = $this->input('range', '24h');

            $metrics = $this->performanceMonitor->getMetrics($timeRange);
            $alerts = $this->performanceMonitor->getAlerts(false, 1000);

            $data = [
                'metrics' => $metrics,
                'alerts' => $alerts,
                'exported_at' => date('c'),
                'time_range' => $timeRange
            ];

            $this->logAction('export_performance_data', [
                'format' => $format,
                'time_range' => $timeRange,
                'metric_count' => count($metrics),
                'alert_count' => count($alerts)
            ]);

            switch ($format) {
                case 'csv':
                    $this->exportCsv($data);
                    break;
                case 'excel':
                    $this->exportExcel($data);
                    break;
                default:
                    $this->exportJson($data);
            }

        } catch (Exception $e) {
            $this->logger->error('Performance data export failed', [
                'error' => $e->getMessage(),
                'format' => $format ?? 'unknown',
                'time_range' => $timeRange ?? 'unknown'
            ]);

            $this->renderError('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Health check endpoint
     * Public endpoint for monitoring systems
     */
    public function healthCheck()
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'checks' => []
            ];

            // Database connectivity
            $health['checks']['database'] = $this->conn ? 'connected' : 'disconnected';

            // Memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memoryPercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

            $health['checks']['memory'] = [
                'usage_bytes' => $memoryUsage,
                'usage_mb' => round($memoryUsage / (1024 * 1024), 2),
                'limit_mb' => round($memoryLimit / (1024 * 1024), 2),
                'usage_percent' => round($memoryPercent, 2),
                'status' => $memoryPercent > 90 ? 'critical' : ($memoryPercent > 75 ? 'warning' : 'ok')
            ];

            // Recent alerts
            $recentAlerts = $this->performanceMonitor->getAlerts(false, 1);
            $health['checks']['alerts'] = [
                'active_count' => count($recentAlerts),
                'status' => count($recentAlerts) > 0 ? 'warning' : 'ok'
            ];

            // Disk space (if available)
            if (function_exists('disk_free_space')) {
                $freeSpace = disk_free_space('./');
                $totalSpace = disk_total_space('./');
                $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

                $health['checks']['disk'] = [
                    'free_gb' => round($freeSpace / (1024 * 1024 * 1024), 2),
                    'total_gb' => round($totalSpace / (1024 * 1024 * 1024), 2),
                    'used_percent' => round($usedPercent, 2),
                    'status' => $usedPercent > 90 ? 'critical' : ($usedPercent > 80 ? 'warning' : 'ok')
                ];
            }

            // Overall status
            $criticalChecks = array_filter($health['checks'], function($check) {
                return (is_array($check) && ($check['status'] ?? '') === 'critical') ||
                       $check === 'disconnected';
            });

            if (!empty($criticalChecks)) {
                $health['status'] = 'unhealthy';
                http_response_code(503);
            }

            $this->logAction('health_check', [
                'status' => $health['status'],
                'critical_checks' => count($criticalChecks)
            ]);

            $this->jsonResponse($health);

        } catch (Exception $e) {
            $this->logger->error('Health check failed', [
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ], 500);
        }
    }

    /**
     * Render performance dashboard HTML
     * Private helper method for dashboard rendering
     *
     * @param array $summary Performance summary data
     * @param array $alerts Alert data
     * @param string $timeRange Selected time range
     */
    private function renderDashboard($summary, $alerts, $timeRange)
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Performance Dashboard - Sci-Bono LMS</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
                .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 2rem; margin-bottom: 2rem; border-radius: 8px; }
                .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
                .header .subtitle { opacity: 0.8; }
                .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
                .card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .card h3 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.2rem; }
                .metric { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
                .metric:last-child { border-bottom: none; }
                .metric-value { font-weight: bold; color: #3498db; }
                .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 5px; border-left: 4px solid; }
                .alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
                .alert-critical { background: #f8d7da; border-color: #dc3545; color: #721c24; }
                .alert-info { background: #d1ecf1; border-color: #0dcaf0; color: #055160; }
                .btn { background: #3498db; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
                .btn:hover { background: #2980b9; }
                .btn-small { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
                .controls { background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
                .controls select, .controls button { margin-right: 1rem; padding: 0.5rem; }
                .chart-container { height: 300px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; }
                .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 0.5rem; }
                .status-ok { background: #28a745; }
                .status-warning { background: #ffc107; }
                .status-critical { background: #dc3545; }
                .refresh-timer { float: right; color: #666; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 Performance Dashboard</h1>
                    <p class="subtitle">Real-time monitoring for Sci-Bono Clubhouse LMS</p>
                    <div class="refresh-timer">Auto-refresh in: <span id="timer">60</span>s</div>
                </div>

                <div class="controls">
                    <select id="timeRange" onchange="changeTimeRange()">
                        <option value="1h" <?= $timeRange === '1h' ? 'selected' : '' ?>>Last Hour</option>
                        <option value="6h" <?= $timeRange === '6h' ? 'selected' : '' ?>>Last 6 Hours</option>
                        <option value="24h" <?= $timeRange === '24h' ? 'selected' : '' ?>>Last 24 Hours</option>
                        <option value="7d" <?= $timeRange === '7d' ? 'selected' : '' ?>>Last 7 Days</option>
                    </select>
                    <button onclick="refreshData()">🔄 Refresh</button>
                    <button onclick="exportData('json')">📊 Export JSON</button>
                    <button onclick="exportData('csv')">📄 Export CSV</button>
                </div>

                <div class="grid">
                    <!-- API Performance Card -->
                    <div class="card">
                        <h3>🌐 API Performance</h3>
                        <?php if (!empty($summary['api_performance'])): ?>
                            <?php foreach ($summary['api_performance'] as $metric): ?>
                                <?php if ($metric['metric_name'] === 'response_time'): ?>
                                    <div class="metric">
                                        <span>Average Response Time</span>
                                        <span class="metric-value"><?= round($metric['avg_value'], 2) ?>ms</span>
                                    </div>
                                    <div class="metric">
                                        <span>Min Response Time</span>
                                        <span class="metric-value"><?= round($metric['min_value'], 2) ?>ms</span>
                                    </div>
                                    <div class="metric">
                                        <span>Max Response Time</span>
                                        <span class="metric-value"><?= round($metric['max_value'], 2) ?>ms</span>
                                    </div>
                                    <div class="metric">
                                        <span>Total Requests</span>
                                        <span class="metric-value"><?= number_format($metric['count']) ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No API performance data available</p>
                        <?php endif; ?>
                    </div>

                    <!-- Error Rate Card -->
                    <div class="card">
                        <h3>⚠️ Error Rate</h3>
                        <div class="metric">
                            <span>Current Error Rate</span>
                            <span class="metric-value">
                                <?php
                                $errorRate = $summary['error_rate'] ?? 0;
                                $statusClass = $errorRate > 5 ? 'status-critical' : ($errorRate > 1 ? 'status-warning' : 'status-ok');
                                ?>
                                <span class="status-indicator <?= $statusClass ?>"></span>
                                <?= round($errorRate, 2) ?>%
                            </span>
                        </div>
                        <div class="metric">
                            <span>Alert Status</span>
                            <span class="metric-value">
                                <?php
                                $alertCount = $summary['alert_count'] ?? 0;
                                $alertStatus = $alertCount > 0 ? 'status-warning' : 'status-ok';
                                ?>
                                <span class="status-indicator <?= $alertStatus ?>"></span>
                                <?= $alertCount ?> active alerts
                            </span>
                        </div>
                    </div>

                    <!-- Memory Usage Card -->
                    <div class="card">
                        <h3>💾 Memory Usage</h3>
                        <div id="memoryChart" class="chart-container">
                            <?php if (!empty($summary['memory_trend'])): ?>
                                Memory usage trend chart placeholder<br>
                                <small>Latest: <?= end($summary['memory_trend'])['memory_mb'] ?? 'N/A' ?>MB</small>
                            <?php else: ?>
                                No memory data available
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Database Performance Card -->
                    <div class="card">
                        <h3>🗄️ Database Performance</h3>
                        <?php if (!empty($summary['slow_queries'])): ?>
                            <div class="metric">
                                <span>Slow Queries (<?= $timeRange ?>)</span>
                                <span class="metric-value"><?= count($summary['slow_queries']) ?></span>
                            </div>
                            <div style="max-height: 200px; overflow-y: auto;">
                                <?php foreach (array_slice($summary['slow_queries'], 0, 5) as $query): ?>
                                    <div class="alert alert-warning" style="margin-bottom: 0.5rem;">
                                        <strong><?= $query['duration'] ?>ms</strong> - <?= $query['query_type'] ?><br>
                                        <small><?= htmlspecialchars(substr($query['query_preview'] ?? '', 0, 100)) ?>...</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="metric">
                                <span>Slow Queries</span>
                                <span class="metric-value">
                                    <span class="status-indicator status-ok"></span>
                                    None detected
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- System Health Card -->
                    <div class="card">
                        <h3>🏥 System Health</h3>
                        <div id="systemHealth">
                            <div class="metric">
                                <span>Overall Status</span>
                                <span class="metric-value">
                                    <span class="status-indicator status-ok"></span>
                                    Healthy
                                </span>
                            </div>
                            <div class="metric">
                                <span>Last Updated</span>
                                <span class="metric-value"><?= $summary['last_updated'] ?? 'N/A' ?></span>
                            </div>
                        </div>
                        <button class="btn btn-small" onclick="checkHealth()">🔍 Detailed Health Check</button>
                    </div>

                    <!-- Recent Alerts Card -->
                    <div class="card">
                        <h3>🚨 Recent Alerts</h3>
                        <div id="alertsList">
                            <?php if (!empty($alerts)): ?>
                                <?php foreach (array_slice($alerts, 0, 5) as $alert): ?>
                                    <div class="alert alert-<?= $alert['alert_level'] ?>">
                                        <strong><?= ucfirst($alert['alert_level']) ?>:</strong>
                                        <?= htmlspecialchars($alert['message']) ?><br>
                                        <small><?= $alert['created_at'] ?></small>
                                        <button class="btn btn-small" onclick="resolveAlert(<?= $alert['id'] ?>)" style="float: right;">
                                            Resolve
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                                <button class="btn" onclick="viewAllAlerts()">View All Alerts</button>
                            <?php else: ?>
                                <p>No recent alerts 🎉</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let refreshInterval;
                let countdownTimer = 60;

                // Auto-refresh functionality
                function startAutoRefresh() {
                    refreshInterval = setInterval(refreshData, 60000); // Refresh every minute
                    startCountdown();
                }

                function startCountdown() {
                    const timerElement = document.getElementById('timer');
                    countdownTimer = 60;

                    const countdown = setInterval(() => {
                        countdownTimer--;
                        if (timerElement) timerElement.textContent = countdownTimer;

                        if (countdownTimer <= 0) {
                            clearInterval(countdown);
                            startCountdown();
                        }
                    }, 1000);
                }

                function refreshData() {
                    const timeRange = document.getElementById('timeRange').value;
                    fetch(`?action=api&range=${timeRange}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateDashboard(data.data);
                            }
                        })
                        .catch(error => console.error('Error refreshing data:', error));
                }

                function updateDashboard(data) {
                    // Update dashboard with new data
                    console.log('Dashboard updated', data);
                }

                function changeTimeRange() {
                    const timeRange = document.getElementById('timeRange').value;
                    window.location.href = `?range=${timeRange}`;
                }

                function exportData(format) {
                    const timeRange = document.getElementById('timeRange').value;
                    window.open(`?action=export&format=${format}&range=${timeRange}`, '_blank');
                }

                function resolveAlert(alertId) {
                    fetch('?action=resolve-alert', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ alert_id: alertId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshData();
                        } else {
                            alert('Failed to resolve alert: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error resolving alert:', error);
                        alert('Failed to resolve alert');
                    });
                }

                function checkHealth() {
                    fetch('?action=health')
                        .then(response => response.json())
                        .then(data => {
                            const healthInfo = JSON.stringify(data, null, 2);
                            alert('System Health Check:\n\n' + healthInfo);
                        })
                        .catch(error => {
                            console.error('Error checking health:', error);
                            alert('Failed to check system health');
                        });
                }

                function viewAllAlerts() {
                    window.open('?action=alerts', '_blank');
                }

                // Start auto-refresh when page loads
                document.addEventListener('DOMContentLoaded', startAutoRefresh);
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Render error page
     * Private helper method
     *
     * @param string $message Error message to display
     */
    private function renderError($message)
    {
        http_response_code(500);
        echo "<h1>Performance Dashboard Error</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
        echo "<p><a href='?'>← Back to Dashboard</a></p>";
    }

    /**
     * Export data as JSON
     * Private helper method for JSON export
     *
     * @param array $data Performance data to export
     */
    private function exportJson($data)
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="performance-metrics-' . date('Y-m-d-H-i') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Export data as CSV
     * Private helper method for CSV export
     *
     * @param array $data Performance data to export
     */
    private function exportCsv($data)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="performance-metrics-' . date('Y-m-d-H-i') . '.csv"');

        $output = fopen('php://output', 'w');

        // Metrics CSV
        fputcsv($output, ['Type', 'Name', 'Count', 'Min Value', 'Max Value', 'Avg Value', 'Unit']);
        foreach ($data['metrics'] as $metric) {
            fputcsv($output, [
                $metric['metric_type'],
                $metric['metric_name'],
                $metric['count'],
                $metric['min_value'],
                $metric['max_value'],
                $metric['avg_value'],
                $metric['unit']
            ]);
        }

        fclose($output);
    }

    /**
     * Export data as Excel (placeholder)
     * Private helper method - currently exports as CSV
     *
     * @param array $data Performance data to export
     */
    private function exportExcel($data)
    {
        // For now, export as CSV (can be enhanced with PHPExcel/PhpSpreadsheet later)
        $this->exportCsv($data);
    }

    /**
     * Parse memory limit string to bytes
     * Private helper method for memory calculations
     *
     * @param string $limit Memory limit string (e.g., '128M', '1G')
     * @return int Memory limit in bytes
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
}

// Handle dashboard requests (backward compatibility)
if (php_sapi_name() === 'cli-server' || basename($_SERVER['SCRIPT_NAME']) === 'PerformanceDashboardController.php') {
    // Include database connection
    require_once __DIR__ . '/../../server.php';

    $controller = new PerformanceDashboardController($mysqli);

    $action = $_GET['action'] ?? 'index';

    switch ($action) {
        case 'api':
            $controller->getMetricsApi();
            break;
        case 'alerts':
            $controller->getAlertsApi();
            break;
        case 'resolve-alert':
            $controller->resolveAlert();
            break;
        case 'export':
            $controller->exportData();
            break;
        case 'health':
            $controller->healthCheck();
            break;
        default:
            $controller->index();
    }
}
