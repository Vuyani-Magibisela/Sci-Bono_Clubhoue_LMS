<?php
/**
 * ⚠️ DEPRECATED - This file is deprecated as of Phase 4 Week 3 Day 4
 *
 * This procedural login controller has been deprecated in favor of the modernized
 * HolidayProgramProfileController which extends BaseController.
 *
 * Migration Path:
 * - Use: HolidayProgramProfileController->verifyEmail() for email verification
 * - Use: HolidayProgramProfileController->createPassword() for password creation
 * - Use: HolidayProgramProfileController->index() for profile access
 *
 * This file is kept for backward compatibility only and will be removed
 * in a future release. Please update your code to use the new controller.
 *
 * @deprecated Phase 4 Week 3 Day 4
 * @see HolidayProgramProfileController
 */

// Log deprecation warning
if (function_exists('error_log')) {
    error_log(
        '[DEPRECATED] holidayProgramLoginC.php is deprecated. ' .
        'Use HolidayProgramProfileController instead. ' .
        'Called from: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
        ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    );
}

session_start();

// Fix paths using __DIR__
require_once __DIR__ . '/../Models/holidayProgramLoginM.php';
require_once __DIR__ . '/../../server.php';

class HolidayProgramLoginController {
    private $conn;
    private $userModel;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->userModel = new UserModel($conn);
    }

    public function handleLogin() {
        $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
        $needsPassword = false;
        $registeredEmail = $_SESSION['email'] ?? '';

        if (!$isLoggedIn && !empty($registeredEmail)) {
            // $needsPassword = $this->userModel->userNeedsPassword($registeredEmail);
        }

        // Process password creation
        $passwordSuccess = false;
        $passwordError = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_password'])) {
            // Password creation logic here
        }

        return [
            'isLoggedIn' => $isLoggedIn,
            'needsPassword' => $needsPassword,
            'passwordSuccess' => $passwordSuccess,
            'passwordError' => $passwordError
        ];
    }
}

// Initialize the controller
$controller = new HolidayProgramLoginController($conn);
$loginData = $controller->handleLogin();

// Export variables for use in views
extract($loginData);
