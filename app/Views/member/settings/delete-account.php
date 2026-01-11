<?php
/**
 * Delete Account - Account deletion page
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Delete Account - Sci-Bono Clubhouse</title>
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
                <h1 class="content-title" style="color: #dc3545;">Delete Account</h1>
                <a href="/settings" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Settings</a>
            </div>

            <div class="settings-container">
                <div class="settings-content">
                    <!-- Warning Banner -->
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1.5rem; margin-bottom: 2rem; border-radius: 4px;">
                        <div style="display: flex; align-items: start;">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 1.5rem; margin-right: 1rem;"></i>
                            <div>
                                <h3 style="margin: 0 0 0.5rem 0; color: #1c1e21;">Warning: This action is permanent!</h3>
                                <p style="margin: 0; color: #65676b;">
                                    Deleting your account will permanently remove all your data from our system. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- What gets deleted -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">What will be deleted:</h3>
                        </div>
                        <div class="form-section-content">
                            <ul style="color: #65676b; line-height: 2;">
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> Your profile and personal information</li>
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> All course enrollments and progress</li>
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> Your posts, comments, and activity</li>
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> All messages and conversations</li>
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> Badges and achievements</li>
                                <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 0.5rem;"></i> Attendance records</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Before you go -->
                    <div style="background: #f0f8ff; border-left: 4px solid #2196F3; padding: 1.5rem; margin: 2rem 0; border-radius: 4px;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1c1e21;">Before you go...</h4>
                        <p style="margin: 0; color: #65676b;">
                            If you're having issues with your account or the platform, please consider contacting our support team first.
                            We're here to help! Email us at <a href="mailto:support@scibono.org" style="color: #2196F3;">support@scibono.org</a>
                        </p>
                    </div>

                    <!-- Delete Account Form -->
                    <div class="form-section" style="border: 2px solid #dc3545; border-radius: 8px; padding: 2rem;">
                        <div class="form-section-header">
                            <h3 class="form-section-title" style="color: #dc3545;">Confirm Account Deletion</h3>
                        </div>
                        <div class="form-section-content">
                            <p style="color: #65676b; margin-bottom: 1.5rem;">
                                To confirm deletion, please enter your password and type <strong>"DELETE"</strong> in the confirmation field below.
                            </p>

                            <form id="delete-account-form" method="post">
                                <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                                <div class="form-group">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" id="password" name="password" class="form-control input-control" required>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="confirmation" class="form-label">Type "DELETE" to confirm *</label>
                                    <input type="text" id="confirmation" name="confirmation" class="form-control input-control" required>
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-group">
                                    <label for="reason" class="form-label">Reason for leaving (optional)</label>
                                    <textarea id="reason" name="reason" class="form-control input-control" rows="3" placeholder="Tell us why you're leaving..."></textarea>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-danger" style="background: #dc3545; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-trash-alt"></i> Delete My Account
                                    </button>
                                    <button type="button" class="btn-secondary" onclick="window.location.href='/settings'">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('delete-account-form')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const confirmation = document.getElementById('confirmation').value;
            if (confirmation !== 'DELETE') {
                alert('Please type "DELETE" exactly to confirm account deletion.');
                return;
            }

            if (!confirm('Are you absolutely sure you want to delete your account? This action CANNOT be undone!')) {
                return;
            }

            const formData = new FormData(this);

            fetch('/settings/delete-account', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your account has been deleted. You will now be logged out.');
                    window.location.href = '/logout';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting your account');
            });
        });
    </script>
</body>
</html>
