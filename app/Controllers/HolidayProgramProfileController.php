<?php
/**
 * Holiday Program Profile Controller
 *
 * Handles holiday program participant profile management including
 * email verification, password creation, profile viewing, and updates.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 3
 *
 * @package App\Controllers
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/HolidayProgramProfileModel.php';

class HolidayProgramProfileController extends BaseController {
    private $model;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->model = new HolidayProgramProfileModel($this->conn);
    }

    /**
     * Display profile page
     * Modern RESTful method
     */
    public function index() {
        try {
            // Check if user is logged in to holiday program
            if (!$this->isHolidayAuthenticated()) {
                return $this->redirect('/holiday-profile-verify-email.php');
            }

            $attendeeId = $_SESSION['holiday_user_id'] ?? null;
            $profile = $this->getProfile($attendeeId);

            if (!$profile) {
                $this->logger->error("Profile not found for authenticated user", [
                    'attendee_id' => $attendeeId
                ]);
                return $this->view('errors.404', ['error' => 'Profile not found'], 'error');
            }

            $this->logAction('view_holiday_profile', [
                'attendee_id' => $attendeeId
            ]);

            return $this->view('holidayPrograms.profile', [
                'profile' => $profile,
                'welcome' => $this->input('welcome', false)
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load profile page", [
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load profile'
            ], 'error');
        }
    }

    /**
     * Display profile edit form
     * Modern RESTful method
     */
    public function edit() {
        try {
            if (!$this->isHolidayAuthenticated()) {
                return $this->redirect('/holiday-profile-verify-email.php');
            }

            $attendeeId = $_SESSION['holiday_user_id'] ?? null;
            $profile = $this->getProfile($attendeeId);

            return $this->view('holidayPrograms.profileEdit', [
                'profile' => $profile
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to load profile edit page", [
                'error' => $e->getMessage()
            ]);

            return $this->view('errors.500', [
                'error' => 'Failed to load page'
            ], 'error');
        }
    }

    /**
     * Handle profile update
     * Modern RESTful method
     */
    public function update() {
        if (!$this->isHolidayAuthenticated()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
            return;
        }

        $attendeeId = $_SESSION['holiday_user_id'] ?? null;
        $result = $this->updateProfile($attendeeId, $_POST, false);

        $this->jsonResponse($result);
    }

    /**
     * Handle email verification
     * Legacy method - maintained for backward compatibility
     *
     * @param string $email Email address to verify
     * @return array Response with success status and message
     */
    public function verifyEmail($email) {
        // Validate CSRF token using BaseController method
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in email verification", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'email' => $email
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            if (empty($email)) {
                return [
                    'success' => false,
                    'message' => 'Please enter your email address.'
                ];
            }

            $attendee = $this->model->getAttendeeByEmail($email);

            if (!$attendee) {
                $this->logger->info("Email verification failed - not found", [
                    'email' => $email
                ]);

                return [
                    'success' => false,
                    'message' => 'Email not found in our records. Please check your email address or contact support.'
                ];
            }

            // Store attendee info in session for next step
            $_SESSION['profile_setup_attendee_id'] = $attendee['id'];
            $_SESSION['profile_setup_email'] = $attendee['email'];

            $this->logAction('verify_email', [
                'attendee_id' => $attendee['id'],
                'email' => $email,
                'has_password' => !empty($attendee['password'])
            ]);

            // Check if password already exists
            if (!empty($attendee['password'])) {
                return [
                    'success' => true,
                    'redirect' => 'holidayProgramLogin.php?email=' . urlencode($email),
                    'message' => 'Account found. Please login with your password.'
                ];
            } else {
                return [
                    'success' => true,
                    'redirect' => 'holiday-profile-create-password.php',
                    'message' => 'Email verified! Please create your password.'
                ];
            }

        } catch (Exception $e) {
            $this->logger->error("Email verification error", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during verification. Please try again.'
            ];
        }
    }

    /**
     * Handle password creation
     * Legacy method - maintained for backward compatibility
     *
     * @param string $password New password
     * @param string $confirmPassword Password confirmation
     * @return array Response with success status and redirect
     */
    public function createPassword($password, $confirmPassword) {
        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in password creation", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            // Check if user came from email verification
            if (!isset($_SESSION['profile_setup_attendee_id']) || !isset($_SESSION['profile_setup_email'])) {
                return [
                    'success' => false,
                    'message' => 'Session expired. Please start over.',
                    'redirect' => 'holiday-profile-verify-email.php'
                ];
            }

            // Validation
            if (empty($password) || empty($confirmPassword)) {
                return [
                    'success' => false,
                    'message' => 'All fields are required.'
                ];
            }

            if ($password !== $confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'Passwords do not match.'
                ];
            }

            if (strlen($password) < 8) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long.'
                ];
            }

            $attendeeId = $_SESSION['profile_setup_attendee_id'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($this->model->updatePassword($attendeeId, $hashedPassword)) {
                // Get updated user data for session
                $userData = $this->model->getAttendeeProfile($attendeeId);

                if ($userData) {
                    $this->setUserSession($userData);

                    // Clear setup session variables
                    unset($_SESSION['profile_setup_attendee_id']);
                    unset($_SESSION['profile_setup_email']);

                    $this->logAction('create_password', [
                        'attendee_id' => $attendeeId,
                        'success' => true
                    ]);

                    return [
                        'success' => true,
                        'redirect' => 'holiday-profile.php?welcome=1',
                        'message' => 'Password created successfully!'
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to create password. Please try again.'
            ];

        } catch (Exception $e) {
            $this->logger->error("Password creation error", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while creating your password.'
            ];
        }
    }

    /**
     * Get profile data for viewing/editing
     * Legacy method - maintained for backward compatibility
     *
     * @param int|null $attendeeId Attendee ID (defaults to current user)
     * @param bool $isAdmin Whether request is from admin
     * @return array|null Profile data or null if not found
     */
    public function getProfile($attendeeId = null, $isAdmin = false) {
        try {
            // Use current user ID if not specified
            if (!$attendeeId) {
                $attendeeId = $_SESSION['holiday_user_id'] ?? null;
            }

            // Non-admin users can only view their own profile
            if (!$isAdmin && $attendeeId !== ($_SESSION['holiday_user_id'] ?? null)) {
                $this->logger->warning("Unauthorized profile access attempt", [
                    'requested_id' => $attendeeId,
                    'user_id' => $_SESSION['holiday_user_id'] ?? null
                ]);
                return null;
            }

            $profile = $this->model->getAttendeeProfile($attendeeId);

            if ($profile) {
                $this->logAction('get_profile', [
                    'attendee_id' => $attendeeId,
                    'is_admin' => $isAdmin
                ]);
            }

            return $profile;

        } catch (Exception $e) {
            $this->logger->error("Failed to get profile", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Update profile data
     * Legacy method - maintained for backward compatibility
     *
     * @param int $attendeeId Attendee ID
     * @param array $formData Form data from POST
     * @param bool $isAdmin Whether update is from admin
     * @return array Response with success status
     */
    public function updateProfile($attendeeId, $formData, $isAdmin = false) {
        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->logger->warning("CSRF validation failed in profile update", [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'attendee_id' => $attendeeId
            ]);

            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            // Prepare update data
            $updateData = $this->prepareUpdateData($formData, $isAdmin);

            // Validate required fields
            if (empty($updateData['first_name']) || empty($updateData['last_name']) || empty($updateData['email'])) {
                return [
                    'success' => false,
                    'message' => 'First name, last name, and email are required.'
                ];
            }

            // Validate email format
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email address.'
                ];
            }

            // Check email uniqueness
            if (!$this->model->isEmailUnique($updateData['email'], $attendeeId)) {
                return [
                    'success' => false,
                    'message' => 'Email address is already in use by another attendee.'
                ];
            }

            // Update profile
            if ($this->model->updateProfile($attendeeId, $updateData)) {
                // Update session if updating own profile
                $currentUserId = $_SESSION['holiday_user_id'] ?? null;
                if ($attendeeId === $currentUserId) {
                    $_SESSION['holiday_name'] = $updateData['first_name'];
                    $_SESSION['holiday_surname'] = $updateData['last_name'];
                    $_SESSION['holiday_email'] = $updateData['email'];
                }

                // Log the update
                $this->model->logProfileUpdate($attendeeId, $currentUserId, $updateData);

                $this->logAction('update_profile', [
                    'attendee_id' => $attendeeId,
                    'is_admin' => $isAdmin,
                    'fields_updated' => array_keys($updateData)
                ]);

                return [
                    'success' => true,
                    'message' => 'Profile updated successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.'
                ];
            }

        } catch (Exception $e) {
            $this->logger->error("Profile update error", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while updating your profile.'
            ];
        }
    }

    /**
     * Prepare update data from form submission
     * Private helper method
     *
     * @param array $formData Form data
     * @param bool $isAdmin Whether admin fields should be included
     * @return array Sanitized update data
     */
    private function prepareUpdateData($formData, $isAdmin) {
        $updateData = [
            'first_name' => trim($formData['first_name'] ?? ''),
            'last_name' => trim($formData['last_name'] ?? ''),
            'email' => trim($formData['email'] ?? ''),
            'phone' => trim($formData['phone'] ?? ''),
            'date_of_birth' => $formData['date_of_birth'] ?: null,
            'gender' => $formData['gender'] ?: null,
            'school' => trim($formData['school'] ?? ''),
            'grade' => $formData['grade'] ? intval($formData['grade']) : null,
            'address' => trim($formData['address'] ?? ''),
            'city' => trim($formData['city'] ?? ''),
            'province' => trim($formData['province'] ?? ''),
            'postal_code' => trim($formData['postal_code'] ?? ''),
            'guardian_name' => trim($formData['guardian_name'] ?? ''),
            'guardian_relationship' => trim($formData['guardian_relationship'] ?? ''),
            'guardian_phone' => trim($formData['guardian_phone'] ?? ''),
            'guardian_email' => trim($formData['guardian_email'] ?? ''),
            'emergency_contact_name' => trim($formData['emergency_contact_name'] ?? ''),
            'emergency_contact_relationship' => trim($formData['emergency_contact_relationship'] ?? ''),
            'emergency_contact_phone' => trim($formData['emergency_contact_phone'] ?? ''),
            'why_interested' => trim($formData['why_interested'] ?? ''),
            'experience_level' => $formData['experience_level'] ?: null,
            'needs_equipment' => isset($formData['needs_equipment']) ? 1 : 0,
            'medical_conditions' => trim($formData['medical_conditions'] ?? ''),
            'allergies' => trim($formData['allergies'] ?? ''),
            'dietary_restrictions' => trim($formData['dietary_restrictions'] ?? ''),
            'photo_permission' => isset($formData['photo_permission']) ? 1 : 0,
            'data_permission' => isset($formData['data_permission']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Admin-only fields
        if ($isAdmin) {
            $updateData['registration_status'] = $formData['registration_status'] ?? 'pending';
            $updateData['status'] = $formData['status'] ?? 'pending';
            $updateData['mentor_status'] = $formData['mentor_status'] ?: null;
            $updateData['is_clubhouse_member'] = intval($formData['is_clubhouse_member'] ?? 0);
            $updateData['additional_notes'] = trim($formData['additional_notes'] ?? '');
        }

        return $updateData;
    }

    /**
     * Set user session variables
     * Private helper method for session management
     *
     * @param array $userData User data from database
     * @return void
     */
    private function setUserSession($userData) {
        $_SESSION['holiday_logged_in'] = true;
        $_SESSION['holiday_user_id'] = $userData['id'];
        $_SESSION['holiday_email'] = $userData['email'];
        $_SESSION['holiday_name'] = $userData['first_name'];
        $_SESSION['holiday_surname'] = $userData['last_name'];
        $_SESSION['holiday_is_mentor'] = $userData['mentor_registration'] ?? false;
        $_SESSION['holiday_mentor_status'] = $userData['mentor_status'];
        $_SESSION['holiday_program_id'] = $userData['program_id'];
        $_SESSION['holiday_user_type'] = $userData['mentor_registration'] ? 'mentor' : 'member';
        $_SESSION['holiday_is_admin'] = false;

        $this->logAction('set_user_session', [
            'attendee_id' => $userData['id'],
            'user_type' => $_SESSION['holiday_user_type']
        ]);
    }

    /**
     * Check if user is authenticated in holiday program
     *
     * @return bool True if authenticated
     */
    private function isHolidayAuthenticated() {
        return isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'];
    }

    /**
     * Change password for authenticated user
     * Modern method for password management
     *
     * @return array Response with success status
     */
    public function changePassword() {
        if (!$this->isHolidayAuthenticated()) {
            return [
                'success' => false,
                'message' => 'Not authenticated'
            ];
        }

        if (!$this->validateCSRF()) {
            return [
                'success' => false,
                'message' => 'Security validation failed',
                'code' => 'CSRF_ERROR'
            ];
        }

        try {
            $attendeeId = $_SESSION['holiday_user_id'];
            $currentPassword = $this->input('current_password');
            $newPassword = $this->input('new_password');
            $confirmPassword = $this->input('confirm_password');

            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }

            if ($newPassword !== $confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'New passwords do not match'
                ];
            }

            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long'
                ];
            }

            // Verify current password
            $profile = $this->model->getAttendeeProfile($attendeeId);
            if (!password_verify($currentPassword, $profile['password'])) {
                $this->logger->warning("Password change failed - incorrect current password", [
                    'attendee_id' => $attendeeId
                ]);

                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($this->model->updatePassword($attendeeId, $hashedPassword)) {
                $this->logAction('change_password', [
                    'attendee_id' => $attendeeId,
                    'success' => true
                ]);

                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to change password'
            ];

        } catch (Exception $e) {
            $this->logger->error("Password change error", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while changing password'
            ];
        }
    }
}
?>
