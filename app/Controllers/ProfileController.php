<?php
/**
 * ProfileController (Holiday Program Users)
 *
 * Handles authentication and profile management for holiday program attendees
 *
 * Phase 3 Week 3: Modern Routing System - Full Implementation
 * Created: November 15, 2025
 * Replaces: HolidayProgramProfileController.php (minimal implementation)
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/AttendeeService.php';
require_once __DIR__ . '/../Services/ProgramService.php';
require_once __DIR__ . '/../core/CSRF.php';

class ProfileController extends BaseController {
    private $attendeeService;
    private $programService;

    public function __construct() {
        global $conn;
        parent::__construct($conn);

        $this->attendeeService = new AttendeeService($conn);
        $this->programService = new ProgramService($conn);
    }

    /**
     * Show login form
     * GET /holiday-login
     */
    public function login() {
        try {
            // Redirect if already logged in
            if (isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true) {
                $intendedUrl = $_SESSION['intended_url'] ?? '/Sci-Bono_Clubhoue_LMS/holiday-dashboard';
                unset($_SESSION['intended_url']);
                header("Location: $intendedUrl");
                exit;
            }

            $this->view('programs.auth.login', [
                'csrfToken' => CSRF::getToken(),
                'error' => $_SESSION['login_error'] ?? null
            ]);

            // Clear error message after displaying
            unset($_SESSION['login_error']);

        } catch (Exception $e) {
            $this->logger->error("Failed to load login page", [
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load login page'
            ]);
        }
    }

    /**
     * Process login
     * POST /holiday-login
     */
    public function authenticate() {
        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validate input
            if (empty($email) || empty($password)) {
                throw new Exception("Email and password are required");
            }

            // Verify login credentials
            $attendee = $this->attendeeService->verifyLogin($email, $password);

            if (!$attendee) {
                throw new Exception("Invalid email or password");
            }

            // Check if email is verified
            if (!$attendee['email_verified']) {
                $_SESSION['unverified_email'] = $email;
                $_SESSION['login_error'] = "Please verify your email address before logging in. Check your inbox for the verification link.";
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            // Check if account is active
            if ($attendee['status'] !== 'active' && $attendee['status'] !== 'confirmed') {
                throw new Exception("Your account is not active. Please contact support.");
            }

            // Set session variables (holiday program namespace)
            $_SESSION['holiday_logged_in'] = true;
            $_SESSION['holiday_user_id'] = $attendee['id'];
            $_SESSION['holiday_email'] = $attendee['email'];
            $_SESSION['holiday_first_name'] = $attendee['first_name'];
            $_SESSION['holiday_last_name'] = $attendee['last_name'];
            $_SESSION['holiday_program_id'] = $attendee['program_id'];

            // Log successful login
            $this->logger->info("Holiday program user logged in", [
                'attendee_id' => $attendee['id'],
                'email' => $email
            ]);

            // Redirect to intended URL or dashboard
            $intendedUrl = $_SESSION['intended_url'] ?? '/Sci-Bono_Clubhoue_LMS/holiday-dashboard';
            unset($_SESSION['intended_url']);
            header("Location: $intendedUrl");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Login failed", [
                'email' => $_POST['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $_SESSION['login_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
            exit;
        }
    }

    /**
     * Logout user
     * POST /holiday-logout
     */
    public function logout() {
        try {
            // Log logout action
            if (isset($_SESSION['holiday_user_id'])) {
                $this->logger->info("Holiday program user logged out", [
                    'attendee_id' => $_SESSION['holiday_user_id'],
                    'email' => $_SESSION['holiday_email'] ?? 'unknown'
                ]);
            }

            // Clear holiday program session variables
            unset($_SESSION['holiday_logged_in']);
            unset($_SESSION['holiday_user_id']);
            unset($_SESSION['holiday_email']);
            unset($_SESSION['holiday_first_name']);
            unset($_SESSION['holiday_last_name']);
            unset($_SESSION['holiday_program_id']);

            $_SESSION['logout_success'] = "You have been successfully logged out.";
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Logout failed", [
                'error' => $e->getMessage()
            ]);

            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-dashboard");
            exit;
        }
    }

    /**
     * Show user dashboard
     * GET /holiday-dashboard
     */
    public function dashboard() {
        try {
            // Check authentication
            if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
                $_SESSION['intended_url'] = '/Sci-Bono_Clubhoue_LMS/holiday-dashboard';
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            $attendeeId = $_SESSION['holiday_user_id'];

            // Get attendee profile
            $profile = $this->attendeeService->getAttendeeProfile($attendeeId);

            if (!$profile) {
                throw new Exception("Profile not found");
            }

            // Get program details
            $program = $this->programService->getProgramById($profile['program_id']);

            // Get enrolled workshops
            $enrolledWorkshops = $profile['enrolled_workshops'] ?? [];

            $this->view('programs.dashboard.participant', [
                'profile' => $profile,
                'program' => $program,
                'enrolledWorkshops' => $enrolledWorkshops,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load dashboard", [
                'attendee_id' => $_SESSION['holiday_user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load dashboard'
            ]);
        }
    }

    /**
     * Verify email address via token
     * GET /holiday-verify-email/{token}
     */
    public function verifyEmail($token) {
        try {
            if (empty($token)) {
                throw new Exception("Verification token is required");
            }

            // Verify email with token
            $result = $this->attendeeService->verifyEmailToken($token);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Log successful verification
            $this->logger->info("Email verified successfully", [
                'attendee_id' => $result['attendee_id'],
                'email' => $result['email']
            ]);

            // Set session for password creation
            $_SESSION['verified_attendee_id'] = $result['attendee_id'];
            $_SESSION['verified_email'] = $result['email'];
            $_SESSION['verification_success'] = "Email verified successfully! Please create your password.";

            // Redirect to password creation
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-create-password");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Email verification failed", [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            $this->view('programs.profile.verify-email', [
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show password creation form
     * GET /holiday-create-password
     */
    public function createPassword() {
        try {
            // Check if email was verified
            if (!isset($_SESSION['verified_attendee_id']) || !isset($_SESSION['verified_email'])) {
                $_SESSION['login_error'] = "Please verify your email address first.";
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            $this->view('programs.profile.create-password', [
                'email' => $_SESSION['verified_email'],
                'csrfToken' => CSRF::getToken(),
                'success' => $_SESSION['verification_success'] ?? null
            ]);

            // Clear success message after displaying
            unset($_SESSION['verification_success']);

        } catch (Exception $e) {
            $this->logger->error("Failed to load password creation form", [
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load password creation form'
            ]);
        }
    }

    /**
     * Store new password
     * POST /holiday-create-password
     */
    public function storePassword() {
        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            // Check if email was verified
            if (!isset($_SESSION['verified_attendee_id']) || !isset($_SESSION['verified_email'])) {
                throw new Exception("Email verification required");
            }

            $attendeeId = $_SESSION['verified_attendee_id'];
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate passwords
            if (empty($password) || empty($confirmPassword)) {
                throw new Exception("Password and confirmation are required");
            }

            if ($password !== $confirmPassword) {
                throw new Exception("Passwords do not match");
            }

            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            // Create password
            $result = $this->attendeeService->createPassword($attendeeId, $password);

            if (!$result) {
                throw new Exception("Failed to create password");
            }

            // Log password creation
            $this->logger->info("Password created successfully", [
                'attendee_id' => $attendeeId,
                'email' => $_SESSION['verified_email']
            ]);

            // Clear verification session variables
            $email = $_SESSION['verified_email'];
            unset($_SESSION['verified_attendee_id']);
            unset($_SESSION['verified_email']);

            // Set success message and redirect to login
            $_SESSION['login_success'] = "Password created successfully! You can now log in.";
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Password creation failed", [
                'attendee_id' => $_SESSION['verified_attendee_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $_SESSION['password_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-create-password");
            exit;
        }
    }

    /**
     * Show profile view
     * GET /holiday-profile
     */
    public function show() {
        try {
            // Check authentication
            if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
                $_SESSION['intended_url'] = '/Sci-Bono_Clubhoue_LMS/holiday-profile';
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            $attendeeId = $_SESSION['holiday_user_id'];

            // Get full profile
            $profile = $this->attendeeService->getAttendeeProfile($attendeeId);

            if (!$profile) {
                throw new Exception("Profile not found");
            }

            // Get program details
            $program = $this->programService->getProgramById($profile['program_id']);

            $this->view('programs.profile.index', [
                'profile' => $profile,
                'program' => $program,
                'success' => $_SESSION['profile_success'] ?? null,
                'error' => $_SESSION['profile_error'] ?? null
            ]);

            // Clear messages after displaying
            unset($_SESSION['profile_success']);
            unset($_SESSION['profile_error']);

        } catch (Exception $e) {
            $this->logger->error("Failed to load profile", [
                'attendee_id' => $_SESSION['holiday_user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load profile'
            ]);
        }
    }

    /**
     * Show profile edit form
     * GET /holiday-profile/edit
     */
    public function edit() {
        try {
            // Check authentication
            if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
                $_SESSION['intended_url'] = '/Sci-Bono_Clubhoue_LMS/holiday-profile/edit';
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            $attendeeId = $_SESSION['holiday_user_id'];

            // Get full profile
            $profile = $this->attendeeService->getAttendeeProfile($attendeeId);

            if (!$profile) {
                throw new Exception("Profile not found");
            }

            // Get program details
            $program = $this->programService->getProgramById($profile['program_id']);

            $this->view('programs.profile.edit', [
                'profile' => $profile,
                'program' => $program,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load profile edit form", [
                'attendee_id' => $_SESSION['holiday_user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load profile edit form'
            ]);
        }
    }

    /**
     * Update profile
     * POST /holiday-profile/edit
     */
    public function update() {
        try {
            // Check authentication
            if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            $attendeeId = $_SESSION['holiday_user_id'];

            // Prepare update data (only allow certain fields to be updated)
            $updateData = [
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'province' => $_POST['province'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_relationship' => $_POST['guardian_relationship'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_email' => $_POST['guardian_email'] ?? '',
                'emergency_contact_name' => $_POST['emergency_contact_name'] ?? '',
                'emergency_contact_relationship' => $_POST['emergency_contact_relationship'] ?? '',
                'emergency_contact_phone' => $_POST['emergency_contact_phone'] ?? '',
                'medical_conditions' => $_POST['medical_conditions'] ?? '',
                'allergies' => $_POST['allergies'] ?? '',
                'dietary_restrictions' => $_POST['dietary_restrictions'] ?? ''
            ];

            // Update profile
            $result = $this->attendeeService->updateProfile($attendeeId, $updateData);

            if (!$result) {
                throw new Exception("Failed to update profile");
            }

            // Log profile update
            $this->logger->info("Profile updated", [
                'attendee_id' => $attendeeId,
                'email' => $_SESSION['holiday_email']
            ]);

            $_SESSION['profile_success'] = "Profile updated successfully!";
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-profile");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Profile update failed", [
                'attendee_id' => $_SESSION['holiday_user_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $_SESSION['profile_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/holiday-profile/edit");
            exit;
        }
    }
}
