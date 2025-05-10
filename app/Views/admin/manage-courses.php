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

// Handle form submissions
$message = '';
$messageType = '';

// Delete course if requested
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $courseId = intval($_GET['delete']);
    if ($courseController->deleteCourse($courseId)) {
        $message = "Course deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete course.";
        $messageType = "danger";
    }
}

// Change course status if requested
if (isset($_GET['status']) && isset($_GET['id'])) {
    $courseId = intval($_GET['id']);
    $status = $_GET['status'];
    
    if (in_array($status, ['active', 'draft', 'archived'])) {
        if ($courseController->updateCourseStatus($courseId, $status)) {
            $message = "Course status updated to " . ucfirst($status) . ".";
            $messageType = "success";
        } else {
            $message = "Failed to update course status.";
            $messageType = "danger";
        }
    }
}

// Toggle featured status if requested
if (isset($_GET['feature']) && isset($_GET['id'])) {
    $courseId = intval($_GET['id']);
    $featured = $_GET['feature'] == '1' ? 1 : 0;
    
    if ($courseController->toggleFeatured($courseId, $featured)) {
        $message = $featured ? "Course is now featured." : "Course removed from featured.";
        $messageType = "success";
    } else {
        $message = "Failed to update featured status.";
        $messageType = "danger";
    }
}

// Get all courses for display
$allCourses = $courseController->getAllCourses();

// Filter courses by type if requested
$courseType = isset($_GET['type']) ? $_GET['type'] : 'all';
if ($courseType != 'all') {
    $filteredCourses = array_filter($allCourses, function($course) use ($courseType) {
        return $course['type'] == $courseType;
    });
    $allCourses = $filteredCourses;
}

// Parse courses by type for statistics
$coursesByType = [
    'full_course' => [],
    'short_course' => [],
    'lesson' => [],
    'skill_activity' => []
];

foreach ($allCourses as $course) {
    if (isset($course['type'])) {
        $coursesByType[$course['type']][] = $course;
    }
}

$courseTypeCounts = [
    'total' => count($allCourses),
    'full_course' => count($coursesByType['full_course']),
    'short_course' => count($coursesByType['short_course']),
    'lesson' => count($coursesByType['lesson']),
    'skill_activity' => count($coursesByType['skill_activity'])
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Sci-Bono Clubhouse</title>
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
            <h1 class="admin-title">Learning Content Management</h1>
            <div class="admin-actions">
                <a href="create-course.php" class="admin-btn">
                    <i class="fas fa-plus"></i> Add New Course
                </a>
                <a href="create-course.php?type=short_course" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Short Course
                </a>
                <a href="create-course.php?type=lesson" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Lesson
                </a>
                <a href="create-course.php?type=skill_activity" class="admin-btn">
                    <i class="fas fa-plus"></i> Add Skill Activity
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-tab-nav">
            <div class="admin-tab <?php echo $courseType == 'all' ? 'active' : ''; ?>" onclick="window.location.href='?type=all'">
                All (<?php echo $courseTypeCounts['total']; ?>)
            </div>
            <div class="admin-tab <?php echo $courseType == 'full_course' ? 'active' : ''; ?>" onclick="window.location.href='?type=full_course'">
                Full Courses (<?php echo $courseTypeCounts['full_course']; ?>)
            </div>
            <div class="admin-tab <?php echo $courseType == 'short_course' ? 'active' : ''; ?>" onclick="window.location.href='?type=short_course'">
                Short Courses (<?php echo $courseTypeCounts['short_course']; ?>)
            </div>
            <div class="admin-tab <?php echo $courseType == 'lesson' ? 'active' : ''; ?>" onclick="window.location.href='?type=lesson'">
                Lessons (<?php echo $courseTypeCounts['lesson']; ?>)
            </div>
            <div class="admin-tab <?php echo $courseType == 'skill_activity' ? 'active' : ''; ?>" onclick="window.location.href='?type=skill_activity'">
                Skill Activities (<?php echo $courseTypeCounts['skill_activity']; ?>)
            </div>
        </div>
        
        <div class="admin-content">
            <?php if (empty($allCourses)): ?>
            <div class="admin-card">
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No courses found</h3>
                    <p>Get started by creating a new learning content.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($allCourses as $course): ?>
                <div class="course-card">
                    <div class="course-card-image">
                        <?php if (isset($course['image_path']) && file_exists('../../public/assets/uploads/images/courses/' . $course['image_path'])): ?>
                            <img src="../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <img src="https://source.unsplash.com/random/600x400?<?php echo urlencode(strtolower($course['title'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="course-card-content">
                        <div class="card-badges">
                            <span class="badge badge-primary"><?php echo $courseController->formatCourseType($course['type']); ?></span>
                            <span class="badge <?php echo $courseController->getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                            <span class="badge <?php echo $course['status'] == 'active' ? 'badge-success' : ($course['status'] == 'draft' ? 'badge-warning' : 'badge-secondary'); ?>">
                                <?php echo ucfirst($course['status']); ?>
                            </span>
                            <?php if ($course['is_featured']): ?>
                            <span class="badge badge-info">Featured</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="course-card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <div class="course-card-info">
                            Created by: <?php echo htmlspecialchars($course['creator_name'] . ' ' . $course['creator_surname']); ?>
                        </div>
                        <div class="course-card-stats">
                            <div class="course-card-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo intval($course['enrollment_count']); ?> enrolled</span>
                            </div>
                            <div class="course-card-stat">
                                <i class="fas fa-list"></i>
                                <span><?php echo intval($course['section_count']); ?> sections</span>
                            </div>
                            <div class="course-card-stat">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo intval($course['lesson_count']); ?> lessons</span>
                            </div>
                        </div>
                        <div class="course-card-actions">
                            <a href="../course.php?id=<?php echo $course['id']; ?>" class="icon-btn view" title="View Course">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="icon-btn edit" title="Edit Course">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($course['status'] != 'active'): ?>
                            <a href="?status=active&id=<?php echo $course['id']; ?>" class="icon-btn publish" title="Publish Course">
                                <i class="fas fa-check-circle"></i>
                            </a>
                            <?php elseif ($course['status'] != 'draft'): ?>
                            <a href="?status=draft&id=<?php echo $course['id']; ?>" class="icon-btn draft" title="Set to Draft">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($course['is_featured']): ?>
                            <a href="?feature=0&id=<?php echo $course['id']; ?>" class="icon-btn featured" title="Remove from Featured">
                                <i class="fas fa-star"></i>
                            </a>
                            <?php else: ?>
                            <a href="?feature=1&id=<?php echo $course['id']; ?>" class="icon-btn not-featured" title="Add to Featured">
                                <i class="far fa-star"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="manage-sections.php?course_id=<?php echo $course['id']; ?>" class="icon-btn sections" title="Manage Sections">
                                <i class="fas fa-th-list"></i>
                            </a>
                            
                            <a href="?delete=<?php echo $course['id']; ?>" class="icon-btn delete" title="Delete Course" onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality needed for the admin interface
            
            // Example: Add confirmation for delete actions
            const deleteButtons = document.querySelectorAll('.icon-btn.delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Example: Add tooltip functionality
            const tooltips = document.querySelectorAll('[title]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', function() {
                    const title = this.getAttribute('title');
                    this.setAttribute('data-title', title);
                    this.setAttribute('title', '');
                    
                    const tooltipEl = document.createElement('div');
                    tooltipEl.className = 'tooltip';
                    tooltipEl.textContent = title;
                    document.body.appendChild(tooltipEl);
                    
                    const rect = this.getBoundingClientRect();
                    tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
                    tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 10 + 'px';
                    
                    this.tooltipElement = tooltipEl;
                });
                
                tooltip.addEventListener('mouseleave', function() {
                    this.setAttribute('title', this.getAttribute('data-title'));
                    if (this.tooltipElement) {
                        this.tooltipElement.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>