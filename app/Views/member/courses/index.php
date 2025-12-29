<?php
/**
 * Course Catalog - Browse and search courses
 * Phase 3: Week 6-7 Implementation
 *
 * Data from Member\CourseController:
 * - $courses: Array of available courses
 * - $categories: Course categories
 * - $filters: Current filter values
 * - $user: Current user data
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Courses'); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/homeStyle.css">
    <style>
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .course-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .course-thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .course-content {
            padding: 1.25rem;
        }
        .course-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #f0f2f5;
            color: #65676b;
            font-size: 0.75rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
        }
        .course-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1c1e21;
            margin: 0.5rem 0;
        }
        .course-description {
            color: #65676b;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0.5rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e4e6eb;
        }
        .course-instructor {
            display: flex;
            align-items: center;
            color: #65676b;
            font-size: 0.875rem;
        }
        .course-stats {
            display: flex;
            gap: 1rem;
            color: #65676b;
            font-size: 0.875rem;
        }
        .filter-bar {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .filter-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1c1e21;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .filter-select, .filter-input {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #e4e6eb;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        .enrolled-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
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
            <div class="search-bar">
                <input type="text" class="search-input" id="course-search" placeholder="Search courses..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>
            <div class="header-icons">
                <div class="icon-btn" data-tooltip="Notifications">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="user-profile">
                    <div class="avatar" style="background-color: <?php echo $user['avatar_color'] ?? '#3F51B5'; ?>">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation -->
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
                <div class="menu-item">
                    <div class="menu-icon"><i class="fas fa-users"></i></div>
                    <div class="menu-text"><a href="/members">Members</a></div>
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
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h1 style="font-size: 1.75rem; font-weight: 700; color: #1c1e21; margin: 0;">Explore Courses</h1>
                    <p style="color: #65676b; margin: 0.5rem 0 0;">Discover and enroll in courses to expand your skills</p>
                </div>
                <a href="/my-courses" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 6px;">
                    <i class="fas fa-book-reader"></i> My Courses
                </a>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form id="filter-form" method="get">
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                            <select name="category" id="category-filter" class="filter-select">
                                <option value="">All Categories</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>"
                                                <?php echo ($filters['category'] ?? '') == $category ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Level</label>
                            <select name="level" id="level-filter" class="filter-select">
                                <option value="">All Levels</option>
                                <option value="beginner" <?php echo ($filters['level'] ?? '') == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo ($filters['level'] ?? '') == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo ($filters['level'] ?? '') == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Sort By</label>
                            <select name="sort" id="sort-filter" class="filter-select">
                                <option value="recent" <?php echo ($filters['sort'] ?? 'recent') == 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                                <option value="popular" <?php echo ($filters['sort'] ?? '') == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="title" <?php echo ($filters['sort'] ?? '') == 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            </select>
                        </div>

                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn-primary" style="width: 100%; padding: 0.625rem; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Course Grid -->
            <div class="course-grid" id="course-grid">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card" onclick="window.location.href='/courses/<?php echo $course['id']; ?>'">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>"
                                     alt="<?php echo htmlspecialchars($course['title']); ?>"
                                     class="course-thumbnail">
                            <?php else: ?>
                                <div class="course-thumbnail"></div>
                            <?php endif; ?>

                            <div class="course-content">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <?php if (!empty($course['category'])): ?>
                                        <span class="course-category"><?php echo htmlspecialchars($course['category']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($course['is_enrolled'])): ?>
                                        <span class="enrolled-badge">Enrolled</span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>

                                <p class="course-description">
                                    <?php echo htmlspecialchars($course['description'] ?? 'No description available'); ?>
                                </p>

                                <div class="course-meta">
                                    <div class="course-instructor">
                                        <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                                        <?php echo htmlspecialchars($course['instructor_name'] ?? 'Sci-Bono'); ?>
                                    </div>
                                    <div class="course-stats">
                                        <?php if (isset($course['lesson_count'])): ?>
                                            <span><i class="fas fa-book"></i> <?php echo $course['lesson_count']; ?> lessons</span>
                                        <?php endif; ?>
                                        <?php if (isset($course['enrolled_count'])): ?>
                                            <span><i class="fas fa-users"></i> <?php echo $course['enrolled_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: #fff; border-radius: 8px;">
                        <i class="fas fa-search" style="font-size: 3rem; color: #e4e6eb; margin-bottom: 1rem;"></i>
                        <h3 style="color: #65676b; margin: 0;">No courses found</h3>
                        <p style="color: #65676b; margin: 0.5rem 0 0;">Try adjusting your filters or search term</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Quick Stats -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Your Learning Stats</div>
                </div>
                <div style="padding: 1rem 0;">
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #65676b; font-size: 0.875rem;">Courses Enrolled</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--primary);">
                            <?php echo $stats['enrolled_count'] ?? 0; ?>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="color: #65676b; font-size: 0.875rem;">Courses Completed</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: #28a745;">
                            <?php echo $stats['completed_count'] ?? 0; ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem;">Total Learning Hours</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: #ff6b6b;">
                            <?php echo $stats['total_hours'] ?? 0; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommended Courses -->
            <?php if (!empty($recommendations)): ?>
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Recommended for You</div>
                </div>
                <div>
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="event-item" onclick="window.location.href='/courses/<?php echo $rec['id']; ?>'" style="cursor: pointer;">
                            <div class="event-icon" style="background: var(--primary); color: white;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="event-details">
                                <div class="event-name"><?php echo htmlspecialchars($rec['title']); ?></div>
                                <div class="event-info" style="color: #65676b; font-size: 0.75rem;">
                                    <?php echo $rec['reason'] ?? 'Based on your interests'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>

    <script>
        // Live search
        let searchTimeout;
        document.getElementById('course-search')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value;
                fetch(`/api/courses/search?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCourseGrid(data.data);
                        }
                    });
            }, 500);
        });

        // Filter changes
        ['category-filter', 'level-filter', 'sort-filter'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });

        function updateCourseGrid(courses) {
            const grid = document.getElementById('course-grid');
            if (!courses || courses.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem;"><i class="fas fa-search" style="font-size: 3rem; color: #e4e6eb;"></i><h3 style="color: #65676b;">No courses found</h3></div>';
                return;
            }

            grid.innerHTML = courses.map(course => `
                <div class="course-card" onclick="window.location.href='/courses/${course.id}'">
                    ${course.thumbnail ? `<img src="${course.thumbnail}" alt="${course.title}" class="course-thumbnail">` : '<div class="course-thumbnail"></div>'}
                    <div class="course-content">
                        ${course.category ? `<span class="course-category">${course.category}</span>` : ''}
                        <h3 class="course-title">${course.title}</h3>
                        <p class="course-description">${course.description || 'No description available'}</p>
                        <div class="course-meta">
                            <div class="course-instructor">
                                <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                                ${course.instructor_name || 'Sci-Bono'}
                            </div>
                            <div class="course-stats">
                                ${course.lesson_count ? `<span><i class="fas fa-book"></i> ${course.lesson_count} lessons</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>
