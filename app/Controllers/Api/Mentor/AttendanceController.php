<?php
/**
 * Api\Mentor\AttendanceController - API Mentor Attendance
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 */

class AttendanceController {
    private function checkMentor() {
        $userType = $_SESSION['user_type'] ?? '';
        if (!in_array($userType, ['mentor', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Mentor/Admin access required']);
            exit;
        }
    }
    
    public function recent() { $this->checkMentor(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'GET /api/v1/mentor/attendance/recent']); exit; }
    public function bulkSignout() { $this->checkMentor(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/mentor/attendance/bulk-signout']); exit; }
}
