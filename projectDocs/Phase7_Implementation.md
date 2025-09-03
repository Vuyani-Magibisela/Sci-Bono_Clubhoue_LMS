# Phase 7: Final Implementation Guide
## API Development & Testing Infrastructure

**Duration**: Weeks 13-16  
**Priority**: HIGH  
**Team Size**: 2-3 developers  

---

## Overview

Phase 7 completes the modernization by implementing a comprehensive RESTful API system and establishing robust testing infrastructure. This phase includes automated testing, API documentation, performance optimization, and deployment preparation.

### Key Objectives
- ✅ Develop RESTful API with proper resource endpoints
- ✅ Implement API authentication and rate limiting
- ✅ Create comprehensive testing suite
- ✅ Set up automated testing pipeline
- ✅ Implement performance monitoring
- ✅ Create API documentation
- ✅ Prepare deployment infrastructure

---

## Pre-Implementation Checklist

- [ ] **Phase 6 Completion**: Ensure all frontend improvements are completed and tested
- [ ] **API Design Review**: Review existing API endpoints and plan new ones
- [ ] **Testing Environment**: Set up dedicated testing environment and database
- [ ] **Documentation Tools**: Install and configure API documentation tools
- [ ] **Performance Baseline**: Establish current performance metrics

---

## Task 1: RESTful API Development

### 1.1 Create API Base Infrastructure
**File**: `app/API/BaseApiController.php`
```php
<?php

namespace App\API;

use App\Core\Controller;
use App\Utils\ResponseHelper;
use App\Utils\Validator;

abstract class BaseApiController extends Controller
{
    protected $requestMethod;
    protected $requestData;
    protected $queryParams;
    protected $headers;
    
    public function __construct($db)
    {
        parent::__construct($db);
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->queryParams = $_GET;
        $this->headers = getallheaders();
        $this->parseRequestData();
        $this->setCorsHeaders();
    }
    
    protected function parseRequestData()
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $this->requestData = json_decode(file_get_contents('php://input'), true);
        } else {
            $this->requestData = $_POST;
        }
    }
    
    protected function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json');
        
        if ($this->requestMethod === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    protected function validateRequest($rules)
    {
        $validator = new Validator();
        return $validator->validate($this->requestData, $rules);
    }
    
    protected function requireAuthentication()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->validateApiToken($token)) {
            ResponseHelper::error('Unauthorized', 401);
        }
    }
    
    protected function getBearerToken()
    {
        $authHeader = $this->headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    protected function validateApiToken($token)
    {
        // Implement JWT or custom token validation
        return ApiTokenService::validate($token);
    }
    
    protected function handleRequest()
    {
        try {
            switch ($this->requestMethod) {
                case 'GET':
                    return $this->handleGet();
                case 'POST':
                    return $this->handlePost();
                case 'PUT':
                    return $this->handlePut();
                case 'DELETE':
                    return $this->handleDelete();
                default:
                    ResponseHelper::error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Logger::error('API Error: ' . $e->getMessage(), [
                'method' => $this->requestMethod,
                'data' => $this->requestData,
                'trace' => $e->getTraceAsString()
            ]);
            ResponseHelper::error('Internal server error', 500);
        }
    }
    
    abstract protected function handleGet();
    abstract protected function handlePost();
    abstract protected function handlePut();
    abstract protected function handleDelete();
}
```

### 1.2 Create Response Helper
**File**: `app/Utils/ResponseHelper.php`
```php
<?php

namespace App\Utils;

class ResponseHelper
{
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ]);
        exit();
    }
    
    public static function paginated($data, $pagination, $message = 'Success')
    {
        self::success([
            'items' => $data,
            'pagination' => $pagination
        ], $message);
    }
    
    public static function created($data = null, $message = 'Resource created successfully')
    {
        self::success($data, $message, 201);
    }
    
    public static function updated($data = null, $message = 'Resource updated successfully')
    {
        self::success($data, $message, 200);
    }
    
    public static function deleted($message = 'Resource deleted successfully')
    {
        self::success(null, $message, 200);
    }
    
    public static function notFound($message = 'Resource not found')
    {
        self::error($message, 404);
    }
    
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }
    
    public static function validationError($errors, $message = 'Validation failed')
    {
        self::error($message, 422, $errors);
    }
}
```

### 1.3 Create User API Controller
**File**: `app/API/UserApiController.php`
```php
<?php

namespace App\API;

use App\Models\UserModel;
use App\Utils\ResponseHelper;

class UserApiController extends BaseApiController
{
    private $userModel;
    
    public function __construct($db)
    {
        parent::__construct($db);
        $this->userModel = new UserModel($db);
    }
    
    protected function handleGet()
    {
        $this->requireAuthentication();
        
        $userId = $this->queryParams['id'] ?? null;
        
        if ($userId) {
            return $this->getUser($userId);
        }
        
        return $this->getUsers();
    }
    
    protected function handlePost()
    {
        $this->requireAuthentication();
        return $this->createUser();
    }
    
    protected function handlePut()
    {
        $this->requireAuthentication();
        $userId = $this->queryParams['id'] ?? null;
        
        if (!$userId) {
            ResponseHelper::error('User ID required', 400);
        }
        
        return $this->updateUser($userId);
    }
    
    protected function handleDelete()
    {
        $this->requireAuthentication();
        $userId = $this->queryParams['id'] ?? null;
        
        if (!$userId) {
            ResponseHelper::error('User ID required', 400);
        }
        
        return $this->deleteUser($userId);
    }
    
    private function getUser($userId)
    {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            ResponseHelper::notFound('User not found');
        }
        
        // Remove sensitive data
        unset($user['password']);
        
        ResponseHelper::success($user);
    }
    
    private function getUsers()
    {
        $page = $this->queryParams['page'] ?? 1;
        $limit = $this->queryParams['limit'] ?? 10;
        $search = $this->queryParams['search'] ?? '';
        $role = $this->queryParams['role'] ?? '';
        
        $filters = array_filter([
            'search' => $search,
            'role' => $role
        ]);
        
        $result = $this->userModel->getPaginated($page, $limit, $filters);
        
        // Remove sensitive data from all users
        $result['data'] = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $result['data']);
        
        ResponseHelper::paginated($result['data'], $result['pagination']);
    }
    
    private function createUser()
    {
        $validation = $this->validateRequest([
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,mentor,member,student'
        ]);
        
        if (!$validation['valid']) {
            ResponseHelper::validationError($validation['errors']);
        }
        
        $userData = $this->requestData;
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $userId = $this->userModel->create($userData);
        
        if (!$userId) {
            ResponseHelper::error('Failed to create user', 500);
        }
        
        $user = $this->userModel->findById($userId);
        unset($user['password']);
        
        ResponseHelper::created($user);
    }
    
    private function updateUser($userId)
    {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            ResponseHelper::notFound('User not found');
        }
        
        $validation = $this->validateRequest([
            'name' => 'string|max:100',
            'surname' => 'string|max:100',
            'email' => 'email|unique:users,email,' . $userId,
            'password' => 'string|min:8',
            'role' => 'in:admin,mentor,member,student'
        ]);
        
        if (!$validation['valid']) {
            ResponseHelper::validationError($validation['errors']);
        }
        
        $updateData = $this->requestData;
        
        if (isset($updateData['password'])) {
            $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
        }
        
        $success = $this->userModel->update($userId, $updateData);
        
        if (!$success) {
            ResponseHelper::error('Failed to update user', 500);
        }
        
        $updatedUser = $this->userModel->findById($userId);
        unset($updatedUser['password']);
        
        ResponseHelper::updated($updatedUser);
    }
    
    private function deleteUser($userId)
    {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            ResponseHelper::notFound('User not found');
        }
        
        $success = $this->userModel->delete($userId);
        
        if (!$success) {
            ResponseHelper::error('Failed to delete user', 500);
        }
        
        ResponseHelper::deleted();
    }
}
```

---

## Task 2: API Authentication System

### 2.1 Create API Token Service
**File**: `app/Services/ApiTokenService.php`
```php
<?php

namespace App\Services;

use App\Core\ConfigLoader;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiTokenService
{
    private static $secretKey;
    private static $algorithm = 'HS256';
    private static $expiration = 3600; // 1 hour
    
    public static function init()
    {
        self::$secretKey = ConfigLoader::get('APP_SECRET_KEY', 'your-secret-key');
    }
    
    public static function generate($userId, $userRole = null)
    {
        self::init();
        
        $payload = [
            'iss' => ConfigLoader::get('APP_URL'),
            'aud' => ConfigLoader::get('APP_URL'),
            'iat' => time(),
            'exp' => time() + self::$expiration,
            'user_id' => $userId,
            'role' => $userRole
        ];
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
    public static function validate($token)
    {
        try {
            self::init();
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function refresh($token)
    {
        $payload = self::validate($token);
        
        if (!$payload) {
            return false;
        }
        
        // Generate new token with same user data
        return self::generate($payload['user_id'], $payload['role']);
    }
    
    public static function getUserFromToken($token)
    {
        $payload = self::validate($token);
        return $payload ? $payload['user_id'] : null;
    }
    
    public static function getRoleFromToken($token)
    {
        $payload = self::validate($token);
        return $payload ? $payload['role'] : null;
    }
}
```

### 2.2 Create Rate Limiting Middleware
**File**: `app/Middleware/RateLimitMiddleware.php`
```php
<?php

namespace App\Middleware;

use App\Utils\ResponseHelper;
use App\Core\ConfigLoader;

class RateLimitMiddleware
{
    private static $limits = [
        'default' => ['requests' => 100, 'window' => 3600], // 100 per hour
        'auth' => ['requests' => 10, 'window' => 600], // 10 per 10 minutes
        'api' => ['requests' => 1000, 'window' => 3600] // 1000 per hour
    ];
    
    public static function check($identifier, $type = 'default')
    {
        $cacheDir = 'storage/cache/rate_limits';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $cacheFile = $cacheDir . '/' . md5($identifier . '_' . $type) . '.json';
        
        $now = time();
        $windowStart = $now - $limit['window'];
        
        // Load existing data
        $data = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true) ?: [];
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if limit exceeded
        if (count($data) >= $limit['requests']) {
            ResponseHelper::error('Rate limit exceeded', 429);
        }
        
        // Add current request
        $data[] = $now;
        
        // Save data
        file_put_contents($cacheFile, json_encode($data));
        
        return true;
    }
    
    public static function getRemaining($identifier, $type = 'default')
    {
        $cacheDir = 'storage/cache/rate_limits';
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $cacheFile = $cacheDir . '/' . md5($identifier . '_' . $type) . '.json';
        
        if (!file_exists($cacheFile)) {
            return $limit['requests'];
        }
        
        $data = json_decode(file_get_contents($cacheFile), true) ?: [];
        $windowStart = time() - $limit['window'];
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $limit['requests'] - count($data));
    }
}
```

---

## Task 3: Testing Infrastructure

### 3.1 Create Base Test Class
**File**: `tests/BaseTestCase.php`
```php
<?php

namespace Tests;

use App\Core\ConfigLoader;
use App\Utils\Logger;

abstract class BaseTestCase
{
    protected $db;
    protected static $testDb;
    
    public static function setUpBeforeClass()
    {
        // Load test environment
        ConfigLoader::load('.env.testing');
        
        // Create test database connection
        self::$testDb = self::createTestDatabase();
        
        // Run migrations for test database
        self::runTestMigrations();
    }
    
    public function setUp()
    {
        $this->db = self::$testDb;
        $this->beginTransaction();
    }
    
    public function tearDown()
    {
        $this->rollbackTransaction();
    }
    
    public static function tearDownAfterClass()
    {
        if (self::$testDb) {
            self::$testDb->close();
        }
    }
    
    private static function createTestDatabase()
    {
        $host = ConfigLoader::get('DB_HOST', 'localhost');
        $username = ConfigLoader::get('DB_USERNAME');
        $password = ConfigLoader::get('DB_PASSWORD');
        $database = ConfigLoader::get('DB_NAME');
        
        $db = new mysqli($host, $username, $password);
        
        if ($db->connect_error) {
            throw new Exception('Failed to connect to test database: ' . $db->connect_error);
        }
        
        // Create test database if it doesn't exist
        $db->query("CREATE DATABASE IF NOT EXISTS `{$database}_test`");
        $db->select_db($database . '_test');
        
        return $db;
    }
    
    private static function runTestMigrations()
    {
        $migrationFiles = glob('Database/migrations/*.sql');
        
        foreach ($migrationFiles as $file) {
            $sql = file_get_contents($file);
            if (!self::$testDb->multi_query($sql)) {
                throw new Exception('Failed to run migration: ' . $file);
            }
            
            // Clear results
            do {
                if ($result = self::$testDb->store_result()) {
                    $result->free();
                }
            } while (self::$testDb->next_result());
        }
    }
    
    protected function beginTransaction()
    {
        $this->db->autocommit(false);
    }
    
    protected function rollbackTransaction()
    {
        $this->db->rollback();
        $this->db->autocommit(true);
    }
    
    protected function assertArrayHasKeys($keys, $array, $message = '')
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new Exception($message ?: "Array missing key: {$key}");
            }
        }
        return true;
    }
    
    protected function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected {$expected}, got {$actual}");
        }
        return true;
    }
    
    protected function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception($message ?: "Condition is not true");
        }
        return true;
    }
    
    protected function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception($message ?: "Condition is not false");
        }
        return true;
    }
    
    protected function assertNull($value, $message = '')
    {
        if ($value !== null) {
            throw new Exception($message ?: "Value is not null");
        }
        return true;
    }
    
    protected function assertNotNull($value, $message = '')
    {
        if ($value === null) {
            throw new Exception($message ?: "Value is null");
        }
        return true;
    }
}
```

### 3.2 Create User Model Tests
**File**: `tests/Models/UserModelTest.php`
```php
<?php

namespace Tests\Models;

use Tests\BaseTestCase;
use App\Models\UserModel;

class UserModelTest extends BaseTestCase
{
    private $userModel;
    
    public function setUp()
    {
        parent::setUp();
        $this->userModel = new UserModel($this->db);
    }
    
    public function testCreateUser()
    {
        $userData = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student'
        ];
        
        $userId = $this->userModel->create($userData);
        
        $this->assertNotNull($userId, 'User ID should not be null');
        $this->assertTrue(is_numeric($userId), 'User ID should be numeric');
        
        // Verify user was created
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user, 'User should exist');
        $this->assertEquals($userData['email'], $user['email']);
    }
    
    public function testFindUserById()
    {
        // Create test user
        $userData = [
            'name' => 'Jane',
            'surname' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'mentor'
        ];
        
        $userId = $this->userModel->create($userData);
        
        // Find user
        $user = $this->userModel->findById($userId);
        
        $this->assertNotNull($user, 'User should be found');
        $this->assertEquals($userId, $user['id']);
        $this->assertEquals($userData['name'], $user['name']);
        $this->assertEquals($userData['role'], $user['role']);
    }
    
    public function testFindUserByEmail()
    {
        $email = 'test@example.com';
        $userData = [
            'name' => 'Test',
            'surname' => 'User',
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'member'
        ];
        
        $userId = $this->userModel->create($userData);
        
        // Find by email
        $user = $this->userModel->findByEmail($email);
        
        $this->assertNotNull($user, 'User should be found by email');
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($userId, $user['id']);
    }
    
    public function testUpdateUser()
    {
        // Create test user
        $userData = [
            'name' => 'Update',
            'surname' => 'Test',
            'email' => 'update@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student'
        ];
        
        $userId = $this->userModel->create($userData);
        
        // Update user
        $updateData = [
            'name' => 'Updated',
            'role' => 'mentor'
        ];
        
        $result = $this->userModel->update($userId, $updateData);
        $this->assertTrue($result, 'Update should succeed');
        
        // Verify update
        $user = $this->userModel->findById($userId);
        $this->assertEquals('Updated', $user['name']);
        $this->assertEquals('mentor', $user['role']);
        $this->assertEquals($userData['email'], $user['email']); // Should remain unchanged
    }
    
    public function testDeleteUser()
    {
        // Create test user
        $userData = [
            'name' => 'Delete',
            'surname' => 'Test',
            'email' => 'delete@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student'
        ];
        
        $userId = $this->userModel->create($userData);
        
        // Verify user exists
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user);
        
        // Delete user
        $result = $this->userModel->delete($userId);
        $this->assertTrue($result, 'Delete should succeed');
        
        // Verify user is deleted
        $user = $this->userModel->findById($userId);
        $this->assertNull($user, 'User should be deleted');
    }
    
    public function testGetUsersPaginated()
    {
        // Create test users
        for ($i = 1; $i <= 15; $i++) {
            $this->userModel->create([
                'name' => "User{$i}",
                'surname' => 'Test',
                'email' => "user{$i}@example.com",
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => $i <= 5 ? 'student' : 'mentor'
            ]);
        }
        
        // Test pagination
        $result = $this->userModel->getPaginated(1, 10);
        
        $this->assertArrayHasKeys(['data', 'pagination'], $result);
        $this->assertEquals(10, count($result['data']));
        $this->assertEquals(15, $result['pagination']['total']);
        $this->assertEquals(2, $result['pagination']['pages']);
        
        // Test filtering by role
        $result = $this->userModel->getPaginated(1, 10, ['role' => 'student']);
        $this->assertEquals(5, count($result['data']));
        $this->assertEquals(5, $result['pagination']['total']);
    }
}
```

### 3.3 Create API Tests
**File**: `tests/API/UserApiTest.php`
```php
<?php

namespace Tests\API;

use Tests\BaseTestCase;
use App\API\UserApiController;
use App\Services\ApiTokenService;

class UserApiTest extends BaseTestCase
{
    private $apiController;
    private $validToken;
    
    public function setUp()
    {
        parent::setUp();
        $this->apiController = new UserApiController($this->db);
        
        // Create admin user and token for testing
        $userId = $this->createTestUser(['role' => 'admin']);
        $this->validToken = ApiTokenService::generate($userId, 'admin');
    }
    
    private function createTestUser($data = [])
    {
        $defaultData = [
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student'
        ];
        
        $userData = array_merge($defaultData, $data);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (name, surname, email, password, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssss', 
            $userData['name'], 
            $userData['surname'], 
            $userData['email'], 
            $userData['password'], 
            $userData['role']
        );
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    private function mockRequest($method, $data = [], $headers = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        // Mock headers
        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        
        // Mock request data
        if ($method !== 'GET') {
            file_put_contents('php://input', json_encode($data));
        }
    }
    
    public function testGetUserById()
    {
        $userId = $this->createTestUser(['email' => 'gettest@example.com']);
        
        $_GET['id'] = $userId;
        $this->mockRequest('GET');
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals($userId, $response['data']['id']);
        $this->assertEquals('gettest@example.com', $response['data']['email']);
        $this->assertFalse(isset($response['data']['password'])); // Password should be removed
    }
    
    public function testCreateUser()
    {
        $userData = [
            'name' => 'New',
            'surname' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'student'
        ];
        
        $this->mockRequest('POST', $userData);
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Resource created successfully', $response['message']);
        $this->assertEquals($userData['email'], $response['data']['email']);
        $this->assertFalse(isset($response['data']['password']));
    }
    
    public function testCreateUserValidationError()
    {
        $invalidData = [
            'name' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email
            'password' => '123' // Too short
        ];
        
        $this->mockRequest('POST', $invalidData);
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Validation failed', $response['message']);
        $this->assertNotNull($response['errors']);
    }
    
    public function testUpdateUser()
    {
        $userId = $this->createTestUser(['email' => 'update@example.com']);
        
        $updateData = [
            'name' => 'Updated',
            'role' => 'mentor'
        ];
        
        $_GET['id'] = $userId;
        $this->mockRequest('PUT', $updateData);
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Updated', $response['data']['name']);
        $this->assertEquals('mentor', $response['data']['role']);
    }
    
    public function testDeleteUser()
    {
        $userId = $this->createTestUser(['email' => 'delete@example.com']);
        
        $_GET['id'] = $userId;
        $this->mockRequest('DELETE');
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Resource deleted successfully', $response['message']);
    }
    
    public function testUnauthorizedAccess()
    {
        $this->mockRequest('GET', [], ['Authorization' => 'Bearer invalid-token']);
        
        ob_start();
        $this->apiController->handleRequest();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['message']);
    }
}
```

---

## Task 4: Test Runner and CI/CD

### 4.1 Create Test Runner
**File**: `tests/TestRunner.php`
```php
<?php

namespace Tests;

require_once 'vendor/autoload.php';

class TestRunner
{
    private $testClasses = [];
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function __construct()
    {
        $this->discoverTestClasses();
    }
    
    private function discoverTestClasses()
    {
        $testFiles = $this->getTestFiles('tests');
        
        foreach ($testFiles as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className && class_exists($className)) {
                $this->testClasses[] = $className;
            }
        }
    }
    
    private function getTestFiles($directory)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                strpos($file->getFilename(), 'Test.php') !== false) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? '';
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? '';
        
        return $namespace ? $namespace . '\\' . $className : $className;
    }
    
    public function run()
    {
        echo "Starting test suite...\n";
        echo str_repeat("=", 50) . "\n";
        
        $startTime = microtime(true);
        
        foreach ($this->testClasses as $className) {
            $this->runTestClass($className);
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->printSummary($duration);
        
        return $this->failedTests === 0;
    }
    
    private function runTestClass($className)
    {
        echo "\nRunning {$className}:\n";
        echo str_repeat("-", 30) . "\n";
        
        $reflection = new \ReflectionClass($className);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Run setUpBeforeClass if exists
        if ($reflection->hasMethod('setUpBeforeClass')) {
            $reflection->getMethod('setUpBeforeClass')->invoke(null);
        }
        
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'test') === 0) {
                $this->runTestMethod($className, $method->getName());
            }
        }
        
        // Run tearDownAfterClass if exists
        if ($reflection->hasMethod('tearDownAfterClass')) {
            $reflection->getMethod('tearDownAfterClass')->invoke(null);
        }
    }
    
    private function runTestMethod($className, $methodName)
    {
        $this->totalTests++;
        
        try {
            $instance = new $className();
            
            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }
            
            $instance->$methodName();
            
            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown();
            }
            
            $this->passedTests++;
            echo "  ✓ {$methodName}\n";
            
        } catch (\Exception $e) {
            $this->failedTests++;
            echo "  ✗ {$methodName}: {$e->getMessage()}\n";
            
            $this->results[] = [
                'class' => $className,
                'method' => $methodName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    private function printSummary($duration)
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        echo "Duration: {$duration}s\n";
        
        if ($this->failedTests > 0) {
            echo "\nFAILURES:\n";
            echo str_repeat("-", 30) . "\n";
            
            foreach ($this->results as $result) {
                echo "{$result['class']}::{$result['method']}\n";
                echo "Error: {$result['error']}\n\n";
            }
        }
        
        $status = $this->failedTests === 0 ? 'PASSED' : 'FAILED';
        echo "\nStatus: {$status}\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $runner = new TestRunner();
    $success = $runner->run();
    exit($success ? 0 : 1);
}
```

### 4.2 Create Automated Test Script
**File**: `bin/run-tests.sh`
```bash
#!/bin/bash

# Test runner script for Sci-Bono LMS

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Sci-Bono LMS Test Suite${NC}"
echo "=========================="

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed or not in PATH${NC}"
    exit 1
fi

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install --no-interaction --prefer-dist
fi

# Create test environment file if it doesn't exist
if [ ! -f ".env.testing" ]; then
    echo -e "${YELLOW}Creating test environment file...${NC}"
    cp .env.example .env.testing
    
    # Update for testing
    sed -i 's/APP_ENV=development/APP_ENV=testing/' .env.testing
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env.testing
    sed -i 's/DB_NAME=accounts/DB_NAME=accounts_test/' .env.testing
fi

# Create required directories
mkdir -p storage/logs/tests
mkdir -p storage/cache/tests

# Set proper permissions
chmod -R 755 storage

echo -e "${YELLOW}Running tests...${NC}"
echo ""

# Run the test suite
php tests/TestRunner.php

TEST_EXIT_CODE=$?

echo ""
echo "=========================="

if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed!${NC}"
fi

exit $TEST_EXIT_CODE
```

### 4.3 Create GitHub Actions Workflow
**File**: `.github/workflows/tests.yml`
```yaml
name: Test Suite

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root_password
          MYSQL_DATABASE: accounts_test
          MYSQL_USER: test_user
          MYSQL_PASSWORD: test_password
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306

    strategy:
      matrix:
        php-version: [7.4, 8.0, 8.1]

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, mysqli, pdo, pdo_mysql
        tools: composer:v2

    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress

    - name: Create environment file
      run: |
        cp .env.example .env.testing
        sed -i 's/APP_ENV=development/APP_ENV=testing/' .env.testing
        sed -i 's/DB_HOST=localhost/DB_HOST=127.0.0.1/' .env.testing
        sed -i 's/DB_NAME=accounts/DB_NAME=accounts_test/' .env.testing
        sed -i 's/DB_USERNAME=your_username/DB_USERNAME=test_user/' .env.testing
        sed -i 's/DB_PASSWORD=your_password/DB_PASSWORD=test_password/' .env.testing

    - name: Create required directories
      run: |
        mkdir -p storage/logs/tests
        mkdir -p storage/cache/tests
        chmod -R 755 storage

    - name: Wait for MySQL
      run: |
        while ! mysqladmin ping -h"127.0.0.1" -P"3306" -u"test_user" -p"test_password" --silent; do
          sleep 1
        done

    - name: Run test suite
      run: |
        chmod +x bin/run-tests.sh
        ./bin/run-tests.sh

    - name: Upload test results
      uses: actions/upload-artifact@v3
      if: failure()
      with:
        name: test-results-php-${{ matrix.php-version }}
        path: storage/logs/tests/
```

---

## Task 5: API Documentation

### 5.1 Create API Documentation Generator
**File**: `tools/ApiDocGenerator.php`
```php
<?php

namespace Tools;

class ApiDocGenerator
{
    private $routes = [];
    private $outputDir = 'docs/api';
    
    public function __construct()
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateDocumentation()
    {
        $this->discoverRoutes();
        $this->generateMarkdownDocs();
        $this->generateOpenApiSpec();
        
        echo "API documentation generated successfully!\n";
        echo "Markdown docs: {$this->outputDir}/\n";
        echo "OpenAPI spec: {$this->outputDir}/openapi.json\n";
    }
    
    private function discoverRoutes()
    {
        // User API routes
        $this->routes['Users'] = [
            [
                'method' => 'GET',
                'path' => '/api/users',
                'description' => 'Get paginated list of users',
                'parameters' => [
                    'page' => 'integer (optional) - Page number',
                    'limit' => 'integer (optional) - Items per page',
                    'search' => 'string (optional) - Search term',
                    'role' => 'string (optional) - Filter by role'
                ],
                'response' => 'Paginated user list'
            ],
            [
                'method' => 'GET',
                'path' => '/api/users/{id}',
                'description' => 'Get user by ID',
                'parameters' => [
                    'id' => 'integer (required) - User ID'
                ],
                'response' => 'User object'
            ],
            [
                'method' => 'POST',
                'path' => '/api/users',
                'description' => 'Create new user',
                'body' => [
                    'name' => 'string (required)',
                    'surname' => 'string (required)',
                    'email' => 'string (required)',
                    'password' => 'string (required)',
                    'role' => 'string (required)'
                ],
                'response' => 'Created user object'
            ],
            [
                'method' => 'PUT',
                'path' => '/api/users/{id}',
                'description' => 'Update user',
                'parameters' => [
                    'id' => 'integer (required) - User ID'
                ],
                'body' => [
                    'name' => 'string (optional)',
                    'surname' => 'string (optional)',
                    'email' => 'string (optional)',
                    'password' => 'string (optional)',
                    'role' => 'string (optional)'
                ],
                'response' => 'Updated user object'
            ],
            [
                'method' => 'DELETE',
                'path' => '/api/users/{id}',
                'description' => 'Delete user',
                'parameters' => [
                    'id' => 'integer (required) - User ID'
                ],
                'response' => 'Success message'
            ]
        ];
        
        // Attendance API routes
        $this->routes['Attendance'] = [
            [
                'method' => 'POST',
                'path' => '/api/attendance/signin',
                'description' => 'Sign in user',
                'body' => [
                    'user_id' => 'integer (required)',
                    'password' => 'string (required)'
                ],
                'response' => 'Attendance record'
            ],
            [
                'method' => 'POST',
                'path' => '/api/attendance/signout',
                'description' => 'Sign out user',
                'body' => [
                    'user_id' => 'integer (required)'
                ],
                'response' => 'Updated attendance record'
            ],
            [
                'method' => 'GET',
                'path' => '/api/attendance/search',
                'description' => 'Search attendance records',
                'parameters' => [
                    'query' => 'string (optional) - Search term',
                    'date' => 'string (optional) - Date filter (YYYY-MM-DD)'
                ],
                'response' => 'Attendance records'
            ],
            [
                'method' => 'GET',
                'path' => '/api/attendance/stats',
                'description' => 'Get attendance statistics',
                'response' => 'Statistics object'
            ]
        ];
    }
    
    private function generateMarkdownDocs()
    {
        $content = "# API Documentation\n\n";
        $content .= "This document describes the REST API endpoints for the Sci-Bono LMS system.\n\n";
        $content .= "## Base URL\n\n";
        $content .= "```\nhttp://your-domain.com/api\n```\n\n";
        $content .= "## Authentication\n\n";
        $content .= "All API endpoints require authentication using Bearer tokens:\n\n";
        $content .= "```\nAuthorization: Bearer {your-token}\n```\n\n";
        
        foreach ($this->routes as $section => $endpoints) {
            $content .= "## {$section}\n\n";
            
            foreach ($endpoints as $endpoint) {
                $content .= "### {$endpoint['method']} {$endpoint['path']}\n\n";
                $content .= "{$endpoint['description']}\n\n";
                
                if (isset($endpoint['parameters'])) {
                    $content .= "#### Parameters\n\n";
                    foreach ($endpoint['parameters'] as $param => $desc) {
                        $content .= "- `{$param}`: {$desc}\n";
                    }
                    $content .= "\n";
                }
                
                if (isset($endpoint['body'])) {
                    $content .= "#### Request Body\n\n";
                    $content .= "```json\n{\n";
                    foreach ($endpoint['body'] as $field => $desc) {
                        $content .= "  \"{$field}\": \"{$desc}\",\n";
                    }
                    $content = rtrim($content, ",\n") . "\n}\n```\n\n";
                }
                
                $content .= "#### Response\n\n";
                $content .= "{$endpoint['response']}\n\n";
                $content .= "---\n\n";
            }
        }
        
        file_put_contents($this->outputDir . '/README.md', $content);
    }
    
    private function generateOpenApiSpec()
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Sci-Bono LMS API',
                'version' => '1.0.0',
                'description' => 'REST API for the Sci-Bono Learning Management System'
            ],
            'servers' => [
                [
                    'url' => 'http://localhost/Sci-Bono_Clubhoue_LMS/api',
                    'description' => 'Development server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ]
            ],
            'security' => [
                ['bearerAuth' => []]
            ],
            'paths' => []
        ];
        
        // Convert routes to OpenAPI format
        foreach ($this->routes as $section => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $path = str_replace('{', '{', $endpoint['path']);
                $method = strtolower($endpoint['method']);
                
                if (!isset($spec['paths'][$path])) {
                    $spec['paths'][$path] = [];
                }
                
                $spec['paths'][$path][$method] = [
                    'summary' => $endpoint['description'],
                    'tags' => [$section],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }
        
        file_put_contents($this->outputDir . '/openapi.json', json_encode($spec, JSON_PRETTY_PRINT));
    }
}

// Run documentation generator
if (php_sapi_name() === 'cli') {
    $generator = new ApiDocGenerator();
    $generator->generateDocumentation();
}
```

---

## Task 6: Performance Monitoring

### 6.1 Create Performance Monitor
**File**: `app/Utils/PerformanceMonitor.php`
```php
<?php

namespace App\Utils;

class PerformanceMonitor
{
    private static $startTime;
    private static $queries = [];
    private static $memoryUsage = [];
    private static $checkpoints = [];
    
    public static function start()
    {
        self::$startTime = microtime(true);
        self::$memoryUsage['start'] = memory_get_usage();
    }
    
    public static function checkpoint($name)
    {
        self::$checkpoints[$name] = [
            'time' => microtime(true),
            'memory' => memory_get_usage()
        ];
    }
    
    public static function logQuery($query, $duration)
    {
        self::$queries[] = [
            'query' => $query,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
    }
    
    public static function end()
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        $report = [
            'execution_time' => round($endTime - self::$startTime, 4),
            'memory_usage' => [
                'start' => self::formatBytes(self::$memoryUsage['start']),
                'end' => self::formatBytes($endMemory),
                'peak' => self::formatBytes($peakMemory)
            ],
            'query_count' => count(self::$queries),
            'slow_queries' => array_filter(self::$queries, function($q) {
                return $q['duration'] > 0.1; // Queries over 100ms
            }),
            'checkpoints' => self::$checkpoints
        ];
        
        // Log performance data
        Logger::info('Performance Report', $report);
        
        // Display in debug mode
        if (ConfigLoader::get('APP_DEBUG', false)) {
            self::displayReport($report);
        }
        
        return $report;
    }
    
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private static function displayReport($report)
    {
        if (php_sapi_name() === 'cli') {
            self::displayCliReport($report);
        } else {
            self::displayWebReport($report);
        }
    }
    
    private static function displayCliReport($report)
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "PERFORMANCE REPORT\n";
        echo str_repeat("=", 50) . "\n";
        echo "Execution Time: {$report['execution_time']}s\n";
        echo "Memory Usage: {$report['memory_usage']['end']} (Peak: {$report['memory_usage']['peak']})\n";
        echo "Database Queries: {$report['query_count']}\n";
        
        if (!empty($report['slow_queries'])) {
            echo "Slow Queries:\n";
            foreach ($report['slow_queries'] as $query) {
                echo "  - " . round($query['duration'], 4) . "s: " . substr($query['query'], 0, 50) . "...\n";
            }
        }
        
        if (!empty($report['checkpoints'])) {
            echo "Checkpoints:\n";
            foreach ($report['checkpoints'] as $name => $data) {
                $duration = round($data['time'] - self::$startTime, 4);
                echo "  - {$name}: {$duration}s\n";
            }
        }
        
        echo str_repeat("=", 50) . "\n";
    }
    
    private static function displayWebReport($report)
    {
        echo '<div style="position: fixed; bottom: 0; right: 0; background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; font-size: 12px; z-index: 9999; max-width: 300px;">';
        echo '<strong>Performance Report</strong><br>';
        echo 'Time: ' . $report['execution_time'] . 's<br>';
        echo 'Memory: ' . $report['memory_usage']['end'] . '<br>';
        echo 'Queries: ' . $report['query_count'];
        
        if (!empty($report['slow_queries'])) {
            echo '<br><strong>Slow Queries:</strong><br>';
            foreach ($report['slow_queries'] as $query) {
                echo '• ' . round($query['duration'], 4) . 's<br>';
            }
        }
        
        echo '</div>';
    }
    
    public static function measureFunction($callback, $name = null)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $end = microtime(true);
        $endMemory = memory_get_usage();
        
        $measurement = [
            'duration' => round($end - $start, 4),
            'memory_diff' => self::formatBytes($endMemory - $startMemory)
        ];
        
        if ($name) {
            Logger::debug("Function Performance: {$name}", $measurement);
        }
        
        return ['result' => $result, 'performance' => $measurement];
    }
}
```

---

## Task 7: Deployment and Production Setup

### 7.1 Create Deployment Script
**File**: `deploy/deploy.sh`
```bash
#!/bin/bash

# Deployment script for Sci-Bono LMS
# Usage: ./deploy.sh [environment]

ENVIRONMENT=${1:-production}
PROJECT_DIR="/var/www/html/Sci-Bono_Clubhoue_LMS"
BACKUP_DIR="/var/backups/sci-bono-lms"
DATE=$(date +"%Y%m%d_%H%M%S")

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Sci-Bono LMS Deployment${NC}"
echo "Environment: $ENVIRONMENT"
echo "Date: $(date)"
echo "=============================="

# Function to log messages
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Check if we're in the correct directory
if [ ! -f "index.php" ] || [ ! -d "app" ]; then
    error "Not in project directory. Please run from project root."
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Step 1: Backup current application
log "Creating backup..."
tar -czf "$BACKUP_DIR/backup_$DATE.tar.gz" \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    --exclude='node_modules' \
    --exclude='.git' \
    . || error "Backup failed"

log "Backup created: $BACKUP_DIR/backup_$DATE.tar.gz"

# Step 2: Put application in maintenance mode
log "Enabling maintenance mode..."
touch storage/maintenance.lock

# Step 3: Pull latest code (if using git)
if [ -d ".git" ]; then
    log "Pulling latest code..."
    git pull origin main || error "Git pull failed"
fi

# Step 4: Install/update dependencies
log "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader || error "Composer install failed"

# Step 5: Run database migrations
log "Running database migrations..."
if [ -f "bin/migrate.php" ]; then
    php bin/migrate.php || error "Database migration failed"
fi

# Step 6: Clear caches
log "Clearing caches..."
rm -rf storage/cache/*
rm -rf storage/logs/*.log

# Step 7: Set proper permissions
log "Setting file permissions..."
chmod -R 755 storage
chmod -R 755 public/assets/uploads
chown -R www-data:www-data storage
chown -R www-data:www-data public/assets/uploads

# Step 8: Optimize for production
if [ "$ENVIRONMENT" = "production" ]; then
    log "Optimizing for production..."
    
    # Generate optimized autoloader
    composer dump-autoload --optimize
    
    # Minify CSS/JS if tools are available
    if command -v terser &> /dev/null; then
        find public/assets/js -name "*.js" -not -name "*.min.js" -exec terser {} -o {}.min.js \;
    fi
    
    if command -v uglifycss &> /dev/null; then
        find public/assets/css -name "*.css" -not -name "*.min.css" -exec uglifycss {} --output {}.min.css \;
    fi
fi

# Step 9: Run tests
if [ -f "bin/run-tests.sh" ] && [ "$ENVIRONMENT" != "production" ]; then
    log "Running tests..."
    ./bin/run-tests.sh || error "Tests failed"
fi

# Step 10: Disable maintenance mode
log "Disabling maintenance mode..."
rm -f storage/maintenance.lock

# Step 11: Restart web server (optional)
if [ "$ENVIRONMENT" = "production" ] && command -v systemctl &> /dev/null; then
    log "Restarting web server..."
    sudo systemctl reload apache2 2>/dev/null || sudo systemctl reload nginx 2>/dev/null || true
fi

log "Deployment completed successfully!"
echo ""
echo "Post-deployment checklist:"
echo "1. Verify application is accessible"
echo "2. Check error logs for any issues"
echo "3. Test critical functionality"
echo "4. Monitor performance metrics"
```

### 7.2 Create Environment-Specific Configuration
**File**: `.env.production.example`
```bash
# Production Environment Configuration

# Application Configuration
APP_NAME="Sci-Bono Clubhouse LMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.scibono.ac.za
APP_SECRET_KEY=your-super-secret-production-key-here

# Database Configuration
DB_HOST=localhost
DB_NAME=sci_bono_lms_prod
DB_USERNAME=lms_user
DB_PASSWORD=secure-database-password
DB_CHARSET=utf8mb4

# Session Configuration
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Mail Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.scibono.ac.za
MAIL_PORT=587
MAIL_USERNAME=noreply@scibono.ac.za
MAIL_PASSWORD=mail-account-password
MAIL_FROM_ADDRESS=noreply@scibono.ac.za
MAIL_FROM_NAME="Sci-Bono Clubhouse"

# Logging Configuration
LOG_LEVEL=warning
LOG_MAX_FILES=7
LOG_MAX_SIZE=10MB

# Cache Configuration
CACHE_DRIVER=file
CACHE_LIFETIME=3600

# Security Configuration
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
CSRF_PROTECTION=true

# File Upload Configuration
UPLOAD_MAX_SIZE=10MB
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,doc,docx
UPLOAD_PATH=public/assets/uploads

# Performance Configuration
PERFORMANCE_MONITORING=true
SLOW_QUERY_THRESHOLD=0.1
MEMORY_LIMIT=256M

# External Services
BACKUP_ENABLED=true
BACKUP_SCHEDULE=daily
BACKUP_RETENTION=30

# Monitoring
ERROR_REPORTING=false
DISPLAY_ERRORS=false
```

---

## Completion Checklist

### ✅ Phase 7 Implementation Tasks

#### API Development
- [ ] **Base API Infrastructure**: Create BaseApiController, ResponseHelper, and API routing system
- [ ] **User API**: Implement complete CRUD operations for users with proper validation
- [ ] **Attendance API**: Extend existing attendance endpoints with full REST compliance
- [ ] **Holiday Programs API**: Create API endpoints for program management
- [ ] **Course Management API**: Implement course and lesson management endpoints

#### Authentication & Security
- [ ] **JWT Token System**: Implement ApiTokenService with secure token generation and validation
- [ ] **Rate Limiting**: Create and configure rate limiting middleware for API protection
- [ ] **API Authentication**: Secure all endpoints with proper authentication checks
- [ ] **CORS Configuration**: Set up cross-origin resource sharing for API access

#### Testing Infrastructure
- [ ] **Base Test Framework**: Implement BaseTestCase with database transaction support
- [ ] **Model Tests**: Create comprehensive tests for all model classes
- [ ] **API Tests**: Test all API endpoints with various scenarios
- [ ] **Integration Tests**: Test complete user workflows and system integration
- [ ] **Test Runner**: Implement automated test execution and reporting
- [ ] **CI/CD Pipeline**: Set up GitHub Actions for automated testing

#### Documentation & Monitoring
- [ ] **API Documentation**: Generate comprehensive API documentation with examples
- [ ] **OpenAPI Specification**: Create machine-readable API specification
- [ ] **Performance Monitoring**: Implement performance tracking and reporting
- [ ] **Error Tracking**: Set up comprehensive error logging and monitoring

#### Deployment & Production
- [ ] **Deployment Scripts**: Create automated deployment with backup and rollback
- [ ] **Environment Configuration**: Set up production-ready configuration templates
- [ ] **Performance Optimization**: Implement caching, compression, and optimization
- [ ] **Monitoring Setup**: Configure production monitoring and alerting

### Testing Verification
- [ ] **Unit Tests**: All models and utilities have corresponding unit tests
- [ ] **API Tests**: All endpoints tested for success and error scenarios
- [ ] **Integration Tests**: Complete user workflows tested end-to-end
- [ ] **Performance Tests**: Load testing for critical endpoints
- [ ] **Security Tests**: Authentication, authorization, and input validation tested

### Documentation Verification
- [ ] **API Docs**: Complete API documentation with examples and schemas
- [ ] **Developer Guide**: Implementation guide for future development
- [ ] **Deployment Guide**: Step-by-step deployment and maintenance procedures
- [ ] **Troubleshooting Guide**: Common issues and resolution procedures

### Production Readiness
- [ ] **Environment Setup**: Production environment properly configured
- [ ] **Security Review**: All security measures implemented and tested
- [ ] **Performance Review**: Performance benchmarks established and monitored
- [ ] **Backup Strategy**: Automated backup and recovery procedures in place
- [ ] **Monitoring**: Comprehensive monitoring and alerting configured

---

## Post-Implementation Tasks

### Week 17: Monitoring and Optimization
1. **Performance Monitoring**: Monitor system performance and identify bottlenecks
2. **User Feedback**: Collect and analyze user feedback on new features
3. **Bug Fixes**: Address any issues discovered in production
4. **Documentation Updates**: Update documentation based on real-world usage

### Week 18-20: Advanced Features (Optional)
1. **Mobile API Optimization**: Enhance API for mobile application development
2. **Real-time Features**: Implement WebSocket support for real-time updates
3. **Advanced Analytics**: Add detailed reporting and analytics capabilities
4. **Integration APIs**: Create webhooks and third-party integration support

---

## Success Metrics

### Technical Metrics
- **API Response Time**: < 200ms for 95% of requests
- **Test Coverage**: > 80% code coverage
- **Error Rate**: < 1% error rate in production
- **Uptime**: > 99.9% system availability

### User Experience Metrics
- **Page Load Time**: < 3 seconds for all pages
- **User Satisfaction**: > 4.0/5.0 in user feedback
- **Feature Adoption**: > 70% adoption rate for new features
- **Support Tickets**: < 50% reduction in technical support tickets

### Development Metrics
- **Deployment Time**: < 15 minutes for full deployment
- **Bug Resolution Time**: < 24 hours for critical bugs
- **Development Velocity**: 20% improvement in feature delivery speed
- **Code Quality**: Consistent coding standards and documentation

---

This completes the comprehensive Phase 7 implementation guide for API development and testing infrastructure. The system will be fully modernized with robust APIs, comprehensive testing, and production-ready deployment capabilities.