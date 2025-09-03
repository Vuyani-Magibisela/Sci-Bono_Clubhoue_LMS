<?php
/**
 * LogsActivity Trait - Activity logging functionality
 * Phase 4 Implementation
 */

trait LogsActivity {
    /**
     * Logger instance
     */
    protected $activityLogger;
    
    /**
     * Enable or disable activity logging
     */
    protected $logsActivity = true;
    
    /**
     * Activities to log
     */
    protected $loggedActivities = ['create', 'update', 'delete'];
    
    /**
     * Activities to ignore
     */
    protected $ignoredActivities = [];
    
    /**
     * Additional data to include in logs
     */
    protected $logAttributes = [];
    
    /**
     * Initialize activity logger
     */
    protected function initializeActivityLogger() {
        if (!$this->activityLogger) {
            $this->activityLogger = new Logger();
        }
    }
    
    /**
     * Log an activity
     */
    public function logActivity($activity, $data = [], $level = 'info') {
        if (!$this->shouldLogActivity($activity)) {
            return;
        }
        
        $this->initializeActivityLogger();
        
        $logData = $this->prepareLogData($activity, $data);
        
        switch ($level) {
            case 'error':
                $this->activityLogger->error($this->formatLogMessage($activity), $logData);
                break;
            case 'warning':
                $this->activityLogger->warning($this->formatLogMessage($activity), $logData);
                break;
            case 'debug':
                $this->activityLogger->debug($this->formatLogMessage($activity), $logData);
                break;
            default:
                $this->activityLogger->info($this->formatLogMessage($activity), $logData);
        }
    }
    
    /**
     * Log successful activity
     */
    public function logSuccess($activity, $data = []) {
        $this->logActivity($activity . '_success', $data, 'info');
    }
    
    /**
     * Log failed activity
     */
    public function logFailure($activity, $data = [], $error = null) {
        if ($error) {
            $data['error'] = $error;
        }
        $this->logActivity($activity . '_failed', $data, 'error');
    }
    
    /**
     * Log warning activity
     */
    public function logWarning($activity, $data = []) {
        $this->logActivity($activity, $data, 'warning');
    }
    
    /**
     * Log debug activity
     */
    public function logDebug($activity, $data = []) {
        $this->logActivity($activity, $data, 'debug');
    }
    
    /**
     * Log user action
     */
    public function logUserAction($action, $userId = null, $data = []) {
        $userId = $userId ?? $this->getCurrentUserId();
        
        $logData = array_merge($data, [
            'user_id' => $userId,
            'action_type' => 'user_action'
        ]);
        
        $this->logActivity("user_{$action}", $logData);
    }
    
    /**
     * Log authentication attempt
     */
    public function logAuthAttempt($identifier, $success = false, $data = []) {
        $logData = array_merge($data, [
            'identifier' => $identifier,
            'success' => $success,
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $activity = $success ? 'auth_success' : 'auth_failure';
        $level = $success ? 'info' : 'warning';
        
        $this->logActivity($activity, $logData, $level);
    }
    
    /**
     * Log database operation
     */
    public function logDatabaseOperation($operation, $table, $data = []) {
        $logData = array_merge($data, [
            'operation' => $operation,
            'table' => $table,
            'operation_type' => 'database'
        ]);
        
        $this->logActivity("db_{$operation}", $logData);
    }
    
    /**
     * Log API request
     */
    public function logApiRequest($endpoint, $method, $data = []) {
        $logData = array_merge($data, [
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_type' => 'api'
        ]);
        
        $this->logActivity("api_request", $logData);
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $severity = 'warning', $data = []) {
        $logData = array_merge($data, [
            'security_event' => $event,
            'severity' => $severity,
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event_type' => 'security'
        ]);
        
        $this->logActivity("security_{$event}", $logData, $severity);
    }
    
    /**
     * Log file operation
     */
    public function logFileOperation($operation, $filename, $data = []) {
        $logData = array_merge($data, [
            'operation' => $operation,
            'filename' => $filename,
            'file_size' => file_exists($filename) ? filesize($filename) : 0,
            'operation_type' => 'file'
        ]);
        
        $this->logActivity("file_{$operation}", $logData);
    }
    
    /**
     * Log system event
     */
    public function logSystemEvent($event, $data = []) {
        $logData = array_merge($data, [
            'system_event' => $event,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'event_type' => 'system'
        ]);
        
        $this->logActivity("system_{$event}", $logData);
    }
    
    /**
     * Log performance metrics
     */
    public function logPerformance($operation, $duration, $data = []) {
        $logData = array_merge($data, [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'operation_type' => 'performance'
        ]);
        
        $level = $duration > 1 ? 'warning' : 'info';
        $this->logActivity("performance_{$operation}", $logData, $level);
    }
    
    /**
     * Determine if activity should be logged
     */
    protected function shouldLogActivity($activity) {
        if (!$this->logsActivity) {
            return false;
        }
        
        if (in_array($activity, $this->ignoredActivities)) {
            return false;
        }
        
        if (!empty($this->loggedActivities)) {
            $baseActivity = str_replace(['_success', '_failed'], '', $activity);
            return in_array($baseActivity, $this->loggedActivities) || 
                   in_array($activity, $this->loggedActivities);
        }
        
        return true;
    }
    
    /**
     * Prepare log data with context
     */
    protected function prepareLogData($activity, $data) {
        $context = [
            'timestamp' => date('Y-m-d H:i:s'),
            'activity' => $activity,
            'class' => get_class($this),
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null
        ];
        
        // Add current user information if available
        if ($userId = $this->getCurrentUserId()) {
            $context['user_id'] = $userId;
            $context['user_type'] = $_SESSION['user_type'] ?? null;
        }
        
        // Add custom log attributes
        foreach ($this->logAttributes as $attribute) {
            if (isset($this->$attribute)) {
                $context[$attribute] = $this->$attribute;
            }
        }
        
        return array_merge($context, $data);
    }
    
    /**
     * Format log message
     */
    protected function formatLogMessage($activity) {
        $class = get_class($this);
        $className = substr($class, strrpos($class, '\\') + 1);
        
        return "{$className}: {$activity}";
    }
    
    /**
     * Get current user ID from session
     */
    protected function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get client IP address
     */
    protected function getClientIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Set logged activities
     */
    public function setLoggedActivities(array $activities) {
        $this->loggedActivities = $activities;
        return $this;
    }
    
    /**
     * Add activity to logged activities
     */
    public function addLoggedActivity($activity) {
        if (!in_array($activity, $this->loggedActivities)) {
            $this->loggedActivities[] = $activity;
        }
        return $this;
    }
    
    /**
     * Set ignored activities
     */
    public function setIgnoredActivities(array $activities) {
        $this->ignoredActivities = $activities;
        return $this;
    }
    
    /**
     * Add activity to ignored activities
     */
    public function addIgnoredActivity($activity) {
        if (!in_array($activity, $this->ignoredActivities)) {
            $this->ignoredActivities[] = $activity;
        }
        return $this;
    }
    
    /**
     * Enable activity logging
     */
    public function enableActivityLogging() {
        $this->logsActivity = true;
        return $this;
    }
    
    /**
     * Disable activity logging
     */
    public function disableActivityLogging() {
        $this->logsActivity = false;
        return $this;
    }
    
    /**
     * Set log attributes to include
     */
    public function setLogAttributes(array $attributes) {
        $this->logAttributes = $attributes;
        return $this;
    }
    
    /**
     * Add log attribute
     */
    public function addLogAttribute($attribute) {
        if (!in_array($attribute, $this->logAttributes)) {
            $this->logAttributes[] = $attribute;
        }
        return $this;
    }
    
    /**
     * Create activity log entry in database (if activity_logs table exists)
     */
    protected function createActivityLogEntry($activity, $data) {
        // Only create database entry if connection exists and table is available
        if (!isset($this->conn)) {
            return;
        }
        
        try {
            $logEntry = [
                'user_id' => $this->getCurrentUserId(),
                'activity' => $activity,
                'description' => $this->formatLogMessage($activity),
                'ip_address' => $this->getClientIpAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO activity_logs (user_id, activity, description, ip_address, user_agent, data, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('issssss', 
                    $logEntry['user_id'],
                    $logEntry['activity'],
                    $logEntry['description'],
                    $logEntry['ip_address'],
                    $logEntry['user_agent'],
                    $logEntry['data'],
                    $logEntry['created_at']
                );
                $stmt->execute();
            }
            
        } catch (Exception $e) {
            // Silently fail if activity_logs table doesn't exist
            // or if there's any other database error
        }
    }
    
    /**
     * Batch log multiple activities
     */
    public function batchLogActivities(array $activities) {
        foreach ($activities as $activity => $data) {
            if (is_numeric($activity)) {
                // If activity is in the data array
                $this->logActivity($data);
            } else {
                $this->logActivity($activity, $data);
            }
        }
    }
}