<?php
/**
 * Member\CourseController - Course browsing and enrollment for members
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/CourseService.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class CourseController extends BaseController {
    private $courseService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->courseService = new CourseService($this->conn);
    }

    /**
     * Display course catalog/listing page
     *
     * Route: GET /courses
     * Middleware: AuthMiddleware
     */
    public function index() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $type = $this->input('type');
            $search = $this->input('search');

            // Get courses based on filters
            if ($search) {
                $courses = $this->courseService->searchCourses($search, $userId);
            } elseif ($type) {
                $courses = $this->courseService->getCoursesByType($type, $userId);
            } else {
                $courses = $this->courseService->getAllCourses($userId);
            }

            // Get course types for filter
            $courseTypes = $this->courseService->getCourseTypes();

            // Get user's enrollments
            $enrolledCourses = $this->courseService->getUserEnrolledCourses($userId);

            $data = [
                'pageTitle' => 'Courses',
                'currentPage' => 'courses',
                'user' => $this->currentUser(),
                'courses' => $courses,
                'courseTypes' => $courseTypes,
                'enrolledCourses' => $enrolledCourses,
                'selectedType' => $type,
                'searchQuery' => $search
            ];

            $this->logAction('courses_view', ['user_id' => $userId]);

            return $this->view('member.courses.index', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Courses page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load courses'], 'error');
        }
    }

    /**
     * Display single course details
     *
     * Route: GET /courses/{id}
     * Middleware: AuthMiddleware
     */
    public function show($courseId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $course = $this->courseService->getCourseDetails($courseId, $userId);

            if (!$course) {
                return $this->view('errors.404', ['error' => 'Course not found'], 'error');
            }

            // Get course statistics
            $statistics = $this->courseService->getCourseStatistics($courseId);

            // Get recommended courses
            $recommended = $this->courseService->getRecommendedCourses($userId, 3);

            $data = [
                'pageTitle' => $course['title'],
                'currentPage' => 'courses',
                'user' => $this->currentUser(),
                'course' => $course,
                'statistics' => $statistics,
                'recommended' => $recommended
            ];

            $this->logAction('course_view', ['course_id' => $courseId, 'user_id' => $userId]);

            return $this->view('member.courses.show', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Course detail load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load course'], 'error');
        }
    }

    /**
     * Display user's enrolled courses (My Courses)
     *
     * Route: GET /my-courses
     * Middleware: AuthMiddleware
     */
    public function myCourses() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $enrolledCourses = $this->courseService->getUserEnrolledCourses($userId);
            $inProgressCourses = $this->courseService->getInProgressCourses($userId);
            $completedCourses = $this->courseService->getCompletedCourses($userId);

            $data = [
                'pageTitle' => 'My Courses',
                'currentPage' => 'my-courses',
                'user' => $this->currentUser(),
                'enrolledCourses' => $enrolledCourses,
                'inProgressCourses' => $inProgressCourses,
                'completedCourses' => $completedCourses
            ];

            $this->logAction('my_courses_view', ['user_id' => $userId]);

            return $this->view('member.courses.my-courses', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("My courses page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load your courses'], 'error');
        }
    }

    /**
     * Enroll user in a course
     *
     * Route: POST /courses/{id}/enroll
     * Middleware: AuthMiddleware, CSRF
     */
    public function enroll($courseId) {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];

            $result = $this->courseService->enrollUser($userId, $courseId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Successfully enrolled in course');
                } else {
                    return $this->redirectWithSuccess("/courses/{$courseId}", 'Successfully enrolled in course');
                }
            }

            throw new Exception("Failed to enroll in course");

        } catch (Exception $e) {
            $this->logger->error("Course enrollment failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/courses/{$courseId}", $e->getMessage());
            }
        }
    }

    /**
     * Unenroll user from a course
     *
     * Route: DELETE /courses/{id}/enroll
     * Middleware: AuthMiddleware, CSRF
     */
    public function unenroll($courseId) {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];

            $result = $this->courseService->unenrollUser($userId, $courseId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Successfully unenrolled from course');
                } else {
                    return $this->redirectWithSuccess('/my-courses', 'Successfully unenrolled from course');
                }
            }

            throw new Exception("Failed to unenroll from course");

        } catch (Exception $e) {
            $this->logger->error("Course unenrollment failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/courses/{$courseId}", $e->getMessage());
            }
        }
    }

    /**
     * Get all courses via AJAX
     *
     * Route: GET /api/courses
     * Middleware: AuthMiddleware
     */
    public function getCourses() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $type = $this->input('type');
            $search = $this->input('search');

            if ($search) {
                $courses = $this->courseService->searchCourses($search, $userId);
            } elseif ($type) {
                $courses = $this->courseService->getCoursesByType($type, $userId);
            } else {
                $courses = $this->courseService->getAllCourses($userId);
            }

            return $this->jsonSuccess($courses, 'Courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load courses: ' . $e->getMessage());
        }
    }

    /**
     * Get course details via AJAX
     *
     * Route: GET /api/courses/{id}
     * Middleware: AuthMiddleware
     */
    public function getCourseData($courseId) {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $course = $this->courseService->getCourseDetails($courseId, $userId);

            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            return $this->jsonSuccess($course, 'Course details retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load course: ' . $e->getMessage());
        }
    }

    /**
     * Get featured courses via AJAX
     *
     * Route: GET /api/courses/featured
     * Middleware: AuthMiddleware
     */
    public function getFeatured() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $limit = $this->input('limit', 6);

            $courses = $this->courseService->getFeaturedCourses($limit, $userId);

            return $this->jsonSuccess($courses, 'Featured courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load featured courses: ' . $e->getMessage());
        }
    }

    /**
     * Get recommended courses via AJAX
     *
     * Route: GET /api/courses/recommended
     * Middleware: AuthMiddleware
     */
    public function getRecommended() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $limit = $this->input('limit', 3);

            $courses = $this->courseService->getRecommendedCourses($userId, $limit);

            return $this->jsonSuccess($courses, 'Recommended courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load recommended courses: ' . $e->getMessage());
        }
    }

    /**
     * Get user's enrolled courses via AJAX
     *
     * Route: GET /api/my-courses
     * Middleware: AuthMiddleware
     */
    public function getEnrolledCourses() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $courses = $this->courseService->getUserEnrolledCourses($userId);

            return $this->jsonSuccess($courses, 'Enrolled courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load enrolled courses: ' . $e->getMessage());
        }
    }

    /**
     * Get in-progress courses via AJAX
     *
     * Route: GET /api/courses/in-progress
     * Middleware: AuthMiddleware
     */
    public function getInProgress() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $courses = $this->courseService->getInProgressCourses($userId);

            return $this->jsonSuccess($courses, 'In-progress courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load in-progress courses: ' . $e->getMessage());
        }
    }

    /**
     * Get completed courses via AJAX
     *
     * Route: GET /api/courses/completed
     * Middleware: AuthMiddleware
     */
    public function getCompleted() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $courses = $this->courseService->getCompletedCourses($userId);

            return $this->jsonSuccess($courses, 'Completed courses retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load completed courses: ' . $e->getMessage());
        }
    }

    /**
     * Search courses via AJAX
     *
     * Route: GET /api/courses/search
     * Middleware: AuthMiddleware
     */
    public function search() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $query = $this->input('q', '');

            if (empty($query)) {
                return $this->jsonSuccess([], 'No search query provided');
            }

            $courses = $this->courseService->searchCourses($query, $userId);

            return $this->jsonSuccess($courses, 'Search results retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get course types via AJAX
     *
     * Route: GET /api/courses/types
     * Middleware: AuthMiddleware
     */
    public function getTypes() {
        $this->requireAuth();

        try {
            $types = $this->courseService->getCourseTypes();

            return $this->jsonSuccess($types, 'Course types retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load course types: ' . $e->getMessage());
        }
    }

    /**
     * Get course statistics via AJAX
     *
     * Route: GET /api/courses/{id}/statistics
     * Middleware: AuthMiddleware
     */
    public function getStatistics($courseId) {
        $this->requireAuth();

        try {
            $statistics = $this->courseService->getCourseStatistics($courseId);

            if (!$statistics) {
                return $this->jsonError('Course not found', null, 404);
            }

            return $this->jsonSuccess($statistics, 'Course statistics retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load statistics: ' . $e->getMessage());
        }
    }
}
