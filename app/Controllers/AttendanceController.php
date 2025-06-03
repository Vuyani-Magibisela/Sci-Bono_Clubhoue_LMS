<?php
/**
 * AttendanceController - Handles attendance-related requests and business logic
 * 
 * @package Controllers
 * @author Sci-Bono Clubhouse LMS
 * @version 1.0
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
     * Handle AJAX sign-in request
     */
    public function handleSignIn() {
        header('Content-Type: application/json');
        
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            // Get and validate inputs
            $userId = intval($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                return;
            }
            
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required']);
                return;
            }
            
            // Check for too many failed attempts
            $failedAttempts = $this->activityLogModel->getFailedLoginAttempts($userId, 30);
            if ($failedAttempts >= 5) {
                $this->activityLogModel->logSigninAttempt($userId, false, 'Too many failed attempts');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Too many failed attempts. Please try again later.',
                    'code' => 'TOO_MANY_ATTEMPTS'
                ]);
                return;
            }
            
            // Validate user credentials
            $user = $this->userModel->validateCredentials($userId, $password);
            if (!$user) {
                $this->activityLogModel->logSigninAttempt($userId, false, 'Invalid credentials');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid credentials',
                    'code' => 'INVALID_CREDENTIALS'
                ]);
                return;
            }
            
            // Attempt to sign in user
            $result = $this->attendanceModel->signInUser($userId);
            
            if ($result['success']) {
                // Log successful signin
                $this->activityLogModel->logSigninAttempt($userId, true);
                
                // Update last login
                $this->userModel->updateLastLogin($userId);
                
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
                // Log failed signin
                $this->activityLogModel->logSigninAttempt($userId, false, $result['message']);
                echo json_encode($result);
            }
            
        } catch (Exception $e) {
            error_log("Error in handleSignIn: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'An error occurred. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ]);
        }
    }
    
    /**
     * Handle AJAX sign-out request
     */
    public function handleSignOut() {
        header('Content-Type: application/json');
        
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            // Get and validate inputs
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                return;
            }
            
            // Verify user exists
            if (!$this->userModel->userExists($userId)) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            
            // Attempt to sign out user
            $result = $this->attendanceModel->signOutUser($userId);
            
            // Log the attempt
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
     * Handle user search AJAX request
     */
    public function handleUserSearch() {
        header('Content-Type: application/json');
        
        try {
            $searchTerm = $_GET['q'] ?? '';
            $users = $this->userModel->searchUsers($searchTerm);
            
            // Add attendance status to users
            $usersWithAttendance = [];
            foreach ($users as $user) {
                $user['is_signed_in'] = $this->attendanceModel->isUserSignedIn($user['id']);
                $user['search_terms'] = $this->userModel->createSearchTerms($user);
                $user['role_class'] = $this->userModel->getUserRoleClass($user['user_type']);
                $usersWithAttendance[] = $user;
            }
            
            echo json_encode([
                'success' => true,
                'users' => $usersWithAttendance,
                'count' => count($usersWithAttendance)
            ]);
            
        } catch (Exception $e) {
            error_log("Error in handleUserSearch: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Search failed. Please try again.'
            ]);
        }
    }
    
    /**
     * Get attendance statistics
     */
    public function getAttendanceStats() {
        header('Content-Type: application/json');
        
        try {
            $timeframe = $_GET['timeframe'] ?? 'today';
            
            $attendanceStats = $this->attendanceModel->getTodayAttendanceStats();
            $activityStats = $this->activityLogModel->getActivityStats($timeframe);
            $userStats = $this->userModel->getUserStats();
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'attendance' => $attendanceStats,
                    'activity' => $activityStats,
                    'users' => $userStats
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getAttendanceStats: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Unable to load statistics'
            ]);
        }
    }
    
    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities() {
        header('Content-Type: application/json');
        
        try {
            $limit = intval($_GET['limit'] ?? 20);
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
            
            $activities = $this->activityLogModel->getRecentActivities($limit, $userId);
            
            echo json_encode([
                'success' => true,
                'activities' => $activities,
                'count' => count($activities)
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getRecentActivities: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Unable to load recent activities'
            ]);
        }
    }
    
    /**
     * Handle bulk sign-out (emergency feature)
     */
    public function handleBulkSignOut() {
        header('Content-Type: application/json');
        
        try {
            // This should be restricted to admin users only
            // Add authentication check here
            
            $signedInUsers = $this->attendanceModel->getSignedInUsers();
            $successCount = 0;
            $failureCount = 0;
            
            foreach ($signedInUsers as $user) {
                $result = $this->attendanceModel->signOutUser($user['id']);
                if ($result['success']) {
                    $this->activityLogModel->logSignoutAttempt($user['id'], true);
                    $successCount++;
                } else {
                    $this->activityLogModel->logSignoutAttempt($user['id'], false);
                    $failureCount++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Bulk sign-out completed. {$successCount} successful, {$failureCount} failed.",
                'stats' => [
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'total_processed' => count($signedInUsers)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in handleBulkSignOut: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Bulk sign-out failed. Please try again.'
            ]);
        }
    }
    
    /**
     * Format time for display
     * 
     * @param string $datetime Datetime string
     * @return string Formatted time
     */
    public function formatTime($datetime) {
        return $datetime ? date('g:i A', strtotime($datetime)) : 'Unknown';
    }
    
    /**
     * Handle errors gracefully
     * 
     * @param string $message Error message
     */
    private function handleError($message) {
        // In a real application, you might want to show a proper error page
        echo "<div class='error-message'>{$message}</div>";
    }
    
    /**
     * Validate request origin (CSRF protection)
     * 
     * @return bool True if request is valid
     */
    private function validateRequest() {
        // Add CSRF token validation here
        // For now, just check if it's a POST request for sensitive operations
        return true;
    }
}