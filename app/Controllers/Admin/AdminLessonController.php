<?php
require_once __DIR__ . '/../../Models/Admin/AdminLessonModel.php';

class AdminLessonController {
    private $conn;
    private $adminLessonModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->adminLessonModel = new AdminLessonModel($conn);
    }
    
    /**
     * Get section details
     * 
     * @param int $sectionId Section ID
     * @return array|null Section details or null if not found
     */
    public function getSectionDetails($sectionId) {
        return $this->adminLessonModel->getSectionDetails($sectionId);
    }
    
    /**
     * Get lessons for a section
     * 
     * @param int $sectionId Section ID
     * @return array Lessons in the section
     */
    public function getSectionLessons($sectionId) {
        return $this->adminLessonModel->getSectionLessons($sectionId);
    }
    
    /**
     * Get lesson details
     * 
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
    public function getLessonDetails($lessonId) {
        return $this->adminLessonModel->getLessonDetails($lessonId);
    }
    
    /**
     * Create a new lesson
     * 
     * @param int $sectionId Section ID
     * @param array $lessonData Lesson data
     * @return int|bool New lesson ID or false on failure
     */
    public function createLesson($sectionId, $lessonData) {
        // Validate required fields
        if (empty($lessonData['title'])) {
            return false;
        }
        
        return $this->adminLessonModel->createLesson($sectionId, $lessonData);
    }
    
    /**
     * Update an existing lesson
     * 
     * @param int $lessonId Lesson ID
     * @param array $lessonData Updated lesson data
     * @return bool Success status
     */
    public function updateLesson($lessonId, $lessonData) {
        // Validate lesson ID
        if ($lessonId <= 0) {
            return false;
        }
        
        // Validate required fields
        if (empty($lessonData['title'])) {
            return false;
        }
        
        return $this->adminLessonModel->updateLesson($lessonId, $lessonData);
    }
    
    /**
     * Delete a lesson
     * 
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function deleteLesson($lessonId) {
        // Validate lesson ID
        if ($lessonId <= 0) {
            return false;
        }
        
        return $this->adminLessonModel->deleteLesson($lessonId);
    }
    
    /**
     * Update lesson order
     * 
     * @param array $lessonOrders Array of lesson IDs and their order
     * @return bool Success status
     */
    public function updateLessonOrder($lessonOrders) {
        if (empty($lessonOrders) || !is_array($lessonOrders)) {
            return false;
        }
        
        return $this->adminLessonModel->updateLessonOrder($lessonOrders);
    }
    
    /**
     * Get appropriate icon for lesson type
     * 
     * @param string $lessonType Lesson type
     * @return string Icon class
     */
    public function getLessonTypeIcon($lessonType) {
        $icons = [
            'text' => 'fa-file-alt',
            'video' => 'fa-video',
            'quiz' => 'fa-question-circle',
            'assignment' => 'fa-tasks',
            'interactive' => 'fa-laptop-code'
        ];
        
        return $icons[$lessonType] ?? 'fa-file';
    }
}