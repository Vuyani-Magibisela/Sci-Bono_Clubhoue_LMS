<?php
/**
 * Api\UserController
 *
 * Handles API user operations
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 */

class UserController {

    public function profile() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'endpoint' => 'GET /api/v1/profile',
            'user_id' => $_SESSION['user_id'] ?? null
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function updateProfile() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'endpoint' => 'PUT /api/v1/profile'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
