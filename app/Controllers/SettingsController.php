<?php
/**
 * SettingsController
 *
 * Handles user settings
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from /app/Views/settings.php
 */

require_once __DIR__ . '/BaseController.php';

class SettingsController extends BaseController {

    /**
     * Display user settings page
     *
     * Route: GET /settings
     * Name: settings.index
     * Middleware: AuthMiddleware
     */
    public function index() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Settings page under migration',
            'controller' => 'SettingsController',
            'method' => 'index',
            'todo' => 'Migrate from /app/Views/settings.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Update user settings
     *
     * Route: POST /settings
     * Name: settings.update
     * Middleware: AuthMiddleware
     */
    public function update() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Settings update under migration',
            'controller' => 'SettingsController',
            'method' => 'update'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
