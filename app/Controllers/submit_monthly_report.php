<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Database connection
require '../../server.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve basic report data
    $reportMonth = sanitize_input($_POST['reportMonth'] ?? '');
    $reportYear = sanitize_input($_POST['reportYear'] ?? '');
    $reportDate = $reportYear . '-' . $reportMonth . '-01'; // First day of the month
    
    $totalAttendees = intval($_POST['totalAttendees'] ?? 0);
    $maleAttendees = intval($_POST['maleAttendees'] ?? 0);
    $femaleAttendees = intval($_POST['femaleAttendees'] ?? 0);
    $monthNarrative = sanitize_input($_POST['monthNarrative'] ?? '');
    $challenges = sanitize_input($_POST['challenges'] ?? '');
    
    // Age groups - convert to JSON for storage
    $ageGroups = isset($_POST['ageGroups']) ? json_encode($_POST['ageGroups']) : '{}';
    
    // First, check if a report for this month already exists
    $checkSql = "SELECT id FROM monthly_reports WHERE MONTH(report_date) = ? AND YEAR(report_date) = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $reportMonth, $reportYear);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Report exists, we'll update it
        $row = $result->fetch_assoc();
        $reportId = $row['id'];
        
        $updateSql = "UPDATE monthly_reports SET 
                     total_attendees = ?,
                     male_attendees = ?,
                     female_attendees = ?,
                     age_groups = ?,
                     narrative = ?,
                     challenges = ?,
                     updated_at = NOW()
                     WHERE id = ?";
                     
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("iiisssi", 
                               $totalAttendees, 
                               $maleAttendees, 
                               $femaleAttendees, 
                               $ageGroups, 
                               $monthNarrative,
                               $challenges,
                               $reportId);
        $updateStmt->execute();
        
        // Delete existing activities to replace with new ones
        $deleteActivitiesSql = "DELETE FROM monthly_report_activities WHERE report_id = ?";
        $deleteStmt = $conn->prepare($deleteActivitiesSql);
        $deleteStmt->bind_param("i", $reportId);
        $deleteStmt->execute();
    } else {
        // Create new report
        $insertSql = "INSERT INTO monthly_reports 
                     (report_date, total_attendees, male_attendees, female_attendees, age_groups, narrative, challenges, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                     
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("siiisss", 
                               $reportDate, 
                               $totalAttendees, 
                               $maleAttendees,
                               $femaleAttendees,
                               $ageGroups,
                               $monthNarrative,
                               $challenges);
        $insertStmt->execute();
        $reportId = $conn->insert_id;
    }
    
    // Process activities
    if (isset($_POST['activities']) && is_array($_POST['activities'])) {
        $activitySql = "INSERT INTO monthly_report_activities 
                       (report_id, program_id, participants, completed_projects, in_progress_projects, not_started_projects, narrative) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        
        foreach ($_POST['activities'] as $index => $activity) {
            $programId = intval($activity['program_id']);
            $participants = intval($activity['participants']);
            $completed = intval($activity['completed']);
            $inProgress = intval($activity['in_progress']);
            $notStarted = intval($activity['not_started']);
            $narrative = sanitize_input($activity['narrative']);
            
            $activityStmt->bind_param("iiiiiss", 
                                    $reportId, 
                                    $programId, 
                                    $participants, 
                                    $completed, 
                                    $inProgress, 
                                    $notStarted, 
                                    $narrative);
            $activityStmt->execute();
            $activityId = $conn->insert_id;
            
            // Handle image uploads for this activity
            if (isset($_FILES['activities']['name'][$index]['images']) && is_array($_FILES['activities']['name'][$index]['images'])) {
                $base_upload_dir = "../../public/assets/uploads/images/";
                $current_month = date('Y-m');
                $target_dir = $base_upload_dir . $current_month . '/';
                
                // Create the monthly directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $imageCount = count($_FILES['activities']['name'][$index]['images']);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    if ($_FILES['activities']['error'][$index]['images'][$i] == 0) {
                        $filename = uniqid() . '_' . basename($_FILES['activities']['name'][$index]['images'][$i]);
                        $target_file = $target_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['activities']['tmp_name'][$index]['images'][$i], $target_file)) {
                            // Store image reference in database
                            $imagePath = $current_month . '/' . $filename;
                            $imageSql = "INSERT INTO monthly_report_images (activity_id, image_path) VALUES (?, ?)";
                            $imageStmt = $conn->prepare($imageSql);
                            $imageStmt->bind_param("is", $activityId, $imagePath);
                            $imageStmt->execute();
                        }
                    }
                }
            }
        }
    }
    
    // Redirect to the monthly report view page
    header("Location: ../views/monthlyReportView.php?year=" . $reportYear . "&month=" . $reportMonth);
    exit;
} else {
    // Not a POST request, redirect to the form
    header("Location: ../views/monthlyReportForm.php");
    exit;
}
?>
