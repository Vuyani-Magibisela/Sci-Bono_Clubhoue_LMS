<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CourseModel;

/**
 * Course Enrollment API Controller
 *
 * Handles course enrollment, unenrollment, and user course management
 * All endpoints require authentication
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 5 Day 2 (January 11, 2026)
 */
class EnrollmentController extends BaseController {

    private $courseModel;

    public function __construct() {
        parent::__construct();
        $this->courseModel = new CourseModel($this->conn);
    }

    /**
     * POST /api/v1/courses/{id}/enroll
     * Enroll authenticated user in a course
     *
     * @param int $id Course ID
     * @return JSON response with enrollment details
     */
    public function enroll($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Validate course exists and is published
            $course = $this->courseModel->getCourseById($id);

            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            if (!$course['is_published'] || $course['status'] !== 'published') {
                return $this->jsonError('Course is not available for enrollment', null, 403);
            }

            // Check if already enrolled
            $existingEnrollment = $this->courseModel->getUserEnrollment($id, $userId);

            if ($existingEnrollment) {
                // Check enrollment status
                if ($existingEnrollment['status'] === 'dropped') {
                    // Allow re-enrollment
                    $result = $this->courseModel->reactivateEnrollment($existingEnrollment['id']);

                    if ($result) {
                        $enrollment = $this->courseModel->getUserEnrollment($id, $userId);

                        $this->logger->log('info', 'Course enrollment reactivated', [
                            'user_id' => $userId,
                            'course_id' => $id,
                            'enrollment_id' => $existingEnrollment['id']
                        ]);

                        return $this->jsonSuccess([
                            'enrollment' => $enrollment,
                            'course' => [
                                'id' => $course['id'],
                                'title' => $course['title'],
                                'description' => $course['description']
                            ]
                        ], 'Successfully re-enrolled in course', 200);
                    }

                    return $this->jsonError('Failed to reactivate enrollment', null, 500);
                }

                return $this->jsonError('You are already enrolled in this course', [
                    'enrollment_status' => $existingEnrollment['status']
                ], 400);
            }

            // Create new enrollment
            $enrollmentId = $this->courseModel->createEnrollment($userId, $id);

            if ($enrollmentId) {
                $enrollment = $this->courseModel->getUserEnrollment($id, $userId);

                $this->logger->log('info', 'User enrolled in course', [
                    'user_id' => $userId,
                    'course_id' => $id,
                    'enrollment_id' => $enrollmentId
                ]);

                return $this->jsonSuccess([
                    'enrollment_id' => $enrollmentId,
                    'enrollment' => $enrollment,
                    'course' => [
                        'id' => $course['id'],
                        'title' => $course['title'],
                        'description' => $course['description'],
                        'total_lessons' => $this->courseModel->getCourseLessonsCount($id)
                    ]
                ], 'Successfully enrolled in course', 201);
            }

            return $this->jsonError('Failed to enroll in course', null, 500);

        } catch (\Exception $e) {
            $this->logger->log('error', 'Enrollment failed', [
                'course_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during enrollment', null, 500);
        }
    }

    /**
     * DELETE /api/v1/courses/{id}/enroll
     * Unenroll authenticated user from a course
     *
     * @param int $id Course ID
     * @return JSON response with success status
     */
    public function unenroll($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if enrolled
            $enrollment = $this->courseModel->getUserEnrollment($id, $userId);

            if (!$enrollment) {
                return $this->jsonError('You are not enrolled in this course', null, 404);
            }

            // Check if enrollment can be dropped
            if ($enrollment['status'] === 'completed') {
                return $this->jsonError('Cannot unenroll from a completed course', null, 400);
            }

            if ($enrollment['status'] === 'dropped') {
                return $this->jsonError('You have already unenrolled from this course', null, 400);
            }

            // Determine unenrollment strategy based on progress
            $progress = floatval($enrollment['progress']);

            if ($progress > 0) {
                // Soft delete: Mark as dropped (preserve progress)
                $result = $this->courseModel->updateEnrollmentStatus($enrollment['id'], 'dropped');
                $actionType = 'marked as dropped';
            } else {
                // Hard delete: Remove enrollment (no progress made)
                $result = $this->courseModel->deleteEnrollment($enrollment['id']);
                $actionType = 'deleted';
            }

            if ($result) {
                $this->logger->log('info', 'User unenrolled from course', [
                    'user_id' => $userId,
                    'course_id' => $id,
                    'enrollment_id' => $enrollment['id'],
                    'progress' => $progress,
                    'action' => $actionType
                ]);

                return $this->jsonSuccess([
                    'course_id' => $id,
                    'enrollment_id' => $enrollment['id'],
                    'action' => $actionType
                ], 'Successfully unenrolled from course');
            }

            return $this->jsonError('Failed to unenroll from course', null, 500);

        } catch (\Exception $e) {
            $this->logger->log('error', 'Unenrollment failed', [
                'course_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during unenrollment', null, 500);
        }
    }

    /**
     * GET /api/v1/user/courses
     * Get authenticated user's enrolled courses
     *
     * Query Parameters:
     * - status (string): Filter by enrollment status (enrolled, active, completed, dropped)
     * - limit (int): Results per page (default: 20, max: 100)
     * - offset (int): Pagination offset (default: 0)
     *
     * @return JSON response with user's courses
     */
    public function userCourses() {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Get query parameters
            $status = $_GET['status'] ?? null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            // Validate limits
            if ($limit < 1) $limit = 20;
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;

            // Build filters
            $filters = [];
            if ($status) {
                // Validate status
                $validStatuses = ['enrolled', 'active', 'completed', 'dropped', 'suspended'];
                if (in_array($status, $validStatuses)) {
                    $filters['status'] = $status;
                }
            }

            // Get user's enrolled courses
            $courses = $this->courseModel->getUserEnrolledCourses($userId, $filters, $limit, $offset);
            $totalCount = $this->courseModel->getUserEnrolledCoursesCount($userId, $filters);

            // Enhance with additional data
            foreach ($courses as &$course) {
                // Calculate progress percentage
                $course['progress_percentage'] = floatval($course['progress']);

                // Add completion status
                if ($course['progress'] >= 100) {
                    $course['completion_status'] = 'completed';
                } else if ($course['progress'] > 0) {
                    $course['completion_status'] = 'in_progress';
                } else {
                    $course['completion_status'] = 'not_started';
                }

                // Calculate days enrolled
                if ($course['enrollment_date']) {
                    $enrollDate = new \DateTime($course['enrollment_date']);
                    $now = new \DateTime();
                    $course['days_enrolled'] = $enrollDate->diff($now)->days;
                }
            }

            return $this->jsonSuccess([
                'courses' => $courses,
                'pagination' => [
                    'total' => $totalCount,
                    'count' => count($courses),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ],
                'filters_applied' => array_filter([
                    'status' => $status
                ])
            ], 'User courses retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve user courses', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving courses', null, 500);
        }
    }

    /**
     * GET /api/v1/user/courses/{id}/progress
     * Get detailed progress for a user's enrolled course
     *
     * @param int $id Course ID
     * @return JSON response with detailed progress
     */
    public function courseProgress($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Check enrollment
            $enrollment = $this->courseModel->getUserEnrollment($id, $userId);

            if (!$enrollment) {
                return $this->jsonError('You are not enrolled in this course', null, 404);
            }

            // Get course details
            $course = $this->courseModel->getCourseById($id);

            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Get sections with progress
            $sections = $this->courseModel->getCourseSectionsWithProgress($id, $userId);

            // Get lesson progress summary
            $lessonProgress = $this->courseModel->getUserLessonProgress($id, $userId);

            // Calculate detailed progress metrics
            $totalLessons = $this->courseModel->getCourseLessonsCount($id);
            $completedLessons = $lessonProgress['completed'] ?? 0;
            $inProgressLessons = $lessonProgress['in_progress'] ?? 0;

            $progressPercentage = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100, 2)
                : 0;

            // Get time spent
            $totalTimeSpent = $lessonProgress['total_time_spent'] ?? 0;

            // Build progress response
            $progress = [
                'enrollment' => [
                    'id' => $enrollment['id'],
                    'enrolled_at' => $enrollment['enrollment_date'],
                    'status' => $enrollment['status'],
                    'last_accessed' => $enrollment['last_accessed_at']
                ],
                'course' => [
                    'id' => $course['id'],
                    'title' => $course['title'],
                    'total_sections' => count($sections),
                    'total_lessons' => $totalLessons
                ],
                'progress' => [
                    'percentage' => $progressPercentage,
                    'lessons_completed' => $completedLessons,
                    'lessons_in_progress' => $inProgressLessons,
                    'lessons_not_started' => $totalLessons - $completedLessons - $inProgressLessons,
                    'total_time_spent' => $totalTimeSpent,
                    'average_time_per_lesson' => $completedLessons > 0
                        ? round($totalTimeSpent / $completedLessons, 2)
                        : 0
                ],
                'sections' => $sections
            ];

            return $this->jsonSuccess([
                'progress' => $progress
            ], 'Course progress retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve course progress', [
                'course_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving progress', null, 500);
        }
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
