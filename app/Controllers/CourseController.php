<?php
/**
 * ====================================================================
 * DEPRECATED CONTROLLER - COMPATIBILITY WRAPPER
 * ====================================================================
 *
 * This controller has been deprecated as of Phase 4 Week 3 implementation.
 * All course operations are now handled by modern controllers with proper
 * service layer and BaseController architecture.
 *
 * **Modern Controllers:**
 * - app/Controllers/Member/CourseController (member course browsing/enrollment)
 * - app/Controllers/Admin/CourseController (admin course management)
 *
 * **Modern Routes:**
 * - GET /courses - Course listing (Member\CourseController::index)
 * - GET /courses/{id} - Course details (Member\CourseController::show)
 * - POST /courses/{id}/enroll - Enroll in course (Member\CourseController::enroll)
 * - GET /my-courses - My enrolled courses (Member\CourseController::myCourses)
 * - GET /admin/courses - Admin course management (Admin\CourseController::index)
 * - GET /admin/courses/{id} - Admin course details (Admin\CourseController::show)
 *
 * **Modern Views:**
 * - app/Views/member/courses/index.php (course listing)
 * - app/Views/member/courses/show.php (course details)
 * - app/Views/member/courses/my-courses.php (enrolled courses)
 * - app/Views/admin/courses/index.php (admin management)
 * - app/Views/admin/courses/show.php (admin details)
 * - app/Views/admin/courses/create.php (create course)
 * - app/Views/admin/courses/edit.php (edit course)
 *
 * @deprecated Since Phase 4 Week 3
 * @see app/Controllers/Member/CourseController
 * @see app/Controllers/Admin/CourseController
 * ====================================================================
 */

// Load modern controllers for compatibility
require_once __DIR__ . '/Member/CourseController.php';
require_once __DIR__ . '/../Services/CourseService.php';
require_once __DIR__ . '/../Models/CourseModel.php';
require_once __DIR__ . '/../Models/EnrollmentModel.php';

/**
 * Legacy CourseController - Compatibility Wrapper
 *
 * This class maintains backward compatibility for views still using
 * the old CourseController. It delegates to CourseService and models
 * to provide the same methods, but users should migrate to modern
 * routes and controllers.
 */
class CourseController {
    private $conn;
    private $courseModel;
    private $enrollmentModel;
    private $courseService;
    private $lessonController;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->courseModel = new CourseModel($conn);
        $this->enrollmentModel = new EnrollmentModel($conn);
        $this->courseService = new CourseService($conn);

        // LessonController might also be deprecated - load conditionally
        if (file_exists(__DIR__ . '/LessonController.php')) {
            require_once __DIR__ . '/LessonController.php';
            $this->lessonController = new LessonController($conn);
        }
    }

    /**
     * @deprecated Use CourseService::getAllCourses() or Member\CourseController::index()
     */
    public function getAllCourses() {
        $userId = $_SESSION['id'] ?? null;
        return $this->courseService->getAllCourses($userId);
    }

    /**
     * @deprecated Use CourseService::getCourseDetails() or Member\CourseController::show()
     */
    public function getCourseDetails($courseId) {
        return $this->courseModel->getCourseDetails($courseId);
    }

    /**
     * @deprecated Use CourseModel::getCourseSections()
     */
    public function getCourseSections($courseId) {
        return $this->courseModel->getCourseSections($courseId);
    }

    /**
     * @deprecated Use EnrollmentModel::isUserEnrolled()
     */
    public function isUserEnrolled($userId, $courseId) {
        if (!$userId || !$courseId) {
            return false;
        }
        return $this->enrollmentModel->isUserEnrolled($userId, $courseId);
    }

    /**
     * @deprecated Use CourseService::enrollUser() or Member\CourseController::enroll()
     */
    public function enrollUser($userId, $courseId) {
        // Validate CSRF token
        require_once __DIR__ . '/../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in deprecated CourseController::enrollUser - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }

        if (!$userId || !$courseId) {
            return false;
        }

        try {
            return $this->courseService->enrollUser($userId, $courseId);
        } catch (Exception $e) {
            error_log("Enrollment failed in deprecated CourseController: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @deprecated Use EnrollmentModel::getUserProgress()
     */
    public function getUserProgress($userId, $courseId) {
        if (!$userId || !$courseId) {
            return [
                'percent' => 0,
                'completed' => false,
                'last_accessed' => null,
                'started' => false
            ];
        }
        return $this->enrollmentModel->getUserProgress($userId, $courseId);
    }

    /**
     * @deprecated Use EnrollmentModel::isLessonCompleted()
     */
    public function isLessonCompleted($userId, $lessonId) {
        return $this->enrollmentModel->isLessonCompleted($userId, $lessonId);
    }

    /**
     * @deprecated Calculate in view or service layer
     */
    public function calculateTotalDuration($courseId) {
        $sections = $this->getCourseSections($courseId);
        $totalMinutes = 0;

        foreach ($sections as $section) {
            if (isset($section['lessons']) && is_array($section['lessons'])) {
                foreach ($section['lessons'] as $lesson) {
                    $totalMinutes += isset($lesson['duration_minutes']) ? intval($lesson['duration_minutes']) : 0;
                }
            }
        }

        return $totalMinutes;
    }

    /**
     * @deprecated Calculate in view or service layer
     */
    public function countLessons($courseId, $userId = null) {
        $sections = $this->getCourseSections($courseId);
        $total = 0;
        $completed = 0;

        foreach ($sections as $section) {
            if (isset($section['lessons']) && is_array($section['lessons'])) {
                foreach ($section['lessons'] as $lesson) {
                    $total++;
                    if ($userId && isset($lesson['completed']) && $lesson['completed']) {
                        $completed++;
                    }
                }
            }
        }

        return [
            'total' => $total,
            'completed' => $completed
        ];
    }

    /**
     * @deprecated Use CourseService::getFeaturedCourses()
     */
    public function getFeaturedCourses() {
        try {
            return $this->courseService->getFeaturedCourses();
        } catch (Exception $e) {
            // Fallback to direct query
            $sql = "SELECT * FROM courses WHERE is_featured = 1 LIMIT 4";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();

            $featuredCourses = [];
            while ($row = $result->fetch_assoc()) {
                $featuredCourses[] = $row;
            }

            return $featuredCourses;
        }
    }

    /**
     * @deprecated Use CourseService::getRecommendedCourses()
     */
    public function getRecommendedCourses($userId) {
        try {
            return $this->courseService->getRecommendedCourses($userId, 4);
        } catch (Exception $e) {
            // Fallback to direct query
            $sql = "SELECT * FROM courses WHERE status = 'active' ORDER BY created_at DESC LIMIT 4";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();

            $recommendedCourses = [];
            while ($row = $result->fetch_assoc()) {
                $recommendedCourses[] = $row;
            }

            return $recommendedCourses;
        }
    }

    /**
     * @deprecated Use CourseService::getUserEnrolledCourses()
     */
    public function getUserEnrollments($userId) {
        try {
            return $this->courseService->getUserEnrolledCourses($userId);
        } catch (Exception $e) {
            // Fallback to direct query
            $sql = "SELECT c.*, e.progress, e.last_accessed
                    FROM courses c
                    JOIN user_enrollments e ON c.id = e.course_id
                    WHERE e.user_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $enrollments = [];
            while ($row = $result->fetch_assoc()) {
                $enrollments[] = $row;
            }

            return $enrollments;
        }
    }

    /**
     * @deprecated Use view helpers or service layer
     */
    public function formatCourseType($type) {
        return ucwords(str_replace('_', ' ', $type));
    }

    /**
     * @deprecated Use view helpers
     */
    public function getDifficultyClass($level) {
        $classes = [
            'Beginner' => 'badge-success',
            'Intermediate' => 'badge-warning',
            'Advanced' => 'badge-danger'
        ];
        return $classes[$level] ?? 'badge-primary';
    }

    /**
     * Get comprehensive course data for view
     * This is a complex method that aggregates data from multiple sources
     *
     * @deprecated Use Member\CourseController::show() which does this properly
     */
    public function getCourseDataForView($courseId, $userId) {
        $courseDetails = $this->courseModel->getCourseDetails($courseId);
        if (!$courseDetails) {
            return null;
        }

        $sectionsFromDB = $this->courseModel->getCourseSections($courseId);
        $populatedSections = [];
        $totalDuration = 0;
        $totalLessons = 0;
        $completedLessons = 0;

        if (is_array($sectionsFromDB) && $this->lessonController) {
            foreach ($sectionsFromDB as $section) {
                $sectionLessons = $this->lessonController->getSectionLessons($section['id']);
                $populatedSectionLessons = [];
                if (is_array($sectionLessons)) {
                    foreach ($sectionLessons as $lesson) {
                        $lessonId = isset($lesson['id']) ? $lesson['id'] : null;
                        if ($lessonId) {
                            $lesson['completed'] = $this->isLessonCompleted($userId, $lessonId);
                            if ($lesson['completed']) {
                                $completedLessons++;
                            }
                        } else {
                            $lesson['completed'] = false;
                        }
                        $totalDuration += isset($lesson['duration_minutes']) ? intval($lesson['duration_minutes']) : 0;
                        $totalLessons++;
                        $populatedSectionLessons[] = $lesson;
                    }
                }
                $section['lessons'] = $populatedSectionLessons;
                $populatedSections[] = $section;
            }
        }

        $isEnrolled = $this->isUserEnrolled($userId, $courseId);
        $userProgressData = $this->getUserProgress($userId, $courseId);

        return [
            'course' => $courseDetails,
            'sections' => $populatedSections,
            'isEnrolled' => $isEnrolled,
            'progress' => $userProgressData,
            'totalDuration' => $totalDuration,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
        ];
    }
}

// Log deprecation warning
if (!defined('COURSE_CONTROLLER_DEPRECATION_LOGGED')) {
    error_log("DEPRECATION WARNING: Legacy CourseController is being used. Please migrate to Member\\CourseController or Admin\\CourseController. Called from: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    define('COURSE_CONTROLLER_DEPRECATION_LOGGED', true);
}
?>
