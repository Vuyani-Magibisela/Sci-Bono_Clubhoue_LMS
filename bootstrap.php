<?php
/**
 * Bootstrap file - Initialize core systems
 * Phase 1 & 2 Implementation
 */

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Load configuration
require_once __DIR__ . '/config/ConfigLoader.php';

// Initialize error handler
require_once __DIR__ . '/core/ErrorHandler.php';
$errorHandler = new ErrorHandler();

// Load core classes
require_once __DIR__ . '/core/Logger.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load security components (Phase 2)
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/ValidationHelpers.php';
require_once __DIR__ . '/app/Middleware/SecurityMiddleware.php';

// Initialize security middleware
$securityMiddleware = new SecurityMiddleware();
$securityMiddleware->handle();

// Set configuration-based error reporting
$config = ConfigLoader::get('app');
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}
?>