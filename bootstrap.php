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
// Check if HTTPS is available
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Start session with security options (PHP 7.3+ syntax)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,           // Prevent JavaScript access to session cookie
        'cookie_secure' => $isHttps,         // Require HTTPS only if available
        'cookie_samesite' => 'Lax',          // CSRF protection with better compatibility (Lax allows same-site POST)
        'use_strict_mode' => true,           // Prevent session fixation attacks
        'gc_maxlifetime' => 7200,            // 2 hours session lifetime
        'cookie_lifetime' => 0,              // Session cookie (expires on browser close)
        'sid_length' => 48,                  // Longer session IDs for better security
        'sid_bits_per_character' => 6        // More entropy in session IDs
    ]);
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
if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
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