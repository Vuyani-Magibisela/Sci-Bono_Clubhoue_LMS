<?php
require 'server.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$editUserId = isset($_GET['id']) && $userType === 'admin' ? $_GET['id'] : $userId;

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $editUserId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    echo "User not found!";
    exit;
}

$errorMsg = "";
$successMsg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic user details
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $password = isset($_POST['password']) && !empty($_POST['password']) ? trim($_POST['password']) : null;
    $user_type = $userType === 'admin' ? trim($_POST['user_type']) : $userData['user_type'];
    $center = isset($_POST['center']) ? trim($_POST['center']) : $userData['Center'];
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : $userData['Gender'];
    
    // Convert date of birth to MySQL format
    $date_of_birth = !empty($_POST['dob']) ? date('Y-m-d', strtotime($_POST['dob'])) : null;
    
    // New fields from Clubhouse Registration Form
    $nationality = isset($_POST['nationality']) ? trim($_POST['nationality']) : null;
    $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : null;
    $home_language = isset($_POST['home_language']) ? trim($_POST['home_language']) : null;
    
    // Address information
    $address_street = isset($_POST['address_street']) ? trim($_POST['address_street']) : null;
    $address_suburb = isset($_POST['address_suburb']) ? trim($_POST['address_suburb']) : null;
    $address_city = isset($_POST['address_city']) ? trim($_POST['address_city']) : null;
    $address_province = isset($_POST['address_province']) ? trim($_POST['address_province']) : null;
    $address_postal_code = isset($_POST['address_postal_code']) ? trim($_POST['address_postal_code']) : null;
    
    // Medical information
    $medical_aid_name = isset($_POST['medical_aid_name']) ? trim($_POST['medical_aid_name']) : null;
    $medical_aid_holder = isset($_POST['medical_aid_holder']) ? trim($_POST['medical_aid_holder']) : null;
    $medical_aid_number = isset($_POST['medical_aid_number']) ? trim($_POST['medical_aid_number']) : null;
    
    // Emergency contact
    $emergency_contact_name = isset($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : null;
    $emergency_contact_relationship = isset($_POST['emergency_contact_relationship']) ? trim($_POST['emergency_contact_relationship']) : null;
    $emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : null;
    $emergency_contact_email = isset($_POST['emergency_contact_email']) ? trim($_POST['emergency_contact_email']) : null;
    $emergency_contact_address = isset($_POST['emergency_contact_address']) ? trim($_POST['emergency_contact_address']) : null;
    
    // Interests and skills
    $interests = isset($_POST['interests']) ? trim($_POST['interests']) : null;
    $role_models = isset($_POST['role_models']) ? trim($_POST['role_models']) : null;
    $goals = isset($_POST['goals']) ? trim($_POST['goals']) : null;
    $has_computer = isset($_POST['has_computer']) ? intval($_POST['has_computer']) : null;
    $computer_skills = isset($_POST['computer_skills']) ? trim($_POST['computer_skills']) : null;
    $computer_skills_source = isset($_POST['computer_skills_source']) ? trim($_POST['computer_skills_source']) : null;
    
    // School details (only for members)
    $grade = isset($_POST['grade']) ? intval($_POST['grade']) : null;
    $school = isset($_POST['school']) ? trim($_POST['school']) : null;
    $learner_number = isset($_POST['learner_number']) ? trim($_POST['learner_number']) : null;
    
    // Cell number (might be mapped to learner_number)
    $cell_number = isset($_POST['cell_number']) ? trim($_POST['cell_number']) : $learner_number;
    
    // Parent details (only for members)
    $parent = isset($_POST['parent']) ? trim($_POST['parent']) : null;
    $parent_email = isset($_POST['parent_email']) ? trim($_POST['parent_email']) : null;
    $parent_number = isset($_POST['parent_number']) ? trim($_POST['parent_number']) : null;
    $relationship = isset($_POST['relationship']) ? trim($_POST['relationship']) : null;

    // Build the SQL query dynamically
    $sql_parts = [
        "username = ?", 
        "email = ?", 
        "name = ?",
        "surname = ?",
        "user_type = ?",
        "date_of_birth = ?",
        "Center = ?",
        "Gender = ?"
    ];
    
    $params = [
        $username, 
        $email, 
        $name, 
        $surname, 
        $user_type, 
        $date_of_birth,
        $center,
        $gender
    ];
    
    $types = "ssssssss";
    
    // Add new fields to the query
    if ($userType === 'admin' || $user_type === 'member') {
        $sql_parts = array_merge($sql_parts, [
            "nationality = ?",
            "id_number = ?",
            "home_language = ?",
            "address_street = ?",
            "address_suburb = ?",
            "address_city = ?",
            "address_province = ?",
            "address_postal_code = ?",
            "medical_aid_name = ?",
            "medical_aid_holder = ?",
            "medical_aid_number = ?",
            "emergency_contact_name = ?",
            "emergency_contact_relationship = ?",
            "emergency_contact_phone = ?",
            "emergency_contact_email = ?",
            "emergency_contact_address = ?",
            "interests = ?",
            "role_models = ?",
            "goals = ?",
            "has_computer = ?",
            "computer_skills = ?",
            "computer_skills_source = ?"
        ]);
        
        $params = array_merge($params, [
            $nationality,
            $id_number,
            $home_language,
            $address_street,
            $address_suburb,
            $address_city,
            $address_province,
            $address_postal_code,
            $medical_aid_name,
            $medical_aid_holder,
            $medical_aid_number,
            $emergency_contact_name,
            $emergency_contact_relationship,
            $emergency_contact_phone,
            $emergency_contact_email,
            $emergency_contact_address,
            $interests,
            $role_models,
            $goals,
            $has_computer,
            $computer_skills,
            $computer_skills_source
        ]);
        
        $types .= "sssssssssssssssssssisss";
    }

    // Add member-specific fields if user is a member
    if ($user_type === 'member') {
        $sql_parts = array_merge($sql_parts, [
            "grade = ?", 
            "school = ?", 
            "leaner_number = ?", 
            "parent = ?", 
            "parent_email = ?", 
            "parent_number = ?", 
            "Relationship = ?"
        ]);
        
        $params = array_merge($params, [
            $grade, 
            $school, 
            $learner_number, 
            $parent, 
            $parent_email, 
            $parent_number, 
            $relationship
        ]);
        
        $types .= "ississs";
    }

    // Add password update if provided
    if ($password) {
        $sql_parts[] = "password = ?";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $params[] = $hashedPassword;
        $types .= "s";
    }

    // Build the complete SQL query
    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    $params[] = $editUserId;
    $types .= "i";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $successMsg = "Profile updated successfully!";
    } else {
        $errorMsg = "Error updating profile: " . $stmt->error;
    }
}
?>