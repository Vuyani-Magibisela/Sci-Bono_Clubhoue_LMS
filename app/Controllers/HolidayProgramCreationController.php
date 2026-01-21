<?php
/**
 * Holiday Program Creation Controller
 *
 * Handles holiday program creation, editing, duplication, and deletion.
 * Includes program and workshop management functionality.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 3
 *
 * @package App\Controllers
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/HolidayProgramCreationModel.php';

class HolidayProgramCreationController extends BaseController {
    private $model;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->model = new HolidayProgramCreationModel($this->conn);
    }

    /**
     * Display program creation form
     * Modern RESTful method
     */
    public function create() {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        try {
            $this->logAction('view_program_creation_form');

            return $this->view('programs.create-form', [
                'program' => null,
                'mode' => 'create'
            ], 'admin');

        } catch (Exception $e) {
            $this->logger->error("Failed to load program creation form", [
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load form'
            ], 'error');
        }
    }

    /**
     * Store new program
     * Modern RESTful method
     */
    public function store() {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        $result = $this->createProgram($_POST);

        $this->jsonResponse($result);
    }

    /**
     * Display program edit form
     * Modern RESTful method
     *
     * @param int $programId Program ID
     */
    public function edit($programId) {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        try {
            $program = $this->getProgramForEdit($programId);

            if (!$program) {
                return $this->view('errors.404', [
                    'error' => 'Program not found'
                ], 'error');
            }

            $this->logAction('view_program_edit_form', [
                'program_id' => $programId
            ]);

            return $this->view('programs.create-form', [
                'program' => $program,
                'mode' => 'edit'
            ], 'admin');

        } catch (Exception $e) {
            $this->logger->error("Failed to load program edit form", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load form'
            ], 'error');
        }
    }

    /**
     * Update existing program
     * Modern RESTful method
     *
     * @param int $programId Program ID
     */
    public function update($programId) {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        $_POST['program_id'] = $programId;
        $_POST['edit_mode'] = true;

        $result = $this->createProgram($_POST);

        $this->jsonResponse($result);
    }

    /**
     * Delete program
     * Modern RESTful method
     *
     * @param int $programId Program ID
     */
    public function destroy($programId) {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        $result = $this->deleteProgram($programId);

        $this->jsonResponse($result);
    }

    /**
     * Create a new holiday program
     * Legacy method - maintained for backward compatibility
     *
     * @param array $data Form data
     * @return array Response with success status
     */
    public function createProgram($data) {
        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in program creation", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            // Check if this is an edit operation
            $isEdit = isset($data['edit_mode']) && isset($data['program_id']);

            // Validate required fields
            $validation = $this->validateProgramData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            // Generate dates string
            $datesString = $this->generateDatesString($data['start_date'], $data['end_date']);

            // Prepare program data
            $programData = [
                'term' => $data['term'],
                'title' => $data['title'],
                'description' => $data['description'],
                'program_goals' => $data['program_goals'] ?? null,
                'dates' => $datesString,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'time' => $data['time'] ?? '9:00 AM - 4:00 PM',
                'location' => $data['location'] ?? 'Sci-Bono Clubhouse',
                'age_range' => $data['age_range'] ?? '13-18 years',
                'max_participants' => intval($data['max_participants']),
                'registration_deadline' => $data['registration_deadline'] ?? null,
                'lunch_included' => isset($data['lunch_included']) ? 1 : 0,
                'registration_open' => isset($data['registration_open']) ? 1 : 0
            ];

            // Create or update the program
            if ($isEdit) {
                $programId = intval($data['program_id']);
                $result = $this->model->updateProgram($programId, $programData);

                if ($result) {
                    // Update workshops
                    $this->updateProgramWorkshops($programId, $data['workshops'] ?? []);

                    $this->logAction('update_program', [
                        'program_id' => $programId,
                        'title' => $programData['title']
                    ]);

                    return [
                        'success' => true,
                        'program_id' => $programId,
                        'message' => 'Program updated successfully!'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to update program. Please try again.'
                    ];
                }
            } else {
                $programId = $this->model->createProgram($programData);

                if ($programId) {
                    // Add workshops
                    $this->createProgramWorkshops($programId, $data['workshops'] ?? []);

                    $this->logAction('create_program', [
                        'program_id' => $programId,
                        'title' => $programData['title']
                    ]);

                    return [
                        'success' => true,
                        'program_id' => $programId,
                        'message' => 'Program created successfully!'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to create program. Please try again.'
                    ];
                }
            }

        } catch (Exception $e) {
            $this->logger->error("Error creating/updating program", [
                'error' => $e->getMessage(),
                'is_edit' => $isEdit ?? false
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ];
        }
    }

    /**
     * Get program data for editing
     * Legacy method - maintained for backward compatibility
     *
     * @param int $programId Program ID
     * @return array|null Program data or null if not found
     */
    public function getProgramForEdit($programId) {
        try {
            $program = $this->model->getProgramById($programId);

            if ($program) {
                // Get workshops for this program
                $program['workshops'] = $this->model->getProgramWorkshops($programId);

                $this->logAction('get_program_for_edit', [
                    'program_id' => $programId
                ]);
            }

            return $program;

        } catch (Exception $e) {
            $this->logger->error("Failed to get program for edit", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Validate program data
     * Private helper method
     *
     * @param array $data Form data
     * @return array Validation result with valid flag and message
     */
    private function validateProgramData($data) {
        $required = ['term', 'title', 'description', 'start_date', 'end_date', 'max_participants'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => 'Please fill in all required fields.'
                ];
            }
        }

        // Validate dates
        try {
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);

            if ($startDate >= $endDate) {
                return [
                    'valid' => false,
                    'message' => 'End date must be after start date.'
                ];
            }
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Invalid date format.'
            ];
        }

        // Validate max participants
        $maxParticipants = intval($data['max_participants']);
        if ($maxParticipants < 1 || $maxParticipants > 200) {
            return [
                'valid' => false,
                'message' => 'Maximum participants must be between 1 and 200.'
            ];
        }

        // Validate workshops
        if (isset($data['workshops']) && is_array($data['workshops'])) {
            $hasValidWorkshop = false;
            foreach ($data['workshops'] as $workshop) {
                if (!empty($workshop['title'])) {
                    $hasValidWorkshop = true;
                    break;
                }
            }

            if (!$hasValidWorkshop) {
                return [
                    'valid' => false,
                    'message' => 'Please add at least one workshop with a title.'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Generate a formatted dates string
     * Private helper method
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return string Formatted date range string
     */
    private function generateDatesString($startDate, $endDate) {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);

            $startFormatted = $start->format('F j');
            $endFormatted = $end->format('F j, Y');

            return $startFormatted . ' - ' . $endFormatted;

        } catch (Exception $e) {
            $this->logger->error("Failed to generate dates string", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);

            return $startDate . ' - ' . $endDate;
        }
    }

    /**
     * Create workshops for a program
     * Private helper method
     *
     * @param int $programId Program ID
     * @param array $workshops Workshop data array
     * @return int Number of workshops created
     */
    private function createProgramWorkshops($programId, $workshops) {
        if (empty($workshops) || !is_array($workshops)) {
            return 0;
        }

        $created = 0;

        try {
            foreach ($workshops as $workshop) {
                if (!empty($workshop['title'])) {
                    $workshopData = [
                        'program_id' => $programId,
                        'title' => $workshop['title'],
                        'description' => $workshop['description'] ?? null,
                        'instructor' => $workshop['instructor'] ?? null,
                        'max_participants' => !empty($workshop['max_participants']) ? intval($workshop['max_participants']) : 15,
                        'location' => $workshop['location'] ?? null
                    ];

                    if ($this->model->createWorkshop($workshopData)) {
                        $created++;
                    }
                }
            }

            $this->logAction('create_program_workshops', [
                'program_id' => $programId,
                'workshops_created' => $created
            ]);

            return $created;

        } catch (Exception $e) {
            $this->logger->error("Failed to create workshops", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return $created;
        }
    }

    /**
     * Update workshops for a program
     * Private helper method
     *
     * @param int $programId Program ID
     * @param array $workshops Workshop data array
     * @return int Number of workshops created
     */
    private function updateProgramWorkshops($programId, $workshops) {
        try {
            // First, delete all existing workshops for this program
            $this->model->deleteWorkshopsByProgramId($programId);

            // Then create new workshops
            $created = $this->createProgramWorkshops($programId, $workshops);

            $this->logAction('update_program_workshops', [
                'program_id' => $programId,
                'workshops_updated' => $created
            ]);

            return $created;

        } catch (Exception $e) {
            $this->logger->error("Failed to update workshops", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Delete a program
     * Legacy method - maintained for backward compatibility
     *
     * @param int $programId Program ID
     * @return array Response with success status
     */
    public function deleteProgram($programId) {
        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in program deletion", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'program_id' => $programId
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            // Check if program has registrations
            $registrationCount = $this->model->getProgramRegistrationCount($programId);

            if ($registrationCount > 0) {
                $this->logger->warning("Attempted to delete program with registrations", [
                    'program_id' => $programId,
                    'registration_count' => $registrationCount
                ]);

                return [
                    'success' => false,
                    'message' => 'Cannot delete program with existing registrations. Please cancel all registrations first.'
                ];
            }

            $result = $this->model->deleteProgram($programId);

            if ($result) {
                $this->logAction('delete_program', [
                    'program_id' => $programId,
                    'success' => true
                ]);

                return [
                    'success' => true,
                    'message' => 'Program deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete program.'
                ];
            }

        } catch (Exception $e) {
            $this->logger->error("Error deleting program", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while deleting the program.'
            ];
        }
    }

    /**
     * Duplicate a program
     * Legacy method - maintained for backward compatibility
     *
     * @param int $programId Program ID to duplicate
     * @return array Response with success status and new program ID
     */
    public function duplicateProgram($programId) {
        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in program duplication", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'program_id' => $programId
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            $originalProgram = $this->getProgramForEdit($programId);

            if (!$originalProgram) {
                return [
                    'success' => false,
                    'message' => 'Original program not found.'
                ];
            }

            // Modify program data for duplication
            $originalProgram['title'] = 'Copy of ' . $originalProgram['title'];
            $originalProgram['registration_open'] = 0; // Start with registration closed

            // Update dates to future dates (add 3 months)
            $startDate = new DateTime($originalProgram['start_date']);
            $endDate = new DateTime($originalProgram['end_date']);
            $startDate->add(new DateInterval('P3M'));
            $endDate->add(new DateInterval('P3M'));

            $originalProgram['start_date'] = $startDate->format('Y-m-d');
            $originalProgram['end_date'] = $endDate->format('Y-m-d');
            $originalProgram['dates'] = $this->generateDatesString($originalProgram['start_date'], $originalProgram['end_date']);

            // Remove ID to create new program
            unset($originalProgram['id']);

            $newProgramId = $this->model->createProgram($originalProgram);

            if ($newProgramId) {
                // Duplicate workshops
                $workshopsCreated = $this->createProgramWorkshops($newProgramId, $originalProgram['workshops']);

                $this->logAction('duplicate_program', [
                    'original_program_id' => $programId,
                    'new_program_id' => $newProgramId,
                    'workshops_duplicated' => $workshopsCreated
                ]);

                return [
                    'success' => true,
                    'program_id' => $newProgramId,
                    'message' => 'Program duplicated successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to duplicate program.'
                ];
            }

        } catch (Exception $e) {
            $this->logger->error("Error duplicating program", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while duplicating the program.'
            ];
        }
    }

    /**
     * Get program statistics for validation
     * Utility method for program info
     *
     * @param int $programId Program ID
     * @return array Statistics with registration and workshop counts
     */
    public function getProgramStatistics($programId) {
        try {
            $stats = [
                'registration_count' => $this->model->getProgramRegistrationCount($programId),
                'workshop_count' => count($this->model->getProgramWorkshops($programId))
            ];

            $this->logAction('get_program_statistics', [
                'program_id' => $programId,
                'stats' => $stats
            ]);

            return $stats;

        } catch (Exception $e) {
            $this->logger->error("Failed to get program statistics", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'registration_count' => 0,
                'workshop_count' => 0
            ];
        }
    }

    /**
     * Clone program with custom date offset
     * Modern method for program duplication with more control
     *
     * @param int $programId Program ID
     * @param int $monthsOffset Months to add to dates (default 3)
     * @return array Response with success status
     */
    public function cloneProgram($programId, $monthsOffset = 3) {
        // Require admin role
        $this->requireRole(['admin', 'manager']);

        if (!$this->validateCSRF()) {
            return [
                'success' => false,
                'message' => 'Security validation failed',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            $originalProgram = $this->getProgramForEdit($programId);

            if (!$originalProgram) {
                return [
                    'success' => false,
                    'message' => 'Program not found'
                ];
            }

            // Clone with custom offset
            $originalProgram['title'] = 'Copy of ' . $originalProgram['title'];
            $originalProgram['registration_open'] = 0;

            $startDate = new DateTime($originalProgram['start_date']);
            $endDate = new DateTime($originalProgram['end_date']);
            $startDate->add(new DateInterval("P{$monthsOffset}M"));
            $endDate->add(new DateInterval("P{$monthsOffset}M"));

            $originalProgram['start_date'] = $startDate->format('Y-m-d');
            $originalProgram['end_date'] = $endDate->format('Y-m-d');
            $originalProgram['dates'] = $this->generateDatesString($originalProgram['start_date'], $originalProgram['end_date']);

            unset($originalProgram['id']);

            $newProgramId = $this->model->createProgram($originalProgram);

            if ($newProgramId) {
                $workshopsCreated = $this->createProgramWorkshops($newProgramId, $originalProgram['workshops']);

                $this->logAction('clone_program', [
                    'original_program_id' => $programId,
                    'new_program_id' => $newProgramId,
                    'months_offset' => $monthsOffset
                ]);

                return [
                    'success' => true,
                    'program_id' => $newProgramId,
                    'workshops_created' => $workshopsCreated,
                    'message' => 'Program cloned successfully!'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to clone program'
            ];

        } catch (Exception $e) {
            $this->logger->error("Program cloning failed", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while cloning the program'
            ];
        }
    }
}
?>
