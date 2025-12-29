<?php
/**
 * Api\Mentor\AttendanceController
 *
 * Handles mentor-specific API attendance operations
 *
 * Phase 3 Week 4: Modern Routing System - Full Implementation
 * Created: November 11, 2025 (stub)
 * Updated: November 26, 2025 (full implementation)
 */

require_once __DIR__ . '/../../BaseController.php';
require_once __DIR__ . '/../../../Services/AttendanceService.php';
require_once __DIR__ . '/../../../../core/CSRF.php';

class AttendanceController extends BaseController {
    private $attendanceService;

    public function __construct() {
        global $conn;
        parent::__construct($conn);

        $this->attendanceService = new AttendanceService($conn);
    }

    /**
     * Check if current user is a mentor or admin
     *
     * @return bool True if authorized, false otherwise
     */
    private function checkMentor() {
        $userType = $_SESSION['user_type'] ?? '';

        if (!in_array($userType, ['mentor', 'admin'])) {
            return false;
        }

        return true;
    }

    /**
     * Get recent attendance records
     * GET /api/v1/mentor/attendance/recent
     *
     * Expected query parameters:
     * - date: Optional date (YYYY-MM-DD), defaults to today
     * - status: Optional filter ('signed_in', 'signed_out', 'all'), defaults to 'all'
     *
     * Response:
     * - success: boolean
     * - date: Date of records
     * - signed_in: Array of currently signed-in users
     * - signed_out: Array of signed-out users
     * - counts: Object with counts
     */
    public function recent() {
        try {
            // Check mentor authorization
            if (!$this->checkMentor()) {
                return $this->jsonError('Mentor/Admin access required', null, 403);
            }

            // Get current attendance
            $result = $this->attendanceService->getCurrentAttendance();

            if ($result['success']) {
                $this->logger->info("Mentor attendance list retrieved", [
                    'date' => $result['date'],
                    'signed_in_count' => $result['counts']['signed_in'],
                    'total_count' => $result['counts']['total']
                ]);

                return $this->jsonSuccess([
                    'date' => $result['date'],
                    'signed_in' => $result['signed_in'],
                    'signed_out' => $result['signed_out'],
                    'counts' => $result['counts']
                ], 'Attendance records retrieved successfully');
            } else {
                return $this->jsonError($result['message'], null, 400);
            }

        } catch (Exception $e) {
            $this->logger->error("Mentor attendance list failed", [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving attendance records', null, 500);
        }
    }

    /**
     * Bulk sign out multiple users
     * POST /api/v1/mentor/attendance/bulk-signout
     *
     * Expected POST data:
     * - user_ids: Array of user IDs to sign out
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - results: Array of sign-out results for each user
     * - summary: Object with success/failure counts
     * - message: Status message
     */
    public function bulkSignout() {
        try {
            // Check mentor authorization
            if (!$this->checkMentor()) {
                return $this->jsonError('Mentor/Admin access required', null, 403);
            }

            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Get user IDs from request
            $userIds = $_POST['user_ids'] ?? [];

            // Validate input
            if (empty($userIds) || !is_array($userIds)) {
                return $this->jsonError('Missing or invalid user_ids array', null, 400);
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

            $this->logger->info("Bulk signout completed", [
                'mentor_id' => $_SESSION['user_id'] ?? 'unknown',
                'total_users' => count($userIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

            // Determine overall success
            $overallSuccess = $successCount > 0;
            $message = $successCount === count($userIds)
                ? 'All users signed out successfully'
                : ($successCount > 0
                    ? "Partial success: $successCount of " . count($userIds) . " users signed out"
                    : 'No users were signed out');

            return $this->jsonSuccess([
                'results' => $results,
                'summary' => [
                    'total' => count($userIds),
                    'success' => $successCount,
                    'failed' => $failureCount
                ]
            ], $message);

        } catch (Exception $e) {
            $this->logger->error("Bulk signout failed", [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during bulk sign-out', null, 500);
        }
    }
}
