<?php
/**
 * Attendance API Routes - Handle AJAX requests for attendance system
 */

// Enable CORS for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include required files
require_once __DIR__ . '/../../server.php'; // Database connection
require_once __DIR__ . '/AttendanceController.php';

try {
    // Initialize the attendance controller
    $attendanceController = new AttendanceController($conn);
    
    // Get the action from URL parameter
    $action = $_GET['action'] ?? '';
    
    // Route the request to appropriate method
    switch ($action) {
        case 'signin':
            $attendanceController->handleSignIn();
            break;
            
        case 'signout':
            $attendanceController->handleSignOut();
            break;
            
        case 'search':
            // Handle search functionality if needed
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Search not implemented yet']);
            break;
            
        case 'stats':
            // Handle statistics request if needed
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Stats not implemented yet']);
            break;
            
        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action. Supported actions: signin, signout, search, stats',
                'provided_action' => $action
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in attendance_routes.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred. Please try again.',
        'error_code' => 'ROUTE_ERROR'
    ]);
}