<?php
/**
 * ⚠️ DEPRECATED - This file is deprecated as of Phase 4 Week 3 Day 4
 *
 * This procedural email sending script has been deprecated in favor of the modernized
 * HolidayProgramEmailController which extends BaseController.
 *
 * Migration Path:
 * - Use: HolidayProgramEmailController->sendProfileAccessEmail($attendeeId) for single emails
 * - Use: HolidayProgramEmailController->sendBulkProfileAccessEmails($attendeeIds) for bulk emails
 *
 * This file is kept for backward compatibility only and will be removed
 * in a future release. Please update your code to use the new controller.
 *
 * @deprecated Phase 4 Week 3 Day 4
 * @see HolidayProgramEmailController
 */

// Log deprecation warning
if (function_exists('error_log')) {
    error_log(
        '[DEPRECATED] send-profile-email.php is deprecated. ' .
        'Use HolidayProgramEmailController instead. ' .
        'Called from: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
        ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    );
}

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
