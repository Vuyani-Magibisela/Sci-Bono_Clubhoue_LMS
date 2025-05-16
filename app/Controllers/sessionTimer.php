<?php
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