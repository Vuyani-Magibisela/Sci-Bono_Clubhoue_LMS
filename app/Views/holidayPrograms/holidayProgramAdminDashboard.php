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

// Handle AJAX requests FIRST
if (isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'update_program_status':
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
            
        case 'update_registration_status':
            header('Content-Type: application/json');
            
            $attendeeId = intval($_POST['attendee_id']);
            $status = $_POST['status'];
            
            try {
                $validStatuses = ['pending', 'confirmed', 'canceled'];
                if (!in_array($status, $validStatuses)) {
                    throw new Exception('Invalid status');
                }
                
                $updateSql = "UPDATE holiday_program_attendees SET status = ?, updated_at = NOW() WHERE id = ?";
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

// Handle AJAX requests
if (isset($_POST['action'])) {
    $adminController->handleAjaxRequest();
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['program_id'])) {
    $programId = intval($_GET['program_id']);
    $adminController->exportRegistrations($programId, 'csv');
    exit;
}

// Get current program ID
$currentProgramId = isset($_GET['program_id']) ? intval($_GET['program_id']) : null;

// Get dashboard data
$dashboardData = $adminController->getDashboardData($currentProgramId);
extract($dashboardData);

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
        
        .admin-dashboard {
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
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
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
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
        }
        
        .tab-navigation {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: white;
        }
        
        .tab-content {
            display: none;
            padding: 30px;
        }
        
        .tab-content.active {
            display: block;
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

                /* Status Management Styles */
        .status-management-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
        }

        .section-header h3 {
            color: #2c3e50;
            margin: 0 0 5px 0;
            font-size: 1.5rem;
        }

        .section-header p {
            color: #6c757d;
            margin: 0;
        }

        .status-controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-display-card,
        .status-controls-card,
        .registration-stats-card,
        .automation-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .status-display-card h4,
        .status-controls-card h4,
        .registration-stats-card h4,
        .automation-card h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.1rem;
        }

        .current-status {
            text-align: center;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .status-indicator.active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-indicator.inactive {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .status-control-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-select {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .update-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
        }

        .update-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .quick-actions {
            margin-top: 20px;
        }

        .quick-actions h5 {
            margin: 0 0 10px 0;
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
        }

        .action-btn.success {
            background: #28a745;
        }

        .action-btn.danger {
            background: #dc3545;
        }

        .action-btn.info {
            background: #17a2b8;
        }

        .action-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .impact-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .capacity-progress {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-weight: 600;
            color: #495057;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .automation-settings {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .rule-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .rule-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .rule-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #007bff;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .rule-description strong {
            display: block;
            color: #495057;
            margin-bottom: 3px;
        }

        .rule-description small {
            color: #6c757d;
        }

        .notification-settings {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .notification-settings h4 {
            margin: 0 0 15px 0;
            color: #495057;
        }

        .notification-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification-options label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #495057;
            cursor: pointer;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.8);
        }

        .close:hover {
            color: white;
        }

        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .history-timeline {
            position: relative;
            padding-left: 30px;
        }

        .history-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .history-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 20px;
        }

        .history-dot {
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #e9ecef;
        }

        .history-dot.success {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a745;
        }

        .history-dot.danger {
            background: #dc3545;
            box-shadow: 0 0 0 2px #dc3545;
        }

        .history-action {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .history-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .history-details small {
            color: #6c757d;
            font-size: 12px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            /* Program status section */
            .status-controls-grid {
                grid-template-columns: 1fr;
            }
            
            .impact-stats {
                grid-template-columns: 1fr;
            }
            
            .status-control-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            /* end of status section */
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
                    <h2>No Pro gram Selected</h2>
                    <p>Please select a holiday program from the dropdown above to view its dashboard.</p>
                </div>
            <?php else: ?>
                
                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card info">
                        <div class="stat-value"><?php echo $stats['total_registrations']; ?></div>
                        <div class="stat-label">Total Registrations</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?php echo $stats['confirmed_registrations']; ?></div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?php echo $stats['pending_registrations']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-value"><?php echo $stats['mentor_applications']; ?></div>
                        <div class="stat-label">Mentor Applications</div>
                    </div>
                </div>
                <?php if ($current_program): ?>

                <!-- Enhanced Program Status Control Section -->
                <div class="status-management-section">
                    <div class="section-header">
                        <h3><i class="fas fa-toggle-on"></i> Program Status Management</h3>
                        <p>Control registration availability and monitor program status</p>
                    </div>
                    
                    <div class="status-controls-grid">
                        <!-- Current Status Display -->
                        <div class="status-display-card">
                            <h4>Current Status</h4>
                            <div class="current-status">
                                <div class="status-indicator <?php echo $current_program['registration_open'] ? 'active' : 'inactive'; ?>">
                                    <div class="status-dot"></div>
                                    <span class="status-text">
                                        <?php echo $current_program['registration_open'] ? 'Registration Open' : 'Registration Closed'; ?>
                                    </span>
                                </div>
                                <div class="status-details">
                                    <small>Last updated: <?php echo date('M j, Y \a\t g:i A', strtotime($current_program['updated_at'] ?? 'now')); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Controls -->
                        <div class="status-controls-card">
                            <h4>Change Status</h4>
                            <div class="control-group">
                                <label for="registration-status-select">Registration Status:</label>
                                <div class="status-control-wrapper">
                                    <select id="registration-status-select" class="status-select">
                                        <option value="0" <?php echo !$current_program['registration_open'] ? 'selected' : ''; ?>>
                                            🔒 Closed - No new registrations
                                        </option>
                                        <option value="1" <?php echo $current_program['registration_open'] ? 'selected' : ''; ?>>
                                            ✅ Open - Accepting registrations
                                        </option>
                                    </select>
                                    <button id="update-status-btn" class="update-btn" onclick="updateProgramStatusEnhanced()">
                                        <i class="fas fa-save"></i> Update Status
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <h5>Quick Actions:</h5>
                                <div class="action-buttons">
                                    <button class="action-btn success" onclick="openRegistration()" 
                                            <?php echo $current_program['registration_open'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-unlock"></i> Open Registration
                                    </button>
                                    <button class="action-btn danger" onclick="closeRegistration()" 
                                            <?php echo !$current_program['registration_open'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-lock"></i> Close Registration
                                    </button>
                                    <button class="action-btn info" onclick="viewStatusHistory()">
                                        <i class="fas fa-history"></i> View History
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Registration Statistics -->
                        <div class="registration-stats-card">
                            <h4>Registration Impact</h4>
                            <div class="impact-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $stats['total_registrations'] ?? 0; ?></div>
                                    <div class="stat-label">Total Registrations</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $stats['pending_registrations'] ?? 0; ?></div>
                                    <div class="stat-label">Pending Approval</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">
                                        <?php 
                                        $capacity = $current_program['max_participants'] ?? 30;
                                        $remaining = $capacity - ($stats['confirmed_registrations'] ?? 0);
                                        echo max(0, $remaining);
                                        ?>
                                    </div>
                                    <div class="stat-label">Spots Remaining</div>
                                </div>
                            </div>
                            
                            <!-- Capacity Progress Bar -->
                            <div class="capacity-progress">
                                <div class="progress-header">
                                    <span>Capacity</span>
                                    <span><?php echo ($stats['confirmed_registrations'] ?? 0); ?>/<?php echo $capacity; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min((($stats['confirmed_registrations'] ?? 0) / $capacity) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Automated Rules -->
                        <div class="automation-card">
                            <h4>Automated Rules</h4>
                            <div class="automation-settings">
                                <div class="rule-item">
                                    <label class="rule-toggle">
                                        <input type="checkbox" id="auto-close-capacity" 
                                            <?php echo ($current_program['auto_close_on_capacity'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="rule-description">
                                        <strong>Auto-close when full</strong>
                                        <small>Automatically close registration when capacity is reached</small>
                                    </div>
                                </div>
                                
                                <div class="rule-item">
                                    <label class="rule-toggle">
                                        <input type="checkbox" id="auto-close-date" 
                                            <?php echo ($current_program['auto_close_on_date'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="rule-description">
                                        <strong>Auto-close on deadline</strong>
                                        <small>Close registration on: <?php echo date('M j, Y', strtotime($current_program['registration_deadline'] ?? '+7 days')); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Change Notifications -->
                    <div class="notification-settings">
                        <h4><i class="fas fa-bell"></i> Notification Settings</h4>
                        <div class="notification-options">
                            <label>
                                <input type="checkbox" id="notify-status-change" checked>
                                Send email notifications when registration status changes
                            </label>
                            <label>
                                <input type="checkbox" id="notify-capacity-warning" checked>
                                Alert when approaching capacity (90% full)
                            </label>
                            <label>
                                <input type="checkbox" id="notify-deadline-approaching" checked>
                                Remind 24 hours before registration deadline
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Status History Modal -->
                <div id="statusHistoryModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fas fa-history"></i> Status Change History</h3>
                            <span class="close" onclick="closeStatusHistoryModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div id="status-history-content">
                                <!-- History will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
                            <i class="fas fa-chalkboard-teacher"></i> Mentors (<?php echo $stats['mentor_applications']; ?>)
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
                        
                        <!-- Bulk Actions Panel -->
                        <div id="bulk-actions" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #dee2e6;">
                            <h4 style="margin: 0 0 15px 0;">Bulk Actions</h4>
                            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                <select id="bulk-action-select" style="padding: 10px; border: 2px solid #e9ecef; border-radius: 6px;">
                                    <option value="">Choose action...</option>
                                    <option value="confirm">Confirm Registrations</option>
                                    <option value="cancel">Cancel Registrations</option>
                                    <option value="delete">Delete Registrations</option>
                                </select>
                                <button class="action-btn primary" onclick="executeBulkAction()">Apply to Selected</button>
                                <button class="action-btn" onclick="hideBulkActions()" style="background: #6c757d; color: white;">Cancel</button>
                                <span id="selected-count" style="color: #666; font-size: 0.9rem;"></span>
                            </div>
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
                                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">
                                                <input type="checkbox" id="select-all" onchange="toggleSelectAll()" style="cursor: pointer;">
                                            </th>
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
                                            <td style="padding: 12px;">
                                                <input type="checkbox" class="registration-checkbox" value="<?php echo $registration['id']; ?>" 
                                                       onchange="updateSelectedCount()" style="cursor: pointer;">
                                            </td>
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
                                                <?php if ($registration['mentor_registration'] && $registration['mentor_status']): ?>
                                                    <br><small class="status-badge" style="background: #f8f9fa; color: #6c757d; margin-top: 5px; display: inline-block;">
                                                        Mentor: <?php echo $registration['mentor_status']; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px;"><?php echo $registration['assigned_workshops'] ?: 'Not assigned'; ?></td>
                                            <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></td>
                                            <td style="padding: 12px;">
                                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                    <button class="action-btn" onclick="viewAttendee(<?php echo $registration['id']; ?>)" 
                                                            style="background: #17a2b8; color: white; padding: 6px 10px; font-size: 0.8rem;">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($registration['registration_status'] === 'pending'): ?>
                                                        <button class="action-btn success" onclick="updateStatus(<?php echo $registration['id']; ?>, 'confirmed')" 
                                                                style="padding: 6px 10px; font-size: 0.8rem;">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="action-btn danger" onclick="updateStatus(<?php echo $registration['id']; ?>, 'canceled')" 
                                                                style="padding: 6px 10px; font-size: 0.8rem;">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($registration['registration_status'] === 'confirmed'): ?>
                                                        <button class="action-btn warning" onclick="updateStatus(<?php echo $registration['id']; ?>, 'pending')" 
                                                                style="padding: 6px 10px; font-size: 0.8rem;">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($registration['mentor_registration'] && ($registration['mentor_status'] ?? '') === 'Pending'): ?>
                                                        <button class="action-btn success" onclick="updateMentorStatus(<?php echo $registration['id']; ?>, 'Approved')" 
                                                                style="padding: 6px 10px; font-size: 0.8rem;" title="Approve Mentor">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                        <button class="action-btn danger" onclick="updateMentorStatus(<?php echo $registration['id']; ?>, 'Declined')" 
                                                                style="padding: 6px 10px; font-size: 0.8rem;" title="Decline Mentor">
                                                            <i class="fas fa-user-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="action-btn" onclick="editAttendee(<?php echo $registration['id']; ?>)" 
                                                            style="background: #fd7e14; color: white; padding: 6px 10px; font-size: 0.8rem;">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
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
                                    <p><strong>Capacity:</strong> <?php echo $workshop['enrolled_count']; ?>/<?php echo $workshop['max_participants']; ?></p>
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: <?php echo ($workshop['max_participants'] > 0) ? min(($workshop['enrolled_count'] / $workshop['max_participants']) * 100, 100) : 0; ?>%;"></div>
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
                                    <?php foreach ($stats['gender_distribution'] as $gender => $count): ?>
                                        <p><?php echo $gender; ?>: <strong><?php echo $count; ?></strong></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h4>Age Distribution</h4>
                                <div style="margin: 15px 0;">
                                    <?php foreach ($stats['age_distribution'] as $age => $count): ?>
                                        <p><?php echo $age; ?> years: <strong><?php echo $count; ?></strong></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h4>Registration Status</h4>
                                <div style="margin: 15px 0;">
                                    <p>Total: <strong><?php echo $stats['total_registrations']; ?></strong></p>
                                    <p>Confirmed: <strong><?php echo $stats['confirmed_registrations']; ?></strong></p>
                                    <p>Pending: <strong><?php echo $stats['pending_registrations']; ?></strong></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h4>Mentor Applications</h4>
                                <div style="margin: 15px 0;">
                                    <p>Total Applications: <strong><?php echo $stats['mentor_applications']; ?></strong></p>
                                    <?php foreach ($stats['mentor_status'] as $status => $count): ?>
                                        <p><?php echo $status; ?>: <strong><?php echo $count; ?></strong></p>
                                    <?php endforeach; ?>
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
    
    <script src="/../../../public/assets/js/holidayProgramIndex.js"></script>
    <script>
    // Chart data from PHP
const statsData = <?php echo json_encode($stats ?? []); ?>;
const programId = <?php echo json_encode($current_program['id'] ?? 0); ?>;

// =================================================================
// TAB FUNCTIONALITY - Fixed and Enhanced
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

// Initialize tab functionality on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up tab click handlers
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
    console.log('Tab functionality initialized');
});

// =================================================================
// PROGRAM STATUS UPDATE - Fixed
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
            
            // Update enhanced status management section if it exists
            updateEnhancedStatusDisplay(status);
            
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

// Update enhanced status display if present
function updateEnhancedStatusDisplay(newStatus) {
    const statusIndicator = document.querySelector('.status-indicator');
    const statusText = document.querySelector('.status-text');
    
    if (statusIndicator && statusText) {
        if (newStatus == 1) {
            statusIndicator.className = 'status-indicator active';
            statusText.textContent = 'Registration Open';
        } else {
            statusIndicator.className = 'status-indicator inactive';
            statusText.textContent = 'Registration Closed';
        }
    }
}

// =================================================================
// ENHANCED STATUS MANAGEMENT FUNCTIONS
// =================================================================

function updateProgramStatusEnhanced() {
    const statusSelect = document.getElementById('registration-status-select');
    const updateBtn = document.getElementById('update-status-btn');
    
    if (!statusSelect || !updateBtn) {
        console.error('Enhanced status controls not found');
        return;
    }
    
    // Disable button and show loading
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'update_program_status');
    formData.append('program_id', programId);
    formData.append('registration_open', statusSelect.value);
    
    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Status updated successfully!', 'success');
            updateEnhancedStatusDisplay(data.new_status);
            
            // Reload page after short delay to refresh all data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        // Re-enable button
        updateBtn.disabled = false;
        updateBtn.innerHTML = '<i class="fas fa-save"></i> Update Status';
    });
}

function openRegistration() {
    const statusSelect = document.getElementById('registration-status-select') || 
                        document.getElementById('registration-status');
    if (statusSelect) {
        statusSelect.value = '1';
        if (document.getElementById('registration-status-select')) {
            updateProgramStatusEnhanced();
        } else {
            updateProgramStatus();
        }
    }
}

function closeRegistration() {
    if (confirm('Are you sure you want to close registration? This will prevent new participants from registering.')) {
        const statusSelect = document.getElementById('registration-status-select') || 
                            document.getElementById('registration-status');
        if (statusSelect) {
            statusSelect.value = '0';
            if (document.getElementById('registration-status-select')) {
                updateProgramStatusEnhanced();
            } else {
                updateProgramStatus();
            }
        }
    }
}

// =================================================================
// STATUS HISTORY MODAL
// =================================================================

function viewStatusHistory() {
    const modal = document.getElementById('statusHistoryModal');
    const content = document.getElementById('status-history-content');
    
    if (!modal || !content) {
        showNotification('Status history feature not available', 'error');
        return;
    }
    
    // Show modal
    modal.style.display = 'block';
    
    // Load history
    content.innerHTML = '<div class="loading">Loading history...</div>';
    
    fetch(`?action=get_status_history&program_id=${programId}`)
        .then(response => response.json())
        .then(history => {
            if (history.length > 0) {
                let historyHtml = '<div class="history-timeline">';
                history.forEach(entry => {
                    const statusText = entry.new_status == 1 ? 'Opened' : 'Closed';
                    const statusClass = entry.new_status == 1 ? 'success' : 'danger';
                    const date = new Date(entry.created_at).toLocaleString();
                    
                    historyHtml += `
                        <div class="history-item">
                            <div class="history-dot ${statusClass}"></div>
                            <div class="history-content">
                                <div class="history-action">Registration ${statusText}</div>
                                <div class="history-details">
                                    <small>By: ${entry.changed_by_name || 'System'}</small>
                                    <small>Date: ${date}</small>
                                    ${entry.change_reason ? `<small>Reason: ${entry.change_reason}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                historyHtml += '</div>';
                content.innerHTML = historyHtml;
            } else {
                content.innerHTML = '<p>No status change history available.</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p>Error loading history.</p>';
            console.error('Error:', error);
        });
}

function closeStatusHistoryModal() {
    const modal = document.getElementById('statusHistoryModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// =================================================================
// AUTOMATION SETTINGS
// =================================================================

function saveAutomationSetting(setting, value) {
    const formData = new FormData();
    formData.append('action', 'save_automation_setting');
    formData.append('program_id', programId);
    formData.append('setting', setting);
    formData.append('value', value ? 1 : 0);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Automation setting saved', 'success');
        } else {
            showNotification('Failed to save setting', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving automation setting:', error);
        showNotification('Network error', 'error');
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

// =================================================================
// REGISTRATION MANAGEMENT
// =================================================================

function filterRegistrations() {
    const searchTerm = document.getElementById('search-registrations')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('filter-status')?.value || '';
    const typeFilter = document.getElementById('filter-type')?.value || '';
    
    const rows = document.querySelectorAll('#registrations-table tbody tr');
    
    rows.forEach(row => {
        const name = row.cells[1]?.textContent.toLowerCase() || '';
        const email = row.cells[2]?.textContent.toLowerCase() || '';
        const status = row.dataset.status || '';
        const type = row.dataset.type || '';
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        row.style.display = matchesSearch && matchesStatus && matchesType ? '' : 'none';
    });
    
    updateSelectedCount();
}

function showBulkActions() {
    const bulkActions = document.getElementById('bulk-actions');
    if (bulkActions) {
        bulkActions.style.display = 'block';
    }
}

function hideBulkActions() {
    const bulkActions = document.getElementById('bulk-actions');
    if (bulkActions) {
        bulkActions.style.display = 'none';
    }
    
    // Uncheck all checkboxes
    document.querySelectorAll('.registration-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all');
    if (selectAll) selectAll.checked = false;
    updateSelectedCount();
}

function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.registration-checkbox');
    
    if (selectAll && checkboxes) {
        checkboxes.forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = selectAll.checked;
            }
        });
        updateSelectedCount();
    }
}

function updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.registration-checkbox:checked');
    const countElement = document.getElementById('selected-count');
    const count = selectedCheckboxes.length;
    
    if (countElement) {
        if (count > 0) {
            countElement.textContent = `${count} registration${count !== 1 ? 's' : ''} selected`;
            const bulkActions = document.getElementById('bulk-actions');
            if (bulkActions) bulkActions.style.display = 'block';
        } else {
            countElement.textContent = '';
        }
    }
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

function updateMentorStatus(attendeeId, status) {
    if (confirm(`Are you sure you want to ${status.toLowerCase()} this mentor application?`)) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_mentor_status&attendee_id=${attendeeId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Mentor status updated successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
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
                <p><strong>Status:</strong> <span class="status-badge ${attendee.registration_status}">${attendee.registration_status}</span></p>
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

function editAttendee(attendeeId) {
    window.location.href = `holidayProgramRegistration.php?program_id=${programId}&edit=1&attendee_id=${attendeeId}`;
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
    console.log('Admin Dashboard loaded successfully');
    
    // Initialize automation settings listeners
    const autoCloseCapacity = document.getElementById('auto-close-capacity');
    const autoCloseDate = document.getElementById('auto-close-date');
    
    if (autoCloseCapacity) {
        autoCloseCapacity.addEventListener('change', function() {
            saveAutomationSetting('auto_close_on_capacity', this.checked);
        });
    }
    
    if (autoCloseDate) {
        autoCloseDate.addEventListener('change', function() {
            saveAutomationSetting('auto_close_on_date', this.checked);
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
    
    // Global function assignments for onclick handlers
    window.showTab = showTab;
    window.updateProgramStatus = updateProgramStatus;
    window.updateProgramStatusEnhanced = updateProgramStatusEnhanced;
    window.openRegistration = openRegistration;
    window.closeRegistration = closeRegistration;
    window.viewStatusHistory = viewStatusHistory;
    window.closeStatusHistoryModal = closeStatusHistoryModal;
    window.exportRegistrations = exportRegistrations;
    window.showBulkEmailModal = showBulkEmailModal;
    window.filterRegistrations = filterRegistrations;
    window.showBulkActions = showBulkActions;
    window.hideBulkActions = hideBulkActions;
    window.toggleSelectAll = toggleSelectAll;
    window.updateSelectedCount = updateSelectedCount;
    window.updateStatus = updateStatus;
    window.updateMentorStatus = updateMentorStatus;
    window.viewAttendee = viewAttendee;
    window.editAttendee = editAttendee;
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
    
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }
`;

document.head.appendChild(dashboardStyle);

console.log('Dashboard JavaScript fully initialized with all functions');
    </script>
</body>
</html>