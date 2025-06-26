<?php
// Fix Mentor Workshop Reference - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Fix Mentor Workshop Reference</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    
    // Check what workshops exist for program 2
    echo "<h3>📋 Checking Workshops for Program 2:</h3>";
    $workshop_sql = "SELECT id, title, max_participants FROM holiday_program_workshops WHERE program_id = 2";
    $result = $conn->query($workshop_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p>✅ Found workshops for program 2:</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Max Participants</th></tr>";
        
        $valid_workshop_ids = [];
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>{$row['max_participants']}</td>";
            echo "</tr>";
            $valid_workshop_ids[] = $row['id'];
        }
        echo "</table>";
        
        echo "<p><strong>Valid workshop IDs for program 2:</strong> " . implode(', ', $valid_workshop_ids) . "</p>";
        
    } else {
        echo "<p>❌ No workshops found for program 2!</p>";
        echo "<p><strong>Solution:</strong> Create workshops for program 2 first, or allow NULL values.</p>";
        
        // Create sample workshops for program 2
        echo "<h3>🛠️ Creating Sample Workshops for Program 2:</h3>";
        
        $sample_workshops = [
            ['title' => 'AI Programming Basics', 'description' => 'Learn the fundamentals of AI programming', 'max_participants' => 15],
            ['title' => 'Machine Learning Workshop', 'description' => 'Hands-on machine learning projects', 'max_participants' => 12],
            ['title' => 'AI Ethics and Applications', 'description' => 'Explore ethical AI and real-world applications', 'max_participants' => 20]
        ];
        
        foreach ($sample_workshops as $workshop) {
            $insert_sql = "INSERT INTO holiday_program_workshops (program_id, title, description, max_participants, created_at, updated_at) 
                          VALUES (2, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($insert_sql);
            if ($stmt) {
                $stmt->bind_param("ssi", $workshop['title'], $workshop['description'], $workshop['max_participants']);
                if ($stmt->execute()) {
                    $workshop_id = $conn->insert_id;
                    echo "<p>✅ Created workshop: {$workshop['title']} (ID: $workshop_id)</p>";
                } else {
                    echo "<p>❌ Failed to create workshop: {$workshop['title']} - " . $stmt->error . "</p>";
                }
            }
        }
        
        // Check again after creating
        $result = $conn->query($workshop_sql);
        if ($result && $result->num_rows > 0) {
            echo "<p><strong>✅ Workshops now available for program 2:</strong></p>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>ID {$row['id']}: " . htmlspecialchars($row['title']) . "</li>";
            }
            echo "</ul>";
        }
    }
    
    // Check the foreign key constraint
    echo "<h3>🔗 Checking Foreign Key Constraint:</h3>";
    $constraint_sql = "SELECT 
                        CONSTRAINT_NAME,
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                      FROM information_schema.KEY_COLUMN_USAGE 
                      WHERE TABLE_NAME = 'holiday_program_mentor_details' 
                      AND CONSTRAINT_NAME != 'PRIMARY'";
    
    $constraint_result = $conn->query($constraint_sql);
    if ($constraint_result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Constraint</th><th>Column</th><th>References Table</th><th>References Column</th></tr>";
        while ($row = $constraint_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['CONSTRAINT_NAME']}</td>";
            echo "<td>{$row['COLUMN_NAME']}</td>";
            echo "<td>{$row['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$row['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Provide fix options
    echo "<h3>💡 Fix Options:</h3>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Option 1: Allow NULL workshop preference (Recommended)</h4>";
    echo "<p>Modify the mentor details insert to allow NULL when no workshop is selected:</p>";
    echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>";
    echo "// In the mentor details insert, change:<br>";
    echo "\$mentor_workshop_preference = intval(\$_POST['mentor_workshop_preference'] ?? 0);<br>";
    echo "// To:<br>";
    echo "\$mentor_workshop_preference = !empty(\$_POST['mentor_workshop_preference']) ? intval(\$_POST['mentor_workshop_preference']) : NULL;";
    echo "</code>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Option 2: Validate workshop ID exists</h4>";
    echo "<p>Check if the workshop ID exists before inserting:</p>";
    echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>";
    echo "// Add validation before mentor insert:<br>";
    echo "if (\$mentor_workshop_preference > 0) {<br>";
    echo "&nbsp;&nbsp;\$check_workshop = \"SELECT id FROM holiday_program_workshops WHERE id = ? AND program_id = ?\";<br>";
    echo "&nbsp;&nbsp;// Only insert if workshop exists<br>";
    echo "}";
    echo "</code>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Option 3: Remove foreign key constraint</h4>";
    echo "<p>Remove the constraint entirely (not recommended for data integrity):</p>";
    echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>";
    echo "ALTER TABLE holiday_program_mentor_details DROP FOREIGN KEY holiday_program_mentor_details_ibfk_2;";
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
echo "<li>Try the mentor registration again after implementing one of the fix options</li>";
echo "<li>Test with both 'no workshop preference' and valid workshop IDs</li>";
echo "<li>Remember to delete this debug file when done!</li>";
echo "</ol>";
?>