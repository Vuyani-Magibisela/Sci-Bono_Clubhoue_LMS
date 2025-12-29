<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - Sci-Bono Clubhouse</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/settingsStyle.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/admin-layout.css">

    <!-- CSRF Token -->
    <?php if (class_exists('CSRF')): ?>
        <?php echo CSRF::metaTag(); ?>
    <?php endif; ?>

    <?php if (isset($additionalStyles)): ?>
        <?php echo $additionalStyles; ?>
    <?php endif; ?>

    <style>
        /* Admin-specific styles */
        :root {
            --primary: #5146e6;
            --light: #f5f5f7;
            --dark: #2F2E41;
            --gray-light: #d0d0db;
            --gray-medium: #6e6e80;
            --white: #ffffff;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --border-radius-sm: 4px;
            --border-radius-md: 10px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
        }

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
            cursor: pointer;
            border: none;
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

        .view-btn {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .view-btn:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }

        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .alert-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .alert-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #4035d4;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-light);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--gray-medium);
            margin-bottom: 10px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--gray-medium);
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
                    <a href="<?php echo BASE_URL; ?>admin" class="sidebar-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                        <div class="sidebar-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>

                <?php if (in_array($_SESSION['user_type'] ?? '', ['admin', 'mentor'])): ?>
                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>admin/users" class="sidebar-link <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>">
                        <div class="sidebar-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="sidebar-text">Users</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (($_SESSION['user_type'] ?? '') === 'admin'): ?>
                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>admin/courses" class="sidebar-link <?php echo ($currentPage ?? '') === 'courses' ? 'active' : ''; ?>">
                        <div class="sidebar-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sidebar-text">Courses</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>admin/programs" class="sidebar-link <?php echo ($currentPage ?? '') === 'programs' ? 'active' : ''; ?>">
                        <div class="sidebar-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="sidebar-text">Programs</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>admin/analytics" class="sidebar-link <?php echo ($currentPage ?? '') === 'analytics' ? 'active' : ''; ?>">
                        <div class="sidebar-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="sidebar-text">Analytics</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>attendance" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="sidebar-text">Attendance</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a href="<?php echo BASE_URL; ?>home.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="sidebar-text">Main Site</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION['user_type'] ?? 'member')); ?></div>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>logout_process.php" class="logout-button">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_success']); ?></span>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_error']); ?></span>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_warning'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_warning']); ?></span>
                </div>
                <?php unset($_SESSION['flash_warning']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_info'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_info']); ?></span>
                </div>
                <?php unset($_SESSION['flash_info']); ?>
            <?php endif; ?>

            <!-- Validation Errors -->
            <?php if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Validation Errors:</strong>
                        <ul style="margin: 5px 0 0 20px;">
                            <?php foreach ($_SESSION['validation_errors'] as $field => $errors): ?>
                                <?php foreach ((array)$errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php unset($_SESSION['validation_errors']); ?>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="page-content">
                <?php echo $content; ?>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Make BASE_URL available globally
        window.BASE_URL = '<?php echo BASE_URL; ?>';

        // CSRF Token helpers
        window.CSRF = {
            getToken: function() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            },

            getTokenField: function() {
                return '<input type="hidden" name="csrf_token" value="' + this.getToken() + '">';
            }
        };

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>

    <script src="<?php echo BASE_URL; ?>public/assets/js/settings.js"></script>

    <?php if (isset($additionalScripts)): ?>
        <?php echo $additionalScripts; ?>
    <?php endif; ?>
</body>
</html>
