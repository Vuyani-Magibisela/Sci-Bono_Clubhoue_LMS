<?php
/**
 * Admin\ProgramController
 *
 * Handles admin holiday program management
 *
 * Phase 3 Week 3: Modern Routing System - Full Implementation
 * Created: November 15, 2025
 * Consolidates: HolidayProgramAdminController.php + HolidayProgramCreationController.php
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/ProgramService.php';
require_once __DIR__ . '/../../Services/AttendeeService.php';
require_once __DIR__ . '/../../Repositories/WorkshopRepository.php';
require_once __DIR__ . '/../../core/CSRF.php';

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
     * Check admin authentication
     */
    private function checkAdminAuth() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }

        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo 'Access Denied - Admin Only';
            exit;
        }
    }

    /**
     * List all programs
     * GET /admin/programs
     */
    public function index() {
        $this->checkAdminAuth();

        try {
            // Get filter parameters
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;

            // Get programs
            if ($search) {
                $programs = $this->programService->searchPrograms($search);
            } elseif ($status) {
                $programs = $this->programService->getAllPrograms($status);
            } else {
                $programs = $this->programService->getAllPrograms();
            }

            // Render view
            $this->view('programs.index', [
                'programs' => $programs,
                'currentStatus' => $status,
                'searchTerm' => $search
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
     * Show program creation form
     * GET /admin/programs/create
     */
    public function create() {
        $this->checkAdminAuth();

        try {
            // Get existing terms for dropdown
            $existingTerms = $this->programService->getExistingTerms();

            $this->view('programs.create-form', [
                'program' => null,
                'existingTerms' => $existingTerms,
                'isEdit' => false,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load program creation form", [
                'error' => $e->getMessage()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load form'
            ]);
        }
    }

    /**
     * Store new program
     * POST /admin/programs
     */
    public function store() {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            // Get program data
            $programData = [
                'term' => $_POST['term'] ?? '',
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'program_goals' => $_POST['program_goals'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'dates' => $_POST['dates'] ?? '',
                'time' => $_POST['time'] ?? '',
                'location' => $_POST['location'] ?? '',
                'age_range' => $_POST['age_range'] ?? '',
                'max_participants' => intval($_POST['max_participants'] ?? 30),
                'registration_deadline' => $_POST['registration_deadline'] ?? null,
                'lunch_included' => isset($_POST['lunch_included']) ? 1 : 0,
                'registration_open' => isset($_POST['registration_open']) ? 1 : 0,
                'auto_close_on_capacity' => isset($_POST['auto_close_on_capacity']) ? 1 : 0,
                'auto_close_on_date' => isset($_POST['auto_close_on_date']) ? 1 : 0
            ];

            // Create program
            $programId = $this->programService->createProgram($programData);

            // Create workshops if provided
            if (isset($_POST['workshops']) && is_array($_POST['workshops'])) {
                foreach ($_POST['workshops'] as $workshopData) {
                    if (!empty($workshopData['title'])) {
                        $this->workshopRepo->createWorkshop([
                            'program_id' => $programId,
                            'title' => $workshopData['title'],
                            'description' => $workshopData['description'] ?? '',
                            'instructor' => $workshopData['instructor'] ?? '',
                            'max_participants' => intval($workshopData['max_participants'] ?? 10),
                            'location' => $workshopData['location'] ?? ''
                        ]);
                    }
                }
            }

            // Log action
            $this->logger->info("Program created", [
                'program_id' => $programId,
                'title' => $programData['title'],
                'user_id' => $_SESSION['user_id'] ?? null
            ]);

            // Redirect with success message
            $_SESSION['flash_success'] = "Program created successfully!";
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Failed to create program", [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);

            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/create");
            exit;
        }
    }

    /**
     * Show program details
     * GET /admin/programs/{id}
     */
    public function show($id) {
        $this->checkAdminAuth();

        try {
            $dashboardData = $this->programService->getDashboardData($id);

            $this->view('programs.dashboard.admin', [
                'program' => $dashboardData['program'],
                'statistics' => $dashboardData['statistics'],
                'capacity' => $dashboardData['capacity'],
                'workshops' => $dashboardData['workshops'],
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load program dashboard", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Program not found'
            ]);
        }
    }

    /**
     * Show program edit form
     * GET /admin/programs/{id}/edit
     */
    public function edit($id) {
        $this->checkAdminAuth();

        try {
            $program = $this->programService->getProgramById($id);
            $existingTerms = $this->programService->getExistingTerms();

            $this->view('programs.create-form', [
                'program' => $program,
                'existingTerms' => $existingTerms,
                'isEdit' => true,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load program edit form", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Program not found'
            ]);
        }
    }

    /**
     * Update program
     * PUT /admin/programs/{id}
     */
    public function update($id) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            // Get program data
            $programData = [
                'term' => $_POST['term'] ?? '',
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'program_goals' => $_POST['program_goals'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'dates' => $_POST['dates'] ?? '',
                'time' => $_POST['time'] ?? '',
                'location' => $_POST['location'] ?? '',
                'age_range' => $_POST['age_range'] ?? '',
                'max_participants' => intval($_POST['max_participants'] ?? 30),
                'registration_deadline' => $_POST['registration_deadline'] ?? null,
                'lunch_included' => isset($_POST['lunch_included']) ? 1 : 0,
                'registration_open' => isset($_POST['registration_open']) ? 1 : 0,
                'auto_close_on_capacity' => isset($_POST['auto_close_on_capacity']) ? 1 : 0,
                'auto_close_on_date' => isset($_POST['auto_close_on_date']) ? 1 : 0
            ];

            // Update program
            $this->programService->updateProgram($id, $programData);

            // Update workshops if provided
            if (isset($_POST['workshops']) && is_array($_POST['workshops'])) {
                // Delete existing workshops
                $this->workshopRepo->deleteWorkshopsByProgram($id);

                // Create new workshops
                foreach ($_POST['workshops'] as $workshopData) {
                    if (!empty($workshopData['title'])) {
                        $this->workshopRepo->createWorkshop([
                            'program_id' => $id,
                            'title' => $workshopData['title'],
                            'description' => $workshopData['description'] ?? '',
                            'instructor' => $workshopData['instructor'] ?? '',
                            'max_participants' => intval($workshopData['max_participants'] ?? 10),
                            'location' => $workshopData['location'] ?? ''
                        ]);
                    }
                }
            }

            // Log action
            $this->logger->info("Program updated", [
                'program_id' => $id,
                'title' => $programData['title'],
                'user_id' => $_SESSION['user_id'] ?? null
            ]);

            // Redirect with success message
            $_SESSION['flash_success'] = "Program updated successfully!";
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/{$id}");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Failed to update program", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/{$id}/edit");
            exit;
        }
    }

    /**
     * Delete program
     * DELETE /admin/programs/{id}
     */
    public function destroy($id) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            // Delete program
            $this->programService->deleteProgram($id);

            // Log action
            $this->logger->info("Program deleted", [
                'program_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);

            // Return JSON for AJAX or redirect
            if ($this->isAjaxRequest()) {
                return $this->jsonSuccess(null, "Program deleted successfully");
            }

            $_SESSION['flash_success'] = "Program deleted successfully!";
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Failed to delete program", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($this->isAjaxRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs");
            exit;
        }
    }

    /**
     * View program registrations
     * GET /admin/programs/{id}/registrations
     */
    public function registrations($id) {
        $this->checkAdminAuth();

        try {
            $program = $this->programService->getProgramById($id);
            $registrations = $this->attendeeService->getRegistrations($id);

            $this->view('programs.admin.registrations', [
                'program' => $program,
                'registrations' => $registrations,
                'csrfToken' => CSRF::getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load registrations", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->view('errors.404', [
                'error' => 'Program not found'
            ]);
        }
    }

    /**
     * Export registrations to CSV
     * GET /admin/programs/{id}/export
     */
    public function exportRegistrations($id) {
        $this->checkAdminAuth();

        try {
            $csv = $this->programService->exportRegistrationsCSV($id);
            $program = $this->programService->getProgramById($id);

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $program['title'] . '_registrations_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $csv;
            exit;

        } catch (Exception $e) {
            $this->logger->error("Failed to export registrations", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/{$id}");
            exit;
        }
    }

    /**
     * Update program status (AJAX)
     * PUT /admin/programs/{id}/status
     */
    public function updateStatus($id) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->json([
                    'success' => false,
                    'message' => 'CSRF validation failed'
                ], 403);
            }

            $status = intval($_POST['registration_open'] ?? 0);
            $this->programService->updateRegistrationStatus($id, $status);

            return $this->jsonSuccess([
                'status' => $status ? 'open' : 'closed'
            ], "Program status updated successfully");

        } catch (Exception $e) {
            $this->logger->error("Failed to update program status", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Duplicate program
     * POST /admin/programs/{id}/duplicate
     */
    public function duplicate($id) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                throw new Exception("CSRF validation failed");
            }

            $dateOffset = intval($_POST['date_offset'] ?? 0);
            $newProgramId = $this->programService->duplicateProgram($id, $dateOffset);

            $_SESSION['flash_success'] = "Program duplicated successfully!";
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/{$newProgramId}/edit");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Failed to duplicate program", [
                'program_id' => $id,
                'error' => $e->getMessage()
            ]);

            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /Sci-Bono_Clubhoue_LMS/admin/programs/{$id}");
            exit;
        }
    }

    /**
     * Update registration status (AJAX)
     * PUT /admin/programs/{id}/registrations/{attendeeId}/status
     */
    public function updateRegistrationStatus($id, $attendeeId) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->json([
                    'success' => false,
                    'message' => 'CSRF validation failed'
                ], 403);
            }

            $status = $_POST['status'] ?? 'pending';
            $this->attendeeService->updateRegistrationStatus($attendeeId, $status);

            return $this->jsonSuccess(null, "Registration status updated successfully");

        } catch (Exception $e) {
            $this->logger->error("Failed to update registration status", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update mentor status (AJAX)
     * PUT /admin/programs/{id}/registrations/{attendeeId}/mentor-status
     */
    public function updateMentorStatus($id, $attendeeId) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->json([
                    'success' => false,
                    'message' => 'CSRF validation failed'
                ], 403);
            }

            $status = $_POST['mentor_status'] ?? 'Pending';
            $this->attendeeService->updateMentorStatus($attendeeId, $status);

            return $this->jsonSuccess(null, "Mentor status updated successfully");

        } catch (Exception $e) {
            $this->logger->error("Failed to update mentor status", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Assign attendee to workshop (AJAX)
     * POST /admin/programs/{id}/registrations/{attendeeId}/assign-workshop
     */
    public function assignWorkshop($id, $attendeeId) {
        $this->checkAdminAuth();

        try {
            // Validate CSRF token
            if (!CSRF::validateToken()) {
                return $this->json([
                    'success' => false,
                    'message' => 'CSRF validation failed'
                ], 403);
            }

            $workshopId = intval($_POST['workshop_id'] ?? 0);
            $this->attendeeService->assignToWorkshop($attendeeId, $workshopId);

            return $this->jsonSuccess(null, "Workshop assigned successfully");

        } catch (Exception $e) {
            $this->logger->error("Failed to assign workshop", [
                'attendee_id' => $attendeeId,
                'workshop_id' => $workshopId ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
