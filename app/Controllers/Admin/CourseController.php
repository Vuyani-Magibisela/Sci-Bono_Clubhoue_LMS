<?php
/**
 * Enhanced Course Controller for Sci-Bono Clubhouse LMS
 * Handles all course management operations including hierarchy management
 */

require_once __DIR__ . '/../../Models/Admin/CourseModel.php';
require_once __DIR__ . '/../../Models/EnrollmentModel.php';

class CourseController {
    private $conn;
    private $courseModel;
    private $enrollmentModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->courseModel = new CourseModel($conn);
        $this->enrollmentModel = new EnrollmentModel($conn);
    }

    // =================== COURSE MANAGEMENT ===================
    
    /**
     * Get all courses with optional filtering
     */
    public function getAllCourses($filters = []) {
        return $this->courseModel->getAllCourses($filters);
    }
    
    /**
     * Get detailed course information
     */
    public function getCourseDetails($courseId, $includeHierarchy = true) {
        return $this->courseModel->getCourseDetails($courseId, $includeHierarchy);
    }
    
    /**
     * Create a new course with validation
     */
    public function createCourse($courseData) {
        // Validate required fields
        $validation = $this->validateCourseData($courseData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Generate course code if not provided
        if (empty($courseData['course_code'])) {
            $courseData['course_code'] = $this->generateCourseCode($courseData['title'], $courseData['type']);
        }
        
        // Set defaults
        $courseData = $this->setDefaultCourseValues($courseData);
        
        $courseId = $this->courseModel->createCourse($courseData);
        
        if ($courseId) {
            // Create default structure based on course type
            $this->createDefaultCourseStructure($courseId, $courseData['type']);
            
            return ['success' => true, 'course_id' => $courseId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create course']];
    }
    
    /**
     * Update course information
     */
    public function updateCourse($courseId, $courseData) {
        $validation = $this->validateCourseData($courseData, $courseId);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $success = $this->courseModel->updateCourse($courseId, $courseData);
        return ['success' => $success];
    }
    
    /**
     * Delete a course
     */
    public function deleteCourse($courseId) {
        // Check if course has enrollments
        $enrollmentCount = $this->getEnrollmentCount($courseId);
        if ($enrollmentCount > 0) {
            return ['success' => false, 'error' => 'Cannot delete course with active enrollments'];
        }
        
        $success = $this->courseModel->deleteCourse($courseId);
        return ['success' => $success];
    }

    // =================== MODULE MANAGEMENT ===================
    
    /**
     * Get course modules
     */
    public function getCourseModules($courseId) {
        return $this->courseModel->getCourseModules($courseId);
    }
    
    /**
     * Create a new module
     */
    public function createModule($courseId, $moduleData) {
        $validation = $this->validateModuleData($moduleData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $moduleData = $this->setDefaultModuleValues($moduleData);
        $moduleId = $this->courseModel->createModule($courseId, $moduleData);
        
        if ($moduleId) {
            return ['success' => true, 'module_id' => $moduleId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create module']];
    }

    // =================== LESSON MANAGEMENT ===================
    
    /**
     * Create a new lesson
     */
    public function createLesson($lessonData) {
        $validation = $this->validateLessonData($lessonData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $lessonData = $this->setDefaultLessonValues($lessonData);
        $lessonId = $this->courseModel->createLesson($lessonData);
        
        if ($lessonId) {
            return ['success' => true, 'lesson_id' => $lessonId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create lesson']];
    }
    
    /**
     * Create lesson section
     */
    public function createLessonSection($lessonId, $sectionData) {
        $validation = $this->validateLessonSectionData($sectionData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $sectionData = $this->setDefaultSectionValues($sectionData);
        $sectionId = $this->courseModel->createLessonSection($lessonId, $sectionData);
        
        if ($sectionId) {
            return ['success' => true, 'section_id' => $sectionId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create lesson section']];
    }

/**
 * Delete Module Method for CourseController
 * Add this method to your existing CourseController class
 */

/**
 * Delete a module and all its related content
 * 
 * @param int $moduleId Module ID to delete
 * @param int $userId User ID performing the deletion (for authorization)
 * @return array Response with success status and message
 */
public function deleteModule($moduleId, $userId = null) {
    // Validate module ID
    if ($moduleId <= 0) {
        return [
            'success' => false, 
            'error' => 'Invalid module ID provided',
            'code' => 'INVALID_ID'
        ];
    }
    
    // Get module details first to check if it exists and get context
    $module = $this->courseModel->getModuleDetails($moduleId);
    
    if (!$module) {
        return [
            'success' => false, 
            'error' => 'Module not found',
            'code' => 'NOT_FOUND'
        ];
    }
    
    // Check user authorization (if user ID provided)
    if ($userId) {
        $canDelete = $this->canUserDeleteModule($userId, $module);
        if (!$canDelete['allowed']) {
            return [
                'success' => false, 
                'error' => $canDelete['reason'],
                'code' => 'UNAUTHORIZED'
            ];
        }
    }
    
    // Check for dependencies that might prevent deletion
    $dependencyCheck = $this->checkModuleDependencies($moduleId);
    if (!$dependencyCheck['can_delete']) {
        return [
            'success' => false, 
            'error' => 'Cannot delete module: ' . $dependencyCheck['reason'],
            'code' => 'HAS_DEPENDENCIES',
            'details' => $dependencyCheck
        ];
    }
    
    // Perform the deletion
    try {
        $success = $this->courseModel->deleteModule($moduleId);
        
        if ($success) {
            // Log the deletion for audit purposes
            $this->logModuleDeletion($moduleId, $module, $userId);
            
            return [
                'success' => true,
                'message' => 'Module "' . htmlspecialchars($module['title']) . '" deleted successfully',
                'module_title' => $module['title'],
                'course_id' => $module['course_id']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to delete module from database',
                'code' => 'DATABASE_ERROR'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error deleting module {$moduleId}: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'An unexpected error occurred while deleting the module',
            'code' => 'SYSTEM_ERROR'
        ];
    }
}

/**
 * Check if user is authorized to delete the module
 * 
 * @param int $userId User ID
 * @param array $module Module details
 * @return array Authorization result
 */
private function canUserDeleteModule($userId, $module) {
    // Get user details to check permissions
    $userQuery = "SELECT user_type FROM users WHERE id = ?";
    $stmt = $this->conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if (!$userResult || $userResult->num_rows === 0) {
        return [
            'allowed' => false,
            'reason' => 'User not found'
        ];
    }
    
    $user = $userResult->fetch_assoc();
    
    // Check permissions based on user type
    switch ($user['user_type']) {
        case 'admin':
            // Admins can delete any module
            return ['allowed' => true];
            
        case 'mentor':
            // Mentors can only delete modules from courses they created
            $courseQuery = "SELECT created_by FROM courses WHERE id = ?";
            $stmt = $this->conn->prepare($courseQuery);
            $stmt->bind_param("i", $module['course_id']);
            $stmt->execute();
            $courseResult = $stmt->get_result();
            
            if ($courseResult && $courseResult->num_rows > 0) {
                $course = $courseResult->fetch_assoc();
                if ($course['created_by'] == $userId) {
                    return ['allowed' => true];
                }
            }
            
            return [
                'allowed' => false,
                'reason' => 'You can only delete modules from courses you created'
            ];
            
        default:
            return [
                'allowed' => false,
                'reason' => 'Insufficient permissions to delete modules'
            ];
    }
}

/**
 * Check for dependencies that might prevent module deletion
 * 
 * @param int $moduleId Module ID
 * @return array Dependency check result
 */
private function checkModuleDependencies($moduleId) {
    $dependencies = [];
    
    // Check for user enrollments/progress in this module
    $progressQuery = "SELECT COUNT(*) as count FROM user_progress 
                     WHERE module_id = ? AND (completion_percentage > 0 OR status != 'not_started')";
    $stmt = $this->conn->prepare($progressQuery);
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    $progressCount = $progressResult->fetch_assoc()['count'] ?? 0;
    
    if ($progressCount > 0) {
        $dependencies[] = "{$progressCount} users have progress in this module";
    }
    
    // Check for activity submissions
    $submissionsQuery = "SELECT COUNT(*) as count FROM activity_submissions 
                        WHERE activity_id IN (SELECT id FROM course_activities WHERE module_id = ?)";
    $stmt = $this->conn->prepare($submissionsQuery);
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $submissionsResult = $stmt->get_result();
    $submissionsCount = $submissionsResult->fetch_assoc()['count'] ?? 0;
    
    if ($submissionsCount > 0) {
        $dependencies[] = "{$submissionsCount} activity submissions will be lost";
    }
    
    // Check for assessment attempts
    $assessmentQuery = "SELECT COUNT(*) as count FROM assessment_attempts 
                       WHERE assessment_id IN (SELECT id FROM course_assessments WHERE module_id = ?)";
    $stmt = $this->conn->prepare($assessmentQuery);
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $assessmentResult = $stmt->get_result();
    $assessmentCount = $assessmentResult->fetch_assoc()['count'] ?? 0;
    
    if ($assessmentCount > 0) {
        $dependencies[] = "{$assessmentCount} assessment attempts will be lost";
    }
    
    // Determine if deletion should be allowed
    $canDelete = true;
    $reason = '';
    
    if (!empty($dependencies)) {
        // For now, we'll warn but allow deletion
        // You might want to make this more restrictive based on your needs
        $canDelete = true; // Set to false if you want to prevent deletion with dependencies
        $reason = implode(', ', $dependencies);
    }
    
    return [
        'can_delete' => $canDelete,
        'reason' => $reason,
        'dependencies' => $dependencies,
        'user_progress_count' => $progressCount,
        'submissions_count' => $submissionsCount,
        'assessment_attempts_count' => $assessmentCount
    ];
}

/**
 * Log module deletion for audit purposes
 * 
 * @param int $moduleId Module ID
 * @param array $module Module details
 * @param int $userId User who performed the deletion
 */
private function logModuleDeletion($moduleId, $module, $userId) {
    $logData = [
        'action' => 'module_deleted',
        'module_id' => $moduleId,
        'module_title' => $module['title'],
        'course_id' => $module['course_id'],
        'course_title' => $module['course_title'] ?? 'Unknown',
        'deleted_by' => $userId,
        'timestamp' => date('Y-m-d H:i:s'),
        'lesson_count' => count($module['lessons'] ?? []),
        'activity_count' => count($module['activities'] ?? [])
    ];
    
    // Log to file (you might want to implement a proper audit log table)
    error_log("MODULE DELETION: " . json_encode($logData));
    
    // Optionally, insert into an audit log table if you have one
    /*
    $auditSql = "INSERT INTO audit_log (action, entity_type, entity_id, user_id, details, created_at) 
                 VALUES (?, 'module', ?, ?, ?, NOW())";
    $stmt = $this->conn->prepare($auditSql);
    $details = json_encode($logData);
    $stmt->bind_param("siis", $logData['action'], $moduleId, $userId, $details);
    $stmt->execute();
    */
}

/**
 * Delete multiple modules (bulk operation)
 * 
 * @param array $moduleIds Array of module IDs
 * @param int $userId User performing the deletion
 * @return array Results for each module deletion
 */
public function deleteMultipleModules($moduleIds, $userId = null) {
    if (!is_array($moduleIds) || empty($moduleIds)) {
        return [
            'success' => false,
            'error' => 'No modules specified for deletion'
        ];
    }
    
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    
    foreach ($moduleIds as $moduleId) {
        $result = $this->deleteModule($moduleId, $userId);
        $results[] = array_merge($result, ['module_id' => $moduleId]);
        
        if ($result['success']) {
            $successCount++;
        } else {
            $failureCount++;
        }
    }
    
    return [
        'success' => $failureCount === 0,
        'total_processed' => count($moduleIds),
        'successful' => $successCount,
        'failed' => $failureCount,
        'results' => $results,
        'summary' => $failureCount === 0 
            ? "All {$successCount} modules deleted successfully"
            : "{$successCount} modules deleted, {$failureCount} failed"
    ];
}

/**
 * Soft delete module (mark as deleted without removing from database)
 * Alternative approach if you prefer to keep deleted content for recovery
 * 
 * @param int $moduleId Module ID
 * @param int $userId User performing the deletion
 * @return array Response with success status
 */
public function softDeleteModule($moduleId, $userId = null) {
    if ($moduleId <= 0) {
        return [
            'success' => false, 
            'error' => 'Invalid module ID provided'
        ];
    }
    
    // Check if module exists
    $module = $this->courseModel->getModuleDetails($moduleId);
    if (!$module) {
        return [
            'success' => false, 
            'error' => 'Module not found'
        ];
    }
    
    try {
        // Mark as deleted (you'll need to add a 'deleted_at' column to your table)
        $sql = "UPDATE course_modules SET 
                is_published = 0, 
                deleted_at = NOW(), 
                deleted_by = ?,
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $moduleId);
        $success = $stmt->execute();
        
        if ($success) {
            // Also soft delete all lessons and activities in this module
            $this->conn->query("UPDATE course_lessons SET deleted_at = NOW() WHERE module_id = {$moduleId}");
            $this->conn->query("UPDATE course_activities SET deleted_at = NOW() WHERE module_id = {$moduleId}");
            
            return [
                'success' => true,
                'message' => 'Module marked as deleted successfully',
                'module_title' => $module['title']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to mark module as deleted'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error soft deleting module {$moduleId}: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'An error occurred while deleting the module'
        ];
    }
}


    // =================== ACTIVITY MANAGEMENT ===================
    
    /**
     * Create a new activity
     */
    public function createActivity($activityData) {
        $validation = $this->validateActivityData($activityData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $activityData = $this->setDefaultActivityValues($activityData);
        $activityId = $this->courseModel->createActivity($activityData);
        
        if ($activityId) {
            return ['success' => true, 'activity_id' => $activityId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create activity']];
    }
    /**
     * Update an existing activity
     * 
     * @param int $activityId Activity ID
     * @param array $activityData Updated activity data
     * @return array Result with success status
     */
    public function updateActivity($activityId, $activityData) {
        $validation = $this->validateActivityData($activityData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $success = $this->courseModel->updateActivity($activityId, $activityData);
        return ['success' => $success];
    }
    
    /**
     * Delete an activity
     * 
     * @param int $activityId Activity ID
     * @return array Result with success status
     */
    public function deleteActivity($activityId) {
        // Validate activity ID
        if ($activityId <= 0) {
            return ['success' => false, 'error' => 'Invalid activity ID'];
        }
        
        $success = $this->courseModel->deleteActivity($activityId);
        return ['success' => $success];
    }
    
    /**
     * Get activities based on filters
     * 
     * @param array $filters Filter criteria
     * @return array List of activities
     */
    public function getActivities($filters = []) {
        // Build the query conditions based on filters
        $whereConditions = ['1=1'];
        $params = [];
        $types = '';
        
        if (!empty($filters['course_id']) && $filters['course_id'] > 0) {
            $whereConditions[] = 'ca.course_id = ?';
            $params[] = $filters['course_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['module_id']) && $filters['module_id'] > 0) {
            $whereConditions[] = 'ca.module_id = ?';
            $params[] = $filters['module_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['lesson_id']) && $filters['lesson_id'] > 0) {
            $whereConditions[] = 'ca.lesson_id = ?';
            $params[] = $filters['lesson_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['activity_type']) && $filters['activity_type'] !== 'all') {
            $whereConditions[] = 'ca.activity_type = ?';
            $params[] = $filters['activity_type'];
            $types .= 's';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT ca.*, c.title as course_title, cm.title as module_title, cl.title as lesson_title,
                (SELECT COUNT(*) FROM activity_submissions WHERE activity_id = ca.id) as submission_count
                FROM course_activities ca
                LEFT JOIN courses c ON ca.course_id = c.id
                LEFT JOIN course_modules cm ON ca.module_id = cm.id
                LEFT JOIN course_lessons cl ON ca.lesson_id = cl.id
                WHERE $whereClause
                ORDER BY ca.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
        }
        
        $activities = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
        
        return $activities;
    }
    
    /**
     * Update module information
     * 
     * @param int $moduleId Module ID
     * @param array $moduleData Updated module data
     * @return array Result with success status
     */
    public function updateModule($moduleId, $moduleData) {
        $validation = $this->validateModuleData($moduleData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $success = $this->courseModel->updateModule($moduleId, $moduleData);
        return ['success' => $success];
    }
    
    /**
     * Update lesson information
     * 
     * @param int $lessonId Lesson ID
     * @param array $lessonData Updated lesson data
     * @return array Result with success status
     */
    public function updateLesson($lessonId, $lessonData) {
        $validation = $this->validateLessonData($lessonData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $success = $this->courseModel->updateLesson($lessonId, $lessonData);
        return ['success' => $success];
    }
    
    /**
     * Delete a lesson
     * 
     * @param int $lessonId Lesson ID
     * @return array Result with success status
     */
    public function deleteLesson($lessonId) {
        // Validate lesson ID
        if ($lessonId <= 0) {
            return ['success' => false, 'error' => 'Invalid lesson ID'];
        }
        
        $success = $this->courseModel->deleteLesson($lessonId);
        return ['success' => $success];
    }
    
    /**
     * Update skill activity information
     * 
     * @param int $skillActivityId Skill activity ID
     * @param array $skillActivityData Updated skill activity data
     * @return array Result with success status
     */
    public function updateSkillActivity($skillActivityId, $skillActivityData) {
        $validation = $this->validateSkillActivityData($skillActivityData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $success = $this->courseModel->updateSkillActivity($skillActivityId, $skillActivityData);
        return ['success' => $success];
    }
    
    /**
     * Delete a skill activity
     * 
     * @param int $skillActivityId Skill activity ID
     * @return array Result with success status
     */
    public function deleteSkillActivity($skillActivityId) {
        // Validate skill activity ID
        if ($skillActivityId <= 0) {
            return ['success' => false, 'error' => 'Invalid skill activity ID'];
        }
        
        $success = $this->courseModel->deleteSkillActivity($skillActivityId);
        return ['success' => $success];
    }
    
    /**
     * Get activity details
     * 
     * @param int $activityId Activity ID
     * @return array|null Activity details or null if not found
     */
    public function getActivityDetails($activityId) {
        return $this->courseModel->getActivityDetails($activityId);
    }
    
    /**
     * Get module details
     * 
     * @param int $moduleId Module ID
     * @return array|null Module details or null if not found
     */
    public function getModuleDetails($moduleId) {
        return $this->courseModel->getModuleDetails($moduleId);
    }
    
    /**
     * Get lesson details
     * 
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
    public function getLessonDetails($lessonId) {
        return $this->courseModel->getLessonDetails($lessonId);
    }
    
    /**
     * Update order for modules
     * 
     * @param array $moduleOrders Array of module IDs and their order
     * @return array Result with success status
     */
    public function updateModuleOrder($moduleOrders) {
        if (empty($moduleOrders) || !is_array($moduleOrders)) {
            return ['success' => false, 'error' => 'Invalid module order data'];
        }
        
        $success = $this->courseModel->updateModuleOrder($moduleOrders);
        return ['success' => $success];
    }
    
    /**
     * Update order for lessons
     * 
     * @param array $lessonOrders Array of lesson IDs and their order
     * @return array Result with success status
     */
    public function updateLessonOrder($lessonOrders) {
        if (empty($lessonOrders) || !is_array($lessonOrders)) {
            return ['success' => false, 'error' => 'Invalid lesson order data'];
        }
        
        $success = $this->courseModel->updateLessonOrder($lessonOrders);
        return ['success' => $success];
    }
    
    /**
     * Update order for activities
     * 
     * @param array $activityOrders Array of activity IDs and their order
     * @return array Result with success status
     */
    public function updateActivityOrder($activityOrders) {
        if (empty($activityOrders) || !is_array($activityOrders)) {
            return ['success' => false, 'error' => 'Invalid activity order data'];
        }
        
        $success = $this->courseModel->updateActivityOrder($activityOrders);
        return ['success' => $success];
    }

    // =================== SKILL ACTIVITIES MANAGEMENT ===================
    
    /**
     * Get all skill activities
     */
    public function getAllSkillActivities($filters = []) {
        return $this->courseModel->getAllSkillActivities($filters);
    }
    
    /**
     * Get skill activity details
     */
    public function getSkillActivityDetails($skillActivityId) {
        return $this->courseModel->getSkillActivityDetails($skillActivityId);
    }
    
    /**
     * Create skill activity
     */
    public function createSkillActivity($skillActivityData) {
        $validation = $this->validateSkillActivityData($skillActivityData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $skillActivityData = $this->setDefaultSkillActivityValues($skillActivityData);
        $skillActivityId = $this->courseModel->createSkillActivity($skillActivityData);
        
        if ($skillActivityId) {
            return ['success' => true, 'skill_activity_id' => $skillActivityId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create skill activity']];
    }

    // =================== PROGRESS AND ENROLLMENT ===================
    
    /**
     * Enroll user in course
     */
    public function enrollUser($userId, $courseId) {
        if ($this->enrollmentModel->isUserEnrolled($userId, $courseId)) {
            return ['success' => false, 'error' => 'User already enrolled'];
        }
        
        $success = $this->enrollmentModel->enrollUser($userId, $courseId);
        
        if ($success) {
            // Initialize progress tracking
            $this->initializeUserProgress($userId, $courseId);
        }
        
        return ['success' => $success];
    }
    
    /**
     * Get user progress
     */
    public function getUserProgress($userId, $courseId) {
        return $this->courseModel->getUserCourseProgress($userId, $courseId);
    }
    
    /**
     * Update user progress
     */
    public function updateUserProgress($progressData) {
        return $this->courseModel->updateUserProgress($progressData);
    }

    // =================== VALIDATION METHODS ===================
    
    /**
     * Validate course data
     */
    private function validateCourseData($data, $courseId = null) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Course title is required';
        }
        
        if (empty($data['type']) || !in_array($data['type'], ['full_course', 'short_course', 'lesson', 'skill_activity'])) {
            $errors['type'] = 'Valid course type is required';
        }
        
        if (empty($data['difficulty_level']) || !in_array($data['difficulty_level'], ['Beginner', 'Intermediate', 'Advanced'])) {
            $errors['difficulty_level'] = 'Valid difficulty level is required';
        }
        
        if (empty($data['created_by']) || !is_numeric($data['created_by'])) {
            $errors['created_by'] = 'Valid creator ID is required';
        }
        
        if (!empty($data['pass_percentage']) && ($data['pass_percentage'] < 0 || $data['pass_percentage'] > 100)) {
            $errors['pass_percentage'] = 'Pass percentage must be between 0 and 100';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate module data
     */
    private function validateModuleData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Module title is required';
        }
        
        if (!empty($data['pass_percentage']) && ($data['pass_percentage'] < 0 || $data['pass_percentage'] > 100)) {
            $errors['pass_percentage'] = 'Pass percentage must be between 0 and 100';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate lesson data
     */
    private function validateLessonData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Lesson title is required';
        }
        
        if (empty($data['lesson_type']) || !in_array($data['lesson_type'], ['text', 'video', 'quiz', 'assignment', 'interactive'])) {
            $errors['lesson_type'] = 'Valid lesson type is required';
        }
        
        if ($data['lesson_type'] === 'video' && empty($data['video_url'])) {
            $errors['video_url'] = 'Video URL is required for video lessons';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate lesson section data
     */
    private function validateLessonSectionData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Section title is required';
        }
        
        if (empty($data['section_type']) || !in_array($data['section_type'], ['text', 'video', 'interactive', 'quiz', 'assignment'])) {
            $errors['section_type'] = 'Valid section type is required';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate activity data
     */
    private function validateActivityData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Activity title is required';
        }
        
        if (empty($data['activity_type']) || !in_array($data['activity_type'], 
            ['practical', 'assignment', 'project', 'quiz', 'assessment', 'skill_exercise'])) {
            $errors['activity_type'] = 'Valid activity type is required';
        }
        
        if (!empty($data['max_points']) && !is_numeric($data['max_points'])) {
            $errors['max_points'] = 'Max points must be numeric';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate skill activity data
     */
    private function validateSkillActivityData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Skill activity title is required';
        }
        
        if (empty($data['technique_taught'])) {
            $errors['technique_taught'] = 'Technique taught is required';
        }
        
        if (empty($data['created_by']) || !is_numeric($data['created_by'])) {
            $errors['created_by'] = 'Valid creator ID is required';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    // =================== DEFAULT VALUE SETTERS ===================
    
    /**
     * Set default values for course data
     */
    private function setDefaultCourseValues($data) {
        $defaults = [
            'status' => 'draft',
            'is_published' => 0,
            'is_featured' => 0,
            'pass_percentage' => 70.0,
            'enrollment_count' => 0,
            'display_order' => 0
        ];
        
        return array_merge($defaults, $data);
    }
    
    /**
     * Set default values for module data
     */
    private function setDefaultModuleValues($data) {
        $defaults = [
            'is_published' => 0,
            'pass_percentage' => 70.0
        ];
        
        return array_merge($defaults, $data);
    }
    
    /**
     * Set default values for lesson data
     */
    private function setDefaultLessonValues($data) {
        $defaults = [
            'is_published' => 0,
            'difficulty_level' => 'Beginner',
            'pass_percentage' => 70.0,
            'duration_minutes' => 30
        ];
        
        return array_merge($defaults, $data);
    }
    
    /**
     * Set default values for section data
     */
    private function setDefaultSectionValues($data) {
        $defaults = [
            'is_published' => 0,
            'estimated_duration_minutes' => 15
        ];
        
        return array_merge($defaults, $data);
    }
    
    /**
     * Set default values for activity data
     */
    private function setDefaultActivityValues($data) {
        $defaults = [
            'max_points' => 100,
            'pass_points' => 70,
            'auto_grade' => 0,
            'is_published' => 0,
            'submission_type' => 'text'
        ];
        
        return array_merge($defaults, $data);
    }
    
    /**
     * Set default values for skill activity data
     */
    private function setDefaultSkillActivityValues($data) {
        $defaults = [
            'difficulty_level' => 'Beginner',
            'is_published' => 0,
            'is_featured' => 0,
            'view_count' => 0,
            'completion_count' => 0
        ];
        
        return array_merge($defaults, $data);
    }

    // =================== UTILITY METHODS ===================
    
    /**
     * Generate course code
     */
    private function generateCourseCode($title, $type) {
        $typePrefix = [
            'full_course' => 'FC',
            'short_course' => 'SC',
            'lesson' => 'LN',
            'skill_activity' => 'SA'
        ];
        
        $prefix = $typePrefix[$type] ?? 'GN';
        $titleCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $title), 0, 4));
        $timestamp = substr(time(), -4);
        
        return $prefix . '-' . $titleCode . $timestamp;
    }
    
    /**
     * Create default course structure based on type
     */
    private function createDefaultCourseStructure($courseId, $type) {
        switch ($type) {
            case 'full_course':
            case 'short_course':
                // Create a default module
                $moduleData = [
                    'title' => 'Introduction Module',
                    'description' => 'Getting started with this course',
                    'is_published' => 1
                ];
                $this->createModule($courseId, $moduleData);
                break;
                
            case 'lesson':
                // Create default lesson sections
                $sectionData = [
                    'title' => 'Lesson Overview',
                    'content' => 'Welcome to this lesson!',
                    'section_type' => 'text',
                    'is_published' => 1
                ];
                // Note: We'd need the lesson ID for this, so this might be handled differently
                break;
        }
    }
    
    /**
     * Initialize user progress tracking
     */
    private function initializeUserProgress($userId, $courseId) {
        $progressData = [
            'user_id' => $userId,
            'course_id' => $courseId,
            'module_id' => null,
            'lesson_id' => null,
            'lesson_section_id' => null,
            'activity_id' => null,
            'progress_type' => 'course',
            'completion_percentage' => 0.0,
            'total_points_earned' => 0,
            'total_points_possible' => 0,
            'status' => 'not_started',
            'last_accessed' => date('Y-m-d H:i:s'),
            'completed_at' => null
        ];
        
        return $this->courseModel->updateUserProgress($progressData);
    }
    
    /**
     * Get enrollment count for a course
     */
    private function getEnrollmentCount($courseId) {
        $sql = "SELECT COUNT(*) as count FROM user_enrollments WHERE course_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }
    
    /**
     * Format course type for display
     */
    public function formatCourseType($type) {
        $types = [
            'full_course' => 'Full Course',
            'short_course' => 'Short Course',
            'lesson' => 'Lesson',
            'skill_activity' => 'Skill Activity'
        ];
        
        return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
    
    /**
     * Get difficulty class for styling
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
     * Get status class for styling
     */
    public function getStatusClass($status) {
        $classes = [
            'active' => 'badge-success',
            'draft' => 'badge-warning',
            'archived' => 'badge-secondary',
            'inactive' => 'badge-danger'
        ];
        
        return $classes[$status] ?? 'badge-primary';
    }
    
    /**
     * Calculate estimated total duration for a course
     */
    public function calculateTotalDuration($courseId) {
        $course = $this->getCourseDetails($courseId, true);
        
        $totalMinutes = 0;
        
        // Add module durations
        if (!empty($course['modules'])) {
            foreach ($course['modules'] as $module) {
                if (!empty($module['estimated_duration_hours'])) {
                    $totalMinutes += $module['estimated_duration_hours'] * 60;
                }
                
                // Add lesson durations within modules
                if (!empty($module['lessons'])) {
                    foreach ($module['lessons'] as $lesson) {
                        $totalMinutes += $lesson['estimated_duration_minutes'] ?? 0;
                    }
                }
            }
        }
        
        // Add standalone lesson durations
        if (!empty($course['standalone_lessons'])) {
            foreach ($course['standalone_lessons'] as $lesson) {
                $totalMinutes += $lesson['estimated_duration_minutes'] ?? 0;
            }
        }
        
        return $totalMinutes;
    }
    
    /**
     * Get course statistics
     */
    public function getCourseStatistics($courseId) {
        $course = $this->getCourseDetails($courseId, true);
        
        $stats = [
            'total_modules' => count($course['modules'] ?? []),
            'total_lessons' => count($course['standalone_lessons'] ?? []),
            'total_activities' => count($course['standalone_activities'] ?? []),
            'total_assessments' => count($course['assessments'] ?? []),
            'total_duration_minutes' => $this->calculateTotalDuration($courseId),
            'enrollment_count' => $course['enrollment_count'] ?? 0,
            'completion_rate' => $this->calculateCompletionRate($courseId),
            'average_rating' => $course['average_rating'] ?? 0
        ];
        
        // Count lessons within modules
        if (!empty($course['modules'])) {
            foreach ($course['modules'] as $module) {
                $stats['total_lessons'] += count($module['lessons'] ?? []);
                $stats['total_activities'] += count($module['activities'] ?? []);
                $stats['total_assessments'] += count($module['assessments'] ?? []);
            }
        }
        
        return $stats;
    }
    
    /**
     * Calculate completion rate for a course
     */
    private function calculateCompletionRate($courseId) {
        $sql = "SELECT 
                    COUNT(*) as total_enrolled,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed
                FROM user_enrollments 
                WHERE course_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total_enrolled'] == 0) {
            return 0;
        }
        
        return ($row['completed'] / $row['total_enrolled']) * 100;
    }
}
?>