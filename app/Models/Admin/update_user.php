<?php
session_start();
require '../../../server.php';
require __DIR__ . '/../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location:'.BASE_URL.'login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];
    $current_user_type = $_SESSION['user_type'];
    $current_user_id = $_SESSION['user_id'];

    // Check permissions
    $can_edit = false;
    if ($current_user_type === 'admin') {
        $can_edit = true;
    } elseif ($current_user_type === 'mentor' && $userId != $current_user_id) {
        // Check if target user is a member
        $check_sql = "SELECT user_type FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $userId);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $user_data = $result->fetch_assoc();
        $can_edit = ($user_data['user_type'] === 'member');
        $check_stmt->close();
    } elseif ($userId == $current_user_id) {
        $can_edit = true;
    }

    if (!$can_edit) {
        $_SESSION['error_message'] = "You don't have permission to edit this user.";
        header('Location:' . BASE_URL . 'app/Views/settings.php');
        exit;
    }

    // Basic user data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $date_of_birth = $_POST['dob'];
    $gender = $_POST['gender'];
    $center = $_POST['center'];
    $password = $_POST['password'];
    
    // New fields from Clubhouse Registration Form
    $nationality = isset($_POST['nationality']) ? $_POST['nationality'] : null;
    $id_number = isset($_POST['id_number']) ? $_POST['id_number'] : null;
    $home_language = isset($_POST['home_language']) ? $_POST['home_language'] : null;
    
    // Address information
    $address_street = isset($_POST['address_street']) ? $_POST['address_street'] : null;
    $address_suburb = isset($_POST['address_suburb']) ? $_POST['address_suburb'] : null;
    $address_city = isset($_POST['address_city']) ? $_POST['address_city'] : null;
    $address_province = isset($_POST['address_province']) ? $_POST['address_province'] : null;
    $address_postal_code = isset($_POST['address_postal_code']) ? $_POST['address_postal_code'] : null;
    
    // Medical information
    $medical_aid_name = isset($_POST['medical_aid_name']) ? $_POST['medical_aid_name'] : null;
    $medical_aid_holder = isset($_POST['medical_aid_holder']) ? $_POST['medical_aid_holder'] : null;
    $medical_aid_number = isset($_POST['medical_aid_number']) ? $_POST['medical_aid_number'] : null;
    
    // Emergency contact
    $emergency_contact_name = isset($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : null;
    $emergency_contact_relationship = isset($_POST['emergency_contact_relationship']) ? $_POST['emergency_contact_relationship'] : null;
    $emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : null;
    $emergency_contact_email = isset($_POST['emergency_contact_email']) ? $_POST['emergency_contact_email'] : null;
    $emergency_contact_address = isset($_POST['emergency_contact_address']) ? $_POST['emergency_contact_address'] : null;
    
    // Interests and skills
    $interests = isset($_POST['interests']) ? $_POST['interests'] : null;
    $role_models = isset($_POST['role_models']) ? $_POST['role_models'] : null;
    $goals = isset($_POST['goals']) ? $_POST['goals'] : null;
    $has_computer = isset($_POST['has_computer']) ? $_POST['has_computer'] : null;
    $computer_skills = isset($_POST['computer_skills']) ? $_POST['computer_skills'] : null;
    $computer_skills_source = isset($_POST['computer_skills_source']) ? $_POST['computer_skills_source'] : null;
    
    // Cell number (repurposing leaner_number field for all user types)
    $cell_number = isset($_POST['cell_number']) ? $_POST['cell_number'] : null;
    
    // User type (if admin is editing)
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : null;

    // For member-specific fields
    $grade = isset($_POST['grade']) ? $_POST['grade'] : null;
    $school = isset($_POST['school']) ? $_POST['school'] : null;
    $learner_number = isset($_POST['learner_number']) ? $_POST['learner_number'] : $cell_number; // Use cell_number as fallback
    $parent = isset($_POST['parent']) ? $_POST['parent'] : null;
    $parent_email = isset($_POST['parent_email']) ? $_POST['parent_email'] : null;
    $parent_number = isset($_POST['parent_number']) ? $_POST['parent_number'] : null;
    $relationship = isset($_POST['relationship']) ? $_POST['relationship'] : null;

    try {
        // Start building the SQL query
        $sql_parts = [
            "username = ?", 
            "email = ?", 
            "name = ?", 
            "surname = ?", 
            "date_of_birth = ?", 
            "Gender = ?", 
            "Center = ?",
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
        ];
        
        $params = [
            $username, 
            $email, 
            $name, 
            $surname, 
            $date_of_birth, 
            $gender, 
            $center,
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
        ];
        
        $types = "sssssssssssssssssssssssssssss";

        // Include password if it's being updated
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql_parts[] = "password = ?";
            $params[] = $hashedPassword;
            $types .= "s";
        }

        // Add user type if admin is editing
        if ($current_user_type === 'admin' && $userType) {
            $sql_parts[] = "user_type = ?";
            $params[] = $userType;
            $types .= "s";
        }

        // Add member-specific fields for all user types (will be NULL for non-members)
        $sql_parts[] = "grade = ?";
        $sql_parts[] = "school = ?";
        $sql_parts[] = "leaner_number = ?";
        $sql_parts[] = "parent = ?";
        $sql_parts[] = "parent_email = ?";
        $sql_parts[] = "parent_number = ?";
        $sql_parts[] = "Relationship = ?";
        
        $params = array_merge($params, [$grade, $school, $learner_number, $parent, $parent_email, $parent_number, $relationship]);
        $types .= "ssissss";

        // Build the complete SQL statement
        $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = ?";
        $params[] = $userId;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Redirect based on context
            if ($current_user_type === 'admin' && $userId != $current_user_id) {
                header('Location:' .BASE_URL. 'app/Views/user_list.php');
            } else {
                header('Location: ' .BASE_URL. 'app/Views/settings.php');
            }
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
            header('Location:' .BASE_URL. 'settings.php');
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location:' .BASE_URL. 'settings.php');
    }
    exit;
}
?>