<?php
class AdminCourseModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all courses with related metadata
     * 
     * @return array Array of courses with metadata
     */
    public function getAllCourses() {
        $courses = [];
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname, 
                (SELECT COUNT(*) FROM course_sections WHERE course_id = c.id) as section_count,
                (SELECT COUNT(*) FROM course_lessons l JOIN course_sections s ON l.section_id = s.id WHERE s.course_id = c.id) as lesson_count,
                (SELECT COUNT(*) FROM user_enrollments WHERE course_id = c.id) as enrollment_count
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
     * Get course details by ID
     * 
     * @param int $courseId Course ID
     * @return array|null Course details or null if not found
     */
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
    
    /**
     * Create a new course
     * 
     * @param array $courseData Course data
     * @return int|bool New course ID or false on failure
     */
    public function createCourse($courseData) {
        // Validate that the user exists
        if (!$this->validateUserExists($courseData['created_by'])) {
            error_log("User with ID {$courseData['created_by']} does not exist");
            return false;
        }
        
        // Generate a unique course code if not provided or empty
        if (empty($courseData['course_code'])) {
            $courseData['course_code'] = $this->generateUniqueCourseCode($courseData['title'], $courseData['type']);
        } else {
            // Check if the provided course code is unique
            if (!$this->isCourseCodeUnique($courseData['course_code'])) {
                // If not unique, generate a new one
                $courseData['course_code'] = $this->generateUniqueCourseCode($courseData['title'], $courseData['type']);
            }
        }
        
        $sql = "INSERT INTO courses (course_code, title, description, type, difficulty_level, duration, image_path, is_featured, is_published, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("sssssssiiss",
            $courseData['course_code'],
            $courseData['title'],
            $courseData['description'],
            $courseData['type'],
            $courseData['difficulty_level'],
            $courseData['duration'],
            $courseData['image_path'],
            $courseData['is_featured'],
            $courseData['is_published'],
            $courseData['status'],
            $courseData['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            error_log("Failed to execute statement: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Validate that a user exists in the database
     * 
     * @param int $userId User ID to validate
     * @return bool True if user exists, false otherwise
     */
    private function validateUserExists($userId) {
        if (empty($userId) || !is_numeric($userId)) {
            return false;
        }
        
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Check if a course code is unique
     * 
     * @param string $courseCode Course code to check
     * @return bool True if unique, false otherwise
     */
    private function isCourseCodeUnique($courseCode) {
        $sql = "SELECT id FROM courses WHERE course_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $courseCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows === 0;
    }
    
    /**
     * Generate a unique course code based on title, type, and timestamp
     * 
     * @param string $title Course title
     * @param string $type Course type
     * @return string Unique course code
     */
    private function generateUniqueCourseCode($title, $type = '') {
        // Create a base code from title and type
        $baseCode = $this->createBaseCode($title, $type);
        
        // Check if the base code is unique
        if ($this->isCourseCodeUnique($baseCode)) {
            return $baseCode;
        }
        
        // If not unique, add a timestamp suffix
        $counter = 1;
        do {
            $code = $baseCode . '-' . $counter;
            $counter++;
        } while (!$this->isCourseCodeUnique($code) && $counter < 1000); // Prevent infinite loop
        
        return $code;
    }
    
    /**
     * Create a base course code from title and type
     * 
     * @param string $title Course title
     * @param string $type Course type
     * @return string Base course code
     */
    private function createBaseCode($title, $type = '') {
        // Clean and process the title
        $titleWords = explode(' ', strtoupper(trim($title)));
        $titleCode = '';
        
        // Get first letter of each word (up to 4 letters)
        $wordCount = 0;
        foreach ($titleWords as $word) {
            if (!empty($word) && $wordCount < 4) {
                $titleCode .= substr($word, 0, 1);
                $wordCount++;
            }
        }
        
        // Add type prefix if provided
        $typePrefix = '';
        if (!empty($type)) {
            switch ($type) {
                case 'full_course':
                    $typePrefix = 'FC-';
                    break;
                case 'short_course':
                    $typePrefix = 'SC-';
                    break;
                case 'lesson':
                    $typePrefix = 'LN-';
                    break;
                case 'skill_activity':
                    $typePrefix = 'SA-';
                    break;
                default:
                    $typePrefix = 'GN-'; // Generic
                    break;
            }
        }
        
        // Combine type prefix and title code
        $baseCode = $typePrefix . $titleCode;
        
        // Ensure minimum length and add timestamp if too short
        if (strlen($baseCode) < 4) {
            $baseCode .= substr(time(), -3);
        }
        
        return $baseCode;
    }

    /**
     * Update an existing course
     * 
     * @param int $courseId Course ID
     * @param array $courseData Updated course data
     * @return bool Success status
     */
    public function updateCourse($courseId, $courseData) {
        $sql = "UPDATE courses SET 
                title = ?, 
                description = ?, 
                type = ?, 
                difficulty_level = ?, 
                duration = ?, 
                image_path = ?, 
                is_featured = ?, 
                is_published = ?, 
                status = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssiisi", 
            $courseData['title'],
            $courseData['description'],
            $courseData['type'],
            $courseData['difficulty_level'],
            $courseData['duration'],
            $courseData['image_path'],
            $courseData['is_featured'],
            $courseData['is_published'],
            $courseData['status'],
            $courseId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete a course
     * 
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function deleteCourse($courseId) {
        // First, get all sections to delete associated lessons
        $sectionIds = [];
        $sectionsQuery = "SELECT id FROM course_sections WHERE course_id = ?";
        $sectionsStmt = $this->conn->prepare($sectionsQuery);
        $sectionsStmt->bind_param("i", $courseId);
        $sectionsStmt->execute();
        $sectionsResult = $sectionsStmt->get_result();
        
        if ($sectionsResult && $sectionsResult->num_rows > 0) {
            while ($row = $sectionsResult->fetch_assoc()) {
                $sectionIds[] = $row['id'];
            }
        }
        
        // Start transaction to ensure all related data is deleted
        $this->conn->begin_transaction();
        
        try {
            // Delete lessons for each section
            if (!empty($sectionIds)) {
                foreach ($sectionIds as $sectionId) {
                    $deleteLessonsQuery = "DELETE FROM course_lessons WHERE section_id = ?";
                    $deleteLessonsStmt = $this->conn->prepare($deleteLessonsQuery);
                    $deleteLessonsStmt->bind_param("i", $sectionId);
                    $deleteLessonsStmt->execute();
                }
            }
            
            // Delete sections
            $deleteSectionsQuery = "DELETE FROM course_sections WHERE course_id = ?";
            $deleteSectionsStmt = $this->conn->prepare($deleteSectionsQuery);
            $deleteSectionsStmt->bind_param("i", $courseId);
            $deleteSectionsStmt->execute();
            
            // Delete enrollments
            $deleteEnrollmentsQuery = "DELETE FROM user_enrollments WHERE course_id = ?";
            $deleteEnrollmentsStmt = $this->conn->prepare($deleteEnrollmentsQuery);
            $deleteEnrollmentsStmt->bind_param("i", $courseId);
            $deleteEnrollmentsStmt->execute();
            
            // Delete course
            $deleteCourseQuery = "DELETE FROM courses WHERE id = ?";
            $deleteCourseStmt = $this->conn->prepare($deleteCourseQuery);
            $deleteCourseStmt->bind_param("i", $courseId);
            $result = $deleteCourseStmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return $result;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Update course status
     * 
     * @param int $courseId Course ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateCourseStatus($courseId, $status) {
        // If status is 'active', also update is_published
        $isPublished = ($status == 'active') ? 1 : 0;
        
        $sql = "UPDATE courses SET status = ?, is_published = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $status, $isPublished, $courseId);
        
        return $stmt->execute();
    }
    
    /**
     * Toggle featured status for a course
     * 
     * @param int $courseId Course ID
     * @param bool $featured Featured status
     * @return bool Success status
     */
    public function toggleFeatured($courseId, $featured) {
        $sql = "UPDATE courses SET is_featured = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $featured, $courseId);
        
        return $stmt->execute();
    }
    
    /**
     * Get course sections with lessons
     * 
     * @param int $courseId Course ID
     * @return array Course sections with lessons
     */
    public function getCourseSections($courseId) {
        $sections = [];
        $sql = "SELECT * FROM course_sections WHERE course_id = ? ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($section = $result->fetch_assoc()) {
                // Get lessons for each section
                $section['lessons'] = $this->getSectionLessons($section['id']);
                $sections[] = $section;
            }
        }
        
        return $sections;
    }
    
    /**
     * Get lessons for a section
     * 
     * @param int $sectionId Section ID
     * @return array Lessons in the section
     */
    private function getSectionLessons($sectionId) {
        $lessons = [];
        $sql = "SELECT * FROM course_lessons WHERE section_id = ? ORDER BY order_number ASC";
        
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
     * Create a new section for a course
     * 
     * @param int $courseId Course ID
     * @param array $sectionData Section data
     * @return int|bool New section ID or false on failure
     */
    public function createSection($courseId, $sectionData) {
        // Get the highest order number for the course
        $orderQuery = "SELECT MAX(order_number) as max_order FROM course_sections WHERE course_id = ?";
        $orderStmt = $this->conn->prepare($orderQuery);
        $orderStmt->bind_param("i", $courseId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        // Insert the new section
        $sql = "INSERT INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $courseId, $sectionData['title'], $sectionData['description'], $orderNumber);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update a section
     * 
     * @param int $sectionId Section ID
     * @param array $sectionData Updated section data
     * @return bool Success status
     */
    public function updateSection($sectionId, $sectionData) {
        $sql = "UPDATE course_sections SET title = ?, description = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $sectionData['title'], $sectionData['description'], $sectionId);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a section
     * 
     * @param int $sectionId Section ID
     * @return bool Success status
     */
    public function deleteSection($sectionId) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Delete lessons in the section
            $deleteLessonsQuery = "DELETE FROM course_lessons WHERE section_id = ?";
            $deleteLessonsStmt = $this->conn->prepare($deleteLessonsQuery);
            $deleteLessonsStmt->bind_param("i", $sectionId);
            $deleteLessonsStmt->execute();
            
            // Delete section
            $deleteSectionQuery = "DELETE FROM course_sections WHERE id = ?";
            $deleteSectionStmt = $this->conn->prepare($deleteSectionQuery);
            $deleteSectionStmt->bind_param("i", $sectionId);
            $result = $deleteSectionStmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return $result;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Update section order
     * 
     * @param array $sectionOrders Array of section IDs and their order
     * @return bool Success status
     */
    public function updateSectionOrder($sectionOrders) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE course_sections SET order_number = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($sectionOrders as $sectionId => $order) {
                $stmt->bind_param("ii", $order, $sectionId);
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
?>