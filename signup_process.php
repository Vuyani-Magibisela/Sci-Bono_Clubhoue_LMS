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
    // Get the form data with validation
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $surname = isset($_POST['surname']) ? $_POST['surname'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';  // Changed from $userType
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : 'member';
    $center = isset($_POST['center']) ? $_POST['center'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate required fields
    if (empty($name) || empty($surname) || empty($gender) || empty($username) || empty($center) || empty($password)) {
        die("All fields are required");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    // Prepare the SQL statement
    $sql = "INSERT INTO users (name, surname, Gender, username, user_type, Center, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Error in prepare statement: " . mysqli_error($conn));
    }

    // Bind the parameters to the prepared statement
    if (!mysqli_stmt_bind_param($stmt, 'sssssss', $name, $surname, $gender, $username, $userType, $center, $hashed_password)) {
        mysqli_stmt_close($stmt);
        die("Error binding parameters: " . mysqli_stmt_error($stmt));
    }

    // Execute the prepared statement
    if (!mysqli_stmt_execute($stmt)) {
        $error = "Error executing statement: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die($error);
    }

    // Check if any rows were affected
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    // Close the statement
    mysqli_stmt_close($stmt);

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

}
