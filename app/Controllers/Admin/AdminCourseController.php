<?php
require_once __DIR__ . '/../../Models/Admin/AdminCourseModel.php';

class AdminCourseController {
    private $conn;
    private $courseModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->courseModel = new AdminCourseModel($conn);
    }
    
    /**
     * Get all courses
     * 
     * @return array List of all courses
     */
    public function getAllCourses() {
        return $this->courseModel->getAllCourses();
    }
    
    /**
     * Get course details by ID
     * 
     * @param int $courseId Course ID
     * @return array|null Course details or null if not found
     */
    public function getCourseDetails($courseId) {
        return $this->courseModel->getCourseDetails($courseId);
    }
    
    /**
     * Create a new course with validation
     * 
     * @param array $courseData Course data
     * @return int|bool New course ID or false on failure
     */
    public function createCourse($courseData) {
        // Validate required fields
        if (empty($courseData['title'])) {
            error_log("Course creation failed: Missing title");
            return false;
        }
        
        if (empty($courseData['created_by']) || !is_numeric($courseData['created_by'])) {
            error_log("Course creation failed: Invalid created_by user ID");
            return false;
        }
        
        // Sanitize data
        $courseData = $this->sanitizeCourseData($courseData);
        
        // Create the course
        return $this->courseModel->createCourse($courseData);
    }
    
    /**
     * Update an existing course
     * 
     * @param int $courseId Course ID
     * @param array $courseData Updated course data
     * @return bool Success status
     */
    public function updateCourse($courseId, $courseData) {
        // Validate course ID
        if ($courseId <= 0) {
            return false;
        }
        
        // Sanitize data
        $courseData = $this->sanitizeCourseData($courseData);
        
        return $this->courseModel->updateCourse($courseId, $courseData);
    }
    
    /**
     * Delete a course
     * 
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function deleteCourse($courseId) {
        if ($courseId <= 0) {
            return false;
        }
        
        return $this->courseModel->deleteCourse($courseId);
    }
    
    /**
     * Update course status
     * 
     * @param int $courseId Course ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateCourseStatus($courseId, $status) {
        $validStatuses = ['draft', 'active', 'archived'];
        
        if ($courseId <= 0 || !in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->courseModel->updateCourseStatus($courseId, $status);
    }
    
    /**
     * Toggle featured status for a course
     * 
     * @param int $courseId Course ID
     * @param bool $featured Featured status
     * @return bool Success status
     */
    public function toggleFeatured($courseId, $featured) {
        if ($courseId <= 0) {
            return false;
        }
        
        return $this->courseModel->toggleFeatured($courseId, $featured);
    }
    
    /**
     * Get course sections with lessons
     * 
     * @param int $courseId Course ID
     * @return array Course sections with lessons
     */
    public function getCourseSections($courseId) {
        if ($courseId <= 0) {
            return [];
        }
        
        return $this->courseModel->getCourseSections($courseId);
    }
    
    /**
     * Create a new section for a course
     * 
     * @param int $courseId Course ID
     * @param array $sectionData Section data
     * @return int|bool New section ID or false on failure
     */
    public function createSection($courseId, $sectionData) {
        if ($courseId <= 0 || empty($sectionData['title'])) {
            return false;
        }
        
        // Sanitize section data
        $sectionData['title'] = trim($sectionData['title']);
        $sectionData['description'] = trim($sectionData['description'] ?? '');
        
        return $this->courseModel->createSection($courseId, $sectionData);
    }
    
    /**
     * Update a section
     * 
     * @param int $sectionId Section ID
     * @param array $sectionData Updated section data
     * @return bool Success status
     */
    public function updateSection($sectionId, $sectionData) {
        if ($sectionId <= 0 || empty($sectionData['title'])) {
            return false;
        }
        
        // Sanitize section data
        $sectionData['title'] = trim($sectionData['title']);
        $sectionData['description'] = trim($sectionData['description'] ?? '');
        
        return $this->courseModel->updateSection($sectionId, $sectionData);
    }
    
    /**
     * Delete a section
     * 
     * @param int $sectionId Section ID
     * @return bool Success status
     */
    public function deleteSection($sectionId) {
        if ($sectionId <= 0) {
            return false;
        }
        
        return $this->courseModel->deleteSection($sectionId);
    }
    
    /**
     * Update section order
     * 
     * @param array $sectionOrders Array of section IDs and their order
     * @return bool Success status
     */
    public function updateSectionOrder($sectionOrders) {
        if (empty($sectionOrders) || !is_array($sectionOrders)) {
            return false;
        }
        
        return $this->courseModel->updateSectionOrder($sectionOrders);
    }
    
    /**
     * Sanitize course data for security
     * 
     * @param array $courseData Raw course data
     * @return array Sanitized course data
     */
    private function sanitizeCourseData($courseData) {
        $sanitized = [];
        
        // Required fields
        $sanitized['title'] = trim($courseData['title'] ?? '');
        $sanitized['description'] = trim($courseData['description'] ?? '');
        $sanitized['type'] = trim($courseData['type'] ?? 'full_course');
        $sanitized['difficulty_level'] = trim($courseData['difficulty_level'] ?? 'Beginner');
        $sanitized['created_by'] = intval($courseData['created_by'] ?? 0);
        
        // Optional fields
        $sanitized['duration'] = trim($courseData['duration'] ?? '');
        $sanitized['image_path'] = trim($courseData['image_path'] ?? '');
        $sanitized['course_code'] = trim($courseData['course_code'] ?? '');
        $sanitized['status'] = trim($courseData['status'] ?? 'draft');
        
        // Boolean fields
        $sanitized['is_featured'] = isset($courseData['is_featured']) ? intval($courseData['is_featured']) : 0;
        $sanitized['is_published'] = isset($courseData['is_published']) ? intval($courseData['is_published']) : 0;
        
        // Validate enum values
        $validTypes = ['full_course', 'short_course', 'lesson', 'skill_activity'];
        if (!in_array($sanitized['type'], $validTypes)) {
            $sanitized['type'] = 'full_course';
        }
        
        $validDifficulties = ['Beginner', 'Intermediate', 'Advanced'];
        if (!in_array($sanitized['difficulty_level'], $validDifficulties)) {
            $sanitized['difficulty_level'] = 'Beginner';
        }
        
        $validStatuses = ['draft', 'active', 'archived'];
        if (!in_array($sanitized['status'], $validStatuses)) {
            $sanitized['status'] = 'draft';
        }
        
        return $sanitized;
    }
    
    /**
     * Format course type for display
     * 
     * @param string $type Course type
     * @return string Formatted course type
     */
    public function formatCourseType($type) {
        $types = [
            'full_course' => 'Full Course',
            'short_course' => 'Short Course',
            'lesson' => 'Lesson',
            'skill_activity' => 'Skill Activity'
        ];
        
        return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
    
    /**
     * Get difficulty class for styling
     * 
     * @param string $level Difficulty level
     * @return string CSS class
     */
    public function getDifficultyClass($level) {
        $classes = [
            'Beginner' => 'badge-success',
            'Intermediate' => 'badge-warning',
            'Advanced' => 'badge-danger'
        ];
        
        return $classes[$level] ?? 'badge-primary';
    }
}
?>