<?php
/**
 * PHPUnit Bootstrap File
 * Phase 3 Week 9 - Testing Infrastructure
 *
 * This file initializes the test environment for PHPUnit tests
 */

// Start output buffering to capture any output during setup
ob_start();

// Define test environment constants
define('IS_TESTING', true);
define('TEST_DB_NAME', getenv('DB_NAME') ?: 'accounts_test');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration and core dependencies
require_once __DIR__ . '/../config/ConfigLoader.php';
require_once __DIR__ . '/../core/Logger.php';

// Load environment variables
ConfigLoader::load();

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Configure error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Disable session for testing
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.cache_limiter', '');

// Set test environment variables
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_NAME'] = TEST_DB_NAME;
$_ENV['CACHE_ENABLED'] = 'false';
$_ENV['DEBUG_MODE'] = 'true';

// Initialize test database
initializeTestDatabase();

// Clean output buffer
ob_end_clean();

echo "\n✅ PHPUnit test environment initialized successfully\n";
echo "📊 Test database: " . TEST_DB_NAME . "\n";
echo "🔧 Environment: testing\n\n";

/**
 * Initialize test database
 */
function initializeTestDatabase() {
    $host = ConfigLoader::env('DB_HOST', 'localhost');
    $username = ConfigLoader::env('DB_USERNAME', 'root');
    $password = ConfigLoader::env('DB_PASSWORD', '');
    $dbName = TEST_DB_NAME;

    try {
        // Connect to MySQL server
        $conn = new mysqli($host, $username, $password);

        if ($conn->connect_error) {
            throw new Exception("Failed to connect to MySQL: " . $conn->connect_error);
        }

        // Create test database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($dbName);

        // Store connection for later use (global for test helpers)
        $GLOBALS['test_db_connection'] = $conn;

        echo "✅ Test database '{$dbName}' ready\n";

    } catch (Exception $e) {
        echo "❌ Failed to initialize test database: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Get test database connection
 *
 * @return mysqli
 */
function getTestDbConnection() {
    return $GLOBALS['test_db_connection'] ?? null;
}

/**
 * Clean test database
 * Call this in tearDown() to reset database state
 */
function cleanTestDatabase() {
    $conn = getTestDbConnection();
    if (!$conn) {
        return;
    }

    // Get all tables
    $result = $conn->query("SHOW TABLES");
    $tables = [];

    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Truncate all tables
    foreach ($tables as $table) {
        $conn->query("TRUNCATE TABLE `{$table}`");
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

/**
 * Create test user helper
 *
 * @param array $data User data override
 * @return int User ID
 */
function createTestUser($data = []) {
    $conn = getTestDbConnection();
    if (!$conn) {
        throw new Exception("Test database connection not available");
    }

    $defaultData = [
        'name' => 'Test',
        'surname' => 'User',
        'username' => 'testuser' . uniqid(),
        'email' => 'test' . uniqid() . '@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'member',
        'user_type' => 'member',
        'status' => 'active'
    ];

    $userData = array_merge($defaultData, $data);

    // Drop and recreate users table to ensure correct schema
    $conn->query("DROP TABLE IF EXISTS users");

    $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL,
            username VARCHAR(100) UNIQUE,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'mentor', 'member', 'parent', 'project_officer', 'manager') DEFAULT 'member',
            user_type ENUM('admin', 'mentor', 'member', 'parent', 'project_officer', 'manager') DEFAULT 'member',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            session_token VARCHAR(255),
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_session (session_token)
        ) ENGINE=InnoDB";

    if (!$conn->query($sql)) {
        throw new Exception("Failed to create users table: " . $conn->error);
    }

    $sql = "INSERT INTO users (name, surname, username, email, password, role, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss',
        $userData['name'],
        $userData['surname'],
        $userData['username'],
        $userData['email'],
        $userData['password'],
        $userData['role'],
        $userData['user_type'],
        $userData['status']
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create test user: " . $stmt->error);
    }

    return $conn->insert_id;
}

/**
 * Create test admin user
 *
 * @return int User ID
 */
function createTestAdminUser() {
    return createTestUser([
        'name' => 'Admin',
        'surname' => 'User',
        'email' => 'admin' . uniqid() . '@example.com',
        'role' => 'admin'
    ]);
}

/**
 * Create test mentor user
 *
 * @return int User ID
 */
function createTestMentorUser() {
    return createTestUser([
        'name' => 'Mentor',
        'surname' => 'User',
        'email' => 'mentor' . uniqid() . '@example.com',
        'role' => 'mentor'
    ]);
}

/**
 * Mock session for testing
 *
 * @param array $sessionData Session data to set
 */
function mockSession($sessionData = []) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $_SESSION = $sessionData;
    } else {
        $_SESSION = array_merge($_SESSION, $sessionData);
    }
}

/**
 * Clear mocked session
 */
function clearMockedSession() {
    $_SESSION = [];
}
