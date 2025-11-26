<?php
/**
 * Mentor\AttendanceController
 *
 * Handles mentor attendance management
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs implementation
 */

require_once __DIR__ . '/../BaseController.php';

class AttendanceController extends BaseController {

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

    public function index() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'message' => 'Mentor attendance list under migration', 'controller' => 'Mentor\AttendanceController', 'method' => 'index'], JSON_PRETTY_PRINT);
        exit;
    }

    public function register() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'message' => 'Attendance register under migration', 'controller' => 'Mentor\AttendanceController', 'method' => 'register'], JSON_PRETTY_PRINT);
        exit;
    }

    public function bulkSignout() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'message' => 'Bulk signout under migration', 'controller' => 'Mentor\AttendanceController', 'method' => 'bulkSignout'], JSON_PRETTY_PRINT);
        exit;
    }
}
