<?php
include '../Models/dashboardStats.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Statistics Dashboard</title>
    <link rel="stylesheet" href="../../public/assets/css/statsDashboardStyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Attendance Statistics Dashboard</h1>
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
                <h2>Daily Attendance (Last 30 Days)</h2>
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

        // Create charts
        function createChart(id, type, labels, data, title) {
            const ctx = document.getElementById(id).getContext('2d');
            new Chart(ctx, {
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

        // Create charts with dynamic data
        createChart('monthlyTrendChart', 'line', monthlyTrend.labels, monthlyTrend.data, 'Monthly Unique Members');
        createChart('weeklyAttendanceChart', 'bar', weeklyAttendance.labels, weeklyAttendance.data, 'Weekly Unique Members');
        createChart('dailyAttendanceChart', 'line', dailyAttendance.labels, dailyAttendance.data, 'Daily Unique Members');
        
        // Display program data
        const programData = <?php echo $programDataJSON; ?>;
        const programDataContainer = document.getElementById('programData');

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
   </script>
</body>
</html>