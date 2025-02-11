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
    
    // Convert date of birth to MySQL format
    $date_of_birth = !empty($_POST['dob']) ? date('Y-m-d', strtotime($_POST['dob'])) : null;
    
    // School details (only for members)
    $grade = isset($_POST['grade']) ? intval($_POST['grade']) : null;
    $school = isset($_POST['school']) ? intval($_POST['school']) : null;
    
    // Parent details (only for members)
    $parent = isset($_POST['parent']) ? intval($_POST['parent']) : null;
    $parent_email = isset($_POST['parent_email']) ? trim($_POST['parent_email']) : null;
    $learner_number = isset($_POST['learner_number']) ? intval($_POST['learner_number']) : null;
    $parent_number = isset($_POST['parent_number']) ? intval($_POST['parent_number']) : null;
    $relationship = isset($_POST['relationship']) ? trim($_POST['relationship']) : null;

    // Build the SQL query dynamically
    $sql = "UPDATE users SET 
            username = ?, 
            email = ?, 
            name = ?,
            surname = ?,
            user_type = ?,
            date_of_birth = ?";

    // Add member-specific fields if user is a member
    if ($user_type === 'member') {
        $sql .= ", grade = ?, 
                  school = ?, 
                  parent = ?, 
                  parent_email = ?, 
                  leaner_number = ?, 
                  parent_number = ?, 
                  Relationship = ?";
    }

    // Add password update if provided
    if ($password) {
        $sql .= ", password = ?";
    }

    $sql .= " WHERE id = ?";

    $stmt = $conn->prepare($sql);

    // Bind parameters based on user type and password
    if ($user_type === 'member') {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssssiiiissssi", 
                $username, $email, $name, $surname, $user_type, $date_of_birth,
                $grade, $school, $parent, $parent_email, $learner_number,
                $parent_number, $relationship, $hashedPassword, $editUserId
            );
        } else {
            $stmt->bind_param("ssssssiiissssi", 
                $username, $email, $name, $surname, $user_type, $date_of_birth,
                $grade, $school, $parent, $parent_email, $learner_number,
                $parent_number, $relationship, $editUserId
            );
        }
    } else {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssssssi", 
                $username, $email, $name, $surname, $user_type, 
                $date_of_birth, $hashedPassword, $editUserId
            );
        } else {
            $stmt->bind_param("ssssssi", 
                $username, $email, $name, $surname, $user_type, 
                $date_of_birth, $editUserId
            );
        }
    }

    if ($stmt->execute()) {
        $successMsg = "Profile updated successfully!";
    } else {
        $errorMsg = "Error updating profile: " . $stmt->error;
    }
}
?>