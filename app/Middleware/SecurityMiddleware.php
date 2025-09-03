<?php
/**
 * Security Middleware - HTTP security headers and CSRF protection
 * Phase 2 Implementation
 */

require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/ValidationHelpers.php';

class SecurityMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        $this->setSecurityHeaders();
        $this->checkCSRF();
        $this->detectSuspiciousActivity();
        
        return true;
    }
    
    private function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // HTTPS enforcement (if HTTPS is available)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy (basic)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';";
        header("Content-Security-Policy: {$csp}");
    }
    
    private function checkCSRF() {
        // Skip CSRF check for safe methods and specific paths
        if ($this->shouldSkipCSRF()) {
            return;
        }
        
        CSRF::check();
    }
    
    private function shouldSkipCSRF() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        
        // Skip for safe HTTP methods
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }
        
        // Skip for specific API endpoints that use alternative auth
        $skipPaths = [
            '/api/webhook/',  // External webhooks
            '/api/callback/', // OAuth callbacks
        ];
        
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectSuspiciousActivity() {
        $suspiciousIndicators = [];
        
        // Check for SQL injection patterns
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && ValidationHelpers::checkSQLInjection($value)) {
                $suspiciousIndicators[] = "SQL injection pattern in {$key}";
            }
        }
        
        // Check for XSS patterns
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && $this->containsXSS($value)) {
                $suspiciousIndicators[] = "XSS pattern in {$key}";
            }
        }
        
        // Check for directory traversal
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && $this->containsDirectoryTraversal($value)) {
                $suspiciousIndicators[] = "Directory traversal in {$key}";
            }
        }
        
        // Log suspicious activity
        if (!empty($suspiciousIndicators)) {
            $this->logger->warning('Suspicious activity detected', [
                'indicators' => $suspiciousIndicators,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'data' => $this->sanitizeLogData($_REQUEST)
            ]);
            
            // Optional: Block request if too many indicators
            if (count($suspiciousIndicators) >= 3) {
                http_response_code(403);
                exit('Suspicious activity detected');
            }
        }
    }
    
    private function containsXSS($input) {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/expression\(/i',
            '/vbscript:/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function containsDirectoryTraversal($input) {
        $traversalPatterns = [
            '/\.\.\//i',
            '/\.\.\\\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/\.\.%2f/i',
            '/\.\.%5c/i'
        ];
        
        foreach ($traversalPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function sanitizeLogData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && strlen($value) > 100) {
                $sanitized[$key] = substr($value, 0, 100) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}