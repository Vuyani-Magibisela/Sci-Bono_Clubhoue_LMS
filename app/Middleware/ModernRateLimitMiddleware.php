<?php
/**
 * Modern Rate Limit Middleware - Router-compatible
 * Phase 3 Week 8 Implementation
 *
 * Prevents brute force attacks and abuse by limiting request rates
 * on sensitive endpoints like login, signup, and password reset.
 */

class ModernRateLimitMiddleware {
    private $action;
    private $logger;
    private $conn;

    /**
     * Rate limit definitions
     * Format: 'action' => ['requests' => max_requests, 'window' => time_window_in_seconds]
     */
    private $limits = [
        'login' => ['requests' => 5, 'window' => 300],      // 5 attempts per 5 minutes
        'signup' => ['requests' => 3, 'window' => 3600],    // 3 signups per hour
        'forgot' => ['requests' => 3, 'window' => 600],     // 3 requests per 10 minutes
        'reset' => ['requests' => 5, 'window' => 3600],     // 5 resets per hour
        'holiday' => ['requests' => 10, 'window' => 600],   // 10 attempts per 10 minutes
        'visitor' => ['requests' => 5, 'window' => 300],    // 5 registrations per 5 minutes
        'default' => ['requests' => 30, 'window' => 60]     // 30 requests per minute
    ];

    public function __construct($action = 'default') {
        global $conn;
        $this->conn = $conn;
        $this->action = $action;
        $this->logger = new Logger();
        $this->createRateLimitTable();
    }

    /**
     * Handle the middleware request
     *
     * @return bool Returns false if rate limited, true otherwise
     */
    public function handle() {
        $identifier = $this->getIdentifier();
        $limit = $this->limits[$this->action] ?? $this->limits['default'];

        if ($this->isRateLimited($identifier, $this->action, $limit)) {
            $this->handleRateLimit($this->action, $limit);
            return false;
        }

        $this->recordRequest($identifier, $this->action);
        return true;
    }

    /**
     * Get unique identifier for rate limiting
     * Uses user ID if authenticated, otherwise uses IP address
     *
     * @return string Unique identifier
     */
    private function getIdentifier() {
        $ip = $this->getRealIP();
        $userId = $_SESSION['user_id'] ?? null;
        return $userId ? "user_{$userId}" : "ip_{$ip}";
    }

    /**
     * Get real IP address (handles proxies and load balancers)
     *
     * @return string IP address
     */
    private function getRealIP() {
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // Real IP header
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        // Forwarded for (take first IP)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Check if identifier has exceeded rate limit
     *
     * @param string $identifier Unique identifier
     * @param string $action Action being rate limited
     * @param array $limit Limit configuration
     * @return bool True if rate limited, false otherwise
     */
    private function isRateLimited($identifier, $action, $limit) {
        $windowStart = time() - $limit['window'];

        $sql = "SELECT COUNT(*) as request_count
                FROM rate_limits
                WHERE identifier = ?
                AND action = ?
                AND timestamp > ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->logger->error('Rate limit check failed', ['error' => $this->conn->error]);
            return false; // Fail open - don't block if DB error
        }

        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['request_count'] >= $limit['requests'];
    }

    /**
     * Record a request for rate limiting
     *
     * @param string $identifier Unique identifier
     * @param string $action Action being tracked
     */
    private function recordRequest($identifier, $action) {
        $sql = "INSERT INTO rate_limits (identifier, action, timestamp, ip, user_agent)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->logger->error('Failed to record rate limit', ['error' => $this->conn->error]);
            return;
        }

        $timestamp = time();
        $ip = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt->bind_param("ssiss", $identifier, $action, $timestamp, $ip, $userAgent);
        $stmt->execute();

        // Cleanup old records (1% chance per request to avoid overhead)
        if (rand(1, 100) === 1) {
            $this->cleanupOldRecords();
        }
    }

    /**
     * Handle rate limit exceeded
     * Logs the event and returns 429 response
     *
     * @param string $action Action that exceeded limit
     * @param array $limit Limit configuration
     */
    private function handleRateLimit($action, $limit) {
        $retryAfter = $limit['window'];

        $this->logger->warning('Rate limit exceeded', [
            'action' => $action,
            'ip' => $this->getRealIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'retry_after' => $retryAfter,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);

        header("Retry-After: {$retryAfter}");
        http_response_code(429);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter
            ]);
        } else {
            $minutes = ceil($retryAfter / 60);
            require __DIR__ . '/../Views/errors/429.php';
        }

        exit;
    }

    /**
     * Clean up old rate limit records
     * Removes records older than the maximum time window
     */
    private function cleanupOldRecords() {
        // Find maximum window from all limits
        $maxWindow = max(array_column($this->limits, 'window'));
        $cutoffTime = time() - ($maxWindow * 2);

        $sql = "DELETE FROM rate_limits WHERE timestamp < ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $cutoffTime);
            $stmt->execute();
        }
    }

    /**
     * Create rate_limits table if it doesn't exist
     */
    private function createRateLimitTable() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp INT NOT NULL,
            ip VARCHAR(45),
            user_agent TEXT,
            INDEX idx_rate_limits (identifier, action, timestamp),
            INDEX idx_cleanup (timestamp)
        ) ENGINE=InnoDB";

        if (!$this->conn->query($sql)) {
            $this->logger->error('Failed to create rate_limits table', ['error' => $this->conn->error]);
        }
    }

    /**
     * Check if the current request is an AJAX request
     *
     * @return bool True if AJAX request, false otherwise
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
