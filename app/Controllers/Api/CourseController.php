<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CourseModel;

/**
 * Public Course API Controller
 *
 * Handles public-facing course APIs for browsing, searching, and viewing courses
 * Authentication required for most endpoints
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 5 Day 1 (January 11, 2026)
 */
class CourseController extends BaseController {

    private $courseModel;

    public function __construct() {
        parent::__construct();
        $this->courseModel = new CourseModel($this->conn);
    }

    /**
     * GET /api/v1/courses
     * List all published courses with filtering and pagination
     *
     * Query Parameters:
     * - category (string): Filter by category
     * - level (string): Filter by difficulty level (beginner, intermediate, advanced)
     * - featured (boolean): Show only featured courses (1/0)
     * - search (string): Search term for title/description
     * - limit (int): Number of results per page (default: 20, max: 100)
     * - offset (int): Pagination offset (default: 0)
     *
     * @return JSON response with course list
     */
    public function index() {
        try {
            // Optional authentication (allow browsing without auth)
            $isAuthenticated = isset($_SESSION['user_id']);

            // Get query parameters
            $category = $_GET['category'] ?? null;
            $level = $_GET['level'] ?? null;
            $featured = isset($_GET['featured']) ? intval($_GET['featured']) : null;
            $search = $_GET['search'] ?? null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            // Validate and sanitize limit
            if ($limit < 1) $limit = 20;
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;

            // Build filter array
            $filters = [
                'is_published' => 1, // Only show published courses
                'status' => 'published'
            ];

            if ($category) {
                $filters['category'] = $category;
            }

            if ($level) {
                $filters['difficulty_level'] = $level;
            }

            if ($featured !== null) {
                $filters['is_featured'] = $featured;
            }

            // Get courses
            $courses = $this->courseModel->getPublishedCourses($filters, $search, $limit, $offset);
            $totalCount = $this->courseModel->getPublishedCoursesCount($filters, $search);

            // Add enrollment info if authenticated
            if ($isAuthenticated) {
                $userId = $_SESSION['user_id'];
                foreach ($courses as &$course) {
                    $enrollment = $this->courseModel->getUserEnrollment($course['id'], $userId);
                    $course['is_enrolled'] = $enrollment !== null;
                    $course['enrollment_progress'] = $enrollment ? $enrollment['progress'] : 0;
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
                    'category' => $category,
                    'level' => $level,
                    'featured' => $featured,
                    'search' => $search
                ])
            ], 'Courses retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve courses', [
                'filters' => $_GET,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving courses', null, 500);
        }
    }

    /**
     * GET /api/v1/courses/{id}
     * Get detailed course information
     *
     * Returns full course details including sections, lessons count, instructor info
     * Full content only available to enrolled users
     *
     * @param int $id Course ID
     * @return JSON response with course details
     */
    public function show($id) {
        try {
            // Optional authentication
            $isAuthenticated = isset($_SESSION['user_id']);
            $userId = $isAuthenticated ? $_SESSION['user_id'] : null;

            // Get course details
            $course = $this->courseModel->getCourseById($id);

            if (!$course) {
                return $this->jsonError('Course not found', null, 404);
            }

            // Check if course is published (unless admin)
            $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
            if (!$isAdmin && (!$course['is_published'] || $course['status'] !== 'published')) {
                return $this->jsonError('Course not available', null, 403);
            }

            // Get course sections
            $sections = $this->courseModel->getCourseSections($id);
            $course['sections'] = $sections;
            $course['sections_count'] = count($sections);

            // Get lessons count
            $lessonsCount = $this->courseModel->getCourseLessonsCount($id);
            $course['total_lessons'] = $lessonsCount;

            // Get enrollment count
            $enrollmentCount = $this->courseModel->getEnrollmentCount($id);
            $course['total_enrollments'] = $enrollmentCount;

            // Check enrollment status if authenticated
            if ($isAuthenticated) {
                $enrollment = $this->courseModel->getUserEnrollment($id, $userId);
                $course['is_enrolled'] = $enrollment !== null;

                if ($enrollment) {
                    $course['enrollment'] = [
                        'enrolled_at' => $enrollment['enrollment_date'],
                        'status' => $enrollment['status'],
                        'progress' => $enrollment['progress'],
                        'lessons_completed' => $enrollment['lessons_completed'],
                        'last_accessed' => $enrollment['last_accessed_at']
                    ];
                }
            } else {
                $course['is_enrolled'] = false;
            }

            // Log course view
            $this->courseModel->incrementViews($id);

            return $this->jsonSuccess([
                'course' => $course
            ], 'Course details retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve course details', [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving course', null, 500);
        }
    }

    /**
     * GET /api/v1/courses/search
     * Search courses by title, description, code
     *
     * Query Parameters:
     * - q (string, required): Search query
     * - category (string): Filter by category
     * - level (string): Filter by difficulty level
     * - limit (int): Results per page (default: 20, max: 100)
     * - offset (int): Pagination offset (default: 0)
     *
     * @return JSON response with search results
     */
    public function search() {
        try {
            // Optional authentication
            $isAuthenticated = isset($_SESSION['user_id']);

            // Get search query
            $query = $_GET['q'] ?? '';

            if (empty(trim($query))) {
                return $this->jsonError('Search query is required', null, 400);
            }

            // Get filters
            $category = $_GET['category'] ?? null;
            $level = $_GET['level'] ?? null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            // Validate limits
            if ($limit < 1) $limit = 20;
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;

            // Build filters
            $filters = [
                'is_published' => 1,
                'status' => 'published'
            ];

            if ($category) $filters['category'] = $category;
            if ($level) $filters['difficulty_level'] = $level;

            // Search courses
            $results = $this->courseModel->searchCourses($query, $filters, $limit, $offset);
            $totalCount = $this->courseModel->searchCoursesCount($query, $filters);

            // Add enrollment info if authenticated
            if ($isAuthenticated) {
                $userId = $_SESSION['user_id'];
                foreach ($results as &$course) {
                    $enrollment = $this->courseModel->getUserEnrollment($course['id'], $userId);
                    $course['is_enrolled'] = $enrollment !== null;
                    $course['enrollment_progress'] = $enrollment ? $enrollment['progress'] : 0;
                }
            }

            return $this->jsonSuccess([
                'results' => $results,
                'query' => $query,
                'pagination' => [
                    'total' => $totalCount,
                    'count' => count($results),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ]
            ], "Found {$totalCount} course(s) matching '{$query}'");

        } catch (\Exception $e) {
            $this->logger->log('error', 'Course search failed', [
                'query' => $_GET['q'] ?? '',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during search', null, 500);
        }
    }

    /**
     * GET /api/v1/courses/featured
     * Get featured courses only
     *
     * No authentication required - public browsing
     *
     * @return JSON response with featured courses
     */
    public function featured() {
        try {
            // Get featured courses
            $courses = $this->courseModel->getFeaturedCourses();

            return $this->jsonSuccess([
                'courses' => $courses,
                'count' => count($courses)
            ], 'Featured courses retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve featured courses', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving featured courses', null, 500);
        }
    }
}
