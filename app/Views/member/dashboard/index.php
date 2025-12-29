<?php
/**
 * Member Dashboard - Main user home page
 * Phase 3: Week 6-7 Implementation
 *
 * Data from DashboardController:
 * - $stats: User statistics (courses, lessons, hours, badges)
 * - $activityFeed: Recent activity feed
 * - $learningProgress: Course progress data
 * - $upcomingEvents: Upcoming events
 * - $clubhousePrograms: Clubhouse programs/groups
 * - $birthdays: Upcoming birthdays
 * - $continueLearning: Continue learning courses
 * - $badges: User badges
 * - $communityChats: Chat rooms
 * - $onlineContacts: Online users
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="/public/assets/css/homeStyle.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <img src="/public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
                <span>SCI-BONO CLUBHOUSE</span>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" id="globalSearch" placeholder="Search the Clubhouse...">
            </div>
            <div class="header-icons">
                <div class="icon-btn" data-tooltip="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($stats['unread_notifications']) && $stats['unread_notifications'] > 0): ?>
                        <span class="badge"><?php echo $stats['unread_notifications']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-btn" data-tooltip="Messages">
                    <i class="fas fa-comment-dots"></i>
                    <?php if (isset($stats['unread_messages']) && $stats['unread_messages'] > 0): ?>
                        <span class="badge"><?php echo $stats['unread_messages']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-profile">
                    <div class="avatar" style="background-color: <?php echo $user['avatar_color'] ?? '#3F51B5'; ?>">
                        <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="menu-group">
                <div class="menu-item active">
                    <div class="menu-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="menu-text"><a href="/dashboard">Home</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="menu-text"><a href="/profile">Profile</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="menu-text"><a href="/messages">Messages</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text"><a href="/members">Members</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fa-solid fa-chalkboard"></i>
                    </div>
                    <div class="menu-text"><a href="/courses">Learn</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="menu-text"><a href="/attendance">Daily Register</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <div class="menu-text"><a href="/projects">Projects</a></div>
                </div>
            </div>

            <!-- Clubhouse Programs/Groups -->
            <?php if (!empty($clubhousePrograms)): ?>
            <div class="menu-group">
                <div class="menu-title">Groups</div>
                <?php foreach (array_slice($clubhousePrograms, 0, 5) as $program): ?>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text">
                        <a href="/programs/<?php echo $program['id']; ?>">
                            <?php echo htmlspecialchars($program['title']); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Settings -->
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="menu-text"><a href="/settings">Settings</a></div>
            </div>

            <!-- Admin Section (if admin) -->
            <?php if (isset($user['user_type']) && $user['user_type'] === 'admin'): ?>
            <div class="menu-group">
                <div class="menu-title">Admin</div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fa-solid fa-chalkboard"></i>
                    </div>
                    <div class="menu-text"><a href="/admin/courses">Manage Courses</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="menu-text"><a href="/reports">Reports</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text"><a href="/admin/users">Manage Users</a></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Logout -->
            <div class="menu-item" onclick="window.location.href='/logout'">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="menu-text">Log out</div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Stats Cards -->
            <?php if (isset($stats) && !empty($stats)): ?>
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="stat-card" style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="color: #65676b; font-size: 0.875rem;">Courses Enrolled</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #1c1e21;">
                                <?php echo $stats['total_courses'] ?? 0; ?>
                            </div>
                        </div>
                        <div style="color: var(--primary); font-size: 2rem;">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="color: #65676b; font-size: 0.875rem;">Lessons Completed</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #1c1e21;">
                                <?php echo $stats['total_lessons_completed'] ?? 0; ?>
                            </div>
                        </div>
                        <div style="color: #28a745; font-size: 2rem;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="color: #65676b; font-size: 0.875rem;">Learning Hours</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #1c1e21;">
                                <?php echo $stats['total_hours'] ?? 0; ?>
                            </div>
                        </div>
                        <div style="color: #ff6b6b; font-size: 2rem;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="color: #65676b; font-size: 0.875rem;">Badges Earned</div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: #1c1e21;">
                                <?php echo $stats['total_badges'] ?? 0; ?>
                            </div>
                        </div>
                        <div style="color: #ffc107; font-size: 2rem;">
                            <i class="fas fa-award"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Continue Learning Section -->
            <?php if (!empty($continueLearning)): ?>
            <div class="continue-learning-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; color: #1c1e21;">Continue Learning</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php foreach ($continueLearning as $course): ?>
                    <div class="course-card" style="border: 1px solid #e4e6eb; border-radius: 8px; overflow: hidden; cursor: pointer;" onclick="window.location.href='/courses/<?php echo $course['id']; ?>'">
                        <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="width: 100%; height: 150px; object-fit: cover;">
                        <?php endif; ?>
                        <div style="padding: 1rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #1c1e21;">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </h4>
                            <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                <?php echo $course['progress']; ?>% complete
                            </div>
                            <div class="progress-bar-container" style="height: 6px; background: #e4e6eb; border-radius: 3px;">
                                <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Post Creation -->
            <div class="create-post">
                <div class="post-input">
                    <div class="avatar" style="background-color: <?php echo $user['avatar_color'] ?? '#3F51B5'; ?>">
                        <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <input type="text" placeholder="What's on your mind?">
                </div>
                <div class="post-actions">
                    <div class="post-action">
                        <i class="fas fa-image photo-icon"></i>
                        <span>Photo</span>
                    </div>
                    <div class="post-action">
                        <i class="fas fa-link link-icon"></i>
                        <span>Link</span>
                    </div>
                    <div class="post-action">
                        <i class="fas fa-smile emoji-icon"></i>
                        <span>Feeling</span>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="feed" id="activityFeed">
                <?php if (!empty($activityFeed)): ?>
                    <?php foreach ($activityFeed as $activity): ?>
                        <div class="post">
                            <div class="post-header">
                                <div class="post-user">
                                    <div class="avatar" style="background-color: <?php echo $activity['avatar_color'] ?? '#3F51B5'; ?>">
                                        <?php echo htmlspecialchars($activity['initials']); ?>
                                    </div>
                                    <div class="post-info">
                                        <div class="post-author"><?php echo htmlspecialchars($activity['user_name']); ?></div>
                                        <div class="post-meta">
                                            <?php echo htmlspecialchars($activity['relative_time']); ?>
                                            <?php if (!empty($activity['activity_type'])): ?>
                                                • <?php echo htmlspecialchars($activity['activity_type']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="post-options">
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                            </div>
                            <div class="post-content">
                                <div class="post-text">
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                </div>
                                <?php if (!empty($activity['image_url'])): ?>
                                <div class="post-images">
                                    <div class="post-image">
                                        <img src="<?php echo htmlspecialchars($activity['image_url']); ?>" alt="Activity Image">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-stats">
                                <div class="like-count">
                                    <i class="fas fa-thumbs-up" style="color: var(--primary);"></i>
                                    <span><?php echo $activity['likes'] ?? 0; ?></span>
                                </div>
                                <div class="comment-share-count">
                                    <span><?php echo $activity['comments'] ?? 0; ?> comments</span>
                                    <span><?php echo $activity['shares'] ?? 0; ?> shares</span>
                                </div>
                            </div>
                            <div class="post-buttons">
                                <div class="post-button like-btn">
                                    <i class="far fa-thumbs-up"></i>
                                    <span>Like</span>
                                </div>
                                <div class="post-button comment-btn">
                                    <i class="far fa-comment"></i>
                                    <span>Comment</span>
                                </div>
                                <div class="post-button share-btn">
                                    <i class="far fa-share-square"></i>
                                    <span>Share</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="post">
                        <div class="post-content" style="text-align: center; padding: 2rem;">
                            <div class="post-text">
                                No recent activity. Be the first to share something with the community!
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Upcoming Events Section -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Your upcoming events</div>
                    <div class="section-more">See All</div>
                </div>
                <div class="event-list">
                    <?php if (empty($upcomingEvents)): ?>
                        <div style="text-align: center; color: #65676b; padding: 1rem;">
                            No upcoming events at this time.
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-item">
                                <div class="event-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="event-details">
                                    <div class="event-name"><?php echo htmlspecialchars($event['title']); ?></div>
                                    <div class="event-info">
                                        <?php echo htmlspecialchars($event['date'] . ' • ' . $event['time']); ?>
                                    </div>
                                    <?php if (!empty($event['location'])): ?>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Learning Progress -->
            <?php if (!empty($learningProgress)): ?>
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Your learning</div>
                    <div class="section-more" onclick="window.location.href='/my-courses'">See All</div>
                </div>
                <div class="learning-progress-mini">
                    <?php foreach ($learningProgress as $course): ?>
                    <div class="course-progress" style="margin-bottom: 1rem;">
                        <div class="course-info">
                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                            <span><?php echo $course['progress']; ?>% complete</span>
                        </div>
                        <div class="progress-bar-container" style="height: 6px; background: #eee; border-radius: 3px; margin: 5px 0;">
                            <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Birthdays Section -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Birthdays</div>
                </div>
                <div class="birthdays-list">
                    <?php if (empty($birthdays)): ?>
                        <div style="text-align: center; color: #65676b; padding: 1rem;">
                            No upcoming birthdays.
                        </div>
                    <?php else: ?>
                        <?php foreach ($birthdays as $birthday): ?>
                            <div class="birthday-item">
                                <div class="birthday-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="birthday-info">
                                    <strong><?php echo htmlspecialchars($birthday['name']); ?></strong>
                                    <?php echo htmlspecialchars($birthday['message']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Community Chats Section -->
            <?php if (!empty($communityChats)): ?>
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Community chats</div>
                    <div class="section-more">See All</div>
                </div>
                <div class="chat-list">
                    <?php foreach ($communityChats as $chat): ?>
                    <div class="chat-item" onclick="window.location.href='/chats/<?php echo $chat['id']; ?>'">
                        <div class="avatar chat-avatar">
                            <?php if (!empty($chat['image'])): ?>
                                <img src="<?php echo htmlspecialchars($chat['image']); ?>" alt="<?php echo htmlspecialchars($chat['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-users"></i>
                            <?php endif; ?>
                            <?php if ($chat['is_active']): ?>
                                <div class="online-indicator"></div>
                            <?php endif; ?>
                        </div>
                        <div class="chat-info">
                            <strong><?php echo htmlspecialchars($chat['name']); ?></strong>
                            <?php if (isset($chat['member_count'])): ?>
                                <div style="font-size: 0.75rem; color: #65676b;">
                                    <?php echo $chat['member_count']; ?> members
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Online Contacts -->
            <?php if (!empty($onlineContacts)): ?>
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Online contacts</div>
                </div>
                <div class="chat-list">
                    <?php foreach ($onlineContacts as $contact): ?>
                    <div class="chat-item" onclick="window.location.href='/messages/<?php echo $contact['id']; ?>'">
                        <div class="avatar chat-avatar">
                            <?php if (!empty($contact['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($contact['avatar_url']); ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>">
                            <?php else: ?>
                                <div style="background-color: <?php echo $contact['avatar_color']; ?>; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">
                                    <?php echo htmlspecialchars($contact['initials']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <a href="/dashboard" class="mobile-menu-item active">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="/courses" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fa fa-leanpub" aria-hidden="true"></i>
            </div>
            <span>Learn</span>
        </a>
        <?php if (isset($user['user_type']) && $user['user_type'] === 'admin'): ?>
        <a href="/reports" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <span>Reports</span>
        </a>
        <?php endif; ?>
        <a href="/settings" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-cog"></i>
            </div>
            <span>Settings</span>
        </a>
        <a href="/logout" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <span>Log Out</span>
        </a>
    </nav>

    <script src="/public/assets/js/homedashboard.js"></script>
    <script>
        // AJAX Dashboard Refresh
        function refreshDashboardSection(section) {
            fetch(`/api/dashboard/${section}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update specific section with new data
                        console.log(`${section} refreshed`, data);
                    }
                })
                .catch(error => console.error('Error refreshing dashboard:', error));
        }

        // Global search functionality
        document.getElementById('globalSearch')?.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = `/search?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Auto-refresh dashboard data every 5 minutes
        setInterval(() => {
            refreshDashboardSection('stats');
            refreshDashboardSection('activity');
        }, 300000);
    </script>
</body>
</html>
