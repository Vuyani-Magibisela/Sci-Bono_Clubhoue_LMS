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

// Get course details and sections
$course = $courseController->getCourseDetails($courseId);
$sections = $courseController->getCourseSections($courseId);
$isEnrolled = $courseController->isUserEnrolled($userId, $courseId);
$progress = $isEnrolled ? $courseController->getUserProgress($userId, $courseId) : 0;

// Rest of the HTML and display logic remains the same...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../public/assets/css/lesson.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
<body>
    <?php include './learn-header.php'; ?>
    
    <div class="lesson-container">
        <aside class="lesson-sidebar">
            <div class="course-info">
                <a href="course.php?id=<?php echo $lesson['course_id']; ?>" class="back-to-course">
                    <i class="fas fa-chevron-left"></i>
                    <span>Back to Course</span>
                </a>
                <div class="course-title"><?php echo htmlspecialchars($lesson['course_title']); ?></div>
            </div>
            
            <?php if (!empty($navigation['siblings'])): ?>
            <div class="lesson-separator">
                <?php echo htmlspecialchars($lesson['section_title']); ?>
            </div>
            <div class="lesson-list">
                <?php foreach ($navigation['siblings'] as $siblingLesson): ?>
                <?php 
                $siblingProgress = getLessonProgress($userId, $siblingLesson['id']);
                $isCompleted = $siblingProgress['completed'] ?? false;
                $isActive = $siblingLesson['id'] == $lessonId;
                ?>
                <a href="lesson.php?id=<?php echo $siblingLesson['id']; ?>" 
                   class="lesson-list-item <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCompleted ? 'completed' : ''; ?>">
                    <div class="lesson-list-icon">
                        <?php if ($isCompleted): ?>
                        <i class="fas fa-check"></i>
                        <?php else: ?>
                        <?php echo $siblingLesson['order_number']; ?>
                        <?php endif; ?>
                    </div>
                    <div><?php echo htmlspecialchars($siblingLesson['title']); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </aside>
        
        <main class="lesson-content-area">
            <div class="lesson-header">
                <div class="lesson-breadcrumb">
                    <a href="course.php?id=<?php echo $lesson['course_id']; ?>"><?php echo htmlspecialchars($lesson['course_title']); ?></a>
                    &rsaquo;
                    <span><?php echo htmlspecialchars($lesson['section_title']); ?></span>
                </div>
                <h1 class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></h1>
                <div class="lesson-meta">
                    <div class="lesson-meta-item">
                        <i class="fas <?php echo getLessonIcon($lesson['lesson_type']); ?>"></i>
                        <span><?php echo ucfirst($lesson['lesson_type']); ?></span>
                    </div>
                    <div class="lesson-meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $lesson['duration_minutes']; ?> minutes</span>
                    </div>
                </div>
            </div>
            
            <?php if ($lesson['lesson_type'] == 'video' && !empty($lesson['video_url'])): ?>
            <div class="lesson-video">
                <div class="video-container">
                    <iframe src="<?php echo $lesson['video_url']; ?>" 
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="lesson-content">
                <?php if ($lesson['lesson_type'] == 'text' || $lesson['lesson_type'] == 'video'): ?>
                    <?php echo $lesson['content']; ?>
                <?php elseif ($lesson['lesson_type'] == 'quiz'): ?>
                    <div class="quiz-container">
                        <div class="quiz-intro">
                            <h2>Quiz Time!</h2>
                            <p>Complete this quiz to test your knowledge of the material covered so far.</p>
                            <p>You'll need to answer all questions correctly to mark this lesson as complete.</p>
                            
                            <!-- Quiz would be loaded dynamically via JavaScript -->
                            <div id="quiz-content">
                                <p>Loading quiz questions...</p>
                            </div>
                        </div>
                    </div>
                <?php elseif ($lesson['lesson_type'] == 'assignment'): ?>
                    <div class="assignment-container">
                        <div class="assignment-intro">
                            <h2>Assignment</h2>
                            <p>Complete this assignment to practice the skills you've learned.</p>
                            <p>Upload your work below when finished.</p>
                            
                            <div class="assignment-description">
                                <?php echo $lesson['content']; ?>
                            </div>
                            
                            <div class="assignment-submission">
                                <h3>Submit Your Assignment</h3>
                                <form id="assignment-form" enctype="multipart/form-data">
                                    <div style="margin-bottom: 1rem;">
                                        <label for="assignment-file" style="display: block; margin-bottom: 0.5rem;">Upload File:</label>
                                        <input type="file" id="assignment-file" name="assignment_file" required>
                                    </div>
                                    <div style="margin-bottom: 1rem;">
                                        <label for="assignment-notes" style="display: block; margin-bottom: 0.5rem;">Notes (optional):</label>
                                        <textarea id="assignment-notes" name="assignment_notes" rows="4" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                                    </div>
                                    <button type="submit" class="lesson-complete-button">Submit Assignment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($lesson['lesson_type'] == 'interactive'): ?>
                    <div class="interactive-container">
                        <div class="interactive-intro">
                            <h2>Interactive Exercise</h2>
                            <p>Complete this interactive exercise to practice the skills you've learned.</p>
                            
                            <div id="interactive-content">
                                <!-- Interactive content would be loaded here via JavaScript -->
                                <p>Loading interactive content...</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php
            // Get the lesson progress
            $progressInfo = $lessonController->getLessonProgress($userId, $lessonId);

            // Check if the lesson is completed
            if (!$progressInfo['completed']): ?>
            <a href="lesson.php?id=<?php echo $lessonId; ?>&complete=1" class="lesson-complete-button">
                Mark as Complete
            </a>
            <?php else: ?>
            <button class="lesson-complete-button completed">
                <i class="fas fa-check"></i> Lesson Completed
            </button>
            <?php endif; ?>
            
            <div class="lesson-navigation">
                <?php if ($navigation['previous']): ?>
                <a href="lesson.php?id=<?php echo $navigation['previous']['id']; ?>" class="lesson-nav-button previous">
                    <div class="lesson-nav-icon">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    <div class="lesson-nav-text">
                        <div class="lesson-nav-direction">Previous Lesson</div>
                        <div class="lesson-nav-title"><?php echo htmlspecialchars($navigation['previous']['title']); ?></div>
                    </div>
                </a>
                <?php else: ?>
                <div class="lesson-nav-button previous disabled">
                    <div class="lesson-nav-icon">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    <div class="lesson-nav-text">
                        <div class="lesson-nav-direction">Previous Lesson</div>
                        <div class="lesson-nav-title">No previous lesson</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($navigation['next']): ?>
                <a href="lesson.php?id=<?php echo $navigation['next']['id']; ?>" class="lesson-nav-button next">
                    <div class="lesson-nav-text">
                        <div class="lesson-nav-direction">Next Lesson</div>
                        <div class="lesson-nav-title"><?php echo htmlspecialchars($navigation['next']['title']); ?></div>
                    </div>
                    <div class="lesson-nav-icon">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
                <?php else: ?>
                <a href="course.php?id=<?php echo $lesson['course_id']; ?>&completed=1" class="lesson-nav-button next">
                    <div class="lesson-nav-text">
                        <div class="lesson-nav-direction">Back to Course</div>
                        <div class="lesson-nav-title">Course Overview</div>
                    </div>
                    <div class="lesson-nav-icon">
                        <i class="fas fa-home"></i>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Mobile sidebar toggle button -->
        <div class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
    </div>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.lesson-sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.lesson-sidebar');
            const toggleButton = document.querySelector('.toggle-sidebar');
            
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && 
                !toggleButton.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Track lesson progress
        document.addEventListener('DOMContentLoaded', function() {
            // For demo purposes, we'll track progress based on scroll position
            // In a real application, this would be more sophisticated
            let hasReachedEnd = false;
            
            window.addEventListener('scroll', function() {
                if (hasReachedEnd) return;
                
                const scrollPosition = window.scrollY;
                const documentHeight = document.documentElement.scrollHeight;
                const windowHeight = window.innerHeight;
                const scrollPercent = (scrollPosition / (documentHeight - windowHeight)) * 100;
                
                // If user has scrolled to 80% of the content, consider it as progress
                if (scrollPercent >= 80) {
                    hasReachedEnd = true;
                    
                    // In a real application, you would send an AJAX request to update progress
                    console.log('User has reached 80% of the content, updating progress...');
                }
            });
            
            // Quiz functionality would be implemented here
            if (document.getElementById('quiz-content')) {
                // Simulate loading quiz questions
                setTimeout(function() {
                    document.getElementById('quiz-content').innerHTML = `
                        <div class="quiz-questions">
                            <form id="quiz-form">
                                <div class="quiz-question">
                                    <h3>1. What is the main purpose of a robot's controller?</h3>
                                    <div class="quiz-options">
                                        <div>
                                            <input type="radio" id="q1_1" name="q1" value="a">
                                            <label for="q1_1">To provide power to the robot</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q1_2" name="q1" value="b">
                                            <label for="q1_2">To serve as the "brain" that processes information</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q1_3" name="q1" value="c">
                                            <label for="q1_3">To connect the robot to the internet</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q1_4" name="q1" value="d">
                                            <label for="q1_4">To provide structural support</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="quiz-question">
                                    <h3>2. Which of the following is NOT a common robotics platform?</h3>
                                    <div class="quiz-options">
                                        <div>
                                            <input type="radio" id="q2_1" name="q2" value="a">
                                            <label for="q2_1">Arduino</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q2_2" name="q2" value="b">
                                            <label for="q2_2">LEGO Mindstorms</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q2_3" name="q2" value="c">
                                            <label for="q2_3">TensorFlow</label>
                                        </div>
                                        <div>
                                            <input type="radio" id="q2_4" name="q2" value="d">
                                            <label for="q2_4">Raspberry Pi</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="lesson-complete-button">Submit Quiz</button>
                            </form>
                        </div>
                    `;
                    
                    // Add quiz submission handler
                    document.getElementById('quiz-form').addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Check answers
                        const q1Answer = document.querySelector('input[name="q1"]:checked');
                        const q2Answer = document.querySelector('input[name="q2"]:checked');
                        
                        if (!q1Answer || !q2Answer) {
                            alert('Please answer all questions.');
                            return;
                        }
                        
                        // Check if answers are correct
                        const isCorrect = q1Answer.value === 'b' && q2Answer.value === 'c';
                        
                        if (isCorrect) {
                            // Redirect to mark lesson as complete
                            window.location.href = 'lesson.php?id=<?php echo $lessonId; ?>&complete=1';
                        } else {
                            alert('Some answers are incorrect. Please try again.');
                        }
                    });
                }, 1000);
            }
            
            // Assignment submission handler
            if (document.getElementById('assignment-form')) {
                document.getElementById('assignment-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // In a real application, you would handle file upload via AJAX
                    alert('Assignment submitted successfully!');
                    
                    // Redirect to mark lesson as complete
                    window.location.href = 'lesson.php?id=<?php echo $lessonId; ?>&complete=1';
                });
            }
            
            // Interactive content handling
            if (document.getElementById('interactive-content')) {
                // Simulate loading interactive content
                setTimeout(function() {
                    document.getElementById('interactive-content').innerHTML = `
                        <div class="interactive-exercise">
                            <h3>Robot Movement Simulation</h3>
                            <p>Use the controls below to program a virtual robot's movement.</p>
                            
                            <div class="simulation-container" style="border: 1px solid #ddd; padding: 1rem; margin: 1rem 0; border-radius: 8px;">
                                <div class="robot-grid" style="display: grid; grid-template-columns: repeat(5, 50px); grid-template-rows: repeat(5, 50px); gap: 2px; margin-bottom: 1rem;">
                                    <!-- Grid cells would be created dynamically via JavaScript -->
                                </div>
                                
                                <div class="control-panel" style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                                    <button class="control-button" onclick="moveRobot('up')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">Up</button>
                                    <button class="control-button" onclick="moveRobot('down')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">Down</button>
                                    <button class="control-button" onclick="moveRobot('left')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">Left</button>
                                    <button class="control-button" onclick="moveRobot('right')" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">Right</button>
                                    <button class="control-button" onclick="resetRobot()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">Reset</button>
                                </div>
                                
                                <div class="program-sequence" style="border: 1px solid #ddd; padding: 0.5rem; border-radius: 4px; min-height: 50px; margin-bottom: 1rem;">
                                    <p>Your program: <span id="program-steps">No steps yet</span></p>
                                </div>
                                
                                <button onclick="runSimulation()" style="padding: 0.5rem 1rem; background: var(--primary); color: white; border: none; border-radius: 4px; cursor: pointer;">Run Program</button>
                            </div>
                            
                            <div class="exercise-completion">
                                <p>Complete the exercise by programming the robot to reach the goal (top-right corner) in less than 10 steps.</p>
                                <button onclick="completeExercise()" class="lesson-complete-button">Complete Exercise</button>
                            </div>
                        </div>
                    `;
                    
                    // Create grid
                    const grid = document.querySelector('.robot-grid');
                    for (let i = 0; i < 25; i++) {
                        const cell = document.createElement('div');
                        cell.style.width = '50px';
                        cell.style.height = '50px';
                        cell.style.backgroundColor = '#f9f9f9';
                        cell.style.border = '1px solid #ddd';
                        cell.dataset.index = i;
                        grid.appendChild(cell);
                    }
                    
                    // Place robot at bottom-left
                    const cells = grid.querySelectorAll('div');
                    cells[20].style.backgroundColor = '#6C63FF';
                    cells[20].innerHTML = '<i class="fas fa-robot" style="color: white; display: flex; justify-content: center; align-items: center; height: 100%;"></i>';
                    
                    // Place goal at top-right
                    cells[4].style.backgroundColor = '#41b35d';
                    cells[4].innerHTML = '<i class="fas fa-flag" style="color: white; display: flex; justify-content: center; align-items: center; height: 100%;"></i>';
                    
                    // Initialize program steps
                    window.programSteps = [];
                    
                    // Define robot movement function
                    window.moveRobot = function(direction) {
                        window.programSteps.push(direction);
                        document.getElementById('program-steps').textContent = window.programSteps.join(' → ');
                    };
                    
                    window.resetRobot = function() {
                        window.programSteps = [];
                        document.getElementById('program-steps').textContent = 'No steps yet';
                    };
                    
                    window.runSimulation = function() {
                        // In a real app, this would animate the robot movement
                        alert('Simulation would run here. Your steps: ' + window.programSteps.join(' → '));
                    };
                    
                    window.completeExercise = function() {
                        // In a real app, this would check if the robot reached the goal
                        if (window.programSteps.length > 0 && window.programSteps.length < 10) {
                            // Redirect to mark lesson as complete
                            window.location.href = 'lesson.php?id=<?php echo $lessonId; ?>&complete=1';
                        } else {
                            alert('Your program is too long or empty. Try to reach the goal in less than 10 steps.');
                        }
                    };
                }, 1000);
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