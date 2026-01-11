<?php
/**
 * API Logger
 *
 * Logs API requests and responses for monitoring, debugging, and analytics.
 * Tracks request details, response status, execution time, and errors.
 *
 * Phase 5 Week 3 Day 4
 *
 * @package App\Utils
 * @since Phase 5 Week 3
 */

namespace App\Utils;

class ApiLogger
{
    /**
     * @var \mysqli Database connection
     */
    private $db;

    /**
     * @var bool Enable/disable logging
     */
    private $enabled;

    /**
     * @var array Configuration options
     */
    private $config;

    /**
     * @var float Request start time
     */
    private $startTime;

    /**
     * @var array Request data
     */
    private $requestData = [];

    /**
     * @var array Default configuration
     */
    private $defaultConfig = [
        'enabled' => true,
        'log_request_body' => true,
        'log_response_body' => true,
        'log_headers' => true,
        'log_query_params' => true,
        'truncate_body_at' => 5000, // characters
        'exclude_paths' => ['/health', '/ping'],
        'exclude_methods' => [],
        'log_only_errors' => false, // Only log failed requests
        'retention_days' => 30 // Auto-delete logs older than this
    ];

    /**
     * Constructor
     *
     * @param \mysqli $db Database connection
     * @param array $config Configuration options
     */
    public function __construct($db, array $config = [])
    {
        $this->db = $db;
        $this->config = array_merge($this->defaultConfig, $config);
        $this->enabled = $this->config['enabled'];
        $this->startTime = microtime(true);
    }

    /**
     * Log incoming request
     *
     * @return int|null Log ID
     */
    public function logRequest()
    {
        if (!$this->enabled) {
            return null;
        }

        // Check if path should be excluded
        if ($this->shouldExcludePath()) {
            return null;
        }

        // Gather request data
        $this->requestData = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
            'query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $this->getClientIp(),
            'headers' => $this->config['log_headers'] ? $this->getRequestHeaders() : null,
            'body' => $this->config['log_request_body'] ? $this->getRequestBody() : null,
            'query_params' => $this->config['log_query_params'] ? $this->getQueryParams() : null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Don't log yet if only logging errors
        if ($this->config['log_only_errors']) {
            return null;
        }

        // Insert request log
        return $this->insertRequestLog();
    }

    /**
     * Log response
     *
     * @param int|null $logId Log ID from logRequest()
     * @param int $statusCode HTTP status code
     * @param mixed $responseBody Response body
     * @param array $additionalData Additional data to log
     */
    public function logResponse($logId, $statusCode, $responseBody = null, array $additionalData = [])
    {
        if (!$this->enabled) {
            return;
        }

        $duration = $this->getExecutionTime();

        // If only logging errors and this is not an error, skip
        if ($this->config['log_only_errors'] && $statusCode < 400) {
            return;
        }

        $responseData = [
            'status_code' => $statusCode,
            'response_body' => $this->config['log_response_body'] ? $this->truncateBody($responseBody) : null,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_peak_usage(true),
            'is_error' => $statusCode >= 400,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Merge additional data
        $responseData = array_merge($responseData, $additionalData);

        // Update existing log or create new one
        if ($logId) {
            $this->updateRequestLog($logId, $responseData);
        } else {
            // If we skipped request logging (log_only_errors), create log now
            $this->insertCompleteLog($responseData);
        }
    }

    /**
     * Log error
     *
     * @param int|null $logId Log ID
     * @param string $errorMessage Error message
     * @param array $errorContext Error context
     */
    public function logError($logId, $errorMessage, array $errorContext = [])
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'is_error' => true,
            'error_message' => $errorMessage,
            'error_context' => json_encode($errorContext),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($logId) {
            $this->updateRequestLog($logId, $errorData);
        } else {
            $this->insertCompleteLog($errorData);
        }
    }

    /**
     * Insert request log into database
     *
     * @return int|null Log ID
     */
    private function insertRequestLog()
    {
        $stmt = $this->db->prepare("
            INSERT INTO api_request_logs (
                method, uri, path, query_string, user_agent, ip_address,
                headers, body, query_params, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return null;
        }

        $headers = is_array($this->requestData['headers']) ? json_encode($this->requestData['headers']) : null;
        $body = $this->truncateBody($this->requestData['body']);
        $queryParams = is_array($this->requestData['query_params']) ? json_encode($this->requestData['query_params']) : null;

        $stmt->bind_param(
            'ssssssssss',
            $this->requestData['method'],
            $this->requestData['uri'],
            $this->requestData['path'],
            $this->requestData['query_string'],
            $this->requestData['user_agent'],
            $this->requestData['ip_address'],
            $headers,
            $body,
            $queryParams,
            $this->requestData['created_at']
        );

        $stmt->execute();
        $logId = $stmt->insert_id;
        $stmt->close();

        return $logId ?: null;
    }

    /**
     * Update request log with response data
     *
     * @param int $logId Log ID
     * @param array $data Response data
     */
    private function updateRequestLog($logId, array $data)
    {
        $fields = [];
        $values = [];
        $types = '';

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";

            if (is_array($value)) {
                $value = json_encode($value);
                $types .= 's';
            } elseif (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_bool($value)) {
                $value = $value ? 1 : 0;
                $types .= 'i';
            } else {
                $types .= 's';
            }

            $values[] = $value;
        }

        $values[] = $logId;
        $types .= 'i';

        $sql = "UPDATE api_request_logs SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return;
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Insert complete log (request + response)
     *
     * @param array $responseData Response data
     */
    private function insertCompleteLog(array $responseData)
    {
        $data = array_merge($this->requestData, $responseData);

        $stmt = $this->db->prepare("
            INSERT INTO api_request_logs (
                method, uri, path, query_string, user_agent, ip_address,
                headers, body, query_params, status_code, response_body,
                duration_ms, memory_usage, is_error, error_message, error_context, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return;
        }

        $headers = is_array($data['headers']) ? json_encode($data['headers']) : null;
        $body = $this->truncateBody($data['body'] ?? null);
        $queryParams = is_array($data['query_params']) ? json_encode($data['query_params']) : null;
        $responseBody = $this->truncateBody($data['response_body'] ?? null);
        $isError = ($data['is_error'] ?? false) ? 1 : 0;

        $stmt->bind_param(
            'sssssssssisdissss',
            $data['method'],
            $data['uri'],
            $data['path'],
            $data['query_string'],
            $data['user_agent'],
            $data['ip_address'],
            $headers,
            $body,
            $queryParams,
            $data['status_code'],
            $responseBody,
            $data['duration_ms'],
            $data['memory_usage'],
            $isError,
            $data['error_message'] ?? null,
            $data['error_context'] ?? null,
            $data['created_at']
        );

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Get request headers
     *
     * @return array
     */
    private function getRequestHeaders()
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get request body
     *
     * @return string|null
     */
    private function getRequestBody()
    {
        $body = file_get_contents('php://input');
        return !empty($body) ? $body : null;
    }

    /**
     * Get query parameters
     *
     * @return array|null
     */
    private function getQueryParams()
    {
        return !empty($_GET) ? $_GET : null;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    /**
     * Truncate body to configured length
     *
     * @param mixed $body Body content
     * @return string|null
     */
    private function truncateBody($body)
    {
        if (empty($body)) {
            return null;
        }

        if (is_array($body)) {
            $body = json_encode($body);
        }

        $maxLength = $this->config['truncate_body_at'];

        if (strlen($body) > $maxLength) {
            return substr($body, 0, $maxLength) . '... [truncated]';
        }

        return $body;
    }

    /**
     * Check if current path should be excluded
     *
     * @return bool
     */
    private function shouldExcludePath()
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        foreach ($this->config['exclude_paths'] as $excludePath) {
            if (strpos($path, $excludePath) !== false) {
                return true;
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (in_array($method, $this->config['exclude_methods'])) {
            return true;
        }

        return false;
    }

    /**
     * Get execution time since request start
     *
     * @return float Seconds
     */
    private function getExecutionTime()
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Get recent logs
     *
     * @param int $limit Number of logs
     * @param array $filters Filters (e.g., ['is_error' => true])
     * @return array
     */
    public function getRecentLogs($limit = 100, array $filters = [])
    {
        $where = [];
        $params = [];
        $types = '';

        foreach ($filters as $key => $value) {
            if (is_bool($value)) {
                $where[] = "$key = ?";
                $params[] = $value ? 1 : 0;
                $types .= 'i';
            } elseif (is_int($value)) {
                $where[] = "$key = ?";
                $params[] = $value;
                $types .= 'i';
            } else {
                $where[] = "$key = ?";
                $params[] = $value;
                $types .= 's';
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM api_request_logs $whereClause ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $logs;
    }

    /**
     * Get performance statistics
     *
     * @param int $hours Hours to analyze (default: 24)
     * @return array
     */
    public function getPerformanceStats($hours = 24)
    {
        $sql = "
            SELECT
                COUNT(*) as total_requests,
                AVG(duration_ms) as avg_duration,
                MIN(duration_ms) as min_duration,
                MAX(duration_ms) as max_duration,
                SUM(CASE WHEN is_error = 1 THEN 1 ELSE 0 END) as error_count,
                SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as success_count,
                AVG(memory_usage) as avg_memory
            FROM api_request_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $hours);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        return [
            'total_requests' => (int)$stats['total_requests'],
            'avg_duration_ms' => round($stats['avg_duration'], 2),
            'min_duration_ms' => round($stats['min_duration'], 2),
            'max_duration_ms' => round($stats['max_duration'], 2),
            'error_count' => (int)$stats['error_count'],
            'success_count' => (int)$stats['success_count'],
            'error_rate' => $stats['total_requests'] > 0
                ? round(($stats['error_count'] / $stats['total_requests']) * 100, 2)
                : 0,
            'avg_memory_mb' => round($stats['avg_memory'] / 1024 / 1024, 2)
        ];
    }

    /**
     * Clean up old logs
     *
     * @return int Number of logs deleted
     */
    public function cleanup()
    {
        $days = $this->config['retention_days'];

        $stmt = $this->db->prepare("
            DELETE FROM api_request_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->bind_param('i', $days);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return $deleted;
    }

    /**
     * Enable logging
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disable logging
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
