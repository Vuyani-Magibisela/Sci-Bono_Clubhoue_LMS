<?php
/**
 * Api\Admin\CourseController
 *
 * Handles AJAX API operations for course management
 *
 * Phase 3 Week 5: Admin Panel Migration - Course Management
 * Created: November 27, 2025
 */

namespace Api\Admin;

require_once __DIR__ . '/../../BaseController.php';
require_once __DIR__ . '/../../../Models/Admin/AdminCourseModel.php';
require_once __DIR__ . '/../../../../core/CSRF.php';

class CourseController extends \BaseController {
    private $courseModel;

    public function __construct($conn = null) {
        if ($conn === null) {
            global $conn;
        }

        parent::__construct($conn);
        $this->courseModel = new \AdminCourseModel($conn);
    }

    /**
     * Update course status
     * POST /api/v1/admin/courses/{id}/status
     *
     * Expected POST data:
     * - status: New status (draft|active|archived)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of updated course
     * - status: New status value
     * - message: Status message
     */
    public function updateStatus($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Get and validate new status
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['draft', 'active', 'archived'];

            if (!in_array($newStatus, $validStatuses)) {
                return $this->jsonError('Invalid status. Must be one of: ' . implode(', ', $validStatuses), null, 400);
            }

            // Check if course exists
            $course = $this->courseModel->getCourseById($id);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Update status
            $result = $this->courseModel->updateCourse($id, [
                'status' => $newStatus
            ]);

            if ($result) {
                $this->logger->log('info', 'Course status updated', [
                    'course_id' => $id,
                    'old_status' => $course['status'],
                    'new_status' => $newStatus,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'status' => $newStatus,
                    'old_status' => $course['status']
                ], 'Course status updated successfully');
            } else {
                return $this->jsonError('Failed to update course status', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course status update failed', [
                'course_id' => $id,
                'status' => $_POST['status'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during status update', null, 500);
        }
    }

    /**
     * Toggle course featured status
     * POST /api/v1/admin/courses/{id}/featured
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of updated course
     * - is_featured: New featured status (boolean)
     * - message: Status message
     */
    public function toggleFeatured($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if course exists
            $course = $this->courseModel->getCourseById($id);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Toggle featured status
            $currentFeatured = !empty($course['is_featured']);
            $newFeatured = !$currentFeatured;

            $result = $this->courseModel->updateCourse($id, [
                'is_featured' => $newFeatured ? 1 : 0
            ]);

            if ($result) {
                $this->logger->log('info', 'Course featured status toggled', [
                    'course_id' => $id,
                    'old_featured' => $currentFeatured,
                    'new_featured' => $newFeatured,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'is_featured' => $newFeatured,
                    'was_featured' => $currentFeatured
                ], $newFeatured ? 'Course featured successfully' : 'Course unfeatured successfully');
            } else {
                return $this->jsonError('Failed to toggle featured status', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course featured toggle failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during featured toggle', null, 500);
        }
    }

    /**
     * Get course modules (hierarchy)
     * GET /api/v1/admin/courses/{id}/modules
     *
     * Response:
     * - success: boolean
     * - course_id: ID of course
     * - modules: Array of module objects with lessons
     * - count: Number of modules
     */
    public function getModules($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Check if course exists
            $course = $this->courseModel->getCourseById($id);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Get modules with lessons
            $modules = $this->courseModel->getCourseModules($id);

            if ($modules !== false) {
                return $this->jsonSuccess([
                    'course_id' => $id,
                    'modules' => $modules,
                    'count' => count($modules)
                ], 'Modules retrieved successfully');
            } else {
                return $this->jsonError('Failed to retrieve modules', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course modules retrieval failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving modules', null, 500);
        }
    }

    /**
     * Create a new module for a course
     * POST /api/v1/admin/courses/{id}/modules
     *
     * Expected POST data:
     * - title: Module title
     * - description: Module description (optional)
     * - order: Display order (optional, defaults to next available)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - module_id: ID of created module
     * - course_id: ID of parent course
     * - message: Status message
     */
    public function createModule($courseId) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if course exists
            $course = $this->courseModel->getCourseById($courseId);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Validate required fields
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                return $this->jsonError('Module title is required', null, 400);
            }

            // Prepare module data
            $moduleData = [
                'course_id' => $courseId,
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'order' => intval($_POST['order'] ?? 0)
            ];

            // Create module
            $moduleId = $this->courseModel->createModule($moduleData);

            if ($moduleId) {
                $this->logger->log('info', 'Course module created', [
                    'module_id' => $moduleId,
                    'course_id' => $courseId,
                    'title' => $title,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'module_id' => $moduleId,
                    'course_id' => $courseId,
                    'title' => $title
                ], 'Module created successfully');
            } else {
                return $this->jsonError('Failed to create module', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course module creation failed', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during module creation', null, 500);
        }
    }

    /**
     * Get course sections (alternative hierarchy)
     * GET /api/v1/admin/courses/{id}/sections
     *
     * Response:
     * - success: boolean
     * - course_id: ID of course
     * - sections: Array of section objects
     * - count: Number of sections
     */
    public function getSections($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Check if course exists
            $course = $this->courseModel->getCourseById($id);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Get sections (if model supports this method)
            if (method_exists($this->courseModel, 'getCourseSections')) {
                $sections = $this->courseModel->getCourseSections($id);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'sections' => $sections,
                    'count' => count($sections)
                ], 'Sections retrieved successfully');
            } else {
                // Fallback: sections not implemented
                return $this->jsonSuccess([
                    'course_id' => $id,
                    'sections' => [],
                    'count' => 0
                ], 'Sections feature not yet implemented');
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course sections retrieval failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving sections', null, 500);
        }
    }

    /**
     * Create a new section for a course
     * POST /api/v1/admin/courses/{id}/sections
     *
     * Expected POST data:
     * - title: Section title
     * - description: Section description (optional)
     * - order: Display order (optional)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - section_id: ID of created section
     * - course_id: ID of parent course
     * - message: Status message
     */
    public function createSection($courseId) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if course exists
            $course = $this->courseModel->getCourseById($courseId);
            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Check if sections are implemented
            if (!method_exists($this->courseModel, 'createSection')) {
                return $this->jsonError('Sections feature not yet implemented', null, 501);
            }

            // Validate required fields
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                return $this->jsonError('Section title is required', null, 400);
            }

            // Prepare section data
            $sectionData = [
                'course_id' => $courseId,
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'order' => intval($_POST['order'] ?? 0)
            ];

            // Create section
            $sectionId = $this->courseModel->createSection($sectionData);

            if ($sectionId) {
                $this->logger->log('info', 'Course section created', [
                    'section_id' => $sectionId,
                    'course_id' => $courseId,
                    'title' => $title,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'section_id' => $sectionId,
                    'course_id' => $courseId,
                    'title' => $title
                ], 'Section created successfully');
            } else {
                return $this->jsonError('Failed to create section', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course section creation failed', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during section creation', null, 500);
        }
    }
}
