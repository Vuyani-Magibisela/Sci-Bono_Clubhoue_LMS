<?php
class CourseModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAllCourses() {
        $courses = [];
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname, 
                COUNT(DISTINCT s.id) as section_count, 
                COUNT(DISTINCT l.id) as lesson_count,
                COUNT(DISTINCT e.id) as enrollment_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                LEFT JOIN course_sections s ON c.id = s.course_id
                LEFT JOIN course_lessons l ON s.id = l.section_id
                LEFT JOIN user_enrollments e ON c.id = e.course_id
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        
        return $courses;
    }
    
    public function getCourseDetails($courseId) {
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname 
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    public function getCourseSections($courseId) {
        $sections = [];
        $sql = "SELECT s.*, COUNT(l.id) as lesson_count 
                FROM course_sections s
                LEFT JOIN course_lessons l ON s.id = l.section_id
                WHERE s.course_id = ?
                GROUP BY s.id
                ORDER BY s.order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        }
        
        return $sections;
    }
    
    // Add other course-related methods here
}
?>