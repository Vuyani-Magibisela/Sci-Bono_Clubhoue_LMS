<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Database connection
require __DIR__ . '/../../../server.php';

// Get report month and year from URL parameters
$reportMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$reportYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Month names array for display
$monthNames = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// Fetch the monthly report data
$reportSql = "SELECT * FROM monthly_reports WHERE MONTH(report_date) = ? AND YEAR(report_date) = ?";
$reportStmt = $conn->prepare($reportSql);
$reportStmt->bind_param("ss", $reportMonth, $reportYear);
$reportStmt->execute();
$reportResult = $reportStmt->get_result();

if ($reportResult->num_rows === 0) {
    // No report found for this month
    $reportExists = false;
    $report = null;
} else {
    $reportExists = true;
    $report = $reportResult->fetch_assoc();
    $reportId = $report['id'];
    
    // Fetch activities for this report
    $activitiesSql = "SELECT a.*, p.title as program_name 
                     FROM monthly_report_activities a 
                     JOIN clubhouse_programs p ON a.program_id = p.id 
                     WHERE a.report_id = ?";
    $activitiesStmt = $conn->prepare($activitiesSql);
    $activitiesStmt->bind_param("i", $reportId);
    $activitiesStmt->execute();
    $activitiesResult = $activitiesStmt->get_result();
    $activities = [];
    
    while ($activity = $activitiesResult->fetch_assoc()) {
        // Fetch images for each activity
        $imagesSql = "SELECT * FROM monthly_report_images WHERE activity_id = ?";
        $imagesStmt = $conn->prepare($imagesSql);
        $imagesStmt->bind_param("i", $activity['id']);
        $imagesStmt->execute();
        $imagesResult = $imagesStmt->get_result();
        $images = [];
        
        while ($image = $imagesResult->fetch_assoc()) {
            $images[] = $image;
        }
        
        $activity['images'] = $images;
        $activities[] = $activity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Report: <?php echo $monthNames[$reportMonth] . ' ' . $reportYear; ?></title>
    <!-- Add CSS files in the correct order -->
    <link rel="stylesheet" href="../../public/assets/css/statsDashboardStyle.css">
    <link rel="stylesheet" href="../../public/assets/css/monthly_report_styles.css">
    <!-- Force CSS reload with a query parameter -->
    <style>
        /* Add some critical styles inline to ensure they're applied */
        #monthlyReportView .report-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        #monthlyReportView .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        #monthlyReportView .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        #monthlyReportView .gender-stats,
        #monthlyReportView .age-groups {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        #monthlyReportView .report-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        #monthlyReportView .btn-primary,
        #monthlyReportView .btn-secondary {
            padding: 12px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            min-width: 120px;
        }
        
        #monthlyReportView .btn-primary {
            background: #2980b9;
            color: white;
            border: none;
        }
        
        #monthlyReportView .btn-secondary {
            background: #7f8c8d;
            color: white;
            border: none;
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
<body id="monthlyReportView">
    <header class="header">
        <div class="logo-left">
            <a href="../../home.php"><img src="../../public/assets/images/Sci-Bono logo White.png" alt="Left Logo" width="" height="121"></a>
        </div>
        <div class="title-center">
            <h1>Monthly Report</h1>
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
        <a href="./statsDashboard.php">
            <div class="viewReports">
                <h3>View Reports</h3>
            </div>
        </a>
        <a href="./monthlyReportForm.php">
            <div class="CreateReport">
                <h3>Create Report</h3>
            </div>
        </a>
    </nav>

    <div class="container">
        <h1>Monthly Report: <?php echo $monthNames[$reportMonth] . ' ' . $reportYear; ?></h1>
        
        <?php if (!$reportExists): ?>
            <div class="no-report">
                <p>No report exists for this month. <a href="./monthlyReportForm.php">Create a new report</a>.</p>
            </div>
        <?php else: ?>
            <div class="report-actions">
                <a href="./monthlyReportForm.php?edit=<?php echo $reportId; ?>" class="btn-secondary">Edit Report</a>
                <a href="./monthlyReportPdf.php?id=<?php echo $reportId; ?>" class="btn-primary" target="_blank">Download PDF</a>
            </div>
            
            <!-- Monthly Statistics Section -->
            <section class="report-section stats-section">
                <h2>Monthly Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Attendees</h3>
                        <p class="stat-number"><?php echo $report['total_attendees']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Gender Breakdown</h3>
                        <div class="gender-stats">
                            <div class="gender-stat">
                                <span class="label">Male:</span> 
                                <span class="value"><?php echo $report['male_attendees']; ?></span>
                                <span class="percentage">(<?php echo ($report['total_attendees'] > 0) ? round(($report['male_attendees'] / $report['total_attendees']) * 100) : 0; ?>%)</span>
                            </div>
                            <div class="gender-stat">
                                <span class="label">Female:</span> 
                                <span class="value"><?php echo $report['female_attendees']; ?></span>
                                <span class="percentage">(<?php echo ($report['total_attendees'] > 0) ? round(($report['female_attendees'] / $report['total_attendees']) * 100) : 0; ?>%)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card age-groups">
                        <h3>Age Groups</h3>
                        <?php 
                        $ageGroups = json_decode($report['age_groups'], true);
                        foreach ($ageGroups as $range => $count): 
                        ?>
                        <div class="age-group">
                            <span class="label"><?php echo $range; ?>:</span>
                            <span class="value"><?php echo $count; ?></span>
                            <span class="percentage">(<?php echo ($report['total_attendees'] > 0) ? round(($count / $report['total_attendees']) * 100) : 0; ?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            
            <!-- Month Narrative Section -->
            <section class="report-section">
                <h2>Month Overview</h2>
                <div class="narrative-content">
                    <?php echo nl2br(htmlspecialchars($report['narrative'])); ?>
                </div>
            </section>
            
            <!-- Clubhouse Activities Section -->
            <section class="report-section">
                <h2>Clubhouse Activities</h2>
                
                <?php if (empty($activities)): ?>
                    <p>No activities recorded for this month.</p>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-card">
                            <h3><?php echo htmlspecialchars($activity['program_name']); ?></h3>
                            
                            <div class="activity-stats">
                                <div class="stat">
                                    <span class="label">Participants:</span><br>
                                    <span class="value"><?php echo $activity['participants']; ?></span>
                                </div>
                                
                                <div class="project-stats">
                                    <h4>Project Progress</h4>
                                    <div class="progress-stats">
                                        <div class="progress-stat">
                                            <span class="label">Completed:</span>
                                            <span class="value Pvalue"><?php echo $activity['completed_projects']; ?></span>
                                        </div>
                                        <div class="progress-stat">
                                            <span class="label">In Progress:</span>
                                            <span class="value"><?php echo $activity['in_progress_projects']; ?></span>
                                        </div>
                                        <div class="progress-stat">
                                            <span class="label">Not Started:</span>
                                            <span class="value"><?php echo $activity['not_started_projects']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-narrative">
                                <h4>Program Narrative</h4>
                                <p><?php echo nl2br(htmlspecialchars($activity['narrative'])); ?></p>
                            </div>
                            
                            <?php if (!empty($activity['images'])): ?>
                                <div class="activity-images">
                                    <h4>Images</h4>
                                    <div class="image-gallery">
                                        <?php foreach ($activity['images'] as $image): ?>
                                            <div class="gallery-image">
                                                <a href="../../public/assets/uploads/images/<?php echo $image['image_path']; ?>" target="_blank">
                                                    <img src="../../public/assets/uploads/images/<?php echo $image['image_path']; ?>" 
                                                         alt="Activity image" loading="lazy">
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
            
            <!-- Challenges Section -->
            <section class="report-section">
                <h2>Challenges</h2>
                <div class="challenges-content">
                    <?php echo nl2br(htmlspecialchars($report['challenges'])); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
    
    <!-- Print debug information -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('CSS files loaded:');
            const cssFiles = Array.from(document.styleSheets).map(sheet => sheet.href);
            console.log(cssFiles);
        });
    </script>
</body>
</html>