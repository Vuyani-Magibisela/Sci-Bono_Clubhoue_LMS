<?php
require 'server.php';

// Debugging: Log received POST data
error_log('Received POST data: ' . print_r($_POST, true));

// Retrieve user ID and password from the AJAX request
$userId = $_POST['userId'];
$password = $_POST['password'];
echo("User ID ".$userId );
echo("User Password ".$password);

// Debugging: Log received user ID and password
error_log('Received user ID: ' . $userId);
error_log('Received password: ' . $password);

// Validate user ID (you might want to add additional validation)
$userId = (int) $userId;

// Debugging - log user ID and password
// console_log($userId);
// console_log($password);

// Validate password against the hashed password stored in the users table
$sql = "SELECT password FROM users WHERE id = $userId"; // Retrieve only the hashed password
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    // Fetch the hashed password from the database
    $row = mysqli_fetch_assoc($result);
    $hashedPasswordFromDB = $row['password'];

    // Verify the entered password against the hashed password from the database
    if (password_verify($password, $hashedPasswordFromDB)) {
        // Password is valid, proceed with marking the user as checked in or checked out
        // You can implement this part based on the context of the request (sign-in or sign-out)

        // Example for marking user as checked in:
        $checkInSql = "INSERT INTO attendance (user_id, check_in_time) VALUES ($userId, NOW())";
        mysqli_query($conn, $checkInSql);

        // Example for marking user as checked out:
        // $checkOutSql = "UPDATE attendance SET check_out_time = NOW() WHERE user_id = $userId AND DATE(check_in_time) = CURDATE()";
        // mysqli_query($conn, $checkOutSql);

        // Send response indicating success
        echo "valid";
    } else {
        // Invalid password
        echo "invalid";
    }
} else {
    // User ID not found or error retrieving password
    echo "error";
}

// Close the database connection
mysqli_close($conn);