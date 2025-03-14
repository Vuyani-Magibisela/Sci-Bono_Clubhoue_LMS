<?php
class LessonModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getLessonDetails($lessonId) {
        $sql = "SELECT l.*, s.title as section_title, s.course_id, c.title as course_title
                FROM course_lessons l
                JOIN course_sections s ON l.section_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE l.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    public function getSectionLessons($sectionId) {
        $lessons = [];
        $sql = "SELECT * FROM course_lessons 
                WHERE section_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
        }
        
        return $lessons;
    }
    
    // Add other lesson-related methods here
}
?>