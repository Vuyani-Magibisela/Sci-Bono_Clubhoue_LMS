<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in to holiday program
$isHolidayLoggedIn = isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true;
$isAdmin = isset($_SESSION['holiday_is_admin']) && $_SESSION['holiday_is_admin'] === true;
$isMentor = isset($_SESSION['holiday_is_mentor']) && $_SESSION['holiday_is_mentor'] === true;
$userType = $_SESSION['holiday_user_type'] ?? 'guest';
$userName = $_SESSION['holiday_name'] ?? '';

// Get the current page to highlight the correct nav item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse Holiday Programs</title>
    <!-- Ensure the CSS is included on all pages -->
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramHeader.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Holiday Program Header -->
    <header class="holiday-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <a href="../../home.php" class="logo">
                        <img src="../../../public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
                    </a>
                    <a href="holidayProgramIndex.php" class="program-title">
                        <h1>Holiday Programs</h1>
                    </a>
                </div>
                
                <nav class="holiday-nav">
                    <ul>
                        <!-- Always show Programs -->
                        <li class="<?php echo ($current_page == 'holidayProgramIndex.php') ? 'active' : ''; ?>">
                            <a href="holidayProgramIndex.php">
                                <i class="fas fa-home"></i>
                                <span>Programs</span>
                            </a>
                        </li>
                        
                        <?php if ($isHolidayLoggedIn): ?>
                            <!-- Show these links only when logged in -->
                            <li class="<?php echo ($current_page == 'holiday-workshops.php') ? 'active' : ''; ?>">
                                <a href="holiday-workshops.php">
                                    <i class="fas fa-laptop-code"></i>
                                    <span>Workshops</span>
                                </a>
                            </li>
                            <li class="<?php echo ($current_page == 'holiday-resources.php') ? 'active' : ''; ?>">
                                <a href="holiday-resources.php">
                                    <i class="fas fa-book"></i>
                                    <span>Resources</span>
                                </a>
                            </li>
                            <li class="<?php echo ($current_page == 'holiday-projects.php') ? 'active' : ''; ?>">
                                <a href="holiday-projects.php">
                                    <i class="fas fa-project-diagram"></i>
                                    <span>Projects</span>
                                </a>
                            </li>
                            
                            <!-- User Dashboard -->
                            <li class="<?php echo ($current_page == 'holiday-dashboard.php') ? 'active' : ''; ?>">
                                <a href="holiday-dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            
                            <?php if ($isAdmin): ?>
                                <!-- Admin Dashboard -->
                                <li class="<?php echo ($current_page == 'holidayProgramAdminDashboard.php') ? 'active' : ''; ?>">
                                    <a href="holidayProgramAdminDashboard.php">
                                        <i class="fas fa-cogs"></i>
                                        <span>Admin Dashboard</span>
                                    </a>
                                </li>
                                
                                <!-- Reports for Admin -->
                                <li class="<?php echo ($current_page == 'holiday-reports.php') ? 'active' : ''; ?>">
                                    <a href="holiday-reports.php">
                                        <i class="fas fa-chart-bar"></i>
                                        <span>Reports</span>
                                    </a>
                                </li>
                            <?php elseif ($isMentor || $userType === 'mentor'): ?>
                                <!-- Reports for Mentors -->
                                <li class="<?php echo ($current_page == 'holiday-reports.php') ? 'active' : ''; ?>">
                                    <a href="holiday-reports.php">
                                        <i class="fas fa-chart-bar"></i>
                                        <span>Reports</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="auth-buttons">
                    <?php if ($isHolidayLoggedIn): ?>
                        <a href="holiday-dashboard.php" class="user-avatar" title="<?php echo htmlspecialchars($userName); ?>">
                            <?php if ($userName): ?>
                                <span><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </a>
                        <a href="holiday-logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="holidayProgramLogin.php" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Breadcrumb Navigation -->
    <?php if ($current_page != 'holidayProgramIndex.php'): ?>
    <div class="breadcrumb-container">
        <div class="container">
            <ul class="breadcrumb">
                <li><a href="holidayProgramIndex.php">Programs</a></li>
                <?php
                // Generate breadcrumb based on current page
                switch ($current_page) {
                    case 'holidayProgramRegistration.php':
                        echo '<li>Registration</li>';
                        break;
                    case 'holidayProgramLogin.php':
                        echo '<li>Login</li>';
                        break;
                    case 'holiday-workshops.php':
                        echo '<li>Workshops</li>';
                        break;
                    case 'holiday-resources.php':
                        echo '<li>Resources</li>';
                        break;
                    case 'holiday-projects.php':
                        echo '<li>Projects</li>';
                        break;
                    case 'holiday-dashboard.php':
                        echo '<li>Dashboard</li>';
                        break;
                    case 'holidayProgramAdminDashboard.php':
                        echo '<li>Admin Dashboard</li>';
                        break;
                    case 'holiday-reports.php':
                        echo '<li>Reports</li>';
                        break;
                    case 'holiday-program-details-term1.php':
                        echo '<li>Program Details</li>';
                        break;
                    default:
                        // For any other page, try to create a readable breadcrumb
                        $pageName = str_replace(['-', '_', '.php'], [' ', ' ', ''], $current_page);
                        $pageName = ucwords($pageName);
                        echo '<li>' . htmlspecialchars($pageName) . '</li>';
                        break;
                }
                ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Mobile navigation menu (hidden by default) -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <h3>Holiday Programs</h3>
            <button class="close-mobile-menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul>
            <!-- Always show Programs -->
            <li class="<?php echo ($current_page == 'holidayProgramIndex.php') ? 'active' : ''; ?>">
                <a href="holidayProgramIndex.php">
                    <i class="fas fa-home"></i>
                    <span>Programs</span>
                </a>
            </li>
            
            <?php if ($isHolidayLoggedIn): ?>
                <li class="<?php echo ($current_page == 'holiday-workshops.php') ? 'active' : ''; ?>">
                    <a href="holiday-workshops.php">
                        <i class="fas fa-laptop-code"></i>
                        <span>Workshops</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'holiday-resources.php') ? 'active' : ''; ?>">
                    <a href="holiday-resources.php">
                        <i class="fas fa-book"></i>
                        <span>Resources</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'holiday-projects.php') ? 'active' : ''; ?>">
                    <a href="holiday-projects.php">
                        <i class="fas fa-project-diagram"></i>
                        <span>Projects</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'holiday-dashboard.php') ? 'active' : ''; ?>">
                    <a href="holiday-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if ($isAdmin): ?>
                    <li class="<?php echo ($current_page == 'holidayProgramAdminDashboard.php') ? 'active' : ''; ?>">
                        <a href="holidayProgramAdminDashboard.php">
                            <i class="fas fa-cogs"></i>
                            <span>Admin Dashboard</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($isAdmin || $isMentor || $userType === 'mentor'): ?>
                    <li class="<?php echo ($current_page == 'holiday-reports.php') ? 'active' : ''; ?>">
                        <a href="holiday-reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li>
                    <a href="holiday-logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="holidayProgramLogin.php">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const closeMobileMenu = document.querySelector('.close-mobile-menu');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (mobileMenuToggle && mobileMenu && closeMobileMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    mobileMenu.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                });
                
                closeMobileMenu.addEventListener('click', function() {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = ''; // Restore scrolling
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (mobileMenu.classList.contains('active') && 
                        !mobileMenu.contains(event.target) && 
                        !mobileMenuToggle.contains(event.target)) {
                        mobileMenu.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>