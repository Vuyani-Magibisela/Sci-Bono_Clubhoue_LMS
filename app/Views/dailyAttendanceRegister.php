<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../login.php");
    exit;
}

// Include the auto-logout script to track inactivity
include '../Controllers/sessionTimer.php';

// Include database connection
require_once '../../server.php';

// Include controller
require_once '../Controllers/AttendanceRegisterController.php';

// Initialize controller
$attendanceController = new AttendanceRegisterController($conn);

// Get current date as default or use date from request
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get filter parameter or default to 'all'
$selectedFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get attendance data
$attendanceData = $attendanceController->getAttendanceData($selectedDate, $selectedFilter);

// Get a list of active dates for datepicker
$activeDates = $attendanceController->getActiveDates();

// Define user type labels for display
$userTypeLabels = [
    'admin' => 'Administrators',
    'mentor' => 'Mentors',
    'member' => 'Members',
    'community' => 'Community Visitors',
    'alumni' => 'Alumni'
];

/**
 * Calculate age from date of birth
 * 
 * @param string $dateOfBirth Date of birth in Y-m-d format
 * @return int|string Age in years or dash if date is invalid
 */
function calculateAge($dateOfBirth) {
    if (empty($dateOfBirth) || $dateOfBirth == '0000-00-00') {
        return '-';
    }
    
    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    
    // Check if it's a valid date
    if ($dob === false) {
        return '-';
    }
    
    $diff = $now->diff($dob);
    return $diff->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Register - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../public/assets/css/homeStyle.css">
    <link rel="stylesheet" href="../../public/assets/css/attendanceRegister.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- GSAP for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <img src="../../public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
            <span>SCI-BONO CLUBHOUSE</span>
        </div>
        <div class="search-bar">
            <input type="text" class="search-input" placeholder="Search the Clubhouse...">
        </div>
        <div class="header-icons">
            <div class="icon-btn" data-tooltip="Notifications">
                <i class="fas fa-bell"></i>
            </div>
            <div class="icon-btn" data-tooltip="Messages">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="user-profile">
                <div class="avatar">
                    <?php if(isset($_SESSION['username'])) : ?>
                        <?php echo substr($_SESSION['username'], 0, 1); ?>
                    <?php else : ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="user-name">
                    <?php if(isset($_SESSION['username'])) echo $_SESSION['username']; ?>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="menu-group">
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="menu-text"><a href="../../home.php">Home</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="menu-text">Profile</div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="menu-text"><a href="../../members.php">Members</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="menu-text"><a href="./learn.php">Learn</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="menu-text"><a href="../../signin.php">Daily Register</a></div>
                </div>
                <div class="menu-item active">
                    <div class="menu-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="menu-text"><a href="./dailyAttendanceRegister.php">Attendance Register</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <div class="menu-text"><a href="./projects.php">Projects</a></div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "admin"): ?>
            <div class="menu-group">
                <div class="menu-title">Admin</div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="menu-text"><a href="./statsDashboard.php">Reports</a></div>
                </div>
                <div class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="menu-text"><a href="../../settings.php">Settings</a></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="menu-item" onclick="window.location.href='../../logout_process.php'">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="menu-text">Log out</div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content attendance-register-content">
            <div class="page-header">
                <h1>Daily Attendance Register</h1>
                <p class="subtitle">Track and view daily attendance records</p>
            </div>
            
            <!-- Filters and Controls -->
            <div class="attendance-controls">
                <div class="date-picker-wrapper">
                    <label for="datePicker">Select Date:</label>
                    <input type="text" id="datePicker" value="<?php echo $selectedDate; ?>" data-active-dates='<?php echo json_encode($activeDates); ?>' readonly>
                </div>
                
                <div class="filter-controls">
                    <label>Filter by Type:</label>
                    <div class="filter-buttons">
                        <button class="filter-btn <?php echo $selectedFilter === 'all' ? 'active' : ''; ?>" data-filter="all">All</button>
                        <button class="filter-btn <?php echo $selectedFilter === 'member' ? 'active' : ''; ?>" data-filter="member">Members</button>
                        <button class="filter-btn <?php echo $selectedFilter === 'mentor' ? 'active' : ''; ?>" data-filter="mentor">Mentors</button>
                        <button class="filter-btn <?php echo $selectedFilter === 'admin' ? 'active' : ''; ?>" data-filter="admin">Admin</button>
                        <button class="filter-btn <?php echo $selectedFilter === 'community' ? 'active' : ''; ?>" data-filter="community">Community</button>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Summary -->
            <div class="attendance-summary">
                <div class="summary-card total">
                    <div class="summary-icon"><i class="fas fa-users"></i></div>
                    <div class="summary-details">
                        <div class="summary-count"><?php echo $attendanceData['counts']['total']; ?></div>
                        <div class="summary-label">Total Attendees</div>
                    </div>
                </div>
                <div class="summary-card members">
                    <div class="summary-icon"><i class="fas fa-user"></i></div>
                    <div class="summary-details">
                        <div class="summary-count"><?php echo $attendanceData['counts']['member']; ?></div>
                        <div class="summary-label">Members</div>
                    </div>
                </div>
                <div class="summary-card mentors">
                    <div class="summary-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="summary-details">
                        <div class="summary-count"><?php echo $attendanceData['counts']['mentor']; ?></div>
                        <div class="summary-label">Mentors</div>
                    </div>
                </div>
                <div class="summary-card admin">
                    <div class="summary-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="summary-details">
                        <div class="summary-count"><?php echo $attendanceData['counts']['admin']; ?></div>
                        <div class="summary-label">Administrators</div>
                    </div>
                </div>
                <div class="summary-card community">
                    <div class="summary-icon"><i class="fas fa-user-friends"></i></div>
                    <div class="summary-details">
                        <div class="summary-count"><?php echo $attendanceData['counts']['community']; ?></div>
                        <div class="summary-label">Community</div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Table Container -->
            <div class="attendance-table-container">
                <?php if (empty($attendanceData['groupedAttendees'])): ?>
                <div class="empty-attendance">
                    <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                    <h3>No attendance records found</h3>
                    <p>There are no attendance records for the selected date.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($attendanceData['groupedAttendees'] as $userType => $attendees): ?>
                        <?php if (!empty($attendees)): ?>
                        <div class="attendance-section" id="section-<?php echo $userType; ?>">
                            <h2 class="section-title"><?php echo $userTypeLabels[$userType] ?? ucfirst($userType); ?></h2>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Grade</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Check-In Time</th>
                                        <th>Check-Out Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendees as $attendee): ?>
                                    <tr class="attendee-row">
                                        <td class="attendee-name">
                                            <div class="attendee-avatar" style="background-color: <?php echo generateAvatarColor($attendee['user_id']); ?>">
                                                <?php echo getInitials($attendee['name'] . ' ' . $attendee['surname']); ?>
                                            </div>
                                            <div class="attendee-details">
                                                <div class="full-name"><?php echo htmlspecialchars($attendee['name'] . ' ' . $attendee['surname']); ?></div>
                                                <div class="center-name"><?php echo htmlspecialchars($attendee['Center']); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo isset($attendee['grade']) && $attendee['grade'] > 0 ? htmlspecialchars($attendee['grade']) : '-'; ?></td>
                                        <td><?php echo isset($attendee['date_of_birth']) ? calculateAge($attendee['date_of_birth']) : '-'; ?></td>
                                        <td><?php echo isset($attendee['Gender']) ? htmlspecialchars($attendee['Gender']) : '-'; ?></td>
                                        <td><?php echo $attendanceController->formatTime($attendee['checked_in']); ?></td>
                                        <td><?php echo $attendanceController->formatTime($attendee['checked_out']); ?></td>
                                        <td><?php echo $attendanceController->calculateDuration($attendee['checked_in'], $attendee['checked_out']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $attendee['sign_in_status'] === 'signedIn' ? 'signed-in' : 'signed-out'; ?>">
                                                <?php echo $attendee['sign_in_status'] === 'signedIn' ? 'Signed In' : 'Signed Out'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="../../members.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-users"></i>
            </div>
            <span>Members</span>
        </a>
        <a href="../../signin.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span>Sign-In</span>
        </a>
        <a href="./dailyAttendanceRegister.php" class="mobile-menu-item active">
            <div class="mobile-menu-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <span>Register</span>
        </a>
        <a href="./learn.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-book"></i>
            </div>
            <span>Learn</span>
        </a>
    </nav>
    
    <!-- Helper functions for display -->
    <?php
    /**
     * Generate a consistent color based on user ID
     * 
     * @param int $userId User ID
     * @return string CSS color value
     */
    function generateAvatarColor($userId) {
        $colors = [
            '#3F51B5', '#2196F3', '#00BCD4', '#009688', '#4CAF50', 
            '#8BC34A', '#CDDC39', '#FFC107', '#FF9800', '#FF5722', 
            '#795548', '#9C27B0', '#673AB7', '#E91E63', '#F44336'
        ];
        
        $colorIndex = $userId % count($colors);
        return $colors[$colorIndex];
    }
    
    /**
     * Get user initials from full name
     * 
     * @param string $fullName User's full name
     * @return string Initials (1-2 characters)
     */
    function getInitials($fullName) {
        $nameParts = explode(' ', $fullName);
        $initials = '';
        
        if (count($nameParts) >= 2) {
            $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
        } else {
            $initials = mb_substr($fullName, 0, 2);
        }
        
        return strtoupper($initials);
    }
    ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../../public/assets/js/attendanceRegister.js"></script>
</body>
</html>