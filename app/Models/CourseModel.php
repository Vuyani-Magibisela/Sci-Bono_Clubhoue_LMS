<?php
class CourseModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAllCourses() {
        $courses = [];
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname, 
                COUNT(DISTINCT s.id) as section_count, 
                COUNT(DISTINCT l.id) as lesson_count,
                COUNT(DISTINCT e.id) as enrollment_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                LEFT JOIN course_sections s ON c.id = s.course_id
                LEFT JOIN course_lessons l ON s.id = l.section_id
                LEFT JOIN user_enrollments e ON c.id = e.course_id
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        
        return $courses;
    }
    
    public function getCourseDetails($courseId) {
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname 
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    public function getCourseSections($courseId) {
        $sections = [];
        $sql = "SELECT s.*, COUNT(l.id) as lesson_count 
                FROM course_sections s
                LEFT JOIN course_lessons l ON s.id = l.section_id
                WHERE s.course_id = ?
                GROUP BY s.id
                ORDER BY s.order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        }
        
        return $sections;
    }
    
    /**
     * Get course by ID (alias for getCourseDetails)
     *
     * @param int $courseId
     * @return array|null
     */
    public function getCourseById($courseId) {
        return $this->getCourseDetails($courseId);
    }

    /**
     * Get published courses with filtering and pagination
     *
     * @param array $filters Filters to apply (category, level, featured, etc.)
     * @param string|null $search Search term
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array List of courses
     */
    public function getPublishedCourses($filters = [], $search = null, $limit = 20, $offset = 0) {
        $courses = [];

        // Base query
        $sql = "SELECT c.*,
                u.firstname as creator_name, u.surname as creator_surname,
                COUNT(DISTINCT e.id) as enrollment_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE 1=1";

        $params = [];
        $types = "";

        // Apply filters
        if (isset($filters['is_published'])) {
            $sql .= " AND c.is_published = ?";
            $params[] = $filters['is_published'];
            $types .= "i";
        }

        if (isset($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $sql .= " AND c.category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }

        if (isset($filters['difficulty_level']) && !empty($filters['difficulty_level'])) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
            $types .= "s";
        }

        if (isset($filters['is_featured'])) {
            $sql .= " AND c.is_featured = ?";
            $params[] = $filters['is_featured'];
            $types .= "i";
        }

        // Apply search
        if ($search && !empty(trim($search))) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.course_code LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $sql .= " GROUP BY c.id ORDER BY c.is_featured DESC, c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }

        return $courses;
    }

    /**
     * Count published courses matching filters
     *
     * @param array $filters Filters to apply
     * @param string|null $search Search term
     * @return int Total count
     */
    public function getPublishedCoursesCount($filters = [], $search = null) {
        $sql = "SELECT COUNT(DISTINCT c.id) as total FROM courses c WHERE 1=1";

        $params = [];
        $types = "";

        // Apply same filters as getPublishedCourses
        if (isset($filters['is_published'])) {
            $sql .= " AND c.is_published = ?";
            $params[] = $filters['is_published'];
            $types .= "i";
        }

        if (isset($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $sql .= " AND c.category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }

        if (isset($filters['difficulty_level']) && !empty($filters['difficulty_level'])) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
            $types .= "s";
        }

        if (isset($filters['is_featured'])) {
            $sql .= " AND c.is_featured = ?";
            $params[] = $filters['is_featured'];
            $types .= "i";
        }

        if ($search && !empty(trim($search))) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.course_code LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }

        return 0;
    }

    /**
     * Search courses by query string
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array Search results
     */
    public function searchCourses($query, $filters = [], $limit = 20, $offset = 0) {
        // Use same method as getPublishedCourses with search parameter
        return $this->getPublishedCourses($filters, $query, $limit, $offset);
    }

    /**
     * Count search results
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return int Total count
     */
    public function searchCoursesCount($query, $filters = []) {
        return $this->getPublishedCoursesCount($filters, $query);
    }

    /**
     * Get featured courses only
     *
     * @return array Featured courses
     */
    public function getFeaturedCourses() {
        $filters = [
            'is_published' => 1,
            'status' => 'published',
            'is_featured' => 1
        ];

        return $this->getPublishedCourses($filters, null, 10, 0);
    }

    /**
     * Get user's enrollment in a course
     *
     * @param int $courseId Course ID
     * @param int $userId User ID
     * @return array|null Enrollment details or null
     */
    public function getUserEnrollment($courseId, $userId) {
        $sql = "SELECT * FROM enrollments WHERE course_id = ? AND user_id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $courseId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get count of lessons in a course
     *
     * @param int $courseId Course ID
     * @return int Lesson count
     */
    public function getCourseLessonsCount($courseId) {
        $sql = "SELECT COUNT(DISTINCT l.id) as total
                FROM lessons l
                INNER JOIN course_sections s ON l.section_id = s.id
                WHERE s.course_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }

        return 0;
    }

    /**
     * Get enrollment count for a course
     *
     * @param int $courseId Course ID
     * @return int Enrollment count
     */
    public function getEnrollmentCount($courseId) {
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE course_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }

        return 0;
    }

    /**
     * Increment course view count
     *
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function incrementViews($courseId) {
        $sql = "UPDATE courses SET views = views + 1 WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);

        return $stmt->execute();
    }

    /**
     * Create new enrollment for a user in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return int|null Enrollment ID or null on failure
     */
    public function createEnrollment($userId, $courseId) {
        // Get total lessons count for this course
        $totalLessons = $this->getCourseLessonsCount($courseId);

        $sql = "INSERT INTO enrollments
                (user_id, course_id, enrollment_date, status, progress, total_lessons)
                VALUES (?, ?, CURDATE(), 'enrolled', 0.00, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $userId, $courseId, $totalLessons);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return null;
    }

    /**
     * Reactivate a dropped enrollment
     *
     * @param int $enrollmentId Enrollment ID
     * @return bool Success status
     */
    public function reactivateEnrollment($enrollmentId) {
        $sql = "UPDATE enrollments SET status = 'active' WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $enrollmentId);

        return $stmt->execute();
    }

    /**
     * Update enrollment status
     *
     * @param int $enrollmentId Enrollment ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateEnrollmentStatus($enrollmentId, $status) {
        $sql = "UPDATE enrollments SET status = ? WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $enrollmentId);

        return $stmt->execute();
    }

    /**
     * Delete an enrollment (hard delete)
     *
     * @param int $enrollmentId Enrollment ID
     * @return bool Success status
     */
    public function deleteEnrollment($enrollmentId) {
        $sql = "DELETE FROM enrollments WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $enrollmentId);

        return $stmt->execute();
    }

    /**
     * Get user's enrolled courses with details
     *
     * @param int $userId User ID
     * @param array $filters Status filters
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array List of enrolled courses
     */
    public function getUserEnrolledCourses($userId, $filters = [], $limit = 20, $offset = 0) {
        $courses = [];

        $sql = "SELECT c.*,
                e.id as enrollment_id,
                e.enrollment_date,
                e.completion_date,
                e.status as enrollment_status,
                e.progress,
                e.last_accessed_at,
                e.total_time_spent,
                e.lessons_completed,
                e.total_lessons,
                u.firstname as creator_name,
                u.surname as creator_surname
                FROM enrollments e
                INNER JOIN courses c ON e.course_id = c.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE e.user_id = ?";

        $params = [$userId];
        $types = "i";

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        $sql .= " ORDER BY e.last_accessed_at DESC, e.enrollment_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }

        return $courses;
    }

    /**
     * Count user's enrolled courses
     *
     * @param int $userId User ID
     * @param array $filters Status filters
     * @return int Total count
     */
    public function getUserEnrolledCoursesCount($userId, $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?";

        $params = [$userId];
        $types = "i";

        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }

        return 0;
    }

    /**
     * Get course sections with user progress
     *
     * @param int $courseId Course ID
     * @param int $userId User ID
     * @return array Sections with progress data
     */
    public function getCourseSectionsWithProgress($courseId, $userId) {
        $sections = [];

        $sql = "SELECT s.*,
                COUNT(DISTINCT l.id) as total_lessons,
                COUNT(DISTINCT CASE WHEN lp.status = 'completed' THEN lp.id END) as completed_lessons,
                ROUND(AVG(CASE WHEN lp.status = 'completed' THEN 100 ELSE 0 END), 2) as progress_percentage
                FROM course_sections s
                LEFT JOIN lessons l ON s.id = l.section_id
                LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                WHERE s.course_id = ?
                GROUP BY s.id
                ORDER BY s.order_number ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        }

        return $sections;
    }

    /**
     * Get user's lesson progress summary for a course
     *
     * @param int $courseId Course ID
     * @param int $userId User ID
     * @return array Progress summary
     */
    public function getUserLessonProgress($courseId, $userId) {
        $sql = "SELECT
                COUNT(CASE WHEN lp.status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN lp.status = 'in_progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN lp.status = 'not_started' THEN 1 END) as not_started,
                SUM(lp.time_spent) as total_time_spent
                FROM lessons l
                INNER JOIN course_sections s ON l.section_id = s.id
                LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                WHERE s.course_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return [
            'completed' => 0,
            'in_progress' => 0,
            'not_started' => 0,
            'total_time_spent' => 0
        ];
    }
}
?>