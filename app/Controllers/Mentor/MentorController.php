<?php
/**
 * Mentor\MentorController
 *
 * Handles mentor dashboard and main mentor functions
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs implementation
 */

require_once __DIR__ . '/../BaseController.php';

class MentorController extends BaseController {

    /**
     * Display the mentor dashboard
     *
     * Route: GET /mentor
     * Name: mentor.dashboard
     * Middleware: AuthMiddleware, RoleMiddleware:mentor,admin
     */
    public function dashboard() {
        // Check authentication and mentor/admin role
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
        
        // Return stub response
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Mentor dashboard under migration',
            'controller' => 'Mentor\MentorController',
            'method' => 'dashboard',
            'todo' => 'Migrate from mentor dashboard views'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
