<?php

namespace App\API;

use App\Controllers\BaseController;
use App\Utils\ResponseHelper;
use App\Traits\ValidatesData;
use App\Services\ApiTokenService;
use App\Utils\Logger;
use Exception;

abstract class BaseApiController extends BaseController
{
    use ValidatesData;
    
    protected $requestMethod;
    protected $requestData;
    protected $queryParams;
    protected $headers;
    protected $user = null;
    
    public function __construct($db)
    {
        parent::__construct($db);
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->queryParams = $_GET;
        $this->headers = $this->getAllHeaders();
        $this->parseRequestData();
        $this->setCorsHeaders();
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
     * Set CORS headers for API responses
     */
    protected function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
        header('Content-Type: application/json; charset=UTF-8');
        
        // Handle preflight OPTIONS request
        if ($this->requestMethod === 'OPTIONS') {
            http_response_code(200);
            exit();
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
     * Abstract methods that must be implemented by child classes
     */
    abstract protected function handleGet();
    abstract protected function handlePost();
    abstract protected function handlePut();
    abstract protected function handleDelete();
    
    /**
     * Optional PATCH method (defaults to PUT behavior)
     */
    protected function handlePatch()
    {
        return $this->handlePut();
    }
}