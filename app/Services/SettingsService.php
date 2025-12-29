<?php
/**
 * Settings Service - Business logic for user settings and profile management
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../../core/SecureFileUploader.php';
require_once __DIR__ . '/CacheManager.php'; // Phase 3 Week 9 - Caching

class SettingsService extends BaseService {
    private $userModel;
    private $fileUploader;
    private $cache; // Phase 3 Week 9 - Cache manager

    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->userModel = new UserModel($this->conn);
        $this->fileUploader = new SecureFileUploader();
        $this->cache = new CacheManager(); // Phase 3 Week 9 - Initialize cache
    }

    /**
     * Get user settings/profile data
     *
     * @param int $userId User ID
     * @return array|null User profile data or null if not found
     */
    public function getUserProfile($userId) {
        try {
            $this->logAction('get_user_profile', ['user_id' => $userId]);

            // PHASE 3 WEEK 9 - CACHING: Cache user profile for 1 hour
            $cacheKey = "user_profile_{$userId}";

            return $this->cache->remember($cacheKey, 3600, function() use ($userId) {
                $user = $this->userModel->getUserById($userId);

                if (!$user) {
                    throw new Exception("User not found with ID: {$userId}");
                }

                // Remove sensitive data before returning
                unset($user['password']);
                unset($user['session_token']);
                unset($user['verification_token']);

                return $user;
            });

        } catch (Exception $e) {
            $this->handleError("Failed to get user profile: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Update user profile information
     *
     * @param int $userId User ID
     * @param array $data Profile data to update
     * @return bool Success status
     */
    public function updateProfile($userId, $data) {
        try {
            $this->logAction('update_profile', ['user_id' => $userId]);

            // Validate the user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found with ID: {$userId}");
            }

            // Validate profile data
            $this->validateProfileData($data, $userId);

            // Sanitize all input data
            $data = $this->sanitizeProfileData($data);

            // Add user ID to data
            $data['id'] = $userId;

            // Remove password from profile update if empty
            if (empty($data['password'])) {
                unset($data['password']);
            }

            // Update the user
            $result = $this->userModel->updateUser($data);

            if ($result) {
                $this->logAction('profile_updated_success', ['user_id' => $userId]);

                // PHASE 3 WEEK 9 - CACHING: Invalidate user profile cache
                $this->cache->delete("user_profile_{$userId}");
                $this->cache->delete("dashboard_data_{$userId}"); // Dashboard shows user info

                return true;
            }

            throw new Exception("Failed to update user profile");

        } catch (Exception $e) {
            $this->handleError("Profile update failed: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Update user password with validation
     *
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool Success status
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $this->logAction('update_password_attempt', ['user_id' => $userId]);

            // Get user
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Verify current password
            if (!$this->verifyCurrentPassword($userId, $currentPassword)) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password strength
            $this->validatePasswordStrength($newPassword);

            // Check new password is different from current
            if ($currentPassword === $newPassword) {
                throw new Exception("New password must be different from current password");
            }

            // Update password
            $result = $this->userModel->update($userId, [
                'password' => $newPassword // Will be hashed by UserModel
            ]);

            if ($result) {
                $this->logAction('password_updated_success', ['user_id' => $userId]);
                return true;
            }

            throw new Exception("Failed to update password");

        } catch (Exception $e) {
            $this->handleError("Password update failed: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Upload profile image with security validation
     *
     * @param int $userId User ID
     * @param array $file Uploaded file from $_FILES
     * @return array Upload result with path and URL
     */
    public function uploadProfileImage($userId, $file) {
        try {
            $this->logAction('upload_profile_image', ['user_id' => $userId]);

            // Validate user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Validate it's an image file
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedTypes)) {
                throw new Exception("Only image files (JPG, PNG, GIF) are allowed for profile pictures");
            }

            // Upload file using SecureFileUploader
            $uploadResult = $this->fileUploader->upload($file);

            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['error'] ?? 'Failed to upload image');
            }

            // Delete old profile image if exists
            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                @unlink($user['profile_image']);
            }

            // Update user profile_image field
            $this->userModel->update($userId, [
                'profile_image' => $uploadResult['path']
            ]);

            $this->logAction('profile_image_uploaded', [
                'user_id' => $userId,
                'path' => $uploadResult['path']
            ]);

            return $uploadResult;

        } catch (Exception $e) {
            $this->handleError("Profile image upload failed: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Update notification preferences
     *
     * @param int $userId User ID
     * @param array $preferences Notification preferences
     * @return bool Success status
     */
    public function updateNotifications($userId, $preferences) {
        try {
            $this->logAction('update_notifications', ['user_id' => $userId]);

            // Validate user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            // Sanitize preferences
            $sanitized = [
                'email_notifications' => isset($preferences['email_notifications']) ? (bool)$preferences['email_notifications'] : true,
                'sms_notifications' => isset($preferences['sms_notifications']) ? (bool)$preferences['sms_notifications'] : false,
                'push_notifications' => isset($preferences['push_notifications']) ? (bool)$preferences['push_notifications'] : true,
                'newsletter' => isset($preferences['newsletter']) ? (bool)$preferences['newsletter'] : true,
                'event_reminders' => isset($preferences['event_reminders']) ? (bool)$preferences['event_reminders'] : true,
                'course_updates' => isset($preferences['course_updates']) ? (bool)$preferences['course_updates'] : true
            ];

            // Store as JSON in a notification_preferences field
            // Note: This assumes a notification_preferences column exists
            // If not, we can extend the users table or create a separate table
            $preferencesJson = json_encode($sanitized);

            // For now, we'll store in a custom field or skip if field doesn't exist
            // This is a placeholder for future implementation
            $this->logAction('notifications_updated', [
                'user_id' => $userId,
                'preferences' => $sanitized
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleError("Failed to update notifications: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Get notification preferences for user
     *
     * @param int $userId User ID
     * @return array Notification preferences
     */
    public function getNotificationPreferences($userId) {
        try {
            // Default preferences
            $defaults = [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'newsletter' => true,
                'event_reminders' => true,
                'course_updates' => true
            ];

            // In future, retrieve from database
            // For now, return defaults
            return $defaults;

        } catch (Exception $e) {
            $this->logger->error("Failed to get notification preferences", ['user_id' => $userId]);
            return [];
        }
    }

    /**
     * Validate profile data comprehensively
     *
     * @param array $data Profile data to validate
     * @param int $userId Current user ID (for email/username uniqueness check)
     * @throws Exception If validation fails
     */
    public function validateProfileData($data, $userId) {
        // Required fields
        $required = ['name', 'surname', 'username', 'email'];
        $this->validateRequired($data, $required);

        // Name validation
        if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
            throw new Exception("Name must be between 2 and 50 characters");
        }

        if (strlen($data['surname']) < 2 || strlen($data['surname']) > 50) {
            throw new Exception("Surname must be between 2 and 50 characters");
        }

        // Username validation
        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $data['username'])) {
            throw new Exception("Username must be 3-30 characters and contain only letters, numbers, hyphens, and underscores");
        }

        // Check username uniqueness
        $existingUser = $this->userModel->findByUsername($data['username']);
        if ($existingUser && $existingUser['id'] != $userId) {
            throw new Exception("Username is already taken");
        }

        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        // Check email uniqueness
        $existingEmail = $this->userModel->findByEmail($data['email']);
        if ($existingEmail && $existingEmail['id'] != $userId) {
            throw new Exception("Email is already registered");
        }

        // Date of birth validation (if provided)
        if (!empty($data['dob'])) {
            $dob = strtotime($data['dob']);
            $age = (time() - $dob) / (365 * 24 * 60 * 60);

            if ($age < 5 || $age > 120) {
                throw new Exception("Invalid date of birth");
            }
        }

        // ID number validation (if provided and South African)
        if (!empty($data['id_number'])) {
            if (!preg_match('/^\d{13}$/', $data['id_number'])) {
                throw new Exception("SA ID number must be exactly 13 digits");
            }
        }

        // Phone number validation
        if (!empty($data['cell_number'])) {
            if (!preg_match('/^\d{10,15}$/', preg_replace('/[\s\-\(\)]/', '', $data['cell_number']))) {
                throw new Exception("Invalid phone number format");
            }
        }

        if (!empty($data['parent_number'])) {
            if (!preg_match('/^\d{10,15}$/', preg_replace('/[\s\-\(\)]/', '', $data['parent_number']))) {
                throw new Exception("Invalid parent phone number format");
            }
        }

        // Parent email validation (if provided)
        if (!empty($data['parent_email'])) {
            if (!filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid parent email address");
            }
        }

        // Postal code validation (if provided)
        if (!empty($data['address_postal_code'])) {
            if (!preg_match('/^\d{4}$/', $data['address_postal_code'])) {
                throw new Exception("Postal code must be 4 digits");
            }
        }

        // Grade validation (if member)
        if (isset($data['grade']) && !empty($data['grade'])) {
            $grade = (int)$data['grade'];
            if ($grade < 1 || $grade > 12) {
                throw new Exception("Grade must be between 1 and 12");
            }
        }

        return true;
    }

    /**
     * Sanitize profile data to prevent XSS
     *
     * @param array $data Raw profile data
     * @return array Sanitized data
     */
    private function sanitizeProfileData($data) {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Don't sanitize password (will be hashed)
                if ($key === 'password') {
                    $sanitized[$key] = $value;
                } else {
                    $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Verify current password
     *
     * @param int $userId User ID
     * @param string $password Password to verify
     * @return bool True if password is correct
     */
    private function verifyCurrentPassword($userId, $password) {
        $user = $this->userModel->getUserById($userId);

        if (!$user) {
            return false;
        }

        // Check if password is hashed (modern approach)
        if (password_verify($password, $user['password'])) {
            return true;
        }

        // Legacy password handling (MD5)
        if (strlen($user['password']) === 32 && ctype_xdigit($user['password'])) {
            return md5($password) === $user['password'];
        }

        return false;
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @throws Exception If password doesn't meet requirements
     */
    private function validatePasswordStrength($password) {
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if (strlen($password) > 128) {
            throw new Exception("Password must not exceed 128 characters");
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            throw new Exception("Password must contain at least one special character (!@#$%^&*(),.?\":{}|<>)");
        }

        // Check for common weak passwords
        $weakPasswords = [
            'Password1!', 'Welcome1!', 'Admin123!', 'Qwerty123!',
            'Abc123!@#', 'Password123!', '12345678!A'
        ];

        if (in_array($password, $weakPasswords)) {
            throw new Exception("This password is too common. Please choose a stronger password");
        }

        return true;
    }

    /**
     * Delete profile image
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteProfileImage($userId) {
        try {
            $this->logAction('delete_profile_image', ['user_id' => $userId]);

            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Delete the file if it exists
            if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                if (!@unlink($user['profile_image'])) {
                    $this->logger->warning('Failed to delete profile image file', [
                        'user_id' => $userId,
                        'path' => $user['profile_image']
                    ]);
                }
            }

            // Update database
            $result = $this->userModel->update($userId, [
                'profile_image' => null
            ]);

            if ($result) {
                $this->logAction('profile_image_deleted', ['user_id' => $userId]);
                return true;
            }

            throw new Exception("Failed to delete profile image from database");

        } catch (Exception $e) {
            $this->handleError("Failed to delete profile image: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }

    /**
     * Get user activity summary for settings page
     *
     * @param int $userId User ID
     * @return array Activity summary
     */
    public function getUserActivitySummary($userId) {
        try {
            $summary = [
                'last_login' => null,
                'total_logins' => 0,
                'account_created' => null,
                'profile_completeness' => 0
            ];

            $user = $this->userModel->getUserById($userId);

            if ($user) {
                $summary['last_login'] = $user['last_login'] ?? null;
                $summary['account_created'] = $user['created_at'] ?? null;
                $summary['profile_completeness'] = $this->calculateProfileCompleteness($user);
            }

            return $summary;

        } catch (Exception $e) {
            $this->logger->error("Failed to get activity summary", ['user_id' => $userId]);
            return [
                'last_login' => null,
                'total_logins' => 0,
                'account_created' => null,
                'profile_completeness' => 0
            ];
        }
    }

    /**
     * Calculate profile completeness percentage
     *
     * @param array $user User data
     * @return int Completeness percentage (0-100)
     */
    private function calculateProfileCompleteness($user) {
        $fields = [
            'name', 'surname', 'username', 'email', 'Gender', 'date_of_birth',
            'nationality', 'home_language', 'address_street', 'address_city',
            'address_province', 'address_postal_code', 'profile_image'
        ];

        // Add member-specific fields
        if ($user['user_type'] === 'member') {
            $fields = array_merge($fields, [
                'school', 'grade', 'parent', 'parent_email', 'parent_number'
            ]);
        }

        $completed = 0;
        $total = count($fields);

        foreach ($fields as $field) {
            if (!empty($user[$field])) {
                $completed++;
            }
        }

        return round(($completed / $total) * 100);
    }

    /**
     * Export user data (GDPR compliance)
     *
     * @param int $userId User ID
     * @return array User data for export
     */
    public function exportUserData($userId) {
        try {
            $this->logAction('export_user_data', ['user_id' => $userId]);

            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Remove sensitive fields
            unset($user['password']);
            unset($user['session_token']);
            unset($user['verification_token']);

            return [
                'export_date' => date('Y-m-d H:i:s'),
                'user_data' => $user
            ];

        } catch (Exception $e) {
            $this->handleError("Failed to export user data: " . $e->getMessage(), ['user_id' => $userId]);
        }
    }
}
