<?php
/**
 * Rate Limiting Middleware - Prevent abuse and brute force attacks
 * Phase 2 Implementation
 */

require_once __DIR__ . '/../../core/Logger.php';

class RateLimitMiddleware {
    private $conn;
    private $logger;
    private $limits = [
        'login' => ['requests' => 5, 'window' => 300],     // 5 attempts per 5 minutes
        'signup' => ['requests' => 3, 'window' => 3600],   // 3 signups per hour
        'api' => ['requests' => 60, 'window' => 60],       // 60 requests per minute
        'upload' => ['requests' => 10, 'window' => 300],   // 10 uploads per 5 minutes
        'default' => ['requests' => 30, 'window' => 60]    // 30 requests per minute
    ];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
        $this->createRateLimitTable();
    }
    
    public function handle($action = 'default') {
        $identifier = $this->getIdentifier();
        $limit = $this->limits[$action] ?? $this->limits['default'];

        // Get current request count and remaining requests
        $stats = $this->getRateLimitStats($identifier, $action, $limit);

        // Add rate limit headers to response
        $this->addRateLimitHeaders($limit['requests'], $stats['remaining'], $stats['reset']);

        if ($this->isRateLimited($identifier, $action, $limit)) {
            $this->handleRateLimit($action, $limit);
            return false;
        }

        $this->recordRequest($identifier, $action);
        return true;
    }
    
    private function getIdentifier() {
        $ip = $this->getRealIP();
        $userId = $_SESSION['user_id'] ?? null;
        
        // Use user ID if available, otherwise use IP
        return $userId ? "user_{$userId}" : "ip_{$ip}";
    }
    
    private function getRealIP() {
        // Handle various proxy scenarios
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    private function isRateLimited($identifier, $action, $limit) {
        $windowStart = time() - $limit['window'];
        
        $sql = "SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE identifier = ? 
                AND action = ? 
                AND timestamp > ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->logger->error('Rate limit database error', ['error' => $this->conn->error]);
            return false; // Fail open for database issues
        }
        
        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $isLimited = $row['request_count'] >= $limit['requests'];
        
        if ($isLimited) {
            $this->logger->warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'action' => $action,
                'requests' => $row['request_count'],
                'limit' => $limit['requests'],
                'window' => $limit['window'],
                'ip' => $this->getRealIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
        
        return $isLimited;
    }
    
    private function handleRateLimit($action, $limit) {
        $retryAfter = $limit['window'];
        $resetTime = time() + $retryAfter;

        // Add rate limit headers (remaining = 0 since limit exceeded)
        if (!headers_sent()) {
            header("X-RateLimit-Limit: {$limit['requests']}");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: {$resetTime}");
            header("Retry-After: {$retryAfter}");
        }

        http_response_code(429);

        if ($this->isAjaxRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode([
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'reset_at' => $resetTime,
                'limit' => $limit['requests'],
                'action' => $action
            ]);
        } else {
            $this->showRateLimitPage($retryAfter);
        }

        exit;
    }
    
    private function showRateLimitPage($retryAfter) {
        $minutes = ceil($retryAfter / 60);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Rate Limit Exceeded - Sci-Bono Clubhouse LMS</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .code { font-size: 80px; color: #ffc107; margin: 0; }
        .message { font-size: 24px; margin: 20px 0; }
        .description { color: #666; margin: 20px 0; }
        .back-link { display: inline-block; background: #F29A2E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="code">429</h1>
        <h2 class="message">Rate Limit Exceeded</h2>
        <p class="description">
            You have made too many requests. Please wait ' . $minutes . ' minute(s) before trying again.
        </p>
        <a href="/" class="back-link">Return Home</a>
    </div>
</body>
</html>';
    }
    
    private function recordRequest($identifier, $action) {
        $sql = "INSERT INTO rate_limits (identifier, action, timestamp, ip, user_agent) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logger->error('Failed to record rate limit entry', ['error' => $this->conn->error]);
            return;
        }
        
        $timestamp = time();
        $ip = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->bind_param("ssiss", $identifier, $action, $timestamp, $ip, $userAgent);
        $stmt->execute();
        
        // Occasionally clean up old records (1% chance)
        if (rand(1, 100) === 1) {
            $this->cleanupOldRecords();
        }
    }
    
    private function cleanupOldRecords() {
        $oldestWindow = max(array_column($this->limits, 'window'));
        $cutoffTime = time() - ($oldestWindow * 2); // Keep records for twice the longest window
        
        $sql = "DELETE FROM rate_limits WHERE timestamp < ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $cutoffTime);
            $stmt->execute();
        }
    }
    
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
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Get remaining requests for an identifier/action
     */
    public function getRemainingRequests($action = 'default') {
        $identifier = $this->getIdentifier();
        $limit = $this->limits[$action] ?? $this->limits['default'];
        $windowStart = time() - $limit['window'];

        $sql = "SELECT COUNT(*) as request_count
                FROM rate_limits
                WHERE identifier = ?
                AND action = ?
                AND timestamp > ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return max(0, $limit['requests'] - $row['request_count']);
    }

    /**
     * Get rate limit statistics (remaining, reset time)
     *
     * @param string $identifier User/IP identifier
     * @param string $action Rate limit action
     * @param array $limit Limit configuration
     * @return array ['remaining' => int, 'reset' => int, 'current' => int]
     */
    private function getRateLimitStats($identifier, $action, $limit) {
        $windowStart = time() - $limit['window'];
        $resetTime = time() + $limit['window'];

        $sql = "SELECT COUNT(*) as request_count
                FROM rate_limits
                WHERE identifier = ?
                AND action = ?
                AND timestamp > ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            // Fail gracefully
            return [
                'remaining' => $limit['requests'],
                'reset' => $resetTime,
                'current' => 0
            ];
        }

        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $currentRequests = (int)$row['request_count'];
        $remaining = max(0, $limit['requests'] - $currentRequests);

        return [
            'remaining' => $remaining,
            'reset' => $resetTime,
            'current' => $currentRequests
        ];
    }

    /**
     * Add rate limit headers to response
     *
     * Adds standard rate limit headers:
     * - X-RateLimit-Limit: Maximum requests allowed
     * - X-RateLimit-Remaining: Requests remaining in current window
     * - X-RateLimit-Reset: Unix timestamp when limit resets
     *
     * @param int $limit Maximum requests allowed
     * @param int $remaining Requests remaining
     * @param int $reset Reset timestamp
     */
    private function addRateLimitHeaders($limit, $remaining, $reset) {
        // Only add headers if not already sent (for testing compatibility)
        if (!headers_sent()) {
            header("X-RateLimit-Limit: {$limit}");
            header("X-RateLimit-Remaining: {$remaining}");
            header("X-RateLimit-Reset: {$reset}");
        }
    }
}