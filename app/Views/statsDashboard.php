<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Statistics Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/statsDashboardStyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    h1 {
        text-align: center;
        color: #2c3e50;
    }

    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    .card {
        background-color: #f7f7f7;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card h2 {
        margin-top: 0;
        color: #3498db;
    }
    #totalMembers {
        font-size: 2em;
        font-weight: bold;
        color: #2980b9;
    }
    canvas {
        max-width: 100%;
    }  
    </style>
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

    <script>
        // Simulated data (replace with actual data from your Python script)
        const totalUniqueMembers = 150;
        const monthlyTrend = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            data: [100, 120, 110, 130, 140, 150]
        };
        const weeklyAttendance = {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            data: [80, 90, 85, 95]
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
    </script>
</body>
</html>