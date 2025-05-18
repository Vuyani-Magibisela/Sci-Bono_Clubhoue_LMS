<?php
class AdminLessonModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get section details
     * 
     * @param int $sectionId Section ID
     * @return array|null Section details or null if not found
     */
    public function getSectionDetails($sectionId) {
        $sql = "SELECT s.*, c.title as course_title 
                FROM course_sections s 
                JOIN courses c ON s.course_id = c.id
                WHERE s.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get lessons for a section
     * 
     * @param int $sectionId Section ID
     * @return array Lessons in the section
     */
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
            while ($lesson = $result->fetch_assoc()) {
                $lessons[] = $lesson;
            }
        }
        
        return $lessons;
    }
    
    /**
     * Get lesson details
     * 
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
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
    
    /**
     * Create a new lesson
     * 
     * @param int $sectionId Section ID
     * @param array $lessonData Lesson data
     * @return int|bool New lesson ID or false on failure
     */
    public function createLesson($sectionId, $lessonData) {
        // Get the highest order number for this section
        $orderQuery = "SELECT MAX(order_number) as max_order FROM course_lessons WHERE section_id = ?";
        $orderStmt = $this->conn->prepare($orderQuery);
        $orderStmt->bind_param("i", $sectionId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        $sql = "INSERT INTO course_lessons (section_id, title, content, lesson_type, video_url, duration_minutes, is_published, order_number)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssiis", 
            $sectionId,
            $lessonData['title'],
            $lessonData['content'],
            $lessonData['lesson_type'],
            $lessonData['video_url'],
            $lessonData['duration_minutes'],
            $lessonData['is_published'],
            $orderNumber
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing lesson
     * 
     * @param int $lessonId Lesson ID
     * @param array $lessonData Updated lesson data
     * @return bool Success status
     */
    public function updateLesson($lessonId, $lessonData) {
        $sql = "UPDATE course_lessons 
                SET title = ?, content = ?, lesson_type = ?, video_url = ?, duration_minutes = ?, is_published = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssiis", 
            $lessonData['title'],
            $lessonData['content'],
            $lessonData['lesson_type'],
            $lessonData['video_url'],
            $lessonData['duration_minutes'],
            $lessonData['is_published'],
            $lessonId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete a lesson
     * 
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function deleteLesson($lessonId) {
        // First, delete any associated lesson progress data
        $deleteProgressQuery = "DELETE FROM lesson_progress WHERE lesson_id = ?";
        $deleteProgressStmt = $this->conn->prepare($deleteProgressQuery);
        $deleteProgressStmt->bind_param("i", $lessonId);
        $deleteProgressStmt->execute();
        
        // Then delete the lesson
        $sql = "DELETE FROM course_lessons WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        
        return $stmt->execute();
    }
    
    /**
     * Update lesson order
     * 
     * @param array $lessonOrders Array of lesson IDs and their order
     * @return bool Success status
     */
    public function updateLessonOrder($lessonOrders) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE course_lessons SET order_number = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($lessonOrders as $lessonId => $order) {
                $stmt->bind_param("ii", $order, $lessonId);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }
}