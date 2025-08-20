<?php
session_start();
require_once '../../../server.php';
require_once '../../Controllers/HolidayProgramProfileController.php';

$profileController = new HolidayProgramProfileController($conn);
$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $result = $profileController->verifyEmail($email);
    
    if ($result['success']) {
        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit();
        }
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Holiday Program Profile</title>
    <link rel="stylesheet" href="../../../public/assets/css/holiday-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="verify-container">
        <div class="step-icon">
            <i class="fas fa-envelope"></i>
        </div>
        
        <h1>Verify Your Email</h1>
        <p>Enter the email address you used to register for the holiday program to access your profile.</p>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Enter your email address"
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-check"></i>
                Verify Email
            </button>
        </form>
        
        <div class="back-link">
            <a href="holidayProgramIndex.php">
                <i class="fas fa-arrow-left"></i>
                Back to Holiday Programs
            </a>
        </div>
    </div>
</body>
</html>