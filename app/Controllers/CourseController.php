<?php
require_once __DIR__ . '/../Models/CourseModel.php';
require_once __DIR__ . '/../Models/EnrollmentModel.php';
require_once __DIR__ . '/LessonController.php'; // Added LessonController

class CourseController {
    private $conn;
    private $courseModel;
    private $enrollmentModel;
    private $lessonController; // Added lessonController property
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->courseModel = new CourseModel($conn);
        $this->enrollmentModel = new EnrollmentModel($conn);
        $this->lessonController = new LessonController($conn); // Instantiate LessonController
    }
    
    public function getAllCourses() {
        return $this->courseModel->getAllCourses();
    }
    
    public function getCourseDetails($courseId) {
        return $this->courseModel->getCourseDetails($courseId);
    }
    
    public function getCourseSections($courseId) {
        return $this->courseModel->getCourseSections($courseId);
    }
    
    /**
     * Check if a user is enrolled in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if enrolled, false otherwise
     */
    public function isUserEnrolled($userId, $courseId) {
        if (!$userId || !$courseId) {
            return false;
        }
        return $this->enrollmentModel->isUserEnrolled($userId, $courseId);
    }
    
    /**
     * Enroll a user in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function enrollUser($userId, $courseId) {
        if (!$userId || !$courseId) {
            return false;
        }
        return $this->enrollmentModel->enrollUser($userId, $courseId);
    }
    
    /**
     * Get user progress for a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Progress data including percent completion, completion status
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
     * Check if a lesson is completed by a user
     *
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool True if completed, false otherwise
     */
    public function isLessonCompleted($userId, $lessonId) {
        return $this->enrollmentModel->isLessonCompleted($userId, $lessonId);
    }
    
    // Calculate total duration for a course
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
    
    // Count total lessons and completed lessons for a course
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

    // Add this method to get featured courses
    public function getFeaturedCourses() {
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

    // Add this method to get recommended courses
    public function getRecommendedCourses($userId) {
        // You can implement your recommendation logic here
        // For now, just return recent courses
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

    // Add this method to get user enrollments
    public function getUserEnrollments($userId) {
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

    // Add helper method to format course type
    public function formatCourseType($type) {
        return ucwords(str_replace('_', ' ', $type));
    }

    // Add helper method to get difficulty class
    public function getDifficultyClass($level) {
        $classes = [
            'Beginner' => 'badge-success',
            'Intermediate' => 'badge-warning',
            'Advanced' => 'badge-danger'
        ];
        return $classes[$level] ?? 'badge-primary';
    }

    public function getCourseDataForView($courseId, $userId) {
        $courseDetails = $this->courseModel->getCourseDetails($courseId);
        if (!$courseDetails) {
            // Consider logging this error or handling it more gracefully
            return null; 
        }

        $sectionsFromDB = $this->courseModel->getCourseSections($courseId);
        $populatedSections = [];
        $totalDuration = 0;
        $totalLessons = 0;
        $completedLessons = 0;

        if (is_array($sectionsFromDB)) {
            foreach ($sectionsFromDB as $section) {
                $sectionLessons = $this->lessonController->getSectionLessons($section['id']);
                $populatedSectionLessons = [];
                if (is_array($sectionLessons)) {
                    foreach ($sectionLessons as $lesson) {
                        // Ensure lesson ID exists before checking completion
                        $lessonId = isset($lesson['id']) ? $lesson['id'] : null;
                        if ($lessonId) {
                            $lesson['completed'] = $this->isLessonCompleted($userId, $lessonId);
                            if ($lesson['completed']) {
                                $completedLessons++;
                            }
                        } else {
                            $lesson['completed'] = false; // Default if no ID
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
        // getUserProgress returns an array e.g. ['percent' => X, 'completed' => Y, ...]
        $userProgressData = $this->getUserProgress($userId, $courseId); 

        return [
            'course' => $courseDetails,
            'sections' => $populatedSections,
            'isEnrolled' => $isEnrolled,
            'progress' => $userProgressData, // Pass the whole array
            'totalDuration' => $totalDuration,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
        ];
    }
}
?>
