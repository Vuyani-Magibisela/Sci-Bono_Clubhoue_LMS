<?php
class EnrollmentModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getUserEnrollments($userId) {
        $enrollments = [];
        $sql = "SELECT e.id, e.course_id, e.progress, e.last_accessed,
                    c.title, c.type, c.description, c.difficulty_level, c.image_path
                FROM user_enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ?
                ORDER BY e.last_accessed DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $enrollments[] = $row;
            }
        }

        return $enrollments;
    }

    /**
     * Get the count of user enrollments
     *
     * @param int $userId User ID
     * @return int Number of enrollments
     */
    public function getUserEnrollmentCount($userId) {
        if (!$userId) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM user_enrollments WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['count'];
        }

        return 0;
    }
    
    /**
     * Check if a user is enrolled in a specific course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if enrolled, false otherwise
     */
    public function isUserEnrolled($userId, $courseId) {
        // Input validation
        if (!$userId || !$courseId) {
            return false;
        }
        
        $sql = "SELECT id FROM user_enrollments WHERE user_id = ? AND course_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            // Handle database error
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $courseId);
        $executed = $stmt->execute();
        
        if (!$executed) {
            return false;
        }
        
        $result = $stmt->get_result();
        return ($result && $result->num_rows > 0);
    }
    
    public function enrollUser($userId, $courseId) {
        if (!$this->isUserEnrolled($userId, $courseId)) {
            $sql = "INSERT INTO user_enrollments (user_id, course_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $courseId);
            $success = $stmt->execute();
            
            if ($success) {
                // Update enrollment count
                $updateSql = "UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("i", $courseId);
                $updateStmt->execute();
                
                return true;
            }
            return false;
        }
        
        return false;
    }
    
    /**
     * Get user progress for a course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Progress data including percent completion, completion status, etc.
     */
    public function getUserProgress($userId, $courseId) {
        $progressData = [
            'percent' => 0,
            'completed' => false,
            'last_accessed' => null,
            'started' => false
        ];
        
        if (!$userId || !$courseId) {
            return $progressData;
        }
        
        $sql = "SELECT progress, completed, last_accessed 
                FROM user_enrollments 
                WHERE user_id = ? AND course_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return $progressData;
        }
        
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $progressData = [
                'percent' => (int)$row['progress'],
                'completed' => (bool)($row['completed'] ?? false),
                'last_accessed' => $row['last_accessed'],
                'started' => true
            ];
        }
        
        return $progressData;
    }
    
    /**
     * Check if a specific lesson is completed by a user
     * 
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool True if completed, false otherwise
     */
    public function isLessonCompleted($userId, $lessonId) {
        if (!$userId || !$lessonId) {
            return false;
        }
        
        $sql = "SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $userId, $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)$row['completed'];
        }

        return false;
    }

    // ========== PHASE 3 WEEK 9 - PERFORMANCE OPTIMIZATIONS (N+1 Query Fixes) ==========

    /**
     * Get enrollment status and progress for multiple courses (batch operation)
     * Eliminates N+1 query problem in CourseService.getAllCourses()
     *
     * @param int $userId User ID
     * @param array $courseIds Array of course IDs
     * @return array [course_id => ['is_enrolled' => bool, 'progress' => int]]
     */
    public function getUserEnrollmentsBatch($userId, $courseIds) {
        if (empty($courseIds) || !$userId) {
            return [];
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        $sql = "SELECT course_id, id, progress_percentage
                FROM user_enrollments
                WHERE user_id = ? AND course_id IN ($placeholders)";

        // Prepare parameters: user_id first, then all course_ids
        $params = array_merge([$userId], $courseIds);
        $types = 'i' . str_repeat('i', count($courseIds)); // 'i' for user_id + 'i' for each course_id

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $enrollments = [];
        while ($row = $result->fetch_assoc()) {
            $enrollments[$row['course_id']] = [
                'is_enrolled' => true,
                'progress' => $row['progress_percentage'] ?? 0
            ];
        }

        return $enrollments;
    }

    /**
     * Get user enrollments with course details in single query (JOIN optimization)
     * Eliminates N+1 query problem in DashboardService.getUserLearningProgress()
     *
     * @param int $userId User ID
     * @return array Enrollments with course data
     */
    public function getUserEnrollmentsWithCourses($userId) {
        if (!$userId) {
            return [];
        }

        $sql = "SELECT e.*, c.title, c.thumbnail, c.difficulty_level
                FROM user_enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ?
                ORDER BY e.last_accessed_at DESC";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get completion status for multiple lessons (batch operation)
     * Eliminates N+1 query problem in LessonService.getSectionLessons()
     *
     * @param int $userId User ID
     * @param array $lessonIds Array of lesson IDs
     * @return array [lesson_id => bool] Completion status
     */
    public function getLessonsCompletionBatch($userId, $lessonIds) {
        if (empty($lessonIds) || !$userId) {
            return [];
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));

        $sql = "SELECT lesson_id, completed
                FROM lesson_progress
                WHERE user_id = ? AND lesson_id IN ($placeholders)";

        // Prepare parameters: user_id first, then all lesson_ids
        $params = array_merge([$userId], $lessonIds);
        $types = 'i' . str_repeat('i', count($lessonIds));

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $completion = [];
        while ($row = $result->fetch_assoc()) {
            $completion[$row['lesson_id']] = (bool)$row['completed'];
        }

        return $completion;
    }
}
?>