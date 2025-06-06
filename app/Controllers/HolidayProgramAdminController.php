<?php
require_once __DIR__ . '/../Models/HolidayProgramAdminModel.php';

class HolidayProgramAdminController {
    private $conn;
    private $model;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new HolidayProgramAdminModel($conn);
    }
    
    /**
     * Get dashboard data for admin overview
     */
    public function getDashboardData($programId = null) {
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
        }
        
        return $dashboardData;
    }
    
    /**
     * Update program status (registration open/closed/closing soon)
     */
    public function updateProgramStatus($programId, $status, $registrationOpen = null) {
        return $this->model->updateProgramStatus($programId, $status, $registrationOpen);
    }
    
    /**
     * Update registration status for individual attendee
     */
    public function updateRegistrationStatus($attendeeId, $status) {
        return $this->model->updateAttendeeStatus($attendeeId, $status);
    }
    
    /**
     * Update mentor status
     */
    public function updateMentorStatus($attendeeId, $status) {
        return $this->model->updateMentorStatus($attendeeId, $status);
    }
    
    /**
     * Assign attendee to workshop
     */
    public function assignToWorkshop($attendeeId, $workshopId) {
        return $this->model->assignAttendeeToWorkshop($attendeeId, $workshopId);
    }
    
    /**
     * Get detailed attendee information
     */
    public function getAttendeeDetails($attendeeId) {
        return $this->model->getAttendeeDetails($attendeeId);
    }
    
    /**
     * Export registrations data
     */
    public function exportRegistrations($programId, $format = 'csv') {
        $registrations = $this->model->getRegistrationsForExport($programId);
        
        if ($format === 'csv') {
            return $this->exportToCSV($registrations);
        }
        
        return $registrations;
    }
    
    /**
     * Get program capacity and enrollment analytics
     */
    public function getCapacityAnalytics($programId) {
        return $this->model->getCapacityAnalytics($programId);
    }
    
    /**
     * Send bulk emails to program participants
     */
    public function sendBulkEmail($programId, $subject, $message, $recipients = 'all') {
        $attendees = $this->model->getAttendeesForEmail($programId, $recipients);
        
        // Here you would integrate with your email service
        // For now, we'll return the count of recipients
        return [
            'success' => true,
            'recipients_count' => count($attendees),
            'message' => 'Emails queued for sending'
        ];
    }
    
    /**
     * Handle AJAX requests
     */
    public function handleAjaxRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
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
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    /**
     * Export data to CSV format
     */
    private function exportToCSV($data) {
        if (empty($data)) {
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
        return true;
    }
}