<?php
// Debug Form Submission - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Form Submission Debug Tool</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

try {
    require_once '../../../server.php';
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if this is a POST request (form submission)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        echo "<h3>📝 Form Submission Detected!</h3>";
        
        // Show what data was submitted (safely)
        echo "<h4>Form Data Received:</h4>";
        echo "<ul>";
        foreach ($_POST as $key => $value) {
            if ($key === 'password' || $key === 'confirm_password') {
                echo "<li><strong>$key:</strong> [HIDDEN FOR SECURITY]</li>";
            } else {
                if (is_array($value)) {
                    echo "<li><strong>$key:</strong> " . implode(', ', $value) . "</li>";
                } else {
                    echo "<li><strong>$key:</strong> " . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . "</li>";
                }
            }
        }
        echo "</ul>";
        
        // Test database table structure
        echo "<h4>🗃️ Testing Database Table Structure:</h4>";
        $structure = $conn->query("DESCRIBE holiday_program_attendees");
        $columns = [];
        if ($structure) {
            while ($row = $structure->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            echo "<p>✅ Table exists with " . count($columns) . " columns</p>";
            echo "<details><summary>Click to see all columns</summary>";
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li>$col</li>";
            }
            echo "</ul></details>";
        } else {
            echo "<p>❌ Could not get table structure</p>";
        }
        
        // Test essential fields
        echo "<h4>🔍 Testing Essential Form Fields:</h4>";
        $required_fields = ['first_name', 'last_name', 'email', 'program_id'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
                echo "<p>❌ Missing required field: $field</p>";
            } else {
                echo "<p>✅ Required field present: $field = " . htmlspecialchars($_POST[$field]) . "</p>";
            }
        }
        
        if (empty($missing_fields)) {
            echo "<h4>🧪 Testing Database Insert (Dry Run):</h4>";
            
            // Simulate the insert without actually doing it
            $program_id = intval($_POST['program_id']);
            $first_name = htmlspecialchars(trim($_POST['first_name']));
            $last_name = htmlspecialchars(trim($_POST['last_name']));
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            echo "<p>Sanitized data:</p>";
            echo "<ul>";
            echo "<li>Program ID: $program_id</li>";
            echo "<li>Name: $first_name $last_name</li>";
            echo "<li>Email: $email</li>";
            echo "</ul>";
            
            // Test if email already exists
            $check_sql = "SELECT id FROM holiday_program_attendees WHERE email = ? AND program_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("si", $email, $program_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    echo "<p>⚠️ Email already registered for this program</p>";
                } else {
                    echo "<p>✅ Email not yet registered - good to proceed</p>";
                }
            }
            
            // Test basic insert query structure
            echo "<h4>🔧 Testing Insert Query Structure:</h4>";
            
            // Simplified insert query for testing
            $test_sql = "INSERT INTO holiday_program_attendees (
                program_id, first_name, last_name, email, created_at
            ) VALUES (?, ?, ?, ?, NOW())";
            
            $test_stmt = $conn->prepare($test_sql);
            if ($test_stmt) {
                echo "<p>✅ Basic insert query prepared successfully</p>";
                echo "<p>Query: $test_sql</p>";
                
                // Don't actually execute, just test preparation
                echo "<p><strong>Note:</strong> Query prepared but not executed (dry run)</p>";
            } else {
                echo "<p>❌ Failed to prepare basic insert query: " . $conn->error . "</p>";
            }
            
            // Test mentor details if applicable
            if (isset($_POST['mentor_registration']) && $_POST['mentor_registration'] == 1) {
                echo "<h4>👨‍🏫 Testing Mentor Registration:</h4>";
                
                $mentor_sql = "INSERT INTO holiday_program_mentor_details (
                    attendee_id, experience, availability, workshop_preference
                ) VALUES (?, ?, ?, ?)";
                
                $mentor_stmt = $conn->prepare($mentor_sql);
                if ($mentor_stmt) {
                    echo "<p>✅ Mentor details query prepared successfully</p>";
                } else {
                    echo "<p>❌ Failed to prepare mentor query: " . $conn->error . "</p>";
                }
            }
            
        } else {
            echo "<p>❌ Cannot test database operations due to missing required fields</p>";
        }
        
    } else {
        echo "<h3>📝 GET Request - Showing Test Form</h3>";
        echo "<p>Submit this form to test the submission process:</p>";
        
        // Simple test form
        ?>
        <form method="POST" action="" style="max-width: 500px; margin: 20px 0;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>Program ID:</strong></label>
                <input type="number" name="program_id" value="2" required style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>First Name:</strong></label>
                <input type="text" name="first_name" value="Test" required style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>Last Name:</strong></label>
                <input type="text" name="last_name" value="User" required style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>Email:</strong></label>
                <input type="email" name="email" value="test@example.com" required style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>Phone:</strong></label>
                <input type="tel" name="phone" value="123-456-7890" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;"><strong>Workshop Preferences (JSON):</strong></label>
                <input type="text" name="workshop_preferences" value='["1","2"]' style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="photo_permission" value="1"> Photo Permission
                </label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="data_permission" value="1" checked> Data Permission (Required)
                </label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="mentor_registration" value="1"> Register as Mentor
                </label>
            </div>
            
            <button type="submit" name="test_submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                Test Submit
            </button>
        </form>
        <?php
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>💡 Common Issues & Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Missing columns:</strong> Add missing database columns</li>";
echo "<li><strong>Data type mismatch:</strong> Check column types vs form data</li>";
echo "<li><strong>JSON format issues:</strong> Workshop preferences must be valid JSON</li>";
echo "<li><strong>Required field validation:</strong> Check form validation logic</li>";
echo "<li><strong>Duplicate entry:</strong> Check unique constraints</li>";
echo "</ul>";

echo "<p><a href='holidayProgramRegistration.php?program_id=2'>🔗 Back to Registration Form</a></p>";
echo "<p><em>Remember to delete this debug file when done!</em></p>";
?>