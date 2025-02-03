<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
	header("Location: ../../login.php");
	exit;
}
require '../../server.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the vuyanjcb_users database exists and is accessible
if (!$conn->select_db('vuyanjcb_users')) {
    die("Database selection failed: " . $conn->error);
}

// Get selected month from GET parameter, default to all months
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Fetch total unique members (for selected month or all months)
$totalMembersQuery = $selectedMonth > 0 
    ? "SELECT COUNT(DISTINCT user_id) AS total_unique_members 
       FROM vuyanjcb_users.attendance 
       WHERE MONTH(checked_out) = $selectedMonth" 
    : "SELECT COUNT(DISTINCT user_id) AS total_unique_members FROM vuyanjcb_users.attendance";
$totalMembersResult = $conn->query($totalMembersQuery);
if (!$totalMembersResult) {
    die("Query failed: " . $conn->error);
}
$totalUniqueMembers = $totalMembersResult->fetch_assoc()['total_unique_members'];

// Fetch monthly attendance trend (filtered or all months)
$monthlyTrendQuery = $selectedMonth > 0 
    ? "SELECT 
        MONTH(checked_out) AS month, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
       WHERE MONTH(checked_out) = $selectedMonth
       GROUP BY MONTH(checked_out)"
    : "SELECT 
        MONTH(checked_out) AS month, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
       GROUP BY MONTH(checked_out)
       ORDER BY month";
$monthlyTrendResult = $conn->query($monthlyTrendQuery);

$monthLabels = [];
$monthData = [];
while ($row = $monthlyTrendResult->fetch_assoc()) {
    $monthLabels[] = date('M', mktime(0, 0, 0, $row['month'], 10));
    $monthData[] = $row['unique_members'];
}

// Fetch weekly attendance (filtered by selected month)
$weeklyAttendanceQuery = $selectedMonth > 0
    ? "SELECT 
        YEARWEEK(checked_out) AS week, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
       WHERE MONTH(checked_out) = $selectedMonth
       GROUP BY YEARWEEK(checked_out)
       ORDER BY week DESC
       LIMIT 10"
    : "SELECT 
        YEARWEEK(checked_out) AS week, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
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

// Fetch daily attendance (filtered by selected month)
$dailyAttendanceQuery = $selectedMonth > 0
    ? "SELECT 
        DATE(checked_out) AS day, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
       WHERE MONTH(checked_out) = $selectedMonth
       GROUP BY DATE(checked_out)
       ORDER BY day"
    : "SELECT 
        DATE(checked_out) AS day, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM vuyanjcb_users.attendance
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

// Fetch program data (filtered by selected month)
$programQuery = $selectedMonth > 0
    ? "SELECT cr.* 
       FROM clubhouse_reports cr
       JOIN clubhouse_programs cp ON cr.program_name = cp.id
       WHERE MONTH(cr.created_at) = $selectedMonth"
    : "SELECT *
       FROM clubhouse_reports
       JOIN clubhouse_programs
       ON clubhouse_reports.program_name = clubhouse_programs.id";
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

// Generate month options for dropdown
$monthOptions = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];


