<?php
session_start();
require 'server.php';

// Check if the user is an admin
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: home.php');
    exit;
}

// Fetch user data
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    header('Location: user_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="editUser">

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
                                    <a href="reports.php">
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
                        <!-- <ul>
                            <a href="" class="SettigsNav_active"><li>Profile</li></a>
                            <a href="user_list.php"><li>Manage Members</li></a>
                            <a href=""><li>Approve members</li></a>
                        </ul> -->
                    </div>

                    <div class="editProTop">
                        <div class="image-container">
                           <h1 class="userlist-Title">Edit User Details</h1>
                            <!-- <i id="change-icon" class="icon">🔄</i> -->
                        </div>
                    </div>

                    <div class="editProBottom">
                        
                            <form action="update_user.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                                
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                
                                <label for="user_type">User Type:</label>
                                <select id="user_type" name="user_type" required>
                                    <option value="member" <?php if ($user['user_type'] == 'member') echo 'selected'; ?>>Member</option>
                                    <option value="mentor" <?php if ($user['user_type'] == 'mentor') echo 'selected'; ?>>Mentor</option>
                                    <option value="admin" <?php if ($user['user_type'] == 'admin') echo 'selected'; ?>>Admin</option>
                                </select>
                                
                                <div class="passEdit">
                                    <label for="password">Password:</label><br>
                                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">     
                                    <button type="submit">Update</button>
                                </div>  

                            </form>                 
                    </div>

                    
                   
                </div>
            </div>
        </section>
    </main>

    <!-- <h1>Edit User</h1>
     -->
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
