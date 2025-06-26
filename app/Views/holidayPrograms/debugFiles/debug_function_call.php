<?php
// Debug Function Call - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Debug getWorkshopsWithPrerequisites Function</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    
    // Copy your exact function here to test it
    function getWorkshopsWithPrerequisites($conn, $programId) {
        $sql = "SELECT
                    w.*,
                    c.name as cohort_name,
                    c.start_date as cohort_start_date,
                    c.end_date as cohort_end_date,
                    GROUP_CONCAT(DISTINCT pr.description SEPARATOR '; ') as prerequisite_descriptions
                FROM holiday_program_workshops w
                LEFT JOIN holiday_program_cohorts c ON w.cohort_id = c.id
                LEFT JOIN holiday_program_prerequisites pr ON w.id = pr.workshop_id AND pr.is_mandatory = TRUE
                WHERE w.program_id = ?
                GROUP BY w.id
                ORDER BY w.title";
       
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            return [];
        }
       
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
       
        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }
        return $workshops;
    }
    
    $program_id = 2;
    
    echo "<h3>🧪 Testing Function Call:</h3>";
    echo "<p>Calling getWorkshopsWithPrerequisites($program_id)...</p>";
    
    $workshops = getWorkshopsWithPrerequisites($conn, $program_id);
    
    echo "<p><strong>Result:</strong> " . count($workshops) . " workshops found</p>";
    
    if (!empty($workshops)) {
        echo "<h4>✅ Function Works! Workshops found:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Cohort</th><th>Prerequisites</th></tr>";
        foreach ($workshops as $workshop) {
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>{$workshop['id']}</strong></td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($workshop['title']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($workshop['cohort_name'] ?? 'None') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($workshop['prerequisite_descriptions'] ?? 'None') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>🎯 Mentor Dropdown Should Show:</h4>";
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<select style='width: 100%; padding: 8px;'>";
        echo "<option value=''>No preference</option>";
        foreach ($workshops as $workshop) {
            echo "<option value='{$workshop['id']}'>" . htmlspecialchars($workshop['title']) . "</option>";
        }
        echo "</select>";
        echo "</div>";
        
    } else {
        echo "<h4>❌ Function Returns Empty - Let's Debug:</h4>";
        
        // Check if tables exist
        $tables_to_check = ['holiday_program_workshops', 'holiday_program_cohorts', 'holiday_program_prerequisites'];
        
        foreach ($tables_to_check as $table) {
            $check_sql = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($check_sql);
            if ($result && $result->num_rows > 0) {
                echo "<p>✅ Table '$table' exists</p>";
            } else {
                echo "<p>❌ Table '$table' does not exist</p>";
            }
        }
        
        // Check if there are workshops for program 2
        $simple_sql = "SELECT COUNT(*) as count FROM holiday_program_workshops WHERE program_id = ?";
        $stmt = $conn->prepare($simple_sql);
        if ($stmt) {
            $stmt->bind_param("i", $program_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            echo "<p><strong>Workshops in database for program $program_id:</strong> $count</p>";
        }
        
        // Try the query step by step
        echo "<h4>🔍 Step-by-step Query Debug:</h4>";
        
        // Step 1: Basic workshops
        $step1_sql = "SELECT id, title FROM holiday_program_workshops WHERE program_id = ?";
        $stmt1 = $conn->prepare($step1_sql);
        if ($stmt1) {
            $stmt1->bind_param("i", $program_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            echo "<p>Step 1 - Basic workshops: " . $result1->num_rows . " found</p>";
            
            if ($result1->num_rows > 0) {
                // Step 2: With cohorts
                $step2_sql = "SELECT w.id, w.title, c.name as cohort_name 
                             FROM holiday_program_workshops w
                             LEFT JOIN holiday_program_cohorts c ON w.cohort_id = c.id
                             WHERE w.program_id = ?";
                $stmt2 = $conn->prepare($step2_sql);
                if ($stmt2) {
                    $stmt2->bind_param("i", $program_id);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    echo "<p>Step 2 - With cohorts: " . $result2->num_rows . " found</p>";
                    
                    // Step 3: The full query
                    echo "<p>Step 3 - Testing full query...</p>";
                    try {
                        $full_workshops = getWorkshopsWithPrerequisites($conn, $program_id);
                        echo "<p>Full query result: " . count($full_workshops) . " workshops</p>";
                    } catch (Exception $e) {
                        echo "<p>❌ Full query failed: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
    }
    
    // Show what the registration form should do
    echo "<h3>💡 What Registration Form Should Do:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<p>In holidayProgramRegistration.php, make sure you have:</p>";
    echo "<ol>";
    echo "<li><code>\$workshops = getWorkshopsWithPrerequisites(\$conn, \$programId);</code></li>";
    echo "<li>Then the mentor dropdown should use <code>\$workshops</code> to populate options</li>";
    echo "<li>If the function works here but not in the form, check that it's being called correctly</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If function works here, check that it's called correctly in the registration form</li>";
echo "<li>Make sure the mentor dropdown uses the \$workshops variable</li>";
echo "<li>Delete this debug file when done!</li>";
echo "</ul>";
?>