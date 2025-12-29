<?php
/**
 * Create Report - Single and batch report creation
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Create Report - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
    <style>
        .report-form-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .batch-report-item {
            border: 1px solid #e4e6eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .remove-report-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e4e6eb;
        }
        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            font-size: 1rem;
            color: #65676b;
            transition: all 0.2s;
        }
        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
                <li class="sidebar-item"><a href="/reports" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-chart-bar"></i></div><span class="sidebar-text">Reports</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header" style="margin-bottom: 2rem;">
                <h1 class="content-title">Create Report</h1>
                <a href="/reports" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>

            <div class="report-form-container">
                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="switchTab('single')">
                        <i class="fas fa-file"></i> Single Report
                    </button>
                    <button class="tab-button" onclick="switchTab('batch')">
                        <i class="fas fa-layer-group"></i> Batch Reports
                    </button>
                </div>

                <!-- Single Report Form -->
                <div id="single-tab" class="tab-content active">
                    <form id="single-report-form" method="post" action="/reports" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <div class="form-section">
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="report-date" class="form-label">Report Date *</label>
                                        <input type="date" id="report-date" name="report_date" class="form-control input-control"
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="program-name" class="form-label">Program Name *</label>
                                        <input type="text" id="program-name" name="program_name" class="form-control input-control"
                                               placeholder="e.g., Robotics Workshop" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="participants" class="form-label">Number of Participants *</label>
                                    <input type="number" id="participants" name="participants" class="form-control input-control"
                                           min="0" placeholder="e.g., 25" required>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="narrative" class="form-label">Activity Narrative *</label>
                                    <textarea id="narrative" name="narrative" class="form-control input-control" rows="6"
                                              placeholder="Describe what happened during the program, achievements, highlights..." required></textarea>
                                    <div class="error-message"></div>
                                    <span class="form-hint">Provide details about activities, achievements, and participant engagement</span>
                                </div>

                                <div class="form-group">
                                    <label for="challenges" class="form-label">Challenges Faced</label>
                                    <textarea id="challenges" name="challenges" class="form-control input-control" rows="4"
                                              placeholder="Any challenges or issues encountered..."></textarea>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="image" class="form-label">Upload Image</label>
                                    <input type="file" id="image" name="image" class="form-control input-control" accept="image/*">
                                    <div class="error-message"></div>
                                    <span class="form-hint">Upload a photo from the program (optional)</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Create Report
                            </button>
                            <a href="/reports" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Batch Report Form -->
                <div id="batch-tab" class="tab-content">
                    <div style="background: #f0f8ff; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
                        <p style="margin: 0; color: #1c1e21;">
                            <strong>Batch Mode:</strong> Create multiple reports at once. Add as many reports as needed for different programs on the same day.
                        </p>
                    </div>

                    <form id="batch-report-form" method="post" action="/reports/batch" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <div id="batch-reports-container">
                            <!-- Initial batch report item -->
                            <div class="batch-report-item">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Program Name *</label>
                                        <input type="text" name="programs[]" class="form-control input-control"
                                               placeholder="e.g., Robotics Workshop" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Participants *</label>
                                        <input type="number" name="participants[]" class="form-control input-control"
                                               min="0" placeholder="25" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Narrative *</label>
                                    <textarea name="narratives[]" class="form-control input-control" rows="3" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Challenges</label>
                                    <textarea name="challenges[]" class="form-control input-control" rows="2"></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Image (optional)</label>
                                    <input type="file" name="images[]" class="form-control input-control" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-secondary" onclick="addBatchReport()" style="margin-bottom: 1.5rem;">
                            <i class="fas fa-plus"></i> Add Another Report
                        </button>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Create All Reports
                            </button>
                            <a href="/reports" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let reportCount = 1;

        function switchTab(tabName) {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        function addBatchReport() {
            reportCount++;
            const container = document.getElementById('batch-reports-container');
            const newReport = document.createElement('div');
            newReport.className = 'batch-report-item';
            newReport.innerHTML = `
                <button type="button" class="remove-report-btn" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Program Name *</label>
                        <input type="text" name="programs[]" class="form-control input-control"
                               placeholder="e.g., Robotics Workshop" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Participants *</label>
                        <input type="number" name="participants[]" class="form-control input-control"
                               min="0" placeholder="25" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Narrative *</label>
                    <textarea name="narratives[]" class="form-control input-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Challenges</label>
                    <textarea name="challenges[]" class="form-control input-control" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Image (optional)</label>
                    <input type="file" name="images[]" class="form-control input-control" accept="image/*">
                </div>
            `;
            container.appendChild(newReport);
        }

        // Form submissions
        document.getElementById('single-report-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/reports', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report created successfully!');
                    window.location.href = '/reports';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the report');
            });
        });

        document.getElementById('batch-report-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/reports/batch', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully created ${data.data.success_count} of ${data.data.total_count} reports!`);
                    window.location.href = '/reports';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating reports');
            });
        });
    </script>
</body>
</html>
