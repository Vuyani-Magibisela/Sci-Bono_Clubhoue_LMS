<?php
/**
 * DeprecationMonitorService
 *
 * Service for monitoring deprecated file usage by parsing error logs
 *
 * Phase 4 Week 4 Day 4: Deprecated File Monitoring Dashboard
 * Created: January 5, 2026
 */

require_once __DIR__ . '/BaseService.php';

class DeprecationMonitorService extends BaseService {

    private $logFile;
    private $deprecatedFiles = [
        'addPrograms.php',
        'holidayProgramLoginC.php',
        'send-profile-email.php',
        'sessionTimer.php',
        'attendance_routes.php'
    ];

    public function __construct($conn) {
        parent::__construct($conn);

        // Determine PHP error log location
        $this->logFile = $this->getErrorLogPath();
    }

    /**
     * Get the PHP error log file path
     */
    private function getErrorLogPath() {
        // Try common locations
        $possiblePaths = [
            ini_get('error_log'),
            '/var/log/php_errors.log',
            '/var/log/php/error.log',
            '/tmp/php_errors.log',
            __DIR__ . '/../../storage/logs/php_errors.log'
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get deprecation statistics for all deprecated files
     *
     * @param int $days Number of days to look back (default: 30)
     * @return array Deprecation statistics
     */
    public function getDeprecationStats($days = 30) {
        if (!$this->logFile) {
            return [
                'error' => 'Error log file not found or not readable',
                'total_hits' => 0,
                'files' => [],
                'recent_hits' => []
            ];
        }

        $cutoffDate = strtotime("-{$days} days");
        $stats = [
            'total_hits' => 0,
            'files' => [],
            'recent_hits' => [],
            'log_file' => $this->logFile
        ];

        // Initialize file stats
        foreach ($this->deprecatedFiles as $file) {
            $stats['files'][$file] = [
                'name' => $file,
                'hit_count' => 0,
                'last_accessed' => null,
                'unique_ips' => [],
                'urls' => []
            ];
        }

        // Parse log file
        $entries = $this->parseLogFile($cutoffDate);

        foreach ($entries as $entry) {
            $file = $entry['file'];

            if (isset($stats['files'][$file])) {
                $stats['files'][$file]['hit_count']++;
                $stats['total_hits']++;

                // Track unique IPs
                if (!in_array($entry['ip'], $stats['files'][$file]['unique_ips'])) {
                    $stats['files'][$file]['unique_ips'][] = $entry['ip'];
                }

                // Track unique URLs
                if (!in_array($entry['url'], $stats['files'][$file]['urls'])) {
                    $stats['files'][$file]['urls'][] = $entry['url'];
                }

                // Update last accessed time
                if (!$stats['files'][$file]['last_accessed'] ||
                    $entry['timestamp'] > $stats['files'][$file]['last_accessed']) {
                    $stats['files'][$file]['last_accessed'] = $entry['timestamp'];
                }

                // Add to recent hits (keep last 100)
                if (count($stats['recent_hits']) < 100) {
                    $stats['recent_hits'][] = $entry;
                }
            }
        }

        // Convert unique_ips arrays to counts
        foreach ($stats['files'] as $file => &$data) {
            $data['unique_ip_count'] = count($data['unique_ips']);
            $data['unique_url_count'] = count($data['urls']);
            unset($data['unique_ips']); // Remove IPs from response for privacy
        }

        // Sort files by hit count (descending)
        uasort($stats['files'], function($a, $b) {
            return $b['hit_count'] - $a['hit_count'];
        });

        return $stats;
    }

    /**
     * Parse error log file for deprecation entries
     *
     * @param int $cutoffDate Unix timestamp for oldest entry to include
     * @return array Parsed log entries
     */
    private function parseLogFile($cutoffDate) {
        $entries = [];

        if (!$this->logFile || !file_exists($this->logFile)) {
            return $entries;
        }

        // Read file in reverse (most recent first) for efficiency
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines) {
            return $entries;
        }

        // Reverse to process newest first
        $lines = array_reverse($lines);

        foreach ($lines as $line) {
            // Look for DEPRECATED tag in log entries
            if (strpos($line, '[DEPRECATED]') === false) {
                continue;
            }

            $entry = $this->parseLogLine($line);

            if ($entry && $entry['timestamp'] >= $cutoffDate) {
                $entries[] = $entry;
            } elseif ($entry && $entry['timestamp'] < $cutoffDate) {
                // Stop processing once we hit old entries (log is reversed)
                break;
            }
        }

        return array_reverse($entries); // Return in chronological order
    }

    /**
     * Parse a single log line for deprecation information
     *
     * @param string $line Log line
     * @return array|null Parsed entry or null if not a deprecation log
     */
    private function parseLogLine($line) {
        // Expected format: [timestamp] [DEPRECATED] filename.php is deprecated. Use ControllerName instead. Called from: /path | IP: x.x.x.x

        // Extract timestamp (PHP error log format: [dd-Mon-yyyy hh:mm:ss timezone])
        if (preg_match('/\[([^\]]+)\]/', $line, $timestampMatch)) {
            $timestamp = strtotime($timestampMatch[1]);
        } else {
            $timestamp = time(); // Fallback to current time if can't parse
        }

        // Extract deprecated filename
        $file = null;
        foreach ($this->deprecatedFiles as $deprecatedFile) {
            if (strpos($line, $deprecatedFile) !== false) {
                $file = $deprecatedFile;
                break;
            }
        }

        if (!$file) {
            return null;
        }

        // Extract URL (Called from: /path)
        $url = 'unknown';
        if (preg_match('/Called from: ([^\|]+)/', $line, $urlMatch)) {
            $url = trim($urlMatch[1]);
        }

        // Extract IP address
        $ip = 'unknown';
        if (preg_match('/IP: ([\d\.]+)/', $line, $ipMatch)) {
            $ip = $ipMatch[1];
        }

        return [
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s', $timestamp),
            'file' => $file,
            'url' => $url,
            'ip' => $ip,
            'raw_line' => $line
        ];
    }

    /**
     * Get deprecation statistics grouped by date
     *
     * @param int $days Number of days to analyze
     * @return array Statistics by date
     */
    public function getStatsByDate($days = 30) {
        $stats = $this->getDeprecationStats($days);
        $byDate = [];

        foreach ($stats['recent_hits'] as $entry) {
            $date = date('Y-m-d', $entry['timestamp']);

            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'date' => $date,
                    'total_hits' => 0,
                    'files' => []
                ];
            }

            $byDate[$date]['total_hits']++;

            if (!isset($byDate[$date]['files'][$entry['file']])) {
                $byDate[$date]['files'][$entry['file']] = 0;
            }

            $byDate[$date]['files'][$entry['file']]++;
        }

        // Sort by date (newest first)
        krsort($byDate);

        return array_values($byDate);
    }

    /**
     * Check if any deprecated files have been accessed recently
     *
     * @param int $hours Number of hours to check (default: 24)
     * @return bool True if deprecated files accessed recently
     */
    public function hasRecentActivity($hours = 24) {
        $cutoffDate = strtotime("-{$hours} hours");
        $entries = $this->parseLogFile($cutoffDate);

        return count($entries) > 0;
    }

    /**
     * Get recommended actions based on deprecation statistics
     *
     * @return array Recommendations
     */
    public function getRecommendations() {
        $stats = $this->getDeprecationStats(30);
        $recommendations = [];

        foreach ($stats['files'] as $file => $data) {
            if ($data['hit_count'] === 0) {
                $recommendations[] = [
                    'file' => $file,
                    'status' => 'safe_to_remove',
                    'message' => "No activity in 30 days. Safe to remove.",
                    'priority' => 'low'
                ];
            } elseif ($data['hit_count'] < 10) {
                $recommendations[] = [
                    'file' => $file,
                    'status' => 'low_usage',
                    'message' => "Low usage ({$data['hit_count']} hits in 30 days). Monitor and plan removal.",
                    'priority' => 'medium'
                ];
            } else {
                $recommendations[] = [
                    'file' => $file,
                    'status' => 'active_usage',
                    'message' => "Active usage ({$data['hit_count']} hits in 30 days). Migration needed before removal.",
                    'priority' => 'high'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Export deprecation statistics to CSV
     *
     * @param int $days Number of days to include
     * @return string CSV content
     */
    public function exportToCsv($days = 30) {
        $stats = $this->getDeprecationStats($days);

        $csv = "File,Hit Count,Last Accessed,Unique URLs,Status\n";

        foreach ($stats['files'] as $file => $data) {
            $lastAccessed = $data['last_accessed'] ? date('Y-m-d H:i:s', $data['last_accessed']) : 'Never';
            $status = $data['hit_count'] === 0 ? 'Safe to Remove' : ($data['hit_count'] < 10 ? 'Low Usage' : 'Active');

            $csv .= sprintf(
                "\"%s\",%d,\"%s\",%d,\"%s\"\n",
                $file,
                $data['hit_count'],
                $lastAccessed,
                $data['unique_url_count'],
                $status
            );
        }

        return $csv;
    }
}
