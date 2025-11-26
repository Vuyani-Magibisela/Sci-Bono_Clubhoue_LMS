<?php
/**
 * Program Service
 * Phase 3 Week 2: Holiday Programs Migration + Phase 4 Integration
 * Business logic for holiday programs, registration, capacity management, and analytics
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Repositories/ProgramRepository.php';
require_once __DIR__ . '/../Repositories/AttendeeRepository.php';
require_once __DIR__ . '/../Repositories/WorkshopRepository.php';

class ProgramService extends BaseService {
    private $programRepo;
    private $attendeeRepo;
    private $workshopRepo;

    /**
     * Constructor
     */
    public function __construct($conn = null) {
        parent::__construct($conn);

        $this->programRepo = new ProgramRepository($this->conn);
        $this->attendeeRepo = new AttendeeRepository($this->conn);
        $this->workshopRepo = new WorkshopRepository($this->conn);
    }

    /**
     * Get program by ID with full details
     */
    public function getProgramById($programId) {
        try {
            $program = $this->programRepo->getProgramWithDetails($programId);

            if (!$program) {
                throw new Exception("Program not found");
            }

            return $program;

        } catch (Exception $e) {
            $this->logAction("get_program_failed", ['program_id' => $programId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get all programs with optional filtering
     */
    public function getAllPrograms($status = null) {
        try {
            if ($status) {
                return $this->programRepo->getProgramsByStatus($status);
            }

            return $this->programRepo->getAllPrograms();

        } catch (Exception $e) {
            $this->handleError("Failed to get programs: " . $e->getMessage());
        }
    }

    /**
     * Search programs
     */
    public function searchPrograms($searchTerm) {
        try {
            return $this->programRepo->searchPrograms($searchTerm);

        } catch (Exception $e) {
            $this->handleError("Failed to search programs: " . $e->getMessage());
        }
    }

    /**
     * Create a new holiday program
     */
    public function createProgram($programData) {
        try {
            // Validate required fields
            $this->validateRequired($programData, [
                'term', 'title', 'description', 'start_date', 'end_date', 'max_participants'
            ]);

            // Sanitize data
            $programData = $this->sanitize($programData);

            // Validate dates
            if (strtotime($programData['end_date']) < strtotime($programData['start_date'])) {
                throw new Exception("End date must be after start date");
            }

            // Check if title already exists
            if ($this->programRepo->titleExists($programData['title'])) {
                throw new Exception("A program with this title already exists");
            }

            // Set defaults
            $programData['registration_open'] = $programData['registration_open'] ?? 1;
            $programData['lunch_included'] = $programData['lunch_included'] ?? 0;

            // Create program
            $programId = $this->programRepo->create($programData);

            $this->logAction("program_created", ['program_id' => $programId, 'title' => $programData['title']]);

            return $programId;

        } catch (Exception $e) {
            $this->handleError("Failed to create program: " . $e->getMessage(), ['data' => $programData]);
        }
    }

    /**
     * Update an existing program
     */
    public function updateProgram($programId, $programData) {
        try {
            // Check if program exists
            $existingProgram = $this->programRepo->find($programId);
            if (!$existingProgram) {
                throw new Exception("Program not found");
            }

            // Sanitize data
            $programData = $this->sanitize($programData);

            // Validate dates if provided
            if (isset($programData['start_date']) && isset($programData['end_date'])) {
                if (strtotime($programData['end_date']) < strtotime($programData['start_date'])) {
                    throw new Exception("End date must be after start date");
                }
            }

            // Check if title already exists (excluding this program)
            if (isset($programData['title']) && $this->programRepo->titleExists($programData['title'], $programId)) {
                throw new Exception("A program with this title already exists");
            }

            // Update program
            $success = $this->programRepo->update($programId, $programData);

            $this->logAction("program_updated", ['program_id' => $programId]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update program: " . $e->getMessage(), ['program_id' => $programId]);
        }
    }

    /**
     * Delete a program
     */
    public function deleteProgram($programId) {
        try {
            // Check if program exists
            $program = $this->programRepo->find($programId);
            if (!$program) {
                throw new Exception("Program not found");
            }

            // Check for existing registrations
            $registrationCount = $this->programRepo->getProgramRegistrationCount($programId);
            if ($registrationCount > 0) {
                throw new Exception("Cannot delete program with existing registrations. Please close registration and archive instead.");
            }

            // Delete workshops first
            $this->workshopRepo->deleteWorkshopsByProgram($programId);

            // Delete program
            $success = $this->programRepo->delete($programId);

            $this->logAction("program_deleted", ['program_id' => $programId, 'title' => $program['title']]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to delete program: " . $e->getMessage(), ['program_id' => $programId]);
        }
    }

    /**
     * Duplicate a program with date adjustments
     */
    public function duplicateProgram($programId, $dateOffset = null) {
        try {
            // Get existing program
            $program = $this->programRepo->find($programId);
            if (!$program) {
                throw new Exception("Program not found");
            }

            // Remove ID and modify title
            unset($program['id']);
            $program['title'] .= ' (Copy)';

            // Adjust dates if offset provided
            if ($dateOffset) {
                $program['start_date'] = date('Y-m-d', strtotime($program['start_date'] . ' + ' . $dateOffset . ' days'));
                $program['end_date'] = date('Y-m-d', strtotime($program['end_date'] . ' + ' . $dateOffset . ' days'));
                if ($program['registration_deadline']) {
                    $program['registration_deadline'] = date('Y-m-d', strtotime($program['registration_deadline'] . ' + ' . $dateOffset . ' days'));
                }
            }

            // Create new program
            $newProgramId = $this->createProgram($program);

            // Duplicate workshops
            $workshops = $this->workshopRepo->getWorkshopsByProgram($programId);
            foreach ($workshops as $workshop) {
                unset($workshop['id']);
                $workshop['program_id'] = $newProgramId;
                $this->workshopRepo->createWorkshop($workshop);
            }

            $this->logAction("program_duplicated", [
                'original_id' => $programId,
                'new_id' => $newProgramId
            ]);

            return $newProgramId;

        } catch (Exception $e) {
            $this->handleError("Failed to duplicate program: " . $e->getMessage(), ['program_id' => $programId]);
        }
    }

    /**
     * Update program registration status
     */
    public function updateRegistrationStatus($programId, $status) {
        try {
            $success = $this->programRepo->updateRegistrationStatus($programId, $status);

            $this->logAction("registration_status_updated", [
                'program_id' => $programId,
                'status' => $status ? 'open' : 'closed'
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update registration status: " . $e->getMessage());
        }
    }

    /**
     * Get program capacity information
     */
    public function getCapacityInfo($programId) {
        try {
            return $this->programRepo->getProgramCapacity($programId);

        } catch (Exception $e) {
            $this->handleError("Failed to get capacity info: " . $e->getMessage());
        }
    }

    /**
     * Check if program can accept registrations
     */
    public function canAcceptRegistrations($programId, $isMentor = false) {
        try {
            $program = $this->programRepo->find($programId);

            if (!$program) {
                return ['can_register' => false, 'reason' => 'Program not found'];
            }

            // Check if registration is open
            if (!$program['registration_open']) {
                return ['can_register' => false, 'reason' => 'Registration is closed'];
            }

            // Check if deadline has passed
            if ($program['registration_deadline'] && strtotime($program['registration_deadline']) < time()) {
                return ['can_register' => false, 'reason' => 'Registration deadline has passed'];
            }

            // Check capacity
            $capacity = $this->getCapacityInfo($programId);

            if ($isMentor) {
                if ($capacity['mentor_full']) {
                    return ['can_register' => false, 'reason' => 'Mentor capacity is full'];
                }
            } else {
                if ($capacity['member_full']) {
                    return ['can_register' => false, 'reason' => 'Member capacity is full'];
                }
            }

            return ['can_register' => true, 'reason' => null];

        } catch (Exception $e) {
            $this->handleError("Failed to check registration availability: " . $e->getMessage());
        }
    }

    /**
     * Get comprehensive program statistics
     */
    public function getProgramStatistics($programId) {
        try {
            return $this->programRepo->getProgramStatistics($programId);

        } catch (Exception $e) {
            $this->handleError("Failed to get program statistics: " . $e->getMessage());
        }
    }

    /**
     * Get dashboard data for a program
     */
    public function getDashboardData($programId) {
        try {
            $program = $this->programRepo->find($programId);

            if (!$program) {
                throw new Exception("Program not found");
            }

            $stats = $this->getProgramStatistics($programId);
            $capacity = $this->getCapacityInfo($programId);
            $workshops = $this->workshopRepo->getWorkshopsWithData($programId);

            return [
                'program' => $program,
                'statistics' => $stats,
                'capacity' => $capacity,
                'workshops' => $workshops
            ];

        } catch (Exception $e) {
            $this->handleError("Failed to get dashboard data: " . $e->getMessage());
        }
    }

    /**
     * Export registrations to CSV
     */
    public function exportRegistrationsCSV($programId) {
        try {
            $registrations = $this->attendeeRepo->getRegistrationsForExport($programId);

            if (empty($registrations)) {
                throw new Exception("No registrations found");
            }

            // Add UTF-8 BOM for Excel compatibility
            $csv = "\xEF\xBB\xBF";

            // Headers
            $headers = array_keys($registrations[0]);
            $csv .= implode(',', array_map(function($header) {
                return '"' . str_replace('"', '""', $header) . '"';
            }, $headers)) . "\n";

            // Data rows
            foreach ($registrations as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value ?? '') . '"';
                }, $row)) . "\n";
            }

            $this->logAction("registrations_exported", ['program_id' => $programId, 'count' => count($registrations)]);

            return $csv;

        } catch (Exception $e) {
            $this->handleError("Failed to export registrations: " . $e->getMessage());
        }
    }

    /**
     * Get capacity analytics for charts
     */
    public function getCapacityAnalytics($programId) {
        try {
            return $this->workshopRepo->getCapacityAnalytics($programId);

        } catch (Exception $e) {
            $this->handleError("Failed to get capacity analytics: " . $e->getMessage());
        }
    }

    /**
     * Automatically close registration if capacity reached
     */
    public function checkAndCloseIfFull($programId) {
        try {
            $program = $this->programRepo->find($programId);

            if (!$program || !$program['auto_close_on_capacity']) {
                return false;
            }

            $capacity = $this->getCapacityInfo($programId);

            if ($capacity['capacity_status'] === 'full') {
                $this->updateRegistrationStatus($programId, 0);

                $this->logAction("registration_auto_closed", [
                    'program_id' => $programId,
                    'reason' => 'capacity_reached'
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->logAction("auto_close_check_failed", [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get registration timeline data for charts
     */
    public function getRegistrationTimeline($programId, $days = 30) {
        try {
            $stats = $this->getProgramStatistics($programId);
            return $stats['registration_timeline'] ?? [];

        } catch (Exception $e) {
            $this->handleError("Failed to get registration timeline: " . $e->getMessage());
        }
    }

    /**
     * Get existing program terms for dropdown
     */
    public function getExistingTerms() {
        try {
            return $this->programRepo->getExistingTerms();

        } catch (Exception $e) {
            $this->handleError("Failed to get existing terms: " . $e->getMessage());
        }
    }

    /**
     * Validate program data
     */
    private function validateProgramData($data, $isUpdate = false) {
        $errors = [];

        // Required fields (for create)
        if (!$isUpdate) {
            if (empty($data['title'])) $errors[] = "Title is required";
            if (empty($data['term'])) $errors[] = "Term is required";
            if (empty($data['start_date'])) $errors[] = "Start date is required";
            if (empty($data['end_date'])) $errors[] = "End date is required";
        }

        // Date validation
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors[] = "End date must be after start date";
            }
        }

        // Capacity validation
        if (isset($data['max_participants']) && $data['max_participants'] < 1) {
            $errors[] = "Maximum participants must be at least 1";
        }

        // Registration deadline validation
        if (!empty($data['registration_deadline']) && !empty($data['start_date'])) {
            if (strtotime($data['registration_deadline']) > strtotime($data['start_date'])) {
                $errors[] = "Registration deadline must be before program start date";
            }
        }

        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode(", ", $errors));
        }

        return true;
    }
}
