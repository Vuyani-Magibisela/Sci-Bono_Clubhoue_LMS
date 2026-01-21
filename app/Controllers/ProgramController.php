<?php
/**
 * ProgramController (Public)
 *
 * Handles public holiday program browsing and registration
 *
 * Phase 3 Week 3: Modern Routing System - Full Implementation
 * Created: November 15, 2025
 * Replaces: HolidayProgramController.php (59 lines minimal implementation)
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/ProgramService.php';
require_once __DIR__ . '/../Services/AttendeeService.php';
require_once __DIR__ . '/../Repositories/WorkshopRepository.php';
require_once __DIR__ . '/../core/CSRF.php';

class ProgramController extends BaseController {
    private $programService;
    private $attendeeService;
    private $workshopRepo;

    public function __construct() {
        global $conn;
        parent::__construct($conn);

        $this->programService = new ProgramService($conn);
        $this->attendeeService = new AttendeeService($conn);
        $this->workshopRepo = new WorkshopRepository($conn);
    }

    /**
     * List all available programs
     * GET /programs
     */
    public function index() {
        try {
            // Get only open programs for public view
            $programs = $this->programService->getAllPrograms('open_registration');

            // Check if user is logged in (holiday program session)
            $isLoggedIn = isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true;

            $this->view('programs.index', [
                'programs' => $programs,
                'isLoggedIn' => $isLoggedIn,
                'userEmail' => $_SESSION['holiday_email'] ?? null
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load programs list", [
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load programs'
            ]);
        }
    }

    /**
     * Show program details
     * GET /programs/{id}
     */
    public function show($id) {
        try {
            // Get program with full details
            $program = $this->programService->getProgramById($id);

            // Get capacity information
            $capacity = $this->programService->getCapacityInfo($id);

            // Check if user can register
            $canRegister = $this->programService->canAcceptRegistrations($id, false);

            // Check if user is already registered
            $userIsRegistered = false;
            $userRegistration = null;
            if (isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true) {
                $userEmail = $_SESSION['holiday_email'];
                $attendee = $this->attendeeService->getAttendeeByEmail($userEmail);

                if ($attendee && $attendee['program_id'] == $id) {
                    $userIsRegistered = true;
                    $userRegistration = $attendee;
                }
            }

            $this->view('programs.show', [
                'program' => $program,
                'capacity' => $capacity,
                'canRegister' => $canRegister,
                'userIsRegistered' => $userIsRegistered,
                'userRegistration' => $userRegistration,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load program details", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Program not found'
            ]);
        }
    }

    /**
     * Handle registration submission
     * POST /programs/{id}/register
     */
    public function register($id) {
        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            // Check if program can accept registrations
            $isMentor = isset($_POST['mentor_registration']) && $_POST['mentor_registration'] == '1';
            $canRegister = $this->programService->canAcceptRegistrations($id, $isMentor);

            if (!$canRegister['can_register']) {
                throw new Exception($canRegister['reason']);
            }

            // Prepare registration data
            $registrationData = [
                'program_id' => $id,
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'date_of_birth' => $_POST['date_of_birth'] ?? '',
                'gender' => $_POST['gender'] ?? '',
                'school' => $_POST['school'] ?? '',
                'grade' => $_POST['grade'] ?? '',
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
                'why_interested' => $_POST['why_interested'] ?? '',
                'experience_level' => $_POST['experience_level'] ?? '',
                'medical_conditions' => $_POST['medical_conditions'] ?? '',
                'allergies' => $_POST['allergies'] ?? '',
                'dietary_restrictions' => $_POST['dietary_restrictions'] ?? '',
                'mentor_registration' => $isMentor ? 1 : 0,
                'workshop_preference' => $_POST['workshop_preference'] ?? null
            ];

            // Mentor-specific fields
            if ($isMentor) {
                $registrationData['mentor_experience'] = $_POST['mentor_experience'] ?? '';
                $registrationData['mentor_availability'] = $_POST['mentor_availability'] ?? '';
                $registrationData['mentor_workshop_preference'] = $_POST['mentor_workshop_preference'] ?? null;
            }

            // Register attendee
            $attendeeId = $this->attendeeService->registerAttendee($registrationData);

            // Generate email verification token
            $token = $this->attendeeService->generateVerificationToken($attendeeId);

            // Log registration
            $this->logger->info("New registration", [
                'attendee_id' => $attendeeId,
                'program_id' => $id,
                'email' => $registrationData['email'],
                'is_mentor' => $isMentor
            ]);

            // Check and auto-close if capacity reached
            $this->programService->checkAndCloseIfFull($id);

            // Redirect to confirmation page with token
            $_SESSION['registration_success'] = true;
            $_SESSION['registration_email'] = $registrationData['email'];
            $_SESSION['verification_token'] = $token;

            header("Location: /Sci-Bono_Clubhoue_LMS/programs/{$id}/registration-confirmation");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Registration failed", [
                'program_id' => $id,
                'error' => $e->getMessage(),
                'post_data' => $_POST
            ]);

            $_SESSION['registration_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/programs/{$id}#register");
            exit;
        }
    }

    /**
     * Show available workshops for a program
     * GET /programs/{id}/workshops
     */
    public function workshops($id) {
        try {
            // Get program
            $program = $this->programService->getProgramById($id);

            // Get workshops with capacity info
            $workshops = $this->workshopRepo->getWorkshopsWithData($id);

            // Check if user is registered
            $userIsRegistered = false;
            $userWorkshops = [];
            if (isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true) {
                $userEmail = $_SESSION['holiday_email'];
                $attendee = $this->attendeeService->getAttendeeByEmail($userEmail);

                if ($attendee && $attendee['program_id'] == $id) {
                    $userIsRegistered = true;
                    $userWorkshops = $this->attendeeService->getAttendeeProfile($attendee['id'])['enrolled_workshops'] ?? [];
                }
            }

            $this->view('programs.workshop-selection', [
                'program' => $program,
                'workshops' => $workshops,
                'userIsRegistered' => $userIsRegistered,
                'userWorkshops' => $userWorkshops,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load workshops", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Program not found'
            ]);
        }
    }

    /**
     * Show user's registered programs
     * GET /programs/my-programs
     */
    public function myPrograms() {
        try {
            // Check if user is logged in
            if (!isset($_SESSION['holiday_logged_in']) || $_SESSION['holiday_logged_in'] !== true) {
                $_SESSION['intended_url'] = '/Sci-Bono_Clubhoue_LMS/programs/my-programs';
                header("Location: /Sci-Bono_Clubhoue_LMS/holiday-login");
                exit;
            }

            $userEmail = $_SESSION['holiday_email'];
            $attendee = $this->attendeeService->getAttendeeByEmail($userEmail);

            if (!$attendee) {
                throw new Exception("Attendee record not found");
            }

            // Get full attendee profile with program details
            $profile = $this->attendeeService->getAttendeeProfile($attendee['id']);

            // Get program details
            $program = $this->programService->getProgramById($attendee['program_id']);

            // Get enrolled workshops
            $enrolledWorkshops = $profile['enrolled_workshops'] ?? [];

            $this->view('programs.my-programs', [
                'attendee' => $attendee,
                'profile' => $profile,
                'program' => $program,
                'enrolledWorkshops' => $enrolledWorkshops
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load user programs", [
                'user_email' => $_SESSION['holiday_email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Registration not found'
            ]);
        }
    }

    /**
     * Show registration confirmation page
     * GET /programs/{id}/registration-confirmation
     */
    public function registrationConfirmation($id) {
        try {
            // Check if registration was successful
            if (!isset($_SESSION['registration_success'])) {
                header("Location: /Sci-Bono_Clubhoue_LMS/programs/{$id}");
                exit;
            }

            $program = $this->programService->getProgramById($id);
            $email = $_SESSION['registration_email'] ?? null;
            $token = $_SESSION['verification_token'] ?? null;

            // Clear session flags
            unset($_SESSION['registration_success']);
            unset($_SESSION['registration_email']);
            unset($_SESSION['verification_token']);

            $this->view('programs.registration-confirmation', [
                'program' => $program,
                'email' => $email,
                'verificationToken' => $token
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load confirmation page", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            header("Location: /Sci-Bono_Clubhoue_LMS/programs");
            exit;
        }
    }
}
