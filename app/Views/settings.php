<?php
session_start(); // Start the session first

// Include database connection
require_once '../../server.php'; // Updated path to server.php from app/views/
require __DIR__ . '/../../config/config.php'; // Updated path to config.php
require_once __DIR__ . '/../../core/CSRF.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../login.php');
    exit;
}

// Get the current user's type and ID
$current_user_type = $_SESSION['user_type'];
$current_user_id = $_SESSION['user_id'];

// Get the ID of the user being edited
$editing_user_id = isset($_GET['id']) ? $_GET['id'] : $current_user_id;

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
    } elseif ($current_user_type === 'mentor' && $user['user_type'] === 'member') {
        $can_edit = true; // Mentor can edit members
    } elseif ($editing_user_id == $current_user_id) {
        $can_edit = true; // Users can edit themselves
    }
}

if (!$can_edit) {
    header('Location: ../../home.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo CSRF::metaTag(); ?>
    <title>Settings | Profile</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/assets/css/settingsStyle.css">
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
                <?php if ($current_user_type === 'admin'): ?>
                <li class="sidebar-item">
                    <a href="./statsDashboard.php" class="sidebar-link">
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
                    <a href="./settings.php" class="sidebar-link active">
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
                <h1 class="content-title">Settings</h1>
            </div>
            
            <div class="settings-container">
                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <a href="./settings.php" class="settings-nav-link active">Profile</a>
                    <?php if ($current_user_type === 'admin'): ?>
                    <a href="./user_list.php" class="settings-nav-link">Manage Members</a>
                    <a href="#" class="settings-nav-link">Approve Members</a>
                    <?php endif; ?>
                </div>
                
                <!-- Settings Content Area -->
                <div class="settings-content">
                    <!-- Profile header with image -->
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="../../public/assets/images/ui-user-profile-negative.svg" alt="Profile" id="profile-image" class="profile-image" style="display: none;">
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
                    <form id="settings-form" action="<?php echo BASE_URL; ?>app/Models/Admin/update_user.php" method="post">
                        <?php echo CSRF::field(); ?>
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        
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
                                        <label for="nationality" class="form-label">Nationality</label>
                                        <select id="nationality" name="nationality" class="form-control form-select" data-other-field="other_nationality_div">
                                            <option value="South African" <?php echo (isset($user['nationality']) && $user['nationality'] == 'South African') ? 'selected' : ''; ?>>South African</option>
                                            <option value="Afghan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Afghan') ? 'selected' : ''; ?>>Afghan</option>
                                            <option value="Albanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Albanian') ? 'selected' : ''; ?>>Albanian</option>
                                            <option value="Algerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Algerian') ? 'selected' : ''; ?>>Algerian</option>
                                            <option value="British" <?php echo (isset($user['nationality']) && $user['nationality'] == 'British') ? 'selected' : ''; ?>>British</option>
                                            <option value="Chinese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                                            <option value="Egyptian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Egyptian') ? 'selected' : ''; ?>>Egyptian</option>
                                            <option value="French" <?php echo (isset($user['nationality']) && $user['nationality'] == 'French') ? 'selected' : ''; ?>>French</option>
                                            <option value="German" <?php echo (isset($user['nationality']) && $user['nationality'] == 'German') ? 'selected' : ''; ?>>German</option>
                                            <option value="Indian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Indian') ? 'selected' : ''; ?>>Indian</option>
                                            <option value="Nigerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Nigerian') ? 'selected' : ''; ?>>Nigerian</option>
                                            <option value="Zambian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zambian') ? 'selected' : ''; ?>>Zambian</option>
                                            <option value="Zimbabwean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zimbabwean') ? 'selected' : ''; ?>>Zimbabwean</option>
                                            <option value="Other" <?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'British', 'Chinese', 'Egyptian', 'French', 'German', 'Indian', 'Nigerian', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <div id="other_nationality_div" style="display: <?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'British', 'Chinese', 'Egyptian', 'French', 'German', 'Indian', 'Nigerian', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? 'block' : 'none'; ?>;">
                                            <label for="other_nationality" class="form-label">Specify Nationality</label>
                                            <input type="text" id="other_nationality" name="other_nationality" class="form-control input-control" value="<?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'British', 'Chinese', 'Egyptian', 'French', 'German', 'Indian', 'Nigerian', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? $user['nationality'] : ''; ?>" placeholder="Specify nationality">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sa-id-number" class="form-label">SA ID Number</label>
                                        <input type="text" id="sa-id-number" name="id_number" class="form-control input-control" value="<?php echo isset($user['id_number']) ? $user['id_number'] : ''; ?>">
                                        <div class="error-message"></div>
                                        <span class="form-hint">South African ID numbers are 13 digits long</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" name="gender" class="form-control form-select" required>
                                            <option value="Male" <?php echo ($user['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($user['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="dob" class="form-label">Date of Birth</label>
                                        <input type="date" id="dob" name="dob" class="form-control input-control" value="<?php echo $user['date_of_birth']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="home-language" class="form-label">Home Language</label>
                                        <select id="home-language" name="home_language" class="form-control form-select" data-other-field="other_language_div">
                                            <option value="Afrikaans" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Afrikaans') ? 'selected' : ''; ?>>Afrikaans</option>
                                            <option value="English" <?php echo (isset($user['home_language']) && $user['home_language'] == 'English') ? 'selected' : ''; ?>>English</option>
                                            <option value="isiNdebele" <?php echo (isset($user['home_language']) && $user['home_language'] == 'isiNdebele') ? 'selected' : ''; ?>>isiNdebele</option>
                                            <option value="isiXhosa" <?php echo (isset($user['home_language']) && $user['home_language'] == 'isiXhosa') ? 'selected' : ''; ?>>isiXhosa</option>
                                            <option value="isiZulu" <?php echo (isset($user['home_language']) && $user['home_language'] == 'isiZulu') ? 'selected' : ''; ?>>isiZulu</option>
                                            <option value="Sepedi" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Sepedi') ? 'selected' : ''; ?>>Sepedi</option>
                                            <option value="Sesotho" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Sesotho') ? 'selected' : ''; ?>>Sesotho</option>
                                            <option value="Setswana" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Setswana') ? 'selected' : ''; ?>>Setswana</option>
                                            <option value="siSwati" <?php echo (isset($user['home_language']) && $user['home_language'] == 'siSwati') ? 'selected' : ''; ?>>siSwati</option>
                                            <option value="Tshivenda" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Tshivenda') ? 'selected' : ''; ?>>Tshivenda</option>
                                            <option value="Xitsonga" <?php echo (isset($user['home_language']) && $user['home_language'] == 'Xitsonga') ? 'selected' : ''; ?>>Xitsonga</option>
                                            <option value="Other" <?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda','Xitsonga']) && !empty($user['home_language'])) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <div id="other_language_div" style="display: <?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda','Xitsonga']) && !empty($user['home_language'])) ? 'block' : 'none'; ?>;">
                                            <label for="other_language" class="form-label">Specify Language</label>
                                            <input type="text" id="other_language" name="other_language" class="form-control input-control" value="<?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda','Xitsonga']) && !empty($user['home_language'])) ? $user['home_language'] : ''; ?>" placeholder="Specify language">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control input-control" value="<?php echo $user['email']; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="cell-number" class="form-label">Cell Number</label>
                                        <input type="text" id="cell-number" name="cell_number" class="form-control phone-input input-control" value="<?php echo isset($user['leaner_number']) ? $user['leaner_number'] : ''; ?>">
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
                        
                        <!-- Address Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Address Information</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-street" class="form-label">Street</label>
                                        <input type="text" id="address-street" name="address_street" class="form-control input-control" value="<?php echo isset($user['address_street']) ? $user['address_street'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="address-suburb" class="form-label">Suburb/Township</label>
                                        <input type="text" id="address-suburb" name="address_suburb" class="form-control input-control" value="<?php echo isset($user['address_suburb']) ? $user['address_suburb'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-city" class="form-label">City</label>
                                        <input type="text" id="address-city" name="address_city" class="form-control input-control" value="<?php echo isset($user['address_city']) ? $user['address_city'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="address-province" class="form-label">Province</label>
                                        <select id="address-province" name="address_province" class="form-control form-select">
                                            <option value="">Select a province</option>
                                            <option value="Eastern Cape" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Eastern Cape') ? 'selected' : ''; ?>>Eastern Cape</option>
                                            <option value="Free State" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Free State') ? 'selected' : ''; ?>>Free State</option>
                                            <option value="Gauteng" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Gauteng') ? 'selected' : ''; ?>>Gauteng</option>
                                            <option value="KwaZulu-Natal" <?php echo (isset($user['address_province']) && $user['address_province'] == 'KwaZulu-Natal') ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                                            <option value="Limpopo" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Limpopo') ? 'selected' : ''; ?>>Limpopo</option>
                                            <option value="Mpumalanga" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Mpumalanga') ? 'selected' : ''; ?>>Mpumalanga</option>
                                            <option value="North West" <?php echo (isset($user['address_province']) && $user['address_province'] == 'North West') ? 'selected' : ''; ?>>North West</option>
                                            <option value="Northern Cape" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Northern Cape') ? 'selected' : ''; ?>>Northern Cape</option>
                                            <option value="Western Cape" <?php echo (isset($user['address_province']) && $user['address_province'] == 'Western Cape') ? 'selected' : ''; ?>>Western Cape</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-postal-code" class="form-label">Postal Code</label>
                                        <input type="text" id="address-postal-code" name="address_postal_code" class="form-control input-control" value="<?php echo isset($user['address_postal_code']) ? $user['address_postal_code'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                        <?php if ($user['user_type'] === 'member'): ?>
                        <!-- School Information Section (Only for Members) -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">School Information</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="school" class="form-label">School Name</label>
                                        <input type="text" id="school" name="school" class="form-control input-control" value="<?php echo $user['school']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="grade" class="form-label">Grade</label>
                                        <select id="grade" name="grade" class="form-control form-select" required>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($user['grade'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <!-- <div class="form-row">
                                    <div class="form-group">
                                        <label for="learner-number" class="form-label">Learner Number</label>
                                        <input type="text" id="learner-number" name="learner_number" class="form-control input-control" value="<?php echo $user['leaner_number']; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        
                        <!-- Parent Details Section (Only for Members) -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Parent/Guardian Details</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="parent" class="form-label">Parent/Guardian Name</label>
                                        <input type="text" id="parent" name="parent" class="form-control input-control" value="<?php echo $user['parent']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="relationship" class="form-label">Relationship</label>
                                        <input type="text" id="relationship" name="relationship" class="form-control input-control" value="<?php echo $user['Relationship']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="parent-email" class="form-label">Parent Email</label>
                                        <input type="email" id="parent-email" name="parent_email" class="form-control input-control" value="<?php echo $user['parent_email']; ?>" >
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="parent-number" class="form-label">Parent Phone</label>
                                        <input type="tel" id="parent-number" name="parent_number" class="form-control phone-input input-control" value="<?php echo $user['parent_number']; ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Emergency Contact Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Emergency Contact</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency-contact-name" class="form-label">Contact Name</label>
                                        <input type="text" id="emergency-contact-name" name="emergency_contact_name" class="form-control input-control" value="<?php echo isset($user['emergency_contact_name']) ? $user['emergency_contact_name'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency-contact-relationship" class="form-label">Relationship</label>
                                        <input type="text" id="emergency-contact-relationship" name="emergency_contact_relationship" class="form-control input-control" value="<?php echo isset($user['emergency_contact_relationship']) ? $user['emergency_contact_relationship'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency-contact-phone" class="form-label">Phone Number</label>
                                        <input type="tel" id="emergency-contact-phone" name="emergency_contact_phone" class="form-control phone-input input-control" value="<?php echo isset($user['emergency_contact_phone']) ? $user['emergency_contact_phone'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency-contact-email" class="form-label">Email</label>
                                        <input type="email" id="emergency-contact-email" name="emergency_contact_email" class="form-control input-control" value="<?php echo isset($user['emergency_contact_email']) ? $user['emergency_contact_email'] : ''; ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency-contact-address" class="form-label">Address</label>
                                        <textarea id="emergency-contact-address" name="emergency_contact_address" class="form-control form-textarea input-control"><?php echo isset($user['emergency_contact_address']) ? $user['emergency_contact_address'] : ''; ?></textarea>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($user['user_type'] === 'member'): ?>
                        <!-- Interests and Skills Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Interests and Skills</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="interests" class="form-label">What are your interests?</label>
                                        <textarea id="interests" name="interests" class="form-control form-textarea input-control"><?php echo isset($user['interests']) ? $user['interests'] : ''; ?></textarea>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="role-models" class="form-label">Who are your role models?</label>
                                        <textarea id="role-models" name="role_models" class="form-control form-textarea input-control"><?php echo isset($user['role_models']) ? $user['role_models'] : ''; ?></textarea>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="goals" class="form-label">What are your goals?</label>
                                        <textarea id="goals" name="goals" class="form-control form-textarea input-control"><?php echo isset($user['goals']) ? $user['goals'] : ''; ?></textarea>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="has-computer" class="form-label">Do you own/have access to a computer?</label>
                                        <select id="has-computer" name="has_computer" class="form-control form-select">
                                            <option value="1" <?php echo (isset($user['has_computer']) && $user['has_computer'] == 1) ? 'selected' : ''; ?>>Yes</option>
                                            <option value="0" <?php echo (isset($user['has_computer']) && $user['has_computer'] == 0) ? 'selected' : ''; ?>>No</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="computer-skills" class="form-label">What can you do on a computer?</label>
                                        <textarea id="computer-skills" name="computer_skills" class="form-control form-textarea input-control"><?php echo isset($user['computer_skills']) ? $user['computer_skills'] : ''; ?></textarea>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="computer-skills-source" class="form-label">Where did you learn your computer skills?</label>
                                        <textarea id="computer-skills-source" name="computer_skills_source" class="form-control form-textarea input-control"><?php echo isset($user['computer_skills_source']) ? $user['computer_skills_source'] : ''; ?></textarea>
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
                                        <span class="form-hint">Only fill this if you want to change your password</span>
                                    </div>
                                </div>
                            </div>
                        </div>                        
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="form-button">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../public/assets/js/modernized-settings.js"></script>
</body>
</html>