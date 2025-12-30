<?php
/**
 * Simple Cache Service
 * Phase 4 Week 2 Day 4
 *
 * Provides file-based caching for frequently accessed configuration data
 * TTL (Time To Live) default: 1 hour
 */

class CacheService {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour in seconds

    /**
     * Constructor
     *
     * @param string $cacheDir Directory for cache files (default: storage/cache)
     */
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../storage/cache';

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $data = json_decode(file_get_contents($filename), true);

        // Check if expired
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Store data in cache
     *
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int|null $ttl Time to live in seconds (null = use default)
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFilename($key);

        $data = [
            'key' => $key,
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl
        ];

        return file_put_contents($filename, json_encode($data)) !== false;
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    /**
     * Clear all cache
     *
     * @return bool Success status
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Check if key exists and is not expired
     *
     * @param string $key Cache key
     * @return bool True if exists and valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    /**
     * Get or set cache (convenience method)
     * If key exists, return cached value
     * If not, execute callback, cache result, and return it
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int|null $ttl Time to live in seconds
     * @return mixed Cached or newly generated data
     */
    public function remember($key, callable $callback, $ttl = null) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get cache filename for a key
     *
     * @param string $key Cache key
     * @return string Full path to cache file
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Clean expired cache files
     *
     * @return int Number of files deleted
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if ($data && $data['expires_at'] < time()) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
?>
