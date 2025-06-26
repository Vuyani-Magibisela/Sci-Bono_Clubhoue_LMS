<?php
// Debug Holiday Programs - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Holiday Programs Debug Tool</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    // Test database connection
    require_once '../../../server.php';
    echo "<h3>✅ Database Connection Test:</h3>";
    echo "<p>Connection successful! Database: " . $conn->server_info . "</p>";
    
    // Check if holiday_programs table exists
    echo "<h3>📋 Database Table Check:</h3>";
    $tables_result = $conn->query("SHOW TABLES LIKE 'holiday_programs'");
    if ($tables_result->num_rows > 0) {
        echo "<p>✅ Table 'holiday_programs' exists</p>";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE holiday_programs");
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $count_result = $conn->query("SELECT COUNT(*) as count FROM holiday_programs");
        $count = $count_result->fetch_assoc()['count'];
        echo "<p><strong>Total Programs in Database:</strong> $count</p>";
        
        if ($count > 0) {
            // Show sample data
            $sample_result = $conn->query("SELECT id, term, title, description, registration_open, start_date, end_date FROM holiday_programs ORDER BY created_at DESC LIMIT 5");
            echo "<p><strong>Sample Programs:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr><th>ID</th><th>Term</th><th>Title</th><th>Registration Open</th><th>Start Date</th><th>End Date</th></tr>";
            while ($row = $sample_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>" . htmlspecialchars($row['term']) . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . ($row['registration_open'] ? '✅ Open' : '❌ Closed') . "</td>";
                echo "<td>{$row['start_date']}</td>";
                echo "<td>{$row['end_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ <strong>ISSUE FOUND:</strong> No programs in the database!</p>";
            echo "<p>You need to create some holiday programs first.</p>";
        }
        
    } else {
        echo "<p>❌ Table 'holiday_programs' does not exist!</p>";
    }
    
    // Test the actual query from holidayProgramIndex.php
    echo "<h3>🔧 Testing Query from holidayProgramIndex.php:</h3>";
    $sql = "SELECT 
                id, 
                term, 
                title, 
                description, 
                dates, 
                start_date, 
                end_date, 
                registration_open,
                max_participants,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
            FROM holiday_programs 
            ORDER BY start_date DESC, created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p>✅ Query executed successfully</p>";
        echo "<p><strong>Number of results:</strong> " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            echo "<p><strong>Query Results:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr><th>ID</th><th>Term</th><th>Title</th><th>Dates</th><th>Registration Open</th><th>Registration Count</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>" . htmlspecialchars($row['term']) . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dates']) . "</td>";
                echo "<td>" . ($row['registration_open'] ? '✅ Open' : '❌ Closed') . "</td>";
                echo "<td>{$row['registration_count']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ Query returned no results</p>";
        }
    } else {
        echo "<p>❌ Query failed: " . $conn->error . "</p>";
    }
    
    // Test Model Loading
    echo "<h3>🏗️ Testing Model Loading:</h3>";
    if (file_exists('../../Models/HolidayProgramCreationModel.php')) {
        echo "<p>✅ HolidayProgramCreationModel.php file exists</p>";
        require_once '../../Models/HolidayProgramCreationModel.php';
        
        if (class_exists('HolidayProgramCreationModel')) {
            echo "<p>✅ HolidayProgramCreationModel class exists</p>";
            
            $model = new HolidayProgramCreationModel($conn);
            
            if (method_exists($model, 'getAllPrograms')) {
                echo "<p>✅ getAllPrograms method exists</p>";
                
                $programs = $model->getAllPrograms();
                echo "<p><strong>Programs from Model:</strong> " . count($programs) . "</p>";
                
                if (!empty($programs)) {
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                    echo "<tr><th>ID</th><th>Term</th><th>Title</th></tr>";
                    foreach ($programs as $program) {
                        echo "<tr>";
                        echo "<td>" . ($program['id'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($program['term'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($program['title'] ?? 'N/A') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p>❌ getAllPrograms method does not exist</p>";
            }
        } else {
            echo "<p>❌ HolidayProgramCreationModel class does not exist</p>";
        }
    } else {
        echo "<p>❌ HolidayProgramCreationModel.php file does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h3>💡 Next Steps:</h3>";
echo "<ul>";
echo "<li>If no programs in database: Create programs using holidayProgramCreationForm.php</li>";
echo "<li>If programs exist but not showing: Check the query logic in holidayProgramIndex.php</li>";
echo "<li>If model issues: Fix the model class or use manual queries</li>";
echo "<li>Remember to delete this debug file when done!</li>";
echo "</ul>";
?>