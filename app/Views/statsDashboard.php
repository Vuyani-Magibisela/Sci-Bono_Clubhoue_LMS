<?php
// Force PHP to show all errors
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// if (!file_exists(__DIR__ . '/../Models/dashboardStats.php')) {
//     die("Error: File not found - " . __DIR__ . '/../Models/dashboardStats.php');
// }

include __DIR__ . '/../Models/dashboardStats.php';
// echo "Included successfully!";
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
    </style>
</head>
<body>
    <h1>Attendance Statistics Dashboard</h1>
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
    </div>

    <h2>Recent Program Data</h2>
    <div id="programData"></div>

    <script>
        // Parse JSON data from PHP
        const totalUniqueMembers = <?php echo $totalUniqueMembers; ?>;
        const monthlyTrend = <?php echo $monthlyTrendJSON; ?>;
        const weeklyAttendance = <?php echo $weeklyAttendanceJSON; ?>;
        const dailyAttendance = <?php echo $dailyAttendanceJSON; ?>;

        let monthlyTrendChart, weeklyAttendanceChart, dailyAttendanceChart;

        // Create charts
        function createChart(id, type, labels, data, title) {
            const ctx = document.getElementById(id).getContext('2d');
            return new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: title,
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Create a function to get URL parameters
        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        // Function to update charts and data
        function updateDashboard() {
            const selectedYear = document.getElementById('yearSelector').value;
            const selectedMonth = document.getElementById('monthSelector').value;
            
            // Show loading state if needed
            document.body.style.cursor = 'wait';
            
            // Redirect with new parameters
            window.location.href = `?year=${selectedYear}&month=${selectedMonth}`;
        }

        // Add event listeners to both selectors
        document.addEventListener('DOMContentLoaded', function() {
            const yearSelector = document.getElementById('yearSelector');
            const monthSelector = document.getElementById('monthSelector');
            
            // Set initial values from URL if they exist
            const urlYear = getUrlParameter('year');
            const urlMonth = getUrlParameter('month');
            
            if (urlYear) yearSelector.value = urlYear;
            if (urlMonth) monthSelector.value = urlMonth;
            
            // Add change event listeners
            yearSelector.addEventListener('change', updateDashboard);
            monthSelector.addEventListener('change', updateDashboard);
        });

        // Initialize charts when page loads
        function initCharts() {
            // Destroy existing charts if they exist
            if (monthlyTrendChart) monthlyTrendChart.destroy();
            if (weeklyAttendanceChart) weeklyAttendanceChart.destroy();
            if (dailyAttendanceChart) dailyAttendanceChart.destroy();

            // Create new charts
            monthlyTrendChart = createChart('monthlyTrendChart', 'line', monthlyTrend.labels, monthlyTrend.data, 'Monthly Unique Members');
            weeklyAttendanceChart = createChart('weeklyAttendanceChart', 'bar', weeklyAttendance.labels, weeklyAttendance.data, 'Weekly Unique Members');
            dailyAttendanceChart = createChart('dailyAttendanceChart', 'line', dailyAttendance.labels, dailyAttendance.data, 'Daily Unique Members');
        }

        // Display program data
        const programData = <?php echo $programDataJSON; ?>;
        const programDataContainer = document.getElementById('programData');
        programDataContainer.innerHTML = ''; // Clear existing content

        programData.forEach(program => {
            const programDiv = document.createElement('div');
            programDiv.className = 'card';
            programDiv.innerHTML = `
                <h3>${program.title}</h3>
                <p>Participants: ${program.participants}</p>
                <p>Narrative: ${program.narrative}</p>
                <p>Challenges: ${program.challenges}</p>
                <div class="image-container">
                ${program.image_path ? `<img src="../../public/assets/uploads/images/${program.image_path}" alt="${program.title}" style="max-width: 100%;">` : ''}
                </div>
                `;
            programDataContainer.appendChild(programDiv);
        });

        // Initialize charts when page loads
        initCharts();
   </script>
</body>
</html>