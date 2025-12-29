<?php
/**
 * Admin\UserController
 *
 * Handles admin user management (CRUD operations)
 *
 * Phase 3: Modern Routing System - Week 5 Implementation
 * Created: November 11, 2025
 * Implemented: November 27, 2025
 * Status: COMPLETE
 *
 * Migrated from:
 * - /app/Controllers/user_list.php → index()
 * - /app/Controllers/user_edit.php → edit()
 * - /app/Controllers/user_update.php → update()
 * - /app/Controllers/user_delete.php → destroy()
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/UserModel.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class UserController extends BaseController {

    private $userModel;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->userModel = new UserModel($conn);
    }

    /**
     * Display user list
     * Route: GET /admin/users
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function index() {
        // Require admin or mentor role
        $this->requireRole(['admin', 'mentor']);

        // Get search/filter parameters
        $search = $this->input('search', '');
        $role = $this->input('role', '');
        $page = max(1, intval($this->input('page', 1)));
        $perPage = 25;

        // Get all users based on current user's permissions
        $currentUserType = $_SESSION['user_type'];
        $users = $this->userModel->getAllUsers($currentUserType);

        // Apply filters
        if (!empty($search)) {
            $users = array_filter($users, function($user) use ($search) {
                $searchLower = strtolower($search);
                return stripos($user['name'], $search) !== false ||
                       stripos($user['surname'], $search) !== false ||
                       stripos($user['username'], $search) !== false ||
                       stripos($user['email'], $search) !== false;
            });
        }

        if (!empty($role)) {
            $users = array_filter($users, function($user) use ($role) {
                return $user['user_type'] === $role;
            });
        }

        // Pagination
        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $perPage);
        $offset = ($page - 1) * $perPage;
        $users = array_slice($users, $offset, $perPage);

        // Render view
        $data = [
            'pageTitle' => 'User Management',
            'currentPage' => 'users',
            'users' => $users,
            'totalUsers' => $totalUsers,
            'currentPageNum' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'roleFilter' => $role
        ];

        return $this->view('admin.users.index', $data, 'admin');
    }

    /**
     * Show create user form
     * Route: GET /admin/users/create
     * Middleware: AuthMiddleware, RoleMiddleware:admin
     */
    public function create() {
        // Only admin can create users
        $this->requireRole('admin');

        $data = [
            'pageTitle' => 'Create User',
            'currentPage' => 'users',
            'userTypes' => ['member', 'mentor', 'admin', 'parent', 'project officer', 'manager']
        ];

        return $this->view('admin.users.create', $data, 'admin');
    }

    /**
     * Store new user
     * Route: POST /admin/users
     * Middleware: AuthMiddleware, RoleMiddleware:admin
     */
    public function store() {
        // Only admin can create users
        $this->requireRole('admin');

        // Validate CSRF token
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in UserController@store - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return $this->redirectWithError(BASE_URL . 'admin/users/create', 'Security validation failed. Please try again.');
        }

        // Validation rules
        $rules = [
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'username' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'password' => 'required|min:6',
            'user_type' => 'required|in:member,mentor,admin,parent,project officer,manager'
        ];

        // Validate input
        $data = $this->validate($this->input(), $rules);

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Create user
        try {
            $userId = $this->userModel->createUser($data);

            if ($userId) {
                return $this->redirectWithSuccess(BASE_URL . 'admin/users', 'User created successfully.');
            } else {
                return $this->redirectWithError(BASE_URL . 'admin/users/create', 'Failed to create user. Username or email may already exist.');
            }
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return $this->redirectWithError(BASE_URL . 'admin/users/create', 'An error occurred while creating the user.');
        }
    }

    /**
     * Show user details
     * Route: GET /admin/users/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function show($id) {
        $this->requireRole(['admin', 'mentor']);

        // Get user data
        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'User not found.');
        }

        // Check permissions
        if (!$this->hasViewPermission($_SESSION['user_type'], $_SESSION['user_id'], $user)) {
            return $this->redirectWithError(BASE_URL . 'admin/users', "You don't have permission to view this user.");
        }

        $data = [
            'pageTitle' => 'User Details',
            'currentPage' => 'users',
            'user' => $user
        ];

        return $this->view('admin.users.show', $data, 'admin');
    }

    /**
     * Show edit user form
     * Route: GET /admin/users/{id}/edit
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function edit($id) {
        $this->requireRole(['admin', 'mentor']);

        // Get user data
        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'User not found.');
        }

        // Check permissions
        if (!$this->hasEditPermission($_SESSION['user_type'], $_SESSION['user_id'], $user)) {
            return $this->redirectWithError(BASE_URL . 'admin/users', "You don't have permission to edit this user.");
        }

        $data = [
            'pageTitle' => 'Edit User',
            'currentPage' => 'users',
            'user' => $user,
            'userTypes' => ['member', 'mentor', 'admin', 'parent', 'project officer', 'manager']
        ];

        return $this->view('admin.users.edit', $data, 'admin');
    }

    /**
     * Update user
     * Route: PUT/POST /admin/users/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function update($id) {
        $this->requireRole(['admin', 'mentor']);

        // Get user data
        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'User not found.');
        }

        // Check permissions
        if (!$this->hasEditPermission($_SESSION['user_type'], $_SESSION['user_id'], $user)) {
            return $this->redirectWithError(BASE_URL . 'admin/users', "You don't have permission to update this user.");
        }

        // Validate CSRF token
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in UserController@update - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", User ID: " . $id);
            return $this->redirectWithError(BASE_URL . "admin/users/{$id}/edit", 'Security validation failed. Please try again.');
        }

        // Get and sanitize form data
        $userData = $this->sanitizeUserData($this->input());
        $userData['id'] = $id;

        // Update user
        $success = $this->userModel->updateUser($userData);

        if ($success) {
            return $this->redirectWithSuccess(BASE_URL . 'admin/users', 'User updated successfully.');
        } else {
            return $this->redirectWithError(BASE_URL . "admin/users/{$id}/edit", 'Failed to update user.');
        }
    }

    /**
     * Delete user
     * Route: DELETE/POST /admin/users/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin
     */
    public function destroy($id) {
        // Only admin can delete users
        $this->requireRole('admin');

        // Get user data
        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'User not found.');
        }

        // Prevent deleting yourself
        if ($id == $_SESSION['user_id']) {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'You cannot delete your own account.');
        }

        // Validate CSRF token
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in UserController@destroy - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", User ID: " . $id);
            return $this->redirectWithError(BASE_URL . 'admin/users', 'Security validation failed. Please try again.');
        }

        // Delete user
        $success = $this->userModel->deleteUser($id);

        if ($success) {
            return $this->redirectWithSuccess(BASE_URL . 'admin/users', 'User deleted successfully.');
        } else {
            return $this->redirectWithError(BASE_URL . 'admin/users', 'Failed to delete user.');
        }
    }

    /**
     * Check if current user has permission to view target user
     */
    private function hasViewPermission($currentUserType, $currentUserId, $targetUser) {
        if ($currentUserType === 'admin') {
            return true; // Admin can view anyone
        } elseif ($currentUserType === 'mentor' && $targetUser['user_type'] === 'member') {
            return true; // Mentor can view members
        } elseif ($currentUserId == $targetUser['id']) {
            return true; // Users can view themselves
        }

        return false;
    }

    /**
     * Check if current user has permission to edit target user
     */
    private function hasEditPermission($currentUserType, $currentUserId, $targetUser) {
        if ($currentUserType === 'admin') {
            return true; // Admin can edit anyone
        } elseif ($currentUserType === 'mentor' && $targetUser['user_type'] === 'member') {
            return true; // Mentor can edit members
        } elseif ($currentUserId == $targetUser['id']) {
            return true; // Users can edit themselves
        }

        return false;
    }

    /**
     * Sanitize and validate user data
     */
    private function sanitizeUserData($formData) {
        $userData = [];

        // Required fields
        $userData['name'] = htmlspecialchars(trim($formData['name'] ?? ''));
        $userData['surname'] = htmlspecialchars(trim($formData['surname'] ?? ''));
        $userData['username'] = htmlspecialchars(trim($formData['username'] ?? ''));
        $userData['email'] = filter_var(trim($formData['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        // Optional fields
        if (isset($formData['user_type'])) {
            $userData['user_type'] = htmlspecialchars(trim($formData['user_type']));
        }

        if (isset($formData['nationality'])) {
            $userData['nationality'] = htmlspecialchars(trim($formData['nationality']));

            // Handle "Other" nationality
            if ($userData['nationality'] === 'Other' && !empty($formData['other_nationality'])) {
                $userData['nationality'] = htmlspecialchars(trim($formData['other_nationality']));
            }
        }

        if (isset($formData['gender'])) {
            $userData['gender'] = htmlspecialchars(trim($formData['gender']));
        }

        if (isset($formData['dob']) || isset($formData['date_of_birth'])) {
            $userData['date_of_birth'] = htmlspecialchars(trim($formData['dob'] ?? $formData['date_of_birth'] ?? ''));
        }

        if (isset($formData['id_number'])) {
            $userData['id_number'] = htmlspecialchars(trim($formData['id_number']));
        }

        if (isset($formData['home_language'])) {
            $userData['home_language'] = htmlspecialchars(trim($formData['home_language']));

            // Handle "Other" language
            if ($userData['home_language'] === 'Other' && !empty($formData['other_language'])) {
                $userData['home_language'] = htmlspecialchars(trim($formData['other_language']));
            }
        }

        // Address information
        if (isset($formData['address_street'])) {
            $userData['address_street'] = htmlspecialchars(trim($formData['address_street']));
        }

        if (isset($formData['address_suburb'])) {
            $userData['address_suburb'] = htmlspecialchars(trim($formData['address_suburb']));
        }

        if (isset($formData['address_city'])) {
            $userData['address_city'] = htmlspecialchars(trim($formData['address_city']));
        }

        if (isset($formData['address_province'])) {
            $userData['address_province'] = htmlspecialchars(trim($formData['address_province']));
        }

        if (isset($formData['address_postal_code'])) {
            $userData['address_postal_code'] = htmlspecialchars(trim($formData['address_postal_code']));
        }

        // Contact information
        if (isset($formData['phone'])) {
            $userData['phone'] = htmlspecialchars(trim($formData['phone']));
        }

        if (isset($formData['emergency_contact'])) {
            $userData['emergency_contact'] = htmlspecialchars(trim($formData['emergency_contact']));
        }

        if (isset($formData['emergency_phone'])) {
            $userData['emergency_phone'] = htmlspecialchars(trim($formData['emergency_phone']));
        }

        return $userData;
    }
}
