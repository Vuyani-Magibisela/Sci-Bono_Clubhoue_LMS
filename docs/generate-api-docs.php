<?php
/**
 * API Documentation Generator
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * This script generates comprehensive API documentation from the 
 * OpenAPI specification and predefined endpoint data
 */

class ApiDocumentationGenerator
{
    private $outputDir;
    private $templateDir;
    private $endpoints = [];
    
    public function __construct($outputDir = null)
    {
        $this->outputDir = $outputDir ?: __DIR__ . '/generated';
        $this->templateDir = __DIR__ . '/templates';
        
        $this->defineEndpoints();
    }
    
    /**
     * Generate all documentation formats
     */
    public function generateAll()
    {
        echo "🚀 Starting API documentation generation...\n\n";
        
        $this->createOutputDirectories();
        $this->generateMarkdownDocs();
        $this->generateHtmlDocs();
        $this->generatePostmanCollection();
        $this->generateCodeExamples();
        
        echo "✅ API documentation generation completed!\n";
        echo "📁 Output directory: {$this->outputDir}\n\n";
    }
    
    /**
     * Define API endpoints manually
     */
    private function defineEndpoints()
    {
        $this->endpoints = [
            // Authentication endpoints
            [
                'controller' => 'AuthController',
                'method' => 'handleLogin',
                'http_method' => 'POST',
                'path' => '/auth/login',
                'description' => 'User authentication and JWT token generation',
                'parameters' => [
                    ['name' => 'identifier', 'type' => 'string', 'description' => 'Username or email address'],
                    ['name' => 'password', 'type' => 'string', 'description' => 'User password']
                ],
                'responses' => ['200' => 'Success', '401' => 'Invalid credentials', '429' => 'Rate limit exceeded']
            ],
            [
                'controller' => 'AuthController',
                'method' => 'handleRefresh',
                'http_method' => 'POST',
                'path' => '/auth/refresh',
                'description' => 'Refresh JWT token',
                'parameters' => [],
                'responses' => ['200' => 'Token refreshed', '401' => 'Invalid token']
            ],
            [
                'controller' => 'AuthController',
                'method' => 'handleLogout',
                'http_method' => 'POST',
                'path' => '/auth/logout',
                'description' => 'Invalidate JWT token and logout',
                'parameters' => [],
                'responses' => ['200' => 'Logout successful', '401' => 'Invalid token']
            ],
            
            // User management endpoints
            [
                'controller' => 'UserApiController',
                'method' => 'handleGet',
                'http_method' => 'GET',
                'path' => '/users',
                'description' => 'Get paginated list of users with optional filtering',
                'parameters' => [
                    ['name' => 'page', 'type' => 'integer', 'description' => 'Page number for pagination'],
                    ['name' => 'limit', 'type' => 'integer', 'description' => 'Number of users per page'],
                    ['name' => 'search', 'type' => 'string', 'description' => 'Search term for filtering'],
                    ['name' => 'user_type', 'type' => 'string', 'description' => 'Filter by user type'],
                    ['name' => 'status', 'type' => 'string', 'description' => 'Filter by user status']
                ],
                'responses' => ['200' => 'Success', '401' => 'Unauthorized', '403' => 'Forbidden']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handlePost',
                'http_method' => 'POST',
                'path' => '/users',
                'description' => 'Create a new user account',
                'parameters' => [
                    ['name' => 'name', 'type' => 'string', 'description' => 'User first name'],
                    ['name' => 'surname', 'type' => 'string', 'description' => 'User surname'],
                    ['name' => 'email', 'type' => 'string', 'description' => 'User email address (required)'],
                    ['name' => 'password', 'type' => 'string', 'description' => 'User password (required)'],
                    ['name' => 'user_type', 'type' => 'string', 'description' => 'User role type']
                ],
                'responses' => ['201' => 'User created', '400' => 'Invalid data', '422' => 'Validation error']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handleGetById',
                'http_method' => 'GET',
                'path' => '/users/{id}',
                'description' => 'Get a specific user by ID',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID']
                ],
                'responses' => ['200' => 'Success', '404' => 'User not found', '401' => 'Unauthorized']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handlePut',
                'http_method' => 'PUT',
                'path' => '/users/{id}',
                'description' => 'Update an existing user',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID'],
                    ['name' => 'name', 'type' => 'string', 'description' => 'User first name'],
                    ['name' => 'surname', 'type' => 'string', 'description' => 'User surname'],
                    ['name' => 'user_type', 'type' => 'string', 'description' => 'User role type'],
                    ['name' => 'status', 'type' => 'string', 'description' => 'User account status']
                ],
                'responses' => ['200' => 'User updated', '400' => 'Invalid data', '404' => 'User not found']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handleDelete',
                'http_method' => 'DELETE',
                'path' => '/users/{id}',
                'description' => 'Delete a user account',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID']
                ],
                'responses' => ['200' => 'User deleted', '404' => 'User not found', '403' => 'Forbidden']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handleGetProfile',
                'http_method' => 'GET',
                'path' => '/users/{id}/profile',
                'description' => 'Get detailed user profile information',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID']
                ],
                'responses' => ['200' => 'Success', '404' => 'User not found']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handleUpdateProfile',
                'http_method' => 'PUT',
                'path' => '/users/{id}/profile',
                'description' => 'Update user profile information',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID'],
                    ['name' => 'phone', 'type' => 'string', 'description' => 'Phone number'],
                    ['name' => 'address', 'type' => 'string', 'description' => 'Physical address'],
                    ['name' => 'bio', 'type' => 'string', 'description' => 'User biography']
                ],
                'responses' => ['200' => 'Profile updated', '400' => 'Invalid data', '404' => 'User not found']
            ],
            [
                'controller' => 'UserApiController',
                'method' => 'handleChangePassword',
                'http_method' => 'POST',
                'path' => '/users/{id}/change-password',
                'description' => 'Change user password',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'description' => 'User ID'],
                    ['name' => 'current_password', 'type' => 'string', 'description' => 'Current password'],
                    ['name' => 'new_password', 'type' => 'string', 'description' => 'New password']
                ],
                'responses' => ['200' => 'Password changed', '400' => 'Invalid password', '401' => 'Incorrect current password']
            ]
        ];
        
        echo "📊 Defined " . count($this->endpoints) . " API endpoints\n";
    }
    
    /**
     * Create output directories
     */
    private function createOutputDirectories()
    {
        $dirs = [
            $this->outputDir,
            $this->outputDir . '/html',
            $this->outputDir . '/markdown',
            $this->outputDir . '/postman',
            $this->outputDir . '/examples'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Generate Markdown documentation
     */
    private function generateMarkdownDocs()
    {
        echo "📝 Generating Markdown documentation...\n";
        
        $this->generateOverviewMarkdown();
        $this->generateAuthenticationMarkdown();
        $this->generateEndpointsMarkdown();
        $this->generateErrorHandlingMarkdown();
        $this->generateQuickStartGuide();
    }
    
    /**
     * Generate overview documentation
     */
    private function generateOverviewMarkdown()
    {
        $content = <<<MARKDOWN
# Sci-Bono Clubhouse LMS API Documentation

## Overview

The Sci-Bono Clubhouse LMS API is a comprehensive RESTful API that provides complete functionality for managing the Learning Management System. This API enables developers to integrate with the LMS platform and build custom applications.

## API Information

- **Version**: 1.0.0
- **Base URL**: `http://localhost/Sci-Bono_Clubhoue_LMS/app/API`
- **Protocol**: HTTP/HTTPS
- **Response Format**: JSON
- **Authentication**: JWT (JSON Web Tokens)

## Features

### User Management
- User registration and authentication
- Profile management
- Role-based access control (Admin, Mentor, Member, Student)
- Password management and reset

### Course Management
- Course creation and management
- Lesson organization
- Progress tracking
- Enrollment management

### Holiday Program Management
- Program registration
- Workshop selection
- Capacity management
- Participant tracking

### Attendance System
- Real-time attendance tracking
- Check-in/check-out functionality
- Attendance reports
- Statistical analysis

### Administrative Features
- Comprehensive dashboard analytics
- User management
- System configuration
- Reporting and exports

## API Standards

### HTTP Methods
- `GET` - Retrieve data
- `POST` - Create new resources
- `PUT` - Update existing resources
- `DELETE` - Remove resources

### Response Codes
- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### Data Format
All API endpoints accept and return JSON data with the following structure:

```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully",
  "pagination": {},
  "errors": []
}
```

## Rate Limiting

API requests are limited based on user authentication:

| User Type | Requests per Minute |
|-----------|-------------------|
| Guest     | 20                |
| Student   | 100               |
| Member    | 200               |
| Mentor    | 500               |
| Admin     | 1000              |

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when limit resets

## Getting Started

1. [Authentication Guide](authentication.md)
2. [Quick Start Tutorial](quickstart.md)
3. [API Endpoints Reference](endpoints.md)
4. [Error Handling Guide](errors.md)
5. [Code Examples](examples.md)

## Support

For technical support or questions about the API:
- Email: dev@sci-bono.co.za
- Documentation: [API Documentation](https://docs.sci-bono-lms.com)
- GitHub Issues: [Report Issues](https://github.com/sci-bono/lms-api/issues)

MARKDOWN;

        file_put_contents($this->outputDir . '/markdown/README.md', $content);
    }
    
    /**
     * Generate authentication documentation
     */
    private function generateAuthenticationMarkdown()
    {
        $content = <<<MARKDOWN
# Authentication Guide

## Overview

The Sci-Bono LMS API uses JWT (JSON Web Tokens) for authentication. This provides a secure, stateless authentication mechanism that's perfect for API integrations.

## Authentication Flow

### 1. Login Request

Send a POST request to `/auth/login` with user credentials:

```bash
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{
    "identifier": "user@example.com",
    "password": "your-password"
  }'
```

### 2. Login Response

Successful login returns a JWT token:

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_at": "2024-01-15T14:30:00Z",
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "user@example.com",
      "user_type": "student"
    }
  }
}
```

### 3. Using the Token

Include the JWT token in the Authorization header for subsequent requests:

```bash
curl -X GET \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \\
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
```

## Token Management

### Token Expiration

JWT tokens expire after 1 hour. Use the refresh token to get a new access token:

```bash
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/refresh \\
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
```

### Token Refresh Response

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_at": "2024-01-15T15:30:00Z"
  }
}
```

### Logout

Invalidate the current token:

```bash
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/logout \\
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
```

## User Roles and Permissions

### Role Hierarchy

1. **Admin** - Full system access
2. **Mentor** - Manage courses and students
3. **Member** - Access to member features
4. **Student** - Basic user access

### Permission Matrix

| Action | Student | Member | Mentor | Admin |
|--------|---------|---------|---------|-------|
| View own profile | ✓ | ✓ | ✓ | ✓ |
| Update own profile | ✓ | ✓ | ✓ | ✓ |
| View other profiles | ✗ | ✗ | Limited | ✓ |
| Create users | ✗ | ✗ | Limited | ✓ |
| Delete users | ✗ | ✗ | ✗ | ✓ |
| Manage courses | ✗ | ✗ | ✓ | ✓ |
| Access admin panel | ✗ | ✗ | ✗ | ✓ |

## Security Best Practices

### Token Security
- Store tokens securely (never in localStorage for web apps)
- Use HTTPS in production
- Implement token refresh logic
- Handle token expiration gracefully

### API Security
- Validate all input data
- Use proper HTTP status codes
- Implement rate limiting
- Log authentication attempts

### Development vs Production

#### Development
- Tokens expire in 1 hour
- Rate limiting is relaxed
- Debug information included in errors

#### Production  
- Tokens expire in 15 minutes
- Strict rate limiting
- Minimal error information
- All traffic over HTTPS

## Error Handling

### Authentication Errors

#### 401 Unauthorized
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Invalid or expired token",
  "code": 401
}
```

#### 403 Forbidden
```json
{
  "success": false,
  "error": "Forbidden",
  "message": "Insufficient permissions",
  "code": 403
}
```

#### 429 Rate Limited
```json
{
  "success": false,
  "error": "Rate Limit Exceeded",
  "message": "Too many requests. Try again later.",
  "code": 429
}
```

## Code Examples

### JavaScript/Node.js

```javascript
const axios = require('axios');

class SciBonolmsApi {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.token = null;
  }
  
  async login(identifier, password) {
    try {
      const response = await axios.post(`\${this.baseUrl}/auth/login`, {
        identifier,
        password
      });
      
      this.token = response.data.data.token;
      return response.data;
    } catch (error) {
      throw new Error(`Login failed: \${error.response?.data?.message}`);
    }
  }
  
  async apiCall(method, endpoint, data = null) {
    const config = {
      method,
      url: `\${this.baseUrl}\${endpoint}`,
      headers: {
        'Authorization': `Bearer \${this.token}`,
        'Content-Type': 'application/json'
      }
    };
    
    if (data) {
      config.data = data;
    }
    
    try {
      const response = await axios(config);
      return response.data;
    } catch (error) {
      if (error.response?.status === 401) {
        // Token expired, try to refresh
        await this.refreshToken();
        return this.apiCall(method, endpoint, data);
      }
      throw error;
    }
  }
}
```

### PHP

```php
class SciBonolmsApiClient 
{
    private \$baseUrl;
    private \$token;
    
    public function __construct(\$baseUrl) 
    {
        \$this->baseUrl = \$baseUrl;
    }
    
    public function login(\$identifier, \$password) 
    {
        \$data = [
            'identifier' => \$identifier,
            'password' => \$password
        ];
        
        \$response = \$this->makeRequest('POST', '/auth/login', \$data);
        
        if (\$response['success']) {
            \$this->token = \$response['data']['token'];
            return \$response;
        }
        
        throw new Exception('Login failed: ' . \$response['message']);
    }
    
    private function makeRequest(\$method, \$endpoint, \$data = null) 
    {
        \$url = \$this->baseUrl . \$endpoint;
        \$headers = ['Content-Type: application/json'];
        
        if (\$this->token) {
            \$headers[] = 'Authorization: Bearer ' . \$this->token;
        }
        
        \$ch = curl_init();
        curl_setopt(\$ch, CURLOPT_URL, \$url);
        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \$method);
        
        if (\$data) {
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$data));
        }
        
        \$response = curl_exec(\$ch);
        curl_close(\$ch);
        
        return json_decode(\$response, true);
    }
}
```

MARKDOWN;

        file_put_contents($this->outputDir . '/markdown/authentication.md', $content);
    }
    
    /**
     * Generate endpoints documentation
     */
    private function generateEndpointsMarkdown()
    {
        $content = "# API Endpoints Reference\n\n";
        $content .= "This document provides detailed information about all available API endpoints.\n\n";
        
        $groupedEndpoints = [];
        foreach ($this->endpoints as $endpoint) {
            $group = str_replace(['Api', 'Controller'], '', $endpoint['controller']);
            $groupedEndpoints[$group][] = $endpoint;
        }
        
        foreach ($groupedEndpoints as $group => $endpoints) {
            $content .= "## {$group} Endpoints\n\n";
            
            foreach ($endpoints as $endpoint) {
                $content .= "### {$endpoint['http_method']} {$endpoint['path']}\n\n";
                $content .= $endpoint['description'] . "\n\n";
                
                if (!empty($endpoint['parameters'])) {
                    $content .= "**Parameters:**\n\n";
                    foreach ($endpoint['parameters'] as $param) {
                        $content .= "- `{$param['name']}` ({$param['type']}) - {$param['description']}\n";
                    }
                    $content .= "\n";
                }
                
                $content .= "**Example Request:**\n\n";
                $content .= "```bash\n";
                $content .= "curl -X {$endpoint['http_method']} \\\n";
                $content .= "  http://localhost/Sci-Bono_Clubhoue_LMS/app/API{$endpoint['path']} \\\n";
                $content .= "  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\\n";
                $content .= "  -H 'Content-Type: application/json'\n";
                $content .= "```\n\n";
                
                $content .= "---\n\n";
            }
        }
        
        file_put_contents($this->outputDir . '/markdown/endpoints.md', $content);
    }
    
    /**
     * Generate error handling documentation
     */
    private function generateErrorHandlingMarkdown()
    {
        $content = <<<MARKDOWN
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
    public function makeRequest(\$endpoint, \$options = []) 
    {
        \$response = \$this->httpClient->request(\$endpoint, \$options);
        
        if (!\$response['success']) {
            \$this->handleError(\$response);
        }
        
        return \$response;
    }
    
    private function handleError(\$errorResponse) 
    {
        switch (\$errorResponse['code']) {
            case 401:
                throw new UnauthorizedException(\$errorResponse['message']);
            case 403:
                throw new ForbiddenException(\$errorResponse['message']);
            case 422:
                throw new ValidationException(\$errorResponse['errors']);
            case 429:
                throw new RateLimitException(\$errorResponse['retry_after']);
            default:
                throw new ApiException(\$errorResponse['message'], \$errorResponse['code']);
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

MARKDOWN;

        file_put_contents($this->outputDir . '/markdown/errors.md', $content);
    }
    
    /**
     * Generate quick start guide
     */
    private function generateQuickStartGuide()
    {
        $content = <<<MARKDOWN
# Quick Start Guide

This guide will help you get started with the Sci-Bono LMS API in minutes.

## Prerequisites

- Access to the Sci-Bono LMS system
- Valid user credentials (email/username and password)
- API client (cURL, Postman, or programming language HTTP library)

## Step 1: Authentication

First, obtain a JWT token by logging in:

```bash
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{
    "identifier": "your-email@example.com",
    "password": "your-password"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "your-email@example.com"
    }
  }
}
```

📝 **Save the token** - you'll need it for subsequent API calls!

## Step 2: Make Your First API Call

Use the token to get your user profile:

```bash
curl -X GET \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John",
    "surname": "Doe",
    "email": "your-email@example.com",
    "user_type": "student",
    "status": "active",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

## Step 3: Explore Common Operations

### Get All Users (Admin/Mentor only)
```bash
curl -X GET \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE'
```

### Update Your Profile
```bash
curl -X PUT \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "John Updated",
    "phone": "+27123456789"
  }'
```

### Change Password
```bash
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123/change-password \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "current_password": "old-password",
    "new_password": "new-secure-password"
  }'
```

## Quick Start with Different Languages

### JavaScript (Browser/Node.js)

```javascript
// Simple API client
class SciBonolmsApi {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.token = localStorage.getItem('api_token');
  }
  
  async login(identifier, password) {
    const response = await fetch(\`\${this.baseUrl}/auth/login\`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ identifier, password })
    });
    
    const data = await response.json();
    if (data.success) {
      this.token = data.data.token;
      localStorage.setItem('api_token', this.token);
    }
    return data;
  }
  
  async get(endpoint) {
    const response = await fetch(\`\${this.baseUrl}\${endpoint}\`, {
      headers: { 'Authorization': \`Bearer \${this.token}\` }
    });
    return response.json();
  }
}

// Usage
const api = new SciBonolmsApi('http://localhost/Sci-Bono_Clubhoue_LMS/app/API');
await api.login('user@example.com', 'password');
const profile = await api.get('/users/123');
```

### Python

```python
import requests

class SciBonolmsApi:
    def __init__(self, base_url):
        self.base_url = base_url
        self.token = None
    
    def login(self, identifier, password):
        response = requests.post(
            f'{self.base_url}/auth/login',
            json={'identifier': identifier, 'password': password}
        )
        data = response.json()
        if data.get('success'):
            self.token = data['data']['token']
        return data
    
    def get(self, endpoint):
        response = requests.get(
            f'{self.base_url}{endpoint}',
            headers={'Authorization': f'Bearer {self.token}'}
        )
        return response.json()

# Usage
api = SciBonolmsApi('http://localhost/Sci-Bono_Clubhoue_LMS/app/API')
api.login('user@example.com', 'password')
profile = api.get('/users/123')
```

### PHP

```php
class SciBonolmsApi {
    private \$baseUrl;
    private \$token;
    
    public function __construct(\$baseUrl) {
        \$this->baseUrl = \$baseUrl;
    }
    
    public function login(\$identifier, \$password) {
        \$data = ['identifier' => \$identifier, 'password' => \$password];
        \$response = \$this->post('/auth/login', \$data);
        
        if (\$response['success']) {
            \$this->token = \$response['data']['token'];
        }
        return \$response;
    }
    
    public function get(\$endpoint) {
        return \$this->makeRequest('GET', \$endpoint);
    }
    
    private function makeRequest(\$method, \$endpoint, \$data = null) {
        \$url = \$this->baseUrl . \$endpoint;
        \$headers = ['Content-Type: application/json'];
        
        if (\$this->token) {
            \$headers[] = 'Authorization: Bearer ' . \$this->token;
        }
        
        \$ch = curl_init();
        curl_setopt(\$ch, CURLOPT_URL, \$url);
        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \$method);
        
        if (\$data) {
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$data));
        }
        
        \$response = curl_exec(\$ch);
        curl_close(\$ch);
        
        return json_decode(\$response, true);
    }
}

// Usage
\$api = new SciBonolmsApi('http://localhost/Sci-Bono_Clubhoue_LMS/app/API');
\$api->login('user@example.com', 'password');
\$profile = \$api->get('/users/123');
```

## Testing with Postman

1. **Import Collection**: Import the provided Postman collection
2. **Set Environment**: Configure base URL and credentials
3. **Login**: Use the login request to get a token
4. **Auto-token**: Collection automatically uses token for authenticated requests

## Next Steps

1. 📖 Read the [Complete API Documentation](README.md)
2. 🔒 Review [Authentication Guide](authentication.md)  
3. 📋 Explore [All API Endpoints](endpoints.md)
4. ⚠️ Learn [Error Handling](errors.md)
5. 💻 Check [Code Examples](examples.md)

## Need Help?

- 📧 Email: dev@sci-bono.co.za
- 📖 Documentation: [Full API Docs](https://docs.sci-bono-lms.com)
- 🐛 Issues: [GitHub Issues](https://github.com/sci-bono/lms-api/issues)

Happy coding! 🚀

MARKDOWN;

        file_put_contents($this->outputDir . '/markdown/quickstart.md', $content);
    }
    
    /**
     * Generate HTML documentation
     */
    private function generateHtmlDocs()
    {
        echo "🌐 Generating HTML documentation...\n";
        
        $this->generateHtmlIndex();
        $this->generateInteractiveApiExplorer();
    }
    
    /**
     * Generate HTML index page
     */
    private function generateHtmlIndex()
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono LMS API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: #2c3e50; color: white; padding: 2rem 0; margin-bottom: 2rem; }
        h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .subtitle { font-size: 1.2rem; opacity: 0.8; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 2rem 0; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h3 { color: #2c3e50; margin-bottom: 1rem; }
        .btn { display: inline-block; background: #3498db; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
        .highlight { background: #f8f9fa; padding: 1rem; border-left: 4px solid #3498db; margin: 1rem 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 5px; overflow-x: auto; }
        .nav { background: #34495e; padding: 1rem 0; }
        .nav a { color: white; text-decoration: none; margin-right: 2rem; }
        .nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🚀 Sci-Bono LMS API</h1>
            <p class="subtitle">Comprehensive RESTful API Documentation</p>
        </div>
    </header>
    
    <nav class="nav">
        <div class="container">
            <a href="#overview">Overview</a>
            <a href="#quickstart">Quick Start</a>
            <a href="#authentication">Authentication</a>
            <a href="#endpoints">Endpoints</a>
            <a href="#examples">Examples</a>
        </div>
    </nav>
    
    <div class="container">
        <section id="overview">
            <h2>📋 Overview</h2>
            <p>The Sci-Bono Clubhouse LMS API provides complete functionality for managing users, courses, holiday programs, and attendance tracking. This RESTful API uses JWT authentication and returns JSON responses.</p>
            
            <div class="highlight">
                <strong>🎯 Key Features:</strong> User Management, Course Management, Holiday Programs, Attendance Tracking, Role-based Access Control
            </div>
        </section>
        
        <div class="grid">
            <div class="card">
                <h3>🔐 Authentication</h3>
                <p>Secure JWT-based authentication with role-based access control. Support for token refresh and logout.</p>
                <a href="authentication.html" class="btn">View Auth Guide</a>
            </div>
            
            <div class="card">
                <h3>👥 User Management</h3>
                <p>Complete CRUD operations for user accounts, profiles, and permissions management.</p>
                <a href="endpoints.html#users" class="btn">View Endpoints</a>
            </div>
            
            <div class="card">
                <h3>📚 API Reference</h3>
                <p>Detailed documentation of all API endpoints with examples and response schemas.</p>
                <a href="api-explorer.html" class="btn">Explore API</a>
            </div>
            
            <div class="card">
                <h3>🚀 Quick Start</h3>
                <p>Get up and running with the API in minutes using our step-by-step guide.</p>
                <a href="quickstart.html" class="btn">Start Now</a>
            </div>
            
            <div class="card">
                <h3>💻 Code Examples</h3>
                <p>Ready-to-use code examples in JavaScript, PHP, Python, and cURL.</p>
                <a href="examples.html" class="btn">View Examples</a>
            </div>
            
            <div class="card">
                <h3>⚠️ Error Handling</h3>
                <p>Comprehensive guide to API error responses and best practices for error handling.</p>
                <a href="errors.html" class="btn">Error Guide</a>
            </div>
        </div>
        
        <section id="quickstart">
            <h2>⚡ Quick Start Example</h2>
            <p>Get started with a simple authentication example:</p>
            
            <pre><code># 1. Login to get JWT token
curl -X POST \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{
    "identifier": "user@example.com",
    "password": "your-password"
  }'

# 2. Use token for authenticated requests
curl -X GET \\
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'</code></pre>
        </section>
        
        <section id="api-info">
            <h2>📊 API Information</h2>
            <div class="grid">
                <div class="card">
                    <h4>Base URL</h4>
                    <code>http://localhost/Sci-Bono_Clubhoue_LMS/app/API</code>
                </div>
                <div class="card">
                    <h4>Response Format</h4>
                    <code>application/json</code>
                </div>
                <div class="card">
                    <h4>Authentication</h4>
                    <code>JWT Bearer Token</code>
                </div>
                <div class="card">
                    <h4>Rate Limiting</h4>
                    <code>100-1000 req/min (role-based)</code>
                </div>
            </div>
        </section>
    </div>
    
    <footer style="background: #2c3e50; color: white; text-align: center; padding: 2rem; margin-top: 3rem;">
        <p>&copy; 2024 Sci-Bono Clubhouse LMS. Built with ❤️ for education.</p>
    </footer>
</body>
</html>
HTML;

        file_put_contents($this->outputDir . '/html/index.html', $html);
    }
    
    /**
     * Generate interactive API explorer
     */
    private function generateInteractiveApiExplorer()
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Explorer - Sci-Bono LMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Monaco', 'Menlo', monospace; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        header { text-align: center; margin-bottom: 2rem; }
        h1 { color: #4fc3f7; font-size: 2rem; margin-bottom: 0.5rem; }
        .explorer { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; height: 80vh; }
        .panel { background: #252526; border-radius: 8px; padding: 1rem; overflow-y: auto; }
        .request-panel { border-top: 3px solid #4caf50; }
        .response-panel { border-top: 3px solid #ff9800; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #569cd6; }
        input, select, textarea { width: 100%; padding: 0.5rem; background: #3c3c3c; border: 1px solid #555; color: #d4d4d4; border-radius: 4px; }
        button { background: #007acc; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; }
        button:hover { background: #005a9e; }
        .response { background: #1e1e1e; padding: 1rem; border-radius: 4px; white-space: pre-wrap; font-family: 'Monaco', monospace; font-size: 0.9rem; }
        .endpoint { background: #2d2d30; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; cursor: pointer; }
        .endpoint:hover { background: #3e3e42; }
        .method { padding: 0.25rem 0.5rem; border-radius: 3px; font-weight: bold; margin-right: 0.5rem; }
        .get { background: #4caf50; }
        .post { background: #2196f3; }
        .put { background: #ff9800; }
        .delete { background: #f44336; }
        .auth-section { background: #2d2d30; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 API Explorer</h1>
            <p>Interactive tool for testing Sci-Bono LMS API endpoints</p>
        </header>
        
        <div class="explorer">
            <div class="panel request-panel">
                <h2>🚀 Request</h2>
                
                <div class="auth-section">
                    <h3>Authentication</h3>
                    <div class="form-group">
                        <input type="text" id="token" placeholder="JWT Token (optional for login)" />
                        <button onclick="login()">Login</button>
                        <button onclick="clearAuth()">Clear</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>HTTP Method:</label>
                    <select id="method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Endpoint URL:</label>
                    <input type="text" id="url" value="/users" />
                </div>
                
                <div class="form-group">
                    <label>Request Body (JSON):</label>
                    <textarea id="body" rows="8" placeholder='{"key": "value"}'></textarea>
                </div>
                
                <div class="form-group">
                    <button onclick="sendRequest()">Send Request</button>
                    <button onclick="clearRequest()">Clear</button>
                </div>
                
                <h3>Quick Endpoints</h3>
                <div class="endpoint" onclick="setEndpoint('POST', '/auth/login', '{\"identifier\": \"user@example.com\", \"password\": \"password\"}')">
                    <span class="method post">POST</span>/auth/login
                </div>
                <div class="endpoint" onclick="setEndpoint('GET', '/users', '')">
                    <span class="method get">GET</span>/users
                </div>
                <div class="endpoint" onclick="setEndpoint('POST', '/users', '{\"name\": \"John\", \"surname\": \"Doe\", \"email\": \"john@example.com\", \"password\": \"password123\"}')">
                    <span class="method post">POST</span>/users
                </div>
                <div class="endpoint" onclick="setEndpoint('GET', '/users/1', '')">
                    <span class="method get">GET</span>/users/:id
                </div>
                <div class="endpoint" onclick="setEndpoint('PUT', '/users/1', '{\"name\": \"John Updated\"}')">
                    <span class="method put">PUT</span>/users/:id
                </div>
            </div>
            
            <div class="panel response-panel">
                <h2>📄 Response</h2>
                <div id="response" class="response">Click "Send Request" to see response...</div>
            </div>
        </div>
    </div>
    
    <script>
        const baseUrl = 'http://localhost/Sci-Bono_Clubhoue_LMS/app/API';
        let authToken = '';
        
        function setEndpoint(method, url, body) {
            document.getElementById('method').value = method;
            document.getElementById('url').value = url;
            document.getElementById('body').value = body;
        }
        
        function login() {
            const identifier = prompt('Enter email/username:');
            const password = prompt('Enter password:');
            
            if (identifier && password) {
                setEndpoint('POST', '/auth/login', JSON.stringify({identifier, password}));
                sendRequest().then(data => {
                    if (data && data.success && data.data.token) {
                        authToken = data.data.token;
                        document.getElementById('token').value = authToken;
                        alert('Login successful! Token saved.');
                    }
                });
            }
        }
        
        function clearAuth() {
            authToken = '';
            document.getElementById('token').value = '';
        }
        
        function clearRequest() {
            document.getElementById('url').value = '';
            document.getElementById('body').value = '';
            document.getElementById('response').textContent = 'Request cleared...';
        }
        
        async function sendRequest() {
            const method = document.getElementById('method').value;
            const url = document.getElementById('url').value;
            const body = document.getElementById('body').value;
            const token = document.getElementById('token').value || authToken;
            
            const headers = {
                'Content-Type': 'application/json'
            };
            
            if (token) {
                headers['Authorization'] = `Bearer \${token}`;
            }
            
            const options = {
                method: method,
                headers: headers
            };
            
            if (body && (method === 'POST' || method === 'PUT')) {
                options.body = body;
            }
            
            const responseDiv = document.getElementById('response');
            responseDiv.textContent = 'Sending request...';
            
            try {
                const response = await fetch(baseUrl + url, options);
                const data = await response.json();
                
                const formattedResponse = {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    body: data
                };
                
                responseDiv.textContent = JSON.stringify(formattedResponse, null, 2);
                
                // Update auth token if login was successful
                if (data && data.success && data.data && data.data.token) {
                    authToken = data.data.token;
                    document.getElementById('token').value = authToken;
                }
                
                return data;
            } catch (error) {
                responseDiv.textContent = `Error: \${error.message}`;
                throw error;
            }
        }
        
        // Set initial token from input
        document.getElementById('token').addEventListener('input', (e) => {
            authToken = e.target.value;
        });
    </script>
</body>
</html>
HTML;

        file_put_contents($this->outputDir . '/html/api-explorer.html', $html);
    }
    
    /**
     * Generate Postman collection
     */
    private function generatePostmanCollection()
    {
        echo "📮 Generating Postman collection...\n";
        
        $collection = [
            'info' => [
                'name' => 'Sci-Bono LMS API',
                'description' => 'Comprehensive API collection for the Sci-Bono Clubhouse Learning Management System',
                'version' => '1.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{jwt_token}}', 'type' => 'string']
                ]
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => 'http://localhost/Sci-Bono_Clubhoue_LMS/app/API'],
                ['key' => 'jwt_token', 'value' => '']
            ],
            'item' => []
        ];
        
        // Authentication folder
        $authFolder = [
            'name' => 'Authentication',
            'item' => [
                [
                    'name' => 'Login',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            ['key' => 'Content-Type', 'value' => 'application/json']
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'identifier' => '{{user_email}}',
                                'password' => '{{user_password}}'
                            ])
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/auth/login',
                            'host' => ['{{base_url}}'],
                            'path' => ['auth', 'login']
                        ]
                    ],
                    'event' => [
                        [
                            'listen' => 'test',
                            'script' => [
                                'type' => 'text/javascript',
                                'exec' => [
                                    'if (pm.response.code === 200) {',
                                    '    var jsonData = pm.response.json();',
                                    '    if (jsonData.success && jsonData.data.token) {',
                                    '        pm.collectionVariables.set("jwt_token", jsonData.data.token);',
                                    '        pm.test("Login successful", function () {',
                                    '            pm.response.to.have.status(200);',
                                    '        });',
                                    '    }',
                                    '}'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Refresh Token',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/auth/refresh',
                            'host' => ['{{base_url}}'],
                            'path' => ['auth', 'refresh']
                        ]
                    ]
                ],
                [
                    'name' => 'Logout',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/auth/logout',
                            'host' => ['{{base_url}}'],
                            'path' => ['auth', 'logout']
                        ]
                    ]
                ]
            ]
        ];
        
        // Users folder
        $usersFolder = [
            'name' => 'Users',
            'item' => [
                [
                    'name' => 'Get All Users',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/users?page=1&limit=20',
                            'host' => ['{{base_url}}'],
                            'path' => ['users'],
                            'query' => [
                                ['key' => 'page', 'value' => '1'],
                                ['key' => 'limit', 'value' => '20']
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Get User by ID',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/users/{{user_id}}',
                            'host' => ['{{base_url}}'],
                            'path' => ['users', '{{user_id}}']
                        ]
                    ]
                ],
                [
                    'name' => 'Create User',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            ['key' => 'Content-Type', 'value' => 'application/json'],
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'name' => 'John',
                                'surname' => 'Doe',
                                'email' => 'john.doe@example.com',
                                'password' => 'SecurePassword123!',
                                'user_type' => 'student'
                            ])
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/users',
                            'host' => ['{{base_url}}'],
                            'path' => ['users']
                        ]
                    ]
                ],
                [
                    'name' => 'Update User',
                    'request' => [
                        'method' => 'PUT',
                        'header' => [
                            ['key' => 'Content-Type', 'value' => 'application/json'],
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'name' => 'John Updated',
                                'user_type' => 'mentor'
                            ])
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/users/{{user_id}}',
                            'host' => ['{{base_url}}'],
                            'path' => ['users', '{{user_id}}']
                        ]
                    ]
                ],
                [
                    'name' => 'Delete User',
                    'request' => [
                        'method' => 'DELETE',
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{jwt_token}}']
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/users/{{user_id}}',
                            'host' => ['{{base_url}}'],
                            'path' => ['users', '{{user_id}}']
                        ]
                    ]
                ]
            ]
        ];
        
        $collection['item'] = [$authFolder, $usersFolder];
        
        file_put_contents(
            $this->outputDir . '/postman/Sci-Bono-LMS-API.postman_collection.json', 
            json_encode($collection, JSON_PRETTY_PRINT)
        );
        
        // Generate environment file
        $environment = [
            'name' => 'Sci-Bono LMS Development',
            'values' => [
                ['key' => 'base_url', 'value' => 'http://localhost/Sci-Bono_Clubhoue_LMS/app/API', 'enabled' => true],
                ['key' => 'user_email', 'value' => 'admin@sci-bono.co.za', 'enabled' => true],
                ['key' => 'user_password', 'value' => 'admin123', 'enabled' => true],
                ['key' => 'jwt_token', 'value' => '', 'enabled' => true],
                ['key' => 'user_id', 'value' => '1', 'enabled' => true]
            ]
        ];
        
        file_put_contents(
            $this->outputDir . '/postman/Sci-Bono-LMS-Development.postman_environment.json',
            json_encode($environment, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Generate code examples
     */
    private function generateCodeExamples()
    {
        echo "💻 Generating code examples...\n";
        
        $this->generateJavaScriptExamples();
        $this->generatePythonExamples();
        $this->generateCurlExamples();
    }
    
    /**
     * Generate JavaScript examples
     */
    private function generateJavaScriptExamples()
    {
        $content = <<<JAVASCRIPT
/**
 * Sci-Bono LMS API Client - JavaScript/Node.js
 * Complete example with error handling and token management
 */

class SciBonolmsApiClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.token = null;
    this.refreshToken = null;
  }

  /**
   * Login and get JWT token
   */
  async login(identifier, password) {
    try {
      const response = await this.makeRequest('POST', '/auth/login', {
        identifier,
        password
      }, false);

      if (response.success) {
        this.token = response.data.token;
        this.refreshToken = response.data.refresh_token;
        
        // Store tokens securely (avoid localStorage in production)
        if (typeof window !== 'undefined') {
          sessionStorage.setItem('sci_bono_token', this.token);
          sessionStorage.setItem('sci_bono_refresh', this.refreshToken);
        }

        return response.data;
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  }

  /**
   * Refresh JWT token
   */
  async refreshAuthToken() {
    try {
      const response = await this.makeRequest('POST', '/auth/refresh', {}, true);
      
      if (response.success) {
        this.token = response.data.token;
        if (typeof window !== 'undefined') {
          sessionStorage.setItem('sci_bono_token', this.token);
        }
        return response.data;
      }
    } catch (error) {
      // If refresh fails, user needs to login again
      await this.logout();
      throw new Error('Session expired. Please login again.');
    }
  }

  /**
   * Logout and clear tokens
   */
  async logout() {
    try {
      if (this.token) {
        await this.makeRequest('POST', '/auth/logout', {}, true);
      }
    } finally {
      this.token = null;
      this.refreshToken = null;
      
      if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sci_bono_token');
        sessionStorage.removeItem('sci_bono_refresh');
      }
    }
  }

  /**
   * Get user profile
   */
  async getUserProfile(userId) {
    return this.makeRequest('GET', `/users/\${userId}`, null, true);
  }

  /**
   * Get all users with pagination
   */
  async getUsers(options = {}) {
    const params = new URLSearchParams({
      page: options.page || 1,
      limit: options.limit || 20,
      ...(options.search && { search: options.search }),
      ...(options.user_type && { user_type: options.user_type }),
      ...(options.status && { status: options.status })
    });

    return this.makeRequest('GET', `/users?\${params}`, null, true);
  }

  /**
   * Create new user
   */
  async createUser(userData) {
    const requiredFields = ['email', 'password'];
    for (const field of requiredFields) {
      if (!userData[field]) {
        throw new Error(`\${field} is required`);
      }
    }

    return this.makeRequest('POST', '/users', userData, true);
  }

  /**
   * Update user
   */
  async updateUser(userId, userData) {
    return this.makeRequest('PUT', `/users/\${userId}`, userData, true);
  }

  /**
   * Delete user
   */
  async deleteUser(userId) {
    return this.makeRequest('DELETE', `/users/\${userId}`, null, true);
  }

  /**
   * Change password
   */
  async changePassword(userId, currentPassword, newPassword) {
    return this.makeRequest('POST', `/users/\${userId}/change-password`, {
      current_password: currentPassword,
      new_password: newPassword
    }, true);
  }

  /**
   * Make HTTP request with error handling and token refresh
   */
  async makeRequest(method, endpoint, data = null, requireAuth = false) {
    const url = `\${this.baseUrl}\${endpoint}`;
    
    const config = {
      method,
      headers: {
        'Content-Type': 'application/json',
      }
    };

    // Add authorization header if required
    if (requireAuth && this.token) {
      config.headers['Authorization'] = `Bearer \${this.token}`;
    }

    // Add request body for POST/PUT requests
    if (data && (method === 'POST' || method === 'PUT')) {
      config.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, config);
      const result = await response.json();

      // Handle different response status codes
      if (response.status === 401 && requireAuth) {
        // Try to refresh token
        try {
          await this.refreshAuthToken();
          // Retry the original request
          config.headers['Authorization'] = `Bearer \${this.token}`;
          const retryResponse = await fetch(url, config);
          return await retryResponse.json();
        } catch (refreshError) {
          throw new Error('Authentication failed. Please login again.');
        }
      }

      if (!response.ok) {
        const error = new Error(result.message || `HTTP \${response.status}: \${response.statusText}`);
        error.status = response.status;
        error.response = result;
        throw error;
      }

      return result;
    } catch (error) {
      console.error(`API request failed:`, error);
      throw error;
    }
  }

  /**
   * Initialize client with stored tokens
   */
  initialize() {
    if (typeof window !== 'undefined') {
      this.token = sessionStorage.getItem('sci_bono_token');
      this.refreshToken = sessionStorage.getItem('sci_bono_refresh');
    }
  }
}

// Usage Examples
async function examples() {
  const api = new SciBonolmsApiClient('http://localhost/Sci-Bono_Clubhoue_LMS/app/API');
  
  try {
    // Initialize with stored tokens
    api.initialize();

    // Login
    const loginResult = await api.login('admin@sci-bono.co.za', 'admin123');
    console.log('Logged in as:', loginResult.user.name);

    // Get users with pagination
    const users = await api.getUsers({ page: 1, limit: 10, user_type: 'student' });
    console.log('Users found:', users.pagination.total);

    // Create a new user
    const newUser = await api.createUser({
      name: 'Jane',
      surname: 'Smith',
      email: 'jane.smith@example.com',
      password: 'SecurePass123!',
      user_type: 'student'
    });
    console.log('Created user:', newUser.data.id);

    // Update user
    const updatedUser = await api.updateUser(newUser.data.id, {
      name: 'Jane Updated',
      user_type: 'member'
    });
    console.log('Updated user:', updatedUser.data.name);

    // Change password
    await api.changePassword(newUser.data.id, 'SecurePass123!', 'NewPassword456!');
    console.log('Password changed successfully');

    // Logout
    await api.logout();
    console.log('Logged out successfully');

  } catch (error) {
    console.error('API Error:', error.message);
    
    // Handle specific error types
    if (error.status === 403) {
      console.error('Permission denied');
    } else if (error.status === 429) {
      console.error('Rate limit exceeded. Please wait before retrying.');
    }
  }
}

// Export for Node.js
if (typeof module !== 'undefined' && module.exports) {
  module.exports = SciBonolmsApiClient;
}

// Browser global
if (typeof window !== 'undefined') {
  window.SciBonolmsApiClient = SciBonolmsApiClient;
}

JAVASCRIPT;

        file_put_contents($this->outputDir . '/examples/javascript-client.js', $content);
    }
    
    /**
     * Generate Python examples
     */
    private function generatePythonExamples()
    {
        $content = <<<PYTHON
"""
Sci-Bono LMS API Client - Python
Complete example with error handling and token management
"""

import json
import requests
from typing import Optional, Dict, Any
from urllib.parse import urljoin, urlencode


class SciBonolmsApiClient:
    """Python client for Sci-Bono LMS API"""
    
    def __init__(self, base_url: str):
        self.base_url = base_url.rstrip('/')
        self.token = None
        self.refresh_token = None
        self.session = requests.Session()
        
        # Set default headers
        self.session.headers.update({
            'Content-Type': 'application/json',
            'User-Agent': 'Sci-Bono-LMS-Python-Client/1.0'
        })
    
    def login(self, identifier: str, password: str) -> Dict[str, Any]:
        """Login and get JWT token"""
        data = {
            'identifier': identifier,
            'password': password
        }
        
        response = self._make_request('POST', '/auth/login', data, require_auth=False)
        
        if response.get('success'):
            self.token = response['data']['token']
            self.refresh_token = response['data'].get('refresh_token')
            self._update_auth_header()
            return response['data']
        else:
            raise Exception(f"Login failed: {response.get('message', 'Unknown error')}")
    
    def refresh_auth_token(self) -> Dict[str, Any]:
        """Refresh JWT token"""
        if not self.token:
            raise Exception("No token to refresh")
        
        response = self._make_request('POST', '/auth/refresh', require_auth=True)
        
        if response.get('success'):
            self.token = response['data']['token']
            self._update_auth_header()
            return response['data']
        else:
            # Clear tokens on refresh failure
            self.logout()
            raise Exception("Session expired. Please login again.")
    
    def logout(self) -> None:
        """Logout and clear tokens"""
        try:
            if self.token:
                self._make_request('POST', '/auth/logout', require_auth=True)
        finally:
            self.token = None
            self.refresh_token = None
            self._clear_auth_header()
    
    def get_users(self, **kwargs) -> Dict[str, Any]:
        """Get users with pagination and filtering"""
        params = {
            'page': kwargs.get('page', 1),
            'limit': kwargs.get('limit', 20)
        }
        
        # Add optional filters
        for key in ['search', 'user_type', 'status']:
            if key in kwargs:
                params[key] = kwargs[key]
        
        endpoint = f"/users?{urlencode(params)}"
        return self._make_request('GET', endpoint, require_auth=True)
    
    def get_user(self, user_id: int) -> Dict[str, Any]:
        """Get user by ID"""
        return self._make_request('GET', f'/users/{user_id}', require_auth=True)
    
    def create_user(self, user_data: Dict[str, Any]) -> Dict[str, Any]:
        """Create new user"""
        required_fields = ['email', 'password']
        for field in required_fields:
            if field not in user_data:
                raise ValueError(f"{field} is required")
        
        return self._make_request('POST', '/users', user_data, require_auth=True)
    
    def update_user(self, user_id: int, user_data: Dict[str, Any]) -> Dict[str, Any]:
        """Update user"""
        return self._make_request('PUT', f'/users/{user_id}', user_data, require_auth=True)
    
    def delete_user(self, user_id: int) -> Dict[str, Any]:
        """Delete user"""
        return self._make_request('DELETE', f'/users/{user_id}', require_auth=True)
    
    def change_password(self, user_id: int, current_password: str, new_password: str) -> Dict[str, Any]:
        """Change user password"""
        data = {
            'current_password': current_password,
            'new_password': new_password
        }
        return self._make_request('POST', f'/users/{user_id}/change-password', data, require_auth=True)
    
    def get_user_profile(self, user_id: int) -> Dict[str, Any]:
        """Get detailed user profile"""
        return self._make_request('GET', f'/users/{user_id}/profile', require_auth=True)
    
    def update_user_profile(self, user_id: int, profile_data: Dict[str, Any]) -> Dict[str, Any]:
        """Update user profile"""
        return self._make_request('PUT', f'/users/{user_id}/profile', profile_data, require_auth=True)
    
    def _make_request(self, method: str, endpoint: str, data: Optional[Dict[str, Any]] = None, 
                     require_auth: bool = False) -> Dict[str, Any]:
        """Make HTTP request with error handling"""
        url = urljoin(self.base_url, endpoint)
        
        # Prepare request arguments
        kwargs = {
            'method': method,
            'url': url,
            'timeout': 30
        }
        
        # Add data for POST/PUT requests
        if data and method in ['POST', 'PUT']:
            kwargs['json'] = data
        
        try:
            response = self.session.request(**kwargs)
            result = response.json()
            
            # Handle 401 (unauthorized) with token refresh
            if response.status_code == 401 and require_auth and self.token:
                try:
                    self.refresh_auth_token()
                    # Retry the request with new token
                    response = self.session.request(**kwargs)
                    result = response.json()
                except Exception:
                    raise Exception("Authentication failed. Please login again.")
            
            # Raise exception for HTTP errors
            if not response.ok:
                error_msg = result.get('message', f'HTTP {response.status_code}: {response.reason}')
                error = Exception(error_msg)
                error.status_code = response.status_code
                error.response_data = result
                raise error
            
            return result
            
        except requests.exceptions.RequestException as e:
            raise Exception(f"Network error: {str(e)}")
        except json.JSONDecodeError:
            raise Exception("Invalid JSON response from server")
    
    def _update_auth_header(self) -> None:
        """Update session with authorization header"""
        if self.token:
            self.session.headers['Authorization'] = f'Bearer {self.token}'
    
    def _clear_auth_header(self) -> None:
        """Remove authorization header from session"""
        self.session.headers.pop('Authorization', None)


# Usage Examples
def main():
    """Example usage of the API client"""
    api = SciBonolmsApiClient('http://localhost/Sci-Bono_Clubhoue_LMS/app/API')
    
    try:
        # Login
        login_result = api.login('admin@sci-bono.co.za', 'admin123')
        print(f"Logged in as: {login_result['user']['name']}")
        
        # Get users with pagination
        users = api.get_users(page=1, limit=10, user_type='student')
        print(f"Found {users['pagination']['total']} users")
        
        # Create a new user
        new_user_data = {
            'name': 'John',
            'surname': 'Doe',
            'email': 'john.doe@example.com',
            'password': 'SecurePass123!',
            'user_type': 'student',
            'phone': '+27123456789'
        }
        
        new_user = api.create_user(new_user_data)
        user_id = new_user['data']['id']
        print(f"Created user with ID: {user_id}")
        
        # Update user
        update_data = {
            'name': 'John Updated',
            'user_type': 'member'
        }
        updated_user = api.update_user(user_id, update_data)
        print(f"Updated user: {updated_user['data']['name']}")
        
        # Get user profile
        profile = api.get_user_profile(user_id)
        print(f"User profile: {profile['data']['email']}")
        
        # Change password
        api.change_password(user_id, 'SecurePass123!', 'NewPassword456!')
        print("Password changed successfully")
        
        # Search users
        search_results = api.get_users(search='John', user_type='member')
        print(f"Search found {len(search_results['data'])} users")
        
        # Clean up - delete test user
        api.delete_user(user_id)
        print(f"Deleted user {user_id}")
        
        # Logout
        api.logout()
        print("Logged out successfully")
        
    except Exception as e:
        print(f"Error: {e}")
        
        # Handle specific error types
        if hasattr(e, 'status_code'):
            if e.status_code == 403:
                print("Permission denied")
            elif e.status_code == 429:
                print("Rate limit exceeded. Please wait before retrying.")
            elif e.status_code == 422:
                print("Validation error:", e.response_data.get('errors', {}))


if __name__ == '__main__':
    main()

PYTHON;

        file_put_contents($this->outputDir . '/examples/python-client.py', $content);
    }
    
    /**
     * Generate cURL examples
     */
    private function generateCurlExamples()
    {
        $content = <<<BASH
#!/bin/bash

# Sci-Bono LMS API - cURL Examples
# Complete set of API examples using cURL

# Configuration
BASE_URL="http://localhost/Sci-Bono_Clubhoue_LMS/app/API"
JWT_TOKEN=""

# Colors for output
RED='\\033[0;31m'
GREEN='\\033[0;32m'
YELLOW='\\033[1;33m'
BLUE='\\033[0;34m'
NC='\\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "\${GREEN}[INFO]\${NC} \$1"
}

print_error() {
    echo -e "\${RED}[ERROR]\${NC} \$1"
}

print_warning() {
    echo -e "\${YELLOW}[WARNING]\${NC} \$1"
}

print_header() {
    echo -e "\${BLUE}=== \$1 ===\${NC}"
}

# Function to extract token from response
extract_token() {
    echo \$1 | grep -o '"token":"[^"]*"' | cut -d'"' -f4
}

# 1. AUTHENTICATION EXAMPLES

print_header "Authentication Examples"

# Login
print_status "Logging in..."
LOGIN_RESPONSE=\$(curl -s -X POST \\
  \${BASE_URL}/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{
    "identifier": "admin@sci-bono.co.za",
    "password": "admin123"
  }')

echo "Login Response:"
echo \$LOGIN_RESPONSE | jq '.'

# Extract token for subsequent requests
JWT_TOKEN=\$(extract_token "\$LOGIN_RESPONSE")

if [ -z "\$JWT_TOKEN" ]; then
    print_error "Failed to extract JWT token from login response"
    exit 1
fi

print_status "JWT Token extracted: \${JWT_TOKEN:0:50}..."

# Refresh token
print_status "Refreshing token..."
curl -s -X POST \\
  \${BASE_URL}/auth/refresh \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# 2. USER MANAGEMENT EXAMPLES

print_header "User Management Examples"

# Get all users with pagination
print_status "Getting all users (page 1, limit 5)..."
curl -s -X GET \\
  "\${BASE_URL}/users?page=1&limit=5" \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# Search users
print_status "Searching users by name..."
curl -s -X GET \\
  "\${BASE_URL}/users?search=admin&user_type=admin" \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# Get specific user
print_status "Getting user by ID (user 1)..."
curl -s -X GET \\
  \${BASE_URL}/users/1 \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# Create new user
print_status "Creating new user..."
CREATE_RESPONSE=\$(curl -s -X POST \\
  \${BASE_URL}/users \\
  -H "Authorization: Bearer \$JWT_TOKEN" \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "Test",
    "surname": "User",
    "email": "testuser@example.com",
    "password": "TestPassword123!",
    "user_type": "student",
    "phone": "+27123456789"
  }')

echo "Create User Response:"
echo \$CREATE_RESPONSE | jq '.'

# Extract user ID for subsequent operations
USER_ID=\$(echo \$CREATE_RESPONSE | grep -o '"id":[0-9]*' | cut -d':' -f2)

if [ -n "\$USER_ID" ]; then
    print_status "Created user with ID: \$USER_ID"
    
    # Update user
    print_status "Updating user \$USER_ID..."
    curl -s -X PUT \\
      \${BASE_URL}/users/\$USER_ID \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -H 'Content-Type: application/json' \\
      -d '{
        "name": "Test Updated",
        "user_type": "member",
        "status": "active"
      }' | jq '.'
    
    # Get user profile
    print_status "Getting user profile..."
    curl -s -X GET \\
      \${BASE_URL}/users/\$USER_ID/profile \\
      -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'
    
    # Update user profile
    print_status "Updating user profile..."
    curl -s -X PUT \\
      \${BASE_URL}/users/\$USER_ID/profile \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -H 'Content-Type: application/json' \\
      -d '{
        "phone": "+27987654321",
        "address": "123 Test Street, Johannesburg",
        "bio": "Test user created via API"
      }' | jq '.'
    
    # Change password
    print_status "Changing user password..."
    curl -s -X POST \\
      \${BASE_URL}/users/\$USER_ID/change-password \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -H 'Content-Type: application/json' \\
      -d '{
        "current_password": "TestPassword123!",
        "new_password": "NewPassword456!"
      }' | jq '.'
    
    # Delete user (cleanup)
    print_status "Deleting test user..."
    curl -s -X DELETE \\
      \${BASE_URL}/users/\$USER_ID \\
      -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'
else
    print_warning "Could not extract user ID from create response"
fi

# 3. ERROR HANDLING EXAMPLES

print_header "Error Handling Examples"

# Test 401 - Unauthorized (invalid token)
print_status "Testing unauthorized access..."
curl -s -X GET \\
  \${BASE_URL}/users \\
  -H "Authorization: Bearer invalid-token" | jq '.'

# Test 403 - Forbidden (insufficient permissions)
print_status "Testing forbidden access (trying to access admin endpoint as student)..."
# This would require a student token, but demonstrates the concept
curl -s -X DELETE \\
  \${BASE_URL}/users/1 \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# Test 404 - Not found
print_status "Testing not found error..."
curl -s -X GET \\
  \${BASE_URL}/users/99999 \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

# Test 422 - Validation error
print_status "Testing validation error..."
curl -s -X POST \\
  \${BASE_URL}/users \\
  -H "Authorization: Bearer \$JWT_TOKEN" \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "",
    "email": "invalid-email",
    "password": "123"
  }' | jq '.'

# Test 429 - Rate limiting (would require many requests)
print_status "Rate limiting test (send many requests quickly)..."
for i in {1..5}; do
    curl -s -X GET \\
      \${BASE_URL}/users \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -w "Request \$i - Status: %{http_code}\\n" \\
      -o /dev/null &
done
wait

# 4. ADVANCED EXAMPLES

print_header "Advanced Examples"

# Bulk operations simulation
print_status "Simulating bulk user creation..."
for i in {1..3}; do
    echo "Creating user \$i..."
    curl -s -X POST \\
      \${BASE_URL}/users \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -H 'Content-Type: application/json' \\
      -d "{
        \"name\": \"Bulk\",
        \"surname\": \"User\$i\",
        \"email\": \"bulkuser\$i@example.com\",
        \"password\": \"BulkPassword\$i!\",
        \"user_type\": \"student\"
      }" | jq '.data.id' 2>/dev/null || echo "Failed to create user \$i"
done

# Pagination example
print_status "Testing pagination..."
echo "Page 1:"
curl -s -X GET \\
  "\${BASE_URL}/users?page=1&limit=2" \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.pagination'

echo "Page 2:"
curl -s -X GET \\
  "\${BASE_URL}/users?page=2&limit=2" \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.pagination'

# Performance test
print_status "Performance test (10 concurrent requests)..."
start_time=\$(date +%s)
for i in {1..10}; do
    curl -s -X GET \\
      \${BASE_URL}/users \\
      -H "Authorization: Bearer \$JWT_TOKEN" \\
      -o /dev/null &
done
wait
end_time=\$(date +%s)
duration=\$((end_time - start_time))
echo "10 concurrent requests completed in \$duration seconds"

# Logout
print_status "Logging out..."
curl -s -X POST \\
  \${BASE_URL}/auth/logout \\
  -H "Authorization: Bearer \$JWT_TOKEN" | jq '.'

print_header "Examples completed!"

echo ""
echo "Additional cURL Examples:"
echo ""

# Formatted examples for documentation
cat << 'EOF'
# Quick Reference - Common API Calls

# 1. Login
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{"identifier": "user@example.com", "password": "password"}'

# 2. Get users with filters
curl -X GET 'http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users?page=1&limit=10&user_type=student&status=active' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 3. Create user
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "John",
    "surname": "Doe", 
    "email": "john@example.com",
    "password": "SecurePass123!",
    "user_type": "student"
  }'

# 4. Update user
curl -X PUT http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{"name": "John Updated", "status": "active"}'

# 5. Change password
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123/change-password \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "current_password": "old_password",
    "new_password": "new_password"
  }'

# 6. Delete user
curl -X DELETE http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 7. Refresh token
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/refresh \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 8. Logout
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/logout \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

EOF

BASH;

        file_put_contents($this->outputDir . '/examples/curl-examples.sh', $content);
        chmod($this->outputDir . '/examples/curl-examples.sh', 0755);
    }
}

// Run the generator
try {
    $generator = new ApiDocumentationGenerator();
    $generator->generateAll();
} catch (Exception $e) {
    echo "❌ Error generating API documentation: " . $e->getMessage() . "\n";
    exit(1);
}