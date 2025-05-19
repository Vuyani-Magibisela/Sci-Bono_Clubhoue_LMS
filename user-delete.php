<?php
// ====================================================================
// ENTRY POINT: User Deletion
// ====================================================================
// This file serves as a public endpoint that:
// 1. Handles the initial HTTP request
// 2. Checks permissions
// 3. Delegates to the UserController
// 4. Redirects to the appropriate view
// ====================================================================

// Start session for authentication
session_start();

// Include core dependencies
require_once 'server.php';  // Database connection
require_once 'app/Controllers/UserController.php';  // Controller

// Authentication & authorization check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] != 'admin') {
    $_SESSION['message'] = "You do not have permission to delete users.";
    $_SESSION['message_type'] = "danger";
    header("Location: home.php");
    exit;
}

// Instantiate the controller
$userController = new UserController($conn);

// Input validation
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId <= 0) {
    $_SESSION['message'] = "Invalid user ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: app/Views/user_list.php");  // Redirect to view
    exit;
}

// Business rule: Prevent deletion of own account
if ($userId == $_SESSION['id']) {
    $_SESSION['message'] = "You cannot delete your own account.";
    $_SESSION['message_type'] = "danger";
    header("Location: app/Views/user_list.php");  // Redirect to view
    exit;
}

// Delegate to controller for business logic
$result = $userController->deleteUser($userId);

// Handle result and prepare for view
if ($result) {
    $_SESSION['message'] = "User has been successfully deleted.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting user.";
    $_SESSION['message_type'] = "danger";
}

// Redirect to the appropriate view
header("Location: app/Views/user_list.php");
exit;
?>