<?php
/**
 * Configuration Loader - Handles environment variables and configuration files
 * Phase 1 Implementation
 */

class ConfigLoader {
    private static $config = [];
    private static $envLoaded = false;
    
    /**
     * Load all configuration files
     */
    public static function load() {
        if (empty(self::$config)) {
            self::loadEnv();
            self::$config = [
                'app' => require __DIR__ . '/app.php',
                'database' => require __DIR__ . '/database.php',
                'mail' => require __DIR__ . '/mail.php',
                'session' => require __DIR__ . '/session.php',
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Get configuration value by key
     */
    public static function get($key, $default = null) {
        $config = self::load();
        
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $config;
            
            foreach ($keys as $k) {
                if (is_array($value) && array_key_exists($k, $value)) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }
            
            return $value;
        }
        
        return $config[$key] ?? $default;
    }
    
    /**
     * Load environment variables from .env file
     */
    private static function loadEnv() {
        if (self::$envLoaded) {
            return;
        }
        
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new Exception('.env file not found');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$envLoaded = true;
    }
    
    /**
     * Get environment variable
     */
    public static function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key) ?? $default;
        
        // Convert boolean strings
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'null':
                case '(null)':
                    return null;
            }
        }
        
        return $value;
    }
}