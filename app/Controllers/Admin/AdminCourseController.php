<?php
require_once __DIR__ . '/../../Models/Admin/AdminCourseModel.php';

class AdminCourseController {
    private $conn;
    private $adminCourseModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->adminCourseModel = new AdminCourseModel($conn);
    }
    
    /**
     * Get all courses with additional metadata
     * 
     * @return array Array of courses with metadata
     */
    public function getAllCourses() {
        return $this->adminCourseModel->getAllCourses();
    }
    
    /**
     * Get course details by ID
     * 
     * @param int $courseId Course ID
     * @return array|null Course details or null if not found
     */
    public function getCourseDetails($courseId) {
        return $this->adminCourseModel->getCourseDetails($courseId);
    }
    
    /**
     * Create a new course
     * 
     * @param array $courseData Course data
     * @return int|bool New course ID or false on failure
     */
    public function createCourse($courseData) {
        // Validate required fields
        if (empty($courseData['title']) || empty($courseData['type'])) {
            return false;
        }
        
        // Set defaults for optional fields
        $courseData['difficulty_level'] = $courseData['difficulty_level'] ?? 'Beginner';
        $courseData['status'] = $courseData['status'] ?? 'draft';
        $courseData['is_published'] = $courseData['is_published'] ?? 0;
        $courseData['is_featured'] = $courseData['is_featured'] ?? 0;
        
        return $this->adminCourseModel->createCourse($courseData);
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
        
        // Validate required fields
        if (empty($courseData['title']) || empty($courseData['type'])) {
            return false;
        }
        
        return $this->adminCourseModel->updateCourse($courseId, $courseData);
    }
    
    /**
     * Delete a course
     * 
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function deleteCourse($courseId) {
        // Validate course ID
        if ($courseId <= 0) {
            return false;
        }
        
        return $this->adminCourseModel->deleteCourse($courseId);
    }
    
    /**
     * Update course status (active, draft, archived)
     * 
     * @param int $courseId Course ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateCourseStatus($courseId, $status) {
        // Validate course ID
        if ($courseId <= 0) {
            return false;
        }
        
        // Validate status
        if (!in_array($status, ['active', 'draft', 'archived'])) {
            return false;
        }
        
        return $this->adminCourseModel->updateCourseStatus($courseId, $status);
    }
    
    /**
     * Toggle featured status for a course
     * 
     * @param int $courseId Course ID
     * @param bool $featured Featured status
     * @return bool Success status
     */
    public function toggleFeatured($courseId, $featured) {
        // Validate course ID
        if ($courseId <= 0) {
            return false;
        }
        
        return $this->adminCourseModel->toggleFeatured($courseId, $featured);
    }
    
    /**
     * Get course sections
     * 
     * @param int $courseId Course ID
     * @return array Course sections
     */
    public function getCourseSections($courseId) {
        // Validate course ID
        if ($courseId <= 0) {
            return [];
        }
        
        return $this->adminCourseModel->getCourseSections($courseId);
    }
    
    /**
     * Create a new section for a course
     * 
     * @param int $courseId Course ID
     * @param array $sectionData Section data
     * @return int|bool New section ID or false on failure
     */
    public function createSection($courseId, $sectionData) {
        // Validate course ID
        if ($courseId <= 0) {
            return false;
        }
        
        // Validate required fields
        if (empty($sectionData['title'])) {
            return false;
        }
        
        return $this->adminCourseModel->createSection($courseId, $sectionData);
    }
    
    /**
     * Update a section
     * 
     * @param int $sectionId Section ID
     * @param array $sectionData Updated section data
     * @return bool Success status
     */
    public function updateSection($sectionId, $sectionData) {
        // Validate section ID
        if ($sectionId <= 0) {
            return false;
        }
        
        // Validate required fields
        if (empty($sectionData['title'])) {
            return false;
        }
        
        return $this->adminCourseModel->updateSection($sectionId, $sectionData);
    }
    
    /**
     * Delete a section
     * 
     * @param int $sectionId Section ID
     * @return bool Success status
     */
    public function deleteSection($sectionId) {
        // Validate section ID
        if ($sectionId <= 0) {
            return false;
        }
        
        return $this->adminCourseModel->deleteSection($sectionId);
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
        
        return $this->adminCourseModel->updateSectionOrder($sectionOrders);
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
        
        return $types[$type] ?? ucwords(str_replace('_', ' ', $type));
    }
    
    /**
     * Get CSS class for difficulty level
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