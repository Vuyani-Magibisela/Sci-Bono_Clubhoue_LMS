<?php
/**
 * AttendanceRegisterController - Daily attendance register management
 * Phase 4: Week 3 - Migrated to extend BaseController
 *
 * Handles display and management of daily attendance registers with
 * filtering, grouping, and PDF generation capabilities.
 *
 * @see app/Views/dailyAttendanceRegister.php
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/AttendanceRegisterModel.php';

class AttendanceRegisterController extends BaseController {
    private $attendanceModel;

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     * @param array|null $config Optional configuration
     */
    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->attendanceModel = new AttendanceRegisterModel($this->conn);
    }

    /**
     * Get attendance data for display
     *
     * @param string $date Date in Y-m-d format
     * @param string $filter User type filter or 'all'
     * @return array Processed attendance data
     */
    public function getAttendanceData($date, $filter = 'all') {
        try {
            $attendees = $this->attendanceModel->getAttendanceByDate($date);
            $counts = $this->attendanceModel->getAttendanceCountByType($date);

            // Group attendees by user type if filter is 'all'
            $groupedAttendees = [];
            if ($filter === 'all') {
                foreach ($attendees as $attendee) {
                    $userType = $attendee['user_type'];
                    if (!isset($groupedAttendees[$userType])) {
                        $groupedAttendees[$userType] = [];
                    }
                    $groupedAttendees[$userType][] = $attendee;
                }
            } else {
                // Filter attendees by user type
                $filteredAttendees = array_filter($attendees, function($attendee) use ($filter) {
                    return $attendee['user_type'] === $filter;
                });
                $groupedAttendees[$filter] = $filteredAttendees;
            }

            $this->logAction('attendance_register_view', [
                'date' => $date,
                'filter' => $filter,
                'count' => count($attendees)
            ]);

            return [
                'groupedAttendees' => $groupedAttendees,
                'counts' => $counts,
                'date' => $date,
                'filter' => $filter
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get attendance data", [
                'date' => $date,
                'filter' => $filter,
                'error' => $e->getMessage()
            ]);

            return [
                'groupedAttendees' => [],
                'counts' => [],
                'date' => $date,
                'filter' => $filter,
                'error' => 'Failed to load attendance data'
            ];
        }
    }

    /**
     * Get a list of active dates with attendance records
     *
     * @return array Dates with attendance records
     */
    public function getActiveDates() {
        try {
            return $this->attendanceModel->getActiveDates();
        } catch (Exception $e) {
            $this->logger->error("Failed to get active dates", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Format attendance time for display
     *
     * @param string $timestamp MySQL datetime
     * @return string Formatted time (e.g., "14:30")
     */
    public function formatTime($timestamp) {
        if (empty($timestamp)) return '-';
        return date('H:i', strtotime($timestamp));
    }

    /**
     * Calculate attendance duration
     *
     * @param string $checkIn Check-in timestamp
     * @param string $checkOut Check-out timestamp
     * @return string Formatted duration (e.g., "2h 30m")
     */
    public function calculateDuration($checkIn, $checkOut) {
        if (empty($checkIn) || empty($checkOut)) {
            return '-';
        }

        $checkInTime = strtotime($checkIn);
        $checkOutTime = strtotime($checkOut);
        $durationMinutes = round(($checkOutTime - $checkInTime) / 60);

        if ($durationMinutes < 0) {
            return 'Invalid';
        }

        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }

    /**
     * Display attendance register page
     * Route: GET /attendance/register
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function index() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $date = $this->input('date', date('Y-m-d'));
            $filter = $this->input('filter', 'all');

            $data = $this->getAttendanceData($date, $filter);
            $data['activeDates'] = $this->getActiveDates();
            $data['pageTitle'] = 'Daily Attendance Register';
            $data['currentPage'] = 'attendance';
            $data['user'] = $this->currentUser();

            return $this->view('attendance.register', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Attendance register page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load attendance register'], 'error');
        }
    }

    /**
     * Export attendance register to PDF
     * Route: POST /attendance/register/export
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function export() {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $date = $this->input('date', date('Y-m-d'));
            $filter = $this->input('filter', 'all');

            $data = $this->getAttendanceData($date, $filter);

            // TODO: Implement PDF generation
            // For now, return JSON response
            if ($this->isAjaxRequest()) {
                return $this->jsonSuccess([
                    'message' => 'PDF export feature coming soon',
                    'data' => $data
                ]);
            }

            return $this->redirect('/attendance/register');

        } catch (Exception $e) {
            $this->logger->error("Attendance export failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError('Export failed', 500);
            }

            return $this->redirect('/attendance/register?error=export_failed');
        }
    }
}
?>
