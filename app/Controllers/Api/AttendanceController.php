<?php
/**
 * Api\AttendanceController
 *
 * Handles API attendance operations
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from /app/Controllers/attendance_routes.php
 */

class AttendanceController {

    public function signin() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Attendance signin under migration',
            'endpoint' => 'POST /api/v1/attendance/signin',
            'todo' => 'Migrate from /app/Controllers/attendance_routes.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function signout() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'endpoint' => 'POST /api/v1/attendance/signout',
            'todo' => 'Migrate from /app/Controllers/attendance_routes.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function searchUsers() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'endpoint' => 'GET /api/v1/attendance/search'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function stats() {
        header('Content-Type: application/json');
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'endpoint' => 'GET /api/v1/attendance/stats'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
