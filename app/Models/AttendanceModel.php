<?php
/**
 * AttendanceModel - Handles all attendance-related database operations
 *
 * @package Models
 * @author Sci-Bono Clubhouse LMS
 * @version 1.0
 */

require_once __DIR__ . '/BaseModel.php';

class AttendanceModel extends BaseModel {
    protected $table = 'attendance';
    protected $fillable = [
        'user_id', 'checked_in', 'checked_out', 'sign_in_status'
    ];
    protected $timestamps = false; // This table doesn't have created_at/updated_at

    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Check if a user is currently signed in
     * 
     * @param int $userId User ID to check
     * @return bool True if user is signed in, false otherwise
     */
    public function isUserSignedIn($userId) {
        $sql = "SELECT id FROM attendance WHERE user_id = ? AND sign_in_status = 'signedIn'";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in isUserSignedIn: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $isSignedIn = $result->num_rows > 0;
        
        $stmt->close();
        return $isSignedIn;
    }
    
    /**
     * Sign in a user
     * 
     * @param int $userId User ID to sign in
     * @return array Result array with success status and message
     */
    public function signInUser($userId) {
        $currentTime = date('Y-m-d H:i:s');
        
        // Check if user is already signed in
        if ($this->isUserSignedIn($userId)) {
            return [
                'success' => false, 
                'message' => 'User is already signed in',
                'code' => 'ALREADY_SIGNED_IN'
            ];
        }
        
        // Insert new attendance record
        $sql = "INSERT INTO attendance (user_id, checked_in, sign_in_status) VALUES (?, ?, 'signedIn')";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in signInUser: " . $this->conn->error);
            return [
                'success' => false, 
                'message' => 'Database error occurred',
                'code' => 'DATABASE_ERROR'
            ];
        }
        
        $stmt->bind_param("is", $userId, $currentTime);
        $success = $stmt->execute();
        
        if ($stmt->error) {
            error_log("Execute error in signInUser: " . $stmt->error);
        }
        
        $stmt->close();
        
        if ($success) {
            return [
                'success' => true, 
                'message' => 'Successfully signed in',
                'timestamp' => $currentTime,
                'code' => 'SIGN_IN_SUCCESS'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to sign in',
                'code' => 'SIGN_IN_FAILED'
            ];
        }
    }
    
    /**
     * Sign out a user
     * 
     * @param int $userId User ID to sign out
     * @return array Result array with success status and message
     */
    public function signOutUser($userId) {
        $currentTime = date('Y-m-d H:i:s');
        
        // Check if user is actually signed in
        if (!$this->isUserSignedIn($userId)) {
            return [
                'success' => false, 
                'message' => 'User is not currently signed in',
                'code' => 'NOT_SIGNED_IN'
            ];
        }
        
        // Update attendance record to sign out
        $sql = "UPDATE attendance SET checked_out = ?, sign_in_status = 'signedOut' 
                WHERE user_id = ? AND sign_in_status = 'signedIn'";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in signOutUser: " . $this->conn->error);
            return [
                'success' => false, 
                'message' => 'Database error occurred',
                'code' => 'DATABASE_ERROR'
            ];
        }
        
        $stmt->bind_param("si", $currentTime, $userId);
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        
        if ($stmt->error) {
            error_log("Execute error in signOutUser: " . $stmt->error);
        }
        
        $stmt->close();
        
        if ($success && $affectedRows > 0) {
            return [
                'success' => true, 
                'message' => 'Successfully signed out',
                'timestamp' => $currentTime,
                'code' => 'SIGN_OUT_SUCCESS'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to sign out',
                'code' => 'SIGN_OUT_FAILED'
            ];
        }
    }
    
    /**
     * Get attendance record for a user
     * 
     * @param int $userId User ID
     * @return array|null Attendance record or null if not found
     */
    public function getUserAttendance($userId) {
        $sql = "SELECT * FROM attendance WHERE user_id = ? AND sign_in_status = 'signedIn' ORDER BY checked_in DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in getUserAttendance: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance = null;
        if ($result->num_rows > 0) {
            $attendance = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $attendance;
    }
    
    /**
     * Get all users with their current attendance status
     * 
     * @return array Array of users with attendance information
     */
    public function getAllUsersWithAttendance() {
        $sql = "SELECT u.*, 
                       a.checked_in, 
                       a.sign_in_status,
                       a.id as attendance_id
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id 
                AND a.sign_in_status = 'signedIn'
                ORDER BY u.name ASC, u.surname ASC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Database error in getAllUsersWithAttendance: " . $this->conn->error);
            return [];
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Get currently signed-in users
     * 
     * @return array Array of currently signed-in users
     */
    public function getSignedInUsers() {
        $sql = "SELECT u.*, a.checked_in, a.id as attendance_id
                FROM users u
                INNER JOIN attendance a ON u.id = a.user_id
                WHERE a.sign_in_status = 'signedIn'
                ORDER BY a.checked_in DESC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Database error in getSignedInUsers: " . $this->conn->error);
            return [];
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Get attendance statistics for today
     * 
     * @return array Attendance statistics
     */
    public function getTodayAttendanceStats() {
        $today = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(DISTINCT CASE WHEN sign_in_status = 'signedIn' THEN user_id END) as currently_signed_in,
                    COUNT(DISTINCT CASE WHEN DATE(checked_in) = ? THEN user_id END) as total_visited_today,
                    COUNT(DISTINCT user_id) as total_unique_visitors
                FROM attendance 
                WHERE DATE(checked_in) = ? OR sign_in_status = 'signedIn'";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in getTodayAttendanceStats: " . $this->conn->error);
            return [
                'currently_signed_in' => 0,
                'total_visited_today' => 0,
                'total_unique_visitors' => 0
            ];
        }
        
        $stmt->bind_param("ss", $today, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats ?: [
            'currently_signed_in' => 0,
            'total_visited_today' => 0,
            'total_unique_visitors' => 0
        ];
    }
    
    /**
     * Get attendance history for a specific user
     * 
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Attendance history
     */
    public function getUserAttendanceHistory($userId, $limit = 10) {
        $sql = "SELECT * FROM attendance 
                WHERE user_id = ? 
                ORDER BY checked_in DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in getUserAttendanceHistory: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
    }
    
    /**
     * Clean up old attendance records (utility method)
     * 
     * @param int $daysOld Number of days old records to clean up
     * @return bool Success status
     */
    public function cleanUpOldRecords($daysOld = 90) {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
        
        $sql = "DELETE FROM attendance WHERE DATE(checked_in) < ? AND sign_in_status = 'signedOut'";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in cleanUpOldRecords: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("s", $cutoffDate);
        $success = $stmt->execute();
        
        if ($success) {
            $deletedRows = $stmt->affected_rows;
            error_log("Cleaned up {$deletedRows} old attendance records");
        }
        
        $stmt->close();
        return $success;
    }
}