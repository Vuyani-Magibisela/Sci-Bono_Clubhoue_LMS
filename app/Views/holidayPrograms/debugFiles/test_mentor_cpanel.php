<?php
// Test Mentor Registration on cPanel - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom error logging function
function logError($message) {
    $log_file = __DIR__ . '/mentor_test_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

echo "<h2>🧪 Test Mentor Registration on cPanel Server</h2>";
echo "<p><strong>WARNING:</strong> This is for cPanel server testing only!</p>";

try {
    require_once '../../../server.php';
    echo "<p>✅ Database connected</p>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_mentor'])) {
        echo "<h3>🎯 Processing Mentor Registration Test...</h3>";
        
        // Log the attempt
        logError("Starting mentor registration test");
        
        // Get form data
        $firstName = htmlspecialchars(trim($_POST['first_name']));
        $lastName = htmlspecialchars(trim($_POST['last_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone']));
        $mentorExperience = htmlspecialchars(trim($_POST['mentor_experience']));
        $mentorAvailability = htmlspecialchars(trim($_POST['mentor_availability']));
        $mentorWorkshopPreference = intval($_POST['mentor_workshop_preference']);
        $programId = 2;
        
        echo "<p><strong>Form Data Received:</strong></p>";
        echo "<ul>";
        echo "<li>Name: $firstName $lastName</li>";
        echo "<li>Email: $email</li>";
        echo "<li>Workshop Preference: $mentorWorkshopPreference</li>";
        echo "</ul>";
        
        logError("Form data: $firstName $lastName, Email: $email, Workshop: $mentorWorkshopPreference");
        
        try {
            // Step 1: Insert main registration
            echo "<p>Step 1: Inserting main registration...</p>";
            
            $sql = "INSERT INTO holiday_program_attendees (
                        program_id, first_name, last_name, email, phone,
                        mentor_registration, mentor_status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 1, 'Pending', NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare main registration: " . $conn->error);
            }
            
            $stmt->bind_param("issss", $programId, $firstName, $lastName, $email, $phone);
            
            if ($stmt->execute()) {
                $newRegistrationId = $conn->insert_id;
                echo "<p>✅ Main registration successful! ID: $newRegistrationId</p>";
                logError("Main registration successful, ID: $newRegistrationId");
                
                // Step 2: Handle mentor details
                echo "<p>Step 2: Processing mentor details...</p>";
                
                // Validate workshop preference
                $validWorkshopId = NULL;
                
                if (!empty($mentorWorkshopPreference) && $mentorWorkshopPreference > 0) {
                    echo "<p>Validating workshop ID $mentorWorkshopPreference...</p>";
                    
                    $checkWorkshopSql = "SELECT id FROM holiday_program_workshops WHERE id = ? AND program_id = ?";
                    $checkStmt = $conn->prepare($checkWorkshopSql);
                    
                    if ($checkStmt) {
                        $checkStmt->bind_param("ii", $mentorWorkshopPreference, $programId);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        
                        if ($checkResult->num_rows > 0) {
                            $validWorkshopId = $mentorWorkshopPreference;
                            echo "<p>✅ Workshop validation passed</p>";
                            logError("Workshop validation passed for ID: $mentorWorkshopPreference");
                        } else {
                            echo "<p>⚠️ Workshop ID $mentorWorkshopPreference not found, using NULL</p>";
                            logError("Workshop ID $mentorWorkshopPreference not found for program $programId");
                        }
                    } else {
                        echo "<p>❌ Failed to prepare workshop validation: " . $conn->error . "</p>";
                        logError("Failed to prepare workshop validation: " . $conn->error);
                    }
                } else {
                    echo "<p>ℹ️ No workshop preference selected</p>";
                    logError("No workshop preference selected");
                }
                
                // Insert mentor details
                echo "<p>Inserting mentor details...</p>";
                
                $mentorSql = "INSERT INTO holiday_program_mentor_details (attendee_id, experience, availability, workshop_preference) VALUES (?, ?, ?, ?)";
                $mentorStmt = $conn->prepare($mentorSql);
                
                if ($mentorStmt) {
                    $mentorStmt->bind_param("issi", $newRegistrationId, $mentorExperience, $mentorAvailability, $validWorkshopId);
                    
                    if ($mentorStmt->execute()) {
                        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                        echo "<h3>🎉 SUCCESS!</h3>";
                        echo "<p>Mentor registration completed successfully!</p>";
                        echo "<p><strong>Registration ID:</strong> $newRegistrationId</p>";
                        echo "<p><strong>Workshop Preference:</strong> " . ($validWorkshopId ? $validWorkshopId : 'None') . "</p>";
                        echo "</div>";
                        logError("SUCCESS: Mentor registration completed successfully, ID: $newRegistrationId");
                    } else {
                        throw new Exception("Failed to insert mentor details: " . $mentorStmt->error);
                    }
                } else {
                    throw new Exception("Failed to prepare mentor details statement: " . $conn->error);
                }
                
            } else {
                throw new Exception("Failed to insert main registration: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>❌ ERROR</h3>";
            echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "</div>";
            logError("ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        }
        
    } else {
        // Show the test form
        echo "<h3>📝 Mentor Registration Test Form</h3>";
        echo "<p>This will test mentor registration specifically on the cPanel server.</p>";
        
        // Get available workshops
        $workshops = [];
        $workshop_sql = "SELECT id, title FROM holiday_program_workshops WHERE program_id = 2 ORDER BY title";
        $workshop_result = $conn->query($workshop_sql);
        if ($workshop_result) {
            while ($row = $workshop_result->fetch_assoc()) {
                $workshops[] = $row;
            }
        }
        
        if (empty($workshops)) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<p><strong>Warning:</strong> No workshops found for program 2!</p>";
            echo "</div>";
        }
        ?>
        
        <style>
            .test-form { max-width: 600px; margin: 20px 0; background: #f8f9fa; padding: 20px; border-radius: 8px; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group select, .form-group textarea { 
                width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
            }
            .submit-btn { 
                background: #007cba; color: white; padding: 12px 24px; 
                border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
            }
            .submit-btn:hover { background: #005a8a; }
        </style>
        
        <form method="POST" action="" class="test-form">
            <h4>Test Mentor Registration</h4>
            
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="Test" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="Mentor" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="test.mentor.<?php echo rand(100,999); ?>@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="+27 123 456 789" required>
            </div>
            
            <div class="form-group">
                <label for="mentor_experience">Experience:</label>
                <textarea id="mentor_experience" name="mentor_experience" rows="3" required>I have experience in technology and education, and I'm excited to mentor young learners in this program.</textarea>
            </div>
            
            <div class="form-group">
                <label for="mentor_availability">Availability:</label>
                <select id="mentor_availability" name="mentor_availability" required>
                    <option value="full_time">Full time</option>
                    <option value="part_time">Part time</option>
                    <option value="specific_hours">Specific hours</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="mentor_workshop_preference">Workshop Preference:</label>
                <select id="mentor_workshop_preference" name="mentor_workshop_preference">
                    <option value="">No preference</option>
                    <?php foreach ($workshops as $workshop): ?>
                        <option value="<?php echo $workshop['id']; ?>">
                            <?php echo htmlspecialchars($workshop['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if (!empty($workshops)): ?>
                <small>Available workshops: 
                    <?php 
                    $workshop_titles = array_map(function($w) { return $w['title']; }, $workshops);
                    echo implode(', ', $workshop_titles);
                    ?>
                </small>
                <?php endif; ?>
            </div>
            
            <button type="submit" name="test_mentor" class="submit-btn">
                🧪 Test Mentor Registration
            </button>
        </form>
        
        <?php
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Connection Error:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    logError("Connection error: " . $e->getMessage());
}

echo "<hr>";
echo "<p><strong>Debug Tools:</strong></p>";
echo "<ul>";
echo "<li><a href='cpanel_debug_logger.php'>🔍 Server Environment Check</a></li>";
echo "<li><a href='holidayProgramRegistration.php?program_id=2'>🔗 Try Real Registration Form</a></li>";
if (file_exists('mentor_test_errors.log')) {
    echo "<li><a href='mentor_test_errors.log' target='_blank'>📋 View Error Log</a></li>";
}
echo "</ul>";
echo "<p><em>Delete all debug files when testing is complete!</em></p>";
?>