<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin or mentor
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../../../login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'mentor')) {
    header("Location: ../../../home.php");
    exit;
}

// Include the auto-logout script to track inactivity
$sessionTimerPath = '../../Controllers/sessionTimer.php';
if (file_exists($sessionTimerPath)) {
    include $sessionTimerPath;
}

// Include database connection
require_once '../../../server.php';

// Include controllers
require_once '../../Controllers/Admin/AdminCourseController.php';
require_once '../../Models/LMSUtilities.php';

// Get user ID from session with validation - handle both 'id' and 'user_id' keys
$userId = 0;
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $userId = intval($_SESSION['id']);
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
    // Standardize the session by setting 'id' if it's missing
    $_SESSION['id'] = $userId;
}

// Clear any error messages from previous operations
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Validate that user ID exists and is valid
if ($userId <= 0) {
    error_log("Invalid user ID in session: " . print_r($_SESSION, true));
    // Try to get user info from database to fix session
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $userQuery = "SELECT id, name, surname FROM users WHERE username = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("s", $_SESSION['username']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult && $userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $userId = intval($userData['id']);
            $_SESSION['id'] = $userId;
            $_SESSION['user_id'] = $userId;
        }
    }
    
    // If still no valid user ID, redirect to login
    if ($userId <= 0) {
        session_destroy();
        header("Location: ../../../login.php");
        exit;
    }
}

// Initialize controller
$courseController = new AdminCourseController($conn);

// Set default values based on content type
$contentType = isset($_GET['type']) ? $_GET['type'] : 'full_course';
$contentTypeLabels = [
    'full_course' => 'Full Course',
    'short_course' => 'Short Course',
    'lesson' => 'Lesson',
    'skill_activity' => 'Skill Activity'
];

$contentLabel = $contentTypeLabels[$contentType] ?? 'Course';

// Process form submission
$message = '';
$messageType = '';
$formData = [
    'title' => '',
    'description' => '',
    'type' => $contentType,
    'difficulty_level' => 'Beginner',
    'duration' => '',
    'image_path' => '',
    'is_featured' => 0,
    'is_published' => 0,
    'status' => 'draft',
    'created_by' => $userId,
    'course_code' => '' // Will be auto-generated
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => $_POST['type'] ?? $contentType,
        'difficulty_level' => $_POST['difficulty_level'] ?? 'Beginner',
        'duration' => trim($_POST['duration'] ?? ''),
        'image_path' => '', // Will be updated if image is uploaded
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'status' => $_POST['status'] ?? 'draft',
        'created_by' => $userId,
        'course_code' => '' // Will be auto-generated in the model
    ];
    
    // Validate required fields
    if (empty($formData['title'])) {
        $message = "Title is required.";
        $messageType = "danger";
    } elseif (strlen($formData['title']) < 3) {
        $message = "Title must be at least 3 characters long.";
        $messageType = "danger";
    } else {
        // Process image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $targetDir = "../../../public/assets/uploads/images/courses/";
           
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $message = "Invalid image format. Please use JPEG, PNG, GIF, or WebP.";
                $messageType = "danger";
            } elseif ($_FILES['image']['size'] > 2097152) { // 2MB limit
                $message = "Image file is too large. Maximum size is 2MB.";
                $messageType = "danger";
            } else {
                // Generate a unique filename
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $targetFile = $targetDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $formData['image_path'] = $filename;
                } else {
                    $message = "Failed to upload image. Please try again.";
                    $messageType = "danger";
                }
            }
        }
        
        // Update status based on is_published
        if ($formData['is_published']) {
            $formData['status'] = 'active';
        }
        
        // Create course if validation passes
        if (empty($message)) {
            try {
                $courseId = $courseController->createCourse($formData);
                
                if ($courseId) {
                    // Set success message
                    $_SESSION['message'] = $contentLabel . " created successfully!";
                    $_SESSION['message_type'] = "success";
                    
                    // Redirect to course editing or section management
                    $redirectUrl = ($contentType == 'lesson' || $contentType == 'skill_activity') 
                        ? "edit-course.php?id=" . $courseId
                        : "manage-sections.php?course_id=" . $courseId;
                        
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    $message = "Failed to create " . strtolower($contentLabel) . ". Please check that you have permission and try again.";
                    $messageType = "danger";
                }
            } catch (Exception $e) {
                $message = "An error occurred while creating the " . strtolower($contentLabel) . ". Please try again.";
                $messageType = "danger";
                error_log("Course creation error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create <?php echo $contentLabel; ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Create New <?php echo $contentLabel; ?></h1>
            <div class="admin-actions">
                <a href="manage-courses.php" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <form method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3 class="form-section-title">Basic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" id="title" name="title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($formData['title']); ?>"
                                   placeholder="Enter a descriptive title for your <?php echo strtolower($contentLabel); ?>">
                            <div class="form-text">The course code will be automatically generated from this title.</div>
                        </div>
                                                
                        <div class="form-group">
                            <label for="type" class="form-label">Content Type</label>
                            <select id="type" name="type" class="form-control" required>
                                <option value="full_course" <?php echo $contentType == 'full_course' ? 'selected' : ''; ?>>Full Course</option>
                                <option value="short_course" <?php echo $contentType == 'short_course' ? 'selected' : ''; ?>>Short Course</option>
                                <option value="lesson" <?php echo $contentType == 'lesson' ? 'selected' : ''; ?>>Lesson</option>
                                <option value="skill_activity" <?php echo $contentType == 'skill_activity' ? 'selected' : ''; ?>>Skill Activity</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="difficulty_level" class="form-label">Difficulty Level</label>
                            <select id="difficulty_level" name="difficulty_level" class="form-control">
                                <option value="Beginner" <?php echo $formData['difficulty_level'] == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo $formData['difficulty_level'] == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo $formData['difficulty_level'] == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration" class="form-label">Estimated Duration</label>
                            <input type="text" id="duration" name="duration" class="form-control" 
                                   placeholder="e.g., 2 weeks, 5 hours, 30 minutes" 
                                   value="<?php echo htmlspecialchars($formData['duration']); ?>">
                            <div class="form-text">Estimated time to complete this content</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="5" 
                                      placeholder="Provide a detailed description of what learners will gain from this <?php echo strtolower($contentLabel); ?>..."><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Cover Image</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image" class="form-label">Upload Cover Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <div class="form-text">Recommended size: 1200x600 pixels. Max file size: 2MB. Supported formats: JPEG, PNG, GIF, WebP.</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Publication Settings</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft" <?php echo $formData['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="active" <?php echo $formData['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="archived" <?php echo $formData['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="is_published" name="is_published" class="form-check-input" <?php echo $formData['is_published'] ? 'checked' : ''; ?>>
                                <label for="is_published" class="form-check-label">Publish Immediately</label>
                            </div>
                            <div class="form-text">When checked, this content will be immediately visible to members.</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="is_featured" name="is_featured" class="form-check-input" <?php echo $formData['is_featured'] ? 'checked' : ''; ?>>
                                <label for="is_featured" class="form-check-label">Feature This Content</label>
                            </div>
                            <div class="form-text">Featured content appears prominently on the learning homepage.</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="admin-btn">
                        <i class="fas fa-save"></i> Create <?php echo $contentLabel; ?>
                    </button>
                    <a href="manage-courses.php" class="admin-btn secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle publication status based on is_published checkbox
            const publishedCheckbox = document.getElementById('is_published');
            const statusSelect = document.getElementById('status');
            
            publishedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    statusSelect.value = 'active';
                } else {
                    if (statusSelect.value === 'active') {
                        statusSelect.value = 'draft';
                    }
                }
            });
            
            // Update is_published checkbox based on status
            statusSelect.addEventListener('change', function() {
                if (this.value === 'active') {
                    publishedCheckbox.checked = true;
                } else {
                    publishedCheckbox.checked = false;
                }
            });
            
            // Content type selection affects form behavior
            const typeSelect = document.getElementById('type');
            
            typeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                const pageTitle = document.querySelector('.admin-title');
                const submitButton = document.querySelector('button[type="submit"]');
                
                // Update UI based on content type
                switch (selectedType) {
                    case 'full_course':
                        pageTitle.textContent = 'Create New Full Course';
                        submitButton.innerHTML = '<i class="fas fa-save"></i> Create Full Course';
                        break;
                    case 'short_course':
                        pageTitle.textContent = 'Create New Short Course';
                        submitButton.innerHTML = '<i class="fas fa-save"></i> Create Short Course';
                        break;
                    case 'lesson':
                        pageTitle.textContent = 'Create New Lesson';
                        submitButton.innerHTML = '<i class="fas fa-save"></i> Create Lesson';
                        break;
                    case 'skill_activity':
                        pageTitle.textContent = 'Create New Skill Activity';
                        submitButton.innerHTML = '<i class="fas fa-save"></i> Create Skill Activity';
                        break;
                }
            });
            
            // Form validation
            const form = document.querySelector('form');
            const titleInput = document.getElementById('title');
            
            form.addEventListener('submit', function(e) {
                if (titleInput.value.trim().length < 3) {
                    e.preventDefault();
                    alert('Title must be at least 3 characters long.');
                    titleInput.focus();
                    return false;
                }
            });
            
            // Real-time title validation
            titleInput.addEventListener('input', function() {
                const value = this.value.trim();
                if (value.length > 0 && value.length < 3) {
                    this.style.borderColor = '#ff6b6b';
                } else {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html>