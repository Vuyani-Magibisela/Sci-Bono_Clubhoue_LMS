<?php
/**
 * My Courses - User's enrolled courses
 * Phase 3: Week 6-7 Implementation
 *
 * Data from Member\CourseController:
 * - $enrolledCourses: User's enrolled courses with progress
 * - $user: Current user data
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/homeStyle.css">
    <style>
        .course-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .course-card-content {
            display: grid;
            grid-template-columns: 200px 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        .course-thumbnail {
            width: 200px;
            height: 140px;
            object-fit: cover;
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .course-info {
            flex: 1;
        }
        .course-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1c1e21;
            margin: 0 0 0.5rem 0;
        }
        .course-description {
            color: #65676b;
            font-size: 0.875rem;
            margin: 0 0 1rem 0;
        }
        .progress-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .progress-bar-wrapper {
            flex: 1;
        }
        .progress-label {
            font-size: 0.875rem;
            color: #65676b;
            margin-bottom: 0.5rem;
        }
        .progress-bar {
            height: 8px;
            background: #e4e6eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.3s;
        }
        .course-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            justify-content: center;
        }
        .tab-navigation {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e4e6eb;
        }
        .tab-link {
            padding: 1rem 1.5rem;
            color: #65676b;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .tab-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #fff;
            border-radius: 8px;
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
                <div class="menu-item">
                    <div class="menu-icon"><i class="fa-solid fa-chalkboard"></i></div>
                    <div class="menu-text"><a href="/courses">Browse Courses</a></div>
                </div>
                <div class="menu-item active">
                    <div class="menu-icon"><i class="fas fa-book-open"></i></div>
                    <div class="menu-text"><a href="/my-courses">My Courses</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon"><i class="fas fa-cog"></i></div>
                    <div class="menu-text"><a href="/settings">Settings</a></div>
                </div>
            </div>
            <div class="menu-item" onclick="window.location.href='/logout'">
                <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                <div class="menu-text">Log out</div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header" style="margin-bottom: 2rem;">
                <h1 style="font-size: 1.75rem; font-weight: 700; color: #1c1e21; margin: 0;">My Courses</h1>
                <p style="color: #65676b; margin: 0.5rem 0 0;">Track your learning progress and continue where you left off</p>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <a href="?filter=all" class="tab-link <?php echo ($filters['filter'] ?? 'all') == 'all' ? 'active' : ''; ?>">
                    All Courses (<?php echo count($enrolledCourses ?? []); ?>)
                </a>
                <a href="?filter=in-progress" class="tab-link <?php echo ($filters['filter'] ?? '') == 'in-progress' ? 'active' : ''; ?>">
                    In Progress
                </a>
                <a href="?filter=completed" class="tab-link <?php echo ($filters['filter'] ?? '') == 'completed' ? 'active' : ''; ?>">
                    Completed
                </a>
            </div>

            <!-- Course List -->
            <?php if (!empty($enrolledCourses)): ?>
                <?php foreach ($enrolledCourses as $course): ?>
                    <div class="course-card">
                        <div class="course-card-content">
                            <!-- Thumbnail -->
                            <div>
                                <?php if (!empty($course['thumbnail'])): ?>
                                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>"
                                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                                         class="course-thumbnail">
                                <?php else: ?>
                                    <div class="course-thumbnail"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Course Info -->
                            <div class="course-info">
                                <h3 class="course-title">
                                    <a href="/courses/<?php echo $course['id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </a>
                                </h3>

                                <p class="course-description">
                                    <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 150)); ?>
                                    <?php echo strlen($course['description'] ?? '') > 150 ? '...' : ''; ?>
                                </p>

                                <!-- Progress Bar -->
                                <div class="progress-info">
                                    <div class="progress-bar-wrapper">
                                        <div class="progress-label">
                                            <?php echo $course['progress'] ?? 0; ?>% complete
                                            • <?php echo $course['completed_lessons'] ?? 0; ?> of <?php echo $course['total_lessons'] ?? 0; ?> lessons
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $course['progress'] ?? 0; ?>%"></div>
                                        </div>
                                    </div>

                                    <?php if (($course['progress'] ?? 0) >= 100): ?>
                                        <div style="color: #28a745; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-check-circle"></i> Completed
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Course Meta -->
                                <div style="display: flex; gap: 1.5rem; margin-top: 1rem; color: #65676b; font-size: 0.875rem;">
                                    <?php if (!empty($course['instructor_name'])): ?>
                                    <span>
                                        <i class="fas fa-user-circle"></i>
                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </span>
                                    <?php endif; ?>

                                    <?php if (!empty($course['last_accessed'])): ?>
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        Last accessed <?php echo htmlspecialchars($course['last_accessed']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="course-actions">
                                <?php if (!empty($course['next_lesson_id'])): ?>
                                    <a href="/lessons/<?php echo $course['next_lesson_id']; ?>"
                                       class="btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 6px; white-space: nowrap;">
                                        <i class="fas fa-play"></i>
                                        <?php echo ($course['progress'] ?? 0) > 0 ? 'Continue' : 'Start Course'; ?>
                                    </a>
                                <?php endif; ?>

                                <a href="/courses/<?php echo $course['id']; ?>"
                                   class="btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #f0f2f5; color: #1c1e21; text-decoration: none; border-radius: 6px; white-space: nowrap;">
                                    <i class="fas fa-info-circle"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-graduation-cap" style="font-size: 4rem; color: #e4e6eb; margin-bottom: 1rem;"></i>
                    <h3 style="color: #1c1e21; margin: 0 0 0.5rem 0;">No courses enrolled yet</h3>
                    <p style="color: #65676b; margin: 0 0 1.5rem 0;">
                        Start learning by enrolling in courses that interest you
                    </p>
                    <a href="/courses" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 6px;">
                        <i class="fas fa-search"></i> Browse Courses
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Learning Stats -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Your Stats</div>
                </div>
                <div style="padding: 1rem 0;">
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #65676b; font-size: 0.875rem;">Courses Enrolled</div>
                        <div style="font-size: 1.75rem; font-weight: 600; color: var(--primary);">
                            <?php echo count($enrolledCourses ?? []); ?>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #65676b; font-size: 0.875rem;">Courses Completed</div>
                        <div style="font-size: 1.75rem; font-weight: 600; color: #28a745;">
                            <?php echo count(array_filter($enrolledCourses ?? [], function($c) { return ($c['progress'] ?? 0) >= 100; })); ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem;">Total Progress</div>
                        <div style="font-size: 1.75rem; font-weight: 600; color: #ff6b6b;">
                            <?php
                            $totalProgress = 0;
                            if (!empty($enrolledCourses)) {
                                $totalProgress = array_sum(array_column($enrolledCourses, 'progress')) / count($enrolledCourses);
                            }
                            echo round($totalProgress);
                            ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Quick Actions</div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem 0;">
                    <a href="/courses" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f0f2f5; border-radius: 6px; text-decoration: none; color: #1c1e21;">
                        <i class="fas fa-search"></i>
                        <span>Browse More Courses</span>
                    </a>
                    <a href="/dashboard" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f0f2f5; border-radius: 6px; text-decoration: none; color: #1c1e21;">
                        <i class="fas fa-home"></i>
                        <span>Go to Dashboard</span>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
