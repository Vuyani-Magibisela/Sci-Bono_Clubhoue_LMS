<?php
/**
 * Visitor Controller
 * 
 * Handles request processing and response generation for visitor management
 */
class VisitorController {
    private $model;
    
    /**
     * Constructor - initializes the controller with a model
     * 
     * @param VisitorModel $model The visitor model
     */
    public function __construct($model) {
        $this->model = $model;
    }
    
    /**
     * Process registration request
     * 
     * @param array $postData POST data from registration form
     * @return array Response data
     */
    public function processRegistration($postData) {
        // Validate required fields
        $requiredFields = [
            'name', 'surname', 'age', 'grade_school', 
            'parent_name', 'parent_surname', 'email', 'phone'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($postData[$field]) || empty(trim($postData[$field]))) {
                return [
                    'success' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }
        
        // Validate email
        if (!filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }
        
        // Validate age
        $age = (int)$postData['age'];
        if ($age < 5 || $age > 100) {
            return [
                'success' => false,
                'message' => 'Age must be between 5 and 100'
            ];
        }
        
        // Sanitize input data
        $visitorData = [
            'name' => $this->sanitizeInput($postData['name']),
            'surname' => $this->sanitizeInput($postData['surname']),
            'age' => $age,
            'grade_school' => $this->sanitizeInput($postData['grade_school']),
            'student_number' => $this->sanitizeInput($postData['student_number'] ?? ''),
            'parent_name' => $this->sanitizeInput($postData['parent_name']),
            'parent_surname' => $this->sanitizeInput($postData['parent_surname']),
            'email' => $this->sanitizeInput($postData['email']),
            'phone' => $this->sanitizeInput($postData['phone'])
        ];
        
        // Register visitor through model
        return $this->model->registerVisitor($visitorData);
    }
    
    /**
     * Process sign in request
     * 
     * @param array $postData POST data from sign in form
     * @return array Response data
     */
    public function processSignIn($postData) {
        // Validate email
        if (!isset($postData['email']) || !filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }
        
        $email = $this->sanitizeInput($postData['email']);
        
        // Get visitor by email
        $visitor = $this->model->getVisitorByEmail($email);
        
        if (!$visitor) {
            return [
                'success' => false,
                'message' => 'Visitor not found. Please register first.'
            ];
        }
        
        // Sign in visitor
        return $this->model->signInVisitor($visitor['id']);
    }
    
    /**
     * Process sign out request
     * 
     * @param array $postData POST data from sign out form
     * @return array Response data
     */
    public function processSignOut($postData) {
        // Validate email
        if (!isset($postData['email']) || !filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }
        
        $email = $this->sanitizeInput($postData['email']);
        $comment = isset($postData['comment']) ? $this->sanitizeInput($postData['comment']) : null;
        
        // Get visitor by email
        $visitor = $this->model->getVisitorByEmail($email);
        
        if (!$visitor) {
            return [
                'success' => false,
                'message' => 'Visitor not found'
            ];
        }
        
        // Sign out visitor
        return $this->model->signOutVisitor($visitor['id'], $comment);
    }
    
    /**
     * Process visitor list request
     * 
     * @param array $getData GET parameters
     * @return array Response data
     */
    public function processVisitorsList($getData) {
        $page = isset($getData['page']) ? (int)$getData['page'] : 1;
        $filter = isset($getData['filter']) ? $this->sanitizeInput($getData['filter']) : 'all';
        $search = isset($getData['search']) ? $this->sanitizeInput($getData['search']) : '';
        
        // Validate page number
        if ($page < 1) {
            $page = 1;
        }
        
        // Validate filter
        $allowedFilters = ['all', 'active', 'completed'];
        if (!in_array($filter, $allowedFilters)) {
            $filter = 'all';
        }
        
        // Get visitors list from model
        $visitorsData = $this->model->getVisitorsList($page, $filter, $search);
        
        return [
            'success' => true,
            'visitors' => $visitorsData['visitors'],
            'totalRecords' => $visitorsData['totalRecords'],
            'totalPages' => $visitorsData['totalPages'],
            'currentPage' => $visitorsData['currentPage']
        ];
    }
    
    /**
     * Sanitize user input
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    private function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}