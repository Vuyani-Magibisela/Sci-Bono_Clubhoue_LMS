<?php
require_once __DIR__ . '/../Models/HolidayProgramCreationModel.php';

class HolidayProgramCreationController {
    private $conn;
    private $model;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new HolidayProgramCreationModel($conn);
    }
    
    /**
     * Create a new holiday program
     */
    public function createProgram($data) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramCreationController::createProgram - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
            error_log("Error creating/updating program: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ];
        }
    }
    
    /**
     * Get program data for editing
     */
    public function getProgramForEdit($programId) {
        $program = $this->model->getProgramById($programId);
        
        if ($program) {
            // Get workshops for this program
            $program['workshops'] = $this->model->getProgramWorkshops($programId);
        }
        
        return $program;
    }
    
    /**
     * Validate program data
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
        $startDate = new DateTime($data['start_date']);
        $endDate = new DateTime($data['end_date']);
        
        if ($startDate >= $endDate) {
            return [
                'valid' => false,
                'message' => 'End date must be after start date.'
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
     */
    private function generateDatesString($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        $startFormatted = $start->format('F j');
        $endFormatted = $end->format('F j, Y');
        
        return $startFormatted . ' - ' . $endFormatted;
    }
    
    /**
     * Create workshops for a program
     */
    private function createProgramWorkshops($programId, $workshops) {
        if (empty($workshops) || !is_array($workshops)) {
            return;
        }
        
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
                
                $this->model->createWorkshop($workshopData);
            }
        }
    }
    
    /**
     * Update workshops for a program
     */
    private function updateProgramWorkshops($programId, $workshops) {
        // First, delete all existing workshops for this program
        $this->model->deleteWorkshopsByProgramId($programId);
        
        // Then create new workshops
        $this->createProgramWorkshops($programId, $workshops);
    }
    
    /**
     * Delete a program
     */
    public function deleteProgram($programId) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramCreationController::deleteProgram - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
                return [
                    'success' => false,
                    'message' => 'Cannot delete program with existing registrations. Please cancel all registrations first.'
                ];
            }
            
            $result = $this->model->deleteProgram($programId);
            
            if ($result) {
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
            error_log("Error deleting program: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while deleting the program.'
            ];
        }
    }
    
    /**
     * Duplicate a program
     */
    public function duplicateProgram($programId) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramCreationController::duplicateProgram - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
                $this->createProgramWorkshops($newProgramId, $originalProgram['workshops']);
                
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
            error_log("Error duplicating program: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while duplicating the program.'
            ];
        }
    }
    
    /**
     * Get program statistics for validation
     */
    public function getProgramStatistics($programId) {
        return [
            'registration_count' => $this->model->getProgramRegistrationCount($programId),
            'workshop_count' => count($this->model->getProgramWorkshops($programId))
        ];
    }
}