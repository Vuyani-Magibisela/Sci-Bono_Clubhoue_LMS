<?php
/**
 * Auth Controller - Handles authentication and user management
 * Phase 4 Implementation - Refactored to use new MVC architecture
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Traits/LogsActivity.php';
require_once __DIR__ . '/../../core/CSRF.php';

class AuthController extends BaseController {
    use LogsActivity;
    
    private $userService;
    
    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->userService = new UserService($conn);
    }
    
    /**
     * Show login form
     */
    public function showLogin() {
        // If user is already logged in, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // Clear any existing session data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $data = [
            'page_title' => 'Login',
            'csrf_token' => CSRF::generateToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'old_input' => $_SESSION['old_input'] ?? []
        ];
        
        // Clear flash messages
        unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['old_input']);
        
        $this->logActivity('login_form_shown', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        
        $this->view('auth/login', $data);
    }
    
    /**
     * Process login attempt
     */
    public function processLogin() {
        try {
            // Validate CSRF token
            $this->validateCsrfToken();
            
            // Get and validate input
            $input = $this->input();
            $validated = $this->validate($input, [
                'identifier' => 'required|min:3',
                'password' => 'required|min:6'
            ]);
            
            // Attempt authentication
            $result = $this->userService->authenticate($validated['identifier'], $validated['password']);
            
            if ($result['success']) {
                // Create session
                $sessionCreated = $this->userService->createSession($result['user']);
                
                if ($sessionCreated) {
                    $this->logUserAction('login', $result['user']['id'], [
                        'identifier' => $validated['identifier'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    // Determine redirect URL based on user type
                    $redirectUrl = $this->getPostLoginRedirectUrl($result['user']);
                    
                    if ($this->isAjaxRequest()) {
                        $this->jsonSuccess([
                            'redirect_url' => $redirectUrl,
                            'user' => $result['user']
                        ], 'Login successful');
                    } else {
                        $this->redirectWithSuccess($redirectUrl, 'Welcome back, ' . $result['user']['name'] . '!');
                    }
                } else {
                    throw new Exception('Failed to create user session');
                }
            } else {
                $this->logAuthAttempt($validated['identifier'], false, [
                    'reason' => $result['message'],
                    'locked_until' => $result['locked_until'] ?? null
                ]);
                
                if ($this->isAjaxRequest()) {
                    $this->jsonError($result['message'], null, 401);
                } else {
                    $_SESSION['old_input'] = ['identifier' => $validated['identifier']];
                    $this->redirectWithError('/login', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            $this->handleError('Login error: ' . $e->getMessage(), [
                'identifier' => $input['identifier'] ?? 'unknown'
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonError('An error occurred during login. Please try again.');
            } else {
                $this->redirectWithError('/login', 'An error occurred during login. Please try again.');
            }
        }
    }
    
    /**
     * Process logout
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['name'] ?? 'Unknown';
        
        if ($userId) {
            $this->logUserAction('logout', $userId);
        }
        
        // Destroy session
        $this->userService->destroySession();
        
        if ($this->isAjaxRequest()) {
            $this->jsonSuccess(null, 'Logged out successfully');
        } else {
            $this->redirectWithSuccess('/login', 'You have been logged out successfully');
        }
    }
    
    /**
     * Show registration form
     */
    public function showRegister() {
        // Check if registration is enabled
        if (!($this->config['registration_enabled'] ?? true)) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('Registration is currently disabled', null, 403);
            } else {
                $this->redirectWithError('/login', 'Registration is currently disabled');
            }
            return;
        }
        
        // If user is already logged in, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $data = [
            'page_title' => 'Register',
            'csrf_token' => CSRF::generateToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'old_input' => $_SESSION['old_input'] ?? [],
            'validation_errors' => $_SESSION['validation_errors'] ?? []
        ];
        
        // Clear flash messages
        unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['old_input'], $_SESSION['validation_errors']);
        
        $this->logActivity('registration_form_shown');
        
        $this->view('auth/register', $data);
    }
    
    /**
     * Process registration
     */
    public function processRegister() {
        try {
            // Check if registration is enabled
            if (!($this->config['registration_enabled'] ?? true)) {
                if ($this->isAjaxRequest()) {
                    $this->jsonError('Registration is currently disabled', null, 403);
                } else {
                    $this->redirectWithError('/login', 'Registration is currently disabled');
                }
                return;
            }
            
            // Validate CSRF token
            $this->validateCsrfToken();
            
            // Get and validate input
            $input = $this->input();
            $validated = $this->validate($input, [
                'username' => 'required|min:3|max:50|alphanumeric',
                'email' => 'required|email|max:255',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|confirmed:password',
                'name' => 'required|min:2|max:100',
                'surname' => 'required|min:2|max:100',
                'user_type' => 'in:student,member'
            ], [
                'password_confirmation.confirmed' => 'Password confirmation does not match'
            ]);
            
            // Set default user type if not provided
            $validated['user_type'] = $validated['user_type'] ?? 'student';
            
            // Create user account
            $result = $this->userService->createUser($validated);
            
            if ($result['success']) {
                $this->logUserAction('registration', $result['user_id'], [
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                    'user_type' => $validated['user_type']
                ]);
                
                if ($this->isAjaxRequest()) {
                    $this->jsonSuccess([
                        'user_id' => $result['user_id'],
                        'message' => 'Account created successfully. You can now log in.'
                    ]);
                } else {
                    $this->redirectWithSuccess('/login', 'Account created successfully! You can now log in.');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonError($result['message'], null, 422);
                } else {
                    $_SESSION['old_input'] = $input;
                    $this->redirectWithError('/register', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            $this->handleError('Registration error: ' . $e->getMessage(), [
                'email' => $input['email'] ?? 'unknown'
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonError('An error occurred during registration. Please try again.');
            } else {
                $_SESSION['old_input'] = $input;
                $this->redirectWithError('/register', 'An error occurred during registration. Please try again.');
            }
        }
    }
    
    /**
     * Show forgot password form
     */
    public function showForgotPassword() {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $data = [
            'page_title' => 'Forgot Password',
            'csrf_token' => CSRF::generateToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'old_input' => $_SESSION['old_input'] ?? []
        ];
        
        unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['old_input']);
        
        $this->view('auth/forgot-password', $data);
    }
    
    /**
     * Process forgot password request
     */
    public function processForgotPassword() {
        try {
            $this->validateCsrfToken();
            
            $input = $this->input();
            $validated = $this->validate($input, [
                'email' => 'required|email'
            ]);
            
            // TODO: Implement password reset functionality
            // For now, just log the attempt
            $this->logActivity('password_reset_requested', ['email' => $validated['email']]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonSuccess(null, 'If your email address exists in our system, you will receive a password reset link shortly.');
            } else {
                $this->redirectWithSuccess('/login', 'If your email address exists in our system, you will receive a password reset link shortly.');
            }
            
        } catch (Exception $e) {
            $this->handleError('Forgot password error: ' . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                $this->jsonError('An error occurred. Please try again.');
            } else {
                $this->redirectWithError('/forgot-password', 'An error occurred. Please try again.');
            }
        }
    }
    
    /**
     * Show change password form (for authenticated users)
     */
    public function showChangePassword() {
        $this->requireAuth();
        
        $data = [
            'page_title' => 'Change Password',
            'csrf_token' => CSRF::generateToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'validation_errors' => $_SESSION['validation_errors'] ?? []
        ];
        
        unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['validation_errors']);
        
        $this->view('auth/change-password', $data);
    }
    
    /**
     * Process password change
     */
    public function processChangePassword() {
        $this->requireAuth();
        
        try {
            $this->validateCsrfToken();
            
            $input = $this->input();
            $validated = $this->validate($input, [
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'new_password_confirmation' => 'required|confirmed:new_password'
            ]);
            
            $userId = $_SESSION['user_id'];
            $result = $this->userService->updatePassword(
                $userId,
                $validated['current_password'],
                $validated['new_password']
            );
            
            if ($result['success']) {
                $this->logUserAction('password_changed', $userId);
                
                if ($this->isAjaxRequest()) {
                    $this->jsonSuccess(null, 'Password updated successfully');
                } else {
                    $this->redirectWithSuccess('/profile', 'Password updated successfully');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonError($result['message'], null, 422);
                } else {
                    $this->redirectWithError('/change-password', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            $this->handleError('Change password error: ' . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                $this->jsonError('An error occurred while updating your password.');
            } else {
                $this->redirectWithError('/change-password', 'An error occurred while updating your password.');
            }
        }
    }
    
    /**
     * Get user profile (API endpoint)
     */
    public function getProfile() {
        $this->requireAuth();
        
        try {
            $userId = $_SESSION['user_id'];
            $result = $this->userService->getUserProfile($userId);
            
            if ($result['success']) {
                $this->jsonSuccess($result['user']);
            } else {
                $this->jsonError($result['message'], null, 404);
            }
            
        } catch (Exception $e) {
            $this->handleError('Get profile error: ' . $e->getMessage());
            $this->jsonError('Failed to retrieve profile information');
        }
    }
    
    /**
     * Check authentication status (API endpoint)
     */
    public function checkAuth() {
        $isAuthenticated = $this->userService->validateSession();
        
        if ($isAuthenticated) {
            $this->jsonSuccess([
                'authenticated' => true,
                'user' => $this->currentUser()
            ]);
        } else {
            $this->jsonError('Not authenticated', null, 401);
        }
    }
    
    // Private helper methods
    
    private function getPostLoginRedirectUrl($user) {
        // Check for intended URL in session
        if (isset($_SESSION['intended_url'])) {
            $url = $_SESSION['intended_url'];
            unset($_SESSION['intended_url']);
            return $url;
        }
        
        // Default redirects based on user type
        switch ($user['user_type']) {
            case 'admin':
                return '/admin/dashboard';
            case 'mentor':
                return '/mentor/dashboard';
            case 'member':
            case 'student':
            default:
                return '/dashboard';
        }
    }
    
    private function handleError($message, $context = []) {
        $this->logFailure('auth_operation', $context, $message);
        $this->logger->error($message, array_merge($context, [
            'controller' => 'AuthController',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]));
    }
}