# Error Handling Guide

## Overview

The Sci-Bono LMS API uses conventional HTTP response codes to indicate success or failure of requests. Error responses include detailed information to help developers debug issues.

## HTTP Status Codes

### Success Codes
- `200 OK` - Request was successful
- `201 Created` - Resource was created successfully
- `202 Accepted` - Request accepted for processing
- `204 No Content` - Request successful, no content to return

### Client Error Codes
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required or invalid
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `409 Conflict` - Resource conflict (e.g., duplicate email)
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limit exceeded

### Server Error Codes
- `500 Internal Server Error` - Unexpected server error
- `502 Bad Gateway` - Server temporarily unavailable
- `503 Service Unavailable` - Server maintenance

## Error Response Format

All error responses follow a consistent format:

```json
{
  "success": false,
  "error": "Error Type",
  "message": "Human-readable error description",
  "code": 400,
  "errors": {
    "field": ["Field-specific error messages"]
  },
  "debug": {
    "request_id": "uuid",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

## Common Error Scenarios

### Authentication Errors

#### Invalid Credentials (401)
```json
{
  "success": false,
  "error": "Invalid Credentials",
  "message": "The provided email and password do not match our records",
  "code": 401
}
```

#### Expired Token (401)
```json
{
  "success": false,
  "error": "Token Expired",
  "message": "Your session has expired. Please log in again",
  "code": 401
}
```

#### Insufficient Permissions (403)
```json
{
  "success": false,
  "error": "Forbidden",
  "message": "You don't have permission to access this resource",
  "code": 403
}
```

### Validation Errors (422)

```json
{
  "success": false,
  "error": "Validation Failed",
  "message": "The provided data is invalid",
  "code": 422,
  "errors": {
    "email": [
      "Email address is required",
      "Email format is invalid"
    ],
    "password": [
      "Password must be at least 8 characters",
      "Password must contain at least one number"
    ]
  }
}
```

### Rate Limiting (429)

```json
{
  "success": false,
  "error": "Rate Limit Exceeded",
  "message": "Too many requests. You can try again in 60 seconds",
  "code": 429,
  "retry_after": 60
}
```

### Resource Not Found (404)

```json
{
  "success": false,
  "error": "Resource Not Found",
  "message": "The requested user with ID 999 was not found",
  "code": 404
}
```

### Server Errors (500)

```json
{
  "success": false,
  "error": "Internal Server Error",
  "message": "An unexpected error occurred. Please try again later",
  "code": 500,
  "debug": {
    "request_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

## Error Handling Best Practices

### Client-Side Error Handling

```javascript
async function makeApiCall(endpoint, options = {}) {
  try {
    const response = await fetch(endpoint, options);
    const data = await response.json();
    
    if (!response.ok) {
      throw new ApiError(data, response.status);
    }
    
    return data;
  } catch (error) {
    if (error instanceof ApiError) {
      handleApiError(error);
    } else {
      handleNetworkError(error);
    }
    throw error;
  }
}

class ApiError extends Error {
  constructor(errorData, status) {
    super(errorData.message);
    this.name = 'ApiError';
    this.status = status;
    this.errorData = errorData;
  }
}

function handleApiError(error) {
  switch (error.status) {
    case 401:
      // Redirect to login or refresh token
      redirectToLogin();
      break;
    case 403:
      showPermissionError();
      break;
    case 422:
      showValidationErrors(error.errorData.errors);
      break;
    case 429:
      showRateLimitMessage(error.errorData.retry_after);
      break;
    default:
      showGenericError(error.message);
  }
}
```

### PHP Error Handling

```php
class ApiClient 
{
    public function makeRequest($endpoint, $options = []) 
    {
        $response = $this->httpClient->request($endpoint, $options);
        
        if (!$response['success']) {
            $this->handleError($response);
        }
        
        return $response;
    }
    
    private function handleError($errorResponse) 
    {
        switch ($errorResponse['code']) {
            case 401:
                throw new UnauthorizedException($errorResponse['message']);
            case 403:
                throw new ForbiddenException($errorResponse['message']);
            case 422:
                throw new ValidationException($errorResponse['errors']);
            case 429:
                throw new RateLimitException($errorResponse['retry_after']);
            default:
                throw new ApiException($errorResponse['message'], $errorResponse['code']);
        }
    }
}
```

## Debugging Tips

### Request Debugging
1. Check request headers (Authorization, Content-Type)
2. Verify request method and URL
3. Validate request body format
4. Check API endpoint availability

### Response Debugging
1. Examine HTTP status code
2. Review error message and code
3. Check field-specific validation errors
4. Look for debug information (development only)

### Common Issues

#### "Invalid JSON" Error
- Ensure Content-Type header is `application/json`
- Validate JSON syntax in request body
- Check for trailing commas or syntax errors

#### "Token Invalid" Error  
- Verify token format (should start with "Bearer ")
- Check token expiration
- Ensure token was copied completely

#### "Permission Denied" Error
- Verify user has correct role/permissions
- Check if resource belongs to authenticated user
- Confirm API endpoint allows the requested action
