<?php
/**
 * Enhanced LMS Utilities for Sci-Bono Clubhouse LMS
 * Provides utility functions for the enhanced course management system
 */

/**
 * Get appropriate icon for lesson type
 * 
 * @param string $lessonType The type of lesson
 * @return string Font Awesome icon class
 */
function getLessonIcon($lessonType) {
    $icons = [
        'text' => 'fa-file-alt',
        'video' => 'fa-video',
        'quiz' => 'fa-question-circle',
        'assignment' => 'fa-tasks',
        'interactive' => 'fa-laptop-code'
    ];
    
    return $icons[$lessonType] ?? 'fa-file';
}

/**
 * Get appropriate icon for activity type
 * 
 * @param string $activityType The type of activity
 * @return string Font Awesome icon class
 */
function getActivityIcon($activityType) {
    $icons = [
        'practical' => 'fa-tools',
        'assignment' => 'fa-tasks',
        'project' => 'fa-project-diagram',
        'quiz' => 'fa-question-circle',
        'assessment' => 'fa-clipboard-check',
        'skill_exercise' => 'fa-dumbbell'
    ];
    
    return $icons[$activityType] ?? 'fa-tasks';
}

/**
 * Get appropriate icon for module type
 * 
 * @param string $moduleType The type of module
 * @return string Font Awesome icon class
 */
function getModuleIcon($moduleType = 'default') {
    $icons = [
        'introduction' => 'fa-flag-checkered',
        'core' => 'fa-cog',
        'advanced' => 'fa-rocket',
        'assessment' => 'fa-clipboard-check',
        'project' => 'fa-project-diagram',
        'default' => 'fa-folder'
    ];
    
    return $icons[$moduleType] ?? 'fa-folder';
}

/**
 * Format course type for display
 * 
 * @param string $type Course type
 * @return string Formatted course type
 */
function formatCourseType($type) {
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
 * 
 * @param string $level Difficulty level
 * @return string CSS class
 */
function getDifficultyClass($level) {
    $classes = [
        'Beginner' => 'badge-success',
        'Intermediate' => 'badge-warning',
        'Advanced' => 'badge-danger'
    ];
    
    return $classes[$level] ?? 'badge-primary';
}

/**
 * Get status class for styling
 * 
 * @param string $status Status
 * @return string CSS class
 */
function getStatusClass($status) {
    $classes = [
        'active' => 'badge-success',
        'draft' => 'badge-warning',
        'archived' => 'badge-secondary',
        'inactive' => 'badge-danger'
    ];
    
    return $classes[$status] ?? 'badge-primary';
}

/**
 * Format duration from minutes to human readable format
 * 
 * @param int $minutes Duration in minutes
 * @return string Formatted duration
 */
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    } elseif ($minutes < 1440) { // Less than 24 hours
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours . 'h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . 'm' : '');
    } else {
        $days = floor($minutes / 1440);
        $remainingHours = floor(($minutes % 1440) / 60);
        return $days . 'd' . ($remainingHours > 0 ? ' ' . $remainingHours . 'h' : '');
    }
}

/**
 * Calculate completion percentage
 * 
 * @param int $completed Number of completed items
 * @param int $total Total number of items
 * @return float Completion percentage
 */
function calculateCompletionPercentage($completed, $total) {
    if ($total == 0) {
        return 0;
    }
    
    return round(($completed / $total) * 100, 1);
}

/**
 * Generate course code
 * 
 * @param string $title Course title
 * @param string $type Course type
 * @return string Generated course code
 */
function generateCourseCode($title, $type) {
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
 * Validate course data
 * 
 * @param array $data Course data to validate
 * @return array Validation result with 'valid' boolean and 'errors' array
 */
function validateCourseData($data) {
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
    
    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Sanitize HTML content for lesson/activity content
 * 
 * @param string $content HTML content to sanitize
 * @return string Sanitized HTML content
 */
function sanitizeContent($content) {
    // Allow specific HTML tags for rich content
    $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre>';
    
    return strip_tags($content, $allowedTags);
}

/**
 * Generate breadcrumb navigation for course hierarchy
 * 
 * @param array $hierarchy Array containing course, module, lesson info
 * @return string HTML breadcrumb
 */
function generateBreadcrumb($hierarchy) {
    $breadcrumb = '<nav class="breadcrumb">';
    
    if (!empty($hierarchy['course'])) {
        $breadcrumb .= '<a href="course.php?id=' . $hierarchy['course']['id'] . '">' . 
                      htmlspecialchars($hierarchy['course']['title']) . '</a>';
    }
    
    if (!empty($hierarchy['module'])) {
        $breadcrumb .= ' <i class="fas fa-chevron-right"></i> ' . 
                      '<span>' . htmlspecialchars($hierarchy['module']['title']) . '</span>';
    }
    
    if (!empty($hierarchy['lesson'])) {
        $breadcrumb .= ' <i class="fas fa-chevron-right"></i> ' . 
                      '<span>' . htmlspecialchars($hierarchy['lesson']['title']) . '</span>';
    }
    
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}

/**
 * Check if user has permission to access course content
 * 
 * @param int $userId User ID
 * @param int $courseId Course ID
 * @param string $action Action being performed
 * @return bool Whether user has permission
 */
function hasPermission($userId, $courseId, $action = 'view') {
    global $conn;
    
    // Get user type
    $userQuery = "SELECT user_type FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    
    if (!$user) {
        return false;
    }
    
    // Admin and mentor always have permission
    if (in_array($user['user_type'], ['admin', 'mentor'])) {
        return true;
    }
    
    // For members, check enrollment for view/participate actions
    if ($action === 'view' || $action === 'participate') {
        $enrollmentQuery = "SELECT id FROM user_enrollments WHERE user_id = ? AND course_id = ?";
        $enrollmentStmt = $conn->prepare($enrollmentQuery);
        $enrollmentStmt->bind_param("ii", $userId, $courseId);
        $enrollmentStmt->execute();
        $enrollmentResult = $enrollmentStmt->get_result();
        
        return $enrollmentResult->num_rows > 0;
    }
    
    // For management actions, only admin/mentor allowed
    return false;
}

/**
 * Get learning path suggestions based on completed courses
 * 
 * @param int $userId User ID
 * @return array Suggested courses/paths
 */
function getLearningPathSuggestions($userId) {
    global $conn;
    
    // Get completed courses
    $completedQuery = "SELECT c.* FROM courses c
                      JOIN user_enrollments ue ON c.id = ue.course_id
                      WHERE ue.user_id = ? AND ue.completed = 1";
    $completedStmt = $conn->prepare($completedQuery);
    $completedStmt->bind_param("i", $userId);
    $completedStmt->execute();
    $completedResult = $completedStmt->get_result();
    
    $completedCourses = [];
    while ($course = $completedResult->fetch_assoc()) {
        $completedCourses[] = $course;
    }
    
    // Simple suggestion logic - recommend courses of next difficulty level
    // In a real implementation, this would be more sophisticated
    $suggestions = [];
    
    // Count completed courses by difficulty
    $beginnerCount = count(array_filter($completedCourses, fn($c) => $c['difficulty_level'] === 'Beginner'));
    $intermediateCount = count(array_filter($completedCourses, fn($c) => $c['difficulty_level'] === 'Intermediate'));
    
    // Suggest next level courses
    if ($beginnerCount >= 2 && $intermediateCount === 0) {
        // Suggest intermediate courses
        $suggestionQuery = "SELECT * FROM courses 
                           WHERE difficulty_level = 'Intermediate' 
                           AND is_published = 1 
                           AND id NOT IN (SELECT course_id FROM user_enrollments WHERE user_id = ?)
                           LIMIT 3";
        $suggestionStmt = $conn->prepare($suggestionQuery);
        $suggestionStmt->bind_param("i", $userId);
        $suggestionStmt->execute();
        $suggestionResult = $suggestionStmt->get_result();
        
        while ($course = $suggestionResult->fetch_assoc()) {
            $suggestions[] = $course;
        }
    }
    
    return $suggestions;
}

/**
 * Log user activity for analytics
 * 
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string $entityType Type of entity (course, lesson, activity)
 * @param int $entityId ID of the entity
 * @param array $metadata Additional metadata
 * @return bool Success status
 */
function logUserActivity($userId, $action, $entityType, $entityId, $metadata = []) {
    global $conn;
    
    $metadataJson = json_encode($metadata);
    
    $sql = "INSERT INTO user_activity_log (user_id, action, entity_type, entity_id, metadata, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issis", $userId, $action, $entityType, $entityId, $metadataJson);
        return $stmt->execute();
    }
    
    return false;
}
?>