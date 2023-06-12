<?php
	session_start();
?>

<?php
require 'server.php';

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

    // Bind the parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $surname, $username, $userType, $hashed_password);

    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // New user created successfully, start session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Location: home.php");
        exit();
    } else {
        // Error creating new user, redirect to sign up page with error message
        $_SESSION['signup_error'] = "Error creating new user";
        header("Location: signup.php");
        exit();
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
