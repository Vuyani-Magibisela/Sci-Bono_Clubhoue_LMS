<?php
require_once __DIR__ . '/../Models/UserModel.php';


class UserController {
    private $userModel;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->userModel = new UserModel($conn);
    }
    
    /**
     * Get all users based on current user's permissions
     * 
     * @param string $currentUserType The current user's type (admin, mentor, etc.)
     * @return array List of users
     */
    public function getAllUsers($currentUserType) {
        return $this->userModel->getAllUsers($currentUserType);
    }
    
    /**
     * Get a single user by ID
     * 
     * @param int $userId The user's ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        return $this->userModel->getUserById($userId);
    }
    
    /**
     * Check if current user has permission to edit the target user
     * 
     * @param string $currentUserType The current user's type
     * @param int $currentUserId The current user's ID
     * @param array $targetUser The target user's data
     * @return bool True if has permission, false otherwise
     */
    public function hasEditPermission($currentUserType, $currentUserId, $targetUser) {
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
     * Process form data and update user
     * 
     * @param array $formData The form data from $_POST
     * @return bool Success status
     */
    public function updateUser($formData) {
        // Process and sanitize form data
        $userData = $this->sanitizeUserData($formData);
        
        // Update user in database
        return $this->userModel->updateUser($userData);
    }
    
    /**
     * Delete a user
     * 
     * @param int $userId The user's ID
     * @return bool Success status
     */
    public function deleteUser($userId) {
        // Check if user exists before attempting deletion
        $user = $this->userModel->getUserById($userId);
        
        if (!$user) {
            return false; // User does not exist
        }
        
        // Perform the deletion
        return $this->userModel->deleteUser($userId);
    }
    
    /**
     * Sanitize and validate user data
     * 
     * @param array $formData Raw form data
     * @return array Sanitized user data
     */
    private function sanitizeUserData($formData) {
        $userData = [];
        
        // Required fields
        $userData['id'] = intval($formData['id']);
        $userData['name'] = htmlspecialchars(trim($formData['name']));
        $userData['surname'] = htmlspecialchars(trim($formData['surname']));
        $userData['username'] = htmlspecialchars(trim($formData['username']));
        $userData['email'] = filter_var(trim($formData['email']), FILTER_SANITIZE_EMAIL);
        
        // Optional fields - only add if present
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
        
        if (isset($formData['dob'])) {
            $userData['dob'] = htmlspecialchars(trim($formData['dob']));
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
        
        // Member-specific fields
        if (isset($formData['school'])) {
            $userData['school'] = htmlspecialchars(trim($formData['school']));
        }
        
        if (isset($formData['grade'])) {
            $userData['grade'] = intval($formData['grade']);
        }
        
        if (isset($formData['parent'])) {
            $userData['parent'] = htmlspecialchars(trim($formData['parent']));
        }
        
        if (isset($formData['parent_email'])) {
            $userData['parent_email'] = filter_var(trim($formData['parent_email']), FILTER_SANITIZE_EMAIL);
        }
        
        if (isset($formData['relationship'])) {
            $userData['relationship'] = htmlspecialchars(trim($formData['relationship']));
        }
        
        if (isset($formData['parent_number'])) {
            $userData['parent_number'] = preg_replace('/[^0-9]/', '', $formData['parent_number']);
        }
        
        if (isset($formData['cell_number'])) {
            $userData['cell_number'] = preg_replace('/[^0-9]/', '', $formData['cell_number']);
        }
        
        // Admin-only fields
        if (isset($formData['user_type'])) {
            $userData['user_type'] = htmlspecialchars(trim($formData['user_type']));
        }
        
        if (isset($formData['center'])) {
            $userData['center'] = htmlspecialchars(trim($formData['center']));
        }
        
        // Emergency contact information
        if (isset($formData['emergency_contact_name'])) {
            $userData['emergency_contact_name'] = htmlspecialchars(trim($formData['emergency_contact_name']));
        }
        
        if (isset($formData['emergency_contact_relationship'])) {
            $userData['emergency_contact_relationship'] = htmlspecialchars(trim($formData['emergency_contact_relationship']));
        }
        
        if (isset($formData['emergency_contact_phone'])) {
            $userData['emergency_contact_phone'] = htmlspecialchars(trim($formData['emergency_contact_phone']));
        }
        
        if (isset($formData['emergency_contact_email'])) {
            $userData['emergency_contact_email'] = filter_var(trim($formData['emergency_contact_email']), FILTER_SANITIZE_EMAIL);
        }
        
        if (isset($formData['emergency_contact_address'])) {
            $userData['emergency_contact_address'] = htmlspecialchars(trim($formData['emergency_contact_address']));
        }
        
        // Interest and skills information
        if (isset($formData['interests'])) {
            $userData['interests'] = htmlspecialchars(trim($formData['interests']));
        }
        
        if (isset($formData['role_models'])) {
            $userData['role_models'] = htmlspecialchars(trim($formData['role_models']));
        }
        
        if (isset($formData['goals'])) {
            $userData['goals'] = htmlspecialchars(trim($formData['goals']));
        }
        
        if (isset($formData['has_computer'])) {
            $userData['has_computer'] = intval($formData['has_computer']);
        }
        
        if (isset($formData['computer_skills'])) {
            $userData['computer_skills'] = htmlspecialchars(trim($formData['computer_skills']));
        }
        
        if (isset($formData['computer_skills_source'])) {
            $userData['computer_skills_source'] = htmlspecialchars(trim($formData['computer_skills_source']));
        }
        
        // Password (only process if not empty)
        if (!empty($formData['password'])) {
            $userData['password'] = $formData['password'];
        }
        
        return $userData;
    }
}
?>