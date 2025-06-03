<?php
/**
 * Attendance Routes - Entry point for attendance-related requests
 * 
 * This file handles routing for attendance operations and serves as the main entry point.
 * It instantiates the AttendanceController and routes requests to appropriate methods.
 * 
 * @package Controllers
 * @author Sci-Bono Clubhouse LMS
 * @version 1.0
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Controllers/AttendanceController.php';

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Initialize the controller
    $attendanceController = new AttendanceController($conn);
    
    // Get the action from request
    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
    
    // Route the request to appropriate controller method
    switch ($action) {
        case 'index':
        case 'show':
        case '':
            // Show the main attendance page
            $attendanceController->showAttendancePage();
            break;
            
        case 'signin':
            // Handle sign-in request
            $attendanceController->handleSignIn();
            break;
            
        case 'signout':
            // Handle sign-out request
            $attendanceController->handleSignOut();
            break;
            
        case 'search':
            // Handle user search
            $attendanceController->handleUserSearch();
            break;
            
        case 'stats':
            // Get attendance statistics
            $attendanceController->getAttendanceStats();
            break;
            
        case 'activities':
            // Get recent activities
            $attendanceController->getRecentActivities();
            break;
            
        case 'bulk_signout':
            // Handle bulk sign-out (admin only)
            $attendanceController->handleBulkSignOut();
            break;
            
        default:
            // Invalid action
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action specified',
                    'code' => 'INVALID_ACTION'
                ]);
            } else {
                http_response_code(404);
                echo "404 - Page not found";
            }
            break;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Attendance Route Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Return appropriate error response
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error occurred',
            'code' => 'SYSTEM_ERROR'
        ]);
    } else {
        http_response_code(500);
        echo "500 - Internal Server Error";
    }
} finally {
    // Close database connection if it exists
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>