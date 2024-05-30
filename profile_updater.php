<?php
session_start();
require 'server.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$editUserId = isset($_GET['id']) && $userType === 'admin' ? $_GET['id'] : $userId;

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $editUserId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    echo "User not found!";
    exit;
}

$errorMsg = "";
$successMsg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : null;
    $user_type = $userType === 'admin' ? trim($_POST['user_type']) : $userData['user_type'];

    // Update user details
    $sql = "UPDATE users SET username = ?, email = ?, user_type = ?";
    if ($password) {
        $sql .= ", password = ?";
    }
    $sql .= " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("ssssi", $username, $email, $user_type, $hashedPassword, $editUserId);
    } else {
        $stmt->bind_param("sssi", $username, $email, $user_type, $editUserId);
    }

    if ($stmt->execute()) {
        $successMsg = "Profile updated successfully!";
    } else {
        $errorMsg = "Error updating profile: " . $stmt->error;
    }
}

