<?php
/**
 * Password Settings - Change password
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Change Password - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
</head>
<body>
    <button id="mobile-nav-toggle" class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
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
                <li class="sidebar-item"><a href="/courses" class="sidebar-link"><div class="sidebar-icon"><i class="fas fa-book"></i></div><span class="sidebar-text">Learn</span></a></li>
                <li class="sidebar-item"><a href="/settings" class="sidebar-link active"><div class="sidebar-icon"><i class="fas fa-cog"></i></div><span class="sidebar-text">Settings</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="/logout" class="logout-button"><i class="fas fa-sign-out-alt logout-icon"></i><span class="logout-text">Logout</span></a>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1 class="content-title">Change Password</h1>
                <a href="/settings" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Settings</a>
            </div>

            <div class="settings-container">
                <div class="settings-nav">
                    <a href="/settings/profile" class="settings-nav-link">Profile</a>
                    <a href="/settings/password" class="settings-nav-link active">Password</a>
                    <a href="/settings/notifications" class="settings-nav-link">Notifications</a>
                </div>

                <div class="settings-content">
                    <div class="password-requirements" style="background: #f0f8ff; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 2rem; border-radius: 4px;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1c1e21;">Password Requirements:</h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #65676b;">
                            <li>At least 8 characters long</li>
                            <li>Contains at least one uppercase letter</li>
                            <li>Contains at least one lowercase letter</li>
                            <li>Contains at least one number</li>
                            <li>Contains at least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>

                    <form id="password-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <div class="form-section">
                            <div class="form-section-content">
                                <div class="form-group">
                                    <label for="current-password" class="form-label">Current Password *</label>
                                    <input type="password" id="current-password" name="current_password" class="form-control input-control" required>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="new-password" class="form-label">New Password *</label>
                                    <input type="password" id="new-password" name="new_password" class="form-control input-control" required>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm-password" class="form-label">Confirm New Password *</label>
                                    <input type="password" id="confirm-password" name="confirm_password" class="form-control input-control" required>
                                    <div class="error-message"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fas fa-key"></i> Update Password</button>
                            <button type="button" class="btn-secondary" onclick="window.location.href='/settings'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('password-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            const newPass = formData.get('new_password');
            const confirmPass = formData.get('confirm_password');

            if (newPass !== confirmPass) {
                alert('New passwords do not match!');
                return;
            }

            fetch('/settings/password', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password updated successfully!');
                    window.location.href = '/settings';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating your password');
            });
        });
    </script>
</body>
</html>
