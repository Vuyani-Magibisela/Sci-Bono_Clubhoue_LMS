<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/courses">Courses</a>
    <span class="breadcrumb-separator">/</span>
    <span><?php echo htmlspecialchars($course['title']); ?></span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo htmlspecialchars($course['title']); ?></h1>
        <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
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
            <span class="badge badge-<?php
                echo $course['difficulty_level'] === 'Beginner' ? 'success' :
                     ($course['difficulty_level'] === 'Intermediate' ? 'warning' : 'danger');
            ?>">
                <?php echo htmlspecialchars($course['difficulty_level']); ?>
            </span>
            <span class="badge badge-<?php
                echo $course['status'] === 'active' ? 'success' :
                     ($course['status'] === 'draft' ? 'warning' : 'secondary');
            ?>">
                <?php echo ucfirst($course['status']); ?>
            </span>
            <?php if (!empty($course['is_featured'])): ?>
                <span class="badge badge-info">
                    <i class="fas fa-star"></i> Featured
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/edit" class="btn-primary">
            <i class="fas fa-edit"></i>
            Edit Course
        </a>
        <a href="<?php echo BASE_URL; ?>admin/courses" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>
</div>

<!-- Course Statistics -->
<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(81, 70, 230, 0.1); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo intval($course['enrollment_count'] ?? 0); ?></h3>
            <p>Total Enrollments</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: var(--success);">
            <i class="fas fa-book-open"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo intval($course['lesson_count'] ?? 0); ?></h3>
            <p>Total Lessons</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning);">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo intval($course['module_count'] ?? 0); ?></h3>
            <p>Modules</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: var(--danger);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo number_format($course['average_rating'] ?? 0, 1); ?></h3>
            <p>Average Rating</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Course Image -->
        <?php if (!empty($course['image_path'])): ?>
        <div class="detail-card" style="margin-bottom: 24px;">
            <img
                src="<?php echo BASE_URL; ?>public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>"
                alt="<?php echo htmlspecialchars($course['title']); ?>"
                style="width: 100%; height: auto; border-radius: var(--border-radius-md);">
        </div>
        <?php endif; ?>

        <!-- Course Description -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h2 class="detail-card-title">
                    <i class="fas fa-align-left"></i>
                    Course Description
                </h2>
            </div>
            <div class="detail-card-body">
                <p style="line-height: 1.8; color: var(--dark); white-space: pre-wrap;"><?php echo htmlspecialchars($course['description']); ?></p>
            </div>
        </div>

        <!-- Course Hierarchy -->
        <?php if (!empty($course['modules'])): ?>
        <div class="detail-card">
            <div class="detail-card-header">
                <h2 class="detail-card-title">
                    <i class="fas fa-sitemap"></i>
                    Course Structure
                </h2>
            </div>
            <div class="detail-card-body">
                <?php foreach ($course['modules'] as $module): ?>
                <div class="module-item">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: var(--dark); font-size: 1.1rem;">
                            <i class="fas fa-folder" style="color: var(--primary); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($module['title']); ?>
                        </h4>
                        <span class="badge badge-secondary">
                            <?php echo count($module['lessons'] ?? []); ?> Lessons
                        </span>
                    </div>
                    <?php if (!empty($module['description'])): ?>
                        <p style="color: var(--gray-medium); margin: 0 0 15px 32px; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($module['description']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($module['lessons'])): ?>
                    <div style="margin-left: 32px;">
                        <?php foreach ($module['lessons'] as $lesson): ?>
                        <div style="padding: 10px; border-left: 3px solid var(--gray-light); margin-bottom: 8px;">
                            <div style="font-weight: 500; color: var(--dark);">
                                <i class="fas fa-book-open" style="color: var(--success); margin-right: 8px;"></i>
                                <?php echo htmlspecialchars($lesson['title']); ?>
                            </div>
                            <?php if (!empty($lesson['duration'])): ?>
                                <small style="color: var(--gray-medium); margin-left: 24px;">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($lesson['duration']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Course Information -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h2 class="detail-card-title">
                    <i class="fas fa-info-circle"></i>
                    Course Information
                </h2>
            </div>
            <div class="detail-card-body">
                <div class="info-row">
                    <span class="info-label">Course Code</span>
                    <span class="info-value">
                        <code><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></code>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Duration</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($course['duration'] ?? 'Not specified'); ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Created By</span>
                    <span class="info-value">
                        <?php
                        if (!empty($course['creator_name'])) {
                            echo htmlspecialchars($course['creator_name'] . ' ' . $course['creator_surname']);
                        } else {
                            echo 'Unknown';
                        }
                        ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Created</span>
                    <span class="info-value">
                        <?php
                        if (!empty($course['created_at'])) {
                            echo date('M d, Y', strtotime($course['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Last Updated</span>
                    <span class="info-value">
                        <?php
                        if (!empty($course['updated_at'])) {
                            echo date('M d, Y', strtotime($course['updated_at']));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h2 class="detail-card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="detail-card-body">
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/edit" class="action-btn">
                        <i class="fas fa-edit"></i>
                        Edit Course
                    </a>

                    <button type="button" class="action-btn" onclick="toggleStatus()">
                        <i class="fas fa-toggle-on"></i>
                        Change Status
                    </button>

                    <button type="button" class="action-btn" onclick="toggleFeatured()">
                        <i class="fas fa-star"></i>
                        <?php echo !empty($course['is_featured']) ? 'Unfeature' : 'Feature'; ?>
                    </button>

                    <a href="<?php echo BASE_URL; ?>courses/<?php echo $course['id']; ?>" class="action-btn" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        View as Student
                    </a>

                    <form method="POST"
                          action="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/delete"
                          style="margin: 0;"
                          onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone and will affect <?php echo intval($course['enrollment_count'] ?? 0); ?> enrolled students.');">
                        <?php echo CSRF::field(); ?>
                        <button type="submit" class="action-btn action-btn-danger">
                            <i class="fas fa-trash"></i>
                            Delete Course
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enrollments Preview -->
        <?php if (!empty($course['enrollment_count']) && $course['enrollment_count'] > 0): ?>
        <div class="detail-card">
            <div class="detail-card-header">
                <h2 class="detail-card-title">
                    <i class="fas fa-users"></i>
                    Recent Enrollments
                </h2>
            </div>
            <div class="detail-card-body">
                <p style="color: var(--gray-medium); margin-bottom: 15px;">
                    This course has <strong><?php echo intval($course['enrollment_count']); ?></strong> total enrollments.
                </p>
                <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/enrollments" class="btn-secondary btn-sm" style="width: 100%;">
                    <i class="fas fa-list"></i>
                    View All Enrollments
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional Styles -->
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .detail-card {
        background: white;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .detail-card-header {
        background: linear-gradient(135deg, var(--primary) 0%, #6c5ce7 100%);
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-light);
    }

    .detail-card-title {
        margin: 0;
        color: white;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detail-card-body {
        padding: 20px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-light);
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: var(--gray-dark);
        font-size: 0.9rem;
    }

    .info-value {
        color: var(--dark);
        font-weight: 500;
        text-align: right;
    }

    .info-value code {
        background: var(--light);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--light);
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-sm);
        color: var(--dark);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        width: 100%;
    }

    .action-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-1px);
    }

    .action-btn-danger {
        background: rgba(231, 76, 60, 0.1);
        border-color: var(--danger);
        color: var(--danger);
    }

    .action-btn-danger:hover {
        background: var(--danger);
        color: white;
    }

    .module-item {
        padding: 20px;
        background: var(--light);
        border-radius: var(--border-radius-sm);
        margin-bottom: 16px;
    }

    .module-item:last-child {
        margin-bottom: 0;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 0.9rem;
    }

    @media (max-width: 968px) {
        div[style*="grid-template-columns: 2fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function toggleStatus() {
        const currentStatus = '<?php echo $course['status']; ?>';
        const statuses = {
            'draft': 'active',
            'active': 'archived',
            'archived': 'draft'
        };
        const newStatus = statuses[currentStatus];

        if (confirm(`Change course status from "${currentStatus}" to "${newStatus}"?`)) {
            fetch('<?php echo BASE_URL; ?>api/v1/admin/courses/<?php echo $course['id']; ?>/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status.');
            });
        }
    }

    function toggleFeatured() {
        const isFeatured = <?php echo !empty($course['is_featured']) ? 'true' : 'false'; ?>;
        const newFeatured = !isFeatured;

        if (confirm(newFeatured ? 'Feature this course?' : 'Remove from featured courses?')) {
            fetch('<?php echo BASE_URL; ?>api/v1/admin/courses/<?php echo $course['id']; ?>/featured', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ featured: newFeatured ? 1 : 0 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update featured status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the featured status.');
            });
        }
    }
</script>
