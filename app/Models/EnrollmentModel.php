<?php
class EnrollmentModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getUserEnrollments($userId) {
        $enrollments = [];
        $sql = "SELECT e.id, e.course_id, e.progress, e.last_accessed, 
                    c.title, c.type, c.description, c.difficulty_level, c.image_path 
                FROM user_enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ? 
                ORDER BY e.last_accessed DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $enrollments[] = $row;
            }
        }
        
        return $enrollments;
    }
    
    /**
     * Check if a user is enrolled in a specific course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if enrolled, false otherwise
     */
    public function isUserEnrolled($userId, $courseId) {
        // Input validation
        if (!$userId || !$courseId) {
            return false;
        }
        
        $sql = "SELECT id FROM user_enrollments WHERE user_id = ? AND course_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            // Handle database error
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $courseId);
        $executed = $stmt->execute();
        
        if (!$executed) {
            return false;
        }
        
        $result = $stmt->get_result();
        return ($result && $result->num_rows > 0);
    }
    
    public function enrollUser($userId, $courseId) {
        if (!$this->isUserEnrolled($userId, $courseId)) {
            $sql = "INSERT INTO user_enrollments (user_id, course_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $courseId);
            $success = $stmt->execute();
            
            if ($success) {
                // Update enrollment count
                $updateSql = "UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("i", $courseId);
                $updateStmt->execute();
                
                return true;
            }
            return false;
        }
        
        return false;
    }
    
    /**
     * Get user progress for a course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Progress data including percent completion, completion status, etc.
     */
    public function getUserProgress($userId, $courseId) {
        $progressData = [
            'percent' => 0,
            'completed' => false,
            'last_accessed' => null,
            'started' => false
        ];
        
        if (!$userId || !$courseId) {
            return $progressData;
        }
        
        $sql = "SELECT progress, completed, last_accessed 
                FROM user_enrollments 
                WHERE user_id = ? AND course_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return $progressData;
        }
        
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $progressData = [
                'percent' => (int)$row['progress'],
                'completed' => (bool)($row['completed'] ?? false),
                'last_accessed' => $row['last_accessed'],
                'started' => true
            ];
        }
        
        return $progressData;
    }
    
    /**
     * Check if a specific lesson is completed by a user
     * 
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool True if completed, false otherwise
     */
    public function isLessonCompleted($userId, $lessonId) {
        if (!$userId || !$lessonId) {
            return false;
        }
        
        $sql = "SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)$row['completed'];
        }
        
        return false;
    }
}
?>