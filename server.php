<?php
// Database credentials
$host = "localhost";
$user = "vuksDev";
$password = "Vu13#k*s3D3V";
$dbname = "accounts";

// Connect to MySQL database
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//"Connected successfully to database.";
