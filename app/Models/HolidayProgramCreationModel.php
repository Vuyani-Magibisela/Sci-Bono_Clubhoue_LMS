<?php
class HolidayProgramCreationModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Create a new holiday program
     */
    public function createProgram($programData) {
        $sql = "INSERT INTO holiday_programs (
                    term, title, description, program_goals, dates, start_date, end_date, 
                    time, location, age_range, max_participants, registration_deadline, 
                    lunch_included, registration_open, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ssssssssssisii", 
            $programData['term'],
            $programData['title'],
            $programData['description'],
            $programData['program_goals'],
            $programData['dates'],
            $programData['start_date'],
            $programData['end_date'],
            $programData['time'],
            $programData['location'],
            $programData['age_range'],
            $programData['max_participants'],
            $programData['registration_deadline'],
            $programData['lunch_included'],
            $programData['registration_open']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Update an existing holiday program
     */
    public function updateProgram($programId, $programData) {
        $sql = "UPDATE holiday_programs SET 
                    term = ?, title = ?, description = ?, program_goals = ?, dates = ?, 
                    start_date = ?, end_date = ?, time = ?, location = ?, age_range = ?, 
                    max_participants = ?, registration_deadline = ?, lunch_included = ?, 
                    registration_open = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ssssssssssisiii", 
            $programData['term'],
            $programData['title'],
            $programData['description'],
            $programData['program_goals'],
            $programData['dates'],
            $programData['start_date'],
            $programData['end_date'],
            $programData['time'],
            $programData['location'],
            $programData['age_range'],
            $programData['max_participants'],
            $programData['registration_deadline'],
            $programData['lunch_included'],
            $programData['registration_open'],
            $programId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get program by ID
     */
    public function getProgramById($programId) {
        $sql = "SELECT * FROM holiday_programs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Delete a program
     */
    public function deleteProgram($programId) {
        // First delete related workshops
        $this->deleteWorkshopsByProgramId($programId);
        
        // Then delete the program
        $sql = "DELETE FROM holiday_programs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        
        return $stmt->execute();
    }
    
    /**
     * Create a workshop
     */
    public function createWorkshop($workshopData) {
        $sql = "INSERT INTO holiday_program_workshops (
                    program_id, title, description, instructor, max_participants, 
                    location, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("isssis",
            $workshopData['program_id'],
            $workshopData['title'],
            $workshopData['description'],
            $workshopData['instructor'],
            $workshopData['max_participants'],
            $workshopData['location']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
    }
    
    /**
     * Get workshops for a program
     */
    public function getProgramWorkshops($programId) {
        $sql = "SELECT * FROM holiday_program_workshops WHERE program_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }
        
        return $workshops;
    }
    
    /**
     * Delete workshops by program ID
     */
    public function deleteWorkshopsByProgramId($programId) {
        $sql = "DELETE FROM holiday_program_workshops WHERE program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        
        return $stmt->execute();
    }
    
    /**
     * Get registration count for a program
     */
    public function getProgramRegistrationCount($programId) {
        $sql = "SELECT COUNT(*) as count FROM holiday_program_attendees WHERE program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }
    
    /**
     * Check if program title exists
     */
    public function titleExists($title, $excludeId = null) {
        $sql = "SELECT id FROM holiday_programs WHERE title = ?";
        $params = [$title];
        $types = "s";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Get all program terms for validation
     */
    public function getExistingTerms() {
        $sql = "SELECT DISTINCT term FROM holiday_programs ORDER BY term";
        $result = $this->conn->query($sql);
        
        $terms = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $terms[] = $row['term'];
            }
        }
        
        return $terms;
    }
    
    /**
     * Search programs
     */
    public function searchPrograms($searchTerm) {
        $sql = "SELECT id, term, title, dates, start_date, end_date, registration_open,
                       (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
                FROM holiday_programs 
                WHERE title LIKE ? OR term LIKE ? OR description LIKE ?
                ORDER BY start_date DESC";
        
        $searchWildcard = '%' . $searchTerm . '%';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $searchWildcard, $searchWildcard, $searchWildcard);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
        
        return $programs;
    }
    
    /**
     * Get programs by status
     */
    public function getProgramsByStatus($status) {
        $sql = "SELECT id, term, title, dates, start_date, end_date, registration_open,
                       (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
                FROM holiday_programs";
        
        if ($status === 'active') {
            $sql .= " WHERE registration_open = 1";
        } elseif ($status === 'closed') {
            $sql .= " WHERE registration_open = 0";
        } elseif ($status === 'upcoming') {
            $sql .= " WHERE start_date > CURDATE()";
        } elseif ($status === 'past') {
            $sql .= " WHERE end_date < CURDATE()";
        } elseif ($status === 'current') {
            $sql .= " WHERE CURDATE() BETWEEN start_date AND end_date";
        }
        
        $sql .= " ORDER BY start_date DESC";
        
        $result = $this->conn->query($sql);
        
        $programs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }
    
    /**
     * Update program status only
     */
    public function updateProgramStatus($programId, $registrationOpen) {
        $sql = "UPDATE holiday_programs SET registration_open = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $registrationOpen, $programId);
        
        return $stmt->execute();
    }
    
    /**
     * Get program with full details including workshops and stats
     */
    public function getProgramWithDetails($programId) {
        $program = $this->getProgramById($programId);
        
        if ($program) {
            $program['workshops'] = $this->getProgramWorkshops($programId);
            $program['registration_count'] = $this->getProgramRegistrationCount($programId);
            
            // Get workshop enrollment counts
            foreach ($program['workshops'] as &$workshop) {
                $sql = "SELECT COUNT(*) as count FROM holiday_workshop_enrollment 
                        WHERE workshop_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $workshop['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $workshop['enrollment_count'] = $row['count'];
            }
        }
        
        return $program;
    }
}