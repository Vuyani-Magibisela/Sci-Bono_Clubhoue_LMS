<?php

namespace Tests;

use App\Utils\Logger;
use Exception;

abstract class BaseTestCase
{
    protected $db;
    protected static $testDb;
    protected static $config;
    private $inTransaction = false;
    
    /**
     * Set up before all tests in the class
     */
    public static function setUpBeforeClass()
    {
        // Load test configuration
        self::loadTestConfig();
        
        // Create test database connection
        self::$testDb = self::createTestDatabase();
        
        // Run test database setup
        self::setupTestDatabase();
    }
    
    /**
     * Set up before each test
     */
    public function setUp()
    {
        $this->db = self::$testDb;
        $this->beginTransaction();
    }
    
    /**
     * Tear down after each test
     */
    public function tearDown()
    {
        if ($this->inTransaction) {
            $this->rollbackTransaction();
        }
    }
    
    /**
     * Tear down after all tests in the class
     */
    public static function tearDownAfterClass()
    {
        if (self::$testDb) {
            self::$testDb->close();
        }
    }
    
    /**
     * Load test configuration
     */
    private static function loadTestConfig()
    {
        self::$config = [
            'DB_HOST' => getenv('TEST_DB_HOST') ?: 'localhost',
            'DB_USERNAME' => getenv('TEST_DB_USERNAME') ?: 'root',
            'DB_PASSWORD' => getenv('TEST_DB_PASSWORD') ?: '',
            'DB_NAME' => getenv('TEST_DB_NAME') ?: 'sci_bono_lms_test'
        ];
    }
    
    /**
     * Create test database connection
     */
    private static function createTestDatabase()
    {
        try {
            $db = new \mysqli(
                self::$config['DB_HOST'],
                self::$config['DB_USERNAME'],
                self::$config['DB_PASSWORD']
            );
            
            if ($db->connect_error) {
                throw new Exception('Failed to connect to test database: ' . $db->connect_error);
            }
            
            // Create test database if it doesn't exist
            $testDbName = self::$config['DB_NAME'];
            $db->query("CREATE DATABASE IF NOT EXISTS `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->select_db($testDbName);
            
            // Set charset
            $db->set_charset('utf8mb4');
            
            echo "Test database '{$testDbName}' connected successfully\n";
            
            return $db;
            
        } catch (Exception $e) {
            throw new Exception('Failed to create test database: ' . $e->getMessage());
        }
    }
    
    /**
     * Set up test database schema
     */
    private static function setupTestDatabase()
    {
        try {
            // Run database migrations/schema files
            self::runSchemaFiles();
            
            // Create any additional test-specific tables
            self::createTestTables();
            
            echo "Test database schema set up successfully\n";
            
        } catch (Exception $e) {
            throw new Exception('Failed to set up test database schema: ' . $e->getMessage());
        }
    }
    
    /**
     * Run schema files from Database directory
     */
    private static function runSchemaFiles()
    {
        $schemaDir = dirname(__DIR__) . '/Database';
        
        // Look for SQL files
        $sqlFiles = [];
        if (is_dir($schemaDir)) {
            $sqlFiles = glob($schemaDir . '/*.sql');
        }
        
        foreach ($sqlFiles as $file) {
            echo "Running schema file: " . basename($file) . "\n";
            
            $sql = file_get_contents($file);
            if ($sql) {
                // Execute multi-query
                if (!self::$testDb->multi_query($sql)) {
                    throw new Exception('Failed to execute schema file ' . $file . ': ' . self::$testDb->error);
                }
                
                // Clear all results
                do {
                    if ($result = self::$testDb->store_result()) {
                        $result->free();
                    }
                } while (self::$testDb->next_result());
            }
        }
    }
    
    /**
     * Create test-specific tables
     */
    private static function createTestTables()
    {
        $tables = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                surname VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'mentor', 'member', 'student') DEFAULT 'student',
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_status (status)
            ) ENGINE=InnoDB",
            
            // Rate limits table for API testing
            "CREATE TABLE IF NOT EXISTS api_rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                timestamp INT NOT NULL,
                ip VARCHAR(45),
                user_agent TEXT,
                endpoint VARCHAR(500),
                method VARCHAR(10),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_rate_limit_check (identifier, action_type, timestamp)
            ) ENGINE=InnoDB",
            
            // Test activity logs
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action)
            ) ENGINE=InnoDB",
            
            // Test sessions table
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id INT,
                data TEXT,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB"
        ];
        
        foreach ($tables as $sql) {
            if (!self::$testDb->query($sql)) {
                throw new Exception('Failed to create test table: ' . self::$testDb->error);
            }
        }
    }
    
    /**
     * Begin database transaction
     */
    protected function beginTransaction()
    {
        if (!$this->inTransaction) {
            $this->db->autocommit(false);
            $this->db->begin_transaction();
            $this->inTransaction = true;
        }
    }
    
    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction()
    {
        if ($this->inTransaction) {
            $this->db->rollback();
            $this->db->autocommit(true);
            $this->inTransaction = false;
        }
    }
    
    /**
     * Commit database transaction
     */
    protected function commitTransaction()
    {
        if ($this->inTransaction) {
            $this->db->commit();
            $this->db->autocommit(true);
            $this->inTransaction = false;
        }
    }
    
    /**
     * Create a test user
     */
    protected function createTestUser($data = [])
    {
        $defaultData = [
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'active'
        ];
        
        $userData = array_merge($defaultData, $data);
        
        $sql = "INSERT INTO users (name, surname, email, password, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssss', 
            $userData['name'],
            $userData['surname'],
            $userData['email'],
            $userData['password'],
            $userData['role'],
            $userData['status']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create test user: ' . $stmt->error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Clean test data between tests
     */
    protected function cleanTestData()
    {
        $tables = ['users', 'api_rate_limits', 'activity_logs', 'user_sessions'];
        
        foreach ($tables as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
    }
    
    // ========== ASSERTION METHODS ==========
    
    /**
     * Assert that a condition is true
     */
    protected function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception($message ?: "Assertion failed: condition is not true");
        }
        return true;
    }
    
    /**
     * Assert that a condition is false
     */
    protected function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception($message ?: "Assertion failed: condition is not false");
        }
        return true;
    }
    
    /**
     * Assert that two values are equal
     */
    protected function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            $expectedStr = is_scalar($expected) ? $expected : gettype($expected);
            $actualStr = is_scalar($actual) ? $actual : gettype($actual);
            throw new Exception($message ?: "Assertion failed: Expected '{$expectedStr}', got '{$actualStr}'");
        }
        return true;
    }
    
    /**
     * Assert that two values are not equal
     */
    protected function assertNotEquals($expected, $actual, $message = '')
    {
        if ($expected === $actual) {
            $valueStr = is_scalar($expected) ? $expected : gettype($expected);
            throw new Exception($message ?: "Assertion failed: Values should not be equal: '{$valueStr}'");
        }
        return true;
    }
    
    /**
     * Assert that a value is null
     */
    protected function assertNull($value, $message = '')
    {
        if ($value !== null) {
            $valueStr = is_scalar($value) ? $value : gettype($value);
            throw new Exception($message ?: "Assertion failed: Value should be null, got '{$valueStr}'");
        }
        return true;
    }
    
    /**
     * Assert that a value is not null
     */
    protected function assertNotNull($value, $message = '')
    {
        if ($value === null) {
            throw new Exception($message ?: "Assertion failed: Value should not be null");
        }
        return true;
    }
    
    /**
     * Assert that a value is empty
     */
    protected function assertEmpty($value, $message = '')
    {
        if (!empty($value)) {
            throw new Exception($message ?: "Assertion failed: Value should be empty");
        }
        return true;
    }
    
    /**
     * Assert that a value is not empty
     */
    protected function assertNotEmpty($value, $message = '')
    {
        if (empty($value)) {
            throw new Exception($message ?: "Assertion failed: Value should not be empty");
        }
        return true;
    }
    
    /**
     * Assert that an array has specific keys
     */
    protected function assertArrayHasKeys($keys, $array, $message = '')
    {
        if (!is_array($array)) {
            throw new Exception($message ?: "Assertion failed: Value is not an array");
        }
        
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new Exception($message ?: "Assertion failed: Array missing key '{$key}'");
            }
        }
        return true;
    }
    
    /**
     * Assert that an array has a specific key
     */
    protected function assertArrayHasKey($key, $array, $message = '')
    {
        return $this->assertArrayHasKeys([$key], $array, $message);
    }
    
    /**
     * Assert that an array does not have a specific key
     */
    protected function assertArrayNotHasKey($key, $array, $message = '')
    {
        if (!is_array($array)) {
            throw new Exception($message ?: "Assertion failed: Value is not an array");
        }
        
        if (array_key_exists($key, $array)) {
            throw new Exception($message ?: "Assertion failed: Array should not have key '{$key}'");
        }
        return true;
    }
    
    /**
     * Assert that a string contains another string
     */
    protected function assertStringContains($needle, $haystack, $message = '')
    {
        if (!is_string($haystack)) {
            throw new Exception($message ?: "Assertion failed: Haystack is not a string");
        }
        
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "Assertion failed: String '{$haystack}' does not contain '{$needle}'");
        }
        return true;
    }
    
    /**
     * Assert that a value is of a specific type
     */
    protected function assertInstanceOf($expected, $actual, $message = '')
    {
        if (!($actual instanceof $expected)) {
            $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
            throw new Exception($message ?: "Assertion failed: Expected instance of '{$expected}', got '{$actualType}'");
        }
        return true;
    }
    
    /**
     * Assert that a count matches expected
     */
    protected function assertCount($expectedCount, $array, $message = '')
    {
        if (!is_array($array) && !($array instanceof \Countable)) {
            throw new Exception($message ?: "Assertion failed: Value is not countable");
        }
        
        $actualCount = count($array);
        if ($actualCount !== $expectedCount) {
            throw new Exception($message ?: "Assertion failed: Expected count {$expectedCount}, got {$actualCount}");
        }
        return true;
    }
    
    /**
     * Assert that an exception is thrown
     */
    protected function assertThrows($expectedException, $callback, $message = '')
    {
        $exceptionThrown = false;
        $thrownException = null;
        
        try {
            $callback();
        } catch (Exception $e) {
            $exceptionThrown = true;
            $thrownException = $e;
        }
        
        if (!$exceptionThrown) {
            throw new Exception($message ?: "Assertion failed: Expected exception '{$expectedException}' was not thrown");
        }
        
        if ($expectedException && !($thrownException instanceof $expectedException)) {
            $thrownType = get_class($thrownException);
            throw new Exception($message ?: "Assertion failed: Expected exception '{$expectedException}', got '{$thrownType}'");
        }
        
        return true;
    }
    
    /**
     * Skip the current test
     */
    protected function skipTest($message = '')
    {
        throw new \Tests\SkipTestException($message ?: 'Test skipped');
    }
    
    /**
     * Mark test as incomplete
     */
    protected function markTestIncomplete($message = '')
    {
        throw new \Tests\IncompleteTestException($message ?: 'Test incomplete');
    }
    
    /**
     * Get database connection for direct queries
     */
    protected function getDatabase()
    {
        return $this->db;
    }
    
    /**
     * Execute raw SQL query for testing
     */
    protected function query($sql)
    {
        return $this->db->query($sql);
    }
    
    /**
     * Get last insert ID
     */
    protected function getLastInsertId()
    {
        return $this->db->insert_id;
    }
}

// Custom test exceptions
class SkipTestException extends Exception {}
class IncompleteTestException extends Exception {}