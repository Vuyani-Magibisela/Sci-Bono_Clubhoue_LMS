<?php
/**
 * Notification Settings - Manage notification preferences
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Notification Settings - Sci-Bono Clubhouse</title>
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
                <h1 class="content-title">Notification Settings</h1>
                <a href="/settings" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Settings</a>
            </div>

            <div class="settings-container">
                <div class="settings-nav">
                    <a href="/settings/profile" class="settings-nav-link">Profile</a>
                    <a href="/settings/password" class="settings-nav-link">Password</a>
                    <a href="/settings/notifications" class="settings-nav-link active">Notifications</a>
                </div>

                <div class="settings-content">
                    <form id="notifications-form" method="post">
                        <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <!-- Email Notifications -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Email Notifications</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="notification-item">
                                    <div>
                                        <strong>Course Updates</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Receive emails about new courses and updates to enrolled courses</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="email_course_updates" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="notification-item">
                                    <div>
                                        <strong>Lesson Completions</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Get notified when you complete a lesson or course</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="email_lesson_completions" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="notification-item">
                                    <div>
                                        <strong>Event Reminders</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Receive reminders about upcoming events and workshops</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="email_event_reminders" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="notification-item">
                                    <div>
                                        <strong>Messages & Comments</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Get notified when someone messages you or comments on your posts</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="email_messages" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Push Notifications -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Push Notifications</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="notification-item">
                                    <div>
                                        <strong>New Messages</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Get instant notifications for new messages</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="push_messages" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="notification-item">
                                    <div>
                                        <strong>Activity Updates</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Receive notifications for likes, comments, and shares</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="push_activity">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Notifications -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">SMS Notifications</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="notification-item">
                                    <div>
                                        <strong>Important Announcements</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Receive SMS for critical clubhouse announcements</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="sms_announcements">
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="notification-item">
                                    <div>
                                        <strong>Event Reminders</strong>
                                        <p style="color: #65676b; font-size: 0.875rem; margin: 0.25rem 0 0;">Get SMS reminders 1 day before events</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="sms_events">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
                            <button type="button" class="btn-secondary" onclick="window.location.href='/settings'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e4e6eb;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>

    <script>
        document.getElementById('notifications-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/settings/notifications', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notification preferences saved!');
                    window.location.href = '/settings';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });
    </script>
</body>
</html>
