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

// Get filter parameters
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
$activityType = isset($_GET['type']) ? $_GET['type'] : 'all';

// Initialize controller
$courseController = new CourseController($conn);

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_activity':
                $activityData = [
                    'course_id' => intval($_POST['course_id'] ?? 0),
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
                
                $result = $courseController->updateActivity($activityId, $activityData);
                echo json_encode($result);
                exit;
                
            case 'delete_activity':
                $activityId = intval($_POST['activity_id'] ?? 0);
                $result = $courseController->deleteActivity($activityId);
                echo json_encode($result);
                exit;
                
            case 'get_activities':
                $filters = [
                    'course_id' => intval($_POST['course_id'] ?? 0),
                    'module_id' => intval($_POST['module_id'] ?? 0),
                    'lesson_id' => intval($_POST['lesson_id'] ?? 0),
                    'activity_type' => $_POST['activity_type'] ?? 'all'
                ];
                
                // Get activities based on filters
                $activities = $courseController->getActivities($filters);
                echo json_encode(['success' => true, 'activities' => $activities]);
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Get available courses for filtering
$courses = $courseController->getAllCourses();

// Get context information
$contextInfo = [];
if ($courseId > 0) {
    $contextInfo['course'] = $courseController->getCourseDetails($courseId, false);
    if ($moduleId > 0) {
        $contextInfo['modules'] = $courseController->getCourseModules($courseId);
        $contextInfo['current_module'] = array_filter($contextInfo['modules'], fn($m) => $m['id'] == $moduleId)[0] ?? null;
    }
}

// Get activities based on current filters
$activities = []; // This would be populated by the getActivities method
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Management - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .activity-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #ffd43b;
            position: relative;
        }
        
        .activity-card[data-type="quiz"] {
            border-left-color: #ff6b6b;
        }
        
        .activity-card[data-type="assignment"] {
            border-left-color: #51cf66;
        }
        
        .activity-card[data-type="project"] {
            border-left-color: #339af0;
        }
        
        .activity-card[data-type="assessment"] {
            border-left-color: #9c88ff;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .activity-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .activity-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .activity-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-badge {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
            text-align: center;
            font-size: 0.8rem;
        }
        
        .stat-badge .stat-number {
            font-weight: 600;
            color: #333;
            display: block;
        }
        
        .stat-badge .stat-label {
            color: #666;
            font-size: 0.7rem;
        }
        
        .activity-actions {
            display: flex;
            gap: 0.5rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        
        .type-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .type-icon.practical {
            background: rgba(255, 212, 59, 0.1);
            color: #ffd43b;
        }
        
        .type-icon.quiz {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }
        
        .type-icon.assignment {
            background: rgba(81, 207, 102, 0.1);
            color: #51cf66;
        }
        
        .type-icon.project {
            background: rgba(51, 154, 240, 0.1);
            color: #339af0;
        }
        
        .type-icon.assessment {
            background: rgba(156, 136, 255, 0.1);
            color: #9c88ff;
        }
        
        .context-breadcrumb {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .context-breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .context-breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quick-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .quick-stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
        
        @media (max-width: 768px) {
            .activity-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .activity-actions {
                position: static;
                margin-top: 1rem;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Activity Management</h1>
            <div class="admin-actions">
                <a href="enhanced-manage-courses.php" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
                <button id="add-activity-btn" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Activity
                </button>
            </div>
        </div>
        
        <!-- Context Breadcrumb -->
        <?php if (!empty($contextInfo)): ?>
        <div class="context-breadcrumb">
            <i class="fas fa-map-marker-alt"></i>
            <strong>Context:</strong>
            <?php if (isset($contextInfo['course'])): ?>
                <a href="manage-course-content.php?course_id=<?php echo $contextInfo['course']['id']; ?>">
                    <?php echo htmlspecialchars($contextInfo['course']['title']); ?>
                </a>
            <?php endif; ?>
            
            <?php if (isset($contextInfo['current_module'])): ?>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($contextInfo['current_module']['title']); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" class="form-control">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="module_id">Module</label>
                        <select id="module_id" name="module_id" class="form-control">
                            <option value="">All Modules</option>
                            <!-- Populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="activity_type">Activity Type</label>
                        <select id="activity_type" name="type" class="form-control">
                            <option value="all" <?php echo $activityType === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="practical" <?php echo $activityType === 'practical' ? 'selected' : ''; ?>>Practical</option>
                            <option value="assignment" <?php echo $activityType === 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                            <option value="project" <?php echo $activityType === 'project' ? 'selected' : ''; ?>>Project</option>
                            <option value="quiz" <?php echo $activityType === 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                            <option value="assessment" <?php echo $activityType === 'assessment' ? 'selected' : ''; ?>>Assessment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="admin-btn">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat-card">
                <div class="quick-stat-number" id="total-activities">0</div>
                <div class="quick-stat-label">Total Activities</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number" id="published-activities">0</div>
                <div class="quick-stat-label">Published</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number" id="draft-activities">0</div>
                <div class="quick-stat-label">Drafts</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number" id="avg-points">0</div>
                <div class="quick-stat-label">Avg Points</div>
            </div>
        </div>
        
        <!-- Activities Grid -->
        <div class="activity-grid" id="activities-container">
            <!-- Activities will be loaded here via JavaScript -->
        </div>
        
        <!-- Empty State -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="fas fa-tasks"></i>
            <h3>No activities found</h3>
            <p>Create your first activity to get started.</p>
            <button class="admin-btn" onclick="openModal('add-activity-modal')">
                <i class="fas fa-plus"></i> Create Activity
            </button>
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-course">Course *</label>
                        <select id="activity-course" name="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-module">Module (optional)</label>
                        <select id="activity-module" name="module_id" class="form-control">
                            <option value="">No Module (Course-level activity)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity-lesson">Lesson (optional)</label>
                        <select id="activity-lesson" name="lesson_id" class="form-control">
                            <option value="">No Lesson</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-title">Activity Title *</label>
                        <input type="text" id="activity-title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-type-select">Activity Type</label>
                        <select id="activity-type-select" name="activity_type" class="form-control">
                            <option value="practical">Practical Exercise</option>
                            <option value="assignment">Assignment</option>
                            <option value="project">Project</option>
                            <option value="quiz">Quiz</option>
                            <option value="assessment">Assessment</option>
                            <option value="skill_exercise">Skill Exercise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity-submission-type">Submission Type</label>
                        <select id="activity-submission-type" name="submission_type" class="form-control">
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
                        <textarea id="activity-instructions" name="instructions" class="form-control" rows="4" placeholder="Detailed instructions for completing this activity..."></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-resources">Resources Needed</label>
                        <textarea id="activity-resources" name="resources_needed" class="form-control" rows="2" placeholder="List any materials, tools, or resources needed..."></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-duration">Duration (minutes)</label>
                        <input type="number" id="activity-duration" name="estimated_duration_minutes" class="form-control" value="60" min="1">
                    </div>
                    <div class="form-group">
                        <label for="activity-max-points">Max Points</label>
                        <input type="number" id="activity-max-points" name="max_points" class="form-control" value="100" min="1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity-pass-points">Pass Points</label>
                        <input type="number" id="activity-pass-points" name="pass_points" class="form-control" value="70" min="1">
                    </div>
                    <div class="form-group">
                        <label for="activity-due-date">Due Date (optional)</label>
                        <input type="datetime-local" id="activity-due-date" name="due_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="activity-auto-grade" name="auto_grade"> Auto-grade submissions
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
                    <button type="submit" class="admin-btn">Create Activity</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: white; z-index: 1001; display: none;"></div>
    
    <script>
        // Global variables
        let currentActivities = [];
        let allCourses = <?php echo json_encode($courses); ?>;
        
        // Modal Management
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
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
        
        // Load activities based on current filters
        function loadActivities() {
            const formData = new FormData();
            formData.append('course_id', document.getElementById('course_id').value || '0');
            formData.append('module_id', document.getElementById('module_id').value || '0');
            formData.append('activity_type', document.getElementById('activity_type').value || 'all');
            
            makeAjaxRequest('get_activities', formData, (response) => {
                if (response.success) {
                    currentActivities = response.activities;
                    renderActivities(currentActivities);
                    updateQuickStats(currentActivities);
                } else {
                    showToast('Failed to load activities', 'error');
                }
            });
        }
        
        // Render activities in the grid
        function renderActivities(activities) {
            const container = document.getElementById('activities-container');
            const emptyState = document.getElementById('empty-state');
            
            if (activities.length === 0) {
                container.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            
            container.innerHTML = activities.map(activity => `
                <div class="activity-card" data-type="${activity.activity_type}" data-activity-id="${activity.id}">
                    <div class="activity-header">
                        <div class="type-icon ${activity.activity_type}">
                            <i class="fas ${getActivityIcon(activity.activity_type)}"></i>
                        </div>
                        <div class="activity-actions">
                            <button class="action-btn primary" onclick="editActivity(${activity.id})" title="Edit Activity">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn secondary" onclick="viewSubmissions(${activity.id})" title="View Submissions">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteActivity(${activity.id})" title="Delete Activity">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-meta">
                        ${activity.course_title || 'Unknown Course'}
                        ${activity.module_title ? ' → ' + activity.module_title : ''}
                        ${activity.lesson_title ? ' → ' + activity.lesson_title : ''}
                        ${activity.is_published ? '' : '<span class="badge badge-warning">Draft</span>'}
                    </div>
                    
                    <div class="activity-stats">
                        <div class="stat-badge">
                            <span class="stat-number">${activity.max_points}</span>
                            <span class="stat-label">Max Points</span>
                        </div>
                        <div class="stat-badge">
                            <span class="stat-number">${activity.estimated_duration_minutes}</span>
                            <span class="stat-label">Minutes</span>
                        </div>
                        <div class="stat-badge">
                            <span class="stat-number">${activity.submission_count || 0}</span>
                            <span class="stat-label">Submissions</span>
                        </div>
                    </div>
                    
                    ${activity.description ? `<p class="activity-description">${activity.description.substring(0, 100)}${activity.description.length > 100 ? '...' : ''}</p>` : ''}
                    
                    ${activity.due_date ? `<div class="due-date"><i class="fas fa-calendar"></i> Due: ${new Date(activity.due_date).toLocaleDateString()}</div>` : ''}
                </div>
            `).join('');
        }
        
        // Update quick stats
        function updateQuickStats(activities) {
            document.getElementById('total-activities').textContent = activities.length;
            document.getElementById('published-activities').textContent = activities.filter(a => a.is_published).length;
            document.getElementById('draft-activities').textContent = activities.filter(a => !a.is_published).length;
            
            const avgPoints = activities.length > 0 ? 
                Math.round(activities.reduce((sum, a) => sum + (a.max_points || 0), 0) / activities.length) : 0;
            document.getElementById('avg-points').textContent = avgPoints;
        }
        
        // Get activity icon
        function getActivityIcon(type) {
            const icons = {
                practical: 'fa-tools',
                assignment: 'fa-tasks',
                project: 'fa-project-diagram',
                quiz: 'fa-question-circle',
                assessment: 'fa-clipboard-check',
                skill_exercise: 'fa-dumbbell'
            };
            return icons[type] || 'fa-tasks';
        }
        
        // Activity actions
        function editActivity(activityId) {
            // Implementation for editing activity
            showToast('Edit functionality coming soon', 'info');
        }
        
        function viewSubmissions(activityId) {
            // Implementation for viewing submissions
            showToast('Submissions view coming soon', 'info');
        }
        
        function deleteActivity(activityId) {
            if (confirm('Are you sure you want to delete this activity? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('activity_id', activityId);
                
                makeAjaxRequest('delete_activity', formData, (response) => {
                    if (response.success) {
                        showToast('Activity deleted successfully!', 'success');
                        loadActivities(); // Reload activities
                    } else {
                        showToast(response.error || 'Failed to delete activity', 'error');
                    }
                });
            }
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial activities
            loadActivities();
            
            // Add activity button
            document.getElementById('add-activity-btn').addEventListener('click', function() {
                openModal('add-activity-modal');
            });
            
            // Filter form submission
            document.getElementById('filter-form').addEventListener('submit', function(e) {
                e.preventDefault();
                loadActivities();
            });
            
            // Course selection change
            document.getElementById('course_id').addEventListener('change', function() {
                loadActivities();
            });
            
            // Activity creation form
            document.getElementById('add-activity-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                makeAjaxRequest('create_activity', formData, (response) => {
                    if (response.success) {
                        showToast('Activity created successfully!', 'success');
                        closeModal('add-activity-modal');
                        loadActivities();
                        this.reset();
                    } else {
                        const errorMessages = Object.values(response.errors || {}).join('\n') || 'Failed to create activity';
                        showToast(errorMessages, 'error');
                    }
                });
            });
            
            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    e.target.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>