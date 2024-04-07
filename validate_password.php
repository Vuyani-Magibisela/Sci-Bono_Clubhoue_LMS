<?php
require 'server.php';

// Retrieve user ID and password from the AJAX request
$userId = $_POST['userId'];
$password = $_POST['password'];

// Validate password against the stored password in the users table
$sql = "SELECT * FROM users WHERE id = $userId AND password = '$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
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

// Close the database connection
mysqli_close($conn);

