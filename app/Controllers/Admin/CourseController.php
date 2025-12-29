<?php
/**
 * Admin Course Controller - Consolidated Course Management
 * Week 5: Admin Panel Migration
 *
 * Handles all admin course management operations following RESTful conventions
 * Combines functionality from legacy CourseController and AdminCourseController
 */

namespace Admin;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Admin/AdminCourseModel.php';
require_once __DIR__ . '/../../Models/Admin/CourseModel.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class CourseController extends \BaseController {
    private $courseModel;
    private $hierarchyModel; // For modules/lessons/activities

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->courseModel = new \AdminCourseModel($conn);
        $this->hierarchyModel = new \CourseModel($conn);
    }

    // =================== RESTFUL CRUD METHODS ===================

    /**
     * Display list of all courses
     * Route: GET /admin/courses
     */
    public function index() {
        $this->requireRole('admin');

        // Get filters from query parameters
        $filters = [
            'type' => $this->input('type', ''),
            'status' => $this->input('status', ''),
            'difficulty_level' => $this->input('difficulty_level', '')
        ];

        // Remove empty filters
        $filters = array_filter($filters);

        // Get search query
        $search = $this->input('search', '');

        // Pagination
        $page = max(1, intval($this->input('page', 1)));
        $perPage = 25;

        // Get all courses
        $allCourses = $this->courseModel->getAllCourses($filters);

        // Apply search filter
        if (!empty($search)) {
            $allCourses = array_filter($allCourses, function($course) use ($search) {
                return stripos($course['title'], $search) !== false ||
                       stripos($course['description'], $search) !== false ||
                       stripos($course['course_code'], $search) !== false;
            });
        }

        // Calculate pagination
        $totalCourses = count($allCourses);
        $totalPages = ceil($totalCourses / $perPage);
        $offset = ($page - 1) * $perPage;
        $courses = array_slice($allCourses, $offset, $perPage);

        $data = [
            'pageTitle' => 'Manage Courses',
            'currentPage' => 'courses',
            'courses' => $courses,
            'totalCourses' => $totalCourses,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'search' => $search,
            'filters' => $filters
        ];

        return $this->view('admin.courses.index', $data, 'admin');
    }

    /**
     * Show form to create new course
     * Route: GET /admin/courses/create
     */
    public function create() {
        $this->requireRole('admin');

        $data = [
            'pageTitle' => 'Create New Course',
            'currentPage' => 'courses',
            'courseTypes' => $this->getCourseTypes(),
            'difficultyLevels' => $this->getDifficultyLevels()
        ];

        return $this->view('admin.courses.create', $data, 'admin');
    }

    /**
     * Process course creation
     * Route: POST /admin/courses
     */
    public function store() {
        $this->requireRole('admin');

        // Validate CSRF token
        if (!\CSRF::validateToken()) {
            error_log("CSRF validation failed in CourseController@store - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return $this->redirectWithError(BASE_URL . 'admin/courses/create', 'Security validation failed.');
        }

        // Validation rules
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:full_course,short_course,lesson,skill_activity',
            'difficulty_level' => 'required|in:Beginner,Intermediate,Advanced'
        ];

        try {
            $data = $this->validate($this->input(), $rules);
        } catch (\Exception $e) {
            return $this->redirectWithError(BASE_URL . 'admin/courses/create', 'Validation failed: ' . $e->getMessage());
        }

        // Sanitize and prepare course data
        $courseData = $this->sanitizeCourseData($data);
        $courseData['created_by'] = $_SESSION['user_id'];

        // Generate course code if not provided
        if (empty($courseData['course_code'])) {
            $courseData['course_code'] = $this->generateCourseCode($courseData['title'], $courseData['type']);
        }

        // Create course
        $courseId = $this->courseModel->createCourse($courseData);

        if ($courseId) {
            $this->logger->log('info', 'Course created', ['course_id' => $courseId, 'user_id' => $_SESSION['user_id']]);
            return $this->redirectWithSuccess(BASE_URL . 'admin/courses', 'Course created successfully.');
        }

        return $this->redirectWithError(BASE_URL . 'admin/courses/create', 'Failed to create course.');
    }

    /**
     * Display specific course details
     * Route: GET /admin/courses/{id}
     */
    public function show($id) {
        $this->requireRole('admin');

        $course = $this->courseModel->getCourseDetails($id, true);

        if (!$course) {
            return $this->redirectWithError(BASE_URL . 'admin/courses', 'Course not found.');
        }

        $data = [
            'pageTitle' => $course['title'],
            'currentPage' => 'courses',
            'course' => $course
        ];

        return $this->view('admin.courses.show', $data, 'admin');
    }

    /**
     * Show form to edit course
     * Route: GET /admin/courses/{id}/edit
     */
    public function edit($id) {
        $this->requireRole('admin');

        $course = $this->courseModel->getCourseDetails($id, false);

        if (!$course) {
            return $this->redirectWithError(BASE_URL . 'admin/courses', 'Course not found.');
        }

        $data = [
            'pageTitle' => 'Edit Course: ' . $course['title'],
            'currentPage' => 'courses',
            'course' => $course,
            'courseTypes' => $this->getCourseTypes(),
            'difficultyLevels' => $this->getDifficultyLevels()
        ];

        return $this->view('admin.courses.edit', $data, 'admin');
    }

    /**
     * Process course update
     * Route: PUT/POST /admin/courses/{id}/update
     */
    public function update($id) {
        $this->requireRole('admin');

        // Check if course exists
        $course = $this->courseModel->getCourseDetails($id, false);
        if (!$course) {
            return $this->redirectWithError(BASE_URL . 'admin/courses', 'Course not found.');
        }

        // Validate CSRF token
        if (!\CSRF::validateToken()) {
            error_log("CSRF validation failed in CourseController@update - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return $this->redirectWithError(BASE_URL . "admin/courses/{$id}/edit", 'Security validation failed.');
        }

        // Validation rules
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:full_course,short_course,lesson,skill_activity',
            'difficulty_level' => 'required|in:Beginner,Intermediate,Advanced'
        ];

        try {
            $data = $this->validate($this->input(), $rules);
        } catch (\Exception $e) {
            return $this->redirectWithError(BASE_URL . "admin/courses/{$id}/edit", 'Validation failed: ' . $e->getMessage());
        }

        // Sanitize course data
        $courseData = $this->sanitizeCourseData($data);

        // Update course
        $success = $this->courseModel->updateCourse($id, $courseData);

        if ($success) {
            $this->logger->log('info', 'Course updated', ['course_id' => $id, 'user_id' => $_SESSION['user_id']]);
            return $this->redirectWithSuccess(BASE_URL . 'admin/courses', 'Course updated successfully.');
        }

        return $this->redirectWithError(BASE_URL . "admin/courses/{$id}/edit", 'Failed to update course.');
    }

    /**
     * Delete course
     * Route: DELETE/POST /admin/courses/{id}/delete
     */
    public function destroy($id) {
        $this->requireRole('admin');

        // Check if course exists
        $course = $this->courseModel->getCourseDetails($id, false);
        if (!$course) {
            return $this->redirectWithError(BASE_URL . 'admin/courses', 'Course not found.');
        }

        // Validate CSRF token
        if (!\CSRF::validateToken()) {
            error_log("CSRF validation failed in CourseController@destroy - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return $this->redirectWithError(BASE_URL . 'admin/courses', 'Security validation failed.');
        }

        // Check for enrollments
        $enrollmentCount = $this->getEnrollmentCount($id);
        if ($enrollmentCount > 0) {
            return $this->redirectWithError(BASE_URL . 'admin/courses', "Cannot delete course with {$enrollmentCount} active enrollments.");
        }

        // Delete course
        $success = $this->courseModel->deleteCourse($id);

        if ($success) {
            $this->logger->log('info', 'Course deleted', ['course_id' => $id, 'user_id' => $_SESSION['user_id']]);
            return $this->redirectWithSuccess(BASE_URL . 'admin/courses', 'Course deleted successfully.');
        }

        return $this->redirectWithError(BASE_URL . 'admin/courses', 'Failed to delete course.');
    }

    // =================== AJAX STATUS METHODS ===================

    /**
     * Update course status (draft, active, archived)
     * Route: POST /admin/courses/{id}/status
     */
    public function updateStatus($id) {
        $this->requireRole('admin');

        // Validate CSRF token
        if (!\CSRF::validateToken()) {
            return $this->jsonError('Security validation failed.', 403);
        }

        $status = $this->input('status', '');
        $validStatuses = ['draft', 'active', 'archived'];

        if (!in_array($status, $validStatuses)) {
            return $this->jsonError('Invalid status value.', 400);
        }

        $success = $this->courseModel->updateCourseStatus($id, $status);

        if ($success) {
            $this->logger->log('info', 'Course status updated', ['course_id' => $id, 'status' => $status]);
            return $this->jsonSuccess(['status' => $status], 'Status updated successfully.');
        }

        return $this->jsonError('Failed to update status.', 500);
    }

    /**
     * Toggle featured status
     * Route: POST /admin/courses/{id}/featured
     */
    public function toggleFeatured($id) {
        $this->requireRole('admin');

        // Validate CSRF token
        if (!\CSRF::validateToken()) {
            return $this->jsonError('Security validation failed.', 403);
        }

        $featured = intval($this->input('featured', 0));

        $success = $this->courseModel->toggleFeatured($id, $featured);

        if ($success) {
            $this->logger->log('info', 'Course featured status toggled', ['course_id' => $id, 'featured' => $featured]);
            return $this->jsonSuccess(['featured' => $featured], 'Featured status updated.');
        }

        return $this->jsonError('Failed to update featured status.', 500);
    }

    // =================== HIERARCHY MANAGEMENT ===================

    /**
     * Get course modules
     * Route: GET /admin/courses/{id}/modules
     */
    public function getModules($id) {
        $this->requireRole('admin');

        $modules = $this->hierarchyModel->getCourseModules($id);

        return $this->jsonSuccess($modules);
    }

    /**
     * Create module
     * Route: POST /admin/courses/{id}/modules
     */
    public function createModule($courseId) {
        $this->requireRole('admin');

        if (!\CSRF::validateToken()) {
            return $this->jsonError('Security validation failed.', 403);
        }

        $moduleData = [
            'title' => $this->input('title', ''),
            'description' => $this->input('description', ''),
            'order_num' => intval($this->input('order_num', 1))
        ];

        if (empty($moduleData['title'])) {
            return $this->jsonError('Module title is required.', 400);
        }

        $moduleId = $this->hierarchyModel->createModule($courseId, $moduleData);

        if ($moduleId) {
            return $this->jsonSuccess(['module_id' => $moduleId], 'Module created successfully.');
        }

        return $this->jsonError('Failed to create module.', 500);
    }

    /**
     * Get course sections
     * Route: GET /admin/courses/{id}/sections
     */
    public function getSections($id) {
        $this->requireRole('admin');

        $sections = $this->courseModel->getCourseSections($id);

        return $this->jsonSuccess($sections);
    }

    /**
     * Create section
     * Route: POST /admin/courses/{id}/sections
     */
    public function createSection($courseId) {
        $this->requireRole('admin');

        if (!\CSRF::validateToken()) {
            return $this->jsonError('Security validation failed.', 403);
        }

        $sectionData = [
            'title' => trim($this->input('title', '')),
            'description' => trim($this->input('description', ''))
        ];

        if (empty($sectionData['title'])) {
            return $this->jsonError('Section title is required.', 400);
        }

        $sectionId = $this->courseModel->createSection($courseId, $sectionData);

        if ($sectionId) {
            return $this->jsonSuccess(['section_id' => $sectionId], 'Section created successfully.');
        }

        return $this->jsonError('Failed to create section.', 500);
    }

    // =================== HELPER METHODS ===================

    /**
     * Sanitize course data for security
     */
    private function sanitizeCourseData($data) {
        $sanitized = [];

        // Required fields
        $sanitized['title'] = trim($data['title'] ?? '');
        $sanitized['description'] = trim($data['description'] ?? '');
        $sanitized['type'] = trim($data['type'] ?? 'full_course');
        $sanitized['difficulty_level'] = trim($data['difficulty_level'] ?? 'Beginner');

        // Optional fields
        $sanitized['duration'] = trim($data['duration'] ?? '');
        $sanitized['image_path'] = trim($data['image_path'] ?? '');
        $sanitized['course_code'] = trim($data['course_code'] ?? '');
        $sanitized['status'] = trim($data['status'] ?? 'draft');

        // Boolean fields
        $sanitized['is_featured'] = isset($data['is_featured']) ? intval($data['is_featured']) : 0;
        $sanitized['is_published'] = isset($data['is_published']) ? intval($data['is_published']) : 0;

        return $sanitized;
    }

    /**
     * Generate unique course code
     */
    private function generateCourseCode($title, $type) {
        // Get first 3 letters of title
        $titlePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $title), 0, 3));

        // Get type prefix
        $typePrefix = [
            'full_course' => 'FC',
            'short_course' => 'SC',
            'lesson' => 'LN',
            'skill_activity' => 'SA'
        ][$type] ?? 'FC';

        // Generate random number
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$titlePrefix}-{$typePrefix}-{$random}";
    }

    /**
     * Get course types
     */
    private function getCourseTypes() {
        return [
            'full_course' => 'Full Course',
            'short_course' => 'Short Course',
            'lesson' => 'Lesson',
            'skill_activity' => 'Skill Activity'
        ];
    }

    /**
     * Get difficulty levels
     */
    private function getDifficultyLevels() {
        return ['Beginner', 'Intermediate', 'Advanced'];
    }

    /**
     * Get enrollment count for course
     */
    private function getEnrollmentCount($courseId) {
        $query = "SELECT COUNT(*) as count FROM user_enrollments WHERE course_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['count'];
        }

        return 0;
    }
}
