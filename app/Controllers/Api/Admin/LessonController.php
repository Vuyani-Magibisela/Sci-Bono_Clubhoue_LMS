<?php
/**
 * Api\Admin\LessonController
 *
 * Handles AJAX API operations for lesson management
 *
 * Phase 5 Week 4: Admin Resource Management APIs - Lesson Management
 * Created: January 10, 2026
 */

namespace Api\Admin;

require_once __DIR__ . '/../../BaseController.php';
require_once __DIR__ . '/../../../Models/Admin/AdminLessonModel.php';
require_once __DIR__ . '/../../../Models/Admin/AdminCourseModel.php';
require_once __DIR__ . '/../../../../core/CSRF.php';

class LessonController extends \BaseController {
    private $lessonModel;
    private $courseModel;

    public function __construct($conn = null) {
        if ($conn === null) {
            global $conn;
        }

        parent::__construct($conn);
        $this->lessonModel = new \AdminLessonModel($conn);
        $this->courseModel = new \AdminCourseModel($conn);
    }

    /**
     * Create a new lesson for a course section
     * POST /api/v1/admin/courses/{courseId}/lessons
     *
     * Expected POST data:
     * - section_id: Section ID (required)
     * - title: Lesson title (required)
     * - description: Lesson description
     * - content: Lesson content
     * - order_number: Display order (optional)
     * - duration: Lesson duration in minutes
     * - is_published: Published status (0|1, default 0)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - lesson_id: ID of created lesson
     * - lesson: Created lesson object
     * - message: Status message
     */
    public function createLesson($courseId) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Validate required fields
            $sectionId = intval($_POST['section_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');

            if ($sectionId <= 0) {
                return $this->jsonError('Section ID is required', null, 400);
            }

            if (empty($title)) {
                return $this->jsonError('Lesson title is required', null, 400);
            }

            // Verify section exists and belongs to the course
            $sectionDetails = $this->lessonModel->getSectionDetails($sectionId);
            if (!$sectionDetails) {
                return $this->jsonError('Section not found', null, 404);
            }

            if ($sectionDetails['course_id'] != $courseId) {
                return $this->jsonError('Section does not belong to this course', null, 400);
            }

            // Prepare lesson data
            $lessonData = [
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'order_number' => intval($_POST['order_number'] ?? 0),
                'duration' => intval($_POST['duration'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? intval($_POST['is_published']) : 0
            ];

            // Create lesson
            $lessonId = $this->lessonModel->createLesson($sectionId, $lessonData);

            if ($lessonId) {
                // Get the created lesson
                $lesson = $this->lessonModel->getLessonDetails($lessonId);

                $this->logger->log('info', 'Lesson created', [
                    'lesson_id' => $lessonId,
                    'section_id' => $sectionId,
                    'course_id' => $courseId,
                    'title' => $title,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'lesson_id' => $lessonId,
                    'lesson' => $lesson,
                    'section_id' => $sectionId,
                    'course_id' => $courseId
                ], 'Lesson created successfully', 201);
            } else {
                return $this->jsonError('Failed to create lesson', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Lesson creation failed', [
                'course_id' => $courseId,
                'section_id' => $_POST['section_id'] ?? 'unknown',
                'title' => $_POST['title'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during lesson creation', null, 500);
        }
    }

    /**
     * Alias for createLesson (RESTful store method)
     */
    public function store($courseId) {
        return $this->createLesson($courseId);
    }

    /**
     * Update an existing lesson
     * PUT /api/v1/admin/lessons/{id}
     *
     * Expected POST data (PUT simulation via POST):
     * - title: Lesson title
     * - description: Lesson description
     * - content: Lesson content
     * - order_number: Display order
     * - duration: Lesson duration in minutes
     * - is_published: Published status (0|1)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - lesson_id: ID of updated lesson
     * - lesson: Updated lesson object
     * - message: Status message
     */
    public function updateLesson($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if lesson exists
            $existingLesson = $this->lessonModel->getLessonDetails($id);
            if (!$existingLesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            // Prepare update data (only update fields that are provided)
            $lessonData = [
                'title' => trim($_POST['title'] ?? $existingLesson['title']),
                'description' => trim($_POST['description'] ?? $existingLesson['description']),
                'content' => $_POST['content'] ?? $existingLesson['content'],
                'order_number' => isset($_POST['order_number']) ? intval($_POST['order_number']) : $existingLesson['order_number'],
                'duration' => isset($_POST['duration']) ? intval($_POST['duration']) : $existingLesson['duration'],
                'is_published' => isset($_POST['is_published']) ? intval($_POST['is_published']) : $existingLesson['is_published']
            ];

            // Validate required fields
            if (empty($lessonData['title'])) {
                return $this->jsonError('Lesson title cannot be empty', null, 400);
            }

            // Update lesson
            $result = $this->lessonModel->updateLesson($id, $lessonData);

            if ($result) {
                // Get the updated lesson
                $lesson = $this->lessonModel->getLessonDetails($id);

                $this->logger->log('info', 'Lesson updated', [
                    'lesson_id' => $id,
                    'title' => $lessonData['title'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'lesson_id' => $id,
                    'lesson' => $lesson
                ], 'Lesson updated successfully');
            } else {
                return $this->jsonError('Failed to update lesson', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Lesson update failed', [
                'lesson_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during lesson update', null, 500);
        }
    }

    /**
     * Alias for updateLesson (RESTful update method)
     */
    public function update($id) {
        return $this->updateLesson($id);
    }

    /**
     * Delete a lesson
     * DELETE /api/v1/admin/lessons/{id}
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - lesson_id: ID of deleted lesson
     * - message: Status message
     */
    public function deleteLesson($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if lesson exists
            $lesson = $this->lessonModel->getLessonDetails($id);
            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            // Delete lesson
            $result = $this->lessonModel->deleteLesson($id);

            if ($result) {
                $this->logger->log('info', 'Lesson deleted', [
                    'lesson_id' => $id,
                    'title' => $lesson['title'],
                    'section_id' => $lesson['section_id'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'lesson_id' => $id,
                    'section_id' => $lesson['section_id']
                ], 'Lesson deleted successfully');
            } else {
                return $this->jsonError('Failed to delete lesson', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Lesson deletion failed', [
                'lesson_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during lesson deletion', null, 500);
        }
    }

    /**
     * Alias for deleteLesson (RESTful destroy method)
     */
    public function destroy($id) {
        return $this->deleteLesson($id);
    }

    /**
     * Upload lesson content file
     * POST /api/v1/admin/lessons/{id}/content
     *
     * Expected FILES data:
     * - content_file: Content file (PDF, DOCX, PPTX, etc., max 10MB)
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - lesson_id: ID of lesson
     * - file_path: Path to uploaded file
     * - file_url: Full URL to file
     * - message: Status message
     */
    public function uploadContent($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if lesson exists
            $lesson = $this->lessonModel->getLessonDetails($id);
            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            // Check if file was uploaded
            if (!isset($_FILES['content_file']) || $_FILES['content_file']['error'] === UPLOAD_ERR_NO_FILE) {
                return $this->jsonError('No content file uploaded', null, 400);
            }

            // Check for upload errors
            if ($_FILES['content_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];

                $errorMsg = $errorMessages[$_FILES['content_file']['error']] ?? 'Unknown upload error';
                return $this->jsonError($errorMsg, null, 500);
            }

            // Validate file type
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'video/mp4',
                'video/webm'
            ];

            $fileType = $_FILES['content_file']['type'];
            if (!in_array($fileType, $allowedTypes)) {
                return $this->jsonError('Invalid file type. Only PDF, DOCX, PPTX, TXT, MP4, and WEBM are allowed', null, 400);
            }

            // Validate file size (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if ($_FILES['content_file']['size'] > $maxSize) {
                return $this->jsonError('File size exceeds 10MB limit', null, 400);
            }

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../../../public/assets/uploads/lessons/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['content_file']['name'], PATHINFO_EXTENSION);
            $filename = 'lesson_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            $relativePath = 'public/assets/uploads/lessons/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($_FILES['content_file']['tmp_name'], $uploadPath)) {
                return $this->jsonError('Failed to move uploaded file', null, 500);
            }

            // Update lesson content field with file path
            $result = $this->lessonModel->updateLesson($id, [
                'title' => $lesson['title'],
                'description' => $lesson['description'],
                'content' => $relativePath, // Store file path in content field
                'order_number' => $lesson['order_number'],
                'duration' => $lesson['duration'],
                'is_published' => $lesson['is_published']
            ]);

            if ($result) {
                // Generate file URL
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $fileUrl = $protocol . '://' . $host . '/' . $relativePath;

                $this->logger->log('info', 'Lesson content uploaded', [
                    'lesson_id' => $id,
                    'filename' => $filename,
                    'size' => $_FILES['content_file']['size'],
                    'type' => $fileType,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'lesson_id' => $id,
                    'file_path' => $relativePath,
                    'file_url' => $fileUrl,
                    'filename' => $filename,
                    'file_type' => $fileType
                ], 'Lesson content uploaded successfully');
            } else {
                // Delete uploaded file if database update failed
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                return $this->jsonError('Failed to update lesson with content path', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Lesson content upload failed', [
                'lesson_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during content upload', null, 500);
        }
    }

    /**
     * Get lessons for a specific section
     * GET /api/v1/admin/sections/{sectionId}/lessons
     *
     * Response:
     * - success: boolean
     * - section_id: ID of section
     * - lessons: Array of lesson objects
     * - count: Number of lessons
     */
    public function getSectionLessons($sectionId) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Get lessons
            $lessons = $this->lessonModel->getSectionLessons($sectionId);

            return $this->jsonSuccess([
                'section_id' => $sectionId,
                'lessons' => $lessons,
                'count' => count($lessons)
            ], 'Lessons retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Section lessons retrieval failed', [
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving section lessons', null, 500);
        }
    }

    /**
     * Get lesson details
     * GET /api/v1/admin/lessons/{id}
     *
     * Response:
     * - success: boolean
     * - lesson: Lesson object
     */
    public function show($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Get lesson
            $lesson = $this->lessonModel->getLessonDetails($id);

            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            return $this->jsonSuccess([
                'lesson' => $lesson
            ], 'Lesson retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Lesson retrieval failed', [
                'lesson_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving lesson', null, 500);
        }
    }
}
