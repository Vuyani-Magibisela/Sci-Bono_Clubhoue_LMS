<?php
/**
 * Role-based Authorization Middleware
 * Phase 3 Implementation
 */

class RoleMiddleware {
    private $requiredRoles;
    private $logger;
    
    public function __construct($roles = null) {
        $this->requiredRoles = is_string($roles) ? explode(',', $roles) : (array) $roles;
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Ensure user is authenticated
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            $this->handleUnauthorized('not_authenticated');
            return false;
        }
        
        $userRole = $_SESSION['user_type'];
        
        // Check if user has required role
        if (!empty($this->requiredRoles) && !in_array($userRole, $this->requiredRoles)) {
            $this->handleUnauthorized('insufficient_permissions');
            return false;
        }
        
        return true;
    }
    
    private function handleUnauthorized($reason) {
        $this->logger->warning('Authorization failed', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_type'] ?? null,
            'required_roles' => $this->requiredRoles,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        if ($this->isAjaxRequest() || $this->isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Insufficient permissions',
                'code' => 'AUTHORIZATION_FAILED',
                'reason' => $reason
            ]);
        } else {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
        }
        
        exit;
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    private function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }
}