<?php
/**
 * Attendee Service
 * Phase 3 Week 2: Holiday Programs Migration + Phase 4 Integration
 * Business logic for attendee registration, profile management, email verification, and workshop enrollment
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Repositories/AttendeeRepository.php';
require_once __DIR__ . '/../Repositories/WorkshopRepository.php';
require_once __DIR__ . '/../Repositories/ProgramRepository.php';

class AttendeeService extends BaseService {
    private $attendeeRepo;
    private $workshopRepo;
    private $programRepo;

    /**
     * Constructor
     */
    public function __construct($conn = null) {
        parent::__construct($conn);

        $this->attendeeRepo = new AttendeeRepository($this->conn);
        $this->workshopRepo = new WorkshopRepository($this->conn);
        $this->programRepo = new ProgramRepository($this->conn);
    }

    /**
     * Get attendee profile by ID
     */
    public function getAttendeeProfile($attendeeId) {
        try {
            $profile = $this->attendeeRepo->getAttendeeProfile($attendeeId);

            if (!$profile) {
                throw new Exception("Attendee not found");
            }

            // Add enrolled workshops
            $profile['enrolled_workshops'] = $this->attendeeRepo->getEnrolledWorkshops($attendeeId);

            return $profile;

        } catch (Exception $e) {
            $this->handleError("Failed to get attendee profile: " . $e->getMessage());
        }
    }

    /**
     * Get attendee by email (for login/verification)
     */
    public function getAttendeeByEmail($email) {
        try {
            return $this->attendeeRepo->getAttendeeByEmail($email);

        } catch (Exception $e) {
            $this->handleError("Failed to get attendee by email: " . $e->getMessage());
        }
    }

    /**
     * Register a new attendee for a program
     */
    public function registerAttendee($registrationData) {
        try {
            // Validate required fields
            $requiredFields = [
                'program_id', 'first_name', 'last_name', 'email', 'phone',
                'date_of_birth', 'gender', 'school', 'grade'
            ];

            $this->validateRequired($registrationData, $requiredFields);

            // Sanitize data
            $registrationData = $this->sanitize($registrationData);

            // Check if email is unique
            if (!$this->attendeeRepo->isEmailUnique($registrationData['email'])) {
                throw new Exception("This email is already registered for a holiday program");
            }

            // Check if program exists and can accept registrations
            $program = $this->programRepo->find($registrationData['program_id']);
            if (!$program) {
                throw new Exception("Program not found");
            }

            // Check capacity
            $capacity = $this->programRepo->getProgramCapacity($registrationData['program_id']);
            $isMentor = $registrationData['mentor_registration'] ?? 0;

            if ($isMentor && $capacity['mentor_full']) {
                throw new Exception("Mentor capacity is full");
            }

            if (!$isMentor && $capacity['member_full']) {
                throw new Exception("Member capacity is full");
            }

            // Set default values
            $registrationData['registration_status'] = $registrationData['registration_status'] ?? 'pending';
            $registrationData['mentor_registration'] = $isMentor ? 1 : 0;
            $registrationData['created_at'] = date('Y-m-d H:i:s');
            $registrationData['updated_at'] = date('Y-m-d H:i:s');

            // Create attendee record
            $attendeeId = $this->attendeeRepo->create($registrationData);

            $this->logAction("attendee_registered", [
                'attendee_id' => $attendeeId,
                'program_id' => $registrationData['program_id'],
                'email' => $registrationData['email']
            ]);

            return $attendeeId;

        } catch (Exception $e) {
            $this->handleError("Failed to register attendee: " . $e->getMessage(), ['data' => $registrationData]);
        }
    }

    /**
     * Update attendee profile
     */
    public function updateProfile($attendeeId, $profileData) {
        try {
            // Get existing profile
            $existingProfile = $this->attendeeRepo->find($attendeeId);
            if (!$existingProfile) {
                throw new Exception("Attendee not found");
            }

            // Sanitize data
            $profileData = $this->sanitize($profileData);

            // Check email uniqueness if email is being changed
            if (isset($profileData['email']) && $profileData['email'] !== $existingProfile['email']) {
                if (!$this->attendeeRepo->isEmailUnique($profileData['email'], $attendeeId)) {
                    throw new Exception("This email is already registered");
                }
            }

            // Update profile
            $success = $this->attendeeRepo->updateProfile($attendeeId, $profileData);

            // Log the update
            $userId = $attendeeId; // In a real system, this would be the logged-in user ID
            $this->attendeeRepo->logProfileUpdate($attendeeId, $userId, $profileData);

            $this->logAction("profile_updated", [
                'attendee_id' => $attendeeId,
                'fields_updated' => array_keys($profileData)
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update profile: " . $e->getMessage());
        }
    }

    /**
     * Create password for attendee
     */
    public function createPassword($attendeeId, $password, $confirmPassword) {
        try {
            // Validate passwords match
            if ($password !== $confirmPassword) {
                throw new Exception("Passwords do not match");
            }

            // Validate password strength
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Update password
            $success = $this->attendeeRepo->updatePassword($attendeeId, $hashedPassword);

            $this->logAction("password_created", ['attendee_id' => $attendeeId]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to create password: " . $e->getMessage());
        }
    }

    /**
     * Update password for attendee
     */
    public function updatePassword($attendeeId, $currentPassword, $newPassword, $confirmPassword) {
        try {
            // Get attendee
            $attendee = $this->attendeeRepo->find($attendeeId);
            if (!$attendee) {
                throw new Exception("Attendee not found");
            }

            // Verify current password
            if (!password_verify($currentPassword, $attendee['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new passwords match
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }

            // Validate password strength
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            $success = $this->attendeeRepo->updatePassword($attendeeId, $hashedPassword);

            $this->logAction("password_updated", ['attendee_id' => $attendeeId]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update password: " . $e->getMessage());
        }
    }

    /**
     * Verify attendee login
     */
    public function verifyLogin($email, $password) {
        try {
            $attendee = $this->getAttendeeByEmail($email);

            if (!$attendee) {
                throw new Exception("Invalid email or password");
            }

            if (!password_verify($password, $attendee['password'])) {
                throw new Exception("Invalid email or password");
            }

            $this->logAction("attendee_login", [
                'attendee_id' => $attendee['id'],
                'email' => $email
            ]);

            return $attendee;

        } catch (Exception $e) {
            $this->logAction("login_failed", ['email' => $email, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update attendee registration status
     */
    public function updateRegistrationStatus($attendeeId, $status) {
        try {
            $validStatuses = ['pending', 'confirmed', 'canceled', 'waitlist'];

            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status. Must be one of: " . implode(', ', $validStatuses));
            }

            $success = $this->attendeeRepo->updateAttendeeStatus($attendeeId, $status);

            $this->logAction("registration_status_updated", [
                'attendee_id' => $attendeeId,
                'status' => $status
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update registration status: " . $e->getMessage());
        }
    }

    /**
     * Update mentor application status
     */
    public function updateMentorStatus($attendeeId, $status) {
        try {
            $validStatuses = ['Pending', 'Approved', 'Declined'];

            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid mentor status. Must be one of: " . implode(', ', $validStatuses));
            }

            $success = $this->attendeeRepo->updateMentorStatus($attendeeId, $status);

            $this->logAction("mentor_status_updated", [
                'attendee_id' => $attendeeId,
                'status' => $status
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to update mentor status: " . $e->getMessage());
        }
    }

    /**
     * Assign attendee to workshop
     */
    public function assignToWorkshop($attendeeId, $workshopId) {
        try {
            // Check if workshop has capacity
            if (!$this->workshopRepo->hasCapacity($workshopId)) {
                throw new Exception("Workshop is full");
            }

            // Assign to workshop
            $success = $this->attendeeRepo->assignToWorkshop($attendeeId, $workshopId);

            $this->logAction("workshop_assigned", [
                'attendee_id' => $attendeeId,
                'workshop_id' => $workshopId
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to assign to workshop: " . $e->getMessage());
        }
    }

    /**
     * Remove attendee from workshop
     */
    public function removeFromWorkshop($attendeeId, $workshopId) {
        try {
            $success = $this->attendeeRepo->removeFromWorkshop($attendeeId, $workshopId);

            $this->logAction("workshop_removed", [
                'attendee_id' => $attendeeId,
                'workshop_id' => $workshopId
            ]);

            return $success;

        } catch (Exception $e) {
            $this->handleError("Failed to remove from workshop: " . $e->getMessage());
        }
    }

    /**
     * Get all registrations for a program
     */
    public function getRegistrations($programId, $page = 1, $perPage = 25) {
        try {
            $offset = ($page - 1) * $perPage;
            return $this->attendeeRepo->getRegistrations($programId, $perPage, $offset);

        } catch (Exception $e) {
            $this->handleError("Failed to get registrations: " . $e->getMessage());
        }
    }

    /**
     * Get attendees for email campaigns
     */
    public function getAttendeesForEmail($programId, $recipients = 'all') {
        try {
            return $this->attendeeRepo->getAttendeesForEmail($programId, $recipients);

        } catch (Exception $e) {
            $this->handleError("Failed to get attendees for email: " . $e->getMessage());
        }
    }

    /**
     * Generate email verification token
     */
    public function generateVerificationToken($attendeeId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Store token in database
            $sql = "INSERT INTO holiday_program_access_tokens (attendee_id, token, expires_at, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("issss", $attendeeId, $token, $expiresAt, $token, $expiresAt);
            $stmt->execute();

            $this->logAction("verification_token_generated", ['attendee_id' => $attendeeId]);

            return $token;

        } catch (Exception $e) {
            $this->handleError("Failed to generate verification token: " . $e->getMessage());
        }
    }

    /**
     * Verify email token
     */
    public function verifyEmailToken($token) {
        try {
            $sql = "SELECT attendee_id FROM holiday_program_access_tokens
                    WHERE token = ? AND expires_at > NOW()";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid or expired token");
            }

            $row = $result->fetch_assoc();

            $this->logAction("email_verified", ['attendee_id' => $row['attendee_id']]);

            return $row['attendee_id'];

        } catch (Exception $e) {
            $this->handleError("Failed to verify email token: " . $e->getMessage());
        }
    }

    /**
     * Get detailed attendee information
     */
    public function getAttendeeDetails($attendeeId) {
        try {
            return $this->attendeeRepo->getAttendeeDetails($attendeeId);

        } catch (Exception $e) {
            $this->handleError("Failed to get attendee details: " . $e->getMessage());
        }
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData($data) {
        $errors = [];

        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Phone validation (basic)
        if (!empty($data['phone']) && !preg_match('/^[0-9\s\-\+\(\)]+$/', $data['phone'])) {
            $errors[] = "Invalid phone number format";
        }

        // Date of birth validation
        if (!empty($data['date_of_birth'])) {
            $dob = strtotime($data['date_of_birth']);
            $age = (time() - $dob) / (365 * 24 * 60 * 60);
            if ($age < 5 || $age > 100) {
                $errors[] = "Invalid date of birth";
            }
        }

        // Grade validation
        if (!empty($data['grade']) && ($data['grade'] < 1 || $data['grade'] > 12)) {
            $errors[] = "Grade must be between 1 and 12";
        }

        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode(", ", $errors));
        }

        return true;
    }
}
