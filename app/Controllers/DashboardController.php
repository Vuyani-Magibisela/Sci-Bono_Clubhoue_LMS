<?php
/**
 * DashboardController
 *
 * Handles user dashboard
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from legacy home.php/dashboard views
 */

require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {

    /**
     * Display the user dashboard
     *
     * Route: GET /dashboard
     * Name: dashboard
     * Middleware: AuthMiddleware
     */
    public function index() {
        // TODO: Migrate functionality from /home.php and dashboard views
        
        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        $userType = $_SESSION['user_type'] ?? 'member';
        $userName = $_SESSION['name'] ?? 'User';
        
        // Return stub response
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Dashboard under migration to new routing system',
            'controller' => 'DashboardController',
            'method' => 'index',
            'user_type' => $userType,
            'user_name' => $userName,
            'todo' => 'Migrate from /home.php and /app/Views/home/* views'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
