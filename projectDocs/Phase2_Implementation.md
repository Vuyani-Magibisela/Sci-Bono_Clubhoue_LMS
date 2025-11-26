# Phase 2: Security Hardening Implementation Guide
## Input Validation, CSRF Protection & File Upload Security

**Duration**: Weeks 2-3
**Priority**: HIGH
**Dependencies**: Phase 1 (Configuration & Error Handling)
**Team Size**: 1-2 developers

---

## Implementation Status

**Last Updated:** November 10, 2025
**Overall Phase 2 Completion:** 85% (Near Complete)

### ✅ Completed Tasks
- [x] **Task 1**: Input Validation System (Completed Sep 3, 2025)
- [x] **Task 2**: CSRF Protection Infrastructure (Completed Sep 3, 2025)
- [x] **Task 3**: Security Middleware (Completed Sep 3, 2025)
- [x] **Task 4**: Secure File Upload System (Completed Sep 3, 2025)
- [x] **Task 5**: Rate Limiting Implementation (Completed Sep 3, 2025)
- [x] **Task 6**: Form-Level CSRF Protection (Completed Nov 10, 2025)
  - ✅ 20 files updated with CSRF protection
  - ✅ 27+ forms secured across application
  - ✅ See `/CSRF_PROTECTION_IMPLEMENTATION_COMPLETE.md` for details

### ⚠️ Pending Tasks
- [ ] **Task 7**: Controller-Level CSRF Validation (NOT STARTED)
  - Holiday Program controllers
  - Admin controllers
  - Other POST/PUT/DELETE handlers
- [ ] **Task 8**: Comprehensive Security Testing (PARTIAL)

---

## Overview

Phase 2 focuses on implementing comprehensive security measures to protect against common web vulnerabilities. This phase builds upon Phase 1's error handling and configuration systems to create a robust security framework.

### Key Objectives
- ✅ Implement comprehensive input validation system
- ✅ Add CSRF protection to all forms and state-changing operations
- ✅ Create secure file upload system with malware scanning
- ✅ Implement security middleware for HTTP headers
- ✅ Add rate limiting for authentication and API endpoints
- ✅ Establish security monitoring and logging

---

## Pre-Implementation Checklist

- [ ] **Phase 1 Complete**: Verify configuration and logging systems are working
- [ ] **Security Audit**: Review current forms and file upload points
- [ ] **Backup System**: Create backup before security modifications
- [ ] **Test Environment**: Set up isolated testing environment
- [ ] **Dependencies**: Ensure all Phase 1 files are in place

---

## Task 1: Input Validation System

### 1.1 Create Validation Framework
**File**: `core/Validator.php`

```php
<?php
/**
 * Input Validation System - Comprehensive validation with security focus
 * Phase 2 Implementation
 */

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
```

### 1.2 Create Validation Helper Functions
**File**: `core/ValidationHelpers.php`

```php
<?php
/**
 * Validation Helper Functions - Common validation utilities
 * Phase 2 Implementation
 */

class ValidationHelpers {
    
    /**
     * Validate South African ID Number
     */
    public static function validateSAIdNumber($idNumber) {
        if (strlen($idNumber) !== 13) {
            return false;
        }
        
        if (!ctype_digit($idNumber)) {
            return false;
        }
        
        // Luhn algorithm check
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($idNumber[$i]);
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = intval($digit / 10) + ($digit % 10);
                }
            }
            
            $sum += $digit;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return intval($idNumber[12]) === $checkDigit;
    }
    
    /**
     * Validate South African cell phone number
     */
    public static function validateSACellNumber($number) {
        // Remove spaces and common separators
        $number = preg_replace('/[\s\-\(\)]/', '', $number);
        
        // South African cell numbers: +27 or 0, followed by specific prefixes
        $pattern = '/^(?:\+27|0)(?:6[0-9]|7[0-9]|8[1-9])[0-9]{7}$/';
        
        return preg_match($pattern, $number);
    }
    
    /**
     * Check if input contains SQL injection patterns
     */
    public static function checkSQLInjection($input) {
        $sqlPatterns = [
            '/(\bunion\s+select)/i',
            '/(\bselect\s+.*\bfrom)/i',
            '/(\binsert\s+into)/i',
            '/(\bupdate\s+.*\bset)/i',
            '/(\bdelete\s+from)/i',
            '/(\bdrop\s+table)/i',
            '/(\btruncate\s+table)/i',
            '/(\balter\s+table)/i',
            '/(\'|\";?\s*(or|and)\s*\')/i',
            '/(\s*(or|and)\s+\d+\s*=\s*\d+)/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize HTML while preserving safe tags
     */
    public static function sanitizeHTML($html, $allowedTags = ['p', 'br', 'strong', 'em']) {
        $allowedString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $allowedString);
    }
}
```

---

## Task 2: CSRF Protection Implementation

### 2.1 Create CSRF Protection System
**File**: `core/CSRF.php`

```php
<?php
/**
 * CSRF Protection System
 * Phase 2 Implementation
 */

class CSRF {
    private static $tokenName = '_csrf_token';
    private static $headerName = 'X-CSRF-TOKEN';
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$tokenName] = $token;
        
        return $token;
    }
    
    /**
     * Get current CSRF token
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION[self::$tokenName] ?? '';
        
        if ($token === null) {
            // Try to get token from various sources
            $token = $_POST[self::$tokenName] ?? 
                    $_GET[self::$tokenName] ?? 
                    $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(self::$headerName))] ?? '';
        }
        
        return !empty($sessionToken) && hash_equals($sessionToken, $token);
    }
    
    /**
     * Create hidden input field for forms
     */
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get token for JavaScript usage
     */
    public static function token() {
        return self::getToken();
    }
    
    /**
     * Create meta tag for HTML head
     */
    public static function metaTag() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Middleware to check CSRF token
     */
    public static function check() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only check for state-changing requests
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!self::validateToken()) {
                // Log the CSRF violation
                $logger = new Logger();
                $logger->warning('CSRF token validation failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'method' => $method,
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
                
                // Return error response
                if (self::isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode([
                        'error' => true,
                        'message' => 'CSRF token validation failed',
                        'code' => 'CSRF_ERROR'
                    ]);
                } else {
                    http_response_code(403);
                    require_once __DIR__ . '/../app/Views/errors/403.php';
                }
                
                exit;
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Regenerate token (use after successful form submission)
     */
    public static function regenerateToken() {
        return self::generateToken();
    }
}
```

### 2.2 Create 403 Forbidden Error Page
**File**: `app/Views/errors/403.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - Sci-Bono Clubhouse LMS</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .error-code { font-size: 120px; color: #dc3545; margin: 0; font-weight: bold; }
        .error-message { font-size: 24px; color: #333; margin: 20px 0; }
        .error-description { color: #666; margin: 20px 0; line-height: 1.6; }
        .back-link { display: inline-block; background: #F29A2E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; transition: background 0.3s; }
        .back-link:hover { background: #E28A26; }
        .security-notice { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0; text-align: left; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">403</h1>
        <h2 class="error-message">Access Forbidden</h2>
        <div class="security-notice">
            <strong>Security Notice:</strong> Your request was blocked for security reasons. This may be due to:
            <ul style="margin: 10px 0; text-align: left;">
                <li>Invalid or missing security token</li>
                <li>Insufficient permissions</li>
                <li>Suspicious activity detected</li>
            </ul>
        </div>
        <p class="error-description">
            If you believe this is an error, please try refreshing the page or contact the system administrator.
        </p>
        <a href="/" class="back-link">Return Home</a>
    </div>
</body>
</html>
```

---

## Task 3: Security Middleware

### 3.1 Create Security Middleware
**File**: `app/Middleware/SecurityMiddleware.php`

```php
<?php
/**
 * Security Middleware - HTTP security headers and CSRF protection
 * Phase 2 Implementation
 */

require_once __DIR__ . '/../../core/CSRF.php';

class SecurityMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        $this->setSecurityHeaders();
        $this->checkCSRF();
        $this->detectSuspiciousActivity();
        
        return true;
    }
    
    private function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // HTTPS enforcement (if HTTPS is available)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy (basic)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';";
        header("Content-Security-Policy: {$csp}");
    }
    
    private function checkCSRF() {
        // Skip CSRF check for safe methods and specific paths
        if ($this->shouldSkipCSRF()) {
            return;
        }
        
        CSRF::check();
    }
    
    private function shouldSkipCSRF() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        
        // Skip for safe HTTP methods
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }
        
        // Skip for specific API endpoints that use alternative auth
        $skipPaths = [
            '/api/webhook/',  // External webhooks
            '/api/callback/', // OAuth callbacks
        ];
        
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectSuspiciousActivity() {
        $suspiciousIndicators = [];
        
        // Check for SQL injection patterns
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && ValidationHelpers::checkSQLInjection($value)) {
                $suspiciousIndicators[] = "SQL injection pattern in {$key}";
            }
        }
        
        // Check for XSS patterns
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && $this->containsXSS($value)) {
                $suspiciousIndicators[] = "XSS pattern in {$key}";
            }
        }
        
        // Check for directory traversal
        foreach ($_REQUEST as $key => $value) {
            if (is_string($value) && $this->containsDirectoryTraversal($value)) {
                $suspiciousIndicators[] = "Directory traversal in {$key}";
            }
        }
        
        // Log suspicious activity
        if (!empty($suspiciousIndicators)) {
            $this->logger->warning('Suspicious activity detected', [
                'indicators' => $suspiciousIndicators,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'data' => $this->sanitizeLogData($_REQUEST)
            ]);
            
            // Optional: Block request if too many indicators
            if (count($suspiciousIndicators) >= 3) {
                http_response_code(403);
                exit('Suspicious activity detected');
            }
        }
    }
    
    private function containsXSS($input) {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/expression\(/i',
            '/vbscript:/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function containsDirectoryTraversal($input) {
        $traversalPatterns = [
            '/\.\.\//i',
            '/\.\.\\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/\.\.%2f/i',
            '/\.\.%5c/i'
        ];
        
        foreach ($traversalPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function sanitizeLogData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && strlen($value) > 100) {
                $sanitized[$key] = substr($value, 0, 100) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
```

---

## Task 4: Secure File Upload System

### 4.1 Create File Upload Security System
**File**: `core/SecureFileUploader.php`

```php
<?php
/**
 * Secure File Upload System with malware scanning
 * Phase 2 Implementation
 */

class SecureFileUploader {
    private $config;
    private $logger;
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    
    public function __construct() {
        require_once __DIR__ . '/../config/ConfigLoader.php';
        $this->config = ConfigLoader::get('app.uploads');
        $this->logger = new Logger();
        
        $this->allowedTypes = $this->config['allowed_types'];
        $this->maxSize = $this->config['max_size'];
        $this->uploadPath = $this->config['path'];
        
        $this->ensureUploadDirectoryExists();
    }
    
    /**
     * Upload file with comprehensive security checks
     */
    public function upload($file, $customPath = null) {
        try {
            $this->validateFile($file);
            
            $uploadDir = $customPath ?? $this->generateSecureUploadPath();
            $filename = $this->generateSecureFilename($file['name']);
            $fullPath = $uploadDir . $filename;
            
            // Create quarantine area for scanning
            $tempPath = sys_get_temp_dir() . '/' . uniqid('upload_', true);
            
            if (move_uploaded_file($file['tmp_name'], $tempPath)) {
                // Perform security scans
                $this->scanForMalware($tempPath);
                $this->scanFileContent($tempPath, $file['name']);
                
                // Move to final location if safe
                if (rename($tempPath, $fullPath)) {
                    chmod($fullPath, 0644);
                    
                    $this->logger->info('File uploaded successfully', [
                        'original_name' => $file['name'],
                        'saved_path' => $fullPath,
                        'size' => $file['size'],
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    return [
                        'success' => true,
                        'path' => $fullPath,
                        'url' => $this->getFileUrl($fullPath),
                        'filename' => $filename
                    ];
                }
            }
            
            throw new Exception('Failed to move uploaded file');
            
        } catch (Exception $e) {
            // Clean up temporary files
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            $this->logger->error('File upload failed', [
                'error' => $e->getMessage(),
                'file_info' => [
                    'name' => $file['name'] ?? 'unknown',
                    'size' => $file['size'] ?? 0,
                    'type' => $file['type'] ?? 'unknown'
                ],
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1048576, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File was not uploaded via HTTP POST');
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes));
        }
        
        // Check filename for dangerous characters
        if (preg_match('/[<>:"\/\\|?*]/', $file['name'])) {
            throw new Exception('Filename contains dangerous characters');
        }
        
        // Check for null bytes in filename
        if (strpos($file['name'], "\0") !== false) {
            throw new Exception('Filename contains null bytes');
        }
        
        // Validate MIME type
        $this->validateMimeType($file['tmp_name'], $extension);
    }
    
    private function validateMimeType($tmpName, $extension) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
        
        if (isset($allowedMimes[$extension])) {
            if (!in_array($mimeType, $allowedMimes[$extension])) {
                throw new Exception("File MIME type ({$mimeType}) does not match extension ({$extension})");
            }
        }
    }
    
    private function scanForMalware($filePath) {
        // Basic malware patterns scan
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new Exception('Cannot read uploaded file for scanning');
        }
        
        $chunkSize = 8192;
        $content = '';
        
        // Read first 64KB for scanning
        for ($i = 0; $i < 8 && !feof($handle); $i++) {
            $content .= fread($handle, $chunkSize);
        }
        fclose($handle);
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/(?:eval|exec|system|shell_exec|passthru|file_get_contents|fopen|fwrite|include|require)\\s*\\(/i',
            '/<\\?php/i',
            '/<script[^>]*>.*?<\\/script>/is',
            '/javascript\\s*:/i',
            '/data\\s*:\\s*[^\\s;]+\\s*;\\s*base64/i',
            '/\\\\x[0-9a-f]{2}/i',
            '/%[0-9a-f]{2}/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->logger->critical('Malware detected in uploaded file', [
                    'pattern' => $pattern,
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                throw new Exception('Potentially malicious content detected in file');
            }
        }
    }
    
    private function scanFileContent($filePath, $originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Additional checks for image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $this->validateImageFile($filePath);
        }
        
        // Check for embedded executables
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            
            // Check for PE executable headers
            if ($header === "MZ\x90\x00" || $header === "PK\x03\x04") {
                throw new Exception('Executable content detected in uploaded file');
            }
        }
    }
    
    private function validateImageFile($filePath) {
        // Verify it's actually an image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            throw new Exception('File is not a valid image');
        }
        
        // Check for reasonable dimensions (prevent memory exhaustion)
        if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
            throw new Exception('Image dimensions are too large');
        }
        
        // Try to create image resource to verify integrity
        $imageType = $imageInfo[2];
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $resource = @imagecreatefromgif($filePath);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        if ($resource === false) {
            throw new Exception('Corrupted or malicious image file');
        }
        
        imagedestroy($resource);
    }
    
    private function generateSecureUploadPath() {
        $datePath = date('Y-m');
        $fullPath = rtrim($this->uploadPath, '/') . '/' . $datePath . '/';
        
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        return $fullPath;
    }
    
    private function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = trim($basename, '_-');
        $basename = substr($basename, 0, 50);
        
        if (empty($basename)) {
            $basename = 'file';
        }
        
        // Add timestamp and random string
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        
        return $timestamp . '_' . $randomString . '_' . $basename . '.' . $extension;
    }
    
    private function getFileUrl($filePath) {
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
        $baseUrl = ConfigLoader::get('app.url');
        return rtrim($baseUrl, '/') . '/' . ltrim($relativePath, '/');
    }
    
    private function ensureUploadDirectoryExists() {
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                throw new Exception('Cannot create upload directory');
            }
        }
        
        // Create .htaccess file to prevent direct execution
        $htaccessFile = rtrim($this->uploadPath, '/') . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\nOptions -Indexes\n";
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
```

---

## Task 5: Rate Limiting Implementation

### 5.1 Create Rate Limiting System
**File**: `app/Middleware/RateLimitMiddleware.php`

```php
<?php
/**
 * Rate Limiting Middleware - Prevent abuse and brute force attacks
 * Phase 2 Implementation
 */

class RateLimitMiddleware {
    private $conn;
    private $logger;
    private $limits = [
        'login' => ['requests' => 5, 'window' => 300],     // 5 attempts per 5 minutes
        'signup' => ['requests' => 3, 'window' => 3600],   // 3 signups per hour
        'api' => ['requests' => 60, 'window' => 60],       // 60 requests per minute
        'upload' => ['requests' => 10, 'window' => 300],   // 10 uploads per 5 minutes
        'default' => ['requests' => 30, 'window' => 60]    // 30 requests per minute
    ];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
        $this->createRateLimitTable();
    }
    
    public function handle($action = 'default') {
        $identifier = $this->getIdentifier();
        $limit = $this->limits[$action] ?? $this->limits['default'];
        
        if ($this->isRateLimited($identifier, $action, $limit)) {
            $this->handleRateLimit($action, $limit);
            return false;
        }
        
        $this->recordRequest($identifier, $action);
        return true;
    }
    
    private function getIdentifier() {
        $ip = $this->getRealIP();
        $userId = $_SESSION['user_id'] ?? null;
        
        // Use user ID if available, otherwise use IP
        return $userId ? "user_{$userId}" : "ip_{$ip}";
    }
    
    private function getRealIP() {
        // Handle various proxy scenarios
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    private function isRateLimited($identifier, $action, $limit) {
        $windowStart = time() - $limit['window'];
        
        $sql = "SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE identifier = ? 
                AND action = ? 
                AND timestamp > ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->logger->error('Rate limit database error', ['error' => $this->conn->error]);
            return false; // Fail open for database issues
        }
        
        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $isLimited = $row['request_count'] >= $limit['requests'];
        
        if ($isLimited) {
            $this->logger->warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'action' => $action,
                'requests' => $row['request_count'],
                'limit' => $limit['requests'],
                'window' => $limit['window'],
                'ip' => $this->getRealIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
        
        return $isLimited;
    }
    
    private function handleRateLimit($action, $limit) {
        $retryAfter = $limit['window'];
        
        header("Retry-After: {$retryAfter}");
        http_response_code(429);
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'action' => $action
            ]);
        } else {
            $this->showRateLimitPage($retryAfter);
        }
        
        exit;
    }
    
    private function showRateLimitPage($retryAfter) {
        $minutes = ceil($retryAfter / 60);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Rate Limit Exceeded - Sci-Bono Clubhouse LMS</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .code { font-size: 80px; color: #ffc107; margin: 0; }
        .message { font-size: 24px; margin: 20px 0; }
        .description { color: #666; margin: 20px 0; }
        .back-link { display: inline-block; background: #F29A2E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="code">429</h1>
        <h2 class="message">Rate Limit Exceeded</h2>
        <p class="description">
            You have made too many requests. Please wait ' . $minutes . ' minute(s) before trying again.
        </p>
        <a href="/" class="back-link">Return Home</a>
    </div>
</body>
</html>';
    }
    
    private function recordRequest($identifier, $action) {
        $sql = "INSERT INTO rate_limits (identifier, action, timestamp, ip, user_agent) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logger->error('Failed to record rate limit entry', ['error' => $this->conn->error]);
            return;
        }
        
        $timestamp = time();
        $ip = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->bind_param("ssiss", $identifier, $action, $timestamp, $ip, $userAgent);
        $stmt->execute();
        
        // Occasionally clean up old records (1% chance)
        if (rand(1, 100) === 1) {
            $this->cleanupOldRecords();
        }
    }
    
    private function cleanupOldRecords() {
        $oldestWindow = max(array_column($this->limits, 'window'));
        $cutoffTime = time() - ($oldestWindow * 2); // Keep records for twice the longest window
        
        $sql = "DELETE FROM rate_limits WHERE timestamp < ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $cutoffTime);
            $stmt->execute();
        }
    }
    
    private function createRateLimitTable() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp INT NOT NULL,
            ip VARCHAR(45),
            user_agent TEXT,
            INDEX idx_rate_limits (identifier, action, timestamp),
            INDEX idx_cleanup (timestamp)
        ) ENGINE=InnoDB";
        
        if (!$this->conn->query($sql)) {
            $this->logger->error('Failed to create rate_limits table', ['error' => $this->conn->error]);
        }
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Get remaining requests for an identifier/action
     */
    public function getRemainingRequests($action = 'default') {
        $identifier = $this->getIdentifier();
        $limit = $this->limits[$action] ?? $this->limits['default'];
        $windowStart = time() - $limit['window'];
        
        $sql = "SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE identifier = ? 
                AND action = ? 
                AND timestamp > ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $identifier, $action, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return max(0, $limit['requests'] - $row['request_count']);
    }
}
```

---

## Phase 2 Integration Tasks

### Task 6: Update Forms with CSRF Protection

### ✅ COMPLETED: November 10, 2025

**Files Updated:** 20 files
**Forms Protected:** 27+ forms
**Documentation:** `/CSRF_PROTECTION_IMPLEMENTATION_COMPLETE.md`

**Implementation Summary:**
- ✅ 5 Holiday Program forms (registration, creation, login, password setup)
- ✅ 12 Admin forms (user edit, course management, lessons, modules, activities)
- ✅ 10 Other critical forms (attendance, settings, reports, visitors)

**Pattern Applied:**
1. Added CSRF class include at file top
2. Added CSRF meta tag in `<head>` section
3. Added CSRF hidden field in all `<form>` tags
4. Added server-side validation (where applicable)

---

#### 6.1 Update Login Form (EXAMPLE - COMPLETED)
**Update**: `login.php` (Add CSRF protection)

```php
<?php
// Add to the top of login.php
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/app/Middleware/RateLimitMiddleware.php';

// Rate limiting check
$rateLimiter = new RateLimitMiddleware($conn);
if (!$rateLimiter->handle('login')) {
    exit; // Rate limit exceeded
}
?>

<!-- Add to HTML head section -->
<?php echo CSRF::metaTag(); ?>

<!-- Add to login form -->
<?php echo CSRF::field(); ?>
```

#### 6.2 Update Login Processing
**Update**: `login_process.php`

```php
<?php
session_start();
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/CSRF.php';

// Validate CSRF token
if (!CSRF::validateToken()) {
    header('Location: login.php?error=invalid_token');
    exit;
}

// Validate input
$validator = new Validator($_POST);
$isValid = $validator->validate([
    'username' => 'required|alpha_dash|min:3|max:50|no_script',
    'password' => 'required|min:1|max:255'
]);

if (!$isValid) {
    $_SESSION['errors'] = $validator->errors();
    header('Location: login.php?error=validation_failed');
    exit;
}

$validatedData = $validator->getValidatedData();

// Continue with existing login logic...
// Remember to regenerate CSRF token after successful login
CSRF::regenerateToken();
?>
```

### Task 7: JavaScript CSRF Integration

#### 7.1 Create CSRF JavaScript Helper
**File**: `public/assets/js/csrf.js`

```javascript
/**
 * CSRF Token Management for AJAX requests
 * Phase 2 Implementation
 */

class CSRFManager {
    constructor() {
        this.token = this.getTokenFromMeta();
        this.setupAjaxDefaults();
    }
    
    getTokenFromMeta() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }
    
    getToken() {
        return this.token;
    }
    
    updateToken(newToken) {
        this.token = newToken;
        
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Update all hidden form fields
        const hiddenFields = document.querySelectorAll('input[name="_csrf_token"]');
        hiddenFields.forEach(field => field.value = newToken);
    }
    
    setupAjaxDefaults() {
        // jQuery setup if available
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.token
                }
            });
        }
        
        // Fetch API setup
        const originalFetch = window.fetch;
        window.fetch = (url, options = {}) => {
            if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = this.getToken();
            }
            return originalFetch(url, options);
        };
    }
    
    addToForm(form) {
        const existingField = form.querySelector('input[name="_csrf_token"]');
        if (existingField) {
            existingField.value = this.token;
        } else {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_csrf_token';
            field.value = this.token;
            form.appendChild(field);
        }
    }
    
    addToFormData(formData) {
        formData.append('_csrf_token', this.token);
        return formData;
    }
}

// Initialize CSRF manager
const csrfManager = new CSRFManager();

// Make it globally available
window.CSRFManager = csrfManager;
```

### Task 8: Update File Upload Forms

#### 8.1 Update File Upload Processing
**Create**: `handlers/secure-upload-handler.php`

```php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/SecureFileUploader.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../app/Middleware/RateLimitMiddleware.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// CSRF check
if (!CSRF::validateToken()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Rate limiting
$rateLimiter = new RateLimitMiddleware($conn);
if (!$rateLimiter->handle('upload')) {
    exit; // Rate limit response handled by middleware
}

// Process file upload
if (isset($_FILES['file'])) {
    $uploader = new SecureFileUploader();
    $result = $uploader->upload($_FILES['file']);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file provided']);
}
?>
```

---

## Phase 2 Testing & Verification

### Task 9: Security Testing Checklist

#### 9.1 CSRF Protection Tests
- [ ] Verify CSRF tokens are generated and validated
- [ ] Test form submission without CSRF token (should fail)
- [ ] Test AJAX requests include CSRF headers
- [ ] Verify token regeneration after login/logout

#### 9.2 Input Validation Tests
- [ ] Test SQL injection patterns (should be blocked)
- [ ] Test XSS attempts (should be sanitized/blocked)
- [ ] Test directory traversal attempts (should be blocked)
- [ ] Verify proper error messages for validation failures

#### 9.3 File Upload Security Tests
- [ ] Upload malicious PHP file (should be blocked)
- [ ] Upload file with script content (should be blocked)
- [ ] Upload oversized file (should be blocked)
- [ ] Upload file with dangerous filename (should be sanitized)
- [ ] Verify uploaded files cannot be executed

#### 9.4 Rate Limiting Tests
- [ ] Exceed login rate limit (should be blocked)
- [ ] Test rate limiting for different actions
- [ ] Verify rate limit resets after time window
- [ ] Test rate limiting for both authenticated and anonymous users

---

## Phase 2 Completion Checklist

**Last Updated:** November 10, 2025
**Overall Status:** 85% Complete

### Security Infrastructure
- [x] Implemented `Validator` class with comprehensive rules ✅
- [x] Created `CSRF` protection system ✅
- [x] Implemented `SecurityMiddleware` with HTTP headers ✅
- [x] Created `SecureFileUploader` with malware scanning ✅
- [x] Implemented `RateLimitMiddleware` system ✅

### Form Security
- [x] Added CSRF tokens to all forms (27+ forms across 20 files) ✅
- [x] Updated form processing to validate CSRF tokens (view-level) ✅
- [ ] Controller-level CSRF validation (PENDING) ⚠️
- [x] Implemented input validation on all user inputs ✅
- [x] Added JavaScript CSRF helper for AJAX requests ✅

### File Upload Security
- [x] Secured all file upload endpoints ✅
- [x] Implemented comprehensive file validation ✅
- [x] Added malware scanning capabilities ✅
- [x] Created secure file naming and storage ✅

### Monitoring & Logging
- [x] Security violations are logged ✅
- [x] Rate limit violations are tracked ✅
- [x] Suspicious activity is monitored and reported ✅
- [x] Error responses don't leak sensitive information ✅

---

## Next Phase Preparation

Before proceeding to Phase 3 (Routing System):
1. **Comprehensive Testing**: Test all security measures
2. **Performance Check**: Ensure security additions don't impact performance significantly
3. **User Experience**: Verify security measures don't interfere with normal usage
4. **Documentation**: Document any custom security configurations
5. **Backup**: Create backup with all Phase 2 security improvements

**Phase 2 establishes critical security foundations. Do not proceed to Phase 3 until all security measures are tested and working correctly.**