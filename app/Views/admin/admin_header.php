<?php
// Check if session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../../../login.php");
    exit;
}

// Check if user has admin or mentor permission for these pages
if (!in_array($_SESSION['user_type'], ['admin', 'mentor'])) {
    header("Location: ../../../home.php");
    exit;
}

// Get the current user's type and ID
$current_user_type = $_SESSION['user_type'];
$current_user_id = $_SESSION['user_id'];


require __DIR__ . '/../../../config/config.php'; // Include the config file

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/assets/css/settingsStyle.css">
    <?//php include '../../../public'; ?>
    <style>
        /* Additional styles specific to the admin area */
        .user-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-list-table {
            width: 100%;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background-color: rgba(81, 70, 230, 0.03);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: var(--border-radius-sm);
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background-color: rgba(81, 70, 230, 0.1);
            color: var(--primary);
        }
        
        .edit-btn:hover {
            background-color: rgba(81, 70, 230, 0.2);
        }
        
        .delete-btn {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .delete-btn:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
    </style>
</head>
<body>
    <!-- Mobile navigation toggle (visible on small screens only) -->
    <button id="mobile-nav-toggle" class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="<?php echo BASE_URL; ?>public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>home.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="sidebar-text">Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../../projects.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <span class="sidebar-text">Projects</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../../members.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="sidebar-text">Members</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../learn.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sidebar-text">Learn</span>
                    </a>
                </li>
                <?php if ($current_user_type === 'admin'): ?>
                <li class="sidebar-item">
                    <a href="../statsDashboard.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="sidebar-text">Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="sidebar-item">
                    <a href="../../signin.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <span class="sidebar-text">Daily Register</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./user_list.php" class="sidebar-link active">
                        <div class="sidebar-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="sidebar-text">Settings</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="../../logout_process.php" class="logout-button">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </aside>