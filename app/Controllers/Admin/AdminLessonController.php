<?php
/**
 * AdminLessonController
 *
 * Handles lesson management operations for administrators
 *
 * Phase 4 Week 4 Day 2: Controller Standardization - BaseController Migration
 * Migrated: January 5, 2026
 * Original: 154 lines, standalone class
 * Migrated: Extended BaseController with security enhancements
 */

namespace App\Controllers\Admin;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Admin/AdminLessonModel.php';

class AdminLessonController extends \BaseController {
    private $adminLessonModel;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->adminLessonModel = new \AdminLessonModel($conn);
    }

    /**
     * Get section details
     *
     * @param int $sectionId Section ID
     * @return array|null Section details or null if not found
     */
    public function getSectionDetails($sectionId) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate section ID
            if ($sectionId <= 0) {
                $this->logger->warning("Invalid section ID requested", [
                    'section_id' => $sectionId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return null;
            }

            $section = $this->adminLessonModel->getSectionDetails($sectionId);

            // Log successful retrieval
            $this->logAction('view_section_details', [
                'section_id' => $sectionId,
                'section_found' => $section !== null
            ]);

            return $section;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get section details", [
                'section_id' => $sectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get lessons for a section
     *
     * @param int $sectionId Section ID
     * @return array Lessons in the section
     */
    public function getSectionLessons($sectionId) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate section ID
            if ($sectionId <= 0) {
                $this->logger->warning("Invalid section ID for lessons", [
                    'section_id' => $sectionId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return [];
            }

            $lessons = $this->adminLessonModel->getSectionLessons($sectionId);

            // Log successful retrieval
            $this->logAction('view_section_lessons', [
                'section_id' => $sectionId,
                'lesson_count' => count($lessons)
            ]);

            return $lessons;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get section lessons", [
                'section_id' => $sectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get lesson details
     *
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
    public function getLessonDetails($lessonId) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate lesson ID
            if ($lessonId <= 0) {
                $this->logger->warning("Invalid lesson ID requested", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return null;
            }

            $lesson = $this->adminLessonModel->getLessonDetails($lessonId);

            // Log successful retrieval
            $this->logAction('view_lesson_details', [
                'lesson_id' => $lessonId,
                'lesson_found' => $lesson !== null
            ]);

            return $lesson;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get lesson details", [
                'lesson_id' => $lessonId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create a new lesson
     *
     * @param int $sectionId Section ID
     * @param array $lessonData Lesson data
     * @return int|bool New lesson ID or false on failure
     */
    public function createLesson($sectionId, $lessonData) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                $this->logger->warning("CSRF validation failed in createLesson", [
                    'section_id' => $sectionId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate section ID
            if ($sectionId <= 0) {
                $this->logger->warning("Invalid section ID for lesson creation", [
                    'section_id' => $sectionId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate required fields
            if (empty($lessonData['title'])) {
                $this->logger->warning("Lesson creation failed - missing title", [
                    'section_id' => $sectionId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }

            $lessonId = $this->adminLessonModel->createLesson($sectionId, $lessonData);

            if ($lessonId) {
                // Log successful creation
                $this->logAction('create_lesson', [
                    'section_id' => $sectionId,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lessonData['title']
                ]);
            } else {
                $this->logger->error("Failed to create lesson", [
                    'section_id' => $sectionId,
                    'lesson_data' => $lessonData
                ]);
            }

            return $lessonId;

        } catch (\Exception $e) {
            $this->logger->error("Exception in createLesson", [
                'section_id' => $sectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update an existing lesson
     *
     * @param int $lessonId Lesson ID
     * @param array $lessonData Updated lesson data
     * @return bool Success status
     */
    public function updateLesson($lessonId, $lessonData) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                $this->logger->warning("CSRF validation failed in updateLesson", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate lesson ID
            if ($lessonId <= 0) {
                $this->logger->warning("Invalid lesson ID for update", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate required fields
            if (empty($lessonData['title'])) {
                $this->logger->warning("Lesson update failed - missing title", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }

            $result = $this->adminLessonModel->updateLesson($lessonId, $lessonData);

            if ($result) {
                // Log successful update
                $this->logAction('update_lesson', [
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lessonData['title']
                ]);
            } else {
                $this->logger->error("Failed to update lesson", [
                    'lesson_id' => $lessonId,
                    'lesson_data' => $lessonData
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Exception in updateLesson", [
                'lesson_id' => $lessonId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete a lesson
     *
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function deleteLesson($lessonId) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                $this->logger->warning("CSRF validation failed in deleteLesson", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate lesson ID
            if ($lessonId <= 0) {
                $this->logger->warning("Invalid lesson ID for deletion", [
                    'lesson_id' => $lessonId,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }

            $result = $this->adminLessonModel->deleteLesson($lessonId);

            if ($result) {
                // Log successful deletion
                $this->logAction('delete_lesson', [
                    'lesson_id' => $lessonId
                ]);
            } else {
                $this->logger->error("Failed to delete lesson", [
                    'lesson_id' => $lessonId
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Exception in deleteLesson", [
                'lesson_id' => $lessonId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update lesson order
     *
     * @param array $lessonOrders Array of lesson IDs and their order
     * @return bool Success status
     */
    public function updateLessonOrder($lessonOrders) {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                $this->logger->warning("CSRF validation failed in updateLessonOrder", [
                    'user_id' => $_SESSION['user_id'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }

            // Validate input
            if (empty($lessonOrders) || !is_array($lessonOrders)) {
                $this->logger->warning("Invalid lesson orders provided", [
                    'user_id' => $_SESSION['user_id'] ?? 'unknown',
                    'orders_type' => gettype($lessonOrders)
                ]);
                return false;
            }

            $result = $this->adminLessonModel->updateLessonOrder($lessonOrders);

            if ($result) {
                // Log successful reordering
                $this->logAction('update_lesson_order', [
                    'lesson_count' => count($lessonOrders)
                ]);
            } else {
                $this->logger->error("Failed to update lesson order", [
                    'lesson_count' => count($lessonOrders)
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Exception in updateLessonOrder", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
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
