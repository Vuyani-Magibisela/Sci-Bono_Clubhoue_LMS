<?php
ob_start();
//Check if the session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection details
require '__DIR__ . ../../server.php';

// Set the inactivity timeout in seconds (e.g., 15 minutes)
$inactivityTimeout = 600; // 10 minutes

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

        // Redirect the user to the login page with a message
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Update the last activity timestamp
$_SESSION['last_activity'] = time();
// End output buffering
ob_end_flush();