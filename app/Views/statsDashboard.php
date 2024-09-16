<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
	header("Location: ../../login.php");
	exit;
}
require '../../server.php';

// Fetch program data
$sql = "SELECT *
        FROM clubhouse_reports
        JOIN clubhouse_programs
        ON clubhouse_reports.program_name = clubhouse_programs.id;";
$result = $conn->query($sql);

$programData = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $programData[] = $row;
    }
}

$conn->close();

// Convert PHP array to JSON for JavaScript
$programDataJSON = json_encode($programData);

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
            <p id="totalMembers">Loading...</p>
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
        // Simulated data (replace with actual data from your Python script)
        const totalUniqueMembers = 69;
        const monthlyTrend = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Nov', 'Dec'],
            data: [ 0, 0, 0, 0, 0, 1, 5, 68, 12, 0, 0, 0]
        };
        const weeklyAttendance = {
            labels: ['Week 23', 'Week 25', 'Week 27', 'Week 28', 'Week 28', 'Week 31', 'Week 33', 'Week 34', 'Week 35', 'Week 36'],
            data: [1, 1, 2, 2, 1, 37, 49, 25, 12]
        };
        const dailyAttendance = {
            labels: [...Array(30).keys()].map(i => `Day ${i+1}`),
            data: Array(30).fill().map(() => Math.floor(Math.random() * 50) + 30)
        };

        // Update total members
        document.getElementById('totalMembers').textContent = totalUniqueMembers;

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