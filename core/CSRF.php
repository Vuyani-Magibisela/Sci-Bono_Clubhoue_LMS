<?php
/**
 * CSRF Protection System
 * Phase 2 Implementation
 */

require_once __DIR__ . '/Logger.php';

class CSRF {
    private static $tokenName = '_csrf_token';
    private static $headerName = 'X-CSRF-TOKEN';
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$tokenName] = $token;
        
        return $token;
    }
    
    /**
     * Get current CSRF token
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION[self::$tokenName] ?? '';
        
        if ($token === null) {
            // Try to get token from various sources
            $token = $_POST[self::$tokenName] ?? 
                    $_GET[self::$tokenName] ?? 
                    $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(self::$headerName))] ?? '';
        }
        
        return !empty($sessionToken) && hash_equals($sessionToken, $token);
    }
    
    /**
     * Create hidden input field for forms
     */
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get token for JavaScript usage
     */
    public static function token() {
        return self::getToken();
    }
    
    /**
     * Create meta tag for HTML head
     */
    public static function metaTag() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Middleware to check CSRF token
     */
    public static function check() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only check for state-changing requests
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!self::validateToken()) {
                // Log the CSRF violation
                $logger = new Logger();
                $logger->warning('CSRF token validation failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'method' => $method,
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
                
                // Return error response
                if (self::isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode([
                        'error' => true,
                        'message' => 'CSRF token validation failed',
                        'code' => 'CSRF_ERROR'
                    ]);
                } else {
                    http_response_code(403);
                    require_once __DIR__ . '/../app/Views/errors/403.php';
                }
                
                exit;
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Regenerate token (use after successful form submission)
     */
    public static function regenerateToken() {
        return self::generateToken();
    }
}