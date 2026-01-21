<?php
/**
 * Holiday Program Admin Controller
 *
 * Handles administrative functions for holiday programs including dashboard,
 * registration management, workshop assignments, and bulk operations.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 3
 *
 * @package App\Controllers
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/HolidayProgramAdminModel.php';

class HolidayProgramAdminController extends BaseController {
    private $model;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->model = new HolidayProgramAdminModel($this->conn);
    }

    /**
     * Display admin dashboard
     * Modern RESTful method with role-based access control
     */
    public function index() {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        try {
            $programId = $this->input('program_id', null);
            $dashboardData = $this->getDashboardData($programId);

            $this->logAction('holiday_admin_dashboard_view', [
                'program_id' => $programId,
                'programs_count' => count($dashboardData['programs'])
            ]);

            return $this->view('programs.dashboard.admin', $dashboardData, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Failed to load admin dashboard", [
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load dashboard'
            ], 'error');
        }
    }

    /**
     * Get dashboard data for admin overview
     * Legacy method - maintained for backward compatibility
     *
     * @param int|null $programId Program ID
     * @return array Dashboard data with programs, stats, registrations, etc.
     */
    public function getDashboardData($programId = null) {
        try {
            // Get all programs for selection
            $programs = $this->model->getAllPrograms();

            // If no program specified, use the most recent one
            if (!$programId && !empty($programs)) {
                $programId = $programs[0]['id'];
            }

            $dashboardData = [
                'programs' => $programs,
                'current_program' => null,
                'stats' => null,
                'registrations' => [],
                'workshops' => [],
                'capacity_info' => null
            ];

            if ($programId) {
                $dashboardData['current_program'] = $this->model->getProgramById($programId);
                $dashboardData['stats'] = $this->model->getProgramStatistics($programId);
                $dashboardData['registrations'] = $this->model->getRegistrations($programId);
                $dashboardData['workshops'] = $this->model->getWorkshops($programId);
                $dashboardData['capacity_info'] = $this->model->getCapacityInfo($programId);

                $this->logAction('get_dashboard_data', [
                    'program_id' => $programId,
                    'registrations_count' => count($dashboardData['registrations'])
                ]);
            }

            return $dashboardData;

        } catch (Exception $e) {
            $this->logger->error("Error getting dashboard data", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Update program status (registration open/closed/closing soon)
     *
     * @param int $programId Program ID
     * @param string $status Program status
     * @param int|null $registrationOpen Registration open flag
     * @return bool True on success
     */
    public function updateProgramStatus($programId, $status, $registrationOpen = null) {
        try {
            $result = $this->model->updateProgramStatus($programId, $status, $registrationOpen);

            if ($result) {
                $this->logAction('update_program_status', [
                    'program_id' => $programId,
                    'status' => $status,
                    'registration_open' => $registrationOpen
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Failed to update program status", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Update registration status for individual attendee
     *
     * @param int $attendeeId Attendee ID
     * @param string $status Registration status
     * @return bool True on success
     */
    public function updateRegistrationStatus($attendeeId, $status) {
        try {
            $result = $this->model->updateAttendeeStatus($attendeeId, $status);

            if ($result) {
                $this->logAction('update_registration_status', [
                    'attendee_id' => $attendeeId,
                    'status' => $status
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Failed to update registration status", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Update mentor status
     *
     * @param int $attendeeId Attendee ID
     * @param string $status Mentor status
     * @return bool True on success
     */
    public function updateMentorStatus($attendeeId, $status) {
        try {
            $result = $this->model->updateMentorStatus($attendeeId, $status);

            if ($result) {
                $this->logAction('update_mentor_status', [
                    'attendee_id' => $attendeeId,
                    'status' => $status
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Failed to update mentor status", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Assign attendee to workshop
     *
     * @param int $attendeeId Attendee ID
     * @param int $workshopId Workshop ID
     * @return bool True on success
     */
    public function assignToWorkshop($attendeeId, $workshopId) {
        try {
            $result = $this->model->assignAttendeeToWorkshop($attendeeId, $workshopId);

            if ($result) {
                $this->logAction('assign_workshop', [
                    'attendee_id' => $attendeeId,
                    'workshop_id' => $workshopId
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Failed to assign workshop", [
                'attendee_id' => $attendeeId,
                'workshop_id' => $workshopId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get detailed attendee information
     *
     * @param int $attendeeId Attendee ID
     * @return array|null Attendee details or null if not found
     */
    public function getAttendeeDetails($attendeeId) {
        try {
            $details = $this->model->getAttendeeDetails($attendeeId);

            if ($details) {
                $this->logAction('get_attendee_details', [
                    'attendee_id' => $attendeeId
                ]);
            }

            return $details;

        } catch (Exception $e) {
            $this->logger->error("Failed to get attendee details", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Export registrations data
     *
     * @param int $programId Program ID
     * @param string $format Export format (csv, excel, pdf)
     * @return mixed CSV download or array data
     */
    public function exportRegistrations($programId, $format = 'csv') {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        try {
            $registrations = $this->model->getRegistrationsForExport($programId);

            $this->logAction('export_registrations', [
                'program_id' => $programId,
                'format' => $format,
                'count' => count($registrations)
            ]);

            if ($format === 'csv') {
                return $this->exportToCSV($registrations);
            }

            return $registrations;

        } catch (Exception $e) {
            $this->logger->error("Failed to export registrations", [
                'program_id' => $programId,
                'format' => $format,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get program capacity and enrollment analytics
     *
     * @param int $programId Program ID
     * @return array|null Capacity analytics or null on error
     */
    public function getCapacityAnalytics($programId) {
        try {
            $analytics = $this->model->getCapacityAnalytics($programId);

            $this->logAction('get_capacity_analytics', [
                'program_id' => $programId
            ]);

            return $analytics;

        } catch (Exception $e) {
            $this->logger->error("Failed to get capacity analytics", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Send bulk emails to program participants
     *
     * @param int $programId Program ID
     * @param string $subject Email subject
     * @param string $message Email message
     * @param string $recipients Recipient filter (all, approved, pending, etc.)
     * @return array Result with success status and count
     */
    public function sendBulkEmail($programId, $subject, $message, $recipients = 'all') {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        try {
            $attendees = $this->model->getAttendeesForEmail($programId, $recipients);

            $this->logAction('send_bulk_email', [
                'program_id' => $programId,
                'recipients_filter' => $recipients,
                'count' => count($attendees)
            ]);

            // Here you would integrate with your email service
            // For now, we'll return the count of recipients
            return [
                'success' => true,
                'recipients_count' => count($attendees),
                'message' => 'Emails queued for sending'
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to send bulk email", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue emails'
            ];
        }
    }

    /**
     * Handle AJAX requests
     * Processes admin actions via AJAX with CSRF validation
     *
     * @return void Sends JSON response
     */
    public function handleAjaxRequest() {
        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        // Validate CSRF token using BaseController method
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in AJAX request", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'action' => $_POST['action'] ?? 'unknown'
            ]);

            http_response_code(403);
            $this->jsonResponse([
                'success' => false,
                'error' => true,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ], 403);
            return;
        }

        $action = $_POST['action'] ?? '';
        $response = ['success' => false, 'message' => 'Unknown action'];

        try {
            switch ($action) {
                case 'update_program_status':
                    $programId = intval($_POST['program_id']);
                    $status = $_POST['status'];
                    $registrationOpen = isset($_POST['registration_open']) ? intval($_POST['registration_open']) : null;

                    $result = $this->updateProgramStatus($programId, $status, $registrationOpen);
                    $response = ['success' => $result, 'message' => $result ? 'Status updated successfully' : 'Failed to update status'];
                    break;

                case 'update_registration_status':
                    $attendeeId = intval($_POST['attendee_id']);
                    $status = $_POST['status'];

                    $result = $this->updateRegistrationStatus($attendeeId, $status);
                    $response = ['success' => $result, 'message' => $result ? 'Registration status updated' : 'Failed to update registration'];
                    break;

                case 'update_mentor_status':
                    $attendeeId = intval($_POST['attendee_id']);
                    $status = $_POST['status'];

                    $result = $this->updateMentorStatus($attendeeId, $status);
                    $response = ['success' => $result, 'message' => $result ? 'Mentor status updated' : 'Failed to update mentor status'];
                    break;

                case 'assign_workshop':
                    $attendeeId = intval($_POST['attendee_id']);
                    $workshopId = intval($_POST['workshop_id']);

                    $result = $this->assignToWorkshop($attendeeId, $workshopId);
                    $response = ['success' => $result, 'message' => $result ? 'Workshop assigned successfully' : 'Failed to assign workshop'];
                    break;

                case 'get_attendee_details':
                    $attendeeId = intval($_POST['attendee_id']);
                    $details = $this->getAttendeeDetails($attendeeId);

                    $response = ['success' => true, 'data' => $details];
                    break;

                case 'send_bulk_email':
                    $programId = intval($_POST['program_id']);
                    $subject = $_POST['subject'];
                    $message = $_POST['message'];
                    $recipients = $_POST['recipients'] ?? 'all';

                    $result = $this->sendBulkEmail($programId, $subject, $message, $recipients);
                    $response = $result;
                    break;

                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
                    $this->logger->warning("Invalid AJAX action", [
                        'action' => $action
                    ]);
            }

        } catch (Exception $e) {
            $this->logger->error("AJAX request error", [
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * Export data to CSV format
     * Generates downloadable CSV file
     *
     * @param array $data Data to export
     * @return bool True on success
     */
    private function exportToCSV($data) {
        try {
            if (empty($data)) {
                $this->logger->warning("Attempted to export empty data to CSV");
                return false;
            }

            $filename = 'holiday_program_registrations_' . date('Y-m-d_H-i-s') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write CSV headers
            fputcsv($output, array_keys($data[0]));

            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }

            fclose($output);

            $this->logAction('csv_export', [
                'filename' => $filename,
                'rows' => count($data)
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->error("CSV export failed", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get program summary statistics
     * API method for dashboard widgets
     *
     * @param int $programId Program ID
     * @return void Sends JSON response
     */
    public function getProgramSummary($programId) {
        try {
            $summary = [
                'program' => $this->model->getProgramById($programId),
                'stats' => $this->model->getProgramStatistics($programId),
                'capacity' => $this->model->getCapacityInfo($programId),
                'recent_registrations' => $this->model->getRecentRegistrations($programId, 10)
            ];

            $this->logAction('get_program_summary', [
                'program_id' => $programId
            ]);

            $this->jsonResponse([
                'success' => true,
                'data' => $summary
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to get program summary", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load program summary'
            ], 500);
        }
    }
}
?>
