<?php
session_start();
require_once '../../../server.php'; // Database connection

// Initialize variables
$formSubmitted = false;
$registrationSuccess = false;
$errorMessage = '';
$userExists = false;
$userData = [];

// Process email check
if (isset($_POST['check_email'])) {
    $email = $_POST['email'];
    
    // Check if user exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userExists = true;
        $userData = $result->fetch_assoc();
        // Return JSON response for AJAX
        echo json_encode([
            'exists' => true,
            'data' => $userData
        ]);
        exit;
    } else {
        // Check if the email exists in holiday_program_attendees table
        $sql = "SELECT * FROM holiday_program_attendees WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            echo json_encode([
                'exists' => true,
                'data' => $userData
            ]);
            exit;
        } else {
            echo json_encode(['exists' => false]);
            exit;
        }
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_registration'])) {
    $formSubmitted = true;
    
    // Check if registering as mentor
    $isMentor = isset($_POST['mentor_registration']) && $_POST['mentor_registration'] == 1;
    
    // Get program ID
    $programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 1;
    
    // Get form data with sanitization
    $firstName = htmlspecialchars(trim($_POST['first_name']));
    $lastName = htmlspecialchars(trim($_POST['last_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $dob = htmlspecialchars(trim($_POST['dob']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    
    // School information - optional for mentors
    $school = $isMentor ? null : (isset($_POST['school']) ? htmlspecialchars(trim($_POST['school'])) : '');
    $grade = $isMentor ? null : (isset($_POST['grade']) ? intval($_POST['grade']) : 0);
    
    // Address information
    $address = htmlspecialchars(trim($_POST['address']));
    $city = htmlspecialchars(trim($_POST['city']));
    $province = htmlspecialchars(trim($_POST['province']));
    $postalCode = htmlspecialchars(trim($_POST['postal_code']));
    
    // Guardian information - optional for mentors
    $guardianName = $isMentor ? null : (isset($_POST['guardian_name']) ? htmlspecialchars(trim($_POST['guardian_name'])) : '');
    $guardianRelationship = $isMentor ? null : (isset($_POST['guardian_relationship']) ? htmlspecialchars(trim($_POST['guardian_relationship'])) : '');
    $guardianPhone = $isMentor ? null : (isset($_POST['guardian_phone']) ? htmlspecialchars(trim($_POST['guardian_phone'])) : '');
    $guardianEmail = $isMentor ? null : (isset($_POST['guardian_email']) ? filter_var(trim($_POST['guardian_email']), FILTER_SANITIZE_EMAIL) : '');
    
    // Emergency contact
    $emergencyContactName = isset($_POST['emergency_contact_name']) ? htmlspecialchars(trim($_POST['emergency_contact_name'])) : '';
    $emergencyContactRelationship = isset($_POST['emergency_contact_relationship']) ? htmlspecialchars(trim($_POST['emergency_contact_relationship'])) : '';
    $emergencyContactPhone = isset($_POST['emergency_contact_phone']) ? htmlspecialchars(trim($_POST['emergency_contact_phone'])) : '';
    
    // Workshop preferences - only for students
    $workshopPreference = $isMentor ? [] : (isset($_POST['workshop_preference']) ? $_POST['workshop_preference'] : []);
    $workshopPreferenceJson = json_encode($workshopPreference);
    
    // Why interested - different handling for mentor vs student
    $whyInterested = $isMentor ? (isset($_POST['mentor_experience']) ? htmlspecialchars(trim($_POST['mentor_experience'])) : '') : 
                               (isset($_POST['why_interested']) ? htmlspecialchars(trim($_POST['why_interested'])) : '');
    
    // Experience level
    $experienceLevel = $isMentor ? 'Advanced' : (isset($_POST['experience_level']) ? htmlspecialchars(trim($_POST['experience_level'])) : '');
    
    // Equipment needs
    $needsEquipment = isset($_POST['needs_equipment']) ? 1 : 0;
    
    // Medical information
    $medicalConditions = isset($_POST['medical_conditions']) ? htmlspecialchars(trim($_POST['medical_conditions'])) : '';
    $allergies = isset($_POST['allergies']) ? htmlspecialchars(trim($_POST['allergies'])) : '';
    
    // Permissions
    $photoPermission = isset($_POST['photo_permission']) ? 1 : 0;
    $dataPermission = isset($_POST['data_permission']) ? 1 : 0;
    
    // Additional information
    $dietaryRestrictions = isset($_POST['dietary_restrictions']) ? htmlspecialchars(trim($_POST['dietary_restrictions'])) : '';
    $additionalNotes = isset($_POST['additional_notes']) ? htmlspecialchars(trim($_POST['additional_notes'])) : '';
    
    // Mentor specific information
    $mentorRegistration = $isMentor ? 1 : 0;
    $mentorStatus = $isMentor ? 'Pending' : NULL;
    $mentorExperience = $isMentor ? (isset($_POST['mentor_experience']) ? htmlspecialchars(trim($_POST['mentor_experience'])) : '') : NULL;
    $mentorAvailability = $isMentor ? (isset($_POST['mentor_availability']) ? htmlspecialchars(trim($_POST['mentor_availability'])) : '') : NULL;
    $mentorWorkshopPreference = $isMentor ? (isset($_POST['mentor_workshop_preference']) ? intval($_POST['mentor_workshop_preference']) : 0) : NULL;
    
    // Check if user exists in the main users table
    $userIdFromMainTable = null;
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userIdFromMainTable = $row['id'];
    }
    
    // Check if the user has already registered for this program
    $sql = "SELECT id FROM holiday_program_attendees WHERE email = ? AND program_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // For the UPDATE statement
    if ($result->num_rows > 0) {
        // Update existing registration
        $row = $result->fetch_assoc();
        $attendeeId = $row['id'];
        
        $sql = "UPDATE holiday_program_attendees SET 
                first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, 
                gender = ?, school = ?, grade = ?, address = ?, city = ?, 
                province = ?, postal_code = ?, guardian_name = ?, 
                guardian_relationship = ?, guardian_phone = ?, guardian_email = ?,
                emergency_contact_name = ?, emergency_contact_relationship = ?, 
                emergency_contact_phone = ?, workshop_preference = ?, 
                why_interested = ?, experience_level = ?, needs_equipment = ?,
                medical_conditions = ?, allergies = ?, photo_permission = ?,
                data_permission = ?, dietary_restrictions = ?, additional_notes = ?, 
                mentor_registration = ?, mentor_status = ?, mentor_workshop_preference = ?
                WHERE id = ?";
        
        // Count the placeholders to verify
        $placeholders_count = substr_count($sql, '?');
        
        // Define all parameters in an array (in the exact order they appear in the SQL)
        $params = [
            $firstName, $lastName, $phone, $dob, $gender, $school, $grade, 
            $address, $city, $province, $postalCode, $guardianName, 
            $guardianRelationship, $guardianPhone, $guardianEmail, 
            $emergencyContactName, $emergencyContactRelationship, 
            $emergencyContactPhone, $workshopPreferenceJson, $whyInterested, 
            $experienceLevel, $needsEquipment, $medicalConditions, $allergies,
            $photoPermission, $dataPermission, $dietaryRestrictions, 
            $additionalNotes, $mentorRegistration, $mentorStatus, $mentorWorkshopPreference, 
            $attendeeId
        ];
        
        // Make sure we have the right number of parameters
        if (count($params) !== $placeholders_count) {
            die("Parameter count (" . count($params) . ") doesn't match placeholder count (" . $placeholders_count . ")");
        }
        
        // Build type string dynamically based on parameter types
        $types = '';
        foreach ($params as $param) {
            if (is_int($param) || is_bool($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's'; // Default to string for everything else
            }
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $errorMessage = "Error preparing statement: " . $conn->error;
            error_log($errorMessage);
        } else {
            // Use the spread operator to bind all parameters
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $registrationSuccess = true;
            } else {
                $errorMessage = "Error updating registration: " . $stmt->error;
                error_log($errorMessage);
            }
        }
    } else {
        // Insert new registration
        $sql = "INSERT INTO holiday_program_attendees (
                program_id, user_id, first_name, last_name, email, phone, 
                date_of_birth, gender, school, grade, address, city, province, 
                postal_code, guardian_name, guardian_relationship, guardian_phone, 
                guardian_email, emergency_contact_name, emergency_contact_relationship, 
                emergency_contact_phone, workshop_preference, why_interested, 
                experience_level, needs_equipment, medical_conditions, allergies, 
                photo_permission, data_permission, dietary_restrictions, additional_notes,
                mentor_registration, mentor_status, mentor_workshop_preference
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
        
        // Count the placeholders to verify
        $placeholders_count = substr_count($sql, '?');
        
        // Define all parameters in an array (in the exact order they appear in the SQL)
        $params = [
            $programId, $userIdFromMainTable, $firstName, $lastName, $email, $phone, 
            $dob, $gender, $school, $grade, $address, $city, $province, 
            $postalCode, $guardianName, $guardianRelationship, $guardianPhone, 
            $guardianEmail, $emergencyContactName, $emergencyContactRelationship, 
            $emergencyContactPhone, $workshopPreferenceJson, $whyInterested, 
            $experienceLevel, $needsEquipment, $medicalConditions, $allergies,
            $photoPermission, $dataPermission, $dietaryRestrictions, $additionalNotes,
            $mentorRegistration, $mentorStatus, $mentorWorkshopPreference
        ];
        
        // Make sure we have the right number of parameters
        if (count($params) !== $placeholders_count) {
            die("Parameter count (" . count($params) . ") doesn't match placeholder count (" . $placeholders_count . ")");
        }
        
        // Build type string dynamically based on parameter types
        $types = '';
        foreach ($params as $param) {
            if (is_int($param) || is_bool($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's'; // Default to string for everything else
            }
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $errorMessage = "Error preparing statement: " . $conn->error;
            error_log($errorMessage);
        } else {
            // Use the spread operator to bind all parameters
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $registrationSuccess = true;
                $attendeeId = $conn->insert_id;
            } else {
                $errorMessage = "Error creating registration: " . $stmt->error;
                error_log($errorMessage);
            }
        }
    }

    // After successful insert or update to holiday_program_attendees
    if ($registrationSuccess && $mentorRegistration) {
        // Get the attendee ID (either from existing record or newly inserted)
        $attendeeId = isset($attendeeId) ? $attendeeId : $conn->insert_id;
        
        // Check if mentor details already exist
        $sql = "SELECT id FROM holiday_program_mentor_details WHERE attendee_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $attendeeId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing mentor details
                $row = $result->fetch_assoc();
                $mentorDetailsId = $row['id'];
                
                $sql = "UPDATE holiday_program_mentor_details SET 
                        experience = ?, availability = ?, workshop_preference = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssii", $mentorExperience, $mentorAvailability, $mentorWorkshopPreference, $mentorDetailsId);
                    $stmt->execute();
                }
            } else {
                // Insert new mentor details
                $sql = "INSERT INTO holiday_program_mentor_details 
                        (attendee_id, experience, availability, workshop_preference) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("issi", $attendeeId, $mentorExperience, $mentorAvailability, $mentorWorkshopPreference);
                    $stmt->execute();
                }
            }
        }
    }
}

// Get program details
$programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 1;
$programDetails = [];

$sql = "SELECT * FROM holiday_programs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $programId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $programDetails = $result->fetch_assoc();
} else {
    // Default program details if not found
    $programDetails = [
        'id' => 1,
        'term' => 'Term 1',
        'title' => 'Multi-Media - Digital Design',
        'dates' => 'March 29 - April 7, 2025',
        'description' => 'Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.'
    ];
}

$programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 1;


// Function to get workshop enrollment counts
function getWorkshopEnrollmentCounts($conn, $programId) {
    $counts = [];
    
    // First get all workshops for this program
    $sql = "SELECT id, title, max_participants 
            FROM holiday_program_workshops
            WHERE program_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $counts[$row['id']] = [
            'title' => $row['title'],
            'max' => $row['max_participants'],
            'enrolled' => 0 // Default to 0, will update below
        ];
    }
    
    // Now get attendance preferences counts from holiday_program_attendees
    $sqlPrefs = "SELECT workshop_preference
                 FROM holiday_program_attendees 
                 WHERE program_id = ? AND mentor_registration = 0";
    
    $stmtPrefs = $conn->prepare($sqlPrefs);
    $stmtPrefs->bind_param("i", $programId);
    $stmtPrefs->execute();
    $resultPrefs = $stmtPrefs->get_result();
    
    while ($row = $resultPrefs->fetch_assoc()) {
        if (empty($row['workshop_preference'])) {
            continue;
        }
        
        $preferences = json_decode($row['workshop_preference'], true);
        
        if (!is_array($preferences) || empty($preferences)) {
            continue;
        }
        
        // Get the first preference (highest priority)
        $topPreference = is_array($preferences[0]) ? $preferences[0]['id'] : $preferences[0];
        
        // Increment the count if this workshop exists in our counts array
        if (isset($counts[$topPreference])) {
            $counts[$topPreference]['enrolled']++;
        }
    }
    
    return $counts;
}

// Get workshop enrollment counts
$workshopEnrollmentCounts = getWorkshopEnrollmentCounts($conn, $programId);


// Get workshop options for this program
$workshops = [];
$sql = "SELECT * FROM holiday_program_workshops WHERE program_id = ? ORDER BY title";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $programId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $workshops[] = $row;
    }
} else {
    // Default workshops if none found
    $workshops = [
        ['id' => 1, 'title' => 'Graphic Design Basics', 'description' => 'Learn the fundamentals of graphic design using industry tools.'],
        ['id' => 2, 'title' => 'Video Editing', 'description' => 'Create and edit videos using professional techniques.'],
        ['id' => 3, 'title' => 'Animation Fundamentals', 'description' => 'Explore the principles of animation and create your own animated shorts.'],
        ['id' => 4, 'title' => 'Digital Photography', 'description' => 'Master digital photography techniques and photo editing.']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Program Registration - Sci-Bono Clubhouse</title>
    <!-- <link rel="stylesheet" href="../../public/assets/css/holidayProgramStyles.css"> -->
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery for form functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

    <?php include './holidayPrograms-header.php'; ?>
    
    <main class="registration-container">
        <div class="registration-header">
            <h1>Registration: <?php echo htmlspecialchars($programDetails['title']); ?></h1>
            <p class="program-dates"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($programDetails['dates']); ?></p>
            <p class="program-description"><?php echo htmlspecialchars($programDetails['description']); ?></p>
        </div>
        
        <?php if ($formSubmitted && $registrationSuccess): ?>
            <div class="success-message">
                <div class="success-content">
                    <i class="fas fa-check-circle"></i>
                    <?php if (isset($_POST['mentor_registration']) && $_POST['mentor_registration'] == 1): ?>
                        <h2>Mentor Registration Submitted!</h2>
                        <p>Thank you for registering as a mentor for the <?php echo htmlspecialchars($programDetails['title']); ?> holiday program. We've sent a confirmation email to <?php echo htmlspecialchars($email); ?>. Your application will be reviewed by our program coordinator.</p>
                        <div class="next-steps">
                            <h3>Next Steps:</h3>
                            <ul>
                                <li>Check your email for confirmation and additional information</li>
                                <li>You will be notified about the status of your application within 5 business days</li>
                                <li>If approved, you'll receive details about mentor training and workshop preparation</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <h2>Registration Successful!</h2>
                        <p>Thank you for registering for the <?php echo htmlspecialchars($programDetails['title']); ?> holiday program. We've sent a confirmation email to <?php echo htmlspecialchars($email); ?> with all the details.</p>
                        <div class="next-steps">
                            <h3>Next Steps:</h3>
                            <ul>
                                <li>Check your email for confirmation and additional information</li>
                                <li>Mark your calendar for <?php echo htmlspecialchars($programDetails['dates']); ?></li>
                                <li>Prepare any materials or equipment mentioned in the email</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="action-buttons">
                        <a href="holiday-dashboard.php" class="primary-button">Go to Dashboard</a>
                        <a href="holidayProgramIndex.php" class="secondary-button">Back to Programs</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="error-message">
            <p><i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="registration-form-container">
            <div class="email-check-container">
                <h2>Already registered or a Clubhouse member?</h2>
                <p>Enter your email to quickly fill out the form:</p>
                <div class="email-check-form">
                    <input type="email" id="check-email" placeholder="Enter your email address" class="form-input">
                    <button id="check-email-btn" class="primary-button">Check Email</button>
                </div>
                <div id="email-check-result" class="hidden">
                    <p class="success-text">Email found! Form will be pre-filled with your information.</p>
                </div>
            </div>

            <form method="POST" action="" class="registration-form" id="registration-form" novalidate>

            <div class="form-section">
                <h2><i class="fas fa-chalkboard-teacher"></i> Mentor Registration</h2>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="mentor_registration" name="mentor_registration" value="1">
                        <label for="mentor_registration">I would like to register as a mentor for this program</label>
                    </div>
                </div>
                
                <div id="mentor_fields" style="display: none;">
                    <div class="form-group">
                        <label for="mentor_experience">Please describe your experience relevant to this program: <span class="required">*</span></label>
                        <textarea id="mentor_experience" name="mentor_experience" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="mentor_availability">Please indicate your availability during the program dates: <span class="required">*</span></label>
                        <select id="mentor_availability" name="mentor_availability" class="form-select">
                            <option value="">Select Availability</option>
                            <option value="full_time">Full time (all program days)</option>
                            <option value="part_time">Part time (specific days only)</option>
                            <option value="specific_hours">Specific hours each day</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mentor_workshop_preference">Which workshop would you prefer to mentor? <span class="required">*</span></label>
                        <select id="mentor_workshop_preference" name="mentor_workshop_preference" class="form-select">
                            <option value="">Select Workshop</option>
                            <?php foreach ($workshops as $workshop): ?>
                                <option value="<?php echo $workshop['id']; ?>"><?php echo htmlspecialchars($workshop['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>            

                <div class="form-section">
                    <h2><i class="fas fa-user"></i> Personal Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dob">Date of Birth <span class="required">*</span></label>
                            <input type="date" id="dob" name="dob" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div id="school_section" class="form-section">
                    <h2><i class="fas fa-school"></i> School Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="school">School Name <span class="required">*</span></label>
                            <input type="text" id="school" name="school" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="grade">Grade <span class="required">*</span></label>
                            <select id="grade" name="grade" class="form-select" required>
                                <option value="">Select Grade</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                
                <div class="form-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Contact Information</h2>
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" class="form-input" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="province">Province <span class="required">*</span></label>
                            <select id="province" name="province" class="form-select" required>
                                <option value="">Select Province</option>
                                <option value="Eastern Cape">Eastern Cape</option>
                                <option value="Free State">Free State</option>
                                <option value="Gauteng">Gauteng</option>
                                <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                <option value="Limpopo">Limpopo</option>
                                <option value="Mpumalanga">Mpumalanga</option>
                                <option value="North West">North West</option>
                                <option value="Northern Cape">Northern Cape</option>
                                <option value="Western Cape">Western Cape</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Postal Code <span class="required">*</span></label>
                        <input type="text" id="postal_code" name="postal_code" class="form-input" required>
                    </div>
                </div>
                
                <div id="guardian_section" class="form-section">
                    <h2><i class="fas fa-user-shield"></i> Parent/Guardian Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="guardian_name">Parent/Guardian Name <span class="required">*</span></label>
                            <input type="text" id="guardian_name" name="guardian_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="guardian_relationship">Relationship <span class="required">*</span></label>
                            <input type="text" id="guardian_relationship" name="guardian_relationship" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="guardian_phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="guardian_phone" name="guardian_phone" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="guardian_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="guardian_email" name="guardian_email" class="form-input" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-first-aid"></i> Emergency Contact</h2>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="same_as_guardian" name="same_as_guardian">
                            <label for="same_as_guardian">Same as Parent/Guardian</label>
                        </div>
                    </div>
                    
                    <div id="emergency_contact_fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Name <span class="required">*</span></label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="emergency_contact_relationship">Relationship <span class="required">*</span></label>
                                <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-input" required>
                        </div>
                    </div>
                </div>
                
                <script>
                // Workshop capacity data
                const workshopCapacityData = <?php echo json_encode($workshopEnrollmentCounts); ?>;
                </script>

                <!-- Replace the existing workshop preferences section with this -->
                <div id="workshop_preferences_section" class="form-section">
                    <h2><i class="fas fa-laptop-code"></i> Workshop Preferences</h2>
                    <p class="section-description">Please select your 1st and 2nd choice workshops (you can only select 2 workshops)</p>
                    
                    <div class="selection-info" id="selection-info" style="display: none;">
                        <p>Selected workshops are prioritized in the order selected. Your first selection will be considered your preferred choice.</p>
                        <p>Click a selected workshop to deselect it if you want to change your preferences.</p>
                    </div>
                    
                    <div class="reorder-controls" style="display: none;">
                        <button type="button" id="swap-preferences" class="secondary-button">Swap Preferences</button>
                    </div>
                    
                    <div class="workshop-options">
                        <?php foreach ($workshops as $workshop): 
                            $capacity = $workshopEnrollmentCounts[$workshop['id']] ?? null;
                            $isFull = $capacity && $capacity['enrolled'] >= $capacity['max'];
                            $capacityPercentage = $capacity ? ($capacity['enrolled'] / $capacity['max']) * 100 : 0;
                            $workshopClass = $isFull ? 'workshop-option full-workshop' : 'workshop-option';
                        ?>
                            <div class="<?php echo $workshopClass; ?>">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="workshop_<?php echo $workshop['id']; ?>" 
                                        name="workshop_preference[]" value="<?php echo $workshop['id']; ?>"
                                        <?php echo $isFull ? 'disabled' : ''; ?>>
                                    <label for="workshop_<?php echo $workshop['id']; ?>"><?php echo htmlspecialchars($workshop['title']); ?></label>
                                    <span class="selection-label"></span>
                                </div>
                                <p class="workshop-description"><?php echo htmlspecialchars($workshop['description']); ?></p>
                                
                                <?php if ($capacity): ?>
                                <div class="capacity-indicator">
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?php echo min($capacityPercentage, 100); ?>%;"></div>
                                    </div>
                                    <div class="capacity-text">
                                        <?php echo $capacity['enrolled']; ?>/<?php echo $capacity['max']; ?> spots filled
                                        <?php if ($isFull): ?>
                                            <span class="capacity-full">FULL</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <input type="hidden" id="workshop_preferences_ordered" name="workshop_preferences_ordered">
                    </div>
                    
                    <div id="workshop_note" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label for="why_interested">Why are you interested in this holiday program? <span class="required">*</span></label>
                        <textarea id="why_interested" name="why_interested" class="form-textarea" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience_level">What is your experience level with digital media and design? <span class="required">*</span></label>
                        <select id="experience_level" name="experience_level" class="form-select" required>
                            <option value="">Select Experience Level</option>
                            <option value="Beginner">Beginner - No experience</option>
                            <option value="Basic">Basic - Some experience but limited skills</option>
                            <option value="Intermediate">Intermediate - Familiar with basic concepts and tools</option>
                            <option value="Advanced">Advanced - Experienced with various digital media tools</option>
                        </select>
                    </div>
                </div>

                <!-- Enhance mentor fields section -->
                <div id="mentor_fields" style="display: none;">
                    <div class="form-group">
                        <label for="mentor_experience">Please describe your experience relevant to this program: <span class="required">*</span></label>
                        <textarea id="mentor_experience" name="mentor_experience" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="mentor_availability">Please indicate your availability during the program dates: <span class="required">*</span></label>
                        <select id="mentor_availability" name="mentor_availability" class="form-select">
                            <option value="">Select Availability</option>
                            <option value="full_time">Full time (all program days)</option>
                            <option value="part_time">Part time (specific days only)</option>
                            <option value="specific_hours">Specific hours each day</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mentor_workshop_preference">Which workshop would you prefer to mentor? <span class="required">*</span></label>
                        <select id="mentor_workshop_preference" name="mentor_workshop_preference" class="form-select">
                            <option value="">Select Workshop</option>
                            <?php foreach ($workshops as $workshop): 
                                $capacity = $workshopEnrollmentCounts[$workshop['id']] ?? null;
                                $isFull = $capacity && $capacity['enrolled'] >= $capacity['max'];
                                $fullText = $isFull ? ' (FULL)' : '';
                                $enrollmentText = $capacity ? " ({$capacity['enrolled']}/{$capacity['max']})" : "";
                            ?>
                                <option value="<?php echo $workshop['id']; ?>"><?php echo htmlspecialchars($workshop['title'] . $enrollmentText . $fullText); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="workshop-capacity-info">
                        <h4>Current Workshop Enrollments:</h4>
                        <?php foreach ($workshops as $workshop): 
                            $capacity = $workshopEnrollmentCounts[$workshop['id']] ?? null;
                            if ($capacity):
                                $capacityPercentage = ($capacity['enrolled'] / $capacity['max']) * 100;
                        ?>
                        <div class="workshop-capacity-row">
                            <div class="workshop-name"><?php echo htmlspecialchars($workshop['title']); ?></div>
                            <div class="workshop-capacity-bar">
                                <div class="workshop-capacity-fill" style="width: <?php echo min($capacityPercentage, 100); ?>%;"></div>
                            </div>
                            <div class="workshop-capacity-text"><?php echo $capacity['enrolled']; ?>/<?php echo $capacity['max']; ?></div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-notes-medical"></i> Medical Information</h2>
                    <div class="form-group">
                        <label for="medical_conditions">Do you have any medical conditions we should be aware of?</label>
                        <textarea id="medical_conditions" name="medical_conditions" class="form-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergies">Do you have any allergies?</label>
                        <textarea id="allergies" name="allergies" class="form-textarea" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-check-circle"></i> Permissions</h2>
                    <div class="form-group">
                        <div class="checkbox-group required-checkbox">
                            <input type="checkbox" id="photo_permission" name="photo_permission" required>
                            <label for="photo_permission">I give permission for photographs and videos of the participant to be used for promotional purposes <span class="required">*</span></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group required-checkbox">
                            <input type="checkbox" id="data_permission" name="data_permission" required>
                            <label for="data_permission">I agree to the collection and processing of the provided information for the purposes of the holiday program <span class="required">*</span></label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Additional Information</h2>
                    <div class="form-group">
                        <label for="dietary_restrictions">Do you have any dietary restrictions or preferences?</label>
                        <textarea id="dietary_restrictions" name="dietary_restrictions" class="form-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_notes">Any additional information you would like to share?</label>
                        <textarea id="additional_notes" name="additional_notes" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_registration" class="primary-button">Complete Registration</button>
                    <a href="holidayProgramIndex.php" class="secondary-button">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <!-- Mobile Navigation (visible on mobile only) -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="holidayProgramIndex.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span>Programs</span>
        </a>
        <a href="../../app/Views/learn.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-book"></i>
            </div>
            <span>Learn</span>
        </a>
        <a href="holiday-dashboard.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-user"></i>
            </div>
            <span>Account</span>
        </a>
    </nav>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../../public/assets/js/workshopSelection.js"></script>

    <script>
    $(document).ready(function() {
        // Trigger the change event to set proper initial state
        $('#mentor_registration').trigger('change');
        
        // Email check functionality
        $('#check-email-btn').click(function(e) {
            e.preventDefault();
            const email = $('#check-email').val();
            
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            // Show loading indicator
                            $(this).html('<i class="fas fa-spinner fa-spin"></i> Checking...');
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    check_email: true,
                    email: email
                },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        $('#email-check-result').removeClass('hidden').addClass('success');
                        $('#email-check-result p').text('Email found! Form will be pre-filled with your information.');
                        
                        // Fill the form with user data
                        const data = response.data;
                        $('#first_name').val(data.first_name || data.name);
                        $('#last_name').val(data.last_name || data.surname);
                        $('#email').val(data.email);
                        $('#phone').val(data.phone || data.leaner_number);
                        $('#dob').val(data.date_of_birth);
                        $('#gender').val(data.gender || data.Gender);
                        $('#school').val(data.school);
                        $('#grade').val(data.grade);
                        $('#address').val(data.address || data.address_street);
                        $('#city').val(data.city || data.address_city);
                        $('#province').val(data.province || data.address_province);
                        $('#postal_code').val(data.postal_code || data.address_postal_code);
                        
                        // Parent/Guardian information
                        $('#guardian_name').val(data.guardian_name || data.parent);
                        $('#guardian_relationship').val(data.guardian_relationship || data.Relationship);
                        $('#guardian_phone').val(data.guardian_phone || data.parent_number);
                        $('#guardian_email').val(data.guardian_email || data.parent_email);
                        
                        // Emergency contact information
                        $('#emergency_contact_name').val(data.emergency_contact_name || '');
                        $('#emergency_contact_relationship').val(data.emergency_contact_relationship || '');
                        $('#emergency_contact_phone').val(data.emergency_contact_phone || '');
                        
                        // Medical information and additional details if available
                        if (data.medical_conditions) $('#medical_conditions').val(data.medical_conditions);
                        if (data.allergies) $('#allergies').val(data.allergies);
                        if (data.dietary_restrictions) $('#dietary_restrictions').val(data.dietary_restrictions);
                        if (data.additional_notes) $('#additional_notes').val(data.additional_notes);
                        
                        // Workshop preferences - this would need to be handled specially for existing registrations
                        if (data.workshop_preference) {
                            try {
                                const workshops = JSON.parse(data.workshop_preference);
                                workshops.forEach(workshopId => {
                                    $(`#workshop_${workshopId}`).prop('checked', true);
                                });
                            } catch (e) {
                                console.error('Error parsing workshop preferences:', e);
                            }
                        }
                        
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $("#registration-form").offset().top - 100
                        }, 500);
                    } else {
                        $('#email-check-result').removeClass('hidden').removeClass('success');
                        $('#email-check-result p').text('Email not found. Please fill out the form below.');
                    }
                },
                error: function() {
                    $('#email-check-result').removeClass('hidden').removeClass('success');
                    $('#email-check-result p').text('Error checking email. Please try again or fill out the form manually.');
                },
                complete: function() {
                    $('#check-email-btn').html('Check Email');
                }
            });
        });
        
        // Same as guardian checkbox
        $('#same_as_guardian').change(function() {
            if ($(this).is(':checked')) {
                // Copy parent/guardian info to emergency contact
                $('#emergency_contact_name').val($('#guardian_name').val());
                $('#emergency_contact_relationship').val($('#guardian_relationship').val());
                $('#emergency_contact_phone').val($('#guardian_phone').val());
                
                // Disable emergency contact fields
                $('#emergency_contact_fields input').prop('disabled', true);
            } else {
                // Enable emergency contact fields
                $('#emergency_contact_fields input').prop('disabled', false);
            }
        });

        $('#mentor_registration').change(function() {
            if ($(this).is(':checked')) {
                $('#mentor_fields').slideDown();

            // Make mentor fields required
            $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', true);
            } else {
            $('#mentor_fields').slideUp();
            // Remove required attribute
            $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', false);
                }
        });

        // Check initial state of mentor checkbox
        if ($('#mentor_registration').is(':checked')) {
            //Show mentor-specific fields
            $('#mentor_fields').show();
            $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', true);
            
            // Hide student-specific sections
            $('#school_section').hide();
            $('#guardian_section').hide();
            $('#workshop_preferences_section').hide();
            
            // Remove required attributes from student-specific fields
            $('#school, #grade').prop('required', false);
            $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
            $('input[name="workshop_preference[]"]').prop('required', false);
            
            // Add a note about mentor workshop assignment
            $('#workshop_note').html('<div class="info-message"><i class="fas fa-info-circle"></i> As a mentor, you will be assigned to a specific workshop based on program needs and your expertise.</div>').show();
        }

        $('#mentor_registration').change(function() {
            if ($(this).is(':checked')) {
                // Hide student-specific sections
                $('#school_section').hide();
                $('#guardian_section').hide();
                $('#workshop_preferences_section').hide();
                
                // Remove required attributes from ALL student-specific fields
                $('#school, #grade').prop('required', false);
                $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
                
                // Also remove required from the workshop section fields
                $('#why_interested, #experience_level').prop('required', false);
                $('input[name="workshop_preference[]"]').prop('required', false);
                
                // Show mentor-specific fields
                $('#mentor_fields').show();
                $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', true);
                
                // Add note about mentor workshop assignment
                $('#workshop_note').html('<div class="info-message"><i class="fas fa-info-circle"></i> As a mentor, you will be assigned to a specific workshop based on program needs and your expertise.</div>').show();
            } else {
                // Show student-specific sections
                $('#school_section').show();
                $('#guardian_section').show();
                $('#workshop_preferences_section').show();
                $('#workshop_note').hide();
                
                // Hide mentor-specific fields
                $('#mentor_fields').hide();
                $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', false);
                
                // Make student fields required again
                $('#school, #grade').prop('required', true);
                $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', true);
                $('#why_interested, #experience_level').prop('required', true);
                // Note: We don't make workshop checkboxes required here as that's handled separately
            }
        });
                
        // Form validation
        $('#registration-form').submit(function(e) {
            // First check if this is a mentor registration
            const isMentor = $('#mentor_registration').is(':checked');

            // Only validate workshop selection for non-mentors
            if (!isMentor && $('input[name="workshop_preference[]"]:checked').length === 0) {
                e.preventDefault();
                alert('Please select at least one workshop preference');
                return false;
            }
            
            // Check required checkboxes
            if (!$('#photo_permission').is(':checked') || !$('#data_permission').is(':checked')) {
                e.preventDefault();
                alert('Please agree to the required permissions');
                return false;
            }

            // Mentor registration toggle functionality
            $('#mentor_registration').change(function() {
                if ($(this).is(':checked')) {
                    // Show mentor-specific fields
                    $('#mentor_fields').slideDown();
                    $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', true);
                    
                    // Hide student-specific sections
                    $('#school_section').slideUp();
                    $('#guardian_section').slideUp();
                    $('#workshop_preferences_section').slideUp();
                    
                    // Remove required attributes from student-specific fields
                    $('#school, #grade').prop('required', false);
                    $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
                    $('input[name="workshop_preference[]"]').prop('required', false);
                    
                    // Add a note about mentor workshop assignment
                    $('#workshop_note').html('<div class="info-message"><i class="fas fa-info-circle"></i> As a mentor, you will be assigned to a specific workshop based on program needs and your expertise.</div>').slideDown();
                } else {
                    // Hide mentor-specific fields
                    $('#mentor_fields').slideUp();
                    $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', false);
                    
                    // Show student-specific sections
                    $('#school_section').slideDown();
                    $('#guardian_section').slideDown();
                    $('#workshop_preferences_section').slideDown();
                    $('#workshop_note').slideUp();
                    
                    // Re-add required attributes to student-specific fields
                    $('#school, #grade').prop('required', true);
                    $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', true);
                }
            });

            // Validate mentor fields if mentor registration
            if (isMentor) {
                if (!$('#mentor_experience').val().trim() || 
                    !$('#mentor_availability').val() || 
                    !$('#mentor_workshop_preference').val()) {
                    e.preventDefault();
                    alert('Please complete all required mentor fields');
                    return false;
                }
            }

            // Age validation - only apply to non-mentors
            if (!isMentor) {
                const dobValue = $('#dob').val();
                if (dobValue) {
                    const dob = new Date(dobValue);
                    const today = new Date();
                    
                    // Calculate age
                    let age = today.getFullYear() - dob.getFullYear();
                    const monthDiff = today.getMonth() - dob.getMonth();
                    
                    // Adjust age if birthday hasn't occurred yet this year
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    
                    // Check program age restrictions
                    const minAge = 13; // Minimum age
                    const maxAge = 18; // Maximum age
                    
                    if (age < minAge || age > maxAge) {
                        e.preventDefault();
                        
                        // Custom error message based on age
                        let errorMessage = `You must be between ${minAge}-${maxAge} years old to register for this program.`;
                        if (age < minAge) {
                            errorMessage = `You must be at least ${minAge} years old to register for this program.`;
                        } else if (age > maxAge) {
                            errorMessage = `You must be ${maxAge} years old or younger to register for this program.`;
                        }
                        
                        // Show error message
                        $('.error-message').remove(); // Remove any existing error messages
                        $('<div class="error-message"><p><i class="fas fa-exclamation-circle"></i> ' + errorMessage + '</p></div>')
                            .insertBefore('#registration-form')
                            .hide()
                            .fadeIn(300);
                        
                        // Highlight the field and scroll to it
                        $('#dob').addClass('error-input');
                        $('html, body').animate({
                            scrollTop: $('#dob').offset().top - 100
                        }, 500);
                        
                        return false;
                    } else {
                        $('#dob').removeClass('error-input');
                    }
                }
            }

             // Age validation (13-18 years old)
            // const dobValue = $('#dob').val();
            // if (dobValue) {
            //     const dob = new Date(dobValue);
            //     const today = new Date();
                
            //     // Calculate age
            //     let age = today.getFullYear() - dob.getFullYear();
            //     const monthDiff = today.getMonth() - dob.getMonth();
                
            //     // Adjust age if birthday hasn't occurred yet this year
            //     if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            //         age--;
            //     }
                
            //     // Check if age is within the required range (13-18)
            //     if (age < 13 || age > 18) {
            //         e.preventDefault();
                    
            //         // Custom error message based on age
            //         let errorMessage = 'You must be between 13-18 years old to register for this program.';
            //         if (age < 13) {
            //             errorMessage = 'You must be at least 13 years old to register for this program.';
            //         } else if (age > 18) {
            //             errorMessage = 'You must be 18 years old or younger to register for this program.';
            //         }
                    
            //         // Show error message
            //         $('<div class="error-message"><p><i class="fas fa-exclamation-circle"></i> ' + errorMessage + '</p></div>')
            //             .insertBefore('#registration-form')
            //             .hide()
            //             .fadeIn(300);
                    
            //         // Highlight the field and scroll to it
            //         $('#dob').addClass('error-input');
            //         $('html, body').animate({
            //             scrollTop: $('#dob').offset().top - 100
            //         }, 500);
                    
            //         return false;
            //     } else {
            //         $('#dob').removeClass('error-input');
            //     }
            // }


            
            return true;
        });
    });
    </script>
</body>
</html>