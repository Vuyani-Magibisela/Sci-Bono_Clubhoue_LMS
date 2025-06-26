<?php
// Test Form Processing - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Test Form Processing</h2>";
echo "<p><strong>WARNING:</strong> This is a test script. Remove after fixing!</p>";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['test_submit'])) {
    echo "<h3>Processing Form Submission...</h3>";
    
    try {
        require_once '../../../server.php';
        echo "<p>Step 1: ✅ Database connected</p>";
        
        // Basic data sanitization
        $program_id = intval($_POST['program_id']);
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        
        echo "<p>Step 2: ✅ Data sanitized</p>";
        
        // Workshop preferences handling
        $workshop_preferences = $_POST['workshop_preferences'] ?? '[]';
        if (!json_decode($workshop_preferences)) {
            $workshop_preferences = '[]';
        }
        
        echo "<p>Step 3: ✅ Workshop preferences processed: $workshop_preferences</p>";
        
        // Permissions
        $photo_permission = isset($_POST['photo_permission']) ? 1 : 0;
        $data_permission = isset($_POST['data_permission']) ? 1 : 0;
        
        // Mentor registration
        $mentor_registration = isset($_POST['mentor_registration']) ? 1 : 0;
        $mentor_status = $mentor_registration ? 'Pending' : NULL;
        
        echo "<p>Step 4: ✅ Permissions and mentor status processed</p>";
        
        // Check for existing registration
        $check_sql = "SELECT id FROM holiday_program_attendees WHERE email = ? AND program_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception("Failed to prepare check query: " . $conn->error);
        }
        
        $check_stmt->bind_param("si", $email, $program_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("Email already registered for this program");
        }
        
        echo "<p>Step 5: ✅ Email availability checked</p>";
        
        // Simplified insert query (only essential fields)
        $sql = "INSERT INTO holiday_program_attendees (
                    program_id, 
                    first_name, 
                    last_name, 
                    email, 
                    phone,
                    workshop_preference,
                    photo_permission,
                    data_permission,
                    mentor_registration,
                    mentor_status,
                    registration_status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare insert query: " . $conn->error);
        }
        
        echo "<p>Step 6: ✅ Insert query prepared</p>";
        
        $stmt->bind_param("issssiiiis", 
            $program_id,
            $first_name,
            $last_name,
            $email,
            $phone,
            $workshop_preferences,
            $photo_permission,
            $data_permission,
            $mentor_registration,
            $mentor_status
        );
        
        echo "<p>Step 7: ✅ Parameters bound</p>";
        
        if ($stmt->execute()) {
            $newRegistrationId = $conn->insert_id;
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>🎉 SUCCESS!</h3>";
            echo "<p>Registration completed successfully!</p>";
            echo "<p><strong>Registration ID:</strong> $newRegistrationId</p>";
            echo "<p><strong>Name:</strong> $first_name $last_name</p>";
            echo "<p><strong>Email:</strong> $email</p>";
            echo "<p><strong>Program ID:</strong> $program_id</p>";
            echo "</div>";
            
            // Handle mentor details if applicable
            if ($mentor_registration) {
                echo "<p>Step 8: Processing mentor details...</p>";
                
                $mentor_experience = htmlspecialchars($_POST['mentor_experience'] ?? 'Not provided');
                $mentor_availability = htmlspecialchars($_POST['mentor_availability'] ?? 'Not specified');
                $mentor_workshop_preference = intval($_POST['mentor_workshop_preference'] ?? 0);
                
                $mentor_sql = "INSERT INTO holiday_program_mentor_details (
                    attendee_id, experience, availability, workshop_preference
                ) VALUES (?, ?, ?, ?)";
                
                $mentor_stmt = $conn->prepare($mentor_sql);
                if ($mentor_stmt) {
                    $mentor_stmt->bind_param("issi", 
                        $newRegistrationId, 
                        $mentor_experience, 
                        $mentor_availability, 
                        $mentor_workshop_preference
                    );
                    
                    if ($mentor_stmt->execute()) {
                        echo "<p>✅ Mentor details saved successfully!</p>";
                    } else {
                        echo "<p>⚠️ Warning: Failed to save mentor details: " . $mentor_stmt->error . "</p>";
                    }
                } else {
                    echo "<p>⚠️ Warning: Failed to prepare mentor query: " . $conn->error . "</p>";
                }
            }
            
        } else {
            throw new Exception("Failed to execute insert: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>❌ ERROR OCCURRED</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
    }
    
} else {
    echo "<h3>📝 Test Registration Form</h3>";
    echo "<p>Fill out this simplified form to test the registration process:</p>";
    ?>
    
    <style>
        .test-form { max-width: 600px; margin: 20px 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; 
        }
        .submit-btn { 
            background: #007cba; color: white; padding: 12px 24px; 
            border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        .submit-btn:hover { background: #005a8a; }
    </style>
    
    <form method="POST" action="" class="test-form">
        <div class="form-group">
            <label for="program_id">Program ID *</label>
            <input type="number" id="program_id" name="program_id" value="2" required>
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" value="John" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" name="last_name" value="Doe" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="john.doe.test<?php echo rand(100,999); ?>@example.com" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="+27 123 456 7890">
        </div>
        
        <div class="form-group">
            <label for="workshop_preferences">Workshop Preferences (JSON format)</label>
            <input type="text" id="workshop_preferences" name="workshop_preferences" value='["1","2"]'>
            <small>Leave as ["1","2"] for workshops 1 and 2</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="photo_permission" value="1" checked> 
                I give permission for photos to be taken
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="data_permission" value="1" checked required> 
                I agree to data processing (Required) *
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="mentor_registration" value="1" id="mentor_check"> 
                Register as a mentor
            </label>
        </div>
        
        <div id="mentor_fields" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
            <div class="form-group">
                <label for="mentor_experience">Mentor Experience</label>
                <textarea id="mentor_experience" name="mentor_experience" rows="3">I have experience in technology and want to help young learners.</textarea>
            </div>
            
            <div class="form-group">
                <label for="mentor_availability">Availability</label>
                <select id="mentor_availability" name="mentor_availability">
                    <option value="full_time">Full time</option>
                    <option value="part_time">Part time</option>
                    <option value="specific_hours">Specific hours</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="mentor_workshop_preference">Workshop Preference</label>
                <select id="mentor_workshop_preference" name="mentor_workshop_preference">
                    <option value="0">No preference</option>
                    <option value="1">Workshop 1</option>
                    <option value="2">Workshop 2</option>
                </select>
            </div>
        </div>
        
        <button type="submit" name="test_submit" class="submit-btn">
            🧪 Test Submit Registration
        </button>
    </form>
    
    <script>
        document.getElementById('mentor_check').addEventListener('change', function() {
            document.getElementById('mentor_fields').style.display = this.checked ? 'block' : 'none';
        });
    </script>
    
    <?php
}

echo "<hr>";
echo "<p><strong>Navigation:</strong></p>";
echo "<ul>";
echo "<li><a href='debug_form_submission.php?program_id=2'>🔍 Detailed Form Debug</a></li>";
echo "<li><a href='holidayProgramRegistration.php?program_id=2'>🔗 Original Registration Form</a></li>";
echo "<li><a href='holidayProgramIndex.php'>🏠 Back to Programs</a></li>";
echo "</ul>";
echo "<p><em>Remember to delete this test file when done debugging!</em></p>";
?>