<?php
/**
 * Enhanced Course Model for Sci-Bono Clubhouse LMS
 * Supports the full learning hierarchy: Courses > Modules > Lessons > Activities
 */

class CourseModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // =================== COURSE METHODS ===================
    
    /**
     * Get all courses with enhanced metadata
     */
    public function getAllCourses($filters = []) {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['type'])) {
            $whereClause .= " AND c.type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['difficulty_level'])) {
            $whereClause .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
            $types .= "s";
        }
        
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname,
                (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count,
                (SELECT COUNT(*) FROM course_sections WHERE course_id = c.id) as section_count,
                (SELECT COUNT(*) FROM course_lessons WHERE course_id = c.id) as lesson_count,
                (SELECT COUNT(*) FROM course_activities WHERE course_id = c.id) as activity_count,
                (SELECT COUNT(*) FROM user_enrollments WHERE course_id = c.id) as enrollment_count,
                (SELECT AVG(rating) FROM course_ratings WHERE course_id = c.id) as average_rating,
                (SELECT COUNT(*) FROM course_ratings WHERE course_id = c.id) as rating_count
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                $whereClause
                ORDER BY c.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
        }
        
        $courses = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        
        return $courses;
    }
    
    /**
     * Get detailed course information including all hierarchy
     */
    public function getCourseDetails($courseId, $includeHierarchy = true) {
        $sql = "SELECT c.*, u.name as creator_name, u.surname as creator_surname,
                (SELECT COUNT(*) FROM user_enrollments WHERE course_id = c.id) as enrollment_count,
                (SELECT AVG(rating) FROM course_ratings WHERE course_id = c.id) as average_rating
                FROM courses c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        $course = $result->fetch_assoc();
        
        if ($includeHierarchy) {
            $course['modules'] = $this->getCourseModules($courseId);
            $course['standalone_lessons'] = $this->getStandaloneLessons($courseId);
            $course['standalone_activities'] = $this->getStandaloneActivities($courseId);
            $course['assessments'] = $this->getCourseAssessments($courseId);
        }
        
        return $course;
    }

    /**
     * delete course by ID
     */
    public function deleteModule($moduleId) {
    $sql = "DELETE FROM modules WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([':id' => $moduleId]);
    }
    
    /**
     * Create a new course
     */
    public function createCourse($courseData) {
        $sql = "INSERT INTO courses (course_code, title, description, learning_objectives, 
                course_requirements, type, difficulty_level, duration, estimated_duration_hours,
                max_enrollments, image_path, is_featured, is_published, status, 
                pass_percentage, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssssiisissdi",
            $courseData['course_code'],
            $courseData['title'],
            $courseData['description'],
            $courseData['learning_objectives'],
            $courseData['course_requirements'],
            $courseData['type'],
            $courseData['difficulty_level'],
            $courseData['duration'],
            $courseData['estimated_duration_hours'],
            $courseData['max_enrollments'],
            $courseData['image_path'],
            $courseData['is_featured'],
            $courseData['is_published'],
            $courseData['status'],
            $courseData['pass_percentage'],
            $courseData['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    /**
     * Update an existing course
     * 
     * @param int $courseId Course ID
     * @param array $courseData Updated course data
     * @return bool Success status
     */
    public function updateCourse($courseId, $courseData) {
        $sql = "UPDATE courses SET 
                title = ?, 
                description = ?, 
                learning_objectives = ?,
                course_requirements = ?,
                type = ?, 
                difficulty_level = ?, 
                duration = ?,
                estimated_duration_hours = ?,
                max_enrollments = ?,
                image_path = ?, 
                is_featured = ?, 
                is_published = ?, 
                status = ?,
                pass_percentage = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssiisissdi", 
            $courseData['title'],
            $courseData['description'],
            $courseData['learning_objectives'],
            $courseData['course_requirements'],
            $courseData['type'],
            $courseData['difficulty_level'],
            $courseData['duration'],
            $courseData['estimated_duration_hours'],
            $courseData['max_enrollments'],
            $courseData['image_path'],
            $courseData['is_featured'],
            $courseData['is_published'],
            $courseData['status'],
            $courseData['pass_percentage'],
            $courseId
        );
        
        return $stmt->execute();
    }

    /**
     * Update module information
     * 
     * @param int $moduleId Module ID
     * @param array $moduleData Updated module data
     * @return bool Success status
     */
    public function updateModule($moduleId, $moduleData) {
        $sql = "UPDATE course_modules SET 
                title = ?, 
                description = ?,
                learning_objectives = ?,
                estimated_duration_hours = ?,
                is_published = ?,
                pass_percentage = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssiidi", 
            $moduleData['title'],
            $moduleData['description'],
            $moduleData['learning_objectives'],
            $moduleData['estimated_duration_hours'],
            $moduleData['is_published'],
            $moduleData['pass_percentage'],
            $moduleId
        );
        
        return $stmt->execute();
    }

    /**
     * Update lesson information
     * 
     * @param int $lessonId Lesson ID
     * @param array $lessonData Updated lesson data
     * @return bool Success status
     */
    public function updateLesson($lessonId, $lessonData) {
        $sql = "UPDATE course_lessons SET 
                title = ?, 
                content = ?,
                lesson_objectives = ?,
                lesson_type = ?,
                video_url = ?,
                duration_minutes = ?,
                estimated_duration_minutes = ?,
                difficulty_level = ?,
                pass_percentage = ?,
                prerequisites = ?,
                is_published = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssiisdsii", 
            $lessonData['title'],
            $lessonData['content'],
            $lessonData['lesson_objectives'],
            $lessonData['lesson_type'],
            $lessonData['video_url'],
            $lessonData['duration_minutes'],
            $lessonData['estimated_duration_minutes'],
            $lessonData['difficulty_level'],
            $lessonData['pass_percentage'],
            $lessonData['prerequisites'],
            $lessonData['is_published'],
            $lessonId
        );
        
        return $stmt->execute();
    }

    /**
     * Update activity information
     * 
     * @param int $activityId Activity ID
     * @param array $activityData Updated activity data
     * @return bool Success status
     */
    public function updateActivity($activityId, $activityData) {
        $sql = "UPDATE course_activities SET 
                title = ?, 
                description = ?,
                activity_type = ?,
                instructions = ?,
                resources_needed = ?,
                estimated_duration_minutes = ?,
                max_points = ?,
                pass_points = ?,
                submission_type = ?,
                auto_grade = ?,
                is_published = ?,
                due_date = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssiiisiisi", 
            $activityData['title'],
            $activityData['description'],
            $activityData['activity_type'],
            $activityData['instructions'],
            $activityData['resources_needed'],
            $activityData['estimated_duration_minutes'],
            $activityData['max_points'],
            $activityData['pass_points'],
            $activityData['submission_type'],
            $activityData['auto_grade'],
            $activityData['is_published'],
            $activityData['due_date'],
            $activityId
        );
        
        return $stmt->execute();
    }

    /**
     * Delete an activity and all its related content
     * 
     * @param int $activityId Activity ID
     * @return bool Success status
     */
    public function deleteActivity($activityId) {
        $this->conn->begin_transaction();
        
        try {
            // Delete activity submissions
            $this->conn->query("DELETE FROM activity_submissions WHERE activity_id = $activityId");
            
            // Delete user progress for this activity
            $this->conn->query("DELETE FROM user_progress WHERE activity_id = $activityId");
            
            // Delete the activity
            $this->conn->query("DELETE FROM course_activities WHERE id = $activityId");
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error deleting activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create skill activity
     * 
     * @param array $skillActivityData Skill activity data
     * @return int|bool New skill activity ID or false on failure
     */
    public function createSkillActivity($skillActivityData) {
        $sql = "INSERT INTO skill_activities (title, description, skill_category, technique_taught,
                tools_required, materials_needed, difficulty_level, estimated_duration_minutes,
                final_outcome_description, instructions, image_path, video_url, resources_links,
                created_by, is_published, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssississiii",
            $skillActivityData['title'],
            $skillActivityData['description'],
            $skillActivityData['skill_category'],
            $skillActivityData['technique_taught'],
            $skillActivityData['tools_required'],
            $skillActivityData['materials_needed'],
            $skillActivityData['difficulty_level'],
            $skillActivityData['estimated_duration_minutes'],
            $skillActivityData['final_outcome_description'],
            $skillActivityData['instructions'],
            $skillActivityData['image_path'],
            $skillActivityData['video_url'],
            $skillActivityData['resources_links'],
            $skillActivityData['created_by'],
            $skillActivityData['is_published'],
            $skillActivityData['is_featured']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    /**
     * Update skill activity
     * 
     * @param int $skillActivityId Skill activity ID
     * @param array $skillActivityData Updated skill activity data
     * @return bool Success status
     */
    public function updateSkillActivity($skillActivityId, $skillActivityData) {
        $sql = "UPDATE skill_activities SET 
                title = ?, 
                description = ?,
                skill_category = ?,
                technique_taught = ?,
                tools_required = ?,
                materials_needed = ?,
                difficulty_level = ?,
                estimated_duration_minutes = ?,
                final_outcome_description = ?,
                instructions = ?,
                image_path = ?,
                video_url = ?,
                resources_links = ?,
                is_published = ?,
                is_featured = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssississiii", 
            $skillActivityData['title'],
            $skillActivityData['description'],
            $skillActivityData['skill_category'],
            $skillActivityData['technique_taught'],
            $skillActivityData['tools_required'],
            $skillActivityData['materials_needed'],
            $skillActivityData['difficulty_level'],
            $skillActivityData['estimated_duration_minutes'],
            $skillActivityData['final_outcome_description'],
            $skillActivityData['instructions'],
            $skillActivityData['image_path'],
            $skillActivityData['video_url'],
            $skillActivityData['resources_links'],
            $skillActivityData['is_published'],
            $skillActivityData['is_featured'],
            $skillActivityId
        );
        
        return $stmt->execute();
    }

    // =================== MODULE METHODS ===================
    
    /**
     * Get all modules for a course
     */
    public function getCourseModules($courseId) {
        $sql = "SELECT * FROM course_modules 
                WHERE course_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $modules = [];
        if ($result && $result->num_rows > 0) {
            while ($module = $result->fetch_assoc()) {
                $module['lessons'] = $this->getModuleLessons($module['id']);
                $module['activities'] = $this->getModuleActivities($module['id']);
                $module['assessments'] = $this->getModuleAssessments($module['id']);
                $modules[] = $module;
            }
        }
        
        return $modules;
    }
    
    /**
     * Create a new module
     */
    public function createModule($courseId, $moduleData) {
        // Get the highest order number
        $orderQuery = "SELECT MAX(order_number) as max_order FROM course_modules WHERE course_id = ?";
        $orderStmt = $this->conn->prepare($orderQuery);
        $orderStmt->bind_param("i", $courseId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        $sql = "INSERT INTO course_modules (course_id, title, description, learning_objectives,
                estimated_duration_hours, order_number, is_published, pass_percentage)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssiiid",
            $courseId,
            $moduleData['title'],
            $moduleData['description'],
            $moduleData['learning_objectives'],
            $moduleData['estimated_duration_hours'],
            $orderNumber,
            $moduleData['is_published'],
            $moduleData['pass_percentage']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // =================== LESSON METHODS ===================
    
    /**
     * Get lessons for a specific module
     */
    public function getModuleLessons($moduleId) {
        $sql = "SELECT * FROM course_lessons 
                WHERE module_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $lessons = [];
        if ($result && $result->num_rows > 0) {
            while ($lesson = $result->fetch_assoc()) {
                $lesson['sections'] = $this->getLessonSections($lesson['id']);
                $lesson['activities'] = $this->getLessonActivities($lesson['id']);
                $lessons[] = $lesson;
            }
        }
        
        return $lessons;
    }

    /**
     * These methods provide enhanced CRUD operations and hierarchy management
     */
    // =================== LESSON DELETION AND DETAILS ===================

    /**
     * Delete a lesson and all its related content
     * 
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function deleteLesson($lessonId) {
        $this->conn->begin_transaction();
        
        try {
            // Delete lesson sections first (due to foreign key constraints)
            $this->conn->query("DELETE FROM lesson_sections WHERE lesson_id = $lessonId");
            
            // Delete activities associated with this lesson
            $this->conn->query("DELETE FROM activity_submissions WHERE activity_id IN (SELECT id FROM course_activities WHERE lesson_id = $lessonId)");
            $this->conn->query("DELETE FROM course_activities WHERE lesson_id = $lessonId");
            
            // Delete user progress for this lesson
            $this->conn->query("DELETE FROM user_progress WHERE lesson_id = $lessonId");
            
            // Delete lesson progress entries
            $this->conn->query("DELETE FROM lesson_progress WHERE lesson_id = $lessonId");
            
            // Finally delete the lesson itself
            $this->conn->query("DELETE FROM course_lessons WHERE id = $lessonId");
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error deleting lesson: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get detailed lesson information including sections and activities
     * 
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details or null if not found
     */
    public function getLessonDetails($lessonId) {
        $sql = "SELECT l.*, c.title as course_title, m.title as module_title, s.title as section_title
                FROM course_lessons l
                LEFT JOIN courses c ON l.course_id = c.id
                LEFT JOIN course_modules m ON l.module_id = m.id
                LEFT JOIN course_sections s ON l.section_id = s.id
                WHERE l.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        $lesson = $result->fetch_assoc();
        
        // Add related content
        $lesson['sections'] = $this->getLessonSections($lessonId);
        $lesson['activities'] = $this->getLessonActivities($lessonId);
        $lesson['progress_data'] = $this->getLessonProgressData($lessonId);
        
        return $lesson;
    }

    /**
     * Get lesson progress data (enrollments, completions, etc.)
     * 
     * @param int $lessonId Lesson ID
     * @return array Progress statistics
     */
    private function getLessonProgressData($lessonId) {
        $sql = "SELECT 
                    COUNT(DISTINCT lp.user_id) as total_enrolled,
                    COUNT(CASE WHEN lp.completed = 1 THEN 1 END) as completed_count,
                    AVG(lp.progress) as average_progress
                FROM lesson_progress lp
                WHERE lp.lesson_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_enrolled' => 0,
            'completed_count' => 0,
            'average_progress' => 0
        ];
    }

    // =================== SKILL ACTIVITY DELETION ===================

    /**
     * Delete a skill activity and all its related content
     * 
     * @param int $skillActivityId Skill activity ID
     * @return bool Success status
     */
    public function deleteSkillActivity($skillActivityId) {
        $this->conn->begin_transaction();
        
        try {
            // Delete skill activity completions
            $this->conn->query("DELETE FROM skill_activity_completions WHERE skill_activity_id = $skillActivityId");
            
            // Delete skill activity steps
            $this->conn->query("DELETE FROM skill_activity_steps WHERE skill_activity_id = $skillActivityId");
            
            // Delete user progress for this skill activity
            $this->conn->query("DELETE FROM user_progress WHERE activity_id = $skillActivityId AND progress_type = 'skill_activity'");
            
            // Delete the skill activity itself
            $this->conn->query("DELETE FROM skill_activities WHERE id = $skillActivityId");
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error deleting skill activity: " . $e->getMessage());
            return false;
        }
    }

    // =================== ACTIVITY DETAILS ===================

    /**
     * Get detailed activity information
     * 
     * @param int $activityId Activity ID
     * @return array|null Activity details or null if not found
     */
    public function getActivityDetails($activityId) {
        $sql = "SELECT a.*, c.title as course_title, m.title as module_title, 
                    l.title as lesson_title, ls.title as lesson_section_title
                FROM course_activities a
                LEFT JOIN courses c ON a.course_id = c.id
                LEFT JOIN course_modules m ON a.module_id = m.id
                LEFT JOIN course_lessons l ON a.lesson_id = l.id
                LEFT JOIN lesson_sections ls ON a.lesson_section_id = ls.id
                WHERE a.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        $activity = $result->fetch_assoc();
        
        // Add submission statistics
        $activity['submission_stats'] = $this->getActivitySubmissionStats($activityId);
        
        return $activity;
    }

    /**
     * Get activity submission statistics
     * 
     * @param int $activityId Activity ID
     * @return array Submission statistics
     */
    private function getActivitySubmissionStats($activityId) {
        $sql = "SELECT 
                    COUNT(*) as total_submissions,
                    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_grading,
                    COUNT(CASE WHEN status = 'graded' THEN 1 END) as graded,
                    AVG(CASE WHEN points_earned IS NOT NULL THEN points_earned END) as average_score,
                    MAX(points_earned) as highest_score,
                    MIN(points_earned) as lowest_score
                FROM activity_submissions
                WHERE activity_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_submissions' => 0,
            'pending_grading' => 0,
            'graded' => 0,
            'average_score' => 0,
            'highest_score' => 0,
            'lowest_score' => 0
        ];
    }

    // =================== MODULE DETAILS ===================

    /**
     * Get detailed module information including lessons and activities
     * 
     * @param int $moduleId Module ID
     * @return array|null Module details or null if not found
     */
    public function getModuleDetails($moduleId) {
        $sql = "SELECT m.*, c.title as course_title
                FROM course_modules m
                LEFT JOIN courses c ON m.course_id = c.id
                WHERE m.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        $module = $result->fetch_assoc();
        
        // Add related content
        $module['lessons'] = $this->getModuleLessons($moduleId);
        $module['activities'] = $this->getModuleActivities($moduleId);
        $module['assessments'] = $this->getModuleAssessments($moduleId);
        $module['progress_data'] = $this->getModuleProgressData($moduleId);
        
        return $module;
    }

    /**
     * Get module progress data
     * 
     * @param int $moduleId Module ID
     * @return array Progress statistics
     */
    private function getModuleProgressData($moduleId) {
        $sql = "SELECT 
                    COUNT(DISTINCT up.user_id) as total_enrolled,
                    COUNT(CASE WHEN up.status = 'completed' THEN 1 END) as completed_count,
                    AVG(up.completion_percentage) as average_progress
                FROM user_progress up
                WHERE up.module_id = ? AND up.progress_type = 'module'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_enrolled' => 0,
            'completed_count' => 0,
            'average_progress' => 0
        ];
    }

    // =================== ORDERING METHODS ===================

    /**
     * Update module order within a course
     * 
     * @param array $moduleOrders Array of module IDs and their new order numbers
     * @return bool Success status
     */
    public function updateModuleOrder($moduleOrders) {
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE course_modules SET order_number = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($moduleOrders as $moduleId => $order) {
                $stmt->bind_param("ii", $order, $moduleId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update module order for ID: $moduleId");
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating module order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update lesson order within a module or course
     * 
     * @param array $lessonOrders Array of lesson IDs and their new order numbers
     * @return bool Success status
     */
    public function updateLessonOrder($lessonOrders) {
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE course_lessons SET order_number = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($lessonOrders as $lessonId => $order) {
                $stmt->bind_param("ii", $order, $lessonId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update lesson order for ID: $lessonId");
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating lesson order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update activity order within its context (course, module, lesson, or lesson section)
     * 
     * @param array $activityOrders Array of activity IDs and their new order numbers
     * @return bool Success status
     */
    public function updateActivityOrder($activityOrders) {
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE course_activities SET order_number = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($activityOrders as $activityId => $order) {
                $stmt->bind_param("ii", $order, $activityId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update activity order for ID: $activityId");
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating activity order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update lesson section order within a lesson
     * 
     * @param array $sectionOrders Array of section IDs and their new order numbers
     * @return bool Success status
     */
    public function updateLessonSectionOrder($sectionOrders) {
        $this->conn->begin_transaction();
        
        try {
            $sql = "UPDATE lesson_sections SET order_number = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($sectionOrders as $sectionId => $order) {
                $stmt->bind_param("ii", $order, $sectionId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update section order for ID: $sectionId");
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating section order: " . $e->getMessage());
            return false;
        }
    }

    

    // =================== BULK OPERATIONS ===================

    /**
     * Bulk update publishing status for multiple items
     * 
     * @param string $table Table name (course_modules, course_lessons, course_activities, etc.)
     * @param array $itemIds Array of item IDs
     * @param bool $published Publishing status
     * @return bool Success status
     */
    public function bulkUpdatePublishStatus($table, $itemIds, $published) {
        if (empty($itemIds) || !is_array($itemIds)) {
            return false;
        }
        
        // Validate table name for security
        $allowedTables = ['course_modules', 'course_lessons', 'course_activities', 'lesson_sections', 'skill_activities'];
        if (!in_array($table, $allowedTables)) {
            return false;
        }
        
        $this->conn->begin_transaction();
        
        try {
            $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
            $sql = "UPDATE $table SET is_published = ?, updated_at = NOW() WHERE id IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $types = 'i' . str_repeat('i', count($itemIds));
            $params = array_merge([$published], $itemIds);
            
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to bulk update publish status");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in bulk update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Duplicate a module and all its content
     * 
     * @param int $moduleId Source module ID
     * @param int $targetCourseId Target course ID (optional, defaults to same course)
     * @param string $newTitle New title for duplicated module
     * @return int|bool New module ID or false on failure
     */
    public function duplicateModule($moduleId, $targetCourseId = null, $newTitle = null) {
        $sourceModule = $this->getModuleDetails($moduleId);
        if (!$sourceModule) {
            return false;
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Prepare new module data
            $newModuleData = [
                'title' => $newTitle ?: $sourceModule['title'] . ' (Copy)',
                'description' => $sourceModule['description'],
                'learning_objectives' => $sourceModule['learning_objectives'],
                'estimated_duration_hours' => $sourceModule['estimated_duration_hours'],
                'is_published' => 0, // Always start as draft
                'pass_percentage' => $sourceModule['pass_percentage']
            ];
            
            $courseId = $targetCourseId ?: $sourceModule['course_id'];
            $newModuleId = $this->createModule($courseId, $newModuleData);
            
            if (!$newModuleId) {
                throw new Exception("Failed to create new module");
            }
            
            // Duplicate lessons
            foreach ($sourceModule['lessons'] as $lesson) {
                $newLessonData = [
                    'section_id' => null,
                    'course_id' => $courseId,
                    'module_id' => $newModuleId,
                    'title' => $lesson['title'],
                    'content' => $lesson['content'],
                    'lesson_objectives' => $lesson['lesson_objectives'],
                    'lesson_type' => $lesson['lesson_type'],
                    'video_url' => $lesson['video_url'],
                    'duration_minutes' => $lesson['duration_minutes'],
                    'estimated_duration_minutes' => $lesson['estimated_duration_minutes'],
                    'difficulty_level' => $lesson['difficulty_level'],
                    'pass_percentage' => $lesson['pass_percentage'],
                    'prerequisites' => $lesson['prerequisites'],
                    'is_published' => 0
                ];
                
                $newLessonId = $this->createLesson($newLessonData);
                
                if ($newLessonId) {
                    // Duplicate lesson sections
                    foreach ($lesson['sections'] as $section) {
                        $newSectionData = [
                            'title' => $section['title'],
                            'content' => $section['content'],
                            'section_type' => $section['section_type'],
                            'video_url' => $section['video_url'],
                            'estimated_duration_minutes' => $section['estimated_duration_minutes'],
                            'is_published' => 0
                        ];
                        
                        $this->createLessonSection($newLessonId, $newSectionData);
                    }
                    
                    // Duplicate lesson activities
                    foreach ($lesson['activities'] as $activity) {
                        $newActivityData = [
                            'course_id' => $courseId,
                            'module_id' => $newModuleId,
                            'lesson_id' => $newLessonId,
                            'lesson_section_id' => null,
                            'title' => $activity['title'],
                            'description' => $activity['description'],
                            'activity_type' => $activity['activity_type'],
                            'instructions' => $activity['instructions'],
                            'resources_needed' => $activity['resources_needed'],
                            'estimated_duration_minutes' => $activity['estimated_duration_minutes'],
                            'max_points' => $activity['max_points'],
                            'pass_points' => $activity['pass_points'],
                            'submission_type' => $activity['submission_type'],
                            'auto_grade' => $activity['auto_grade'],
                            'is_published' => 0,
                            'due_date' => null
                        ];
                        
                        $this->createActivity($newActivityData);
                    }
                }
            }
            
            // Duplicate module-level activities
            foreach ($sourceModule['activities'] as $activity) {
                $newActivityData = [
                    'course_id' => $courseId,
                    'module_id' => $newModuleId,
                    'lesson_id' => null,
                    'lesson_section_id' => null,
                    'title' => $activity['title'],
                    'description' => $activity['description'],
                    'activity_type' => $activity['activity_type'],
                    'instructions' => $activity['instructions'],
                    'resources_needed' => $activity['resources_needed'],
                    'estimated_duration_minutes' => $activity['estimated_duration_minutes'],
                    'max_points' => $activity['max_points'],
                    'pass_points' => $activity['pass_points'],
                    'submission_type' => $activity['submission_type'],
                    'auto_grade' => $activity['auto_grade'],
                    'is_published' => 0,
                    'due_date' => null
                ];
                
                $this->createActivity($newActivityData);
            }
            
            $this->conn->commit();
            return $newModuleId;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error duplicating module: " . $e->getMessage());
            return false;
        }
    }

    // =================== SEARCH AND FILTERING ===================

    /**
     * Search across all course content
     * 
     * @param string $searchTerm Search term
     * @param array $filters Additional filters (type, difficulty, etc.)
     * @return array Search results organized by content type
     */
    public function searchCourseContent($searchTerm, $filters = []) {
        $searchTerm = '%' . $searchTerm . '%';
        $results = [
            'courses' => [],
            'modules' => [],
            'lessons' => [],
            'activities' => [],
            'skill_activities' => []
        ];
        
        // Search courses
        $sql = "SELECT id, title, description, type, difficulty_level FROM courses 
                WHERE (title LIKE ? OR description LIKE ?) AND is_published = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['courses'][] = $row;
        }
        
        // Search modules
        $sql = "SELECT m.id, m.title, m.description, c.title as course_title, c.id as course_id
                FROM course_modules m 
                JOIN courses c ON m.course_id = c.id
                WHERE (m.title LIKE ? OR m.description LIKE ?) AND m.is_published = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['modules'][] = $row;
        }
        
        // Search lessons
        $sql = "SELECT l.id, l.title, l.content, l.lesson_type, c.title as course_title, c.id as course_id,
                    m.title as module_title, m.id as module_id
                FROM course_lessons l 
                JOIN courses c ON l.course_id = c.id
                LEFT JOIN course_modules m ON l.module_id = m.id
                WHERE (l.title LIKE ? OR l.content LIKE ?) AND l.is_published = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['lessons'][] = $row;
        }
        
        // Search activities
        $sql = "SELECT a.id, a.title, a.description, a.activity_type, c.title as course_title, c.id as course_id
                FROM course_activities a 
                JOIN courses c ON a.course_id = c.id
                WHERE (a.title LIKE ? OR a.description LIKE ?) AND a.is_published = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['activities'][] = $row;
        }
        
        // Search skill activities
        $sql = "SELECT id, title, description, skill_category, difficulty_level
                FROM skill_activities 
                WHERE (title LIKE ? OR description LIKE ?) AND is_published = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results['skill_activities'][] = $row;
        }
        
        return $results;
    }
    
    /**
     * Get standalone lessons (not part of modules)
     */
    public function getStandaloneLessons($courseId) {
        $sql = "SELECT * FROM course_lessons 
                WHERE course_id = ? AND module_id IS NULL
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $lessons = [];
        if ($result && $result->num_rows > 0) {
            while ($lesson = $result->fetch_assoc()) {
                $lesson['sections'] = $this->getLessonSections($lesson['id']);
                $lesson['activities'] = $this->getLessonActivities($lesson['id']);
                $lessons[] = $lesson;
            }
        }
        
        return $lessons;
    }
    
    /**
     * Create a new lesson
     */
    public function createLesson($lessonData) {
        // Determine order number based on context
        if (!empty($lessonData['module_id'])) {
            $orderQuery = "SELECT MAX(order_number) as max_order FROM course_lessons WHERE module_id = ?";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bind_param("i", $lessonData['module_id']);
        } else {
            $orderQuery = "SELECT MAX(order_number) as max_order FROM course_lessons WHERE course_id = ? AND module_id IS NULL";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bind_param("i", $lessonData['course_id']);
        }
        
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        $sql = "INSERT INTO course_lessons (section_id, course_id, module_id, title, content, 
                lesson_objectives, lesson_type, video_url, duration_minutes, estimated_duration_minutes,
                difficulty_level, pass_percentage, prerequisites, order_number, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiisssssiiisdii",
            $lessonData['section_id'],
            $lessonData['course_id'],
            $lessonData['module_id'],
            $lessonData['title'],
            $lessonData['content'],
            $lessonData['lesson_objectives'],
            $lessonData['lesson_type'],
            $lessonData['video_url'],
            $lessonData['duration_minutes'],
            $lessonData['estimated_duration_minutes'],
            $lessonData['difficulty_level'],
            $lessonData['pass_percentage'],
            $lessonData['prerequisites'],
            $orderNumber,
            $lessonData['is_published']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // =================== LESSON SECTION METHODS ===================
    
    /**
     * Get sections for a lesson
     */
    public function getLessonSections($lessonId) {
        $sql = "SELECT * FROM lesson_sections 
                WHERE lesson_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sections = [];
        if ($result && $result->num_rows > 0) {
            while ($section = $result->fetch_assoc()) {
                $sections[] = $section;
            }
        }
        
        return $sections;
    }
    
    /**
     * Create a new lesson section
     */
    public function createLessonSection($lessonId, $sectionData) {
        $orderQuery = "SELECT MAX(order_number) as max_order FROM lesson_sections WHERE lesson_id = ?";
        $orderStmt = $this->conn->prepare($orderQuery);
        $orderStmt->bind_param("i", $lessonId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        $sql = "INSERT INTO lesson_sections (lesson_id, title, content, section_type, 
                video_url, estimated_duration_minutes, order_number, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssiiii",
            $lessonId,
            $sectionData['title'],
            $sectionData['content'],
            $sectionData['section_type'],
            $sectionData['video_url'],
            $sectionData['estimated_duration_minutes'],
            $orderNumber,
            $sectionData['is_published']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // =================== ACTIVITY METHODS ===================
    
    /**
     * Get activities for a module
     */
    public function getModuleActivities($moduleId) {
        $sql = "SELECT * FROM course_activities 
                WHERE module_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $this->fetchActivities($result);
    }
    
    /**
     * Get activities for a lesson
     */
    public function getLessonActivities($lessonId) {
        $sql = "SELECT * FROM course_activities 
                WHERE lesson_id = ? 
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $this->fetchActivities($result);
    }
    
    /**
     * Get standalone activities for a course
     */
    public function getStandaloneActivities($courseId) {
        $sql = "SELECT * FROM course_activities 
                WHERE course_id = ? AND module_id IS NULL AND lesson_id IS NULL
                ORDER BY order_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $this->fetchActivities($result);
    }
    
    /**
     * Helper method to fetch activities from result
     */
    private function fetchActivities($result) {
        $activities = [];
        if ($result && $result->num_rows > 0) {
            while ($activity = $result->fetch_assoc()) {
                $activities[] = $activity;
            }
        }
        return $activities;
    }
    
    /**
     * Create a new activity
     */
    public function createActivity($activityData) {
        // Determine context for order number
        $orderQuery = "SELECT MAX(order_number) as max_order FROM course_activities WHERE ";
        $params = [];
        $types = "";
        
        if (!empty($activityData['lesson_section_id'])) {
            $orderQuery .= "lesson_section_id = ?";
            $params[] = $activityData['lesson_section_id'];
            $types .= "i";
        } elseif (!empty($activityData['lesson_id'])) {
            $orderQuery .= "lesson_id = ?";
            $params[] = $activityData['lesson_id'];
            $types .= "i";
        } elseif (!empty($activityData['module_id'])) {
            $orderQuery .= "module_id = ?";
            $params[] = $activityData['module_id'];
            $types .= "i";
        } else {
            $orderQuery .= "course_id = ? AND module_id IS NULL AND lesson_id IS NULL";
            $params[] = $activityData['course_id'];
            $types .= "i";
        }
        
        $orderStmt = $this->conn->prepare($orderQuery);
        $orderStmt->bind_param($types, ...$params);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderRow = $orderResult->fetch_assoc();
        $orderNumber = ($orderRow['max_order'] ?? 0) + 1;
        
        $sql = "INSERT INTO course_activities (course_id, module_id, lesson_id, lesson_section_id,
                title, description, activity_type, instructions, resources_needed,
                estimated_duration_minutes, max_points, pass_points, submission_type,
                auto_grade, order_number, is_published, due_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiisssssiiisiiis",
            $activityData['course_id'],
            $activityData['module_id'],
            $activityData['lesson_id'],
            $activityData['lesson_section_id'],
            $activityData['title'],
            $activityData['description'],
            $activityData['activity_type'],
            $activityData['instructions'],
            $activityData['resources_needed'],
            $activityData['estimated_duration_minutes'],
            $activityData['max_points'],
            $activityData['pass_points'],
            $activityData['submission_type'],
            $activityData['auto_grade'],
            $orderNumber,
            $activityData['is_published'],
            $activityData['due_date']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // =================== ASSESSMENT METHODS ===================
    
    /**
     * Get assessments for a course
     */
    public function getCourseAssessments($courseId) {
        $sql = "SELECT * FROM course_assessments 
                WHERE course_id = ? AND module_id IS NULL
                ORDER BY assessment_type ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $this->fetchAssessments($result);
    }
    
    /**
     * Get assessments for a module
     */
    public function getModuleAssessments($moduleId) {
        $sql = "SELECT * FROM course_assessments 
                WHERE module_id = ?
                ORDER BY assessment_type ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $this->fetchAssessments($result);
    }
    
    /**
     * Helper method to fetch assessments
     */
    private function fetchAssessments($result) {
        $assessments = [];
        if ($result && $result->num_rows > 0) {
            while ($assessment = $result->fetch_assoc()) {
                $assessments[] = $assessment;
            }
        }
        return $assessments;
    }

    // =================== SKILL ACTIVITIES METHODS ===================
    
    /**
     * Get all skill activities
     */
    public function getAllSkillActivities($filters = []) {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['skill_category'])) {
            $whereClause .= " AND skill_category = ?";
            $params[] = $filters['skill_category'];
            $types .= "s";
        }
        
        if (!empty($filters['difficulty_level'])) {
            $whereClause .= " AND difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
            $types .= "s";
        }
        
        if (!empty($filters['is_published'])) {
            $whereClause .= " AND is_published = ?";
            $params[] = $filters['is_published'];
            $types .= "i";
        }
        
        $sql = "SELECT sa.*, u.name as creator_name, u.surname as creator_surname,
                (SELECT COUNT(*) FROM skill_activity_completions WHERE skill_activity_id = sa.id) as completion_count
                FROM skill_activities sa
                LEFT JOIN users u ON sa.created_by = u.id
                $whereClause
                ORDER BY sa.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
        }
        
        $skillActivities = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $skillActivities[] = $row;
            }
        }
        
        return $skillActivities;
    }
    
    /**
     * Get skill activity details with steps
     */
    public function getSkillActivityDetails($skillActivityId) {
        $sql = "SELECT sa.*, u.name as creator_name, u.surname as creator_surname
                FROM skill_activities sa
                LEFT JOIN users u ON sa.created_by = u.id
                WHERE sa.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $skillActivityId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        
        $skillActivity = $result->fetch_assoc();
        $skillActivity['steps'] = $this->getSkillActivitySteps($skillActivityId);
        
        return $skillActivity;
    }
    
    /**
     * Get steps for a skill activity
     */
    public function getSkillActivitySteps($skillActivityId) {
        $sql = "SELECT * FROM skill_activity_steps 
                WHERE skill_activity_id = ? 
                ORDER BY step_number ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $skillActivityId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $steps = [];
        if ($result && $result->num_rows > 0) {
            while ($step = $result->fetch_assoc()) {
                $steps[] = $step;
            }
        }
        
        return $steps;
    }

    // =================== PROGRESS TRACKING METHODS ===================
    
    /**
     * Get user progress for a course
     */
    public function getUserCourseProgress($userId, $courseId) {
        $sql = "SELECT * FROM user_progress 
                WHERE user_id = ? AND course_id = ? AND progress_type = 'course'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Update user progress
     */
    public function updateUserProgress($progressData) {
        $sql = "INSERT INTO user_progress (user_id, course_id, module_id, lesson_id, 
                lesson_section_id, activity_id, progress_type, completion_percentage,
                total_points_earned, total_points_possible, status, last_accessed, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                completion_percentage = VALUES(completion_percentage),
                total_points_earned = VALUES(total_points_earned),
                total_points_possible = VALUES(total_points_possible),
                status = VALUES(status),
                last_accessed = VALUES(last_accessed),
                completed_at = VALUES(completed_at)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiiissdissss",
            $progressData['user_id'],
            $progressData['course_id'],
            $progressData['module_id'],
            $progressData['lesson_id'],
            $progressData['lesson_section_id'],
            $progressData['activity_id'],
            $progressData['progress_type'],
            $progressData['completion_percentage'],
            $progressData['total_points_earned'],
            $progressData['total_points_possible'],
            $progressData['status'],
            $progressData['last_accessed'],
            $progressData['completed_at']
        );
        
        return $stmt->execute();
    }

    // =================== UTILITY METHODS ===================
    
    /**
     * Calculate course completion percentage
     */
    public function calculateCourseCompletion($userId, $courseId) {
        // Get total possible points from all activities and assessments
        $totalPointsSql = "SELECT 
            (SELECT COALESCE(SUM(max_points), 0) FROM course_activities WHERE course_id = ?) +
            (SELECT COALESCE(SUM(total_points), 0) FROM course_assessments WHERE course_id = ?) as total_points";
        
        $stmt = $this->conn->prepare($totalPointsSql);
        $stmt->bind_param("ii", $courseId, $courseId);
        $stmt->execute();
        $totalResult = $stmt->get_result();
        $totalPoints = $totalResult->fetch_assoc()['total_points'] ?? 0;
        
        if ($totalPoints == 0) {
            return 0;
        }
        
        // Get user's earned points
        $earnedPointsSql = "SELECT 
            (SELECT COALESCE(SUM(points_earned), 0) FROM activity_submissions 
             WHERE user_id = ? AND activity_id IN (SELECT id FROM course_activities WHERE course_id = ?)) +
            (SELECT COALESCE(SUM(points_earned), 0) FROM assessment_attempts 
             WHERE user_id = ? AND assessment_id IN (SELECT id FROM course_assessments WHERE course_id = ?)) as earned_points";
        
        $stmt = $this->conn->prepare($earnedPointsSql);
        $stmt->bind_param("iiii", $userId, $courseId, $userId, $courseId);
        $stmt->execute();
        $earnedResult = $stmt->get_result();
        $earnedPoints = $earnedResult->fetch_assoc()['earned_points'] ?? 0;
        
        return ($earnedPoints / $totalPoints) * 100;
    }
    
    /**
     * Check if user passed the course
     */
    public function hasUserPassedCourse($userId, $courseId) {
        $course = $this->getCourseDetails($courseId, false);
        if (!$course) {
            return false;
        }
        
        $completionPercentage = $this->calculateCourseCompletion($userId, $courseId);
        return $completionPercentage >= $course['pass_percentage'];
    }
    
    /**
     * Delete course and all related data
     */
    public function deleteCourse($courseId) {
        $this->conn->begin_transaction();
        
        try {
            // Delete in proper order to avoid foreign key constraints
            $this->conn->query("DELETE FROM user_progress WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM assessment_attempts WHERE assessment_id IN (SELECT id FROM course_assessments WHERE course_id = $courseId)");
            $this->conn->query("DELETE FROM activity_submissions WHERE activity_id IN (SELECT id FROM course_activities WHERE course_id = $courseId)");
            $this->conn->query("DELETE FROM course_assessments WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM course_activities WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM lesson_sections WHERE lesson_id IN (SELECT id FROM course_lessons WHERE course_id = $courseId)");
            $this->conn->query("DELETE FROM course_lessons WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM course_modules WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM user_enrollments WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM course_ratings WHERE course_id = $courseId");
            $this->conn->query("DELETE FROM courses WHERE id = $courseId");
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>