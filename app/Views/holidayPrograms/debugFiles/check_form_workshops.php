<?php
// Check Form Workshop Options - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Check Form Workshop Options</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    
    $program_id = 2;
    
    echo "<h3>📋 Workshops in Database for Program 2:</h3>";
    $workshop_sql = "SELECT id, title, description, max_participants FROM holiday_program_workshops WHERE program_id = ?";
    $stmt = $conn->prepare($workshop_sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $db_workshops = [];
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Max Participants</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $db_workshops[] = $row;
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>{$row['id']}</strong></td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['description'] ?? 'No description') . "</td>";
            echo "<td style='padding: 8px;'>{$row['max_participants']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No workshops found in database!</p>";
    }
    
    // Test the functions used by the registration form
    echo "<h3>🔧 Testing Registration Form Functions:</h3>";
    
    // Include the functions file if it exists
    if (file_exists('../../Models/holiday-program-functions.php')) {
        require_once '../../Models/holiday-program-functions.php';
        echo "<p>✅ holiday-program-functions.php included</p>";
    }
    
    // Test getWorkshopsWithPrerequisites function
    if (function_exists('getWorkshopsWithPrerequisites')) {
        echo "<p>✅ getWorkshopsWithPrerequisites function exists</p>";
        try {
            $form_workshops = getWorkshopsWithPrerequisites($conn, $program_id);
            echo "<p><strong>Workshops returned by form function:</strong> " . count($form_workshops) . "</p>";
            
            if (!empty($form_workshops)) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
                echo "<tr><th>ID</th><th>Title</th><th>Prerequisites</th></tr>";
                foreach ($form_workshops as $workshop) {
                    echo "<tr>";
                    echo "<td style='padding: 8px;'><strong>{$workshop['id']}</strong></td>";
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($workshop['title']) . "</td>";
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($workshop['prerequisite_descriptions'] ?? 'None') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p>❌ getWorkshopsWithPrerequisites failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ getWorkshopsWithPrerequisites function not found</p>";
    }
    
    // Check what the registration form is actually showing
    echo "<h3>🎯 What Form Should Show for Mentor Workshop Dropdown:</h3>";
    
    if (!empty($db_workshops)) {
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h4>Correct mentor workshop dropdown should have:</h4>";
        echo "<select style='width: 100%; padding: 8px; margin: 10px 0;'>";
        echo "<option value=''>No preference</option>";
        foreach ($db_workshops as $workshop) {
            echo "<option value='{$workshop['id']}'>" . htmlspecialchars($workshop['title']) . "</option>";
        }
        echo "</select>";
        echo "</div>";
    }
    
    // Check if form is using hardcoded values
    echo "<h3>🐛 Common Issues:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Issue 1: Hardcoded Workshop Options</h4>";
    echo "<p>The registration form might have hardcoded workshop options like:</p>";
    echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>";
    echo "&lt;option value=\"1\"&gt;Workshop 1&lt;/option&gt;<br>";
    echo "&lt;option value=\"2\"&gt;Workshop 2&lt;/option&gt;<br>";
    echo "&lt;option value=\"3\"&gt;Workshop 3&lt;/option&gt;";
    echo "</code>";
    echo "<p><strong>But program 2 actually has workshop IDs:</strong> 5, 6, 7</p>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Issue 2: Wrong Function Call</h4>";
    echo "<p>The form might be calling a function that returns default workshops instead of program-specific workshops.</p>";
    echo "</div>";
    
    // Test form access with debug
    echo "<h3>🔗 Testing Form Access:</h3>";
    echo "<p><a href='holidayProgramRegistration.php?program_id=2&debug=1' target='_blank'>🔍 Open Registration Form with Debug</a></p>";
    echo "<p><a href='holidayProgramRegistration.php?program_id=2' target='_blank'>🔗 Open Normal Registration Form</a></p>";
    
    // Show the exact fix needed
    echo "<h3>💡 Quick Fix:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>To fix the mentor workshop dropdown in holidayProgramRegistration.php:</h4>";
    echo "<ol>";
    echo "<li>Find the mentor workshop dropdown (around the mentor fields section)</li>";
    echo "<li>Make sure it's using <code>\$workshops</code> from the database query, not hardcoded values</li>";
    echo "<li>The dropdown should look like this:</li>";
    echo "</ol>";
    echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>";
    echo "&lt;select name=\"mentor_workshop_preference\"&gt;<br>";
    echo "&nbsp;&nbsp;&lt;option value=\"\"&gt;No preference&lt;/option&gt;<br>";
    echo "&nbsp;&nbsp;&lt;?php foreach (\$workshops as \$workshop): ?&gt;<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&lt;option value=\"&lt;?php echo \$workshop['id']; ?&gt;\"&gt;<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;?php echo htmlspecialchars(\$workshop['title']); ?&gt;<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&lt;/option&gt;<br>";
    echo "&nbsp;&nbsp;&lt;?php endforeach; ?&gt;<br>";
    echo "&lt;/select&gt;";
    echo "</code>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check the registration form source code to see what workshop options are shown</li>";
echo "<li>Update the mentor workshop dropdown to use actual workshop IDs (5, 6, 7)</li>";
echo "<li>Test mentor registration again</li>";
echo "<li>Delete this debug file when done!</li>";
echo "</ol>";
?>