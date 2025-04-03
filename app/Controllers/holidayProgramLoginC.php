<?php
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
            $needsPassword = $this->userModel->userNeedsPassword($registeredEmail);
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