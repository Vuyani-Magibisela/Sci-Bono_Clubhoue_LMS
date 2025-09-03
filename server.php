<?php
// Load new configuration system
require_once __DIR__ . '/config/ConfigLoader.php';

try {
    // Use new configuration system
    $config = ConfigLoader::get('database.connections.mysql');
    
    $host = $config['host'];
    $user = $config['username'];
    $password = $config['password'];
    $dbname = $config['database'];
} catch (Exception $e) {
    // Fallback to hardcoded values during transition
    $host = "localhost";
    $user = "vuksDev";
    $password = "Vu13#k*s3D3V";
    $dbname = "accounts";
    
    error_log("Configuration system not available, using fallback values: " . $e->getMessage());
}

// Connect to MySQL database
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//"Connected successfully to database.";
