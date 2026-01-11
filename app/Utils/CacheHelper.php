<?php
/**
 * Cache Helper - Utilities for HTTP caching
 *
 * Provides helper methods for:
 * - ETag generation
 * - Cache-Control header formatting
 * - Last-Modified timestamps
 * - Conditional request validation
 *
 * Phase 5 Week 3 Day 1
 *
 * @package App\Utils
 * @since Phase 5 Week 3
 */

namespace App\Utils;

class CacheHelper
{
    /**
     * Generate ETag for content
     *
     * Creates a strong ETag (MD5 hash) for the given content.
     * ETags are used for cache validation in conditional requests.
     *
     * @param string|array $content Content to hash (arrays are JSON-encoded)
     * @param bool $weak Whether to generate a weak ETag (default: false)
     * @return string ETag value (e.g., "abc123" or W/"abc123")
     */
    public static function generateETag($content, $weak = false)
    {
        // Convert arrays to JSON for consistent hashing
        if (is_array($content)) {
            $content = json_encode($content);
        }

        // Generate MD5 hash
        $hash = md5($content);

        // Return weak or strong ETag
        return $weak ? 'W/"' . $hash . '"' : '"' . $hash . '"';
    }

    /**
     * Parse ETag from header value
     *
     * Extracts the ETag value from the If-None-Match header.
     *
     * @param string $headerValue If-None-Match header value
     * @return string|null Parsed ETag or null
     */
    public static function parseETag($headerValue)
    {
        if (empty($headerValue)) {
            return null;
        }

        // Remove W/ prefix if present (weak ETag)
        $etag = preg_replace('/^W\//', '', $headerValue);

        // Remove quotes
        $etag = trim($etag, '"');

        return $etag ?: null;
    }

    /**
     * Check if ETags match
     *
     * Compares two ETags, handling both weak and strong ETags.
     *
     * @param string $etag1 First ETag
     * @param string $etag2 Second ETag
     * @return bool True if ETags match
     */
    public static function etagsMatch($etag1, $etag2)
    {
        if (empty($etag1) || empty($etag2)) {
            return false;
        }

        // Normalize both ETags (remove W/ and quotes)
        $normalized1 = self::parseETag($etag1);
        $normalized2 = self::parseETag($etag2);

        return $normalized1 === $normalized2;
    }

    /**
     * Generate Cache-Control header value
     *
     * Creates a Cache-Control header value based on configuration.
     *
     * @param array $config Cache configuration:
     *                      - max_age: Max age in seconds
     *                      - s_maxage: Shared cache max age
     *                      - private: Whether cache is private (default: false)
     *                      - public: Whether cache is public (default: true)
     *                      - no_cache: Whether to disable caching (default: false)
     *                      - no_store: Whether to prevent storage (default: false)
     *                      - must_revalidate: Whether to require revalidation (default: false)
     * @return string Cache-Control header value
     */
    public static function generateCacheControl($config = [])
    {
        $directives = [];

        // No caching
        if ($config['no_cache'] ?? false) {
            $directives[] = 'no-cache';
        }

        if ($config['no_store'] ?? false) {
            $directives[] = 'no-store';
        }

        // Public/private
        if ($config['private'] ?? false) {
            $directives[] = 'private';
        } elseif ($config['public'] ?? true) {
            $directives[] = 'public';
        }

        // Max age
        if (isset($config['max_age']) && is_numeric($config['max_age'])) {
            $directives[] = 'max-age=' . (int)$config['max_age'];
        }

        // Shared cache max age
        if (isset($config['s_maxage']) && is_numeric($config['s_maxage'])) {
            $directives[] = 's-maxage=' . (int)$config['s_maxage'];
        }

        // Revalidation
        if ($config['must_revalidate'] ?? false) {
            $directives[] = 'must-revalidate';
        }

        return implode(', ', $directives);
    }

    /**
     * Format Last-Modified timestamp
     *
     * Converts a timestamp to HTTP date format for Last-Modified header.
     *
     * @param int|string $timestamp Unix timestamp or datetime string
     * @return string HTTP date (e.g., "Thu, 09 Jan 2026 12:00:00 GMT")
     */
    public static function formatLastModified($timestamp)
    {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    /**
     * Parse If-Modified-Since header
     *
     * @param string $headerValue If-Modified-Since header value
     * @return int|null Unix timestamp or null
     */
    public static function parseIfModifiedSince($headerValue)
    {
        if (empty($headerValue)) {
            return null;
        }

        $timestamp = strtotime($headerValue);
        return $timestamp !== false ? $timestamp : null;
    }

    /**
     * Check if resource was modified since timestamp
     *
     * @param int|string $lastModified Resource last modified timestamp
     * @param int|string $ifModifiedSince If-Modified-Since timestamp
     * @return bool True if resource was modified
     */
    public static function wasModifiedSince($lastModified, $ifModifiedSince)
    {
        if (is_string($lastModified)) {
            $lastModified = strtotime($lastModified);
        }

        if (is_string($ifModifiedSince)) {
            $ifModifiedSince = strtotime($ifModifiedSince);
        }

        // Resource was modified if last modified is after if-modified-since
        return $lastModified > $ifModifiedSince;
    }

    /**
     * Get cache configuration for endpoint
     *
     * Returns appropriate cache settings based on endpoint type.
     *
     * @param string $endpoint Endpoint path (e.g., "/api/v1/users")
     * @param string $method HTTP method (GET, POST, etc.)
     * @return array Cache configuration
     */
    public static function getCacheConfig($endpoint, $method = 'GET')
    {
        // No caching for non-GET requests
        if ($method !== 'GET') {
            return [
                'no_cache' => true,
                'no_store' => true
            ];
        }

        // User-specific endpoints (private cache)
        if (strpos($endpoint, '/users/me') !== false || strpos($endpoint, '/profile') !== false) {
            return [
                'private' => true,
                'max_age' => 300,        // 5 minutes
                'must_revalidate' => true
            ];
        }

        // List endpoints (public, shorter cache)
        if (preg_match('/\/(users|courses|programs)$/', $endpoint)) {
            return [
                'public' => true,
                'max_age' => 60,         // 1 minute
                's_maxage' => 300        // 5 minutes for shared caches
            ];
        }

        // Detail endpoints (public, longer cache)
        if (preg_match('/\/(users|courses|programs)\/\d+$/', $endpoint)) {
            return [
                'public' => true,
                'max_age' => 600,        // 10 minutes
                's_maxage' => 1800       // 30 minutes for shared caches
            ];
        }

        // Authentication endpoints (no cache)
        if (strpos($endpoint, '/auth/') !== false) {
            return [
                'no_cache' => true,
                'no_store' => true,
                'private' => true
            ];
        }

        // Default: public cache with moderate duration
        return [
            'public' => true,
            'max_age' => 300,            // 5 minutes
            'must_revalidate' => true
        ];
    }

    /**
     * Check if endpoint should be cached
     *
     * @param string $endpoint Endpoint path
     * @param string $method HTTP method
     * @return bool True if endpoint should be cached
     */
    public static function shouldCache($endpoint, $method = 'GET')
    {
        // Only cache GET requests
        if ($method !== 'GET') {
            return false;
        }

        // Don't cache authentication endpoints
        if (strpos($endpoint, '/auth/') !== false) {
            return false;
        }

        // Don't cache admin modification endpoints
        if (strpos($endpoint, '/admin/') !== false && preg_match('/(create|update|delete)/', $endpoint)) {
            return false;
        }

        return true;
    }

    /**
     * Generate Vary header value
     *
     * The Vary header indicates which request headers affect the response.
     *
     * @param array $headers Headers that affect the response
     * @return string Vary header value
     */
    public static function generateVary($headers = ['Accept', 'Accept-Encoding', 'Authorization'])
    {
        return implode(', ', $headers);
    }

    /**
     * Check if response is cacheable based on status code
     *
     * @param int $statusCode HTTP status code
     * @return bool True if response is cacheable
     */
    public static function isCacheableStatus($statusCode)
    {
        // Cacheable status codes per RFC 7231
        $cacheableStatuses = [
            200, // OK
            203, // Non-Authoritative Information
            204, // No Content
            206, // Partial Content
            300, // Multiple Choices
            301, // Moved Permanently
            404, // Not Found (can be cached)
            405, // Method Not Allowed
            410, // Gone
            414, // URI Too Long
            501  // Not Implemented
        ];

        return in_array($statusCode, $cacheableStatuses);
    }

    /**
     * Invalidate cache for resource
     *
     * Returns a Cache-Control header that invalidates caches.
     *
     * @return string Cache-Control header value
     */
    public static function invalidateCache()
    {
        return 'no-cache, no-store, must-revalidate, max-age=0';
    }

    /**
     * Get cache key for request
     *
     * Generates a unique cache key based on request parameters.
     *
     * @param string $endpoint Endpoint path
     * @param array $params Query parameters
     * @param int|null $userId User ID (for user-specific caching)
     * @return string Cache key
     */
    public static function getCacheKey($endpoint, $params = [], $userId = null)
    {
        // Sort params for consistent key
        ksort($params);

        $keyParts = [
            'endpoint' => $endpoint,
            'params' => $params
        ];

        if ($userId !== null) {
            $keyParts['user'] = $userId;
        }

        return md5(json_encode($keyParts));
    }
}
