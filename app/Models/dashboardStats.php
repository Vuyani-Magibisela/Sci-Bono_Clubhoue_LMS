<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
	header("Location: ../../login.php");
	exit;
}
require '../../server.php';

// Get selected year from GET parameter, default to current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
// Generate year options (e.g., last 5 years)
$currentYear = date('Y');
$yearOptions = array();
for ($year = $currentYear; $year >= $currentYear - 4; $year--) {
    $yearOptions[$year] = $year;
}

// Get selected month from GET parameter, default to all months
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Fetch total unique members (for selected YEAR, Month or all months)
$totalMembersQuery = $selectedMonth > 0 
    ? "SELECT COUNT(DISTINCT user_id) AS total_unique_members 
       FROM accounts.attendance 
       WHERE MONTH(checked_out) = $selectedMonth 
       AND YEAR(checked_out) = $selectedYear" 
    : "SELECT COUNT(DISTINCT user_id) AS total_unique_members 
        FROM accounts.attendance WHERE YEAR(checked_out) = $selectedYear";
$totalMembersResult = $conn->query($totalMembersQuery);
$totalUniqueMembers = $totalMembersResult->fetch_assoc()['total_unique_members'];

// Fetch monthly attendance trend (filtered or all months)
$monthlyTrendQuery = $selectedMonth > 0 
    ? "SELECT 
        MONTH(checked_out) AS month, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM accounts.attendance
       WHERE MONTH(checked_out) = $selectedMonth AND YEAR(checked_out) = $selectedYear
       GROUP BY MONTH(checked_out)"
    : "SELECT 
        MONTH(checked_out) AS month, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM accounts.attendance
       WHERE YEAR(checked_out) = $selectedYear
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
       FROM accounts.attendance
       WHERE MONTH(checked_out) = $selectedMonth
       AND YEAR(checked_out) = $selectedYear
       GROUP BY YEARWEEK(checked_out)
       ORDER BY week DESC
       LIMIT 10"
    : "SELECT 
        YEARWEEK(checked_out) AS week, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM accounts.attendance
       WHERE YEAR(checked_out) = $selectedYear
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
       FROM accounts.attendance
       WHERE MONTH(checked_out) = $selectedMonth
       AND YEAR(checked_out) = $selectedYear
       GROUP BY DATE(checked_out)
       ORDER BY day"
    : "SELECT 
        DATE(checked_out) AS day, 
        COUNT(DISTINCT user_id) AS unique_members
       FROM accounts.attendance
       WHERE YEAR(checked_out) = $selectedYear
       AND checked_out >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       GROUP BY DATE(checked_out)
       ORDER BY day";
$dailyAttendanceResult = $conn->query($dailyAttendanceQuery);

$dayLabels = [];
$dayData = [];
while ($row = $dailyAttendanceResult->fetch_assoc()) {
    $dayLabels[] = date('M d', strtotime($row['day']));
    $dayData[] = $row['unique_members'];
}

// Gender distribution query
$genderDistributionQuery = $selectedMonth > 0 
    ? "SELECT u.Gender, COUNT(DISTINCT a.user_id) as count
       FROM attendance a
       JOIN users u ON a.user_id = u.id
       WHERE MONTH(a.checked_out) = $selectedMonth 
       AND YEAR(a.checked_out) = $selectedYear
       GROUP BY u.Gender"
    : "SELECT u.Gender, COUNT(DISTINCT a.user_id) as count
       FROM attendance a
       JOIN users u ON a.user_id = u.id
       WHERE YEAR(a.checked_out) = $selectedYear
       GROUP BY u.Gender";
       
$genderResult = $conn->query($genderDistributionQuery);

$genderLabels = [];
$genderData = [];
while ($row = $genderResult->fetch_assoc()) {
    $genderLabels[] = $row['Gender'];
    $genderData[] = $row['count'];
}

// Age group distribution
$ageGroupQuery = $selectedMonth > 0 
    ? "SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-', $selectedMonth, '-01')) BETWEEN 9 AND 12 THEN '9-12'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-', $selectedMonth, '-01')) BETWEEN 13 AND 14 THEN '13-14'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-', $selectedMonth, '-01')) BETWEEN 15 AND 16 THEN '15-16'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-', $selectedMonth, '-01')) BETWEEN 17 AND 18 THEN '17-18'
            ELSE 'Other'
        END AS age_group,
        COUNT(DISTINCT a.user_id) as count
      FROM attendance a
      JOIN users u ON a.user_id = u.id
      WHERE MONTH(a.checked_out) = $selectedMonth 
      AND YEAR(a.checked_out) = $selectedYear
      AND u.date_of_birth IS NOT NULL
      GROUP BY age_group
      ORDER BY age_group"
    : "SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-01-01')) BETWEEN 9 AND 12 THEN '9-12'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-01-01')) BETWEEN 13 AND 14 THEN '13-14'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-01-01')) BETWEEN 15 AND 16 THEN '15-16'
            WHEN TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT($selectedYear, '-01-01')) BETWEEN 17 AND 18 THEN '17-18'
            ELSE 'Other'
        END AS age_group,
        COUNT(DISTINCT a.user_id) as count
      FROM attendance a
      JOIN users u ON a.user_id = u.id
      WHERE YEAR(a.checked_out) = $selectedYear
      AND u.date_of_birth IS NOT NULL
      GROUP BY age_group
      ORDER BY age_group";

$ageGroupResult = $conn->query($ageGroupQuery);

$ageGroupLabels = [];
$ageGroupData = [];
while ($row = $ageGroupResult->fetch_assoc()) {
    $ageGroupLabels[] = $row['age_group'];
    $ageGroupData[] = $row['count'];
}

// Convert gender and age group data to JSON for JavaScript
$genderDistributionJSON = json_encode([
    'labels' => $genderLabels,
    'data' => $genderData
]);

$ageGroupJSON = json_encode([
    'labels' => $ageGroupLabels,
    'data' => $ageGroupData
]);

// Fetch program data 
$programQuery = $selectedMonth > 0
    ? "SELECT cr.* 
       FROM clubhouse_reports cr
       JOIN clubhouse_programs cp ON cr.program_name = cp.id
       WHERE MONTH(cr.created_at) = $selectedMonth
       AND YEAR(cr.created_at) = $selectedYear"
    : "SELECT *
       FROM clubhouse_reports cr
       JOIN clubhouse_programs cp
       ON cr.program_name = cp.id
       WHERE YEAR(cr.created_at) = $selectedYear";
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


