<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: login.php");
    exit;
}

// Include the auto-logout script to track inactivity
include 'app/Controllers/sessionTimer.php';

// Include dashboard data loader
require_once 'app/Models/dashboard-data-loader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubhouse Social</title>
    <link rel="stylesheet" href="./public/assets/css/homeStyle.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<!-- Loading Spinner (Hidden by default) -->
<div class="loading" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <img src="public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
                <span>SCI-BONO CLUBHOUSE</span>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search the Clubhouse...">
            </div>
            <div class="header-icons">
                <div class="icon-btn" data-tooltip="Notifications">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="icon-btn" data-tooltip="Messages">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="user-profile">
                    <div class="avatar">
                        <?php if(isset($_SESSION['username'])) : ?>
                            <?php echo substr($_SESSION['username'], 0, 1); ?>
                        <?php else : ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-name">
                        <?php if(isset($_SESSION['username'])) echo $_SESSION['username']; ?>
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
                    <div class="menu-text">Home</div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="menu-text">Profile</div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="menu-text">Messages</div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text"><a href="./members.php">Members</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="menu-text">Feed</div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="menu-text"><a href="./app/Views/learn.php">learn</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="menu-text"><a href="./signin.php">Daily Register</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <div class="menu-text"><a href="./app/Views/projects.php">Projects</a></div>
                </div>
            </div>
            
            <div class="menu-group">
                <div class="menu-title">Groups</div>
                <?php foreach ($clubhousePrograms as $program): ?>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text"><?php echo htmlspecialchars($program['title']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Admin links shown only to admin users -->
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "admin"): ?>
            <div class="menu-group">
                <div class="menu-title">Admin</div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="menu-text"><a href="./app/Views/statsDashboard.php">Reports</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="menu-text"><a href="./app/Views/settings.php">Settings</a></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="menu-item" onclick="window.location.href='logout_process.php'">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="menu-text">Log out</div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Post Creation -->
            <div class="create-post">
                <div class="post-input">
                    <div class="avatar">
                        <?php if(isset($_SESSION['username'])) : ?>
                            <?php echo substr($_SESSION['username'], 0, 1); ?>
                        <?php else : ?>
                            <i class="fas fa-user"></i>
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

            <!-- Feed -->
            <div class="feed">
                <!-- Member Posts Section -->
                <?php if (!empty($memberPosts)): ?>
                    <?php foreach ($memberPosts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <div class="post-user">
                                    <div class="avatar" style="background-color: <?php echo getAvatarColor($post['user_id']); ?>">
                                        <?php echo getInitials($post['name'] . ' ' . $post['surname']); ?>
                                    </div>
                                    <div class="post-info">
                                        <div class="post-author"><?php echo htmlspecialchars($post['name'] . ' ' . $post['surname']); ?></div>
                                        <div class="post-meta"><?php echo htmlspecialchars($post['formatted_date']); ?></div>
                                    </div>
                                </div>
                                <div class="post-options">
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                            </div>
                            <div class="post-content">
                                <div class="post-text">
                                    <?php echo htmlspecialchars($post['description']); ?>
                                </div>
                                <div class="post-images">
                                    <?php if (isset($post['image']) && file_exists('public/assets/uploads/images/' . $post['image'])): ?>
                                        <div class="post-image">
                                            <img src="public/assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="post-image">
                                            <img src="https://source.unsplash.com/random/600x400?<?php echo urlencode($post['tags'][0] ?? 'technology'); ?>" alt="Project Image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="post-stats">
                                <div class="like-count">
                                    <i class="fas fa-thumbs-up" style="color: var(--primary);"></i>
                                    <span><?php echo rand(5, 50); ?></span>
                                </div>
                                <div class="comment-share-count">
                                    <span><?php echo rand(1, 20); ?> comments</span>
                                    <span><?php echo rand(0, 5); ?> shares</span>
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
                                No posts yet. Be the first to share something with the community!
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
                        <div style="text-align: center; color: #65676b;">
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
                                        <?php echo htmlspecialchars($event['formatted_date'] . ' • ' . $event['time']); ?>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
          <!-- Learning Progress (Optional) -->
          <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Your learning</div>
                    <div class="section-more">See All</div>
                </div>
                <div class="learning-progress-mini">
                    <div class="course-progress">
                        <div class="course-info">
                            <strong>Robotics</strong>
                            <span>65% complete</span>
                        </div>
                        <div class="progress-bar-container" style="height: 6px; background: #eee; border-radius: 3px; margin: 5px 0;">
                            <div class="progress-bar" style="width: 65%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div class="course-progress">
                        <div class="course-info">
                            <strong>Web Development</strong>
                            <span>40% complete</span>
                        </div>
                        <div class="progress-bar-container" style="height: 6px; background: #eee; border-radius: 3px; margin: 5px 0;">
                            <div class="progress-bar" style="width: 40%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Birthdays Section -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Birthdays</div>
                </div>
                <div class="birthdays-list">
                    <?php 
                    // This would come from your database in a real implementation
                    $birthdays = [];
                    if (isset($userBirthdays)) {
                        $birthdays = $userBirthdays;
                    }
                    ?>
                    
                    <?php if (empty($birthdays)): ?>
                        <div class="birthday-item">
                            <div class="birthday-icon">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                            <div class="birthday-info">
                                <strong>Bob Hammond</strong> turns 28 years old today
                            </div>
                        </div>
                        <div class="birthday-item">
                            <div class="birthday-icon">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                            <div class="birthday-info">
                                <strong>Haarper Mitchell</strong> turns 21 years old tomorrow
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($birthdays as $birthday): ?>
                            <div class="birthday-item">
                                <div class="birthday-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="birthday-info">
                                    <?php echo $birthday['message']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Community Chats Section -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Community chats</div>
                    <div class="section-more">See All</div>
                </div>
                <div class="chat-list">
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <img src="https://source.unsplash.com/random/100x100?robot" alt="Robotics Chat">
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong>Robotics Team</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <img src="https://source.unsplash.com/random/100x100?code" alt="Coding Chat">
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong>Coding Club</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <img src="https://source.unsplash.com/random/100x100?art" alt="Digital Art Chat">
                        </div>
                        <div class="chat-info">
                            <strong>Digital Artists</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <img src="https://source.unsplash.com/random/100x100?music" alt="Music Chat">
                        </div>
                        <div class="chat-info">
                            <strong>Music Production</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Online Contacts -->
            <div class="sidebar-section">
                <div class="section-header">
                    <div class="section-title">Online contacts</div>
                </div>
                <div class="chat-list">
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <div style="background-color: #3F51B5; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">ML</div>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong>Mark Larsen</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <div style="background-color: #009688; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">ER</div>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong>Ethan Reynolds</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <div style="background-color: #FF5722; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">AT</div>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="chat-info">
                            <strong>Ava Thompson</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <div style="background-color: #607D8B; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">HM</div>
                        </div>
                        <div class="chat-info">
                            <strong>Haarper Mitchell</strong>
                        </div>
                    </div>
                    <div class="chat-item">
                        <div class="avatar chat-avatar">
                            <div style="background-color: #9C27B0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 500;">PM</div>
                        </div>
                        <div class="chat-info">
                            <strong>Pablo Morandi</strong>
                        </div>
                    </div>
                </div>
            </div>
            
  
        </aside>
    </div>
    <!-- Mobile Navigation (visible on mobile only) -->
    <nav class="mobile-nav">
        <a href="home.php" class="mobile-menu-item active">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="members.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-users"></i>
            </div>
            <span>Members</span>
        </a>
        <a href="app/Views/messages.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <span>Messages</span>
        </a>
        <a href="app/Views/projects.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <span>Projects</span>
        </a>
        <a href="app/Views/settings.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-cog"></i>
            </div>
            <span>Settings</span>
        </a>
    </nav>
    <script src="./public/assets/js/homedashboard.js"></script>
</body>
</html>
</html>