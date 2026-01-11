<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CourseModel;
use App\Models\LessonModel;

/**
 * Public Lesson API Controller
 *
 * Handles lesson viewing and progress tracking for enrolled users
 * All endpoints require authentication and course enrollment
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 5 Day 3 (January 11, 2026)
 */
class LessonController extends BaseController {

    private $courseModel;
    private $lessonModel;

    public function __construct() {
        parent::__construct();
        $this->courseModel = new CourseModel($this->conn);
        $this->lessonModel = new LessonModel($this->conn);
    }

    /**
     * GET /api/v1/courses/{courseId}/lessons
     * Get all lessons for a course (grouped by section)
     *
     * Shows full content only if user is enrolled
     * Shows titles/descriptions only if not enrolled (preview)
     *
     * @param int $courseId Course ID
     * @return JSON response with lessons
     */
    public function getCourseLessons($courseId) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate course exists
            $course = $this->courseModel->getCourseById($courseId);

            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Check enrollment status
            $enrollment = $this->courseModel->getUserEnrollment($courseId, $userId);
            $isEnrolled = $enrollment !== null;

            // Get course sections with lessons
            $sections = $this->lessonModel->getCourseSectionsWithLessons($courseId, $userId, $isEnrolled);

            // If not enrolled, limit information shown
            if (!$isEnrolled) {
                foreach ($sections as &$section) {
                    foreach ($section['lessons'] as &$lesson) {
                        // Only show preview information
                        if (!$lesson['is_free_preview']) {
                            unset($lesson['content']);
                            unset($lesson['video_url']);
                            unset($lesson['attachments']);
                            unset($lesson['materials']);
                            $lesson['preview_only'] = true;
                        }
                    }
                }
            }

            return $this->jsonSuccess([
                'course' => [
                    'id' => $course['id'],
                    'title' => $course['title']
                ],
                'is_enrolled' => $isEnrolled,
                'sections' => $sections,
                'total_sections' => count($sections),
                'total_lessons' => array_sum(array_column($sections, 'lesson_count'))
            ], 'Course lessons retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve course lessons', [
                'course_id' => $courseId,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving lessons', null, 500);
        }
    }

    /**
     * GET /api/v1/lessons/{id}
     * Get lesson details with content
     *
     * Requires enrollment for full content access
     * Tracks lesson view
     *
     * @param int $id Lesson ID
     * @return JSON response with lesson details
     */
    public function show($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Get lesson details
            $lesson = $this->lessonModel->getLessonById($id);

            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            // Get course ID from lesson
            $courseId = $lesson['course_id'];

            // Check enrollment
            $enrollment = $this->courseModel->getUserEnrollment($courseId, $userId);

            if (!$enrollment) {
                // Not enrolled - check if free preview
                if (!$lesson['is_free_preview']) {
                    return $this->jsonError('You must be enrolled in this course to view this lesson', [
                        'course_id' => $courseId,
                        'requires_enrollment' => true
                    ], 403);
                }

                // Free preview - limit content
                unset($lesson['quiz_questions']);
                unset($lesson['assignment_details']);
                $lesson['preview_mode'] = true;
            }

            // Get user's progress for this lesson
            $progress = $this->lessonModel->getUserLessonProgress($id, $userId);
            $lesson['user_progress'] = $progress;

            // Track lesson view (only if enrolled)
            if ($enrollment) {
                $this->lessonModel->trackLessonView($id, $userId, $enrollment['id']);
            }

            // Get next lesson
            $nextLesson = $this->lessonModel->getNextLesson($id);
            $lesson['next_lesson'] = $nextLesson;

            // Get previous lesson
            $previousLesson = $this->lessonModel->getPreviousLesson($id);
            $lesson['previous_lesson'] = $previousLesson;

            return $this->jsonSuccess([
                'lesson' => $lesson,
                'is_enrolled' => $enrollment !== null
            ], 'Lesson details retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve lesson details', [
                'lesson_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving lesson', null, 500);
        }
    }

    /**
     * POST /api/v1/lessons/{id}/complete
     * Mark lesson as complete for authenticated user
     *
     * Requires enrollment in the course
     * Updates enrollment progress
     *
     * Optional POST parameters:
     * - time_spent (int): Time spent on lesson in minutes
     * - quiz_score (float): Quiz score percentage (0-100)
     * - notes (string): User notes
     *
     * @param int $id Lesson ID
     * @return JSON response with updated progress
     */
    public function markComplete($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Get lesson details
            $lesson = $this->lessonModel->getLessonById($id);

            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            $courseId = $lesson['course_id'];

            // Check enrollment
            $enrollment = $this->courseModel->getUserEnrollment($courseId, $userId);

            if (!$enrollment) {
                return $this->jsonError('You must be enrolled in this course', null, 403);
            }

            // Get optional parameters
            $timeSpent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;
            $quizScore = isset($_POST['quiz_score']) ? floatval($_POST['quiz_score']) : null;
            $notes = $_POST['notes'] ?? null;

            // Mark lesson as complete
            $result = $this->lessonModel->markLessonComplete(
                $id,
                $userId,
                $enrollment['id'],
                $timeSpent,
                $quizScore,
                $notes
            );

            if ($result) {
                // Get updated progress
                $progress = $this->lessonModel->getUserLessonProgress($id, $userId);

                // Update enrollment progress
                $this->updateEnrollmentProgress($enrollment['id'], $courseId, $userId);

                // Get updated enrollment
                $updatedEnrollment = $this->courseModel->getUserEnrollment($courseId, $userId);

                $this->logger->log('info', 'Lesson marked complete', [
                    'user_id' => $userId,
                    'lesson_id' => $id,
                    'course_id' => $courseId,
                    'time_spent' => $timeSpent,
                    'quiz_score' => $quizScore
                ]);

                return $this->jsonSuccess([
                    'lesson_id' => $id,
                    'progress' => $progress,
                    'course_progress' => [
                        'percentage' => $updatedEnrollment['progress'],
                        'lessons_completed' => $updatedEnrollment['lessons_completed']
                    ]
                ], 'Lesson marked as complete');
            }

            return $this->jsonError('Failed to mark lesson complete', null, 500);

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to mark lesson complete', [
                'lesson_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while updating lesson', null, 500);
        }
    }

    /**
     * GET /api/v1/lessons/{id}/progress
     * Get user's progress for a specific lesson
     *
     * @param int $id Lesson ID
     * @return JSON response with lesson progress
     */
    public function getLessonProgress($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Get lesson details
            $lesson = $this->lessonModel->getLessonById($id);

            if (!$lesson) {
                return $this->jsonError('Lesson not found', null, 404);
            }

            // Get progress
            $progress = $this->lessonModel->getUserLessonProgress($id, $userId);

            if (!$progress) {
                // No progress yet - return default
                $progress = [
                    'status' => 'not_started',
                    'progress_percentage' => 0,
                    'time_spent' => 0,
                    'completed_at' => null
                ];
            }

            return $this->jsonSuccess([
                'lesson' => [
                    'id' => $lesson['id'],
                    'title' => $lesson['title'],
                    'duration' => $lesson['duration']
                ],
                'progress' => $progress
            ], 'Lesson progress retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve lesson progress', [
                'lesson_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving progress', null, 500);
        }
    }

    /**
     * Update enrollment progress after lesson completion
     *
     * @param int $enrollmentId Enrollment ID
     * @param int $courseId Course ID
     * @param int $userId User ID
     * @return bool Success status
     */
    private function updateEnrollmentProgress($enrollmentId, $courseId, $userId) {
        // Get total lessons and completed lessons
        $totalLessons = $this->courseModel->getCourseLessonsCount($courseId);
        $lessonProgress = $this->courseModel->getUserLessonProgress($courseId, $userId);
        $completedLessons = $lessonProgress['completed'] ?? 0;

        // Calculate progress percentage
        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100, 2)
            : 0;

        // Update enrollment
        return $this->lessonModel->updateEnrollmentProgress(
            $enrollmentId,
            $progressPercentage,
            $completedLessons,
            $totalLessons
        );
    }

    /**
     * Helper method to require authentication
     *
     * @throws \Exception if not authenticated
     */
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            throw new \Exception('Authentication required');
        }
    }
}
