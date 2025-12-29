<?php
/**
 * Edit Report - Update existing report
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Edit Report - Sci-Bono Clubhouse</title>
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
            <div class="content-header" style="margin-bottom: 2rem;">
                <a href="/reports/<?php echo $report['id']; ?>" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back to Report
                </a>
                <h1 class="content-title">Edit Report</h1>
            </div>

            <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 2rem;">
                <form id="edit-report-form" method="post" action="/reports/<?php echo $report['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="_method" value="PUT">

                    <div class="form-section">
                        <div class="form-section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="report-date" class="form-label">Report Date *</label>
                                    <input type="date" id="report-date" name="report_date" class="form-control input-control"
                                           value="<?php echo htmlspecialchars($report['report_date']); ?>" required>
                                    <div class="error-message"></div>
                                </div>
                                <div class="form-group">
                                    <label for="program-name" class="form-label">Program Name *</label>
                                    <input type="text" id="program-name" name="program_name" class="form-control input-control"
                                           value="<?php echo htmlspecialchars($report['program_name']); ?>" required>
                                    <div class="error-message"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="participants" class="form-label">Number of Participants *</label>
                                <input type="number" id="participants" name="participants" class="form-control input-control"
                                       min="0" value="<?php echo htmlspecialchars($report['participants']); ?>" required>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="narrative" class="form-label">Activity Narrative *</label>
                                <textarea id="narrative" name="narrative" class="form-control input-control" rows="6" required><?php echo htmlspecialchars($report['narrative']); ?></textarea>
                                <div class="error-message"></div>
                            </div>

                            <div class="form-group">
                                <label for="challenges" class="form-label">Challenges Faced</label>
                                <textarea id="challenges" name="challenges" class="form-control input-control" rows="4"><?php echo htmlspecialchars($report['challenges'] ?? ''); ?></textarea>
                                <div class="error-message"></div>
                            </div>

                            <?php if (!empty($report['image_path'])): ?>
                            <div class="form-group">
                                <label class="form-label">Current Image</label>
                                <div>
                                    <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Report Image"
                                         style="max-width: 300px; height: auto; border-radius: 6px; margin-bottom: 0.5rem;">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Update Report
                        </button>
                        <a href="/reports/<?php echo $report['id']; ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('edit-report-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/reports/<?php echo $report['id']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report updated successfully!');
                    window.location.href = '/reports/<?php echo $report['id']; ?>';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the report');
            });
        });
    </script>
</body>
</html>
