<?php
session_start();
require_once '../../../server.php';
require_once '../../Controllers/HolidayProgramProfileController.php';

$profileController = new HolidayProgramProfileController($conn);
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $result = $profileController->createPassword($password, $confirmPassword);
    
    if ($result['success']) {
        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit();
        }
    } else {
        $error = $result['message'];
        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password - Holiday Program Profile</title>
    <link rel="stylesheet" href="../../../public/assets/css/holiday-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="verify-container">
        <div class="step-icon">
            <i class="fas fa-key"></i>
        </div>
        
        <h1>Create Your Password</h1>
        <p>Set up a secure password to access your profile and dashboard.</p>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="password-requirements">
            <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
            <ul>
                <li>At least 8 characters long</li>
                <li>Mix of letters and numbers recommended</li>
                <li>Choose something you'll remember</li>
            </ul>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter password (min 8 characters)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Confirm your password">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-check"></i>
                Create Password & Access Profile
            </button>
        </form>
    </div>
</body>
</html>