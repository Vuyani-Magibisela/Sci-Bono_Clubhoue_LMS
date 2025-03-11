<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: login.php");
    exit;
}

// Include auto-logout script
include 'app/Controllers/sessionTimer.php';

// Include dashboard data loader
include 'app/Models/dashboard-data-loader.php';

// Include the auto-logout script to track inactivity
include 'app/Controllers/sessionTimer.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubhouse Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="public/assets/css/header.css">
    <link rel="stylesheet" href="public/assets/css/screenSizes.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Code+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/css/homeStyle.css">
</head>
<body >
    <!-- Loading Spinner (Hidden by default) -->
    <div class="loading" style="display: none;">
        <div class="spinner"></div>
    </div>

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <!-- Logo can go here if needed -->
            </div>
            
            <ul class="nav-menu">
                <li>
                    <a href="home.php" class="active">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 23V53H49V23L30 8L11 23Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23.75 36.25V52.5H36.25V36.25H23.75Z" fill="#F29A2E" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M11.25 52.5H48.75" stroke="#F29A2E" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Home
                    </a>
                </li>
                <li>
                    <a href="projects.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M55 36.25H5V52.5H55V36.25Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M44.375 47.5C46.1009 47.5 47.5 46.1009 47.5 44.375C47.5 42.6491 46.1009 41.25 44.375 41.25C42.6491 41.25 41.25 42.6491 41.25 44.375C41.25 46.1009 42.6491 47.5 44.375 47.5Z" fill="white"/>
                            <path d="M5 36.2498L11.298 6.24878H48.7756L55 36.2498" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        Projects
                    </a>
                </li>
                <li>
                    <a href="members.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M52.5 10H7.5C6.11929 10 5 11.1193 5 12.5V47.5C5 48.8807 6.11929 50 7.5 50H52.5C53.8807 50 55 48.8807 55 47.5V12.5C55 11.1193 53.8807 10 52.5 10Z" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M21.25 31.25C24.0114 31.25 26.25 29.0114 26.25 26.25C26.25 23.4886 24.0114 21.25 21.25 21.25C18.4886 21.25 16.25 23.4886 16.25 26.25C16.25 29.0114 18.4886 31.25 21.25 31.25Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M28.75 38.75C28.75 34.6079 25.3921 31.25 21.25 31.25C17.1079 31.25 13.75 34.6079 13.75 38.75" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Members
                    </a>
                </li>
                <li>
                    <a href="learn.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M40 7.5H27.5V52.5H40V7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M52.5 7.5H40V52.5H52.5V7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M12.5 7.5L22.5 8.75L18.125 52.5L7.5 51.25L12.5 7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        Learn
                    </a>
                </li>
                <li>
                    <a href="signin.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M33 59.125C47.4284 59.125 59.125 47.4284 59.125 33C59.125 18.5716 47.4284 6.875 33 6.875C18.5716 6.875 6.875 18.5716 6.875 33C6.875 47.4284 18.5716 59.125 33 59.125Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2"/>
                            <path d="M49.5083 33.817V33.8156C49.5083 29.822 46.2708 26.5845 42.2771 26.5845H23.7229C19.7292 26.5845 16.4918 29.822 16.4918 33.8156V33.817C16.4918 37.8106 19.7292 41.0481 23.7229 41.0481H42.2771C46.2708 41.0481 49.5083 37.8106 49.5083 33.817Z" fill="#8CC86E" stroke="white" stroke-width="2"/>
                        </svg>
                        Sign In
                    </a>
                </li>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "admin"): ?>
                <li>
                    <a href="app/Views/statsDashboard.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50 15H10C8.61929 15 7.5 16.1193 7.5 17.5V50C7.5 51.3807 8.61929 52.5 10 52.5H50C51.3807 52.5 52.5 51.3807 52.5 50V17.5C52.5 16.1193 51.3807 15 50 15Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M22.437 30.0103H37.437" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7.5 16.25L16.25 6.25H43.75L52.5 16.25" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Reports
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="settings.php">
                        <svg class="nav-icon" width="30" height="30" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M30 50H8.75C6.67894 50 5 48.3211 5 46.25V13.75C5 11.6789 6.67894 10 8.75 10H51.25C53.3211 10 55 11.6789 55 13.75V28.8235" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 13.75C5 11.6789 6.67894 10 8.75 10H51.25C53.3211 10 55 11.6789 55 13.75V25H5V13.75Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2"/>
                        </svg>
                        Settings
                    </a>
                </li>
            </ul>

            <div class="logout-container">
                <a href="logout_process.php">
                    <button class="logout-btn">Logout</button>
                </a>
            </div>
        </aside>

<!-- Main Content Area -->
<main class="main-content">
    <!-- Header Section -->
    <header class="dashboard-header">
        <div class="announcement">
            <div class="announcement-box">
                <h4>Announcements</h4>
                <h6><?php echo date("l, F jS, Y"); ?></h6>
            </div>
        </div>
        <div class="user-info">
            <div class="user-details">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-name">
                    <?php if(isset($_SESSION['loggedin'])) : ?>
                        <h3><?php echo $_SESSION['username']; ?></h3>
                        <?php if (isset($_SESSION['user_type'])) : ?>
                            <div class="user-role"><?php echo $_SESSION['user_type']; ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Content Area -->
    <section class="dashboard-content">
        <div class="content-grid">
            <!-- Place the Dynamic Dashboard Content here -->
            <?php include 'app/Views/dynamic-dashboard-content.php'; ?>
            <!-- Alternatively, you can copy and paste the content directly here -->
        </div>
    </section>
</main>
        <script src="public/assets/js/homedashboard.js"></script>
</body>
</html>