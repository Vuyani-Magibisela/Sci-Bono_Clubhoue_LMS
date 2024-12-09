<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
	header("Location: ../../login.php");
	exit;
}
require '../../server.php';

// Fetch total unique members
$totalMembersQuery = "SELECT COUNT(DISTINCT user_id) AS total_unique_members FROM accounts.attendance";
$totalMembersResult = $conn->query($totalMembersQuery);
$totalUniqueMembers = $totalMembersResult->fetch_assoc()['total_unique_members'];

// Fetch monthly attendance trend
$monthlyTrendQuery = "SELECT 
    MONTH(checked_out) AS month, 
    COUNT(DISTINCT user_id) AS unique_members
FROM accounts.attendance
GROUP BY MONTH(checked_out)
ORDER BY month";
$monthlyTrendResult = $conn->query($monthlyTrendQuery);

$monthLabels = [];
$monthData = [];
while ($row = $monthlyTrendResult->fetch_assoc()) {
    $monthLabels[] = date('M', mktime(0, 0, 0, $row['month'], 10));
    $monthData[] = $row['unique_members'];
}

// Fetch weekly attendance
$weeklyAttendanceQuery = "SELECT 
    YEARWEEK(checked_out) AS week, 
    COUNT(DISTINCT user_id) AS unique_members
FROM accounts.attendance
GROUP BY YEARWEEK(checked_out)
ORDER BY week DESC
LIMIT 10";
$weeklyAttendanceResult = $conn->query($weeklyAttendanceQuery);

$weekLabels = [];
$weekData = [];
while ($row = $weeklyAttendanceResult->fetch_assoc()) {
    $year = substr($row['week'], 0, 4);
    $week = substr($row['week'], 4);
    $weekLabels[] = "Week $week ($year)";
    $weekData[] = $row['unique_members'];
}

// Fetch daily attendance for last 30 days
$dailyAttendanceQuery = "SELECT 
    DATE(checked_out) AS day, 
    COUNT(DISTINCT user_id) AS unique_members
FROM accounts.attendance
WHERE checked_out >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(checked_out)
ORDER BY day";
$dailyAttendanceResult = $conn->query($dailyAttendanceQuery);

$dayLabels = [];
$dayData = [];
while ($row = $dailyAttendanceResult->fetch_assoc()) {
    $dayLabels[] = date('M d', strtotime($row['day']));
    $dayData[] = $row['unique_members'];
}

// Fetch program data (keeping your existing query)
$programQuery = "SELECT *
        FROM clubhouse_reports
        JOIN clubhouse_programs
        ON clubhouse_reports.program_name = clubhouse_programs.id;";
$programResult = $conn->query($programQuery);

$programData = array();
if ($programResult->num_rows > 0) {
    while($row = $programResult->fetch_assoc()) {
        $programData[] = $row;
    }
}

$conn->close();

// Convert PHP arrays to JSON for JavaScript
$monthlyTrendJSON = json_encode([
    'labels' => $monthLabels,
    'data' => $monthData
]);
$weeklyAttendanceJSON = json_encode([
    'labels' => $weekLabels,
    'data' => $weekData
]);
$dailyAttendanceJSON = json_encode([
    'labels' => $dayLabels,
    'data' => $dayData
]);
$programDataJSON = json_encode($programData);


