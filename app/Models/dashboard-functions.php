<?php
/**
 * Dashboard Functions for Clubhouse LMS
 * 
 * This file contains functions to fetch dynamic content for the Clubhouse LMS Dashboard
 * including member posts, announcements, events, and attendance data.
 */

// Include database connection
require_once 'server.php';

/**
 * Fetch latest member posts/projects
 * 
 * @param int $limit Number of posts to return
 * @return array Array of member posts
 */
function getMemberPosts($limit = 5) {
    global $conn;
    
    $posts = [];
    
    // In a real implementation, you would have a posts/projects table
    // For now, we'll create sample data
    
    // This is the query you would use with an actual 'member_posts' table
    /*
    $sql = "SELECT p.*, u.name, u.surname, u.user_type 
           FROM member_posts p 
           JOIN users u ON p.user_id = u.id 
           ORDER BY p.created_at DESC 
           LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    */
    
    // Sample data for demonstration
    $samplePosts = [
        [
            'id' => 1,
            'title' => 'FTC Robotics Competition Preparation',
            'description' => 'Our team has been working on the robot design for the upcoming FTC competition. We\'ve made significant progress on the mechanical design and autonomous programming. Check out our progress and key learning points!',
            'image' => 'robotics_project.jpg',
            'created_at' => '2025-03-08',
            'user_id' => 8,
            'name' => 'Jabu',
            'surname' => 'Khumalo',
            'user_type' => 'mentor',
            'tags' => ['Robotics', 'FTC']
        ],
        [
            'id' => 2,
            'title' => 'Digital Art Creation: Exploring New Techniques',
            'description' => 'In our latest digital art workshop, we experimented with different brushes and effects to create stunning digital landscapes. This tutorial shares my process and tips for creating realistic digital paintings.',
            'image' => 'digital_art.jpg',
            'created_at' => '2025-03-05',
            'user_id' => 9,
            'name' => 'Lebo',
            'surname' => 'Skhosana',
            'user_type' => 'admin',
            'tags' => ['Digital Art', 'Tutorial']
        ],
        [
            'id' => 3,
            'title' => 'Building My First Web App with JavaScript',
            'description' => 'I\'ve been learning JavaScript for the past month and recently completed my first interactive web application. In this post, I\'ll share what I learned about DOM manipulation and event handling.',
            'image' => 'javascript_project.jpg',
            'created_at' => '2025-03-02',
            'user_id' => 2,
            'name' => 'Itumeleng',
            'surname' => 'Kgakane',
            'user_type' => 'member',
            'tags' => ['Coding', 'JavaScript']
        ],
        [
            'id' => 4,
            'title' => 'Our Adventure with Arduino Robotics',
            'description' => 'Last week, our team built an obstacle-avoiding robot using Arduino. Here\'s our step-by-step process, code snippets, and what we learned about sensors and motors.',
            'image' => 'arduino_robot.jpg',
            'created_at' => '2025-02-28',
            'user_id' => 7,
            'name' => 'Sam',
            'surname' => 'Kabanga',
            'user_type' => 'member',
            'tags' => ['Arduino', 'Robotics']
        ],
        [
            'id' => 5,
            'title' => '3D Modeling Workshop Results',
            'description' => 'Check out the amazing 3D models our members created during last month\'s workshop! We learned how to use Blender to create characters, environments, and props.',
            'image' => '3d_models.jpg',
            'created_at' => '2025-02-25',
            'user_id' => 5,
            'name' => 'Themba',
            'surname' => 'Kgakane',
            'user_type' => 'mentor',
            'tags' => ['3D Modeling', 'Blender']
        ]
    ];
    
    // Return sample data (in production, you'd return database results)
    return array_slice($samplePosts, 0, $limit);
}

/**
 * Fetch upcoming events
 * 
 * @param int $limit Number of events to return
 * @return array Array of upcoming events
 */
function getUpcomingEvents($limit = 4) {
    global $conn;
    
    $events = [];
    
    // In a real implementation, you would have an events table
    // For now, we'll create sample data
    
    // This is the query you would use with an actual 'events' table
    /*
    $currentDate = date('Y-m-d');
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
            'date' => '2025-03-12',
            'time' => '15:00 - 17:00',
            'location' => 'Sci-Bono Clubhouse',
            'description' => 'Hands-on workshop preparing for the FTC competition'
        ],
        [
            'id' => 2,
            'title' => 'Web Development Basics',
            'date' => '2025-03-14',
            'time' => '14:30 - 16:30',
            'location' => 'Computer Lab',
            'description' => 'Introduction to HTML, CSS, and JavaScript'
        ],
        [
            'id' => 3,
            'title' => 'Digital Art Challenge',
            'date' => '2025-03-18',
            'time' => '16:00 - 18:00',
            'location' => 'Art Studio',
            'description' => 'Compete to create the best digital artwork in 2 hours'
        ],
        [
            'id' => 4,
            'title' => 'AI For Youth Workshop',
            'date' => '2025-03-24',
            'time' => '15:30 - 17:30',
            'location' => 'Sci-Bono Clubhouse',
            'description' => 'Learn about artificial intelligence and machine learning'
        ],
        [
            'id' => 5,
            'title' => '3D Printing Demonstration',
            'date' => '2025-03-27',
            'time' => '14:00 - 16:00',
            'location' => 'Maker Space',
            'description' => 'See how 3D printers work and design your first 3D model'
        ]
    ];
    
    // Return sample data (in production, you'd return database results)
    return array_slice($sampleEvents, 0, $limit);
}

/**
 * Fetch latest announcements
 * 
 * @param int $limit Number of announcements to return
 * @return array Array of announcements
 */
function getAnnouncements($limit = 3) {
    global $conn;
    
    $announcements = [];
    
    // In a real implementation, you would have an announcements table
    // For now, we'll create sample data
    
    // This is the query you would use with an actual 'announcements' table
    /*
    $sql = "SELECT * FROM announcements 
           ORDER BY created_at DESC 
           LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    */
    
    // Sample data for demonstration
    $sampleAnnouncements = [
        [
            'id' => 1,
            'title' => 'FTC Nationals Preparation',
            'content' => 'All FTC team members should prepare for the upcoming National Competition. Extra practice sessions will be held every Tuesday and Thursday.',
            'created_at' => '2025-03-08',
            'priority' => 'high'
        ],
        [
            'id' => 2,
            'title' => 'New Equipment Arrival',
            'content' => 'We\'ve received new 3D printers and VR headsets! Training sessions will be announced soon. Sign up at the front desk to reserve your spot.',
            'created_at' => '2025-03-05',
            'priority' => 'medium'
        ],
        [
            'id' => 3,
            'title' => 'Holiday Program Registration Open',
            'content' => 'Registration for the April holiday program is now open. Limited spots available for robotics, coding, and digital art tracks.',
            'created_at' => '2025-03-01',
            'priority' => 'medium'
        ],
        [
            'id' => 4,
            'title' => 'Guest Speaker: Tech Industry Professional',
            'content' => 'We\'ll be hosting a guest speaker from a leading tech company on March 20th. Don\'t miss this opportunity to learn about careers in technology!',
            'created_at' => '2025-02-28',
            'priority' => 'low'
        ]
    ];
    
    // Return sample data (in production, you'd return database results)
    return array_slice($sampleAnnouncements, 0, $limit);
}

/**
 * Get calendar data including events for the month
 * 
 * @param int $month Month number (1-12)
 * @param int $year Year (e.g., 2025)
 * @return array Calendar data with events
 */
function getCalendarData($month, $year) {
    global $conn;
    
    // In a real implementation, you would fetch events for the specified month
    // For now, we'll create sample data
    
    // This is the query you would use with an actual 'events' table
    /*
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "SELECT id, title, DATE_FORMAT(date, '%d') as day 
           FROM events 
           WHERE date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $eventDays = [];
    while ($row = $result->fetch_assoc()) {
        $eventDays[(int)$row['day']] = $row;
    }
    */
    
    // Sample event days for March 2025
    $eventDays = [
        12 => ['title' => 'FTC Robotics Workshop'],
        14 => ['title' => 'Web Development Basics'],
        18 => ['title' => 'Digital Art Challenge'],
        24 => ['title' => 'AI For Youth Workshop'],
        27 => ['title' => '3D Printing Demonstration']
    ];
    
    // Calculate days in month and starting day of week
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));
    
    // Adjust to make Sunday the first day (1 = Monday in date('N'))
    $firstDayOfMonth = $firstDayOfMonth % 7;
    
    // Today's date
    $today = date('j');
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    return [
        'month' => $month,
        'year' => $year,
        'daysInMonth' => $daysInMonth,
        'firstDayOfMonth' => $firstDayOfMonth,
        'eventDays' => $eventDays,
        'today' => ($month == $currentMonth && $year == $currentYear) ? $today : null
    ];
}

/**
 * Generate calendar HTML
 * 
 * @param array $calendarData Calendar data from getCalendarData()
 * @return string HTML for calendar
 */
function generateCalendarHTML($calendarData) {
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    $dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
    
    $html = '<div class="calendar-container">';
    
    // Calendar header
    $html .= '<div class="calendar-header">';
    $html .= '<div class="month-year">' . $monthNames[$calendarData['month']] . ' ' . $calendarData['year'] . '</div>';
    $html .= '<div class="calendar-nav">';
    $html .= '<button class="nav-btn" data-action="prev-month"><i class="fas fa-chevron-left"></i></button>';
    $html .= '<button class="nav-btn" data-action="next-month"><i class="fas fa-chevron-right"></i></button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Day names
    $html .= '<div class="day-names">';
    foreach ($dayNames as $dayName) {
        $html .= '<div class="day-name">' . $dayName . '</div>';
    }
    $html .= '</div>';
    
    // Calendar grid
    $html .= '<div class="calendar-grid">';
    
    // Empty cells for days before the start of month
    for ($i = 0; $i < $calendarData['firstDayOfMonth']; $i++) {
        $html .= '<div class="calendar-day empty"></div>';
    }
    
    // Days of the month
    for ($day = 1; $day <= $calendarData['daysInMonth']; $day++) {
        $classes = ['calendar-day'];
        
        // Check if this day has events
        if (isset($calendarData['eventDays'][$day])) {
            $classes[] = 'event-day';
            $eventTitle = $calendarData['eventDays'][$day]['title'];
        } else {
            $eventTitle = '';
        }
        
        // Check if this is today
        if ($day == $calendarData['today']) {
            $classes[] = 'current-day';
        }
        
        $html .= '<div class="' . implode(' ', $classes) . '" data-day="' . $day . '" title="' . $eventTitle . '">' . $day . '</div>';
    }
    
    // Empty cells for days after the end of month
    $totalCells = $calendarData['firstDayOfMonth'] + $calendarData['daysInMonth'];
    $remainingCells = 7 - ($totalCells % 7);
    
    if ($remainingCells < 7) {
        for ($i = 0; $i < $remainingCells; $i++) {
            $html .= '<div class="calendar-day empty"></div>';
        }
    }
    
    $html .= '</div>'; // End calendar-grid
    $html .= '</div>'; // End calendar-container
    
    return $html;
}

/**
 * Get current user attendance stats
 * 
 * @param int $userId User ID
 * @return array Attendance statistics
 */
function getUserAttendanceStats($userId) {
    global $conn;
    
    $stats = [
        'total_sessions' => 0,
        'total_hours' => 0,
        'last_attendance' => null,
        'streak' => 0
    ];
    
    // In a real implementation, you would fetch attendance data
    // This is the query you would use with the actual 'attendance' table
    
    $sql = "SELECT 
            COUNT(*) as total_sessions,
            SUM(TIMESTAMPDIFF(HOUR, checked_in, checked_out)) as total_hours,
            MAX(checked_in) as last_attendance
            FROM attendance
            WHERE user_id = ? AND checked_out IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['total_sessions'] = $row['total_sessions'];
        $stats['total_hours'] = $row['total_hours'] ?: 0;
        $stats['last_attendance'] = $row['last_attendance'];
    }
    
    // Calculate attendance streak (this would be more complex in a real app)
    // This is a simplified version for demonstration
    
    $sql = "SELECT DISTINCT DATE(checked_in) as attendance_date
            FROM attendance
            WHERE user_id = ?
            ORDER BY attendance_date DESC
            LIMIT 30"; // Check last 30 days max
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['attendance_date'];
    }
    
    // Calculate streak based on consecutive days
    $streak = 0;
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    foreach ($dates as $index => $date) {
        if ($index === 0 && $date === $yesterday) {
            // First recorded date is yesterday, start counting
            $streak = 1;
        } elseif ($index > 0) {
            $prev_date = date('Y-m-d', strtotime($dates[$index-1] . ' -1 day'));
            if ($date === $prev_date) {
                $streak++;
            } else {
                break; // Streak is broken
            }
        }
    }
    
    $stats['streak'] = $streak;
    
    return $stats;
}

/**
 * Get user information
 * 
 * @param int $userId User ID
 * @return array User data
 */
function getUserInfo($userId) {
    global $conn;
    
    $sql = "SELECT id, username, name, surname, user_type, Center, Gender 
           FROM users 
           WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return null;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Format string (default: 'M j, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Get first letter of each word in a name (for avatars)
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