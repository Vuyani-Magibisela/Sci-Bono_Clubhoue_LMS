<?php
class AttendanceRegisterModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
   /**
 * Get attendance data for a specific date
 * 
 * @param string $date Date in Y-m-d format
 * @return array Attendance records
 */
public function getAttendanceByDate($date) {
    $sql = "SELECT a.id, a.user_id, a.checked_in, a.checked_out, a.sign_in_status, 
                   u.username, u.name, u.surname, u.user_type, u.Center, u.Gender,
                   u.grade, u.date_of_birth
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE DATE(a.checked_in) = ?
            ORDER BY u.user_type, u.surname, u.name";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendees = [];
    while ($row = $result->fetch_assoc()) {
        $attendees[] = $row;
    }
    
    return $attendees;
}
    
    /**
     * Get attendance count by user type for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return array Count of attendees by user type
     */
    public function getAttendanceCountByType($date) {
        $sql = "SELECT u.user_type, COUNT(DISTINCT a.user_id) as count
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE DATE(a.checked_in) = ?
                GROUP BY u.user_type";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $counts = [
            'admin' => 0,
            'mentor' => 0,
            'member' => 0,
            'community' => 0,
            'alumni' => 0,
            'total' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            $counts[$row['user_type']] = $row['count'];
            $counts['total'] += $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get active dates with attendance records
     * 
     * @param int $limit Number of dates to return
     * @return array Dates with attendance records
     */
    public function getActiveDates($limit = 30) {
        $sql = "SELECT DISTINCT DATE(checked_in) as attendance_date
                FROM attendance
                ORDER BY attendance_date DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['attendance_date'];
        }
        
        return $dates;
    }
}
?>