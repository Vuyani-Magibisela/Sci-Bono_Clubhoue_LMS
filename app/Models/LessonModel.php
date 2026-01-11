<?php
class LessonModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getLessonDetails($lessonId) {
        $sql = "SELECT l.*, s.title as section_title, s.course_id, c.title as course_title
                FROM course_lessons l
                JOIN course_sections s ON l.section_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE l.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    public function getSectionLessons($sectionId) {
        $lessons = [];
        $sql = "SELECT * FROM course_lessons 
                WHERE section_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
        }
        
        return $lessons;
    }
    
    /**
     * Get lesson by ID (alias for getLessonDetails)
     *
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
    public function getLessonById($lessonId) {
        return $this->getLessonDetails($lessonId);
    }

    /**
     * Get course sections with lessons
     * Includes user progress if authenticated and enrolled
     *
     * @param int $courseId Course ID
     * @param int|null $userId User ID (optional)
     * @param bool $isEnrolled Whether user is enrolled
     * @return array Array of sections with lessons
     */
    public function getCourseSectionsWithLessons($courseId, $userId = null, $isEnrolled = false) {
        $sections = [];

        // Get all sections for the course
        $sectionSql = "SELECT * FROM course_sections
                       WHERE course_id = ?
                       ORDER BY order_number ASC";

        $stmt = $this->conn->prepare($sectionSql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $sectionResult = $stmt->get_result();

        while ($section = $sectionResult->fetch_assoc()) {
            // Get lessons for this section
            $lessonSql = "SELECT l.*";

            if ($userId && $isEnrolled) {
                $lessonSql .= ", lp.status as progress_status,
                               lp.progress_percentage,
                               lp.completed_at,
                               lp.time_spent";
            }

            $lessonSql .= " FROM course_lessons l";

            if ($userId && $isEnrolled) {
                $lessonSql .= " LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?";
            }

            $lessonSql .= " WHERE l.section_id = ? ORDER BY l.order_number ASC";

            $lessonStmt = $this->conn->prepare($lessonSql);

            if ($userId && $isEnrolled) {
                $lessonStmt->bind_param("ii", $userId, $section['id']);
            } else {
                $lessonStmt->bind_param("i", $section['id']);
            }

            $lessonStmt->execute();
            $lessonResult = $lessonStmt->get_result();

            $lessons = [];
            while ($lesson = $lessonResult->fetch_assoc()) {
                $lessons[] = $lesson;
            }

            $section['lessons'] = $lessons;
            $section['lesson_count'] = count($lessons);
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Get user's progress for a specific lesson
     *
     * @param int $lessonId Lesson ID
     * @param int $userId User ID
     * @return array|null Progress data or null if not found
     */
    public function getUserLessonProgress($lessonId, $userId) {
        $sql = "SELECT * FROM lesson_progress
                WHERE lesson_id = ? AND user_id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $lessonId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Track lesson view
     * Creates or updates lesson_progress record with 'in_progress' status
     *
     * @param int $lessonId Lesson ID
     * @param int $userId User ID
     * @param int $enrollmentId Enrollment ID
     * @return bool Success status
     */
    public function trackLessonView($lessonId, $userId, $enrollmentId) {
        // Check if progress record exists
        $existing = $this->getUserLessonProgress($lessonId, $userId);

        if ($existing) {
            // Update last_accessed_at and set status to in_progress if not completed
            if ($existing['status'] !== 'completed') {
                $sql = "UPDATE lesson_progress
                        SET status = 'in_progress',
                            last_accessed_at = NOW()
                        WHERE id = ?";

                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $existing['id']);
                return $stmt->execute();
            }

            // Just update last_accessed_at if already completed
            $sql = "UPDATE lesson_progress
                    SET last_accessed_at = NOW()
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $existing['id']);
            return $stmt->execute();
        }

        // Create new progress record
        $sql = "INSERT INTO lesson_progress
                (user_id, lesson_id, enrollment_id, status, progress_percentage, started_at, last_accessed_at)
                VALUES (?, ?, ?, 'in_progress', 0, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $userId, $lessonId, $enrollmentId);
        return $stmt->execute();
    }

    /**
     * Get next lesson in sequence
     *
     * @param int $currentLessonId Current lesson ID
     * @return array|null Next lesson details or null if none
     */
    public function getNextLesson($currentLessonId) {
        // Get current lesson details
        $current = $this->getLessonById($currentLessonId);

        if (!$current) {
            return null;
        }

        // Try to find next lesson in same section
        $sql = "SELECT l.*, s.title as section_title
                FROM course_lessons l
                JOIN course_sections s ON l.section_id = s.id
                WHERE l.section_id = ? AND l.order_number > ?
                ORDER BY l.order_number ASC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $current['section_id'], $current['order_number']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // No more lessons in current section, try next section
        $sectionSql = "SELECT * FROM course_sections
                       WHERE course_id = ? AND order_number > ?
                       ORDER BY order_number ASC
                       LIMIT 1";

        $sectionStmt = $this->conn->prepare($sectionSql);
        $sectionStmt->bind_param("ii", $current['course_id'], $current['section_id']);
        $sectionStmt->execute();
        $sectionResult = $sectionStmt->get_result();

        if ($sectionResult && $sectionResult->num_rows > 0) {
            $nextSection = $sectionResult->fetch_assoc();

            // Get first lesson of next section
            $firstLessonSql = "SELECT l.*, s.title as section_title
                               FROM course_lessons l
                               JOIN course_sections s ON l.section_id = s.id
                               WHERE l.section_id = ?
                               ORDER BY l.order_number ASC
                               LIMIT 1";

            $firstLessonStmt = $this->conn->prepare($firstLessonSql);
            $firstLessonStmt->bind_param("i", $nextSection['id']);
            $firstLessonStmt->execute();
            $firstLessonResult = $firstLessonStmt->get_result();

            if ($firstLessonResult && $firstLessonResult->num_rows > 0) {
                return $firstLessonResult->fetch_assoc();
            }
        }

        return null;
    }

    /**
     * Get previous lesson in sequence
     *
     * @param int $currentLessonId Current lesson ID
     * @return array|null Previous lesson details or null if none
     */
    public function getPreviousLesson($currentLessonId) {
        // Get current lesson details
        $current = $this->getLessonById($currentLessonId);

        if (!$current) {
            return null;
        }

        // Try to find previous lesson in same section
        $sql = "SELECT l.*, s.title as section_title
                FROM course_lessons l
                JOIN course_sections s ON l.section_id = s.id
                WHERE l.section_id = ? AND l.order_number < ?
                ORDER BY l.order_number DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $current['section_id'], $current['order_number']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // No previous lessons in current section, try previous section
        $sectionSql = "SELECT * FROM course_sections
                       WHERE course_id = ? AND order_number < ?
                       ORDER BY order_number DESC
                       LIMIT 1";

        $sectionStmt = $this->conn->prepare($sectionSql);
        $sectionStmt->bind_param("ii", $current['course_id'], $current['section_id']);
        $sectionStmt->execute();
        $sectionResult = $sectionStmt->get_result();

        if ($sectionResult && $sectionResult->num_rows > 0) {
            $prevSection = $sectionResult->fetch_assoc();

            // Get last lesson of previous section
            $lastLessonSql = "SELECT l.*, s.title as section_title
                              FROM course_lessons l
                              JOIN course_sections s ON l.section_id = s.id
                              WHERE l.section_id = ?
                              ORDER BY l.order_number DESC
                              LIMIT 1";

            $lastLessonStmt = $this->conn->prepare($lastLessonSql);
            $lastLessonStmt->bind_param("i", $prevSection['id']);
            $lastLessonStmt->execute();
            $lastLessonResult = $lastLessonStmt->get_result();

            if ($lastLessonResult && $lastLessonResult->num_rows > 0) {
                return $lastLessonResult->fetch_assoc();
            }
        }

        return null;
    }

    /**
     * Mark lesson as complete
     *
     * @param int $lessonId Lesson ID
     * @param int $userId User ID
     * @param int $enrollmentId Enrollment ID
     * @param int $timeSpent Time spent in minutes
     * @param float|null $quizScore Quiz score (0-100)
     * @param string|null $notes User notes
     * @return bool Success status
     */
    public function markLessonComplete($lessonId, $userId, $enrollmentId, $timeSpent = 0, $quizScore = null, $notes = null) {
        // Check if progress record exists
        $existing = $this->getUserLessonProgress($lessonId, $userId);

        if ($existing) {
            // Update existing record
            $sql = "UPDATE lesson_progress
                    SET status = 'completed',
                        progress_percentage = 100.00,
                        time_spent = time_spent + ?,
                        completed_at = NOW(),
                        last_accessed_at = NOW()";

            $params = [$timeSpent];
            $types = "i";

            if ($quizScore !== null) {
                $sql .= ", quiz_score = ?";
                $params[] = $quizScore;
                $types .= "d";
            }

            if ($notes !== null) {
                $sql .= ", notes = ?";
                $params[] = $notes;
                $types .= "s";
            }

            $sql .= " WHERE id = ?";
            $params[] = $existing['id'];
            $types .= "i";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        }

        // Create new completed record
        $sql = "INSERT INTO lesson_progress
                (user_id, lesson_id, enrollment_id, status, progress_percentage, time_spent, quiz_score, notes, started_at, completed_at, last_accessed_at)
                VALUES (?, ?, ?, 'completed', 100.00, ?, ?, ?, NOW(), NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiids", $userId, $lessonId, $enrollmentId, $timeSpent, $quizScore, $notes);
        return $stmt->execute();
    }

    /**
     * Update enrollment progress percentage and lesson counts
     *
     * @param int $enrollmentId Enrollment ID
     * @param float $progressPercentage Progress percentage (0-100)
     * @param int $completedLessons Number of completed lessons
     * @param int $totalLessons Total number of lessons
     * @return bool Success status
     */
    public function updateEnrollmentProgress($enrollmentId, $progressPercentage, $completedLessons, $totalLessons) {
        $sql = "UPDATE enrollments
                SET progress = ?,
                    lessons_completed = ?,
                    total_lessons = ?,
                    last_accessed_at = NOW()
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("diii", $progressPercentage, $completedLessons, $totalLessons, $enrollmentId);
        return $stmt->execute();
    }
}
?>