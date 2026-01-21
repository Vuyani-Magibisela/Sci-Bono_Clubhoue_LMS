checkCohortCapacity(<?php
// ENHANCED REGISTRATION FORM v2.0 - Adding Emergency Contact
session_start();

function checkCohortCapacity($conn, $cohortId) {
    $sql = "SELECT c.max_participants, c.current_participants, c.status,
                   COUNT(a.id) as actual_registrations
            FROM holiday_program_cohorts c
            LEFT JOIN holiday_program_attendees a ON c.id = a.cohort_id
            WHERE c.id = ?
            GROUP BY c.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cohortId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cohort = $result->fetch_assoc();
    $stmt->close();
    
    if (!$cohort) {
        return ['available' => false, 'remaining' => 0];
    }
    
    // Use actual registrations count (more reliable than current_participants)
    $used = $cohort['actual_registrations'];
    $max = $cohort['max_participants'];
    $remaining = $max - $used;
    
    return [
        'available' => ($cohort['status'] === 'active' && $remaining > 0),
        'remaining' => max(0, $remaining),
        'total' => $max,
        'used' => $used
    ];
}



// Get program ID
$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 2;

// Initialize variables
$success = false;
$error = '';
$workshops = [];

// Get available workshops for this program
try {
    require_once '../../../server.php';
    
    if ($conn) {
        $workshopSql = "SELECT id, title, description, max_participants FROM holiday_program_workshops WHERE program_id = ? ORDER BY title";
        $workshopStmt = $conn->prepare($workshopSql);
        if ($workshopStmt) {
            $workshopStmt->bind_param("i", $programId);
            $workshopStmt->execute();
            $result = $workshopStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $workshops[] = $row;
            }
        }
    }
    
    // Default workshops if none found in database
    if (empty($workshops)) {
        $workshops = [
            ['id' => 1, 'title' => 'Graphic Design Basics', 'description' => 'Learn design fundamentals', 'max_participants' => 15],
            ['id' => 2, 'title' => 'Video Editing', 'description' => 'Video production skills', 'max_participants' => 12],
            ['id' => 3, 'title' => 'Animation Fundamentals', 'description' => 'Create animated content', 'max_participants' => 10]
        ];
    }
} catch (Exception $e) {
    // Continue with default workshops if database query fails
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    try {
        // Get form data
        $cohortId = (int)$_POST['cohort_id']; // NEW: Get cohort selection
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $school = trim($_POST['school']);
        $grade = (int)$_POST['grade'];
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $province = $_POST['province'];
        $postalCode = trim($_POST['postal_code']);
        $guardianName = trim($_POST['guardian_name']);
        $guardianPhone = trim($_POST['guardian_phone']);
        $guardianEmail = trim($_POST['guardian_email']);
        $whyInterested = trim($_POST['why_interested']);
        $photoPermission = isset($_POST['photo_permission']) ? 1 : 0;
        $dataPermission = isset($_POST['data_permission']) ? 1 : 0;
        
        // NEW v2.0: Emergency contact information
        $emergencyContactName = trim($_POST['emergency_contact_name']);
        $emergencyContactRelationship = trim($_POST['emergency_contact_relationship']);
        $emergencyContactPhone = trim($_POST['emergency_contact_phone']);

        // NEW: Check if selected cohort is still available
        if ($cohortId > 0) {
            $selectedCohortCapacity = checkCohortCapacity($conn, $cohortId);
            if (!$selectedCohortCapacity['available']) {
                throw new Exception("Sorry, the selected week is now full. Please choose another week.");
            }
        }
        
        // Handle "same as guardian" option
        $sameAsGuardian = isset($_POST['same_as_guardian']);
        if ($sameAsGuardian) {
            $emergencyContactName = $guardianName;
            $emergencyContactRelationship = $_POST['guardian_relationship'] ?? 'Parent/Guardian';
            $emergencyContactPhone = $guardianPhone;
        }
        
        // Handle workshop preferences
        $workshopPreferences = [];
        if (isset($_POST['workshop_1']) && !empty($_POST['workshop_1'])) {
            $workshopPreferences[] = (int)$_POST['workshop_1'];
        }
        if (isset($_POST['workshop_2']) && !empty($_POST['workshop_2']) && $_POST['workshop_2'] != $_POST['workshop_1']) {
            $workshopPreferences[] = (int)$_POST['workshop_2'];
        }
        $workshopPreferenceJson = json_encode($workshopPreferences);
        
        // Basic validation
        if ($cohortId <= 0) {
            throw new Exception("Please select a program week.");
        }

        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        if (empty($workshopPreferences)) {
            throw new Exception("Please select at least one workshop preference.");
        }
        
        // NEW v2.0: Emergency contact validation
        if (!$sameAsGuardian) {
            if (empty($emergencyContactName) || empty($emergencyContactRelationship) || empty($emergencyContactPhone)) {
                throw new Exception("Please fill in all emergency contact fields or select 'Same as Guardian'.");
            }
        }
        
        // Check for duplicate registration
        $checkSql = "SELECT id FROM holiday_program_attendees WHERE email = ? AND program_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $email, $programId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("This email is already registered for this program.");
        }
        
        // Insert registration with emergency contact fields
        $sql = "INSERT INTO holiday_program_attendees (
                    program_id, cohort_id, first_name, last_name, email, phone, 
                    date_of_birth, gender, school, grade, address, 
                    city, province, postal_code, guardian_name, 
                    guardian_phone, guardian_email, why_interested,
                    emergency_contact_name, emergency_contact_relationship, emergency_contact_phone,
                    workshop_preference, photo_permission, data_permission,
                    registration_status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW()
                )";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("iisssssssississsssssssii", 
            $programId, $cohortId, $firstName, $lastName, $email, $phone,
            $dateOfBirth, $gender, $school, $grade, $address,
            $city, $province, $postalCode, $guardianName,
            $guardianPhone, $guardianEmail, $whyInterested,
            $emergencyContactName, $emergencyContactRelationship, $emergencyContactPhone,
            $workshopPreferenceJson, $photoPermission, $dataPermission
        );
        
        if ($stmt->execute()) {
            $success = true;
            $registrationId = $conn->insert_id;
        } else {
            throw new Exception("Registration failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$cohort3Capacity = checkCohortCapacity($conn, 3);
$cohort4Capacity = checkCohortCapacity($conn, 4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Program Registration v2.0</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        .required {
            color: red;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .workshop-selection {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
        }
        
        .workshop-option {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .workshop-option h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .workshop-option p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .version-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        /* NEW v2.0: Emergency contact styling */
        .emergency-contact {
            background: #fff3e0;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ff9800;
            margin: 20px 0;
        }
        
        .same-as-guardian {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #b3d9ff;
        }
        
        .emergency-fields {
            transition: opacity 0.3s ease;
        }
        
        .emergency-fields.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        option:disabled {
            color: #999;
            background-color: #f5f5f5;
            font-style: italic;
        }

        select option:disabled {
            color: #999 !important;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- <div class="version-info">
            <strong>🚀 Enhanced Registration v2.0</strong> - Now with Workshop Selection + Emergency Contact!
        </div> -->
        
        <h1>🎓 Holiday Program Registration</h1>
        
        <?php if ($success): ?>
            <div class="success">
                <h2>✅ Registration Successful!</h2>
                <p>Thank you for registering for the holiday program.</p>
                <p><strong>Registration ID:</strong> #<?php echo $registrationId; ?></p>
                <p><strong>Workshop Preferences:</strong> 
                <?php 
                $prefs = json_decode($workshopPreferenceJson, true);
                if (!empty($prefs)) {
                    $workshopNames = [];
                    foreach ($prefs as $prefId) {
                        foreach ($workshops as $workshop) {
                            if ($workshop['id'] == $prefId) {
                                $workshopNames[] = $workshop['title'];
                                break;
                            }
                        }
                    }
                    echo implode(', ', $workshopNames);
                }
                ?>
                </p>
                <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($emergencyContactName); ?> (<?php echo htmlspecialchars($emergencyContactRelationship); ?>)</p>
                <p>You will receive a confirmation email shortly.</p>
                <p><strong>Selected Week:</strong> <?php echo htmlspecialchars($cohortNames[$cohortId] ?? 'Unknown'); ?></p>
                <p><strong>Dashboard Access:</strong> You will receive an email with instructions to set up your password and access your program dashboard.</p>
                
                <div style="margin-top: 20px;">
                    <a href="holidayProgramIndex.php" class="submit-btn" style="text-decoration: none; display: inline-block; text-align: center;">
                        🏠 Back to Programs
                    </a>
                </div>
            </div>
        <?php else: ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="program_id" value="<?php echo $programId; ?>">
                
                <!-- Personal Information -->
                <h3>👤 Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required 
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            <option value="Prefer not to say" <?php echo ($_POST['gender'] ?? '') === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <h3>📅 Program Week Selection</h3>
                <div class="form-group">
                    <label for="cohort_id">Select Program Week <span class="required">*</span></label>
                    <select id="cohort_id" name="cohort_id" required>
                        <option value="">Choose your week</option>
                        
                        <!-- Week 1 Option -->
                        <option value="3" 
                                <?php echo ($_POST['cohort_id'] ?? '') == '3' ? 'selected' : ''; ?>
                                <?php echo !$cohort3Capacity['available'] ? 'disabled' : ''; ?>>
                            Week 1: June 30 - July 4, 2025
                            <?php if (!$cohort3Capacity['available']): ?>
                                (FULL - <?php echo $cohort3Capacity['used']; ?>/<?php echo $cohort3Capacity['total']; ?>)
                            <?php else: ?>
                                (<?php echo $cohort3Capacity['remaining']; ?> spots left)
                            <?php endif; ?>
                        </option>
                        
                        <!-- Week 2 Option -->
                        <option value="4" 
                                <?php echo ($_POST['cohort_id'] ?? '') == '4' ? 'selected' : ''; ?>
                                <?php echo !$cohort4Capacity['available'] ? 'disabled' : ''; ?>>
                            Week 2: July 7 - July 11, 2025
                            <?php if (!$cohort4Capacity['available']): ?>
                                (FULL - <?php echo $cohort4Capacity['used']; ?>/<?php echo $cohort4Capacity['total']; ?>)
                            <?php else: ?>
                                (<?php echo $cohort4Capacity['remaining']; ?> spots left)
                            <?php endif; ?>
                        </option>
                    </select>
                    
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Both weeks offer the same workshops. Choose based on your availability.
                        <?php if (!$cohort3Capacity['available'] || !$cohort4Capacity['available']): ?>
                            <br><strong style="color: #e74c3c;">⚠️ Some weeks are full. Please select an available week.</strong>
                        <?php endif; ?>
                    </small>

                    <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196F3;">
                        <h4 style="margin-top: 0;">📊 Program Capacity Status:</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <strong>Week 1 (June 30 - July 4):</strong><br>
                                <?php if ($cohort3Capacity['available']): ?>
                                    <span style="color: #4CAF50;">✅ Available - <?php echo $cohort3Capacity['remaining']; ?> spots left</span>
                                <?php else: ?>
                                    <span style="color: #e74c3c;">❌ Full (<?php echo $cohort3Capacity['used']; ?>/<?php echo $cohort3Capacity['total']; ?>)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <strong>Week 2 (July 7 - July 11):</strong><br>
                                <?php if ($cohort4Capacity['available']): ?>
                                    <span style="color: #4CAF50;">✅ Available - <?php echo $cohort4Capacity['remaining']; ?> spots left</span>
                                <?php else: ?>
                                    <span style="color: #e74c3c;">❌ Full (<?php echo $cohort4Capacity['used']; ?>/<?php echo $cohort4Capacity['total']; ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Workshop Selection -->
                <div class="workshop-selection">
                    <h3>🎨 Workshop Preferences</h3>
                    <p>Select your <strong>first choice</strong> and <strong>second choice</strong> workshops:</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="workshop_1">First Choice <span class="required">*</span></label>
                            <select id="workshop_1" name="workshop_1" required>
                                <option value="">Select First Choice</option>
                                <?php foreach ($workshops as $workshop): ?>
                                    <option value="<?php echo $workshop['id']; ?>" 
                                            <?php echo ($_POST['workshop_1'] ?? '') == $workshop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($workshop['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="workshop_2">Second Choice</label>
                            <select id="workshop_2" name="workshop_2">
                                <option value="">Select Second Choice (Optional)</option>
                                <?php foreach ($workshops as $workshop): ?>
                                    <option value="<?php echo $workshop['id']; ?>"
                                            <?php echo ($_POST['workshop_2'] ?? '') == $workshop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($workshop['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Workshop Information -->
                    <div style="margin-top: 20px;">
                        <h4>Available Workshops:</h4>
                        <?php foreach ($workshops as $workshop): ?>
                            <div class="workshop-option">
                                <h4><?php echo htmlspecialchars($workshop['title']); ?></h4>
                                <p><?php echo htmlspecialchars($workshop['description']); ?></p>
                                <small>Max participants: <?php echo $workshop['max_participants']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- School Information -->
                <h3>🏫 School Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="school">School Name <span class="required">*</span></label>
                        <input type="text" id="school" name="school" required 
                               value="<?php echo htmlspecialchars($_POST['school'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="grade">Grade <span class="required">*</span></label>
                        <select id="grade" name="grade" required>
                            <option value="">Select Grade</option>
                            <?php for ($i = 5; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($_POST['grade'] ?? '') == $i ? 'selected' : ''; ?>>
                                    Grade <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Address -->
                <h3>📍 Address Information</h3>
                <div class="form-group">
                    <label for="address">Street Address <span class="required">*</span></label>
                    <input type="text" id="address" name="address" required 
                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required 
                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="province">Province <span class="required">*</span></label>
                        <select id="province" name="province" required>
                            <option value="">Select Province</option>
                            <option value="Gauteng" <?php echo ($_POST['province'] ?? '') === 'Gauteng' ? 'selected' : ''; ?>>Gauteng</option>
                            <option value="Western Cape" <?php echo ($_POST['province'] ?? '') === 'Western Cape' ? 'selected' : ''; ?>>Western Cape</option>
                            <option value="KwaZulu-Natal" <?php echo ($_POST['province'] ?? '') === 'KwaZulu-Natal' ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                            <option value="Eastern Cape" <?php echo ($_POST['province'] ?? '') === 'Eastern Cape' ? 'selected' : ''; ?>>Eastern Cape</option>
                            <option value="Free State" <?php echo ($_POST['province'] ?? '') === 'Free State' ? 'selected' : ''; ?>>Free State</option>
                            <option value="Limpopo" <?php echo ($_POST['province'] ?? '') === 'Limpopo' ? 'selected' : ''; ?>>Limpopo</option>
                            <option value="Mpumalanga" <?php echo ($_POST['province'] ?? '') === 'Mpumalanga' ? 'selected' : ''; ?>>Mpumalanga</option>
                            <option value="Northern Cape" <?php echo ($_POST['province'] ?? '') === 'Northern Cape' ? 'selected' : ''; ?>>Northern Cape</option>
                            <option value="North West" <?php echo ($_POST['province'] ?? '') === 'North West' ? 'selected' : ''; ?>>North West</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="postal_code">Postal Code <span class="required">*</span></label>
                    <input type="text" id="postal_code" name="postal_code" required 
                           value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                </div>
                
                <!-- Guardian Information -->
                <h3>👨‍👩‍👧‍👦 Parent/Guardian Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardian_name">Parent/Guardian Name <span class="required">*</span></label>
                        <input type="text" id="guardian_name" name="guardian_name" required 
                               value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="guardian_relationship">Relationship <span class="required">*</span></label>
                        <select id="guardian_relationship" name="guardian_relationship" required>
                            <option value="">Select Relationship</option>
                            <option value="Mother" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                            <option value="Father" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Father' ? 'selected' : ''; ?>>Father</option>
                            <option value="Grandmother" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Grandmother' ? 'selected' : ''; ?>>Grandmother</option>
                            <option value="Grandfather" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Grandfather' ? 'selected' : ''; ?>>Grandfather</option>
                            <option value="Guardian" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                            <option value="Other" <?php echo ($_POST['guardian_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardian_phone">Guardian Phone <span class="required">*</span></label>
                        <input type="tel" id="guardian_phone" name="guardian_phone" required 
                               value="<?php echo htmlspecialchars($_POST['guardian_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="guardian_email">Guardian Email <span class="required">*</span></label>
                        <input type="email" id="guardian_email" name="guardian_email" required 
                               value="<?php echo htmlspecialchars($_POST['guardian_email'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- NEW v2.0: Emergency Contact -->
                <div class="emergency-contact">
                    <h3>🚨 Emergency Contact Information</h3>
                    <p>Please provide emergency contact details for your child.</p>
                    
                    <div class="same-as-guardian">
                        <div class="checkbox-group">
                            <input type="checkbox" id="same_as_guardian" name="same_as_guardian" value="1"
                                   <?php echo isset($_POST['same_as_guardian']) ? 'checked' : ''; ?>>
                            <label for="same_as_guardian">Same as Parent/Guardian above</label>
                        </div>
                        <small style="color: #666;">Check this if your emergency contact is the same person as the parent/guardian.</small>
                    </div>
                    
                    <div id="emergency_fields" class="emergency-fields <?php echo isset($_POST['same_as_guardian']) ? 'disabled' : ''; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Emergency Contact Name <span class="required">*</span></label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>"
                                       <?php echo isset($_POST['same_as_guardian']) ? '' : 'required'; ?>>
                            </div>
                            <div class="form-group">
                                <label for="emergency_contact_relationship">Relationship <span class="required">*</span></label>
                                <select id="emergency_contact_relationship" name="emergency_contact_relationship"
                                        <?php echo isset($_POST['same_as_guardian']) ? '' : 'required'; ?>>
                                    <option value="">Select Relationship</option>
                                    <option value="Mother" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                    <option value="Father" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Father' ? 'selected' : ''; ?>>Father</option>
                                    <option value="Grandmother" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Grandmother' ? 'selected' : ''; ?>>Grandmother</option>
                                    <option value="Grandfather" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Grandfather' ? 'selected' : ''; ?>>Grandfather</option>
                                    <option value="Aunt" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Aunt' ? 'selected' : ''; ?>>Aunt</option>
                                    <option value="Uncle" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Uncle' ? 'selected' : ''; ?>>Uncle</option>
                                    <option value="Family Friend" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Family Friend' ? 'selected' : ''; ?>>Family Friend</option>
                                    <option value="Other" <?php echo ($_POST['emergency_contact_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone <span class="required">*</span></label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                                   value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>"
                                   <?php echo isset($_POST['same_as_guardian']) ? '' : 'required'; ?>>
                        </div>
                    </div>
                </div>
                
                <!-- Why Interested -->
                <h3>💭 Interest</h3>
                <div class="form-group">
                    <label for="why_interested">Why are you interested in this program? <span class="required">*</span></label>
                    <textarea id="why_interested" name="why_interested" rows="4" required 
                              placeholder="Tell us what interests you about this program..."><?php echo htmlspecialchars($_POST['why_interested'] ?? ''); ?></textarea>
                </div>
                
                <!-- Permissions -->
                <h3>✅ Permissions</h3>
                <div class="checkbox-group">
                    <input type="checkbox" id="photo_permission" name="photo_permission" value="1" required>
                    <label for="photo_permission">I give permission for photographs and videos to be used for promotional purposes <span class="required">*</span></label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="data_permission" name="data_permission" value="1" required>
                    <label for="data_permission">I agree to the collection and processing of the provided information <span class="required">*</span></label>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">
                    🚀 Complete Registration
                </button>
                
                <div style="margin-top: 15px; text-align: center;">
                    <a href="holidayProgramIndex.php" style="color: #666; text-decoration: none; font-size: 14px;">
                        ← Back to All Programs
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Set maximum date for date of birth (minimum age 8)
        document.addEventListener('DOMContentLoaded', function() {
            const dobInput = document.getElementById('date_of_birth');
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 8, today.getMonth(), today.getDate());
            dobInput.max = maxDate.toISOString().split('T')[0];
            
            // Prevent selecting same workshop for both choices
            const workshop1 = document.getElementById('workshop_1');
            const workshop2 = document.getElementById('workshop_2');
            
            workshop1.addEventListener('change', function() {
                const selectedValue = this.value;
                const options = workshop2.querySelectorAll('option');
                options.forEach(option => {
                    if (option.value === selectedValue && option.value !== '') {
                        option.style.display = 'none';
                    } else {
                        option.style.display = 'block';
                    }
                });
                
                // Reset workshop 2 if it's the same as workshop 1
                if (workshop2.value === selectedValue) {
                    workshop2.value = '';
                }
            });
            
            // NEW v2.0: Emergency contact "same as guardian" functionality
            const sameAsGuardianCheckbox = document.getElementById('same_as_guardian');
            const emergencyFields = document.getElementById('emergency_fields');
            const emergencyInputs = emergencyFields.querySelectorAll('input, select');
            
            // Guardian fields for copying data
            const guardianName = document.getElementById('guardian_name');
            const guardianRelationship = document.getElementById('guardian_relationship');
            const guardianPhone = document.getElementById('guardian_phone');
            
            // Emergency contact fields
            const emergencyName = document.getElementById('emergency_contact_name');
            const emergencyRelationship = document.getElementById('emergency_contact_relationship');
            const emergencyPhone = document.getElementById('emergency_contact_phone');
            
            function toggleEmergencyFields() {
                if (sameAsGuardianCheckbox.checked) {
                    // Disable emergency fields and copy guardian data
                    emergencyFields.classList.add('disabled');
                    emergencyInputs.forEach(input => {
                        input.removeAttribute('required');
                        input.disabled = true;
                    });
                    
                    // Copy guardian data to emergency fields
                    emergencyName.value = guardianName.value;
                    emergencyRelationship.value = guardianRelationship.value;
                    emergencyPhone.value = guardianPhone.value;
                    
                } else {
                    // Enable emergency fields
                    emergencyFields.classList.remove('disabled');
                    emergencyInputs.forEach(input => {
                        input.setAttribute('required', 'required');
                        input.disabled = false;
                    });
                    
                    // Clear emergency fields if they were copied from guardian
                    if (emergencyName.value === guardianName.value) {
                        emergencyName.value = '';
                        emergencyRelationship.value = '';
                        emergencyPhone.value = '';
                    }
                }
            }
            
            // Listen for changes to "same as guardian" checkbox
            sameAsGuardianCheckbox.addEventListener('change', toggleEmergencyFields);
            
            // Listen for changes to guardian fields to update emergency contact if checked
            function updateEmergencyFromGuardian() {
                if (sameAsGuardianCheckbox.checked) {
                    emergencyName.value = guardianName.value;
                    emergencyRelationship.value = guardianRelationship.value;
                    emergencyPhone.value = guardianPhone.value;
                }
            }
            
            guardianName.addEventListener('input', updateEmergencyFromGuardian);
            guardianRelationship.addEventListener('change', updateEmergencyFromGuardian);
            guardianPhone.addEventListener('input', updateEmergencyFromGuardian);
            
            // Initialize the emergency contact state
            toggleEmergencyFields();
        });

        // Auto-refresh capacity every 30 seconds (optional)
        setTimeout(function() {
            // Only refresh if user hasn't selected anything yet
            const cohortSelect = document.getElementById('cohort_id');
            if (cohortSelect && cohortSelect.value === '') {
                window.location.reload();
            }
        }, 30000); // 30 seconds

        // Show warning if trying to select disabled option
        document.getElementById('cohort_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.disabled) {
                alert('This week is full. Please select an available week.');
                this.value = '';
            }
        });
    </script>
</body>
</html>