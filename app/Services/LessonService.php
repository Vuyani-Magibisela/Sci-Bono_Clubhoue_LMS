<?php
/**
 * Lesson Service - Business logic for lesson management and progress tracking
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/LessonModel.php';
require_once __DIR__ . '/../Models/EnrollmentModel.php';
require_once __DIR__ . '/../Models/CourseModel.php';

class LessonService extends BaseService {
    private $lessonModel;
    private $enrollmentModel;
    private $courseModel;

    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->lessonModel = new LessonModel($this->conn);
        $this->enrollmentModel = new EnrollmentModel($this->conn);
        $this->courseModel = new CourseModel($this->conn);
    }

    /**
     * Get lesson details with user progress
     *
     * @param int $lessonId Lesson ID
     * @param int|null $userId User ID for progress tracking
     * @return array|null Lesson details or null if not found
     */
    public function getLessonDetails($lessonId, $userId = null) {
        try {
            $this->logAction('get_lesson_details', ['lesson_id' => $lessonId, 'user_id' => $userId]);

            $lesson = $this->lessonModel->getLessonDetails($lessonId);

            if (!$lesson) {
                return null;
            }

            // Add progress info if user provided
            if ($userId) {
                $lesson['is_completed'] = $this->enrollmentModel->isLessonCompleted($userId, $lessonId);
                $lesson['progress'] = $this->getLessonProgress($userId, $lessonId);
            }

            return $lesson;

        } catch (Exception $e) {
            $this->handleError("Failed to get lesson details: " . $e->getMessage(), [
                'lesson_id' => $lessonId,
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Get lessons for a section with user progress
     *
     * @param int $sectionId Section ID
     * @param int|null $userId User ID for progress tracking
     * @return array List of lessons
     */
    public function getSectionLessons($sectionId, $userId = null) {
        try {
            $this->logAction('get_section_lessons', ['section_id' => $sectionId, 'user_id' => $userId]);

            $lessons = $this->lessonModel->getSectionLessons($sectionId);

            // PHASE 3 WEEK 9 - PERFORMANCE: Batch query eliminates N+1 problem
            // OLD: Multiple queries for each lesson's completion status
            // NEW: Single batch query for all lessons
            if ($userId && !empty($lessons)) {
                $lessonIds = array_column($lessons, 'id');
                $completionData = $this->enrollmentModel->getLessonsCompletionBatch($userId, $lessonIds);

                foreach ($lessons as &$lesson) {
                    $lesson['is_completed'] = $completionData[$lesson['id']] ?? false;
                }
            }

            return $lessons;

        } catch (Exception $e) {
            $this->logger->error("Failed to get section lessons", ['section_id' => $sectionId]);
            return [];
        }
    }

    /**
     * Mark lesson as completed for user
     *
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function markLessonCompleted($userId, $lessonId) {
        try {
            $this->logAction('mark_lesson_completed_attempt', ['user_id' => $userId, 'lesson_id' => $lessonId]);

            // Get lesson details
            $lesson = $this->lessonModel->getLessonDetails($lessonId);
            if (!$lesson) {
                throw new Exception("Lesson not found");
            }

            // Check if user is enrolled in the course
            $courseId = $lesson['course_id'];
            if (!$this->enrollmentModel->isUserEnrolled($userId, $courseId)) {
                throw new Exception("User is not enrolled in this course");
            }

            // Check if already completed
            if ($this->enrollmentModel->isLessonCompleted($userId, $lessonId)) {
                return true; // Already completed
            }

            // Mark as completed
            $sql = "INSERT INTO lesson_progress (user_id, lesson_id, completed, completed_at)
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $lessonId);
            $result = $stmt->execute();

            if ($result) {
                // Update course progress
                $this->updateCourseProgress($userId, $courseId);

                $this->logAction('lesson_completed_success', [
                    'user_id' => $userId,
                    'lesson_id' => $lessonId,
                    'course_id' => $courseId
                ]);

                return true;
            }

            throw new Exception("Failed to mark lesson as completed");

        } catch (Exception $e) {
            $this->handleError("Failed to mark lesson completed: " . $e->getMessage(), [
                'user_id' => $userId,
                'lesson_id' => $lessonId
            ]);
        }
    }

    /**
     * Mark lesson as in progress
     *
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function markLessonInProgress($userId, $lessonId) {
        try {
            $this->logAction('mark_lesson_in_progress', ['user_id' => $userId, 'lesson_id' => $lessonId]);

            // Get lesson details
            $lesson = $this->lessonModel->getLessonDetails($lessonId);
            if (!$lesson) {
                throw new Exception("Lesson not found");
            }

            // Check enrollment
            $courseId = $lesson['course_id'];
            if (!$this->enrollmentModel->isUserEnrolled($userId, $courseId)) {
                throw new Exception("User is not enrolled in this course");
            }

            // Mark as in progress
            $sql = "INSERT INTO lesson_progress (user_id, lesson_id, started_at, last_accessed)
                    VALUES (?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE last_accessed = NOW()";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $lessonId);
            $result = $stmt->execute();

            // Update enrollment last_accessed
            $updateSql = "UPDATE user_enrollments SET last_accessed = NOW()
                          WHERE user_id = ? AND course_id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $userId, $courseId);
            $updateStmt->execute();

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Failed to mark lesson in progress", [
                'user_id' => $userId,
                'lesson_id' => $lessonId
            ]);
            return false;
        }
    }

    /**
     * Get lesson progress for user
     *
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return array Progress data
     */
    public function getLessonProgress($userId, $lessonId) {
        try {
            $sql = "SELECT completed, started_at, completed_at, last_accessed
                    FROM lesson_progress
                    WHERE user_id = ? AND lesson_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $lessonId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            return [
                'completed' => false,
                'started_at' => null,
                'completed_at' => null,
                'last_accessed' => null
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get lesson progress", [
                'user_id' => $userId,
                'lesson_id' => $lessonId
            ]);
            return [
                'completed' => false,
                'started_at' => null,
                'completed_at' => null,
                'last_accessed' => null
            ];
        }
    }

    /**
     * Update course progress based on completed lessons
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool Success status
     */
    private function updateCourseProgress($userId, $courseId) {
        try {
            // Get total lessons in course
            $totalSql = "SELECT COUNT(DISTINCT l.id) as total_lessons
                         FROM course_lessons l
                         JOIN course_sections s ON l.section_id = s.id
                         WHERE s.course_id = ?";

            $totalStmt = $this->conn->prepare($totalSql);
            $totalStmt->bind_param("i", $courseId);
            $totalStmt->execute();
            $totalResult = $totalStmt->get_result();
            $totalRow = $totalResult->fetch_assoc();
            $totalLessons = $totalRow['total_lessons'];

            if ($totalLessons == 0) {
                return false; // No lessons to track
            }

            // Get completed lessons count
            $completedSql = "SELECT COUNT(DISTINCT lp.lesson_id) as completed_lessons
                             FROM lesson_progress lp
                             JOIN course_lessons l ON lp.lesson_id = l.id
                             JOIN course_sections s ON l.section_id = s.id
                             WHERE s.course_id = ? AND lp.user_id = ? AND lp.completed = 1";

            $completedStmt = $this->conn->prepare($completedSql);
            $completedStmt->bind_param("ii", $courseId, $userId);
            $completedStmt->execute();
            $completedResult = $completedStmt->get_result();
            $completedRow = $completedResult->fetch_assoc();
            $completedLessons = $completedRow['completed_lessons'];

            // Calculate progress percentage
            $progress = round(($completedLessons / $totalLessons) * 100);

            // Update enrollment
            $updateSql = "UPDATE user_enrollments
                          SET progress = ?, completed = ?, last_accessed = NOW()
                          WHERE user_id = ? AND course_id = ?";

            $completed = $progress >= 100 ? 1 : 0;
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("iiii", $progress, $completed, $userId, $courseId);

            return $updateStmt->execute();

        } catch (Exception $e) {
            $this->logger->error("Failed to update course progress", [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
            return false;
        }
    }

    /**
     * Get next lesson for user in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array|null Next lesson or null if all completed
     */
    public function getNextLesson($userId, $courseId) {
        try {
            // Get all sections and lessons in order
            $sections = $this->courseModel->getCourseSections($courseId);

            foreach ($sections as $section) {
                $lessons = $this->lessonModel->getSectionLessons($section['id']);

                foreach ($lessons as $lesson) {
                    // Check if not completed
                    if (!$this->enrollmentModel->isLessonCompleted($userId, $lesson['id'])) {
                        $lesson['section_title'] = $section['title'];
                        $lesson['course_id'] = $courseId;
                        return $lesson;
                    }
                }
            }

            // All lessons completed
            return null;

        } catch (Exception $e) {
            $this->logger->error("Failed to get next lesson", [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
            return null;
        }
    }

    /**
     * Get course completion summary for user
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Completion summary
     */
    public function getCourseCompletionSummary($userId, $courseId) {
        try {
            // Get total lessons
            $totalSql = "SELECT COUNT(DISTINCT l.id) as total
                         FROM course_lessons l
                         JOIN course_sections s ON l.section_id = s.id
                         WHERE s.course_id = ?";

            $totalStmt = $this->conn->prepare($totalSql);
            $totalStmt->bind_param("i", $courseId);
            $totalStmt->execute();
            $totalResult = $totalStmt->get_result();
            $totalRow = $totalResult->fetch_assoc();

            // Get completed lessons
            $completedSql = "SELECT COUNT(DISTINCT lp.lesson_id) as completed
                             FROM lesson_progress lp
                             JOIN course_lessons l ON lp.lesson_id = l.id
                             JOIN course_sections s ON l.section_id = s.id
                             WHERE s.course_id = ? AND lp.user_id = ? AND lp.completed = 1";

            $completedStmt = $this->conn->prepare($completedSql);
            $completedStmt->bind_param("ii", $courseId, $userId);
            $completedStmt->execute();
            $completedResult = $completedStmt->get_result();
            $completedRow = $completedResult->fetch_assoc();

            $total = $totalRow['total'];
            $completed = $completedRow['completed'];
            $remaining = $total - $completed;
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

            return [
                'total_lessons' => $total,
                'completed_lessons' => $completed,
                'remaining_lessons' => $remaining,
                'progress_percentage' => $progress,
                'is_completed' => $progress >= 100
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get completion summary", [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
            return [
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'remaining_lessons' => 0,
                'progress_percentage' => 0,
                'is_completed' => false
            ];
        }
    }

    /**
     * Reset lesson progress (for retaking)
     *
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function resetLessonProgress($userId, $lessonId) {
        try {
            $this->logAction('reset_lesson_progress', ['user_id' => $userId, 'lesson_id' => $lessonId]);

            $sql = "DELETE FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $lessonId);
            $result = $stmt->execute();

            if ($result) {
                // Update course progress
                $lesson = $this->lessonModel->getLessonDetails($lessonId);
                if ($lesson) {
                    $this->updateCourseProgress($userId, $lesson['course_id']);
                }

                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error("Failed to reset lesson progress", [
                'user_id' => $userId,
                'lesson_id' => $lessonId
            ]);
            return false;
        }
    }

    /**
     * Get recently accessed lessons for user
     *
     * @param int $userId User ID
     * @param int $limit Number of lessons to return
     * @return array List of recent lessons
     */
    public function getRecentLessons($userId, $limit = 5) {
        try {
            $sql = "SELECT l.*, lp.last_accessed, lp.completed,
                           s.title as section_title, c.title as course_title, c.id as course_id
                    FROM lesson_progress lp
                    JOIN course_lessons l ON lp.lesson_id = l.id
                    JOIN course_sections s ON l.section_id = s.id
                    JOIN courses c ON s.course_id = c.id
                    WHERE lp.user_id = ?
                    ORDER BY lp.last_accessed DESC
                    LIMIT ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $lessons = [];
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }

            return $lessons;

        } catch (Exception $e) {
            $this->logger->error("Failed to get recent lessons", ['user_id' => $userId]);
            return [];
        }
    }
}
