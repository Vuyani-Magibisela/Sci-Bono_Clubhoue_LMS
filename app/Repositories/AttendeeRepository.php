<?php
/**
 * Attendee Repository
 * Phase 3 Week 2: Holiday Programs Migration
 * Consolidates queries from HolidayProgramProfileModel, HolidayProgramAdminModel
 */

require_once __DIR__ . '/BaseRepository.php';

class AttendeeRepository extends BaseRepository {
    protected $table = 'holiday_program_attendees';
    protected $primaryKey = 'id';

    /**
     * Constructor
     */
    public function __construct($conn) {
        parent::__construct($conn, null);
    }

    /**
     * Get attendee by email for verification
     * Migrated from: HolidayProgramProfileModel::getAttendeeByEmail()
     */
    public function getAttendeeByEmail($email) {
        $sql = "SELECT id, first_name, last_name, email, password, mentor_registration,
                       program_id, registration_status
                FROM holiday_program_attendees
                WHERE email = ? AND registration_status IN ('confirmed', 'pending')";

        $result = $this->query($sql, [$email]);
        return $result->fetch_assoc();
    }

    /**
     * Get full attendee profile with program info
     * Migrated from: HolidayProgramProfileModel::getAttendeeProfile()
     */
    public function getAttendeeProfile($attendeeId) {
        $sql = "SELECT hpa.*, hp.title as program_title, hp.dates as program_dates
                FROM holiday_program_attendees hpa
                LEFT JOIN holiday_programs hp ON hpa.program_id = hp.id
                WHERE hpa.id = ?";

        $result = $this->query($sql, [$attendeeId]);
        return $result->fetch_assoc();
    }

    /**
     * Get detailed attendee information with workshops and mentor details
     * Migrated from: HolidayProgramAdminModel::getAttendeeDetails()
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

        $result = $this->query($sql, [$attendeeId]);
        return $result->fetch_assoc();
    }

    /**
     * Get all registrations for a program with pagination
     * Migrated from: HolidayProgramAdminModel::getRegistrations()
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
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $programId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->query($sql, [$programId]);
        }

        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }

        return $registrations;
    }

    /**
     * Get registrations data for CSV export
     * Migrated from: HolidayProgramAdminModel::getRegistrationsForExport()
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

        $result = $this->query($sql, [$programId]);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get attendees for email campaigns
     * Migrated from: HolidayProgramAdminModel::getAttendeesForEmail()
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

        $result = $this->query($sql, [$programId]);

        $attendees = [];
        while ($row = $result->fetch_assoc()) {
            $attendees[] = $row;
        }

        return $attendees;
    }

    /**
     * Update attendee password
     * Migrated from: HolidayProgramProfileModel::updatePassword()
     */
    public function updatePassword($attendeeId, $hashedPassword) {
        return $this->update($attendeeId, [
            'password' => $hashedPassword
        ]);
    }

    /**
     * Update attendee profile (supports dynamic fields)
     * Migrated from: HolidayProgramProfileModel::updateProfile()
     */
    public function updateProfile($attendeeId, $profileData) {
        // Add updated_at timestamp
        $profileData['updated_at'] = date('Y-m-d H:i:s');

        return $this->update($attendeeId, $profileData);
    }

    /**
     * Update attendee registration status
     * Migrated from: HolidayProgramAdminModel::updateAttendeeStatus()
     */
    public function updateAttendeeStatus($attendeeId, $status) {
        return $this->update($attendeeId, [
            'registration_status' => $status
        ]);
    }

    /**
     * Update mentor status
     * Migrated from: HolidayProgramAdminModel::updateMentorStatus()
     */
    public function updateMentorStatus($attendeeId, $status) {
        $sql = "UPDATE holiday_program_attendees
                SET mentor_status = ?, updated_at = NOW()
                WHERE id = ? AND mentor_registration = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $attendeeId);

        return $stmt->execute();
    }

    /**
     * Check if email is unique
     * Migrated from: HolidayProgramProfileModel::isEmailUnique()
     */
    public function isEmailUnique($email, $excludeId = null) {
        $sql = "SELECT id FROM holiday_program_attendees WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result->num_rows === 0;
    }

    /**
     * Log profile update to audit trail
     * Migrated from: HolidayProgramProfileModel::logProfileUpdate()
     */
    public function logProfileUpdate($attendeeId, $userId, $updateData) {
        $sql = "INSERT INTO holiday_program_audit_trail
                (program_id, user_id, action, new_values, ip_address, created_at)
                VALUES (
                    (SELECT program_id FROM holiday_program_attendees WHERE id = ?),
                    ?, 'profile_update', ?, ?, NOW()
                )";

        $stmt = $this->conn->prepare($sql);
        $auditValues = json_encode($updateData);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("iiss", $attendeeId, $userId, $auditValues, $ipAddress);

        return $stmt->execute();
    }

    /**
     * Assign attendee to workshop
     * Migrated from: HolidayProgramAdminModel::assignAttendeeToWorkshop()
     */
    public function assignToWorkshop($attendeeId, $workshopId) {
        // Check if already enrolled
        $checkSql = "SELECT id FROM holiday_workshop_enrollment WHERE attendee_id = ? AND workshop_id = ?";
        $result = $this->query($checkSql, [$attendeeId, $workshopId]);

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
     * Remove attendee from workshop
     */
    public function removeFromWorkshop($attendeeId, $workshopId) {
        $sql = "DELETE FROM holiday_workshop_enrollment WHERE attendee_id = ? AND workshop_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $attendeeId, $workshopId);

        return $stmt->execute();
    }

    /**
     * Get attendee's enrolled workshops
     */
    public function getEnrolledWorkshops($attendeeId) {
        $sql = "SELECT w.*
                FROM holiday_program_workshops w
                JOIN holiday_workshop_enrollment e ON w.id = e.workshop_id
                WHERE e.attendee_id = ?
                ORDER BY w.title";

        $result = $this->query($sql, [$attendeeId]);

        $workshops = [];
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }

        return $workshops;
    }

    /**
     * Count attendees by program
     */
    public function countByProgram($programId, $mentorOnly = false) {
        $conditions = ['program_id' => $programId];

        if ($mentorOnly) {
            $conditions['mentor_registration'] = 1;
        }

        return $this->count($conditions);
    }

    /**
     * Count attendees by status
     */
    public function countByStatus($programId, $status) {
        return $this->count([
            'program_id' => $programId,
            'registration_status' => $status
        ]);
    }
}
