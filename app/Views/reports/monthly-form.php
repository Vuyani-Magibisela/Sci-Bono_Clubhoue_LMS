<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Database connection
require __DIR__ . '/../../../server.php';
require_once __DIR__ . '/../../core/CSRF.php';

// Get current month and year for default selection
$currentMonth = date('m');
$currentYear = date('Y');

// Get month and year from URL parameters if present
$reportMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
$reportYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo CSRF::metaTag(); ?>
    <title>Monthly Report Form</title>
    <link rel="Stylesheet" href="../../public/assets/css/statsDashboardStyle.css">
    <style>
        /* Styles for the auto-populated stats */
        .stats-display {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stats-item {
            margin-bottom: 15px;
        }
        
        .stats-item label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-value {
            font-size: 18px;
            background-color: white;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .stats-item.half {
            flex: 1;
        }
        
        .age-groups-display {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .age-group-item {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .age-group-item label {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .age-group-item .stat-value {
            font-size: 16px;
            background-color: #f0f7fb;
        }

        /* Mobile First  */
        .header {
        display: none !important;
        }

        @media (max-width: 768px) {
            /* .stats-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .age-groups-display {
                grid-template-columns: 1fr; */
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
        

        }
    </style>
</head>

<body id="monthlyReportForm">
    <header class="header">
        <div class="logo-left">
            <a href="../../home.php"><img src="../../public/assets/images/Sci-Bono logo White.png" alt="Left Logo" width="" height="121"></a>
        </div>
        <div class="title-center">
            <h1>Monthly Report Form</h1>
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
        <a href="./addClubhouseProgram.php">
            <div class="addProgram">
                <h3>Add Program</h3>
            </div>
        </a>
    </nav>

    <div class="container">
        <h1>Monthly Report</h1>
        <form id="monthlyReportForm" action="../Controllers/submit_monthly_report.php" method="post" enctype="multipart/form-data">
            <?php echo CSRF::field(); ?>
            <!-- Month and Year Selection -->
            <div class="form-section">
                <div class="form-group">
                    <label for="reportMonth">Month:</label>
                    <select name="reportMonth" id="reportMonth" required>
                        <option value="01" <?php echo ($reportMonth == '01') ? 'selected' : ''; ?>>January</option>
                        <option value="02" <?php echo ($reportMonth == '02') ? 'selected' : ''; ?>>February</option>
                        <option value="03" <?php echo ($reportMonth == '03') ? 'selected' : ''; ?>>March</option>
                        <option value="04" <?php echo ($reportMonth == '04') ? 'selected' : ''; ?>>April</option>
                        <option value="05" <?php echo ($reportMonth == '05') ? 'selected' : ''; ?>>May</option>
                        <option value="06" <?php echo ($reportMonth == '06') ? 'selected' : ''; ?>>June</option>
                        <option value="07" <?php echo ($reportMonth == '07') ? 'selected' : ''; ?>>July</option>
                        <option value="08" <?php echo ($reportMonth == '08') ? 'selected' : ''; ?>>August</option>
                        <option value="09" <?php echo ($reportMonth == '09') ? 'selected' : ''; ?>>September</option>
                        <option value="10" <?php echo ($reportMonth == '10') ? 'selected' : ''; ?>>October</option>
                        <option value="11" <?php echo ($reportMonth == '11') ? 'selected' : ''; ?>>November</option>
                        <option value="12" <?php echo ($reportMonth == '12') ? 'selected' : ''; ?>>December</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reportYear">Year:</label>
                    <select name="reportYear" id="reportYear" required>
                        <?php 
                        $startYear = 2024;
                        $endYear = date('Y') + 1;
                        for($year = $startYear; $year <= $endYear; $year++) {
                            $selected = ($year == $reportYear) ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <script>
                    // Add event listeners to refresh the page when month or year changes
                    document.addEventListener('DOMContentLoaded', function() {
                        const monthSelector = document.getElementById('reportMonth');
                        const yearSelector = document.getElementById('reportYear');
                        
                        function refreshStats() {
                            const month = monthSelector.value;
                            const year = yearSelector.value;
                            window.location.href = `monthlyReportForm.php?month=${month}&year=${year}`;
                        }
                        
                        monthSelector.addEventListener('change', refreshStats);
                        yearSelector.addEventListener('change', refreshStats);
                    });
                </script>
            </div>

            <!-- Month Overview -->
            <div class="form-section">
                <h2>Month Overview</h2>
                <?php
                // Get month and year from form selection
                $reportMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
                $reportYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
                
                // Fetch attendance statistics from the database for the selected month/year
                // We'll use the existing connection from server.php
                
                // Total attendees
                $totalAttendeesQuery = "SELECT COUNT(DISTINCT user_id) AS total_attendees 
                                       FROM attendance 
                                       WHERE MONTH(checked_out) = ? 
                                       AND YEAR(checked_out) = ?";
                $totalStmt = $conn->prepare($totalAttendeesQuery);
                $totalStmt->bind_param("ss", $reportMonth, $reportYear);
                $totalStmt->execute();
                $totalResult = $totalStmt->get_result();
                $totalAttendees = $totalResult->fetch_assoc()['total_attendees'];
                
                // Gender breakdown - Male
                $maleAttendeesQuery = "SELECT COUNT(DISTINCT a.user_id) AS male_attendees 
                                      FROM attendance a 
                                      JOIN users u ON a.user_id = u.id 
                                      WHERE MONTH(a.checked_out) = ? 
                                      AND YEAR(a.checked_out) = ? 
                                      AND u.Gender = 'Male'";
                $maleStmt = $conn->prepare($maleAttendeesQuery);
                $maleStmt->bind_param("ss", $reportMonth, $reportYear);
                $maleStmt->execute();
                $maleResult = $maleStmt->get_result();
                $maleAttendees = $maleResult->fetch_assoc()['male_attendees'];
                
                // Gender breakdown - Female
                $femaleAttendeesQuery = "SELECT COUNT(DISTINCT a.user_id) AS female_attendees 
                                        FROM attendance a 
                                        JOIN users u ON a.user_id = u.id 
                                        WHERE MONTH(a.checked_out) = ? 
                                        AND YEAR(a.checked_out) = ? 
                                        AND u.Gender = 'Female'";
                $femaleStmt = $conn->prepare($femaleAttendeesQuery);
                $femaleStmt->bind_param("ss", $reportMonth, $reportYear);
                $femaleStmt->execute();
                $femaleResult = $femaleStmt->get_result();
                $femaleAttendees = $femaleResult->fetch_assoc()['female_attendees'];
                
                // Age groups
                $ageGroupsData = [
                    '9-12' => 0,
                    '12-14' => 0,
                    '14-16' => 0,
                    '16-18' => 0
                ];
                
                // Calculate ages based on date_of_birth
                $ageQuery = "SELECT 
                               DISTINCT a.user_id,
                               u.date_of_birth,
                               TIMESTAMPDIFF(YEAR, u.date_of_birth, CONCAT(?, '-', ?, '-01')) AS age
                             FROM attendance a
                             JOIN users u ON a.user_id = u.id
                             WHERE MONTH(a.checked_out) = ?
                             AND YEAR(a.checked_out) = ?
                             AND u.date_of_birth IS NOT NULL";
                $ageStmt = $conn->prepare($ageQuery);
                $ageStmt->bind_param("ssss", $reportYear, $reportMonth, $reportMonth, $reportYear);
                $ageStmt->execute();
                $ageResult = $ageStmt->get_result();
                
                while ($row = $ageResult->fetch_assoc()) {
                    $age = intval($row['age']);
                    if ($age >= 9 && $age < 12) {
                        $ageGroupsData['9-12']++;
                    } else if ($age >= 12 && $age < 14) {
                        $ageGroupsData['12-14']++;
                    } else if ($age >= 14 && $age < 16) {
                        $ageGroupsData['14-16']++;
                    } else if ($age >= 16 && $age <= 18) {
                        $ageGroupsData['16-18']++;
                    }
                }
                ?>
                
                <div class="stats-display">
                    <div class="stats-item">
                        <label>Total Attendees:</label>
                        <span class="stat-value"><?php echo $totalAttendees; ?></span>
                        <input type="hidden" name="totalAttendees" value="<?php echo $totalAttendees; ?>">
                    </div>
                    
                    <div class="stats-row">
                        <div class="stats-item half">
                            <label>Male Attendees:</label>
                            <span class="stat-value"><?php echo $maleAttendees; ?></span>
                            <input type="hidden" name="maleAttendees" value="<?php echo $maleAttendees; ?>">
                        </div>
                        <div class="stats-item half">
                            <label>Female Attendees:</label>
                            <span class="stat-value"><?php echo $femaleAttendees; ?></span>
                            <input type="hidden" name="femaleAttendees" value="<?php echo $femaleAttendees; ?>">
                        </div>
                    </div>
                    
                    <div class="stats-item">
                        <label>Age Groups:</label>
                        <div class="age-groups-display">
                            <?php foreach ($ageGroupsData as $range => $count): ?>
                            <div class="age-group-item">
                                <label><?php echo $range; ?> years:</label>
                                <span class="stat-value"><?php echo $count; ?></span>
                                <input type="hidden" name="ageGroups[<?php echo $range; ?>]" value="<?php echo $count; ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Month Narrative -->
            <div class="form-section">
                <h2>Month Narrative</h2>
                <div class="form-group">
                    <textarea id="monthNarrative" name="monthNarrative" rows="6" required 
                        placeholder="Provide an overview of this month's activities, achievements, and notable events..."></textarea>
                </div>
            </div>
            
            

            <!-- Clubhouse Activities -->
            <div class="form-section" id="clubhouseActivitiesSection">
                <h2>Clubhouse Activities</h2>
                <div class="activity-container" id="activityContainer">
                    <div class="activity-form">
                        <div class="form-group">
                            <label for="program0">Program:</label>
                            <select name="activities[0][program_id]" id="program0" required>
                                <option value="">Select a program</option>
                                <?php
                                // Database connection details
                                require '../../server.php';

                                $sql = "SELECT id, title FROM clubhouse_programs";
                                $result = $conn->query($sql);
                                
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["title"]) . "</option>";
                                    }
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="participants0">Number of Participants:</label>
                            <input type="number" name="activities[0][participants]" id="participants0" min="0" required>
                        </div>

                        <div class="form-group project-stats">
                            <label>Project Progress:</label>
                            <div class="form-row">
                                <div class="form-group third">
                                    <label for="completed0">Completed:</label>
                                    <input type="number" name="activities[0][completed]" id="completed0" min="0" required>
                                </div>
                                <div class="form-group third">
                                    <label for="inProgress0">In Progress:</label>
                                    <input type="number" name="activities[0][in_progress]" id="inProgress0" min="0" required>
                                </div>
                                <div class="form-group third">
                                    <label for="notStarted0">Not Started:</label>
                                    <input type="number" name="activities[0][not_started]" id="notStarted0" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="narrative0">Program Narrative:</label>
                            <textarea name="activities[0][narrative]" id="narrative0" rows="4" required
                                placeholder="Describe program activities, outcomes, and participant engagement..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="images0">Upload Images:</label>
                            <input type="file" name="activities[0][images][]" id="images0" accept="image/*" multiple>
                        </div>
                    </div>
                </div>
                <button type="button" id="addActivity" class="btn-secondary">Add Activity</button>
            </div>

            <!-- Challenges Section -->
            <div class="form-section">
                <h2>Challenges</h2>
                <div class="form-group">
                    <textarea id="challenges" name="challenges" rows="6" required
                        placeholder="Describe any challenges encountered during the month and how they were addressed..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Submit Report</button>
            </div>
        </form>
    </div>

    <script>
        let activityCount = 1;
        
        document.getElementById('addActivity').addEventListener('click', function() {
            const activityContainer = document.getElementById('activityContainer');
            const newActivity = document.createElement('div');
            newActivity.className = 'activity-form';
            newActivity.innerHTML = `
                <hr class="activity-divider">
                <div class="form-group">
                    <label for="program${activityCount}">Program:</label>
                    <select name="activities[${activityCount}][program_id]" id="program${activityCount}" required>
                        <option value="">Select a program</option>
                        <?php
                        // Re-establish database connection
                        require '../../server.php';
                        $sql = "SELECT id, title FROM clubhouse_programs";
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["title"]) . "</option>";
                            }
                        }
                        $conn->close();
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="participants${activityCount}">Number of Participants:</label>
                    <input type="number" name="activities[${activityCount}][participants]" id="participants${activityCount}" min="0" required>
                </div>

                <div class="form-group project-stats">
                    <label>Project Progress:</label>
                    <div class="form-row">
                        <div class="form-group third">
                            <label for="completed${activityCount}">Completed:</label>
                            <input type="number" name="activities[${activityCount}][completed]" id="completed${activityCount}" min="0" required>
                        </div>
                        <div class="form-group third">
                            <label for="inProgress${activityCount}">In Progress:</label>
                            <input type="number" name="activities[${activityCount}][in_progress]" id="inProgress${activityCount}" min="0" required>
                        </div>
                        <div class="form-group third">
                            <label for="notStarted${activityCount}">Not Started:</label>
                            <input type="number" name="activities[${activityCount}][not_started]" id="notStarted${activityCount}" min="0" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="narrative${activityCount}">Program Narrative:</label>
                    <textarea name="activities[${activityCount}][narrative]" id="narrative${activityCount}" rows="4" required
                        placeholder="Describe program activities, outcomes, and participant engagement..."></textarea>
                </div>

                <div class="form-group">
                    <label for="images${activityCount}">Upload Images:</label>
                    <input type="file" name="activities[${activityCount}][images][]" id="images${activityCount}" accept="image/*" multiple>
                </div>
                
                <button type="button" class="remove-activity btn-danger" data-id="${activityCount}">Remove Activity</button>
            `;
            activityContainer.appendChild(newActivity);
            
            // Add event listener to the remove button
            newActivity.querySelector('.remove-activity').addEventListener('click', function() {
                activityContainer.removeChild(newActivity);
            });
            
            activityCount++;
        });

        // Validation for total attendees matching gender breakdown
        const totalInput = document.getElementById('totalAttendees');
        const maleInput = document.getElementById('maleAttendees');
        const femaleInput = document.getElementById('femaleAttendees');

        function validateTotals() {
            const total = parseInt(totalInput.value) || 0;
            const male = parseInt(maleInput.value) || 0;
            const female = parseInt(femaleInput.value) || 0;
            
            if (male + female !== total) {
                alert('The sum of male and female attendees must equal the total attendees.');
                return false;
            }
            return true;
        }

        document.getElementById('monthlyReportForm').addEventListener('submit', function(event) {
            if (!validateTotals()) {
                event.preventDefault();
            }
        });

        // Helper function to update totals when values change
        function updateTotal() {
            const male = parseInt(maleInput.value) || 0;
            const female = parseInt(femaleInput.value) || 0;
            totalInput.value = male + female;
        }

        maleInput.addEventListener('change', updateTotal);
        femaleInput.addEventListener('change', updateTotal);
    </script>
</body>
</html>