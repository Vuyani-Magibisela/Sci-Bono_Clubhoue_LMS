<?php
class AdminCourseModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function createCourse($title, $description, $type, $difficulty, $duration, $imagePath, $createdBy) {
        $sql = "INSERT INTO courses (title, description, type, difficulty_level, duration, image_path, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", $title, $description, $type, $difficulty, $duration, $imagePath, $createdBy);
        
        return $stmt->execute();
    }
    
    public function addSection($courseId, $title, $description, $orderNumber) {
        $sql = "INSERT INTO course_sections (course_id, title, description, order_number) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $courseId, $title, $description, $orderNumber);
        
        return $stmt->execute();
    }
    
    public function addLesson($sectionId, $title, $content, $lessonType, $videoUrl, $durationMinutes, $orderNumber, $isPublished) {
        $sql = "INSERT INTO course_lessons (section_id, title, content, lesson_type, video_url, duration_minutes, order_number, is_published) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssiii", $sectionId, $title, $content, $lessonType, $videoUrl, $durationMinutes, $orderNumber, $isPublished);
        
        return $stmt->execute();
    }
    
    /**
     * Get all lessons for a specific course section
     * 
     * @param int $sectionId Section ID
     * @return array Array of lesson data
     */
    public function getSectionLessons($sectionId) {
        $lessons = [];
        
        if (!$sectionId) {
            return $lessons;
        }
        
        $sql = "SELECT l.*, 
                (SELECT COUNT(*) FROM lesson_progress WHERE lesson_id = l.id AND completed = 1) as completion_count 
                FROM course_lessons l 
                WHERE l.section_id = ? 
                ORDER BY l.order_number ASC";
                
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            // Handle database error
            return $lessons;
        }
        
        $stmt->bind_param("i", $sectionId);
        $executed = $stmt->execute();
        
        if (!$executed) {
            return $lessons;
        }
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
        }
        
        return $lessons;
    }
    
    /**
     * Get lessons count for a section
     * 
     * @param int $sectionId Section ID
     * @return int Number of lessons in the section
     */
    public function getSectionLessonCount($sectionId) {
        if (!$sectionId) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as lesson_count FROM course_lessons WHERE section_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['lesson_count'];
        }
        
        return 0;
    }
    
    /**
     * Get all courses with additional metadata
     * 
     * @return array Array of courses with section counts, lesson counts, etc.
     */
    public function getAllCoursesWithMetadata() {
        $courses = [];
        
        $sql = "SELECT c.*, 
                u.name as creator_name, u.surname as creator_surname,
                (SELECT COUNT(*) FROM course_sections WHERE course_id = c.id) as section_count,
                (SELECT COUNT(*) FROM course_sections cs 
                 JOIN course_lessons cl ON cs.id = cl.section_id 
                 WHERE cs.course_id = c.id) as lesson_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                ORDER BY c.created_at DESC";
                
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        
        return $courses;
    }
    
    /**
     * Get course details with creator information
     * 
     * @param int $courseId Course ID
     * @return array|null Course details or null if not found
     */
    public function getCourseDetails($courseId) {
        if (!$courseId) {
            return null;
        }
        
        $sql = "SELECT c.*, 
                u.name as creator_name, u.surname as creator_surname
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
                
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get all sections for a course
     * 
     * @param int $courseId Course ID
     * @return array Array of course sections
     */
    public function getCourseSections($courseId) {
        $sections = [];
        
        if (!$courseId) {
            return $sections;
        }
        
        $sql = "SELECT cs.*, 
                (SELECT COUNT(*) FROM course_lessons WHERE section_id = cs.id) as lesson_count
                FROM course_sections cs
                WHERE cs.course_id = ?
                ORDER BY cs.order_number ASC";
                
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return $sections;
        }
        
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
    
    /**
     * Update a course
     * 
     * @param int $courseId Course ID
     * @param array $data Updated course data
     * @return bool Success status
     */
    public function updateCourse($courseId, $data) {
        if (!$courseId || empty($data)) {
            return false;
        }
        
        $updateFields = [];
        $params = [];
        $types = '';
        
        // Build update statement based on provided data
        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
            $types .= 's';
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
            $types .= 's';
        }
        
        if (isset($data['type'])) {
            $updateFields[] = "type = ?";
            $params[] = $data['type'];
            $types .= 's';
        }
        
        if (isset($data['difficulty_level'])) {
            $updateFields[] = "difficulty_level = ?";
            $params[] = $data['difficulty_level'];
            $types .= 's';
        }
        
        if (isset($data['duration'])) {
            $updateFields[] = "duration = ?";
            $params[] = $data['duration'];
            $types .= 's';
        }
        
        if (isset($data['image_path']) && !empty($data['image_path'])) {
            $updateFields[] = "image_path = ?";
            $params[] = $data['image_path'];
            $types .= 's';
        }
        
        if (isset($data['is_published'])) {
            $updateFields[] = "is_published = ?";
            $params[] = $data['is_published'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (isset($data['is_featured'])) {
            $updateFields[] = "is_featured = ?";
            $params[] = $data['is_featured'] ? 1 : 0;
            $types .= 'i';
        }
        
        // Add updated_at timestamp
        $updateFields[] = "updated_at = NOW()";
        
        if (empty($updateFields)) {
            return false; // Nothing to update
        }
        
        $sql = "UPDATE courses SET " . implode(", ", $updateFields) . " WHERE id = ?";
        
        $params[] = $courseId;
        $types .= 'i';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
    
    /**
     * Delete a course
     * 
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function deleteCourse($courseId) {
        if (!$courseId) {
            return false;
        }
        
        // Start transaction to ensure all related data is deleted
        $this->conn->begin_transaction();
        
        try {
            // Delete lesson progress
            $sql1 = "DELETE lp FROM lesson_progress lp 
                     JOIN course_lessons cl ON lp.lesson_id = cl.id 
                     JOIN course_sections cs ON cl.section_id = cs.id 
                     WHERE cs.course_id = ?";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("i", $courseId);
            $stmt1->execute();
            
            // Delete quiz attempts and related data
            // (If you have quiz functionality)
            
            // Delete lessons
            $sql2 = "DELETE cl FROM course_lessons cl 
                     JOIN course_sections cs ON cl.section_id = cs.id 
                     WHERE cs.course_id = ?";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("i", $courseId);
            $stmt2->execute();
            
            // Delete sections
            $sql3 = "DELETE FROM course_sections WHERE course_id = ?";
            $stmt3 = $this->conn->prepare($sql3);
            $stmt3->bind_param("i", $courseId);
            $stmt3->execute();
            
            // Delete enrollments
            $sql4 = "DELETE FROM user_enrollments WHERE course_id = ?";
            $stmt4 = $this->conn->prepare($sql4);
            $stmt4->bind_param("i", $courseId);
            $stmt4->execute();
            
            // Delete ratings
            $sql5 = "DELETE FROM course_ratings WHERE course_id = ?";
            $stmt5 = $this->conn->prepare($sql5);
            $stmt5->bind_param("i", $courseId);
            $stmt5->execute();
            
            // Delete course
            $sql6 = "DELETE FROM courses WHERE id = ?";
            $stmt6 = $this->conn->prepare($sql6);
            $stmt6->bind_param("i", $courseId);
            $stmt6->execute();
            
            // Commit transaction
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            return false;
        }
    }
}
?>