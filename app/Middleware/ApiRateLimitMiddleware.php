<?php

namespace App\Middleware;

use App\Utils\ResponseHelper;
use App\Utils\Logger;
use Exception;

class ApiRateLimitMiddleware
{
    private $db;
    private $limits = [
        'default' => ['requests' => 100, 'window' => 3600],        // 100 per hour
        'auth' => ['requests' => 10, 'window' => 600],             // 10 per 10 minutes  
        'api' => ['requests' => 1000, 'window' => 3600],           // 1000 per hour
        'api_strict' => ['requests' => 60, 'window' => 60],        // 60 per minute
        'api_user' => ['requests' => 500, 'window' => 3600],       // 500 per hour per user
        'api_admin' => ['requests' => 2000, 'window' => 3600],     // 2000 per hour for admins
        'upload' => ['requests' => 20, 'window' => 300],           // 20 uploads per 5 minutes
        'search' => ['requests' => 200, 'window' => 3600],         // 200 searches per hour
        'bulk_operation' => ['requests' => 10, 'window' => 300]    // 10 bulk ops per 5 minutes
    ];
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->createRateLimitTable();
    }
    
    /**
     * Check rate limit for API endpoint
     */
    public static function check($db, $identifier, $type = 'default')
    {
        $instance = new self($db);
        return $instance->checkRateLimit($identifier, $type);
    }
    
    /**
     * Check rate limit and handle response
     */
    public function checkRateLimit($identifier, $type = 'default')
    {
        try {
            $limit = $this->limits[$type] ?? $this->limits['default'];
            
            if ($this->isRateLimited($identifier, $type, $limit)) {
                $remaining = $this->getRemainingTime($identifier, $type, $limit);
                
                // Log rate limit violation
                Logger::warning('API rate limit exceeded', [
                    'identifier' => $identifier,
                    'type' => $type,
                    'limit' => $limit['requests'],
                    'window' => $limit['window'],
                    'remaining_time' => $remaining,
                    'ip' => $this->getRealIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'endpoint' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
                
                ResponseHelper::rateLimitExceeded(
                    'Rate limit exceeded. Try again in ' . $this->formatTime($remaining),
                    $remaining
                );
                
                return false;
            }
            
            // Record the request
            $this->recordRequest($identifier, $type);
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Rate limit check failed: ' . $e->getMessage());
            // Fail open - allow request if rate limiting fails
            return true;
        }
    }
    
    /**
     * Check if identifier is rate limited
     */
    private function isRateLimited($identifier, $type, $limit)
    {
        $windowStart = time() - $limit['window'];
        
        $sql = "SELECT COUNT(*) as request_count 
                FROM api_rate_limits 
                WHERE identifier = ? 
                AND action_type = ? 
                AND timestamp > ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param("ssi", $identifier, $type, $windowStart);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['request_count'] >= $limit['requests'];
    }
    
    /**
     * Record API request for rate limiting
     */
    private function recordRequest($identifier, $type)
    {
        $sql = "INSERT INTO api_rate_limits (identifier, action_type, timestamp, ip, user_agent, endpoint, method) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare rate limit insert: ' . $this->db->error);
        }
        
        $timestamp = time();
        $ip = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        $stmt->bind_param("ssissss", $identifier, $type, $timestamp, $ip, $userAgent, $endpoint, $method);
        $stmt->execute();
        
        // Periodically cleanup old records (5% chance)
        if (rand(1, 20) === 1) {
            $this->cleanupOldRecords();
        }
    }
    
    /**
     * Get remaining time until rate limit resets
     */
    private function getRemainingTime($identifier, $type, $limit)
    {
        $sql = "SELECT MIN(timestamp) as oldest_timestamp 
                FROM api_rate_limits 
                WHERE identifier = ? 
                AND action_type = ? 
                ORDER BY timestamp ASC 
                LIMIT " . $limit['requests'];
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $identifier, $type);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row && $row['oldest_timestamp']) {
            return max(0, ($row['oldest_timestamp'] + $limit['window']) - time());
        }
        
        return $limit['window'];
    }
    
    /**
     * Get remaining requests for identifier
     */
    public function getRemainingRequests($identifier, $type = 'default')
    {
        $limit = $this->limits[$type] ?? $this->limits['default'];
        $windowStart = time() - $limit['window'];
        
        $sql = "SELECT COUNT(*) as request_count 
                FROM api_rate_limits 
                WHERE identifier = ? 
                AND action_type = ? 
                AND timestamp > ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $identifier, $type, $windowStart);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return max(0, $limit['requests'] - $row['request_count']);
    }
    
    /**
     * Get rate limit headers for API responses
     */
    public function getRateLimitHeaders($identifier, $type = 'default')
    {
        $limit = $this->limits[$type] ?? $this->limits['default'];
        $remaining = $this->getRemainingRequests($identifier, $type);
        $resetTime = time() + $limit['window'];
        
        return [
            'X-RateLimit-Limit' => $limit['requests'],
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime,
            'X-RateLimit-Window' => $limit['window']
        ];
    }
    
    /**
     * Set rate limit headers in response
     */
    public function setRateLimitHeaders($identifier, $type = 'default')
    {
        $headers = $this->getRateLimitHeaders($identifier, $type);
        
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }
    
    /**
     * Create API rate limit table
     */
    private function createRateLimitTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS api_rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            timestamp INT NOT NULL,
            ip VARCHAR(45),
            user_agent TEXT,
            endpoint VARCHAR(500),
            method VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rate_limit_check (identifier, action_type, timestamp),
            INDEX idx_cleanup (timestamp),
            INDEX idx_endpoint_stats (endpoint, timestamp)
        ) ENGINE=InnoDB";
        
        if (!$this->db->query($sql)) {
            Logger::error('Failed to create api_rate_limits table: ' . $this->db->error);
        }
    }
    
    /**
     * Clean up old rate limit records
     */
    private function cleanupOldRecords()
    {
        try {
            $maxWindow = max(array_column($this->limits, 'window'));
            $cutoffTime = time() - ($maxWindow * 2); // Keep records for twice the longest window
            
            $sql = "DELETE FROM api_rate_limits WHERE timestamp < ?";
            $stmt = $this->db->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $cutoffTime);
                $stmt->execute();
                
                $deletedRows = $stmt->affected_rows;
                if ($deletedRows > 0) {
                    Logger::info("Cleaned up {$deletedRows} old rate limit records");
                }
            }
        } catch (Exception $e) {
            Logger::error('Rate limit cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get real IP address handling proxies
     */
    private function getRealIP()
    {
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
    
    /**
     * Format time duration for human reading
     */
    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' second' . ($seconds == 1 ? '' : 's');
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return $minutes . ' minute' . ($minutes == 1 ? '' : 's');
        } else {
            $hours = ceil($seconds / 3600);
            return $hours . ' hour' . ($hours == 1 ? '' : 's');
        }
    }
    
    /**
     * Get API rate limit statistics
     */
    public function getStatistics($timeframe = 3600)
    {
        $windowStart = time() - $timeframe;
        
        $sql = "SELECT 
                    action_type,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT identifier) as unique_clients,
                    COUNT(DISTINCT ip) as unique_ips,
                    AVG(timestamp) as avg_timestamp
                FROM api_rate_limits 
                WHERE timestamp > ? 
                GROUP BY action_type 
                ORDER BY total_requests DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $windowStart);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $statistics = [];
        
        while ($row = $result->fetch_assoc()) {
            $statistics[] = $row;
        }
        
        return $statistics;
    }
    
    /**
     * Get rate limit configuration
     */
    public function getLimits()
    {
        return $this->limits;
    }
    
    /**
     * Update rate limit configuration
     */
    public function updateLimit($type, $requests, $window)
    {
        $this->limits[$type] = [
            'requests' => (int)$requests,
            'window' => (int)$window
        ];
        
        Logger::info('Rate limit updated', [
            'type' => $type,
            'requests' => $requests,
            'window' => $window
        ]);
    }
    
    /**
     * Check if request should be rate limited based on user role
     */
    public static function checkUserRateLimit($db, $userId, $userRole, $endpoint = 'default')
    {
        // Determine rate limit type based on user role and endpoint
        $type = 'api_user';
        
        if ($userRole === 'admin') {
            $type = 'api_admin';
        } elseif (strpos($endpoint, 'upload') !== false) {
            $type = 'upload';
        } elseif (strpos($endpoint, 'search') !== false) {
            $type = 'search';
        } elseif (strpos($endpoint, 'bulk') !== false) {
            $type = 'bulk_operation';
        }
        
        $identifier = "user_{$userId}";
        return self::check($db, $identifier, $type);
    }
    
    /**
     * Check IP-based rate limit (for unauthenticated requests)
     */
    public static function checkIpRateLimit($db, $endpoint = 'default')
    {
        $instance = new self($db);
        $ip = $instance->getRealIP();
        $identifier = "ip_{$ip}";
        
        $type = 'default';
        if (strpos($endpoint, 'auth') !== false) {
            $type = 'auth';
        } elseif (strpos($endpoint, 'api') !== false) {
            $type = 'api_strict';
        }
        
        return self::check($db, $identifier, $type);
    }
}