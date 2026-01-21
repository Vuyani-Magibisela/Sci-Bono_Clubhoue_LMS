<?php
/**
 * Report Details - View single report
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
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
                <li class="sidebar-item"><a href="/reports" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-chart-bar"></i></div><span class="sidebar-text">Reports</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <a href="/reports" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <i class="fas fa-arrow-left"></i> Back to Reports
                    </a>
                    <h1 class="content-title">Report Details</h1>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="/reports/<?php echo $report['id']; ?>/edit" class="btn-secondary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php if ($user['user_type'] === 'admin'): ?>
                    <button onclick="deleteReport(<?php echo $report['id']; ?>)" class="btn-danger" style="background: #dc3545;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Report Date</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: #1c1e21;">
                            <?php echo date('F d, Y', strtotime($report['report_date'])); ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Program Name</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--primary);">
                            <?php echo htmlspecialchars($report['program_name']); ?>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="text-align: center;">
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Participants</div>
                        <div style="font-size: 2rem; font-weight: 700; color: #28a745;">
                            <?php echo $report['participants']; ?>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Reported By</div>
                        <div style="font-size: 1rem; font-weight: 600;">
                            <?php echo htmlspecialchars($report['created_by_name'] ?? 'Unknown'); ?>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">Created</div>
                        <div style="font-size: 0.875rem; font-weight: 600;">
                            <?php echo date('M d, Y', strtotime($report['created_at'])); ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($report['image_path'])): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: #1c1e21; margin-bottom: 1rem;">Program Image</h3>
                    <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Report Image"
                         style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 2rem;">
                    <h3 style="color: #1c1e21; margin-bottom: 1rem;">Activity Narrative</h3>
                    <div style="line-height: 1.8; color: #65676b; white-space: pre-wrap;">
                        <?php echo htmlspecialchars($report['narrative']); ?>
                    </div>
                </div>

                <?php if (!empty($report['challenges'])): ?>
                <div>
                    <h3 style="color: #1c1e21; margin-bottom: 1rem;">Challenges Faced</h3>
                    <div style="line-height: 1.8; color: #65676b; white-space: pre-wrap;">
                        <?php echo htmlspecialchars($report['challenges']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report?')) return;

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
                    window.location.href = '/reports';
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
