<?php
/**
 * Bootstrap file - Initialize core systems
 * Phase 3 Week 8 - Updated with database connection
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

// Configure session security (Phase 3 Week 9 - Security Hardening)
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 1);        // Require HTTPS for session cookie
ini_set('session.use_strict_mode', 1);      // Prevent session fixation attacks
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.gc_maxlifetime', 7200);    // 2 hours session lifetime
ini_set('session.cookie_lifetime', 0);      // Session cookie (expires on browser close)

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

// Force production error handling on non-localhost (Phase 3 Week 9 - Security Hardening)
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    ini_set('display_errors', '0');  // Never display errors in production
    ini_set('log_errors', '1');      // Always log errors
}

// ====== DATABASE CONNECTION ======
// Phase 3 Week 8 - Consolidated from server.php
try {
    // Use configuration system for database credentials
    $dbConfig = ConfigLoader::get('database.connections.mysql');

    $host = $dbConfig['host'];
    $user = $dbConfig['username'];
    $password = $dbConfig['password'];
    $dbname = $dbConfig['database'];
} catch (Exception $e) {
    // Phase 3 Week 9 - Security: Fail gracefully instead of using hardcoded credentials
    $logger = new Logger();
    $logger->critical("Database configuration missing", [
        'error' => $e->getMessage(),
        'server' => $_SERVER['SERVER_NAME']
    ]);

    // Fail gracefully - do not expose credentials or continue with unsafe defaults
    die("Database configuration error. Please contact system administrator.");
}

// Establish database connection
global $conn;
$conn = mysqli_connect($host, $user, $password, $dbname);

// Verify connection
if (!$conn) {
    $logger = new Logger();
    $logger->error("Database connection failed", ['error' => mysqli_connect_error()]);
    die("Database connection failed. Please check your configuration.");
}

// Set charset for security
mysqli_set_charset($conn, 'utf8mb4');
?>