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
