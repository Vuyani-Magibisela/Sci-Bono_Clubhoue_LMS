<?php
/**
 * Course Service - Business logic for course management and enrollment
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/CourseModel.php';
require_once __DIR__ . '/../Models/EnrollmentModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/CacheManager.php'; // Phase 3 Week 9 - Caching

class CourseService extends BaseService {
    private $courseModel;
    private $enrollmentModel;
    private $userModel;
    private $cache; // Phase 3 Week 9 - Cache manager

    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->courseModel = new CourseModel($this->conn);
        $this->enrollmentModel = new EnrollmentModel($this->conn);
        $this->userModel = new UserModel($this->conn);
        $this->cache = new CacheManager(); // Phase 3 Week 9 - Initialize cache
    }

    /**
     * Get all courses with enrollment status for a user
     *
     * @param int|null $userId User ID (optional, for enrollment status)
     * @return array List of courses
     */
    public function getAllCourses($userId = null) {
        try {
            $this->logAction('get_all_courses', ['user_id' => $userId]);

            // PHASE 3 WEEK 9 - CACHING: Cache course list for 15 minutes
            // Different cache keys for authenticated vs public users
            $cacheKey = "courses_list_" . ($userId ?? 'public');

            $courses = $this->cache->remember($cacheKey, 900, function() use ($userId) {
                // Fetch all courses from database
                $courses = $this->courseModel->getAllCourses();

                // PHASE 3 WEEK 9 - PERFORMANCE: Batch query eliminates N+1 problem
                // OLD: 101 queries for 50 courses (1 base + 50×2 for enrollment + progress)
                // NEW: 2 queries (1 base + 1 batch enrollment check)
                if ($userId && !empty($courses)) {
                    $courseIds = array_column($courses, 'id');
                    $enrollmentData = $this->enrollmentModel->getUserEnrollmentsBatch($userId, $courseIds);

                    foreach ($courses as &$course) {
                        $courseId = $course['id'];
                        $course['is_enrolled'] = isset($enrollmentData[$courseId]);
                        $course['progress'] = $enrollmentData[$courseId]['progress'] ?? 0;
                    }
                }

                return $courses;
            });

            return $courses;

        } catch (Exception $e) {
            $this->logger->error("Failed to get courses", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get course details with sections and lessons
     *
     * @param int $courseId Course ID
     * @param int|null $userId User ID for progress tracking
     * @return array|null Course details or null if not found
     */
    public function getCourseDetails($courseId, $userId = null) {
        try {
            $this->logAction('get_course_details', ['course_id' => $courseId, 'user_id' => $userId]);

            $course = $this->courseModel->getCourseDetails($courseId);

            if (!$course) {
                return null;
            }

            // Get sections with lessons
            $sections = $this->courseModel->getCourseSections($courseId);

            // Add enrollment and progress info if user provided
            if ($userId) {
                $course['is_enrolled'] = $this->enrollmentModel->isUserEnrolled($userId, $courseId);
                $course['progress'] = $this->enrollmentModel->getUserProgress($userId, $courseId);
            }

            $course['sections'] = $sections;

            return $course;

        } catch (Exception $e) {
            $this->handleError("Failed to get course details: " . $e->getMessage(), [
                'course_id' => $courseId,
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Enroll user in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function enrollUser($userId, $courseId) {
        try {
            $this->logAction('enroll_user_attempt', ['user_id' => $userId, 'course_id' => $courseId]);

            // Validate user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Validate course exists
            $course = $this->courseModel->getCourseDetails($courseId);
            if (!$course) {
                throw new Exception("Course not found");
            }

            // Check if already enrolled
            if ($this->enrollmentModel->isUserEnrolled($userId, $courseId)) {
                throw new Exception("User is already enrolled in this course");
            }

            // Enroll the user
            $result = $this->enrollmentModel->enrollUser($userId, $courseId);

            if ($result) {
                $this->logAction('user_enrolled_success', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'course_title' => $course['title']
                ]);

                // PHASE 3 WEEK 9 - CACHING: Invalidate enrollment-related caches
                $this->invalidateEnrollmentCache($userId, $courseId);

                return true;
            }

            throw new Exception("Failed to enroll user");

        } catch (Exception $e) {
            $this->handleError("Enrollment failed: " . $e->getMessage(), [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
        }
    }

    /**
     * Get user's enrolled courses
     *
     * @param int $userId User ID
     * @return array List of enrolled courses
     */
    public function getUserEnrolledCourses($userId) {
        try {
            $this->logAction('get_user_enrollments', ['user_id' => $userId]);

            $enrollments = $this->enrollmentModel->getUserEnrollments($userId);

            return $enrollments;

        } catch (Exception $e) {
            $this->logger->error("Failed to get user enrollments", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get courses by category/type
     *
     * @param string $type Course type (e.g., 'programming', 'design', 'robotics')
     * @param int|null $userId User ID for enrollment status
     * @return array List of courses
     */
    public function getCoursesByType($type, $userId = null) {
        try {
            $this->logAction('get_courses_by_type', ['type' => $type, 'user_id' => $userId]);

            $allCourses = $this->courseModel->getAllCourses();

            // Filter by type
            $filteredCourses = array_filter($allCourses, function($course) use ($type) {
                return strtolower($course['type']) === strtolower($type);
            });

            // Add enrollment status
            if ($userId) {
                foreach ($filteredCourses as &$course) {
                    $course['is_enrolled'] = $this->enrollmentModel->isUserEnrolled($userId, $course['id']);
                    $course['progress'] = $this->enrollmentModel->getUserProgress($userId, $course['id']);
                }
            }

            return array_values($filteredCourses);

        } catch (Exception $e) {
            $this->logger->error("Failed to get courses by type", ['type' => $type]);
            return [];
        }
    }

    /**
     * Search courses by query
     *
     * @param string $query Search query
     * @param int|null $userId User ID for enrollment status
     * @return array List of matching courses
     */
    public function searchCourses($query, $userId = null) {
        try {
            $this->logAction('search_courses', ['query' => $query, 'user_id' => $userId]);

            $allCourses = $this->courseModel->getAllCourses();

            // Search in title and description
            $query = strtolower($query);
            $results = array_filter($allCourses, function($course) use ($query) {
                $title = strtolower($course['title']);
                $description = strtolower($course['description'] ?? '');
                return strpos($title, $query) !== false || strpos($description, $query) !== false;
            });

            // Add enrollment status
            if ($userId) {
                foreach ($results as &$course) {
                    $course['is_enrolled'] = $this->enrollmentModel->isUserEnrolled($userId, $course['id']);
                    $course['progress'] = $this->enrollmentModel->getUserProgress($userId, $course['id']);
                }
            }

            return array_values($results);

        } catch (Exception $e) {
            $this->logger->error("Course search failed", ['query' => $query]);
            return [];
        }
    }

    /**
     * Get featured/recommended courses
     *
     * @param int $limit Number of courses to return
     * @param int|null $userId User ID for personalization
     * @return array List of featured courses
     */
    public function getFeaturedCourses($limit = 6, $userId = null) {
        try {
            $allCourses = $this->courseModel->getAllCourses();

            // Sort by enrollment count (most popular)
            usort($allCourses, function($a, $b) {
                return ($b['enrollment_count'] ?? 0) - ($a['enrollment_count'] ?? 0);
            });

            $featured = array_slice($allCourses, 0, $limit);

            // Add enrollment status
            if ($userId) {
                foreach ($featured as &$course) {
                    $course['is_enrolled'] = $this->enrollmentModel->isUserEnrolled($userId, $course['id']);
                    $course['progress'] = $this->enrollmentModel->getUserProgress($userId, $course['id']);
                }
            }

            return $featured;

        } catch (Exception $e) {
            $this->logger->error("Failed to get featured courses");
            return [];
        }
    }

    /**
     * Get courses in progress for user
     *
     * @param int $userId User ID
     * @return array List of in-progress courses
     */
    public function getInProgressCourses($userId) {
        try {
            $enrollments = $this->enrollmentModel->getUserEnrollments($userId);

            // Filter for courses with 0 < progress < 100
            $inProgress = array_filter($enrollments, function($enrollment) {
                $progress = $enrollment['progress'] ?? 0;
                return $progress > 0 && $progress < 100;
            });

            return array_values($inProgress);

        } catch (Exception $e) {
            $this->logger->error("Failed to get in-progress courses", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get completed courses for user
     *
     * @param int $userId User ID
     * @return array List of completed courses
     */
    public function getCompletedCourses($userId) {
        try {
            $enrollments = $this->enrollmentModel->getUserEnrollments($userId);

            // Filter for courses with progress = 100
            $completed = array_filter($enrollments, function($enrollment) {
                return ($enrollment['progress'] ?? 0) >= 100;
            });

            return array_values($completed);

        } catch (Exception $e) {
            $this->logger->error("Failed to get completed courses", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get course statistics
     *
     * @param int $courseId Course ID
     * @return array Course statistics
     */
    public function getCourseStatistics($courseId) {
        try {
            $course = $this->courseModel->getCourseDetails($courseId);

            if (!$course) {
                return null;
            }

            return [
                'total_enrollments' => $course['enrollment_count'] ?? 0,
                'total_sections' => $course['section_count'] ?? 0,
                'total_lessons' => $course['lesson_count'] ?? 0,
                'difficulty_level' => $course['difficulty_level'] ?? 'Beginner',
                'course_type' => $course['type'] ?? 'General',
                'created_at' => $course['created_at'] ?? null
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get course statistics", ['course_id' => $courseId]);
            return null;
        }
    }

    /**
     * Unenroll user from course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool Success status
     */
    public function unenrollUser($userId, $courseId) {
        try {
            $this->logAction('unenroll_user_attempt', ['user_id' => $userId, 'course_id' => $courseId]);

            // Check if enrolled
            if (!$this->enrollmentModel->isUserEnrolled($userId, $courseId)) {
                throw new Exception("User is not enrolled in this course");
            }

            // Delete enrollment
            $sql = "DELETE FROM user_enrollments WHERE user_id = ? AND course_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $courseId);
            $result = $stmt->execute();

            if ($result) {
                // Update enrollment count
                $updateSql = "UPDATE courses SET enrollment_count = GREATEST(0, enrollment_count - 1) WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("i", $courseId);
                $updateStmt->execute();

                $this->logAction('user_unenrolled_success', [
                    'user_id' => $userId,
                    'course_id' => $courseId
                ]);

                // PHASE 3 WEEK 9 - CACHING: Invalidate enrollment-related caches
                $this->invalidateEnrollmentCache($userId, $courseId);

                return true;
            }

            throw new Exception("Failed to unenroll user");

        } catch (Exception $e) {
            $this->handleError("Unenrollment failed: " . $e->getMessage(), [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
        }
    }

    /**
     * Get recommended courses based on user's enrollments
     *
     * @param int $userId User ID
     * @param int $limit Number of recommendations
     * @return array List of recommended courses
     */
    public function getRecommendedCourses($userId, $limit = 3) {
        try {
            // Get user's enrolled courses
            $enrollments = $this->enrollmentModel->getUserEnrollments($userId);
            $enrolledTypes = [];

            foreach ($enrollments as $enrollment) {
                if (!empty($enrollment['type'])) {
                    $enrolledTypes[] = strtolower($enrollment['type']);
                }
            }

            // Get all courses
            $allCourses = $this->courseModel->getAllCourses();

            // Filter out enrolled courses
            $available = array_filter($allCourses, function($course) use ($userId) {
                return !$this->enrollmentModel->isUserEnrolled($userId, $course['id']);
            });

            // Prioritize courses of same type as enrolled
            $recommended = [];
            foreach ($available as $course) {
                $course['is_enrolled'] = false;
                $course['progress'] = ['percent' => 0, 'completed' => false];

                // Score based on relevance
                $score = 0;
                if (in_array(strtolower($course['type']), $enrolledTypes)) {
                    $score += 10;
                }
                $score += ($course['enrollment_count'] ?? 0) / 10; // Popular courses

                $course['recommendation_score'] = $score;
                $recommended[] = $course;
            }

            // Sort by score
            usort($recommended, function($a, $b) {
                return $b['recommendation_score'] - $a['recommendation_score'];
            });

            return array_slice($recommended, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get recommendations", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get course types/categories
     *
     * @return array List of unique course types
     */
    public function getCourseTypes() {
        try {
            $allCourses = $this->courseModel->getAllCourses();
            $types = [];

            foreach ($allCourses as $course) {
                if (!empty($course['type']) && !in_array($course['type'], $types)) {
                    $types[] = $course['type'];
                }
            }

            sort($types);
            return $types;

        } catch (Exception $e) {
            $this->logger->error("Failed to get course types");
            return [];
        }
    }

    /**
     * Validate course access for user
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if user can access course
     */
    public function canUserAccessCourse($userId, $courseId) {
        try {
            // Check if enrolled
            return $this->enrollmentModel->isUserEnrolled($userId, $courseId);

        } catch (Exception $e) {
            $this->logger->error("Failed to check course access", [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
            return false;
        }
    }

    // ========== PHASE 3 WEEK 9 - CACHE INVALIDATION METHODS ==========

    /**
     * Invalidate course-related caches
     * Call this method when courses are created, updated, or deleted
     *
     * @param int|null $courseId Specific course ID (null to invalidate all)
     * @param int|null $userId Specific user ID (null to invalidate all users)
     */
    public function invalidateCourseCache($courseId = null, $userId = null) {
        try {
            if ($courseId && $userId) {
                // Invalidate specific user's course list cache
                $this->cache->delete("courses_list_{$userId}");
                $this->cache->delete("course_details_{$courseId}_{$userId}");
                $this->logger->info("Invalidated course cache", [
                    'course_id' => $courseId,
                    'user_id' => $userId
                ]);
            } elseif ($courseId) {
                // Invalidate all caches related to a specific course
                $this->cache->deletePattern("course_*_{$courseId}_*");
                $this->cache->deletePattern("courses_list_*");
                $this->logger->info("Invalidated all caches for course", ['course_id' => $courseId]);
            } elseif ($userId) {
                // Invalidate all caches for a specific user
                $this->cache->deletePattern("courses_list_{$userId}");
                $this->cache->deletePattern("course_*_{$userId}");
                $this->logger->info("Invalidated all course caches for user", ['user_id' => $userId]);
            } else {
                // Nuclear option: clear all course-related caches
                $this->cache->deletePattern("courses_*");
                $this->cache->deletePattern("course_*");
                $this->logger->info("Invalidated all course caches");
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to invalidate course cache", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate enrollment caches for a user
     * Call this when user enrolls or unenrolls from a course
     *
     * @param int $userId User ID
     * @param int|null $courseId Course ID (optional)
     */
    public function invalidateEnrollmentCache($userId, $courseId = null) {
        try {
            // Invalidate user's course list (now includes new enrollment status)
            $this->cache->delete("courses_list_{$userId}");
            $this->cache->delete("enrolled_courses_{$userId}");
            $this->cache->delete("inprogress_courses_{$userId}");
            $this->cache->delete("completed_courses_{$userId}");

            // Also invalidate dashboard cache (enrollment affects dashboard)
            $this->cache->delete("dashboard_data_{$userId}");

            if ($courseId) {
                $this->cache->delete("course_details_{$courseId}_{$userId}");
            }

            $this->logger->info("Invalidated enrollment cache", [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
        } catch (Exception $e) {
            $this->logger->error("Failed to invalidate enrollment cache", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
