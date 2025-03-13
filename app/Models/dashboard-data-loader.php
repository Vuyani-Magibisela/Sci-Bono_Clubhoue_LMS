<?php
/**
 * Clubhouse Social Data Loader
 * 
 * This file is responsible for loading data into the social-style dashboard
 * Place this in app/Models/ directory and include it at the top of your home.php
 */

// Include database connection
require_once 'server.php';

/**
 * Fetch clubhouse programs for sidebar display
 * 
 * @return array Array of clubhouse programs
 */
function getClubhousePrograms() {
    global $conn;
    
    $programs = [];
    
    $sql = "SELECT id, title FROM clubhouse_programs ORDER BY id DESC LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    } else {
        // Sample data if no programs in database
        $samplePrograms = [
            ['id' => 1, 'title' => 'FTC Robotics'],
            ['id' => 2, 'title' => 'Coding Club'],
            ['id' => 3, 'title' => 'Digital Art'],
            ['id' => 4, 'title' => 'FLL'],
            ['id' => 5, 'title' => 'AR Workshop']
        ];
        $programs = $samplePrograms;
    }
    
    return $programs;
}

/**
 * Fetch member activity for the feed
 * 
 * @param int $limit Number of activities to return
 * @return array Array of member activities/posts
 */
function getMemberActivity($limit = 10) {
    global $conn;
    
    // This is placeholder data for demonstration purposes
    // In a real implementation, you would have tables for member posts, projects, etc.
    
    $activities = [
        [
            'id' => 1,
            'user_id' => 8,
            'name' => 'Jabu',
            'surname' => 'Khumalo',
            'user_type' => 'mentor',
            'type' => 'project',
            'title' => 'FTC Robotics Progress',
            'description' => 'Our team has made significant progress on our robot design for the FTC competition! Check out our prototype for the autonomous navigation system. We\'ve implemented a new sensor array that gives us much better accuracy.',
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
            'description' => 'Great news everyone! We\'ve just received new 3D printers and VR headsets in the clubhouse. Training sessions will be scheduled next week. Sign up at the front desk if you want to learn how to use them. Limited spots available!',
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
            'description' => 'I just finished my first JavaScript web application! It\'s a simple task manager that allows you to add, edit, and delete tasks. I learned so much about DOM manipulation and event handling. Thanks to everyone who helped me with debugging!',
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
            'description' => 'Had an amazing time at the Arduino workshop today! We built an obstacle-avoiding robot using ultrasonic sensors. Looking forward to the next session where we\'ll add more features to our robots.',
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
            'description' => 'I\'ll be hosting an AI workshop next Friday at 3 PM! We\'ll be covering the basics of machine learning and how to build a simple image recognition model. No prior experience needed. Bring your laptops!',
            'image' => 'ai_workshop.jpg',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'tags' => ['AI', 'Machine Learning', 'Workshop']
        ]
    ];
    
    // Format dates for display
    foreach ($activities as &$activity) {
        $activity['formatted_date'] = formatRelativeTime($activity['created_at']);
    }
    
    return array_slice($activities, 0, $limit);
}

/**
 * Fetch upcoming events
 * 
 * @param int $limit Number of events to return
 * @return array Array of upcoming events
 */
function getUpcomingEvents($limit = 4) {
    global $conn;
    
    $currentDate = date('Y-m-d');
    $events = [];
    
    // In a real implementation, you would have an events table
    // This is the query you would use with an actual 'events' table
    /*
    $sql = "SELECT * FROM events 
           WHERE event_date >= ? 
           ORDER BY event_date ASC 
           LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $currentDate, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    */
    
    // Sample data for demonstration
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
        ],
        [
            'id' => 5,
            'title' => '3D Printing Demonstration',
            'date' => date('Y-m-d', strtotime('+16 days')),
            'time' => '14:00 - 16:00',
            'location' => 'Maker Space',
            'description' => 'See how 3D printers work and design your first 3D model'
        ]
    ];
    
    // Format dates for display
    foreach ($sampleEvents as &$event) {
        $event['formatted_date'] = date('F j, Y', strtotime($event['date']));
    }
    
    return array_slice($sampleEvents, 0, $limit);
}

/**
 * Get members with upcoming birthdays
 * 
 * @param int $limit Number of birthdays to return
 * @return array Array of upcoming birthdays
 */
function getUpcomingBirthdays($limit = 5) {
    global $conn;
    
    $birthdays = [];
    
    // In a real implementation, you would query the users table for upcoming birthdays
    // This is placeholder data
    
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
}

/**
 * Get user learning progress
 * 
 * @param int $userId User ID
 * @return array User learning progress data
 */
function getUserLearningProgress($userId) {
    global $conn;
    
    // This would come from a learning management system or similar
    // Placeholder data for demonstration
    
    return [
        [
            'course' => 'Robotics',
            'progress' => 65,
            'level' => 'Intermediate'
        ],
        [
            'course' => 'Web Development',
            'progress' => 40,
            'level' => 'Beginner'
        ],
        [
            'course' => 'Digital Design',
            'progress' => 25,
            'level' => 'Beginner'
        ]
    ];
}

/**
 * Get user badges
 * 
 * @param int $userId User ID
 * @return array User badges
 */
function getUserBadges($userId) {
    global $conn;
    
    // This would come from a badges/achievements system
    // Placeholder data for demonstration
    
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
}

/**
 * Format date as relative time
 * 
 * @param string $datetime Date/time string
 * @return string Formatted relative time string
 */
function formatRelativeTime($datetime) {
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
 * Get avatar color based on user ID (for consistent colors)
 * 
 * @param int $userId User ID
 * @return string Hex color code
 */
function getAvatarColor($userId) {
    $colors = [
        '#6C63FF', // Primary purple
        '#F29A2E', // Orange
        '#3F51B5', // Indigo
        '#009688', // Teal
        '#FF5722', // Deep Orange
        '#607D8B', // Blue Grey
        '#9C27B0', // Purple
        '#00BCD4'  // Cyan
    ];
    
    // Use user ID to get consistent color for each user
    return $colors[$userId % count($colors)];
}

/**
 * Get initials from name (for avatars)
 * 
 * @param string $name Full name
 * @return string Initials
 */
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    
    return substr($initials, 0, 2); // Limit to 2 characters
}

/**
 * Get community chat groups
 * 
 * @return array Chat groups
 */
function getCommunityChats() {
    global $conn;
    
    // In a real implementation, this would be from a chat_groups table
    // Placeholder data for demonstration
    
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
}

/**
 * Get online contacts (other members currently online)
 * 
 * @return array Online contacts
 */
function getOnlineContacts() {
    global $conn;
    
    // In a real implementation, this would check session activity or online status
    // Placeholder data for demonstration
    
    return [
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
        ],
        [
            'id' => 11,
            'name' => 'Haarper Mitchell',
            'initials' => 'HM',
            'color' => '#607D8B',
            'online' => false
        ],
        [
            'id' => 23,
            'name' => 'Pablo Morandi',
            'initials' => 'PM',
            'color' => '#9C27B0',
            'online' => false
        ]
    ];
}

// Load required data for the dashboard
$currentUserId = $_SESSION['id'] ?? 1; // Default to 1 if not set

// Load clubhouse programs for sidebar
$clubhousePrograms = getClubhousePrograms();

// Load member activity for the feed
$memberPosts = getMemberActivity(5);

// Load upcoming events for the right sidebar
$upcomingEvents = getUpcomingEvents(4);

// Load upcoming birthdays
$userBirthdays = getUpcomingBirthdays(3);

// Load user learning progress
$userLearningProgress = getUserLearningProgress($currentUserId);

// Load user badges
$userBadges = getUserBadges($currentUserId);

// Load community chats
$communityChats = getCommunityChats();

// Load online contacts
$onlineContacts = getOnlineContacts();