<?php
/**
 * Api\UserController
 *
 * Handles API user profile operations
 *
 * Phase 5 Week 2 Day 1: User Profile API Implementation
 * Created: November 11, 2025 (stub)
 * Implemented: January 8, 2026
 *
 * Purpose: REST API for user profile access and updates
 * - GET /api/v1/user/profile - Get authenticated user's profile
 * - PUT /api/v1/user/profile - Update profile (name, surname, email, phone)
 * - PUT /api/v1/user/password - Change password with current password verification
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 2 Day 1
 */

namespace App\Controllers\Api;

require_once __DIR__ . '/../../API/BaseApiController.php';
require_once __DIR__ . '/../../Services/SettingsService.php';

use App\API\BaseApiController;
use App\Services\SettingsService;

class UserController extends BaseApiController {

    private $settingsService;

    public function __construct() {
        parent::__construct();
        $this->settingsService = new SettingsService($this->db);
    }

    /**
     * GET /api/v1/user/profile
     *
     * Get authenticated user's profile information
     *
     * @return JSON response with user profile data
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "username": "john_doe",
     *     "email": "john@example.com",
     *     "name": "John",
     *     "surname": "Doe",
     *     "user_type": "member",
     *     "phone": "0123456789",
     *     "date_of_birth": "1990-01-15",
     *     "gender": "male",
     *     "profile_image": "/uploads/profiles/john_doe.jpg",
     *     "created_at": "2025-01-01 10:00:00",
     *     "last_login": "2026-01-08 09:30:00"
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No valid authentication token
     */
    public function profile() {
        try {
            // Authentication is handled by AuthMiddleware
            // Get user ID from JWT payload or session
            $userId = $this->getUserIdFromAuth();

            if (!$userId) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get user profile from SettingsService
            $profile = $this->settingsService->getUserProfile($userId);

            if (!$profile) {
                return $this->errorResponse('User profile not found', 404);
            }

            // Remove sensitive data
            unset($profile['password']);

            // Log activity
            $this->logAction('api_profile_viewed', [
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            return $this->successResponse($profile, 'Profile retrieved successfully');

        } catch (\Exception $e) {
            error_log("UserController::profile() error: " . $e->getMessage());
            return $this->errorResponse('Failed to retrieve profile', 500);
        }
    }

    /**
     * PUT /api/v1/user/profile
     *
     * Update authenticated user's profile information
     *
     * Request body:
     * {
     *   "name": "John",
     *   "surname": "Doe",
     *   "email": "john@example.com",
     *   "phone": "0123456789",
     *   "date_of_birth": "1990-01-15",
     *   "gender": "male"
     * }
     *
     * @return JSON response with updated profile
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "username": "john_doe",
     *     "email": "john@example.com",
     *     "name": "John",
     *     "surname": "Doe",
     *     ...
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No valid authentication token
     * - 422 Unprocessable Entity: Validation errors
     * - 409 Conflict: Email already in use by another user
     */
    public function updateProfile() {
        try {
            // Authentication is handled by AuthMiddleware
            $userId = $this->getUserIdFromAuth();

            if (!$userId) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Parse JSON request body
            $data = $this->requestData;

            // Validate required fields
            $allowedFields = ['name', 'surname', 'email', 'phone', 'date_of_birth', 'gender'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                return $this->errorResponse('No valid fields provided for update', 422, [
                    'allowed_fields' => $allowedFields
                ]);
            }

            // Validate profile data using SettingsService
            $validation = $this->settingsService->validateProfileData($updateData, $userId);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 422, [
                    'errors' => $validation['errors']
                ]);
            }

            // Update profile
            $result = $this->settingsService->updateProfile($userId, $updateData);

            if (!$result['success']) {
                return $this->errorResponse($result['error'] ?? 'Failed to update profile', 500);
            }

            // Get updated profile
            $profile = $this->settingsService->getUserProfile($userId);
            unset($profile['password']);

            // Log activity
            $this->logAction('api_profile_updated', [
                'user_id' => $userId,
                'updated_fields' => array_keys($updateData),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            return $this->successResponse($profile, 'Profile updated successfully');

        } catch (\Exception $e) {
            error_log("UserController::updateProfile() error: " . $e->getMessage());
            return $this->errorResponse('Failed to update profile', 500);
        }
    }

    /**
     * PUT /api/v1/user/password
     *
     * Change authenticated user's password
     * Requires current password verification for security
     *
     * Request body:
     * {
     *   "current_password": "oldpassword123",
     *   "new_password": "newpassword456",
     *   "new_password_confirmation": "newpassword456"
     * }
     *
     * @return JSON response with success message
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "message": "Password changed successfully. Please login again with your new password."
     * }
     *
     * Errors:
     * - 401 Unauthorized: No valid authentication token
     * - 422 Unprocessable Entity: Validation errors
     * - 400 Bad Request: Current password incorrect
     * - 400 Bad Request: Password confirmation doesn't match
     */
    public function updatePassword() {
        try {
            // Authentication is handled by AuthMiddleware
            $userId = $this->getUserIdFromAuth();

            if (!$userId) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Parse JSON request body
            $data = $this->requestData;

            // Validate required fields
            $requiredFields = ['current_password', 'new_password', 'new_password_confirmation'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->errorResponse('Missing required fields', 422, [
                    'missing_fields' => $missingFields
                ]);
            }

            // Validate password confirmation
            if ($data['new_password'] !== $data['new_password_confirmation']) {
                return $this->errorResponse('Password confirmation does not match', 400, [
                    'field' => 'new_password_confirmation'
                ]);
            }

            // Validate password strength (minimum 8 characters)
            if (strlen($data['new_password']) < 8) {
                return $this->errorResponse('New password must be at least 8 characters long', 400, [
                    'field' => 'new_password',
                    'min_length' => 8
                ]);
            }

            // Update password using SettingsService
            $result = $this->settingsService->updatePassword(
                $userId,
                $data['current_password'],
                $data['new_password']
            );

            if (!$result['success']) {
                $statusCode = ($result['error'] === 'Current password is incorrect') ? 400 : 500;
                return $this->errorResponse($result['error'], $statusCode);
            }

            // Log activity
            $this->logAction('api_password_changed', [
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            return $this->successResponse(null, 'Password changed successfully. Please login again with your new password.');

        } catch (\Exception $e) {
            error_log("UserController::updatePassword() error: " . $e->getMessage());
            return $this->errorResponse('Failed to change password', 500);
        }
    }

    /**
     * Helper method to get user ID from authentication
     * Works with both JWT (AuthMiddleware) and session
     *
     * @return int|null User ID or null if not authenticated
     */
    private function getUserIdFromAuth() {
        // Try to get from session (populated by AuthMiddleware for both JWT and session auth)
        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Helper method to log actions
     *
     * @param string $action Action identifier
     * @param array $metadata Additional metadata
     */
    private function logAction($action, $metadata = []) {
        try {
            // Use existing activity log table
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, action, metadata, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $userId = $metadata['user_id'] ?? null;
            $ipAddress = $metadata['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $metadata['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadataJson = json_encode($metadata);

            $stmt->bind_param('issss', $userId, $action, $metadataJson, $ipAddress, $userAgent);
            $stmt->execute();
        } catch (\Exception $e) {
            // Log but don't fail the request
            error_log("Failed to log action: " . $e->getMessage());
        }
    }
}
