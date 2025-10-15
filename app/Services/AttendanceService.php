<?php
/**
 * Attendance Service - Attendance management business logic
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../Models/AttendanceModel.php';

class AttendanceService extends BaseService {
    private $attendanceModel;
    
    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->attendanceModel = new AttendanceModel($this->conn);
    }
    
    /**
     * Record user sign-in
     */
    public function signIn($userId, $options = []) {
        $this->logAction('signin_attempt', ['user_id' => $userId]);
        
        try {
            $this->validateRequired(['user_id' => $userId], ['user_id']);
            
            // Check if user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Check if user is already signed in today
            if ($this->isUserSignedInToday($userId)) {
                return [
                    'success' => false,
                    'message' => 'You are already signed in for today'
                ];
            }
            
            // Prepare attendance data (using actual database column names)
            $attendanceData = [
                'user_id' => $userId,
                'checked_in' => date('Y-m-d H:i:s'),
                'sign_in_status' => 'signedIn'
            ];

            // Create attendance record
            $attendanceId = $this->attendanceModel->create($attendanceData);
            
            if ($attendanceId) {
                $this->logAction('signin_success', [
                    'user_id' => $userId,
                    'attendance_id' => $attendanceId,
                    'signin_time' => $attendanceData['signin_time']
                ]);
                
                // Update user stats
                $this->updateUserAttendanceStats($userId);
                
                return [
                    'success' => true,
                    'attendance_id' => $attendanceId,
                    'signin_time' => $attendanceData['checked_in'],
                    'message' => 'Successfully signed in'
                ];
            }
            
            throw new Exception('Failed to create attendance record');
            
        } catch (Exception $e) {
            $this->handleError('Sign-in error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to sign in: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Record user sign-out
     */
    public function signOut($userId, $options = []) {
        $this->logAction('signout_attempt', ['user_id' => $userId]);
        
        try {
            $this->validateRequired(['user_id' => $userId], ['user_id']);
            
            // Find today's attendance record
            $attendance = $this->getTodayAttendance($userId);
            
            if (!$attendance) {
                return [
                    'success' => false,
                    'message' => 'No sign-in record found for today'
                ];
            }
            
            if ($attendance['signout_time']) {
                return [
                    'success' => false,
                    'message' => 'You are already signed out for today'
                ];
            }
            
            // Calculate duration
            $signinTime = new DateTime($attendance['signin_time']);
            $signoutTime = new DateTime();
            $duration = $signoutTime->diff($signinTime);
            $durationMinutes = ($duration->h * 60) + $duration->i;
            
            // Update attendance record (using actual database column names)
            $updated = $this->attendanceModel->update($attendance['id'], [
                'checked_out' => $signoutTime->format('Y-m-d H:i:s'),
                'sign_in_status' => 'signedOut'
            ]);
            
            if ($updated) {
                $this->logAction('signout_success', [
                    'user_id' => $userId,
                    'attendance_id' => $attendance['id'],
                    'signout_time' => $signoutTime->format('Y-m-d H:i:s'),
                    'duration_minutes' => $durationMinutes
                ]);
                
                // Update user stats
                $this->updateUserAttendanceStats($userId);
                
                return [
                    'success' => true,
                    'attendance_id' => $attendance['id'],
                    'signout_time' => $signoutTime->format('Y-m-d H:i:s'),
                    'duration_minutes' => $durationMinutes,
                    'duration_formatted' => $this->formatDuration($durationMinutes),
                    'message' => 'Successfully signed out'
                ];
            }
            
            throw new Exception('Failed to update attendance record');
            
        } catch (Exception $e) {
            $this->handleError('Sign-out error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to sign out: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get attendance records for user
     */
    public function getUserAttendance($userId, $options = []) {
        try {
            $startDate = $options['start_date'] ?? date('Y-m-01');
            $endDate = $options['end_date'] ?? date('Y-m-d');
            $limit = $options['limit'] ?? 100;

            $sql = "SELECT *, checked_in as signin_time, checked_out as signout_time, DATE(checked_in) as signin_date
                    FROM attendance
                    WHERE user_id = ? AND DATE(checked_in) BETWEEN ? AND ?
                    ORDER BY checked_in DESC
                    LIMIT ?";

            $result = $this->attendanceModel->query($sql, [$userId, $startDate, $endDate, $limit]);
            $records = $result->fetch_all(MYSQLI_ASSOC);
            
            // Calculate totals
            $stats = $this->calculateAttendanceStats($records);
            
            return [
                'success' => true,
                'records' => $records,
                'stats' => $stats,
                'count' => count($records)
            ];
            
        } catch (Exception $e) {
            $this->handleError('Get user attendance error: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve attendance records'
            ];
        }
    }
    
    /**
     * Get attendance statistics for period
     */
    public function getAttendanceStats($options = []) {
        try {
            $startDate = $options['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $options['end_date'] ?? date('Y-m-d'); // Today
            
            // Total attendance records
            $totalSql = "SELECT COUNT(*) as total FROM attendance WHERE DATE(checked_in) BETWEEN ? AND ?";
            $totalResult = $this->attendanceModel->query($totalSql, [$startDate, $endDate]);
            $total = $totalResult->fetch_assoc()['total'];

            // Unique users
            $uniqueUsersSql = "SELECT COUNT(DISTINCT user_id) as unique_users FROM attendance WHERE DATE(checked_in) BETWEEN ? AND ?";
            $uniqueUsersResult = $this->attendanceModel->query($uniqueUsersSql, [$startDate, $endDate]);
            $uniqueUsers = $uniqueUsersResult->fetch_assoc()['unique_users'];
            
            // Average daily attendance
            $daysDiff = (new DateTime($endDate))->diff(new DateTime($startDate))->days + 1;
            $avgDaily = $daysDiff > 0 ? round($total / $daysDiff, 2) : 0;
            
            // Current day stats
            $todayStats = $this->getTodayStats();
            
            // Weekly breakdown
            $weeklyStats = $this->getWeeklyStats($startDate, $endDate);
            
            return [
                'success' => true,
                'stats' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'days' => $daysDiff
                    ],
                    'totals' => [
                        'total_attendance' => $total,
                        'unique_users' => $uniqueUsers,
                        'average_daily' => $avgDaily
                    ],
                    'today' => $todayStats,
                    'weekly' => $weeklyStats
                ]
            ];
            
        } catch (Exception $e) {
            $this->handleError('Get attendance stats error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve attendance statistics'
            ];
        }
    }
    
    /**
     * Search attendance records
     */
    public function searchAttendance($query, $options = []) {
        try {
            $searchTerm = $this->sanitize($query);
            $limit = $options['limit'] ?? 50;
            
            $sql = "SELECT a.*, u.name, u.surname, u.username, u.user_type,
                           a.checked_in as signin_time,
                           a.checked_out as signout_time,
                           DATE(a.checked_in) as signin_date
                    FROM attendance a
                    JOIN users u ON a.user_id = u.id
                    WHERE (u.name LIKE ? OR u.surname LIKE ? OR u.username LIKE ?)
                    AND DATE(a.checked_in) BETWEEN ? AND ?
                    ORDER BY a.checked_in DESC
                    LIMIT ?";
            
            $searchPattern = "%{$searchTerm}%";
            $startDate = $options['start_date'] ?? date('Y-m-01');
            $endDate = $options['end_date'] ?? date('Y-m-d');
            
            $result = $this->attendanceModel->query($sql, [
                $searchPattern,
                $searchPattern, 
                $searchPattern,
                $startDate,
                $endDate,
                $limit
            ]);
            
            $records = $result->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'records' => $records,
                'count' => count($records),
                'query' => $searchTerm
            ];
            
        } catch (Exception $e) {
            $this->handleError('Search attendance error: ' . $e->getMessage(), [
                'query' => $query
            ]);
            
            return [
                'success' => false,
                'message' => 'Search failed'
            ];
        }
    }
    
    /**
     * Get current attendance status for all users
     */
    public function getCurrentAttendance() {
        try {
            $today = date('Y-m-d');

            $sql = "SELECT a.*, u.name, u.surname, u.username, u.user_type, u.id as user_id,
                           a.checked_in as signin_time,
                           a.checked_out as signout_time,
                           DATE(a.checked_in) as signin_date
                    FROM attendance a
                    JOIN users u ON a.user_id = u.id
                    WHERE DATE(a.checked_in) = ?
                    ORDER BY
                        CASE WHEN a.checked_out IS NULL THEN 0 ELSE 1 END,
                        a.checked_in DESC";

            $result = $this->attendanceModel->query($sql, [$today]);
            $records = $result->fetch_all(MYSQLI_ASSOC);
            
            // Separate signed in and signed out
            $signedIn = [];
            $signedOut = [];
            
            foreach ($records as $record) {
                if ($record['signout_time']) {
                    $signedOut[] = $record;
                } else {
                    $signedIn[] = $record;
                }
            }
            
            return [
                'success' => true,
                'date' => $today,
                'signed_in' => $signedIn,
                'signed_out' => $signedOut,
                'counts' => [
                    'signed_in' => count($signedIn),
                    'signed_out' => count($signedOut),
                    'total' => count($records)
                ]
            ];
            
        } catch (Exception $e) {
            $this->handleError('Get current attendance error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve current attendance'
            ];
        }
    }
    
    // Private helper methods
    
    private function getUserById($userId) {
        try {
            $sql = "SELECT * FROM users WHERE id = ?";
            $result = $this->attendanceModel->query($sql, [$userId]);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logger->error('Get user by ID error', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    private function isUserSignedInToday($userId) {
        $today = date('Y-m-d');
        $sql = "SELECT id FROM attendance WHERE user_id = ? AND DATE(checked_in) = ? AND sign_in_status = 'signedIn'";
        $result = $this->attendanceModel->query($sql, [$userId, $today]);
        return $result && $result->num_rows > 0;
    }

    private function getTodayAttendance($userId) {
        $today = date('Y-m-d');
        $sql = "SELECT *, checked_in as signin_time, checked_out as signout_time
                FROM attendance
                WHERE user_id = ? AND DATE(checked_in) = ?
                ORDER BY checked_in DESC LIMIT 1";
        $result = $this->attendanceModel->query($sql, [$userId, $today]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }
    
    private function calculateAttendanceStats($records) {
        $totalDuration = 0;
        $completedSessions = 0;
        $incompleteSessions = 0;
        
        foreach ($records as $record) {
            if ($record['signout_time']) {
                $completedSessions++;
                $totalDuration += $record['duration_minutes'] ?? 0;
            } else {
                $incompleteSessions++;
            }
        }
        
        $avgDuration = $completedSessions > 0 ? round($totalDuration / $completedSessions) : 0;
        
        return [
            'total_sessions' => count($records),
            'completed_sessions' => $completedSessions,
            'incomplete_sessions' => $incompleteSessions,
            'total_duration_minutes' => $totalDuration,
            'average_duration_minutes' => $avgDuration,
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            'average_duration_formatted' => $this->formatDuration($avgDuration)
        ];
    }
    
    private function getTodayStats() {
        $today = date('Y-m-d');
        
        try {
            // Total sign-ins today
            $totalSql = "SELECT COUNT(*) as total FROM attendance WHERE DATE(checked_in) = ?";
            $totalResult = $this->attendanceModel->query($totalSql, [$today]);
            $total = $totalResult->fetch_assoc()['total'];

            // Currently signed in
            $signedInSql = "SELECT COUNT(*) as signed_in FROM attendance WHERE DATE(checked_in) = ? AND checked_out IS NULL";
            $signedInResult = $this->attendanceModel->query($signedInSql, [$today]);
            $signedIn = $signedInResult->fetch_assoc()['signed_in'];
            
            // Signed out
            $signedOut = $total - $signedIn;
            
            return [
                'total' => $total,
                'signed_in' => $signedIn,
                'signed_out' => $signedOut
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Get today stats error', ['error' => $e->getMessage()]);
            return ['total' => 0, 'signed_in' => 0, 'signed_out' => 0];
        }
    }
    
    private function getWeeklyStats($startDate, $endDate) {
        try {
            $sql = "SELECT DAYNAME(checked_in) as day_name, DATE(checked_in) as date, COUNT(*) as count
                    FROM attendance
                    WHERE DATE(checked_in) BETWEEN ? AND ?
                    GROUP BY DATE(checked_in)
                    ORDER BY checked_in";

            $result = $this->attendanceModel->query($sql, [$startDate, $endDate]);
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            $this->logger->error('Get weekly stats error', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    private function updateUserAttendanceStats($userId) {
        try {
            // Calculate user's total attendance stats
            $statsSql = "SELECT
                            COUNT(*) as total_sessions,
                            SUM(CASE WHEN checked_out IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions,
                            MAX(DATE(checked_in)) as last_attendance
                         FROM attendance
                         WHERE user_id = ?";

            $result = $this->attendanceModel->query($statsSql, [$userId]);
            $stats = $result->fetch_assoc();
            
            // Update user table with attendance stats (if columns exist)
            $updateSql = "UPDATE users SET 
                         last_attendance = ?,
                         updated_at = NOW()
                         WHERE id = ?";
            
            $this->attendanceModel->query($updateSql, [
                $stats['last_attendance'],
                $userId
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Update user attendance stats error', ['error' => $e->getMessage()]);
        }
    }
    
    private function formatDuration($minutes) {
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        
        return $hours . 'h ' . $remainingMinutes . 'm';
    }
}