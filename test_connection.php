<?php
/**
 * Quick database connection and user password test
 * Place this file in your root directory and access via browser
 */

// Include your database connection
require_once 'server.php';

// Test user ID (change this to the ID you're trying to sign in with)
$testUserId = 1;

echo "<h2>Database Connection Test</h2>";

// Test 1: Database connection
if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Database connected successfully<br>";
}

// Test 2: Check if user exists
$sql = "SELECT id, username, name, surname, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $testUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User found: " . $user['username'] . " (" . $user['name'] . " " . $user['surname'] . ")<br>";
    echo "Password stored: " . (strlen($user['password']) > 0 ? "Yes (length: " . strlen($user['password']) . ")" : "No password set") . "<br>";
    
    // Test 3: Password verification tests
    echo "<h3>Password Tests for User ID: $testUserId</h3>";
    
    $testPasswords = [
        (string)$testUserId,  // User ID as password
        'test123',            // Common test password
        'clubhouse',          // System password
        $user['username'],    // Username as password
        '123456'              // Simple password
    ];
    
    foreach ($testPasswords as $testPass) {
        echo "Testing password '$testPass': ";
        
        if (!empty($user['password'])) {
            // Test hashed password
            if (password_verify($testPass, $user['password'])) {
                echo "✅ VALID (hashed)<br>";
                continue;
            }
            
            // Test plain text password
            if ($user['password'] === $testPass) {
                echo "✅ VALID (plain text)<br>";
                continue;
            }
        }
        
        echo "❌ Invalid<br>";
    }
    
} else {
    echo "❌ User with ID $testUserId not found<br>";
}

// Test 4: Check attendance table
$attendanceSql = "SELECT COUNT(*) as count FROM attendance WHERE user_id = ?";
$attendanceStmt = $conn->prepare($attendanceSql);
$attendanceStmt->bind_param("i", $testUserId);
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();
$attendanceRow = $attendanceResult->fetch_assoc();
echo "Attendance records for user: " . $attendanceRow['count'] . "<br>";

// Test 5: Check if activity_log table exists
$tableCheckSql = "SHOW TABLES LIKE 'activity_log'";
$tableResult = $conn->query($tableCheckSql);
if ($tableResult->num_rows > 0) {
    echo "✅ Activity log table exists<br>";
} else {
    echo "⚠️ Activity log table missing - this might cause issues<br>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>