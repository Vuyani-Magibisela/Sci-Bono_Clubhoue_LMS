<?php

namespace App\API;

use App\Models\UserModel;
use App\Utils\ResponseHelper;
use App\Utils\Logger;
use Exception;

class UserApiController extends BaseApiController
{
    private $userModel;
    
    public function __construct($db)
    {
        parent::__construct($db);
        $this->userModel = new UserModel($db);
    }
    
    /**
     * Handle GET requests - List users or get specific user
     */
    protected function handleGet()
    {
        $this->requireAuthentication();
        
        $userId = $this->queryParams['id'] ?? null;
        
        if ($userId) {
            return $this->getUser($userId);
        }
        
        return $this->getUsers();
    }
    
    /**
     * Handle POST requests - Create new user
     */
    protected function handlePost()
    {
        $this->requireRole('admin'); // Only admins can create users
        return $this->createUser();
    }
    
    /**
     * Handle PUT requests - Update existing user
     */
    protected function handlePut()
    {
        $this->requireAuthentication();
        
        $userId = $this->queryParams['id'] ?? null;
        
        if (!$userId) {
            ResponseHelper::badRequest('User ID required in URL path');
        }
        
        return $this->updateUser($userId);
    }
    
    /**
     * Handle DELETE requests - Delete user
     */
    protected function handleDelete()
    {
        $this->requireRole('admin'); // Only admins can delete users
        
        $userId = $this->queryParams['id'] ?? null;
        
        if (!$userId) {
            ResponseHelper::badRequest('User ID required in URL path');
        }
        
        return $this->deleteUser($userId);
    }
    
    /**
     * Get specific user by ID
     */
    private function getUser($userId)
    {
        try {
            // Validate user ID
            if (!is_numeric($userId)) {
                ResponseHelper::badRequest('Invalid user ID format');
            }
            
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                ResponseHelper::notFound('User not found');
            }
            
            // Remove sensitive information
            $user = $this->sanitizeUserData($user);
            
            // Check permissions - users can only see themselves unless admin
            $currentUser = $this->getAuthenticatedUser();
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $userId) {
                ResponseHelper::forbidden('You can only access your own user data');
            }
            
            ResponseHelper::success($user, 'User retrieved successfully');
            
        } catch (Exception $e) {
            Logger::error('Get user error: ' . $e->getMessage(), ['user_id' => $userId]);
            ResponseHelper::internalError('Failed to retrieve user');
        }
    }
    
    /**
     * Get paginated list of users
     */
    private function getUsers()
    {
        try {
            $this->requireRole('admin'); // Only admins can list all users
            
            $pagination = $this->getPaginationParams();
            $search = $this->queryParams['search'] ?? '';
            $role = $this->queryParams['role'] ?? '';
            $status = $this->queryParams['status'] ?? '';
            
            // Build filters
            $filters = array_filter([
                'search' => $search,
                'role' => $role,
                'status' => $status
            ]);
            
            // Get users with pagination
            $result = $this->userModel->getPaginated(
                $pagination['page'], 
                $pagination['limit'], 
                $filters
            );
            
            // Sanitize user data
            $result['data'] = array_map([$this, 'sanitizeUserData'], $result['data']);
            
            // Build pagination metadata
            $paginationMeta = $this->buildPaginationMeta(
                $result['pagination']['total'],
                $pagination['page'],
                $pagination['limit']
            );
            
            ResponseHelper::paginated($result['data'], $paginationMeta, 'Users retrieved successfully');
            
        } catch (Exception $e) {
            Logger::error('Get users error: ' . $e->getMessage());
            ResponseHelper::internalError('Failed to retrieve users');
        }
    }
    
    /**
     * Create new user
     */
    private function createUser()
    {
        try {
            // Validate required fields
            $validation = $this->validateData($this->requestData, [
                'name' => 'required|string|max:100',
                'surname' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,mentor,member,student'
            ]);
            
            if (!$validation['valid']) {
                ResponseHelper::validationError($validation['errors']);
            }
            
            $userData = $this->requestData;
            
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Add timestamps
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            // Create user
            $userId = $this->userModel->create($userData);
            
            if (!$userId) {
                ResponseHelper::internalError('Failed to create user');
            }
            
            // Get created user
            $user = $this->userModel->findById($userId);
            $user = $this->sanitizeUserData($user);
            
            // Log user creation
            Logger::info('User created via API', [
                'user_id' => $userId,
                'created_by' => $this->getAuthenticatedUser()['id'],
                'user_email' => $userData['email']
            ]);
            
            ResponseHelper::created($user, 'User created successfully');
            
        } catch (Exception $e) {
            Logger::error('Create user error: ' . $e->getMessage(), [
                'request_data' => $this->requestData
            ]);
            ResponseHelper::internalError('Failed to create user');
        }
    }
    
    /**
     * Update existing user
     */
    private function updateUser($userId)
    {
        try {
            // Validate user ID
            if (!is_numeric($userId)) {
                ResponseHelper::badRequest('Invalid user ID format');
            }
            
            // Check if user exists
            $existingUser = $this->userModel->findById($userId);
            if (!$existingUser) {
                ResponseHelper::notFound('User not found');
            }
            
            // Check permissions - users can only update themselves unless admin
            $currentUser = $this->getAuthenticatedUser();
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $userId) {
                ResponseHelper::forbidden('You can only update your own user data');
            }
            
            // Validate update data (all fields are optional for update)
            $validationRules = [
                'name' => 'string|max:100',
                'surname' => 'string|max:100',
                'email' => 'email|unique:users,email,' . $userId,
                'password' => 'string|min:8'
            ];
            
            // Only admins can change roles
            if ($currentUser['role'] === 'admin') {
                $validationRules['role'] = 'in:admin,mentor,member,student';
            }
            
            $validation = $this->validateData($this->requestData, $validationRules);
            
            if (!$validation['valid']) {
                ResponseHelper::validationError($validation['errors']);
            }
            
            $updateData = $this->requestData;
            
            // Hash password if provided
            if (isset($updateData['password'])) {
                $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
            }
            
            // Add updated timestamp
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            // Remove fields that shouldn't be updated by non-admins
            if ($currentUser['role'] !== 'admin') {
                unset($updateData['role']);
            }
            
            // Update user
            $success = $this->userModel->update($userId, $updateData);
            
            if (!$success) {
                ResponseHelper::internalError('Failed to update user');
            }
            
            // Get updated user
            $updatedUser = $this->userModel->findById($userId);
            $updatedUser = $this->sanitizeUserData($updatedUser);
            
            // Log user update
            Logger::info('User updated via API', [
                'user_id' => $userId,
                'updated_by' => $currentUser['id'],
                'updated_fields' => array_keys($updateData)
            ]);
            
            ResponseHelper::updated($updatedUser, 'User updated successfully');
            
        } catch (Exception $e) {
            Logger::error('Update user error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'request_data' => $this->requestData
            ]);
            ResponseHelper::internalError('Failed to update user');
        }
    }
    
    /**
     * Delete user
     */
    private function deleteUser($userId)
    {
        try {
            // Validate user ID
            if (!is_numeric($userId)) {
                ResponseHelper::badRequest('Invalid user ID format');
            }
            
            // Check if user exists
            $user = $this->userModel->findById($userId);
            if (!$user) {
                ResponseHelper::notFound('User not found');
            }
            
            // Prevent deletion of current user
            $currentUser = $this->getAuthenticatedUser();
            if ($currentUser['id'] == $userId) {
                ResponseHelper::badRequest('Cannot delete your own account');
            }
            
            // Prevent deletion of other admins (unless super admin)
            if ($user['role'] === 'admin' && $currentUser['role'] !== 'super_admin') {
                ResponseHelper::forbidden('Cannot delete admin users');
            }
            
            // Delete user
            $success = $this->userModel->delete($userId);
            
            if (!$success) {
                ResponseHelper::internalError('Failed to delete user');
            }
            
            // Log user deletion
            Logger::info('User deleted via API', [
                'deleted_user_id' => $userId,
                'deleted_by' => $currentUser['id'],
                'deleted_user_email' => $user['email']
            ]);
            
            ResponseHelper::deleted('User deleted successfully');
            
        } catch (Exception $e) {
            Logger::error('Delete user error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            ResponseHelper::internalError('Failed to delete user');
        }
    }
    
    /**
     * Remove sensitive data from user object
     */
    private function sanitizeUserData($user)
    {
        if (!is_array($user)) {
            return $user;
        }
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'password_reset_token', 'email_verification_token'];
        
        foreach ($sensitiveFields as $field) {
            unset($user[$field]);
        }
        
        return $user;
    }
    
    /**
     * Get user profile (self-service endpoint)
     */
    public function getProfile()
    {
        $currentUser = $this->requireAuthentication();
        
        try {
            $user = $this->userModel->findById($currentUser['id']);
            
            if (!$user) {
                ResponseHelper::notFound('User profile not found');
            }
            
            $user = $this->sanitizeUserData($user);
            
            ResponseHelper::success($user, 'Profile retrieved successfully');
            
        } catch (Exception $e) {
            Logger::error('Get profile error: ' . $e->getMessage(), [
                'user_id' => $currentUser['id']
            ]);
            ResponseHelper::internalError('Failed to retrieve profile');
        }
    }
    
    /**
     * Update user profile (self-service endpoint)
     */
    public function updateProfile()
    {
        $currentUser = $this->requireAuthentication();
        
        try {
            // Users can only update certain fields in their profile
            $allowedFields = ['name', 'surname', 'email', 'password', 'phone', 'bio'];
            $updateData = array_intersect_key($this->requestData, array_flip($allowedFields));
            
            if (empty($updateData)) {
                ResponseHelper::badRequest('No valid fields to update');
            }
            
            // Validate update data
            $validationRules = [
                'name' => 'string|max:100',
                'surname' => 'string|max:100',
                'email' => 'email|unique:users,email,' . $currentUser['id'],
                'password' => 'string|min:8',
                'phone' => 'string|max:20',
                'bio' => 'string|max:500'
            ];
            
            $validation = $this->validateData($updateData, $validationRules);
            
            if (!$validation['valid']) {
                ResponseHelper::validationError($validation['errors']);
            }
            
            // Hash password if provided
            if (isset($updateData['password'])) {
                $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
            }
            
            // Add updated timestamp
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            // Update user profile
            $success = $this->userModel->update($currentUser['id'], $updateData);
            
            if (!$success) {
                ResponseHelper::internalError('Failed to update profile');
            }
            
            // Get updated user
            $updatedUser = $this->userModel->findById($currentUser['id']);
            $updatedUser = $this->sanitizeUserData($updatedUser);
            
            // Log profile update
            Logger::info('User profile updated via API', [
                'user_id' => $currentUser['id'],
                'updated_fields' => array_keys($updateData)
            ]);
            
            ResponseHelper::updated($updatedUser, 'Profile updated successfully');
            
        } catch (Exception $e) {
            Logger::error('Update profile error: ' . $e->getMessage(), [
                'user_id' => $currentUser['id']
            ]);
            ResponseHelper::internalError('Failed to update profile');
        }
    }
}