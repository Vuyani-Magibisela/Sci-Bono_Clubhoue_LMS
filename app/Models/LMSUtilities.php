<?php
/**
 * Utility functions for the Clubhouse LMS Learning System
 * 
 * Contains helper functions used across the learning system components
 * 
 * @package ClubhouseLMS
 */

/**
 * Format course type for display
 * 
 * Converts course type codes to user-friendly display format
 * 
 * @param string $type Course type code
 * @return string Formatted course type text
 */
function formatCourseType($type) {
    $types = [
        'robotics' => 'Robotics',
        'coding' => 'Coding & Programming',
        'design' => 'Digital Design',
        'video' => 'Video Production',
        'art' => 'Digital Art',
        'web' => 'Web Development',
        'game' => 'Game Development',
        'ai' => 'Artificial Intelligence',
        'electronics' => 'Electronics',
        'soft_skills' => 'Soft Skills',
        'life_skills' => 'Life Skills',
        'computer_basics' => 'Computer Basics',
        'ftc' => 'FTC Robotics',
        'fll' => 'FLL Robotics'
    ];
    
    return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * Get appropriate Font Awesome icon for lesson type
 * 
 * Returns the appropriate Font Awesome icon class based on the lesson type
 * 
 * @param string $lessonType Type of lesson
 * @return string Font Awesome icon class
 */
function getLessonIcon($lessonType) {
    $icons = [
        'video' => 'fa-video',
        'text' => 'fa-file-alt',
        'quiz' => 'fa-question-circle',
        'assignment' => 'fa-tasks',
        'exercise' => 'fa-laptop-code',
        'discussion' => 'fa-comments',
        'presentation' => 'fa-desktop',
        'project' => 'fa-project-diagram',
        'resource' => 'fa-download',
        'external' => 'fa-external-link-alt'
    ];
    
    return $icons[$lessonType] ?? 'fa-file';
}

/**
 * Format duration in minutes to human-readable text
 * 
 * Converts raw minutes to hours and minutes format
 * 
 * @param int $minutes Total duration in minutes
 * @return string Formatted duration text
 */
function formatDuration($minutes) {
    if ($minutes < 1) {
        return 'Less than 1 minute';
    }
    
    if ($minutes < 60) {
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    $result = $hours . ' hour' . ($hours != 1 ? 's' : '');
    
    if ($mins > 0) {
        $result .= ' ' . $mins . ' minute' . ($mins != 1 ? 's' : '');
    }
    
    return $result;
}

/**
 * Calculate total course duration
 * 
 * Sums up the duration of all lessons in a course
 * 
 * @param array $sections Course sections containing lessons
 * @return int Total duration in minutes
 */
function calculateTotalDuration($sections) {
    $totalMinutes = 0;
    
    foreach ($sections as $section) {
        if (isset($section['lessons']) && is_array($section['lessons'])) {
            foreach ($section['lessons'] as $lesson) {
                $totalMinutes += isset($lesson['duration_minutes']) ? intval($lesson['duration_minutes']) : 0;
            }
        }
    }
    
    return $totalMinutes;
}

/**
 * Count total lessons and completed lessons
 * 
 * @param array $sections Course sections
 * @return array Associative array with 'total' and 'completed' counts
 */
function countLessons($sections) {
    $total = 0;
    $completed = 0;
    
    foreach ($sections as $section) {
        if (isset($section['lessons']) && is_array($section['lessons'])) {
            foreach ($section['lessons'] as $lesson) {
                $total++;
                if (isset($lesson['completed']) && $lesson['completed']) {
                    $completed++;
                }
            }
        }
    }
    
    return [
        'total' => $total,
        'completed' => $completed
    ];
}

/**
 * Get lesson progress status for a user
 * 
 * Retrieves the progress status of a lesson for a specific user
 * 
 * @param int $userId User ID
 * @param int $lessonId Lesson ID
 * @return array Progress data including completion status, last access time, etc.
 */
function getLessonProgress($userId, $lessonId) {
    global $conn;
    
    $result = [
        'completed' => false,
        'started' => false,
        'completion_date' => null,
        'last_accessed' => null,
        'time_spent' => 0,
        'progress_percent' => 0
    ];
    
    // Ensure we have valid IDs
    if (!$userId || !$lessonId) {
        return $result;
    }
    
    // Query the lesson_progress table
    $sql = "SELECT * FROM lesson_progress 
            WHERE user_id = ? AND lesson_id = ? 
            LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Handle database error
        return $result;
    }
    
    $stmt->bind_param("ii", $userId, $lessonId);
    $stmt->execute();
    
    $queryResult = $stmt->get_result();
    if (!$queryResult) {
        return $result;
    }
    
    $progressData = $queryResult->fetch_assoc();
    
    if ($progressData) {
        $result = [
            'completed' => (bool)$progressData['completed'],
            'started' => true,
            'completion_date' => $progressData['completion_date'],
            'last_accessed' => $progressData['last_accessed'],
            'time_spent' => (int)($progressData['time_spent'] ?? 0),
            'progress_percent' => (int)($progressData['progress'] ?? 0) // Note: Changed to match your table column name
        ];
    }
    
    return $result;
}

/**
 * Get CSS class for course difficulty level
 * 
 * Returns an appropriate CSS class based on course difficulty level
 * 
 * @param string $difficultyLevel Course difficulty level ('Beginner', 'Intermediate', 'Advanced')
 * @return string CSS class for styling
 */
function getDifficultyClass($difficultyLevel) {
    $classes = [
        'Beginner' => 'difficulty-beginner',
        'Intermediate' => 'difficulty-intermediate',
        'Advanced' => 'difficulty-advanced'
    ];
    
    return $classes[$difficultyLevel] ?? 'difficulty-beginner';
}