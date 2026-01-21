<!-- Additional dashboard components -->

<!-- Learning Progress Widget -->
<div class="learning-progress">
    <h2 class="section-title">Your Learning Path</h2>
    
    <div class="progress-tracks">
        <!-- Robotics Track -->
        <div class="track-card">
            <div class="track-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="track-details">
                <h3 class="track-name">Robotics</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 65%"></div>
                </div>
                <div class="progress-info">
                    <span class="progress-percentage">65% Complete</span>
                    <span class="progress-level">Intermediate</span>
                </div>
                <div class="next-lesson">
                    <strong>Next:</strong> Sensor Integration
                </div>
            </div>
        </div>
        
        <!-- Coding Track -->
        <div class="track-card">
            <div class="track-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="track-details">
                <h3 class="track-name">Web Development</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 40%"></div>
                </div>
                <div class="progress-info">
                    <span class="progress-percentage">40% Complete</span>
                    <span class="progress-level">Beginner</span>
                </div>
                <div class="next-lesson">
                    <strong>Next:</strong> JavaScript Basics
                </div>
            </div>
        </div>
        
        <!-- Digital Design Track -->
        <div class="track-card">
            <div class="track-icon">
                <i class="fas fa-palette"></i>
            </div>
            <div class="track-details">
                <h3 class="track-name">Digital Design</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 25%"></div>
                </div>
                <div class="progress-info">
                    <span class="progress-percentage">25% Complete</span>
                    <span class="progress-level">Beginner</span>
                </div>
                <div class="next-lesson">
                    <strong>Next:</strong> Color Theory
                </div>
            </div>
        </div>
    </div>
    
    <div class="view-all-courses">
        <a href="learn.php" class="btn-secondary">View All Courses</a>
    </div>
</div>

<!-- Badges and Achievements Widget -->
<div class="badges-achievements">
    <h2 class="section-title">Your Badges</h2>
    
    <div class="badges-grid">
        <div class="badge-item earned">
            <div class="badge-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="badge-name">Code Beginner</div>
        </div>
        
        <div class="badge-item earned">
            <div class="badge-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="badge-name">Robotics Explorer</div>
        </div>
        
        <div class="badge-item earned">
            <div class="badge-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="badge-name">Team Player</div>
        </div>
        
        <div class="badge-item locked">
            <div class="badge-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="badge-name">Advanced Coder</div>
        </div>
        
        <div class="badge-item locked">
            <div class="badge-icon">
                <i class="fas fa-pencil-ruler"></i>
            </div>
            <div class="badge-name">Designer</div>
        </div>
    </div>
    
    <div class="view-all-badges">
        <a href="badges.php" class="btn-secondary">View All Badges</a>
    </div>
</div>

<!-- Dynamic Member Posts Section -->
<div class="member-posts">
    <h2 class="section-title">Latest Member Projects</h2>
    
    <?php if (empty($memberPosts)): ?>
        <div class="empty-state">
            <p>No member projects found. Be the first to share your work!</p>
            <a href="create-post.php" class="btn-primary">Create a Project</a>
        </div>
    <?php else: ?>
        <?php foreach ($memberPosts as $post): ?>
            <div class="post-card">
                <div class="post-image">
                    <?php if (isset($post['image']) && file_exists('public/assets/uploads/images/' . $post['image'])): ?>
                        <img src="public/assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php else: ?>
                        <img src="https://cdnjs.cloudflare.com/ajax/placeholder/400/300" alt="Project Image">
                    <?php endif; ?>
                </div>
                <div class="post-content">
                    <div class="post-info">
                        <div class="post-author">
                            <div class="author-avatar" style="background-color: <?php echo getAvatarColor($post['user_id']); ?>">
                                <?php echo getInitials($post['name'] . ' ' . $post['surname']); ?>
                            </div>
                            <span><?php echo htmlspecialchars($post['name'] . ' ' . $post['surname']); ?></span>
                        </div>
                        <div class="post-date"><?php echo htmlspecialchars($post['formatted_date']); ?></div>
                    </div>
                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p class="post-description">
                        <?php echo htmlspecialchars($post['description']); ?>
                    </p>
                    <div class="post-actions">
                        <div class="post-tags">
                            <?php foreach ($post['tags'] as $tag): ?>
                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <a href="view-post.php?id=<?php echo $post['id']; ?>" class="read-more">Read More</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Dynamic Sidebar Content -->
<div class="sidebar-content">
    <!-- Calendar Widget -->
    <?php echo $calendarHTML; ?>
    
    <!-- Upcoming Events Widget -->
    <div class="upcoming-events">
        <h2 class="section-title">Upcoming Events</h2>
        
        <?php if (empty($upcomingEvents)): ?>
            <div class="empty-state">
                <p>No upcoming events scheduled at this time.</p>
            </div>
        <?php else: ?>
            <div class="event-list">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-card">
                        <div class="event-date-box">
                            <span class="event-month"><?php echo htmlspecialchars($event['month']); ?></span>
                            <span class="event-day-num"><?php echo htmlspecialchars($event['day']); ?></span>
                        </div>
                        <div class="event-details">
                            <h3 class="event-name"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <div class="event-time"><?php echo htmlspecialchars($event['time']); ?></div>
                            <div class="event-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Announcements Widget -->
    <div class="announcement-list">
        <h2 class="section-title">Announcements</h2>
        
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <p>No announcements at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item <?php echo 'priority-' . $announcement['priority']; ?>">
                    <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                    <div class="announcement-date"><?php echo htmlspecialchars($announcement['formatted_date']); ?></div>
                    <p class="announcement-text">
                        <?php echo htmlspecialchars($announcement['content']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- User Attendance Stats Widget (Optional) -->
    <?php if (isset($attendanceStats) && $attendanceStats['total_sessions'] > 0): ?>
    <div class="attendance-stats">
        <h2 class="section-title">Your Attendance</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendanceStats['total_sessions']; ?></div>
                <div class="stat-label">Sessions</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendanceStats['total_hours']; ?></div>
                <div class="stat-label">Hours</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendanceStats['streak']; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>
        <div class="last-attendance">
            <strong>Last Visit:</strong> 
            <?php echo formatDate($attendanceStats['last_attendance'], 'F j, Y, g:i a'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

