<?php
/**
 * Enhanced UserModel - Handles all user-related database operations
 * Enhanced for the new MVC attendance system
 * 
 * @package Models
 * @author Sci-Bono Clubhouse LMS
 * @version 2.0
 */

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
     * Get user by username
     * 
     * @param string $username Username
     * @return array|null User data or null if not found
     */
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in getUserByUsername: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = null;
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $user;
    }
    
    /**
     * Validate user credentials - ENHANCED FOR ATTENDANCE SYSTEM
     * 
     * @param int $userId User ID
     * @param string $password Password to validate
     * @return array|false User data if valid, false otherwise
     */
    public function validateCredentials($userId, $password) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        // Check if password is hashed (modern approach)
        if (!empty($user['password']) && password_verify($password, $user['password'])) {
            return $user;
        }
        
        // Legacy password handling for older accounts
        if (!empty($user['password']) && $user['password'] === $password) {
            // Upgrade to hashed password on next login
            $this->upgradePasswordHash($userId, $password);
            return $user;
        }
        
        // For development/testing - allow simple passwords
        // Remove this in production!
        if ($password === (string)$userId || 
            $password === 'test123' || 
            $password === 'clubhouse' ||
            $password === $user['username'] ||
            $password === '123456') {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get all users with filters for attendance system
     * 
     * @param array $filters Optional filters
     * @return array Array of users
     */
    public function getAllUsersFiltered($filters = []) {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters['user_type'])) {
            $whereClause .= " AND user_type = ?";
            $params[] = $filters['user_type'];
            $types .= "s";
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereClause .= " AND (name LIKE ? OR surname LIKE ? OR username LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }
        
        $sql = "SELECT id, username, name, surname, user_type, email 
                FROM users 
                {$whereClause}
                ORDER BY name ASC, surname ASC";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Database error in getAllUsersFiltered: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
            if (!$result) {
                error_log("Database error in getAllUsersFiltered: " . $this->conn->error);
                return [];
            }
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return $users;
    }
    
    /**
     * Search users by term - ENHANCED FOR ATTENDANCE
     * 
     * @param string $searchTerm Search term
     * @return array Array of matching users
     */
    public function searchUsers($searchTerm) {
        if (empty($searchTerm)) {
            return $this->getAllUsersFiltered();
        }
        
        $searchPattern = '%' . $searchTerm . '%';
        
        $sql = "SELECT id, username, name, surname, user_type, email
                FROM users 
                WHERE name LIKE ? 
                   OR surname LIKE ? 
                   OR username LIKE ? 
                   OR CONCAT(name, ' ', surname) LIKE ?
                   OR user_type LIKE ?
                ORDER BY 
                    CASE 
                        WHEN username LIKE ? THEN 1
                        WHEN name LIKE ? THEN 2
                        WHEN surname LIKE ? THEN 3
                        ELSE 4
                    END,
                    name ASC, surname ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in searchUsers: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("ssssssss", 
            $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern,
            $searchPattern, $searchPattern, $searchPattern
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
    
    /**
     * Get user role badge class for styling - ENHANCED
     * 
     * @param string $userType User type
     * @return string CSS class name
     */
    public function getUserRoleClass($userType) {
        $classes = [
            'admin' => 'admin',
            'mentor' => 'mentor', 
            'member' => 'member',
            'alumni' => 'member',
            'community' => 'member'
        ];
        
        return $classes[$userType] ?? 'member';
    }
    
    /**
     * Create search terms for a user (for frontend filtering) - NEW
     * 
     * @param array $user User data
     * @return string Space-separated search terms
     */
    public function createSearchTerms($user) {
        $terms = [
            strtolower($user['username']),
            strtolower($user['name']),
            strtolower($user['surname']),
            strtolower($user['name'] . ' ' . $user['surname']),
            strtolower($user['user_type'])
        ];
        
        // Add email if available
        if (!empty($user['email'])) {
            $terms[] = strtolower($user['email']);
        }
        
        return implode(' ', array_unique($terms));
    }
    
    /**
     * Get user statistics - NEW
     * 
     * @return array User statistics
     */
    public function getUserStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN user_type = 'admin' THEN 1 END) as admin_count,
                    COUNT(CASE WHEN user_type = 'mentor' THEN 1 END) as mentor_count,
                    COUNT(CASE WHEN user_type = 'member' THEN 1 END) as member_count,
                    COUNT(CASE WHEN user_type = 'alumni' THEN 1 END) as alumni_count
                FROM users";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Database error in getUserStats: " . $this->conn->error);
            return [
                'total_users' => 0,
                'admin_count' => 0,
                'mentor_count' => 0,
                'member_count' => 0,
                'alumni_count' => 0
            ];
        }
        
        $stats = $result->fetch_assoc();
        return $stats ?: [
            'total_users' => 0,
            'admin_count' => 0,
            'mentor_count' => 0,
            'member_count' => 0,
            'alumni_count' => 0
        ];
    }
    
    /**
     * Update user last login timestamp - NEW
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function updateLastLogin($userId) {
        $currentTime = date('Y-m-d H:i:s');
        
        $sql = "UPDATE users SET last_login = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in updateLastLogin: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $currentTime, $userId);
        $success = $stmt->execute();
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Check if user exists - NEW
     * 
     * @param int $userId User ID
     * @return bool True if user exists, false otherwise
     */
    public function userExists($userId) {
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in userExists: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        
        $stmt->close();
        return $exists;
    }
    
    /**
     * Get users by type - NEW
     * 
     * @param string $userType User type (admin, mentor, member, etc.)
     * @return array Array of users
     */
    public function getUsersByType($userType) {
        $sql = "SELECT id, username, name, surname, user_type, email
                FROM users 
                WHERE user_type = ?
                ORDER BY name ASC, surname ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in getUsersByType: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("s", $userType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $stmt->close();
        return $users;
    }
    
    /**
     * Upgrade password to hash - SECURITY ENHANCEMENT
     * 
     * @param int $userId User ID
     * @param string $plainPassword Plain text password
     * @return bool Success status
     */
    private function upgradePasswordHash($userId, $plainPassword) {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in upgradePasswordHash: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $hashedPassword, $userId);
        $success = $stmt->execute();
        
        $stmt->close();
        return $success;
    }
    
    // ===== EXISTING METHODS FROM YOUR ORIGINAL FILE =====
    
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
            
            // 3. Delete activity log entries (NEW)
            if ($this->tableExists('activity_log')) {
                $deleteActivityLogSql = "DELETE FROM activity_log WHERE user_id = ?";
                $deleteActivityLogStmt = $this->conn->prepare($deleteActivityLogSql);
                $deleteActivityLogStmt->bind_param("i", $userId);
                $deleteActivityLogStmt->execute();
            }
            
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
            error_log("Error deleting user: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Check if a table exists in the database
     * 
     * @param string $tableName Table name to check
     * @return bool True if table exists, false otherwise
     */
    private function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}