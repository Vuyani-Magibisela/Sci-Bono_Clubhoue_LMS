<?php
	session_start();
?>

<?php
require 'server.php';

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the user exists in the database
    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            // Redirect to home page if the password is correct
            // Start session
            session_start();
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $row['user_type']; // Store the user_type in session
            $_SESSION['user_id'] = $row['id']; 

            header("Location: home.php");
            exit;
        } else {
            // Password is incorrect, redirect to login page with error message
            session_start();
            $_SESSION['login_error'] = "Incorrect password";
            header("Location: login.php");
            exit;
        }
    } else {
        // Username does not exist, redirect to login page with error message
        session_start();
        $_SESSION['login_error'] = "Username not found";
        header("Location: login.php");
        exit;
    }
}



mysqli_close($conn);
?>

