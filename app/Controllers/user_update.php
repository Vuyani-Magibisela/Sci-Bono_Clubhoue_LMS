<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Check if user has permission to perform updates
if (!in_array($_SESSION['user_type'], ['admin', 'mentor'])) {
    header("Location: ../../home.php");
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./user_list.php");
    exit;
}

// Include database connection
require_once '../../server.php';

// Include controller
require_once __DIR__ . '/UserController.php';

// Initialize controller
$userController = new UserController($conn);

// Get the user being edited
$editing_user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$user = $userController->getUserById($editing_user_id);

// Check if user exists
if (!$user) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit;
}

// Check permissions
$can_edit = $userController->hasEditPermission($_SESSION['user_type'], $_SESSION['id'], $user);

if (!$can_edit) {
    $_SESSION['message'] = "You don't have permission to update this user.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit;
}

// Process the update
$success = $userController->updateUser($_POST);

if ($success) {
    $_SESSION['message'] = "User updated successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to update user.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to user list
header("Location: ./user_list.php");
exit;
?>