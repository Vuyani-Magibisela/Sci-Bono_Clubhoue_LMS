<?php
/**
 * API Middleware - Handle API-specific concerns
 * Phase 3 Implementation
 */

class ApiMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Set API headers
        $this->setApiHeaders();
        
        // Handle preflight requests
        if ($this->isPreflightRequest()) {
            $this->handlePreflight();
            return false; // Stop further processing
        }
        
        // Validate content type for non-GET requests
        if (!$this->validateContentType()) {
            $this->respondWithError('Invalid content type', 415);
            return false;
        }
        
        // Rate limiting (reuse existing middleware)
        global $conn;
        if ($conn) {
            $rateLimiter = new RateLimitMiddleware($conn);
            if (!$rateLimiter->handle('api')) {
                return false; // Rate limit response handled by middleware
            }
        }
        
        return true;
    }
    
    private function setApiHeaders() {
        // CORS headers
        header('Access-Control-Allow-Origin: *'); // Configure based on your needs
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        header('Access-Control-Max-Age: 86400'); // 24 hours
        
        // API headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
    
    private function isPreflightRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
    }
    
    private function handlePreflight() {
        http_response_code(200);
        echo json_encode(['status' => 'OK']);
        exit;
    }
    
    private function validateContentType() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only validate content type for requests that should have a body
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return true;
        }
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Allow these content types
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data' // For file uploads
        ];
        
        foreach ($allowedTypes as $type) {
            if (strpos($contentType, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function respondWithError($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 'API_ERROR'
        ]);
        exit;
    }
}