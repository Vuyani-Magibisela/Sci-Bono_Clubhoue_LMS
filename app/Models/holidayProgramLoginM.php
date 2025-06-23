<?php 

class UserModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function userNeedsPassword($email, $programId) {
        $sql = "SELECT id FROM holiday_program_attendees 
                WHERE email = ? AND program_id = ? AND password IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $email, $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function updatePassword($email, $programId, $hashedPassword) {
        $sql = "UPDATE holiday_program_attendees 
                SET password = ? 
                WHERE email = ? AND program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $hashedPassword, $email, $programId);
        return $stmt->execute();
    }
}
