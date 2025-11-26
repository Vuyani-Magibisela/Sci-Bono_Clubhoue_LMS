<?php
session_start();
require_once '../../server.php';
require_once 'HolidayProgramEmailController.php';

// Check if user is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendee_id'])) {
    // Validate CSRF token
    require_once __DIR__ . '/../../core/CSRF.php';
    if (!CSRF::validateToken()) {
        error_log("CSRF validation failed in send-profile-email.php - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Security validation failed. Please refresh the page and try again.',
            'code' => 'CSRF_ERROR'
        ]);
        exit();
    }

    $attendeeId = intval($_POST['attendee_id']);

    $emailController = new HolidayProgramEmailController($conn);
    $result = $emailController->sendProfileAccessEmail($attendeeId);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>