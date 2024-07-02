<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'server.php';

// Check connection
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $username = $_POST['username'];
    $userType = $_POST['user_type'];
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare the SQL statement
    $sql = "INSERT INTO users (name, surname, username, user_type, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Error in prepare statement: " . mysqli_error($conn));
    }

    // Bind the parameters to the prepared statement
    if (!mysqli_stmt_bind_param($stmt, 'sssss', $name, $surname, $username, $userType, $hashed_password)) {
        die("Error binding parameters: " . mysqli_stmt_error($stmt));
    }

    // Execute the prepared statement
    if (!mysqli_stmt_execute($stmt)) {
        die("Error executing statement: " . mysqli_stmt_error($stmt));
    }

    // Check if any rows were affected
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    if ($affected_rows == 1) {
        // New user created successfully, start session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Location: home.php");
        exit();
    } else {
        // No rows affected, which is unexpected
        die("Insert statement did not affect any rows. Affected rows: " . $affected_rows);
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
