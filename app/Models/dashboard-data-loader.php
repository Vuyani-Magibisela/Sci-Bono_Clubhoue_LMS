<?php
/**
 * Dashboard Data Loader
 * 
 * This file is responsible for loading data into the dashboard
 * Use this file at the top of your home.php before the HTML to load dynamic content
 */

// Include the dashboard functions
require_once 'app/Models/dashboard-functions.php';

// Get current user ID from session
$currentUserId = $_SESSION['id'] ?? 1; // Default to 1 if not set

// Load user information
$userInfo = getUserInfo($currentUserId);

// Load member posts
$memberPosts = getMemberPosts(5); // Get 5 latest posts

// Load announcements
$announcements = getAnnouncements(3); // Get 3 latest announcements

// Load upcoming events
$upcomingEvents = getUpcomingEvents(4); // Get 4 upcoming events

// Get current month and year for calendar
$currentMonth = date('n');
$currentYear = date('Y');

// Get month and year from URL parameters if present
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = $currentMonth;
}

if ($year < 2020 || $year > 2030) {
    $year = $currentYear;
}

// Load calendar data
$calendarData = getCalendarData($month, $year);

// Generate calendar HTML
$calendarHTML = generateCalendarHTML($calendarData);

// Get user attendance stats
$attendanceStats = getUserAttendanceStats($currentUserId);

// Convert ISO dates to readable format
foreach ($memberPosts as &$post) {
    $post['formatted_date'] = formatDate($post['created_at']);
}

foreach ($announcements as &$announcement) {
    $announcement['formatted_date'] = formatDate($announcement['created_at']);
}

// For events, extract month, day, and formatted time
foreach ($upcomingEvents as &$event) {
    $event['month'] = date('M', strtotime($event['date']));
    $event['day'] = date('j', strtotime($event['date']));
    $event['formatted_date'] = formatDate($event['date']);
}

// Get username initials for avatars
if ($userInfo) {
    $userInfo['initials'] = getInitials($userInfo['name'] . ' ' . $userInfo['surname']);
}

// Function to get random avatar color based on user ID
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