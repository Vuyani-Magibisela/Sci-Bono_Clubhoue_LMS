<?php
// Database credentials
$host = "localhost";
$user = "root";
$password = "";
$dbname = "accounts";

// Connect to MySQL database
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

"Connected successfully to database.";

?>
