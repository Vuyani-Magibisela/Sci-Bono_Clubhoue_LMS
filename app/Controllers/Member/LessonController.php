<?php
/**
 * Member\LessonController - Lesson viewing and progress tracking
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/LessonService.php';
require_once __DIR__ . '/../../Services/CourseService.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class LessonController extends BaseController {
    private $lessonService;
    private $courseService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->lessonService = new LessonService($this->conn);
        $this->courseService = new CourseService($this->conn);
    }

    /**
     * Display lesson viewer page
     *
     * Route: GET /lessons/{id}
     * Middleware: AuthMiddleware
     */
    public function show($lessonId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $lesson = $this->lessonService->getLessonDetails($lessonId, $userId);

            if (!$lesson) {
                return $this->view('errors.404', ['error' => 'Lesson not found'], 'error');
            }

            // Check if user has access (enrolled in course)
            $courseId = $lesson['course_id'];
            if (!$this->courseService->canUserAccessCourse($userId, $courseId)) {
                return $this->view('errors.403', ['error' => 'You must enroll in this course to access lessons'], 'error');
            }

            // Mark lesson as in progress
            $this->lessonService->markLessonInProgress($userId, $lessonId);

            // Get next lesson
            $nextLesson = $this->lessonService->getNextLesson($userId, $courseId);

            // Get course completion summary
            $completionSummary = $this->lessonService->getCourseCompletionSummary($userId, $courseId);

            $data = [
                'pageTitle' => $lesson['title'],
                'currentPage' => 'lessons',
                'user' => $this->currentUser(),
                'lesson' => $lesson,
                'nextLesson' => $nextLesson,
                'completionSummary' => $completionSummary
            ];

            $this->logAction('lesson_view', ['lesson_id' => $lessonId, 'user_id' => $userId]);

            return $this->view('member.lessons.show', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Lesson page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load lesson'], 'error');
        }
    }

    /**
     * Mark lesson as completed
     *
     * Route: POST /lessons/{id}/complete
     * Middleware: AuthMiddleware, CSRF
     */
    public function complete($lessonId) {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];

            $result = $this->lessonService->markLessonCompleted($userId, $lessonId);

            if ($result) {
                // Get next lesson
                $lesson = $this->lessonService->getLessonDetails($lessonId, $userId);
                $nextLesson = $this->lessonService->getNextLesson($userId, $lesson['course_id']);

                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess([
                        'next_lesson' => $nextLesson,
                        'completed' => true
                    ], 'Lesson marked as completed');
                } else {
                    if ($nextLesson) {
                        return $this->redirectWithSuccess("/lessons/{$nextLesson['id']}", 'Lesson completed! Moving to next lesson');
                    } else {
                        return $this->redirectWithSuccess("/courses/{$lesson['course_id']}", 'Congratulations! You have completed all lessons in this course');
                    }
                }
            }

            throw new Exception("Failed to mark lesson as completed");

        } catch (Exception $e) {
            $this->logger->error("Lesson completion failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/lessons/{$lessonId}", $e->getMessage());
            }
        }
    }

    /**
     * Reset lesson progress (for retaking)
     *
     * Route: POST /lessons/{id}/reset
     * Middleware: AuthMiddleware, CSRF
     */
    public function reset($lessonId) {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];

            $result = $this->lessonService->resetLessonProgress($userId, $lessonId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Lesson progress reset');
                } else {
                    return $this->redirectWithSuccess("/lessons/{$lessonId}", 'Lesson progress reset successfully');
                }
            }

            throw new Exception("Failed to reset lesson progress");

        } catch (Exception $e) {
            $this->logger->error("Lesson reset failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/lessons/{$lessonId}", $e->getMessage());
            }
        }
    }

    /**
     * Get lesson details via AJAX
     *
     * Route: GET /api/lessons/{id}
     * Middleware: AuthMiddleware
     */
    public function getLessonData($lessonId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $lesson = $this->lessonService->getLessonDetails($lessonId, $userId);

            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            return $this->jsonSuccess($lesson, 'Lesson details retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load lesson: ' . $e->getMessage());
        }
    }

    /**
     * Get lessons for a section via AJAX
     *
     * Route: GET /api/sections/{id}/lessons
     * Middleware: AuthMiddleware
     */
    public function getSectionLessons($sectionId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $lessons = $this->lessonService->getSectionLessons($sectionId, $userId);

            return $this->jsonSuccess($lessons, 'Section lessons retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load lessons: ' . $e->getMessage());
        }
    }

    /**
     * Get lesson progress via AJAX
     *
     * Route: GET /api/lessons/{id}/progress
     * Middleware: AuthMiddleware
     */
    public function getProgress($lessonId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $progress = $this->lessonService->getLessonProgress($userId, $lessonId);

            return $this->jsonSuccess($progress, 'Lesson progress retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load progress: ' . $e->getMessage());
        }
    }

    /**
     * Get next lesson for a course via AJAX
     *
     * Route: GET /api/courses/{id}/next-lesson
     * Middleware: AuthMiddleware
     */
    public function getNextLesson($courseId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $nextLesson = $this->lessonService->getNextLesson($userId, $courseId);

            if (!$nextLesson) {
                return $this->jsonSuccess(null, 'No more lessons in this course');
            }

            return $this->jsonSuccess($nextLesson, 'Next lesson retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load next lesson: ' . $e->getMessage());
        }
    }

    /**
     * Get course completion summary via AJAX
     *
     * Route: GET /api/courses/{id}/completion
     * Middleware: AuthMiddleware
     */
    public function getCompletionSummary($courseId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $summary = $this->lessonService->getCourseCompletionSummary($userId, $courseId);

            return $this->jsonSuccess($summary, 'Completion summary retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load completion summary: ' . $e->getMessage());
        }
    }

    /**
     * Get recent lessons via AJAX
     *
     * Route: GET /api/lessons/recent
     * Middleware: AuthMiddleware
     */
    public function getRecentLessons() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $limit = $this->input('limit', 5);

            $lessons = $this->lessonService->getRecentLessons($userId, $limit);

            return $this->jsonSuccess($lessons, 'Recent lessons retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load recent lessons: ' . $e->getMessage());
        }
    }
}
