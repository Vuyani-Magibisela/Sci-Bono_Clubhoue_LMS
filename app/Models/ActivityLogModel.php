<?php
/**
 * ActivityLogModel - Handles activity logging for audit trails
 * 
 * @package Models
 * @author Sci-Bono Clubhouse LMS
 * @version 1.0
 */

class ActivityLogModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Log an activity
     * 
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $status Status (success/failed)
     * @param array $additionalData Optional additional data
     * @return bool Success status
     */
    public function logActivity($userId, $action, $status = 'success', $additionalData = null) {
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $this->getClientIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Convert additional data to JSON if provided
        $additionalDataJson = null;
        if ($additionalData && is_array($additionalData)) {
            $additionalDataJson = json_encode($additionalData);
        }
        
        $sql = "INSERT INTO activity_log (user_id, action, status, ip_address, user_agent, timestamp, additional_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in logActivity: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("issssss", $userId, $action, $status, $ipAddress, $userAgent, $timestamp, $additionalDataJson);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to log activity: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Get recent activities
     * 
     * @param int $limit Number of activities to retrieve
     * @param int $userId Optional user ID to filter by
     * @return array Array of recent activities
     */
    public function getRecentActivities($limit = 50, $userId = null) {
        $whereClause = "";
        $params = [];
        $types = "";
        
        if ($userId !== null) {
            $whereClause = "WHERE al.user_id = ?";
            $params[] = $userId;
            $types = "i";
        }
        
        $sql = "SELECT 
                    al.id,
                    al.user_id,
                    u.username,
                    u.name,
                    u.surname,
                    al.action,
                    al.status,
                    al.ip_address,
                    al.timestamp,
                    al.additional_data,
                    CASE 
                        WHEN al.action = 'signin' AND al.status = 'success' THEN 'Signed In'
                        WHEN al.action = 'signout' AND al.status = 'success' THEN 'Signed Out'
                        WHEN al.action = 'signin_failed' THEN 'Sign In Failed'
                        WHEN al.action = 'password_attempt' THEN 'Password Attempt'
                        ELSE CONCAT(UPPER(SUBSTRING(al.action, 1, 1)), SUBSTRING(al.action, 2))
                    END as activity_description
                FROM activity_log al
                LEFT JOIN users u ON al.user_id = u.id
                {$whereClause}
                ORDER BY al.timestamp DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in getRecentActivities: " . $this->conn->error);
            return [];
        }
        
        $params[] = $limit;
        $types .= "i";
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            // Parse additional data if present
            if ($row['additional_data']) {
                $row['additional_data'] = json_decode($row['additional_data'], true);
            }
            $activities[] = $row;
        }
        
        $stmt->close();
        return $activities;
    }
    
    /**
     * Get activity statistics
     * 
     * @param string $timeframe Timeframe for stats (today, week, month)
     * @return array Activity statistics
     */
    public function getActivityStats($timeframe = 'today') {
        $dateCondition = "";
        switch ($timeframe) {
            case 'today':
                $dateCondition = "DATE(timestamp) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "timestamp >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateCondition = "timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $dateCondition = "DATE(timestamp) = CURDATE()";
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_activities,
                    COUNT(CASE WHEN action = 'signin' AND status = 'success' THEN 1 END) as successful_signins,
                    COUNT(CASE WHEN action = 'signout' AND status = 'success' THEN 1 END) as successful_signouts,
                    COUNT(CASE WHEN action = 'signin' AND status = 'failed' THEN 1 END) as failed_signins,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as total_failures,
                    COUNT(DISTINCT user_id) as unique_users
                FROM activity_log 
                WHERE {$dateCondition}";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Database error in getActivityStats: " . $this->conn->error);
            return [
                'total_activities' => 0,
                'successful_signins' => 0,
                'successful_signouts' => 0,
                'failed_signins' => 0,
                'total_failures' => 0,
                'unique_users' => 0
            ];
        }
        
        $stats = $result->fetch_assoc();
        return $stats ?: [
            'total_activities' => 0,
            'successful_signins' => 0,
            'successful_signouts' => 0,
            'failed_signins' => 0,
            'total_failures' => 0,
            'unique_users' => 0
        ];
    }
    
    /**
     * Get failed login attempts for a user
     * 
     * @param int $userId User ID
     * @param int $timeWindow Time window in minutes
     * @return int Number of failed attempts
     */
    public function getFailedLoginAttempts($userId, $timeWindow = 30) {
        $sql = "SELECT COUNT(*) as failed_attempts
                FROM activity_log 
                WHERE user_id = ? 
                AND action IN ('signin_failed', 'password_attempt') 
                AND status = 'failed'
                AND timestamp >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error in getFailedLoginAttempts: " . $this->conn->error);
            return 0;
        }
        
        $stmt->bind_param("ii", $userId, $timeWindow);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['failed_attempts'] ?? 0;
    }
    
    /**
     * Clean up old activity logs
     * 
     * @param int $daysOld Number of days old to keep
     * @return bool Success status
     */
    public function cleanupOldLogs($daysOld = 90) {
        $sql = "DELETE FROM activity_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Database error in cleanupOldLogs: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $daysOld);
        $success = $stmt->execute();
        
        if ($success) {
            $deletedRows = $stmt->affected_rows;
            error_log("Cleaned up {$deletedRows} old activity log records");
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log signin attempt
     * 
     * @param int $userId User ID
     * @param bool $success Whether signin was successful
     * @param string $reason Optional reason for failure
     * @return bool Success status
     */
    public function logSigninAttempt($userId, $success, $reason = '') {
        $action = $success ? 'signin' : 'signin_failed';
        $status = $success ? 'success' : 'failed';
        $additionalData = $reason ? ['reason' => $reason] : null;
        
        return $this->logActivity($userId, $action, $status, $additionalData);
    }
    
    /**
     * Log signout attempt
     * 
     * @param int $userId User ID
     * @param bool $success Whether signout was successful
     * @return bool Success status
     */
    public function logSignoutAttempt($userId, $success) {
        $action = 'signout';
        $status = $success ? 'success' : 'failed';
        
        return $this->logActivity($userId, $action, $status);
    }
    
    /**
     * Get activity summary for dashboard
     * 
     * @return array Activity summary
     */
    public function getActivitySummary() {
        $sql = "SELECT 
                    DATE(timestamp) as activity_date,
                    COUNT(CASE WHEN action = 'signin' AND status = 'success' THEN 1 END) as signins,
                    COUNT(CASE WHEN action = 'signout' AND status = 'success' THEN 1 END) as signouts,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failures,
                    COUNT(DISTINCT user_id) as unique_users
                FROM activity_log 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(timestamp)
                ORDER BY activity_date DESC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Database error in getActivitySummary: " . $this->conn->error);
            return [];
        }
        
        $summary = [];
        while ($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }
        
        return $summary;
    }
}