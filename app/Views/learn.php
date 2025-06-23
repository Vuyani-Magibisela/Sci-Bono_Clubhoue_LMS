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
require_once '../../config/config.php';
// // Add these helper functions
// function formatCourseType($type) {
//     return ucwords(str_replace('_', ' ', $type));
// }

// function getDifficultyClass($level) {
//     $classes = [
//         'Beginner' => 'badge-success',
//         'Intermediate' => 'badge-warning',
//         'Advanced' => 'badge-danger'
//     ];
//     return $classes[$level] ?? 'badge-primary';
// }

// Get user ID from session
$userId = $_SESSION['id'] ?? 0;

// Initialize controllers
$courseController = new CourseController($conn);
$lessonController = new LessonController($conn);

// Only process course-specific logic if an ID is provided
if (isset($_GET['id'])) {
    $courseId = intval($_GET['id']);
    
    if ($courseId > 0) {
        // Process enrollment if requested
        if (isset($_GET['enroll']) && $_GET['enroll'] == 1) {
            $courseController->enrollUser($userId, $courseId);
            header("Location: course.php?id=" . $courseId);
            exit;
        }

        // Get course details and sections
        $course = $courseController->getCourseDetails($courseId);
        $sections = $courseController->getCourseSections($courseId);
        $isEnrolled = $courseController->isUserEnrolled($userId, $courseId);
        $progress = $isEnrolled ? $courseController->getUserProgress($userId, $courseId) : 0;
    }
}

// Get all courses for display
$featuredCourses = $courseController->getFeaturedCourses();
$recommendedCourses = $courseController->getRecommendedCourses($userId);
$userEnrollments = $courseController->getUserEnrollments($userId);

// Rest of the HTML and display logic remains the same...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../public/assets/css/learn.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include  './admin/learn-header.php';
    ?>
    
    <div class="learn-container">
        <h1 class="page-title">Explore Learning</h1>
        
        <div class="search-bar">
            <input type="text" class="search-input" id="course-search" placeholder="Search for courses, lessons, and skill activities...">
        </div>
        
        <div class="course-filter">
            <div class="filter-btn active" data-filter="all">All</div>
            <div class="filter-btn" data-filter="full_course">Full Courses</div>
            <div class="filter-btn" data-filter="short_course">Short Courses</div>
            <div class="filter-btn" data-filter="lesson">Single Lessons</div>
            <div class="filter-btn" data-filter="skill_activity">Skill Activities</div>
        </div>
        
        <?php if (!empty($userEnrollments)): ?>
        <div class="section-title">
            <span>Continue Learning</span>
            <span class="view-all">View All</span>
        </div>
        <div class="cards-grid enrolled-courses">
            <?php foreach (array_slice($userEnrollments, 0, 4) as $course): ?>
            <div class="course-card">
                <div class="card-image">
                    <?php if (isset($course['image_path']) && file_exists('../../public/assets/uploads/images/courses/' . $course['image_path'])): ?>
                        <img src="../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php else: ?>
                        <img src="https://source.unsplash.com/random/600x400?<?php echo urlencode(strtolower($course['title'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <div class="card-badges">
                        <span class="badge badge-primary"><?php echo formatCourseType($course['type']); ?></span>
                        <span class="badge <?php echo getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%"></div>
                    </div>
                    <div class="progress-text"><?php echo $course['progress']; ?>% complete</div>
                    <div class="card-footer">
                        <span class="enrollment-status">Enrolled</span>
                        <a href="course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">Continue</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Featured Courses -->
        <div class="featured-section">
            <div class="section-title">
                <span>Featured Courses</span>
            </div>
            <div class="featured-grid">
                <?php foreach ($featuredCourses as $course): ?>
                <div class="featured-card">
                        <?php if (isset($course['image_path']) && !empty($course['image_path'])): ?>
                            <img src="../../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <img src="https://picsum.photos/seed/<?php echo $course['id']; ?>/600/400" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                    <div class="featured-overlay">
                        <div class="featured-badges">
                            <span class="featured-badge"><?php echo formatCourseType($course['type']); ?></span>
                            <span class="featured-badge"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                        </div>
                        <h3 class="featured-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recommended Courses -->
        <div class="section-title">
            <span>Recommended for You</span>
            <span class="view-all">View All</span>
        </div>
        <div class="cards-grid recommended-courses">
            <?php foreach ($recommendedCourses as $course): ?>
            <div class="course-card" data-type="<?php echo $course['type']; ?>">
                <div class="card-image">
                    <?php if (isset($course['image_path']) && !empty($course['image_path'])): ?>
                        <img src="../../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" 
                                alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php else: ?>
                        <img src="https://picsum.photos/seed/<?php echo $course['id']; ?>/600/400" 
                                alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <div class="card-badges">
                        <span class="badge badge-primary"><?php echo formatCourseType($course['type']); ?></span>
                        <span class="badge <?php echo getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="card-footer">
                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- All Courses Section -->
        <div class="section-title">
            <span>All Courses</span>
            <span class="view-all">View All</span>
        </div>
        <div class="cards-grid all-courses">
            <?php
            // Initialize the course arrays if they don't exist
            $fullCourses = [];
            $shortCourses = [];
            $singleLessons = [];
            $skillActivities = [];

            // Categorize courses by type
            if (!empty($allCourses)) {
                foreach ($allCourses as $course) {
                    if (isset($course['type'])) {
                        switch ($course['type']) {
                            case 'full_course':
                                $fullCourses[] = $course;
                                break;
                            case 'short_course':
                                $shortCourses[] = $course;
                                break;
                            case 'lesson':
                                $singleLessons[] = $course;
                                break;
                            case 'skill_activity':
                                $skillActivities[] = $course;
                                break;
                            default:
                                // Default to full course if type is not recognized
                                $fullCourses[] = $course;
                        }
                    } else {
                        // Default to full course if type is not set
                        $fullCourses[] = $course;
                    }
                }
            }

            // Now you can safely merge the arrays
            $allCoursesGrouped = array_merge($fullCourses, $shortCourses, $singleLessons, $skillActivities);
            // $allCourses = array_merge($fullCourses, $shortCourses, $singleLessons, $skillActivities);
            if (empty($allCourses)):
            ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-book"></i></div>
                <h3>No courses available</h3>
                <p>Check back soon for new learning opportunities!</p>
            </div>
            <?php else: 
                foreach ($allCourses as $course): 
            ?>
            <div class="course-card" data-type="<?php echo $course['type']; ?>">
                <div class="card-image">
                    <?php if (isset($course['image_path']) && file_exists('../../public/assets/uploads/images/courses/' . $course['image_path'])): ?>
                        <img src="../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php else: ?>
                        <img src="https://source.unsplash.com/random/600x400?<?php echo urlencode(strtolower($course['title'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <div class="card-badges">
                        <span class="badge badge-primary"><?php echo formatCourseType($course['type']); ?></span>
                        <span class="badge <?php echo getDifficultyClass($course['difficulty_level']); ?>"><?php echo htmlspecialchars($course['difficulty_level']); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="card-footer">
                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter buttons functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            const courseCards = document.querySelectorAll('.all-courses .course-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get filter value
                    const filterValue = this.getAttribute('data-filter');
                    
                    // Show/hide cards based on filter
                    courseCards.forEach(card => {
                        if (filterValue === 'all' || card.getAttribute('data-type') === filterValue) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Search functionality
            const searchInput = document.getElementById('course-search');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                // If search is empty, reset to showing all cards
                if (searchTerm === '') {
                    // Check which filter button is active
                    const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
                    
                    courseCards.forEach(card => {
                        if (activeFilter === 'all' || card.getAttribute('data-type') === activeFilter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    return;
                }
                
                // Otherwise, search by title
                courseCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // "View All" buttons
            const viewAllButtons = document.querySelectorAll('.view-all');
            viewAllButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // This would typically navigate to a dedicated page
                    // For now, let's scroll to the All Courses section
                    document.querySelector('.all-courses').scrollIntoView({
                        behavior: 'smooth'
                    });
                    
                    // Reset filter to show all courses
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    document.querySelector('[data-filter="all"]').classList.add('active');
                    
                    // Show all courses
                    courseCards.forEach(card => {
                        card.style.display = 'block';
                    });
                });
            });
            
            // Initialize progress bars with animation
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                
                setTimeout(() => {
                    bar.style.transition = 'width 1s ease';
                    bar.style.width = width;
                }, 300);
            });
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