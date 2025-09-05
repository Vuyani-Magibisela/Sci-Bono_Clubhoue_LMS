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
    return this.makeRequest('GET', `/users/${userId}`, null, true);
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

    return this.makeRequest('GET', `/users?${params}`, null, true);
  }

  /**
   * Create new user
   */
  async createUser(userData) {
    const requiredFields = ['email', 'password'];
    for (const field of requiredFields) {
      if (!userData[field]) {
        throw new Error(`${field} is required`);
      }
    }

    return this.makeRequest('POST', '/users', userData, true);
  }

  /**
   * Update user
   */
  async updateUser(userId, userData) {
    return this.makeRequest('PUT', `/users/${userId}`, userData, true);
  }

  /**
   * Delete user
   */
  async deleteUser(userId) {
    return this.makeRequest('DELETE', `/users/${userId}`, null, true);
  }

  /**
   * Change password
   */
  async changePassword(userId, currentPassword, newPassword) {
    return this.makeRequest('POST', `/users/${userId}/change-password`, {
      current_password: currentPassword,
      new_password: newPassword
    }, true);
  }

  /**
   * Make HTTP request with error handling and token refresh
   */
  async makeRequest(method, endpoint, data = null, requireAuth = false) {
    const url = `${this.baseUrl}${endpoint}`;
    
    const config = {
      method,
      headers: {
        'Content-Type': 'application/json',
      }
    };

    // Add authorization header if required
    if (requireAuth && this.token) {
      config.headers['Authorization'] = `Bearer ${this.token}`;
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
          config.headers['Authorization'] = `Bearer ${this.token}`;
          const retryResponse = await fetch(url, config);
          return await retryResponse.json();
        } catch (refreshError) {
          throw new Error('Authentication failed. Please login again.');
        }
      }

      if (!response.ok) {
        const error = new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
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
