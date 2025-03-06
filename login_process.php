<?php
session_start();
require 'server.php';

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the user exists in the database
    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = mysqli_prepare($conn, $sql);
    
    // Check if prepare statement was successful
    if ($stmt === false) {
        die('Error in prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            // Set session variables and redirect to home page if the password is correct
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['user_id'] = $row['id']; 

            header("Location: home.php");
            exit;
        } else {
            // Password is incorrect, redirect to login page with error message
            $_SESSION['login_error'] = "Incorrect password";
            header("Location: login.php");
            exit;
        }
    } else {
        // Username does not exist, redirect to login page with error message
        $_SESSION['login_error'] = "Username not found";
        header("Location: login.php");
        exit;
    }
}

mysqli_close($conn);
?>