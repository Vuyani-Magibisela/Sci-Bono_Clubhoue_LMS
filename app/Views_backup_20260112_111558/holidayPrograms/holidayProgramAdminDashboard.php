<?php

// Start session if not already started

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



// Check if user is logged in and is admin

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] !== 'admin') {

    header("Location: ../../../login.php?redirect=app/Views/holidayPrograms/holidayProgramAdminDashboard.php");

    exit;

}



// Include required files

require_once '../../../server.php';

require_once '../../Controllers/HolidayProgramAdminController.php';

require __DIR__ . '/../../../config/config.php'; // Include the config file



// Initialize controller

$adminController = new HolidayProgramAdminController($conn);

function checkCohortCapacity($conn, $cohortId) {
    $sql = "SELECT c.max_participants, c.current_participants, c.status,
                   COUNT(a.id) as actual_registrations
            FROM holiday_program_cohorts c
            LEFT JOIN holiday_program_attendees a ON c.id = a.cohort_id
            WHERE c.id = ?
            GROUP BY c.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cohortId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cohort = $result->fetch_assoc();
    $stmt->close();
    
    if (!$cohort) {
        return ['available' => false, 'remaining' => 0];
    }
    
    // Use actual registrations count (more reliable than current_participants)
    $used = $cohort['actual_registrations'];
    $max = $cohort['max_participants'];
    $remaining = $max - $used;
    
    return [
        'available' => ($cohort['status'] === 'active' && $remaining > 0),
        'remaining' => max(0, $remaining),
        'total' => $max,
        'used' => $used
    ];
}

// Get capacity info for both cohorts
$cohort3Capacity = checkCohortCapacity($conn, 3);
$cohort4Capacity = checkCohortCapacity($conn, 4);


// Handle Schedule Manager form submissions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {

        switch($_POST['action']) {

            case 'update_program_structure':

                $result = updateProgramStructure($conn, $_POST);

                $message = $result['success'] ? 'Program structure updated successfully!' : $result['message'];

                $messageType = $result['success'] ? 'success' : 'error';

                break;

                

            case 'add_cohort':

                $result = addCohort($conn, $_POST);

                $message = $result['success'] ? 'Cohort added successfully!' : $result['message'];

                $messageType = $result['success'] ? 'success' : 'error';

                break;

                

            case 'update_workshop':

                $result = updateWorkshop($conn, $_POST);

                $message = $result['success'] ? 'Workshop updated successfully!' : $result['message'];

                $messageType = $result['success'] ? 'success' : 'error';

                break;

                

            case 'update_program_status':

                header('Content-Type: application/json');

                

                $programId = intval($_POST['program_id']);

                $registrationOpen = intval($_POST['registration_open']);

                

                try {

                    // Update program status

                    $updateSql = "UPDATE holiday_programs SET registration_open = ? WHERE id = ?";

                    $updateStmt = $conn->prepare($updateSql);

                    $updateStmt->bind_param("ii", $registrationOpen, $programId);

                    

                    if ($updateStmt->execute()) {

                        // Log the status change

                        $logSql = "INSERT INTO holiday_program_status_log (program_id, old_status, new_status, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())";

                        $logStmt = $conn->prepare($logSql);

                        $oldStatus = !$registrationOpen;

                        $logStmt->bind_param("iiii", $programId, $oldStatus, $registrationOpen, $_SESSION['user_id']);

                        $logStmt->execute();

                        

                        echo json_encode([

                            'success' => true,

                            'message' => 'Program status updated successfully'

                        ]);

                    } else {

                        echo json_encode([

                            'success' => false,

                            'message' => 'Failed to update program status'

                        ]);

                    }

                } catch (Exception $e) {

                    echo json_encode([

                        'success' => false,

                        'message' => 'Error: ' . $e->getMessage()

                    ]);

                }

                exit;

                

            case 'update_registration_status':

                header('Content-Type: application/json');

                

                $attendeeId = intval($_POST['attendee_id']);

                $status = $_POST['status'];

                

                try {

                    $validStatuses = ['pending', 'confirmed', 'canceled'];

                    if (!in_array($status, $validStatuses)) {

                        throw new Exception('Invalid status');

                    }

                    

                    $updateSql = "UPDATE holiday_program_attendees SET registration_status = ?, updated_at = NOW() WHERE id = ?";

                    $updateStmt = $conn->prepare($updateSql);

                    $updateStmt->bind_param("si", $status, $attendeeId);

                    

                    if ($updateStmt->execute()) {

                        echo json_encode([

                            'success' => true,

                            'message' => 'Registration status updated successfully'

                        ]);

                    } else {

                        echo json_encode([

                            'success' => false,

                            'message' => 'Failed to update registration status'

                        ]);

                    }

                } catch (Exception $e) {

                    echo json_encode([

                        'success' => false,

                        'message' => 'Error: ' . $e->getMessage()

                    ]);

                }

                exit;

                

            case 'update_mentor_status':

                header('Content-Type: application/json');

                

                $attendeeId = intval($_POST['attendee_id']);

                $status = $_POST['status'];

                

                try {

                    $validStatuses = ['Pending', 'Approved', 'Declined'];

                    if (!in_array($status, $validStatuses)) {

                        throw new Exception('Invalid mentor status');

                    }

                    

                    $updateSql = "UPDATE holiday_program_attendees SET mentor_status = ?, updated_at = NOW() WHERE id = ?";

                    $updateStmt = $conn->prepare($updateSql);

                    $updateStmt->bind_param("si", $status, $attendeeId);

                    

                    if ($updateStmt->execute()) {

                        echo json_encode([

                            'success' => true,

                            'message' => 'Mentor status updated successfully'

                        ]);

                    } else {

                        echo json_encode([

                            'success' => false,

                            'message' => 'Failed to update mentor status'

                        ]);

                    }

                } catch (Exception $e) {

                    echo json_encode([

                        'success' => false,

                        'message' => 'Error: ' . $e->getMessage()

                    ]);

                }

                exit;

                

            case 'get_attendee_details':

                header('Content-Type: application/json');

                

                $attendeeId = intval($_POST['attendee_id']);

                

                try {

                    $sql = "SELECT a.*, 

                                   GROUP_CONCAT(w.title SEPARATOR ', ') as enrolled_workshops

                            FROM holiday_program_attendees a

                            LEFT JOIN holiday_workshop_enrollment we ON a.id = we.attendee_id

                            LEFT JOIN holiday_program_workshops w ON we.workshop_id = w.id

                            WHERE a.id = ?

                            GROUP BY a.id";

                    

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param("i", $attendeeId);

                    $stmt->execute();

                    $result = $stmt->get_result();

                    

                    if ($attendee = $result->fetch_assoc()) {

                        echo json_encode([

                            'success' => true,

                            'data' => $attendee

                        ]);

                    } else {

                        echo json_encode([

                            'success' => false,

                            'message' => 'Attendee not found'

                        ]);

                    }

                } catch (Exception $e) {

                    echo json_encode([

                        'success' => false,

                        'message' => 'Error: ' . $e->getMessage()

                    ]);

                }

                exit;

                

            case 'send_bulk_email':

                header('Content-Type: application/json');

                

                $programId = intval($_POST['program_id']);

                $recipients = $_POST['recipients'];

                $subject = $_POST['subject'];

                $message = $_POST['message'];

                

                try {

                    // Get recipient emails based on selection

                    $sql = "SELECT email FROM holiday_program_attendees WHERE program_id = ?";

                    $params = [$programId];

                    

                    if ($recipients === 'members') {

                        $sql .= " AND mentor_registration = 0";

                    } elseif ($recipients === 'mentors') {

                        $sql .= " AND mentor_registration = 1";

                    } elseif ($recipients === 'confirmed') {

                        $sql .= " AND status = 'confirmed'";

                    }

                    

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param("i", ...$params);

                    $stmt->execute();

                    $result = $stmt->get_result();

                    

                    $emails = [];

                    while ($row = $result->fetch_assoc()) {

                        $emails[] = $row['email'];

                    }

                    

                    // Here you would integrate with your email system

                    // For now, we'll just return success

                    echo json_encode([

                        'success' => true,

                        'message' => 'Emails queued successfully',

                        'recipients_count' => count($emails)

                    ]);

                    

                } catch (Exception $e) {

                    echo json_encode([

                        'success' => false,

                        'message' => 'Error: ' . $e->getMessage()

                    ]);

                }

                exit;

                

            default:

                // Handle other actions via the controller

                $adminController->handleAjaxRequest();

                exit;

         }

    }

}



// Handle GET actions

if (isset($_GET['action'])) {

    switch($_GET['action']) {

        case 'get_status_history':

            header('Content-Type: application/json');

            

            $programId = intval($_GET['program_id']);

            

            $historySql = "SELECT psl.*, u.name as changed_by_name 

                           FROM holiday_program_status_log psl 

                           LEFT JOIN users u ON psl.changed_by = u.id 

                           WHERE psl.program_id = ? 

                           ORDER BY psl.created_at DESC 

                           LIMIT 10";

            $historyStmt = $conn->prepare($historySql);

            $historyStmt->bind_param("i", $programId);

            $historyStmt->execute();

            $historyResult = $historyStmt->get_result();

            

            $history = [];

            while ($row = $historyResult->fetch_assoc()) {

                $history[] = $row;

            }

            

            echo json_encode($history);

            exit;

    }

}



// Handle CSV export

if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['program_id'])) {

    $programId = intval($_GET['program_id']);

    $adminController->exportRegistrations($programId, 'csv');

    exit;

}



// Functions for handling program configuration

function updateProgramStructure($conn, $data) {

    try {

        $programId = intval($data['program_id']);

        $duration = intval($data['duration']);

        $structure = json_encode([

            'duration_weeks' => $duration,

            'cohort_system' => isset($data['enable_cohorts']),

            'prerequisites_enabled' => isset($data['enable_prerequisites']),

            'updated_at' => date('Y-m-d H:i:s')

        ]);

        

        $sql = "UPDATE holiday_programs SET program_structure = ?, updated_at = NOW() WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("si", $structure, $programId);

        

        return ['success' => $stmt->execute()];

    } catch (Exception $e) {

        return ['success' => false, 'message' => $e->getMessage()];

    }

}



function addCohort($conn, $data) {

    try {

        $programId = intval($data['program_id']);

        $cohortName = trim($data['cohort_name']);

        $startDate = $data['cohort_start_date'];

        $endDate = $data['cohort_end_date'];

        $maxParticipants = intval($data['cohort_capacity']);

        

        $sql = "INSERT INTO holiday_program_cohorts (program_id, name, start_date, end_date, max_participants, created_at) VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("isssi", $programId, $cohortName, $startDate, $endDate, $maxParticipants);

        

        return ['success' => $stmt->execute()];

    } catch (Exception $e) {

        return ['success' => false, 'message' => $e->getMessage()];

    }

}



function updateWorkshop($conn, $data) {

    try {

        $workshopId = intval($data['workshop_id']);

        $title = trim($data['workshop_title']);

        $description = trim($data['workshop_description']);

        $instructor = trim($data['workshop_instructor']);

        $maxParticipants = intval($data['workshop_capacity']);

        $prerequisites = trim($data['workshop_prerequisites']);

        

        $sql = "UPDATE holiday_program_workshops SET title = ?, description = ?, instructor = ?, max_participants = ?, prerequisites = ?, updated_at = NOW() WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("sssisi", $title, $description, $instructor, $maxParticipants, $prerequisites, $workshopId);

        

        return ['success' => $stmt->execute()];

    } catch (Exception $e) {

        return ['success' => false, 'message' => $e->getMessage()];

    }

}



// Get program structure data

function getProgramStructure($conn, $programId) {

    $sql = "SELECT program_structure FROM holiday_programs WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $programId);

    $stmt->execute();

    $result = $stmt->get_result();

    $program = $result->fetch_assoc();

    

    // Handle null or empty program structure

    if (!$program || empty($program['program_structure'])) {

        return [

            'duration_weeks' => 2,

            'cohort_system' => false,

            'prerequisites_enabled' => false

        ];

    }

    

    $structure = json_decode($program['program_structure'], true);

    

    // Return default values if json_decode failed

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($structure)) {

        return [

            'duration_weeks' => 2,

            'cohort_system' => false,

            'prerequisites_enabled' => false

        ];

    }

    

    return $structure;

}



// Get cohorts for program

function getProgramCohorts($conn, $programId) {

    $sql = "SELECT * FROM holiday_program_cohorts WHERE program_id = ? ORDER BY start_date";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $programId);

    $stmt->execute();

    $result = $stmt->get_result();

    

    $cohorts = [];

    while ($row = $result->fetch_assoc()) {

        $cohorts[] = $row;

    }

    return $cohorts;

}



// Get current program ID

$currentProgramId = isset($_GET['program_id']) ? intval($_GET['program_id']) : null;



// Get dashboard data

$dashboardData = $adminController->getDashboardData($currentProgramId);

extract($dashboardData);



// Initialize default variables if not set in dashboard data

$programs = $programs ?? [];

$current_program = $current_program ?? null;

$registrations = $registrations ?? [];

$workshops = $workshops ?? [];

$stats = $stats ?? [];

$capacity_info = $capacity_info ?? null;



$programStructure = $current_program ? getProgramStructure($conn, $current_program['id']) : [];

$cohorts = $current_program ? getProgramCohorts($conn, $current_program['id']) : [];



?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Holiday Program Admin Dashboard - Sci-Bono Clubhouse</title>

    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">

    <link rel="stylesheet" href="../../../public/assets/css/holidayDahsboard.css">

    <!-- Font Awesome for icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js for statistics -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    

    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }

        

        body {

            font-family: 'Poppins', sans-serif;

            background: #f8f9fa;

            color: #333;

        }

        

        /* Main layout container fixes */

        .admin-dashboard {

            min-height: 100vh;

            padding: 20px 0;

            position: relative;

        }

        

        /* Ensure proper stacking and spacing */

        .admin-dashboard .container > * {

            position: relative;

            z-index: 2;

            clear: both;

        }

        

        /* Fix program selector overlap */

        .program-selector {

            background: rgba(255, 255, 255, 0.1);

            padding: 20px;

            border-radius: 10px;

            margin-top: 20px;

            position: relative;

            z-index: 15;

        }

        

        /* Ensure schedule manager doesn't overlap with header */

        .schedule-manager {

            margin-top: 0;

            position: static;

        }

        

        /* Content spacing fixes */

        .dashboard-content {

            margin-top: 30px;

            position: relative;

            z-index: 2;

        }

        

        .container {

            max-width: 1200px;

            margin: 0 auto;

            padding: 0 20px;

            position: relative;

            z-index: 1;

        }

        

        .dashboard-header {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            color: white;

            padding: 30px 0;

            margin-bottom: 30px;

            position: relative;

            z-index: 10;

        }

        

        .dashboard-header h1 {

            margin: 0 0 10px 0;

            font-size: 2.2rem;

            font-weight: 600;

        }

        

        .dashboard-header p {

            margin: 0;

            opacity: 0.9;

        }

        

        .program-selector {

            background: rgba(255, 255, 255, 0.1);

            padding: 20px;

            border-radius: 10px;

            margin-top: 20px;

        }

        

        .selector-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            flex-wrap: wrap;

        }

        

        .selector-left {

            flex: 1;

            min-width: 300px;

        }

        

        .selector-left label {

            display: block;

            margin-bottom: 10px;

            font-weight: 500;

        }

        

        .selector-left select {

            width: 100%;

            padding: 12px 16px;

            border: none;

            border-radius: 8px;

            font-size: 16px;

            background: white;

            color: #333;

        }

        

        .selector-right {

            display: flex;

            gap: 10px;

            align-items: flex-end;

        }

        

        .quick-stats {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

            margin: 30px 0;

            clear: both;

            position: relative;

            z-index: 3;

        }

        

        .stat-card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            border-left: 4px solid #667eea;

            position: relative;

            z-index: 3;

        }

        

        .stat-card.success { border-left-color: #28a745; }

        .stat-card.warning { border-left-color: #ffc107; }

        .stat-card.danger { border-left-color: #dc3545; }

        .stat-card.info { border-left-color: #17a2b8; }

        

        .stat-value {

            font-size: 2.5rem;

            font-weight: 700;

            color: #333;

            margin-bottom: 5px;

        }

        

        .stat-label {

            color: #666;

            font-size: 0.9rem;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }

        

        .status-section {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            margin-bottom: 30px;

        }

        

        .status-section h3 {

            margin: 0 0 20px 0;

            color: #333;

            display: flex;

            align-items: center;

            gap: 10px;

        }

        

        .status-controls {

            display: flex;

            align-items: center;

            gap: 20px;

            flex-wrap: wrap;

        }

        

        .status-group {

            display: flex;

            align-items: center;

            gap: 10px;

        }

        

        .status-group label {

            font-weight: 500;

            color: #555;

        }

        

        .status-group select {

            padding: 8px 12px;

            border: 2px solid #e9ecef;

            border-radius: 6px;

            font-size: 14px;

        }

        

        .action-buttons {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }

        

        .status-badge {

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 0.85rem;

            font-weight: 500;

            text-transform: capitalize;

        }

        

        .status-badge.confirmed { 

            background: #d4edda; 

            color: #155724; 

        }

        

        .status-badge.canceled { 

            background: #f8d7da; 

            color: #721c24; 

        }

        

        .status-info {

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #e9ecef;

            font-size: 0.9rem;

            color: #666;

        }

        

        .capacity-section {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            margin-bottom: 30px;

        }

        

        .capacity-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 30px;

            margin-top: 15px;

        }

        

        .capacity-item {

            display: flex;

            justify-content: space-between;

            margin-bottom: 8px;

        }

        

        .capacity-bar {

            width: 100%;

            height: 20px;

            background: #e9ecef;

            border-radius: 10px;

            overflow: hidden;

            margin: 8px 0;

        }

        

        .capacity-fill {

            height: 100%;

            background: linear-gradient(90deg, #28a745, #20c997);

            transition: width 0.3s ease;

        }

        

        .capacity-fill.warning { 

            background: linear-gradient(90deg, #ffc107, #fd7e14); 

        }

        

        .capacity-fill.danger { 

            background: linear-gradient(90deg, #dc3545, #e83e8c); 

        }

        

        .dashboard-tabs {

            background: white;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            overflow: hidden;

            border: 1px solid #e9ecef;

        }

        

        .tab-navigation {

            display: flex;

            background: #f8f9fa;

            border-bottom: 1px solid #dee2e6;

            overflow-x: auto;

        }

        

        .tab-btn {

            padding: 15px 25px;

            background: none;

            border: none;

            cursor: pointer;

            font-weight: 500;

            color: #6c757d;

            transition: all 0.3s;

            border-bottom: 3px solid transparent;

            white-space: nowrap;

            font-size: 14px;

        }

        

        .tab-btn:hover {

            color: #495057;

            background: rgba(102, 126, 234, 0.05);

        }

        

        .tab-btn.active {

            color: #667eea;

            border-bottom-color: #667eea;

            background: white;

            font-weight: 600;

        }

        

        .tab-content {

            display: none;

            padding: 30px;

            min-height: 400px;

            background: white;

        }

        

        .tab-content.active {

            display: block;

        }

        

        .tab-content h3 {

            color: #495057;

            margin-bottom: 15px;

            font-size: 1.4rem;

            font-weight: 600;

        }

        

        .tab-content p {

            color: #6c757d;

            margin-bottom: 20px;

        }

        

        .action-btn {

            padding: 10px 16px;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            font-size: 0.9rem;

            font-weight: 500;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 6px;

            transition: all 0.3s;

            width: auto;

        }

        

        .action-btn.primary { 

            background: #007bff; 

            color: white; 

        }

        

        .action-btn.success { 

            background: #28a745; 

            color: white; 

        }

        

        .action-btn.warning { 

            background: #ffc107; 

            color: #212529; 

        }

        

        .action-btn.danger { 

            background: #dc3545; 

            color: white; 

        }

        

        .action-btn:hover {

            transform: translateY(-1px);

            box-shadow: 0 2px 5px rgba(0,0,0,0.2);

        }

        

        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: #6c757d;

        }

        

        .empty-state h2 {

            font-size: 1.8rem;

            margin-bottom: 15px;

            color: #495057;

        }



        /* NEW SCHEDULE MANAGER STYLES - Fixed positioning and layout */

        .schedule-manager {

            background: white;

            border-radius: 12px;

            padding: 30px;

            margin: 0 0 30px 0;

            box-shadow: 0 4px 12px rgba(0,0,0,0.1);

            border: 1px solid #e1e8ed;

            position: relative;

            z-index: 5;

            width: 100%;

            max-width: 100%;

        }

        

        .schedule-manager h2 {

            color: #495057;

            margin-bottom: 25px;

            padding-bottom: 15px;

            border-bottom: 3px solid #667eea;

            font-size: 1.5rem;

            font-weight: 600;

        }

        

        .config-section {

            margin: 25px 0;

            padding: 20px;

            background: #f8f9fa;

            border-radius: 8px;

            border-left: 4px solid #667eea;

            border: 1px solid #e9ecef;

            clear: both;

            width: 100%;

        }

        

        .config-section h3 {

            margin-top: 0;

            margin-bottom: 20px;

            color: #495057;

            font-size: 1.2rem;

            font-weight: 600;

        }

        

        .form-group {

            margin: 15px 0;

        }

        

        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-weight: 500;

            color: #495057;

            font-size: 14px;

        }

        

        .form-control {

            width: 100%;

            padding: 10px 15px;

            border: 2px solid #e9ecef;

            border-radius: 6px;

            font-size: 14px;

            transition: border-color 0.3s;

            background: white;

            color: #495057;

        }

        

        .form-control:focus {

            outline: none;

            border-color: #667eea;

            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);

        }

        

        .form-control::placeholder {

            color: #6c757d;

        }

        

        .btn {

            padding: 10px 20px;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            transition: all 0.3s;

            font-size: 14px;

        }

        

        .btn-primary {

            background: #667eea;

            color: white;

        }

        

        .btn-primary:hover {

            background: #5a52d5;

            transform: translateY(-1px);

            box-shadow: 0 2px 5px rgba(0,0,0,0.2);

        }

        

        .btn-success {

            background: #28a745;

            color: white;

        }

        

        .btn-success:hover {

            background: #218838;

            transform: translateY(-1px);

            box-shadow: 0 2px 5px rgba(0,0,0,0.2);

        }

        

        .btn-secondary {

            background: #6c757d;

            color: white;

        }

        

        .btn-secondary:hover {

            background: #545b62;

        }

        

        .cohort-item, .workshop-item {

            background: white;

            padding: 20px;

            margin: 15px 0;

            border-radius: 8px;

            border: 1px solid #dee2e6;

            box-shadow: 0 2px 4px rgba(0,0,0,0.05);

        }

        

        .cohort-item h4, .workshop-item h4 {

            margin: 0 0 10px 0;

            color: #495057;

            font-size: 1.1rem;

        }

        

        .cohort-item p, .workshop-item p {

            margin: 5px 0;

            color: #6c757d;

            font-size: 14px;

        }

        

        .two-column {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }

        

        .alert {

            padding: 15px 20px;

            margin: 15px 0;

            border-radius: 8px;

            border-left: 4px solid;

            font-size: 14px;

        }

        

        .alert-success {

            background: #d4edda;

            border-color: #28a745;

            color: #155724;

        }

        

        .alert-error {

            background: #f8d7da;

            border-color: #dc3545;

            color: #721c24;

        }

        

        .checkbox-group {

            display: flex;

            align-items: center;

            gap: 10px;

            margin: 10px 0;

        }

        

        .checkbox-group input[type="checkbox"] {

            width: auto;

            margin: 0;

        }

        

        .checkbox-group label {

            margin: 0;

            font-weight: 500;

            color: #495057;

            cursor: pointer;

        }

        

        /* Workshop form specific styles */

        .workshop-form {

            border-top: 1px solid #e9ecef;

            padding-top: 20px;

            margin-top: 15px;

        }

        

        .workshop-form:first-child {

            border-top: none;

            padding-top: 0;

            margin-top: 0;

        }

        

        .add-cohort-form {

            background: white;

            padding: 20px;

            border-radius: 8px;

            border: 1px solid #e9ecef;

            margin-top: 15px;

        }

        

        /* Improved textarea styling */

        textarea.form-control {

            resize: vertical;

            min-height: 80px;

        }

        

        /* Better spacing for form elements */

        .config-section .form-group:last-child {

            margin-bottom: 20px;

        }

        

        /* Cohorts list styling */

        .cohorts-list {

            margin-bottom: 20px;

        }

        

        .cohorts-list:empty::after {

            content: "No cohorts have been added yet. Add your first cohort below.";

            color: #6c757d;

            font-style: italic;

            display: block;

            text-align: center;

            padding: 20px;

            background: #f8f9fa;

            border-radius: 6px;

            border: 1px solid #e9ecef;

        }

        

        .workshops-list {

            margin-bottom: 20px;

        }



        /* Table and content styling fixes */

        #registrations-table {

            width: 100%;

            border-collapse: collapse;

            background: white;

            border-radius: 8px;

            overflow: hidden;

            box-shadow: 0 2px 10px rgba(0,0,0,0.05);

            border: 1px solid #e9ecef;

        }

        

        #registrations-table thead tr {

            background: #f8f9fa;

        }

        

        #registrations-table th {

            padding: 12px 15px;

            text-align: left;

            border-bottom: 2px solid #dee2e6;

            color: #495057;

            font-weight: 600;

            font-size: 14px;

        }

        

        #registrations-table td {

            padding: 12px 15px;

            border-bottom: 1px solid #f1f3f4;

            color: #495057;

            font-size: 14px;

        }

        

        #registrations-table tbody tr:hover {

            background: #f8f9fa;

        }

        

        /* Search and filter inputs */

        #search-registrations,

        #filter-status,

        #filter-type {

            padding: 10px 15px;

            border: 2px solid #e9ecef;

            border-radius: 6px;

            font-size: 14px;

            color: #495057;

            background: white;

        }

        

        #search-registrations:focus,

        #filter-status:focus,

        #filter-type:focus {

            outline: none;

            border-color: #667eea;

            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);

        }

        

        #search-registrations::placeholder {

            color: #6c757d;

        }

        

        /* Status badges - ensure proper contrast */

        .status-badge {

            padding: 4px 10px;

            border-radius: 12px;

            font-size: 12px;

            font-weight: 600;

            text-transform: capitalize;

            display: inline-block;

        }

        

        .status-badge.confirmed {

            background: #d4edda;

            color: #155724;

            border: 1px solid #c3e6cb;

        }

        

        .status-badge.pending {

            background: #fff3cd;

            color: #856404;

            border: 1px solid #ffeaa7;

        }

        

        .status-badge.canceled {

            background: #f8d7da;

            color: #721c24;

            border: 1px solid #f5c6cb;

        }

        

        /* Statistics cards styling */

        .stat-card h4 {

            color: #495057;

            margin-bottom: 15px;

            font-size: 1.1rem;

            font-weight: 600;

        }

        

        .stat-card p {

            color: #6c757d;

            margin: 5px 0;

            font-size: 14px;

        }

        

        .stat-card strong {

            color: #495057;

        }

        

        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: #6c757d;

            background: white;

            border-radius: 8px;

            border: 1px solid #e9ecef;

            margin: 30px auto;

            max-width: 600px;

            position: relative;

            z-index: 3;

        }

        

        .empty-state h2,

        .empty-state h3 {

            color: #495057;

            margin-bottom: 15px;

        }

        

        /* Action buttons in tables */

        .action-btn {

            padding: 6px 12px;

            border: none;

            border-radius: 4px;

            cursor: pointer;

            font-size: 12px;

            font-weight: 500;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 4px;

            transition: all 0.3s;

            margin: 2px;

        }

        

        .action-btn:hover {

            transform: translateY(-1px);

            box-shadow: 0 2px 5px rgba(0,0,0,0.2);

        }

        

        /* Ensure all text in content areas has proper color */

        .tab-content * {

            color: inherit;

        }

        

        .config-section * {

            color: inherit;

        }

        

        /* Workshop and cohort item text */

        .workshop-item .form-control,

        .cohort-item .form-control {

            color: #495057;

        }

        

        .workshop-item label,

        .cohort-item label,

        .add-cohort-form label {

            color: #495057;

        }

        

        @media (max-width: 768px) {

            .selector-row {

                flex-direction: column;

            }

            

            .selector-right {

                width: 100%;

                justify-content: flex-start;

            }

            

            .quick-stats {

                grid-template-columns: 1fr;

            }

            

            .capacity-grid {

                grid-template-columns: 1fr;

            }

            

            .status-controls {

                flex-direction: column;

                align-items: flex-start;

            }

            

            .action-buttons {

                width: 100%;

            }

            

            .tab-navigation {

                flex-wrap: wrap;

            }

            

            .two-column {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>

<body>

    <?php include './holidayPrograms-header.php'; ?>

    

    <div class="admin-dashboard">

        <!-- Dashboard Header -->

        <div class="dashboard-header">

            <div class="container">

                <h1><i class="fas fa-cogs"></i> Holiday Program Admin Dashboard</h1>

                <p>Manage holiday programs, registrations, and participants</p>

                

                <?php if (!empty($programs)): ?>

                <div class="program-selector">

                    <div class="selector-row">

                        <div class="selector-left">

                            <label for="program-select"><i class="fas fa-calendar-alt"></i> Select Program:</label>

                            <select id="program-select" onchange="window.location.href='?program_id='+this.value">

                                <option value="">Choose a program...</option>

                                <?php foreach ($programs as $program): ?>

                                    <option value="<?php echo $program['id']; ?>" 

                                        <?php echo ($current_program && $program['id'] == $current_program['id']) ? 'selected' : ''; ?>>

                                        <?php echo htmlspecialchars($program['term'] . ': ' . $program['title']); ?>

                                        (<?php echo $program['total_registrations']; ?> registrations)

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        

                        <div class="selector-right">

                            <a href="holidayProgramCreationForm.php" class="action-btn success">

                                <i class="fas fa-plus-circle"></i> Add New Program

                            </a>

                            <?php if ($current_program): ?>

                                <a href="holidayProgramCreationForm.php?edit=1&program_id=<?php echo $current_program['id']; ?>" class="action-btn warning">

                                    <i class="fas fa-edit"></i> Edit Program

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <?php endif; ?>

            </div>

        </div>

        

        <div class="container">

            <?php if (!$current_program): ?>

                <div class="empty-state">

                    <h2>No Program Selected</h2>

                    <p>Please select a holiday program from the dropdown above to view its dashboard.</p>

                </div>

            <?php else: ?>

                

                <?php if (isset($message)): ?>

                    <div class="alert alert-<?php echo $messageType; ?>">

                        <?php echo htmlspecialchars($message); ?>

                    </div>

                <?php endif; ?>

                <div class="dashboard-content">

                    <!-- Quick Stats -->

                    <div class="quick-stats">

                        <div class="stat-card info">

                            <div class="stat-value"><?php echo $stats['total_registrations'] ?? 0; ?></div>

                            <div class="stat-label">Total Registrations</div>

                        </div>

                        <div class="stat-card success">

                            <div class="stat-value"><?php echo $stats['confirmed_registrations'] ?? 0; ?></div>

                            <div class="stat-label">Confirmed</div>

                        </div>

                        <div class="stat-card warning">

                            <div class="stat-value"><?php echo $stats['pending_registrations'] ?? 0; ?></div>

                            <div class="stat-label">Pending</div>

                        </div>

                        <div class="stat-card danger">

                            <div class="stat-value"><?php echo $stats['mentor_applications'] ?? 0; ?></div>

                            <div class="stat-label">Mentor Applications</div>

                        </div>

                    </div>

                    

                    <!-- Program Status Control -->

                    <div class="status-section">

                        <h3><i class="fas fa-toggle-on"></i> Program Status</h3>

                        <div class="status-controls">

                            <div class="status-group">

                                <label for="registration-status">Registration Status:</label>

                                <select id="registration-status" onchange="updateProgramStatus()">

                                    <option value="0" <?php echo !$current_program['registration_open'] ? 'selected' : ''; ?>>Closed</option>

                                    <option value="1" <?php echo $current_program['registration_open'] ? 'selected' : ''; ?>>Open</option>

                                </select>

                                <span id="status-indicator" class="status-badge <?php echo $current_program['registration_open'] ? 'confirmed' : 'canceled'; ?>">

                                    <?php echo $current_program['registration_open'] ? 'Registration Open' : 'Registration Closed'; ?>

                                </span>

                            </div>

                            <div class="action-buttons">

                                <button class="action-btn primary" onclick="showBulkEmailModal()">

                                    <i class="fas fa-envelope"></i> Send Bulk Email

                                </button>

                                <button class="action-btn success" onclick="exportRegistrations()">

                                    <i class="fas fa-download"></i> Export CSV

                                </button>

                            </div>

                        </div>

                        <div class="status-info">

                            <p><strong>Current Status:</strong> 

                                <?php if ($current_program['registration_open']): ?>

                                    <span style="color: #28a745;">✓ Participants can register for this program</span>

                                <?php else: ?>

                                    <span style="color: #dc3545;">✗ Registration is currently closed</span>

                                <?php endif; ?>

                            </p>

                            <p><strong>Program Period:</strong> <?php echo htmlspecialchars($current_program['dates']); ?></p>

                        </div>

                    </div>

                    

                    <!-- Capacity Overview -->

                    <?php if ($capacity_info): ?>

                    <div class="capacity-section">

                        <h3><i class="fas fa-users"></i> Capacity Overview</h3>

                        <div class="capacity-grid">

                            <div>

                                <div class="capacity-item">

                                    <span>Members:</span>

                                    <span><?php echo $capacity_info['member_registered']; ?>/<?php echo $capacity_info['member_capacity']; ?></span>

                                </div>

                                <div class="capacity-bar">

                                    <div class="capacity-fill <?php echo $capacity_info['member_percentage'] > 80 ? 'warning' : ''; ?> <?php echo $capacity_info['is_member_full'] ? 'danger' : ''; ?>" 

                                         style="width: <?php echo min($capacity_info['member_percentage'], 100); ?>%;"></div>

                                </div>

                            </div>

                            <div>

                                <div class="capacity-item">

                                    <span>Mentors:</span>

                                    <span><?php echo $capacity_info['mentor_registered']; ?>/<?php echo $capacity_info['mentor_capacity']; ?></span>

                                </div>

                                <div class="capacity-bar">

                                    <div class="capacity-fill <?php echo $capacity_info['mentor_percentage'] > 80 ? 'warning' : ''; ?> <?php echo $capacity_info['is_mentor_full'] ? 'danger' : ''; ?>" 

                                         style="width: <?php echo min($capacity_info['mentor_percentage'], 100); ?>%;"></div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <?php endif; ?>

                    

                    <!-- Dashboard Tabs -->

                    <div class="dashboard-tabs">

                        <div class="tab-navigation">

                            <button class="tab-btn active" onclick="showTab('registrations')">

                                <i class="fas fa-list"></i> Registrations (<?php echo count($registrations); ?>)

                            </button>

                            <button class="tab-btn" onclick="showTab('workshops')">

                                <i class="fas fa-laptop-code"></i> Workshops (<?php echo count($workshops); ?>)

                            </button>

                            <button class="tab-btn" onclick="showTab('statistics')">

                                <i class="fas fa-chart-bar"></i> Statistics

                            </button>

                            <button class="tab-btn" onclick="showTab('mentors')">

                                <i class="fas fa-chalkboard-teacher"></i> Mentors (<?php echo $stats['mentor_applications'] ?? 0; ?>)

                            </button>

                        </div>

                        

                        <!-- Registrations Tab -->

                        <div id="registrations" class="tab-content active">

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">

                                <h3>All Registrations</h3>

                                <div style="display: flex; gap: 10px;">

                                    <button class="action-btn primary" onclick="showBulkActions()">

                                        <i class="fas fa-tasks"></i> Bulk Actions

                                    </button>

                                    <button class="action-btn success" onclick="exportRegistrations()">

                                        <i class="fas fa-download"></i> Export CSV

                                    </button>

                                </div>

                            </div>

                            

                            <!-- Search and Filters -->

                            <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">

                                <input type="text" id="search-registrations" placeholder="Search by name or email..." 

                                       onkeyup="filterRegistrations()" 

                                       style="padding: 10px; border: 2px solid #e9ecef; border-radius: 6px; min-width: 250px;">

                                <select id="filter-status" onchange="filterRegistrations()" 

                                        style="padding: 10px; border: 2px solid #e9ecef; border-radius: 6px;">

                                    <option value="">All Statuses</option>

                                    <option value="pending">Pending</option>

                                    <option value="confirmed">Confirmed</option>

                                    <option value="canceled">Canceled</option>

                                </select>

                                <select id="filter-type" onchange="filterRegistrations()" 

                                        style="padding: 10px; border: 2px solid #e9ecef; border-radius: 6px;">

                                    <option value="">All Types</option>

                                    <option value="member">Members</option>

                                    <option value="mentor">Mentors</option>

                                </select>

                            </div>

                            

                            <?php if (empty($registrations)): ?>

                                <div class="empty-state">

                                    <h3>No Registrations Yet</h3>

                                    <p>This program doesn't have any registrations yet.</p>

                                </div>

                            <?php else: ?>

                                <div style="overflow-x: auto; margin-top: 20px;">

                                    <table id="registrations-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">

                                        <thead>

                                            <tr style="background: #f8f9fa;">

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Name</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Email</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Type</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Status</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Workshops</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Date</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Actions</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php foreach ($registrations as $registration): ?>

                                            <tr data-id="<?php echo $registration['id']; ?>" 

                                                data-status="<?php echo $registration['registration_status']; ?>"

                                                data-type="<?php echo $registration['mentor_registration'] ? 'mentor' : 'member'; ?>"

                                                style="border-bottom: 1px solid #f1f3f4;">

                                                <td style="padding: 12px;"><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></td>

                                                <td style="padding: 12px;"><?php echo htmlspecialchars($registration['email']); ?></td>

                                                <td style="padding: 12px;">

                                                    <?php if ($registration['mentor_registration']): ?>

                                                        <span class="status-badge" style="background: #d1ecf1; color: #0c5460;">Mentor</span>

                                                    <?php else: ?>

                                                        <span class="status-badge" style="background: #e7f3ff; color: #004085;">Member</span>

                                                    <?php endif; ?>

                                                </td>

                                                <td style="padding: 12px;">

                                                    <span class="status-badge <?php echo $registration['registration_status']; ?>">

                                                        <?php echo ucfirst($registration['registration_status']); ?>

                                                    </span>

                                                </td>

                                                <td style="padding: 12px;"><?php echo $registration['assigned_workshops'] ?? 'Not assigned'; ?></td>

                                                <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></td>

                                                <td style="padding: 12px;">
                                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">

                                                        <!-- View Attendee Button -->
                                                        <button class="action-btn" onclick="viewAttendee(<?php echo $registration['id']; ?>)"
                                                                style="background: #17a2b8; color: white; padding: 6px 10px; font-size: 0.8rem;">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <!-- View/Edit Profile Link -->
                                                        <a href="holiday-profile.php?user_id=<?php echo $registration['id']; ?>" 
                                                        class="action-btn view-profile" 
                                                        title="View/Edit Profile" 
                                                        style="background: #6c757d; color: white; padding: 6px 10px; font-size: 0.8rem; display: flex; align-items: center;">
                                                            <i class="fas fa-user-edit"></i>
                                                        </a>

                                                        <!-- Send Profile Email Button -->
                                                        <button onclick="sendProfileEmail(<?php echo $registration['id']; ?>)" 
                                                                class="action-btn send-email" 
                                                                title="Send Profile Access Email" 
                                                                style="background: #ffc107; color: black; padding: 6px 10px; font-size: 0.8rem;">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>

                                                        <!-- Conditional Buttons for Pending Status -->
                                                        <?php if ($registration['registration_status'] === 'pending'): ?>
                                                            <button class="action-btn success" onclick="updateStatus(<?php echo $registration['id']; ?>, 'confirmed')" 
                                                                    style="padding: 6px 10px; font-size: 0.8rem;">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="action-btn danger" onclick="updateStatus(<?php echo $registration['id']; ?>, 'canceled')" 
                                                                    style="padding: 6px 10px; font-size: 0.8rem;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                    </div>
                                                </td>

                                            </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>

                            <?php endif; ?>

                        </div>

                        

                        <!-- Workshops Tab -->

                        <div id="workshops" class="tab-content">

                            <h3>Workshop Management</h3>

                            <p>Monitor workshop capacity and assignments.</p>

                            

                            <?php if (empty($workshops)): ?>

                                <div class="empty-state">

                                    <h3>No Workshops</h3>

                                    <p>No workshops have been set up for this program yet.</p>

                                </div>

                            <?php else: ?>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">

                                    <?php foreach ($workshops as $workshop): ?>

                                    <div class="stat-card">

                                        <h4><?php echo htmlspecialchars($workshop['title']); ?></h4>

                                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($workshop['instructor'] ?? 'TBA'); ?></p>

                                        <p><strong>Capacity:</strong> <?php echo $workshop['enrolled_count'] ?? 0; ?>/<?php echo $workshop['max_participants']; ?></p>

                                        <div class="capacity-bar">

                                            <div class="capacity-fill" style="width: <?php echo ($workshop['max_participants'] > 0) ? min((($workshop['enrolled_count'] ?? 0) / $workshop['max_participants']) * 100, 100) : 0; ?>%;"></div>

                                        </div>

                                        <p><strong>Assigned Mentors:</strong> <?php echo $workshop['assigned_mentors'] ?? 0; ?></p>

                                    </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        

                        <!-- Statistics Tab -->

                        <div id="statistics" class="tab-content">

                            <h3>Program Statistics</h3>

                            <p>View detailed analytics for this program.</p>

                            

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">

                                <div class="stat-card">

                                    <h4>Gender Distribution</h4>

                                    <div style="margin: 15px 0;">

                                        <?php if (isset($stats['gender_distribution'])): ?>

                                            <?php foreach ($stats['gender_distribution'] as $gender => $count): ?>

                                                <p><?php echo htmlspecialchars($gender); ?>: <strong><?php echo $count; ?></strong></p>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <p>No data available</p>

                                        <?php endif; ?>

                                    </div>

                                </div>

                                

                                <div class="stat-card">

                                    <h4>Age Distribution</h4>

                                    <div style="margin: 15px 0;">

                                        <?php if (isset($stats['age_distribution'])): ?>

                                            <?php foreach ($stats['age_distribution'] as $age => $count): ?>

                                                <p><?php echo htmlspecialchars($age); ?> years: <strong><?php echo $count; ?></strong></p>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <p>No data available</p>

                                        <?php endif; ?>

                                    </div>

                                </div>

                                

                                <div class="stat-card">

                                    <h4>Registration Status</h4>

                                    <div style="margin: 15px 0;">

                                        <p>Total: <strong><?php echo $stats['total_registrations'] ?? 0; ?></strong></p>

                                        <p>Confirmed: <strong><?php echo $stats['confirmed_registrations'] ?? 0; ?></strong></p>

                                        <p>Pending: <strong><?php echo $stats['pending_registrations'] ?? 0; ?></strong></p>

                                    </div>

                                </div>

                                

                                <div class="stat-card">

                                    <h4>Mentor Applications</h4>

                                    <div style="margin: 15px 0;">

                                        <p>Total Applications: <strong><?php echo $stats['mentor_applications'] ?? 0; ?></strong></p>

                                        <?php if (isset($stats['mentor_status'])): ?>

                                            <?php foreach ($stats['mentor_status'] as $status => $count): ?>

                                                <p><?php echo htmlspecialchars($status); ?>: <strong><?php echo $count; ?></strong></p>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                        

                        <!-- Mentors Tab -->

                        <div id="mentors" class="tab-content">

                            <h3>Mentor Applications</h3>

                            <p>Review and manage mentor applications.</p>

                            

                            <?php 

                            $mentorRegistrations = array_filter($registrations, function($reg) {

                                return $reg['mentor_registration'] == 1;

                            });

                            ?>

                            

                            <?php if (empty($mentorRegistrations)): ?>

                                <div class="empty-state">

                                    <h3>No Mentor Applications</h3>

                                    <p>No one has applied to be a mentor for this program yet.</p>

                                </div>

                            <?php else: ?>

                                <div style="overflow-x: auto; margin-top: 20px;">

                                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">

                                        <thead>

                                            <tr style="background: #f8f9fa;">

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Name</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Email</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Status</th>

                                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Application Date</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php foreach ($mentorRegistrations as $mentor): ?>

                                            <tr style="border-bottom: 1px solid #f1f3f4;">

                                                <td style="padding: 12px;"><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></td>

                                                <td style="padding: 12px;"><?php echo htmlspecialchars($mentor['email']); ?></td>

                                                <td style="padding: 12px;">

                                                    <span class="status-badge <?php echo strtolower($mentor['mentor_status'] ?? 'pending'); ?>">

                                                        <?php echo $mentor['mentor_status'] ?? 'Pending'; ?>

                                                    </span>

                                                </td>

                                                <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($mentor['created_at'])); ?></td>

                                            </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <!-- NEW: Holiday Programs Schedule Manager Section -->

                <div class="schedule-manager">

                    <h2><i class="fas fa-calendar-check"></i> Holiday Programs Schedule Manager</h2>

                    

                    <!-- Program Structure Configuration -->

                    <div class="config-section">

                        <h3><i class="fas fa-sitemap"></i> Program Structure</h3>

                        <form method="POST" action="">

                            <input type="hidden" name="action" value="update_program_structure">

                            <input type="hidden" name="program_id" value="<?php echo $current_program['id']; ?>">

                            

                            <div class="two-column">

                                <div class="form-group">

                                    <label for="duration">Program Duration (weeks)</label>

                                    <input type="number" id="duration" name="duration" class="form-control" 

                                           value="<?php echo $programStructure['duration_weeks'] ?? 2; ?>" min="1" max="8" required>

                                </div>

                                

                                <div class="form-group">

                                    <label>Program Features</label>

                                    <div class="checkbox-group">

                                        <input type="checkbox" id="enable_cohorts" name="enable_cohorts" 

                                               <?php echo ($programStructure['cohort_system'] ?? false) ? 'checked' : ''; ?>>

                                        <label for="enable_cohorts">Enable Cohort System</label>

                                    </div>

                                    <div class="checkbox-group">

                                        <input type="checkbox" id="enable_prerequisites" name="enable_prerequisites"

                                               <?php echo ($programStructure['prerequisites_enabled'] ?? false) ? 'checked' : ''; ?>>

                                        <label for="enable_prerequisites">Enable Prerequisites</label>

                                    </div>

                                </div>

                            </div>

                            

                            <button type="submit" class="btn btn-primary">

                                <i class="fas fa-save"></i> Update Program Structure

                            </button>

                        </form>

                    </div>



                    <!-- Cohorts Management -->

                    <div class="config-section">

                        <h3><i class="fas fa-users"></i> Cohorts Management</h3>
                        <!-- Cohort Capacity Stats -->
                        <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196F3;">
                            <h4 style="margin-top: 0;">📊 Program Capacity Status:</h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <strong>Week 1 (June 30 - July 4):</strong><br>
                                    <?php if ($cohort3Capacity['available']): ?>
                                        <span style="color: #4CAF50;">✅ Available - <?php echo $cohort3Capacity['remaining']; ?> spots left</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">❌ Full (<?php echo $cohort3Capacity['used']; ?>/<?php echo $cohort3Capacity['total']; ?>)</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <strong>Week 2 (July 7 - July 11):</strong><br>
                                    <?php if ($cohort4Capacity['available']): ?>
                                        <span style="color: #4CAF50;">✅ Available - <?php echo $cohort4Capacity['remaining']; ?> spots left</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">❌ Full (<?php echo $cohort4Capacity['used']; ?>/<?php echo $cohort4Capacity['total']; ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>                        
                        

                        <?php if (!empty($cohorts)): ?>

                            <div class="cohorts-list">

                                <?php foreach ($cohorts as $cohort): ?>

                                    <div class="cohort-item">

                                        <h4><?php echo htmlspecialchars($cohort['name']); ?></h4>

                                        <p><strong>Duration:</strong> <?php echo date('M j', strtotime($cohort['start_date'])); ?> - <?php echo date('M j, Y', strtotime($cohort['end_date'])); ?></p>

                                        <p><strong>Capacity:</strong> <?php echo $cohort['max_participants']; ?> participants</p>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                        

                        <form method="POST" action="" class="add-cohort-form">

                            <input type="hidden" name="action" value="add_cohort">

                            <input type="hidden" name="program_id" value="<?php echo $current_program['id']; ?>">

                            

                            <div class="two-column">

                                <div class="form-group">

                                    <label for="cohort_name">Cohort Name</label>

                                    <input type="text" id="cohort_name" name="cohort_name" class="form-control" 

                                           placeholder="e.g., Week 1 Group, Advanced Cohort" required>

                                </div>

                                

                                <div class="form-group">

                                    <label for="cohort_capacity">Capacity</label>

                                    <input type="number" id="cohort_capacity" name="cohort_capacity" class="form-control" 

                                           value="20" min="5" max="50" required>

                                </div>

                            </div>

                            

                            <div class="two-column">

                                <div class="form-group">

                                    <label for="cohort_start_date">Start Date</label>

                                    <input type="date" id="cohort_start_date" name="cohort_start_date" class="form-control" required>

                                </div>

                                

                                <div class="form-group">

                                    <label for="cohort_end_date">End Date</label>

                                    <input type="date" id="cohort_end_date" name="cohort_end_date" class="form-control" required>

                                </div>

                            </div>

                            

                            <button type="submit" class="btn btn-success">

                                <i class="fas fa-plus"></i> Add Cohort

                            </button>

                        </form>

                    </div>



                    <!-- Workshop Offerings Management -->

                    <div class="config-section">

                        <h3><i class="fas fa-laptop-code"></i> Workshop Offerings</h3>

                        

                        <?php if (!empty($workshops)): ?>

                            <div class="workshops-list">

                                <?php foreach ($workshops as $workshop): ?>

                                    <div class="workshop-item">

                                        <form method="POST" action="" class="workshop-form">

                                            <input type="hidden" name="action" value="update_workshop">

                                            <input type="hidden" name="workshop_id" value="<?php echo $workshop['id']; ?>">

                                            

                                            <div class="two-column">

                                                <div class="form-group">

                                                    <label>Workshop Title</label>

                                                    <input type="text" name="workshop_title" class="form-control" 

                                                           value="<?php echo htmlspecialchars($workshop['title']); ?>" required>

                                                </div>

                                                

                                                <div class="form-group">

                                                    <label>Instructor</label>

                                                    <input type="text" name="workshop_instructor" class="form-control" 

                                                           value="<?php echo htmlspecialchars($workshop['instructor'] ?? ''); ?>">

                                                </div>

                                            </div>

                                            

                                            <div class="form-group">

                                                <label>Description</label>

                                                <textarea name="workshop_description" class="form-control" rows="3"><?php echo htmlspecialchars($workshop['description'] ?? ''); ?></textarea>

                                            </div>

                                            

                                            <div class="two-column">

                                                <div class="form-group">

                                                    <label>Capacity</label>

                                                    <input type="number" name="workshop_capacity" class="form-control" 

                                                           value="<?php echo $workshop['max_participants']; ?>" min="5" max="30" required>

                                                </div>

                                                

                                                <div class="form-group">

                                                    <label>Prerequisites</label>

                                                    <input type="text" name="workshop_prerequisites" class="form-control" 

                                                           value="<?php echo htmlspecialchars($workshop['prerequisites'] ?? ''); ?>" 

                                                           placeholder="e.g., Basic computer skills, Age 13+">

                                                </div>

                                            </div>

                                            

                                            <button type="submit" class="btn btn-primary">

                                                <i class="fas fa-save"></i> Update Workshop

                                            </button>

                                        </form>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <p>No workshops configured for this program. <a href="holidayProgramCreationForm.php?program_id=<?php echo $current_program['id']; ?>">Add workshops here</a>.</p>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- END: Schedule Manager Section -->

                

            <?php endif; ?>

        </div>

    </div>

    

    <!-- Modals -->

    <!-- Attendee Details Modal -->

    <div id="attendeeModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);">

        <div style="background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 700px; max-height: 80vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 25px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white;">

                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">Attendee Details</h2>

                <span onclick="closeModal('attendeeModal')" style="font-size: 32px; font-weight: bold; cursor: pointer; color: rgba(255,255,255,0.8); line-height: 1; padding: 5px;">&times;</span>

            </div>

            <div id="attendeeDetails" style="padding: 30px; max-height: 60vh; overflow-y: auto;">

                <!-- Content will be loaded via AJAX -->

            </div>

        </div>

    </div>

    

    <!-- Bulk Email Modal -->

    <div id="bulkEmailModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);">

        <div style="background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 25px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white;">

                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600;">Send Bulk Email</h2>

                <span onclick="closeModal('bulkEmailModal')" style="font-size: 32px; font-weight: bold; cursor: pointer; color: rgba(255,255,255,0.8); line-height: 1; padding: 5px;">&times;</span>

            </div>

            <form id="bulkEmailForm" style="padding: 30px;">

                <div style="margin-bottom: 20px;">

                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #495057;">Recipients:</label>

                    <select name="recipients" style="width: 100%; padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">

                        <option value="all">All Participants</option>

                        <option value="members">Members Only</option>

                        <option value="mentors">Mentors Only</option>

                        <option value="confirmed">Confirmed Registrations Only</option>

                    </select>

                </div>

                <div style="margin-bottom: 20px;">

                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #495057;">Subject:</label>

                    <input type="text" name="subject" required style="width: 100%; padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;">

                </div>

                <div style="margin-bottom: 20px;">

                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #495057;">Message:</label>

                    <textarea name="message" rows="8" required style="width: 100%; padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 120px;"></textarea>

                </div>

                <div style="text-align: right;">

                    <button type="button" class="action-btn" onclick="closeModal('bulkEmailModal')" style="background: #6c757d; color: white; margin-right: 10px;">Cancel</button>

                    <button type="submit" class="action-btn primary">Send Email</button>

                </div>

            </form>

        </div>

    </div>

    

    <script src="../../../public/assets/js/holidayProgramIndex.js"></script>

    <script>

    // Chart data from PHP

const statsData = <?php echo json_encode($stats ?? []); ?>;

const programId = <?php echo json_encode($current_program['id'] ?? 0); ?>;



// =================================================================

// TAB FUNCTIONALITY

// =================================================================



function showTab(tabName) {

    // Hide all tabs

    document.querySelectorAll('.tab-content').forEach(tab => {

        tab.classList.remove('active');

        tab.style.display = 'none';

    });

    

    // Remove active class from all buttons

    document.querySelectorAll('.tab-btn').forEach(btn => {

        btn.classList.remove('active');

    });

    

    // Show selected tab

    const selectedTab = document.getElementById(tabName);

    const clickedButton = event.target.closest('.tab-btn');

    

    if (selectedTab) {

        selectedTab.classList.add('active');

        selectedTab.style.display = 'block';

    }

    

    if (clickedButton) {

        clickedButton.classList.add('active');

    }

    

    console.log(`Switched to tab: ${tabName}`);

}



// =================================================================

// PROGRAM STATUS UPDATE

// =================================================================



function updateProgramStatus() {

    const statusSelect = document.getElementById('registration-status');

    if (!statusSelect) {

        console.error('Status select element not found');

        return;

    }

    

    const status = statusSelect.value;

    const statusIndicator = document.getElementById('status-indicator');

    

    if (!programId || programId === 0) {

        showNotification('No program selected', 'error');

        return;

    }

    

    console.log(`Updating program ${programId} status to: ${status}`);

    

    // Show loading state

    statusSelect.disabled = true;

    const originalIndicatorText = statusIndicator ? statusIndicator.textContent : '';

    if (statusIndicator) {

        statusIndicator.textContent = 'Updating...';

    }

    

    fetch(window.location.href, {

        method: 'POST',

        headers: {

            'Content-Type': 'application/x-www-form-urlencoded',

        },

        body: `action=update_program_status&program_id=${programId}&registration_open=${status}`

    })

    .then(response => {

        console.log('Response status:', response.status);

        return response.json();

    })

    .then(data => {

        console.log('Response data:', data);

        

        if (data.success) {

            // Update status indicator

            if (statusIndicator) {

                if (status === '1') {

                    statusIndicator.textContent = 'Registration Open';

                    statusIndicator.className = 'status-badge confirmed';

                } else {

                    statusIndicator.textContent = 'Registration Closed';

                    statusIndicator.className = 'status-badge canceled';

                }

            }

            

            showNotification('Program status updated successfully!', 'success');

            

            // Reload page after 2 seconds to reflect changes

            setTimeout(() => {

                window.location.reload();

            }, 2000);

        } else {

            showNotification('Error: ' + (data.message || 'Unknown error'), 'error');

            // Restore original state

            if (statusIndicator) {

                statusIndicator.textContent = originalIndicatorText;

            }

        }

    })

    .catch(error => {

        console.error('Error:', error);

        showNotification('Network error occurred. Please try again.', 'error');

        // Restore original state

        if (statusIndicator) {

            statusIndicator.textContent = originalIndicatorText;

        }

    })

    .finally(() => {

        statusSelect.disabled = false;

    });

}



// =================================================================

// EXPORT AND BULK ACTIONS

// =================================================================



function exportRegistrations() {

    if (!programId || programId === 0) {

        showNotification('No program selected', 'error');

        return;

    }

    window.location.href = `?export=csv&program_id=${programId}`;

}



function showBulkEmailModal() {

    const modal = document.getElementById('bulkEmailModal');

    if (modal) {

        modal.style.display = 'block';

    } else {

        showNotification('Bulk email feature coming soon!', 'info');

    }

}



function showBulkActions() {

    showNotification('Bulk actions feature available in full version', 'info');

}



// =================================================================

// REGISTRATION MANAGEMENT

// =================================================================



function filterRegistrations() {

    const searchTerm = document.getElementById('search-registrations')?.value.toLowerCase() || '';

    const statusFilter = document.getElementById('filter-status')?.value || '';

    const typeFilter = document.getElementById('filter-type')?.value || '';

    

    const rows = document.querySelectorAll('#registrations-table tbody tr');

    

    rows.forEach(row => {

        const name = row.cells[0]?.textContent.toLowerCase() || '';

        const email = row.cells[1]?.textContent.toLowerCase() || '';

        const status = row.dataset.status || '';

        const type = row.dataset.type || '';

        

        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);

        const matchesStatus = !statusFilter || status === statusFilter;

        const matchesType = !typeFilter || type === typeFilter;

        

        row.style.display = matchesSearch && matchesStatus && matchesType ? '' : 'none';

    });

}



function updateStatus(attendeeId, status) {

    const statusText = status === 'confirmed' ? 'confirm' : status === 'canceled' ? 'cancel' : status;

    

    if (confirm(`Are you sure you want to ${statusText} this registration?`)) {

        updateRegistrationStatus(attendeeId, status).then(() => {

            showNotification('Registration status updated successfully!', 'success');

            setTimeout(() => location.reload(), 1000);

        }).catch(error => {

            console.error('Update error:', error);

            showNotification('Failed to update registration status.', 'error');

        });

    }

}



function updateRegistrationStatus(attendeeId, status) {

    return fetch(window.location.href, {

        method: 'POST',

        headers: {

            'Content-Type': 'application/x-www-form-urlencoded',

        },

        body: `action=update_registration_status&attendee_id=${attendeeId}&status=${status}`

    })

    .then(response => response.json())

    .then(data => {

        if (!data.success) {

            throw new Error(data.message);

        }

        return data;

    });

}



// =================================================================

// MODAL FUNCTIONS

// =================================================================



function viewAttendee(attendeeId) {

    fetch(window.location.href, {

        method: 'POST',

        headers: {

            'Content-Type': 'application/x-www-form-urlencoded',

        },

        body: `action=get_attendee_details&attendee_id=${attendeeId}`

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            displayAttendeeDetails(data.data);

            const modal = document.getElementById('attendeeModal');

            if (modal) modal.style.display = 'block';

        } else {

            showNotification('Error: ' + data.message, 'error');

        }

    })

    .catch(error => {

        console.error('Error:', error);

        showNotification('An error occurred. Please try again.', 'error');

    });

}



function displayAttendeeDetails(attendee) {

    const html = `

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

            <div>

                <h4 style="margin-bottom: 15px; color: #495057;">Personal Information</h4>

                <p><strong>Name:</strong> ${attendee.first_name} ${attendee.last_name}</p>

                <p><strong>Email:</strong> ${attendee.email}</p>

                <p><strong>Phone:</strong> ${attendee.phone || 'Not provided'}</p>

                <p><strong>Date of Birth:</strong> ${attendee.date_of_birth || 'Not provided'}</p>

                <p><strong>Gender:</strong> ${attendee.gender || 'Not specified'}</p>

                ${!attendee.mentor_registration ? `

                    <p><strong>School:</strong> ${attendee.school || 'Not provided'}</p>

                    <p><strong>Grade:</strong> ${attendee.grade || 'Not provided'}</p>

                ` : ''}

            </div>

            <div>

                <h4 style="margin-bottom: 15px; color: #495057;">Registration Information</h4>

                <p><strong>Status:</strong> <span class="status-badge ${attendee.status || attendee.registration_status}">${attendee.status || attendee.registration_status}</span></p>

                <p><strong>Type:</strong> ${attendee.mentor_registration ? 'Mentor' : 'Member'}</p>

                <p><strong>Registration Date:</strong> ${new Date(attendee.created_at).toLocaleDateString()}</p>

                ${attendee.mentor_registration ? `

                    <p><strong>Mentor Status:</strong> <span class="status-badge ${(attendee.mentor_status || '').toLowerCase()}">${attendee.mentor_status || 'Pending'}</span></p>

                ` : ''}

            </div>

        </div>

    `;

    

    const detailsContainer = document.getElementById('attendeeDetails');

    if (detailsContainer) {

        detailsContainer.innerHTML = html;

    }

}



function closeModal(modalId) {

    const modal = document.getElementById(modalId);

    if (modal) {

        modal.style.display = 'none';

    }

}



// =================================================================

// NOTIFICATION SYSTEM

// =================================================================



function showNotification(message, type) {

    // Remove existing notifications

    const existingNotifications = document.querySelectorAll('.notification');

    existingNotifications.forEach(n => n.remove());

    

    const notification = document.createElement('div');

    notification.className = `notification ${type}`;

    notification.style.cssText = `

        position: fixed;

        top: 20px;

        right: 20px;

        padding: 15px 20px;

        border-radius: 8px;

        color: white;

        font-weight: 500;

        z-index: 10000;

        animation: slideInRight 0.3s ease;

        min-width: 300px;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);

    `;

    

    if (type === 'success') {

        notification.style.background = '#28a745';

    } else if (type === 'error') {

        notification.style.background = '#dc3545';

    } else if (type === 'info') {

        notification.style.background = '#17a2b8';

    } else {

        notification.style.background = '#6c757d';

    }

    

    notification.innerHTML = `

        <div style="display: flex; align-items: center; gap: 10px;">

            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}-circle"></i>

            <span>${message}</span>

            <button onclick="this.parentElement.parentElement.remove()" 

                    style="background: none; border: none; color: white; cursor: pointer; margin-left: auto;">

                <i class="fas fa-times"></i>

            </button>

        </div>

    `;

    

    document.body.appendChild(notification);

    

    // Auto remove after 5 seconds

    setTimeout(() => {

        if (notification.parentNode) {

            notification.style.animation = 'slideOutRight 0.3s ease';

            setTimeout(() => notification.remove(), 300);

        }

    }, 5000);

}



// =================================================================

// EVENT LISTENERS AND INITIALIZATION

// =================================================================



document.addEventListener('DOMContentLoaded', function() {

    console.log('Enhanced Admin Dashboard loaded successfully');

    

    // Set minimum dates for cohort forms

    const today = new Date().toISOString().split('T')[0];

    const cohortStartDate = document.getElementById('cohort_start_date');

    const cohortEndDate = document.getElementById('cohort_end_date');

    

    if (cohortStartDate) {

        cohortStartDate.min = today;

        cohortStartDate.addEventListener('change', function() {

            if (cohortEndDate) {

                cohortEndDate.min = this.value;

            }

        });

    }

    

    // Initialize bulk email form

    const bulkEmailForm = document.getElementById('bulkEmailForm');

    if (bulkEmailForm) {

        bulkEmailForm.addEventListener('submit', function(e) {

            e.preventDefault();

            

            const formData = new FormData(this);

            formData.append('action', 'send_bulk_email');

            formData.append('program_id', programId);

            

            fetch(window.location.href, {

                method: 'POST',

                body: formData

            })

            .then(response => response.json())

            .then(data => {

                if (data.success) {

                    showNotification(`Email sent to ${data.recipients_count || 0} recipients!`, 'success');

                    closeModal('bulkEmailModal');

                } else {

                    showNotification('Error: ' + data.message, 'error');

                }

            })

            .catch(error => {

                console.error('Error:', error);

                showNotification('An error occurred. Please try again.', 'error');

            });

        });

    }

    

    // Close modals when clicking outside

    window.onclick = function(event) {

        const modals = document.querySelectorAll('[id$="Modal"]');

        modals.forEach(modal => {

            if (event.target === modal) {

                modal.style.display = 'none';

            }

        });

    }

    

    // Initialize tab functionality

    document.querySelectorAll('.tab-btn').forEach(btn => {

        btn.addEventListener('click', function(e) {

            e.preventDefault();

            const tabName = this.textContent.trim().toLowerCase();

            

            // Extract tab name from button text

            if (tabName.includes('registrations')) {

                showTab('registrations');

            } else if (tabName.includes('workshops')) {

                showTab('workshops');

            } else if (tabName.includes('statistics')) {

                showTab('statistics');

            } else if (tabName.includes('mentors')) {

                showTab('mentors');

            }

        });

    });

    

    // Show first tab by default

    showTab('registrations');

    

    // Global function assignments for onclick handlers

    window.showTab = showTab;

    window.updateProgramStatus = updateProgramStatus;

    window.exportRegistrations = exportRegistrations;

    window.showBulkEmailModal = showBulkEmailModal;

    window.showBulkActions = showBulkActions;

    window.filterRegistrations = filterRegistrations;

    window.updateStatus = updateStatus;

    window.viewAttendee = viewAttendee;

    window.closeModal = closeModal;

});



// Add CSS animations

const dashboardStyle = document.createElement('style');

dashboardStyle.textContent = `

    @keyframes slideInRight {

        from {

            opacity: 0;

            transform: translateX(100%);

        }

        to {

            opacity: 1;

            transform: translateX(0);

        }

    }

    

    @keyframes slideOutRight {

        from {

            opacity: 1;

            transform: translateX(0);

        }

        to {

            opacity: 0;

            transform: translateX(100%);

        }

    }

    

    .tab-content {

        display: none;

    }

    

    .tab-content.active {

        display: block;

        animation: fadeIn 0.3s ease;

    }

    

    @keyframes fadeIn {

        from { opacity: 0; }

        to { opacity: 1; }

    }

`;



document.head.appendChild(dashboardStyle);



console.log('Enhanced Dashboard JavaScript fully initialized');

    // Add to your existing admin dashboard JavaScript
    function sendProfileEmail(attendeeId) {
        if (confirm('Send profile access email to this attendee?')) {
            fetch('../../Controllers/send-profile-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'attendee_id=' + attendeeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profile access email sent successfully!', 'success');
                } else {
                    showNotification('Failed to send email: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error sending email', 'error');
            });
        }
    }

    </script>

</body>

</html>