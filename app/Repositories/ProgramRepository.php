<?php
/**
 * Program Repository
 * Phase 3 Week 2: Holiday Programs Migration
 * Consolidates queries from HolidayProgramModel, HolidayProgramCreationModel, HolidayProgramAdminModel
 */

require_once __DIR__ . '/BaseRepository.php';

class ProgramRepository extends BaseRepository {
    protected $table = 'holiday_programs';
    protected $primaryKey = 'id';

    /**
     * Constructor
     */
    public function __construct($conn) {
        parent::__construct($conn, null);
    }

    /**
     * Get program by ID with full details including nested structures
     * Migrated from: HolidayProgramModel::getProgramById()
     */
    public function getProgramWithDetails($programId) {
        try {
            $program = $this->find($programId);

            if ($program) {
                // Add nested structures
                $program['workshops'] = $this->getWorkshopsForProgram($programId);
                $program['daily_schedule'] = $this->getScheduleForProgram($programId);
                $program['project_requirements'] = $this->getRequirementsForProgram($programId);
                $program['evaluation_criteria'] = $this->getCriteriaForProgram($programId);
                $program['what_to_bring'] = $this->getItemsForProgram($programId);
                $program['faq'] = $this->getFaqsForProgram($programId);
                $program['registration_count'] = $this->getProgramRegistrationCount($programId);

                // Get workshop enrollment counts
                foreach ($program['workshops'] as &$workshop) {
                    $workshop['enrollment_count'] = $this->getWorkshopEnrollmentCount($workshop['id']);
                }
            }

            return $program;

        } catch (Exception $e) {
            $this->logError("Failed to get program with details", $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get workshops for a program
     * Migrated from: HolidayProgramModel::getWorkshopsForProgram()
     */
    public function getWorkshopsForProgram($programId) {
        $sql = "SELECT * FROM holiday_program_workshops WHERE program_id = ? ORDER BY id";
        $result = $this->query($sql, [$programId]);

        $workshops = [];
        while ($workshop = $result->fetch_assoc()) {
            // Map database column names to expected view format
            $workshop['mentor'] = $workshop['instructor'];
            $workshop['capacity'] = $workshop['max_participants'];

            // TODO: These should come from database tables in future
            $workshop['skills'] = ['3D modeling', 'Texturing', 'Lighting', 'Rendering'];
            $workshop['software'] = ['Blender'];
            $workshop['icon'] = 'fas fa-cube';

            $workshops[] = $workshop;
        }

        return $workshops;
    }

    /**
     * Get schedule for a program
     * Migrated from: HolidayProgramModel::getScheduleForProgram()
     */
    private function getScheduleForProgram($programId) {
        $schedule = [];

        // Get schedule days
        $sql = "SELECT * FROM holiday_program_schedules WHERE program_id = ? ORDER BY day_number";
        $result = $this->query($sql, [$programId]);

        while ($day = $result->fetch_assoc()) {
            $dayName = $day['day_name'];
            $schedule[$dayName] = [
                'date' => date('l, F j, Y', strtotime($day['date'])),
                'theme' => $day['theme'],
                'morning' => [],
                'afternoon' => []
            ];

            // Get schedule items for this day
            $itemsSql = "SELECT * FROM holiday_program_schedule_items WHERE schedule_id = ? ORDER BY id";
            $itemsStmt = $this->conn->prepare($itemsSql);
            $itemsStmt->bind_param("i", $day['id']);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();

            while ($item = $itemsResult->fetch_assoc()) {
                if ($item['session_type'] == 'morning') {
                    $schedule[$dayName]['morning'][$item['time_slot']] = $item['activity'];
                } else {
                    $schedule[$dayName]['afternoon'][$item['time_slot']] = $item['activity'];
                }
            }
        }

        // Return default schedule if none found
        if (empty($schedule)) {
            $schedule = $this->getDefaultSchedule();
        }

        return $schedule;
    }

    /**
     * Get default schedule structure
     */
    private function getDefaultSchedule() {
        return [
            'Day 1' => [
                'date' => 'To be announced',
                'theme' => 'Introduction & Fundamentals',
                'morning' => [
                    '9:00 - 9:30' => 'Welcome and program overview for all participants'
                ],
                'afternoon' => [
                    '1:00 - 2:30' => 'Software introduction and basic skills training'
                ]
            ]
        ];
    }

    /**
     * Get project requirements (currently hardcoded, TODO: move to database)
     */
    private function getRequirementsForProgram($programId) {
        // TODO: Store in database table holiday_program_requirements
        return [
            'All projects must address at least one UN Sustainable Development Goal',
            'Projects must be completed by the end of the program',
            'Each participant/team must prepare a brief presentation for the showcase',
            'Projects should demonstrate application of skills learned during the program'
        ];
    }

    /**
     * Get evaluation criteria (currently hardcoded, TODO: move to database)
     */
    private function getCriteriaForProgram($programId) {
        // TODO: Store in database table holiday_program_criteria
        return [
            'Technical Execution' => 'Quality of technical skills demonstrated',
            'Creativity' => 'Original ideas and creative approach',
            'Message' => 'Clear connection to SDGs and effective communication of message',
            'Completion' => 'Level of completion and polish',
            'Presentation' => 'Quality of showcase presentation'
        ];
    }

    /**
     * Get items to bring (currently hardcoded, TODO: move to database)
     */
    private function getItemsForProgram($programId) {
        // TODO: Store in database table holiday_program_items
        return [
            'Notebook and pen/pencil',
            'Snacks (lunch will be provided)',
            'Water bottle',
            'Enthusiasm and creativity!'
        ];
    }

    /**
     * Get FAQs (currently hardcoded, TODO: move to database)
     */
    private function getFaqsForProgram($programId) {
        // TODO: Store in database table holiday_program_faqs
        return [
            [
                'question' => 'Do I need prior experience to participate?',
                'answer' => 'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.'
            ]
        ];
    }

    /**
     * Get all programs with registration counts
     * Migrated from: HolidayProgramCreationModel::getAllPrograms() + HolidayProgramAdminModel::getAllPrograms()
     */
    public function getAllPrograms() {
        $sql = "SELECT
                    hp.*,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = hp.id) as registration_count,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = hp.id AND registration_status = 'confirmed') as confirmed_count
                FROM holiday_programs hp
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
     * Get programs by status
     * Migrated from: HolidayProgramCreationModel::getProgramsByStatus()
     */
    public function getProgramsByStatus($status) {
        $sql = "SELECT
                    hp.*,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = hp.id) as registration_count,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = hp.id AND registration_status = 'confirmed') as confirmed_count
                FROM holiday_programs hp";

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
     * Get active programs (shortcut method)
     */
    public function getActivePrograms() {
        return $this->getProgramsByStatus('active');
    }

    /**
     * Get upcoming programs (shortcut method)
     */
    public function getUpcomingPrograms() {
        return $this->getProgramsByStatus('upcoming');
    }

    /**
     * Search programs by keyword
     * Migrated from: HolidayProgramCreationModel::searchPrograms()
     */
    public function searchPrograms($searchTerm) {
        $sql = "SELECT
                    hp.*,
                    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = hp.id) as registration_count
                FROM holiday_programs hp
                WHERE title LIKE ? OR term LIKE ? OR description LIKE ?
                ORDER BY start_date DESC, created_at DESC";

        $searchWildcard = '%' . $searchTerm . '%';
        $result = $this->query($sql, [$searchWildcard, $searchWildcard, $searchWildcard]);

        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }

        return $programs;
    }

    /**
     * Get program capacity information
     * Migrated from: HolidayProgramCreationModel::getProgramCapacity() + HolidayProgramModel::checkProgramCapacity()
     */
    public function getProgramCapacity($programId) {
        $sql = "SELECT
                    p.max_participants,
                    COUNT(a.id) as total_registrations,
                    COUNT(CASE WHEN a.registration_status = 'confirmed' THEN 1 END) as confirmed_count,
                    COUNT(CASE WHEN a.registration_status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN a.mentor_registration = 1 THEN 1 END) as mentor_count,
                    COUNT(CASE WHEN a.mentor_registration = 0 THEN 1 END) as member_count
                FROM holiday_programs p
                LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
                WHERE p.id = ?
                GROUP BY p.id, p.max_participants";

        $result = $this->query($sql, [$programId]);

        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();

            // Calculate capacity percentage
            $data['capacity_percentage'] = $data['max_participants'] > 0
                ? round(($data['confirmed_count'] / $data['max_participants']) * 100, 1)
                : 0;

            // Also calculate legacy fields (member/mentor percentages)
            $memberCapacity = 30; // TODO: Make this configurable
            $mentorCapacity = 5;  // TODO: Make this configurable
            $data['member_capacity'] = $memberCapacity;
            $data['mentor_capacity'] = $mentorCapacity;
            $data['member_full'] = $data['member_count'] >= $memberCapacity;
            $data['mentor_full'] = $data['mentor_count'] >= $mentorCapacity;
            $data['member_percentage'] = min(($data['member_count'] / $memberCapacity) * 100, 100);
            $data['mentor_percentage'] = min(($data['mentor_count'] / $mentorCapacity) * 100, 100);

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
            'capacity_status' => 'available',
            'member_capacity' => 30,
            'mentor_capacity' => 5,
            'member_full' => false,
            'mentor_full' => false,
            'member_percentage' => 0,
            'mentor_percentage' => 0
        ];
    }

    /**
     * Get program registration count
     * Migrated from: HolidayProgramCreationModel::getProgramRegistrationCount()
     */
    public function getProgramRegistrationCount($programId) {
        $sql = "SELECT COUNT(*) as count FROM holiday_program_attendees WHERE program_id = ?";
        $result = $this->query($sql, [$programId]);
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Check if program title exists
     * Migrated from: HolidayProgramCreationModel::titleExists()
     */
    public function titleExists($title, $excludeId = null) {
        $sql = "SELECT id FROM holiday_programs WHERE title = ?";
        $params = [$title];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result->num_rows > 0;
    }

    /**
     * Get existing program terms
     * Migrated from: HolidayProgramCreationModel::getExistingTerms()
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
     * Update program registration status
     * Migrated from: HolidayProgramCreationModel::updateProgramStatus() + updateRegistrationStatus()
     */
    public function updateRegistrationStatus($programId, $status) {
        try {
            $success = $this->update($programId, [
                'registration_open' => $status
            ]);

            if ($success) {
                $this->logStatusChange($programId, $status);
            }

            return $success;

        } catch (Exception $e) {
            $this->logError("Failed to update registration status", $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log status changes to audit table if it exists
     * Migrated from: HolidayProgramCreationModel::logStatusChange()
     */
    private function logStatusChange($programId, $newStatus) {
        // Check if status log table exists
        $checkTable = "SHOW TABLES LIKE 'holiday_program_status_log'";
        $result = $this->conn->query($checkTable);

        if ($result && $result->num_rows > 0) {
            $sql = "INSERT INTO holiday_program_status_log
                    (program_id, old_status, new_status, change_reason, ip_address, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

            $oldStatus = $newStatus == 1 ? 0 : 1;
            $reason = $newStatus == 1 ? 'Registration opened' : 'Registration closed';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiiss", $programId, $oldStatus, $newStatus, $reason, $ipAddress);
            $stmt->execute();
        }
    }

    /**
     * Get comprehensive program statistics
     * Migrated from: HolidayProgramAdminModel::getProgramStatistics() + HolidayProgramCreationModel::getProgramStatistics()
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

        // Get capacity data first
        $capacity = $this->getProgramCapacity($programId);
        $stats['total_registrations'] = $capacity['total_registrations'];
        $stats['member_registrations'] = $capacity['member_count'];
        $stats['mentor_applications'] = $capacity['mentor_count'];
        $stats['confirmed_registrations'] = $capacity['confirmed_count'];
        $stats['pending_registrations'] = $capacity['pending_count'];
        $stats['capacity_percentage'] = $capacity['capacity_percentage'];
        $stats['capacity_status'] = $capacity['capacity_status'];

        // Get program details
        $program = $this->find($programId);
        $stats['spots_remaining'] = max(0, ($program['max_participants'] ?? 0) - $capacity['confirmed_count']);
        $stats['registration_open'] = $program['registration_open'] ?? 0;

        // Gender distribution
        $sql = "SELECT gender, COUNT(*) as count
                FROM holiday_program_attendees
                WHERE program_id = ? AND mentor_registration = 0 AND gender IS NOT NULL
                GROUP BY gender";
        $result = $this->query($sql, [$programId]);

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
        $result = $this->query($sql, [$programId]);

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
        $result = $this->query($sql, [$programId]);

        while ($row = $result->fetch_assoc()) {
            $stats['grade_distribution']['Grade ' . $row['grade']] = $row['count'];
        }

        // Workshop enrollments
        $sql = "SELECT w.id, w.title, w.max_participants,
                    (SELECT COUNT(*) FROM holiday_workshop_enrollment e WHERE e.workshop_id = w.id) as enrolled
                FROM holiday_program_workshops w
                WHERE w.program_id = ?";
        $result = $this->query($sql, [$programId]);

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
        $result = $this->query($sql, [$programId]);

        while ($row = $result->fetch_assoc()) {
            $stats['mentor_status'][$row['mentor_status']] = $row['count'];
        }

        // Registration timeline (last 30 days)
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as registrations
                FROM holiday_program_attendees
                WHERE program_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date";
        $result = $this->query($sql, [$programId]);

        while ($row = $result->fetch_assoc()) {
            $stats['registration_timeline'][] = [
                'date' => $row['date'],
                'registrations' => $row['registrations']
            ];
        }

        return $stats;
    }

    /**
     * Get workshop enrollment count
     */
    private function getWorkshopEnrollmentCount($workshopId) {
        $sql = "SELECT COUNT(*) as count FROM holiday_workshop_enrollment WHERE workshop_id = ?";
        $result = $this->query($sql, [$workshopId]);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
