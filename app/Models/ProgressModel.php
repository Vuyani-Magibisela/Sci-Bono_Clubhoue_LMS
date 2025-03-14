<?php
class ProgressModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getLessonProgress($userId, $lessonId) {
        $sql = "SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'status' => 'not_started',
            'progress' => 0,
            'completed' => false
        ];
    }
    
    public function updateLessonProgress($userId, $lessonId, $status = 'in_progress', $progress = 0, $completed = false) {
        // Implementation remains the same...
    }
    
    public function updateCourseProgress($userId, $lessonId) {
        // Implementation remains the same...
    }
    
    // Add other progress-related methods here
}
?>