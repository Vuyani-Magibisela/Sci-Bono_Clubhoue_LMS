<?php
require_once __DIR__ . '/../Models/HolidayProgramProfileModel.php';

class HolidayProgramProfileController {
    private $conn;
    private $model;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new HolidayProgramProfileModel($conn);
    }
    
    /**
     * Handle email verification
     */
    public function verifyEmail($email) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramProfileController::verifyEmail - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

        if (empty($email)) {
            return [
                'success' => false,
                'message' => 'Please enter your email address.'
            ];
        }
        
        $attendee = $this->model->getAttendeeByEmail($email);
        
        if (!$attendee) {
            return [
                'success' => false,
                'message' => 'Email not found in our records. Please check your email address or contact support.'
            ];
        }
        
        // Store attendee info in session for next step
        $_SESSION['profile_setup_attendee_id'] = $attendee['id'];
        $_SESSION['profile_setup_email'] = $attendee['email'];
        
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
    }
    
    /**
     * Handle password creation
     */
    public function createPassword($password, $confirmPassword) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramProfileController::createPassword - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return [
                'success' => false,
                'message' => 'Security validation failed. Please refresh the page and try again.',
                'code' => 'CSRF_ERROR'
            ];
        }

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
    }
    
    /**
     * Get profile data for viewing/editing
     */
    public function getProfile($attendeeId = null, $isAdmin = false) {
        // Use current user ID if not specified
        if (!$attendeeId) {
            $attendeeId = $_SESSION['holiday_user_id'] ?? null;
        }
        
        // Non-admin users can only view their own profile
        if (!$isAdmin && $attendeeId !== ($_SESSION['holiday_user_id'] ?? null)) {
            return null;
        }
        
        return $this->model->getAttendeeProfile($attendeeId);
    }
    
    /**
     * Update profile data
     */
    public function updateProfile($attendeeId, $formData, $isAdmin = false) {
        // Validate CSRF token
        require_once __DIR__ . '/../../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in HolidayProgramProfileController::updateProfile - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
            error_log("Profile update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while updating your profile.'
            ];
        }
    }
    
    /**
     * Prepare update data from form submission
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
    }
}