<?php
/**
 * Api\HealthController
 *
 * Handles API health checks
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: FUNCTIONAL - Basic health check implemented
 */

require_once __DIR__ . '/../../../core/Database.php';

class HealthController {

    /**
     * API Health Check
     *
     * Route: GET /api/v1/health
     * Name: api.health
     * Middleware: ApiMiddleware
     */
    public function check() {
        global $conn;

        $status = 'ok';
        $checks = [];

        // Check database connection
        try {
            $checks['database'] = ($conn && $conn->ping()) ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            $checks['database'] = 'error';
            $status = 'degraded';
        }

        // Check PHP version
        $checks['php_version'] = PHP_VERSION;

        // Check session
        $checks['session'] = (session_status() === PHP_SESSION_ACTIVE) ? 'active' : 'inactive';

        // Return JSON response
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'status' => $status,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'environment' => 'development',
            'checks' => $checks
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
