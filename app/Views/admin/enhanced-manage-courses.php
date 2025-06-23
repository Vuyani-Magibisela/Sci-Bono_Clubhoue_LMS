<?php
session_start();
// Check if user is logged in and is an admin or mentor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || 
    ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'mentor')) {
    header("Location: ../../login.php");
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

// Initialize controller
$courseController = new CourseController($conn);

// Handle form submissions
$message = '';
$messageType = '';
$showModal = false;

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_course':
                $courseData = [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'learning_objectives' => $_POST['learning_objectives'] ?? '',
                    'course_requirements' => $_POST['course_requirements'] ?? '',
                    'type' => $_POST['type'] ?? 'full_course',
                    'difficulty_level' => $_POST['difficulty_level'] ?? 'Beginner',
                    'duration' => $_POST['duration'] ?? '',
                    'estimated_duration_hours' => intval($_POST['estimated_duration_hours'] ?? 0),
                    'max_enrollments' => intval($_POST['max_enrollments'] ?? 0),
                    'pass_percentage' => floatval($_POST['pass_percentage'] ?? 70),
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'is_published' => isset($_POST['is_published']) ? 1 : 0,
                    'status' => $_POST['status'] ?? 'draft',
                    'created_by' => $userId
                ];
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $uploadDir = '../../../public/assets/uploads/images/courses/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . time() . '.' . $extension;
                    $targetFile = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                        $courseData['image_path'] = $filename;
                    }
                }
                
                $result = $courseController->createCourse($courseData);
                echo json_encode($result);
                exit;
                
            case 'update_course_status':
                $courseId = intval($_POST['course_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                
                if ($courseId > 0 && in_array($status, ['active', 'draft', 'archived'])) {
                    $course = $courseController->getCourseDetails($courseId, false);
                    if ($course) {
                        $courseData = array_merge($course, ['status' => $status]);
                        if ($status == 'active') {
                            $courseData['is_published'] = 1;
                        }
                        $result = $courseController->updateCourse($courseId, $courseData);
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Course not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                }
                exit;
                
            case 'toggle_featured':
                $courseId = intval($_POST['course_id'] ?? 0);
                $featured = intval($_POST['featured'] ?? 0);
                
                if ($courseId > 0) {
                    $course = $courseController->getCourseDetails($courseId, false);
                    if ($course) {
                        $courseData = array_merge($course, ['is_featured' => $featured]);
                        $result = $courseController->updateCourse($courseId, $courseData);
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Course not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
                }
                exit;
                
            case 'delete_course':
                $courseId = intval($_POST['course_id'] ?? 0);
                if ($courseId > 0) {
                    $result = $courseController->deleteCourse($courseId);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
                }
                exit;
                
            case 'get_course_stats':
                $courseId = intval($_POST['course_id'] ?? 0);
                if ($courseId > 0) {
                    $stats = $courseController->getCourseStatistics($courseId);
                    echo json_encode(['success' => true, 'stats' => $stats]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
                }
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Handle regular form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    if (isset($_POST['create_course'])) {
        $showModal = true;
    }
}

// Get filter parameters
$filters = [
    'type' => $_GET['type'] ?? 'all',
    'status' => $_GET['status'] ?? '',
    'difficulty_level' => $_GET['difficulty_level'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Build filters for the model
$modelFilters = [];
if ($filters['type'] !== 'all') {
    $modelFilters['type'] = $filters['type'];
}
if (!empty($filters['status'])) {
    $modelFilters['status'] = $filters['status'];
}
if (!empty($filters['difficulty_level'])) {
    $modelFilters['difficulty_level'] = $filters['difficulty_level'];
}

// Get all courses
$allCourses = $courseController->getAllCourses($modelFilters);

// Apply search filter
if (!empty($filters['search'])) {
    $searchTerm = strtolower($filters['search']);
    $allCourses = array_filter($allCourses, function($course) use ($searchTerm) {
        return strpos(strtolower($course['title']), $searchTerm) !== false ||
               strpos(strtolower($course['description']), $searchTerm) !== false;
    });
}

// Calculate statistics
$stats = [
    'total' => count($allCourses),
    'full_course' => count(array_filter($allCourses, fn($c) => $c['type'] === 'full_course')),
    'short_course' => count(array_filter($allCourses, fn($c) => $c['type'] === 'short_course')),
    'lesson' => count(array_filter($allCourses, fn($c) => $c['type'] === 'lesson')),
    'skill_activity' => count(array_filter($allCourses, fn($c) => $c['type'] === 'skill_activity')),
    'published' => count(array_filter($allCourses, fn($c) => $c['is_published'] == 1)),
    'draft' => count(array_filter($allCourses, fn($c) => $c['status'] === 'draft')),
    'featured' => count(array_filter($allCourses, fn($c) => $c['is_featured'] == 1))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Course Management - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced styles for the new features */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .filter-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .course-card {
            position: relative;
            overflow: hidden;
        }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .action-btn.success {
            background: #51cf66;
            color: white;
        }
        
        .action-btn.warning {
            background: #ffd43b;
            color: #333;
        }
        
        .action-btn.danger {
            background: #ff6b6b;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            z-index: 1001;
            display: none;
        }
        
        .toast.success {
            background: #51cf66;
        }
        
        .toast.error {
            background: #ff6b6b;
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Learning Content Management</h1>
            <div class="admin-actions">
                <button id="create-course-btn" class="admin-btn">
                    <i class="fas fa-plus"></i> Create New Content
                </button>
                <a href="./skill-activities.php" class="admin-btn secondary">
                    <i class="fas fa-tools"></i> Skill Activities
                </a>
            </div>
        </div>
        
        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Content</div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-number"><?php echo $stats['published']; ?></div>
                <div class="stat-label">Published</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['full_course'] + $stats['short_course']; ?></div>
                <div class="stat-label">Courses</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['lesson'] + $stats['skill_activity']; ?></div>
                <div class="stat-label">Activities</div>
            </div>
        </div>
        
        <!-- Advanced Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="search">Search Content</label>
                        <input type="text" id="search" name="search" class="filter-input" 
                               placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="type">Content Type</label>
                        <select id="type" name="type" class="filter-input">
                            <option value="all" <?php echo $filters['type'] === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="full_course" <?php echo $filters['type'] === 'full_course' ? 'selected' : ''; ?>>Full Course</option>
                            <option value="short_course" <?php echo $filters['type'] === 'short_course' ? 'selected' : ''; ?>>Short Course</option>
                            <option value="lesson" <?php echo $filters['type'] === 'lesson' ? 'selected' : ''; ?>>Lesson</option>
                            <option value="skill_activity" <?php echo $filters['type'] === 'skill_activity' ? 'selected' : ''; ?>>Skill Activity</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="filter-input">
                            <option value="" <?php echo $filters['status'] === '' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="draft" <?php echo $filters['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="archived" <?php echo $filters['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="difficulty_level">Difficulty</label>
                        <select id="difficulty_level" name="difficulty_level" class="filter-input">
                            <option value="" <?php echo $filters['difficulty_level'] === '' ? 'selected' : ''; ?>>All Levels</option>
                            <option value="Beginner" <?php echo $filters['difficulty_level'] === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo $filters['difficulty_level'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo $filters['difficulty_level'] === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="admin-btn">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Course Grid -->
        <div class="courses-grid">
            <?php if (empty($allCourses)): ?>
            <div class="admin-card">
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No content found</h3>
                    <p>Get started by creating new learning content.</p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($allCourses as $course): ?>
                <div class="course-card" data-course-id="<?php echo $course['id']; ?>">
                    <div class="course-card-image">
                        <?php if (isset($course['image_path']) && !empty($course['image_path'])): ?>
                            <img src="../../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <img src="https://picsum.photos/seed/<?php echo $course['id']; ?>/600/400" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="course-card-content">
                        <div class="card-badges">
                            <span class="badge badge-primary"><?php echo $courseController->formatCourseType($course['type']); ?></span>
                            <span class="badge <?php echo $courseController->getDifficultyClass($course['difficulty_level']); ?>">
                                <?php echo htmlspecialchars($course['difficulty_level']); ?>
                            </span>
                            <span class="badge <?php echo $courseController->getStatusClass($course['status']); ?>">
                                <?php echo ucfirst($course['status']); ?>
                            </span>
                            <?php if ($course['is_featured']): ?>
                            <span class="badge badge-info">Featured</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="course-card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="course-card-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                        <div class="course-card-info">
                            <small>Created by: <?php echo htmlspecialchars($course['creator_name'] . ' ' . $course['creator_surname']); ?></small>
                        </div>
                        <div class="course-card-stats">
                            <div class="course-card-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo intval($course['enrollment_count'] ?? 0); ?> enrolled</span>
                            </div>
                            <div class="course-card-stat">
                                <i class="fas fa-list"></i>
                                <span><?php echo intval($course['module_count'] ?? 0); ?> modules</span>
                            </div>
                            <div class="course-card-stat">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo intval($course['lesson_count'] ?? 0); ?> lessons</span>
                            </div>
                            <div class="course-card-stat">
                                <i class="fas fa-tasks"></i>
                                <span><?php echo intval($course['activity_count'] ?? 0); ?> activities</span>
                            </div>
                        </div>
                        <div class="course-actions">
                            <button class="action-btn primary" onclick="viewCourse(<?php echo $course['id']; ?>)" title="View Course">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn primary" onclick="manageCourse(<?php echo $course['id']; ?>)" title="Manage Content">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn <?php echo $course['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                    onclick="toggleStatus(<?php echo $course['id']; ?>, '<?php echo $course['status'] === 'active' ? 'draft' : 'active'; ?>')" 
                                    title="<?php echo $course['status'] === 'active' ? 'Set to Draft' : 'Publish'; ?>">
                                <i class="fas <?php echo $course['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                            </button>
                            <button class="action-btn <?php echo $course['is_featured'] ? 'warning' : 'primary'; ?>" 
                                    onclick="toggleFeatured(<?php echo $course['id']; ?>, <?php echo $course['is_featured'] ? 0 : 1; ?>)" 
                                    title="<?php echo $course['is_featured'] ? 'Remove Featured' : 'Feature'; ?>">
                                <i class="fas <?php echo $course['is_featured'] ? 'fa-star' : 'fa-star'; ?>"></i>
                            </button>
                            <button class="action-btn primary" onclick="viewStats(<?php echo $course['id']; ?>)" title="View Statistics">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteCourse(<?php echo $course['id']; ?>)" title="Delete Course">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create Course Modal -->
    <div class="modal-backdrop" id="create-course-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create New Learning Content</h3>
                <button type="button" class="modal-close" onclick="closeModal('create-course-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="create-course-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Content Type *</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="full_course">Full Course</option>
                            <option value="short_course">Short Course</option>
                            <option value="lesson">Single Lesson</option>
                            <option value="skill_activity">Skill Activity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="difficulty_level">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level" class="form-control">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="learning_objectives">Learning Objectives</label>
                        <textarea id="learning_objectives" name="learning_objectives" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration Description</label>
                        <input type="text" id="duration" name="duration" class="form-control" placeholder="e.g., 6 weeks, 2 hours">
                    </div>
                    <div class="form-group">
                        <label for="estimated_duration_hours">Estimated Hours</label>
                        <input type="number" id="estimated_duration_hours" name="estimated_duration_hours" class="form-control" min="1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_enrollments">Max Enrollments</label>
                        <input type="number" id="max_enrollments" name="max_enrollments" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label for="pass_percentage">Pass Percentage (%)</label>
                        <input type="number" id="pass_percentage" name="pass_percentage" class="form-control" value="70" min="0" max="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="image">Cover Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_featured" name="is_featured"> Featured Content
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_published" name="is_published"> Publish Immediately
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('create-course-modal')">Cancel</button>
                    <button type="submit" class="admin-btn">Create Content</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <script>
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
            toast.className = `toast ${type}`;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        // AJAX Helper
        function makeAjaxRequest(action, data, callback) {
            const formData = new FormData();
            formData.append('action', action);
            
            for (const key in data) {
                formData.append(key, data[key]);
            }
            
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
        
        // Course Actions
        function viewCourse(courseId) {
            window.open(`../course.php?id=${courseId}`, '_blank');
        }
        
        function manageCourse(courseId) {
            window.location.href = `manage-course-content.php?course_id=${courseId}`;
        }
        
        function toggleStatus(courseId, newStatus) {
            makeAjaxRequest('update_course_status', {
                course_id: courseId,
                status: newStatus
            }, (response) => {
                if (response.success) {
                    showToast(`Course status updated to ${newStatus}`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.error || 'Failed to update status', 'error');
                }
            });
        }
        
        function toggleFeatured(courseId, featured) {
            makeAjaxRequest('toggle_featured', {
                course_id: courseId,
                featured: featured
            }, (response) => {
                if (response.success) {
                    showToast(featured ? 'Course featured' : 'Course unfeatured', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.error || 'Failed to update featured status', 'error');
                }
            });
        }
        
        function deleteCourse(courseId) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                makeAjaxRequest('delete_course', {
                    course_id: courseId
                }, (response) => {
                    if (response.success) {
                        showToast('Course deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(response.error || 'Failed to delete course', 'error');
                    }
                });
            }
        }
        
        function viewStats(courseId) {
            makeAjaxRequest('get_course_stats', {
                course_id: courseId
            }, (response) => {
                if (response.success) {
                    const stats = response.stats;
                    const statsHtml = `
                        <div class="stats-popup">
                            <h4>Course Statistics</h4>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-label">Modules:</span>
                                    <span class="stat-value">${stats.total_modules}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Lessons:</span>
                                    <span class="stat-value">${stats.total_lessons}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Activities:</span>
                                    <span class="stat-value">${stats.total_activities}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Enrollments:</span>
                                    <span class="stat-value">${stats.enrollment_count}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Duration:</span>
                                    <span class="stat-value">${Math.round(stats.total_duration_minutes / 60)}h</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Completion Rate:</span>
                                    <span class="stat-value">${stats.completion_rate.toFixed(1)}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Create and show stats modal
                    const modal = document.createElement('div');
                    modal.className = 'modal-backdrop active';
                    modal.innerHTML = `
                        <div class="modal">
                            ${statsHtml}
                            <div class="form-actions">
                                <button type="button" class="admin-btn" onclick="this.closest('.modal-backdrop').remove()">Close</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                } else {
                    showToast('Failed to load statistics', 'error');
                }
            });
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Create course button
            document.getElementById('create-course-btn').addEventListener('click', function() {
                openModal('create-course-modal');
            });
            
            // Create course form submission
            document.getElementById('create-course-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'create_course');
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showToast('Content created successfully!', 'success');
                        closeModal('create-course-modal');
                        setTimeout(() => {
                            window.location.href = `manage-course-content.php?course_id=${response.course_id}`;
                        }, 1000);
                    } else {
                        if (response.errors) {
                            const errorMessages = Object.values(response.errors).join('\n');
                            showToast(errorMessages, 'error');
                        } else {
                            showToast('Failed to create content', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while creating content', 'error');
                });
            });
            
            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-backdrop')) {
                    e.target.classList.remove('active');
                }
            });
            
            // Handle course type change in form
            document.getElementById('type').addEventListener('change', function() {
                const type = this.value;
                const durationHoursGroup = document.getElementById('estimated_duration_hours').closest('.form-group');
                const maxEnrollmentsGroup = document.getElementById('max_enrollments').closest('.form-group');
                
                if (type === 'lesson' || type === 'skill_activity') {
                    durationHoursGroup.style.display = 'none';
                    maxEnrollmentsGroup.style.display = 'none';
                } else {
                    durationHoursGroup.style.display = 'block';
                    maxEnrollmentsGroup.style.display = 'block';
                }
            });
        });
    </script>
    
    <style>
        .stats-popup {
            padding: 1rem;
        }
        
        .stats-popup .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .stat-label {
            font-weight: 500;
        }
        
        .stat-value {
            font-weight: 700;
            color: #667eea;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .modal-header {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .course-card-description {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
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
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .course-actions {
                justify-content: center;
            }
            
            .stats-popup .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>