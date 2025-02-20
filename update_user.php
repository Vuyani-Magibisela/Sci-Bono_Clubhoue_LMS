<?php
session_start();
require 'server.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
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
        header('Location: settings.php');
        exit;
    }

    // Get form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : null;
    $password = $_POST['password'];
    $date_of_birth = $_POST['dob'];
    $gender = $_POST['gender'];
    $center = $_POST['center'];

    // For member-specific fields
    $grade = isset($_POST['grade']) ? $_POST['grade'] : null;
    $school = isset($_POST['school']) ? $_POST['school'] : null;
    $learner_number = isset($_POST['learner_number']) ? $_POST['learner_number'] : null;
    $parent = isset($_POST['parent']) ? $_POST['parent'] : null;
    $parent_email = isset($_POST['parent_email']) ? $_POST['parent_email'] : null;
    $parent_number = isset($_POST['parent_number']) ? $_POST['parent_number'] : null;
    $relationship = isset($_POST['relationship']) ? $_POST['relationship'] : null;

    try {
        // Build SQL based on user type and whether password is being updated
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Include password in update
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    name = ?, 
                    surname = ?, 
                    date_of_birth = ?, 
                    Gender = ?, 
                    Center = ?,
                    password = ? ";
            $params = [$username, $email, $name, $surname, $date_of_birth, $gender, $center, $hashedPassword];
            $types = "ssssssss";
        } else {
            // Exclude password from update
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    name = ?, 
                    surname = ?, 
                    date_of_birth = ?, 
                    Gender = ?, 
                    Center = ? ";
            $params = [$username, $email, $name, $surname, $date_of_birth, $gender, $center];
            $types = "sssssss";
        }

        // Add user type if admin is editing
        if ($current_user_type === 'admin' && $userType) {
            $sql .= ", user_type = ? ";
            $params[] = $userType;
            $types .= "s";
        }

        // Add member-specific fields if applicable
        if ($userType === 'member' || (!$userType && $current_user_type === 'member')) {
            $sql .= ", grade = ?, school = ?, leaner_number = ?, parent = ?, parent_email = ?, parent_number = ?, Relationship = ? ";
            $params = array_merge($params, [$grade, $school, $learner_number, $parent, $parent_email, $parent_number, $relationship]);
            $types .= "ssissss";
        }

        $sql .= "WHERE id = ?";
        $params[] = $userId;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Redirect based on context
            if ($current_user_type === 'admin' && $userId != $current_user_id) {
                header('Location: user_list.php');
            } else {
                header('Location: settings.php');
            }
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
            header('Location: settings.php');
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location: settings.php');
    }
    exit;
}
?>