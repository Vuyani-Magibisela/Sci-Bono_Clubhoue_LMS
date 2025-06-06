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

// Initialize controller
$adminController = new HolidayProgramAdminController($conn);

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
        .admin-dashboard {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .program-selector {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .program-selector select {
            background: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            max-width: 400px;
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
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        .registrations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .registrations-table th,
        .registrations-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .registrations-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.confirmed { background: #d4edda; color: #155724; }
        .status-badge.canceled { background: #f8d7da; color: #721c24; }
        .status-badge.approved { background: #d1ecf1; color: #0c5460; }
        .status-badge.declined { background: #f5c6cb; color: #721c24; }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 0 2px;
            transition: all 0.3s;
        }
        
        .action-btn.primary { background: #007bff; color: white; }
        .action-btn.success { background: #28a745; color: white; }
        .action-btn.warning { background: #ffc107; color: #212529; }
        .action-btn.danger { background: #dc3545; color: white; }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .search-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-filters input,
        .search-filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .capacity-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .capacity-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .capacity-fill.warning { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .capacity-fill.danger { background: linear-gradient(90deg, #dc3545, #e83e8c); }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .bulk-actions.active {
            display: block;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .tab-navigation {
                flex-wrap: wrap;
            }
            
            .search-filters {
                flex-direction: column;
            }
            
            .registrations-table {
                font-size: 0.85rem;
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
                
                <!-- Program Status Control -->
                <div class="stat-card" style="margin-bottom: 30px;">
                    <h3><i class="fas fa-toggle-on"></i> Program Status</h3>
                    <div style="display: flex; align-items: center; gap: 20px; margin-top: 15px;">
                        <div>
                            <label>Registration Status:</label>
                            <select id="registration-status" onchange="updateProgramStatus()">
                                <option value="1" <?php echo $current_program['registration_open'] ? 'selected' : ''; ?>>Open</option>
                                <option value="0" <?php echo !$current_program['registration_open'] ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        <div>
                            <button class="action-btn primary" onclick="showBulkEmailModal()">
                                <i class="fas fa-envelope"></i> Send Bulk Email
                            </button>
                            <button class="action-btn success" onclick="exportRegistrations()">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Capacity Overview -->
                <?php if ($capacity_info): ?>
                <div class="stat-card" style="margin-bottom: 30px;">
                    <h3><i class="fas fa-users"></i> Capacity Overview</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 15px;">
                        <div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Members:</span>
                                <span><?php echo $capacity_info['member_registered']; ?>/<?php echo $capacity_info['member_capacity']; ?></span>
                            </div>
                            <div class="capacity-bar">
                                <div class="capacity-fill <?php echo $capacity_info['member_percentage'] > 80 ? 'warning' : ''; ?> <?php echo $capacity_info['is_member_full'] ? 'danger' : ''; ?>" 
                                     style="width: <?php echo min($capacity_info['member_percentage'], 100); ?>%;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between;">
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
                            <i class="fas fa-list"></i> Registrations
                        </button>
                        <button class="tab-btn" onclick="showTab('workshops')">
                            <i class="fas fa-laptop-code"></i> Workshops
                        </button>
                        <button class="tab-btn" onclick="showTab('statistics')">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </button>
                        <button class="tab-btn" onclick="showTab('mentors')">
                            <i class="fas fa-chalkboard-teacher"></i> Mentors
                        </button>
                    </div>
                    
                    <!-- Registrations Tab -->
                    <div id="registrations" class="tab-content active">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>All Registrations (<?php echo count($registrations); ?>)</h3>
                            <div>
                                <button class="action-btn primary" onclick="showBulkActions()">
                                    <i class="fas fa-tasks"></i> Bulk Actions
                                </button>
                            </div>
                        </div>
                        
                        <!-- Search and Filters -->
                        <div class="search-filters">
                            <input type="text" id="search-registrations" placeholder="Search by name or email..." onkeyup="filterRegistrations()">
                            <select id="filter-status" onchange="filterRegistrations()">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                            <select id="filter-type" onchange="filterRegistrations()">
                                <option value="">All Types</option>
                                <option value="member">Members</option>
                                <option value="mentor">Mentors</option>
                            </select>
                        </div>
                        
                        <!-- Bulk Actions Panel -->
                        <div id="bulk-actions" class="bulk-actions">
                            <h4>Bulk Actions</h4>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <select id="bulk-action-select">
                                    <option value="">Choose action...</option>
                                    <option value="confirm">Confirm Registrations</option>
                                    <option value="cancel">Cancel Registrations</option>
                                    <option value="email">Send Email</option>
                                </select>
                                <button class="action-btn primary" onclick="executeBulkAction()">Apply</button>
                                <button class="action-btn" onclick="hideBulkActions()">Cancel</button>
                            </div>
                        </div>
                        
                        <!-- Registrations Table -->
                        <div style="overflow-x: auto;">
                            <table class="registrations-table" id="registrations-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Workshops</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $registration): ?>
                                    <tr data-id="<?php echo $registration['id']; ?>" 
                                        data-status="<?php echo $registration['registration_status']; ?>"
                                        data-type="<?php echo $registration['mentor_registration'] ? 'mentor' : 'member'; ?>">
                                        <td><input type="checkbox" class="registration-checkbox" value="<?php echo $registration['id']; ?>"></td>
                                        <td><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                        <td>
                                            <?php if ($registration['mentor_registration']): ?>
                                                <span class="status-badge approved">Mentor</span>
                                            <?php else: ?>
                                                <span class="status-badge info">Member</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <span class="status-badge <?php echo $registration['registration_status']; ?>">
                                                <?php echo ucfirst($registration['registration_status']); ?>
                                            </span>
                                            <?php if ($registration['mentor_registration'] && $registration['mentor_status']): ?>
                                                <br><small class="status-badge <?php echo strtolower($registration['mentor_status']); ?>">
                                                    <?php echo $registration['mentor_status']; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $registration['assigned_workshops'] ?? 'Not assigned'; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></td>                                        
                                        
                                        <!-- Registration -->
                                        <td>
                                            <button class="action-btn primary" onclick="viewAttendee(<?php echo $registration['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn success" onclick="editAttendee(<?php echo $registration['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($registration['registration_status'] === 'pending'): ?>
                                            <button class="action-btn success" onclick="updateStatus(<?php echo $registration['id']; ?>, 'confirmed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>     

                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Workshops Tab -->
                    <div id="workshops" class="tab-content">
                        <h3>Workshop Management</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                            <?php foreach ($workshops as $workshop): ?>
                            <div class="stat-card">
                                <h4><?php echo htmlspecialchars($workshop['title']); ?></h4>
                                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($workshop['instructor']); ?></p>
                                <p><strong>Capacity:</strong> <?php echo $workshop['enrolled_count']; ?>/<?php echo $workshop['max_participants']; ?></p>
                                <div class="capacity-bar">
                                    <div class="capacity-fill" style="width: <?php echo ($workshop['max_participants'] > 0) ? min(($workshop['enrolled_count'] / $workshop['max_participants']) * 100, 100) : 0; ?>%;"></div>
                                </div>
                                <p><strong>Assigned Mentors:</strong> <?php echo $workshop['assigned_mentors']; ?></p>
                                <div style="margin-top: 15px;">
                                    <button class="action-btn primary" onclick="manageWorkshop(<?php echo $workshop['id']; ?>)">
                                        <i class="fas fa-cog"></i> Manage
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Statistics Tab -->
                    <div id="statistics" class="tab-content">
                        <h3>Program Statistics</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                            <div class="stat-card">
                                <h4>Gender Distribution</h4>
                                <div class="chart-container">
                                    <canvas id="genderChart"></canvas>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h4>Age Distribution</h4>
                                <div class="chart-container">
                                    <canvas id="ageChart"></canvas>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h4>Registration Timeline</h4>
                                <div class="chart-container">
                                    <canvas id="timelineChart"></canvas>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h4>Workshop Enrollments</h4>
                                <div class="chart-container">
                                    <canvas id="workshopChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mentors Tab -->
                    <div id="mentors" class="tab-content">
                        <h3>Mentor Applications</h3>
                        <div style="overflow-x: auto; margin-top: 20px;">
                            <table class="registrations-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Preferred Workshop</th>
                                        <th>Experience</th>
                                        <th>Application Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $registration): ?>
                                        <?php if ($registration['mentor_registration']): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo strtolower($registration['mentor_status'] ?? 'pending'); ?>">
                                                    <?php echo $registration['mentor_status'] ?? 'Pending'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $preferredWorkshop = '';
                                                foreach ($workshops as $workshop) {
                                                    if ($workshop['id'] == $registration['mentor_workshop_preference']) {
                                                        $preferredWorkshop = $workshop['title'];
                                                        break;
                                                    }
                                                }
                                                echo htmlspecialchars($preferredWorkshop ?: 'Not specified');
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($registration['experience'] ?? '', 0, 50)) . (strlen($registration['experience'] ?? '') > 50 ? '...' : ''); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></td>
                                            <td>
                                                <?php if (($registration['mentor_status'] ?? '') === 'Pending'): ?>
                                                <button class="action-btn success" onclick="updateMentorStatus(<?php echo $registration['id']; ?>, 'Approved')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="action-btn danger" onclick="updateMentorStatus(<?php echo $registration['id']; ?>, 'Declined')">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                                <?php endif; ?>
                                                <button class="action-btn primary" onclick="viewAttendee(<?php echo $registration['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- Attendee Details Modal -->
    <div id="attendeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Attendee Details</h2>
                <span class="close" onclick="closeModal('attendeeModal')">&times;</span>
            </div>
            <div id="attendeeDetails">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Bulk Email Modal -->
    <div id="bulkEmailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Send Bulk Email</h2>
                <span class="close" onclick="closeModal('bulkEmailModal')">&times;</span>
            </div>
            <form id="bulkEmailForm">
                <div style="margin-bottom: 20px;">
                    <label>Recipients:</label>
                    <select name="recipients" style="width: 100%; padding: 10px; margin-top: 5px;">
                        <option value="all">All Participants</option>
                        <option value="members">Members Only</option>
                        <option value="mentors">Mentors Only</option>
                        <option value="confirmed">Confirmed Registrations Only</option>
                    </select>
                </div>
                <div style="margin-bottom: 20px;">
                    <label>Subject:</label>
                    <input type="text" name="subject" required style="width: 100%; padding: 10px; margin-top: 5px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label>Message:</label>
                    <textarea name="message" rows="8" required style="width: 100%; padding: 10px; margin-top: 5px;"></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="action-btn" onclick="closeModal('bulkEmailModal')">Cancel</button>
                    <button type="submit" class="action-btn primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Chart data from PHP
        const statsData = <?php echo json_encode($stats ?? []); ?>;
        const programId = <?php echo json_encode($current_program['id'] ?? 0); ?>;
        
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Initialize charts if statistics tab is shown
            if (tabName === 'statistics') {
                setTimeout(initializeCharts, 100);
            }
        }
        
        // Filter registrations
        function filterRegistrations() {
            const searchTerm = document.getElementById('search-registrations').value.toLowerCase();
            const statusFilter = document.getElementById('filter-status').value;
            const typeFilter = document.getElementById('filter-type').value;
            
            const rows = document.querySelectorAll('#registrations-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const status = row.dataset.status;
                const type = row.dataset.type;
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesType = !typeFilter || type === typeFilter;
                
                row.style.display = matchesSearch && matchesStatus && matchesType ? '' : 'none';
            });
        }
        
        // Bulk actions
        function showBulkActions() {
            document.getElementById('bulk-actions').classList.add('active');
        }
        
        function hideBulkActions() {
            document.getElementById('bulk-actions').classList.remove('active');
            // Uncheck all checkboxes
            document.querySelectorAll('.registration-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.registration-checkbox');
            
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
        }
        
        function executeBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            const selectedIds = Array.from(document.querySelectorAll('.registration-checkbox:checked'))
                .map(cb => cb.value);
            
            if (!action || selectedIds.length === 0) {
                alert('Please select an action and at least one registration.');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} registration(s)?`)) {
                // Handle bulk action via AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=bulk_${action}&ids=${selectedIds.join(',')}&program_id=${programId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
        
        // Update program status
        function updateProgramStatus() {
            const status = document.getElementById('registration-status').value;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_program_status&program_id=${programId}&registration_open=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Program status updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Update individual registration status
        function updateStatus(attendeeId, status) {
            if (confirm(`Are you sure you want to ${status} this registration?`)) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_registration_status&attendee_id=${attendeeId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
        
        // Update mentor status
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
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
        
        // View attendee details
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
                    document.getElementById('attendeeModal').style.display = 'block';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        function displayAttendeeDetails(attendee) {
            const html = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4>Personal Information</h4>
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
                        <h4>Contact Information</h4>
                        <p><strong>Address:</strong> ${attendee.address || 'Not provided'}</p>
                        <p><strong>City:</strong> ${attendee.city || 'Not provided'}</p>
                        <p><strong>Province:</strong> ${attendee.province || 'Not provided'}</p>
                        ${!attendee.mentor_registration ? `
                            <h4>Guardian Information</h4>
                            <p><strong>Guardian:</strong> ${attendee.guardian_name || 'Not provided'}</p>
                            <p><strong>Relationship:</strong> ${attendee.guardian_relationship || 'Not provided'}</p>
                            <p><strong>Guardian Phone:</strong> ${attendee.guardian_phone || 'Not provided'}</p>
                            <p><strong>Guardian Email:</strong> ${attendee.guardian_email || 'Not provided'}</p>
                        ` : ''}
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h4>Program Information</h4>
                    <p><strong>Registration Status:</strong> <span class="status-badge ${attendee.registration_status}">${attendee.registration_status}</span></p>
                    <p><strong>Type:</strong> ${attendee.mentor_registration ? 'Mentor' : 'Member'}</p>
                    ${attendee.mentor_registration ? `
                        <p><strong>Mentor Status:</strong> <span class="status-badge ${(attendee.mentor_status || '').toLowerCase()}">${attendee.mentor_status || 'Pending'}</span></p>
                        <p><strong>Experience:</strong> ${attendee.experience || 'Not provided'}</p>
                        <p><strong>Availability:</strong> ${attendee.availability || 'Not provided'}</p>
                    ` : `
                        <p><strong>Why Interested:</strong> ${attendee.why_interested || 'Not provided'}</p>
                        <p><strong>Experience Level:</strong> ${attendee.experience_level || 'Not provided'}</p>
                    `}
                    <p><strong>Enrolled Workshops:</strong> ${attendee.enrolled_workshops || 'None assigned'}</p>
                    <p><strong>Registration Date:</strong> ${new Date(attendee.created_at).toLocaleDateString()}</p>
                </div>
                
                ${attendee.medical_conditions || attendee.allergies || attendee.dietary_restrictions ? `
                    <div style="margin-top: 20px;">
                        <h4>Medical Information</h4>
                        ${attendee.medical_conditions ? `<p><strong>Medical Conditions:</strong> ${attendee.medical_conditions}</p>` : ''}
                        ${attendee.allergies ? `<p><strong>Allergies:</strong> ${attendee.allergies}</p>` : ''}
                        ${attendee.dietary_restrictions ? `<p><strong>Dietary Restrictions:</strong> ${attendee.dietary_restrictions}</p>` : ''}
                    </div>
                ` : ''}
            `;
            
            document.getElementById('attendeeDetails').innerHTML = html;
        }
        
        // Edit attendee
        function editAttendee(attendeeId) {
            // Redirect to edit page or open edit modal
            window.location.href = `holidayProgramRegistration.php?program_id=${programId}&edit=1&attendee_id=${attendeeId}`;
        }
        
        // Manage workshop
        function manageWorkshop(workshopId) {
            // Redirect to workshop management page
            window.location.href = `workshop-management.php?workshop_id=${workshopId}&program_id=${programId}`;
        }
        
        // Export registrations
        function exportRegistrations() {
            window.location.href = `?export=csv&program_id=${programId}`;
        }
        
        // Bulk email modal
        function showBulkEmailModal() {
            document.getElementById('bulkEmailModal').style.display = 'block';
        }
        
        // Handle bulk email form
        document.getElementById('bulkEmailForm').addEventListener('submit', function(e) {
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
                    alert(`Email queued for ${data.recipients_count} recipients!`);
                    closeModal('bulkEmailModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // Modal functions
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Initialize charts
        function initializeCharts() {
            if (typeof Chart === 'undefined' || !statsData) return;
            
            // Gender distribution chart
            const genderCtx = document.getElementById('genderChart');
            if (genderCtx) {
                new Chart(genderCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(statsData.gender_distribution || {}),
                        datasets: [{
                            data: Object.values(statsData.gender_distribution || {}),
                            backgroundColor: ['#007bff', '#e83e8c', '#6f42c1']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Age distribution chart
            const ageCtx = document.getElementById('ageChart');
            if (ageCtx) {
                new Chart(ageCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(statsData.age_distribution || {}),
                        datasets: [{
                            label: 'Participants',
                            data: Object.values(statsData.age_distribution || {}),
                            backgroundColor: '#28a745'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
            
            // Registration timeline chart
            const timelineCtx = document.getElementById('timelineChart');
            if (timelineCtx && statsData.registration_timeline) {
                new Chart(timelineCtx, {
                    type: 'line',
                    data: {
                        labels: statsData.registration_timeline.map(item => item.date),
                        datasets: [{
                            label: 'Registrations',
                            data: statsData.registration_timeline.map(item => item.registrations),
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
            
            // Workshop enrollments chart
            const workshopCtx = document.getElementById('workshopChart');
            if (workshopCtx && statsData.workshop_enrollments) {
                const workshops = Object.values(statsData.workshop_enrollments);
                new Chart(workshopCtx, {
                    type: 'bar',
                    data: {
                        labels: workshops.map(w => w.title),
                        datasets: [{
                            label: 'Enrolled',
                            data: workshops.map(w => w.enrolled),
                            backgroundColor: '#6c63ff'
                        }, {
                            label: 'Capacity',
                            data: workshops.map(w => w.max_participants),
                            backgroundColor: 'rgba(108, 99, 255, 0.3)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts if statistics tab is visible
            if (document.getElementById('statistics').classList.contains('active')) {
                setTimeout(initializeCharts, 100);
            }
        });
    </script>
</body>
</html>