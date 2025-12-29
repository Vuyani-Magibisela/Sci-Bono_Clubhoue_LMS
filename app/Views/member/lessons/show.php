<?php
/**
 * Lesson Viewer - View and complete lessons
 * Phase 3: Week 6-7 Implementation
 *
 * Data from Member\LessonController:
 * - $lesson: Lesson details
 * - $course: Parent course details
 * - $navigation: Previous/next lesson info
 * - $isCompleted: Whether lesson is completed
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
        }
        .lesson-container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
        }
        .lesson-header {
            background: #1c1e21;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .lesson-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .lesson-progress-bar {
            flex: 1;
            max-width: 300px;
            height: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
            margin: 0 2rem;
        }
        .lesson-progress-fill {
            height: 100%;
            background: var(--primary, #2196F3);
            border-radius: 3px;
            transition: width 0.3s;
        }
        .lesson-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            min-height: calc(100vh - 80px);
        }
        .lesson-content {
            padding: 2rem;
        }
        .video-player {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .content-tabs {
            display: flex;
            border-bottom: 2px solid #e4e6eb;
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            font-size: 1rem;
            color: #65676b;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: var(--primary, #2196F3);
            border-bottom-color: var(--primary, #2196F3);
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .lesson-sidebar {
            background: #f8f9fa;
            border-left: 1px solid #e4e6eb;
            padding: 1.5rem;
            overflow-y: auto;
        }
        .sidebar-section {
            margin-bottom: 2rem;
        }
        .sidebar-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1c1e21;
        }
        .lesson-list {
            list-style: none;
        }
        .lesson-list-item {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .lesson-list-item:hover {
            background: #e4e6eb;
        }
        .lesson-list-item.current {
            background: var(--primary, #2196F3);
            color: white;
        }
        .lesson-list-item.completed {
            color: #28a745;
        }
        .lesson-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lesson-navigation {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e4e6eb;
            background: #f8f9fa;
        }
        .nav-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .nav-btn-primary {
            background: var(--primary, #2196F3);
            color: white;
        }
        .nav-btn-secondary {
            background: #e4e6eb;
            color: #1c1e21;
        }
        .nav-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .complete-btn {
            background: #28a745;
            color: white;
        }
        .notes-textarea {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            border: 1px solid #e4e6eb;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
        }
    </style>
</head>
<body>
    <div class="lesson-container">
        <!-- Header -->
        <div class="lesson-header">
            <a href="/courses/<?php echo $course['id']; ?>" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"></i>
                <?php echo htmlspecialchars($course['title']); ?>
            </a>

            <div class="lesson-progress-bar">
                <div class="lesson-progress-fill" style="width: <?php echo $course['progress'] ?? 0; ?>%"></div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <span><?php echo $course['progress'] ?? 0; ?>% Complete</span>
                <a href="/my-courses" style="color: white;">
                    <i class="fas fa-th-large"></i>
                </a>
            </div>
        </div>

        <div class="lesson-layout">
            <!-- Main Content Area -->
            <div class="lesson-content">
                <h1 style="font-size: 1.75rem; font-weight: 700; color: #1c1e21; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($lesson['title']); ?>
                </h1>
                <p style="color: #65676b; margin-bottom: 2rem;">
                    Lesson <?php echo $lesson['order'] ?? 1; ?> of <?php echo $course['total_lessons'] ?? 0; ?>
                </p>

                <!-- Video Player (if video content) -->
                <?php if (!empty($lesson['video_url'])): ?>
                <div class="video-player">
                    <video controls style="width: 100%; height: 100%;">
                        <source src="<?php echo htmlspecialchars($lesson['video_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <?php endif; ?>

                <!-- Content Tabs -->
                <div class="content-tabs">
                    <button class="tab-btn active" onclick="switchTab('overview')">Overview</button>
                    <button class="tab-btn" onclick="switchTab('notes')">My Notes</button>
                    <?php if (!empty($lesson['resources'])): ?>
                    <button class="tab-btn" onclick="switchTab('resources')">Resources</button>
                    <?php endif; ?>
                </div>

                <!-- Overview Tab -->
                <div id="overview-tab" class="tab-content active">
                    <div style="line-height: 1.8; color: #1c1e21;">
                        <?php echo nl2br(htmlspecialchars($lesson['content'] ?? 'No content available')); ?>
                    </div>

                    <?php if (!empty($lesson['learning_objectives'])): ?>
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                        <h3 style="margin-bottom: 1rem; color: #1c1e21;">Learning Objectives</h3>
                        <ul style="list-style-position: inside; color: #65676b;">
                            <?php foreach (explode("\n", $lesson['learning_objectives']) as $objective): ?>
                                <?php if (trim($objective)): ?>
                                <li style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars(trim($objective)); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Notes Tab -->
                <div id="notes-tab" class="tab-content">
                    <form id="notes-form">
                        <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                        <textarea name="notes" class="notes-textarea" placeholder="Take notes as you learn..."><?php echo htmlspecialchars($lesson['user_notes'] ?? ''); ?></textarea>
                        <button type="submit" class="nav-btn nav-btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-save"></i> Save Notes
                        </button>
                    </form>
                </div>

                <!-- Resources Tab -->
                <?php if (!empty($lesson['resources'])): ?>
                <div id="resources-tab" class="tab-content">
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($lesson['resources'] as $resource): ?>
                        <a href="<?php echo htmlspecialchars($resource['url']); ?>" target="_blank"
                           style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; text-decoration: none; color: inherit;">
                            <div style="width: 40px; height: 40px; background: var(--primary, #2196F3); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1c1e21;">
                                    <?php echo htmlspecialchars($resource['title']); ?>
                                </div>
                                <div style="color: #65676b; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($resource['type'] ?? 'Download'); ?>
                                </div>
                            </div>
                            <i class="fas fa-external-link-alt" style="margin-left: auto; color: #65676b;"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lesson-sidebar">
                <!-- Course Progress -->
                <div class="sidebar-section">
                    <div class="sidebar-title">Course Progress</div>
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary, #2196F3);">
                            <?php echo $course['progress'] ?? 0; ?>%
                        </div>
                        <div style="color: #65676b; font-size: 0.875rem;">
                            <?php echo $course['completed_lessons'] ?? 0; ?> of <?php echo $course['total_lessons'] ?? 0; ?> lessons
                        </div>
                    </div>
                </div>

                <!-- Curriculum -->
                <div class="sidebar-section">
                    <div class="sidebar-title">Course Curriculum</div>
                    <ul class="lesson-list">
                        <?php if (!empty($curriculum)): ?>
                            <?php foreach ($curriculum as $curriculumLesson): ?>
                                <li class="lesson-list-item <?php echo $curriculumLesson['id'] == $lesson['id'] ? 'current' : ''; ?> <?php echo !empty($curriculumLesson['completed']) ? 'completed' : ''; ?>"
                                    onclick="window.location.href='/lessons/<?php echo $curriculumLesson['id']; ?>'">
                                    <div class="lesson-icon">
                                        <?php if (!empty($curriculumLesson['completed'])): ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php elseif ($curriculumLesson['id'] == $lesson['id']): ?>
                                            <i class="fas fa-play-circle"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1; font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($curriculumLesson['title']); ?>
                                    </div>
                                    <?php if (!empty($curriculumLesson['duration'])): ?>
                                    <div style="font-size: 0.75rem; color: #65676b;">
                                        <?php echo $curriculumLesson['duration']; ?>m
                                    </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Navigation Footer -->
        <div class="lesson-navigation">
            <div style="display: flex; gap: 1rem;">
                <?php if (!empty($navigation['previous'])): ?>
                    <a href="/lessons/<?php echo $navigation['previous']['id']; ?>" class="nav-btn nav-btn-secondary">
                        <i class="fas fa-chevron-left"></i> Previous Lesson
                    </a>
                <?php else: ?>
                    <a href="/courses/<?php echo $course['id']; ?>" class="nav-btn nav-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Course
                    </a>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 1rem;">
                <?php if (!$isCompleted): ?>
                    <button type="button" id="complete-btn" class="nav-btn complete-btn">
                        <i class="fas fa-check"></i> Mark as Complete
                    </button>
                <?php else: ?>
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem; color: #28a745; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Completed
                    </span>
                <?php endif; ?>

                <?php if (!empty($navigation['next'])): ?>
                    <a href="/lessons/<?php echo $navigation['next']['id']; ?>" class="nav-btn nav-btn-primary">
                        Next Lesson <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <a href="/my-courses" class="nav-btn nav-btn-primary">
                        <i class="fas fa-graduation-cap"></i> Finish Course
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Complete lesson
        document.getElementById('complete-btn')?.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch('/lessons/<?php echo $lesson['id']; ?>/complete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
                    this.classList.remove('complete-btn');
                    this.style.background = '#28a745';
                    this.disabled = true;

                    // Update progress
                    if (data.data && data.data.next_lesson) {
                        setTimeout(() => {
                            if (confirm('Lesson completed! Continue to next lesson?')) {
                                window.location.href = '/lessons/' + data.data.next_lesson.id;
                            }
                        }, 500);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });

        // Save notes
        document.getElementById('notes-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch('/api/lessons/<?php echo $lesson['id']; ?>/notes', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notes saved successfully!');
                } else {
                    alert('Error saving notes: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving notes');
            });
        });
    </script>
</body>
</html>
