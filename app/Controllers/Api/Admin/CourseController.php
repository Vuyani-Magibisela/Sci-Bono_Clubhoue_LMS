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

    /**
     * Create a new course
     * POST /api/v1/admin/courses
     *
     * Expected POST data:
     * - title: Course title (required)
     * - description: Course description (required)
     * - type: Course type (full_course|short_course|lesson|skill_activity)
     * - difficulty_level: Difficulty level (beginner|intermediate|advanced)
     * - duration: Course duration in minutes
     * - image_path: Path to course image (optional)
     * - is_featured: Featured status (0|1, default 0)
     * - is_published: Published status (0|1, default 0)
     * - status: Course status (draft|active|archived, default draft)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of created course
     * - course: Created course object
     * - message: Status message
     */
    /**
     * Alias for createCourse (RESTful store method)
     */
    public function store() {
        return $this->createCourse();
    }

    public function createCourse() {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Validate required fields
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($title)) {
                return $this->jsonError('Course title is required', null, 400);
            }

            if (empty($description)) {
                return $this->jsonError('Course description is required', null, 400);
            }

            // Prepare course data
            $courseData = [
                'title' => $title,
                'description' => $description,
                'type' => $_POST['type'] ?? 'full_course',
                'difficulty_level' => $_POST['difficulty_level'] ?? 'beginner',
                'duration' => intval($_POST['duration'] ?? 0),
                'image_path' => $_POST['image_path'] ?? null,
                'is_featured' => isset($_POST['is_featured']) ? intval($_POST['is_featured']) : 0,
                'is_published' => isset($_POST['is_published']) ? intval($_POST['is_published']) : 0,
                'status' => $_POST['status'] ?? 'draft',
                'created_by' => $_SESSION['user_id'] ?? null,
                'course_code' => $_POST['course_code'] ?? '' // Will be auto-generated if empty
            ];

            // Validate created_by
            if (empty($courseData['created_by'])) {
                return $this->jsonError('User not authenticated', null, 401);
            }

            // Create course
            $courseId = $this->courseModel->createCourse($courseData);

            if ($courseId) {
                // Get the created course
                $course = $this->courseModel->getCourseById($courseId);

                $this->logger->log('info', 'Course created', [
                    'course_id' => $courseId,
                    'title' => $title,
                    'type' => $courseData['type'],
                    'user_id' => $courseData['created_by']
                ]);

                return $this->jsonSuccess([
                    'course_id' => $courseId,
                    'course' => $course
                ], 'Course created successfully', 201);
            } else {
                return $this->jsonError('Failed to create course', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course creation failed', [
                'title' => $_POST['title'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during course creation', null, 500);
        }
    }

    /**
     * Update an existing course
     * PUT /api/v1/admin/courses/{id}
     *
     * Expected POST data (PUT simulation via POST):
     * - title: Course title
     * - description: Course description
     * - type: Course type
     * - difficulty_level: Difficulty level
     * - duration: Course duration in minutes
     * - image_path: Path to course image
     * - is_featured: Featured status (0|1)
     * - is_published: Published status (0|1)
     * - status: Course status (draft|active|archived)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of updated course
     * - course: Updated course object
     * - message: Status message
     */
    /**
     * Alias for updateCourse (RESTful update method)
     */
    public function update($id) {
        return $this->updateCourse($id);
    }

    public function updateCourse($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if course exists
            $existingCourse = $this->courseModel->getCourseById($id);
            if (!$existingCourse) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Prepare update data (only update fields that are provided)
            $courseData = [
                'title' => trim($_POST['title'] ?? $existingCourse['title']),
                'description' => trim($_POST['description'] ?? $existingCourse['description']),
                'type' => $_POST['type'] ?? $existingCourse['type'],
                'difficulty_level' => $_POST['difficulty_level'] ?? $existingCourse['difficulty_level'],
                'duration' => isset($_POST['duration']) ? intval($_POST['duration']) : $existingCourse['duration'],
                'image_path' => $_POST['image_path'] ?? $existingCourse['image_path'],
                'is_featured' => isset($_POST['is_featured']) ? intval($_POST['is_featured']) : $existingCourse['is_featured'],
                'is_published' => isset($_POST['is_published']) ? intval($_POST['is_published']) : $existingCourse['is_published'],
                'status' => $_POST['status'] ?? $existingCourse['status']
            ];

            // Validate required fields
            if (empty($courseData['title'])) {
                return $this->jsonError('Course title cannot be empty', null, 400);
            }

            if (empty($courseData['description'])) {
                return $this->jsonError('Course description cannot be empty', null, 400);
            }

            // Update course
            $result = $this->courseModel->updateCourse($id, $courseData);

            if ($result) {
                // Get the updated course
                $course = $this->courseModel->getCourseById($id);

                $this->logger->log('info', 'Course updated', [
                    'course_id' => $id,
                    'title' => $courseData['title'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'course' => $course
                ], 'Course updated successfully');
            } else {
                return $this->jsonError('Failed to update course', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course update failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during course update', null, 500);
        }
    }

    /**
     * Delete a course
     * DELETE /api/v1/admin/courses/{id}
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of deleted course
     * - message: Status message
     */
    /**
     * Alias for deleteCourse (RESTful destroy method)
     */
    public function destroy($id) {
        return $this->deleteCourse($id);
    }

    public function deleteCourse($id) {
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

            // Delete course
            $result = $this->courseModel->deleteCourse($id);

            if ($result) {
                $this->logger->log('info', 'Course deleted', [
                    'course_id' => $id,
                    'title' => $course['title'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id
                ], 'Course deleted successfully');
            } else {
                return $this->jsonError('Failed to delete course', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course deletion failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during course deletion', null, 500);
        }
    }

    /**
     * Upload course image
     * POST /api/v1/admin/courses/{id}/image
     *
     * Expected FILES data:
     * - image: Image file (JPG, PNG, GIF, max 5MB)
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - course_id: ID of course
     * - image_path: Path to uploaded image
     * - image_url: Full URL to image
     * - message: Status message
     */
    public function uploadImage($id) {
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

            // Check if file was uploaded
            if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
                return $this->jsonError('No image file uploaded', null, 400);
            }

            // Check for upload errors
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];

                $errorMsg = $errorMessages[$_FILES['image']['error']] ?? 'Unknown upload error';
                return $this->jsonError($errorMsg, null, 500);
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['image']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                return $this->jsonError('Invalid file type. Only JPG, PNG, and GIF are allowed', null, 400);
            }

            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if ($_FILES['image']['size'] > $maxSize) {
                return $this->jsonError('File size exceeds 5MB limit', null, 400);
            }

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../../../public/assets/uploads/courses/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'course_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            $relativePath = 'public/assets/uploads/courses/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                return $this->jsonError('Failed to move uploaded file', null, 500);
            }

            // Delete old image if exists
            if (!empty($course['image_path']) && file_exists(__DIR__ . '/../../../../' . $course['image_path'])) {
                unlink(__DIR__ . '/../../../../' . $course['image_path']);
            }

            // Update course with new image path
            $result = $this->courseModel->updateCourse($id, [
                'title' => $course['title'],
                'description' => $course['description'],
                'type' => $course['type'],
                'difficulty_level' => $course['difficulty_level'],
                'duration' => $course['duration'],
                'image_path' => $relativePath,
                'is_featured' => $course['is_featured'],
                'is_published' => $course['is_published'],
                'status' => $course['status']
            ]);

            if ($result) {
                // Generate image URL
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $imageUrl = $protocol . '://' . $host . '/' . $relativePath;

                $this->logger->log('info', 'Course image uploaded', [
                    'course_id' => $id,
                    'filename' => $filename,
                    'size' => $_FILES['image']['size'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'image_path' => $relativePath,
                    'image_url' => $imageUrl,
                    'filename' => $filename
                ], 'Course image uploaded successfully');
            } else {
                // Delete uploaded file if database update failed
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                return $this->jsonError('Failed to update course with image path', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course image upload failed', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during image upload', null, 500);
        }
    }
}
