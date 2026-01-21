<?php
/**
 * Reports Index - Admin/Mentor reports listing
 * Phase 3: Week 6-7 Implementation
 *
 * Data from ReportController:
 * - $reports: Array of reports
 * - $statistics: Report statistics
 * - $programSummary: Reports by program
 * - $filters: Current filter values
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Sci-Bono Clubhouse Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .stat-label {
            color: #65676b;
            font-size: 0.875rem;
        }
        .reports-table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #1c1e21;
            border-bottom: 2px solid #e4e6eb;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e4e6eb;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .filter-bar {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        .report-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <button id="mobile-nav-toggle" class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="/dashboard" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-home"></i></div><span class="sidebar-text">Home</span></a></li>
                <li class="sidebar-item"><a href="/admin/courses" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-book"></i></div><span class="sidebar-text">Manage Courses</span></a></li>
                <li class="sidebar-item"><a href="/admin/users" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-users"></i></div><span class="sidebar-text">Manage Users</span></a></li>
                <li class="sidebar-item"><a href="/reports" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-chart-bar"></i></div><span class="sidebar-text">Reports</span></a></li>
                <li class="sidebar-item"><a href="/visitors" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-user-check"></i></div><span class="sidebar-text">Visitors</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 class="content-title">Clubhouse Reports</h1>
                    <p style="color: #65676b; margin: 0.5rem 0 0;">Track program activities and participant engagement</p>
                </div>
                <a href="/reports/create" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 6px;">
                    <i class="fas fa-plus"></i> Create Report
                </a>
            </div>

            <!-- Statistics Cards -->
            <?php if (!empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Reports</div>
                    <div class="stat-value" style="color: var(--primary);">
                        <?php echo $statistics['total_reports'] ?? 0; ?>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem;">
                        <i class="fas fa-calendar"></i> This month: <?php echo $statistics['this_month'] ?? 0; ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Participants</div>
                    <div class="stat-value" style="color: #28a745;">
                        <?php echo $statistics['total_participants'] ?? 0; ?>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem;">
                        <i class="fas fa-users"></i> Across all programs
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Active Programs</div>
                    <div class="stat-value" style="color: #ff6b6b;">
                        <?php echo $statistics['active_programs'] ?? 0; ?>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem;">
                        <i class="fas fa-chart-line"></i> Currently running
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Avg Attendance</div>
                    <div class="stat-value" style="color: #ffc107;">
                        <?php echo round($statistics['avg_attendance'] ?? 0); ?>%
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem;">
                        <i class="fas fa-percent"></i> Last 30 days
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="get" action="/reports">
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Date From</label>
                            <input type="date" name="date_from" class="form-control input-control"
                                   value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                        </div>

                        <div class="filter-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Date To</label>
                            <input type="date" name="date_to" class="form-control input-control"
                                   value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                        </div>

                        <div class="filter-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Program</label>
                            <select name="program_name" class="form-control form-select">
                                <option value="">All Programs</option>
                                <?php if (!empty($programSummary)): ?>
                                    <?php foreach ($programSummary as $program): ?>
                                        <option value="<?php echo htmlspecialchars($program['program_name']); ?>"
                                                <?php echo ($filters['program_name'] ?? '') == $program['program_name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                            (<?php echo $program['count']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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

            <!-- Reports Table -->
            <div class="reports-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Program Name</th>
                            <th>Participants</th>
                            <th>Narrative</th>
                            <th>Reported By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo date('M d, Y', strtotime($report['report_date'])); ?>
                                        </div>
                                        <div style="color: #65676b; font-size: 0.75rem;">
                                            <?php echo date('l', strtotime($report['report_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; color: #1c1e21;">
                                            <?php echo htmlspecialchars($report['program_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="report-badge" style="background: #e3f2fd; color: #1976d2;">
                                            <i class="fas fa-users"></i> <?php echo $report['participants']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #65676b;">
                                            <?php echo htmlspecialchars(substr($report['narrative'], 0, 80)); ?>
                                            <?php echo strlen($report['narrative']) > 80 ? '...' : ''; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="color: #65676b; font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($report['created_by_name'] ?? 'Unknown'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="/reports/<?php echo $report['id']; ?>"
                                               style="color: var(--primary); text-decoration: none;"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/reports/<?php echo $report['id']; ?>/edit"
                                               style="color: #ffc107; text-decoration: none;"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['user_type'] === 'admin'): ?>
                                            <a href="#" onclick="deleteReport(<?php echo $report['id']; ?>); return false;"
                                               style="color: #dc3545; text-decoration: none;"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: #65676b;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #e4e6eb; display: block; margin-bottom: 1rem;"></i>
                                    No reports found. Create your first report to get started.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
            formData.append('_method', 'DELETE');

            fetch(`/reports/${reportId}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report deleted successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the report');
            });
        }

        // Mobile nav toggle
        document.getElementById('mobile-nav-toggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Sidebar toggle
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
