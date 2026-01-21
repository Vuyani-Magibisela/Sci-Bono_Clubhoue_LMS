<?php
/**
 * User Service - Authentication and user management business logic
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserService extends BaseService {
    private $userModel;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 3600; // 1 hour
    
    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->userModel = new UserModel($this->conn);
    }
    
    /**
     * Authenticate user with email/username and password
     */
    public function authenticate($identifier, $password) {
        $this->logAction('authenticate_attempt', ['identifier' => $identifier]);
        
        try {
            // Check if account is locked
            if ($this->isAccountLocked($identifier)) {
                return [
                    'success' => false,
                    'message' => 'Account is temporarily locked due to multiple failed login attempts. Please try again later.',
                    'locked_until' => $this->getAccountLockExpiry($identifier)
                ];
            }
            
            // Find user by email or username
            $user = $this->findUserByIdentifier($identifier);
            
            if (!$user) {
                $this->recordFailedAttempt($identifier);
                return [
                    'success' => false,
                    'message' => 'Invalid credentials provided.'
                ];
            }
            
            // Verify password
            if (!$this->verifyPassword($password, $user['password'])) {
                $this->recordFailedAttempt($identifier);
                $this->logAction('authenticate_failed', [
                    'user_id' => $user['id'],
                    'reason' => 'invalid_password'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid credentials provided.'
                ];
            }
            
            // Check if user is active
            if (!$this->isUserActive($user)) {
                return [
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact an administrator.'
                ];
            }
            
            // Clear failed attempts on successful login
            $this->clearFailedAttempts($identifier);

            // Update last login (commented out - column doesn't exist in database)
            // $this->updateLastLogin($user['id']);

            $this->logAction('authenticate_success', ['user_id' => $user['id']]);

            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'message' => 'Authentication successful'
            ];
            
        } catch (Exception $e) {
            $this->handleError('Authentication error: ' . $e->getMessage(), [
                'identifier' => $identifier
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred during authentication. Please try again.'
            ];
        }
    }
    
    /**
     * Create a new user session
     */
    public function createSession($user) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Regenerate session ID for security (only if headers not sent)
            if (!headers_sent()) {
                session_regenerate_id(true);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['surname'] = $user['surname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['last_activity'] = time();
            $_SESSION['session_token'] = $this->generateSessionToken();

            // Database update removed - session_token and last_login columns don't exist
            // Sessions are managed by PHP session system
            // TODO: Add these columns to users table if needed:
            //   - session_token VARCHAR(255) NULL
            //   - last_login TIMESTAMP NULL

            $this->logAction('session_created', [
                'user_id' => $user['id'],
                'session_token' => substr($_SESSION['session_token'], 0, 8) . '...'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->handleError('Session creation error: ' . $e->getMessage(), [
                'user_id' => $user['id'] ?? null
            ]);
            return false;
        }
    }
    
    /**
     * Validate current session
     */
    public function validateSession() {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                return false;
            }
            
            // Check if required session data exists
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
                return false;
            }
            
            // Check session timeout
            $timeout = $this->config['session_timeout'] ?? 7200; // 2 hours default
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity']) > $timeout) {
                $this->destroySession();
                return false;
            }
            
            // Verify session token in database
            $user = $this->userModel->find($_SESSION['user_id']);
            if (!$user || $user['session_token'] !== $_SESSION['session_token']) {
                $this->destroySession();
                return false;
            }
            
            // Check if user is still active
            if (!$this->isUserActive($user)) {
                $this->destroySession();
                return false;
            }
            
            // Update last activity
            $_SESSION['last_activity'] = time();
            
            return true;
            
        } catch (Exception $e) {
            $this->handleError('Session validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy user session
     */
    public function destroySession() {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                return true;
            }
            
            $userId = $_SESSION['user_id'] ?? null;

            // Log session destruction
            if ($userId) {
                $this->logAction('session_destroyed', ['user_id' => $userId]);
            }

            // Destroy session
            session_unset();
            session_destroy();

            return true;
            
        } catch (Exception $e) {
            $this->handleError('Session destruction error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user account
     */
    public function createUser($data) {
        $this->logAction('user_creation_attempt', ['email' => $data['email'] ?? null]);
        
        try {
            // Validate required fields
            $this->validateRequired($data, ['username', 'email', 'password', 'name', 'surname']);
            
            // Sanitize input
            $data = $this->sanitize($data);
            
            // Additional validation
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (strlen($data['password']) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            
            // Check for existing username or email
            if ($this->userExists($data['username'], $data['email'])) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default values
            $data['user_type'] = $data['user_type'] ?? 'student';
            $data['status'] = $data['status'] ?? 'active';
            $data['email_verified'] = false;
            $data['verification_token'] = $this->generateVerificationToken();
            
            // Create user
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                $this->logAction('user_created', [
                    'user_id' => $userId,
                    'username' => $data['username'],
                    'user_type' => $data['user_type']
                ]);
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'User account created successfully'
                ];
            }
            
            throw new Exception('Failed to create user account');
            
        } catch (Exception $e) {
            $this->handleError('User creation error: ' . $e->getMessage(), [
                'email' => $data['email'] ?? null
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify current password
            if (!$this->verifyPassword($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Validate new password
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long');
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updated = $this->userModel->update($userId, [
                'password' => $hashedPassword,
                'password_changed_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($updated) {
                $this->logAction('password_updated', ['user_id' => $userId]);
                return [
                    'success' => true,
                    'message' => 'Password updated successfully'
                ];
            }
            
            throw new Exception('Failed to update password');
            
        } catch (Exception $e) {
            $this->handleError('Password update error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user profile with safe data
     */
    public function getUserProfile($userId) {
        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user)
            ];
            
        } catch (Exception $e) {
            $this->handleError('Get user profile error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve user profile'
            ];
        }
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($userId, $roles) {
        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return false;
            }
            
            $allowedRoles = is_array($roles) ? $roles : [$roles];
            return in_array($user['user_type'], $allowedRoles);
            
        } catch (Exception $e) {
            $this->handleError('Role check error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    // Private helper methods
    
    private function findUserByIdentifier($identifier) {
        // Try to find by email first
        $user = $this->userModel->findAll(['email' => $identifier]);
        
        if (empty($user)) {
            // Try to find by username
            $user = $this->userModel->findAll(['username' => $identifier]);
        }
        
        return !empty($user) ? $user[0] : null;
    }
    
    private function verifyPassword($password, $hash) {
        // Support legacy MD5 hashes and modern password_hash
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            // Legacy MD5 hash
            return md5($password) === $hash;
        }
        
        return password_verify($password, $hash);
    }
    
    private function isUserActive($user) {
        // Check both 'active' (boolean) and 'status' (string) fields for compatibility
        if (isset($user['active'])) {
            return $user['active'] == 1 || $user['active'] === true;
        }
        if (isset($user['status'])) {
            return $user['status'] === 'active';
        }
        // Default to active if field doesn't exist (for backward compatibility)
        return true;
    }
    
    private function userExists($username, $email) {
        return $this->userModel->exists(['username' => $username]) ||
               $this->userModel->exists(['email' => $email]);
    }
    
    private function sanitizeUserData($user) {
        $safe = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'],
            'surname' => $user['surname'],
            'user_type' => $user['user_type'],
            'role' => $user['user_type'], // Alias for backward compatibility
            'status' => $user['status'] ?? 'active',
            'created_at' => $user['created_at'] ?? null,
            'last_login' => $user['last_login'] ?? null
        ];

        return $safe;
    }
    
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function generateVerificationToken() {
        return bin2hex(random_bytes(16));
    }
    
    private function updateLastLogin($userId) {
        $this->userModel->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    // Failed login attempt tracking
    
    private function isAccountLocked($identifier) {
        try {
            $sql = "SELECT failed_attempts, locked_until FROM login_attempts 
                    WHERE identifier = ? AND locked_until > NOW()";
            $result = $this->userModel->query($sql, [$identifier]);
            
            return $result && $result->num_rows > 0;
            
        } catch (Exception $e) {
            $this->logger->error('Account lock check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    private function getAccountLockExpiry($identifier) {
        try {
            $sql = "SELECT locked_until FROM login_attempts 
                    WHERE identifier = ? AND locked_until > NOW()";
            $result = $this->userModel->query($sql, [$identifier]);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['locked_until'];
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logger->error('Get account lock expiry failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    private function recordFailedAttempt($identifier) {
        try {
            // Get current attempts
            $sql = "SELECT id, failed_attempts FROM login_attempts WHERE identifier = ?";
            $result = $this->userModel->query($sql, [$identifier]);
            
            if ($result && $result->num_rows > 0) {
                // Update existing record
                $row = $result->fetch_assoc();
                $attempts = $row['failed_attempts'] + 1;
                
                $updateSql = "UPDATE login_attempts SET 
                            failed_attempts = ?, 
                            last_attempt = NOW(),
                            locked_until = IF(? >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NULL)
                            WHERE id = ?";
                
                $this->userModel->query($updateSql, [
                    $attempts,
                    $attempts,
                    $this->maxLoginAttempts,
                    $this->lockoutDuration,
                    $row['id']
                ]);
            } else {
                // Create new record
                $insertSql = "INSERT INTO login_attempts (identifier, failed_attempts, last_attempt) 
                            VALUES (?, 1, NOW())";
                $this->userModel->query($insertSql, [$identifier]);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Record failed attempt error', ['error' => $e->getMessage()]);
        }
    }
    
    private function clearFailedAttempts($identifier) {
        try {
            $sql = "DELETE FROM login_attempts WHERE identifier = ?";
            $this->userModel->query($sql, [$identifier]);
            
        } catch (Exception $e) {
            $this->logger->error('Clear failed attempts error', ['error' => $e->getMessage()]);
        }
    }
}