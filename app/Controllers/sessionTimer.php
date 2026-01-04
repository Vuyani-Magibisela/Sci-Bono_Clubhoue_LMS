<?php
/**
 * ⚠️ DEPRECATED - This file is deprecated as of Phase 4 Week 3 Day 4
 *
 * This procedural session timeout handler should be converted to middleware.
 *
 * Migration Path:
 * - Recommended: Implement as middleware class in app/Middleware/SessionTimeout.php
 * - Alternative: Integrate session timeout logic into BaseController
 * - Use modern session management with:
 *   - Session configuration in config files
 *   - Middleware stack for session handling
 *   - Event-driven session timeout notifications
 *
 * This file is kept for backward compatibility only and will be removed
 * in a future release. Please migrate to a proper middleware implementation.
 *
 * @deprecated Phase 4 Week 3 Day 4
 * @see Future: app/Middleware/SessionTimeout.php (to be created)
 */

// Log deprecation warning
if (function_exists('error_log')) {
    error_log(
        '[DEPRECATED] sessionTimer.php is deprecated. ' .
        'Migrate to middleware-based session management. ' .
        'Called from: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
        ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    );
}

ob_start();
// Check if the session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection details
require __DIR__ . '/../../server.php';

// Set the inactivity timeout in seconds
$inactivityTimeout = 900; // 15 minutes

// Check if the last activity time is set in the session
if (isset($_SESSION['last_activity'])) {
    // Calculate the time since the last activity
    $timeSinceLastActivity = time() - $_SESSION['last_activity'];

    // If the time since the last activity exceeds the timeout, log the user out
    if ($timeSinceLastActivity > $inactivityTimeout) {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        // Dynamically determine the path to login.php
        $loginPath = '/Sci-Bono_Clubhoue_LMS/login.php'; // Adjust this to match your root path

        // Redirect the user to the login page with a timeout message
        header('Location: ' . $loginPath . '?timeout=1');
        exit;
    }
}

// Update the last activity timestamp
$_SESSION['last_activity'] = time();
// End output buffering
ob_end_flush();
