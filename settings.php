<?php
session_start(); // Start the session first
// Include database connection
require 'server.php'; // Make sure this path is correct

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
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
        header('Location: home.php');
        exit;
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./public/assets/css/settingsStyles.css">
    <link rel="stylesheet" href="./public/assets/css/screenSizes.css">
    <style>
                .form-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .form-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
            padding-right: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        
        textarea {
            height: 80px;
        }
        
        .action-buttons {
            margin-top: 20px;
            text-align: right;
        }
        
        button[type="submit"] {
            background-color: #6C63FF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        button[type="submit"]:hover {
            background-color: #5a52d5;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        /* Ensure dropdowns appear above other elements */
        .select2-dropdown {
            z-index: 9999;
        }
        /* Style for the custom input fields */
        #other_nationality, #other_language {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body id="settings">
    <main id="container-settings">
        <section id="nav_section">
            <div class="nav">
                <ul>
                    <li >
                        <a  href="home.php" >
                        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 23V53H49V23L30 8L11 23Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23.75 36.25V52.5H36.25V36.25H23.75Z" fill="#F29A2E" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M11.25 52.5H48.75" stroke="#F29A2E" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Home
                        </a>
                    </li>
                    <li>
                        <a href="projects.php">
                            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M55 36.25H5V52.5H55V36.25Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M44.375 47.5C46.1009 47.5 47.5 46.1009 47.5 44.375C47.5 42.6491 46.1009 41.25 44.375 41.25C42.6491 41.25 41.25 42.6491 41.25 44.375C41.25 46.1009 42.6491 47.5 44.375 47.5Z" fill="white"/>
                                <path d="M5 36.2498L11.298 6.24878H48.7756L55 36.2498" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M23.7575 20.0323C21.0794 20.0323 18.75 21.8905 18.75 24.391C18.75 27.4997 21.3684 28.7497 24.6216 28.7497C25.1796 28.7497 25.6959 28.7497 26.221 28.7497" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M36.2588 20.0323C38.8799 20.0323 41.25 21.2492 41.25 24.391C41.25 27.4997 38.6128 28.7497 35.3596 28.7497C34.8015 28.7497 34.2516 28.7497 33.7339 28.7497" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M36.2586 20.0326C36.2586 16.3029 33.7788 13.75 29.9997 13.75C26.2207 13.75 23.7573 16.2409 23.7573 20.0326" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M25 28.75H35" stroke="#F29A2E" stroke-width="2"/>
                            </svg>
                        Projects
                        </a>
                    </li>
                        <li>
                            <a href="members.php" >
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M52.5 10H7.5C6.11929 10 5 11.1193 5 12.5V47.5C5 48.8807 6.11929 50 7.5 50H52.5C53.8807 50 55 48.8807 55 47.5V12.5C55 11.1193 53.8807 10 52.5 10Z" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M21.25 31.25C24.0114 31.25 26.25 29.0114 26.25 26.25C26.25 23.4886 24.0114 21.25 21.25 21.25C18.4886 21.25 16.25 23.4886 16.25 26.25C16.25 29.0114 18.4886 31.25 21.25 31.25Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M28.75 38.75C28.75 34.6079 25.3921 31.25 21.25 31.25C17.1079 31.25 13.75 34.6079 13.75 38.75" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M35 25H45" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M37.5 35H45" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                            Members
                            </a> 
                        </li> 
                        <li>
                            <a href="learn.php">
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M40 7.5H27.5V52.5H40V7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M52.5 7.5H40V52.5H52.5V7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M12.5 7.5L22.5 8.75L18.125 52.5L7.5 51.25L12.5 7.5Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M46.25 22.5V18.75" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M33.75 22.5V18.75" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            Learn
                            </a>
                        </li>
                        
                        <li>
                        <!-- Show the icon only if the user is an admin -->
                        <?php 
                            if (isset($_SESSION['user_type'])) {
                                $userType = $_SESSION['user_type'];
                                if ($userType === "admin"): ?>
                                    <a href="app/Views/statsDashboard.php">
                                        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M50 15H10C8.61929 15 7.5 16.1193 7.5 17.5V50C7.5 51.3807 8.61929 52.5 10 52.5H50C51.3807 52.5 52.5 51.3807 52.5 50V17.5C52.5 16.1193 51.3807 15 50 15Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M22.437 30.0103H37.437" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7.5 16.25L16.25 6.25H43.75L52.5 16.25" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Reports
                                    </a>
                                <?php endif; 
                            }
                        ?>
                        </li>
                        
                        <li>
                            <a href="signin.php">
                            <svg width="66" height="66" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M33 59.125C47.4284 59.125 59.125 47.4284 59.125 33C59.125 18.5716 47.4284 6.875 33 6.875C18.5716 6.875 6.875 18.5716 6.875 33C6.875 47.4284 18.5716 59.125 33 59.125Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2"/>
                                <path d="M49.5083 33.817V33.8156C49.5083 29.822 46.2708 26.5845 42.2771 26.5845H23.7229C19.7292 26.5845 16.4918 29.822 16.4918 33.8156V33.817C16.4918 37.8106 19.7292 41.0481 23.7229 41.0481H42.2771C46.2708 41.0481 49.5083 37.8106 49.5083 33.817Z" fill="#8CC86E" stroke="white" stroke-width="2"/>
                                <path d="M35.7583 34.1714C35.7583 37.9683 38.8363 41.0464 42.6333 41.0464C46.4302 41.0464 49.5083 37.9683 49.5083 34.1714C49.5083 30.3744 46.4302 27.2964 42.6333 27.2964C38.8363 27.2964 35.7583 30.3744 35.7583 34.1714Z" stroke="white" stroke-width="2"/>
                            </svg>

                            Sign In
                            </a>
                        </li>

                        <li class="active">
                            <a href="settings.php">
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M30 50H8.75C6.67894 50 5 48.3211 5 46.25V13.75C5 11.6789 6.67894 10 8.75 10H51.25C53.3211 10 55 11.6789 55 13.75V28.8235" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5 13.75C5 11.6789 6.67894 10 8.75 10H51.25C53.3211 10 55 11.6789 55 13.75V25H5V13.75Z" fill="#6C63FF" stroke="#F29A2E" stroke-width="2"/>
                                    <path d="M10 17.5C10 16.1193 11.1193 15 12.5 15C13.8807 15 15 16.1193 15 17.5C15 18.8807 13.8807 20 12.5 20C11.1193 20 10 18.8807 10 17.5Z" fill="white"/>
                                    <path d="M17.5 17.5C17.5 16.1193 18.6193 15 20 15C21.3807 15 22.5 16.1193 22.5 17.5C22.5 18.8807 21.3807 20 20 20C18.6193 20 17.5 18.8807 17.5 17.5Z" fill="white"/>
                                    <path d="M46.25 46.25C48.3211 46.25 50 44.5711 50 42.5C50 40.4289 48.3211 38.75 46.25 38.75C44.1789 38.75 42.5 40.4289 42.5 42.5C42.5 44.5711 44.1789 46.25 46.25 46.25Z" stroke="#F29A2E" stroke-width="2"/>
                                    <path d="M46.25 51.25V46.25" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M46.25 38.75V33.75" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M38.6724 46.875L43.0025 44.375" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M49.4976 40.625L53.8277 38.125" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M38.6724 38.125L43.0025 40.625" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M49.4976 44.375L53.8277 46.875" stroke="#F29A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            Settings
                            </a>
                        </li>
                </ul>
            </div>

            <div class="logout">
            <a href="logout_process.php"><button>Logout</button></a>
            </div>
            
        </section>
        
        <section id="settings_main_section">
            <div class="content_section_settings">
                <h1>Settings</h1>
                <div class="settingsContainer">
                    <div class="settingsNav">
                        <a id="SettigsNav_active" href="settings.php">Profile</a>
                        <a href="user_list.php">Manage Members</a>
                        <a href="">Approve Members</a>
                    </div>

                    <div class="editProTop">
                        <div class="image-container">
                            <div id="proImage">
                                <img src="image1.jpg" alt="Profile Image">
                                <i id="change-icon" class="icon">🔄</i>
                            </div>
                            
                            <div class="usrID">
                                <h1><?php echo $user['name']; ?></h1>
                                <h1><?php echo $user['surname']; ?></h1>
                            </div>
                        </div>
                    </div>

                    <div class="editProBottom">
                        <h1>Edit Profile</h1>
                        <form action="update_user.php" method="post">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            
                            <!-- Personal Details Section -->
                            <div class="form-section">
                                <h2>Personal Details</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name">First Name:</label>
                                        <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="surname">Surname:</label>
                                        <input type="text" id="surname" name="surname" value="<?php echo $user['surname']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username">Username:</label>
                                        <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="nationality">Nationality:</label>
                                        <select id="nationality" name="nationality" class="searchable-select">
                                            <option value="">Select or type to search</option>
                                            <option value="South African" <?php echo (isset($user['nationality']) && $user['nationality'] == 'South African') ? 'selected' : ''; ?>>South African</option>
                                            <option value="Afghan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Afghan') ? 'selected' : ''; ?>>Afghan</option>
                                            <option value="Albanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Albanian') ? 'selected' : ''; ?>>Albanian</option>
                                            <option value="Algerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Algerian') ? 'selected' : ''; ?>>Algerian</option>
                                            <option value="Angolan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Angolan') ? 'selected' : ''; ?>>Angolan</option>
                                            <option value="Argentine" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Argentine') ? 'selected' : ''; ?>>Argentine</option>
                                            <option value="Australian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Australian') ? 'selected' : ''; ?>>Australian</option>
                                            <option value="Austrian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Austrian') ? 'selected' : ''; ?>>Austrian</option>
                                            <option value="Bangladeshi" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Bangladeshi') ? 'selected' : ''; ?>>Bangladeshi</option>
                                            <option value="Belgian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Belgian') ? 'selected' : ''; ?>>Belgian</option>
                                            <option value="Bolivian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Bolivian') ? 'selected' : ''; ?>>Bolivian</option>
                                            <option value="Botswanan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Botswanan') ? 'selected' : ''; ?>>Botswanan</option>
                                            <option value="Brazilian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Brazilian') ? 'selected' : ''; ?>>Brazilian</option>
                                            <option value="British" <?php echo (isset($user['nationality']) && $user['nationality'] == 'British') ? 'selected' : ''; ?>>British</option>
                                            <option value="Bulgarian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Bulgarian') ? 'selected' : ''; ?>>Bulgarian</option>
                                            <option value="Cambodian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Cambodian') ? 'selected' : ''; ?>>Cambodian</option>
                                            <option value="Cameroonian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Cameroonian') ? 'selected' : ''; ?>>Cameroonian</option>
                                            <option value="Canadian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Canadian') ? 'selected' : ''; ?>>Canadian</option>
                                            <option value="Chilean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Chilean') ? 'selected' : ''; ?>>Chilean</option>
                                            <option value="Chinese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                                            <option value="Colombian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Colombian') ? 'selected' : ''; ?>>Colombian</option>
                                            <option value="Congolese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Congolese') ? 'selected' : ''; ?>>Congolese</option>
                                            <option value="Croatian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Croatian') ? 'selected' : ''; ?>>Croatian</option>
                                            <option value="Cuban" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Cuban') ? 'selected' : ''; ?>>Cuban</option>
                                            <option value="Czech" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Czech') ? 'selected' : ''; ?>>Czech</option>
                                            <option value="Danish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Danish') ? 'selected' : ''; ?>>Danish</option>
                                            <option value="Dutch" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Dutch') ? 'selected' : ''; ?>>Dutch</option>
                                            <option value="Ecuadorian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Ecuadorian') ? 'selected' : ''; ?>>Ecuadorian</option>
                                            <option value="Egyptian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Egyptian') ? 'selected' : ''; ?>>Egyptian</option>
                                            <option value="Ethiopian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Ethiopian') ? 'selected' : ''; ?>>Ethiopian</option>
                                            <option value="Finnish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Finnish') ? 'selected' : ''; ?>>Finnish</option>
                                            <option value="French" <?php echo (isset($user['nationality']) && $user['nationality'] == 'French') ? 'selected' : ''; ?>>French</option>
                                            <option value="German" <?php echo (isset($user['nationality']) && $user['nationality'] == 'German') ? 'selected' : ''; ?>>German</option>
                                            <option value="Ghanaian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Ghanaian') ? 'selected' : ''; ?>>Ghanaian</option>
                                            <option value="Greek" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Greek') ? 'selected' : ''; ?>>Greek</option>
                                            <option value="Guatemalan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Guatemalan') ? 'selected' : ''; ?>>Guatemalan</option>
                                            <option value="Haitian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Haitian') ? 'selected' : ''; ?>>Haitian</option>
                                            <option value="Indian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Indian') ? 'selected' : ''; ?>>Indian</option>
                                            <option value="Indonesian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Indonesian') ? 'selected' : ''; ?>>Indonesian</option>
                                            <option value="Iranian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Iranian') ? 'selected' : ''; ?>>Iranian</option>
                                            <option value="Iraqi" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Iraqi') ? 'selected' : ''; ?>>Iraqi</option>
                                            <option value="Irish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Irish') ? 'selected' : ''; ?>>Irish</option>
                                            <option value="Israeli" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Israeli') ? 'selected' : ''; ?>>Israeli</option>
                                            <option value="Italian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Italian') ? 'selected' : ''; ?>>Italian</option>
                                            <option value="Jamaican" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Jamaican') ? 'selected' : ''; ?>>Jamaican</option>
                                            <option value="Japanese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Japanese') ? 'selected' : ''; ?>>Japanese</option>
                                            <option value="Jordanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Jordanian') ? 'selected' : ''; ?>>Jordanian</option>
                                            <option value="Kenyan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Kenyan') ? 'selected' : ''; ?>>Kenyan</option>
                                            <option value="Korean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Korean') ? 'selected' : ''; ?>>Korean</option>
                                            <option value="Kuwaiti" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Kuwaiti') ? 'selected' : ''; ?>>Kuwaiti</option>
                                            <option value="Lebanese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Lebanese') ? 'selected' : ''; ?>>Lebanese</option>
                                            <option value="Libyan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Libyan') ? 'selected' : ''; ?>>Libyan</option>
                                            <option value="Malaysian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Malaysian') ? 'selected' : ''; ?>>Malaysian</option>
                                            <option value="Mexican" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Mexican') ? 'selected' : ''; ?>>Mexican</option>
                                            <option value="Moroccan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Moroccan') ? 'selected' : ''; ?>>Moroccan</option>
                                            <option value="Mozambican" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Mozambican') ? 'selected' : ''; ?>>Mozambican</option>
                                            <option value="Namibian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Namibian') ? 'selected' : ''; ?>>Namibian</option>
                                            <option value="Nepalese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Nepalese') ? 'selected' : ''; ?>>Nepalese</option>
                                            <option value="New Zealander" <?php echo (isset($user['nationality']) && $user['nationality'] == 'New Zealander') ? 'selected' : ''; ?>>New Zealander</option>
                                            <option value="Nigerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Nigerian') ? 'selected' : ''; ?>>Nigerian</option>
                                            <option value="Norwegian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Norwegian') ? 'selected' : ''; ?>>Norwegian</option>
                                            <option value="Pakistani" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Pakistani') ? 'selected' : ''; ?>>Pakistani</option>
                                            <option value="Peruvian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Peruvian') ? 'selected' : ''; ?>>Peruvian</option>
                                            <option value="Philippine" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Philippine') ? 'selected' : ''; ?>>Philippine</option>
                                            <option value="Polish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Polish') ? 'selected' : ''; ?>>Polish</option>
                                            <option value="Portuguese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Portuguese') ? 'selected' : ''; ?>>Portuguese</option>
                                            <option value="Romanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Romanian') ? 'selected' : ''; ?>>Romanian</option>
                                            <option value="Russian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Russian') ? 'selected' : ''; ?>>Russian</option>
                                            <option value="Saudi" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Saudi') ? 'selected' : ''; ?>>Saudi</option>
                                            <option value="Scottish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Scottish') ? 'selected' : ''; ?>>Scottish</option>
                                            <option value="Senegalese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Senegalese') ? 'selected' : ''; ?>>Senegalese</option>
                                            <option value="Serbian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Serbian') ? 'selected' : ''; ?>>Serbian</option>
                                            <option value="Singaporean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Singaporean') ? 'selected' : ''; ?>>Singaporean</option>
                                            <option value="Slovak" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Slovak') ? 'selected' : ''; ?>>Slovak</option>
                                            <option value="Somali" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Somali') ? 'selected' : ''; ?>>Somali</option>
                                            <option value="Spanish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                                            <option value="Sudanese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Sudanese') ? 'selected' : ''; ?>>Sudanese</option>
                                            <option value="Swedish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Swedish') ? 'selected' : ''; ?>>Swedish</option>
                                            <option value="Swiss" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Swiss') ? 'selected' : ''; ?>>Swiss</option>
                                            <option value="Syrian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Syrian') ? 'selected' : ''; ?>>Syrian</option>
                                            <option value="Taiwanese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Taiwanese') ? 'selected' : ''; ?>>Taiwanese</option>
                                            <option value="Tanzanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Tanzanian') ? 'selected' : ''; ?>>Tanzanian</option>
                                            <option value="Thai" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Thai') ? 'selected' : ''; ?>>Thai</option>
                                            <option value="Tunisian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Tunisian') ? 'selected' : ''; ?>>Tunisian</option>
                                            <option value="Turkish" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Turkish') ? 'selected' : ''; ?>>Turkish</option>
                                            <option value="Ugandan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Ugandan') ? 'selected' : ''; ?>>Ugandan</option>
                                            <option value="Ukrainian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Ukrainian') ? 'selected' : ''; ?>>Ukrainian</option>
                                            <option value="Emirati" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Emirati') ? 'selected' : ''; ?>>Emirati</option>
                                            <option value="American" <?php echo (isset($user['nationality']) && $user['nationality'] == 'American') ? 'selected' : ''; ?>>American</option>
                                            <option value="Uruguayan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Uruguayan') ? 'selected' : ''; ?>>Uruguayan</option>
                                            <option value="Venezuelan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Venezuelan') ? 'selected' : ''; ?>>Venezuelan</option>
                                            <option value="Vietnamese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Vietnamese') ? 'selected' : ''; ?>>Vietnamese</option>
                                            <option value="Welsh" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Welsh') ? 'selected' : ''; ?>>Welsh</option>
                                            <option value="Yemeni" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Yemeni') ? 'selected' : ''; ?>>Yemeni</option>
                                            <option value="Zambian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zambian') ? 'selected' : ''; ?>>Zambian</option>
                                            <option value="Zimbabwean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zimbabwean') ? 'selected' : ''; ?>>Zimbabwean</option>
                                            <option value="Other" <?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'Angolan', 'Argentine', 'Australian', 'Austrian', 'Bangladeshi', 'Belgian', 'Bolivian', 'Botswanan', 'Brazilian', 'British', 'Bulgarian', 'Cambodian', 'Cameroonian', 'Canadian', 'Chilean', 'Chinese', 'Colombian', 'Congolese', 'Croatian', 'Cuban', 'Czech', 'Danish', 'Dutch', 'Ecuadorian', 'Egyptian', 'Ethiopian', 'Finnish', 'French', 'German', 'Ghanaian', 'Greek', 'Guatemalan', 'Haitian', 'Indian', 'Indonesian', 'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Jamaican', 'Japanese', 'Jordanian', 'Kenyan', 'Korean', 'Kuwaiti', 'Lebanese', 'Libyan', 'Malaysian', 'Mexican', 'Moroccan', 'Mozambican', 'Namibian', 'Nepalese', 'New Zealander', 'Nigerian', 'Norwegian', 'Pakistani', 'Peruvian', 'Philippine', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Saudi', 'Scottish', 'Senegalese', 'Serbian', 'Singaporean', 'Slovak', 'Somali', 'Spanish', 'Sudanese', 'Swedish', 'Swiss', 'Syrian', 'Taiwanese', 'Tanzanian', 'Thai', 'Tunisian', 'Turkish', 'Ugandan', 'Ukrainian', 'Emirati', 'American', 'Uruguayan', 'Venezuelan', 'Vietnamese', 'Welsh', 'Yemeni', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div id="other_nationality_div" style="display: <?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'Angolan', 'Argentine', 'Australian', 'Austrian', 'Bangladeshi', 'Belgian', 'Bolivian', 'Botswanan', 'Brazilian', 'British', 'Bulgarian', 'Cambodian', 'Cameroonian', 'Canadian', 'Chilean', 'Chinese', 'Colombian', 'Congolese', 'Croatian', 'Cuban', 'Czech', 'Danish', 'Dutch', 'Ecuadorian', 'Egyptian', 'Ethiopian', 'Finnish', 'French', 'German', 'Ghanaian', 'Greek', 'Guatemalan', 'Haitian', 'Indian', 'Indonesian', 'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Jamaican', 'Japanese', 'Jordanian', 'Kenyan', 'Korean', 'Kuwaiti', 'Lebanese', 'Libyan', 'Malaysian', 'Mexican', 'Moroccan', 'Mozambican', 'Namibian', 'Nepalese', 'New Zealander', 'Nigerian', 'Norwegian', 'Pakistani', 'Peruvian', 'Philippine', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Saudi', 'Scottish', 'Senegalese', 'Serbian', 'Singaporean', 'Slovak', 'Somali', 'Spanish', 'Sudanese', 'Swedish', 'Swiss', 'Syrian', 'Taiwanese', 'Tanzanian', 'Thai', 'Tunisian', 'Turkish', 'Ugandan', 'Ukrainian', 'Emirati', 'American', 'Uruguayan', 'Venezuelan', 'Vietnamese', 'Welsh', 'Yemeni', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? 'block' : 'none'; ?>; margin-top: 5px;">
                                            <input type="text" id="other_nationality" name="other_nationality" value="<?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'Angolan', 'Argentine', 'Australian', 'Austrian', 'Bangladeshi', 'Belgian', 'Bolivian', 'Botswanan', 'Brazilian', 'British', 'Bulgarian', 'Cambodian', 'Cameroonian', 'Canadian', 'Chilean', 'Chinese', 'Colombian', 'Congolese', 'Croatian', 'Cuban', 'Czech', 'Danish', 'Dutch', 'Ecuadorian', 'Egyptian', 'Ethiopian', 'Finnish', 'French', 'German', 'Ghanaian', 'Greek', 'Guatemalan', 'Haitian', 'Indian', 'Indonesian', 'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Jamaican', 'Japanese', 'Jordanian', 'Kenyan', 'Korean', 'Kuwaiti', 'Lebanese', 'Libyan', 'Malaysian', 'Mexican', 'Moroccan', 'Mozambican', 'Namibian', 'Nepalese', 'New Zealander', 'Nigerian', 'Norwegian', 'Pakistani', 'Peruvian', 'Philippine', 'Polish', 'Portuguese', 'Romanian', 'Russian', 'Saudi', 'Scottish', 'Senegalese', 'Serbian', 'Singaporean', 'Slovak', 'Somali', 'Spanish', 'Sudanese', 'Swedish', 'Swiss', 'Syrian', 'Taiwanese', 'Tanzanian', 'Thai', 'Tunisian', 'Turkish', 'Ugandan', 'Ukrainian', 'Emirati', 'American', 'Uruguayan', 'Venezuelan', 'Vietnamese', 'Welsh', 'Yemeni', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? $user['nationality'] : ''; ?>" placeholder="Specify nationality">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="id_number">SA ID Number:</label>
                                        <input type="text" id="id_number" name="id_number" value="<?php echo isset($user['id_number']) ? $user['id_number'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender:</label>
                                        <select id="gender" name="gender" required>
                                            <option value="Male" <?php echo ($user['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($user['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="dob">Date of Birth:</label>
                                        <input type="date" id="dob" name="dob" value="<?php echo $user['date_of_birth']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="home_language">Home Language:</label>
                                        <select id="home_language" name="home_language" class="searchable-select">
                                            <option value="">Select or type to search</option>
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
                                            <option value="Other" <?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda', 'Xitsonga']) && !empty($user['home_language'])) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div id="other_language_div" style="display: <?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda', 'Xitsonga']) && !empty($user['home_language'])) ? 'block' : 'none'; ?>; margin-top: 5px;">
                                            <input type="text" id="other_language" name="other_language" value="<?php echo (isset($user['home_language']) && !in_array($user['home_language'], ['Afrikaans', 'English', 'isiNdebele', 'isiXhosa', 'isiZulu', 'Sepedi', 'Sesotho', 'Setswana', 'siSwati', 'Tshivenda', 'Xitsonga']) && !empty($user['home_language'])) ? $user['home_language'] : ''; ?>" placeholder="Specify language">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email:</label>
                                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cell_number">Cell Number:</label>
                                        <input type="tel" id="cell_number" name="cell_number" pattern="[0-9]+" value="<?php echo isset($user['leaner_number']) ? $user['leaner_number'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <?php if ($current_user_type === 'admin'): ?>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="user_type">User Type:</label>
                                        <select id="user_type" name="user_type" required>
                                            <option value="member" <?php echo ($user['user_type'] == 'member') ? 'selected' : ''; ?>>Member</option>
                                            <option value="mentor" <?php echo ($user['user_type'] == 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                                            <option value="admin" <?php echo ($user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="alumni" <?php echo ($user['user_type'] == 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                                            <option value="community" <?php echo ($user['user_type'] == 'community') ? 'selected' : ''; ?>>Community</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="center">Center:</label>
                                        <select id="center" name="center" required>
                                            <option value="Sci-Bono Clubhouse" <?php echo ($user['Center'] == 'Sci-Bono Clubhouse') ? 'selected' : ''; ?>>Sci-Bono Clubhouse</option>
                                            <option value="Waverly Girls Solar Lab" <?php echo ($user['Center'] == 'Waverly Girls Solar Lab') ? 'selected' : ''; ?>>Waverly Girls Solar Lab</option>
                                            <option value="Mapetla Solar Lab" <?php echo ($user['Center'] == 'Mapetla Solar Lab') ? 'selected' : ''; ?>>Mapetla Solar Lab</option>
                                            <option value="Emdeni Solar Lab" <?php echo ($user['Center'] == 'Emdeni Solar Lab') ? 'selected' : ''; ?>>Emdeni Solar Lab</option>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Address Section -->
                            <div class="form-section">
                                <h2>Address Information</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address_street">Street:</label>
                                        <input type="text" id="address_street" name="address_street" value="<?php echo isset($user['address_street']) ? $user['address_street'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address_suburb">Suburb/Township:</label>
                                        <input type="text" id="address_suburb" name="address_suburb" value="<?php echo isset($user['address_suburb']) ? $user['address_suburb'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address_city">City:</label>
                                        <input type="text" id="address_city" name="address_city" value="<?php echo isset($user['address_city']) ? $user['address_city'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address_province">Province:</label>
                                        <select id="address_province" name="address_province">
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
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address_postal_code">Postal Code:</label>
                                        <input type="text" id="address_postal_code" name="address_postal_code" value="<?php echo isset($user['address_postal_code']) ? $user['address_postal_code'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Medical Information Section -->
                            <div class="form-section">
                                <h2>Medical Information</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="medical_aid_name">Medical Aid Name:</label>
                                        <input type="text" id="medical_aid_name" name="medical_aid_name" value="<?php echo isset($user['medical_aid_name']) ? $user['medical_aid_name'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="medical_aid_holder">Medical Aid Holder:</label>
                                        <input type="text" id="medical_aid_holder" name="medical_aid_holder" value="<?php echo isset($user['medical_aid_holder']) ? $user['medical_aid_holder'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="medical_aid_number">Medical Aid Number:</label>
                                        <input type="text" id="medical_aid_number" name="medical_aid_number" value="<?php echo isset($user['medical_aid_number']) ? $user['medical_aid_number'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- School Information (Only for Members) -->
                            <?php if ($user['user_type'] === 'member'): ?>
                            <div class="form-section">
                                <h2>School Information</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="school">School Name:</label>
                                        <input type="text" id="school" name="school" value="<?php echo $user['school']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="grade">Grade:</label>
                                        <input type="number" id="grade" name="grade" min="1" max="12" value="<?php echo $user['grade']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="learner_number">Learner Number:</label>
                                        <input type="text" id="learner_number" name="learner_number" value="<?php echo $user['leaner_number']; ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Emergency Contact Information -->
                            <div class="form-section">
                                <h2>Emergency Contact</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency_contact_name">Contact Name:</label>
                                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo isset($user['emergency_contact_name']) ? $user['emergency_contact_name'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency_contact_relationship">Relationship:</label>
                                        <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" value="<?php echo isset($user['emergency_contact_relationship']) ? $user['emergency_contact_relationship'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency_contact_phone">Phone Number:</label>
                                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo isset($user['emergency_contact_phone']) ? $user['emergency_contact_phone'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency_contact_email">Email:</label>
                                        <input type="email" id="emergency_contact_email" name="emergency_contact_email" value="<?php echo isset($user['emergency_contact_email']) ? $user['emergency_contact_email'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency_contact_address">Address:</label>
                                        <textarea id="emergency_contact_address" name="emergency_contact_address"><?php echo isset($user['emergency_contact_address']) ? $user['emergency_contact_address'] : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Parent Details (Only for Members) -->
                            <?php if ($user['user_type'] === 'member'): ?>
                            <div class="form-section">
                                <h2>Parent/Guardian Details</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="parent">Parent/Guardian Name:</label>
                                        <input type="text" id="parent" name="parent" value="<?php echo $user['parent']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="relationship">Relationship:</label>
                                        <input type="text" id="relationship" name="relationship" value="<?php echo $user['Relationship']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="parent_email">Parent Email:</label>
                                        <input type="email" id="parent_email" name="parent_email" value="<?php echo $user['parent_email']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="parent_number">Parent Phone:</label>
                                        <input type="tel" id="parent_number" name="parent_number" value="<?php echo $user['parent_number']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <!-- Interests and Computer Skills -->
                            <div class="form-section">
                                <h2>Interests and Skills</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="interests">What are your interests?</label>
                                        <textarea id="interests" name="interests"><?php echo isset($user['interests']) ? $user['interests'] : ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="role_models">Who are your role models?</label>
                                        <textarea id="role_models" name="role_models"><?php echo isset($user['role_models']) ? $user['role_models'] : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="goals">What are your goals?</label>
                                        <textarea id="goals" name="goals"><?php echo isset($user['goals']) ? $user['goals'] : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="has_computer">Do you own/have access to a computer?</label>
                                        <select id="has_computer" name="has_computer">
                                            <option value="1" <?php echo (isset($user['has_computer']) && $user['has_computer'] == 1) ? 'selected' : ''; ?>>Yes</option>
                                            <option value="0" <?php echo (isset($user['has_computer']) && $user['has_computer'] == 0) ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="computer_skills">What can you do on a computer?</label>
                                        <textarea id="computer_skills" name="computer_skills"><?php echo isset($user['computer_skills']) ? $user['computer_skills'] : ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="computer_skills_source">Where did you learn your computer skills?</label>
                                        <textarea id="computer_skills_source" name="computer_skills_source"><?php echo isset($user['computer_skills_source']) ? $user['computer_skills_source'] : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <?php endif; ?>
                            
                            <!-- Password Section -->
                            <div class="form-section">
                                <h2>Password</h2>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="password">New Password:</label>
                                        <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                                        <small>Only fill this if you want to change your password</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for searchable dropdowns
            $('.searchable-select').select2({
                width: '100%',
                placeholder: 'Select or type to search',
                allowClear: true
            });
            
            // Check if "Other" is initially selected for nationality
            if ($('#nationality').val() === 'Other') {
                $('#other_nationality_div').show();
            }
            
            // Handle "Other" option for nationality
            $('#nationality').on('change', function() {
                if ($(this).val() === 'Other') {
                    $('#other_nationality_div').show();
                } else {
                    $('#other_nationality_div').hide();
                }
            });
            
            // Check if "Other" is initially selected for language
            if ($('#home_language').val() === 'Other') {
                $('#other_language_div').show();
            }
            
            // Handle "Other" option for language
            $('#home_language').on('change', function() {
                if ($(this).val() === 'Other') {
                    $('#other_language_div').show();
                } else {
                    $('#other_language_div').hide();
                }
            });
            
            // Handle form submission for nationality and language
            $('form').on('submit', function() {
                if ($('#nationality').val() === 'Other') {
                    var otherNationality = $('#other_nationality').val();
                    if (otherNationality) {
                        // Create a hidden input to store the actual value
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'nationality',
                            value: otherNationality
                        }).appendTo('form');
                    }
                }
                
                if ($('#home_language').val() === 'Other') {
                    var otherLanguage = $('#other_language').val();
                    if (otherLanguage) {
                        // Create a hidden input to store the actual value
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'home_language',
                            value: otherLanguage
                        }).appendTo('form');
                    }
                }
            });
        });
    </script>

</body>
</html>