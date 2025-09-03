<?php
/**
 * Authentication Middleware
 * Phase 3 Implementation
 */

class AuthMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $this->handleUnauthenticated();
            return false;
        }
        
        // Optional: Verify session is still valid
        if (!$this->verifySession()) {
            $this->handleInvalidSession();
            return false;
        }
        
        // Extend session timeout
        $this->extendSession();
        
        return true;
    }
    
    private function verifySession() {
        // Check session timeout
        $sessionTimeout = ConfigLoader::get('app.security.session_timeout', 7200); // 2 hours default
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $sessionTimeout) {
                return false;
            }
        }
        
        // Optional: Verify user still exists and is active
        if (isset($_SESSION['user_id'])) {
            global $conn;
            
            $sql = "SELECT id, user_type, active FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false; // User no longer exists
            }
            
            $user = $result->fetch_assoc();
            if (isset($user['active']) && !$user['active']) {
                return false; // User account is deactivated
            }
        }
        
        return true;
    }
    
    private function extendSession() {
        $_SESSION['last_activity'] = time();
    }
    
    private function handleUnauthenticated() {
        $this->logger->info('Unauthenticated access attempt', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ]);
        
        $this->redirectToLogin();
    }
    
    private function handleInvalidSession() {
        $this->logger->warning('Invalid session detected', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'last_activity' => $_SESSION['last_activity'] ?? null
        ]);
        
        // Clear session
        session_destroy();
        session_start();
        
        $this->redirectToLogin('session_expired');
    }
    
    private function redirectToLogin($reason = 'authentication_required') {
        if ($this->isAjaxRequest() || $this->isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Authentication required',
                'code' => 'AUTHENTICATION_REQUIRED',
                'reason' => $reason
            ]);
        } else {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $loginUrl = '/login';
            
            if (!empty($currentUrl) && $currentUrl !== '/logout') {
                $loginUrl .= '?redirect=' . urlencode($currentUrl);
            }
            
            if ($reason !== 'authentication_required') {
                $loginUrl .= (strpos($loginUrl, '?') !== false ? '&' : '?') . 'reason=' . $reason;
            }
            
            header('Location: ' . $loginUrl);
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