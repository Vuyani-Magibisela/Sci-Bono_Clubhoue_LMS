<?php
/**
 * URL Helper - Generate URLs and manage routing utilities
 * Phase 3 Implementation
 */

class UrlHelper {
    private static $router;
    private static $basePath = '';
    
    /**
     * Set router instance
     */
    public static function setRouter($router) {
        self::$router = $router;
    }
    
    /**
     * Set base path
     */
    public static function setBasePath($basePath) {
        self::$basePath = rtrim($basePath, '/');
    }
    
    /**
     * Generate URL for named route
     */
    public static function route($name, $params = []) {
        if (self::$router) {
            return self::$router->url($name, $params);
        }
        
        throw new Exception('Router not initialized');
    }
    
    /**
     * Generate URL with base path
     */
    public static function to($path) {
        $path = ltrim($path, '/');
        return self::$basePath . '/' . $path;
    }
    
    /**
     * Generate asset URL
     */
    public static function asset($path) {
        $path = ltrim($path, '/');
        return self::$basePath . '/public/assets/' . $path;
    }
    
    /**
     * Get current URL
     */
    public static function current() {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    /**
     * Check if current URL matches pattern
     */
    public static function is($pattern) {
        $current = self::current();
        
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
        $regex = '/^' . $regex . '$/';
        
        return preg_match($regex, $current);
    }
    
    /**
     * Generate URL with query parameters
     */
    public static function withQuery($url, $params) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }
    
    /**
     * Get previous URL from referrer
     */
    public static function previous($default = '/') {
        return $_SERVER['HTTP_REFERER'] ?? $default;
    }
    
    /**
     * Generate secure URL (HTTPS)
     */
    public static function secure($path) {
        $url = self::to($path);
        return str_replace('http://', 'https://', $url);
    }
    
    /**
     * Check if URL is external
     */
    public static function isExternal($url) {
        return filter_var($url, FILTER_VALIDATE_URL) && 
               parse_url($url, PHP_URL_HOST) !== $_SERVER['HTTP_HOST'];
    }
}