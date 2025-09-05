<?php

namespace App\Utils;

class ResponseHelper
{
    /**
     * Send successful response
     */
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'status_code' => $code,
            'message' => $message,
            'timestamp' => date('c'),
            'data' => $data
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null, $debug = null)
    {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'status_code' => $code,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        // Add errors if provided
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Add debug information in development
        if ($debug !== null && (defined('APP_DEBUG') && APP_DEBUG)) {
            $response['debug'] = $debug;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Success')
    {
        $response = [
            'items' => $data,
            'pagination' => $pagination
        ];
        
        self::success($response, $message);
    }
    
    /**
     * Send created response (201)
     */
    public static function created($data = null, $message = 'Resource created successfully')
    {
        self::success($data, $message, 201);
    }
    
    /**
     * Send updated response (200)
     */
    public static function updated($data = null, $message = 'Resource updated successfully')
    {
        self::success($data, $message, 200);
    }
    
    /**
     * Send deleted response (200)
     */
    public static function deleted($message = 'Resource deleted successfully')
    {
        self::success(null, $message, 200);
    }
    
    /**
     * Send not found response (404)
     */
    public static function notFound($message = 'Resource not found')
    {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response (401)
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response (403)
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }
    
    /**
     * Send validation error response (422)
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send rate limit exceeded response (429)
     */
    public static function rateLimitExceeded($message = 'Too many requests', $retryAfter = null)
    {
        if ($retryAfter) {
            header('Retry-After: ' . $retryAfter);
        }
        
        self::error($message, 429);
    }
    
    /**
     * Send method not allowed response (405)
     */
    public static function methodNotAllowed($allowedMethods = [], $message = 'Method not allowed')
    {
        if (!empty($allowedMethods)) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }
        
        self::error($message, 405);
    }
    
    /**
     * Send conflict response (409)
     */
    public static function conflict($message = 'Conflict', $errors = null)
    {
        self::error($message, 409, $errors);
    }
    
    /**
     * Send bad request response (400)
     */
    public static function badRequest($message = 'Bad request', $errors = null)
    {
        self::error($message, 400, $errors);
    }
    
    /**
     * Send internal server error response (500)
     */
    public static function internalError($message = 'Internal server error', $debug = null)
    {
        self::error($message, 500, null, $debug);
    }
    
    /**
     * Send service unavailable response (503)
     */
    public static function serviceUnavailable($message = 'Service unavailable', $retryAfter = null)
    {
        if ($retryAfter) {
            header('Retry-After: ' . $retryAfter);
        }
        
        self::error($message, 503);
    }
    
    /**
     * Send no content response (204)
     */
    public static function noContent()
    {
        http_response_code(204);
        exit();
    }
    
    /**
     * Send accepted response (202)
     */
    public static function accepted($data = null, $message = 'Accepted')
    {
        self::success($data, $message, 202);
    }
    
    /**
     * Build standardized API response structure
     */
    public static function buildResponse($success, $data = null, $message = '', $statusCode = 200, $errors = null)
    {
        $response = [
            'success' => $success,
            'status_code' => $statusCode,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }
    
    /**
     * Send custom response
     */
    public static function custom($data, $statusCode = 200, $headers = [])
    {
        http_response_code($statusCode);
        
        // Set additional headers
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        
        if (is_array($data) || is_object($data)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo $data;
        }
        
        exit();
    }
}