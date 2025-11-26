<?php
/**
 * Workshop Repository
 * Phase 3 Week 2: Holiday Programs Migration
 * Consolidates workshop-related queries from HolidayProgramCreationModel, HolidayProgramAdminModel
 */

require_once __DIR__ . '/BaseRepository.php';

class WorkshopRepository extends BaseRepository {
    protected $table = 'holiday_program_workshops';
    protected $primaryKey = 'id';

    /**
     * Constructor
     */
    public function __construct($conn) {
        parent::__construct($conn, null);
    }

    /**
     * Get workshops for a program
     * Migrated from: HolidayProgramCreationModel::getProgramWorkshops()
     */
    public function getWorkshopsByProgram($programId) {
        $sql = "SELECT * FROM holiday_program_workshops WHERE program_id = ? ORDER BY id";
        $result = $this->query($sql, [$programId]);

        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }

        return $workshops;
    }

    /**
     * Get workshops with enrollment data and mentor assignments
     * Migrated from: HolidayProgramAdminModel::getWorkshops()
     */
    public function getWorkshopsWithData($programId) {
        $sql = "SELECT w.*,
                    COALESCE(wcv.enrolled_count, 0) as enrolled_count,
                    (SELECT COUNT(*) FROM holiday_program_attendees a
                        WHERE a.program_id = ? AND a.mentor_registration = 1
                        AND a.mentor_workshop_preference = w.id AND a.mentor_status = 'Approved') as assigned_mentors
                FROM holiday_program_workshops w
                LEFT JOIN workshop_capacity_view wcv ON w.id = wcv.workshop_id
                WHERE w.program_id = ?
                ORDER BY w.title";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $programId, $programId);
        $stmt->execute();
        $result = $stmt->get_result();

        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            // If view doesn't exist, calculate enrollment count manually
            if (!isset($row['enrolled_count'])) {
                $row['enrolled_count'] = $this->getEnrollmentCount($row['id']);
            }
            $workshops[] = $row;
        }

        return $workshops;
    }

    /**
     * Get enrollment count for a workshop
     */
    public function getEnrollmentCount($workshopId) {
        $sql = "SELECT COUNT(*) as count FROM holiday_workshop_enrollment WHERE workshop_id = ?";
        $result = $this->query($sql, [$workshopId]);
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Create a new workshop
     * Migrated from: HolidayProgramCreationModel::createWorkshop()
     */
    public function createWorkshop($workshopData) {
        // Add timestamps
        $workshopData['created_at'] = date('Y-m-d H:i:s');
        $workshopData['updated_at'] = date('Y-m-d H:i:s');

        return $this->create($workshopData);
    }

    /**
     * Update workshop
     */
    public function updateWorkshop($workshopId, $workshopData) {
        // Add updated timestamp
        $workshopData['updated_at'] = date('Y-m-d H:i:s');

        return $this->update($workshopId, $workshopData);
    }

    /**
     * Delete workshop by ID
     */
    public function deleteWorkshop($workshopId) {
        // First delete enrollments
        $this->deleteEnrollments($workshopId);

        // Then delete the workshop
        return $this->delete($workshopId);
    }

    /**
     * Delete all workshops for a program
     * Migrated from: HolidayProgramCreationModel::deleteWorkshopsByProgramId()
     */
    public function deleteWorkshopsByProgram($programId) {
        // Get all workshop IDs first
        $workshops = $this->getWorkshopsByProgram($programId);

        // Delete enrollments for each workshop
        foreach ($workshops as $workshop) {
            $this->deleteEnrollments($workshop['id']);
        }

        // Delete all workshops for the program
        $sql = "DELETE FROM holiday_program_workshops WHERE program_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);

        return $stmt->execute();
    }

    /**
     * Delete all enrollments for a workshop
     */
    public function deleteEnrollments($workshopId) {
        $sql = "DELETE FROM holiday_workshop_enrollment WHERE workshop_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $workshopId);

        return $stmt->execute();
    }

    /**
     * Get enrolled attendees for a workshop
     */
    public function getEnrolledAttendees($workshopId) {
        $sql = "SELECT a.*
                FROM holiday_program_attendees a
                JOIN holiday_workshop_enrollment e ON a.id = e.attendee_id
                WHERE e.workshop_id = ?
                ORDER BY a.last_name, a.first_name";

        $result = $this->query($sql, [$workshopId]);

        $attendees = [];
        while ($row = $result->fetch_assoc()) {
            $attendees[] = $row;
        }

        return $attendees;
    }

    /**
     * Check if workshop has capacity
     */
    public function hasCapacity($workshopId) {
        $workshop = $this->find($workshopId);

        if (!$workshop) {
            return false;
        }

        $enrollmentCount = $this->getEnrollmentCount($workshopId);
        $maxParticipants = $workshop['max_participants'] ?? 0;

        return $enrollmentCount < $maxParticipants;
    }

    /**
     * Get workshop capacity info
     */
    public function getCapacityInfo($workshopId) {
        $workshop = $this->find($workshopId);

        if (!$workshop) {
            return null;
        }

        $enrollmentCount = $this->getEnrollmentCount($workshopId);
        $maxParticipants = $workshop['max_participants'] ?? 0;

        return [
            'workshop_id' => $workshopId,
            'title' => $workshop['title'],
            'max_participants' => $maxParticipants,
            'enrolled' => $enrollmentCount,
            'available' => max(0, $maxParticipants - $enrollmentCount),
            'percentage' => $maxParticipants > 0 ? round(($enrollmentCount / $maxParticipants) * 100, 1) : 0,
            'is_full' => $enrollmentCount >= $maxParticipants
        ];
    }

    /**
     * Get capacity analytics for all workshops in a program
     * Migrated from: HolidayProgramAdminModel::getCapacityAnalytics()
     */
    public function getCapacityAnalytics($programId) {
        $workshops = $this->getWorkshopsWithData($programId);
        $analytics = [];

        foreach ($workshops as $workshop) {
            $analytics[] = [
                'workshop' => $workshop['title'],
                'enrolled' => $workshop['enrolled_count'] ?? 0,
                'capacity' => $workshop['max_participants'],
                'percentage' => $workshop['max_participants'] > 0 ?
                    round(($workshop['enrolled_count'] / $workshop['max_participants']) * 100) : 0
            ];
        }

        return $analytics;
    }

    /**
     * Enroll attendee in workshop
     */
    public function enrollAttendee($workshopId, $attendeeId) {
        // Check if already enrolled
        $checkSql = "SELECT id FROM holiday_workshop_enrollment WHERE workshop_id = ? AND attendee_id = ?";
        $result = $this->query($checkSql, [$workshopId, $attendeeId]);

        if ($result->num_rows > 0) {
            return true; // Already enrolled
        }

        // Check capacity
        if (!$this->hasCapacity($workshopId)) {
            return false; // Workshop is full
        }

        // Enroll attendee
        $sql = "INSERT INTO holiday_workshop_enrollment (workshop_id, attendee_id, enrolled_at) VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $workshopId, $attendeeId);

        return $stmt->execute();
    }

    /**
     * Unenroll attendee from workshop
     */
    public function unenrollAttendee($workshopId, $attendeeId) {
        $sql = "DELETE FROM holiday_workshop_enrollment WHERE workshop_id = ? AND attendee_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $workshopId, $attendeeId);

        return $stmt->execute();
    }

    /**
     * Get workshops by instructor
     */
    public function getWorkshopsByInstructor($instructor, $programId = null) {
        $sql = "SELECT * FROM holiday_program_workshops WHERE instructor = ?";
        $params = [$instructor];

        if ($programId) {
            $sql .= " AND program_id = ?";
            $params[] = $programId;
        }

        $sql .= " ORDER BY title";

        $result = $this->query($sql, $params);

        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }

        return $workshops;
    }

    /**
     * Search workshops by keyword
     */
    public function searchWorkshops($searchTerm, $programId = null) {
        $sql = "SELECT * FROM holiday_program_workshops WHERE (title LIKE ? OR description LIKE ? OR instructor LIKE ?)";
        $searchWildcard = '%' . $searchTerm . '%';
        $params = [$searchWildcard, $searchWildcard, $searchWildcard];

        if ($programId) {
            $sql .= " AND program_id = ?";
            $params[] = $programId;
        }

        $sql .= " ORDER BY title";

        $result = $this->query($sql, $params);

        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }

        return $workshops;
    }
}
