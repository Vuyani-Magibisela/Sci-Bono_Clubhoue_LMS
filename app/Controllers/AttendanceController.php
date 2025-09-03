<?php
/**
 * Attendance Controller - Refactored to use new MVC architecture
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/AttendanceService.php';
require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Traits/LogsActivity.php';

class AttendanceController extends BaseController {
    use LogsActivity;
    
    private $attendanceService;
    private $userService;
    
    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->attendanceService = new AttendanceService($conn);
        $this->userService = new UserService($conn);
    }
    
    /**
     * Handle AJAX sign-in request
     */
    public function handleSignIn() {
        try {
            // Get and validate input
            $input = $this->input();
            $validated = $this->validate($input, [
                'user_id' => 'required|integer',
                'password' => 'required|min:1'
            ]);
            
            $userId = $validated['user_id'];
            $password = $validated['password'];
            
            $this->logActivity('signin_attempt', ['user_id' => $userId]);
            
            // First authenticate the user using UserService
            $userResult = $this->userService->getUserProfile($userId);
            
            if (!$userResult['success']) {
                $this->logFailure('signin_auth', ['user_id' => $userId], 'User not found');
                return $this->jsonError('User not found', null, 404);
            }
            
            $user = $userResult['user'];
            
            // Verify password for attendance access
            $authResult = $this->userService->authenticate($user['email'], $password);
            
            if (!$authResult['success']) {
                // Try username if email failed
                $authResult = $this->userService->authenticate($user['username'], $password);
            }
            
            if (!$authResult['success']) {
                $this->logFailure('signin_auth', ['user_id' => $userId], 'Invalid credentials');
                return $this->jsonError('Invalid credentials', null, 401);
            }
            
            // Now perform the sign-in using AttendanceService
            $signInResult = $this->attendanceService->signIn($userId, [
                'method' => 'password_auth',
                'location' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            if ($signInResult['success']) {
                $this->logSuccess('signin_complete', [
                    'user_id' => $userId,
                    'attendance_id' => $signInResult['attendance_id']
                ]);
                
                return $this->jsonSuccess([
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'surname' => $user['surname'],
                        'user_type' => $user['user_type']
                    ],
                    'attendance_id' => $signInResult['attendance_id'],
                    'signin_time' => $signInResult['signin_time']
                ], 'Successfully signed in');
            } else {
                $this->logFailure('signin_attendance', ['user_id' => $userId], $signInResult['message']);
                return $this->jsonError($signInResult['message'], null, 422);
            }
            
        } catch (Exception $e) {
            $this->logFailure('signin_system', ['user_id' => $input['user_id'] ?? 'unknown'], $e->getMessage());
            return $this->jsonError('An error occurred during sign-in. Please try again.');
        }
    }
    
    // ... rest of your existing methods remain the same ...
    
    /**
     * Display the main attendance page
     */
    public function showAttendancePage() {
        try {
            $this->logAction('attendance_page_view');
            
            // Get current attendance data
            $attendanceData = $this->attendanceService->getCurrentAttendance();
            
            if (!$attendanceData['success']) {
                throw new Exception('Failed to retrieve attendance data');
            }
            
            // Get attendance statistics
            $statsData = $this->attendanceService->getAttendanceStats();
            
            if (!$statsData['success']) {
                throw new Exception('Failed to retrieve attendance statistics');
            }
            
            // Prepare data for the view
            $viewData = [
                'page_title' => 'Attendance System',
                'signed_in_users' => $attendanceData['signed_in'],
                'signed_out_users' => $attendanceData['signed_out'],
                'attendance_counts' => $attendanceData['counts'],
                'attendance_date' => $attendanceData['date'],
                'stats' => $statsData['stats'],
                'current_user' => $this->currentUser()
            ];
            
            // Add search terms and styling for each user
            foreach ($viewData['signed_in_users'] as &$user) {
                $user['search_terms'] = $this->createSearchTerms($user);
                $user['role_class'] = $this->getUserRoleClass($user['user_type']);
                $user['is_signed_in'] = true;
            }
            
            foreach ($viewData['signed_out_users'] as &$user) {
                $user['search_terms'] = $this->createSearchTerms($user);
                $user['role_class'] = $this->getUserRoleClass($user['user_type']);
                $user['is_signed_in'] = false;
            }
            
            $this->view('attendance/signin', $viewData);
            
        } catch (Exception $e) {
            $this->logFailure('attendance_page_load', [], $e->getMessage());
            $this->view('errors/500', [
                'error_message' => 'Unable to load attendance page. Please try again.',
                'page_title' => 'Error'
            ]);
        }
    }
    
    /**
     * Handle AJAX sign-out request
     */
    public function handleSignOut() {
        try {
            // Get and validate input
            $input = $this->input();
            $validated = $this->validate($input, [
                'user_id' => 'required|integer'
            ]);
            
            $userId = $validated['user_id'];
            
            $this->logActivity('signout_attempt', ['user_id' => $userId]);
            
            // Verify user exists
            $userResult = $this->userService->getUserProfile($userId);
            
            if (!$userResult['success']) {
                $this->logFailure('signout_user', ['user_id' => $userId], 'User not found');
                return $this->jsonError('User not found', null, 404);
            }
            
            // Perform sign-out using AttendanceService
            $signOutResult = $this->attendanceService->signOut($userId, [
                'method' => 'manual',
                'location' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            if ($signOutResult['success']) {
                $this->logSuccess('signout_complete', [
                    'user_id' => $userId,
                    'attendance_id' => $signOutResult['attendance_id'],
                    'duration_minutes' => $signOutResult['duration_minutes']
                ]);
                
                return $this->jsonSuccess([
                    'attendance_id' => $signOutResult['attendance_id'],
                    'signout_time' => $signOutResult['signout_time'],
                    'duration_minutes' => $signOutResult['duration_minutes'],
                    'duration_formatted' => $signOutResult['duration_formatted']
                ], 'Successfully signed out');
            } else {
                $this->logFailure('signout_attendance', ['user_id' => $userId], $signOutResult['message']);
                return $this->jsonError($signOutResult['message'], null, 422);
            }
            
        } catch (Exception $e) {
            $this->logFailure('signout_system', ['user_id' => $input['user_id'] ?? 'unknown'], $e->getMessage());
            return $this->jsonError('An error occurred during sign-out. Please try again.');
        }
    }
    
    /**
     * Search attendance records (API endpoint)
     */
    public function searchAttendance() {
        try {
            $input = $this->input();
            $query = $input['query'] ?? '';
            $limit = (int) ($input['limit'] ?? 50);
            
            if (empty($query)) {
                return $this->jsonError('Search query is required');
            }
            
            $result = $this->attendanceService->searchAttendance($query, [
                'limit' => $limit,
                'start_date' => $input['start_date'] ?? null,
                'end_date' => $input['end_date'] ?? null
            ]);
            
            if ($result['success']) {
                return $this->jsonSuccess($result);
            } else {
                return $this->jsonError($result['message']);
            }
            
        } catch (Exception $e) {
            $this->logFailure('search_attendance', [], $e->getMessage());
            return $this->jsonError('Search failed. Please try again.');
        }
    }
    
    /**
     * Get attendance statistics (API endpoint)
     */
    public function getStats() {
        try {
            $input = $this->input();
            
            $result = $this->attendanceService->getAttendanceStats([
                'start_date' => $input['start_date'] ?? null,
                'end_date' => $input['end_date'] ?? null
            ]);
            
            if ($result['success']) {
                return $this->jsonSuccess($result['stats']);
            } else {
                return $this->jsonError($result['message']);
            }
            
        } catch (Exception $e) {
            $this->logFailure('get_attendance_stats', [], $e->getMessage());
            return $this->jsonError('Failed to retrieve statistics');
        }
    }
    
    /**
     * Get user attendance history (API endpoint)
     */
    public function getUserAttendance() {
        try {
            $input = $this->input();
            $validated = $this->validate($input, [
                'user_id' => 'required|integer'
            ]);
            
            $result = $this->attendanceService->getUserAttendance($validated['user_id'], [
                'start_date' => $input['start_date'] ?? null,
                'end_date' => $input['end_date'] ?? null,
                'limit' => (int) ($input['limit'] ?? 100)
            ]);
            
            if ($result['success']) {
                return $this->jsonSuccess($result);
            } else {
                return $this->jsonError($result['message']);
            }
            
        } catch (Exception $e) {
            $this->logFailure('get_user_attendance', [], $e->getMessage());
            return $this->jsonError('Failed to retrieve user attendance');
        }
    }
    
    /**
     * Get current attendance status (API endpoint)
     */
    public function getCurrentAttendance() {
        try {
            $result = $this->attendanceService->getCurrentAttendance();
            
            if ($result['success']) {
                return $this->jsonSuccess($result);
            } else {
                return $this->jsonError($result['message']);
            }
            
        } catch (Exception $e) {
            $this->logFailure('get_current_attendance', [], $e->getMessage());
            return $this->jsonError('Failed to retrieve current attendance');
        }
    }
    
    // Private helper methods
    
    /**
     * Create search terms for a user
     */
    private function createSearchTerms($user) {
        $terms = [
            strtolower($user['username'] ?? ''),
            strtolower($user['name'] ?? ''),
            strtolower($user['surname'] ?? ''),
            strtolower(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')),
            strtolower($user['user_type'] ?? '')
        ];
        
        if (!empty($user['email'])) {
            $terms[] = strtolower($user['email']);
        }
        
        return implode(' ', array_unique(array_filter($terms)));
    }
    
    /**
     * Get user role CSS class
     */
    private function getUserRoleClass($userType) {
        $classes = [
            'admin' => 'admin',
            'mentor' => 'mentor',
            'member' => 'member',
            'student' => 'member',
            'alumni' => 'member'
        ];
        
        return $classes[$userType ?? 'member'] ?? 'member';
    }
    
    /**
     * Format time for display
     */
    private function formatTime($datetime) {
        return $datetime ? date('g:i A', strtotime($datetime)) : 'Unknown';
    }
}