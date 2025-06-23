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

    /**
     * Get all holiday programs
    */
    public function getAllPrograms() {
        $sql = "SELECT 
                    id, 
                    term, 
                    title, 
                    description, 
                    dates, 
                    start_date, 
                    end_date, 
                    registration_open,
                    max_participants,
                    auto_close_on_capacity,
                    auto_close_on_date,
                    registration_deadline,
                    created_at,
                    updated_at,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
                FROM holiday_programs 
                ORDER BY start_date DESC, created_at DESC";
        
        $result = $this->conn->query($sql);
        
        $programs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }

    /**
     * Get programs by status - method already exists but here's the improved version
     */
    public function getProgramsByStatus($status) {
        $sql = "SELECT 
                    id, 
                    term, 
                    title, 
                    description, 
                    dates, 
                    start_date, 
                    end_date, 
                    registration_open,
                    max_participants,
                    auto_close_on_capacity,
                    auto_close_on_date,
                    registration_deadline,
                    created_at,
                    updated_at,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
                FROM holiday_programs";
        
        $conditions = [];
        
        switch($status) {
            case 'active':
                $conditions[] = "registration_open = 1";
                break;
            case 'closed':
                $conditions[] = "registration_open = 0";
                break;
            case 'upcoming':
                $conditions[] = "start_date > CURDATE()";
                break;
            case 'past':
                $conditions[] = "end_date < CURDATE()";
                break;
            case 'current':
                $conditions[] = "CURDATE() BETWEEN start_date AND end_date";
                break;
            case 'open_registration':
                $conditions[] = "registration_open = 1 AND (registration_deadline IS NULL OR registration_deadline > NOW())";
                break;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY start_date DESC, created_at DESC";
        
        $result = $this->conn->query($sql);
        
        $programs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }

    /**
     * Get a single program by ID
     */
    public function getProgramById($programId) {
        $sql = "SELECT 
                    id, 
                    term, 
                    title, 
                    description, 
                    dates, 
                    start_date, 
                    end_date, 
                    registration_open,
                    max_participants,
                    auto_close_on_capacity,
                    auto_close_on_date,
                    registration_deadline,
                    created_at,
                    updated_at,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id AND status = 'confirmed') as confirmed_count
                FROM holiday_programs 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    /**
     * Check if program exists
     */
    public function programExists($programId) {
        $sql = "SELECT id FROM holiday_programs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows > 0;
    }

    /**
     * Get program capacity information
     */
    public function getProgramCapacity($programId) {
        $sql = "SELECT 
                    p.max_participants,
                    COUNT(a.id) as total_registrations,
                    COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_count,
                    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN a.mentor_registration = 1 THEN 1 END) as mentor_count,
                    COUNT(CASE WHEN a.mentor_registration = 0 THEN 1 END) as member_count
                FROM holiday_programs p
                LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
                WHERE p.id = ?
                GROUP BY p.id, p.max_participants";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            
            // Calculate capacity percentage
            $data['capacity_percentage'] = $data['max_participants'] > 0 
                ? round(($data['confirmed_count'] / $data['max_participants']) * 100, 1) 
                : 0;
                
            // Determine capacity status
            if ($data['confirmed_count'] >= $data['max_participants']) {
                $data['capacity_status'] = 'full';
            } elseif ($data['capacity_percentage'] >= 90) {
                $data['capacity_status'] = 'nearly_full';
            } elseif ($data['capacity_percentage'] >= 75) {
                $data['capacity_status'] = 'filling_up';
            } else {
                $data['capacity_status'] = 'available';
            }
            
            return $data;
        }
        
        return [
            'max_participants' => 0,
            'total_registrations' => 0,
            'confirmed_count' => 0,
            'pending_count' => 0,
            'mentor_count' => 0,
            'member_count' => 0,
            'capacity_percentage' => 0,
            'capacity_status' => 'available'
        ];
    }

    /**
     * Update program registration status
     */
    public function updateRegistrationStatus($programId, $status) {
        $sql = "UPDATE holiday_programs 
                SET registration_open = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $status, $programId);
        
        if ($stmt->execute()) {
            // Log the status change if status log table exists
            $this->logStatusChange($programId, $status);
            return true;
        }
        
        return false;
    }

    /**
     * Log status changes (if status log table exists)
     */
    private function logStatusChange($programId, $newStatus) {
        // Check if status log table exists
        $checkTable = "SHOW TABLES LIKE 'holiday_program_status_log'";
        $result = $this->conn->query($checkTable);
        
        if ($result && $result->num_rows > 0) {
            $sql = "INSERT INTO holiday_program_status_log 
                    (program_id, old_status, new_status, change_reason, ip_address, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $oldStatus = $newStatus == 1 ? 0 : 1; // opposite of new status
            $reason = $newStatus == 1 ? 'Registration opened' : 'Registration closed';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt->bind_param("iiiss", $programId, $oldStatus, $newStatus, $reason, $ipAddress);
            $stmt->execute();
        }
    }

    /**
     * Get active programs (registration open)
     */
    public function getActivePrograms() {
        return $this->getProgramsByStatus('active');
    }

    /**
     * Get upcoming programs
     */
    public function getUpcomingPrograms() {
        return $this->getProgramsByStatus('upcoming');
    }

    /**
     * Search programs
     */
    public function searchPrograms($searchTerm) {
        $sql = "SELECT 
                    id, 
                    term, 
                    title, 
                    description, 
                    dates, 
                    start_date, 
                    end_date, 
                    registration_open,
                    max_participants,
                    created_at,
                    updated_at,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
                FROM holiday_programs 
                WHERE title LIKE ? OR term LIKE ? OR description LIKE ?
                ORDER BY start_date DESC, created_at DESC";
        
        $searchWildcard = '%' . $searchTerm . '%';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $searchWildcard, $searchWildcard, $searchWildcard);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $programs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }

    /**
     * Get program statistics
     */
    public function getProgramStatistics($programId) {
        $capacity = $this->getProgramCapacity($programId);
        $program = $this->getProgramById($programId);
        
        return [
            'total_registrations' => $capacity['total_registrations'],
            'confirmed_registrations' => $capacity['confirmed_count'],
            'pending_registrations' => $capacity['pending_count'],
            'mentor_applications' => $capacity['mentor_count'],
            'member_registrations' => $capacity['member_count'],
            'capacity_percentage' => $capacity['capacity_percentage'],
            'capacity_status' => $capacity['capacity_status'],
            'spots_remaining' => max(0, ($program['max_participants'] ?? 0) - $capacity['confirmed_count']),
            'registration_open' => $program['registration_open'] ?? 0
        ];
    }
}