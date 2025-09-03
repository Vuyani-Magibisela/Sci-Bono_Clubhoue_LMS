<?php
/**
 * API Entry Point
 * Phase 3 Implementation
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

// Load database connection
require_once __DIR__ . '/server.php';

try {
    // Load API routes
    $router = require_once __DIR__ . '/routes/api.php';
    
    // Set base path
    $basePath = '/Sci-Bono_Clubhoue_LMS';
    $router->setBasePath($basePath);
    
    // Dispatch API request
    $router->dispatch();
    
} catch (Exception $e) {
    // Log API error
    $logger = new Logger();
    $logger->error('API error: ' . $e->getMessage(), [
        'exception' => $e,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'input' => file_get_contents('php://input')
    ]);
    
    // Return JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    
    if (ConfigLoader::get('app.debug')) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode([
            'error' => true,
            'message' => 'Internal server error',
            'code' => 'INTERNAL_ERROR'
        ]);
    }
}
?>