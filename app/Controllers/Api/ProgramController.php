<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use HolidayProgramModel;
use HolidayProgramAdminModel;

/**
 * Public Holiday Program API Controller
 *
 * Handles public-facing program browsing and user registration
 * All endpoints require authentication
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 5 Day 4 (January 11, 2026)
 */
class ProgramController extends BaseController {

    private $programModel;
    private $adminModel;

    public function __construct() {
        parent::__construct();

        // Load models (using require for non-namespaced models)
        require_once __DIR__ . '/../../Models/HolidayProgramModel.php';
        require_once __DIR__ . '/../../Models/HolidayProgramAdminModel.php';

        $this->programModel = new \HolidayProgramModel($this->conn);
        $this->adminModel = new \HolidayProgramAdminModel($this->conn);
    }

    /**
     * GET /api/v1/programs
     * Get list of available holiday programs
     *
     * Shows only programs with registration_open = 1
     * Optional filtering by status, dates, etc.
     *
     * Query Parameters:
     * - status (string): Filter by program status (upcoming, ongoing, past)
     * - year (int): Filter by year
     * - limit (int): Results per page (default: 20, max: 100)
     * - offset (int): Pagination offset (default: 0)
     *
     * @return JSON response with programs list
     */
    public function index() {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Get query parameters
            $status = $_GET['status'] ?? null;
            $year = isset($_GET['year']) ? intval($_GET['year']) : null;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            // Validate limits
            if ($limit < 1) $limit = 20;
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;

            // Get all programs (we'll filter)
            $allPrograms = $this->adminModel->getAllPrograms();

            // Filter programs based on criteria
            $programs = [];
            $currentDate = date('Y-m-d');

            foreach ($allPrograms as $program) {
                // Only show programs with registration open (public browsing)
                if (!$program['registration_open']) {
                    continue;
                }

                // Filter by year if specified
                if ($year && date('Y', strtotime($program['start_date'])) != $year) {
                    continue;
                }

                // Filter by status if specified
                if ($status) {
                    $programStatus = $this->getProgramStatus($program, $currentDate);
                    if ($programStatus !== $status) {
                        continue;
                    }
                }

                // Add computed fields
                $program['status'] = $this->getProgramStatus($program, $currentDate);
                $program['days_until_start'] = $this->getDaysUntilStart($program['start_date'], $currentDate);
                $program['is_full'] = $this->isProgramFull($program['id'], $program['max_participants']);

                // Check if user is already registered
                $program['is_registered'] = $this->isUserRegistered($program['id'], $userId);

                // Add availability info
                $capacityInfo = $this->programModel->checkProgramCapacity($program['id']);
                $program['capacity_info'] = [
                    'current' => $program['total_registrations'],
                    'max' => $program['max_participants'],
                    'available' => max($program['max_participants'] - $program['total_registrations'], 0),
                    'percentage_full' => $program['max_participants'] > 0
                        ? min(($program['total_registrations'] / $program['max_participants']) * 100, 100)
                        : 0
                ];

                $programs[] = $program;
            }

            // Apply pagination
            $totalCount = count($programs);
            $programs = array_slice($programs, $offset, $limit);

            return $this->jsonSuccess([
                'programs' => $programs,
                'pagination' => [
                    'total' => $totalCount,
                    'count' => count($programs),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ],
                'filters_applied' => array_filter([
                    'status' => $status,
                    'year' => $year
                ])
            ], 'Holiday programs retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve holiday programs', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving programs', null, 500);
        }
    }

    /**
     * GET /api/v1/programs/{id}
     * Get detailed information for a specific program
     *
     * Includes workshops, schedule, requirements, FAQs, capacity status
     *
     * @param int $id Program ID
     * @return JSON response with program details
     */
    public function show($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Get program details
            $program = $this->programModel->getProgramById($id);

            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Check if registration is open
            if (!$program['registration_open']) {
                return $this->jsonError('This program is not currently open for registration', [
                    'registration_open' => false
                ], 403);
            }

            // Add computed fields
            $currentDate = date('Y-m-d');
            $program['status'] = $this->getProgramStatus($program, $currentDate);
            $program['days_until_start'] = $this->getDaysUntilStart($program['start_date'], $currentDate);

            // Check user registration status
            $program['is_registered'] = $this->isUserRegistered($id, $userId);

            if ($program['is_registered']) {
                // Get user's registration details
                $program['user_registration'] = $this->getUserRegistration($id, $userId);
            }

            // Get capacity info
            $capacityInfo = $this->programModel->checkProgramCapacity($id);
            $program['capacity_info'] = [
                'member_count' => $capacityInfo['member_count'],
                'member_capacity' => $capacityInfo['member_capacity'],
                'member_available' => max($capacityInfo['member_capacity'] - $capacityInfo['member_count'], 0),
                'member_full' => $capacityInfo['member_full'],
                'mentor_count' => $capacityInfo['mentor_count'],
                'mentor_capacity' => $capacityInfo['mentor_capacity'],
                'mentor_available' => max($capacityInfo['mentor_capacity'] - $capacityInfo['mentor_count'], 0),
                'mentor_full' => $capacityInfo['mentor_full']
            ];

            return $this->jsonSuccess([
                'program' => $program
            ], 'Program details retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve program details', [
                'program_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving program', null, 500);
        }
    }

    /**
     * POST /api/v1/programs/{id}/register
     * Register authenticated user for a holiday program
     *
     * Required POST parameters:
     * - registration_type (string): 'member' or 'mentor'
     * - selected_workshops (array): Array of workshop IDs
     * - emergency_contact_name (string)
     * - emergency_contact_phone (string)
     * - emergency_contact_relationship (string)
     * - medical_info (string, optional)
     * - dietary_requirements (string, optional)
     *
     * @param int $id Program ID
     * @return JSON response with registration details
     */
    public function register($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate CSRF token
            if (!\\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Get program details
            $program = $this->adminModel->getProgramById($id);

            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Check if registration is open
            if (!$program['registration_open']) {
                return $this->jsonError('Registration is closed for this program', null, 403);
            }

            // Check if already registered
            if ($this->isUserRegistered($id, $userId)) {
                return $this->jsonError('You are already registered for this program', null, 400);
            }

            // Get POST data
            $registrationType = $_POST['registration_type'] ?? 'member';
            $selectedWorkshops = isset($_POST['selected_workshops']) ? json_decode($_POST['selected_workshops'], true) : [];
            $emergencyContactName = $_POST['emergency_contact_name'] ?? null;
            $emergencyContactPhone = $_POST['emergency_contact_phone'] ?? null;
            $emergencyContactRelationship = $_POST['emergency_contact_relationship'] ?? null;
            $medicalInfo = $_POST['medical_info'] ?? null;
            $dietaryRequirements = $_POST['dietary_requirements'] ?? null;

            // Validate required fields
            if (!in_array($registrationType, ['member', 'mentor'])) {
                return $this->jsonError('Invalid registration type', null, 400);
            }

            if (!$emergencyContactName || !$emergencyContactPhone || !$emergencyContactRelationship) {
                return $this->jsonError('Emergency contact information is required', null, 400);
            }

            if (empty($selectedWorkshops) || !is_array($selectedWorkshops)) {
                return $this->jsonError('You must select at least one workshop', null, 400);
            }

            // Check capacity
            $capacityInfo = $this->programModel->checkProgramCapacity($id);
            $isMentorRegistration = ($registrationType === 'mentor');

            if ($isMentorRegistration && $capacityInfo['mentor_full']) {
                return $this->jsonError('Mentor capacity is full for this program', null, 400);
            }

            if (!$isMentorRegistration && $capacityInfo['member_full']) {
                return $this->jsonError('Member capacity is full for this program', null, 400);
            }

            // Get user details from users table
            $userSql = "SELECT * FROM users WHERE id = ?";
            $userStmt = $this->conn->prepare($userSql);
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $user = $userStmt->get_result()->fetch_assoc();

            if (!$user) {
                return $this->jsonError('User not found', null, 404);
            }

            // Create registration
            $sql = "INSERT INTO holiday_program_attendees
                    (program_id, user_id, name, surname, email, contact_number,
                     date_of_birth, gender, grade, school,
                     emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
                     medical_info, dietary_requirements,
                     mentor_registration, registration_status, registered_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

            $stmt = $this->conn->prepare($sql);
            $mentorFlag = $isMentorRegistration ? 1 : 0;

            $stmt->bind_param(
                "iisssssssssssssi",
                $id,
                $userId,
                $user['name'],
                $user['surname'],
                $user['email'],
                $user['contact_number'] ?? '',
                $user['date_of_birth'] ?? null,
                $user['gender'] ?? null,
                $user['grade'] ?? null,
                $user['school'] ?? null,
                $emergencyContactName,
                $emergencyContactPhone,
                $emergencyContactRelationship,
                $medicalInfo,
                $dietaryRequirements,
                $mentorFlag
            );

            if ($stmt->execute()) {
                $attendeeId = $stmt->insert_id;

                // Register for selected workshops
                $workshopSuccess = $this->registerWorkshops($attendeeId, $selectedWorkshops);

                if (!$workshopSuccess) {
                    $this->logger->log('warning', 'Workshop registration partially failed', [
                        'user_id' => $userId,
                        'program_id' => $id,
                        'attendee_id' => $attendeeId
                    ]);
                }

                // Get the created registration
                $registration = $this->getUserRegistration($id, $userId);

                $this->logger->log('info', 'User registered for holiday program', [
                    'user_id' => $userId,
                    'program_id' => $id,
                    'attendee_id' => $attendeeId,
                    'registration_type' => $registrationType,
                    'workshops_count' => count($selectedWorkshops)
                ]);

                return $this->jsonSuccess([
                    'registration_id' => $attendeeId,
                    'registration' => $registration,
                    'program' => [
                        'id' => $program['id'],
                        'title' => $program['title'],
                        'dates' => $program['dates']
                    ]
                ], 'Successfully registered for program', 201);
            }

            return $this->jsonError('Failed to register for program', null, 500);

        } catch (\Exception $e) {
            $this->logger->log('error', 'Program registration failed', [
                'program_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during registration', null, 500);
        }
    }

    /**
     * GET /api/v1/programs/{id}/workshops
     * Get workshops for a specific program with enrollment status
     *
     * @param int $id Program ID
     * @return JSON response with workshops list
     */
    public function workshops($id) {
        try {
            // Require authentication
            $this->requireAuth();

            $userId = $_SESSION['user_id'];

            // Validate program exists
            $program = $this->adminModel->getProgramById($id);

            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Get workshops
            $sql = "SELECT w.*,
                    (SELECT COUNT(*) FROM holiday_workshop_enrollment e WHERE e.workshop_id = w.id) as enrolled_count
                    FROM holiday_program_workshops w
                    WHERE w.program_id = ?
                    ORDER BY w.id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $workshops = [];
            while ($workshop = $result->fetch_assoc()) {
                // Calculate availability
                $workshop['available_spots'] = max($workshop['max_participants'] - $workshop['enrolled_count'], 0);
                $workshop['is_full'] = $workshop['enrolled_count'] >= $workshop['max_participants'];
                $workshop['enrollment_percentage'] = $workshop['max_participants'] > 0
                    ? min(($workshop['enrolled_count'] / $workshop['max_participants']) * 100, 100)
                    : 0;

                // Check if user is enrolled in this workshop
                $workshop['is_enrolled'] = false;
                if ($this->isUserRegistered($id, $userId)) {
                    $attendeeId = $this->getUserAttendeeId($id, $userId);
                    if ($attendeeId) {
                        $enrollSql = "SELECT COUNT(*) as count FROM holiday_workshop_enrollment
                                     WHERE attendee_id = ? AND workshop_id = ?";
                        $enrollStmt = $this->conn->prepare($enrollSql);
                        $enrollStmt->bind_param("ii", $attendeeId, $workshop['id']);
                        $enrollStmt->execute();
                        $enrollResult = $enrollStmt->get_result()->fetch_assoc();
                        $workshop['is_enrolled'] = $enrollResult['count'] > 0;
                    }
                }

                $workshops[] = $workshop;
            }

            return $this->jsonSuccess([
                'program' => [
                    'id' => $program['id'],
                    'title' => $program['title']
                ],
                'workshops' => $workshops,
                'total_workshops' => count($workshops)
            ], 'Workshops retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to retrieve workshops', [
                'program_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error while retrieving workshops', null, 500);
        }
    }

    /**
     * Helper: Get program status based on dates
     *
     * @param array $program Program data
     * @param string $currentDate Current date (Y-m-d)
     * @return string Status (upcoming, ongoing, past)
     */
    private function getProgramStatus($program, $currentDate) {
        if ($currentDate < $program['start_date']) {
            return 'upcoming';
        } elseif ($currentDate >= $program['start_date'] && $currentDate <= $program['end_date']) {
            return 'ongoing';
        } else {
            return 'past';
        }
    }

    /**
     * Helper: Get days until program starts
     *
     * @param string $startDate Program start date
     * @param string $currentDate Current date
     * @return int Days until start (negative if past)
     */
    private function getDaysUntilStart($startDate, $currentDate) {
        $start = new \DateTime($startDate);
        $current = new \DateTime($currentDate);
        $diff = $current->diff($start);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Helper: Check if program is full
     *
     * @param int $programId Program ID
     * @param int $maxParticipants Max participants
     * @return bool True if full
     */
    private function isProgramFull($programId, $maxParticipants) {
        $sql = "SELECT COUNT(*) as count FROM holiday_program_attendees
                WHERE program_id = ? AND mentor_registration = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] >= $maxParticipants;
    }

    /**
     * Helper: Check if user is registered for program
     *
     * @param int $programId Program ID
     * @param int $userId User ID
     * @return bool True if registered
     */
    private function isUserRegistered($programId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM holiday_program_attendees
                WHERE program_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $programId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] > 0;
    }

    /**
     * Helper: Get user's registration details
     *
     * @param int $programId Program ID
     * @param int $userId User ID
     * @return array|null Registration data or null
     */
    private function getUserRegistration($programId, $userId) {
        $sql = "SELECT * FROM holiday_program_attendees
                WHERE program_id = ? AND user_id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $programId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Helper: Get user's attendee ID for a program
     *
     * @param int $programId Program ID
     * @param int $userId User ID
     * @return int|null Attendee ID or null
     */
    private function getUserAttendeeId($programId, $userId) {
        $registration = $this->getUserRegistration($programId, $userId);
        return $registration ? $registration['id'] : null;
    }

    /**
     * Helper: Register user for selected workshops
     *
     * @param int $attendeeId Attendee ID
     * @param array $workshopIds Array of workshop IDs
     * @return bool Success status
     */
    private function registerWorkshops($attendeeId, $workshopIds) {
        $success = true;

        foreach ($workshopIds as $workshopId) {
            $sql = "INSERT INTO holiday_workshop_enrollment (attendee_id, workshop_id, enrolled_at)
                    VALUES (?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $attendeeId, $workshopId);

            if (!$stmt->execute()) {
                $success = false;
                $this->logger->log('error', 'Failed to enroll in workshop', [
                    'attendee_id' => $attendeeId,
                    'workshop_id' => $workshopId
                ]);
            }
        }

        return $success;
    }

    /**
     * Helper method to require authentication
     *
     * @throws \Exception if not authenticated
     */
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            throw new \Exception('Authentication required');
        }
    }
}
