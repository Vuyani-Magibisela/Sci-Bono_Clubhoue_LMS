<?php
// Force PHP to show all errors
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include __DIR__ . '/../Models/dashboardStats.php';

// Get year and month filters
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '0'; // 0 means all months

// Fetch monthly reports data
require '../../server.php';

$monthlyReports = [];
$reportSql = "SELECT mr.*, 
              MONTH(mr.report_date) as month_num, 
              YEAR(mr.report_date) as year_num 
              FROM monthly_reports mr 
              WHERE YEAR(mr.report_date) = ?";

// Add month filter if specified
$params = [$selectedYear];
if ($selectedMonth != '0') {
    $reportSql .= " AND MONTH(mr.report_date) = ?";
    $params[] = $selectedMonth;
}

$reportSql .= " ORDER BY mr.report_date DESC";
$reportStmt = $conn->prepare($reportSql);

// Bind parameters based on count
if (count($params) == 1) {
    $reportStmt->bind_param("s", $params[0]);
} else {
    $reportStmt->bind_param("ss", $params[0], $params[1]);
}

$reportStmt->execute();
$reportResult = $reportStmt->get_result();

while ($report = $reportResult->fetch_assoc()) {
    // Get month name
    $monthNum = $report['month_num'];
    $monthName = date('F', mktime(0, 0, 0, $monthNum, 1, 2000));
    
    // Fetch activity count
    $activitySql = "SELECT COUNT(*) as activity_count FROM monthly_report_activities WHERE report_id = ?";
    $activityStmt = $conn->prepare($activitySql);
    $activityStmt->bind_param("i", $report['id']);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result();
    $activityCount = $activityResult->fetch_assoc()['activity_count'];
    
    // Add to reports array
    $report['month_name'] = $monthName;
    $report['activity_count'] = $activityCount;
    $monthlyReports[] = $report;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Statistics Dashboard</title>
    <link rel="stylesheet" href="../../public/assets/css/statsDashboardStyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .month-filter {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .month-filter select {
            padding: 10px;
            font-size: 16px;
        }
        
        /* Monthly reports cards */
        .monthly-reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .monthly-report-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .monthly-report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .monthly-report-header {
            background: #2980b9;
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        
        .monthly-report-content {
            padding: 15px;
        }
        
        .report-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .report-stat:last-child {
            border-bottom: none;
        }
        
        .report-actions {
            display: flex;
            justify-content: center;
            padding: 15px;
            gap: 10px;
        }
        
        .report-actions a {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        
        .view-report {
            background: #2980b9;
            color: white;
            flex: 1;
        }
        
        .edit-report {
            background: #f39c12;
            color: white;
            flex: 1;
        }
        
        .no-reports {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .no-reports a {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #2980b9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        /* Mobile First  */
        .header {
        display: none !important;
        }

        /* Desktop */
        @media (min-width: 1024px) {
        .header {
            display: grid !important;
            grid-template-columns: auto 1fr auto;
            background-color: #2980b9;
            color: #fff;
            align-items: center;
            justify-content: center; 
            align-content: center;
            box-shadow: 0 0 15px 0 rgba(0, 0, 0, 0.9);
            border-radius: 5px;
        }
        
        .logo-left img {
            width: 60%;
            height: auto;
            margin: 0 auto;
            padding: 1rem 0;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .title-center {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-style: normal;
            font-weight: 600;
            font-size: 18px;
            line-height: 45px;
            text-transform: uppercase;
            margin: 0 auto;
            width: max-content;
            padding: 1rem 0;
        }

        .title-center h1 {
           color: #ffffff;
        }

        .logo-right img {
            width: 40%;
            height: auto;
            margin: 0 auto;
            padding: 1rem 0;

            display: flex;
            justify-content: center;
            align-items: center;
        }
    }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo-left">
            <a href="../../home.php"><img src="../../public/assets/images/Sci-Bono logo White.png" alt="Left Logo" width="" height="121"></a>
        </div>
        <div class="title-center">
            <h1>Sci-Bono Clubhouse Reports</h1>
        </div>
        <div class="logo-right">
            <img src="../../public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Right Logo" width="" height="110">
        </div>
    </header> 

    <nav class="navigation">
        <a href="../../home.php"> 
            <div class="dashboardLink">
                <h3>Dashboard</h3>
            </div>
        </a>
       <a href="./monthlyReportForm.php">
            <div class="CreateReport">
                <h3>Create Report</h3>
            </div>
       </a>
        <a href="./addClubhouseProgram.php">
            <div class="addProgram">
                <h3>Add Program</h3>
            </div>
        </a>
    </nav>

    <h1>Attendance Statistics Dashboard</h1>
    
    <!-- Filters Section -->
    <div class="filters-section">
        <!-- Year Filter Dropdown -->
        <div class="year-filter">
            <select id="yearSelector">
                <?php foreach ($yearOptions as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo ($selectedYear == $year ? 'selected' : ''); ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Month Filter Dropdown -->
        <div class="month-filter">
            <select id="monthSelector">
                <option value="0">All Months</option>
                <?php foreach ($monthOptions as $monthNum => $monthName): ?>
                    <option value="<?php echo $monthNum; ?>" <?php echo ($selectedMonth == $monthNum ? 'selected' : ''); ?>>
                        <?php echo $monthName; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Attendance Stats Dashboard -->
    <div class="dashboard">
        <div class="card">
            <h2>Total Unique Members</h2>
            <p id="totalMembers"><?php echo $totalUniqueMembers; ?></p>
        </div>
        <div class="card">
            <h2>Monthly Attendance Trend</h2>
            <canvas id="monthlyTrendChart"></canvas>
        </div>
        <div class="card">
            <h2>Weekly Attendance</h2>
            <canvas id="weeklyAttendanceChart"></canvas>
        </div>
        <div class="card">
            <h2>Daily Attendance</h2>
            <canvas id="dailyAttendanceChart"></canvas>
        </div>
        <div class="card">
            <h2>Gender Distribution</h2>
            <canvas id="genderDistributionChart"></canvas>
        </div>
        <div class="card">
            <h2>Age Group Distribution</h2>
            <canvas id="ageGroupChart"></canvas>
        </div>
    </div>

    <!-- Monthly Reports Section -->
    <section class="monthly-reports-section">
        <div class="section-header">
            <h2>Monthly Reports</h2>
            <!-- <a href="./monthlyReportForm.php" class="btn-primary">Create New Report</a> -->
        </div>
        
        <?php if (empty($monthlyReports)): ?>
            <div class="no-reports">
                <p>No monthly reports found for the selected period.</p>
                <a href="./monthlyReportForm.php">Create New Report</a>
            </div>
        <?php else: ?>
            <div class="monthly-reports-grid">
                <?php foreach ($monthlyReports as $report): ?>
                    <div class="monthly-report-card">
                        <div class="monthly-report-header">
                            <?php echo $report['month_name'] . ' ' . $report['year_num']; ?>
                        </div>
                        <div class="monthly-report-content">
                            <div class="report-stat">
                                <span>Total Attendees:</span>
                                <span><?php echo $report['total_attendees']; ?></span>
                            </div>
                            <div class="report-stat">
                                <span>Activities:</span>
                                <span><?php echo $report['activity_count']; ?></span>
                            </div>
                            <div class="report-stat">
                                <span>Created:</span>
                                <span><?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <a href="./monthlyReportView.php?year=<?php echo $report['year_num']; ?>&month=<?php echo str_pad($report['month_num'], 2, '0', STR_PAD_LEFT); ?>" class="view-report">View</a>
                            <a href="./monthlyReportForm.php?edit=<?php echo $report['id']; ?>" class="edit-report">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Program Data Section -->
    <!-- <h2>Program Reports</h2>
    <div id="programData"></div> -->

    <script>
    // Parse JSON data from PHP with error handling
    let totalUniqueMembers, monthlyTrend, weeklyAttendance, dailyAttendance, genderDistribution, ageGroupDistribution;
    let monthlyTrendChart, weeklyAttendanceChart, dailyAttendanceChart, genderDistributionChart, ageGroupChart;

    try {
        totalUniqueMembers = <?php echo isset($totalUniqueMembers) ? $totalUniqueMembers : 0; ?>;
        monthlyTrend = <?php echo !empty($monthlyTrendJSON) ? $monthlyTrendJSON : '{"labels":[], "data":[]}'; ?>;
        weeklyAttendance = <?php echo !empty($weeklyAttendanceJSON) ? $weeklyAttendanceJSON : '{"labels":[], "data":[]}'; ?>;
        dailyAttendance = <?php echo !empty($dailyAttendanceJSON) ? $dailyAttendanceJSON : '{"labels":[], "data":[]}'; ?>;
        genderDistribution = <?php echo !empty($genderDistributionJSON) ? $genderDistributionJSON : '{"labels":[], "data":[]}'; ?>;
        ageGroupDistribution = <?php echo !empty($ageGroupJSON) ? $ageGroupJSON : '{"labels":[], "data":[]}'; ?>;
    } catch (e) {
        console.error("Error parsing JSON data:", e);
        totalUniqueMembers = 0;
        monthlyTrend = {"labels":[], "data":[]};
        weeklyAttendance = {"labels":[], "data":[]};
        dailyAttendance = {"labels":[], "data":[]};
        genderDistribution = {"labels":[], "data":[]};
        ageGroupDistribution = {"labels":[], "data":[]};
    }

    // Function to safely get a 2D context
    function getSafeContext(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas element with ID '${canvasId}' not found`);
            return null;
        }
        
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error(`Could not get 2D context for canvas '${canvasId}'`);
            return null;
        }
        
        return ctx;
    }

    // Create charts with error handling
    function createChart(id, type, labels, data, title, colors = null) {
        const ctx = getSafeContext(id);
        if (!ctx) return null;
        
        try {
            // Configure datasets based on chart type
            let datasets = [];
            
            if (type === 'pie' || type === 'doughnut') {
                // Use a colorful palette for pie/doughnut charts
                const defaultColors = [
                    'rgba(54, 162, 235, 0.8)',   // Blue
                    'rgba(255, 99, 132, 0.8)',   // Pink
                    'rgba(255, 206, 86, 0.8)',   // Yellow
                    'rgba(75, 192, 192, 0.8)',   // Teal
                    'rgba(153, 102, 255, 0.8)',  // Purple
                    'rgba(255, 159, 64, 0.8)',   // Orange
                    'rgba(199, 199, 199, 0.8)'   // Gray
                ];
                
                datasets = [{
                    data: data,
                    backgroundColor: colors || defaultColors.slice(0, data.length),
                    borderWidth: 1
                }];
            } else {
                // Line, bar charts use single color with label
                datasets = [{
                    label: title,
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }];
            }
            
            // Define chart options based on type
            let options = {
                responsive: true,
                maintainAspectRatio: true
            };
            
            if (type === 'pie' || type === 'doughnut') {
                options.plugins = {
                    legend: {
                        position: 'right',
                        labels: {
                            generateLabels: function(chart) {
                                const originalLabels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                
                                // Add percentages to labels
                                originalLabels.forEach((label, i) => {
                                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const value = chart.data.datasets[0].data[i];
                                    const percentage = ((value / total) * 100).toFixed(1) + '%';
                                    label.text = `${chart.data.labels[i]}: ${percentage} (${value})`;
                                });
                                
                                return originalLabels;
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                };
            } else {
                options.scales = {
                    y: {
                        beginAtZero: true
                    }
                };
            }
            
            return new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: options
            });
        } catch (e) {
            console.error(`Error creating chart '${id}':`, e);
            return null;
        }
    }

    // Function to update charts and data
    function updateDashboard() {
        const selectedYear = document.getElementById('yearSelector').value;
        const selectedMonth = document.getElementById('monthSelector').value;
        
        // Show loading state
        document.body.style.cursor = 'wait';
        
        // Redirect with new parameters
        window.location.href = `?year=${selectedYear}&month=${selectedMonth}`;
    }

    // Initialize charts when page loads
    function initCharts() {
        console.log("Initializing charts...");
        
        // Clear existing charts if they exist
        if (monthlyTrendChart) monthlyTrendChart.destroy();
        if (weeklyAttendanceChart) weeklyAttendanceChart.destroy();
        if (dailyAttendanceChart) dailyAttendanceChart.destroy();
        if (genderDistributionChart) genderDistributionChart.destroy();
        if (ageGroupChart) ageGroupChart.destroy();

        // Create new charts
        monthlyTrendChart = createChart('monthlyTrendChart', 'line', monthlyTrend.labels, monthlyTrend.data, 'Monthly Unique Members');
        weeklyAttendanceChart = createChart('weeklyAttendanceChart', 'bar', weeklyAttendance.labels, weeklyAttendance.data, 'Weekly Unique Members');
        dailyAttendanceChart = createChart('dailyAttendanceChart', 'line', dailyAttendance.labels, dailyAttendance.data, 'Daily Unique Members');
        
        // Create gender and age distribution charts
        genderDistributionChart = createChart('genderDistributionChart', 'doughnut', genderDistribution.labels, genderDistribution.data, 'Gender Distribution', [
            'rgba(54, 162, 235, 0.8)',  // Blue for Male
            'rgba(255, 99, 132, 0.8)',  // Pink for Female
            'rgba(255, 206, 86, 0.8)'   // Yellow for Other
        ]);
        
        ageGroupChart = createChart('ageGroupChart', 'bar', ageGroupDistribution.labels, ageGroupDistribution.data, 'Age Groups');
    }

    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded, setting up event listeners...");
        
        const yearSelector = document.getElementById('yearSelector');
        const monthSelector = document.getElementById('monthSelector');
        
        if (yearSelector && monthSelector) {
            // Set initial values from URL if they exist
            const urlYear = getUrlParameter('year');
            const urlMonth = getUrlParameter('month');
            
            if (urlYear) yearSelector.value = urlYear;
            if (urlMonth) monthSelector.value = urlMonth;
            
            // Add change event listeners
            yearSelector.addEventListener('change', updateDashboard);
            monthSelector.addEventListener('change', updateDashboard);
        }
        
        // Initialize charts with a slight delay to ensure canvas elements are fully loaded
        setTimeout(() => {
            initCharts();
            displayProgramData();
        }, 100);
    });

    // Helper functions
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    function displayProgramData() {
        try {
            const programData = <?php echo !empty($programDataJSON) ? $programDataJSON : '[]'; ?>;
            const programDataContainer = document.getElementById('programData');
            
            if (!programDataContainer) {
                console.error("Program data container not found");
                return;
            }
            
            programDataContainer.innerHTML = ''; // Clear existing content

            if (programData.length === 0) {
                programDataContainer.innerHTML = '<p>No program data available for the selected period.</p>';
                return;
            }

            programData.forEach(program => {
                const programDiv = document.createElement('div');
                programDiv.className = 'card';
                programDiv.innerHTML = `
                    <h3>${program.title || 'Unknown Program'}</h3>
                    <p>Participants: ${program.participants || 0}</p>
                    <p>Narrative: ${program.narrative || 'No narrative provided'}</p>
                    <p>Challenges: ${program.challenges || 'No challenges reported'}</p>
                    <div class="image-container">
                    ${program.image_path ? `<img src="../../public/assets/uploads/images/${program.image_path}" alt="${program.title || 'Program image'}" style="max-width: 100%;">` : ''}
                    </div>
                    `;
                programDataContainer.appendChild(programDiv);
            });
        } catch (e) {
            console.error("Error displaying program data:", e);
            document.getElementById('programData').innerHTML = '<p>Error loading program data.</p>';
        }
    }
    </script>
</body>
</html>