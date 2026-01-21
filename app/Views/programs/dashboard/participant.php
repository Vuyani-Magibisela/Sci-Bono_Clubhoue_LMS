<?php
session_start();
require_once '../../../server.php';

// Check if user is logged in
if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
    header('Location: holidayProgramLogin.php');
    exit();
}

// Get user information
$userId = $_SESSION['holiday_user_id'];
$userEmail = $_SESSION['holiday_email'];
$userName = $_SESSION['holiday_name'];
$userSurname = $_SESSION['holiday_surname'];
$isMentor = $_SESSION['holiday_is_mentor'] ?? false;
$isAdmin = $_SESSION['holiday_is_admin'] ?? false;
$userType = $_SESSION['holiday_user_type'] ?? 'member';

// Get user's registration details
$sql = "SELECT hpa.*, hp.title as program_title, hp.dates as program_dates, hp.time as program_time
        FROM holiday_program_attendees hpa
        LEFT JOIN holiday_programs hp ON hpa.program_id = hp.id
        WHERE hpa.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userRegistration = $result->fetch_assoc();

// Get user's workshop preferences
$workshopPreferences = [];
if ($userRegistration && $userRegistration['workshop_preference']) {
    $workshopPreferences = json_decode($userRegistration['workshop_preference'], true) ?? [];
}

// Get workshop details
$workshops = [];
if (!empty($workshopPreferences)) {
    $workshopIds = array_map('intval', $workshopPreferences);
    $placeholders = str_repeat('?,', count($workshopIds) - 1) . '?';
    
    $sql = "SELECT * FROM holiday_program_workshops WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($workshopIds)), ...$workshopIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $workshops[] = $row;
    }
}

// Check for welcome message
$showWelcome = isset($_GET['welcome']) && $_GET['welcome'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sci-Bono Clubhouse Holiday Programs</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #6c63ff, #8e8af7);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-banner h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .card-icon.program { background: linear-gradient(135deg, #6c63ff, #8e8af7); }
        .card-icon.workshop { background: linear-gradient(135deg, #28a745, #5cb85c); }
        .card-icon.status { background: linear-gradient(135deg, #17a2b8, #5bc0de); }
        .card-icon.mentor { background: linear-gradient(135deg, #fd7e14, #ffa726); }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c63ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .quick-action-btn:hover {
            background: #5a52d5;
        }
        
        .quick-action-btn.secondary {
            background: #6c757d;
        }
        
        .quick-action-btn.secondary:hover {
            background: #5a6268;
        }
        
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        
        .workshop-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .workshop-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #6c63ff;
        }
        
        .workshop-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .workshop-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <?php include '../shared/header.php'; ?>
    
    <div class="dashboard-container">
        <?php if ($showWelcome): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <strong>Welcome!</strong> Your account has been successfully created. You can now access all holiday program features.
        </div>
        <?php endif; ?>
        
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($userName . ' ' . $userSurname); ?>!</h1>
            <p><?php echo $isMentor ? 'Mentor Dashboard' : 'Participant Dashboard'; ?></p>
        </div>
        
        <div class="dashboard-grid">
            <!-- Program Information Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon program">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="card-title">Program Information</h3>
                </div>
                
                <?php if ($userRegistration): ?>
                    <div class="info-item">
                        <div class="info-label">Program</div>
                        <div class="info-value"><?php echo htmlspecialchars($userRegistration['program_title']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dates</div>
                        <div class="info-value"><?php echo htmlspecialchars($userRegistration['program_dates']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Time</div>
                        <div class="info-value"><?php echo htmlspecialchars($userRegistration['program_time']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Registration Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo strtolower($userRegistration['registration_status']); ?>">
                                <?php echo ucfirst($userRegistration['registration_status']); ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No program registration found.</p>
                <?php endif; ?>
                
                <div class="quick-actions">
                    <a href="holiday-program-details-term1.php?id=<?php echo $userRegistration['program_id']; ?>" class="quick-action-btn">
                        <i class="fas fa-info-circle"></i> View Details
                    </a>
                    <a href="holidayProgramRegistration.php?program_id=<?php echo $userRegistration['program_id']; ?>&edit=1" class="quick-action-btn secondary">
                        <i class="fas fa-edit"></i> Edit Registration
                    </a>
                </div>
            </div>
            
            <!-- Workshop Preferences Card -->
            <?php if (!$isMentor): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon workshop">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="card-title">Workshop Preferences</h3>
                </div>
                
                <?php if (!empty($workshops)): ?>
                    <ul class="workshop-list">
                        <?php foreach ($workshops as $index => $workshop): ?>
                            <li class="workshop-item">
                                <div class="workshop-title">
                                    <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($workshop['title']); ?>
                                    <?php if ($index === 0): ?>
                                        <span style="color: #28a745; font-size: 0.8rem;">(Preferred)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="workshop-description"><?php echo htmlspecialchars($workshop['description']); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No workshop preferences selected.</p>
                <?php endif; ?>
                
                <div class="quick-actions">
                    <a href="holiday-workshops.php" class="quick-action-btn">
                        <i class="fas fa-eye"></i> View All Workshops
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Mentor Status Card (for mentors) -->
            <?php if ($isMentor): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon mentor">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="card-title">Mentor Status</h3>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Application Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo strtolower($userRegistration['mentor_status'] ?? 'pending'); ?>">
                            <?php echo ucfirst($userRegistration['mentor_status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($userRegistration['mentor_status'] === 'Approved'): ?>
                    <div class="alert info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Congratulations!</strong> Your mentor application has been approved. You'll receive training details soon.
                    </div>
                <?php elseif ($userRegistration['mentor_status'] === 'Pending'): ?>
                    <div class="alert info">
                        <i class="fas fa-clock"></i>
                        Your mentor application is under review. We'll notify you within 5 business days.
                    </div>
                <?php endif; ?>
                
                <div class="quick-actions">
                    <a href="mentor-resources.php" class="quick-action-btn">
                        <i class="fas fa-book"></i> Mentor Resources
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Account Information Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon status">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="card-title">Account Information</h3>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($userName . ' ' . $userSurname); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($userEmail); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Account Type</div>
                    <div class="info-value"><?php echo ucfirst($userType); ?></div>
                </div>
                <?php if ($userRegistration && $userRegistration['last_login']): ?>
                <div class="info-item">
                    <div class="info-label">Last Login</div>
                    <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($userRegistration['last_login'])); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="quick-actions">
                    <a href="holiday-profile.php" class="quick-action-btn">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="holiday-logout.php" class="quick-action-btn secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Information Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-icon program">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h3 class="card-title">Important Information</h3>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4><i class="fas fa-calendar-check"></i> Upcoming Events</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <strong>Program Start:</strong> <?php echo htmlspecialchars($userRegistration['program_dates'] ?? 'TBA'); ?>
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <strong>Orientation:</strong> First day, 9:00 AM
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>Showcase:</strong> Final day, 2:00 PM
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4><i class="fas fa-exclamation-triangle"></i> What to Bring</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <i class="fas fa-check text-success"></i> Notebook and pen
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <i class="fas fa-check text-success"></i> Water bottle
                        </li>
                        <li style="padding: 8px 0;">
                            <i class="fas fa-check text-success"></i> Enthusiasm and creativity!
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4><i class="fas fa-phone"></i> Contact Information</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <strong>Email:</strong> clubhouse@sci-bono.co.za
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <strong>Phone:</strong> +27 11 639 8400
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>Address:</strong> Sci-Bono Discovery Centre, Newtown
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="holidayProgramIndex.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span>Programs</span>
        </a>
        <a href="holiday-workshops.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <span>Workshops</span>
        </a>
        <a href="holiday-dashboard.php" class="mobile-menu-item active">
            <div class="mobile-menu-icon">
                <i class="fas fa-user"></i>
            </div>
            <span>Dashboard</span>
        </a>
    </nav>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth animations for cards
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Add hover effects to quick action buttons
            const buttons = document.querySelectorAll('.quick-action-btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>