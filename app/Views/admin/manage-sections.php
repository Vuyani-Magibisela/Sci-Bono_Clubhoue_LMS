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
require_once '../../Models/LMSUtilities.php';

// Get user ID from session
$userId = $_SESSION['id'] ?? 0;

// Initialize controller
$courseController = new AdminCourseController($conn);

// Get course ID from URL
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($courseId <= 0) {
    header("Location: manage-courses.php");
    exit;
}

// Get course details
$course = $courseController->getCourseDetails($courseId);

if (!$course) {
    header("Location: manage-courses.php");
    exit;
}

// Handle section actions
$message = '';
$messageType = '';

// Create new section
if (isset($_POST['action']) && $_POST['action'] == 'create_section') {
    $sectionData = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if (empty($sectionData['title'])) {
        $message = "Section title is required.";
        $messageType = "danger";
    } else {
        $sectionId = $courseController->createSection($courseId, $sectionData);
        
        if ($sectionId) {
            $message = "Section created successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to create section.";
            $messageType = "danger";
        }
    }
}

// Update section
if (isset($_POST['action']) && $_POST['action'] == 'update_section') {
    $sectionId = intval($_POST['section_id'] ?? 0);
    $sectionData = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($sectionId <= 0 || empty($sectionData['title'])) {
        $message = "Section ID and title are required.";
        $messageType = "danger";
    } else {
        $result = $courseController->updateSection($sectionId, $sectionData);
        
        if ($result) {
            $message = "Section updated successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to update section.";
            $messageType = "danger";
        }
    }
}

// Delete section
if (isset($_GET['delete_section']) && $_GET['delete_section'] > 0) {
    $sectionId = intval($_GET['delete_section']);
    $result = $courseController->deleteSection($sectionId);
    
    if ($result) {
        $message = "Section deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete section.";
        $messageType = "danger";
    }
}

// Update section order
if (isset($_POST['action']) && $_POST['action'] == 'update_order') {
    $sectionOrders = $_POST['section_order'] ?? [];
    
    if (!empty($sectionOrders)) {
        $result = $courseController->updateSectionOrder($sectionOrders);
        
        if ($result) {
            // Return success for AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            
            $message = "Section order updated successfully.";
            $messageType = "success";
        } else {
            // Return error for AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update section order.']);
                exit;
            }
            
            $message = "Failed to update section order.";
            $messageType = "danger";
        }
    }
}

// Get course sections with lessons
$sections = $courseController->getCourseSections($courseId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery for drag and drop functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
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
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Manage Sections - <?php echo htmlspecialchars($course['title']); ?></h1>
            <div class="admin-actions">
                <a href="manage-courses.php" class="admin-btn secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
                <button id="add-section-btn" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Section
                </button>
                <a href="edit-course.php?id=<?php echo $courseId; ?>" class="admin-btn">
                    <i class="fas fa-edit"></i> Edit Course
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Course Sections</h2>
                <div>
                    <span class="badge badge-primary"><?php echo $courseController->formatCourseType($course['type']); ?></span>
                    <span class="badge <?php echo $courseController->getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                    <span class="badge <?php echo $course['status'] == 'active' ? 'badge-success' : ($course['status'] == 'draft' ? 'badge-warning' : 'badge-secondary'); ?>">
                        <?php echo ucfirst($course['status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if (empty($sections)): ?>
            <div class="empty-state">
                <i class="fas fa-list"></i>
                <h3>No sections yet</h3>
                <p>Get started by adding your first section.</p>
            </div>
            <?php else: ?>
            <div class="section-list sortable-container">
                <?php foreach ($sections as $index => $section): ?>
                <div class="section-item draggable-item" data-section-id="<?php echo $section['id']; ?>">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-grip-lines"></i>
                            <span><?php echo htmlspecialchars($section['title']); ?></span>
                        </div>
                        <div class="section-meta">
                            <span><?php echo count($section['lessons'] ?? []); ?> lessons</span>
                            <div class="section-actions">
                                <a href="manage-lessons.php?section_id=<?php echo $section['id']; ?>" class="icon-btn" title="Manage Lessons">
                                    <i class="fas fa-list"></i>
                                </a>
                                <button class="icon-btn edit-section-btn" title="Edit Section" 
                                        data-section-id="<?php echo $section['id']; ?>"
                                        data-section-title="<?php echo htmlspecialchars($section['title']); ?>"
                                        data-section-description="<?php echo htmlspecialchars($section['description'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?course_id=<?php echo $courseId; ?>&delete_section=<?php echo $section['id']; ?>" class="icon-btn delete" title="Delete Section" onclick="return confirm('Are you sure you want to delete this section? All lessons in this section will also be deleted. This action cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</body>