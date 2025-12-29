<?php
/**
 * Api\AttendanceController
 *
 * Handles API attendance operations
 *
 * Phase 3 Week 4: Modern Routing System - Full Implementation
 * Created: November 11, 2025 (stub)
 * Updated: November 15, 2025 (full implementation)
 * Migrated from: /app/Controllers/attendance_routes.php
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/AttendanceService.php';
require_once __DIR__ . '/../../Services/UserService.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class AttendanceController extends BaseController {
    private $attendanceService;
    private $userService;

    public function __construct() {
        global $conn;
        parent::__construct($conn);

        $this->attendanceService = new AttendanceService($conn);
        $this->userService = new UserService($conn);
    }

    /**
     * Sign in a user
     * POST /api/v1/attendance/signin
     *
     * Expected POST data:
     * - user_id: User ID to sign in
     * - password: User password for verification
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - attendance_id: ID of attendance record (on success)
     * - signin_time: Timestamp of sign-in
     * - message: Status message
     */
    public function signin() {
        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            $userId = $_POST['user_id'] ?? null;
            $password = $_POST['password'] ?? null;

            if (!$userId || !$password) {
                return $this->jsonError('Missing required fields: user_id and password', null, 400);
            }

            // Authenticate user
            $authResult = $this->userService->authenticateUser($userId, $password);

            if (!$authResult['success']) {
                return $this->jsonError($authResult['message'] ?? 'Authentication failed', null, 401);
            }

            // Sign in via attendance service
            $result = $this->attendanceService->signIn($userId);

            if ($result['success']) {
                $this->logger->info("Attendance signin successful", [
                    'user_id' => $userId,
                    'attendance_id' => $result['attendance_id']
                ]);

                return $this->jsonSuccess([
                    'attendance_id' => $result['attendance_id'],
                    'signin_time' => $result['signin_time']
                ], $result['message']);
            } else {
                return $this->jsonError($result['message'], null, 400);
            }

        } catch (Exception $e) {
            $this->logger->error("Attendance signin failed", [
                'user_id' => $_POST['user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during sign-in', null, 500);
        }
    }

    /**
     * Sign out a user
     * POST /api/v1/attendance/signout
     *
     * Expected POST data:
     * - attendance_id: ID of attendance record to sign out
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - attendance_id: ID of attendance record
     * - signout_time: Timestamp of sign-out
     * - duration_minutes: Duration of attendance in minutes
     * - message: Status message
     */
    public function signout() {
        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            $attendanceId = $_POST['attendance_id'] ?? null;

            if (!$attendanceId) {
                return $this->jsonError('Missing required field: attendance_id', null, 400);
            }

            // Get attendance record to find user_id
            $attendance = $this->attendanceService->getAttendanceById($attendanceId);

            if (!$attendance || !$attendance['success']) {
                return $this->jsonError('Attendance record not found', null, 404);
            }

            $userId = $attendance['record']['user_id'];

            // Sign out via attendance service
            $result = $this->attendanceService->signOut($userId);

            if ($result['success']) {
                $this->logger->info("Attendance signout successful", [
                    'user_id' => $userId,
                    'attendance_id' => $result['attendance_id'],
                    'duration_minutes' => $result['duration_minutes']
                ]);

                return $this->jsonSuccess([
                    'attendance_id' => $result['attendance_id'],
                    'signout_time' => $result['signout_time'],
                    'duration_minutes' => $result['duration_minutes'],
                    'duration_formatted' => $result['duration_formatted']
                ], $result['message']);
            } else {
                return $this->jsonError($result['message'], null, 400);
            }

        } catch (Exception $e) {
            $this->logger->error("Attendance signout failed", [
                'attendance_id' => $_POST['attendance_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during sign-out', null, 500);
        }
    }

    /**
     * Search users for attendance
     * GET /api/v1/attendance/search
     *
     * Expected query parameters:
     * - query: Search term (min 2 characters)
     * - limit: Optional limit (default: 50)
     *
     * Response:
     * - success: boolean
     * - results: Array of user records with attendance info
     * - count: Number of results
     */
    public function searchUsers() {
        try {
            $query = $_GET['query'] ?? '';

            if (strlen($query) < 2) {
                return $this->jsonError('Query too short. Minimum 2 characters required.', null, 400);
            }

            // Search via attendance service
            $result = $this->attendanceService->searchAttendance($query, [
                'limit' => $_GET['limit'] ?? 50
            ]);

            if ($result['success']) {
                $this->logger->info("Attendance search successful", [
                    'query' => $query,
                    'result_count' => $result['count']
                ]);

                return $this->jsonSuccess([
                    'results' => $result['records'],
                    'count' => $result['count'],
                    'query' => $result['query']
                ], 'Search completed successfully');
            } else {
                return $this->jsonError($result['message'], null, 400);
            }

        } catch (Exception $e) {
            $this->logger->error("Attendance search failed", [
                'query' => $_GET['query'] ?? '',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during search', null, 500);
        }
    }

    /**
     * Get today's attendance statistics
     * GET /api/v1/attendance/stats
     *
     * Response:
     * - success: boolean
     * - stats: Object with today's statistics
     *   - total: Total sign-ins today
     *   - signed_in: Currently signed in
     *   - signed_out: Signed out
     */
    public function stats() {
        try {
            // Get stats via attendance service
            $result = $this->attendanceService->getAttendanceStats([
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d')
            ]);

            if ($result['success']) {
                $todayStats = $result['stats']['today'];

                $this->logger->info("Attendance stats retrieved", [
                    'total' => $todayStats['total'],
                    'signed_in' => $todayStats['signed_in']
                ]);

                return $this->jsonSuccess([
                    'total_today' => $todayStats['total'],
                    'signed_in' => $todayStats['signed_in'],
                    'signed_out' => $todayStats['signed_out'],
                    'date' => date('Y-m-d')
                ], 'Stats retrieved successfully');
            } else {
                return $this->jsonError($result['message'], null, 400);
            }

        } catch (Exception $e) {
            $this->logger->error("Attendance stats failed", [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving stats', null, 500);
        }
    }
}
