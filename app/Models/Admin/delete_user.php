<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] != 'admin') {
    // Set error message
    $_SESSION['message'] = "You do not have permission to delete users.";
    $_SESSION['message_type'] = "danger";
    
    // Redirect to home page
    header("Location: home.php");
    exit;
}

// Include database connection
require_once 'server.php';

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate user ID
if ($userId <= 0) {
    $_SESSION['message'] = "Invalid user ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: user_list.php");
    exit;
}

// Prevent deletion of own account
if ($userId == $_SESSION['id']) {
    $_SESSION['message'] = "You cannot delete your own account.";
    $_SESSION['message_type'] = "danger";
    header("Location: user_list.php");
    exit;
}

// Get user details to check if exists and for logging
$checkSql = "SELECT username, name, surname, user_type FROM users WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows == 0) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: user_list.php");
    exit;
}

$userData = $checkResult->fetch_assoc();
$userFullName = $userData['name'] . ' ' . $userData['surname'];
$userType = $userData['user_type'];

// Begin transaction for data integrity
$conn->begin_transaction();

try {
    // Delete associated records first to maintain database integrity
    
    // 1. Delete attendance records
    $deleteAttendanceSql = "DELETE FROM attendance WHERE user_id = ?";
    $deleteAttendanceStmt = $conn->prepare($deleteAttendanceSql);
    $deleteAttendanceStmt->bind_param("i", $userId);
    $deleteAttendanceStmt->execute();
    
    // 2. Delete course enrollments and progress
    $deleteEnrollmentsSql = "DELETE FROM user_enrollments WHERE user_id = ?";
    $deleteEnrollmentsStmt = $conn->prepare($deleteEnrollmentsSql);
    $deleteEnrollmentsStmt->bind_param("i", $userId);
    $deleteEnrollmentsStmt->execute();
    
    $deleteLessonProgressSql = "DELETE FROM lesson_progress WHERE user_id = ?";
    $deleteLessonProgressStmt = $conn->prepare($deleteLessonProgressSql);
    $deleteLessonProgressStmt->bind_param("i", $userId);
    $deleteLessonProgressStmt->execute();
    
    // 4. Delete the user record
    $deleteUserSql = "DELETE FROM users WHERE id = ?";
    $deleteUserStmt = $conn->prepare($deleteUserSql);
    $deleteUserStmt->bind_param("i", $userId);
    $deleteUserStmt->execute();
    
    // Commit transaction if all operations succeeded
    $conn->commit();
    
    // Set success message
    $_SESSION['message'] = "User \"$userFullName\" has been successfully deleted.";
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    // Rollback transaction if any operation failed
    $conn->rollback();
    
    // Set error message
    $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Redirect back to user list
header("Location: user_list.php");
exit;
?>