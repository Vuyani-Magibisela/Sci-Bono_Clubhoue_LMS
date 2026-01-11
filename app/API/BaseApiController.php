<?php

namespace App\API;

require_once __DIR__ . '/../Controllers/BaseController.php';
require_once __DIR__ . '/../Utils/ResponseHelper.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../Middleware/CacheMiddleware.php';
require_once __DIR__ . '/../Utils/CacheHelper.php';
require_once __DIR__ . '/../Middleware/CorsMiddleware.php';
require_once __DIR__ . '/../Utils/ApiLogger.php';

use App\Utils\ResponseHelper;
use App\Middleware\CacheMiddleware;
use App\Utils\CacheHelper;
use App\Middleware\CorsMiddleware;
use App\Utils\ApiLogger;
use Exception;

abstract class BaseApiController extends \BaseController
{
    protected $db;
    protected $requestMethod;
    protected $requestData;
    protected $queryParams;
    protected $headers;
    protected $user = null;
    protected $cacheMiddleware = null;
    protected $cachingEnabled = true;
    protected $corsMiddleware = null;
    protected $apiLogger = null;
    protected $requestLogId = null;
    protected $loggingEnabled = true;

    public function __construct()
    {
        // Get database connection
        require_once __DIR__ . '/../../server.php';
        global $conn;
        $this->db = $conn;

        // Initialize parent with database connection
        parent::__construct($this->db);

        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->queryParams = $_GET ?? [];
        $this->headers = $this->getAllHeaders();
        $this->parseRequestData();

        // Initialize CORS middleware
        $this->corsMiddleware = new CorsMiddleware([
            'allowed_origins' => ['*'],
            'supports_credentials' => true,
            'enabled' => true
        ]);

        // Handle CORS (may exit for preflight)
        $this->corsMiddleware->handle();

        // Initialize cache middleware
        $this->cacheMiddleware = new CacheMiddleware($this->db, [
            'enabled' => $this->cachingEnabled
        ]);

        // Initialize API logger
        $this->apiLogger = new ApiLogger($this->db, [
            'enabled' => $this->loggingEnabled
        ]);

        // Log incoming request
        if ($this->loggingEnabled) {
            $this->requestLogId = $this->apiLogger->logRequest();
        }
    }
    
    /**
     * Get all HTTP headers (fallback for getallheaders)
     */
    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
    
    /**
     * Parse incoming request data
     */
    protected function parseRequestData()
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $this->requestData = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                ResponseHelper::error('Invalid JSON format', 400);
            }
        } else {
            $this->requestData = $_POST;
        }
        
        // Ensure requestData is always an array
        if (!is_array($this->requestData)) {
            $this->requestData = [];
        }
    }
    
    /**
     * Set Content-Type header
     */
    protected function setContentTypeHeader()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
    }
    
    /**
     * Require authentication for the API endpoint
     */
    protected function requireAuthentication()
    {
        $token = $this->getBearerToken();
        
        if (!$token) {
            ResponseHelper::unauthorized('Authorization token required');
        }
        
        $payload = $this->validateApiToken($token);
        if (!$payload) {
            ResponseHelper::unauthorized('Invalid or expired token');
        }
        
        // Store user information from token
        $this->user = [
            'id' => $payload['user_id'],
            'role' => $payload['role'] ?? 'user'
        ];
        
        return $this->user;
    }
    
    /**
     * Require specific role for the API endpoint
     */
    protected function requireRole($requiredRole)
    {
        $user = $this->requireAuthentication();
        
        if ($user['role'] !== $requiredRole && $user['role'] !== 'admin') {
            ResponseHelper::forbidden('Insufficient permissions');
        }
        
        return $user;
    }
    
    /**
     * Extract Bearer token from Authorization header
     */
    protected function getBearerToken()
    {
        $authHeader = $this->headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Validate API token
     */
    protected function validateApiToken($token)
    {
        try {
            return ApiTokenService::validate($token);
        } catch (Exception $e) {
            Logger::error('Token validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current authenticated user
     */
    protected function getAuthenticatedUser()
    {
        return $this->user;
    }
    
    /**
     * Handle API request routing
     */
    public function handleRequest()
    {
        try {
            // Log API request for monitoring
            $this->logApiRequest();
            
            switch ($this->requestMethod) {
                case 'GET':
                    return $this->handleGet();
                case 'POST':
                    return $this->handlePost();
                case 'PUT':
                    return $this->handlePut();
                case 'DELETE':
                    return $this->handleDelete();
                case 'PATCH':
                    return $this->handlePatch();
                default:
                    ResponseHelper::error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Logger::error('API Error: ' . $e->getMessage(), [
                'method' => $this->requestMethod,
                'data' => $this->requestData,
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->user['id'] ?? null
            ]);
            
            if (defined('APP_DEBUG') && APP_DEBUG) {
                ResponseHelper::error('Internal server error: ' . $e->getMessage(), 500);
            } else {
                ResponseHelper::error('Internal server error', 500);
            }
        }
    }
    
    /**
     * Log API request for monitoring
     */
    protected function logApiRequest()
    {
        Logger::info('API Request', [
            'method' => $this->requestMethod,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_id' => $this->user['id'] ?? null,
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Validate pagination parameters
     */
    protected function getPaginationParams()
    {
        $page = max(1, (int)($this->queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int)($this->queryParams['limit'] ?? 10))); // Max 100 items per page
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Build pagination metadata
     */
    protected function buildPaginationMeta($total, $page, $limit)
    {
        $totalPages = ceil($total / $limit);
        
        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ];
    }
    
    /**
     * Send success response
     */
    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        $this->setContentTypeHeader();

        if (!headers_sent()) {
            http_response_code($code);
        }

        $responseBody = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        echo json_encode($responseBody, JSON_PRETTY_PRINT);

        // Log response
        if ($this->loggingEnabled && $this->apiLogger) {
            $this->apiLogger->logResponse($this->requestLogId, $code, $responseBody);
        }

        // Don't exit in test environment (when headers were already sent)
        if (!headers_sent() || !defined('PHPUNIT_RUNNING')) {
            return; // Return instead of exit for test compatibility
        }
    }

    /**
     * Send error response
     */
    protected function errorResponse($message, $code = 400, $errors = null)
    {
        $this->setContentTypeHeader();

        if (!headers_sent()) {
            http_response_code($code);
        }

        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);

        // Log error response
        if ($this->loggingEnabled && $this->apiLogger) {
            $this->apiLogger->logResponse($this->requestLogId, $code, $response);
        }

        // Don't exit in test environment (when headers were already sent)
        if (!headers_sent() || !defined('PHPUNIT_RUNNING')) {
            return; // Return instead of exit for test compatibility
        }
    }

    /**
     * Get user ID from authentication (session or JWT)
     */
    protected function getUserIdFromAuth()
    {
        // Check session first
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }

        // Could add JWT token checking here later
        return null;
    }

    /**
     * Log an action to the activity log
     */
    protected function logAction($action, $metadata = [])
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadataJson = json_encode($metadata);

            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, action, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param('issss', $userId, $action, $metadataJson, $ipAddress, $userAgent);
            $stmt->execute();
        } catch (Exception $e) {
            // Log but don't fail the request
            error_log("Failed to log action: " . $e->getMessage());
        }
    }

    /**
     * Cache-aware success response
     *
     * Sends a success response with caching headers.
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     * @param array $cacheOptions Cache options (optional)
     * @return void
     */
    protected function cachedSuccessResponse($data, $message = 'Success', $code = 200, $cacheOptions = [])
    {
        $this->setContentTypeHeader();

        // Send response
        if (!headers_sent()) {
            http_response_code($code);
        }

        $responseBodyArray = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        $responseBody = json_encode($responseBodyArray, JSON_PRETTY_PRINT);

        // Add cache headers
        if ($this->requestMethod === 'GET' && $this->cachingEnabled) {
            $endpoint = $_SERVER['REQUEST_URI'] ?? '';
            $this->cacheMiddleware->handleResponse(
                $endpoint,
                $this->requestMethod,
                $responseBody,
                $code,
                $cacheOptions
            );
        }

        echo $responseBody;

        // Log response
        if ($this->loggingEnabled && $this->apiLogger) {
            $this->apiLogger->logResponse($this->requestLogId, $code, $responseBodyArray);
        }
    }

    /**
     * Check conditional request and return 304 if not modified
     *
     * @return bool True if request should continue, false if 304 sent
     */
    protected function checkConditionalRequest()
    {
        if ($this->requestMethod !== 'GET' || !$this->cachingEnabled) {
            return true;
        }

        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        return $this->cacheMiddleware->handleRequest($endpoint, $this->requestMethod);
    }

    /**
     * Invalidate cache for endpoint
     *
     * @param string|null $endpoint Endpoint to invalidate (null = current endpoint)
     * @return bool True on success
     */
    protected function invalidateCache($endpoint = null)
    {
        if ($endpoint === null) {
            $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        }

        return $this->cacheMiddleware->invalidateEndpoint($endpoint);
    }

    /**
     * Invalidate cache by pattern
     *
     * @param string $pattern Endpoint pattern (e.g., "/api/v1/users/%")
     * @return bool True on success
     */
    protected function invalidateCachePattern($pattern)
    {
        return $this->cacheMiddleware->invalidatePattern($pattern);
    }

    /**
     * Disable caching for current request
     *
     * @return void
     */
    protected function disableCaching()
    {
        $this->cachingEnabled = false;
    }

    /**
     * Enable caching for current request
     *
     * @return void
     */
    protected function enableCaching()
    {
        $this->cachingEnabled = true;
    }

    /**
     * Abstract methods that must be implemented by child classes
     * Made optional with default implementations
     */
    protected function handleGet()
    {
        $this->errorResponse('Method not implemented', 501);
    }

    protected function handlePost()
    {
        $this->errorResponse('Method not implemented', 501);
    }

    protected function handlePut()
    {
        $this->errorResponse('Method not implemented', 501);
    }

    protected function handleDelete()
    {
        $this->errorResponse('Method not implemented', 501);
    }

    /**
     * Optional PATCH method (defaults to PUT behavior)
     */
    protected function handlePatch()
    {
        return $this->handlePut();
    }
}