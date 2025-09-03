<?php
/**
 * Base Controller - Common functionality for all controllers
 * Phase 4 Implementation
 */

require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../config/ConfigLoader.php';

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