<?php
// Start session if not already started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <!-- <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css"> -->
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
                        <li class="<?php echo ($current_page == 'holidayProgramIndex.php') ? 'active' : ''; ?>">
                            <a href="holidayProgramIndex.php">
                                <i class="fas fa-home"></i>
                                <span>Programs</span>
                            </a>
                        </li>
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
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                            <li class="<?php echo ($current_page == 'holiday-dashboard.php') ? 'active' : ''; ?>">
                                <a href="holiday-dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'mentor')): ?>
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
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                        <a href="holiday-dashboard.php" class="user-avatar">
                            <?php if (isset($_SESSION['name'])): ?>
                                <span><?php echo substr($_SESSION['name'], 0, 1); ?></span>
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </a>
                        <a href="../../../logout_process.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="../../login.php" class="login-btn">
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
                <li><a href="./holidayProgramIndex.php">Home</a></li>
                <li><a href="holidayProgramRegistration.php">Holiday Programs Registration</a></li>
                <?php
                // Generate breadcrumb based on current page
                switch ($current_page) {
                    case 'holiday-registration.php':
                        echo '<li>Registration</li>';
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
                    case 'holiday-reports.php':
                        echo '<li>Reports</li>';
                        break;
                    // Add more cases as needed
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
            <li class="<?php echo ($current_page == 'holidayProgramIndex.php') ? 'active' : ''; ?>">
                <a href="holidayProgramIndex.php">
                    <i class="fas fa-home"></i>
                    <span>Programs</span>
                </a>
            </li>
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
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <li class="<?php echo ($current_page == 'holiday-dashboard.php') ? 'active' : ''; ?>">
                    <a href="holiday-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'mentor')): ?>
                    <li class="<?php echo ($current_page == 'holiday-reports.php') ? 'active' : ''; ?>">
                        <a href="holiday-reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="../../logout_process.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="../../login.php">
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