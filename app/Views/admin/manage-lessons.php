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
require_once '../../Controllers/Admin/AdminCourseController.php';
require_once '../../Controllers/Admin/AdminLessonController.php';
require_once '../../Models/LMSUtilities.php';

// Get user ID from session
$userId = $_SESSION['id'] ?? 0;

// Initialize controllers
$courseController = new AdminCourseController($conn);
$lessonController = new AdminLessonController($conn);

// Get section ID from URL
$sectionId = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($sectionId <= 0) {
    header("Location: manage-courses.php");
    exit;
}

// Get section details and course info
$section = $lessonController->getSectionDetails($sectionId);

if (!$section) {
    header("Location: manage-courses.php");
    exit;
}

$courseId = $section['course_id'];
$course = $courseController->getCourseDetails($courseId);

// Handle lesson actions
$message = '';
$messageType = '';

// Create new lesson
if (isset($_POST['action']) && $_POST['action'] == 'create_lesson') {
    $lessonData = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'lesson_type' => $_POST['lesson_type'] ?? 'text',
        'video_url' => $_POST['video_url'] ?? '',
        'duration_minutes' => intval($_POST['duration_minutes'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0
    ];
    
    if (empty($lessonData['title'])) {
        $message = "Lesson title is required.";
        $messageType = "danger";
    } else {
        $lessonId = $lessonController->createLesson($sectionId, $lessonData);
        
        if ($lessonId) {
            $message = "Lesson created successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to create lesson.";
            $messageType = "danger";
        }
    }
}

// Update lesson
if (isset($_POST['action']) && $_POST['action'] == 'update_lesson') {
    $lessonId = intval($_POST['lesson_id'] ?? 0);
    $lessonData = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'lesson_type' => $_POST['lesson_type'] ?? 'text',
        'video_url' => $_POST['video_url'] ?? '',
        'duration_minutes' => intval($_POST['duration_minutes'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0
    ];
    
    if ($lessonId <= 0 || empty($lessonData['title'])) {
        $message = "Lesson ID and title are required.";
        $messageType = "danger";
    } else {
        $result = $lessonController->updateLesson($lessonId, $lessonData);
        
        if ($result) {
            $message = "Lesson updated successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to update lesson.";
            $messageType = "danger";
        }
    }
}

// Delete lesson
if (isset($_GET['delete_lesson']) && $_GET['delete_lesson'] > 0) {
    $lessonId = intval($_GET['delete_lesson']);
    $result = $lessonController->deleteLesson($lessonId);
    
    if ($result) {
        $message = "Lesson deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete lesson.";
        $messageType = "danger";
    }
}

// Update lesson order
if (isset($_POST['action']) && $_POST['action'] == 'update_order') {
    $lessonOrders = $_POST['lesson_order'] ?? [];
    
    if (!empty($lessonOrders)) {
        $result = $lessonController->updateLessonOrder($lessonOrders);
        
        if ($result) {
            // Return success for AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            
            $message = "Lesson order updated successfully.";
            $messageType = "success";
        } else {
            // Return error for AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update lesson order.']);
                exit;
            }
            
            $message = "Failed to update lesson order.";
            $messageType = "danger";
        }
    }
}

// Get lessons for this section
$lessons = $lessonController->getSectionLessons($sectionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - <?php echo htmlspecialchars($section['title']); ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery for drag and drop functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- Tiny MCE for rich text editing -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        /* Additional styles for drag and drop */
        .draggable-item {
            cursor: move;
        }
        
        .ui-sortable-helper {
            background-color: #f8f9fa;
            border: 1px dashed #ccc;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .ui-sortable-placeholder {
            visibility: visible !important;
            background-color: #f0f0f0;
            border: 1px dashed #ccc;
            height: 60px;
            margin-bottom: 0.5rem;
        }
        
        /* Rich text editor styles */
        .tox-tinymce {
            border-radius: 6px;
            border-color: #ddd;
        }
        
        /* Styles for different lesson types */
        .lesson-item[data-type="video"] .lesson-icon {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .lesson-item[data-type="quiz"] .lesson-icon {
            background-color: #fff8e1;
            color: #ffc107;
        }
        
        .lesson-item[data-type="assignment"] .lesson-icon {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .lesson-item[data-type="interactive"] .lesson-icon {
            background-color: #f3e5f5;
            color: #9c27b0;
        }
        
        /* Dynamic form fields for different lesson types */
        .lesson-type-fields {
            display: none;
        }
        
        .lesson-type-fields.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Manage Lessons - <?php echo htmlspecialchars($section['title']); ?></h1>
            <div class="admin-actions">
                <a href="manage-sections.php?course_id=<?php echo $courseId; ?>" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Sections
                </a>
                <button id="add-lesson-btn" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Lesson
                </button>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">Course: <?php echo htmlspecialchars($course['title']); ?></h2>
                    <p>Section: <?php echo htmlspecialchars($section['title']); ?></p>
                </div>
                <div>
                    <span class="badge badge-primary"><?php echo $courseController->formatCourseType($course['type']); ?></span>
                    <span class="badge <?php echo $courseController->getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (empty($lessons)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No lessons yet</h3>
                <p>Get started by adding your first lesson.</p>
            </div>
            <?php else: ?>
            <div class="lesson-list sortable-container">
                <?php foreach ($lessons as $index => $lesson): ?>
                <div class="lesson-item draggable-item" data-lesson-id="<?php echo $lesson['id']; ?>" data-type="<?php echo $lesson['lesson_type']; ?>">
                    <div class="lesson-info">
                        <div class="lesson-icon">
                            <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i>
                        </div>
                        <div>
                            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            <div class="lesson-meta">
                                <span><?php echo ucfirst($lesson['lesson_type']); ?></span>
                                <?php if (!empty($lesson['duration_minutes'])): ?>
                                <span> • <?php echo $lesson['duration_minutes']; ?> min</span>
                                <?php endif; ?>
                                <?php if (!$lesson['is_published']): ?>
                                <span class="badge badge-warning">Draft</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lesson-actions">
                        <button class="icon-btn edit-lesson-btn" title="Edit Lesson" 
                                data-lesson-id="<?php echo $lesson['id']; ?>"
                                data-lesson-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                data-lesson-content="<?php echo htmlspecialchars($lesson['content'] ?? ''); ?>"
                                data-lesson-type="<?php echo $lesson['lesson_type']; ?>"
                                data-lesson-video-url="<?php echo htmlspecialchars($lesson['video_url'] ?? ''); ?>"
                                data-lesson-duration="<?php echo $lesson['duration_minutes']; ?>"
                                data-lesson-published="<?php echo $lesson['is_published']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?section_id=<?php echo $sectionId; ?>&delete_lesson=<?php echo $lesson['id']; ?>" class="icon-btn delete" title="Delete Lesson" onclick="return confirm('Are you sure you want to delete this lesson? This action cannot be undone.');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Lesson Modal -->
    <div class="modal-backdrop" id="add-lesson-modal">
        <div class="modal" style="width: 800px; max-width: 90%;">
            <div class="modal-header">
                <h3 class="modal-title">Add New Lesson</h3>
                <button type="button" class="modal-close" id="close-add-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-lesson-form" method="post">
                    <input type="hidden" name="action" value="create_lesson">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Lesson Title *</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lesson_type" class="form-label">Lesson Type</label>
                            <select id="lesson_type" name="lesson_type" class="form-control">
                                <option value="text">Text Lesson</option>
                                <option value="video">Video Lesson</option>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                                <option value="interactive">Interactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" min="1" value="15">
                        </div>
                    </div>
                    
                    <!-- Video URL field (for video lessons) -->
                    <div class="form-row lesson-type-fields" id="video-fields">
                        <div class="form-group">
                            <label for="video_url" class="form-label">Video URL</label>
                            <input type="url" id="video_url" name="video_url" class="form-control" placeholder="https://www.youtube.com/embed/...">
                            <div class="form-text">Enter a YouTube or Vimeo embed URL</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="content" class="form-label">Lesson Content</label>
                            <textarea id="content" name="content" class="form-control rich-text-editor" rows="10"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="is_published" name="is_published" class="form-check-input" checked>
                                <label for="is_published" class="form-check-label">Publish Immediately</label>
                            </div>
                            <div class="form-text">When checked, this lesson will be immediately visible to members.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn secondary" id="cancel-add">Cancel</button>
                <button type="button" class="admin-btn" id="submit-add-lesson">Add Lesson</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Lesson Modal -->
    <div class="modal-backdrop" id="edit-lesson-modal">
        <div class="modal" style="width: 800px; max-width: 90%;">
            <div class="modal-header">
                <h3 class="modal-title">Edit Lesson</h3>
                <button type="button" class="modal-close" id="close-edit-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-lesson-form" method="post">
                    <input type="hidden" name="action" value="update_lesson">
                    <input type="hidden" name="lesson_id" id="edit-lesson-id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-title" class="form-label">Lesson Title *</label>
                            <input type="text" id="edit-title" name="title" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-lesson-type" class="form-label">Lesson Type</label>
                            <select id="edit-lesson-type" name="lesson_type" class="form-control">
                                <option value="text">Text Lesson</option>
                                <option value="video">Video Lesson</option>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                                <option value="interactive">Interactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-duration-minutes" class="form-label">Duration (minutes)</label>
                            <input type="number" id="edit-duration-minutes" name="duration_minutes" class="form-control" min="1">
                        </div>
                    </div>
                    
                    <!-- Video URL field (for video lessons) -->
                    <div class="form-row lesson-type-fields" id="edit-video-fields">
                        <div class="form-group">
                            <label for="edit-video-url" class="form-label">Video URL</label>
                            <input type="url" id="edit-video-url" name="video_url" class="form-control" placeholder="https://www.youtube.com/embed/...">
                            <div class="form-text">Enter a YouTube or Vimeo embed URL</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-content" class="form-label">Lesson Content</label>
                            <textarea id="edit-content" name="content" class="form-control rich-text-editor" rows="10"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="edit-is-published" name="is_published" class="form-check-input">
                                <label for="edit-is-published" class="form-check-label">Publish Immediately</label>
                            </div>
                            <div class="form-text">When checked, this lesson will be immediately visible to members.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn secondary" id="cancel-edit">Cancel</button>
                <button type="button" class="admin-btn" id="submit-edit-lesson">Save Changes</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TinyMCE
            tinymce.init({
                selector: '.rich-text-editor',
                height: 400,
                plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
                menubar: 'file edit view insert format tools table help',
                toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
                content_style: 'body { font-family:Poppins,Arial,sans-serif; font-size:16px }'
            });
            
            // Add confirmation for delete actions
            const deleteButtons = document.querySelectorAll('.icon-btn.delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Add Lesson Modal Functionality
            const addLessonBtn = document.getElementById('add-lesson-btn');
            const addLessonModal = document.getElementById('add-lesson-modal');
            const closeAddModalBtn = document.getElementById('close-add-modal');
            const cancelAddBtn = document.getElementById('cancel-add');
            const submitAddLessonBtn = document.getElementById('submit-add-lesson');
            const addLessonForm = document.getElementById('add-lesson-form');
            
            addLessonBtn.addEventListener('click', function() {
                addLessonModal.classList.add('active');
                
                // Reset form
                addLessonForm.reset();
                
                // Reset TinyMCE
                tinymce.get('content').setContent('');
                
                // Show appropriate fields based on lesson type
                updateLessonTypeFields();
            });
            
            closeAddModalBtn.addEventListener('click', function() {
                addLessonModal.classList.remove('active');
            });
            
            cancelAddBtn.addEventListener('click', function() {
                addLessonModal.classList.remove('active');
            });
            
            submitAddLessonBtn.addEventListener('click', function() {
                // Update TinyMCE content before submission
                tinymce.get('content').save();
                
                if (addLessonForm.checkValidity()) {
                    addLessonForm.submit();
                } else {
                    addLessonForm.reportValidity();
                }
            });
            
            // Edit Lesson Modal Functionality
            const editLessonBtns = document.querySelectorAll('.edit-lesson-btn');
            const editLessonModal = document.getElementById('edit-lesson-modal');
            const closeEditModalBtn = document.getElementById('close-edit-modal');
            const cancelEditBtn = document.getElementById('cancel-edit');
            const submitEditLessonBtn = document.getElementById('submit-edit-lesson');
            const editLessonForm = document.getElementById('edit-lesson-form');
            
            editLessonBtns.forEach(button => {
                button.addEventListener('click', function() {
                    const lessonId = this.getAttribute('data-lesson-id');
                    const lessonTitle = this.getAttribute('data-lesson-title');
                    const lessonContent = this.getAttribute('data-lesson-content');
                    const lessonType = this.getAttribute('data-lesson-type');
                    const lessonVideoUrl = this.getAttribute('data-lesson-video-url');
                    const lessonDuration = this.getAttribute('data-lesson-duration');
                    const lessonPublished = this.getAttribute('data-lesson-published') === '1';
                    
                    // Set form values
                    document.getElementById('edit-lesson-id').value = lessonId;
                    document.getElementById('edit-title').value = lessonTitle;
                    document.getElementById('edit-lesson-type').value = lessonType;
                    document.getElementById('edit-duration-minutes').value = lessonDuration;
                    document.getElementById('edit-video-url').value = lessonVideoUrl;
                    document.getElementById('edit-is-published').checked = lessonPublished;
                    
                    // Set TinyMCE content
                    tinymce.get('edit-content').setContent(lessonContent);
                    
                    // Show appropriate fields based on lesson type
                    updateEditLessonTypeFields();
                    
                    editLessonModal.classList.add('active');
                });
            });
            
            closeEditModalBtn.addEventListener('click', function() {
                editLessonModal.classList.remove('active');
            });
            
            cancelEditBtn.addEventListener('click', function() {
                editLessonModal.classList.remove('active');
            });
            
            submitEditLessonBtn.addEventListener('click', function() {
                // Update TinyMCE content before submission
                tinymce.get('edit-content').save();
                
                if (editLessonForm.checkValidity()) {
                    editLessonForm.submit();
                } else {
                    editLessonForm.reportValidity();
                }
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === addLessonModal) {
                    addLessonModal.classList.remove('active');
                }
                
                if (event.target === editLessonModal) {
                    editLessonModal.classList.remove('active');
                }
            });
            
            // Show/hide fields based on lesson type
            const lessonTypeSelect = document.getElementById('lesson_type');
            const editLessonTypeSelect = document.getElementById('edit-lesson-type');
            
            function updateLessonTypeFields() {
                const lessonType = lessonTypeSelect.value;
                const allTypeFields = document.querySelectorAll('#add-lesson-modal .lesson-type-fields');
                
                allTypeFields.forEach(field => {
                    field.classList.remove('active');
                });
                
                if (lessonType === 'video') {
                    document.getElementById('video-fields').classList.add('active');
                }
            }
            
            function updateEditLessonTypeFields() {
                const lessonType = editLessonTypeSelect.value;
                const allTypeFields = document.querySelectorAll('#edit-lesson-modal .lesson-type-fields');
                
                allTypeFields.forEach(field => {
                    field.classList.remove('active');
                });
                
                if (lessonType === 'video') {
                    document.getElementById('edit-video-fields').classList.add('active');
                }
            }
            
            lessonTypeSelect.addEventListener('change', updateLessonTypeFields);
            editLessonTypeSelect.addEventListener('change', updateEditLessonTypeFields);
            
            // Initialize type fields on page load
            updateLessonTypeFields();
            
            // Implement drag and drop for lessons
            $(function() {
                $('.sortable-container').sortable({
                    handle: '.lesson-info',
                    placeholder: 'ui-sortable-placeholder',
                    update: function(event, ui) {
                        // Collect new order
                        const lessonOrders = {};
                        $('.lesson-item').each(function(index) {
                            const lessonId = $(this).data('lesson-id');
                            lessonOrders[lessonId] = index + 1;
                        });
                        
                        // Send AJAX request to update order
                        $.ajax({
                            url: 'manage-lessons.php?section_id=<?php echo $sectionId; ?>',
                            type: 'POST',
                            data: {
                                action: 'update_order',
                                lesson_order: lessonOrders
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Optional: Show success message
                                } else {
                                    // Handle error
                                    alert('Failed to update lesson order');
                                }
                            },
                            error: function() {
                                alert('An error occurred while updating lesson order');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>