<?php
/**
 * Api\Admin\ProgramController
 *
 * Handles AJAX API operations for holiday program management
 *
 * Phase 5 Week 4: Admin Resource Management APIs - Program Management
 * Created: January 10, 2026
 */

namespace Api\Admin;

require_once __DIR__ . '/../../BaseController.php';
require_once __DIR__ . '/../../../Models/HolidayProgramCreationModel.php';
require_once __DIR__ . '/../../../Models/HolidayProgramAdminModel.php';
require_once __DIR__ . '/../../../../core/CSRF.php';

class ProgramController extends \BaseController {
    private $creationModel;
    private $adminModel;

    public function __construct($conn = null) {
        if ($conn === null) {
            global $conn;
        }

        parent::__construct($conn);
        $this->creationModel = new \HolidayProgramCreationModel($conn);
        $this->adminModel = new \HolidayProgramAdminModel($conn);
    }

    /**
     * Get all holiday programs
     * GET /api/v1/admin/programs
     *
     * Response:
     * - success: boolean
     * - programs: Array of program objects
     * - count: Number of programs
     */
    public function index() {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Get all programs
            $programs = $this->adminModel->getAllPrograms();

            return $this->jsonSuccess([
                'programs' => $programs,
                'count' => count($programs)
            ], 'Programs retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Programs retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving programs', null, 500);
        }
    }

    /**
     * Get program details
     * GET /api/v1/admin/programs/{id}
     *
     * Response:
     * - success: boolean
     * - program: Program object with statistics
     */
    public function show($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Get program
            $program = $this->adminModel->getProgramById($id);

            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Get program statistics
            $statistics = $this->adminModel->getProgramStatistics($id);

            // Get capacity info
            $capacityInfo = $this->adminModel->getCapacityInfo($id);

            return $this->jsonSuccess([
                'program' => $program,
                'statistics' => $statistics,
                'capacity_info' => $capacityInfo
            ], 'Program retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Program retrieval failed', [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving program', null, 500);
        }
    }

    /**
     * Create a new holiday program
     * POST /api/v1/admin/programs
     *
     * Expected POST data:
     * - term: Program term (required)
     * - title: Program title (required)
     * - description: Program description
     * - program_goals: Program goals
     * - dates: Program dates (display string)
     * - start_date: Start date (YYYY-MM-DD, required)
     * - end_date: End date (YYYY-MM-DD, required)
     * - time: Time range (default: "9:00 AM - 4:00 PM")
     * - location: Location (default: "Sci-Bono Clubhouse")
     * - age_range: Age range (default: "13-18 years")
     * - max_participants: Maximum participants (default: 30)
     * - registration_deadline: Registration deadline
     * - lunch_included: Lunch included (0|1, default 1)
     * - registration_open: Registration open (0|1, default 1)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - program_id: ID of created program
     * - program: Created program object
     * - message: Status message
     */
    public function createProgram() {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Validate required fields
            $term = trim($_POST['term'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');

            if (empty($term)) {
                return $this->jsonError('Program term is required', null, 400);
            }

            if (empty($title)) {
                return $this->jsonError('Program title is required', null, 400);
            }

            if (empty($startDate)) {
                return $this->jsonError('Start date is required', null, 400);
            }

            if (empty($endDate)) {
                return $this->jsonError('End date is required', null, 400);
            }

            // Validate date format
            if (!$this->isValidDate($startDate)) {
                return $this->jsonError('Invalid start date format. Use YYYY-MM-DD', null, 400);
            }

            if (!$this->isValidDate($endDate)) {
                return $this->jsonError('Invalid end date format. Use YYYY-MM-DD', null, 400);
            }

            // Validate end date is after start date
            if (strtotime($endDate) < strtotime($startDate)) {
                return $this->jsonError('End date must be after start date', null, 400);
            }

            // Prepare program data
            $programData = [
                'term' => $term,
                'title' => $title,
                'description' => trim($_POST['description'] ?? ''),
                'program_goals' => trim($_POST['program_goals'] ?? ''),
                'dates' => trim($_POST['dates'] ?? ''),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'time' => trim($_POST['time'] ?? '9:00 AM - 4:00 PM'),
                'location' => trim($_POST['location'] ?? 'Sci-Bono Clubhouse'),
                'age_range' => trim($_POST['age_range'] ?? '13-18 years'),
                'max_participants' => isset($_POST['max_participants']) ? intval($_POST['max_participants']) : 30,
                'registration_deadline' => trim($_POST['registration_deadline'] ?? ''),
                'lunch_included' => isset($_POST['lunch_included']) ? intval($_POST['lunch_included']) : 1,
                'registration_open' => isset($_POST['registration_open']) ? intval($_POST['registration_open']) : 1
            ];

            // Auto-generate dates string if not provided
            if (empty($programData['dates'])) {
                $programData['dates'] = date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
            }

            // Create program
            $programId = $this->creationModel->createProgram($programData);

            if ($programId) {
                // Get the created program
                $program = $this->adminModel->getProgramById($programId);

                $this->logger->log('info', 'Holiday program created', [
                    'program_id' => $programId,
                    'term' => $term,
                    'title' => $title,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'program_id' => $programId,
                    'program' => $program
                ], 'Holiday program created successfully', 201);
            } else {
                return $this->jsonError('Failed to create holiday program', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Holiday program creation failed', [
                'title' => $_POST['title'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during program creation', null, 500);
        }
    }

    /**
     * Alias for createProgram (RESTful store method)
     */
    public function store() {
        return $this->createProgram();
    }

    /**
     * Update an existing holiday program
     * PUT /api/v1/admin/programs/{id}
     *
     * Expected POST data (PUT simulation via POST):
     * - term: Program term
     * - title: Program title
     * - description: Program description
     * - program_goals: Program goals
     * - dates: Program dates
     * - start_date: Start date (YYYY-MM-DD)
     * - end_date: End date (YYYY-MM-DD)
     * - time: Time range
     * - location: Location
     * - age_range: Age range
     * - max_participants: Maximum participants
     * - registration_deadline: Registration deadline
     * - lunch_included: Lunch included (0|1)
     * - registration_open: Registration open (0|1)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - program_id: ID of updated program
     * - program: Updated program object
     * - message: Status message
     */
    public function updateProgram($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if program exists
            $existingProgram = $this->adminModel->getProgramById($id);
            if (!$existingProgram) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Prepare update data (only update fields that are provided)
            $programData = [
                'term' => trim($_POST['term'] ?? $existingProgram['term']),
                'title' => trim($_POST['title'] ?? $existingProgram['title']),
                'description' => trim($_POST['description'] ?? $existingProgram['description']),
                'program_goals' => trim($_POST['program_goals'] ?? $existingProgram['program_goals']),
                'dates' => trim($_POST['dates'] ?? $existingProgram['dates']),
                'start_date' => trim($_POST['start_date'] ?? $existingProgram['start_date']),
                'end_date' => trim($_POST['end_date'] ?? $existingProgram['end_date']),
                'time' => trim($_POST['time'] ?? $existingProgram['time']),
                'location' => trim($_POST['location'] ?? $existingProgram['location']),
                'age_range' => trim($_POST['age_range'] ?? $existingProgram['age_range']),
                'max_participants' => isset($_POST['max_participants']) ? intval($_POST['max_participants']) : $existingProgram['max_participants'],
                'registration_deadline' => trim($_POST['registration_deadline'] ?? $existingProgram['registration_deadline']),
                'lunch_included' => isset($_POST['lunch_included']) ? intval($_POST['lunch_included']) : $existingProgram['lunch_included'],
                'registration_open' => isset($_POST['registration_open']) ? intval($_POST['registration_open']) : $existingProgram['registration_open']
            ];

            // Validate required fields
            if (empty($programData['term'])) {
                return $this->jsonError('Program term cannot be empty', null, 400);
            }

            if (empty($programData['title'])) {
                return $this->jsonError('Program title cannot be empty', null, 400);
            }

            if (empty($programData['start_date'])) {
                return $this->jsonError('Start date cannot be empty', null, 400);
            }

            if (empty($programData['end_date'])) {
                return $this->jsonError('End date cannot be empty', null, 400);
            }

            // Validate date format if provided
            if (!$this->isValidDate($programData['start_date'])) {
                return $this->jsonError('Invalid start date format. Use YYYY-MM-DD', null, 400);
            }

            if (!$this->isValidDate($programData['end_date'])) {
                return $this->jsonError('Invalid end date format. Use YYYY-MM-DD', null, 400);
            }

            // Validate end date is after start date
            if (strtotime($programData['end_date']) < strtotime($programData['start_date'])) {
                return $this->jsonError('End date must be after start date', null, 400);
            }

            // Update program
            $result = $this->creationModel->updateProgram($id, $programData);

            if ($result) {
                // Get the updated program
                $program = $this->adminModel->getProgramById($id);

                $this->logger->log('info', 'Holiday program updated', [
                    'program_id' => $id,
                    'title' => $programData['title'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'program_id' => $id,
                    'program' => $program
                ], 'Holiday program updated successfully');
            } else {
                return $this->jsonError('Failed to update holiday program', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Holiday program update failed', [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during program update', null, 500);
        }
    }

    /**
     * Alias for updateProgram (RESTful update method)
     */
    public function update($id) {
        return $this->updateProgram($id);
    }

    /**
     * Delete a holiday program
     * DELETE /api/v1/admin/programs/{id}
     *
     * Expected POST data:
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - program_id: ID of deleted program
     * - message: Status message
     */
    public function deleteProgram($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if program exists
            $program = $this->adminModel->getProgramById($id);
            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Check if program has registrations
            $registrations = $this->adminModel->getRegistrations($id);
            if (count($registrations) > 0) {
                return $this->jsonError('Cannot delete program with existing registrations', null, 400);
            }

            // Delete program
            $result = $this->creationModel->deleteProgram($id);

            if ($result) {
                $this->logger->log('info', 'Holiday program deleted', [
                    'program_id' => $id,
                    'title' => $program['title'],
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'program_id' => $id
                ], 'Holiday program deleted successfully');
            } else {
                return $this->jsonError('Failed to delete holiday program', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Holiday program deletion failed', [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during program deletion', null, 500);
        }
    }

    /**
     * Alias for deleteProgram (RESTful destroy method)
     */
    public function destroy($id) {
        return $this->deleteProgram($id);
    }

    /**
     * Get program registrations
     * GET /api/v1/admin/programs/{id}/registrations
     *
     * Query parameters:
     * - limit: Number of registrations to return (optional)
     * - offset: Offset for pagination (optional)
     *
     * Response:
     * - success: boolean
     * - program_id: ID of program
     * - registrations: Array of registration objects
     * - count: Number of registrations
     * - total: Total number of registrations
     */
    public function getRegistrations($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Check if program exists
            $program = $this->adminModel->getProgramById($id);
            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Get pagination parameters
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

            // Get registrations
            $registrations = $this->adminModel->getRegistrations($id, $limit, $offset);

            // Get total count
            $allRegistrations = $this->adminModel->getRegistrations($id);
            $totalCount = count($allRegistrations);

            return $this->jsonSuccess([
                'program_id' => $id,
                'registrations' => $registrations,
                'count' => count($registrations),
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset
            ], 'Registrations retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->log('error', 'Program registrations retrieval failed', [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error retrieving registrations', null, 500);
        }
    }

    /**
     * Update program capacity
     * PUT /api/v1/admin/programs/{id}/capacity
     *
     * Expected POST data:
     * - max_participants: New maximum participants (required)
     * - csrf_token: CSRF protection token
     *
     * Response:
     * - success: boolean
     * - program_id: ID of program
     * - max_participants: New maximum participants
     * - current_registrations: Current number of registrations
     * - available_spots: Available spots
     * - message: Status message
     */
    public function updateCapacity($id) {
        try {
            // Check admin authorization
            $this->requireRole('admin');

            // Validate CSRF token
            if (!\CSRF::validateToken()) {
                return $this->jsonError('Invalid CSRF token', null, 403);
            }

            // Check if program exists
            $program = $this->adminModel->getProgramById($id);
            if (!$program) {
                return $this->jsonError('Program not found', null, 404);
            }

            // Validate max_participants
            if (!isset($_POST['max_participants'])) {
                return $this->jsonError('Maximum participants is required', null, 400);
            }

            $maxParticipants = intval($_POST['max_participants']);

            if ($maxParticipants < 1) {
                return $this->jsonError('Maximum participants must be at least 1', null, 400);
            }

            // Get current registrations count
            $capacityInfo = $this->adminModel->getCapacityInfo($id);
            $currentRegistrations = $capacityInfo['total_registered'] ?? 0;

            // Check if new capacity is less than current registrations
            if ($maxParticipants < $currentRegistrations) {
                return $this->jsonError("Cannot set capacity to $maxParticipants. Current registrations: $currentRegistrations", null, 400);
            }

            // Update program capacity
            $programData = [
                'term' => $program['term'],
                'title' => $program['title'],
                'description' => $program['description'],
                'program_goals' => $program['program_goals'],
                'dates' => $program['dates'],
                'start_date' => $program['start_date'],
                'end_date' => $program['end_date'],
                'time' => $program['time'],
                'location' => $program['location'],
                'age_range' => $program['age_range'],
                'max_participants' => $maxParticipants,
                'registration_deadline' => $program['registration_deadline'],
                'lunch_included' => $program['lunch_included'],
                'registration_open' => $program['registration_open']
            ];

            $result = $this->creationModel->updateProgram($id, $programData);

            if ($result) {
                $availableSpots = $maxParticipants - $currentRegistrations;

                $this->logger->log('info', 'Program capacity updated', [
                    'program_id' => $id,
                    'old_capacity' => $program['max_participants'],
                    'new_capacity' => $maxParticipants,
                    'current_registrations' => $currentRegistrations,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                return $this->jsonSuccess([
                    'program_id' => $id,
                    'max_participants' => $maxParticipants,
                    'current_registrations' => $currentRegistrations,
                    'available_spots' => $availableSpots,
                    'utilization_percentage' => round(($currentRegistrations / $maxParticipants) * 100, 2)
                ], 'Program capacity updated successfully');
            } else {
                return $this->jsonError('Failed to update program capacity', null, 500);
            }

        } catch (\Exception $e) {
            $this->logger->log('error', 'Program capacity update failed', [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->jsonError('Server error during capacity update', null, 500);
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
