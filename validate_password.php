<?php
require 'server.php';
// Retrieve user ID and password from the AJAX request
$userId = $_POST['userId'];

//deburging
// echo("User ID ".$userId );
// echo("User Password ".$password);

if (isset($_POST['password'])) {
    $password = $_POST['password'];
    // Validate the password...
} else {
    // Handle the case where 'password' key is not present
    echo "Error: Password parameter is missing.";
}


// Debugging: Log received POST data
error_log('Received POST data: ' . print_r($_POST, true));



// Debugging: Log received user ID and password
error_log('Received user ID: ' . $userId);
error_log('Received password: ' . $password);

// Validate user ID (you might want to add additional validation)
$userId = (int) $userId;

// Debugging - log user ID and password
// console_log($userId);
// console_log($password);


//signout
if (isset($_POST['action']) && $_POST['action'] === "signOut") {
    // Retrieve user ID from the POST data
    $userId = $_POST['userId'];
    // Perform sign-out process (if any)
    // For example, you can update the checked_out column in the attendance table
    
    // Example:
    $checkOutSql = "UPDATE attendance SET checked_out = NOW(), sign_in_status = 'signedOut' WHERE user_id = $userId AND DATE(checked_in) = CURDATE()";
    if (mysqli_query($conn, $checkOutSql)) {
        // Sign-out successful
        echo "success";
    } else {
        // Sign-out failed
        echo "error";
    }
} else {
    // Handle other actions (e.g., sign-in) - Sign In
    // Validate password against the hashed password stored in the users table
    $password = $_POST['password'];
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
            $checkInSql = "INSERT INTO attendance (user_id, checked_in, sign_in_status) VALUES ($userId, NOW(), 'signedIn')";
            mysqli_query($conn, $checkInSql);

            // Example for marking user as checked out:
        // $checkOutSql = "UPDATE attendance SET checked_out = NOW() WHERE user_id = $userId AND DATE(checked_in) = CURDATE()";
            //mysqli_query($conn, $checkOutSql);

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
}



// Close the database connection
mysqli_close($conn);