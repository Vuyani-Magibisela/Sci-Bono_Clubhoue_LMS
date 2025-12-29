<?php
/**
 * Settings Controller - User profile and settings management
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/SettingsService.php';
require_once __DIR__ . '/../../core/CSRF.php';

class SettingsController extends BaseController {
    private $settingsService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->settingsService = new SettingsService($this->conn);
    }

    /**
     * Display user settings page
     *
     * Route: GET /settings
     * Middleware: AuthMiddleware
     */
    public function index() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            // Get user profile
            $profile = $this->settingsService->getUserProfile($userId);

            // Get notification preferences
            $notifications = $this->settingsService->getNotificationPreferences($userId);

            // Get activity summary
            $activitySummary = $this->settingsService->getUserActivitySummary($userId);

            $data = [
                'pageTitle' => 'Settings',
                'currentPage' => 'settings',
                'user' => $this->currentUser(),
                'profile' => $profile,
                'notifications' => $notifications,
                'activitySummary' => $activitySummary
            ];

            $this->logAction('settings_view', ['user_id' => $userId]);

            return $this->view('member.settings.index', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Settings page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load settings'], 'error');
        }
    }

    /**
     * Show edit profile page
     *
     * Route: GET /settings/profile
     * Middleware: AuthMiddleware
     */
    public function editProfile() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $profile = $this->settingsService->getUserProfile($userId);

            $data = [
                'pageTitle' => 'Edit Profile',
                'currentPage' => 'settings',
                'user' => $this->currentUser(),
                'profile' => $profile
            ];

            return $this->view('member.settings.profile', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Edit profile page load failed: " . $e->getMessage());
            return $this->redirectWithError('/settings', 'Failed to load profile editor');
        }
    }

    /**
     * Update user profile
     *
     * Route: POST /settings/profile
     * Middleware: AuthMiddleware, CSRF
     */
    public function updateProfile() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];
            $data = $this->input();

            // Update profile
            $result = $this->settingsService->updateProfile($userId, $data);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Profile updated successfully');
                } else {
                    return $this->redirectWithSuccess('/settings', 'Profile updated successfully');
                }
            }

            throw new Exception("Failed to update profile");

        } catch (Exception $e) {
            $this->logger->error("Profile update failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/settings/profile', $e->getMessage());
            }
        }
    }

    /**
     * Show change password page
     *
     * Route: GET /settings/password
     * Middleware: AuthMiddleware
     */
    public function editPassword() {
        $this->requireAuth();

        $data = [
            'pageTitle' => 'Change Password',
            'currentPage' => 'settings',
            'user' => $this->currentUser()
        ];

        return $this->view('member.settings.password', $data, 'app');
    }

    /**
     * Update user password
     *
     * Route: POST /settings/password
     * Middleware: AuthMiddleware, CSRF
     */
    public function updatePassword() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];
            $currentPassword = $this->input('current_password');
            $newPassword = $this->input('new_password');
            $confirmPassword = $this->input('confirm_password');

            // Validate passwords match
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }

            // Update password
            $result = $this->settingsService->updatePassword($userId, $currentPassword, $newPassword);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Password updated successfully');
                } else {
                    return $this->redirectWithSuccess('/settings', 'Password updated successfully');
                }
            }

            throw new Exception("Failed to update password");

        } catch (Exception $e) {
            $this->logger->error("Password update failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/settings/password', $e->getMessage());
            }
        }
    }

    /**
     * Upload profile image
     *
     * Route: POST /settings/profile-image
     * Middleware: AuthMiddleware, CSRF
     */
    public function uploadProfileImage() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];
            $file = $this->file('profile_image');

            if (!$file) {
                throw new Exception("No file uploaded");
            }

            // Upload image
            $result = $this->settingsService->uploadProfileImage($userId, $file);

            if ($result && $result['success']) {
                return $this->jsonSuccess([
                    'url' => $result['url'],
                    'path' => $result['path']
                ], 'Profile image uploaded successfully');
            }

            throw new Exception($result['error'] ?? 'Failed to upload image');

        } catch (Exception $e) {
            $this->logger->error("Profile image upload failed: " . $e->getMessage());
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Delete profile image
     *
     * Route: DELETE /settings/profile-image
     * Middleware: AuthMiddleware, CSRF
     */
    public function deleteProfileImage() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];

            $result = $this->settingsService->deleteProfileImage($userId);

            if ($result) {
                return $this->jsonSuccess(null, 'Profile image deleted successfully');
            }

            throw new Exception("Failed to delete profile image");

        } catch (Exception $e) {
            $this->logger->error("Profile image deletion failed: " . $e->getMessage());
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Show notification settings page
     *
     * Route: GET /settings/notifications
     * Middleware: AuthMiddleware
     */
    public function editNotifications() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $notifications = $this->settingsService->getNotificationPreferences($userId);

            $data = [
                'pageTitle' => 'Notification Settings',
                'currentPage' => 'settings',
                'user' => $this->currentUser(),
                'notifications' => $notifications
            ];

            return $this->view('member.settings.notifications', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Notification settings load failed: " . $e->getMessage());
            return $this->redirectWithError('/settings', 'Failed to load notification settings');
        }
    }

    /**
     * Update notification preferences
     *
     * Route: POST /settings/notifications
     * Middleware: AuthMiddleware, CSRF
     */
    public function updateNotifications() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];
            $preferences = $this->input();

            $result = $this->settingsService->updateNotifications($userId, $preferences);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Notification preferences updated');
                } else {
                    return $this->redirectWithSuccess('/settings', 'Notification preferences updated');
                }
            }

            throw new Exception("Failed to update notification preferences");

        } catch (Exception $e) {
            $this->logger->error("Notification update failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/settings/notifications', $e->getMessage());
            }
        }
    }

    /**
     * Get user profile via AJAX
     *
     * Route: GET /settings/profile/data
     * Middleware: AuthMiddleware
     */
    public function getProfileData() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $profile = $this->settingsService->getUserProfile($userId);

            return $this->jsonSuccess($profile, 'Profile data retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load profile: ' . $e->getMessage());
        }
    }

    /**
     * Get activity summary via AJAX
     *
     * Route: GET /settings/activity
     * Middleware: AuthMiddleware
     */
    public function getActivitySummary() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $summary = $this->settingsService->getUserActivitySummary($userId);

            return $this->jsonSuccess($summary, 'Activity summary retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load activity summary: ' . $e->getMessage());
        }
    }

    /**
     * Export user data (GDPR compliance)
     *
     * Route: GET /settings/export
     * Middleware: AuthMiddleware
     */
    public function exportData() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $data = $this->settingsService->exportUserData($userId);

            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="user_data_' . $userId . '_' . date('Y-m-d') . '.json"');

            echo json_encode($data, JSON_PRETTY_PRINT);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Data export failed: " . $e->getMessage());
            return $this->redirectWithError('/settings', 'Failed to export data');
        }
    }

    /**
     * Show account deletion confirmation page
     *
     * Route: GET /settings/delete-account
     * Middleware: AuthMiddleware
     */
    public function showDeleteAccount() {
        $this->requireAuth();

        $data = [
            'pageTitle' => 'Delete Account',
            'currentPage' => 'settings',
            'user' => $this->currentUser()
        ];

        return $this->view('member.settings.delete-account', $data, 'app');
    }

    /**
     * Delete user account (requires admin or self)
     *
     * Route: DELETE /settings/account
     * Middleware: AuthMiddleware, CSRF
     */
    public function deleteAccount() {
        $this->requireAuth();
        $this->validateCsrfToken();

        try {
            $userId = $this->currentUser()['id'];
            $password = $this->input('password');

            // Verify password before deletion
            if (!$this->settingsService->verifyCurrentPassword($userId, $password)) {
                throw new Exception("Password incorrect");
            }

            // Perform deletion (this should be handled by UserService/AdminService)
            // For now, return not implemented
            throw new Exception("Account deletion requires administrator approval. Please contact support.");

        } catch (Exception $e) {
            $this->logger->error("Account deletion failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/settings', $e->getMessage());
            }
        }
    }
}
