<?php
session_start();
require 'server.php';

// Check if the user is logged in and has appropriate permissions
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['user_type'], ['admin', 'mentor'])) {
    header('Location: home.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get all form data
    $userId = $_POST['id'];
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
        // Build the SQL query based on user type
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($userType === 'member') {
                $sql = "UPDATE users SET 
                        username = ?, 
                        email = ?, 
                        name = ?, 
                        surname = ?, 
                        user_type = ?, 
                        date_of_birth = ?, 
                        Gender = ?, 
                        Center = ?, 
                        grade = ?, 
                        school = ?, 
                        parent = ?, 
                        parent_email = ?, 
                        leaner_number = ?, 
                        parent_number = ?, 
                        Relationship = ?,
                        password = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssssssssi", 
                    $username, $email, $name, $surname, 
                    $userType, $date_of_birth, $gender, 
                    $center, $grade, $school, 
                    $parent, $parent_email, $learner_number, 
                    $parent_number, $relationship, $hashedPassword, 
                    $userId
                );
            } else {
                $sql = "UPDATE users SET 
                        username = ?, 
                        email = ?, 
                        name = ?, 
                        surname = ?, 
                        user_type = ?, 
                        date_of_birth = ?, 
                        Gender = ?, 
                        Center = ?,
                        password = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssssssi", 
                    $username, $email, $name, $surname, 
                    $userType, $date_of_birth, $gender, 
                    $center, $hashedPassword, $userId
                );
            }
        } else {
            if ($userType === 'member') {
                $sql = "UPDATE users SET 
                        username = ?, 
                        email = ?, 
                        name = ?, 
                        surname = ?, 
                        user_type = ?, 
                        date_of_birth = ?, 
                        Gender = ?, 
                        Center = ?, 
                        grade = ?, 
                        school = ?, 
                        parent = ?, 
                        parent_email = ?, 
                        leaner_number = ?, 
                        parent_number = ?, 
                        Relationship = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssssssssssssi", 
                    $username, $email, $name, $surname, 
                    $userType, $date_of_birth, $gender, 
                    $center, $grade, $school, 
                    $parent, $parent_email, $learner_number, 
                    $parent_number, $relationship, $userId
                );
            } else {
                $sql = "UPDATE users SET 
                        username = ?, 
                        email = ?, 
                        name = ?, 
                        surname = ?, 
                        user_type = ?, 
                        date_of_birth = ?, 
                        Gender = ?, 
                        Center = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssi", 
                    $username, $email, $name, $surname, 
                    $userType, $date_of_birth, $gender, 
                    $center, $userId
                );
            }
        }

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating user: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    // Close the connection
    $conn->close();

    // Redirect back to settings or user list
    header('Location: ' . ($_SESSION['user_type'] === 'admin' ? 'user_list.php' : 'settings.php'));
    exit;
}
?>