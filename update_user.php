<?php
session_start();
require 'server.php';

// Check if the user is an admin
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: home.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $userType = $_POST['user_type'];
    $password = $_POST['password'];

    // Prepare an SQL statement
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, email = ?, user_type = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $email, $userType, $hashedPassword, $userId);
    } else {
        $sql = "UPDATE users SET username = ?, email = ?, user_type = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $email, $userType, $userId);
    }

    // Execute the statement
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating user!";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect to the user list page
    header('Location: user_list.php');
    exit;
}

