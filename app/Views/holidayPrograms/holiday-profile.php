<?php
session_start();
require_once '../../../server.php';
require_once '../../Controllers/HolidayProgramProfileController.php';

// Check if user is logged in
if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
    header('Location: holiday-profile-verify-email.php');
    exit();
}

$profileController = new HolidayProgramProfileController($conn);
$userId = $_SESSION['holiday_user_id'];
$isAdmin = isset($_SESSION['holiday_is_admin']) && $_SESSION['holiday_is_admin'];
$isMentor = isset($_SESSION['holiday_is_mentor']) && $_SESSION['holiday_is_mentor'];

// Check if admin is viewing someone else's profile
$viewingUserId = isset($_GET['user_id']) && $isAdmin ? intval($_GET['user_id']) : $userId;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $result = $profileController->updateProfile($viewingUserId, $_POST, $isAdmin);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get profile data
$userData = $profileController->getProfile($viewingUserId, $isAdmin);

if (!$userData) {
    die("Profile not found or access denied.");
}

// Helper functions
function getStatusDisplay($userData) {
    if ($userData['mentor_registration']) {
        return ucfirst($userData['mentor_status'] ?? 'Pending') . ' Mentor';
    } else {
        return ucfirst($userData['registration_status'] ?? 'pending') . ' Attendee';
    }
}

function isFieldReadonly($fieldName, $isAdmin) {
    $adminOnlyFields = [
        'registration_status', 'status', 'mentor_status', 
        'is_clubhouse_member', 'additional_notes'
    ];
    
    return in_array($fieldName, $adminOnlyFields) && !$isAdmin;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/holiday-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-name">
                <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
            </div>
            <div class="profile-status">
                <?php echo htmlspecialchars(getStatusDisplay($userData)); ?>
            </div>
            <?php if ($userData['program_title']): ?>
                <div class="profile-program">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo htmlspecialchars($userData['program_title'] . ' - ' . $userData['program_dates']); ?>
                </div>
            <?php endif; ?>
            <div class="header-actions">
                <a href="holiday-dashboard.php" class="btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <?php if ($isAdmin && $viewingUserId !== $userId): ?>
                    <a href="holidayProgramAdminDashboard.php" class="btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Admin
                    </a>
                <?php endif; ?>
                <a href="holiday-logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="profile-content">
            <!-- Alert Messages -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['welcome'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Welcome! Your profile has been set up successfully. You can now edit your information below.
                </div>
            <?php endif; ?>

            <!-- Include Profile Form Components -->
            <?php include 'components/profile-sections.php'; ?>
        </div>
    </div>

    <script src="../../../public/assets/js/holiday-profile.js"></script>
</body>
</html>