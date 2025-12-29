<?php
/**
 * CacheManager - File-based caching system
 * Phase 3 Week 9 - Performance Optimization
 *
 * Provides simple, efficient file-based caching for query results and computed data.
 * Supports TTL (time-to-live), cache invalidation, and automatic cleanup.
 */

require_once __DIR__ . '/../../core/Logger.php';

class CacheManager {
    private $cacheDir;
    private $defaultTtl;
    private $enabled;
    private $logger;

    /**
     * Initialize cache manager
     */
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        $this->defaultTtl = 300; // 5 minutes default
        $this->enabled = true; // Always enabled unless explicitly disabled
        $this->logger = new Logger();

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                $this->logger->error("Failed to create cache directory: {$this->cacheDir}");
                $this->enabled = false;
            }
        }

        // Ensure cache directory is writable
        if (!is_writable($this->cacheDir)) {
            $this->logger->error("Cache directory is not writable: {$this->cacheDir}");
            $this->enabled = false;
        }

        // Perform periodic cleanup (1% chance on instantiation)
        if (mt_rand(1, 100) === 1) {
            $this->cleanup();
        }
    }

    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }

        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        try {
            $data = unserialize(file_get_contents($filename));

            // Check if data is valid and not expired
            if (!isset($data['value']) || !isset($data['expires_at'])) {
                $this->delete($key);
                return null;
            }

            // Check expiration
            if ($data['expires_at'] < time()) {
                $this->delete($key);
                return null;
            }

            // Update access time for LRU tracking
            touch($filename);

            return $data['value'];

        } catch (Exception $e) {
            $this->logger->error("Cache read error", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time-to-live in seconds (null uses default)
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $filename = $this->getCacheFilename($key);

        try {
            $data = [
                'value' => $value,
                'expires_at' => time() + $ttl,
                'created_at' => time(),
                'key' => $key
            ];

            $serialized = serialize($data);
            $result = file_put_contents($filename, $serialized, LOCK_EX);

            if ($result === false) {
                $this->logger->error("Failed to write cache file", ['key' => $key]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->logger->error("Cache write error", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove value from cache
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        if (!$this->enabled) {
            return false;
        }

        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            return @unlink($filename);
        }

        return true;
    }

    /**
     * Clear all cache entries
     *
     * @return bool Success status
     */
    public function clear() {
        if (!$this->enabled) {
            return false;
        }

        try {
            $files = glob($this->cacheDir . '*.cache');
            $deleted = 0;

            foreach ($files as $file) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }

            $this->logger->info("Cache cleared", ['files_deleted' => $deleted]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Cache clear error", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache filename for key
     *
     * @param string $key Cache key
     * @return string Full path to cache file
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . $hash . '.cache';
    }

    /**
     * Remember: Get from cache or execute callback and cache result
     * This is the primary method for implementing caching throughout the application
     *
     * @param string $key Cache key
     * @param int $ttl Time-to-live in seconds
     * @param callable $callback Function to execute if cache miss
     * @return mixed Cached or computed value
     */
    public function remember($key, $ttl, $callback) {
        // Try to get from cache first
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        // Cache miss - execute callback
        $value = $callback();

        // Store in cache
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Check if key exists in cache and is not expired
     *
     * @param string $key Cache key
     * @return bool True if key exists and is valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    /**
     * Get multiple values from cache
     *
     * @param array $keys Array of cache keys
     * @return array Associative array of key => value (only existing keys)
     */
    public function getMultiple($keys) {
        $results = [];

        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Delete multiple cache entries
     *
     * @param array $keys Array of cache keys
     * @return int Number of keys deleted
     */
    public function deleteMultiple($keys) {
        $deleted = 0;

        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Delete cache entries matching a pattern
     * Useful for invalidating related caches (e.g., all course-related caches)
     *
     * @param string $pattern Pattern to match (e.g., "course_*")
     * @return int Number of keys deleted
     */
    public function deletePattern($pattern) {
        if (!$this->enabled) {
            return 0;
        }

        $deleted = 0;
        $files = glob($this->cacheDir . '*.cache');

        foreach ($files as $file) {
            try {
                $data = unserialize(file_get_contents($file));
                if (isset($data['key']) && fnmatch($pattern, $data['key'])) {
                    if (@unlink($file)) {
                        $deleted++;
                    }
                }
            } catch (Exception $e) {
                // Skip invalid cache files
                continue;
            }
        }

        return $deleted;
    }

    /**
     * Clean up expired cache entries
     * Called periodically (1% chance on instantiation)
     *
     * @return int Number of expired entries deleted
     */
    private function cleanup() {
        if (!$this->enabled) {
            return 0;
        }

        $deleted = 0;
        $files = glob($this->cacheDir . '*.cache');
        $now = time();

        foreach ($files as $file) {
            try {
                $data = unserialize(file_get_contents($file));

                if (!isset($data['expires_at']) || $data['expires_at'] < $now) {
                    if (@unlink($file)) {
                        $deleted++;
                    }
                }
            } catch (Exception $e) {
                // Delete corrupted cache files
                @unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->logger->info("Cache cleanup completed", ['expired_entries' => $deleted]);
        }

        return $deleted;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache stats (total entries, total size, oldest entry)
     */
    public function getStats() {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'total_entries' => 0,
                'total_size' => 0
            ];
        }

        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $validEntries = 0;
        $expiredEntries = 0;
        $oldestTime = PHP_INT_MAX;
        $now = time();

        foreach ($files as $file) {
            $totalSize += filesize($file);

            try {
                $data = unserialize(file_get_contents($file));

                if (isset($data['expires_at'])) {
                    if ($data['expires_at'] >= $now) {
                        $validEntries++;

                        if (isset($data['created_at']) && $data['created_at'] < $oldestTime) {
                            $oldestTime = $data['created_at'];
                        }
                    } else {
                        $expiredEntries++;
                    }
                }
            } catch (Exception $e) {
                $expiredEntries++;
            }
        }

        return [
            'enabled' => true,
            'total_entries' => count($files),
            'valid_entries' => $validEntries,
            'expired_entries' => $expiredEntries,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'oldest_entry_age_seconds' => $oldestTime === PHP_INT_MAX ? 0 : ($now - $oldestTime),
            'cache_directory' => $this->cacheDir
        ];
    }

    /**
     * Disable caching (useful for debugging)
     */
    public function disable() {
        $this->enabled = false;
        $this->logger->info("Caching disabled");
    }

    /**
     * Enable caching
     */
    public function enable() {
        $this->enabled = true;
        $this->logger->info("Caching enabled");
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
}
