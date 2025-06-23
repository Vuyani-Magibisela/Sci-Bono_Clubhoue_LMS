<?php
session_start();
// Check if user is logged in and is an admin or mentor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || 
    ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'mentor')) {
    header("Location: ../../../login.php");
    exit;
}

// Include the auto-logout script to track inactivity
include '../../Controllers/sessionTimer.php';

// Include database connection
require_once '../../../server.php';

// Include controllers
require_once '../../Controllers/Admin/CourseController.php';
require_once '../../Models/LMSUtilities.php';

// Get user ID from session
$userId = $_SESSION['id'] ?? 0;
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($courseId <= 0) {
    header("Location: enhanced-manage-courses.php");
    exit;
}

// Initialize controller
$courseController = new CourseController($conn);

// Get course details with full hierarchy
$course = $courseController->getCourseDetails($courseId, true);

if (!$course) {
    header("Location: enhanced-manage-courses.php");
    exit;
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_module':
                $moduleData = [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'learning_objectives' => $_POST['learning_objectives'] ?? '',
                    'estimated_duration_hours' => intval($_POST['estimated_duration_hours'] ?? 0),
                    'pass_percentage' => floatval($_POST['pass_percentage'] ?? 70),
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                $result = $courseController->createModule($courseId, $moduleData);
                echo json_encode($result);
                exit;
                
            case 'create_lesson':
                $lessonData = [
                    'course_id' => $courseId,
                    'module_id' => intval($_POST['module_id'] ?? 0) ?: null,
                    'section_id' => intval($_POST['section_id'] ?? 0) ?: null,
                    'title' => $_POST['title'] ?? '',
                    'content' => $_POST['content'] ?? '',
                    'lesson_objectives' => $_POST['lesson_objectives'] ?? '',
                    'lesson_type' => $_POST['lesson_type'] ?? 'text',
                    'video_url' => $_POST['video_url'] ?? '',
                    'duration_minutes' => intval($_POST['duration_minutes'] ?? 30),
                    'estimated_duration_minutes' => intval($_POST['estimated_duration_minutes'] ?? 30),
                    'difficulty_level' => $_POST['difficulty_level'] ?? 'Beginner',
                    'pass_percentage' => floatval($_POST['pass_percentage'] ?? 70),
                    'prerequisites' => $_POST['prerequisites'] ?? '',
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                $result = $courseController->createLesson($lessonData);
                echo json_encode($result);
                exit;
                
            case 'create_activity':
                $activityData = [
                    'course_id' => $courseId,
                    'module_id' => intval($_POST['module_id'] ?? 0) ?: null,
                    'lesson_id' => intval($_POST['lesson_id'] ?? 0) ?: null,
                    'lesson_section_id' => intval($_POST['lesson_section_id'] ?? 0) ?: null,
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'activity_type' => $_POST['activity_type'] ?? 'practical',
                    'instructions' => $_POST['instructions'] ?? '',
                    'resources_needed' => $_POST['resources_needed'] ?? '',
                    'estimated_duration_minutes' => intval($_POST['estimated_duration_minutes'] ?? 60),
                    'max_points' => intval($_POST['max_points'] ?? 100),
                    'pass_points' => intval($_POST['pass_points'] ?? 70),
                    'submission_type' => $_POST['submission_type'] ?? 'text',
                    'auto_grade' => isset($_POST['auto_grade']) ? 1 : 0,
                    'is_published' => isset($_POST['is_published']) ? 1 : 0,
                    'due_date' => $_POST['due_date'] ?? null
                ];
                
                $result = $courseController->createActivity($activityData);
                echo json_encode($result);
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Get course statistics
$stats = $courseController->getCourseStatistics($courseId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course Content - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .course-hierarchy {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .hierarchy-item {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            position: relative;
        }
        
        .hierarchy-item.module {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
        
        .hierarchy-item.lesson {
            margin-left: 2rem;
            background: #fff;
            border-left: 4px solid #51cf66;
        }
        
        .hierarchy-item.activity {
            margin-left: 4rem;
            background: #fefefe;
            border-left: 4px solid #ffd43b;
        }
        
        .item-header {
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .item-title {
            font-weight: 600;
            flex-grow: 1;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .add-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-btn:hover {
            background: #5a67d8;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-backdrop.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .hierarchy-item.lesson {
                margin-left: 1rem;
            }
            
            .hierarchy-item.activity {
                margin-left: 2rem;
            }
            
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Course Content Management</h1>
            <div class="admin-actions">
                <a href="enhanced-manage-courses.php" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
        </div>
        
        <!-- Course Overview -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <div class="badge-group">
                    <span class="badge badge-primary"><?php echo $courseController->formatCourseType($course['type']); ?></span>
                    <span class="badge <?php echo $courseController->getDifficultyClass($course['difficulty_level']); ?>">
                        <?php echo htmlspecialchars($course['difficulty_level']); ?>
                    </span>
                    <span class="badge <?php echo $courseController->getStatusClass($course['status']); ?>">
                        <?php echo ucfirst($course['status']); ?>
                    </span>
                </div>
            </div>
            
            <p><?php echo htmlspecialchars($course['description']); ?></p>
        </div>
        
        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_modules']; ?></div>
                <div class="stat-label">Modules</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_lessons']; ?></div>
                <div class="stat-label">Lessons</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_activities']; ?></div>
                <div class="stat-label">Activities</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['enrollment_count']; ?></div>
                <div class="stat-label">Enrollments</div>
            </div>
        </div>
        
        <!-- Course Hierarchy -->
        <div class="course-hierarchy">
            <div class="hierarchy-header">
                <h3>Course Structure</h3>
                <?php if ($course['type'] === 'full_course' || $course['type'] === 'short_course'): ?>
                <button class="add-btn" onclick="openModal('add-module-modal')">
                    <i class="fas fa-plus"></i> Add Module
                </button>
                <?php else: ?>
                <button class="add-btn" onclick="openModal('add-lesson-modal')">
                    <i class="fas fa-plus"></i> Add Lesson
                </button>
                <?php endif; ?>
            </div>
            
            <div class="hierarchy-content">
                <?php if (empty($course['modules']) && empty($course['standalone_lessons'])): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h4>No content yet</h4>
                    <p>Start building your course by adding modules and lessons.</p>
                </div>
                <?php else: ?>
                
                <!-- Modules -->
                <?php foreach ($course['modules'] ?? [] as $module): ?>
                <div class="hierarchy-item module" data-module-id="<?php echo $module['id']; ?>">
                    <div class="item-header">
                        <div class="item-title">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($module['title']); ?>
                            <small>(<?php echo count($module['lessons']); ?> lessons, <?php echo count($module['activities']); ?> activities)</small>
                        </div>
                        <div class="item-actions">
                            <button class="add-btn" onclick="openAddLessonModal(<?php echo $module['id']; ?>)">
                                <i class="fas fa-plus"></i> Add Lesson
                            </button>
                            <button class="add-btn" onclick="openAddActivityModal(<?php echo $module['id']; ?>, null)">
                                <i class="fas fa-plus"></i> Add Activity
                            </button>
                        </div>
                    </div>
                    
                    <!-- Module Lessons -->
                    <?php foreach ($module['lessons'] ?? [] as $lesson): ?>
                    <div class="hierarchy-item lesson" data-lesson-id="<?php echo $lesson['id']; ?>">
                        <div class="item-header">
                            <div class="item-title">
                                <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i> 
                                <?php echo htmlspecialchars($lesson['title']); ?>
                                <small>(<?php echo $lesson['estimated_duration_minutes']; ?> min)</small>
                            </div>
                            <div class="item-actions">
                                <button class="add-btn" onclick="openAddActivityModal(<?php echo $module['id']; ?>, <?php echo $lesson['id']; ?>)">
                                    <i class="fas fa-plus"></i> Add Activity
                                </button>
                            </div>
                        </div>
                        
                        <!-- Lesson Activities -->
                        <?php foreach ($lesson['activities'] ?? [] as $activity): ?>
                        <div class="hierarchy-item activity" data-activity-id="<?php echo $activity['id']; ?>">
                            <div class="item-header">
                                <div class="item-title">
                                    <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($activity['title']); ?>
                                    <small>(<?php echo ucfirst($activity['activity_type']); ?> - <?php echo $activity['max_points']; ?> pts)</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Module Activities -->
                    <?php foreach ($module['activities'] ?? [] as $activity): ?>
                    <div class="hierarchy-item activity" data-activity-id="<?php echo $activity['id']; ?>">
                        <div class="item-header">
                            <div class="item-title">
                                <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($activity['title']); ?>
                                <small>(Module Activity - <?php echo $activity['max_points']; ?> pts)</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Standalone Lessons -->
                <?php foreach ($course['standalone_lessons'] ?? [] as $lesson): ?>
                <div class="hierarchy-item lesson" data-lesson-id="<?php echo $lesson['id']; ?>">
                    <div class="item-header">
                        <div class="item-title">
                            <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i> 
                            <?php echo htmlspecialchars($lesson['title']); ?>
                            <small>(Standalone - <?php echo $lesson['estimated_duration_minutes']; ?> min)</small>
                        </div>
                        <div class="item-actions">
                            <button class="add-btn" onclick="openAddActivityModal(null, <?php echo $lesson['id']; ?>)">
                                <i class="fas fa-plus"></i> Add Activity
                            </button>
                        </div>
                    </div>
                    
                    <!-- Lesson Activities -->
                    <?php foreach ($lesson['activities'] ?? [] as $activity): ?>
                    <div class="hierarchy-item activity" data-activity-id="<?php echo $activity['id']; ?>">
                        <div class="item-header">
                            <div class="item-title">
                                <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($activity['title']); ?>
                                <small>(<?php echo ucfirst($activity['activity_type']); ?> - <?php echo $activity['max_points']; ?> pts)</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Standalone Activities -->
                <?php foreach ($course['standalone_activities'] ?? [] as $activity): ?>
                <div class="hierarchy-item activity" data-activity-id="<?php echo $activity['id']; ?>">
                    <div class="item-header">
                        <div class="item-title">
                            <i class="fas fa-tasks"></i> <?php echo htmlspecialchars($activity['title']); ?>
                            <small>(Course Activity - <?php echo $activity['max_points']; ?> pts)</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Module Modal -->
    <div class="modal-backdrop" id="add-module-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add New Module</h3>
                <button type="button" class="modal-close" onclick="closeModal('add-module-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="add-module-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="module-title">Module Title *</label>
                        <input type="text" id="module-title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="module-description">Description</label>
                        <textarea id="module-description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="module-objectives">Learning Objectives</label>
                        <textarea id="module-objectives" name="learning_objectives" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="module-duration">Estimated Duration (hours)</label>
                        <input type="number" id="module-duration" name="estimated_duration_hours" class="form-control" min="1">
                    </div>
                    <div class="form-group">
                        <label for="module-pass">Pass Percentage (%)</label>
                        <input type="number" id="module-pass" name="pass_percentage" class="form-control" value="70" min="0" max="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="module-published" name="is_published"> Publish immediately
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('add-module-modal')">Cancel</button>
                    <button type="submit" class="admin-btn">Add Module</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Lesson Modal -->
    <div class="modal-backdrop" id="add-lesson-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add New Lesson</h3>
                <button type="button" class="modal-close" onclick="closeModal('add-lesson-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="add-lesson-form">
                <input type="hidden" id="lesson-module-id" name="module_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-title">Lesson Title *</label>
                        <input type="text" id="lesson-title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-type">Lesson Type</label>
                        <select id="lesson-type" name="lesson_type" class="form-control">
                            <option value="text">Text Lesson</option>
                            <option value="video">Video Lesson</option>
                            <option value="quiz">Quiz</option>
                            <option value="assignment">Assignment</option>
                            <option value="interactive">Interactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="lesson-difficulty">Difficulty Level</label>
                        <select id="lesson-difficulty" name="difficulty_level" class="form-control">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row video-fields" style="display: none;">
                    <div class="form-group">
                        <label for="lesson-video">Video URL</label>
                        <input type="url" id="lesson-video" name="video_url" class="form-control" placeholder="https://www.youtube.com/embed/...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-objectives">Learning Objectives</label>
                        <textarea id="lesson-objectives" name="lesson_objectives" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-content">Content</label>
                        <textarea id="lesson-content" name="content" class="form-control" rows="5"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-duration">Duration (minutes)</label>
                        <input type="number" id="lesson-duration" name="estimated_duration_minutes" class="form-control" value="30" min="1">
                    </div>
                    <div class="form-group">
                        <label for="lesson-pass">Pass Percentage (%)</label>
                        <input type="number" id="lesson-pass" name="pass_percentage" class="form-control" value="70" min="0" max="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson-prerequisites">Prerequisites</label>
                        <textarea id="lesson-prerequisites" name="prerequisites" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="lesson-published" name="is_published"> Publish immediately
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('add-lesson-modal')">Cancel</button>
                    <button type="submit" class="admin-btn">Add Lesson</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Activity Modal -->
    <div class="modal-backdrop" id="add-activity-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add New Activity</h3>
                <button type="button" class="modal-close" onclick="closeModal('add-activity-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="add-activity-form">
                <input type="hidden" id="activity-module-id" name="module_id">
                <input type="hidden" id="activity-lesson-id" name="lesson_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-title">Activity Title *</label>
                        <input type="text" id="activity-title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-type">Activity Type</label>
                        <select id="activity-type" name="activity_type" class="form-control">
                            <option value="practical">Practical Exercise</option>
                            <option value="assignment">Assignment</option>
                            <option value="project">Project</option>
                            <option value="quiz">Quiz</option>
                            <option value="assessment">Assessment</option>
                            <option value="skill_exercise">Skill Exercise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity-submission">Submission Type</label>
                        <select id="activity-submission" name="submission_type" class="form-control">
                            <option value="text">Text Response</option>
                            <option value="file">File Upload</option>
                            <option value="link">Link/URL</option>
                            <option value="code">Code Submission</option>
                            <option value="none">No Submission</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-description">Description</label>
                        <textarea id="activity-description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-instructions">Instructions</label>
                        <textarea id="activity-instructions" name="instructions" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-resources">Resources Needed</label>
                        <textarea id="activity-resources" name="resources_needed" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-duration">Duration (minutes)</label>
                        <input type="number" id="activity-duration" name="estimated_duration_minutes" class="form-control" value="60" min="1">
                    </div>
                    <div class="form-group">
                        <label for="activity-points">Max Points</label>
                        <input type="number" id="activity-points" name="max_points" class="form-control" value="100" min="1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-pass-points">Pass Points</label>
                        <input type="number" id="activity-pass-points" name="pass_points" class="form-control" value="70" min="1">
                    </div>
                    <div class="form-group">
                        <label for="activity-due">Due Date (optional)</label>
                        <input type="datetime-local" id="activity-due" name="due_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="activity-auto-grade" name="auto_grade"> Auto-grade submission
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="activity-published" name="is_published"> Publish immediately
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('add-activity-modal')">Cancel</button>
                    <button type="submit" class="admin-btn">Add Activity</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: white; z-index: 1001; display: none;"></div>
    
    <script>
        // Modal Management
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function openAddLessonModal(moduleId) {
            document.getElementById('lesson-module-id').value = moduleId || '';
            openModal('add-lesson-modal');
        }
        
        function openAddActivityModal(moduleId, lessonId) {
            document.getElementById('activity-module-id').value = moduleId || '';
            document.getElementById('activity-lesson-id').value = lessonId || '';
            openModal('add-activity-modal');
        }
        
        // Toast Notifications
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = type === 'success' ? '#51cf66' : '#ff6b6b';
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        // AJAX Helper
        function makeAjaxRequest(action, formData, callback) {
            formData.append('action', action);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(callback)
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Module form submission
            document.getElementById('add-module-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                makeAjaxRequest('create_module', formData, (response) => {
                    if (response.success) {
                        showToast('Module created successfully!', 'success');
                        closeModal('add-module-modal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        const errorMessages = Object.values(response.errors || {}).join('\n') || 'Failed to create module';
                        showToast(errorMessages, 'error');
                    }
                });
            });
            
            // Lesson form submission
            document.getElementById('add-lesson-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                makeAjaxRequest('create_lesson', formData, (response) => {
                    if (response.success) {
                        showToast('Lesson created successfully!', 'success');
                        closeModal('add-lesson-modal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        const errorMessages = Object.values(response.errors || {}).join('\n') || 'Failed to create lesson';
                        showToast(errorMessages, 'error');
                    }
                });
            });
            
            // Activity form submission
            document.getElementById('add-activity-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                makeAjaxRequest('create_activity', formData, (response) => {
                    if (response.success) {
                        showToast('Activity created successfully!', 'success');
                        closeModal('add-activity-modal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        const errorMessages = Object.values(response.errors || {}).join('\n') || 'Failed to create activity';
                        showToast(errorMessages, 'error');
                    }
                });
            });
            
            // Show/hide video URL field based on lesson type
            document.getElementById('lesson-type').addEventListener('change', function() {
                const videoFields = document.querySelector('.video-fields');
                if (this.value === 'video') {
                    videoFields.style.display = 'flex';
                    document.getElementById('lesson-video').required = true;
                } else {
                    videoFields.style.display = 'none';
                    document.getElementById('lesson-video').required = false;
                }
            });
            
            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    e.target.classList.remove('active');
                }
            });
        });
        
        // Helper function for lesson icons (reuse from existing codebase)
        function getLessonIcon(lessonType) {
            const icons = {
                'text': 'fa-file-alt',
                'video': 'fa-video',
                'quiz': 'fa-question-circle',
                'assignment': 'fa-tasks',
                'interactive': 'fa-laptop-code'
            };
            return icons[lessonType] || 'fa-file';
        }
    </script>
</body>
</html>