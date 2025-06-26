<?php
// Check Current Mentor Dropdown - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Check Current Mentor Dropdown in Registration Form</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    
    $program_id = 2;
    
    echo "<h3>📝 Simulating Registration Form Variables:</h3>";
    
    // Simulate what the registration form should do
    echo "<p>Step 1: Testing function call...</p>";
    
    // Add your function here (same as in registration form)
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
    
    // This is what should happen in the registration form
    $programId = $program_id;
    $workshops = getWorkshopsWithPrerequisites($conn, $programId);
    
    echo "<p>✅ \$workshops variable populated with " . count($workshops) . " workshops</p>";
    
    if (!empty($workshops)) {
        echo "<h3>✅ Current workshops available:</h3>";
        foreach ($workshops as $workshop) {
            echo "<p>ID: {$workshop['id']} - " . htmlspecialchars($workshop['title']) . "</p>";
        }
    } else {
        echo "<p>❌ No workshops found - this is the problem!</p>";
    }
    
    echo "<h3>🎯 What Mentor Dropdown Should Look Like:</h3>";
    ?>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4>CORRECT Mentor Workshop Dropdown:</h4>
        <select id="mentor_workshop_preference" name="mentor_workshop_preference" class="form-select" style="width: 100%; padding: 8px;">
            <option value="">No preference</option>
            <?php if (!empty($workshops)): ?>
                <?php foreach ($workshops as $workshop): ?>
                    <option value="<?php echo $workshop['id']; ?>">
                        <?php echo htmlspecialchars($workshop['title']); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">No workshops available</option>
            <?php endif; ?>
        </select>
        
        <p style="margin-top: 10px;"><strong>Workshop IDs shown:</strong> 
        <?php 
        if (!empty($workshops)) {
            $ids = array_column($workshops, 'id');
            echo implode(', ', $ids);
        } else {
            echo "None";
        }
        ?>
        </p>
    </div>
    
    <?php
    echo "<h3>🔧 Registration Form Debugging Steps:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Check These in holidayProgramRegistration.php:</h4>";
    echo "<ol>";
    echo "<li><strong>Line where \$workshops is populated:</strong><br>";
    echo "<code>\$workshops = getWorkshopsWithPrerequisites(\$conn, \$programId);</code></li>";
    echo "<li><strong>Add debug output after the function call:</strong><br>";
    echo "<code>echo \"Debug: Found \" . count(\$workshops) . \" workshops\";</code></li>";
    echo "<li><strong>Check if mentor dropdown uses \$workshops variable</strong></li>";
    echo "<li><strong>Make sure the function is called BEFORE the HTML form is rendered</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🚨 Most Likely Issues:</h3>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Issue 1: Function called but \$workshops not used in dropdown</h4>";
    echo "<p>The mentor dropdown might still have hardcoded options instead of using \$workshops</p>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Issue 2: Function not called in the right place</h4>";
    echo "<p>The function might be called after the HTML is rendered, or not at all</p>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Issue 3: Table doesn't exist</h4>";
    echo "<p>One of the tables in the LEFT JOIN might not exist</p>";
    echo "</div>";
    
    // Quick fix for testing
    echo "<h3>💡 Quick Test Fix:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<p>To quickly test, you can temporarily add this PHP code in your mentor dropdown section:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px; margin: 10px 0;'>";
    echo htmlspecialchars('<?php
// Quick fix - add this right before the mentor dropdown
$temp_workshops = [];
$temp_sql = "SELECT id, title FROM holiday_program_workshops WHERE program_id = ?";
$temp_stmt = $conn->prepare($temp_sql);
if ($temp_stmt) {
    $temp_stmt->bind_param("i", $programId);
    $temp_stmt->execute();
    $temp_result = $temp_stmt->get_result();
    while ($temp_row = $temp_result->fetch_assoc()) {
        $temp_workshops[] = $temp_row;
    }
}
echo "<!-- Debug: Found " . count($temp_workshops) . " workshops -->";
?>');
    echo "</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Action Plan:</strong></p>";
echo "<ol>";
echo "<li>Run the debug function test first</li>";
echo "<li>Check if \$workshops is populated in the actual registration form</li>";
echo "<li>Update the mentor dropdown to use \$workshops</li>";
echo "<li>Test the mentor registration</li>";
echo "</ol>";
?>