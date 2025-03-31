<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php?redirect=app/Views/Programs/holiday-dashboard.php");
    exit;
}

// Include database connection
require_once '../../../server.php';

// Get user information
$userId = $_SESSION['id'];
$userType = $_SESSION['user_type'] ?? 'member';

// Function to get registrations for a user
function getUserRegistrations($conn, $userId) {
    $sql = "SELECT a.*, p.title as program_title, p.dates as program_dates, p.registration_open, 
                   (SELECT COUNT(*) FROM holiday_workshop_enrollment e WHERE e.attendee_id = a.id) as workshops_enrolled
            FROM holiday_program_attendees a 
            JOIN holiday_programs p ON a.program_id = p.id
            WHERE a.user_id = ? OR a.email = ?
            ORDER BY p.start_date DESC";
    
    $email = $_SESSION['email'] ?? '';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    return $registrations;
}

// Function to get mentor registrations
function getMentorRegistrations($conn, $userId) {
    $sql = "SELECT a.*, p.title as program_title, p.dates as program_dates, 
                   w.title as workshop_title, w.max_participants, 
                   (SELECT COUNT(*) FROM holiday_workshop_enrollment e 
                    JOIN holiday_program_attendees att ON e.attendee_id = att.id 
                    WHERE e.workshop_id = a.mentor_workshop_preference AND att.program_id = a.program_id) as enrolled_count,
                   m.experience, m.availability
            FROM holiday_program_attendees a 
            JOIN holiday_programs p ON a.program_id = p.id
            LEFT JOIN holiday_program_workshops w ON a.mentor_workshop_preference = w.id
            LEFT JOIN holiday_program_mentor_details m ON m.attendee_id = a.id
            WHERE (a.user_id = ? OR a.email = ?) AND a.mentor_registration = 1
            ORDER BY p.start_date DESC";
    
    $email = $_SESSION['email'] ?? '';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    return $registrations;
}

// Function to get workshops for a specific registration
function getRegistrationWorkshops($conn, $attendeeId) {
    $sql = "SELECT e.*, w.title as workshop_title, w.description, w.instructor,
                   (SELECT COUNT(*) FROM holiday_program_projects p WHERE p.attendee_id = e.attendee_id AND p.workshop_id = e.workshop_id) as project_count
            FROM holiday_workshop_enrollment e
            JOIN holiday_program_workshops w ON e.workshop_id = w.id
            WHERE e.attendee_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attendeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $workshops = [];
    while ($row = $result->fetch_assoc()) {
        $workshops[] = $row;
    }
    
    return $workshops;
}

// Function to get attendance for a specific registration
function getAttendanceRecords($conn, $attendeeId) {
    $sql = "SELECT a.*, w.title as workshop_title
            FROM holiday_program_attendance a
            JOIN holiday_program_workshops w ON a.workshop_id = w.id
            WHERE a.attendee_id = ?
            ORDER BY a.date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attendeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    return $attendance;
}

// Function to get projects for a specific registration
function getProjects($conn, $attendeeId) {
    $sql = "SELECT p.*, w.title as workshop_title
            FROM holiday_program_projects p
            LEFT JOIN holiday_program_workshops w ON p.workshop_id = w.id
            WHERE p.attendee_id = ?
            ORDER BY p.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attendeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    return $projects;
}

// Function to get program statistics (for admin)
function getProgramStats($conn, $programId) {
    // Base statistics
    $stats = [
        'total' => 0,
        'male' => 0,
        'female' => 0,
        'other' => 0,
        'age_groups' => [
            '9-12' => 0,
            '13-15' => 0,
            '16-18' => 0,
            '19+' => 0
        ],
        'workshops' => [],
        'attendance' => [
            'total_days' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ]
    ];
    
    // Get basic stats
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female,
                SUM(CASE WHEN gender NOT IN ('Male', 'Female') OR gender IS NULL THEN 1 ELSE 0 END) as other
            FROM holiday_program_attendees
            WHERE program_id = ? AND mentor_registration = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['total'] = $row['total'];
        $stats['male'] = $row['male'];
        $stats['female'] = $row['female'];
        $stats['other'] = $row['other'];
    }
    
    // Get age distribution
    $sql = "SELECT
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) as age_9_12,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 13 AND 15 THEN 1 ELSE 0 END) as age_13_15,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 16 AND 18 THEN 1 ELSE 0 END) as age_16_18,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 19 THEN 1 ELSE 0 END) as age_19_plus
            FROM holiday_program_attendees
            WHERE program_id = ? AND mentor_registration = 0 AND date_of_birth IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['age_groups']['9-12'] = $row['age_9_12'];
        $stats['age_groups']['13-15'] = $row['age_13_15'];
        $stats['age_groups']['16-18'] = $row['age_16_18'];
        $stats['age_groups']['19+'] = $row['age_19_plus'];
    }
    
    // Get workshop stats
    $sql = "SELECT w.id, w.title, w.max_participants,
                (SELECT COUNT(*) FROM holiday_workshop_enrollment e 
                 JOIN holiday_program_attendees a ON e.attendee_id = a.id 
                 WHERE e.workshop_id = w.id AND a.program_id = ?) as enrolled
            FROM holiday_program_workshops w
            WHERE w.program_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $programId, $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $stats['workshops'][$row['id']] = [
            'title' => $row['title'],
            'max' => $row['max_participants'],
            'enrolled' => $row['enrolled'],
            'fill_percentage' => ($row['max_participants'] > 0) ? round(($row['enrolled'] / $row['max_participants']) * 100) : 0
        ];
    }
    
    // Get attendance stats
    $sql = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
            FROM holiday_program_attendance a
            JOIN holiday_program_attendees att ON a.attendee_id = att.id
            WHERE att.program_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats['attendance']['total_days'] = $row['total_records'];
        $stats['attendance']['present'] = $row['present'];
        $stats['attendance']['absent'] = $row['absent'];
        $stats['attendance']['late'] = $row['late'];
        $stats['attendance']['excused'] = $row['excused'];
    }
    
    return $stats;
}

// Get all programs for admin filtering
function getAllPrograms($conn) {
    $sql = "SELECT * FROM holiday_programs ORDER BY start_date DESC";
    $result = $conn->query($sql);
    
    $programs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    
    return $programs;
}

// Get user registrations
$userRegistrations = getUserRegistrations($conn, $userId);

// For admin and mentors, get additional data
$isMentor = ($userType === 'mentor' || $userType === 'admin');
$mentorRegistrations = $isMentor ? getMentorRegistrations($conn, $userId) : [];

// Get program ID from URL or use the first registration
$currentProgramId = isset($_GET['program']) ? intval($_GET['program']) : 
                   (!empty($userRegistrations) ? $userRegistrations[0]['program_id'] : 0);

// For admin, get statistics
$programStats = ($userType === 'admin' && $currentProgramId) ? getProgramStats($conn, $currentProgramId) : null;
$allPrograms = ($userType === 'admin') ? getAllPrograms($conn) : [];

// Get detailed data for the selected registration
$selectedRegistration = null;
$registrationWorkshops = [];
$attendanceRecords = [];
$projects = [];

if (!empty($userRegistrations)) {
    foreach ($userRegistrations as $registration) {
        if ($registration['program_id'] == $currentProgramId) {
            $selectedRegistration = $registration;
            break;
        }
    }
    
    if ($selectedRegistration) {
        $registrationWorkshops = getRegistrationWorkshops($conn, $selectedRegistration['id']);
        $attendanceRecords = getAttendanceRecords($conn, $selectedRegistration['id']);
        $projects = getProjects($conn, $selectedRegistration['id']);
    }
}

// Format helper function for dates
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Program Dashboard - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="../../../public/assets/css/holidayDahsboard.css">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js for statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <main class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <h1>Holiday Program Dashboard</h1>
                
                <?php if ($userType === 'admin'): ?>
                <!-- Program selector for admin -->
                <div class="program-selector">
                    <label for="program-select">Select Program:</label>
                    <select id="program-select" onchange="window.location.href='holiday-dashboard.php?program='+this.value">
                        <?php foreach ($allPrograms as $program): ?>
                            <option value="<?php echo $program['id']; ?>" <?php echo ($program['id'] == $currentProgramId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($program['term'] . ': ' . $program['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-wrapper">
                <!-- Sidebar navigation -->
                <div class="dashboard-sidebar">
                    <div class="sidebar-user">
                        <div class="user-avatar">
                            <?php if (isset($_SESSION['name'])): ?>
                                <span><?php echo substr($_SESSION['name'], 0, 1); ?></span>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($_SESSION['name'] . ' ' . ($_SESSION['surname'] ?? '')); ?></h3>
                            <p class="user-role"><?php echo ucfirst($userType); ?></p>
                        </div>
                    </div>
                    
                    <nav class="sidebar-nav">
                        <ul>
                            <li class="active">
                                <a href="#overview" class="nav-link" data-section="overview">
                                    <i class="fas fa-home"></i> Overview
                                </a>
                            </li>
                            
                            <?php if (!empty($userRegistrations)): ?>
                            <li>
                                <a href="#registrations" class="nav-link" data-section="registrations">
                                    <i class="fas fa-clipboard-list"></i> My Registrations
                                </a>
                            </li>
                            
                            <li>
                                <a href="#workshops" class="nav-link" data-section="workshops">
                                    <i class="fas fa-laptop-code"></i> Workshops
                                </a>
                            </li>
                            
                            <li>
                                <a href="#projects" class="nav-link" data-section="projects">
                                    <i class="fas fa-project-diagram"></i> Projects
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($isMentor): ?>
                            <li>
                                <a href="#mentor" class="nav-link" data-section="mentor">
                                    <i class="fas fa-chalkboard-teacher"></i> Mentor Portal
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($userType === 'admin'): ?>
                            <li>
                                <a href="#statistics" class="nav-link" data-section="statistics">
                                    <i class="fas fa-chart-bar"></i> Statistics
                                </a>
                            </li>
                            
                            <li>
                                <a href="#management" class="nav-link" data-section="management">
                                    <i class="fas fa-cogs"></i> Program Management
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                
                <!-- Main dashboard content -->
                <div class="dashboard-content">
                    <!-- Overview Section -->
                    <section id="overview" class="dashboard-section active">
                        <div class="section-header">
                            <h2>Dashboard Overview</h2>
                        </div>
                        
                        <div class="overview-grid">
                            <?php if (!empty($userRegistrations)): ?>
                                <div class="overview-card registrations-card">
                                    <div class="card-icon"><i class="fas fa-clipboard-check"></i></div>
                                    <div class="card-content">
                                        <h3>Your Registrations</h3>
                                        <p class="card-value"><?php echo count($userRegistrations); ?></p>
                                        <p class="card-description">Holiday programs you're registered for</p>
                                    </div>
                                </div>
                                
                                <div class="overview-card workshops-card">
                                    <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                                    <div class="card-content">
                                        <h3>Workshops</h3>
                                        <p class="card-value"><?php echo count($registrationWorkshops); ?></p>
                                        <p class="card-description">Workshops you're enrolled in</p>
                                    </div>
                                </div>
                                
                                <div class="overview-card projects-card">
                                    <div class="card-icon"><i class="fas fa-project-diagram"></i></div>
                                    <div class="card-content">
                                        <h3>Projects</h3>
                                        <p class="card-value"><?php echo count($projects); ?></p>
                                        <p class="card-description">Projects you've submitted</p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($attendanceRecords)): ?>
                                <div class="overview-card attendance-card">
                                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                                    <div class="card-content">
                                        <h3>Attendance</h3>
                                        <p class="card-value"><?php 
                                            $presentCount = array_reduce($attendanceRecords, function($carry, $item) {
                                                return $carry + ($item['status'] === 'present' ? 1 : 0);
                                            }, 0);
                                            echo $presentCount . '/' . count($attendanceRecords); 
                                        ?></p>
                                        <p class="card-description">Days attended</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="overview-empty">
                                    <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
                                    <h3>No Registrations Yet</h3>
                                    <p>You haven't registered for any holiday programs yet.</p>
                                    <a href="holidayProgramIndex.php" class="cta-button">Browse Programs</a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($isMentor && !empty($mentorRegistrations)): ?>
                                <div class="overview-card mentor-card">
                                    <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                    <div class="card-content">
                                        <h3>Mentor Status</h3>
                                        <p class="card-value"><?php 
                                            $approvedCount = array_reduce($mentorRegistrations, function($carry, $item) {
                                                return $carry + ($item['mentor_status'] === 'Approved' ? 1 : 0);
                                            }, 0);
                                            echo $approvedCount . '/' . count($mentorRegistrations); 
                                        ?></p>
                                        <p class="card-description">Approved mentor applications</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($userType === 'admin' && $programStats): ?>
                                <div class="overview-card admin-card">
                                    <div class="card-icon"><i class="fas fa-users"></i></div>
                                    <div class="card-content">
                                        <h3>Total Participants</h3>
                                        <p class="card-value"><?php echo $programStats['total']; ?></p>
                                        <p class="card-description">Members registered for current program</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($userRegistrations)): ?>
                        <div class="upcoming-section">
                            <h3>Upcoming Holiday Programs</h3>
                            <div class="program-cards">
                                <?php 
                                $upcomingPrograms = array_filter($userRegistrations, function($reg) {
                                    return strtotime($reg['end_date'] ?? '') >= time();
                                });
                                
                                if (!empty($upcomingPrograms)):
                                    foreach ($upcomingPrograms as $program): 
                                ?>
                                    <div class="program-card">
                                        <div class="program-card-header">
                                            <h4><?php echo htmlspecialchars($program['program_title']); ?></h4>
                                            <span class="program-dates"><?php echo htmlspecialchars($program['program_dates']); ?></span>
                                        </div>
                                        <div class="program-card-body">
                                            <div class="program-status">
                                                <span class="status-badge <?php echo strtolower($program['registration_status']); ?>">
                                                    <?php echo ucfirst($program['registration_status']); ?>
                                                </span>
                                            </div>
                                            <div class="program-workshops">
                                                <strong>Workshops:</strong> <?php echo $program['workshops_enrolled']; ?> enrolled
                                            </div>
                                            <div class="program-actions">
                                                <a href="holiday-program-details.php?id=<?php echo $program['program_id']; ?>" class="secondary-button">View Program</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <div class="no-upcoming-programs">
                                        <p>You don't have any upcoming holiday programs.</p>
                                        <a href="holidayProgramIndex.php" class="cta-button">Browse Programs</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Registrations Section -->
                    <?php if (!empty($userRegistrations)): ?>
                    <section id="registrations" class="dashboard-section">
                        <div class="section-header">
                            <h2>My Program Registrations</h2>
                        </div>
                        
                        <div class="registrations-list">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Program</th>
                                            <th>Dates</th>
                                            <th>Status</th>
                                            <th>Workshops</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($userRegistrations as $registration): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($registration['program_title']); ?></td>
                                            <td><?php echo htmlspecialchars($registration['program_dates']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo strtolower($registration['registration_status']); ?>">
                                                    <?php echo ucfirst($registration['registration_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $registration['workshops_enrolled']; ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="?program=<?php echo $registration['program_id']; ?>#workshops" 
                                                       class="action-btn" title="View Workshops">
                                                        <i class="fas fa-laptop-code"></i>
                                                    </a>
                                                    <a href="holidayProgramRegistration.php?program_id=<?php echo $registration['program_id']; ?>&edit=1" 
                                                       class="action-btn" title="Edit Registration">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="holiday-program-details.php?id=<?php echo $registration['program_id']; ?>" 
                                                       class="action-btn" title="View Program Details">
                                                        <i class="fas fa-info-circle"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Workshops Section -->
                    <section id="workshops" class="dashboard-section">
                        <div class="section-header">
                            <h2>My Workshops</h2>
                            <?php if ($selectedRegistration): ?>
                            <div class="section-subtitle">
                                For <?php echo htmlspecialchars($selectedRegistration['program_title']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($registrationWorkshops)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-laptop-code"></i></div>
                            <h3>No Workshop Enrollments</h3>
                            <p>You haven't been assigned to any workshops for this program yet.</p>
                            <?php if ($selectedRegistration && $selectedRegistration['registration_status'] === 'confirmed'): ?>
                            <a href="holiday-workshop-selection.php?program=<?php echo $currentProgramId; ?>" class="cta-button">
                                Select Workshops
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="workshops-grid">
                            <?php foreach ($registrationWorkshops as $workshop): ?>
                            <div class="workshop-card">
                                <div class="workshop-header">
                                    <h3><?php echo htmlspecialchars($workshop['workshop_title']); ?></h3>
                                    <div class="workshop-meta">
                                        <span class="workshop-instructor">
                                            <i class="fas fa-chalkboard-teacher"></i> 
                                            <?php echo htmlspecialchars($workshop['instructor'] ?? 'TBA'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="workshop-body">
                                    <p><?php echo htmlspecialchars($workshop['description'] ?? 'No description available.'); ?></p>
                                    
                                    <div class="workshop-stats">
                                        <div class="stat-item">
                                            <span class="stat-label">Projects:</span>
                                            <span class="stat-value"><?php echo $workshop['project_count']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Status:</span>
                                            <span class="stat-value">
                                                <span class="status-badge <?php echo strtolower($workshop['attendance_status'] ?? 'registered'); ?>">
                                                    <?php echo ucfirst($workshop['attendance_status'] ?? 'Registered'); ?>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="workshop-footer">
                                    <a href="workshop-detail.php?id=<?php echo $workshop['workshop_id']; ?>" class="secondary-button">
                                        Workshop Details
                                    </a>
                                    <a href="project-submission.php?workshop=<?php echo $workshop['workshop_id']; ?>" class="primary-button">
                                        Submit Project
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($attendanceRecords)): ?>
                        <div class="attendance-section">
                            <h3>Attendance Records</h3>
                            
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Workshop</th>
                                            <th>Check-in Time</th>
                                            <th>Check-out Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceRecords as $record): ?>
                                        <tr>
                                            <td><?php echo formatDate($record['date']); ?></td>
                                            <td><?php echo htmlspecialchars($record['workshop_title']); ?></td>
                                            <td><?php echo $record['check_in_time'] ? date('h:i A', strtotime($record['check_in_time'])) : 'Not checked in'; ?></td>
                                            <td><?php echo $record['check_out_time'] ? date('h:i A', strtotime($record['check_out_time'])) : 'Not checked out'; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo strtolower($record['status']); ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>
                    
                    <!-- Projects Section -->
                    <section id="projects" class="dashboard-section">
                        <div class="section-header">
                            <h2>My Projects</h2>
                            <?php if ($selectedRegistration): ?>
                            <div class="section-subtitle">
                                For <?php echo htmlspecialchars($selectedRegistration['program_title']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($projects)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-project-diagram"></i></div>
                            <h3>No Projects Yet</h3>
                            <p>You haven't submitted any projects for this program yet.</p>
                            <?php if (!empty($registrationWorkshops)): ?>
                            <a href="project-submission.php?workshop=<?php echo $registrationWorkshops[0]['workshop_id']; ?>" class="cta-button">
                                Submit a Project
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="projects-grid">
                            <?php foreach ($projects as $project): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                    <div class="project-meta">
                                        <span class="project-workshop">
                                            <i class="fas fa-laptop-code"></i> 
                                            <?php echo htmlspecialchars($project['workshop_title'] ?? 'General'); ?>
                                        </span>
                                        <span class="project-date">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php echo formatDate($project['submission_date']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="project-body">
                                    <p><?php echo htmlspecialchars($project['description'] ?? 'No description provided.'); ?></p>
                                    
                                    <?php if ($project['file_path']): ?>
                                    <div class="project-attachment">
                                        <i class="fas fa-paperclip"></i> 
                                        <a href="<?php echo htmlspecialchars($project['file_path']); ?>" target="_blank">
                                            View Attachment (<?php echo htmlspecialchars($project['file_type']); ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($project['feedback']): ?>
                                    <div class="project-feedback">
                                        <h4>Feedback:</h4>
                                        <p><?php echo htmlspecialchars($project['feedback']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($project['rating']): ?>
                                    <div class="project-rating">
                                        <span>Rating:</span>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo ($i <= $project['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="project-footer">
                                    <span class="status-badge <?php echo strtolower($project['status']); ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                    <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="secondary-button">
                                        Edit Project
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Mentor Portal Section -->
                    <?php if ($isMentor): ?>
                    <section id="mentor" class="dashboard-section">
                        <div class="section-header">
                            <h2>Mentor Portal</h2>
                        </div>
                        
                        <?php if (empty($mentorRegistrations)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h3>No Mentor Applications</h3>
                            <p>You haven't applied to be a mentor for any holiday programs yet.</p>
                            <a href="holidayProgramIndex.php" class="cta-button">Apply as Mentor</a>
                        </div>
                        <?php else: ?>
                        <div class="mentor-applications">
                            <h3>Your Mentor Applications</h3>
                            
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Program</th>
                                            <th>Workshop</th>
                                            <th>Status</th>
                                            <th>Enrollment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mentorRegistrations as $application): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($application['program_title']); ?></td>
                                            <td><?php echo htmlspecialchars($application['workshop_title'] ?? 'Not assigned'); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo strtolower($application['mentor_status']); ?>">
                                                    <?php echo ucfirst($application['mentor_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($application['max_participants']): ?>
                                                    <?php echo $application['enrolled_count']; ?>/<?php echo $application['max_participants']; ?>
                                                    <div class="mini-progress">
                                                        <div class="mini-progress-bar" style="width: <?php echo ($application['max_participants'] > 0) ? ($application['enrolled_count'] / $application['max_participants']) * 100 : 0; ?>%;"></div>
                                                    </div>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <?php if ($application['mentor_status'] === 'Approved'): ?>
                                                    <a href="mentor-workshop.php?id=<?php echo $application['mentor_workshop_preference']; ?>" 
                                                       class="action-btn" title="Manage Workshop">
                                                        <i class="fas fa-users-cog"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="holidayProgramRegistration.php?program_id=<?php echo $application['program_id']; ?>&edit=1" 
                                                       class="action-btn" title="Edit Application">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="holiday-program-details.php?id=<?php echo $application['program_id']; ?>" 
                                                       class="action-btn" title="View Program Details">
                                                        <i class="fas fa-info-circle"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php 
                        // Get approved workshops for mentoring
                        $approvedApplications = array_filter($mentorRegistrations, function($app) {
                            return $app['mentor_status'] === 'Approved';
                        });
                        
                        if (!empty($approvedApplications)): 
                            $firstApproved = reset($approvedApplications);
                        ?>
                        <div class="mentor-workshop-section">
                            <h3>Workshop Management</h3>
                            
                            <div class="workshop-management-card">
                                <div class="workshop-header">
                                    <h4><?php echo htmlspecialchars($firstApproved['workshop_title']); ?></h4>
                                    <div class="workshop-meta">
                                        <span><?php echo htmlspecialchars($firstApproved['program_title']); ?></span>
                                        <span><?php echo htmlspecialchars($firstApproved['program_dates']); ?></span>
                                    </div>
                                </div>
                                <div class="workshop-stats">
                                    <div class="stat-box">
                                        <div class="stat-value"><?php echo $firstApproved['enrolled_count']; ?></div>
                                        <div class="stat-label">Students</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">0</div>
                                        <div class="stat-label">Projects</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">0</div>
                                        <div class="stat-label">Days</div>
                                    </div>
                                </div>
                                <div class="workshop-actions">
                                    <a href="mentor-workshop.php?id=<?php echo $firstApproved['mentor_workshop_preference']; ?>" class="cta-button">
                                        Manage Workshop
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Statistics Section (Admin Only) -->
                    <?php if ($userType === 'admin' && $programStats): ?>
                    <section id="statistics" class="dashboard-section">
                        <div class="section-header">
                            <h2>Program Statistics</h2>
                            <div class="section-subtitle">
                                <?php 
                                $currentProgram = array_filter($allPrograms, function($p) use ($currentProgramId) {
                                    return $p['id'] == $currentProgramId;
                                });
                                $currentProgram = reset($currentProgram);
                                echo htmlspecialchars($currentProgram['term'] . ': ' . $currentProgram['title']);
                                ?>
                            </div>
                        </div>
                        
                        <div class="stats-overview">
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>Participant Demographics</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="stats-row">
                                        <div class="stat-item">
                                            <div class="stat-label">Total Participants</div>
                                            <div class="stat-value"><?php echo $programStats['total']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Male</div>
                                            <div class="stat-value"><?php echo $programStats['male']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Female</div>
                                            <div class="stat-value"><?php echo $programStats['female']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Other/Undisclosed</div>
                                            <div class="stat-value"><?php echo $programStats['other']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="chart-container">
                                        <canvas id="genderChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>Age Distribution</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="chart-container">
                                        <canvas id="ageChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>Attendance Statistics</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="stats-row">
                                        <div class="stat-item">
                                            <div class="stat-label">Present</div>
                                            <div class="stat-value"><?php echo $programStats['attendance']['present']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Absent</div>
                                            <div class="stat-value"><?php echo $programStats['attendance']['absent']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Late</div>
                                            <div class="stat-value"><?php echo $programStats['attendance']['late']; ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Excused</div>
                                            <div class="stat-value"><?php echo $programStats['attendance']['excused']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-detail">
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>Workshop Enrollment</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Workshop</th>
                                                    <th>Enrollment</th>
                                                    <th>Capacity</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($programStats['workshops'] as $workshopId => $workshop): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($workshop['title']); ?></td>
                                                    <td><?php echo $workshop['enrolled']; ?></td>
                                                    <td><?php echo $workshop['max']; ?></td>
                                                    <td>
                                                        <div class="progress-bar">
                                                            <div class="progress-fill" style="width: <?php echo $workshop['fill_percentage']; ?>%"></div>
                                                        </div>
                                                        <span class="progress-text"><?php echo $workshop['fill_percentage']; ?>%</span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="chart-container">
                                        <canvas id="workshopChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-actions">
                            <a href="holiday-program-report.php?program=<?php echo $currentProgramId; ?>" class="primary-button">
                                <i class="fas fa-download"></i> Download Full Report
                            </a>
                            <a href="holiday-program-print.php?program=<?php echo $currentProgramId; ?>" class="secondary-button" target="_blank">
                                <i class="fas fa-print"></i> Print Report
                            </a>
                        </div>
                    </section>
                    
                    <!-- Program Management Section (Admin Only) -->
                    <section id="management" class="dashboard-section">
                        <div class="section-header">
                            <h2>Program Management</h2>
                        </div>
                        
                        <div class="management-cards">
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-users"></i></div>
                                <div class="card-content">
                                    <h3>Participants</h3>
                                    <p>Manage program participants and their registrations</p>
                                    <a href="program-participants.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        View Participants
                                    </a>
                                </div>
                            </div>
                            
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div class="card-content">
                                    <h3>Mentors</h3>
                                    <p>Review and manage mentor applications and assignments</p>
                                    <a href="program-mentors.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        Manage Mentors
                                    </a>
                                </div>
                            </div>
                            
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                                <div class="card-content">
                                    <h3>Workshops</h3>
                                    <p>Configure program workshops and their capacities</p>
                                    <a href="program-workshops.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        Manage Workshops
                                    </a>
                                </div>
                            </div>
                            
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-project-diagram"></i></div>
                                <div class="card-content">
                                    <h3>Projects</h3>
                                    <p>Review and manage submitted projects</p>
                                    <a href="program-projects.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        View Projects
                                    </a>
                                </div>
                            </div>
                            
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="card-content">
                                    <h3>Attendance</h3>
                                    <p>Track and manage program attendance</p>
                                    <a href="program-attendance.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        Manage Attendance
                                    </a>
                                </div>
                            </div>
                            
                            <div class="management-card">
                                <div class="card-icon"><i class="fas fa-cog"></i></div>
                                <div class="card-content">
                                    <h3>Program Settings</h3>
                                    <p>Configure program details, dates, and registration settings</p>
                                    <a href="program-settings.php?program=<?php echo $currentProgramId; ?>" class="card-action">
                                        Edit Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Mobile Navigation (visible only on mobile) -->
    <nav class="mobile-nav">
        <a href="#overview" class="mobile-menu-item active" data-section="overview">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Overview</span>
        </a>
        
        <?php if (!empty($userRegistrations)): ?>
        <a href="#workshops" class="mobile-menu-item" data-section="workshops">
            <div class="mobile-menu-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <span>Workshops</span>
        </a>
        
        <a href="#projects" class="mobile-menu-item" data-section="projects">
            <div class="mobile-menu-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <span>Projects</span>
        </a>
        <?php endif; ?>
        
        <?php if ($userType === 'admin'): ?>
        <a href="#statistics" class="mobile-menu-item" data-section="statistics">
            <div class="mobile-menu-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <span>Stats</span>
        </a>
        <?php endif; ?>
        
        <?php if ($isMentor): ?>
        <a href="#mentor" class="mobile-menu-item" data-section="mentor">
            <div class="mobile-menu-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <span>Mentor</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <script>
        // Section navigation
        document.querySelectorAll('.nav-link, .mobile-menu-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetSection = this.getAttribute('data-section');
                
                // Hide all sections and remove active class from nav links
                document.querySelectorAll('.dashboard-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.querySelectorAll('.nav-link, .mobile-menu-item').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                
                // Show target section and add active class to clicked link
                document.getElementById(targetSection).classList.add('active');
                document.querySelectorAll(`[data-section="${targetSection}"]`).forEach(activeLink => {
                    activeLink.classList.add('active');
                });
            });
        });
        
        // Get section from URL hash if present
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            const navLink = document.querySelector(`[data-section="${hash}"]`);
            if (navLink) {
                navLink.click();
            }
        }
        
        <?php if ($userType === 'admin' && $programStats): ?>
        // Charts initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Gender distribution chart
            const genderCtx = document.getElementById('genderChart').getContext('2d');
            new Chart(genderCtx, {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female', 'Other/Undisclosed'],
                    datasets: [{
                        data: [
                            <?php echo $programStats['male']; ?>,
                            <?php echo $programStats['female']; ?>,
                            <?php echo $programStats['other']; ?>
                        ],
                        backgroundColor: [
                            '#4CAF50',
                            '#2196F3',
                            '#9C27B0'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: true,
                            text: 'Gender Distribution'
                        }
                    }
                }
            });
            
            // Age distribution chart
            const ageCtx = document.getElementById('ageChart').getContext('2d');
            new Chart(ageCtx, {
                type: 'bar',
                data: {
                    labels: ['9-12', '13-15', '16-18', '19+'],
                    datasets: [{
                        label: 'Number of Participants',
                        data: [
                            <?php echo $programStats['age_groups']['9-12']; ?>,
                            <?php echo $programStats['age_groups']['13-15']; ?>,
                            <?php echo $programStats['age_groups']['16-18']; ?>,
                            <?php echo $programStats['age_groups']['19+']; ?>
                        ],
                        backgroundColor: '#6C63FF',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Age Distribution'
                        }
                    }
                }
            });
            
            // Attendance chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late', 'Excused'],
                    datasets: [{
                        data: [
                            <?php echo $programStats['attendance']['present']; ?>,
                            <?php echo $programStats['attendance']['absent']; ?>,
                            <?php echo $programStats['attendance']['late']; ?>,
                            <?php echo $programStats['attendance']['excused']; ?>
                        ],
                        backgroundColor: [
                            '#4CAF50',
                            '#F44336',
                            '#FFC107',
                            '#2196F3'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: true,
                            text: 'Attendance Distribution'
                        }
                    }
                }
            });
            
            // Workshop enrollment chart
            const workshopCtx = document.getElementById('workshopChart').getContext('2d');
            new Chart(workshopCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        $workshopTitles = array_map(function($workshop) {
                            return "'" . addslashes($workshop['title']) . "'";
                        }, $programStats['workshops']);
                        echo implode(', ', $workshopTitles);
                        ?>
                    ],
                    datasets: [{
                        label: 'Enrolled',
                        data: [
                            <?php
                            $enrolledCounts = array_map(function($workshop) {
                                return $workshop['enrolled'];
                            }, $programStats['workshops']);
                            echo implode(', ', $enrolledCounts);
                            ?>
                        ],
                        backgroundColor: '#6C63FF'
                    },
                    {
                        label: 'Capacity',
                        data: [
                            <?php
                            $maxCounts = array_map(function($workshop) {
                                return $workshop['max'];
                            }, $programStats['workshops']);
                            echo implode(', ', $maxCounts);
                            ?>
                        ],
                        backgroundColor: 'rgba(108, 99, 255, 0.2)',
                        borderColor: '#6C63FF',
                        borderWidth: 1,
                        type: 'line'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Workshop Enrollment vs. Capacity'
                        }
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>