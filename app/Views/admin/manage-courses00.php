<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/manage-courses.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE for rich text editing
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '.rich-text-editor',
                plugins: 'code table lists link image',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
                height: 300
            });
        });
    </script>
    </head>

<body>
    <?php include '../learn-header.php'; 
    require_once '../../Models/Admin/AdminCourseModel.php';
    // include '';
    $adminCourseModel = new AdminCourseModel($conn);
    ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Course Management</h1>
            <div class="admin-actions">
                <button class="admin-btn" onclick="openModal('create-course-modal')">
                    <i class="fas fa-plus"></i> Create Course
                </button>
                <a href="../../home.php" class="admin-btn secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-tab-nav">
            <div class="admin-tab <?php echo !isset($_GET['course_id']) ? 'active' : ''; ?>" onclick="location.href='manage-courses.php'">All Courses</div>
            <?php if (isset($_GET['course_id'])): ?>
            <div class="admin-tab active">
                <?php echo htmlspecialchars($courseDetails['title']); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="admin-content">
            <?php if (!isset($_GET['course_id'])): // Show all courses ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">All Courses</h2>
                </div>
                
                <?php if (empty($courses)): ?>
                <p>No courses found. Create a new course to get started.</p>
                <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-card-image">
                            <?php if (!empty($course['image_path']) && file_exists("../../public/assets/uploads/images/courses/" . $course['image_path'])): ?>
                                <img src="../../public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <?php else: ?>
                                <img src="https://source.unsplash.com/random/600x400?<?php echo urlencode(strtolower($course['title'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="course-card-content">
                            <h3 class="course-card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <div class="course-card-info">
                                <div><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $course['type'])); ?></div>
                                <div><strong>Level:</strong> <?php echo htmlspecialchars($course['difficulty_level']); ?></div>
                                <div><strong>Created by:</strong> <?php echo htmlspecialchars($course['creator_name'] . ' ' . $course['creator_surname']); ?></div>
                            </div>
                            <div class="course-card-stats">
                                <div class="course-card-stat">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?php echo $course['section_count']; ?> Sections</span>
                                </div>
                                <div class="course-card-stat">
                                    <i class="fas fa-file-alt"></i>
                                    <span><?php echo $course['lesson_count']; ?> Lessons</span>
                                </div>
                                <div class="course-card-stat">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $course['enrollment_count']; ?> Students</span>
                                </div>
                            </div>
                            <div class="course-card-actions">
                                <a href="manage-courses.php?course_id=<?php echo $course['id']; ?>" class="icon-btn view" title="View Course">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="icon-btn edit" title="Edit Course" onclick="editCourse(<?php echo $course['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="icon-btn delete" title="Delete Course" onclick="confirmDeleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: // Show course details ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">Course Details</h2>
                    <div class="admin-card-actions">
                        <button class="admin-btn secondary" onclick="editCourse(<?php echo $courseDetails['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit Course
                        </button>
                    </div>
                </div>
                
                <div class="course-details">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <div><?php echo htmlspecialchars($courseDetails['title']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <div><?php echo ucfirst(str_replace('_', ' ', $courseDetails['type'])); ?></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Difficulty Level</label>
                            <div><?php echo htmlspecialchars($courseDetails['difficulty_level']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duration</label>
                            <div><?php echo htmlspecialchars($courseDetails['duration']); ?></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <div><?php echo nl2br(htmlspecialchars($courseDetails['description'])); ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Created By</label>
                        <div><?php echo htmlspecialchars($courseDetails['creator_name'] . ' ' . $courseDetails['creator_surname']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">Course Sections</h2>
                    <div class="admin-card-actions">
                        <button class="admin-btn" onclick="openModal('add-section-modal')">
                            <i class="fas fa-plus"></i> Add Section
                        </button>
                    </div>
                </div>
                
                <div class="section-list">
                    <?php if (empty($courseSections)): ?>
                    <p>No sections found. Add a section to get started.</p>
                    <?php else: ?>
                    <?php foreach ($courseSections as $section): ?>
                    <div class="section-item">
                        <div class="section-header" onclick="toggleSection(this.parentNode)">
                            <div class="section-title">
                                <span><?php echo htmlspecialchars($section['title']); ?></span>
                            </div>
                            <div class="section-meta">
                                <span><i class="fas fa-file-alt"></i> <?php echo $section['lesson_count']; ?> Lessons</span>
                                <span><i class="fas fa-sort"></i> Order: <?php echo $section['order_number']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="section-content">
                            <?php if (!empty($section['description'])): ?>
                            <div class="section-description">
                                <?php echo nl2br(htmlspecialchars($section['description'])); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php
                            // Get lessons for this section
                            // $lessons = getSectionLessons($section['id']);
                            ?>
                            
                            <div class="lesson-container">
                                <div class="lesson-header">
                                    <h3>Lessons</h3>
                                    <button class="admin-btn" onclick="openAddLessonModal(<?php echo $section['id']; ?>, <?php echo $section['lesson_count'] + 1; ?>)">
                                        <i class="fas fa-plus"></i> Add Lesson
                                    </button>
                                </div>
                                
                                <?php if (empty($lessons)): ?>
                                <p>No lessons found. Add a lesson to get started.</p>
                                <?php else: ?>
                                <ul class="lesson-list">
                                    <?php foreach ($lessons as $lesson): ?>
                                    <li class="lesson-item">
                                        <div class="lesson-info">
                                            <div class="lesson-icon">
                                                <i class="fas fa-<?php 
                                                    if ($lesson['lesson_type'] == 'video') echo 'video';
                                                    elseif ($lesson['lesson_type'] == 'quiz') echo 'question-circle';
                                                    elseif ($lesson['lesson_type'] == 'assignment') echo 'clipboard-check';
                                                    elseif ($lesson['lesson_type'] == 'interactive') echo 'gamepad';
                                                    else echo 'file-alt';
                                                ?>"></i>
                                            </div>
                                            <div>
                                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                                <div class="lesson-meta">
                                                    <span><?php echo ucfirst($lesson['lesson_type']); ?></span>
                                                    <span><?php echo $lesson['duration_minutes']; ?> min</span>
                                                    <span>Order: <?php echo $lesson['order_number']; ?></span>
                                                    <span><?php echo $lesson['is_published'] ? 'Published' : 'Draft'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="lesson-actions">
                                            <button class="icon-btn edit" title="Edit Lesson" onclick="editLesson(<?php echo $lesson['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="icon-btn delete" title="Delete Lesson" onclick="confirmDeleteLesson(<?php echo $lesson['id']; ?>, '<?php echo htmlspecialchars($lesson['title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create Course Modal -->
    <div id="create-course-modal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Create New Course</h2>
                <button class="modal-close" onclick="closeModal('create-course-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-section">
                        <h3 class="form-section-title">Course Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title" class="form-label">Course Title *</label>
                                <input type="text" id="title" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="type" class="form-label">Course Type *</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="full_course">Full Course</option>
                                    <option value="short_course">Short Course</option>
                                    <option value="lesson">Single Lesson</option>
                                    <option value="skill_activity">Skill Activity</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="difficulty_level" class="form-label">Difficulty Level *</label>
                                <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate">Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="duration" class="form-label">Duration</label>
                                <input type="text" id="duration" name="duration" class="form-control" placeholder="e.g., 4 weeks, 10 hours">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="course_image" class="form-label">Course Image</label>
                            <input type="file" id="course_image" name="course_image" class="form-control">
                            <div class="form-text">Recommended size: 1200x600 pixels. Formats: JPG, PNG, GIF</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('create-course-modal')">Cancel</button>
                    <button type="submit" name="create_course" class="admin-btn">Create Course</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Section Modal -->
    <div id="add-section-modal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Add New Section</h2>
                <button class="modal-close" onclick="closeModal('add-section-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="course_id" value="<?php echo $_GET['course_id'] ?? ''; ?>">
                    <div class="form-group">
                        <label for="section_title" class="form-label">Section Title *</label>
                        <input type="text" id="section_title" name="section_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="section_description" class="form-label">Description</label>
                        <textarea id="section_description" name="section_description" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="order_number" class="form-label">Order Number *</label>
                        <input type="number" id="order_number" name="order_number" class="form-control" value="<?php echo count($courseSections) + 1; ?>" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('add-section-modal')">Cancel</button>
                    <button type="submit" name="add_section" class="admin-btn">Add Section</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Lesson Modal -->
    <div id="add-lesson-modal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Add New Lesson</h2>
                <button class="modal-close" onclick="closeModal('add-lesson-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="section_id" name="section_id" value="">
                    <div class="form-group">
                        <label for="lesson_title" class="form-label">Lesson Title *</label>
                        <input type="text" id="lesson_title" name="lesson_title" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lesson_type" class="form-label">Lesson Type *</label>
                            <select id="lesson_type" name="lesson_type" class="form-control" required onchange="toggleVideoUrlField()">
                                <option value="text">Text</option>
                                <option value="video">Video</option>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                                <option value="interactive">Interactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="duration_minutes" class="form-label">Duration (minutes) *</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-group video-url-field" style="display: none;">
                        <label for="video_url" class="form-label">Video URL *</label>
                        <input type="url" id="video_url" name="video_url" class="form-control" placeholder="YouTube or Vimeo embed URL">
                        <div class="form-text">For YouTube videos, use the embed URL format: https://www.youtube.com/embed/VIDEO_ID</div>
                    </div>
                    <div class="form-group">
                        <label for="lesson_content" class="form-label">Lesson Content *</label>
                        <textarea id="lesson_content" name="lesson_content" class="form-control rich-text-editor" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lesson_order" class="form-label">Order Number *</label>
                            <input type="number" id="lesson_order" name="lesson_order" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="form-group">
                            <div class="form-check" style="margin-top: 2rem;">
                                <input type="checkbox" id="is_published" name="is_published" class="form-check-input" checked>
                                <label for="is_published" class="form-check-label">Publish immediately</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn secondary" onclick="closeModal('add-lesson-modal')">Cancel</button>
                    <button type="submit" name="add_lesson" class="admin-btn">Add Lesson</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle video URL field based on lesson type
        function toggleVideoUrlField() {
            const lessonType = document.getElementById('lesson_type').value;
            const videoUrlField = document.querySelector('.video-url-field');
            
            if (lessonType === 'video') {
                videoUrlField.style.display = 'block';
                document.getElementById('video_url').setAttribute('required', '');
            } else {
                videoUrlField.style.display = 'none';
                document.getElementById('video_url').removeAttribute('required');
            }
        }
        
        // Toggle section content
        function toggleSection(section) {
            const content = section.querySelector('.section-content');
            const icon = section.querySelector('.fa-chevron-down');
            
            content.classList.toggle('active');
            
            if (content.classList.contains('active')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0)';
            }
        }
        
        // Open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Open add lesson modal
        function openAddLessonModal(sectionId, orderNumber) {
            document.getElementById('section_id').value = sectionId;
            document.getElementById('lesson_order').value = orderNumber;
            openModal('add-lesson-modal');
        }
        
        // Edit course
        function editCourse(courseId) {
            // In a real implementation, you would fetch course details via AJAX and populate a form
            alert('Edit course: ' + courseId);
        }
        
        // Edit lesson
        function editLesson(lessonId) {
            // In a real implementation, you would fetch lesson details via AJAX and populate a form
            alert('Edit lesson: ' + lessonId);
        }
        
        // Confirm delete course
        function confirmDeleteCourse(courseId, courseTitle) {
            if (confirm(`Are you sure you want to delete the course "${courseTitle}"? This action cannot be undone.`)) {
                // In a real implementation, you would submit a form or make an AJAX request to delete the course
                alert('Delete course: ' + courseId);
            }
        }
        
        // Confirm delete lesson
        function confirmDeleteLesson(lessonId, lessonTitle) {
            if (confirm(`Are you sure you want to delete the lesson "${lessonTitle}"? This action cannot be undone.`)) {
                // In a real implementation, you would submit a form or make an AJAX request to delete the lesson
                alert('Delete lesson: ' + lessonId);
            }
        }
        
        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal-backdrop.active');
            
            modals.forEach(modal => {
                const modalContent = modal.querySelector('.modal');
                
                if (event.target === modal && !modalContent.contains(event.target)) {
                    modal.classList.remove('active');
                }
            });
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-expand the first section if on course detail page
            const sections = document.querySelectorAll('.section-item');
            if (sections.length > 0) {
                toggleSection(sections[0]);
            }
        });
    </script>
</body>
</html>