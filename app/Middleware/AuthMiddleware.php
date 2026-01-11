<?php
/**
 * Authentication Middleware
 * Enhanced with Hybrid Authentication (JWT + Sessions)
 * Phase 5 Week 1 Day 3
 */

require_once __DIR__ . '/../Services/ApiTokenService.php';

use App\Services\ApiTokenService;

class AuthMiddleware {
    private $logger;
    private $authMethod = null; // 'jwt' or 'session'
    private $authenticatedUser = null;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function handle() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Try JWT authentication first (for API requests)
        if ($this->hasJwtToken()) {
            if ($this->authenticateWithJwt()) {
                return true;
            }
            // JWT authentication failed, fall back to session
        }

        // Fall back to session-based authentication
        if ($this->authenticateWithSession()) {
            return true;
        }

        // Both authentication methods failed
        $this->handleUnauthenticated();
        return false;
    }

    /**
     * Check if request has JWT token
     *
     * @return bool
     */
    private function hasJwtToken()
    {
        $authHeader = $this->getAuthorizationHeader();
        return $authHeader && strpos($authHeader, 'Bearer ') === 0;
    }

    /**
     * Authenticate using JWT token
     *
     * @return bool
     */
    private function authenticateWithJwt()
    {
        global $conn;

        // Set database connection for blacklist checking
        ApiTokenService::setConnection($conn);

        // Extract token from Authorization header
        $authHeader = $this->getAuthorizationHeader();
        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        // Validate token
        $payload = ApiTokenService::validate($token);

        if (!$payload) {
            return false;
        }

        // Store user information in session for compatibility
        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['role'] = $payload['role'] ?? 'user';
        $_SESSION['auth_method'] = 'jwt';
        $_SESSION['last_activity'] = time();

        $this->authMethod = 'jwt';
        $this->authenticatedUser = $payload;

        return true;
    }

    /**
     * Authenticate using session
     *
     * @return bool
     */
    private function authenticateWithSession()
    {
        // Check if user is authenticated via session
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }

        // Verify session is still valid
        if (!$this->verifySession()) {
            $this->handleInvalidSession();
            return false;
        }

        // Extend session timeout
        $this->extendSession();

        $this->authMethod = 'session';
        $this->authenticatedUser = [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? 'user'
        ];

        return true;
    }

    /**
     * Get Authorization header from request
     *
     * @return string|null
     */
    private function getAuthorizationHeader()
    {
        // Apache
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Nginx or Apache with FastCGI
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Alternative: check getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return null;
    }

    /**
     * Get authentication method used
     *
     * @return string|null 'jwt' or 'session'
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * Get authenticated user information
     *
     * @return array|null
     */
    public function getAuthenticatedUser()
    {
        return $this->authenticatedUser;
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
            $loginUrl = '/Sci-Bono_Clubhoue_LMS/login';  // Added base path

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