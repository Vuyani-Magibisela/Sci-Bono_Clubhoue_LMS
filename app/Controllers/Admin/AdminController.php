<?php
/**
 * Admin\AdminController
 *
 * Handles admin dashboard and main admin functions
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs implementation
 */

require_once __DIR__ . '/../BaseController.php';

class AdminController extends BaseController {

    /**
     * Display the admin dashboard
     *
     * Route: GET /admin
     * Name: admin.dashboard
     * Middleware: AuthMiddleware, RoleMiddleware:admin
     */
    public function dashboard() {
        // Check authentication and admin role
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo 'Access Denied - Admin Only';
            exit;
        }
        
        // Return stub response
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Admin dashboard under migration',
            'controller' => 'Admin\AdminController',
            'method' => 'dashboard',
            'todo' => 'Migrate from admin dashboard views'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
