<?php
// Debug Registration Form - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Holiday Program Registration Debug Tool</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 2;
echo "<h3>🎯 Testing Program ID: $program_id</h3>";

try {
    // Test database connection
    require_once '../../../server.php';
    echo "<p>✅ Database connection successful!</p>";
    
    // Test if program exists
    echo "<h3>📋 Program Existence Check:</h3>";
    $program_sql = "SELECT id, term, title, registration_open, max_participants FROM holiday_programs WHERE id = ?";
    $stmt = $conn->prepare($program_sql);
    if ($stmt) {
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $program = $result->fetch_assoc();
            echo "<p>✅ Program found: " . htmlspecialchars($program['title']) . "</p>";
            echo "<p>Registration Open: " . ($program['registration_open'] ? 'Yes' : 'No') . "</p>";
            echo "<p>Max Participants: " . $program['max_participants'] . "</p>";
        } else {
            echo "<p>❌ Program with ID $program_id not found!</p>";
        }
    } else {
        echo "<p>❌ Failed to prepare program query: " . $conn->error . "</p>";
    }
    
    // Test workshop enrollment query (this was failing in logs)
    echo "<h3>🔧 Testing Workshop Enrollment Query:</h3>";
    
    // First try the original problematic query
    $problematic_sql = "SELECT 
                            w.id,
                            w.title,
                            w.max_participants,
                            COUNT(CASE WHEN JSON_CONTAINS(a.workshop_preference, CAST(w.id AS JSON)) 
                                    AND a.status NOT IN ('cancelled', 'declined')
                                    THEN 1 END) as enrollment_count
                        FROM holiday_program_workshops w
                        LEFT JOIN holiday_program_attendees a ON a.program_id = w.program_id
                        WHERE w.program_id = ?
                        GROUP BY w.id, w.title, w.max_participants";
    
    echo "<p><strong>Testing original query...</strong></p>";
    $stmt = $conn->prepare($problematic_sql);
    if ($stmt) {
        echo "<p>✅ Query prepared successfully</p>";
        $stmt->bind_param("i", $program_id);
        if ($stmt->execute()) {
            echo "<p>✅ Query executed successfully</p>";
            $result = $stmt->get_result();
            echo "<p>Workshop results: " . $result->num_rows . " rows</p>";
        } else {
            echo "<p>❌ Query execution failed: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>❌ Query prepare failed: " . $conn->error . "</p>";
    }
    
    // Try a simpler workshop query
    echo "<h3>🔧 Testing Simplified Workshop Query:</h3>";
    $simple_sql = "SELECT id, title, max_participants FROM holiday_program_workshops WHERE program_id = ?";
    $stmt = $conn->prepare($simple_sql);
    if ($stmt) {
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<p>✅ Simple workshop query works. Found " . $result->num_rows . " workshops</p>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<p>- Workshop: " . htmlspecialchars($row['title']) . " (Max: " . $row['max_participants'] . ")</p>";
        }
    }
    
    // Test attendee table structure
    echo "<h3>📋 Testing Attendee Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE holiday_program_attendees");
    if ($structure) {
        echo "<p><strong>workshop_preference column info:</strong></p>";
        while ($row = $structure->fetch_assoc()) {
            if ($row['Field'] == 'workshop_preference') {
                echo "<p>Type: {$row['Type']}, Null: {$row['Null']}, Default: {$row['Default']}</p>";
                break;
            }
        }
    }
    
    // Test if there are any attendees with workshop preferences
    echo "<h3>👥 Testing Existing Attendee Data:</h3>";
    $attendee_sql = "SELECT id, workshop_preference FROM holiday_program_attendees WHERE program_id = ? LIMIT 3";
    $stmt = $conn->prepare($attendee_sql);
    if ($stmt) {
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<p>Found " . $result->num_rows . " attendees for this program</p>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<p>Attendee ID: {$row['id']}, Workshop Pref: " . htmlspecialchars($row['workshop_preference'] ?? 'NULL') . "</p>";
        }
    }
    
    // Test basic file includes
    echo "<h3>📁 Testing File Includes:</h3>";
    $files_to_check = [
        '../../../server.php' => 'Database connection',
        '../../Models/holiday-program-functions.php' => 'Holiday functions',
        '../../Controllers/HolidayProgramController.php' => 'Program controller',
    ];
    
    foreach ($files_to_check as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ $description: File exists</p>";
        } else {
            echo "<p>❌ $description: File missing ($file)</p>";
        }
    }
    
    // Try to identify the specific function causing issues
    echo "<h3>🔍 Testing Function Calls:</h3>";
    
    if (function_exists('getWorkshopEnrollmentCounts')) {
        echo "<p>✅ getWorkshopEnrollmentCounts function exists</p>";
        try {
            // This was line 222 in the error
            $counts = getWorkshopEnrollmentCounts($conn, $program_id);
            echo "<p>✅ getWorkshopEnrollmentCounts executed successfully</p>";
        } catch (Exception $e) {
            echo "<p>❌ getWorkshopEnrollmentCounts failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ getWorkshopEnrollmentCounts function not found</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h3>💡 Next Steps:</h3>";
echo "<ul>";
echo "<li>If JSON query is failing: Fix the workshop enrollment query</li>";
echo "<li>If function missing: Check holiday-program-functions.php</li>";
echo "<li>If program not found: Create the program first</li>";
echo "<li>Remember to delete this debug file when done!</li>";
echo "</ul>";

echo "<p><a href='holidayProgramRegistration.php?program_id=$program_id'>🔗 Try accessing the actual registration form</a></p>";
?>