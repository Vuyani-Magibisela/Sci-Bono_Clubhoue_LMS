<?php
/**
 * Mentor\ReportController
 *
 * Handles mentor reports
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 */

require_once __DIR__ . '/../BaseController.php';

class ReportController extends BaseController {

    private function checkMentorAuth() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        $userType = $_SESSION['user_type'] ?? '';
        if (!in_array($userType, ['mentor', 'admin'])) {
            http_response_code(403);
            echo 'Access Denied';
            exit;
        }
    }

    public function index() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'controller' => 'Mentor\ReportController', 'method' => 'index'], JSON_PRETTY_PRINT);
        exit;
    }

    public function attendance() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'controller' => 'Mentor\ReportController', 'method' => 'attendance'], JSON_PRETTY_PRINT);
        exit;
    }

    public function programs() {
        $this->checkMentorAuth();
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'controller' => 'Mentor\ReportController', 'method' => 'programs'], JSON_PRETTY_PRINT);
        exit;
    }
}
