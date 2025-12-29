<?php
/**
 * Main Application Entry Point
 * Phase 3 Implementation - Updated for routing
 */

// Load bootstrap (includes database connection as of Phase 3 Week 8)
require_once __DIR__ . '/bootstrap.php';

try {
    // Load and configure router
    $router = require_once __DIR__ . '/routes/web.php';
    
    // Set base path for subdirectory installations
    $basePath = '/Sci-Bono_Clubhoue_LMS';
    $router->setBasePath($basePath);
    
    // Check if route caching is enabled and available
    $cacheFile = __DIR__ . '/storage/cache/routes.php';
    
    if (ConfigLoader::get('app.env') === 'production' && file_exists($cacheFile)) {
        $router->loadCache($cacheFile);
    }
    
    // Dispatch the request
    $router->dispatch();
    
} catch (Exception $e) {
    // Log the error
    $logger = new Logger();
    $logger->error('Routing error: ' . $e->getMessage(), [
        'exception' => $e,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
    ]);
    
    // Show error page
    http_response_code(500);
    if (ConfigLoader::get('app.debug')) {
        echo '<h1>Routing Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        require_once __DIR__ . '/app/Views/errors/500.php';
    }
}
?>