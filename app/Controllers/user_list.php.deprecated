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

// Get all users based on permission level
$users = $userController->getAllUsers($_SESSION['user_type']);

// Load the view
require_once __DIR__ . '../Views/user_list.php';
?>