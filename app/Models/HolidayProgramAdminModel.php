<?php
class HolidayProgramAdminModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all holiday programs
     */
    public function getAllPrograms() {
        $sql = "SELECT id, term, title, dates, start_date, end_date, max_participants, registration_open, 
                       (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as total_registrations
                FROM holiday_programs 
                ORDER BY start_date DESC";
        
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
     * Get comprehensive program statistics
     */
    public function getProgramStatistics($programId) {
        $stats = [
            'total_registrations' => 0,
            'member_registrations' => 0,
            'mentor_applications' => 0,
            'confirmed_registrations' => 0,
            'pending_registrations' => 0,
            'gender_distribution' => ['Male' => 0, 'Female' => 0, 'Other' => 0],
            'age_distribution' => ['9-12' => 0, '13-15' => 0, '16-18' => 0, '19+' => 0],
            'grade_distribution' => [],
            'workshop_enrollments' => [],
            'mentor_status' => ['Pending' => 0, 'Approved' => 0, 'Declined' => 0],
            'registration_timeline' => []
        ];
        
        // Total registrations
        $sql = "SELECT COUNT(*) as total FROM holiday_program_attendees WHERE program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_registrations'] = $result->fetch_assoc()['total'];
        
        // Member vs mentor registrations
        $sql = "SELECT 
                    SUM(CASE WHEN mentor_registration = 0 THEN 1 ELSE 0 END) as members,
                    SUM(CASE WHEN mentor_registration = 1 THEN 1 ELSE 0 END) as mentors
                FROM holiday_program_attendees WHERE program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['member_registrations'] = $row['members'];
        $stats['mentor_applications'] = $row['mentors'];
        
        // Registration status distribution
        $sql = "SELECT registration_status, COUNT(*) as count 
                FROM holiday_program_attendees 
                WHERE program_id = ? 
                GROUP BY registration_status";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if ($row['registration_status'] === 'confirmed') {
                $stats['confirmed_registrations'] = $row['count'];
            } elseif ($row['registration_status'] === 'pending') {
                $stats['pending_registrations'] = $row['count'];
            }
        }
        
        // Gender distribution (excluding mentors)
        $sql = "SELECT gender, COUNT(*) as count 
                FROM holiday_program_attendees 
                WHERE program_id = ? AND mentor_registration = 0 AND gender IS NOT NULL
                GROUP BY gender";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats['gender_distribution'][$row['gender']] = $row['count'];
        }
        
        // Age distribution
        $sql = "SELECT 
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) as age_9_12,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 13 AND 15 THEN 1 ELSE 0 END) as age_13_15,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 16 AND 18 THEN 1 ELSE 0 END) as age_16_18,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 19 THEN 1 ELSE 0 END) as age_19_plus
                FROM holiday_program_attendees 
                WHERE program_id = ? AND mentor_registration = 0 AND date_of_birth IS NOT NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stats['age_distribution']['9-12'] = $row['age_9_12'];
            $stats['age_distribution']['13-15'] = $row['age_13_15'];
            $stats['age_distribution']['16-18'] = $row['age_16_18'];
            $stats['age_distribution']['19+'] = $row['age_19_plus'];
        }
        
        // Grade distribution
        $sql = "SELECT grade, COUNT(*) as count 
                FROM holiday_program_attendees 
                WHERE program_id = ? AND mentor_registration = 0 AND grade IS NOT NULL
                GROUP BY grade ORDER BY grade";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats['grade_distribution']['Grade ' . $row['grade']] = $row['count'];
        }
        
        // Workshop enrollments (based on preferences)
        $sql = "SELECT w.id, w.title, w.max_participants,
                    (SELECT COUNT(*) FROM holiday_workshop_enrollment e WHERE e.workshop_id = w.id) as enrolled
                FROM holiday_program_workshops w 
                WHERE w.program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats['workshop_enrollments'][$row['id']] = [
                'title' => $row['title'],
                'max_participants' => $row['max_participants'],
                'enrolled' => $row['enrolled'],
                'percentage' => $row['max_participants'] > 0 ? round(($row['enrolled'] / $row['max_participants']) * 100) : 0
            ];
        }
        
        // Mentor status distribution
        $sql = "SELECT mentor_status, COUNT(*) as count 
                FROM holiday_program_attendees 
                WHERE program_id = ? AND mentor_registration = 1 AND mentor_status IS NOT NULL
                GROUP BY mentor_status";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats['mentor_status'][$row['mentor_status']] = $row['count'];
        }
        
        // Registration timeline (last 30 days)
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as registrations
                FROM holiday_program_attendees 
                WHERE program_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at) 
                ORDER BY date";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats['registration_timeline'][] = [
                'date' => $row['date'],
                'registrations' => $row['registrations']
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get all registrations for a program
     */
    public function getRegistrations($programId, $limit = null, $offset = 0) {
        $sql = "SELECT a.*, 
                       (SELECT GROUP_CONCAT(w.title) FROM holiday_workshop_enrollment e 
                        JOIN holiday_program_workshops w ON e.workshop_id = w.id 
                        WHERE e.attendee_id = a.id) as assigned_workshops,
                       md.experience, md.availability
                FROM holiday_program_attendees a
                LEFT JOIN holiday_program_mentor_details md ON a.id = md.attendee_id
                WHERE a.program_id = ? 
                ORDER BY a.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($limit) {
            $stmt->bind_param("iii", $programId, $limit, $offset);
        } else {
            $stmt->bind_param("i", $programId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
        
        return $registrations;
    }
    
    /**
     * Get workshops for a program
     */
    public function getWorkshops($programId) {
        $sql = "SELECT w.*, 
                       (SELECT COUNT(*) FROM holiday_workshop_enrollment e WHERE e.workshop_id = w.id) as enrolled_count,
                       (SELECT COUNT(*) FROM holiday_program_attendees a 
                        WHERE a.program_id = ? AND a.mentor_registration = 1 AND a.mentor_workshop_preference = w.id AND a.mentor_status = 'Approved') as assigned_mentors
                FROM holiday_program_workshops w 
                WHERE w.program_id = ? 
                ORDER BY w.title";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $programId, $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }
        
        return $workshops;
    }
    
    /**
     * Get capacity information
     */
    public function getCapacityInfo($programId) {
        // Get program max participants
        $program = $this->getProgramById($programId);
        $maxParticipants = $program['max_participants'] ?? 30;
        
        // Count current registrations
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN mentor_registration = 0 THEN 1 ELSE 0 END) as members,
                    SUM(CASE WHEN mentor_registration = 1 THEN 1 ELSE 0 END) as mentors
                FROM holiday_program_attendees 
                WHERE program_id = ? AND registration_status != 'canceled'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();
        
        $memberCapacity = 30; // Could be made configurable
        $mentorCapacity = 5;  // Could be made configurable
        
        return [
            'total_capacity' => $maxParticipants,
            'total_registered' => $counts['total'],
            'member_capacity' => $memberCapacity,
            'member_registered' => $counts['members'],
            'mentor_capacity' => $mentorCapacity,
            'mentor_registered' => $counts['mentors'],
            'member_percentage' => round(($counts['members'] / $memberCapacity) * 100),
            'mentor_percentage' => round(($counts['mentors'] / $mentorCapacity) * 100),
            'is_member_full' => $counts['members'] >= $memberCapacity,
            'is_mentor_full' => $counts['mentors'] >= $mentorCapacity
        ];
    }
    
    /**
     * Update program status
     */
    public function updateProgramStatus($programId, $status, $registrationOpen = null) {
        // If registrationOpen is provided, use it directly, otherwise derive from status
        if ($registrationOpen !== null) {
            $regOpen = intval($registrationOpen);
        } else {
            // Handle different status values
            switch (strtolower($status)) {
                case 'open':
                    $regOpen = 1;
                    break;
                case 'closed':
                    $regOpen = 0;
                    break;
                case 'closing_soon':
                    $regOpen = 1; // Still open but closing soon
                    break;
                default:
                    $regOpen = 0;
            }
        }
        
        $sql = "UPDATE holiday_programs SET registration_open = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $regOpen, $programId);
        
        return $stmt->execute();
    }
    
    /**
     * Update attendee registration status
     */
    public function updateAttendeeStatus($attendeeId, $status) {
        $sql = "UPDATE holiday_program_attendees SET registration_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $attendeeId);
        
        return $stmt->execute();
    }
    
    /**
     * Update mentor status
     */
    public function updateMentorStatus($attendeeId, $status) {
        $sql = "UPDATE holiday_program_attendees SET mentor_status = ?, updated_at = NOW() WHERE id = ? AND mentor_registration = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $attendeeId);
        
        return $stmt->execute();
    }
    
    /**
     * Assign attendee to workshop
     */
    public function assignAttendeeToWorkshop($attendeeId, $workshopId) {
        // Check if already enrolled
        $checkSql = "SELECT id FROM holiday_workshop_enrollment WHERE attendee_id = ? AND workshop_id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $attendeeId, $workshopId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            return true; // Already enrolled
        }
        
        // Enroll in workshop
        $sql = "INSERT INTO holiday_workshop_enrollment (attendee_id, workshop_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $attendeeId, $workshopId);
        
        return $stmt->execute();
    }
    
    /**
     * Get detailed attendee information
     */
    public function getAttendeeDetails($attendeeId) {
        $sql = "SELECT a.*, 
                       md.experience, md.availability, md.notes,
                       GROUP_CONCAT(w.title) as enrolled_workshops
                FROM holiday_program_attendees a
                LEFT JOIN holiday_program_mentor_details md ON a.id = md.attendee_id
                LEFT JOIN holiday_workshop_enrollment e ON a.id = e.attendee_id
                LEFT JOIN holiday_program_workshops w ON e.workshop_id = w.id
                WHERE a.id = ?
                GROUP BY a.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attendeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get registrations data for export
     */
    public function getRegistrationsForExport($programId) {
        $sql = "SELECT 
                    a.first_name, a.last_name, a.email, a.phone, a.date_of_birth, a.gender,
                    a.school, a.grade, a.address, a.city, a.province, a.postal_code,
                    a.guardian_name, a.guardian_relationship, a.guardian_phone, a.guardian_email,
                    a.emergency_contact_name, a.emergency_contact_relationship, a.emergency_contact_phone,
                    a.why_interested, a.experience_level, a.medical_conditions, a.allergies,
                    a.dietary_restrictions, a.registration_status, a.mentor_registration, a.mentor_status,
                    a.created_at,
                    GROUP_CONCAT(w.title) as assigned_workshops
                FROM holiday_program_attendees a
                LEFT JOIN holiday_workshop_enrollment e ON a.id = e.attendee_id
                LEFT JOIN holiday_program_workshops w ON e.workshop_id = w.id
                WHERE a.program_id = ?
                GROUP BY a.id
                ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get attendees for email sending
     */
    public function getAttendeesForEmail($programId, $recipients) {
        $sql = "SELECT a.email, a.first_name, a.last_name, a.guardian_email 
                FROM holiday_program_attendees a 
                WHERE a.program_id = ?";
        
        if ($recipients === 'members') {
            $sql .= " AND a.mentor_registration = 0";
        } elseif ($recipients === 'mentors') {
            $sql .= " AND a.mentor_registration = 1";
        } elseif ($recipients === 'confirmed') {
            $sql .= " AND a.registration_status = 'confirmed'";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendees = [];
        while ($row = $result->fetch_assoc()) {
            $attendees[] = $row;
        }
        
        return $attendees;
    }
    
    /**
     * Get capacity analytics for charts
     */
    public function getCapacityAnalytics($programId) {
        $workshops = $this->getWorkshops($programId);
        $analytics = [];
        
        foreach ($workshops as $workshop) {
            $analytics[] = [
                'workshop' => $workshop['title'],
                'enrolled' => $workshop['enrolled_count'],
                'capacity' => $workshop['max_participants'],
                'percentage' => $workshop['max_participants'] > 0 ? 
                    round(($workshop['enrolled_count'] / $workshop['max_participants']) * 100) : 0
            ];
        }
        
        return $analytics;
    }
}