<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Include the auto-logout script to track inactivity
include '../Controllers/sessionTimer.php';

// Include database connection
require_once '../../server.php';
require __DIR__ . '/../../config/config.php'; // Include the config file

// Fetch all users from database
$sql = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($sql);
$users = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Process any messages passed from other pages
$message = '';
$messageType = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    
    // Clear the message after displaying it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../public/assets/css/settingsStyle.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for user management */
        .user-list-container {
            margin-top: 20px;
        }
        
        .user-list {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-list th, .user-list td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .user-list th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .user-list tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .user-action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .edit-btn {
            background-color: #3498db;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #2980b9;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
        }
        
        .user-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-admin {
            background-color: #e74c3c;
            color: white;
        }
        
        .badge-mentor {
            background-color: #3498db;
            color: white;
        }
        
        .badge-member {
            background-color: #2ecc71;
            color: white;
        }
        
        .badge-alumni {
            background-color: #9b59b6;
            color: white;
        }
        
        .badge-community {
            background-color: #f39c12;
            color: white;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-btn {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-btn:hover {
            background-color: #2980b9;
        }
        
        .add-user-btn {
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
        }
        
        .pagination a.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #f8f9fa;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                    <img src="../../public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="../../home.php" class="sidebar-link">
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
                    <a href="./learn.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sidebar-text">Learn</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./statsDashboard.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="sidebar-text">Reports</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../../signin.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <span class="sidebar-text">Daily Register</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./settings.php" class="sidebar-link">
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

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1 class="content-title">User Management</h1>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <div class="settings-container">
                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <a href="./settings.php" class="settings-nav-link">Profile</a>
                    <a href="./user_list.php" class="settings-nav-link active">Manage Members</a>
                    <a href="#" class="settings-nav-link">Approve Members</a>
                </div>
                
                <!-- Settings Content Area -->
                <div class="settings-content">
                    <div class="settings-header">
                        <h2>All Users</h2>
                        <a href="../../signup.php" class="form-button add-user-btn">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                    
                    <!-- Search and filters -->
                    <div class="search-form">
                        <input type="text" class="search-input" placeholder="Search by name, email, or username...">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    
                    <div class="filters">
                        <select class="filter-select">
                            <option value="">All User Types</option>
                            <option value="admin">Admin</option>
                            <option value="mentor">Mentor</option>
                            <option value="member">Member</option>
                            <option value="alumni">Alumni</option>
                            <option value="community">Community</option>
                        </select>
                        
                        <select class="filter-select">
                            <option value="">All Centers</option>
                            <option value="Sci-Bono Clubhouse">Sci-Bono Clubhouse</option>
                            <option value="Waverly Girls Solar Lab">Waverly Girls Solar Lab</option>
                            <option value="Mapetla Solar Lab">Mapetla Solar Lab</option>
                            <option value="Emdeni Solar Lab">Emdeni Solar Lab</option>
                        </select>
                    </div>
                    
                    <!-- User list -->
                    <div class="user-list-container">
                        <table class="user-list">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Center</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6">No users found.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="user-type-badge badge-<?php echo $user['user_type']; ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['Center']); ?></td>
                                        <td class="user-actions">
                                            <a href="./settings.php?id=<?php echo $user['id']; ?>" class="user-action-btn edit-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                                <a href="<?php echo BASE_URL; ?>user-delete.php?id=<?php echo $user['id']; ?>" class="user-action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <a href="#">&laquo;</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">4</a>
                        <a href="#">5</a>
                        <a href="#">&raquo;</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar expansion
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (sidebarToggle && sidebar && mainContent) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('sidebar-expanded');
                    mainContent.classList.toggle('main-expanded');
                });
            }
            
            // Mobile sidebar toggle
            const mobileToggle = document.getElementById('mobile-nav-toggle');
            
            if (mobileToggle && sidebar) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('sidebar-visible');
                });
            }
            
            // Close sidebar when clicking outside (mobile)
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && 
                    sidebar && 
                    sidebar.classList.contains('sidebar-visible') && 
                    !sidebar.contains(event.target) && 
                    mobileToggle && 
                    !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('sidebar-visible');
                }
            });
            
            // Add confirmation for delete actions
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Add search functionality
            const searchInput = document.querySelector('.search-input');
            const searchBtn = document.querySelector('.search-btn');
            const userRows = document.querySelectorAll('.user-list tbody tr');
            
            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const searchTerm = searchInput.value.toLowerCase();
                
                userRows.forEach(row => {
                    const name = row.querySelector('td:first-child').textContent.toLowerCase();
                    const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || username.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Add filter functionality
            const filterSelects = document.querySelectorAll('.filter-select');
            
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const userTypeFilter = filterSelects[0].value;
                    const centerFilter = filterSelects[1].value;
                    
                    userRows.forEach(row => {
                        const userType = row.querySelector('td:nth-child(4) .user-type-badge').textContent.toLowerCase();
                        const center = row.querySelector('td:nth-child(5)').textContent;
                        
                        const matchesUserType = userTypeFilter === '' || userType.includes(userTypeFilter.toLowerCase());
                        const matchesCenter = centerFilter === '' || center === centerFilter;
                        
                        if (matchesUserType && matchesCenter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>