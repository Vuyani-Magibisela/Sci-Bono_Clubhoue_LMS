<?php
/**
 * Dashboard Service
 * Phase 3 Week 6-7: User Dashboard & Remaining Features
 *
 * Business logic for member dashboard data aggregation and activity feeds
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/CourseModel.php';
require_once __DIR__ . '/../Models/EnrollmentModel.php';
require_once __DIR__ . '/../Models/AttendanceModel.php';
require_once __DIR__ . '/../Models/HolidayProgramModel.php';
require_once __DIR__ . '/CacheManager.php'; // Phase 3 Week 9 - Caching

class DashboardService extends BaseService {
    private $userModel;
    private $courseModel;
    private $enrollmentModel;
    private $attendanceModel;
    private $programModel;
    private $cache; // Phase 3 Week 9 - Cache manager

    /**
     * Constructor
     */
    public function __construct($conn = null) {
        parent::__construct($conn);

        $this->userModel = new UserModel($this->conn);
        $this->courseModel = new CourseModel($this->conn);
        $this->enrollmentModel = new EnrollmentModel($this->conn);
        $this->attendanceModel = new AttendanceModel($this->conn);
        $this->programModel = new HolidayProgramModel($this->conn);
        $this->cache = new CacheManager(); // Phase 3 Week 9 - Initialize cache
    }

    /**
     * Get comprehensive dashboard data for a user
     *
     * @param int $userId User ID
     * @return array Dashboard data including stats, activity feed, programs, events
     */
    public function getUserDashboardData($userId) {
        try {
            $this->logAction('get_dashboard_data', ['user_id' => $userId]);

            // PHASE 3 WEEK 9 - CACHING: Cache dashboard data for 5 minutes
            $cacheKey = "dashboard_data_{$userId}";

            return $this->cache->remember($cacheKey, 300, function() use ($userId) {
                return [
                    'user_stats' => $this->getUserStats($userId),
                    'activity_feed' => $this->getActivityFeed($userId, 10),
                    'learning_progress' => $this->getUserLearningProgress($userId),
                    'upcoming_events' => $this->getUpcomingEvents(4),
                    'clubhouse_programs' => $this->getClubhousePrograms(5),
                    'birthdays' => $this->getBirthdays(3),
                    'continue_learning' => $this->getContinueLearning($userId, 3),
                    'badges' => $this->getUserBadges($userId),
                    'community_chats' => $this->getCommunityChats(),
                    'online_contacts' => $this->getOnlineContacts(5)
                ];
            });

        } catch (Exception $e) {
            $this->handleError("Failed to get dashboard data: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Get user statistics
     *
     * @param int $userId User ID
     * @return array User statistics
     */
    public function getUserStats($userId) {
        try {
            // Get enrollment count
            $enrollmentCount = $this->enrollmentModel->getUserEnrollmentCount($userId);

            // Get attendance streak
            $attendanceStreak = $this->attendanceModel->getUserAttendanceStreak($userId);

            // Get badges count
            $badgesCount = $this->countEarnedBadges($userId);

            // Get projects count (from sample data for now)
            $projectsCount = 0;

            return [
                'enrolled_courses' => $enrollmentCount,
                'attendance_streak' => $attendanceStreak,
                'badges_earned' => $badgesCount,
                'projects_completed' => $projectsCount
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get user stats: " . $e->getMessage(), ['user_id' => $userId]);

            return [
                'enrolled_courses' => 0,
                'attendance_streak' => 0,
                'badges_earned' => 0,
                'projects_completed' => 0
            ];
        }
    }

    /**
     * Get activity feed for dashboard
     *
     * @param int $userId Current user ID
     * @param int $limit Number of activities to return
     * @return array Activity feed items
     */
    public function getActivityFeed($userId, $limit = 10) {
        try {
            // In a full implementation, this would query an activities table
            // For now, we'll return sample data similar to dashboard-data-loader.php

            $activities = [
                [
                    'id' => 1,
                    'user_id' => 8,
                    'name' => 'Jabu',
                    'surname' => 'Khumalo',
                    'user_type' => 'mentor',
                    'type' => 'project',
                    'title' => 'FTC Robotics Progress',
                    'description' => 'Our team has made significant progress on our robot design for the FTC competition! Check out our prototype for the autonomous navigation system.',
                    'image' => 'robot_prototype.jpg',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'tags' => ['Robotics', 'FTC', 'Engineering']
                ],
                [
                    'id' => 2,
                    'user_id' => 9,
                    'name' => 'Lebo',
                    'surname' => 'Skhosana',
                    'user_type' => 'admin',
                    'type' => 'announcement',
                    'title' => 'New Equipment Arrival',
                    'description' => 'Great news everyone! We\'ve just received new 3D printers and VR headsets in the clubhouse. Training sessions will be scheduled next week.',
                    'image' => '3d_printers.jpg',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'tags' => ['Announcement', 'Equipment', '3D Printing']
                ],
                [
                    'id' => 3,
                    'user_id' => 2,
                    'name' => 'Itumeleng',
                    'surname' => 'Kgakane',
                    'user_type' => 'member',
                    'type' => 'project',
                    'title' => 'My First Web App',
                    'description' => 'I just finished my first JavaScript web application! It\'s a simple task manager that allows you to add, edit, and delete tasks.',
                    'image' => 'web_app.jpg',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'tags' => ['JavaScript', 'Web Development', 'Coding']
                ],
                [
                    'id' => 4,
                    'user_id' => 7,
                    'name' => 'Sam',
                    'surname' => 'Kabanga',
                    'user_type' => 'member',
                    'type' => 'photo',
                    'title' => 'Arduino Workshop',
                    'description' => 'Had an amazing time at the Arduino workshop today! We built an obstacle-avoiding robot using ultrasonic sensors.',
                    'image' => 'arduino_workshop.jpg',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                    'tags' => ['Arduino', 'Electronics', 'Robotics']
                ],
                [
                    'id' => 5,
                    'user_id' => 5,
                    'name' => 'Themba',
                    'surname' => 'Kgakane',
                    'user_type' => 'mentor',
                    'type' => 'event',
                    'title' => 'Upcoming AI Workshop',
                    'description' => 'I\'ll be hosting an AI workshop next Friday at 3 PM! We\'ll be covering the basics of machine learning and how to build a simple image recognition model.',
                    'image' => 'ai_workshop.jpg',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'tags' => ['AI', 'Machine Learning', 'Workshop']
                ]
            ];

            // Format dates for display
            foreach ($activities as &$activity) {
                $activity['formatted_date'] = $this->formatRelativeTime($activity['created_at']);
                $activity['avatar_color'] = $this->getAvatarColor($activity['user_id']);
                $activity['initials'] = $this->getInitials($activity['name'] . ' ' . $activity['surname']);
            }

            return array_slice($activities, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get activity feed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user learning progress
     *
     * @param int $userId User ID
     * @return array Learning progress data
     */
    public function getUserLearningProgress($userId) {
        try {
            // PHASE 3 WEEK 9 - PERFORMANCE: Single JOIN query eliminates N+1 problem
            // OLD: 11 queries for 10 enrollments (1 to get enrollments + 10 for course details)
            // NEW: 1 query (single JOIN gets everything)
            $enrollments = $this->enrollmentModel->getUserEnrollmentsWithCourses($userId);

            $progress = [];
            foreach ($enrollments as $enrollment) {
                $progress[] = [
                    'course' => $enrollment['title'],
                    'progress' => $enrollment['progress_percentage'] ?? 0,
                    'level' => $enrollment['difficulty_level'] ?? 'Beginner',
                    'thumbnail' => $enrollment['thumbnail'] ?? null
                ];
            }

            // Limit to top 3 in-progress courses
            return array_slice($progress, 0, 3);

        } catch (Exception $e) {
            $this->logger->error("Failed to get learning progress: " . $e->getMessage(), ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get upcoming events
     *
     * @param int $limit Number of events to return
     * @return array Upcoming events
     */
    public function getUpcomingEvents($limit = 4) {
        try {
            // Sample data - in real implementation would query events table
            $sampleEvents = [
                [
                    'id' => 1,
                    'title' => 'FTC Robotics Workshop',
                    'date' => date('Y-m-d', strtotime('+3 days')),
                    'time' => '15:00 - 17:00',
                    'location' => 'Sci-Bono Clubhouse',
                    'description' => 'Hands-on workshop preparing for the FTC competition'
                ],
                [
                    'id' => 2,
                    'title' => 'Web Development Basics',
                    'date' => date('Y-m-d', strtotime('+5 days')),
                    'time' => '14:30 - 16:30',
                    'location' => 'Computer Lab',
                    'description' => 'Introduction to HTML, CSS, and JavaScript'
                ],
                [
                    'id' => 3,
                    'title' => 'Digital Art Challenge',
                    'date' => date('Y-m-d', strtotime('+1 week')),
                    'time' => '16:00 - 18:00',
                    'location' => 'Art Studio',
                    'description' => 'Compete to create the best digital artwork in 2 hours'
                ],
                [
                    'id' => 4,
                    'title' => 'AI For Youth Workshop',
                    'date' => date('Y-m-d', strtotime('+2 weeks')),
                    'time' => '15:30 - 17:30',
                    'location' => 'Sci-Bono Clubhouse',
                    'description' => 'Learn about artificial intelligence and machine learning'
                ]
            ];

            // Format dates
            foreach ($sampleEvents as &$event) {
                $event['formatted_date'] = date('F j, Y', strtotime($event['date']));
            }

            return array_slice($sampleEvents, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get upcoming events: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get clubhouse programs for sidebar
     *
     * @param int $limit Number of programs to return
     * @return array Clubhouse programs
     */
    private function getClubhousePrograms($limit = 5) {
        try {
            $sql = "SELECT id, title FROM clubhouse_programs ORDER BY id DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $programs = [];
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }

            // Return sample data if no programs in database
            if (empty($programs)) {
                $programs = [
                    ['id' => 1, 'title' => 'FTC Robotics'],
                    ['id' => 2, 'title' => 'Coding Club'],
                    ['id' => 3, 'title' => 'Digital Art'],
                    ['id' => 4, 'title' => 'FLL'],
                    ['id' => 5, 'title' => 'AR Workshop']
                ];
            }

            return $programs;

        } catch (Exception $e) {
            $this->logger->error("Failed to get clubhouse programs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get upcoming birthdays
     *
     * @param int $limit Number of birthdays to return
     * @return array Upcoming birthdays
     */
    public function getBirthdays($limit = 3) {
        try {
            // Sample data - in real implementation would query users table for upcoming birthdays
            $birthdays = [
                [
                    'user_id' => 10,
                    'name' => 'Bob Hammond',
                    'age' => 28,
                    'date' => date('Y-m-d'),
                    'message' => '<strong>Bob Hammond</strong> turns 28 years old today'
                ],
                [
                    'user_id' => 11,
                    'name' => 'Haarper Mitchell',
                    'age' => 21,
                    'date' => date('Y-m-d', strtotime('+1 day')),
                    'message' => '<strong>Haarper Mitchell</strong> turns 21 years old tomorrow'
                ],
                [
                    'user_id' => 12,
                    'name' => 'Mason Cooper',
                    'age' => 30,
                    'date' => date('Y-m-d', strtotime('+3 days')),
                    'message' => '<strong>Mason Cooper</strong> turns 30 years old in 3 days'
                ]
            ];

            return array_slice($birthdays, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get birthdays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get continue learning courses (in-progress courses)
     *
     * @param int $userId User ID
     * @param int $limit Number of courses to return
     * @return array In-progress courses
     */
    private function getContinueLearning($userId, $limit = 3) {
        try {
            $enrollments = $this->enrollmentModel->getUserEnrollments($userId);

            $continueCourses = [];
            foreach ($enrollments as $enrollment) {
                $progress = $enrollment['progress_percentage'] ?? 0;

                // Only include courses that are started but not completed
                if ($progress > 0 && $progress < 100) {
                    $courseId = $enrollment['course_id'];
                    $course = $this->courseModel->getCourseById($courseId);

                    if ($course) {
                        $continueCourses[] = [
                            'id' => $course['id'],
                            'title' => $course['title'],
                            'type' => $course['type'] ?? 'course',
                            'difficulty' => $course['difficulty_level'] ?? 'Beginner',
                            'progress' => $progress,
                            'image' => $course['image_path'] ?? null
                        ];
                    }
                }
            }

            // Sort by progress (show courses closest to completion first)
            usort($continueCourses, function($a, $b) {
                return $b['progress'] - $a['progress'];
            });

            return array_slice($continueCourses, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get continue learning: " . $e->getMessage(), ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Get user badges
     *
     * @param int $userId User ID
     * @return array User badges
     */
    private function getUserBadges($userId) {
        try {
            // Sample data - in real implementation would query badges/achievements table
            return [
                [
                    'id' => 1,
                    'name' => 'Code Beginner',
                    'icon' => 'fas fa-code',
                    'earned' => true
                ],
                [
                    'id' => 2,
                    'name' => 'Robotics Explorer',
                    'icon' => 'fas fa-robot',
                    'earned' => true
                ],
                [
                    'id' => 3,
                    'name' => 'Team Player',
                    'icon' => 'fas fa-users',
                    'earned' => true
                ],
                [
                    'id' => 4,
                    'name' => 'Advanced Coder',
                    'icon' => 'fas fa-graduation-cap',
                    'earned' => false
                ],
                [
                    'id' => 5,
                    'name' => 'Designer',
                    'icon' => 'fas fa-pencil-ruler',
                    'earned' => false
                ]
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get badges: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count earned badges
     *
     * @param int $userId User ID
     * @return int Number of earned badges
     */
    private function countEarnedBadges($userId) {
        $badges = $this->getUserBadges($userId);
        return count(array_filter($badges, function($badge) {
            return $badge['earned'];
        }));
    }

    /**
     * Get community chat groups
     *
     * @return array Chat groups
     */
    private function getCommunityChats() {
        try {
            // Sample data - in real implementation would query chat_groups table
            return [
                [
                    'id' => 1,
                    'name' => 'Robotics Team',
                    'image' => 'https://source.unsplash.com/random/100x100?robot',
                    'online' => true
                ],
                [
                    'id' => 2,
                    'name' => 'Coding Club',
                    'image' => 'https://source.unsplash.com/random/100x100?code',
                    'online' => true
                ],
                [
                    'id' => 3,
                    'name' => 'Digital Artists',
                    'image' => 'https://source.unsplash.com/random/100x100?art',
                    'online' => false
                ],
                [
                    'id' => 4,
                    'name' => 'Music Production',
                    'image' => 'https://source.unsplash.com/random/100x100?music',
                    'online' => false
                ]
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get community chats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get online contacts
     *
     * @param int $limit Number of contacts to return
     * @return array Online contacts
     */
    private function getOnlineContacts($limit = 5) {
        try {
            // Sample data - in real implementation would check session activity
            $contacts = [
                [
                    'id' => 20,
                    'name' => 'Mark Larsen',
                    'initials' => 'ML',
                    'color' => '#3F51B5',
                    'online' => true
                ],
                [
                    'id' => 21,
                    'name' => 'Ethan Reynolds',
                    'initials' => 'ER',
                    'color' => '#009688',
                    'online' => true
                ],
                [
                    'id' => 22,
                    'name' => 'Ava Thompson',
                    'initials' => 'AT',
                    'color' => '#FF5722',
                    'online' => true
                ]
            ];

            return array_slice($contacts, 0, $limit);

        } catch (Exception $e) {
            $this->logger->error("Failed to get online contacts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format date as relative time
     *
     * @param string $datetime Date/time string
     * @return string Formatted relative time string
     */
    private function formatRelativeTime($datetime) {
        $now = new DateTime();
        $timestamp = new DateTime($datetime);
        $diff = $now->diff($timestamp);

        if ($diff->y > 0) {
            return $diff->y == 1 ? "1 year ago" : $diff->y . " years ago";
        }

        if ($diff->m > 0) {
            return $diff->m == 1 ? "1 month ago" : $diff->m . " months ago";
        }

        if ($diff->d > 0) {
            if ($diff->d == 1) {
                return "Yesterday";
            }

            if ($diff->d < 7) {
                return $diff->d . " days ago";
            }

            return floor($diff->d / 7) == 1 ? "1 week ago" : floor($diff->d / 7) . " weeks ago";
        }

        if ($diff->h > 0) {
            return $diff->h == 1 ? "1 hour ago" : $diff->h . " hours ago";
        }

        if ($diff->i > 0) {
            return $diff->i == 1 ? "1 minute ago" : $diff->i . " minutes ago";
        }

        return "Just now";
    }

    /**
     * Get avatar color based on user ID
     *
     * @param int $userId User ID
     * @return string Hex color code
     */
    private function getAvatarColor($userId) {
        $colors = [
            '#6C63FF', '#F29A2E', '#3F51B5', '#009688',
            '#FF5722', '#607D8B', '#9C27B0', '#00BCD4'
        ];

        return $colors[$userId % count($colors)];
    }

    /**
     * Get initials from name
     *
     * @param string $name Full name
     * @return string Initials
     */
    private function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }
}
