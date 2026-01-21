<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
</div>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
    <div class="header-actions">
        <form method="GET" action="<?php echo BASE_URL; ?>admin" style="display: flex; gap: 10px;">
            <select name="year" class="form-select" onchange="this.form.submit()">
                <?php foreach ($yearOptions as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="month" class="form-select" onchange="this.form.submit()">
                <?php foreach ($monthOptions as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $selectedMonth == $value ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Statistics Cards Grid -->
<div class="stats-grid">
    <!-- Total Users Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(81, 70, 230, 0.1);">
            <i class="fas fa-users" style="color: var(--primary);"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['totalUsers']); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/users" class="stat-link">
            View All <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Total Courses Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1);">
            <i class="fas fa-book" style="color: var(--success);"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['totalCourses']); ?></div>
            <div class="stat-label">Total Courses</div>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/courses" class="stat-link">
            Manage <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Active Programs Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(243, 156, 18, 0.1);">
            <i class="fas fa-calendar-alt" style="color: var(--warning);"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['totalPrograms']); ?></div>
            <div class="stat-label">Active Programs</div>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/programs" class="stat-link">
            View Programs <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Today's Attendance Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(231, 76, 60, 0.1);">
            <i class="fas fa-clipboard-check" style="color: var(--danger);"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['todayAttendance']); ?></div>
            <div class="stat-label">Attendance Today</div>
        </div>
        <a href="<?php echo BASE_URL; ?>attendance" class="stat-link">
            View Register <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Monthly Members Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1);">
            <i class="fas fa-user-clock" style="color: #3498db;"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['monthlyMembers']); ?></div>
            <div class="stat-label">
                <?php echo $selectedMonth > 0 ? $monthOptions[$selectedMonth] : 'Yearly'; ?> Members
            </div>
        </div>
    </div>

    <!-- Course Enrollments Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(155, 89, 182, 0.1);">
            <i class="fas fa-graduation-cap" style="color: #9b59b6;"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['totalEnrollments']); ?></div>
            <div class="stat-label">Course Enrollments</div>
        </div>
    </div>

    <!-- Program Registrations Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(26, 188, 156, 0.1);">
            <i class="fas fa-user-check" style="color: #1abc9c;"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($stats['totalRegistrations']); ?></div>
            <div class="stat-label">Program Registrations</div>
        </div>
    </div>

    <!-- System Health Card -->
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1);">
            <i class="fas fa-server" style="color: var(--success);"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">
                <i class="fas fa-circle" style="color: var(--success); font-size: 0.5em; margin-right: 5px;"></i>
                Online
            </div>
            <div class="stat-label">System Status</div>
        </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="quick-actions-section" style="margin-top: 30px;">
    <h2 class="section-title" style="font-size: 1.4rem; margin-bottom: 20px; color: var(--dark);">
        <i class="fas fa-bolt" style="margin-right: 8px;"></i>
        Quick Actions
    </h2>
    <div class="quick-actions-grid">
        <a href="<?php echo BASE_URL; ?>admin/users/create" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="qa-label">Add User</div>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/courses/create" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="qa-label">Create Course</div>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/programs/create" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="qa-label">New Program</div>
        </a>

        <a href="<?php echo BASE_URL; ?>attendance" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-clipboard"></i>
            </div>
            <div class="qa-label">Attendance</div>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/analytics" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="qa-label">Analytics</div>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/settings" class="quick-action-card">
            <div class="qa-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="qa-label">Settings</div>
        </a>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="recent-activity-section" style="margin-top: 30px;">
    <h2 class="section-title" style="font-size: 1.4rem; margin-bottom: 20px; color: var(--dark);">
        <i class="fas fa-history" style="margin-right: 8px;"></i>
        Recent Activity
    </h2>

    <?php if (!empty($recentActivity)): ?>
        <div class="activity-list">
            <?php foreach ($recentActivity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon activity-<?php echo $activity['color']; ?>">
                        <i class="fas <?php echo $activity['icon']; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                        <div class="activity-time">
                            <i class="fas fa-clock"></i>
                            <?php
                            $timestamp = strtotime($activity['created_at']);
                            $diff = time() - $timestamp;

                            if ($diff < 60) {
                                echo 'Just now';
                            } elseif ($diff < 3600) {
                                echo floor($diff / 60) . ' minutes ago';
                            } elseif ($diff < 86400) {
                                echo floor($diff / 3600) . ' hours ago';
                            } else {
                                echo date('M d, Y', $timestamp);
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 3rem; color: var(--gray-light); margin-bottom: 10px;"></i>
            <p>No recent activity</p>
        </div>
    <?php endif; ?>
</div>

<!-- Additional Styles -->
<style>
    .form-select {
        padding: 8px 12px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-sm);
        font-size: 0.9rem;
        cursor: pointer;
        background-color: white;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius-md);
        padding: 24px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        position: relative;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 15px;
    }

    .stat-content {
        margin-bottom: 15px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.95rem;
        color: var(--gray-medium);
        font-weight: 500;
    }

    .stat-link {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: gap 0.2s ease;
    }

    .stat-link:hover {
        gap: 10px;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }

    .quick-action-card {
        background: white;
        border-radius: var(--border-radius-md);
        padding: 24px 16px;
        text-align: center;
        text-decoration: none;
        color: var(--dark);
        box-shadow: var(--shadow-sm);
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        background: var(--light);
    }

    .qa-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .qa-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
    }

    .activity-list {
        background: white;
        border-radius: var(--border-radius-md);
        padding: 20px;
        box-shadow: var(--shadow-sm);
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid var(--gray-light);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .activity-success {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success);
    }

    .activity-primary {
        background-color: rgba(81, 70, 230, 0.1);
        color: var(--primary);
    }

    .activity-warning {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning);
    }

    .activity-danger {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger);
    }

    .activity-content {
        flex: 1;
    }

    .activity-description {
        font-size: 0.95rem;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .activity-time {
        font-size: 0.85rem;
        color: var(--gray-medium);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-medium);
    }

    .section-title {
        display: flex;
        align-items: center;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>
