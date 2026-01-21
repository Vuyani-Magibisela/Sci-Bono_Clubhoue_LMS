<?php
// Error Display Script - DELETE AFTER DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 PHP Error Debug Tool</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

// Test if the main files exist and are readable
$files_to_check = [
    '../../../server.php',
    '../../Controllers/HolidayProgramCreationController.php',
    '../../Models/HolidayProgramCreationModel.php'
];

echo "<h3>File Existence Check:</h3>";
foreach ($files_to_check as $file) {
    $full_path = realpath($file);
    echo "<p>";
    echo "<strong>$file:</strong> ";
    if (file_exists($file)) {
        echo "✅ EXISTS";
        if (is_readable($file)) {
            echo " & READABLE";
        } else {
            echo " ❌ NOT READABLE";
        }
        echo " (Path: $full_path)";
    } else {
        echo "❌ NOT FOUND";
    }
    echo "</p>";
}

echo "<h3>Testing Database Connection:</h3>";
try {
    require_once '../../../server.php';
    echo "✅ Database connection successful<br>";
    echo "Database: " . $conn->server_info . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>Testing Controller:</h3>";
try {
    require_once '../../Controllers/HolidayProgramCreationController.php';
    echo "✅ Controller file loaded successfully<br>";
    
    $controller = new HolidayProgramCreationController($conn);
    echo "✅ Controller instantiated successfully<br>";
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
}

echo "<h3>Testing Session:</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Logged in: " . (isset($_SESSION['loggedin']) ? 'YES' : 'NO') . "<br>";
echo "User type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "<br>";

echo "<h3>PHP Environment:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Check if we can access the actual form
echo "<h3>Testing Form Access:</h3>";
echo '<a href="holidayProgramCreationForm.php" target="_blank">🔗 Try accessing the form</a><br>';

echo "<hr>";
echo "<p><strong>Remember to delete this file when done debugging!</strong></p>";
?>