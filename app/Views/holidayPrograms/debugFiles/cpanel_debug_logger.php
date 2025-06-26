<?php
// cPanel Debug Logger - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 cPanel Server Debug Tool</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    
    echo "<h3>📋 Server Environment Check:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Document Root</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
    echo "<tr><td>Current Directory</td><td>" . getcwd() . "</td></tr>";
    echo "<tr><td>Error Log Location</td><td>" . ini_get('error_log') . "</td></tr>";
    echo "<tr><td>Display Errors</td><td>" . (ini_get('display_errors') ? 'On' : 'Off') . "</td></tr>";
    echo "</table>";
    
    // Check if we can create a custom error log
    echo "<h3>📝 Custom Error Log Test:</h3>";
    $log_file = __DIR__ . '/debug_errors.log';
    
    if (is_writable(__DIR__)) {
        error_log("Test log entry from debug script - " . date('Y-m-d H:i:s'), 3, $log_file);
        if (file_exists($log_file)) {
            echo "<p>✅ Custom log file created: <a href='debug_errors.log' target='_blank'>debug_errors.log</a></p>";
        } else {
            echo "<p>❌ Failed to create custom log file</p>";
        }
    } else {
        echo "<p>❌ Directory not writable for custom logs</p>";
    }
    
    // Test database connection
    echo "<h3>🗄️ Database Connection Test:</h3>";
    echo "<p>✅ Database connected successfully</p>";
    echo "<p>Database Version: " . $conn->server_info . "</p>";
    
    // Check for workshops for program 2
    echo "<h3>🎯 Workshop Data for Program 2:</h3>";
    $workshop_sql = "SELECT id, title, max_participants FROM holiday_program_workshops WHERE program_id = 2";
    $result = $conn->query($workshop_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Max Participants</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>{$row['max_participants']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No workshops found for program 2</p>";
    }
    
    // Check if files are accessible
    echo "<h3>📁 File Access Check:</h3>";
    $files_to_check = [
        'holidayProgramRegistration.php' => 'Registration Form',
        '../../../server.php' => 'Database Connection',
        '../../Models/holiday-program-functions.php' => 'Holiday Functions'
    ];
    
    foreach ($files_to_check as $file => $description) {
        if (file_exists($file)) {
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "<p>✅ $description: Exists (Size: $size bytes, Modified: $modified)</p>";
        } else {
            echo "<p>❌ $description: Not found ($file)</p>";
        }
    }
    
    // Test mentor registration simulation
    echo "<h3>🧪 Mentor Registration Simulation:</h3>";
    
    // Simulate what happens during mentor registration
    $test_mentor_workshop_id = 5; // ID from our debug results
    $test_program_id = 2;
    
    echo "<p>Testing workshop ID $test_mentor_workshop_id for program $test_program_id...</p>";
    
    // Test the workshop validation query
    $checkWorkshopSql = "SELECT id FROM holiday_program_workshops WHERE id = ? AND program_id = ?";
    $checkStmt = $conn->prepare($checkWorkshopSql);
    
    if ($checkStmt) {
        $checkStmt->bind_param("ii", $test_mentor_workshop_id, $test_program_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo "<p>✅ Workshop validation passed - Workshop ID $test_mentor_workshop_id exists for program $test_program_id</p>";
            
            // Test mentor details table structure
            $mentor_table_check = $conn->query("DESCRIBE holiday_program_mentor_details");
            if ($mentor_table_check) {
                echo "<p>✅ Mentor details table exists</p>";
                echo "<details><summary>Table Structure</summary>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($col = $mentor_table_check->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$col['Field']}</td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>{$col['Key']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</details>";
            }
            
        } else {
            echo "<p>❌ Workshop validation failed - Workshop ID $test_mentor_workshop_id not found for program $test_program_id</p>";
        }
    } else {
        echo "<p>❌ Failed to prepare workshop validation query: " . $conn->error . "</p>";
    }
    
    // Check recent registrations
    echo "<h3>👥 Recent Registrations (Last 5):</h3>";
    $recent_sql = "SELECT id, first_name, last_name, email, mentor_registration, created_at 
                   FROM holiday_program_attendees 
                   WHERE program_id = 2 
                   ORDER BY created_at DESC 
                   LIMIT 5";
    $recent_result = $conn->query($recent_sql);
    
    if ($recent_result && $recent_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Mentor</th><th>Created</th></tr>";
        while ($row = $recent_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ($row['mentor_registration'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent registrations found</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='test_mentor_registration.php'>🧪 Test Mentor Registration</a></li>";
echo "<li><a href='holidayProgramRegistration.php?program_id=2'>🔗 Try Actual Registration</a></li>";
if (file_exists('debug_errors.log')) {
    echo "<li><a href='debug_errors.log' target='_blank'>📋 View Debug Log</a></li>";
}
echo "</ul>";
echo "<p><em>Remember to delete all debug files when done!</em></p>";
?>