<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Courses</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Course Management</h1>
        <p style="color: var(--gray-medium); margin-top: 5px;">
            Manage all learning content across the platform
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo BASE_URL; ?>admin/courses/create" class="btn-primary">
            <i class="fas fa-plus"></i>
            Create Course
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(81, 70, 230, 0.1); color: var(--primary);">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Total Courses</p>
        </div>
    </div>

    <?php
    // Count by type
    $typeCounts = [
        'full_course' => 0,
        'short_course' => 0,
        'lesson' => 0,
        'skill_activity' => 0
    ];
    foreach ($courses as $course) {
        if (isset($typeCounts[$course['type']])) {
            $typeCounts[$course['type']]++;
        }
    }
    ?>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: var(--success);">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $typeCounts['full_course']; ?></h3>
            <p>Full Courses</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning);">
            <i class="fas fa-certificate"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $typeCounts['short_course']; ?></h3>
            <p>Short Courses</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: var(--danger);">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $typeCounts['lesson'] + $typeCounts['skill_activity']; ?></h3>
            <p>Lessons & Activities</p>
        </div>
    </div>
</div>

<!-- Filter and Search Bar -->
<div class="filter-section" style="background: white; padding: 20px; border-radius: var(--border-radius-md); box-shadow: var(--shadow-sm); margin-bottom: 20px;">
    <form method="GET" action="<?php echo BASE_URL; ?>admin/courses" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 250px;">
            <label for="search" class="form-label">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="form-control"
                placeholder="Search by title, code, or description..."
                value="<?php echo htmlspecialchars($search ?? ''); ?>">
        </div>

        <div class="form-group" style="min-width: 150px;">
            <label for="type" class="form-label">Type</label>
            <select id="type" name="type" class="form-control">
                <option value="">All Types</option>
                <option value="full_course" <?php echo ($filters['type'] ?? '') === 'full_course' ? 'selected' : ''; ?>>Full Course</option>
                <option value="short_course" <?php echo ($filters['type'] ?? '') === 'short_course' ? 'selected' : ''; ?>>Short Course</option>
                <option value="lesson" <?php echo ($filters['type'] ?? '') === 'lesson' ? 'selected' : ''; ?>>Lesson</option>
                <option value="skill_activity" <?php echo ($filters['type'] ?? '') === 'skill_activity' ? 'selected' : ''; ?>>Skill Activity</option>
            </select>
        </div>

        <div class="form-group" style="min-width: 150px;">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All Status</option>
                <option value="draft" <?php echo ($filters['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="archived" <?php echo ($filters['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <div class="form-group" style="min-width: 150px;">
            <label for="difficulty_level" class="form-label">Difficulty</label>
            <select id="difficulty_level" name="difficulty_level" class="form-control">
                <option value="">All Levels</option>
                <option value="Beginner" <?php echo ($filters['difficulty_level'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                <option value="Intermediate" <?php echo ($filters['difficulty_level'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                <option value="Advanced" <?php echo ($filters['difficulty_level'] ?? '') === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
            </select>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search"></i>
                Search
            </button>
            <a href="<?php echo BASE_URL; ?>admin/courses" class="btn-secondary">
                <i class="fas fa-times"></i>
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Courses Table -->
<div class="table-container">
    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <i class="fas fa-book" style="font-size: 48px; color: var(--gray-medium); margin-bottom: 20px;"></i>
            <h3 style="color: var(--dark); margin-bottom: 10px;">No courses found</h3>
            <p style="color: var(--gray-medium); margin-bottom: 20px;">
                <?php if (!empty($search) || !empty(array_filter($filters ?? []))): ?>
                    Try adjusting your filters or search query.
                <?php else: ?>
                    Get started by creating your first course.
                <?php endif; ?>
            </p>
            <a href="<?php echo BASE_URL; ?>admin/courses/create" class="btn-primary">
                <i class="fas fa-plus"></i>
                Create First Course
            </a>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Title</th>
                    <th style="width: 120px;">Code</th>
                    <th style="width: 140px;">Type</th>
                    <th style="width: 120px;">Difficulty</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 100px;">Enrollments</th>
                    <th style="width: 140px;">Created</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?php echo htmlspecialchars($course['id']); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if (!empty($course['is_featured'])): ?>
                                <i class="fas fa-star" style="color: var(--warning);" title="Featured"></i>
                            <?php endif; ?>
                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                        </div>
                        <?php if (!empty($course['creator_name'])): ?>
                            <small style="color: var(--gray-medium);">
                                by <?php echo htmlspecialchars($course['creator_name'] . ' ' . $course['creator_surname']); ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code style="background: var(--light); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">
                            <?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?>
                        </code>
                    </td>
                    <td>
                        <span class="badge badge-primary">
                            <?php
                            $types = [
                                'full_course' => 'Full Course',
                                'short_course' => 'Short Course',
                                'lesson' => 'Lesson',
                                'skill_activity' => 'Skill Activity'
                            ];
                            echo $types[$course['type']] ?? ucfirst($course['type']);
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php
                            echo $course['difficulty_level'] === 'Beginner' ? 'success' :
                                 ($course['difficulty_level'] === 'Intermediate' ? 'warning' : 'danger');
                        ?>">
                            <?php echo htmlspecialchars($course['difficulty_level']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php
                            echo $course['status'] === 'active' ? 'success' :
                                 ($course['status'] === 'draft' ? 'warning' : 'secondary');
                        ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php echo intval($course['enrollment_count'] ?? 0); ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($course['created_at'])) {
                            echo date('M d, Y', strtotime($course['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>"
                               class="btn-icon" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/edit"
                               class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST"
                                  action="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/delete"
                                  style="display: inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                                <?php echo CSRF::field(); ?>
                                <button type="submit" class="btn-icon btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i>
                    Previous
                </a>
            <?php endif; ?>

            <div class="pagination-numbers">
                <?php
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);

                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>"
                       class="pagination-number <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" class="pagination-btn">
                    Next
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Additional Styles -->
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--border-radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-details h3 {
        margin: 0;
        font-size: 1.8rem;
        color: var(--dark);
        font-weight: 700;
    }

    .stat-details p {
        margin: 5px 0 0 0;
        color: var(--gray-medium);
        font-size: 0.9rem;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: var(--border-radius-sm);
        background: var(--light);
        color: var(--dark);
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-icon:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-1px);
    }

    .btn-icon.btn-danger:hover {
        background: var(--danger);
        color: white;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filter-section form {
            flex-direction: column;
        }

        .filter-section form > * {
            width: 100% !important;
        }
    }
</style>
