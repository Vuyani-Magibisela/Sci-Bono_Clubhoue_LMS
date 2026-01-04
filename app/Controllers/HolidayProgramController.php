<?php
/**
 * Holiday Program Controller
 *
 * Handles holiday program viewing and basic functionality.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 3
 *
 * @package App\Controllers
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/HolidayProgramModel.php';

class HolidayProgramController extends BaseController {
    private $model;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->model = new HolidayProgramModel($this->conn);
    }

    /**
     * Display holiday program index/listing
     * Modern RESTful method
     */
    public function index() {
        try {
            $programs = $this->model->getAllPrograms();

            $this->logAction('holiday_programs_view', [
                'count' => count($programs)
            ]);

            return $this->view('holidayPrograms.index', [
                'programs' => $programs
            ]);
        } catch (Exception $e) {
            $this->logger->error("Failed to load holiday programs", [
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load programs'
            ], 'error');
        }
    }

    /**
     * Display a specific holiday program
     * Modern RESTful method
     *
     * @param int $programId Program ID
     * @return mixed View or JSON response
     */
    public function show($programId) {
        try {
            $data = $this->getProgram($programId);

            $this->logAction('holiday_program_view', [
                'program_id' => $programId
            ]);

            return $this->view('holidayPrograms.details', $data);
        } catch (Exception $e) {
            $this->logger->error("Failed to load holiday program", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.404', [
                'error' => 'Program not found'
            ], 'error');
        }
    }

    /**
     * Get program data with capacity status
     * Legacy method - maintained for backward compatibility
     *
     * @param int $programId Program ID
     * @return array Program data with registration and capacity info
     */
    public function getProgram($programId) {
        try {
            $program = $this->model->getProgramById($programId);

            if (!$program) {
                // Return default program data if not found
                $program = $this->getDefaultProgram($programId);

                $this->logger->warning("Program not found, using default", [
                    'program_id' => $programId
                ]);
            }

            // Check if user is registered
            $userIsRegistered = false;
            if ($this->isAuthenticated()) {
                $userId = $this->getUserId();
                // Check if user is registered for this program
                $userIsRegistered = $this->model->checkUserRegistration($programId, $userId);
            }

            // Get capacity information
            $capacityStatus = $this->model->checkProgramCapacity($programId);

            $this->logAction('get_program', [
                'program_id' => $programId,
                'user_registered' => $userIsRegistered,
                'capacity_full' => $capacityStatus['is_full'] ?? false
            ]);

            return [
                'program' => $program,
                'user_is_registered' => $userIsRegistered,
                'capacity_status' => $capacityStatus
            ];

        } catch (Exception $e) {
            $this->logger->error("Error getting program", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get default program data for fallback
     * Legacy method - maintained for backward compatibility
     *
     * @param int $programId Program ID
     * @return array Default program data
     */
    private function getDefaultProgram($programId) {
        // Return default program data if database doesn't have it
        return [
            'id' => $programId,
            'term' => 'Term 1',
            'title' => 'Multi-Media - Digital Design',
            'description' => 'Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.',
            'dates' => 'March 31 - April 4, 2025',
            'time' => '9:00 AM - 4:00 PM',
            'location' => 'Sci-Bono Clubhouse',
            'age_range' => '13-18 years',
            'max_participants' => 30,
            'registration_deadline' => 'March 24, 2025',
            'lunch_included' => true,
            'program_goals' => 'This program introduces participants to various aspects of digital design. Participants will learn essential skills in their chosen workshop track while addressing one or more of the 17 UN Sustainable Development Goals through their projects.',
            'registration_open' => true
        ];
    }

    /**
     * Get all active holiday programs
     * API method for AJAX requests
     *
     * @return void Sends JSON response
     */
    public function getActivePrograms() {
        try {
            $programs = $this->model->getActivePrograms();

            $this->logAction('get_active_programs', [
                'count' => count($programs)
            ]);

            $this->jsonResponse([
                'success' => true,
                'data' => $programs
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to get active programs", [
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load active programs'
            ], 500);
        }
    }

    /**
     * Get program by term
     * Utility method for program lookup
     *
     * @param string $term Program term (e.g., "Term 1")
     * @return array|null Program data or null if not found
     */
    public function getProgramByTerm($term) {
        try {
            $program = $this->model->getProgramByTerm($term);

            if ($program) {
                $this->logAction('get_program_by_term', [
                    'term' => $term,
                    'program_id' => $program['id']
                ]);
            }

            return $program;

        } catch (Exception $e) {
            $this->logger->error("Failed to get program by term", [
                'term' => $term,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if user is authenticated in holiday program session
     *
     * @return bool True if authenticated
     */
    private function isAuthenticated() {
        return isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'];
    }

    /**
     * Get current holiday program user ID
     *
     * @return int|null User ID or null if not logged in
     */
    private function getUserId() {
        return $_SESSION['holiday_user_id'] ?? null;
    }
}
?>
