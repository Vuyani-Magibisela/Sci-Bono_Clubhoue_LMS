# Quick Start Guide

This guide will help you get started with the Sci-Bono LMS API in minutes.

## Prerequisites

- Access to the Sci-Bono LMS system
- Valid user credentials (email/username and password)
- API client (cURL, Postman, or programming language HTTP library)

## Step 1: Authentication

First, obtain a JWT token by logging in:

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \
  -H 'Content-Type: application/json' \
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
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \
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
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE'
```

### Update Your Profile
```bash
curl -X PUT \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "John Updated",
    "phone": "+27123456789"
  }'
```

### Change Password
```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123/change-password \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' \
  -H 'Content-Type: application/json' \
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
    const response = await fetch(\`${this.baseUrl}/auth/login\`, {
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
    const response = await fetch(\`${this.baseUrl}${endpoint}\`, {
      headers: { 'Authorization': \`Bearer ${this.token}\` }
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
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }
    
    public function login($identifier, $password) {
        $data = ['identifier' => $identifier, 'password' => $password];
        $response = $this->post('/auth/login', $data);
        
        if ($response['success']) {
            $this->token = $response['data']['token'];
        }
        return $response;
    }
    
    public function get($endpoint) {
        return $this->makeRequest('GET', $endpoint);
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
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

// Usage
$api = new SciBonolmsApi('http://localhost/Sci-Bono_Clubhoue_LMS/app/API');
$api->login('user@example.com', 'password');
$profile = $api->get('/users/123');
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
