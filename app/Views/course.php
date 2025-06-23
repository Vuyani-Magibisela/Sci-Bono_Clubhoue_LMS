<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: login.php");
    exit;
}

// Include the auto-logout script to track inactivity
include '../Controllers/sessionTimer.php';

// Include database connection
require_once '../../server.php';

// Include controllers
require_once '../Controllers/CourseController.php';
require_once '../Controllers/LessonController.php';
// Include utilities
require_once '../Models/LMSUtilities.php';

// Get user ID from session
$userId = $_SESSION['id'] ?? 0;

// Get course ID from URL
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId <= 0) {
    // Redirect to learn page if course ID is invalid
    header("Location: learn.php");
    exit;
}

// Initialize controllers
$courseController = new CourseController($conn);
$lessonController = new LessonController($conn);

// Process enrollment if requested
if (isset($_GET['enroll']) && $_GET['enroll'] == 1) {
    $courseController->enrollUser($userId, $courseId);
    header("Location: course.php?id=" . $courseId);
    exit;
}

// Get all course page data using the new method
$coursePageData = $courseController->getCourseDataForView($courseId, $userId);

if (!$coursePageData || !$coursePageData['course']) {
    // Redirect to learn page if course data is invalid or course not found
    header("Location: learn.php?error=course_not_found");
    exit;
}

$course = $coursePageData['course'];
$sections = $coursePageData['sections']; // This will now have lessons populated
$isEnrolled = $coursePageData['isEnrolled'];
// $userProgressData is an array like ['percent' => X, 'completed_lessons' => Y, 'total_lessons_in_progress' => Z]
// For the progress bar, we need the percentage.
$progressPercent = $coursePageData['progress']['percent'] ?? 0; 
$totalDuration = $coursePageData['totalDuration'];
$totalLessons = $coursePageData['totalLessons'];
$completedLessons = $coursePageData['completedLessons']; // This is calculated in getCourseDataForView


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../public/assets/css/course.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    </head>
<body>
    <?php include './admin/learn-header.php'; ?>
    
    <div class="course-container">
        <div class="course-header">
            <div class="course-banner">
                <?php if (isset($course['image_path']) && file_exists('../../public/assets/uploads/images/courses/' . $course['image_path'])): ?>
                    <img src="../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                <?php else: ?>
                    <img src="https://source.unsplash.com/random/1200x400?<?php echo urlencode(strtolower($course['title'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                <?php endif; ?>
                <div class="course-banner-overlay">
                    <a href="./admin/manage-courses.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="course-badges">
                        <span class="course-badge"><?php echo formatCourseType($course['type']); ?></span>
                        <span class="course-badge"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                    </div>
                    <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <div class="course-meta">
                        <div class="meta-item">
                            <span class="meta-icon"><i class="fas fa-clock"></i></span>
                            <span><?php echo formatDuration($totalDuration); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon"><i class="fas fa-list"></i></span>
                            <span><?php echo count($sections); ?> sections</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon"><i class="fas fa-file-alt"></i></span>
                            <span><?php echo $totalLessons; ?> lessons</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon"><i class="fas fa-users"></i></span>
                            <span><?php echo $course['enrollment_count']; ?> students</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon"><i class="fas fa-calendar-alt"></i></span>
                            <span>Last updated: <?php echo date('M j, Y', strtotime($course['updated_at'] ?? $course['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="course-content">
            <div class="course-main">
                <div class="course-description">
                    <h2 class="description-title">About This Course</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                    </div>
                </div>
                
                <div class="course-sections">
                    <?php foreach ($sections as $index => $section): ?>
                    <div class="section <?php echo $index === 0 ? 'section-expanded' : ''; ?>">
                        <div class="section-header" onclick="toggleSection(this.parentNode)">
                            <div class="section-title">
                                <span class="section-number"><?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($section['title']); ?>
                            </div>
                            <div class="section-meta">
                                <?php 
                                $sectionLessons = count($section['lessons']);
                                $sectionCompleted = 0;
                                foreach ($section['lessons'] as $lesson) {
                                    if ($lesson['completed']) {
                                        $sectionCompleted++;
                                    }
                                }
                                ?>
                                <span><?php echo $sectionLessons; ?> lessons</span>
                                &bull;
                                <span><?php echo $sectionCompleted; ?>/<?php echo $sectionLessons; ?> completed</span>
                                <i class="fas fa-chevron-down section-toggle"></i>
                            </div>
                        </div>
                        <div class="section-content">
                            <?php if (!empty($section['description'])): ?>
                            <div class="section-description">
                                <?php echo htmlspecialchars($section['description']); ?>
                            </div>
                            <?php endif; ?>
                            <ul class="lesson-list">
                                <?php foreach ($section['lessons'] as $lesson): ?>
                                <li class="lesson-item <?php echo $lesson['completed'] ? 'lesson-completed' : ''; ?>">
                                    <div class="lesson-icon">
                                        <?php if ($lesson['completed']): ?>
                                        <i class="fas fa-check"></i>
                                        <?php else: ?>
                                        <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lesson-details">
                                        <div class="lesson-title">
                                            <span><?php echo htmlspecialchars($lesson['title']); ?></span>
                                        </div>
                                        <div class="lesson-meta">
                                            <div class="lesson-type">
                                                <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i>
                                                <span><?php echo ucfirst($lesson['lesson_type']); ?></span>
                                            </div>
                                            <div class="lesson-duration">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo $lesson['duration_minutes']; ?> min</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="course-sidebar">
                <div class="course-action-card">
                    <?php if ($isEnrolled): ?>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Your Progress</span>
                            <span><?php echo htmlspecialchars($progressPercent); ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?php echo htmlspecialchars($progressPercent); ?>%"></div>
                        </div>
                        <div class="progress-stats" style="margin-top:0.75rem; font-size:0.85rem; color:#777;">
                            <span><?php echo htmlspecialchars($completedLessons); ?> of <?php echo htmlspecialchars($totalLessons); ?> lessons completed</span>
                        </div>
                    </div>
                    <?php 
                    // Find the first uncompleted lesson or the first lesson if all are completed or none started
                    $continueLessonId = null;
                    if (!empty($sections) && !empty($sections[0]['lessons'])) {
                        $continueLessonId = $sections[0]['lessons'][0]['id']; // Default to first lesson of first section
                        $foundNext = false;
                        foreach ($sections as $s) {
                            if (isset($s['lessons']) && is_array($s['lessons'])) {
                                foreach ($s['lessons'] as $l) {
                                    if (isset($l['id']) && !$l['completed']) {
                                        $continueLessonId = $l['id'];
                                        $foundNext = true;
                                        break;
                                    }
                                }
                            }
                            if ($foundNext) break;
                        }
                    }
                    ?>
                    <a href="lesson.php?id=<?php echo $continueLessonId; ?>" class="action-button">Continue Learning</a>
                    <?php else: ?>
                    <a href="course.php?id=<?php echo $course['id']; ?>&enroll=1" class="action-button">Enroll Now</a>
                    <?php endif; ?>
                </div>
                
                <div class="course-info-card">
                    <ul class="info-list">
                        <li class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-signal"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Level</div>
                                <div class="info-value"><?php echo htmlspecialchars($course['difficulty_level']); ?></div>
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Duration</div>
                                <div class="info-value"><?php echo formatDuration($totalDuration); ?></div>
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Sections</div>
                                <div class="info-value"><?php echo count($sections); ?> sections, <?php echo $totalLessons; ?> lessons</div>
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Certificate</div>
                                <div class="info-value">Yes, upon completion</div>
                            </div>
                        </li>
                    </ul>
                    
                    <div class="instructor-info">
                        <h3 class="instructor-title">Instructor</h3>
                        <div class="instructor-card">
                            <div class="instructor-avatar">
                                <?php echo strtoupper(substr($course['author_name'], 0, 1)); ?>
                            </div>
                            <div class="instructor-details">
                                <div class="instructor-name"><?php echo htmlspecialchars($course['author_name'] . ' ' . $course['author_surname']); ?></div>
                                <div class="instructor-role">Clubhouse Mentor</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSection(section) {
            // Toggle expanded class
            section.classList.toggle('section-expanded');
            
            // If this is expanded, collapse others
            if (section.classList.contains('section-expanded')) {
                const allSections = document.querySelectorAll('.section');
                allSections.forEach(s => {
                    if (s !== section) {
                        s.classList.remove('section-expanded');
                    }
                });
            }
        }
        
        // Progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const width = progressBar.style.width;
                progressBar.style.width = '0%';
                
                setTimeout(() => {
                    progressBar.style.transition = 'width 1s ease';
                    progressBar.style.width = width;
                }, 300);
            }
        });
    </script>
    
<!-- Mobile Navigation (visible on mobile only) -->
<nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="./learn.php" class="mobile-menu-item active">
            <div class="mobile-menu-icon">
                <i class="fas fa-book"></i>
            </div>
            <span>Learn</span>
        </a>
        <a href="./messages.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <span>Messages</span>
        </a>
        <a href="./projects.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <span>Projects</span>
        </a>
        <a href="../profile.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-user"></i>
            </div>
            <span>Profile</span>
        </a>
    </nav>
</body>
</html>
