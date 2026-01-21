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
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/homeStyle.css">
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
                <img src="/Sci-Bono_Clubhoue_LMS/public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
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
            <!-- Main Navigation -->
            <div class="menu-group">
                <a href="/Sci-Bono_Clubhoue_LMS/dashboard" class="menu-item active">
                    <div class="menu-icon"><i class="fas fa-home"></i></div>
                    <div class="menu-text">Home</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/settings" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-user"></i></div>
                    <div class="menu-text">Profile</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Messages feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-envelope"></i></div>
                    <div class="menu-text">Messages</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Members directory coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-users"></i></div>
                    <div class="menu-text">Members</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/courses" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-book"></i></div>
                    <div class="menu-text">Courses</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/attendance" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="menu-text">Register</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Projects feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-project-diagram"></i></div>
                    <div class="menu-text">Projects</div>
                </a>
            </div>

            <!-- Groups Section -->
            <div class="menu-group">
                <div class="menu-title">Groups</div>
                <a href="#" class="menu-item" onclick="alert('Groups feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-vr-cardboard"></i></div>
                    <div class="menu-text">Mixed Reality</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Groups feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-theater-masks"></i></div>
                    <div class="menu-text">Performance</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Groups feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-robot"></i></div>
                    <div class="menu-text">FLL</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Groups feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-video"></i></div>
                    <div class="menu-text">Video</div>
                </a>
                <a href="#" class="menu-item" onclick="alert('Groups feature coming soon!'); return false;">
                    <div class="menu-icon"><i class="fas fa-cogs"></i></div>
                    <div class="menu-text">FTC</div>
                </a>
            </div>

            <!-- Admin Section (Conditional) -->
            <?php if (isset($user['user_type']) && $user['user_type'] === 'admin'): ?>
            <div class="menu-group admin-section">
                <div class="menu-title">Admin</div>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/users" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                    <div class="menu-text">Users</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/programs" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="menu-text">Programs</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/courses" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-book"></i></div>
                    <div class="menu-text">Courses</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/reports" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="menu-text">Reports</div>
                </a>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/settings" class="menu-item">
                    <div class="menu-icon"><i class="fas fa-cog"></i></div>
                    <div class="menu-text">Settings</div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Logout Button -->
            <div class="menu-group logout-section">
                <form method="POST" action="/Sci-Bono_Clubhoue_LMS/logout" style="margin: 0;">
                    <?php
                    // Include CSRF token for logout
                    require_once __DIR__ . '/../../../../core/CSRF.php';
                    echo CSRF::field();
                    ?>
                    <button type="submit" class="menu-item logout-item" style="border: none; background: none; width: 100%; cursor: pointer;">
                        <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <div class="menu-text">Logout</div>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Post Creation Widget -->
            <div class="post-creation">
                <div class="post-creation-header">
                    <div class="post-avatar" style="background-color: <?php echo $user['avatar_color'] ?? '#6366F1'; ?>">
                        <?php
                        $firstName = $user['name'] ?? $user['username'] ?? 'U';
                        $lastName = $user['surname'] ?? '';
                        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                        echo $initials;
                        ?>
                    </div>
                    <div class="post-input">
                        What's on your mind, <?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'User'); ?>?
                    </div>
                </div>
                <div class="post-actions">
                    <button class="post-action-btn">
                        <i class="fas fa-image" style="color: #10B981;"></i>
                        <span>Photo</span>
                    </button>
                    <button class="post-action-btn">
                        <i class="fas fa-link" style="color: #3B82F6;"></i>
                        <span>Link</span>
                    </button>
                    <button class="post-action-btn">
                        <i class="fas fa-smile" style="color: #F59E0B;"></i>
                        <span>Feeling</span>
                    </button>
                </div>
            </div>

            <!-- Social Feed -->
            <?php
            // Mock social posts data
            $mockPosts = [
                [
                    'user_name' => 'Thabo Mokoena',
                    'initials' => 'TM',
                    'avatar_color' => '#3B82F6',
                    'relative_time' => '2 hours ago',
                    'post_text' => 'Just finished building my first Arduino robot! It can follow a line and avoid obstacles. Thanks to the mentors at Sci-Bono for the guidance. 🤖',
                    'likes' => 24,
                    'comments' => 5,
                    'shares' => 2
                ],
                [
                    'user_name' => 'Lesego Dlamini',
                    'initials' => 'LD',
                    'avatar_color' => '#EC4899',
                    'relative_time' => '4 hours ago',
                    'post_text' => 'Our FLL team just completed our first practice run! We\'re so excited for the competition next month. Let\'s go Team Innovators! 💪',
                    'likes' => 18,
                    'comments' => 3,
                    'shares' => 1
                ],
                [
                    'user_name' => 'Sipho Khumalo',
                    'initials' => 'SK',
                    'avatar_color' => '#F59E0B',
                    'relative_time' => '6 hours ago',
                    'post_text' => 'Check out this cool 3D model I designed in Blender! It\'s a replica of the Sci-Bono building. Still learning but I\'m getting better every day.',
                    'likes' => 32,
                    'comments' => 8,
                    'shares' => 4
                ],
                [
                    'user_name' => 'Nomsa Mthembu',
                    'initials' => 'NM',
                    'avatar_color' => '#8B5CF6',
                    'relative_time' => '8 hours ago',
                    'post_text' => 'Had an amazing time at today\'s coding workshop! Learned about APIs and how to fetch data from external sources. Can\'t wait to build my own weather app!',
                    'likes' => 15,
                    'comments' => 2,
                    'shares' => 0
                ],
                [
                    'user_name' => 'Themba Ndlovu',
                    'initials' => 'TN',
                    'avatar_color' => '#14B8A6',
                    'relative_time' => '12 hours ago',
                    'post_text' => 'Our video production team just wrapped filming for our documentary about renewable energy. Huge shoutout to everyone involved!',
                    'likes' => 27,
                    'comments' => 6,
                    'shares' => 3
                ]
            ];

            foreach ($mockPosts as $post):
            ?>
            <div class="social-post">
                <div class="post-header">
                    <div class="post-avatar-large" style="background-color: <?php echo $post['avatar_color']; ?>">
                        <?php echo $post['initials']; ?>
                    </div>
                    <div class="post-user-info">
                        <h4><?php echo htmlspecialchars($post['user_name']); ?></h4>
                        <span class="post-time"><?php echo $post['relative_time']; ?></span>
                    </div>
                </div>
                <div class="post-content">
                    <?php echo htmlspecialchars($post['post_text']); ?>
                </div>
                <div class="post-stats">
                    <div class="post-stat">
                        <i class="fas fa-thumbs-up" style="color: var(--primary);"></i>
                        <span><?php echo $post['likes']; ?> likes</span>
                    </div>
                    <div class="post-stat">
                        <i class="fas fa-comment"></i>
                        <span><?php echo $post['comments']; ?> comments</span>
                    </div>
                    <div class="post-stat">
                        <i class="fas fa-share"></i>
                        <span><?php echo $post['shares']; ?> shares</span>
                    </div>
                </div>
                <div class="post-interactions">
                    <button class="interaction-btn">
                        <i class="far fa-thumbs-up"></i>
                        <span>Like</span>
                    </button>
                    <button class="interaction-btn">
                        <i class="far fa-comment"></i>
                        <span>Comment</span>
                    </button>
                    <button class="interaction-btn">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Upcoming Events Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">Upcoming Events</h3>
                    <a href="/events" class="widget-link">View all</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($upcomingEvents)): ?>
                        <?php foreach (array_slice($upcomingEvents, 0, 3) as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <div class="event-date-day"><?php echo date('d', strtotime($event['date'])); ?></div>
                                <div class="event-date-month"><?php echo date('M', strtotime($event['date'])); ?></div>
                            </div>
                            <div class="event-details">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <div class="event-time"><?php echo date('H:i', strtotime($event['time'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="event-item">
                            <div class="event-date" style="background-color: #6366F1;">
                                <div class="event-date-day">15</div>
                                <div class="event-date-month">JAN</div>
                            </div>
                            <div class="event-details">
                                <h4>Robotics Workshop</h4>
                                <div class="event-time">14:00</div>
                            </div>
                        </div>
                        <div class="event-item">
                            <div class="event-date" style="background-color: #10B981;">
                                <div class="event-date-day">18</div>
                                <div class="event-date-month">JAN</div>
                            </div>
                            <div class="event-details">
                                <h4>3D Printing Basics</h4>
                                <div class="event-time">10:00</div>
                            </div>
                        </div>
                        <div class="event-item">
                            <div class="event-date" style="background-color: #F59E0B;">
                                <div class="event-date-day">22</div>
                                <div class="event-date-month">JAN</div>
                            </div>
                            <div class="event-details">
                                <h4>Coding Bootcamp</h4>
                                <div class="event-time">09:00</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Learning Progress Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">My Learning</h3>
                    <a href="/courses" class="widget-link">View all</a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($learningProgress)): ?>
                        <?php foreach (array_slice($learningProgress, 0, 3) as $course): ?>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title"><?php echo htmlspecialchars($course['title']); ?></span>
                                <span class="progress-percentage"><?php echo $course['progress']; ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $course['progress']; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title">Arduino Basics</span>
                                <span class="progress-percentage">75%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: 75%; background-color: #6366F1;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title">3D Design</span>
                                <span class="progress-percentage">45%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: 45%; background-color: #10B981;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-title">Web Development</span>
                                <span class="progress-percentage">90%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: 90%; background-color: #F59E0B;"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Birthdays Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">Birthdays</h3>
                </div>
                <div class="widget-content">
                    <?php if (!empty($birthdays)): ?>
                        <?php foreach (array_slice($birthdays, 0, 3) as $birthday): ?>
                        <div class="birthday-item">
                            <div class="birthday-avatar" style="background-color: <?php echo $birthday['avatar_color'] ?? '#6366F1'; ?>">
                                <?php
                                $bFirstName = $birthday['name'] ?? 'U';
                                $bLastName = $birthday['surname'] ?? '';
                                echo strtoupper(substr($bFirstName, 0, 1) . substr($bLastName, 0, 1));
                                ?>
                            </div>
                            <div class="birthday-info">
                                <h4><?php echo htmlspecialchars($birthday['name'] . ($birthday['surname'] ?? '')); ?></h4>
                                <div class="birthday-date"><?php echo date('F d', strtotime($birthday['date'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="birthday-item">
                            <div class="birthday-avatar" style="background-color: #EC4899;">LS</div>
                            <div class="birthday-info">
                                <h4>Lerato Sithole</h4>
                                <div class="birthday-date">January 16</div>
                            </div>
                        </div>
                        <div class="birthday-item">
                            <div class="birthday-avatar" style="background-color: #8B5CF6;">MB</div>
                            <div class="birthday-info">
                                <h4>Michael Banda</h4>
                                <div class="birthday-date">January 20</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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

    <script src="/Sci-Bono_Clubhoue_LMS/public/assets/js/homedashboard.js"></script>
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
