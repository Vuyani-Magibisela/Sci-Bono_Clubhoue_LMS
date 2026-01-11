<?php
/**
 * Api\Admin\UserController - API Admin User Management
 *
 * Phase 5 Week 2 Day 2: Admin User Management API Implementation
 * Created: November 11, 2025 (stub)
 * Implemented: January 8, 2026
 *
 * Purpose: REST API for admin user management (CRUD operations)
 * - GET /api/v1/admin/users - List users with pagination and filters
 * - GET /api/v1/admin/users/{id} - View user details
 * - POST /api/v1/admin/users - Create new user (Day 3)
 * - PUT /api/v1/admin/users/{id} - Update user (Day 3)
 * - DELETE /api/v1/admin/users/{id} - Delete user (Day 4)
 *
 * @package App\Controllers\Api\Admin
 * @since Phase 5 Week 2 Day 2
 */

namespace App\Controllers\Api\Admin;

require_once __DIR__ . '/../../../API/BaseApiController.php';
require_once __DIR__ . '/../../../Repositories/UserRepository.php';
require_once __DIR__ . '/../../../Services/UserService.php';

use App\API\BaseApiController;

class UserController extends BaseApiController {

    private $userRepository;
    private $userService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new \UserRepository($this->db);
        $this->userService = new \UserService($this->db);
    }

    /**
     * GET /api/v1/admin/users
     *
     * List all users with pagination, filtering, and search
     * Requires admin role
     *
     * Query Parameters:
     * - page (int): Page number (default: 1)
     * - per_page (int): Items per page (default: 25, max: 100)
     * - role (string): Filter by user type (admin, mentor, member, student, parent, project_officer, manager)
     * - status (string): Filter by status (active, inactive)
     * - search (string): Search by name, surname, email, username
     * - sort_by (string): Sort field (name, email, created_at, last_login)
     * - sort_order (string): Sort direction (asc, desc)
     *
     * @return JSON response with user list and pagination
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "data": {
     *     "users": [
     *       {
     *         "id": 1,
     *         "username": "john_doe",
     *         "email": "john@example.com",
     *         "name": "John",
     *         "surname": "Doe",
     *         "user_type": "admin",
     *         "active": 1,
     *         "created_at": "2025-01-01 10:00:00",
     *         "last_login": "2026-01-08 09:30:00",
     *         "total_attendance": 45,
     *         "last_attendance": "2026-01-07",
     *         "activity_level": "online"
     *       },
     *       ...
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 25,
     *       "total": 150,
     *       "total_pages": 6,
     *       "has_more": true
     *     }
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No authentication
     * - 403 Forbidden: Not an admin user
     */
    public function index() {
        try {
            // Check admin role
            if (!$this->isAdmin()) {
                return $this->errorResponse('Admin access required', 403);
            }

            // Get query parameters
            $page = isset($this->queryParams['page']) ? max(1, (int)$this->queryParams['page']) : 1;
            $perPage = isset($this->queryParams['per_page']) ? min(100, max(1, (int)$this->queryParams['per_page'])) : 25;

            // Build filters
            $filters = [];

            if (isset($this->queryParams['role']) && !empty($this->queryParams['role'])) {
                $filters['role'] = $this->queryParams['role'];
            }

            if (isset($this->queryParams['status']) && !empty($this->queryParams['status'])) {
                $filters['status'] = $this->queryParams['status'];
            }

            if (isset($this->queryParams['search']) && !empty($this->queryParams['search'])) {
                $filters['search'] = trim($this->queryParams['search']);
            }

            // Get users from repository
            $result = $this->userRepository->getAdminUserList($page, $perPage, $filters);

            // Remove passwords from all users
            $users = array_map(function($user) {
                unset($user['password']);
                return $user;
            }, $result['items']);

            // Log activity
            $this->logAction('api_admin_users_list', [
                'page' => $page,
                'per_page' => $perPage,
                'filters' => $filters,
                'total_results' => $result['pagination']['total']
            ]);

            return $this->successResponse([
                'users' => $users,
                'pagination' => $result['pagination']
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            error_log("Admin\UserController::index() error: " . $e->getMessage());
            return $this->errorResponse('Failed to retrieve users', 500);
        }
    }

    /**
     * GET /api/v1/admin/users/{id}
     *
     * View detailed information for a specific user
     * Requires admin role
     *
     * @param int $id User ID
     * @return JSON response with user details
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
     *     "user_type": "admin",
     *     "active": 1,
     *     "phone": "0123456789",
     *     "date_of_birth": "1990-01-15",
     *     "gender": "male",
     *     "profile_image": "/uploads/profiles/john_doe.jpg",
     *     "created_at": "2025-01-01 10:00:00",
     *     "updated_at": "2026-01-08 09:00:00",
     *     "last_login": "2026-01-08 09:30:00",
     *     "login_count": 245,
     *     "total_attendance": 45,
     *     "stats": {
     *       "courses_enrolled": 5,
     *       "courses_completed": 2,
     *       "programs_registered": 3,
     *       "total_activity_hours": 120
     *     }
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No authentication
     * - 403 Forbidden: Not an admin user
     * - 404 Not Found: User ID doesn't exist
     */
    public function show($id) {
        try {
            // Check admin role
            if (!$this->isAdmin()) {
                return $this->errorResponse('Admin access required', 403);
            }

            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->errorResponse('Invalid user ID', 422);
            }

            // Get user profile
            $user = $this->userRepository->getProfile((int)$id);

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Remove password
            unset($user['password']);

            // Get additional stats
            $stats = $this->getUserStats((int)$id);
            $user['stats'] = $stats;

            // Log activity
            $this->logAction('api_admin_user_viewed', [
                'viewed_user_id' => $id,
                'viewed_user_email' => $user['email'] ?? null
            ]);

            return $this->successResponse($user, 'User details retrieved successfully');

        } catch (\Exception $e) {
            error_log("Admin\UserController::show() error: " . $e->getMessage());
            return $this->errorResponse('Failed to retrieve user details', 500);
        }
    }

    /**
     * POST /api/v1/admin/users
     *
     * Create a new user
     * Requires admin role
     *
     * @return JSON response with created user
     *
     * Request Body:
     * {
     *   "username": "john_doe",
     *   "email": "john@example.com",
     *   "password": "SecurePass123",
     *   "password_confirmation": "SecurePass123",
     *   "name": "John",
     *   "surname": "Doe",
     *   "user_type": "member",
     *   "phone": "0123456789",
     *   "date_of_birth": "1990-01-15",
     *   "gender": "male"
     * }
     *
     * Response (201 Created):
     * {
     *   "success": true,
     *   "message": "User created successfully",
     *   "data": {
     *     "id": 123,
     *     "username": "john_doe",
     *     "email": "john@example.com",
     *     "name": "John",
     *     "surname": "Doe",
     *     "user_type": "member",
     *     "active": 1,
     *     "created_at": "2026-01-09 10:00:00"
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No authentication
     * - 403 Forbidden: Not an admin user
     * - 422 Unprocessable Entity: Validation errors
     */
    public function store() {
        try {
            // Check admin role
            if (!$this->isAdmin()) {
                return $this->errorResponse('Admin access required', 403);
            }

            // Get request data
            $data = $this->requestData;

            // Validate required fields
            $required = ['username', 'email', 'password', 'password_confirmation', 'name', 'surname', 'user_type'];
            $missing = [];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                return $this->errorResponse('Validation failed', 422, [
                    'missing_fields' => $missing
                ]);
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse('Validation failed', 422, [
                    'email' => 'Invalid email format'
                ]);
            }

            // Validate password length
            if (strlen($data['password']) < 8) {
                return $this->errorResponse('Validation failed', 422, [
                    'password' => 'Password must be at least 8 characters long'
                ]);
            }

            // Validate password confirmation
            if ($data['password'] !== $data['password_confirmation']) {
                return $this->errorResponse('Validation failed', 422, [
                    'password_confirmation' => 'Password confirmation does not match'
                ]);
            }

            // Validate user_type
            $validTypes = ['admin', 'mentor', 'member', 'student', 'parent', 'project_officer', 'manager'];
            if (!in_array($data['user_type'], $validTypes)) {
                return $this->errorResponse('Validation failed', 422, [
                    'user_type' => 'Invalid user type. Must be one of: ' . implode(', ', $validTypes)
                ]);
            }

            // Check for existing username or email
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser) {
                return $this->errorResponse('Validation failed', 422, [
                    'email' => 'Email already exists'
                ]);
            }

            // Check username
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param('s', $data['username']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return $this->errorResponse('Validation failed', 422, [
                    'username' => 'Username already exists'
                ]);
            }

            // Use UserService to create user
            $result = $this->userService->createUser($data);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 500);
            }

            // Get created user
            $user = $this->userRepository->find($result['user_id']);
            unset($user['password']);

            // Log activity
            $this->logAction('api_admin_user_created', [
                'created_user_id' => $result['user_id'],
                'created_user_email' => $data['email'],
                'user_type' => $data['user_type']
            ]);

            return $this->successResponse($user, 'User created successfully', 201);

        } catch (\Exception $e) {
            error_log("Admin\\UserController::store() error: " . $e->getMessage());
            return $this->errorResponse('Failed to create user', 500);
        }
    }

    /**
     * PUT /api/v1/admin/users/{id}
     *
     * Update an existing user
     * Requires admin role
     *
     * @param int $id User ID
     * @return JSON response with updated user
     *
     * Request Body:
     * {
     *   "email": "newemail@example.com",
     *   "name": "Updated Name",
     *   "surname": "Updated Surname",
     *   "user_type": "mentor",
     *   "phone": "0987654321",
     *   "date_of_birth": "1990-01-15",
     *   "gender": "male",
     *   "active": 1
     * }
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "message": "User updated successfully",
     *   "data": {
     *     "id": 123,
     *     "username": "john_doe",
     *     "email": "newemail@example.com",
     *     "name": "Updated Name",
     *     "surname": "Updated Surname",
     *     "user_type": "mentor",
     *     "active": 1,
     *     "updated_at": "2026-01-09 10:30:00"
     *   }
     * }
     *
     * Errors:
     * - 401 Unauthorized: No authentication
     * - 403 Forbidden: Not an admin user
     * - 404 Not Found: User doesn't exist
     * - 422 Unprocessable Entity: Validation errors
     */
    public function update($id) {
        try {
            // Check admin role
            if (!$this->isAdmin()) {
                return $this->errorResponse('Admin access required', 403);
            }

            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->errorResponse('Invalid user ID', 422);
            }

            // Check if user exists
            $user = $this->userRepository->find((int)$id);
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Get request data
            $data = $this->requestData;

            // Define updatable fields
            $updatableFields = ['email', 'name', 'surname', 'user_type', 'phone', 'date_of_birth', 'gender', 'active'];
            $updateData = [];

            foreach ($updatableFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // No fields to update
            if (empty($updateData)) {
                return $this->errorResponse('No valid fields provided for update', 422);
            }

            // Validate email if provided
            if (isset($updateData['email'])) {
                if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->errorResponse('Validation failed', 422, [
                        'email' => 'Invalid email format'
                    ]);
                }

                // Check if email is already taken by another user
                $existingUser = $this->userRepository->findByEmail($updateData['email']);
                if ($existingUser && $existingUser['id'] != $id) {
                    return $this->errorResponse('Validation failed', 422, [
                        'email' => 'Email already exists'
                    ]);
                }
            }

            // Validate user_type if provided
            if (isset($updateData['user_type'])) {
                $validTypes = ['admin', 'mentor', 'member', 'student', 'parent', 'project_officer', 'manager'];
                if (!in_array($updateData['user_type'], $validTypes)) {
                    return $this->errorResponse('Validation failed', 422, [
                        'user_type' => 'Invalid user type. Must be one of: ' . implode(', ', $validTypes)
                    ]);
                }
            }

            // Validate active if provided
            if (isset($updateData['active'])) {
                if (!in_array($updateData['active'], [0, 1, '0', '1', true, false], true)) {
                    return $this->errorResponse('Validation failed', 422, [
                        'active' => 'Active must be 0 or 1'
                    ]);
                }
                $updateData['active'] = (int)$updateData['active'];
            }

            // Use UserModel to update user
            $stmt = $this->db->prepare("UPDATE users SET " .
                implode(', ', array_map(fn($k) => "$k = ?", array_keys($updateData))) .
                ", updated_at = NOW() WHERE id = ?");

            $types = str_repeat('s', count($updateData)) . 'i';
            $params = array_merge(array_values($updateData), [$id]);
            $stmt->bind_param($types, ...$params);
            $updated = $stmt->execute();

            if (!$updated) {
                return $this->errorResponse('Failed to update user', 500);
            }

            // Get updated user
            $updatedUser = $this->userRepository->find((int)$id);
            unset($updatedUser['password']);

            // Log activity
            $this->logAction('api_admin_user_updated', [
                'updated_user_id' => $id,
                'updated_fields' => array_keys($updateData)
            ]);

            return $this->successResponse($updatedUser, 'User updated successfully');

        } catch (\Exception $e) {
            error_log("Admin\\UserController::update() error: " . $e->getMessage());
            return $this->errorResponse('Failed to update user', 500);
        }
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     *
     * Delete/deactivate a user (soft delete)
     * Requires admin role
     *
     * @param int $id User ID
     * @return JSON response
     *
     * Response (200 OK):
     * {
     *   "success": true,
     *   "message": "User deactivated successfully"
     * }
     *
     * Errors:
     * - 401 Unauthorized: No authentication
     * - 403 Forbidden: Not an admin user
     * - 404 Not Found: User doesn't exist
     * - 422 Unprocessable Entity: Invalid ID, cannot delete self, cannot delete last admin
     */
    public function destroy($id) {
        try {
            // Check admin role
            if (!$this->isAdmin()) {
                return $this->errorResponse('Admin access required', 403);
            }

            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->errorResponse('Invalid user ID', 422);
            }

            // Check if user exists
            $user = $this->userRepository->find((int)$id);
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Prevent self-deletion
            $currentUserId = $_SESSION['user_id'] ?? null;
            if ($currentUserId == $id) {
                return $this->errorResponse('Cannot delete your own account', 422);
            }

            // Check if this is the last admin
            if ($user['user_type'] === 'admin') {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin' AND active = 1");
                $stmt->execute();
                $result = $stmt->get_result();
                $adminCount = $result->fetch_assoc()['count'];

                if ($adminCount <= 1) {
                    return $this->errorResponse('Cannot delete the last admin user', 422);
                }
            }

            // Soft delete: Set active = 0
            $stmt = $this->db->prepare("UPDATE users SET active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            $deleted = $stmt->execute();

            if (!$deleted) {
                return $this->errorResponse('Failed to deactivate user', 500);
            }

            // Log activity
            $this->logAction('api_admin_user_deleted', [
                'deleted_user_id' => $id,
                'deleted_user_email' => $user['email'],
                'deleted_user_type' => $user['user_type'],
                'deletion_type' => 'soft_delete'
            ]);

            return $this->successResponse(null, 'User deactivated successfully', 200);

        } catch (\Exception $e) {
            error_log("Admin\\UserController::destroy() error: " . $e->getMessage());
            return $this->errorResponse('Failed to deactivate user', 500);
        }
    }

    /**
     * Helper method to check if current user is admin
     *
     * @return bool True if user is admin
     */
    private function isAdmin() {
        // Check session (populated by AuthMiddleware)
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            return true;
        }

        // Also check role from session
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Get additional user statistics
     *
     * @param int $userId User ID
     * @return array User statistics
     */
    private function getUserStats($userId) {
        try {
            $stats = [
                'courses_enrolled' => 0,
                'courses_completed' => 0,
                'programs_registered' => 0,
                'total_activity_hours' => 0
            ];

            // Get course enrollments
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['courses_enrolled'] = $result->fetch_assoc()['count'];

            // Get completed courses
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE user_id = ? AND status = 'completed'");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['courses_completed'] = $result->fetch_assoc()['count'];

            // Get program registrations
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM program_registrations WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['programs_registered'] = $result->fetch_assoc()['count'];

            // Get total attendance hours (assuming each attendance is 1 hour)
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM attendance WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_activity_hours'] = $result->fetch_assoc()['count'];

            return $stats;

        } catch (\Exception $e) {
            error_log("getUserStats() error: " . $e->getMessage());
            return [
                'courses_enrolled' => 0,
                'courses_completed' => 0,
                'programs_registered' => 0,
                'total_activity_hours' => 0
            ];
        }
    }

}
