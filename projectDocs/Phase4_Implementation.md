# Phase 4: MVC Refinement Implementation Guide
## Service Layer, Repository Pattern & Code Reusability

**Duration**: Weeks 4-5  
**Priority**: MEDIUM  
**Dependencies**: Phase 1 (Configuration), Phase 2 (Security), Phase 3 (Routing)  
**Team Size**: 1-2 developers  

---

## Overview

Phase 4 focuses on refining the MVC architecture by implementing proper separation of concerns through service layers, repository patterns, and reusable components. This phase reduces code duplication and creates a more maintainable architecture.

### Key Objectives
- ✅ Create BaseController and BaseModel classes
- ✅ Implement Service layer for business logic
- ✅ Establish Repository pattern for data access
- ✅ Build reusable components and traits
- ✅ Refactor existing controllers to use new architecture
- ✅ Create standardized response formats

---

## Pre-Implementation Checklist

- [ ] **Previous Phases Complete**: Phases 1-3 are fully implemented and tested
- [ ] **Code Review**: Current controllers and models reviewed for refactoring opportunities
- [ ] **Backup System**: Create backup before major refactoring
- [ ] **Testing Strategy**: Plan how to test refactored components
- [ ] **Dependencies**: Ensure autoloading system is ready for new classes

---

## Task 1: Base Classes Implementation

### 1.1 Create BaseController
**File**: `app/Controllers/BaseController.php`

```php
<?php
/**
 * Base Controller - Common functionality for all controllers
 * Phase 4 Implementation
 */

abstract class BaseController {
    protected $conn;
    protected $config;
    protected $logger;
    protected $validator;
    
    public function __construct($conn, $config = null) {
        $this->conn = $conn;
        $this->config = $config ?? ConfigLoader::load();
        $this->logger = new Logger();
        $this->initializeSession();
    }
    
    /**
     * Initialize session if not started
     */
    protected function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Render a view with data
     */
    protected function view($view, $data = [], $layout = null) {
        // Make data available as variables
        extract($data);
        
        // Include view helpers
        require_once __DIR__ . '/../Helpers/ViewHelpers.php';
        
        $viewPath = $this->getViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new Exception("View not found: {$view}");
        }
        
        if ($layout) {
            $this->renderWithLayout($viewPath, $layout, $data);
        } else {
            require $viewPath;
        }
    }
    
    /**
     * Render view with layout
     */
    protected function renderWithLayout($viewPath, $layout, $data) {
        // Start output buffering for content
        ob_start();
        extract($data);
        require $viewPath;
        $content = ob_get_clean();
        
        // Render layout with content
        $layoutPath = __DIR__ . "/../Views/layouts/{$layout}.php";
        if (!file_exists($layoutPath)) {
            throw new Exception("Layout not found: {$layout}");
        }
        
        extract(array_merge($data, ['content' => $content]));
        require $layoutPath;
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Return success JSON response
     */
    protected function jsonSuccess($data = null, $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->json($response);
    }
    
    /**
     * Return error JSON response
     */
    protected function jsonError($message, $errors = null, $statusCode = 400) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess($url, $message) {
        $_SESSION['flash_success'] = $message;
        $this->redirect($url);
    }
    
    /**
     * Redirect with error message
     */
    protected function redirectWithError($url, $message) {
        $_SESSION['flash_error'] = $message;
        $this->redirect($url);
    }
    
    /**
     * Validate request data
     */
    protected function validate($data, $rules) {
        $this->validator = new Validator($data);
        
        if (!$this->validator->validate($rules)) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('Validation failed', $this->validator->errors(), 422);
            } else {
                $_SESSION['validation_errors'] = $this->validator->errors();
                $_SESSION['old_input'] = $data;
                $this->redirectBack();
            }
        }
        
        return $this->validator->getValidatedData();
    }
    
    /**
     * Get validated data from last validation
     */
    protected function validated() {
        return $this->validator ? $this->validator->getValidatedData() : [];
    }
    
    /**
     * Redirect back to previous page
     */
    protected function redirectBack() {
        $previous = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($previous);
    }
    
    /**
     * Check if user is authenticated
     */
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('Authentication required', null, 401);
            } else {
                $this->redirect('/login');
            }
        }
    }
    
    /**
     * Check if user has required role
     */
    protected function requireRole($roles) {
        $this->requireAuth();
        
        $userRole = $_SESSION['user_type'] ?? '';
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        
        if (!in_array($userRole, $allowedRoles)) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('Insufficient permissions', null, 403);
            } else {
                $this->redirect('/403');
            }
        }
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current authenticated user
     */
    protected function currentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'name' => $_SESSION['name'] ?? '',
            'user_type' => $_SESSION['user_type'] ?? ''
        ];
    }
    
    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Get request input
     */
    protected function input($key = null, $default = null) {
        $input = array_merge($_GET, $_POST);
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    /**
     * Get file from request
     */
    protected function file($key) {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Check CSRF token
     */
    protected function validateCsrfToken() {
        if (!CSRF::validateToken()) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('Invalid CSRF token', null, 403);
            } else {
                $this->redirectWithError($_SERVER['HTTP_REFERER'] ?? '/', 'Invalid security token');
            }
        }
    }
    
    /**
     * Log controller action
     */
    protected function logAction($action, $data = []) {
        $this->logger->info("Controller action: {$action}", array_merge($data, [
            'controller' => get_class($this),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]));
    }
    
    /**
     * Handle pagination
     */
    protected function paginate($query, $page = 1, $perPage = 25) {
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
        $result = $this->conn->query($countQuery);
        $total = $result->fetch_assoc()['total'];
        
        // Get paginated results
        $paginatedQuery = $query . " LIMIT {$offset}, {$perPage}";
        $result = $this->conn->query($paginatedQuery);
        $items = $result->fetch_all(MYSQLI_ASSOC);
        
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total
            ]
        ];
    }
    
    /**
     * Get view file path
     */
    private function getViewPath($view) {
        $view = str_replace('.', '/', $view);
        return __DIR__ . "/../Views/{$view}.php";
    }
}
```

### 1.2 Create BaseModel
**File**: `app/Models/BaseModel.php`

```php
<?php
/**
 * Base Model - Common functionality for all models
 * Phase 4 Implementation
 */

abstract class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    protected $timestamps = true;
    protected $logger;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
        
        if (empty($this->table)) {
            $this->table = $this->getTableName();
        }
    }
    
    /**
     * Get table name from class name
     */
    protected function getTableName() {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        $className = str_replace('Model', '', $className);
        
        // Convert CamelCase to snake_case and make plural
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        // Simple pluralization
        if (substr($table, -1) === 'y') {
            $table = substr($table, 0, -1) . 'ies';
        } elseif (!in_array(substr($table, -1), ['s', 'x', 'z'])) {
            $table .= 's';
        }
        
        return $table;
    }
    
    /**
     * Find record by primary key
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logError("Database error in find()", $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Find multiple records
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $types = "";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
                $types .= $this->getParamType($value);
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logError("Database error in create()", $this->conn->error);
            throw new Exception("Failed to prepare create statement");
        }
        
        $types = "";
        $values = [];
        foreach ($data as $value) {
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        
        if ($success) {
            $id = $this->conn->insert_id;
            $this->logAction("create", ['id' => $id, 'data' => $data]);
            return $id;
        }
        
        $this->logError("Failed to create record", $stmt->error);
        throw new Exception("Failed to create record: " . $stmt->error);
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "{$field} = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . 
               " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logError("Database error in update()", $this->conn->error);
            throw new Exception("Failed to prepare update statement");
        }
        
        $types = "";
        $values = [];
        foreach ($data as $value) {
            $types .= $this->getParamType($value);
            $values[] = $value;
        }
        $types .= "i";
        $values[] = $id;
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        
        if ($success) {
            $this->logAction("update", ['id' => $id, 'data' => $data]);
            return $stmt->affected_rows > 0;
        }
        
        $this->logError("Failed to update record", $stmt->error);
        throw new Exception("Failed to update record: " . $stmt->error);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        // Check if record exists
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logError("Database error in delete()", $this->conn->error);
            throw new Exception("Failed to prepare delete statement");
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        if ($success) {
            $this->logAction("delete", ['id' => $id, 'record' => $record]);
            return $stmt->affected_rows > 0;
        }
        
        $this->logError("Failed to delete record", $stmt->error);
        throw new Exception("Failed to delete record: " . $stmt->error);
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $types = "";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
                $types .= $this->getParamType($value);
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int) $row['count'];
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logError("Database error in query()", $this->conn->error);
            throw new Exception("Failed to prepare query");
        }
        
        if (!empty($params)) {
            $types = "";
            foreach ($params as $param) {
                $types .= $this->getParamType($param);
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Check if record exists
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Filter data based on fillable/guarded fields
     */
    protected function filterFillable($data) {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }
        
        return $data;
    }
    
    /**
     * Get parameter type for binding
     */
    protected function getParamType($value) {
        if (is_int($value)) {
            return 'i';
        } elseif (is_float($value)) {
            return 'd';
        } elseif (is_string($value)) {
            return 's';
        } else {
            return 'b'; // blob
        }
    }
    
    /**
     * Log database actions
     */
    protected function logAction($action, $data = []) {
        $this->logger->info("Model action: {$action}", array_merge($data, [
            'model' => get_class($this),
            'table' => $this->table
        ]));
    }
    
    /**
     * Log database errors
     */
    protected function logError($message, $error) {
        $this->logger->error($message, [
            'model' => get_class($this),
            'table' => $this->table,
            'database_error' => $error
        ]);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
}
```

---

## Task 2: Service Layer Implementation

### 2.1 Create Base Service Class
**File**: `app/Services/BaseService.php`

```php
<?php
/**
 * Base Service - Common functionality for all services
 * Phase 4 Implementation
 */

abstract class BaseService {
    protected $conn;
    protected $logger;
    protected $config;
    
    public function __construct($conn = null) {
        global $conn as $globalConn;
        $this->conn = $conn ?? $globalConn;
        $this->logger = new Logger();
        $this->config = ConfigLoader::load();
    }
    
    /**
     * Handle service errors consistently
     */
    protected function handleError($message, $context = []) {
        $this->logger->error($message, array_merge($context, [
            'service' => get_class($this)
        ]));
        
        throw new Exception($message);
    }
    
    /**
     * Log service actions
     */
    protected function logAction($action, $context = []) {
        $this->logger->info("Service action: {$action}", array_merge($context, [
            'service' => get_class($this)
        ]));
    }
    
    /**
     * Validate required parameters
     */
    protected function validateRequired($data, $required) {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field missing: {$field}");
            }
        }
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
    }
}
```

### 2.2 Create User Service
**File**: `app/Services/UserService.php`

```php
<?php
/**
 * User Service - Business logic for user operations
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserService extends BaseService {
    private $userModel;
    
    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->userModel = new UserModel($this->conn);
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        $this->validateRequired(['username' => $username, 'password' => $password], ['username', 'password']);
        
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            $this->logAction('login_attempt_failed', ['username' => $username, 'reason' => 'user_not_found']);
            throw new Exception('Invalid credentials');
        }
        
        if (!$this->userModel->validateCredentials($user['id'], $password)) {
            $this->logAction('login_attempt_failed', ['username' => $username, 'reason' => 'invalid_password']);
            throw new Exception('Invalid credentials');
        }
        
        // Update last login
        $this->userModel->updateLastLogin($user['id']);
        
        $this->logAction('login_successful', ['user_id' => $user['id'], 'username' => $username]);
        
        return $user;
    }
    
    /**
     * Create new user account
     */
    public function createUser($userData) {
        $required = ['username', 'email', 'password', 'name', 'surname'];
        $this->validateRequired($userData, $required);
        
        // Check for existing username
        if ($this->userModel->getUserByUsername($userData['username'])) {
            throw new Exception('Username already exists');
        }
        
        // Check for existing email
        if ($this->userExists(['email' => $userData['email']])) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Sanitize data
        $userData = $this->sanitize($userData);
        
        // Set default values
        $userData['user_type'] = $userData['user_type'] ?? 'member';
        $userData['active'] = $userData['active'] ?? 1;
        
        try {
            $userId = $this->userModel->create($userData);
            
            $this->logAction('user_created', [
                'user_id' => $userId,
                'username' => $userData['username'],
                'user_type' => $userData['user_type']
            ]);
            
            return $this->userModel->find($userId);
            
        } catch (Exception $e) {
            $this->handleError('Failed to create user: ' . $e->getMessage(), ['userData' => $userData]);
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $profileData) {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Remove sensitive fields that shouldn't be updated via profile
        unset($profileData['password'], $profileData['user_type'], $profileData['active']);
        
        // Sanitize data
        $profileData = $this->sanitize($profileData);
        
        try {
            $success = $this->userModel->update($userId, $profileData);
            
            if ($success) {
                $this->logAction('profile_updated', ['user_id' => $userId]);
                return $this->userModel->find($userId);
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->handleError('Failed to update profile: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Verify current password
        if (!$this->userModel->validateCredentials($userId, $currentPassword)) {
            throw new Exception('Current password is incorrect');
        }
        
        // Validate new password strength
        $passwordErrors = Validator::validatePassword($newPassword);
        if (!empty($passwordErrors)) {
            throw new Exception('Password requirements not met: ' . implode(', ', $passwordErrors));
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $success = $this->userModel->update($userId, ['password' => $hashedPassword]);
            
            if ($success) {
                $this->logAction('password_changed', ['user_id' => $userId]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->handleError('Failed to change password: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUser($userId) {
        return $this->userModel->find($userId);
    }
    
    /**
     * Get users with filtering and pagination
     */
    public function getUsers($filters = [], $page = 1, $perPage = 25) {
        try {
            return $this->userModel->getAllUsersFiltered($filters, $page, $perPage);
        } catch (Exception $e) {
            $this->handleError('Failed to retrieve users: ' . $e->getMessage(), ['filters' => $filters]);
        }
    }
    
    /**
     * Search users
     */
    public function searchUsers($searchTerm, $filters = []) {
        try {
            return $this->userModel->searchUsers($searchTerm, $filters);
        } catch (Exception $e) {
            $this->handleError('Failed to search users: ' . $e->getMessage(), ['searchTerm' => $searchTerm]);
        }
    }
    
    /**
     * Deactivate user account
     */
    public function deactivateUser($userId, $reason = null) {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        try {
            $success = $this->userModel->update($userId, ['active' => 0]);
            
            if ($success) {
                $this->logAction('user_deactivated', [
                    'user_id' => $userId,
                    'reason' => $reason
                ]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->handleError('Failed to deactivate user: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
    
    /**
     * Check if user exists
     */
    public function userExists($conditions) {
        return $this->userModel->exists($conditions);
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        try {
            return $this->userModel->getUserStats();
        } catch (Exception $e) {
            $this->handleError('Failed to retrieve user stats: ' . $e->getMessage());
        }
    }
}
```

### 2.3 Create Attendance Service
**File**: `app/Services/AttendanceService.php`

```php
<?php
/**
 * Attendance Service - Business logic for attendance operations
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/AttendanceModel.php';
require_once __DIR__ . '/../Models/UserModel.php';

class AttendanceService extends BaseService {
    private $attendanceModel;
    private $userModel;
    
    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->attendanceModel = new AttendanceModel($this->conn);
        $this->userModel = new UserModel($this->conn);
    }
    
    /**
     * Sign in user with authentication
     */
    public function signInUser($userId, $password) {
        // Validate user exists and credentials
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        if (!$this->userModel->validateCredentials($userId, $password)) {
            $this->logAction('signin_failed', [
                'user_id' => $userId,
                'reason' => 'invalid_credentials'
            ]);
            throw new Exception('Invalid credentials');
        }
        
        // Check if already signed in
        if ($this->attendanceModel->isUserSignedIn($userId)) {
            throw new Exception('User is already signed in');
        }
        
        try {
            $result = $this->attendanceModel->signInUser($userId);
            
            if ($result['success']) {
                $this->logAction('signin_successful', [
                    'user_id' => $userId,
                    'username' => $user['username']
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->handleError('Failed to sign in user: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
    
    /**
     * Sign out user
     */
    public function signOutUser($userId) {
        // Check if user is signed in
        if (!$this->attendanceModel->isUserSignedIn($userId)) {
            throw new Exception('User is not signed in');
        }
        
        try {
            $result = $this->attendanceModel->signOutUser($userId);
            
            if ($result['success']) {
                $this->logAction('signout_successful', ['user_id' => $userId]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->handleError('Failed to sign out user: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStats($dateRange = null) {
        try {
            return $this->attendanceModel->getAttendanceStats($dateRange);
        } catch (Exception $e) {
            $this->handleError('Failed to retrieve attendance stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current attendance status
     */
    public function getCurrentAttendance() {
        try {
            return $this->attendanceModel->getCurrentSignedInUsers();
        } catch (Exception $e) {
            $this->handleError('Failed to retrieve current attendance: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk sign out users (mentor/admin function)
     */
    public function bulkSignOut($userIds, $performedBy) {
        if (empty($userIds)) {
            throw new Exception('No users selected for bulk sign out');
        }
        
        $results = [];
        $errors = [];
        
        foreach ($userIds as $userId) {
            try {
                if ($this->attendanceModel->isUserSignedIn($userId)) {
                    $result = $this->attendanceModel->signOutUser($userId);
                    $results[] = [
                        'user_id' => $userId,
                        'success' => $result['success']
                    ];
                } else {
                    $errors[] = "User {$userId} is not signed in";
                }
            } catch (Exception $e) {
                $errors[] = "Failed to sign out user {$userId}: " . $e->getMessage();
            }
        }
        
        $this->logAction('bulk_signout', [
            'performed_by' => $performedBy,
            'user_ids' => $userIds,
            'successful' => count($results),
            'errors' => count($errors)
        ]);
        
        return [
            'results' => $results,
            'errors' => $errors,
            'summary' => [
                'total_attempted' => count($userIds),
                'successful' => count($results),
                'failed' => count($errors)
            ]
        ];
    }
    
    /**
     * Get attendance report
     */
    public function getAttendanceReport($startDate, $endDate, $userType = null) {
        try {
            return $this->attendanceModel->getAttendanceReport($startDate, $endDate, $userType);
        } catch (Exception $e) {
            $this->handleError('Failed to generate attendance report: ' . $e->getMessage(), [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'userType' => $userType
            ]);
        }
    }
    
    /**
     * Check user attendance status
     */
    public function getUserAttendanceStatus($userId) {
        try {
            return [
                'is_signed_in' => $this->attendanceModel->isUserSignedIn($userId),
                'last_signin' => $this->attendanceModel->getLastSignIn($userId),
                'total_hours_today' => $this->attendanceModel->getTodayHours($userId)
            ];
        } catch (Exception $e) {
            $this->handleError('Failed to get user attendance status: ' . $e->getMessage(), ['user_id' => $userId]);
        }
    }
}
```

---

## Task 3: Repository Pattern Implementation

### 3.1 Create Repository Interface
**File**: `app/Repositories/RepositoryInterface.php`

```php
<?php
/**
 * Repository Interface - Standard methods for all repositories
 * Phase 4 Implementation
 */

interface RepositoryInterface {
    public function find($id);
    public function findAll($conditions = []);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function count($conditions = []);
    public function exists($conditions);
}
```

### 3.2 Create Base Repository
**File**: `app/Repositories/BaseRepository.php`

```php
<?php
/**
 * Base Repository - Common repository functionality
 * Phase 4 Implementation
 */

require_once __DIR__ . '/RepositoryInterface.php';

abstract class BaseRepository implements RepositoryInterface {
    protected $conn;
    protected $model;
    protected $logger;
    
    public function __construct($conn, $model = null) {
        $this->conn = $conn;
        $this->logger = new Logger();
        
        if ($model) {
            $this->model = $model;
        } else {
            $this->initializeModel();
        }
    }
    
    /**
     * Initialize model (to be implemented by child classes)
     */
    abstract protected function initializeModel();
    
    /**
     * Find record by ID
     */
    public function find($id) {
        return $this->model->find($id);
    }
    
    /**
     * Find all records with conditions
     */
    public function findAll($conditions = []) {
        return $this->model->findAll($conditions);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        return $this->model->create($data);
    }
    
    /**
     * Update existing record
     */
    public function update($id, $data) {
        return $this->model->update($id, $data);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        return $this->model->delete($id);
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        return $this->model->count($conditions);
    }
    
    /**
     * Check if record exists
     */
    public function exists($conditions) {
        return $this->model->exists($conditions);
    }
    
    /**
     * Find records with pagination
     */
    public function paginate($page = 1, $perPage = 25, $conditions = []) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        
        // Build query for pagination
        $sql = $this->buildPaginationQuery($conditions, $offset, $perPage);
        $result = $this->model->query($sql);
        $items = $result->fetch_all(MYSQLI_ASSOC);
        
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total
            ]
        ];
    }
    
    /**
     * Build pagination query (to be implemented by child classes if needed)
     */
    protected function buildPaginationQuery($conditions, $offset, $limit) {
        $table = $this->model->table ?? 'unknown';
        $sql = "SELECT * FROM {$table}";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = '{$value}'";
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $sql .= " LIMIT {$offset}, {$limit}";
        return $sql;
    }
}
```

### 3.3 Create User Repository
**File**: `app/Repositories/UserRepository.php`

```php
<?php
/**
 * User Repository - Specialized user data access
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserRepository extends BaseRepository {
    
    protected function initializeModel() {
        $this->model = new UserModel($this->conn);
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        return $this->model->getUserByUsername($username);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $result = $this->model->query($sql, [$email]);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Find users by role
     */
    public function findByRole($role) {
        return $this->model->getUsersByType($role);
    }
    
    /**
     * Search users with advanced filters
     */
    public function search($searchTerm, $filters = []) {
        return $this->model->searchUsers($searchTerm, $filters);
    }
    
    /**
     * Get active users only
     */
    public function getActiveUsers($conditions = []) {
        $conditions['active'] = 1;
        return $this->findAll($conditions);
    }
    
    /**
     * Get users with recent activity
     */
    public function getRecentlyActiveUsers($days = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "SELECT * FROM users 
                WHERE last_login >= ? 
                ORDER BY last_login DESC";
        
        $result = $this->model->query($sql, [$cutoffDate]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get user statistics by role
     */
    public function getStatsByRole() {
        return $this->model->getUserStats();
    }
    
    /**
     * Batch update users
     */
    public function batchUpdate($userIds, $data) {
        $results = [];
        
        foreach ($userIds as $userId) {
            try {
                $success = $this->update($userId, $data);
                $results[] = [
                    'user_id' => $userId,
                    'success' => $success
                ];
            } catch (Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Soft delete user (deactivate instead of delete)
     */
    public function softDelete($id) {
        return $this->update($id, ['active' => 0]);
    }
}
```

---

## Task 4: Traits for Code Reusability

### 4.1 Create Timestamp Trait
**File**: `app/Traits/HasTimestamps.php`

```php
<?php
/**
 * HasTimestamps Trait - Automatic timestamp management
 * Phase 4 Implementation
 */

trait HasTimestamps {
    
    /**
     * Add created_at and updated_at timestamps to data
     */
    protected function addTimestamps($data, $updating = false) {
        $now = date('Y-m-d H:i:s');
        
        if (!$updating) {
            $data['created_at'] = $now;
        }
        
        $data['updated_at'] = $now;
        
        return $data;
    }
    
    /**
     * Format timestamp for display
     */
    protected function formatTimestamp($timestamp, $format = 'Y-m-d H:i:s') {
        if (empty($timestamp)) {
            return null;
        }
        
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Get relative time (e.g., "2 hours ago")
     */
    protected function getRelativeTime($timestamp) {
        if (empty($timestamp)) {
            return null;
        }
        
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }
}
```

### 4.2 Create Validation Trait
**File**: `app/Traits/ValidatesData.php`

```php
<?php
/**
 * ValidatesData Trait - Common validation methods
 * Phase 4 Implementation
 */

trait ValidatesData {
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new ValidationException([
                'missing_fields' => 'Required fields are missing: ' . implode(', ', $missing)
            ]);
        }
        
        return true;
    }
    
    /**
     * Validate email format
     */
    protected function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(['email' => 'Invalid email format']);
        }
        
        return true;
    }
    
    /**
     * Validate password strength
     */
    protected function validatePassword($password) {
        $errors = Validator::validatePassword($password);
        
        if (!empty($errors)) {
            throw new ValidationException(['password' => $errors]);
        }
        
        return true;
    }
    
    /**
     * Validate user role
     */
    protected function validateUserRole($role) {
        $allowedRoles = ['admin', 'mentor', 'member', 'alumni'];
        
        if (!in_array($role, $allowedRoles)) {
            throw new ValidationException(['role' => 'Invalid user role']);
        }
        
        return true;
    }
    
    /**
     * Validate date format
     */
    protected function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        
        if (!$d || $d->format($format) !== $date) {
            throw new ValidationException(['date' => 'Invalid date format']);
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
    }
}
```

### 4.3 Create Logging Trait
**File**: `app/Traits/LogsActivity.php`

```php
<?php
/**
 * LogsActivity Trait - Consistent activity logging
 * Phase 4 Implementation
 */

trait LogsActivity {
    protected $logger;
    
    /**
     * Initialize logger if not already set
     */
    protected function initializeLogger() {
        if (!$this->logger) {
            $this->logger = new Logger();
        }
    }
    
    /**
     * Log info level message
     */
    protected function logInfo($message, $context = []) {
        $this->initializeLogger();
        
        $this->logger->info($message, array_merge($context, [
            'class' => get_class($this),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]));
    }
    
    /**
     * Log error level message
     */
    protected function logError($message, $context = []) {
        $this->initializeLogger();
        
        $this->logger->error($message, array_merge($context, [
            'class' => get_class($this),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]));
    }
    
    /**
     * Log warning level message
     */
    protected function logWarning($message, $context = []) {
        $this->initializeLogger();
        
        $this->logger->warning($message, array_merge($context, [
            'class' => get_class($this),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]));
    }
    
    /**
     * Log user action
     */
    protected function logUserAction($action, $data = []) {
        $this->logInfo("User action: {$action}", $data);
    }
    
    /**
     * Log system event
     */
    protected function logSystemEvent($event, $data = []) {
        $this->logInfo("System event: {$event}", $data);
    }
}
```

---

## Task 5: Refactor Existing Controllers

### 5.1 Refactor AuthController
**File**: `app/Controllers/AuthController.php` (Refactored)

```php
<?php
/**
 * Auth Controller - Refactored to use new architecture
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Traits/ValidatesData.php';
require_once __DIR__ . '/../Traits/LogsActivity.php';

class AuthController extends BaseController {
    use ValidatesData, LogsActivity;
    
    private $userService;
    
    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->userService = new UserService($conn);
    }
    
    /**
     * Show login form
     */
    public function showLogin() {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth.login', [
            'title' => 'Login - ' . $this->config['app']['name'],
            'errors' => $_SESSION['validation_errors'] ?? [],
            'old_input' => $_SESSION['old_input'] ?? []
        ]);
        
        // Clear flash data
        unset($_SESSION['validation_errors'], $_SESSION['old_input']);
    }
    
    /**
     * Process login
     */
    public function login() {
        $this->validateCsrfToken();
        
        $data = $this->validate($this->input(), [
            'username' => 'required|alpha_dash|min:3|max:50',
            'password' => 'required|min:1|max:255'
        ]);
        
        try {
            $user = $this->userService->authenticate($data['username'], $data['password']);
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['surname'] = $user['surname'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Regenerate CSRF token
            CSRF::regenerateToken();
            
            $this->logUserAction('login', ['username' => $user['username']]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonSuccess([
                    'redirect' => $this->getRedirectUrl()
                ], 'Login successful');
            } else {
                $this->redirectWithSuccess($this->getRedirectUrl(), 'Welcome back!');
            }
            
        } catch (Exception $e) {
            $this->logWarning('Login failed', [
                'username' => $data['username'],
                'error' => $e->getMessage()
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonError($e->getMessage(), null, 401);
            } else {
                $this->redirectWithError('/login', $e->getMessage());
            }
        }
    }
    
    /**
     * Show signup form
     */
    public function showSignup() {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth.signup', [
            'title' => 'Sign Up - ' . $this->config['app']['name'],
            'errors' => $_SESSION['validation_errors'] ?? [],
            'old_input' => $_SESSION['old_input'] ?? []
        ]);
        
        // Clear flash data
        unset($_SESSION['validation_errors'], $_SESSION['old_input']);
    }
    
    /**
     * Process signup
     */
    public function signup() {
        $this->validateCsrfToken();
        
        $data = $this->validate($this->input(), [
            'username' => 'required|alpha_dash|min:3|max:50|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|password|confirmed',
            'name' => 'required|alpha|min:2|max:50',
            'surname' => 'required|alpha|min:2|max:50',
            'user_type' => 'in:member,alumni'
        ]);
        
        try {
            $user = $this->userService->createUser($data);
            
            $this->logUserAction('signup', [
                'user_id' => $user['id'],
                'username' => $user['username']
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonSuccess([
                    'redirect' => '/login?signup=success'
                ], 'Account created successfully');
            } else {
                $this->redirectWithSuccess('/login', 'Account created successfully! Please log in.');
            }
            
        } catch (Exception $e) {
            $this->logError('Signup failed', [
                'username' => $data['username'],
                'email' => $data['email'],
                'error' => $e->getMessage()
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonError($e->getMessage(), null, 400);
            } else {
                $this->redirectWithError('/signup', $e->getMessage());
            }
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $this->validateCsrfToken();
        
        $userId = $_SESSION['user_id'] ?? null;
        
        // Clear session
        session_destroy();
        session_start();
        
        // Regenerate CSRF token
        CSRF::generateToken();
        
        $this->logUserAction('logout', ['user_id' => $userId]);
        
        if ($this->isAjaxRequest()) {
            $this->jsonSuccess(['redirect' => '/'], 'Logged out successfully');
        } else {
            $this->redirectWithSuccess('/', 'You have been logged out successfully');
        }
    }
    
    /**
     * Get redirect URL after login
     */
    private function getRedirectUrl() {
        // Check for intended URL
        $intended = $_GET['redirect'] ?? $_SESSION['intended_url'] ?? null;
        unset($_SESSION['intended_url']);
        
        if ($intended && $this->isValidRedirectUrl($intended)) {
            return $intended;
        }
        
        // Default redirect based on user role
        $userType = $_SESSION['user_type'] ?? 'member';
        
        switch ($userType) {
            case 'admin':
                return '/admin';
            case 'mentor':
                return '/mentor';
            default:
                return '/dashboard';
        }
    }
    
    /**
     * Validate redirect URL for security
     */
    private function isValidRedirectUrl($url) {
        // Prevent open redirect vulnerabilities
        $parsed = parse_url($url);
        
        // Only allow relative URLs or URLs on the same host
        return !isset($parsed['host']) || $parsed['host'] === $_SERVER['HTTP_HOST'];
    }
}
```

### 5.2 Refactor AttendanceController
**File**: `app/Controllers/AttendanceController.php` (Refactored)

```php
<?php
/**
 * Attendance Controller - Refactored to use new architecture
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/AttendanceService.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Traits/ValidatesData.php';
require_once __DIR__ . '/../Traits/LogsActivity.php';

class AttendanceController extends BaseController {
    use ValidatesData, LogsActivity;
    
    private $attendanceService;
    private $userService;
    
    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->attendanceService = new AttendanceService($conn);
        $this->userService = new UserService($conn);
    }
    
    /**
     * Show attendance page
     */
    public function index() {
        $this->view('attendance.index', [
            'title' => 'Attendance Register',
            'stats' => $this->attendanceService->getAttendanceStats(),
            'current_attendance' => $this->attendanceService->getCurrentAttendance()
        ]);
    }
    
    /**
     * Handle sign-in request
     */
    public function handleSignIn() {
        $data = $this->validate($this->input(), [
            'user_id' => 'required|integer',
            'password' => 'required'
        ]);
        
        try {
            $result = $this->attendanceService->signInUser($data['user_id'], $data['password']);
            
            $this->logUserAction('attendance_signin', [
                'user_id' => $data['user_id'],
                'result' => $result['success']
            ]);
            
            $this->jsonSuccess($result, $result['message']);
            
        } catch (Exception $e) {
            $this->logWarning('Attendance signin failed', [
                'user_id' => $data['user_id'],
                'error' => $e->getMessage()
            ]);
            
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
    
    /**
     * Handle sign-out request
     */
    public function handleSignOut() {
        $data = $this->validate($this->input(), [
            'user_id' => 'required|integer'
        ]);
        
        try {
            $result = $this->attendanceService->signOutUser($data['user_id']);
            
            $this->logUserAction('attendance_signout', [
                'user_id' => $data['user_id'],
                'result' => $result['success']
            ]);
            
            $this->jsonSuccess($result, $result['message']);
            
        } catch (Exception $e) {
            $this->logWarning('Attendance signout failed', [
                'user_id' => $data['user_id'],
                'error' => $e->getMessage()
            ]);
            
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
    
    /**
     * Handle user search
     */
    public function handleUserSearch() {
        $searchTerm = $this->input('search', '');
        
        if (strlen($searchTerm) < 2) {
            $this->jsonError('Search term must be at least 2 characters', null, 400);
        }
        
        try {
            $users = $this->userService->searchUsers($searchTerm);
            
            $this->jsonSuccess([
                'users' => $users,
                'count' => count($users)
            ]);
            
        } catch (Exception $e) {
            $this->logError('User search failed', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonError('Search failed', null, 500);
        }
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStats() {
        try {
            $dateRange = [
                'start' => $this->input('start_date'),
                'end' => $this->input('end_date')
            ];
            
            $stats = $this->attendanceService->getAttendanceStats($dateRange);
            
            $this->jsonSuccess($stats);
            
        } catch (Exception $e) {
            $this->logError('Failed to get attendance stats', [
                'error' => $e->getMessage()
            ]);
            
            $this->jsonError('Failed to retrieve statistics', null, 500);
        }
    }
    
    /**
     * Bulk sign out users (mentor/admin only)
     */
    public function handleBulkSignOut() {
        $this->requireRole(['mentor', 'admin']);
        
        $data = $this->validate($this->input(), [
            'user_ids' => 'required',
        ]);
        
        $userIds = is_array($data['user_ids']) ? $data['user_ids'] : explode(',', $data['user_ids']);
        
        try {
            $result = $this->attendanceService->bulkSignOut($userIds, $this->currentUser()['id']);
            
            $this->logUserAction('bulk_signout', [
                'user_ids' => $userIds,
                'summary' => $result['summary']
            ]);
            
            $this->jsonSuccess($result, 'Bulk sign out completed');
            
        } catch (Exception $e) {
            $this->logError('Bulk sign out failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
}
```

---

## Phase 4 Testing & Integration

### Task 6: Testing Framework

#### 6.1 Create Test Base Class
**File**: `tests/TestCase.php`

```php
<?php
/**
 * Base Test Case for Phase 4 testing
 */

class TestCase {
    protected $conn;
    protected $testDb = 'test_accounts';
    
    protected function setUp() {
        // Create test database connection
        $this->conn = new mysqli('localhost', 'test_user', 'test_pass', $this->testDb);
        
        if ($this->conn->connect_error) {
            throw new Exception("Test database connection failed: " . $this->conn->connect_error);
        }
        
        // Set up test data
        $this->seedTestData();
    }
    
    protected function tearDown() {
        // Clean up test data
        $this->cleanupTestData();
        
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    protected function seedTestData() {
        // Create test users
        $this->conn->query("INSERT INTO users (username, email, password, name, surname, user_type) VALUES 
            ('test_admin', 'admin@test.com', 'hashed_password', 'Test', 'Admin', 'admin'),
            ('test_mentor', 'mentor@test.com', 'hashed_password', 'Test', 'Mentor', 'mentor'),
            ('test_member', 'member@test.com', 'hashed_password', 'Test', 'Member', 'member')");
    }
    
    protected function cleanupTestData() {
        // Remove test data
        $this->conn->query("DELETE FROM users WHERE username LIKE 'test_%'");
    }
    
    protected function assertArrayHasKey($key, $array) {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Array does not have key: {$key}");
        }
    }
    
    protected function assertEquals($expected, $actual) {
        if ($expected !== $actual) {
            throw new Exception("Expected {$expected}, got {$actual}");
        }
    }
    
    protected function assertTrue($condition) {
        if (!$condition) {
            throw new Exception("Assertion failed: condition is not true");
        }
    }
}
```

#### 6.2 Create Service Tests
**File**: `tests/Services/UserServiceTest.php`

```php
<?php
/**
 * User Service Tests
 */

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../../app/Services/UserService.php';

class UserServiceTest extends TestCase {
    private $userService;
    
    public function setUp() {
        parent::setUp();
        $this->userService = new UserService($this->conn);
    }
    
    public function testAuthenticate() {
        $user = $this->userService->authenticate('test_admin', 'test_password');
        
        $this->assertArrayHasKey('id', $user);
        $this->assertEquals('test_admin', $user['username']);
        $this->assertEquals('admin', $user['user_type']);
    }
    
    public function testCreateUser() {
        $userData = [
            'username' => 'new_test_user',
            'email' => 'newuser@test.com',
            'password' => 'TestPassword123!',
            'name' => 'New',
            'surname' => 'User'
        ];
        
        $user = $this->userService->createUser($userData);
        
        $this->assertArrayHasKey('id', $user);
        $this->assertEquals('new_test_user', $user['username']);
    }
    
    public function testUpdateProfile() {
        // Get test user
        $testUser = $this->userService->getUserByUsername('test_member');
        
        $profileData = [
            'name' => 'Updated',
            'surname' => 'Name'
        ];
        
        $updatedUser = $this->userService->updateProfile($testUser['id'], $profileData);
        
        $this->assertEquals('Updated', $updatedUser['name']);
        $this->assertEquals('Name', $updatedUser['surname']);
    }
}
```

---

## Phase 4 Completion Checklist

### Base Classes
- [ ] BaseController implemented with common functionality
- [ ] BaseModel implemented with CRUD operations
- [ ] BaseService implemented for business logic layer
- [ ] BaseRepository implemented for data access layer

### Service Layer
- [ ] UserService created with authentication and profile management
- [ ] AttendanceService created with sign-in/out logic
- [ ] Services handle business logic properly
- [ ] Error handling and logging implemented in services

### Repository Pattern
- [ ] Repository interface defined
- [ ] UserRepository implemented with specialized queries
- [ ] Repositories provide clean data access layer
- [ ] Pagination support implemented

### Traits & Reusability
- [ ] Timestamp management trait created
- [ ] Validation trait implemented
- [ ] Activity logging trait implemented
- [ ] Traits are properly used across classes

### Controller Refactoring
- [ ] AuthController refactored to use new architecture
- [ ] AttendanceController refactored to use services
- [ ] Controllers follow single responsibility principle
- [ ] Consistent response formats implemented

### Testing
- [ ] Test base class created
- [ ] Service tests implemented
- [ ] All tests pass
- [ ] Code coverage is adequate

---

## Benefits Achieved

1. **Code Reusability**: Base classes and traits eliminate duplication
2. **Separation of Concerns**: Clear separation between controllers, services, and repositories
3. **Testability**: Business logic isolated in services for easier testing
4. **Maintainability**: Consistent architecture makes code easier to maintain
5. **Scalability**: Pattern can be easily extended to new features

---

## Next Phase Preparation

Before proceeding to Phase 5 (Database Layer):
1. **Complete Testing**: Ensure all refactored components work correctly
2. **Performance Check**: Verify new architecture doesn't impact performance
3. **Code Review**: Review refactored code for best practices
4. **Documentation**: Update documentation to reflect new architecture
5. **Team Training**: Ensure team understands new patterns and conventions

**Phase 4 establishes a solid architectural foundation. All future development should follow these patterns.**