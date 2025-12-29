<?php
/**
 * AdminCourseController - DEPRECATED
 *
 * This controller is deprecated as of Week 5: Admin Panel Migration
 * All functionality has been consolidated into Admin\CourseController
 *
 * This file acts as a proxy to maintain backward compatibility
 * Please update code to use the new Admin\CourseController directly
 *
 * @deprecated Use Admin\CourseController instead
 */

require_once __DIR__ . '/CourseController.php';
require_once __DIR__ . '/../../Models/Admin/AdminCourseModel.php';

class AdminCourseController {
    private $newController;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->newController = new \Admin\CourseController($conn);

        // Log deprecation warning
        error_log("[DEPRECATED] AdminCourseController is deprecated. Use Admin\\CourseController instead. Called from: " .
                  ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    }

    /**
     * Proxy method: getAllCourses
     * @deprecated
     */
    public function getAllCourses() {
        return $this->newController->index();
    }

    /**
     * Proxy method: getCourseDetails
     * @deprecated
     */
    public function getCourseDetails($courseId) {
        return $this->newController->show($courseId);
    }

    /**
     * Proxy method: createCourse
     * @deprecated
     */
    public function createCourse($courseData) {
        // Store data in $_POST for the new controller
        $_POST = array_merge($_POST, $courseData);
        return $this->newController->store();
    }

    /**
     * Proxy method: updateCourse
     * @deprecated
     */
    public function updateCourse($courseId, $courseData) {
        $_POST = array_merge($_POST, $courseData);
        return $this->newController->update($courseId);
    }

    /**
     * Proxy method: deleteCourse
     * @deprecated
     */
    public function deleteCourse($courseId) {
        return $this->newController->destroy($courseId);
    }

    /**
     * Proxy method: updateCourseStatus
     * @deprecated
     */
    public function updateCourseStatus($courseId, $status) {
        $_POST['status'] = $status;
        return $this->newController->updateStatus($courseId);
    }

    /**
     * Proxy method: toggleFeatured
     * @deprecated
     */
    public function toggleFeatured($courseId, $featured) {
        $_POST['featured'] = $featured;
        return $this->newController->toggleFeatured($courseId);
    }

    /**
     * Proxy method: getCourseSections
     * @deprecated
     */
    public function getCourseSections($courseId) {
        return $this->newController->getSections($courseId);
    }

    /**
     * Proxy method: createSection
     * @deprecated
     */
    public function createSection($courseId, $sectionData) {
        $_POST = array_merge($_POST, $sectionData);
        return $this->newController->createSection($courseId);
    }

    /**
     * Proxy method: updateSection
     * @deprecated
     */
    public function updateSection($sectionId, $sectionData) {
        // This method doesn't have a direct equivalent in the new controller
        // Fall back to AdminCourseModel directly
        $model = new AdminCourseModel($this->conn);
        return $model->updateSection($sectionId, $sectionData);
    }

    /**
     * Proxy method: deleteSection
     * @deprecated
     */
    public function deleteSection($sectionId) {
        // This method doesn't have a direct equivalent in the new controller
        // Fall back to AdminCourseModel directly
        $model = new AdminCourseModel($this->conn);
        return $model->deleteSection($sectionId);
    }

    /**
     * Proxy method: updateSectionOrder
     * @deprecated
     */
    public function updateSectionOrder($sectionOrders) {
        // This method doesn't have a direct equivalent in the new controller
        // Fall back to AdminCourseModel directly
        $model = new AdminCourseModel($this->conn);
        return $model->updateSectionOrder($sectionOrders);
    }

    /**
     * Helper method: formatCourseType
     * @deprecated
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
     * Helper method: getDifficultyClass
     * @deprecated
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
