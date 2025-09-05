# Authentication Guide

## Overview

The Sci-Bono LMS API uses JWT (JSON Web Tokens) for authentication. This provides a secure, stateless authentication mechanism that's perfect for API integrations.

## Authentication Flow

### 1. Login Request

Send a POST request to `/auth/login` with user credentials:

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \
  -H 'Content-Type: application/json' \
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
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
```

## Token Management

### Token Expiration

JWT tokens expire after 1 hour. Use the refresh token to get a new access token:

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/refresh \
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
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/logout \
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
      const response = await axios.post(`${this.baseUrl}/auth/login`, {
        identifier,
        password
      });
      
      this.token = response.data.data.token;
      return response.data;
    } catch (error) {
      throw new Error(`Login failed: ${error.response?.data?.message}`);
    }
  }
  
  async apiCall(method, endpoint, data = null) {
    const config = {
      method,
      url: `${this.baseUrl}${endpoint}`,
      headers: {
        'Authorization': `Bearer ${this.token}`,
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
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl) 
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function login($identifier, $password) 
    {
        $data = [
            'identifier' => $identifier,
            'password' => $password
        ];
        
        $response = $this->makeRequest('POST', '/auth/login', $data);
        
        if ($response['success']) {
            $this->token = $response['data']['token'];
            return $response;
        }
        
        throw new Exception('Login failed: ' . $response['message']);
    }
    
    private function makeRequest($method, $endpoint, $data = null) 
    {
        $url = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json'];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```
