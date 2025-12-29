<?php
/**
 * Visitors Index - Admin visitor management
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitors - Sci-Bono Clubhouse Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2rem; font-weight: 700; margin: 0.5rem 0; }
        .visitors-table { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #e4e6eb; }
        td { padding: 1rem; border-bottom: 1px solid #e4e6eb; }
        tr:hover { background: #f8f9fa; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
        .status-checked-in { background: #d4edda; color: #155724; }
        .status-checked-out { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <button id="mobile-nav-toggle" class="mobile-nav-toggle"><i class="fas fa-bars"></i></button>

    <div class="container">
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar"><i class="fas fa-chevron-right"></i></button>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="/dashboard" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-home"></i></div><span class="sidebar-text">Home</span></a></li>
                <li class="sidebar-item"><a href="/reports" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-chart-bar"></i></div><span class="sidebar-text">Reports</span></a></li>
                <li class="sidebar-item"><a href="/visitors" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-user-check"></i></div><span class="sidebar-text">Visitors</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 class="content-title">Visitor Management</h1>
                    <p style="color: #65676b; margin: 0.5rem 0 0;">Track and manage clubhouse visitors</p>
                </div>
                <a href="/visitor/register" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user-plus"></i> Register Visitor
                </a>
            </div>

            <?php if (!empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div style="color: #65676b; font-size: 0.875rem;">Total Visitors</div>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $statistics['total_visitors'] ?? 0; ?></div>
                    <div style="color: #65676b; font-size: 0.875rem;"><i class="fas fa-calendar"></i> All time</div>
                </div>
                <div class="stat-card">
                    <div style="color: #65676b; font-size: 0.875rem;">Today's Visitors</div>
                    <div class="stat-value" style="color: #28a745;"><?php echo $statistics['today_count'] ?? 0; ?></div>
                    <div style="color: #65676b; font-size: 0.875rem;"><i class="fas fa-clock"></i> Currently: <?php echo $statistics['checked_in'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <div style="color: #65676b; font-size: 0.875rem;">This Month</div>
                    <div class="stat-value" style="color: #ff6b6b;"><?php echo $statistics['this_month'] ?? 0; ?></div>
                    <div style="color: #65676b; font-size: 0.875rem;"><i class="fas fa-chart-line"></i> visitors</div>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
                <form method="get" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="date" name="date_from" placeholder="Date From" class="form-control input-control" value="<?php echo $filters['date_from'] ?? ''; ?>">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <input type="date" name="date_to" placeholder="Date To" class="form-control input-control" value="<?php echo $filters['date_to'] ?? ''; ?>">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <select name="purpose" class="form-control form-select">
                            <option value="">All Purposes</option>
                            <?php foreach ($purposeSummary ?? [] as $purpose): ?>
                                <option value="<?php echo htmlspecialchars($purpose['purpose']); ?>" <?php echo ($filters['purpose'] ?? '') == $purpose['purpose'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($purpose['purpose']); ?> (<?php echo $purpose['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </form>
            </div>

            <div class="visitors-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email / Phone</th>
                            <th>Purpose</th>
                            <th>Company</th>
                            <th>Visit Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($visitors)): ?>
                            <?php foreach ($visitors as $visitor): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($visitor['name'] . ' ' . $visitor['surname']); ?></div>
                                        <?php if (!empty($visitor['id_number'])): ?>
                                        <div style="color: #65676b; font-size: 0.75rem;">ID: <?php echo htmlspecialchars($visitor['id_number']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;"><?php echo htmlspecialchars($visitor['email']); ?></div>
                                        <div style="color: #65676b; font-size: 0.75rem;"><?php echo htmlspecialchars($visitor['phone']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['company'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($visitor['visit_date'])); ?></td>
                                    <td>
                                        <?php if (!empty($visitor['check_out_time'])): ?>
                                            <span class="status-badge status-checked-out">Checked Out</span>
                                        <?php elseif (!empty($visitor['check_in_time'])): ?>
                                            <span class="status-badge status-checked-in">Checked In</span>
                                        <?php else: ?>
                                            <span class="status-badge">Registered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="/visitors/<?php echo $visitor['id']; ?>" title="View"><i class="fas fa-eye" style="color: var(--primary);"></i></a>
                                            <?php if (empty($visitor['check_out_time'])): ?>
                                                <?php if (empty($visitor['check_in_time'])): ?>
                                                    <a href="#" onclick="checkIn(<?php echo $visitor['id']; ?>); return false;" title="Check In"><i class="fas fa-sign-in-alt" style="color: #28a745;"></i></a>
                                                <?php else: ?>
                                                    <a href="#" onclick="checkOut(<?php echo $visitor['id']; ?>); return false;" title="Check Out"><i class="fas fa-sign-out-alt" style="color: #ffc107;"></i></a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($user['user_type'] === 'admin'): ?>
                                            <a href="#" onclick="deleteVisitor(<?php echo $visitor['id']; ?>); return false;" title="Delete"><i class="fas fa-trash" style="color: #dc3545;"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 3rem; color: #65676b;">No visitors found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function checkIn(id) {
            fetch(`/visitors/${id}/checkin`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { alert('Checked in successfully'); location.reload(); }
                else { alert('Error: ' + data.message); }
            });
        }

        function checkOut(id) {
            fetch(`/visitors/${id}/checkout`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { alert('Checked out successfully'); location.reload(); }
                else { alert('Error: ' + data.message); }
            });
        }

        function deleteVisitor(id) {
            if (!confirm('Delete this visitor?')) return;
            fetch(`/visitors/${id}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>&_method=DELETE'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { alert('Deleted'); location.reload(); }
                else { alert('Error: ' + data.message); }
            });
        }
    </script>
</body>
</html>
