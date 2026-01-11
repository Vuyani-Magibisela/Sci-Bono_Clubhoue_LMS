<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * Search & Filter API Controller
 *
 * Handles global search across courses, programs, and lessons
 * Provides category and filter options discovery
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 5 Day 5 (January 11, 2026)
 */
class SearchController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/v1/search
     * Global search across courses, programs, and lessons
     *
     * Query Parameters:
     * - q (string, required): Search query
     * - type (string): Filter by entity type (course, program, lesson, all)
     * - category (string): Filter by course category
     * - limit (int): Results per type (default: 10, max: 50)
     *
     * @return JSON response with search results grouped by type
     */
    public function search() {
        try {
            // Authentication is optional for search (public browsing)
            $isAuthenticated = isset($_SESSION['user_id']);
            $userId = $isAuthenticated ? $_SESSION['user_id'] : null;

            // Get query parameters
            $query = $_GET['q'] ?? null;
            $type = $_GET['type'] ?? 'all';
            $category = $_GET['category'] ?? null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

            // Validate query
            if (!$query || trim($query) === '') {
                return $this->jsonError('Search query is required', null, 400);
            }

            // Validate limit
            if ($limit < 1) $limit = 10;
            if ($limit > 50) $limit = 50;

            // Validate type
            $validTypes = ['all', 'course', 'program', 'lesson'];
            if (!in_array($type, $validTypes)) {
                $type = 'all';
            }

            // Sanitize query for LIKE search
            $searchTerm = '%' . $this->sanitizeSearchQuery($query) . '%';

            // Initialize results
            $results = [
                'courses' => [],
                'programs' => [],
                'lessons' => []
            ];

            $totalResults = 0;

            // Search courses
            if ($type === 'all' || $type === 'course') {
                $courses = $this->searchCourses($searchTerm, $category, $limit, $userId);
                $results['courses'] = $courses;
                $totalResults += count($courses);
            }

            // Search programs
            if ($type === 'all' || $type === 'program') {
                $programs = $this->searchPrograms($searchTerm, $limit, $userId);
                $results['programs'] = $programs;
                $totalResults += count($programs);
            }

            // Search lessons
            if ($type === 'all' || $type === 'lesson') {
                $lessons = $this->searchLessons($searchTerm, $limit, $userId);
                $results['lessons'] = $lessons;
                $totalResults += count($lessons);
            }

            // Build response
            $response = [
                'query' => $query,
                'results' => $results,
                'summary' => [
                    'total_results' => $totalResults,
                    'courses_count' => count($results['courses']),
                    'programs_count' => count($results['programs']),
                    'lessons_count' => count($results['lessons'])
                ],
                'filters_applied' => array_filter([
                    'type' => $type !== 'all' ? $type : null,
                    'category' => $category
                ])
            ];

            $message = $totalResults > 0
                ? "Found {$totalResults} result(s) for '{$query}'"
                : "No results found for '{$query}'";

            return $this->jsonSuccess($response, $message);

        } catch (\Exception $e) {
            $this->logger->log('error', 'Search failed', [
                'query' => $_GET['q'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during search', null, 500);
        }
    }

    /**
     * GET /api/v1/categories
     * Get list of available course categories
     *
     * Returns all unique categories from published courses
     *
     * @return JSON response with categories list
     */
    public function categories() {
        try {
            // Get all unique categories from published courses
            $sql = "SELECT DISTINCT category
                    FROM courses
                    WHERE is_published = 1 AND status = 'published' AND category IS NOT NULL AND category != ''
                    ORDER BY category ASC";

            $result = $this->conn->query($sql);

            $categories = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $category = $row['category'];

                    // Get course count for this category
                    $countSql = "SELECT COUNT(*) as count FROM courses
                                WHERE is_published = 1 AND status = 'published' AND category = ?";
                    $countStmt = $this->conn->prepare($countSql);
                    $countStmt->bind_param("s", $category);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result()->fetch_assoc();

                    $categories[] = [
                        'name' => $category,
                        'slug' => $this->slugify($category),
                        'course_count' => $countResult['count']
                    ];
                }
            }

            return $this->jsonSuccess([
                'categories' => $categories,
                'total' => count($categories)
            ], 'Categories retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve categories', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving categories', null, 500);
        }
    }

    /**
     * GET /api/v1/filters/options
     * Get available filter options for courses and programs
     *
     * Returns available values for common filters
     *
     * @return JSON response with filter options
     */
    public function filterOptions() {
        try {
            $options = [
                'course_filters' => [
                    'categories' => $this->getCourseCategories(),
                    'difficulty_levels' => $this->getDifficultyLevels(),
                    'statuses' => [
                        ['value' => 'published', 'label' => 'Published', 'count' => $this->getCoursesCountByStatus('published')],
                        ['value' => 'draft', 'label' => 'Draft', 'count' => $this->getCoursesCountByStatus('draft')]
                    ],
                    'featured' => [
                        ['value' => '1', 'label' => 'Featured', 'count' => $this->getFeaturedCoursesCount()],
                        ['value' => '0', 'label' => 'Not Featured', 'count' => $this->getNonFeaturedCoursesCount()]
                    ]
                ],
                'program_filters' => [
                    'statuses' => [
                        ['value' => 'upcoming', 'label' => 'Upcoming', 'count' => $this->getProgramsCountByStatus('upcoming')],
                        ['value' => 'ongoing', 'label' => 'Ongoing', 'count' => $this->getProgramsCountByStatus('ongoing')],
                        ['value' => 'past', 'label' => 'Past', 'count' => $this->getProgramsCountByStatus('past')]
                    ],
                    'years' => $this->getProgramYears()
                ],
                'lesson_filters' => [
                    'statuses' => [
                        ['value' => 'not_started', 'label' => 'Not Started'],
                        ['value' => 'in_progress', 'label' => 'In Progress'],
                        ['value' => 'completed', 'label' => 'Completed']
                    ]
                ]
            ];

            return $this->jsonSuccess($options, 'Filter options retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve filter options', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving filter options', null, 500);
        }
    }

    /**
     * Search courses
     *
     * @param string $searchTerm LIKE-formatted search term
     * @param string|null $category Category filter
     * @param int $limit Results limit
     * @param int|null $userId User ID for enrollment status
     * @return array Course results
     */
    private function searchCourses($searchTerm, $category, $limit, $userId) {
        $sql = "SELECT c.id, c.title, c.description, c.category, c.difficulty_level,
                c.is_featured, c.image_path, c.created_at,
                u.name as creator_name, u.surname as creator_surname,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as enrollment_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.is_published = 1 AND c.status = 'published'
                AND (c.title LIKE ? OR c.description LIKE ? OR c.course_code LIKE ?)";

        $params = [$searchTerm, $searchTerm, $searchTerm];
        $types = "sss";

        if ($category) {
            $sql .= " AND c.category = ?";
            $params[] = $category;
            $types .= "s";
        }

        $sql .= " ORDER BY c.is_featured DESC, c.created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $courses = [];
        while ($course = $result->fetch_assoc()) {
            // Add enrollment status if authenticated
            if ($userId) {
                $enrollSql = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ? AND user_id = ?";
                $enrollStmt = $this->conn->prepare($enrollSql);
                $enrollStmt->bind_param("ii", $course['id'], $userId);
                $enrollStmt->execute();
                $enrollResult = $enrollStmt->get_result()->fetch_assoc();
                $course['is_enrolled'] = $enrollResult['count'] > 0;
            } else {
                $course['is_enrolled'] = false;
            }

            $course['entity_type'] = 'course';
            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * Search holiday programs
     *
     * @param string $searchTerm LIKE-formatted search term
     * @param int $limit Results limit
     * @param int|null $userId User ID for registration status
     * @return array Program results
     */
    private function searchPrograms($searchTerm, $limit, $userId) {
        $sql = "SELECT p.id, p.term, p.title, p.description, p.dates,
                p.start_date, p.end_date, p.max_participants, p.registration_open,
                (SELECT COUNT(*) FROM holiday_program_attendees a WHERE a.program_id = p.id) as total_registrations
                FROM holiday_programs p
                WHERE p.registration_open = 1
                AND (p.title LIKE ? OR p.description LIKE ? OR p.term LIKE ?)
                ORDER BY p.start_date DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $programs = [];
        $currentDate = date('Y-m-d');

        while ($program = $result->fetch_assoc()) {
            // Compute status
            if ($currentDate < $program['start_date']) {
                $program['status'] = 'upcoming';
            } elseif ($currentDate >= $program['start_date'] && $currentDate <= $program['end_date']) {
                $program['status'] = 'ongoing';
            } else {
                $program['status'] = 'past';
            }

            // Check if user is registered
            if ($userId) {
                $regSql = "SELECT COUNT(*) as count FROM holiday_program_attendees
                          WHERE program_id = ? AND user_id = ?";
                $regStmt = $this->conn->prepare($regSql);
                $regStmt->bind_param("ii", $program['id'], $userId);
                $regStmt->execute();
                $regResult = $regStmt->get_result()->fetch_assoc();
                $program['is_registered'] = $regResult['count'] > 0;
            } else {
                $program['is_registered'] = false;
            }

            $program['entity_type'] = 'program';
            $programs[] = $program;
        }

        return $programs;
    }

    /**
     * Search lessons
     *
     * @param string $searchTerm LIKE-formatted search term
     * @param int $limit Results limit
     * @param int|null $userId User ID for progress status
     * @return array Lesson results
     */
    private function searchLessons($searchTerm, $limit, $userId) {
        $sql = "SELECT l.id, l.title, l.description, l.duration, l.order_number,
                l.is_free_preview, l.section_id,
                s.title as section_title, s.course_id,
                c.title as course_title
                FROM course_lessons l
                JOIN course_sections s ON l.section_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE c.is_published = 1 AND c.status = 'published'
                AND (l.title LIKE ? OR l.description LIKE ?)
                ORDER BY c.is_featured DESC, l.order_number ASC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $lessons = [];
        while ($lesson = $result->fetch_assoc()) {
            // Check if user is enrolled in course
            if ($userId) {
                $enrollSql = "SELECT COUNT(*) as count FROM enrollments
                             WHERE course_id = ? AND user_id = ?";
                $enrollStmt = $this->conn->prepare($enrollSql);
                $enrollStmt->bind_param("ii", $lesson['course_id'], $userId);
                $enrollStmt->execute();
                $enrollResult = $enrollStmt->get_result()->fetch_assoc();
                $lesson['is_enrolled'] = $enrollResult['count'] > 0;

                // Get progress if enrolled
                if ($lesson['is_enrolled']) {
                    $progressSql = "SELECT status, progress_percentage FROM lesson_progress
                                   WHERE lesson_id = ? AND user_id = ?";
                    $progressStmt = $this->conn->prepare($progressSql);
                    $progressStmt->bind_param("ii", $lesson['id'], $userId);
                    $progressStmt->execute();
                    $progressResult = $progressStmt->get_result();

                    if ($progressResult->num_rows > 0) {
                        $progress = $progressResult->fetch_assoc();
                        $lesson['progress_status'] = $progress['status'];
                        $lesson['progress_percentage'] = $progress['progress_percentage'];
                    } else {
                        $lesson['progress_status'] = 'not_started';
                        $lesson['progress_percentage'] = 0;
                    }
                }
            } else {
                $lesson['is_enrolled'] = false;
                $lesson['progress_status'] = null;
            }

            $lesson['entity_type'] = 'lesson';
            $lessons[] = $lesson;
        }

        return $lessons;
    }

    /**
     * Get course categories with counts
     *
     * @return array Categories
     */
    private function getCourseCategories() {
        $sql = "SELECT DISTINCT category, COUNT(*) as count
                FROM courses
                WHERE is_published = 1 AND status = 'published' AND category IS NOT NULL AND category != ''
                GROUP BY category
                ORDER BY category ASC";

        $result = $this->conn->query($sql);
        $categories = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = [
                    'value' => $row['category'],
                    'label' => ucfirst($row['category']),
                    'count' => $row['count']
                ];
            }
        }

        return $categories;
    }

    /**
     * Get difficulty levels with counts
     *
     * @return array Difficulty levels
     */
    private function getDifficultyLevels() {
        $sql = "SELECT difficulty_level, COUNT(*) as count
                FROM courses
                WHERE is_published = 1 AND status = 'published' AND difficulty_level IS NOT NULL
                GROUP BY difficulty_level
                ORDER BY FIELD(difficulty_level, 'beginner', 'intermediate', 'advanced')";

        $result = $this->conn->query($sql);
        $levels = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $levels[] = [
                    'value' => $row['difficulty_level'],
                    'label' => ucfirst($row['difficulty_level']),
                    'count' => $row['count']
                ];
            }
        }

        return $levels;
    }

    /**
     * Get courses count by status
     *
     * @param string $status Status value
     * @return int Count
     */
    private function getCoursesCountByStatus($status) {
        $sql = "SELECT COUNT(*) as count FROM courses WHERE status = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    /**
     * Get featured courses count
     *
     * @return int Count
     */
    private function getFeaturedCoursesCount() {
        $sql = "SELECT COUNT(*) as count FROM courses
                WHERE is_published = 1 AND status = 'published' AND is_featured = 1";
        return $this->conn->query($sql)->fetch_assoc()['count'];
    }

    /**
     * Get non-featured courses count
     *
     * @return int Count
     */
    private function getNonFeaturedCoursesCount() {
        $sql = "SELECT COUNT(*) as count FROM courses
                WHERE is_published = 1 AND status = 'published' AND is_featured = 0";
        return $this->conn->query($sql)->fetch_assoc()['count'];
    }

    /**
     * Get programs count by status
     *
     * @param string $status Status value (upcoming, ongoing, past)
     * @return int Count
     */
    private function getProgramsCountByStatus($status) {
        $currentDate = date('Y-m-d');
        $sql = "";

        switch ($status) {
            case 'upcoming':
                $sql = "SELECT COUNT(*) as count FROM holiday_programs WHERE start_date > ?";
                break;
            case 'ongoing':
                $sql = "SELECT COUNT(*) as count FROM holiday_programs WHERE start_date <= ? AND end_date >= ?";
                break;
            case 'past':
                $sql = "SELECT COUNT(*) as count FROM holiday_programs WHERE end_date < ?";
                break;
        }

        $stmt = $this->conn->prepare($sql);

        if ($status === 'ongoing') {
            $stmt->bind_param("ss", $currentDate, $currentDate);
        } else {
            $stmt->bind_param("s", $currentDate);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    /**
     * Get program years
     *
     * @return array Years with counts
     */
    private function getProgramYears() {
        $sql = "SELECT YEAR(start_date) as year, COUNT(*) as count
                FROM holiday_programs
                WHERE registration_open = 1
                GROUP BY YEAR(start_date)
                ORDER BY year DESC";

        $result = $this->conn->query($sql);
        $years = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $years[] = [
                    'value' => $row['year'],
                    'label' => $row['year'],
                    'count' => $row['count']
                ];
            }
        }

        return $years;
    }

    /**
     * Sanitize search query for LIKE search
     *
     * @param string $query Search query
     * @return string Sanitized query
     */
    private function sanitizeSearchQuery($query) {
        // Remove special characters that could affect LIKE search
        $query = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        return trim($query);
    }

    /**
     * Create URL-friendly slug from string
     *
     * @param string $string Input string
     * @return string Slug
     */
    private function slugify($string) {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }
}
