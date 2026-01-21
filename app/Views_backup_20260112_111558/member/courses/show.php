<?php
/**
 * Course Details - View single course with curriculum
 * Phase 3: Week 6-7 Implementation
 *
 * Data from Member\CourseController:
 * - $course: Course details
 * - $curriculum: Course sections and lessons
 * - $isEnrolled: Whether user is enrolled
 * - $progress: User's progress if enrolled
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo htmlspecialchars($course['title']); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/homeStyle.css">
    <style>
        .course-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .course-hero h1 {
            font-size: 2rem;
            margin: 0 0 1rem 0;
        }
        .course-meta-badges {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .course-content-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        .course-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1c1e21;
            margin: 0 0 1rem 0;
        }
        .curriculum-section {
            border: 1px solid #e4e6eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .section-header {
            padding: 1rem 1.5rem;
            background: #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .section-header:hover {
            background: #e4e6eb;
        }
        .lesson-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .lesson-item {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e4e6eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        .lesson-item:hover {
            background: #f0f2f5;
        }
        .lesson-item.completed {
            background: #f0fff4;
        }
        .lesson-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .lesson-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65676b;
        }
        .lesson-item.completed .lesson-icon {
            background: #28a745;
            color: white;
        }
        .enroll-card {
            position: sticky;
            top: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <img src="/public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
                <span>SCI-BONO CLUBHOUSE</span>
            </div>
            <div class="header-icons">
                <div class="user-profile">
                    <div class="avatar" style="background-color: <?php echo $user['avatar_color'] ?? '#3F51B5'; ?>">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="menu-group">
                <div class="menu-item">
                    <div class="menu-icon"><i class="fas fa-home"></i></div>
                    <div class="menu-text"><a href="/dashboard">Home</a></div>
                </div>
                <div class="menu-item active">
                    <div class="menu-icon"><i class="fa-solid fa-chalkboard"></i></div>
                    <div class="menu-text"><a href="/courses">Courses</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon"><i class="fas fa-book-open"></i></div>
                    <div class="menu-text"><a href="/my-courses">My Courses</a></div>
                </div>
            </div>
            <div class="menu-item" onclick="window.location.href='/logout'">
                <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                <div class="menu-text">Log out</div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content" style="grid-column: 2 / -1;">
            <!-- Back Button -->
            <div style="margin-bottom: 1rem;">
                <a href="/courses" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>

            <!-- Course Hero -->
            <div class="course-hero">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p style="font-size: 1.125rem; margin: 0 0 1rem 0; opacity: 0.9;">
                    <?php echo htmlspecialchars($course['description'] ?? ''); ?>
                </p>

                <div class="course-meta-badges">
                    <?php if (!empty($course['category'])): ?>
                    <span class="badge">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($course['category']); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($course['level'])): ?>
                    <span class="badge">
                        <i class="fas fa-signal"></i>
                        <?php echo ucfirst($course['level']); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (isset($course['lesson_count'])): ?>
                    <span class="badge">
                        <i class="fas fa-book"></i>
                        <?php echo $course['lesson_count']; ?> Lessons
                    </span>
                    <?php endif; ?>

                    <?php if (isset($course['duration'])): ?>
                    <span class="badge">
                        <i class="fas fa-clock"></i>
                        <?php echo $course['duration']; ?> hours
                    </span>
                    <?php endif; ?>

                    <?php if (isset($course['enrolled_count'])): ?>
                    <span class="badge">
                        <i class="fas fa-users"></i>
                        <?php echo $course['enrolled_count']; ?> enrolled
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="course-content-layout">
                <!-- Left Column - Course Info & Curriculum -->
                <div>
                    <!-- What You'll Learn -->
                    <?php if (!empty($course['learning_outcomes'])): ?>
                    <div class="course-section">
                        <h2 class="section-title">What You'll Learn</h2>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach (explode("\n", $course['learning_outcomes']) as $outcome): ?>
                                <?php if (trim($outcome)): ?>
                                <li style="padding: 0.5rem 0; display: flex; gap: 0.75rem;">
                                    <i class="fas fa-check-circle" style="color: #28a745; margin-top: 0.25rem;"></i>
                                    <span><?php echo htmlspecialchars(trim($outcome)); ?></span>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Course Curriculum -->
                    <div class="course-section">
                        <h2 class="section-title">Course Curriculum</h2>

                        <?php if (!empty($curriculum)): ?>
                            <?php foreach ($curriculum as $section): ?>
                                <div class="curriculum-section">
                                    <div class="section-header" onclick="toggleSection(this)">
                                        <div>
                                            <strong style="font-size: 1rem;">
                                                <?php echo htmlspecialchars($section['title']); ?>
                                            </strong>
                                            <div style="color: #65676b; font-size: 0.875rem; margin-top: 0.25rem;">
                                                <?php echo count($section['lessons']); ?> lessons
                                                <?php if (isset($section['duration'])): ?>
                                                    • <?php echo $section['duration']; ?> min
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                                    </div>

                                    <ul class="lesson-list" style="display: none;">
                                        <?php foreach ($section['lessons'] as $lesson): ?>
                                            <li class="lesson-item <?php echo !empty($lesson['completed']) ? 'completed' : ''; ?>"
                                                onclick="<?php echo $isEnrolled ? "window.location.href='/lessons/{$lesson['id']}'" : 'alert(\'Please enroll in this course first\')'; ?>">
                                                <div class="lesson-info">
                                                    <div class="lesson-icon">
                                                        <?php if (!empty($lesson['completed'])): ?>
                                                            <i class="fas fa-check"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-play"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 500;">
                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                        </div>
                                                        <?php if (!empty($lesson['duration'])): ?>
                                                        <div style="color: #65676b; font-size: 0.875rem;">
                                                            <?php echo $lesson['duration']; ?> min
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if (!$isEnrolled): ?>
                                                    <i class="fas fa-lock" style="color: #65676b;"></i>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #65676b; text-align: center; padding: 2rem;">
                                Curriculum will be available soon.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Instructor Info -->
                    <?php if (!empty($course['instructor_name'])): ?>
                    <div class="course-section">
                        <h2 class="section-title">Instructor</h2>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 600;">
                                <?php echo strtoupper(substr($course['instructor_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 1.125rem;">
                                    <?php echo htmlspecialchars($course['instructor_name']); ?>
                                </div>
                                <div style="color: #65676b; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($course['instructor_title'] ?? 'Instructor'); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($course['instructor_bio'])): ?>
                        <p style="margin-top: 1rem; color: #65676b; line-height: 1.6;">
                            <?php echo htmlspecialchars($course['instructor_bio']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column - Enrollment Card -->
                <div>
                    <div class="enroll-card">
                        <?php if ($isEnrolled): ?>
                            <!-- Progress Display -->
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <div class="progress-ring">
                                    <svg width="120" height="120">
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e4e6eb" stroke-width="8"/>
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--primary)" stroke-width="8"
                                                stroke-dasharray="<?php echo (339.292 * ($progress['progress'] ?? 0) / 100); ?> 339.292"
                                                stroke-linecap="round" transform="rotate(-90 60 60)"/>
                                        <text x="60" y="60" text-anchor="middle" dy=".3em" font-size="24" font-weight="600" fill="#1c1e21">
                                            <?php echo $progress['progress'] ?? 0; ?>%
                                        </text>
                                    </svg>
                                </div>
                                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Your Progress
                                </div>
                                <div style="color: #65676b; font-size: 0.875rem;">
                                    <?php echo $progress['completed_lessons'] ?? 0; ?> of <?php echo $course['lesson_count'] ?? 0; ?> lessons completed
                                </div>
                            </div>

                            <a href="<?php echo $progress['next_lesson_url'] ?? '/my-courses'; ?>"
                               class="btn-primary" style="display: block; text-align: center; padding: 1rem; background: var(--primary); color: white; text-decoration: none; border-radius: 6px; margin-bottom: 1rem;">
                                <i class="fas fa-play"></i> Continue Learning
                            </a>

                            <a href="/my-courses" style="display: block; text-align: center; color: var(--primary); text-decoration: none;">
                                Go to My Courses
                            </a>
                        <?php else: ?>
                            <!-- Enroll Button -->
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <div style="font-size: 2rem; font-weight: 700; color: #1c1e21; margin-bottom: 0.5rem;">
                                    Free
                                </div>
                                <div style="color: #65676b; font-size: 0.875rem;">
                                    Full lifetime access
                                </div>
                            </div>

                            <form id="enroll-form" method="post" action="/courses/<?php echo $course['id']; ?>/enroll">
                                <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-graduation-cap"></i> Enroll Now
                                </button>
                            </form>

                            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e4e6eb;">
                                <div style="font-weight: 600; margin-bottom: 0.75rem;">This course includes:</div>
                                <ul style="list-style: none; padding: 0; margin: 0; color: #65676b; font-size: 0.875rem;">
                                    <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-check" style="color: #28a745;"></i>
                                        <span><?php echo $course['lesson_count'] ?? 0; ?> lessons</span>
                                    </li>
                                    <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-check" style="color: #28a745;"></i>
                                        <span>Full lifetime access</span>
                                    </li>
                                    <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-check" style="color: #28a745;"></i>
                                        <span>Access on mobile and desktop</span>
                                    </li>
                                    <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-check" style="color: #28a745;"></i>
                                        <span>Certificate of completion</span>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSection(header) {
            const lessonList = header.nextElementSibling;
            const icon = header.querySelector('i');

            if (lessonList.style.display === 'none') {
                lessonList.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                lessonList.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Enroll form submission
        document.getElementById('enroll-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Successfully enrolled in course!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while enrolling');
            });
        });
    </script>
</body>
</html>
