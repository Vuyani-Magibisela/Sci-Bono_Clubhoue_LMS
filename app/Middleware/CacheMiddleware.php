<?php
/**
 * Cache Middleware - HTTP caching with ETags and conditional requests
 *
 * Implements HTTP caching per RFC 7234 and RFC 7232:
 * - ETag generation for responses
 * - Cache-Control headers
 * - Conditional requests (If-None-Match, If-Modified-Since)
 * - 304 Not Modified responses
 *
 * Phase 5 Week 3 Day 1
 *
 * @package App\Middleware
 * @since Phase 5 Week 3
 */

namespace App\Middleware;

require_once __DIR__ . '/../Utils/CacheHelper.php';
require_once __DIR__ . '/../../core/Logger.php';

use App\Utils\CacheHelper;
use Logger;

class CacheMiddleware
{
    private $db;
    private $enabled;
    private $config;

    /**
     * Constructor
     *
     * @param mixed $db Database connection (MySQLi)
     * @param array $config Cache configuration
     */
    public function __construct($db, $config = [])
    {
        $this->db = $db;
        $this->enabled = $config['enabled'] ?? true;
        $this->config = $config;
    }

    /**
     * Handle request caching (before controller)
     *
     * Checks for conditional request headers and returns 304 if appropriate.
     *
     * @param string $endpoint Request endpoint
     * @param string $method HTTP method
     * @return bool True if request should continue, false if 304 returned
     */
    public function handleRequest($endpoint, $method = 'GET')
    {
        // Skip if caching disabled
        if (!$this->enabled) {
            return true;
        }

        // Skip if endpoint shouldn't be cached
        if (!CacheHelper::shouldCache($endpoint, $method)) {
            return true;
        }

        // Get conditional request headers
        $ifNoneMatch = $this->getHeader('If-None-Match');
        $ifModifiedSince = $this->getHeader('If-Modified-Since');

        // If no conditional headers, continue processing
        if (!$ifNoneMatch && !$ifModifiedSince) {
            return true;
        }

        // Get current resource ETag and last modified from database
        $resourceInfo = $this->getResourceInfo($endpoint);

        if (!$resourceInfo) {
            // Resource info not found, continue processing
            return true;
        }

        // Check ETag match (If-None-Match)
        if ($ifNoneMatch && isset($resourceInfo['etag'])) {
            if (CacheHelper::etagsMatch($ifNoneMatch, $resourceInfo['etag'])) {
                $this->sendNotModified($resourceInfo);
                return false;
            }
        }

        // Check last modified (If-Modified-Since)
        if ($ifModifiedSince && isset($resourceInfo['last_modified'])) {
            $ifModifiedSinceTime = CacheHelper::parseIfModifiedSince($ifModifiedSince);

            if ($ifModifiedSinceTime && !CacheHelper::wasModifiedSince($resourceInfo['last_modified'], $ifModifiedSinceTime)) {
                $this->sendNotModified($resourceInfo);
                return false;
            }
        }

        // Resource was modified, continue processing
        return true;
    }

    /**
     * Handle response caching (after controller)
     *
     * Adds cache headers to response.
     *
     * @param string $endpoint Request endpoint
     * @param string $method HTTP method
     * @param string $responseBody Response body
     * @param int $statusCode HTTP status code
     * @param array $options Additional options
     * @return void
     */
    public function handleResponse($endpoint, $method, $responseBody, $statusCode = 200, $options = [])
    {
        // Skip if caching disabled
        if (!$this->enabled) {
            return;
        }

        // Skip if endpoint shouldn't be cached
        if (!CacheHelper::shouldCache($endpoint, $method)) {
            // Add no-cache headers
            $this->addNoCacheHeaders();
            return;
        }

        // Skip if status code is not cacheable
        if (!CacheHelper::isCacheableStatus($statusCode)) {
            $this->addNoCacheHeaders();
            return;
        }

        // Generate ETag for response
        $etag = CacheHelper::generateETag($responseBody);

        // Get cache configuration for endpoint
        $cacheConfig = CacheHelper::getCacheConfig($endpoint, $method);

        // Override with custom config if provided
        if (isset($options['cache_config'])) {
            $cacheConfig = array_merge($cacheConfig, $options['cache_config']);
        }

        // Generate Cache-Control header
        $cacheControl = CacheHelper::generateCacheControl($cacheConfig);

        // Add cache headers
        if (!headers_sent()) {
            header('ETag: ' . $etag);
            header('Cache-Control: ' . $cacheControl);

            // Add Last-Modified if available
            if (isset($options['last_modified'])) {
                $lastModified = CacheHelper::formatLastModified($options['last_modified']);
                header('Last-Modified: ' . $lastModified);
            } else {
                // Use current time as last modified
                $lastModified = CacheHelper::formatLastModified(time());
                header('Last-Modified: ' . $lastModified);
            }

            // Add Vary header
            $vary = CacheHelper::generateVary();
            header('Vary: ' . $vary);
        }

        // Store cache info in database for future conditional requests
        $this->storeResourceInfo($endpoint, $etag, $options['last_modified'] ?? time());
    }

    /**
     * Send 304 Not Modified response
     *
     * @param array $resourceInfo Resource information
     * @return void
     */
    private function sendNotModified($resourceInfo)
    {
        // Set 304 status code
        if (!headers_sent()) {
            http_response_code(304);

            // Add required headers for 304 response
            if (isset($resourceInfo['etag'])) {
                header('ETag: ' . $resourceInfo['etag']);
            }

            if (isset($resourceInfo['last_modified'])) {
                $lastModified = CacheHelper::formatLastModified($resourceInfo['last_modified']);
                header('Last-Modified: ' . $lastModified);
            }

            // Add Cache-Control header
            header('Cache-Control: public, max-age=300');

            // Add Vary header
            header('Vary: Accept, Accept-Encoding, Authorization');
        }

        // Send empty body
        exit;
    }

    /**
     * Add no-cache headers
     *
     * @return void
     */
    private function addNoCacheHeaders()
    {
        if (!headers_sent()) {
            $noCacheHeader = CacheHelper::invalidateCache();
            header('Cache-Control: ' . $noCacheHeader);
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    /**
     * Get resource information from database
     *
     * @param string $endpoint Request endpoint
     * @return array|null Resource info or null
     */
    private function getResourceInfo($endpoint)
    {
        try {
            // Create table if not exists
            $this->createCacheTable();

            $stmt = $this->db->prepare("
                SELECT etag, last_modified, updated_at
                FROM api_cache_info
                WHERE endpoint = ?
                AND expires_at > NOW()
                LIMIT 1
            ");

            $stmt->bind_param('s', $endpoint);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return null;
            }

            return $result->fetch_assoc();

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to get resource info', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store resource information in database
     *
     * @param string $endpoint Request endpoint
     * @param string $etag ETag value
     * @param int|string $lastModified Last modified timestamp
     * @return void
     */
    private function storeResourceInfo($endpoint, $etag, $lastModified)
    {
        try {
            // Create table if not exists
            $this->createCacheTable();

            // Convert timestamp to datetime
            if (is_numeric($lastModified)) {
                $lastModified = date('Y-m-d H:i:s', $lastModified);
            }

            // Calculate expiration (1 hour from now)
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $this->db->prepare("
                INSERT INTO api_cache_info (endpoint, etag, last_modified, expires_at, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    etag = VALUES(etag),
                    last_modified = VALUES(last_modified),
                    expires_at = VALUES(expires_at),
                    updated_at = NOW()
            ");

            $stmt->bind_param('ssss', $endpoint, $etag, $lastModified, $expiresAt);
            $stmt->execute();

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to store resource info', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate cache for endpoint
     *
     * @param string $endpoint Request endpoint
     * @return bool True on success
     */
    public function invalidateEndpoint($endpoint)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM api_cache_info WHERE endpoint = ?");
            $stmt->bind_param('s', $endpoint);
            return $stmt->execute();

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to invalidate endpoint', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache by pattern
     *
     * @param string $pattern Endpoint pattern (e.g., "/api/v1/users/%")
     * @return bool True on success
     */
    public function invalidatePattern($pattern)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM api_cache_info WHERE endpoint LIKE ?");
            $stmt->bind_param('s', $pattern);
            return $stmt->execute();

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to invalidate pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all expired cache entries
     *
     * @return int Number of entries deleted
     */
    public function clearExpired()
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM api_cache_info WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->affected_rows;

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to clear expired', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clear all cache entries
     *
     * @return bool True on success
     */
    public function clearAll()
    {
        try {
            $stmt = $this->db->prepare("TRUNCATE TABLE api_cache_info");
            return $stmt->execute();

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to clear all', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get request header
     *
     * @param string $name Header name
     * @return string|null Header value or null
     */
    private function getHeader($name)
    {
        // Try standard $_SERVER format
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }

        // Try direct format
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        // Try getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers[$name])) {
                return $headers[$name];
            }
        }

        return null;
    }

    /**
     * Create api_cache_info table
     *
     * @return bool True on success
     */
    private function createCacheTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS api_cache_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                endpoint VARCHAR(500) NOT NULL UNIQUE,
                etag VARCHAR(64) NOT NULL,
                last_modified DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_endpoint (endpoint),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            return $this->db->query($sql);

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to create table', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [];

            // Total cached endpoints
            $result = $this->db->query("SELECT COUNT(*) as total FROM api_cache_info");
            $stats['total_endpoints'] = $result->fetch_assoc()['total'];

            // Active (not expired) endpoints
            $result = $this->db->query("SELECT COUNT(*) as active FROM api_cache_info WHERE expires_at > NOW()");
            $stats['active_endpoints'] = $result->fetch_assoc()['active'];

            // Expired endpoints
            $stats['expired_endpoints'] = $stats['total_endpoints'] - $stats['active_endpoints'];

            // Most cached endpoints
            $result = $this->db->query("
                SELECT endpoint, updated_at
                FROM api_cache_info
                WHERE expires_at > NOW()
                ORDER BY updated_at DESC
                LIMIT 10
            ");

            $stats['recent_endpoints'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['recent_endpoints'][] = $row;
            }

            return $stats;

        } catch (\Exception $e) {
            Logger::error('Cache: Failed to get statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
