<?php
/**
 * Mentor\AttendanceController
 *
 * Handles mentor attendance management (web views)
 *
 * Phase 3 Week 4: Modern Routing System - Full Implementation
 * Created: November 11, 2025 (stub)
 * Updated: November 26, 2025 (full implementation)
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/AttendanceService.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class AttendanceController extends BaseController {
    private $attendanceService;

    public function __construct() {
        global $conn;
        parent::__construct($conn);

        $this->attendanceService = new AttendanceService($conn);
    }

    /**
     * Check mentor authentication and authorization
     *
     * @return void Redirects to login if not authenticated
     */
    private function checkMentorAuth() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }

        $userType = $_SESSION['user_type'] ?? '';
        if (!in_array($userType, ['mentor', 'admin'])) {
            http_response_code(403);
            echo 'Access Denied - Mentor/Admin Only';
            exit;
        }
    }

    /**
     * Display mentor attendance dashboard
     * GET /mentor/attendance
     *
     * Shows list of all attendance records for today with ability to filter
     *
     * @return void Renders view
     */
    public function index() {
        try {
            $this->checkMentorAuth();

            // Get current attendance data
            $result = $this->attendanceService->getCurrentAttendance();

            if (!$result['success']) {
                $_SESSION['error'] = $result['message'] ?? 'Failed to load attendance records';
                $attendanceData = [
                    'date' => date('Y-m-d'),
                    'signed_in' => [],
                    'signed_out' => [],
                    'counts' => ['signed_in' => 0, 'signed_out' => 0, 'total' => 0]
                ];
            } else {
                $attendanceData = $result;
            }

            // Get attendance stats
            $statsResult = $this->attendanceService->getAttendanceStats([
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d')
            ]);

            $stats = $statsResult['success'] ? $statsResult['stats'] : null;

            // Generate CSRF token
            $csrfToken = CSRF::generateToken();

            // Log access
            $this->logger->info("Mentor attendance dashboard accessed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'date' => $attendanceData['date'],
                'total_records' => $attendanceData['counts']['total']
            ]);

            // Pass data to view
            $data = [
                'attendanceData' => $attendanceData,
                'stats' => $stats,
                'csrfToken' => $csrfToken,
                'currentDate' => date('Y-m-d'),
                'mentorName' => $_SESSION['name'] ?? 'Mentor'
            ];

            // Render view
            $this->renderView('mentor/attendance/index', $data);

        } catch (Exception $e) {
            $this->logger->error("Mentor attendance index failed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'An error occurred while loading attendance records';
            $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor');
        }
    }

    /**
     * Display attendance register for manual sign-in
     * GET /mentor/attendance/register
     *
     * Shows interface for mentors to manually register attendance
     *
     * @return void Renders view
     */
    public function register() {
        try {
            $this->checkMentorAuth();

            // Get today's attendance stats
            $statsResult = $this->attendanceService->getAttendanceStats([
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d')
            ]);

            $todayStats = $statsResult['success']
                ? $statsResult['stats']['today']
                : ['total' => 0, 'signed_in' => 0, 'signed_out' => 0];

            // Get recently signed-in users (last 10)
            $currentAttendance = $this->attendanceService->getCurrentAttendance();
            $recentSignIns = $currentAttendance['success']
                ? array_slice($currentAttendance['signed_in'], 0, 10)
                : [];

            // Generate CSRF token
            $csrfToken = CSRF::generateToken();

            // Log access
            $this->logger->info("Mentor attendance register accessed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);

            // Pass data to view
            $data = [
                'todayStats' => $todayStats,
                'recentSignIns' => $recentSignIns,
                'csrfToken' => $csrfToken,
                'currentDate' => date('Y-m-d'),
                'mentorName' => $_SESSION['name'] ?? 'Mentor'
            ];

            // Render view
            $this->renderView('mentor/attendance/register', $data);

        } catch (Exception $e) {
            $this->logger->error("Mentor attendance register failed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'An error occurred while loading the attendance register';
            $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor/attendance');
        }
    }

    /**
     * Handle bulk signout request
     * POST /mentor/attendance/bulk-signout
     *
     * Signs out multiple users at once
     *
     * @return void JSON response or redirect
     */
    public function bulkSignout() {
        try {
            $this->checkMentorAuth();

            // Validate CSRF token
            if (!CSRF::validateToken()) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonError('Invalid CSRF token', null, 403);
                }

                $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
                $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor/attendance');
                return;
            }

            // Get user IDs from request
            $userIds = $_POST['user_ids'] ?? [];

            // Validate input
            if (empty($userIds)) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonError('No users selected for sign-out', null, 400);
                }

                $_SESSION['error'] = 'Please select at least one user to sign out';
                $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor/attendance');
                return;
            }

            // Ensure user_ids is an array
            if (!is_array($userIds)) {
                $userIds = explode(',', $userIds);
            }

            // Initialize results
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            // Sign out each user
            foreach ($userIds as $userId) {
                try {
                    $result = $this->attendanceService->signOut($userId);

                    if ($result['success']) {
                        $successCount++;
                        $results[] = [
                            'user_id' => $userId,
                            'success' => true,
                            'attendance_id' => $result['attendance_id'],
                            'duration_minutes' => $result['duration_minutes']
                        ];
                    } else {
                        $failureCount++;
                        $results[] = [
                            'user_id' => $userId,
                            'success' => false,
                            'error' => $result['message']
                        ];
                    }
                } catch (Exception $e) {
                    $failureCount++;
                    $results[] = [
                        'user_id' => $userId,
                        'success' => false,
                        'error' => 'Server error during sign-out'
                    ];
                }
            }

            // Log the bulk signout
            $this->logger->info("Bulk signout performed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'total_users' => count($userIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

            // Prepare response message
            $message = $successCount === count($userIds)
                ? "All {$successCount} users signed out successfully"
                : ($successCount > 0
                    ? "Partial success: {$successCount} of " . count($userIds) . " users signed out"
                    : 'Failed to sign out users');

            // Return appropriate response
            if ($this->isAjaxRequest()) {
                return $this->jsonSuccess([
                    'results' => $results,
                    'summary' => [
                        'total' => count($userIds),
                        'success' => $successCount,
                        'failed' => $failureCount
                    ]
                ], $message);
            } else {
                if ($successCount > 0) {
                    $_SESSION['success'] = $message;
                } else {
                    $_SESSION['error'] = $message;
                }
                $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor/attendance');
            }

        } catch (Exception $e) {
            $this->logger->error("Bulk signout failed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($this->isAjaxRequest()) {
                return $this->jsonError('Server error during bulk sign-out', null, 500);
            } else {
                $_SESSION['error'] = 'An error occurred during bulk sign-out';
                $this->redirect('/Sci-Bono_Clubhoue_LMS/mentor/attendance');
            }
        }
    }

    /**
     * Check if current request is an AJAX request
     *
     * @return bool
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Render a view with data
     *
     * @param string $view View file path (relative to app/Views/)
     * @param array $data Data to pass to view
     * @return void
     */
    private function renderView($view, $data = []) {
        // Extract data to variables
        extract($data);

        // Build full view path
        $viewPath = __DIR__ . '/../../Views/' . $view . '.php';

        // Check if view exists
        if (!file_exists($viewPath)) {
            throw new Exception("View not found: {$view}");
        }

        // Include the view
        require $viewPath;
    }
}
