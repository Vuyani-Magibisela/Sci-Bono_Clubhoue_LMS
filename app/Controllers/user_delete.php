<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Check if user has admin permission
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../../home.php");
    exit;
}

// Include database connection
require_once '../../server.php';

// Include controller
require_once __DIR__ . '/UserController.php';

// Initialize controller
$userController = new UserController($conn);

// Get the user ID from the URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get user data to check if it exists
$user = $userController->getUserById($user_id);

// Check if user exists
if (!$user) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit;
}

// Prevent deleting yourself
if ($user_id === $_SESSION['id']) {
    $_SESSION['message'] = "You cannot delete your own account.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit;
}

// Validate CSRF token
require_once '../../core/CSRF.php';
if (!CSRF::validateToken()) {
    error_log("CSRF validation failed in user_delete.php - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", User ID: " . $user_id);
    $_SESSION['message'] = "Security validation failed. Please try again.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit();
}

// Process the deletion
$success = $userController->deleteUser($user_id);

if ($success) {
    $_SESSION['message'] = "User deleted successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to delete user.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to user list
header("Location: ./user_list.php");
exit;
?>