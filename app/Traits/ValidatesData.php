<?php
/**
 * ValidatesData Trait - Data validation functionality
 * Phase 4 Implementation
 */

trait ValidatesData {
    /**
     * Validation errors
     */
    protected $validationErrors = [];
    
    /**
     * Validated data after successful validation
     */
    protected $validatedData = [];
    
    /**
     * Custom validation messages
     */
    protected $validationMessages = [
        'required' => 'The :field field is required.',
        'email' => 'The :field field must be a valid email address.',
        'min' => 'The :field field must be at least :min characters.',
        'max' => 'The :field field may not be greater than :max characters.',
        'numeric' => 'The :field field must be a number.',
        'integer' => 'The :field field must be an integer.',
        'alpha' => 'The :field field may only contain letters.',
        'alphanumeric' => 'The :field field may only contain letters and numbers.',
        'url' => 'The :field field must be a valid URL.',
        'date' => 'The :field field must be a valid date.',
        'boolean' => 'The :field field must be true or false.',
        'confirmed' => 'The :field field confirmation does not match.',
        'unique' => 'The :field field has already been taken.',
        'exists' => 'The selected :field is invalid.',
        'regex' => 'The :field field format is invalid.',
        'in' => 'The selected :field is invalid.',
        'not_in' => 'The selected :field is invalid.',
    ];
    
    /**
     * Validate data against rules
     */
    public function validate($data, $rules, $messages = []) {
        $this->validationErrors = [];
        $this->validatedData = [];
        $customMessages = array_merge($this->validationMessages, $messages);
        
        foreach ($rules as $field => $ruleSet) {
            $fieldRules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = isset($data[$field]) ? $data[$field] : null;
            
            foreach ($fieldRules as $rule) {
                $this->validateField($field, $value, $rule, $customMessages);
            }
            
            // Add to validated data if no errors for this field
            if (!isset($this->validationErrors[$field])) {
                $this->validatedData[$field] = $value;
            }
        }
        
        return empty($this->validationErrors);
    }
    
    /**
     * Validate a single field against a rule
     */
    protected function validateField($field, $value, $rule, $messages) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = isset($ruleParts[1]) ? $ruleParts[1] : null;
        
        $isValid = true;
        $message = '';
        
        switch ($ruleName) {
            case 'required':
                $isValid = !empty($value) || $value === '0' || $value === 0;
                break;
                
            case 'email':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                break;
                
            case 'min':
                $isValid = empty($value) || strlen($value) >= (int) $ruleValue;
                $message = str_replace(':min', $ruleValue, $messages[$ruleName]);
                break;
                
            case 'max':
                $isValid = empty($value) || strlen($value) <= (int) $ruleValue;
                $message = str_replace(':max', $ruleValue, $messages[$ruleName]);
                break;
                
            case 'numeric':
                $isValid = empty($value) || is_numeric($value);
                break;
                
            case 'integer':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
                break;
                
            case 'alpha':
                $isValid = empty($value) || preg_match('/^[a-zA-Z]+$/', $value);
                break;
                
            case 'alphanumeric':
                $isValid = empty($value) || preg_match('/^[a-zA-Z0-9]+$/', $value);
                break;
                
            case 'url':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
                break;
                
            case 'date':
                $isValid = empty($value) || $this->isValidDate($value);
                break;
                
            case 'boolean':
                $isValid = empty($value) || in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = isset($this->validatedData[$confirmField]) ? $this->validatedData[$confirmField] : 
                              (isset($_POST[$confirmField]) ? $_POST[$confirmField] : null);
                $isValid = $value === $confirmValue;
                break;
                
            case 'regex':
                $isValid = empty($value) || preg_match($ruleValue, $value);
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                $isValid = empty($value) || in_array($value, $allowedValues);
                break;
                
            case 'not_in':
                $forbiddenValues = explode(',', $ruleValue);
                $isValid = empty($value) || !in_array($value, $forbiddenValues);
                break;
                
            case 'unique':
                $isValid = $this->validateUnique($field, $value, $ruleValue);
                break;
                
            case 'exists':
                $isValid = $this->validateExists($field, $value, $ruleValue);
                break;
                
            default:
                // Custom validation method
                if (method_exists($this, 'validate' . ucfirst($ruleName))) {
                    $isValid = $this->{'validate' . ucfirst($ruleName)}($field, $value, $ruleValue);
                }
                break;
        }
        
        if (!$isValid) {
            if (empty($message)) {
                $message = isset($messages[$ruleName]) ? $messages[$ruleName] : "The {$field} field is invalid.";
            }
            
            $message = str_replace(':field', $this->getFieldDisplayName($field), $message);
            
            if (!isset($this->validationErrors[$field])) {
                $this->validationErrors[$field] = [];
            }
            
            $this->validationErrors[$field][] = $message;
        }
    }
    
    /**
     * Validate unique constraint
     */
    protected function validateUnique($field, $value, $ruleValue) {
        if (empty($value)) {
            return true;
        }
        
        // Parse rule value (table:column:except:idColumn)
        $parts = explode(',', $ruleValue);
        $table = $parts[0];
        $column = isset($parts[1]) ? $parts[1] : $field;
        $except = isset($parts[2]) ? $parts[2] : null;
        $idColumn = isset($parts[3]) ? $parts[3] : 'id';
        
        if (!$this->conn) {
            return true; // Can't validate without connection
        }
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($except) {
            $sql .= " AND {$idColumn} != ?";
            $params[] = $except;
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return true;
        }
        
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] == 0;
    }
    
    /**
     * Validate exists constraint
     */
    protected function validateExists($field, $value, $ruleValue) {
        if (empty($value)) {
            return true;
        }
        
        // Parse rule value (table:column)
        $parts = explode(',', $ruleValue);
        $table = $parts[0];
        $column = isset($parts[1]) ? $parts[1] : $field;
        
        if (!$this->conn) {
            return true; // Can't validate without connection
        }
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return true;
        }
        
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Check if a date is valid
     */
    protected function isValidDate($date) {
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'm/d/Y', 'd/m/Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get field display name
     */
    protected function getFieldDisplayName($field) {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $field));
    }
    
    /**
     * Get validation errors
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }
    
    /**
     * Get first validation error for a field
     */
    public function getFirstError($field = null) {
        if ($field) {
            return isset($this->validationErrors[$field]) ? $this->validationErrors[$field][0] : null;
        }
        
        foreach ($this->validationErrors as $errors) {
            if (!empty($errors)) {
                return $errors[0];
            }
        }
        
        return null;
    }
    
    /**
     * Check if validation has errors
     */
    public function hasErrors($field = null) {
        if ($field) {
            return isset($this->validationErrors[$field]) && !empty($this->validationErrors[$field]);
        }
        
        return !empty($this->validationErrors);
    }
    
    /**
     * Get validated data
     */
    public function getValidatedData() {
        return $this->validatedData;
    }
    
    /**
     * Clear validation errors
     */
    public function clearValidationErrors() {
        $this->validationErrors = [];
        $this->validatedData = [];
    }
    
    /**
     * Add custom validation error
     */
    public function addValidationError($field, $message) {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }
        
        $this->validationErrors[$field][] = $message;
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeData($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validate password strength
     */
    protected function validatePasswordStrength($field, $value, $ruleValue) {
        if (empty($value)) {
            return true;
        }
        
        $minLength = 8;
        $requireUpper = true;
        $requireLower = true;
        $requireNumber = true;
        $requireSpecial = false;
        
        // Parse rule parameters
        if ($ruleValue) {
            $params = explode(',', $ruleValue);
            $minLength = isset($params[0]) ? (int) $params[0] : $minLength;
            $requireUpper = isset($params[1]) ? $params[1] === 'true' : $requireUpper;
            $requireLower = isset($params[2]) ? $params[2] === 'true' : $requireLower;
            $requireNumber = isset($params[3]) ? $params[3] === 'true' : $requireNumber;
            $requireSpecial = isset($params[4]) ? $params[4] === 'true' : $requireSpecial;
        }
        
        $errors = [];
        
        if (strlen($value) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        if ($requireUpper && !preg_match('/[A-Z]/', $value)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($requireLower && !preg_match('/[a-z]/', $value)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($requireNumber && !preg_match('/\d/', $value)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($requireSpecial && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addValidationError($field, $error);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    protected function validatePhone($field, $value, $ruleValue) {
        if (empty($value)) {
            return true;
        }
        
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $value);
        
        // Check if it's a valid length (10-15 digits)
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }
    
    /**
     * Validate South African ID number
     */
    protected function validateSaId($field, $value, $ruleValue) {
        if (empty($value)) {
            return true;
        }
        
        // Remove spaces and check length
        $id = preg_replace('/\s+/', '', $value);
        
        if (strlen($id) !== 13 || !ctype_digit($id)) {
            return false;
        }
        
        // Validate checksum using Luhn algorithm
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            if ($i % 2 === 0) {
                $sum += (int) $id[$i];
            } else {
                $doubled = (int) $id[$i] * 2;
                $sum += $doubled > 9 ? $doubled - 9 : $doubled;
            }
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $checkDigit === (int) $id[12];
    }
}