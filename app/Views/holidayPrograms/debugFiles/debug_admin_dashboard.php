<?php
/**
 * DEBUG SCRIPT FOR HOLIDAY PROGRAM ADMIN DASHBOARD
 * 
 * This script will help diagnose issues with accessing the admin dashboard.
 * Upload this file to your server and access it to see what's happening.
 * 
 * IMPORTANT: Remove this file once you've fixed the issue for security!
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your database connection
require_once '../../../server.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Program Admin Dashboard - Debug</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .debug-section { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .error { color: #d32f2f; }
        .success { color: #388e3c; }
        .warning { color: #f57c00; }
        .info { color: #1976d2; }
        pre { 
            background: #f8f8f8; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { background: #f2f2f2; }
        .fix-button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .fix-button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🔧 Holiday Program Admin Dashboard Debug Tool</h1>
    <p><strong>WARNING:</strong> This is a debug tool. Remove it from your server once the issue is resolved!</p>

    <?php
    // 1. Check session variables
    echo '<div class="debug-section">';
    echo '<h2>1. Session Variables Analysis</h2>';
    
    if (empty($_SESSION)) {
        echo '<p class="error">❌ No session variables found. You may need to login first.</p>';
    } else {
        echo '<h3>All Session Variables:</h3>';
        echo '<table>';
        echo '<tr><th>Variable</th><th>Value</th></tr>';
        foreach ($_SESSION as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars(print_r($value, true)) . '</td></tr>';
        }
        echo '</table>';
    }
    
    // Check specific session variables needed for admin dashboard
    echo '<h3>Required Session Variables for Admin Dashboard:</h3>';
    $required_vars = ['loggedin', 'user_type'];
    $missing_vars = [];
    
    echo '<table>';
    echo '<tr><th>Variable</th><th>Expected</th><th>Actual</th><th>Status</th></tr>';
    
    foreach ($required_vars as $var) {
        $status = isset($_SESSION[$var]) ? '✅ Present' : '❌ Missing';
        $actual = isset($_SESSION[$var]) ? $_SESSION[$var] : 'Not Set';
        
        if ($var === 'loggedin') {
            $expected = 'true (boolean)';
            if (!isset($_SESSION[$var]) || $_SESSION[$var] != true) {
                $missing_vars[] = $var;
                $status = '❌ Missing or False';
            }
        } elseif ($var === 'user_type') {
            $expected = 'admin';
            if (!isset($_SESSION[$var]) || $_SESSION[$var] !== 'admin') {
                $missing_vars[] = $var;
                $status = '❌ Not Admin';
            }
        }
        
        echo '<tr><td>' . $var . '</td><td>' . $expected . '</td><td>' . htmlspecialchars(print_r($actual, true)) . '</td><td>' . $status . '</td></tr>';
    }
    echo '</table>';
    
    // Check holiday program session variables
    echo '<h3>Holiday Program Session Variables:</h3>';
    $holiday_vars = ['holiday_logged_in', 'holiday_user_type', 'holiday_is_admin'];
    
    echo '<table>';
    echo '<tr><th>Variable</th><th>Value</th><th>Status</th></tr>';
    
    foreach ($holiday_vars as $var) {
        $value = isset($_SESSION[$var]) ? $_SESSION[$var] : 'Not Set';
        $status = isset($_SESSION[$var]) ? '✅ Present' : '❌ Missing';
        echo '<tr><td>' . $var . '</td><td>' . htmlspecialchars(print_r($value, true)) . '</td><td>' . $status . '</td></tr>';
    }
    echo '</table>';
    
    echo '</div>';

    // 2. Database connection test
    echo '<div class="debug-section">';
    echo '<h2>2. Database Connection Test</h2>';
    
    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->connect_error) {
            echo '<p class="error">❌ Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>';
        } else {
            echo '<p class="success">✅ Database connection successful</p>';
            echo '<p>Database: ' . htmlspecialchars($conn->get_server_info()) . '</p>';
        }
    } else {
        echo '<p class="error">❌ Database connection object not found</p>';
    }
    echo '</div>';

    // 3. File path checks
    echo '<div class="debug-section">';
    echo '<h2>3. File Path Analysis</h2>';
    
    $files_to_check = [
        '../../../server.php',
        '../../Controllers/HolidayProgramAdminController.php',
        '../../../config/config.php',
        '../../../login.php'
    ];
    
    echo '<table>';
    echo '<tr><th>File</th><th>Absolute Path</th><th>Exists</th><th>Readable</th></tr>';
    
    foreach ($files_to_check as $file) {
        $absolute_path = realpath($file);
        $exists = file_exists($file) ? '✅ Yes' : '❌ No';
        $readable = is_readable($file) ? '✅ Yes' : '❌ No';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file) . '</td>';
        echo '<td>' . htmlspecialchars($absolute_path ?: 'Path not resolved') . '</td>';
        echo '<td>' . $exists . '</td>';
        echo '<td>' . $readable . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';

    // 4. Authentication analysis
    echo '<div class="debug-section">';
    echo '<h2>4. Authentication System Analysis</h2>';
    
    echo '<h3>Current Authentication Status:</h3>';
    
    // Check main system login
    $main_system_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true;
    $main_system_is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    
    // Check holiday system login
    $holiday_system_logged_in = isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true;
    $holiday_system_is_admin = isset($_SESSION['holiday_is_admin']) && $_SESSION['holiday_is_admin'] === true;
    
    echo '<table>';
    echo '<tr><th>System</th><th>Logged In</th><th>Is Admin</th><th>Status</th></tr>';
    echo '<tr><td>Main System</td><td>' . ($main_system_logged_in ? '✅ Yes' : '❌ No') . '</td><td>' . ($main_system_is_admin ? '✅ Yes' : '❌ No') . '</td><td>' . ($main_system_logged_in && $main_system_is_admin ? '✅ Ready for Admin Dashboard' : '❌ Not Ready') . '</td></tr>';
    echo '<tr><td>Holiday System</td><td>' . ($holiday_system_logged_in ? '✅ Yes' : '❌ No') . '</td><td>' . ($holiday_system_is_admin ? '✅ Yes' : '❌ No') . '</td><td>' . ($holiday_system_logged_in && $holiday_system_is_admin ? '✅ Alternative Access' : '❌ Not Admin') . '</td></tr>';
    echo '</table>';
    
    echo '</div>';

    // 5. Generate fixes
    echo '<div class="debug-section">';
    echo '<h2>5. Potential Issues and Solutions</h2>';
    
    if (!$main_system_logged_in || !$main_system_is_admin) {
        echo '<div class="error">';
        echo '<h3>❌ Main Issue Detected:</h3>';
        echo '<p>The admin dashboard is checking for main system authentication ($_SESSION[\'loggedin\'] and $_SESSION[\'user_type\'] === \'admin\'), but you appear to be using the holiday program authentication system.</p>';
        
        echo '<h4>Solutions:</h4>';
        echo '<ol>';
        echo '<li><strong>Option 1 - Modify Admin Dashboard Authentication (Recommended):</strong><br>';
        echo 'Update the admin dashboard to accept holiday system admin authentication.</li>';
        
        echo '<li><strong>Option 2 - Login to Main System:</strong><br>';
        echo 'Login through the main system login page with admin credentials.</li>';
        
        echo '<li><strong>Option 3 - Sync Session Variables:</strong><br>';
        echo 'Create a bridge to sync holiday system admin login to main system variables.</li>';
        echo '</ol>';
        echo '</div>';
        
        // Check if user has holiday admin access
        if ($holiday_system_logged_in && $holiday_system_is_admin) {
            echo '<div class="warning">';
            echo '<h3>⚠️ You have Holiday System Admin Access</h3>';
            echo '<p>You are logged in as an admin in the holiday system, but the admin dashboard is looking for main system authentication.</p>';
            echo '</div>';
        }
    } else {
        echo '<div class="success">';
        echo '<h3>✅ Authentication Looks Good!</h3>';
        echo '<p>You appear to have the correct authentication. The issue might be elsewhere.</p>';
        echo '</div>';
    }
    
    // Check for file permission issues
    if (!file_exists('../../Controllers/HolidayProgramAdminController.php')) {
        echo '<div class="error">';
        echo '<h3>❌ Controller File Missing</h3>';
        echo '<p>The HolidayProgramAdminController.php file cannot be found. Check the file path and permissions.</p>';
        echo '</div>';
    }
    
    echo '</div>';

    // 6. Quick fix options
    if (isset($_POST['fix_session']) && $holiday_system_logged_in && $holiday_system_is_admin) {
        echo '<div class="debug-section">';
        echo '<h2>6. Applying Quick Fix...</h2>';
        
        // Set main system session variables based on holiday system
        $_SESSION['loggedin'] = true;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_id'] = $_SESSION['holiday_user_id'] ?? 1;
        $_SESSION['email'] = $_SESSION['holiday_email'] ?? '';
        $_SESSION['name'] = $_SESSION['holiday_name'] ?? '';
        $_SESSION['surname'] = $_SESSION['holiday_surname'] ?? '';
        
        echo '<p class="success">✅ Session variables have been synchronized! Try accessing the admin dashboard now.</p>';
        echo '<p><a href="holidayProgramAdminDashboard.php" class="fix-button">Go to Admin Dashboard</a></p>';
        echo '</div>';
    }
    
    if ($holiday_system_logged_in && $holiday_system_is_admin && !$main_system_logged_in) {
        echo '<div class="debug-section">';
        echo '<h2>6. Quick Fix Available</h2>';
        echo '<p class="info">Since you\'re logged in as an admin in the holiday system, we can sync the session variables:</p>';
        echo '<form method="post">';
        echo '<button type="submit" name="fix_session" class="fix-button">🔧 Sync Session Variables</button>';
        echo '</form>';
        echo '<p><small>This will set the main system session variables based on your holiday system login.</small></p>';
        echo '</div>';
    }

    // 7. Server info
    echo '<div class="debug-section">';
    echo '<h2>7. Server Environment</h2>';
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
    echo '<tr><td>Server Software</td><td>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</td></tr>';
    echo '<tr><td>Document Root</td><td>' . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . '</td></tr>';
    echo '<tr><td>Current Directory</td><td>' . getcwd() . '</td></tr>';
    echo '<tr><td>Script Name</td><td>' . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . '</td></tr>';
    echo '<tr><td>Session ID</td><td>' . session_id() . '</td></tr>';
    echo '<tr><td>Session Save Path</td><td>' . session_save_path() . '</td></tr>';
    echo '</table>';
    echo '</div>';

    // 8. Next steps
    echo '<div class="debug-section">';
    echo '<h2>8. Next Steps</h2>';
    echo '<ol>';
    echo '<li>If the quick fix worked, consider updating your admin dashboard authentication permanently</li>';
    echo '<li>If you\'re getting file not found errors, check your file paths and permissions</li>';
    echo '<li>If database errors occur, verify your database connection settings</li>';
    echo '<li><strong>Remember to delete this debug file once done!</strong></li>';
    echo '</ol>';
    
    echo '<h3>Recommended Permanent Fix:</h3>';
    echo '<p>Update the authentication check in holidayProgramAdminDashboard.php to:</p>';
    echo '<pre>';
    echo htmlspecialchars('// Check if user is logged in and is admin (support both systems)
$is_main_admin = isset($_SESSION[\'loggedin\']) && $_SESSION[\'loggedin\'] == true && $_SESSION[\'user_type\'] === \'admin\';
$is_holiday_admin = isset($_SESSION[\'holiday_logged_in\']) && $_SESSION[\'holiday_logged_in\'] === true && 
                    isset($_SESSION[\'holiday_is_admin\']) && $_SESSION[\'holiday_is_admin\'] === true;

if (!$is_main_admin && !$is_holiday_admin) {
    header("Location: ../../../login.php?redirect=app/Views/holidayPrograms/holidayProgramAdminDashboard.php");
    exit;
}');
    echo '</pre>';
    echo '</div>';
    ?>

    <div class="debug-section">
        <h2>🚨 Security Warning</h2>
        <p class="error"><strong>IMPORTANT:</strong> This debug script exposes sensitive information about your system. Delete it immediately after fixing your issue!</p>
        <p>To delete: Remove this file from your server or rename it to something else.</p>
    </div>

    <script>
        // Auto-refresh every 30 seconds if no user interaction
        let lastActivity = Date.now();
        
        document.addEventListener('click', () => lastActivity = Date.now());
        document.addEventListener('keypress', () => lastActivity = Date.now());
        
        setInterval(() => {
            if (Date.now() - lastActivity > 30000) {
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>