<?php
// Check if a session is not already active
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $destination = "../../../home.php";
    $learnHome = "./learn.php";
} else {
    $destination = "../../../login.php";
    $learnHome = "./login.php";
}

// Get current page to highlight active menu item
$currentPage = basename($_SERVER['PHP_SELF']);

require __DIR__ . '/../../../config/config.php'; // Include the config file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse Learning</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Learning Header Specific Styles */
        :root {
            --primary: #6C63FF;
            --secondary: #F29A2E;
            --purple: #9002D2;
            --dark: #2F2E41;
            --light: #E6EAEE;
            --white: #FFFFFF;
            --blue: #1E6CB4;
            --dark-blue: #393A7B;
        }
        
        .learning-header {
            background: linear-gradient(90deg, var(--blue) 0%, var(--dark-blue) 100%);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-container img {
            height: 40px;
            width: auto;
        }
        
        .site-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .site-title i {
            font-size: 1.1rem;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .nav-link i {
            font-size: 0.9rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--purple);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 500;
            font-size: 1rem;
        }
        
        .user-name {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--white);
        }
        
        .notification-icon {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .notification-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: var(--secondary);
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border: none;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        /* Mobile navigation */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            background-color: var(--dark-blue);
            padding: 1rem;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .mobile-nav.active {
            display: flex;
        }
        
        .mobile-nav .nav-link {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .header-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
            }
        }
        
        @media (max-width: 768px) {
            .site-title span {
                display: none;
            }
            
            .logo-container img:nth-child(2) {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .learning-header {
                padding: 0.75rem 1rem;
            }
            
            .user-name {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="learning-header">
        <div class="header-left">
            <div class="logo-container">
                <a href="<?php echo $destination; ?>">
                    <img src="<?php echo BASE_URL; ?>public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Logo">
                </a>
                <img src="<?php echo BASE_URL; ?>public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo" style="height: 32px;">
            </div>
            
            <a href="<?php echo BASE_URL; ?>app/Views/admin/manage-courses.php" class="site-title">
                <i class="fas fa-graduation-cap"></i>
                <span>Learning Hub</span>
            </a>
            
            <nav class="header-nav">
                <a href="<?php echo BASE_URL; ?>app/Views/admin/manage-courses.php" class="nav-link <?php echo ($currentPage == 'manage-courses.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="./explore.php" class="nav-link <?php echo ($currentPage == 'explore.php') ? 'active' : ''; ?>">
                    <i class="fas fa-compass"></i> Explore
                </a>
                <a href="./my-courses.php" class="nav-link <?php echo ($currentPage == 'my-courses.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> My Courses
                </a>
                <a href="./certificates.php" class="nav-link <?php echo ($currentPage == 'certificates.php') ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i> Certificates
                </a>
            </nav>
            
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="header-right">
            <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
            
            <div class="user-profile">
                <div class="avatar">
                    <?php if(isset($_SESSION['username'])): ?>
                        <?php echo substr($_SESSION['username'], 0, 1); ?>
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="user-name">
                    <?php if(isset($_SESSION['username'])) echo $_SESSION['username']; ?>
                </div>
            </div>
            <?php else: ?>
            <a href="../../login.php" class="nav-link">
                <i class="fas fa-sign-in-alt"></i> Log In
            </a>
            <a href="../../signup.php" class="nav-link" style="background-color: var(--secondary);">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
            <?php endif; ?>
        </div>
    </header>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <a href="<?php echo $learnHome; ?>" class="nav-link <?php echo ($currentPage == 'learn.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="./explore.php" class="nav-link <?php echo ($currentPage == 'explore.php') ? 'active' : ''; ?>">
            <i class="fas fa-compass"></i> Explore
        </a>
        <a href="./my-courses.php" class="nav-link <?php echo ($currentPage == 'my-courses.php') ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> My Courses
        </a>
        <a href="./certificates.php" class="nav-link <?php echo ($currentPage == 'certificates.php') ? 'active' : ''; ?>">
            <i class="fas fa-certificate"></i> Certificates
        </a>
        <a href="<?php echo $destination; ?>" class="nav-link">
            <i class="fas fa-arrow-left"></i> Back to Clubhouse
        </a>
    </div>
    
    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            mobileNav.classList.toggle('active');
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileNav = document.getElementById('mobileNav');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (mobileNav.classList.contains('active') && 
                !mobileNav.contains(event.target) && 
                !mobileMenuBtn.contains(event.target)) {
                mobileNav.classList.remove('active');
            }
        });
    </script>