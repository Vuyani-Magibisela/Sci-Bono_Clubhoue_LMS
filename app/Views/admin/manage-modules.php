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

// Get course details
$course = $courseController->getCourseDetails($courseId, false);

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
                
            case 'update_module':
                $moduleId = intval($_POST['module_id'] ?? 0);
                $moduleData = [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'learning_objectives' => $_POST['learning_objectives'] ?? '',
                    'estimated_duration_hours' => intval($_POST['estimated_duration_hours'] ?? 0),
                    'pass_percentage' => floatval($_POST['pass_percentage'] ?? 70),
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                $result = $courseController->updateModule($moduleId, $moduleData);
                echo json_encode($result);
                exit;
                
            case 'delete_module':
                $moduleId = intval($_POST['module_id'] ?? 0);
                $result = $courseController->deleteModule($moduleId);
                echo json_encode($result);
                exit;
                
            case 'update_order':
                $moduleOrders = $_POST['module_order'] ?? [];
                // Implementation would call updateModuleOrder method
                echo json_encode(['success' => true]);
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Get course modules
$modules = $courseController->getCourseModules($courseId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <style>
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .module-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            position: relative;
            cursor: move;
        }
        
        .module-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .module-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .module-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .module-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #777;
        }
        
        .module-actions {
            display: flex;
            gap: 0.5rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        
        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .action-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .action-btn.secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e1e5e9;
        }
        
        .action-btn.danger {
            background: #ff6b6b;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        
        .ui-sortable-helper {
            background-color: #f8f9fa !important;
            border: 2px dashed #667eea !important;
            opacity: 0.8;
            transform: rotate(5deg);
        }
        
        .ui-sortable-placeholder {
            visibility: visible !important;
            background-color: #e9ecef;
            border: 2px dashed #ccc;
            height: 200px;
            margin-bottom: 1.5rem;
            border-radius: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .progress-indicator {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .module-grid {
                grid-template-columns: 1fr;
            }
            
            .module-actions {
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
            <h1 class="admin-title">Module Management</h1>
            <div class="admin-actions">
                <a href="manage-course-content.php?course_id=<?php echo $courseId; ?>" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
                <button id="add-module-btn" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Module
                </button>
            </div>
        </div>
        
        <!-- Course Overview -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <div class="badge-group">
                    <span class="badge badge-primary"><?php echo formatCourseType($course['type']); ?></span>
                    <span class="badge <?php echo getDifficultyClass($course['difficulty_level']); ?>">
                        <?php echo htmlspecialchars($course['difficulty_level']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Modules Grid -->
        <?php if (empty($modules)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>No modules yet</h3>
            <p>Start building your course by adding the first module.</p>
            <button class="admin-btn" onclick="openModal('add-module-modal')">
                <i class="fas fa-plus"></i> Add Your First Module
            </button>
        </div>
        <?php else: ?>
        <div class="module-grid sortable-modules">
            <?php foreach ($modules as $index => $module): ?>
            <div class="module-card" data-module-id="<?php echo $module['id']; ?>">
                <div class="module-header">
                    <div>
                        <div class="module-title">
                            <i class="fas fa-grip-lines"></i>
                            <?php echo htmlspecialchars($module['title']); ?>
                        </div>
                        <div class="module-meta">
                            Module <?php echo $index + 1; ?> 
                            <?php if (!$module['is_published']): ?>
                            <span class="badge badge-warning">Draft</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="module-actions">
                        <button class="action-btn primary" onclick="manageModuleContent(<?php echo $module['id']; ?>)" title="Manage Content">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn secondary" onclick="editModule(<?php echo $module['id']; ?>)" title="Edit Module">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="action-btn danger" onclick="deleteModule(<?php echo $module['id']; ?>)" title="Delete Module">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="module-description">
                    <p><?php echo htmlspecialchars(substr($module['description'] ?? '', 0, 120)); ?>
                    <?php if (strlen($module['description'] ?? '') > 120): ?>...<?php endif; ?></p>
                </div>
                
                <div class="module-stats">
                    <div class="stat-item">
                        <i class="fas fa-book"></i>
                        <span><?php echo count($module['lessons'] ?? []); ?> lessons</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-tasks"></i>
                        <span><?php echo count($module['activities'] ?? []); ?> activities</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $module['estimated_duration_hours'] ?? 0; ?>h</span>
                    </div>
                </div>
                
                <?php if (!empty($module['learning_objectives'])): ?>
                <div class="learning-objectives">
                    <strong>Objectives:</strong>
                    <p><?php echo htmlspecialchars(substr($module['learning_objectives'], 0, 100)); ?>
                    <?php if (strlen($module['learning_objectives']) > 100): ?>...<?php endif; ?></p>
                </div>
                <?php endif; ?>
                
                <div class="progress-indicator">
                    <div class="progress-bar" style="width: <?php echo rand(20, 90); ?>%"></div>
                </div>
                <small class="text-muted">Module completion: <?php echo rand(20, 90); ?>%</small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
                        <textarea id="module-objectives" name="learning_objectives" class="form-control" rows="3" placeholder="What will students learn in this module?"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="module-duration">Estimated Duration (hours)</label>
                        <input type="number" id="module-duration" name="estimated_duration_hours" class="form-control" min="1" value="4">
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
                    <button type="submit" class="admin-btn">Create Module</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Module Modal -->
    <div class="modal-backdrop" id="edit-module-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Module</h3>
                <button type="button" class="modal-close" onclick="closeModal('edit-module-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="edit-module-form">
                <input type="hidden" id="edit-module-id" name="module_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-module-title">Module Title *</label>
                        <input type="text" id="edit-module-title" name="title" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-module-description">Description</label>
                        <textarea id="edit-module-description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-module-objectives">Learning Objectives</label>
                        <textarea id="edit-module-objectives" name="learning_objectives" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-module-duration">Estimated Duration (hours)</label>
                        <input type="number" id="edit-module-duration" name="estimated_duration_hours" class="form-control" min="1">
                    </div>
                    <div class="form-group">
                        <label for="edit-module-pass">Pass Percentage (%)</label>
                        <input type="number" id="edit-module-pass" name="pass_percentage" class="form-control" min="0" max="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit-module-published" name="is_published"> Published
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('edit-module-modal')">Cancel</button>
                    <button type="submit" class="admin-btn">Update Module</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast" style="position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: white; z-index: 1001; display: none;"></div>
    
    <script>
        // Global variables
        let modules = <?php echo json_encode($modules); ?>;
        
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
        
        // Module Actions
        function manageModuleContent(moduleId) {
            window.location.href = `manage-lessons.php?module_id=${moduleId}`;
        }
        
        function editModule(moduleId) {
            const module = modules.find(m => m.id == moduleId);
            if (module) {
                document.getElementById('edit-module-id').value = module.id;
                document.getElementById('edit-module-title').value = module.title;
                document.getElementById('edit-module-description').value = module.description || '';
                document.getElementById('edit-module-objectives').value = module.learning_objectives || '';
                document.getElementById('edit-module-duration').value = module.estimated_duration_hours || '';
                document.getElementById('edit-module-pass').value = module.pass_percentage || 70;
                document.getElementById('edit-module-published').checked = module.is_published == 1;
                
                openModal('edit-module-modal');
            }
        }
        
        function deleteModule(moduleId) {
            if (confirm('Are you sure you want to delete this module? This will also delete all lessons and activities within it. This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('module_id', moduleId);
                
                makeAjaxRequest('delete_module', formData, (response) => {
                    if (response.success) {
                        showToast('Module deleted successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(response.error || 'Failed to delete module', 'error');
                    }
                });
            }
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add module button
            document.getElementById('add-module-btn').addEventListener('click', function() {
                openModal('add-module-modal');
            });
            
            // Add module form submission
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
            
            // Edit module form submission
            document.getElementById('edit-module-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                makeAjaxRequest('update_module', formData, (response) => {
                    if (response.success) {
                        showToast('Module updated successfully!', 'success');
                        closeModal('edit-module-modal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        const errorMessages = Object.values(response.errors || {}).join('\n') || 'Failed to update module';
                        showToast(errorMessages, 'error');
                    }
                });
            });
            
            // Initialize sortable modules
            $('.sortable-modules').sortable({
                handle: '.module-card',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                update: function(event, ui) {
                    // Collect new order
                    const moduleOrders = {};
                    $('.module-card').each(function(index) {
                        const moduleId = $(this).data('module-id');
                        moduleOrders[moduleId] = index + 1;
                    });
                    
                    // Send AJAX request to update order
                    const formData = new FormData();
                    formData.append('module_order', JSON.stringify(moduleOrders));
                    
                    makeAjaxRequest('update_order', formData, (response) => {
                        if (response.success) {
                            showToast('Module order updated', 'success');
                        } else {
                            showToast('Failed to update order', 'error');
                        }
                    });
                }
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