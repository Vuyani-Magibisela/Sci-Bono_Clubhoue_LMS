<?php
session_start();
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../core/CSRF.php';
require_once __DIR__ . '/../../../server.php';
require_once __DIR__ . '/../../Models/holiday-program-functions.php';

// Function to get program configuration and structure
function getProgramConfiguration($conn, $programId) {
    $sql = "SELECT 
                p.*,
                JSON_EXTRACT(p.program_structure, '$.duration_weeks') as duration_weeks,
                JSON_EXTRACT(p.program_structure, '$.cohort_system') as has_cohorts,
                JSON_EXTRACT(p.program_structure, '$.prerequisites_enabled') as has_prerequisites
            FROM holiday_programs p 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Function to get available cohorts for program
function getAvailableCohorts($conn, $programId) {
    $sql = "SELECT 
                c.*,
                (c.max_participants - c.current_participants) as available_spots
            FROM holiday_program_cohorts c
            WHERE c.program_id = ? 
            AND c.status = 'active'
            AND c.start_date > CURDATE()
            ORDER BY c.start_date";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cohorts = [];
    while ($row = $result->fetch_assoc()) {
        $cohorts[] = $row;
    }
    return $cohorts;
}

// Function to get workshops with prerequisites and cohort information
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

// Function to get workshop enrollment counts
function getWorkshopEnrollmentCounts($conn, $programId) {
    $counts = [];
    
    $sql = "SELECT id, title, max_participants FROM holiday_program_workshops WHERE program_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $counts[$row['id']] = [
                'title' => $row['title'],
                'max' => $row['max_participants'],
                'enrolled' => 0  // Simplified for now
            ];
        }
    }
    
    return $counts;
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function isValidPhone($phone) {
    return preg_match('/^[\d\s\-\+\(\)]{10,15}$/', $phone);
}


// Get program ID from URL
$programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 1;

// Check for mentor registration parameter
$defaultMentorRegistration = isset($_GET['mentor']) && $_GET['mentor'] == '1';

// Initialize variables
$formSubmitted = false;
$registrationSuccess = false;
$errorMessage = '';
$userExists = false;
$userData = [];

// Process email check FIRST (before other processing)
if (isset($_POST['check_email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!isValidEmail($email)) {
        echo json_encode(['exists' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check if user exists in the users table
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['exists' => false, 'error' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userExists = true;
        $userData = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'data' => $userData
        ]);
        exit;
    } else {
        // Check if the email exists in holiday_program_attendees table
        $sql = "SELECT * FROM holiday_program_attendees WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['exists' => false, 'error' => 'Database error']);
            exit;
        }
        
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

// Get program configuration
$programConfig = getProgramConfiguration($conn, $programId);
if (!$programConfig) {
    die("Program not found or database error.");
}

$cohorts = getAvailableCohorts($conn, $programId);
$workshops = getWorkshopsWithPrerequisites($conn, $programId);
$workshopEnrollmentCounts = getWorkshopEnrollmentCounts($conn, $programId);

// Default to basic workshops if none configured
if (empty($workshops)) {
    $workshops = [
        [
            'id' => 1, 
            'title' => 'Graphic Design Basics', 
            'description' => 'Learn the fundamentals of graphic design using industry tools.',
            'prerequisites' => 'Basic computer skills, Age 13+',
            'max_participants' => 15
        ],
        [
            'id' => 2, 
            'title' => 'Video Editing', 
            'description' => 'Create and edit videos using professional techniques.',
            'prerequisites' => 'Intermediate computer skills, Age 15+',
            'max_participants' => 12
        ],
        [
            'id' => 3, 
            'title' => 'Animation Fundamentals', 
            'description' => 'Explore the principles of animation and create your own animated shorts.',
            'prerequisites' => 'Creative mindset, Age 12+',
            'max_participants' => 10
        ]
    ];
}

// Get program details
$sql = "SELECT * FROM holiday_programs WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $programId);
$stmt->execute();
$result = $stmt->get_result();
$programDetails = $result->fetch_assoc();

if (!$programDetails) {
    die("Program not found.");
}

// Check if registration is open
$registrationClosed = !$programDetails['registration_open'];

// Process form submission for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration'])) {
    // Validate CSRF token
    if (!CSRF::validateToken()) {
        $errorMessage = 'Invalid security token. Please refresh the page and try again.';
        $formSubmitted = true;
        $registrationSuccess = false;
        error_log("CSRF validation failed for program registration: IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        goto end_processing;
    }

    $formSubmitted = true;
    
    // Collect and sanitize form data
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = sanitizeInput($_POST['phone']);
    $dob = $_POST['date_of_birth'];
    $gender = sanitizeInput($_POST['gender']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $province = sanitizeInput($_POST['province']);
    $postalCode = sanitizeInput($_POST['postal_code']);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        $errorMessage = "Please fill in all required fields.";
        goto end_processing;
    }
    
    if (!isValidEmail($email)) {
        $errorMessage = "Please enter a valid email address.";
        goto end_processing;
    }
    
    if (!isValidPhone($phone)) {
        $errorMessage = "Please enter a valid phone number.";
        goto end_processing;
    }
    
    // DUPLICATE REGISTRATION CHECK
    $duplicateCheckSql = "SELECT id, first_name, last_name, email, status, registration_status 
                         FROM holiday_program_attendees 
                         WHERE (email = ? OR (first_name = ? AND last_name = ? AND phone = ?)) 
                         AND program_id = ?
                         AND status NOT IN ('cancelled', 'declined')";
    
    $duplicateStmt = $conn->prepare($duplicateCheckSql);
    if (!$duplicateStmt) {
        $errorMessage = "Database error occurred. Please try again.";
        goto end_processing;
    }
    
    $duplicateStmt->bind_param("ssssi", $email, $firstName, $lastName, $phone, $programId);
    $duplicateStmt->execute();
    $duplicateResult = $duplicateStmt->get_result();
    
    if ($duplicateResult->num_rows > 0) {
        $existingRegistration = $duplicateResult->fetch_assoc();
        $registrationSuccess = false;
        
        $existingStatus = $existingRegistration['status'] ?? $existingRegistration['registration_status'];
        
        switch($existingStatus) {
            case 'pending':
                $errorMessage = "You are already registered for this program. Your registration is currently pending approval.";
                break;
            case 'confirmed':
                $errorMessage = "You are already registered and confirmed for this program.";
                break;
            case 'waitlisted':
                $errorMessage = "You are already on the waitlist for this program.";
                break;
            case 'completed':
                $errorMessage = "You have already completed this program.";
                break;
            default:
                $errorMessage = "You are already registered for this program.";
        }
        
        $errorMessage .= " If you need to update your information or have questions, please contact our support team.";
        error_log("Duplicate registration attempt for program ID $programId: Email: $email, Name: $firstName $lastName");
        goto end_processing;
    }
    
    // Dashboard password handling
    $dashboardPassword = null;
    $createFullAccount = isset($_POST['create_full_account']) && $_POST['create_full_account'] == 1;
    $isExistingMember = false;
    $userIdFromMainTable = null;
    
    // Check if user exists in main users table
    $sql = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userIdFromMainTable = $row['id'];
            $isExistingMember = !empty($row['password']);
        }
    }
    
    // Handle password creation for non-members
    if (!$isExistingMember) {
        if (isset($_POST['dashboard_password']) && !empty($_POST['dashboard_password'])) {
            $password = $_POST['dashboard_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($password !== $confirmPassword) {
                $errorMessage = "Passwords do not match. Please try again.";
                goto end_processing;
            }
            
            if (strlen($password) < 8) {
                $errorMessage = "Password must be at least 8 characters long.";
                goto end_processing;
            }
            
            $dashboardPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Create full account if requested and user doesn't exist
            if ($createFullAccount && !$userIdFromMainTable) {
                $insertUserSql = "INSERT INTO users (name, surname, email, phone, password, date_of_birth, gender, address, city, province, postal_code, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $userStmt = $conn->prepare($insertUserSql);
                if ($userStmt) {
                    $userStmt->bind_param("sssssssssss", $firstName, $lastName, $email, $phone, $dashboardPassword, $dob, $gender, $address, $city, $province, $postalCode);
                    
                    if ($userStmt->execute()) {
                        $userIdFromMainTable = $conn->insert_id;
                        error_log("Created full Clubhouse account for new user: Email: $email, User ID: $userIdFromMainTable");
                    } else {
                        error_log("Failed to create full Clubhouse account: " . $conn->error);
                    }
                }
            }
        } else {
            $errorMessage = "Please create a password for dashboard access.";
            goto end_processing;
        }
    }
    
    // School information
    $school = sanitizeInput($_POST['school'] ?? '');

    // FIXED: Grade field - handle as integer, not string
    $grade = null;
    if (isset($_POST['grade']) && !empty(trim($_POST['grade'])) && is_numeric($_POST['grade'])) {
        $grade = intval($_POST['grade']);
    }
    
    // Guardian information
    $guardianName = sanitizeInput($_POST['guardian_name'] ?? '');
    $guardianRelationship = sanitizeInput($_POST['guardian_relationship'] ?? '');
    $guardianPhone = sanitizeInput($_POST['guardian_phone'] ?? '');
    $guardianEmail = filter_var($_POST['guardian_email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    // Emergency contact information
    $emergencyContactName = sanitizeInput($_POST['emergency_contact_name'] ?? '');
    $emergencyContactRelationship = sanitizeInput($_POST['emergency_contact_relationship'] ?? '');
    $emergencyContactPhone = sanitizeInput($_POST['emergency_contact_phone'] ?? '');
    
    // Workshop preferences - ensure it's valid JSON
    $workshopPreferences = $_POST['workshop_preferences'] ?? '[]';
    if (!json_decode($workshopPreferences)) {
        $workshopPreferences = '[]';
    }
    
    // Other information
    $whyInterested = sanitizeInput($_POST['why_interested'] ?? '');
    $experienceLevel = sanitizeInput($_POST['experience_level'] ?? '');
    $needsEquipment = isset($_POST['needs_equipment']) ? 1 : 0;
    
    // Medical information
    $medicalConditions = sanitizeInput($_POST['medical_conditions'] ?? '');
    $allergies = sanitizeInput($_POST['allergies'] ?? '');
    
    // Permissions
    $photoPermission = isset($_POST['photo_permission']) ? 1 : 0;
    $dataPermission = isset($_POST['data_permission']) ? 1 : 0;
    
    // Additional information
    $dietaryRestrictions = sanitizeInput($_POST['dietary_restrictions'] ?? '');
    $additionalNotes = sanitizeInput($_POST['additional_notes'] ?? '');
    
    // Check if registering as mentor
    $isMentor = isset($_POST['mentor_registration']) && $_POST['mentor_registration'] == 1;
    
    // Mentor specific information
    $mentorRegistration = $isMentor ? 1 : 0;
    $mentorStatus = $isMentor ? 'Pending' : NULL;
    $mentorExperience = $isMentor ? sanitizeInput($_POST['mentor_experience'] ?? '') : NULL;
    $mentorAvailability = $isMentor ? sanitizeInput($_POST['mentor_availability'] ?? '') : NULL;
    $mentorWorkshopPreference = $isMentor ? intval($_POST['mentor_workshop_preference'] ?? 0) : NULL;
    
    // Cohort selection
    $cohortId = isset($_POST['cohort_id']) ? intval($_POST['cohort_id']) : NULL;
    
    // Insert new registration
    $sql = "INSERT INTO holiday_program_attendees (
            program_id, user_id, first_name, last_name, email, phone, 
            date_of_birth, gender, school, grade, address, city, province, 
            postal_code, guardian_name, guardian_relationship, guardian_phone, 
            guardian_email, emergency_contact_name, emergency_contact_relationship, 
            emergency_contact_phone, workshop_preference, why_interested, 
            experience_level, needs_equipment, medical_conditions, allergies, 
            photo_permission, data_permission, dietary_restrictions, additional_notes,
            mentor_registration, mentor_status, mentor_workshop_preference, password, cohort_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
    
    $params = [
        $programId, $userIdFromMainTable, $firstName, $lastName, $email, $phone, 
        $dob, $gender, $school, $grade, $address, $city, $province, 
        $postalCode, $guardianName, $guardianRelationship, $guardianPhone, 
        $guardianEmail, $emergencyContactName, $emergencyContactRelationship, 
        $emergencyContactPhone, $workshopPreferences, $whyInterested, 
        $experienceLevel, $needsEquipment, $medicalConditions, $allergies,
        $photoPermission, $dataPermission, $dietaryRestrictions, 
        $additionalNotes, $mentorRegistration, $mentorStatus, $mentorWorkshopPreference, $dashboardPassword, $cohortId
    ];
    
    // Build type string
    $types = str_repeat('s', count($params));
$types[0] = 'i'; // programId is integer
    if ($userIdFromMainTable !== null) {
        $types[1] = 'i'; // user_id is integer
    }
    if ($cohortId !== null) {
        $types[count($params)-1] = 'i'; // cohort_id is integer
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $errorMessage = "Database error occurred. Please try again.";
        error_log("Error preparing statement: " . $conn->error);
        goto end_processing;
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $registrationSuccess = true;
        $newRegistrationId = $conn->insert_id;
        error_log("Successful registration for program ID $programId: Registration ID: $newRegistrationId, Email: $email, Name: $firstName $lastName");
        
        // Insert mentor details if applicable
        if ($mentorRegistration && !empty($mentorExperience)) {
            // Validate workshop preference - only insert if workshop exists for this program
            $validWorkshopId = NULL;
            
            if (!empty($mentorWorkshopPreference) && $mentorWorkshopPreference > 0) {
                // Check if workshop exists for this program
                $checkWorkshopSql = "SELECT id FROM holiday_program_workshops WHERE id = ? AND program_id = ?";
                $checkStmt = $conn->prepare($checkWorkshopSql);
                
                if ($checkStmt) {
                    $checkStmt->bind_param("ii", $mentorWorkshopPreference, $programId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $validWorkshopId = $mentorWorkshopPreference;
                    } else {
                        error_log("Warning: Workshop ID $mentorWorkshopPreference does not exist for program $programId");
                    }
                }
            }
            
            // Insert mentor details with validated workshop preference
            $mentorSql = "INSERT INTO holiday_program_mentor_details (attendee_id, experience, availability, workshop_preference) VALUES (?, ?, ?, ?)";
            $mentorStmt = $conn->prepare($mentorSql);
            
            if ($mentorStmt) {
                $mentorStmt->bind_param("issi", $newRegistrationId, $mentorExperience, $mentorAvailability, $validWorkshopId);
                
                if ($mentorStmt->execute()) {
                    error_log("Mentor details saved successfully for attendee ID: $newRegistrationId");
                } else {
                    error_log("Failed to save mentor details: " . $mentorStmt->error);
                }
            } else {
                error_log("Failed to prepare mentor details statement: " . $conn->error);
            }
        }
        
    } else {
        $errorMessage = "Registration failed. Please try again.";
        error_log("Error executing statement: " . $stmt->error);
    }
    
    end_processing: // Label for goto statements
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for <?php echo htmlspecialchars($programDetails['title']); ?> - Sci-Bono Clubhouse</title>
    <?php echo CSRF::metaTag(); ?>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        .program-structure-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .structure-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .structure-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .structure-item i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .cohort-selection {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        
        .cohort-option {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: white;
            border-radius: 8px;
            border: 2px solid #e1e8ed;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cohort-option:hover {
            border-color: #6c63ff;
            box-shadow: 0 2px 8px rgba(108, 99, 255, 0.1);
        }
        
        .cohort-option.selected {
            border-color: #6c63ff;
            background: #f8f7ff;
        }
        
        .cohort-option input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
        }
        
        .cohort-details h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .cohort-details p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .cohort-availability {
            margin-left: auto;
            text-align: right;
        }
        
        .availability-good {
            color: #28a745;
        }
        
        .availability-limited {
            color: #ffc107;
        }
        
        .availability-full {
            color: #dc3545;
        }
        
        .workshop-card {
            position: relative;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .workshop-card:hover {
            border-color: #6c63ff;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.1);
        }
        
        .workshop-card.selected {
            border-color: #6c63ff;
            background: #f8f7ff;
        }
        
        .workshop-card.first-choice {
            border-color: #28a745;
            background: #f8fff8;
        }
        
        .workshop-card.second-choice {
            border-color: #ffc107;
            background: #fffbf0;
        }
        
        .workshop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .selection-number {
            background: #6c63ff;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .workshop-card.first-choice .selection-number {
            background: #28a745;
        }
        
        .workshop-card.second-choice .selection-number {
            background: #ffc107;
            color: #000;
        }
        
        .workshop-capacity {
            margin-top: 15px;
        }
        
        .capacity-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .capacity-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .capacity-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
            transition: width 0.3s;
        }
        
        .prerequisites-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .prerequisites-info strong {
            color: #856404;
        }
        
        .workshop-card.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .disabled-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-weight: 600;
            color: #dc3545;
        }
        
        .cohort-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .cohort-info i {
            color: #1976d2;
            margin-right: 5px;
        }
        
        .form-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .required {
            color: #dc3545;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-top: 3px;
        }
        
        .primary-button, .secondary-button {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .primary-button {
            background: #667eea;
            color: white;
        }
        
        .primary-button:hover {
            background: #5a52d5;
            transform: translateY(-1px);
        }
        
        .secondary-button {
            background: #6c757d;
            color: white;
        }
        
        .secondary-button:hover {
            background: #5a6268;
        }
        
        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        #mentor_fields {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .hidden {
            display: none;
        }
        
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
        }
        
        #email-check-result {
            margin-top: 10px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 40px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .success-content i {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .member-status-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .member-status-info i {
            color: #28a745;
            font-size: 1.5rem;
        }
        
        .member-status-info p {
            margin: 0;
            color: #155724;
        }
        
        .password-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .password-info i {
            color: #856404;
            font-size: 1.5rem;
        }
        
        .password-info p {
            margin: 0;
            color: #856404;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
        }
        
        .password-weak {
            color: #dc3545;
        }
        
        .password-medium {
            color: #ffc107;
        }
        
        .password-strong {
            color: #28a745;
        }
        
        .password-match-success {
            color: #28a745;
        }
        
        .password-match-error {
            color: #dc3545;
        }
        
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .structure-grid {
                grid-template-columns: 1fr;
            }
            
            .cohort-option {
                flex-direction: column;
                text-align: center;
            }
            
            .cohort-availability {
                margin-left: 0;
                margin-top: 10px;
            }
            
            .workshop-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="container">
        <?php if ($registrationClosed): ?>
            <div class="alert error">
                <h2>Registration Closed</h2>
                <p>Registration for this program is currently closed. Please check back later or contact us for more information.</p>
            </div>
        <?php elseif ($formSubmitted && $registrationSuccess): ?>
            <div class="success-message">
                <div class="success-content">
                    <i class="fas fa-check-circle"></i>
                    <h2>Registration Successful!</h2>
                    <p>Thank you for registering for the <?php echo htmlspecialchars($programDetails['title']); ?> holiday program.</p>
                    <p>You will receive a confirmation email shortly with further details.</p>
                </div>
            </div>
        <?php elseif ($formSubmitted && !$registrationSuccess && !empty($errorMessage)): ?>
            <div class="alert error">
                <h2><i class="fas fa-exclamation-triangle"></i> Registration Not Completed</h2>
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
                <div style="margin-top: 15px;">
                    <p><strong>Need help?</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Contact our support team for assistance</li>
                        <li>Check your email for any previous registration confirmations</li>
                        <li>If you need to update your information, please contact us directly</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <!-- Program Structure Information -->
            <?php if ($programConfig): ?>
            <div class="program-structure-info">
                <h2><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($programConfig['title']); ?> Structure</h2>
                <p><?php echo htmlspecialchars($programConfig['description'] ?? 'Comprehensive holiday program designed to enhance your skills.'); ?></p>
                
                <div class="structure-grid">
                    <div class="structure-item">
                        <i class="fas fa-clock"></i>
                        <h4><?php echo $programConfig['duration_weeks'] ?? 2; ?> Week<?php echo ($programConfig['duration_weeks'] ?? 2) > 1 ? 's' : ''; ?></h4>
                        <p>Program Duration</p>
                    </div>
                    
                    <?php if ($programConfig['has_cohorts']): ?>
                    <div class="structure-item">
                        <i class="fas fa-users"></i>
                        <h4><?php echo count($cohorts); ?> Cohort<?php echo count($cohorts) != 1 ? 's' : ''; ?></h4>
                        <p>Available Groups</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="structure-item">
                        <i class="fas fa-laptop-code"></i>
                        <h4><?php echo count($workshops); ?> Workshop<?php echo count($workshops) != 1 ? 's' : ''; ?></h4>
                        <p>Available Sessions</p>
                    </div>
                    
                    <?php if ($programConfig['has_prerequisites']): ?>
                    <div class="structure-item">
                        <i class="fas fa-check-circle"></i>
                        <h4>Prerequisites</h4>
                        <p>Some workshops have requirements</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form id="registration-form" method="POST" action="">
                <?php echo CSRF::field(); ?>
                <input type="hidden" name="program_id" value="<?php echo $programId; ?>">
                
                <!-- Email Check Section -->
                <div class="form-section">
                    <h2><i class="fas fa-search"></i> Check Existing Information</h2>
                    <p>If you're already a Sci-Bono member, we can pre-fill your information.</p>
                    <div class="form-group">
                        <label for="check-email">Enter your email address:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="email" id="check-email" class="form-input" placeholder="your@email.com" style="flex: 1;">
                            <button type="button" id="check-email-btn" class="secondary-button">Check Email</button>
                        </div>
                    </div>
                    <div id="email-check-result" class="hidden">
                        <p></p>
                    </div>
                </div>

                <!-- Mentor Registration Option -->
                <div class="form-section">
                    <h2><i class="fas fa-chalkboard-teacher"></i> Registration Type</h2>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="mentor_registration" name="mentor_registration" value="1" <?php echo $defaultMentorRegistration ? 'checked' : ''; ?>>
                            <label for="mentor_registration">I want to register as a mentor for this program</label>
                        </div>
                        <div class="help-text">
                            <small>Mentors assist in facilitating workshops and guiding participants. Mentor applications are subject to approval.</small>
                        </div>
                    </div>
                    
                    <!-- Mentor Fields -->
                    <div id="mentor_fields" style="<?php echo $defaultMentorRegistration ? 'display: block;' : 'display: none;'; ?>">
                        <div class="form-group">
                            <label for="mentor_experience">Please describe your experience relevant to this program: <span class="required">*</span></label>
                            <textarea id="mentor_experience" name="mentor_experience" class="form-textarea" rows="4" 
                                placeholder="Describe your background, skills, and experience that qualify you to mentor in this program..."></textarea>
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
                            <label for="mentor_workshop_preference">Which workshop would you prefer to mentor?</label>
                            <select id="mentor_workshop_preference" name="mentor_workshop_preference" class="form-select">
                                <option value="">No preference</option>
                                <?php foreach ($workshops as $workshop): ?>
                                    <option value="<?php echo $workshop['id']; ?>"><?php echo htmlspecialchars($workshop['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Information -->
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
                            <input type="date" id="dob" name="date_of_birth" class="form-input" required>
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
                
                <!-- Dashboard Access Section -->
                <div class="form-section" id="dashboard_access_section">
                    <h2><i class="fas fa-key"></i> Dashboard Access</h2>
                    
                    <!-- Member Status Display -->
                    <div id="member_status_display" class="hidden">
                        <div class="member-status-info">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <p><strong>Great! You're already a Sci-Bono Clubhouse member.</strong></p>
                                <p>You can use your existing Clubhouse password to access your holiday program dashboard after registration.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Creation for Non-Members -->
                    <div id="password_creation_section">
                        <div class="password-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p><strong>Create a Dashboard Password</strong></p>
                                <p>Since you don't have a Sci-Bono Clubhouse account yet, please create a password to access your holiday program dashboard.</p>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dashboard_password">Dashboard Password <span class="required">*</span></label>
                                <input type="password" id="dashboard_password" name="dashboard_password" class="form-input" 
                                       minlength="8" placeholder="Minimum 8 characters" required>
                                <div class="help-text">
                                    <small>Password must be at least 8 characters long and contain letters and numbers</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       minlength="8" placeholder="Re-enter your password" required>
                                <div id="password_match_feedback" class="help-text"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <!-- <div class="checkbox-group">
                                <input type="checkbox" id="create_full_account" name="create_full_account" value="1">
                                <label for="create_full_account">Also create a full Sci-Bono Clubhouse account for me (recommended)</label>
                            </div> 
                            <div class="help-text">
                                <small>This will give you access to all Clubhouse programs and courses, not just holiday programs.</small>
                            </div>-->
                        </div>
                    </div>
                </div>
                
                <!-- Address Information -->
                <div class="form-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Address Information</h2>
                    <div class="form-group">
                        <label for="address">Street Address <span class="required">*</span></label>
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
                                <option value="Gauteng">Gauteng</option>
                                <option value="Western Cape">Western Cape</option>
                                <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                <option value="Eastern Cape">Eastern Cape</option>
                                <option value="Free State">Free State</option>
                                <option value="Limpopo">Limpopo</option>
                                <option value="Mpumalanga">Mpumalanga</option>
                                <option value="Northern Cape">Northern Cape</option>
                                <option value="North West">North West</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Postal Code <span class="required">*</span></label>
                        <input type="text" id="postal_code" name="postal_code" class="form-input" required>
                    </div>
                </div>
                
                <!-- School Information (for students) -->
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
                                <?php for ($i = 5; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Guardian Information (for students) -->
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
                
                <!-- Emergency Contact -->
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
                
                
                
                <!-- Cohort Selection (if cohorts are enabled) -->
                <?php if ($programConfig['has_cohorts'] && !empty($cohorts)): ?>
                <div class="form-section">
                    <h2><i class="fas fa-users"></i> Cohort Selection</h2>
                    <p class="section-description">Please choose which week you'd like to attend the holiday program.
The same workshops will run each week, so you only need to attend one week. Select the week that works best for you.</p>
                    
                    <div class="cohort-selection">
                        <?php foreach ($cohorts as $cohort): ?>
                            <?php 
                            $availability = '';
                            $availabilityClass = '';
                            if ($cohort['available_spots'] > 10) {
                                $availability = 'Good availability';
                                $availabilityClass = 'availability-good';
                            } elseif ($cohort['available_spots'] > 0) {
                                $availability = 'Limited spots';
                                $availabilityClass = 'availability-limited';
                            } else {
                                $availability = 'Full';
                                $availabilityClass = 'availability-full';
                            }
                            ?>
                            <div class="cohort-option <?php echo $cohort['available_spots'] <= 0 ? 'disabled' : ''; ?>" 
                                 onclick="selectCohort(<?php echo $cohort['id']; ?>, this)">
                                <input type="radio" name="cohort_id" value="<?php echo $cohort['id']; ?>" 
                                       <?php echo $cohort['available_spots'] <= 0 ? 'disabled' : ''; ?>>
                                <div class="cohort-details">
                                    <h4><?php echo htmlspecialchars($cohort['name']); ?></h4>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($cohort['start_date'])); ?> - <?php echo date('M j, Y', strtotime($cohort['end_date'])); ?></p>
                                    <p><i class="fas fa-users"></i> Max <?php echo $cohort['max_participants']; ?> participants</p>
                                </div>
                                <div class="cohort-availability">
                                    <span class="<?php echo $availabilityClass; ?>">
                                        <?php echo $availability; ?>
                                    </span>
                                    <br>
                                    <small><?php echo $cohort['available_spots']; ?> spots left</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Workshop Preferences Section -->
                <div id="workshop_preferences_section" class="form-section">
                    <h2><i class="fas fa-laptop-code"></i> Workshop Preferences</h2>
                    <p class="section-description">
                        Please select your 1st and 2nd choice workshops. 
                        <?php if ($programConfig['has_prerequisites']): ?>
                        <strong>Note:</strong> Some workshops have prerequisites that must be met.
                        <?php endif; ?>
                    </p>
                    
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
                            $capacityPercentage = $capacity ? round(($capacity['enrolled'] / $capacity['max']) * 100) : 0;
                        ?>
                            <div class="workshop-card <?php echo $isFull ? 'disabled' : ''; ?>" 
                                 data-workshop-id="<?php echo $workshop['id']; ?>"
                                 onclick="selectWorkshop(<?php echo $workshop['id']; ?>, this)">
                                
                                <?php if ($isFull): ?>
                                <div class="disabled-overlay">
                                    <i class="fas fa-times-circle"></i> Workshop Full
                                </div>
                                <?php endif; ?>
                                
                                <div class="workshop-header">
                                    <h3><?php echo htmlspecialchars($workshop['title']); ?></h3>
                                    <div class="selection-number" style="display: none;"></div>
                                </div>
                                
                                <p class="workshop-description"><?php echo htmlspecialchars($workshop['description']); ?></p>
                                
                                <?php if (!empty($workshop['instructor'])): ?>
                                <p class="workshop-instructor">
                                    <i class="fas fa-user-tie"></i> 
                                    <strong>Instructor:</strong> <?php echo htmlspecialchars($workshop['instructor']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="workshop-capacity">
                                    <div class="capacity-info">
                                        <span>Capacity: <?php echo $capacity ? $capacity['enrolled'] : 0; ?>/<?php echo $workshop['max_participants']; ?></span>
                                        <span class="capacity-percentage"><?php echo $capacityPercentage; ?>% full</span>
                                    </div>
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?php echo min($capacityPercentage, 100); ?>%;"></div>
                                    </div>
                                </div>
                                
                                <?php if ($programConfig['has_prerequisites'] && !empty($workshop['prerequisites'])): ?>
                                <div class="prerequisites-info">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Prerequisites:</strong>
                                    <?php echo htmlspecialchars($workshop['prerequisites']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($workshop['cohort_name'])): ?>
                                <div class="cohort-info">
                                    <i class="fas fa-users"></i> 
                                    <strong>Cohort:</strong> <?php echo htmlspecialchars($workshop['cohort_name']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <input type="hidden" id="workshop_preferences" name="workshop_preferences" value="">
                </div>
                
                <!-- Why Interested Section -->
                <div class="form-section">
                    <h2><i class="fas fa-question-circle"></i> Interest & Experience</h2>
                    <div class="form-group">
                        <label for="why_interested">Why are you interested in this program? <span class="required">*</span></label>
                        <textarea id="why_interested" name="why_interested" class="form-textarea" rows="4" required 
                                  placeholder="Tell us what interests you about this program and what you hope to learn..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience_level">What is your experience level with technology/programming? <span class="required">*</span></label>
                        <select id="experience_level" name="experience_level" class="form-select" required>
                            <option value="">Select Experience Level</option>
                            <option value="Beginner">Beginner - Little to no experience</option>
                            <option value="Novice">Novice - Some basic knowledge</option>
                            <option value="Intermediate">Intermediate - Comfortable with basics</option>
                            <option value="Advanced">Advanced - Strong technical skills</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="needs_equipment" name="needs_equipment" value="1">
                            <label for="needs_equipment">I will bring my own device (Laptop).</label>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Information -->
                <div class="form-section">
                    <h2><i class="fas fa-notes-medical"></i> Medical Information</h2>
                    <div class="form-group">
                        <label for="medical_conditions">Do you have any medical conditions we should be aware of?</label>
                        <textarea id="medical_conditions" name="medical_conditions" class="form-textarea" rows="2" 
                                  placeholder="Please list any medical conditions, medications, or health considerations..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergies">Do you have any allergies?</label>
                        <textarea id="allergies" name="allergies" class="form-textarea" rows="2" 
                                  placeholder="Please list any food allergies, environmental allergies, etc..."></textarea>
                    </div>
                </div>
                
                <!-- Permissions -->
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
                
                <!-- Additional Information -->
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Additional Information</h2>
                    <div class="form-group">
                        <label for="dietary_restrictions">Do you have any dietary restrictions or preferences?</label>
                        <textarea id="dietary_restrictions" name="dietary_restrictions" class="form-textarea" rows="2" 
                                  placeholder="Please list any dietary restrictions, food preferences, or special meal requirements..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_notes">Any additional information you would like to share?</label>
                        <textarea id="additional_notes" name="additional_notes" class="form-textarea" rows="3" 
                                  placeholder="Anything else you think we should know..."></textarea>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" name="submit_registration" class="primary-button">
                        <i class="fas fa-paper-plane"></i> Complete Registration
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        let selectedWorkshops = [];
        const maxSelections = 2;
        
        // Workshop capacity data
        const workshopCapacityData = <?php echo json_encode($workshopEnrollmentCounts); ?>;
        
        $(document).ready(function() {
            // Email check functionality
            $('#check-email-btn').click(function(e) {
                e.preventDefault();
                const email = $('#check-email').val();
                
                if (!email) {
                    alert('Please enter an email address');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    alert('Please enter a valid email address');
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
                            
                            // Pre-fill form with user data
                            const data = response.data;
                            $('#first_name').val(data.first_name || data.name);
                            $('#last_name').val(data.last_name || data.surname);
                            $('#email').val(data.email);
                            $('#phone').val(data.phone);
                            if (data.date_of_birth) $('#dob').val(data.date_of_birth);
                            if (data.gender) $('#gender').val(data.gender);
                            if (data.school) $('#school').val(data.school);
                            if (data.grade) $('#grade').val(data.grade);
                            if (data.address) $('#address').val(data.address);
                            if (data.city) $('#city').val(data.city);
                            if (data.province) $('#province').val(data.province);
                            if (data.postal_code) $('#postal_code').val(data.postal_code);
                            if (data.guardian_name) $('#guardian_name').val(data.guardian_name);
                            if (data.guardian_relationship) $('#guardian_relationship').val(data.guardian_relationship);
                            if (data.guardian_phone) $('#guardian_phone').val(data.guardian_phone);
                            if (data.guardian_email) $('#guardian_email').val(data.guardian_email);
                            if (data.medical_conditions) $('#medical_conditions').val(data.medical_conditions);
                            if (data.allergies) $('#allergies').val(data.allergies);
                            if (data.dietary_restrictions) $('#dietary_restrictions').val(data.dietary_restrictions);
                            if (data.additional_notes) $('#additional_notes').val(data.additional_notes);
                            
                            // Handle dashboard access section based on member status
                            handleMembershipStatus(data);
                            
                            // Workshop preferences - handle existing registrations
                            if (data.workshop_preference) {
                                try {
                                    const workshops = JSON.parse(data.workshop_preference);
                                    workshops.forEach(workshopId => {
                                        selectWorkshop(parseInt(workshopId), document.querySelector(`[data-workshop-id="${workshopId}"]`));
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
                            
                            // Show password creation section for new users
                            handleMembershipStatus(null);
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
            
            // Emergency contact same as guardian
            $('#same_as_guardian').change(function() {
                if ($(this).is(':checked')) {
                    // Copy parent/guardian info to emergency contact
                    $('#emergency_contact_name').val($('#guardian_name').val());
                    $('#emergency_contact_relationship').val($('#guardian_relationship').val());
                    $('#emergency_contact_phone').val($('#guardian_phone').val());
                    
                    // Disable emergency contact fields
                    $('#emergency_contact_fields input').prop('disabled', true);
                    $('#emergency_contact_fields input').prop('required', false);
                } else {
                    // Clear and enable emergency contact fields
                    $('#emergency_contact_fields input').prop('disabled', false);
                    $('#emergency_contact_fields input').prop('required', true);
                }
            });
            
            // Listen for changes in guardian fields to update emergency contact if checkbox is checked
            $('#guardian_name, #guardian_relationship, #guardian_phone').on('input', function() {
                if ($('#same_as_guardian').is(':checked')) {
                    $('#emergency_contact_name').val($('#guardian_name').val());
                    $('#emergency_contact_relationship').val($('#guardian_relationship').val());
                    $('#emergency_contact_phone').val($('#guardian_phone').val());
                }
            });
            
            // Mentor registration toggle
            $('#mentor_registration').change(function() {
                if ($(this).is(':checked')) {
                    // Show mentor-specific fields
                    $('#mentor_fields').slideDown();
                    $('#mentor_experience, #mentor_availability').prop('required', true);
                    
                    // Hide student-specific sections
                    $('#school_section').slideUp();
                    $('#guardian_section').slideUp();
                    $('#workshop_preferences_section').slideUp();
                    
                    // Remove required attributes from student-specific fields
                    $('#school, #grade').prop('required', false);
                    $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
                    $('#why_interested, #experience_level').prop('required', false);
                } else {
                    // Hide mentor-specific fields
                    $('#mentor_fields').slideUp();
                    $('#mentor_experience, #mentor_availability').prop('required', false);
                    
                    // Show student-specific sections
                    $('#school_section').slideDown();
                    $('#guardian_section').slideDown();
                    $('#workshop_preferences_section').slideDown();
                    
                    // Add required attributes to student-specific fields
                    $('#school, #grade').prop('required', true);
                    $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', true);
                    $('#why_interested, #experience_level').prop('required', true);
                }
            });
            
            // Check initial state of mentor checkbox
            if ($('#mentor_registration').is(':checked')) {
                $('#mentor_fields').show();
                $('#mentor_experience, #mentor_availability').prop('required', true);
                $('#school_section').hide();
                $('#guardian_section').hide();
                $('#workshop_preferences_section').hide();
                $('#school, #grade').prop('required', false);
                $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
                $('#why_interested, #experience_level').prop('required', false);
            }
            
            // Set minimum age for date of birth (typically 8 years old minimum)
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 8, today.getMonth(), today.getDate());
            $('#dob').attr('max', maxDate.toISOString().split('T')[0]);
            
            // Password validation
            $('#dashboard_password, #confirm_password').on('input', function() {
                validatePasswords();
            });
            
            // Real-time email checking for membership status
            $('#email').on('blur', function() {
                const email = $(this).val();
                if (email && isValidEmail(email)) {
                    checkMembershipStatus(email);
                }
            });
        });
        
        // Function to handle membership status display
        function handleMembershipStatus(userData) {
            const memberStatusDisplay = $('#member_status_display');
            const passwordCreationSection = $('#password_creation_section');
            
            if (userData && (userData.password || userData.id)) {
                // User is an existing member
                memberStatusDisplay.removeClass('hidden');
                passwordCreationSection.hide();
                
                // Remove required attributes from password fields
                $('#dashboard_password, #confirm_password').prop('required', false);
            } else {
                // User is not a member, show password creation
                memberStatusDisplay.addClass('hidden');
                passwordCreationSection.show();
                
                // Add required attributes to password fields
                $('#dashboard_password, #confirm_password').prop('required', true);
            }
        }
        
        // Function to check membership status via email
        function checkMembershipStatus(email) {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    check_email: true,
                    email: email
                },
                dataType: 'json',
                success: function(response) {
                    handleMembershipStatus(response.exists ? response.data : null);
                },
                error: function() {
                    // On error, assume new user
                    handleMembershipStatus(null);
                }
            });
        }
        
        // Function to validate email format
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Function to validate passwords
        function validatePasswords() {
            const password = $('#dashboard_password').val();
            const confirmPassword = $('#confirm_password').val();
            const feedback = $('#password_match_feedback');
            
            // Password strength validation
            if (password.length > 0) {
                let strength = '';
                let strengthClass = '';
                
                if (password.length < 8) {
                    strength = 'Password must be at least 8 characters';
                    strengthClass = 'password-weak';
                } else if (password.length >= 8 && /^(?=.*[a-zA-Z])(?=.*\d)/.test(password)) {
                    strength = 'Strong password';
                    strengthClass = 'password-strong';
                } else if (password.length >= 8) {
                    strength = 'Good password (consider adding numbers)';
                    strengthClass = 'password-medium';
                } else {
                    strength = 'Weak password';
                    strengthClass = 'password-weak';
                }
                
                $('#dashboard_password').next('.help-text').find('small').html(
                    `Password must be at least 8 characters long and contain letters and numbers<br>
                    <span class="${strengthClass}">${strength}</span>`
                );
            }
            
            // Password match validation
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    feedback.html('<span class="password-match-success"><i class="fas fa-check"></i> Passwords match</span>');
                } else {
                    feedback.html('<span class="password-match-error"><i class="fas fa-times"></i> Passwords do not match</span>');
                }
            } else {
                feedback.html('');
            }
        }
        
        // Cohort selection
        function selectCohort(cohortId, element) {
            // Remove selection from all cohorts
            document.querySelectorAll('.cohort-option').forEach(option => {
                option.classList.remove('selected');
                const radio = option.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });
            
            // Select this cohort
            if (element && !element.classList.contains('disabled')) {
                element.classList.add('selected');
                const radio = element.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            }
        }
        
        // Workshop selection
        function selectWorkshop(workshopId, element) {
            if (!element || element.classList.contains('disabled')) {
                return;
            }
            
            const isSelected = selectedWorkshops.includes(workshopId);
            
            if (isSelected) {
                // Deselect workshop
                selectedWorkshops = selectedWorkshops.filter(id => id !== workshopId);
                element.classList.remove('selected', 'first-choice', 'second-choice');
                const selectionNumber = element.querySelector('.selection-number');
                if (selectionNumber) selectionNumber.style.display = 'none';
            } else {
                // Select workshop if under limit
                if (selectedWorkshops.length < maxSelections) {
                    selectedWorkshops.push(workshopId);
                    element.classList.add('selected');
                    
                    // Add appropriate choice class
                    const selectionNumber = element.querySelector('.selection-number');
                    if (selectedWorkshops.length === 1) {
                        element.classList.add('first-choice');
                        if (selectionNumber) selectionNumber.textContent = '1st Choice';
                    } else if (selectedWorkshops.length === 2) {
                        element.classList.add('second-choice');
                        if (selectionNumber) selectionNumber.textContent = '2nd Choice';
                    }
                    
                    if (selectionNumber) selectionNumber.style.display = 'block';
                } else {
                    alert('You can only select up to ' + maxSelections + ' workshops. Please deselect one first.');
                }
            }
            
            updateWorkshopPreferences();
            updateSelectionInfo();
        }
        
        function updateWorkshopPreferences() {
            const hiddenInput = document.getElementById('workshop_preferences');
            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(selectedWorkshops);
            }
        }
        
        function updateSelectionInfo() {
            const selectionInfo = document.getElementById('selection-info');
            const reorderControls = document.querySelector('.reorder-controls');
            
            if (selectionInfo) {
                if (selectedWorkshops.length > 0) {
                    selectionInfo.style.display = 'block';
                } else {
                    selectionInfo.style.display = 'none';
                }
            }
            
            if (reorderControls) {
                if (selectedWorkshops.length === 2) {
                    reorderControls.style.display = 'block';
                } else {
                    reorderControls.style.display = 'none';
                }
            }
        }
        
        // Swap preferences functionality
        $('#swap-preferences').click(function() {
            if (selectedWorkshops.length === 2) {
                // Swap the order
                selectedWorkshops.reverse();
                
                // Update the visual indicators
                document.querySelectorAll('.workshop-card.selected').forEach((card, index) => {
                    const workshopId = parseInt(card.dataset.workshopId);
                    const orderIndex = selectedWorkshops.indexOf(workshopId);
                    
                    card.classList.remove('first-choice', 'second-choice');
                    
                    const selectionNumber = card.querySelector('.selection-number');
                    if (orderIndex === 0) {
                        card.classList.add('first-choice');
                        if (selectionNumber) selectionNumber.textContent = '1st Choice';
                    } else if (orderIndex === 1) {
                        card.classList.add('second-choice');
                        if (selectionNumber) selectionNumber.textContent = '2nd Choice';
                    }
                });
                
                updateWorkshopPreferences();
            }
        });
        
        // Form validation
        $('#registration-form').submit(function(e) {
            // Check if this is a mentor registration
            const isMentor = $('#mentor_registration').is(':checked');

            // Only validate workshop selection for non-mentors
            if (!isMentor && selectedWorkshops.length === 0) {
                e.preventDefault();
                alert('Please select at least one workshop preference');
                return false;
            }
            
            // Check if cohort is required and selected
            const cohortRequired = <?php echo $programConfig['has_cohorts'] ? 'true' : 'false'; ?>;
            if (cohortRequired && !isMentor) {
                const selectedCohort = document.querySelector('input[name="cohort_id"]:checked');
                if (!selectedCohort) {
                    e.preventDefault();
                    alert('Please select a cohort for your program.');
                    return false;
                }
            }
            
            // Check required checkboxes
            if (!$('#photo_permission').is(':checked') || !$('#data_permission').is(':checked')) {
                e.preventDefault();
                alert('Please agree to the required permissions');
                return false;
            }
            
            // Validate passwords for non-members
            if ($('#password_creation_section').is(':visible')) {
                const password = $('#dashboard_password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (!password || password.length < 8) {
                    e.preventDefault();
                    alert('Please create a password that is at least 8 characters long.');
                    $('#dashboard_password').focus();
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match. Please check your password and try again.');
                    $('#confirm_password').focus();
                    return false;
                }
                
                // Check password strength
                if (!/^(?=.*[a-zA-Z])(?=.*\d)/.test(password)) {
                    if (!confirm('Your password could be stronger by including both letters and numbers. Continue anyway?')) {
                        e.preventDefault();
                        $('#dashboard_password').focus();
                        return false;
                    }
                }
            }
        });
    </script>
</body>
</html>