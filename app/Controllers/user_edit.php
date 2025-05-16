<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Check if user has permission to view this page
if (!in_array($_SESSION['user_type'], ['admin', 'mentor'])) {
    header("Location: ../../home.php");
    exit;
}

// Include database connection
require_once '../../server.php';

// Include controller
require_once __DIR__ . '/UserController.php';

// Initialize controller
$userController = new UserController($conn);

// Get the ID of the user being edited
$editing_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get user data
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
    $_SESSION['message'] = "You don't have permission to edit this user.";
    $_SESSION['message_type'] = "danger";
    header("Location: ./user_list.php");
    exit;
}

// Load the view
require_once __DIR__ . '/../Views/admin/user_edit.php';
?>