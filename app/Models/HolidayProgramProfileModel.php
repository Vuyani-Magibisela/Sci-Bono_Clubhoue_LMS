
<?php
class HolidayProgramProfileModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get attendee by email for verification
     */
    public function getAttendeeByEmail($email) {
        $sql = "SELECT id, first_name, last_name, email, password, mentor_registration, 
                       program_id, registration_status 
                FROM holiday_program_attendees 
                WHERE email = ? AND registration_status IN ('confirmed', 'pending')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get full attendee profile data
     */
    public function getAttendeeProfile($attendeeId) {
        $sql = "SELECT hpa.*, hp.title as program_title, hp.dates as program_dates
                FROM holiday_program_attendees hpa
                LEFT JOIN holiday_programs hp ON hpa.program_id = hp.id
                WHERE hpa.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attendeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Update attendee password
     */
    public function updatePassword($attendeeId, $hashedPassword) {
        $sql = "UPDATE holiday_program_attendees 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $attendeeId);
        
        return $stmt->execute();
    }
    
    /**
     * Update attendee profile
     */
    public function updateProfile($attendeeId, $profileData) {
        // Build dynamic update query
        $updateFields = [];
        $updateValues = [];
        $types = '';
        
        foreach ($profileData as $field => $value) {
            $updateFields[] = "`$field` = ?";
            $updateValues[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }
        
        $sql = "UPDATE holiday_program_attendees 
                SET " . implode(', ', $updateFields) . " 
                WHERE id = ?";
        
        $updateValues[] = $attendeeId;
        $types .= 'i';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$updateValues);
        
        return $stmt->execute();
    }
    
    /**
     * Check if email is unique
     */
    public function isEmailUnique($email, $excludeId = null) {
        $sql = "SELECT id FROM holiday_program_attendees WHERE email = ?";
        $params = [$email];
        $types = "s";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows === 0;
    }
    
    /**
     * Log profile update for audit trail
     */
    public function logProfileUpdate($attendeeId, $userId, $updateData) {
        $sql = "INSERT INTO holiday_program_audit_trail 
                (program_id, user_id, action, new_values, ip_address, created_at) 
                VALUES (
                    (SELECT program_id FROM holiday_program_attendees WHERE id = ?), 
                    ?, 'profile_update', ?, ?, NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $auditValues = json_encode($updateData);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("iiss", $attendeeId, $userId, $auditValues, $ipAddress);
        
        return $stmt->execute();
    }
}
