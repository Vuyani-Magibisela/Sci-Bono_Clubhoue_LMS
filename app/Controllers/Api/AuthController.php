<?php
/**
 * Api\AuthController
 *
 * Handles API authentication
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 */

class AuthController {

    public function login() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'message' => 'API login under migration', 'endpoint' => 'POST /api/v1/auth/login'], JSON_PRETTY_PRINT);
        exit;
    }

    public function logout() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/auth/logout'], JSON_PRETTY_PRINT);
        exit;
    }

    public function refresh() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/auth/refresh'], JSON_PRETTY_PRINT);
        exit;
    }

    public function forgotPassword() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/auth/forgot-password'], JSON_PRETTY_PRINT);
        exit;
    }

    public function resetPassword() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/auth/reset-password'], JSON_PRETTY_PRINT);
        exit;
    }
}
