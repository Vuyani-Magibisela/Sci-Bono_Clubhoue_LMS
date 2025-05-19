<?php
class UserModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all users based on user type
     * 
     * @param string $userType The type of user ('admin', 'mentor', etc.)
     * @return array List of users matching criteria
     */
    public function getAllUsers($userType = null) {
        if ($userType === 'admin') {
            // Admin can see all users
            $sql = "SELECT * FROM users ORDER BY name ASC";
            $stmt = $this->conn->prepare($sql);
        } else {
            // Mentors can only see members
            $sql = "SELECT * FROM users WHERE user_type = 'member' ORDER BY name ASC";
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get a single user by ID
     * 
     * @param int $userId The user's ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Update user information
     * 
     * @param array $userData Array of user data to update
     * @return bool Success status
     */
    public function updateUser($userData) {
        // Start with the basic fields that are always present
        $sql = "UPDATE users SET 
                name = ?, 
                surname = ?, 
                username = ?, 
                email = ?";
        
        $params = [
            $userData['name'],
            $userData['surname'],
            $userData['username'],
            $userData['email']
        ];
        $types = "ssss";
        
        // Add more fields conditionally
        if (!empty($userData['nationality'])) {
            $sql .= ", nationality = ?";
            $params[] = $userData['nationality'];
            $types .= "s";
        }
        
        if (!empty($userData['gender'])) {
            $sql .= ", Gender = ?";
            $params[] = $userData['gender'];
            $types .= "s";
        }
        
        if (!empty($userData['dob'])) {
            $sql .= ", date_of_birth = ?";
            $params[] = $userData['dob'];
            $types .= "s";
        }
        
        if (!empty($userData['id_number'])) {
            $sql .= ", id_number = ?";
            $params[] = $userData['id_number'];
            $types .= "s";
        }
        
        if (!empty($userData['home_language'])) {
            $sql .= ", home_language = ?";
            $params[] = $userData['home_language'];
            $types .= "s";
        }
        
        if (!empty($userData['address_street'])) {
            $sql .= ", address_street = ?";
            $params[] = $userData['address_street'];
            $types .= "s";
        }
        
        if (!empty($userData['address_suburb'])) {
            $sql .= ", address_suburb = ?";
            $params[] = $userData['address_suburb'];
            $types .= "s";
        }
        
        if (!empty($userData['address_city'])) {
            $sql .= ", address_city = ?";
            $params[] = $userData['address_city'];
            $types .= "s";
        }
        
        if (!empty($userData['address_province'])) {
            $sql .= ", address_province = ?";
            $params[] = $userData['address_province'];
            $types .= "s";
        }
        
        if (!empty($userData['address_postal_code'])) {
            $sql .= ", address_postal_code = ?";
            $params[] = $userData['address_postal_code'];
            $types .= "s";
        }
        
        // For member-specific fields
        if (isset($userData['school'])) {
            $sql .= ", school = ?";
            $params[] = $userData['school'];
            $types .= "s";
        }
        
        if (isset($userData['grade'])) {
            $sql .= ", grade = ?";
            $params[] = $userData['grade'];
            $types .= "i";
        }
        
        if (isset($userData['parent'])) {
            $sql .= ", parent = ?";
            $params[] = $userData['parent'];
            $types .= "s";
        }
        
        if (isset($userData['parent_email'])) {
            $sql .= ", parent_email = ?";
            $params[] = $userData['parent_email'];
            $types .= "s";
        }
        
        if (isset($userData['relationship'])) {
            $sql .= ", Relationship = ?";
            $params[] = $userData['relationship'];
            $types .= "s";
        }
        
        if (isset($userData['parent_number'])) {
            $sql .= ", parent_number = ?";
            $params[] = $userData['parent_number'];
            $types .= "i";
        }
        
        if (isset($userData['cell_number'])) {
            $sql .= ", leaner_number = ?";
            $params[] = $userData['cell_number'];
            $types .= "i";
        }
        
        // For admin-only fields
        if (isset($userData['user_type'])) {
            $sql .= ", user_type = ?";
            $params[] = $userData['user_type'];
            $types .= "s";
        }
        
        if (isset($userData['center'])) {
            $sql .= ", Center = ?";
            $params[] = $userData['center'];
            $types .= "s";
        }
        
        // Emergency contact information
        if (isset($userData['emergency_contact_name'])) {
            $sql .= ", emergency_contact_name = ?";
            $params[] = $userData['emergency_contact_name'];
            $types .= "s";
        }
        
        if (isset($userData['emergency_contact_relationship'])) {
            $sql .= ", emergency_contact_relationship = ?";
            $params[] = $userData['emergency_contact_relationship'];
            $types .= "s";
        }
        
        if (isset($userData['emergency_contact_phone'])) {
            $sql .= ", emergency_contact_phone = ?";
            $params[] = $userData['emergency_contact_phone'];
            $types .= "s";
        }
        
        if (isset($userData['emergency_contact_email'])) {
            $sql .= ", emergency_contact_email = ?";
            $params[] = $userData['emergency_contact_email'];
            $types .= "s";
        }
        
        if (isset($userData['emergency_contact_address'])) {
            $sql .= ", emergency_contact_address = ?";
            $params[] = $userData['emergency_contact_address'];
            $types .= "s";
        }
        
        // Interest and skills information (for members)
        if (isset($userData['interests'])) {
            $sql .= ", interests = ?";
            $params[] = $userData['interests'];
            $types .= "s";
        }
        
        if (isset($userData['role_models'])) {
            $sql .= ", role_models = ?";
            $params[] = $userData['role_models'];
            $types .= "s";
        }
        
        if (isset($userData['goals'])) {
            $sql .= ", goals = ?";
            $params[] = $userData['goals'];
            $types .= "s";
        }
        
        if (isset($userData['has_computer'])) {
            $sql .= ", has_computer = ?";
            $params[] = $userData['has_computer'];
            $types .= "i";
        }
        
        if (isset($userData['computer_skills'])) {
            $sql .= ", computer_skills = ?";
            $params[] = $userData['computer_skills'];
            $types .= "s";
        }
        
        if (isset($userData['computer_skills_source'])) {
            $sql .= ", computer_skills_source = ?";
            $params[] = $userData['computer_skills_source'];
            $types .= "s";
        }
        
        // Password update (only if provided)
        if (!empty($userData['password'])) {
            $sql .= ", password = ?";
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            $params[] = $passwordHash;
            $types .= "s";
        }
        
        // Complete the query with the WHERE clause
        $sql .= " WHERE id = ?";
        $params[] = $userData['id'];
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        // Dynamically bind parameters
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        
        return $result;
    }
        
    /**
     * Delete a user and all related data
     * 
     * @param int $userId User ID to delete
     * @return bool Success or failure
     */
    public function deleteUser($userId) {
        // Get user details for logging
        $checkSql = "SELECT username, name, surname, user_type FROM users WHERE id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            return false; // User not found
        }
        
        // Begin transaction for data integrity
        $this->conn->begin_transaction();
        
        try {
            // Delete associated records first to maintain database integrity
            
            // 1. Delete attendance records
            $deleteAttendanceSql = "DELETE FROM attendance WHERE user_id = ?";
            $deleteAttendanceStmt = $this->conn->prepare($deleteAttendanceSql);
            $deleteAttendanceStmt->bind_param("i", $userId);
            $deleteAttendanceStmt->execute();
            
            // 2. Delete course enrollments and progress
            $deleteEnrollmentsSql = "DELETE FROM user_enrollments WHERE user_id = ?";
            $deleteEnrollmentsStmt = $this->conn->prepare($deleteEnrollmentsSql);
            $deleteEnrollmentsStmt->bind_param("i", $userId);
            $deleteEnrollmentsStmt->execute();
            
            $deleteLessonProgressSql = "DELETE FROM lesson_progress WHERE user_id = ?";
            $deleteLessonProgressStmt = $this->conn->prepare($deleteLessonProgressSql);
            $deleteLessonProgressStmt->bind_param("i", $userId);
            $deleteLessonProgressStmt->execute();
            
            // 4. Delete the user record
            $deleteUserSql = "DELETE FROM users WHERE id = ?";
            $deleteUserStmt = $this->conn->prepare($deleteUserSql);
            $deleteUserStmt->bind_param("i", $userId);
            $deleteUserStmt->execute();
            
            // Commit transaction if all operations succeeded
            $this->conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction if any operation failed
            $this->conn->rollback();
            
            // Log the error if you have a logging system
            // logError("Error deleting user: " . $e->getMessage());
            
            return false;
        }
    }
    
}
?>