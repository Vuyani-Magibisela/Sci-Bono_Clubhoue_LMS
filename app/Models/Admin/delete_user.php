<?php
session_start(); // Start the session first

// Include database connection
require_once 'server.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Get the current user's type and ID
$current_user_type = $_SESSION['user_type'];
$current_user_id = $_SESSION['id'];

// Get the ID of the user being edited
$editing_user_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

// Fetch the user being edited
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $editing_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check permissions
$can_edit = false;
if ($user) {
    if ($current_user_type === 'admin') {
        $can_edit = true; // Admin can edit anyone
    } elseif ($current_user_type === 'mentor' && ($user['user_type'] === 'member' || $user['user_type'] === 'community')) {
        $can_edit = true; // Mentor can edit members and community users
    } elseif ($editing_user_id == $current_user_id) {
        $can_edit = true; // Users can edit themselves
    }
}

if (!$can_edit) {
    // Not allowed to edit, redirect to home
    $_SESSION['message'] = "You do not have permission to edit this user's information.";
    $_SESSION['message_type'] = "danger";
    header('Location: home.php');
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = $_POST['name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Additional fields based on user type
    $dob = $_POST['dob'] ?? null;
    $school = $_POST['school'] ?? '';
    $grade = isset($_POST['grade']) ? intval($_POST['grade']) : 0;
    $user_type = $_POST['user_type'] ?? $user['user_type']; // Default to current type
    $center = $_POST['center'] ?? $user['Center'];
    
    // Check if password should be updated
    $password_update = '';
    if (!empty($_POST['password'])) {
        // Hash the new password
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_update = ", password = '$hashed_password'";
    }
    
    // Only admins can change user type
    if ($current_user_type !== 'admin') {
        $user_type = $user['user_type']; // Keep original user type
    }
    
    // Basic validation
    if (empty($name) || empty($surname) || empty($username) || empty($email)) {
        $message = "Name, surname, username, and email are required fields.";
        $message_type = "danger";
    } else {
        // Check if username is already taken by another user
        $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $username, $editing_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username is already taken. Please choose another.";
            $message_type = "danger";
        } else {
            // Prepare SQL for update
            $sql = "UPDATE users SET 
                    name = ?,
                    surname = ?,
                    username = ?,
                    email = ?,
                    Gender = ?,
                    date_of_birth = ?,
                    school = ?,
                    grade = ?,
                    user_type = ?,
                    Center = ?
                    $password_update
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            
            if ($password_update) {
                $stmt->bind_param("sssssssissi", 
                    $name, 
                    $surname, 
                    $username, 
                    $email, 
                    $gender, 
                    $dob, 
                    $school, 
                    $grade, 
                    $user_type, 
                    $center, 
                    $editing_user_id
                );
            } else {
                $stmt->bind_param("sssssssissi", 
                    $name, 
                    $surname, 
                    $username, 
                    $email, 
                    $gender, 
                    $dob, 
                    $school, 
                    $grade, 
                    $user_type, 
                    $center, 
                    $editing_user_id
                );
            }
            
            if ($stmt->execute()) {
                $message = "User information updated successfully.";
                $message_type = "success";
                
                // Refresh user data if editing self
                if ($editing_user_id == $current_user_id) {
                    $_SESSION['username'] = $username;
                    $_SESSION['name'] = $name;
                    $_SESSION['surname'] = $surname;
                    $_SESSION['email'] = $email;
                    // Update user_type in session only if admin changed their own type
                    if ($current_user_type === 'admin') {
                        $_SESSION['user_type'] = $user_type;
                    }
                }
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $editing_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $message = "Error updating user information: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Set page title based on who is being edited
$is_self_edit = ($editing_user_id == $current_user_id);
$page_title = $is_self_edit ? "Edit Your Profile" : "Edit User: " . ($user['name'] ?? '') . " " . ($user['surname'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/settingsStyle.css">
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
                    <img src="public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="home.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="sidebar-text">Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="projects.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <span class="sidebar-text">Projects</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="members.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="sidebar-text">Members</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="app/Views/learn.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sidebar-text">Learn</span>
                    </a>
                </li>
                <?php if ($current_user_type === 'admin'): ?>
                <li class="sidebar-item">
                    <a href="app/Views/statsDashboard.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="sidebar-text">Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="sidebar-item">
                    <a href="signin.php" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <span class="sidebar-text">Daily Register</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="app/Views/settings.php" class="sidebar-link active">
                        <div class="sidebar-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="sidebar-text">Settings</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="logout_process.php" class="logout-button">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1 class="content-title"><?php echo $page_title; ?></h1>
                
                <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="settings-container">
                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <a href="app/Views/settings.php" class="settings-nav-link active">Profile</a>
                    <?php if ($current_user_type === 'admin'): ?>
                    <a href="app/Views/user_list.php" class="settings-nav-link">Manage Members</a>
                    <a href="#" class="settings-nav-link">Approve Members</a>
                    <?php endif; ?>
                </div>
                
                <!-- Settings Content Area -->
                <div class="settings-content">
                    <!-- Profile header with image -->
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="public/assets/images/ui-user-profile-negative.svg" alt="Profile" id="profile-image" class="profile-image" style="display: none;">
                            <div class="profile-image-placeholder"><?php echo substr($user['name'], 0, 1); ?></div>
                            <input type="file" id="profile-image-input" accept="image/*" style="display: none;">
                            <button type="button" id="change-image-btn" class="change-image" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-name"><?php echo $user['name'] . ' ' . $user['surname']; ?></h2>
                            <p class="profile-role"><?php echo ucfirst($user['user_type']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Form -->
                    <form id="settings-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($is_self_edit ? "" : "?id=" . $editing_user_id)); ?>" method="post">
                        <!-- Personal Details Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Personal Details</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first-name" class="form-label">First Name</label>
                                        <input type="text" id="first-name" name="name" class="form-control input-control" value="<?php echo $user['name']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="surname" class="form-label">Surname</label>
                                        <input type="text" id="surname" name="surname" class="form-control input-control" value="<?php echo $user['surname']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" id="username" name="username" class="form-control input-control" value="<?php echo $user['username']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control input-control" value="<?php echo $user['email']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" name="gender" class="form-control form-select" required>
                                            <option value="Male" <?php echo ($user['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($user['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="dob" class="form-label">Date of Birth</label>
                                        <input type="date" id="dob" name="dob" class="form-control input-control" value="<?php echo $user['date_of_birth']; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <?php if ($current_user_type === 'admin'): ?>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="user-type" class="form-label">User Type</label>
                                        <select id="user-type" name="user_type" class="form-control form-select" required>
                                            <option value="member" <?php echo ($user['user_type'] == 'member') ? 'selected' : ''; ?>>Member</option>
                                            <option value="mentor" <?php echo ($user['user_type'] == 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                                            <option value="admin" <?php echo ($user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="alumni" <?php echo ($user['user_type'] == 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                                            <option value="community" <?php echo ($user['user_type'] == 'community') ? 'selected' : ''; ?>>Community</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="center" class="form-label">Center</label>
                                        <select id="center" name="center" class="form-control form-select" required>
                                            <option value="Sci-Bono Clubhouse" <?php echo ($user['Center'] == 'Sci-Bono Clubhouse') ? 'selected' : ''; ?>>Sci-Bono Clubhouse</option>
                                            <option value="Waverly Girls Solar Lab" <?php echo ($user['Center'] == 'Waverly Girls Solar Lab') ? 'selected' : ''; ?>>Waverly Girls Solar Lab</option>
                                            <option value="Mapetla Solar Lab" <?php echo ($user['Center'] == 'Mapetla Solar Lab') ? 'selected' : ''; ?>>Mapetla Solar Lab</option>
                                            <option value="Emdeni Solar Lab" <?php echo ($user['Center'] == 'Emdeni Solar Lab') ? 'selected' : ''; ?>>Emdeni Solar Lab</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($user['user_type'] === 'member'): ?>
                        <!-- School Information -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">School Information</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="school" class="form-label">School Name</label>
                                        <input type="text" id="school" name="school" class="form-control input-control" value="<?php echo $user['school']; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="grade" class="form-label">Grade</label>
                                        <select id="grade" name="grade" class="form-control form-select">
                                            <option value="0">Select Grade</option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($user['grade'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Password Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Password</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="password" class="form-label">New Password</label>
                                        <input type="password" id="password" name="password" class="form-control input-control" placeholder="Leave blank to keep current password">
                                        <div class="error-message"></div>
                                        <span class="form-hint">Only fill this if you want to change the password</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <?php if (!$is_self_edit): ?>
                            <a href="app/Views/user_list.php" class="form-button form-button-secondary">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="form-button">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="public/assets/js/settings.js"></script>
</body>
</html>