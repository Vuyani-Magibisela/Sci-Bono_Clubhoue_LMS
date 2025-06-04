<?php
/**
 * Debug Version of AttendanceController - Add debugging for password issues
 */

require_once __DIR__ . '/../Models/AttendanceModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ActivityLogModel.php';

class AttendanceController {
    private $conn;
    private $attendanceModel;
    private $userModel;
    private $activityLogModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->attendanceModel = new AttendanceModel($conn);
        $this->userModel = new UserModel($conn);
        $this->activityLogModel = new ActivityLogModel($conn);
    }
    
    /**
     * Handle AJAX sign-in request with enhanced debugging
     */
    public function handleSignIn() {
        header('Content-Type: application/json');
        
        // Enable error logging for debugging
        error_log("=== SIGNIN DEBUG START ===");
        error_log("POST data: " . print_r($_POST, true));
        
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            // Get and validate inputs
            $userId = intval($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            
            error_log("Received userId: $userId");
            error_log("Received password length: " . strlen($password));
            
            if ($userId <= 0) {
                error_log("Invalid user ID: $userId");
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                return;
            }
            
            if (empty($password)) {
                error_log("Empty password provided");
                echo json_encode(['success' => false, 'message' => 'Password is required']);
                return;
            }
            
            // Check if user exists first
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                error_log("User not found with ID: $userId");
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            
            error_log("Found user: " . $user['username']);
            error_log("User password hash: " . substr($user['password'], 0, 20) . "...");
            
            // Check for too many failed attempts
            $failedAttempts = $this->activityLogModel->getFailedLoginAttempts($userId, 30);
            error_log("Failed attempts for user $userId: $failedAttempts");
            
            if ($failedAttempts >= 5) {
                $this->activityLogModel->logSigninAttempt($userId, false, 'Too many failed attempts');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Too many failed attempts. Please try again later.',
                    'code' => 'TOO_MANY_ATTEMPTS'
                ]);
                return;
            }
            
            // Debug password validation
            error_log("Starting password validation...");
            
            // Test different password scenarios
            $validationResults = [
                'hashed_check' => false,
                'plain_check' => false,
                'dev_check' => false
            ];
            
            // 1. Check hashed password
            if (!empty($user['password'])) {
                $validationResults['hashed_check'] = password_verify($password, $user['password']);
                error_log("Hashed password check: " . ($validationResults['hashed_check'] ? 'PASS' : 'FAIL'));
            }
            
            // 2. Check plain text password (legacy)
            if (!empty($user['password'])) {
                $validationResults['plain_check'] = ($user['password'] === $password);
                error_log("Plain password check: " . ($validationResults['plain_check'] ? 'PASS' : 'FAIL'));
            }
            
            // 3. Check development passwords
            $devPasswords = [(string)$userId, 'test123', 'clubhouse', $user['username']];
            foreach ($devPasswords as $devPass) {
                if ($password === $devPass) {
                    $validationResults['dev_check'] = true;
                    error_log("Dev password matched: $devPass");
                    break;
                }
            }
            
            error_log("Validation results: " . print_r($validationResults, true));
            
            // Check if any validation passed
            $isValid = $validationResults['hashed_check'] || 
                      $validationResults['plain_check'] || 
                      $validationResults['dev_check'];
            
            if (!$isValid) {
                error_log("Password validation FAILED for user $userId");
                $this->activityLogModel->logSigninAttempt($userId, false, 'Invalid credentials');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid credentials',
                    'code' => 'INVALID_CREDENTIALS',
                    'debug' => [
                        'user_exists' => true,
                        'password_length' => strlen($password),
                        'stored_password_length' => strlen($user['password']),
                        'validation_attempts' => $validationResults
                    ]
                ]);
                return;
            }
            
            error_log("Password validation PASSED for user $userId");
            
            // Attempt to sign in user
            $result = $this->attendanceModel->signInUser($userId);
            
            if ($result['success']) {
                // Log successful signin
                $this->activityLogModel->logSigninAttempt($userId, true);
                
                // Update last login
                $this->userModel->updateLastLogin($userId);
                
                error_log("Signin successful for user $userId");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Successfully signed in',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'surname' => $user['surname']
                    ],
                    'timestamp' => $result['timestamp']
                ]);
            } else {
                error_log("Signin failed for user $userId: " . $result['message']);
                // Log failed signin
                $this->activityLogModel->logSigninAttempt($userId, false, $result['message']);
                echo json_encode($result);
            }
            
        } catch (Exception $e) {
            error_log("Exception in handleSignIn: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'message' => 'An error occurred. Please try again.',
                'code' => 'SYSTEM_ERROR',
                'debug' => $e->getMessage()
            ]);
        } finally {
            error_log("=== SIGNIN DEBUG END ===");
        }
    }
    
    // ... rest of your existing methods remain the same ...
    
    /**
     * Display the main attendance page
     */
    public function showAttendancePage() {
        try {
            // Get all users with their attendance status
            $users = $this->attendanceModel->getAllUsersWithAttendance();
            
            // Separate signed in and signed out users
            $signedInUsers = [];
            $signedOutUsers = [];
            $signedInCount = 0;
            
            foreach ($users as $user) {
                $user['search_terms'] = $this->userModel->createSearchTerms($user);
                $user['role_class'] = $this->userModel->getUserRoleClass($user['user_type']);
                $user['is_signed_in'] = !empty($user['sign_in_status']) && $user['sign_in_status'] === 'signedIn';
                
                if ($user['is_signed_in']) {
                    $signedInUsers[] = $user;
                    $signedInCount++;
                } else {
                    $signedOutUsers[] = $user;
                }
            }
            
            // Get attendance statistics
            $stats = $this->attendanceModel->getTodayAttendanceStats();
            
            // Include the view
            include __DIR__ . '/../Views/attendance/signin.php';
            
        } catch (Exception $e) {
            error_log("Error in showAttendancePage: " . $e->getMessage());
            $this->handleError("Unable to load attendance page. Please try again.");
        }
    }
    
    /**
     * Handle AJAX sign-out request
     */
    public function handleSignOut() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                return;
            }
            
            if (!$this->userModel->userExists($userId)) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            
            $result = $this->attendanceModel->signOutUser($userId);
            $this->activityLogModel->logSignoutAttempt($userId, $result['success']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Error in handleSignOut: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'An error occurred. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ]);
        }
    }
    
    /**
     * Format time for display
     */
    public function formatTime($datetime) {
        return $datetime ? date('g:i A', strtotime($datetime)) : 'Unknown';
    }
    
    /**
     * Handle errors gracefully
     */
    private function handleError($message) {
        echo "<div class='error-message'>{$message}</div>";
    }
}