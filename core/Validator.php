<?php
/**
 * Input Validation System - Comprehensive validation with security focus
 * Phase 2 Implementation
 */

require_once __DIR__ . '/Logger.php';

class Validator {
    private $data = [];
    private $rules = [];
    private $errors = [];
    private $logger;
    
    public function __construct($data = []) {
        $this->data = $this->sanitizeData($data);
        $this->logger = new Logger();
    }
    
    /**
     * Validate data against rules
     */
    public function validate($rules) {
        $this->rules = $rules;
        $this->errors = [];
        
        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
        
        // Log validation attempts for security monitoring
        if (!empty($this->errors)) {
            $this->logger->warning('Validation failed', [
                'fields' => array_keys($this->errors),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
        
        return empty($this->errors);
    }
    
    public function errors() {
        return $this->errors;
    }
    
    public function firstError() {
        $firstField = array_key_first($this->errors);
        return $firstField ? $this->errors[$firstField][0] : null;
    }
    
    public function getValidatedData() {
        $validated = [];
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }
    
    private function sanitizeData($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }
        
        if (is_string($data)) {
            // Remove null bytes
            $data = str_replace("\0", '', $data);
            // Trim whitespace
            $data = trim($data);
        }
        
        return $data;
    }
    
    private function validateField($field, $rules) {
        $value = $this->data[$field] ?? null;
        $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
        
        foreach ($rulesArray as $rule) {
            if (!$this->validateRule($field, $value, $rule)) {
                break; // Stop on first validation failure for this field
            }
        }
    }
    
    private function validateRule($field, $value, $rule) {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }
        
        switch ($ruleName) {
            case 'required':
                return $this->validateRequired($field, $value);
                
            case 'email':
                return $this->validateEmail($field, $value);
                
            case 'min':
                return $this->validateMin($field, $value, $parameter);
                
            case 'max':
                return $this->validateMax($field, $value, $parameter);
                
            case 'numeric':
                return $this->validateNumeric($field, $value);
                
            case 'integer':
                return $this->validateInteger($field, $value);
                
            case 'alpha':
                return $this->validateAlpha($field, $value);
                
            case 'alpha_num':
                return $this->validateAlphaNum($field, $value);
                
            case 'alpha_dash':
                return $this->validateAlphaDash($field, $value);
                
            case 'regex':
                return $this->validateRegex($field, $value, $parameter);
                
            case 'in':
                return $this->validateIn($field, $value, $parameter);
                
            case 'unique':
                return $this->validateUnique($field, $value, $parameter);
                
            case 'confirmed':
                return $this->validateConfirmed($field, $value);
                
            case 'password':
                return $this->validatePassword($field, $value);
                
            case 'safe_filename':
                return $this->validateSafeFilename($field, $value);
                
            case 'no_script':
                return $this->validateNoScript($field, $value);
                
            default:
                return true;
        }
    }
    
    private function validateRequired($field, $value) {
        if (empty($value)) {
            $this->errors[$field][] = "The {$field} field is required.";
            return false;
        }
        return true;
    }
    
    private function validateEmail($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "The {$field} must be a valid email address.";
            return false;
        }
        return true;
    }
    
    private function validateMin($field, $value, $min) {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field][] = "The {$field} must be at least {$min} characters.";
            return false;
        }
        return true;
    }
    
    private function validateMax($field, $value, $max) {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field][] = "The {$field} may not be greater than {$max} characters.";
            return false;
        }
        return true;
    }
    
    private function validateNumeric($field, $value) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "The {$field} must be a number.";
            return false;
        }
        return true;
    }
    
    private function validateInteger($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = "The {$field} must be an integer.";
            return false;
        }
        return true;
    }
    
    private function validateAlpha($field, $value) {
        if (!empty($value) && !preg_match('/^[a-zA-Z]+$/', $value)) {
            $this->errors[$field][] = "The {$field} may only contain letters.";
            return false;
        }
        return true;
    }
    
    private function validateAlphaNum($field, $value) {
        if (!empty($value) && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            $this->errors[$field][] = "The {$field} may only contain letters and numbers.";
            return false;
        }
        return true;
    }
    
    private function validateAlphaDash($field, $value) {
        if (!empty($value) && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->errors[$field][] = "The {$field} may only contain letters, numbers, dashes and underscores.";
            return false;
        }
        return true;
    }
    
    private function validateRegex($field, $value, $pattern) {
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$field][] = "The {$field} format is invalid.";
            return false;
        }
        return true;
    }
    
    private function validateIn($field, $value, $options) {
        $optionsList = explode(',', $options);
        if (!empty($value) && !in_array($value, $optionsList)) {
            $this->errors[$field][] = "The selected {$field} is invalid.";
            return false;
        }
        return true;
    }
    
    private function validateUnique($field, $value, $parameter) {
        if (empty($value)) {
            return true;
        }
        
        $parts = explode(',', $parameter);
        $table = $parts[0];
        $column = $parts[1] ?? $field;
        $exceptId = $parts[2] ?? null;
        
        global $conn;
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        $types = "s";
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $this->errors[$field][] = "The {$field} has already been taken.";
            return false;
        }
        
        return true;
    }
    
    private function validateConfirmed($field, $value) {
        $confirmationField = $field . '_confirmation';
        if ($value !== ($this->data[$confirmationField] ?? null)) {
            $this->errors[$field][] = "The {$field} confirmation does not match.";
            return false;
        }
        return true;
    }
    
    private function validatePassword($field, $value) {
        if (empty($value)) {
            return true; // Let 'required' rule handle empty values
        }
        
        $errors = [];
        
        if (strlen($value) < 8) {
            $errors[] = 'must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'must contain at least one number';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            $errors[] = 'must contain at least one special character';
        }
        
        if (!empty($errors)) {
            $this->errors[$field][] = "The {$field} " . implode(', ', $errors) . '.';
            return false;
        }
        
        return true;
    }
    
    private function validateSafeFilename($field, $value) {
        if (!empty($value) && !preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
            $this->errors[$field][] = "The {$field} contains unsafe characters.";
            return false;
        }
        return true;
    }
    
    private function validateNoScript($field, $value) {
        if (!empty($value)) {
            $dangerous = ['<script', 'javascript:', 'eval(', 'expression(', 'onload=', 'onerror='];
            foreach ($dangerous as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    $this->errors[$field][] = "The {$field} contains potentially dangerous content.";
                    
                    // Log security violation
                    $this->logger->warning('Potential XSS attempt detected', [
                        'field' => $field,
                        'pattern' => $pattern,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Static helper methods
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeFilename($filename) {
        // Remove directory traversal attempts
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        return substr($filename, 0, 255);
    }
}