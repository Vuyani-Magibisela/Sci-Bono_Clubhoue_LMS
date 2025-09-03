<?php
/**
 * User Repository - Specialized data access for users
 * Phase 4 Implementation
 */

require_once __DIR__ . '/BaseRepository.php';

class UserRepository extends BaseRepository {
    protected $table = 'users';
    
    /**
     * Find user by email or username
     */
    public function findByIdentifier($identifier) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE email = ? OR username = ? LIMIT 1";
            $result = $this->query($sql, [$identifier, $identifier]);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logError("Find by identifier error", $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find users by role/user_type
     */
    public function findByRole($role) {
        return $this->findAll(['user_type' => $role], 'name, surname');
    }
    
    /**
     * Find active users
     */
    public function findActive($orderBy = 'name, surname') {
        return $this->findAll(['status' => 'active'], $orderBy);
    }
    
    /**
     * Search users by name, surname, email, or username
     */
    public function search($query, $limit = 50) {
        try {
            $searchTerm = '%' . $query . '%';
            $sql = "SELECT id, username, email, name, surname, user_type, status, created_at, last_login 
                    FROM {$this->table} 
                    WHERE (name LIKE ? OR surname LIKE ? OR email LIKE ? OR username LIKE ?)
                    AND status = 'active'
                    ORDER BY name, surname 
                    LIMIT ?";
            
            $result = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
            
            if ($result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->logError("User search error", $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user statistics by role
     */
    public function getUserStats() {
        try {
            $sql = "SELECT 
                        user_type,
                        COUNT(*) as count,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                        SUM(CASE WHEN last_login IS NOT NULL THEN 1 ELSE 0 END) as has_logged_in,
                        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_last_30_days
                    FROM {$this->table} 
                    GROUP BY user_type 
                    ORDER BY user_type";
            
            $result = $this->query($sql);
            
            if ($result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->logError("Get user stats error", $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recently registered users
     */
    public function getRecentUsers($days = 7, $limit = 10) {
        try {
            $sql = "SELECT id, username, email, name, surname, user_type, status, created_at 
                    FROM {$this->table} 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $result = $this->query($sql, [$days, $limit]);
            
            if ($result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->logError("Get recent users error", $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get users with recent login activity
     */
    public function getActiveUsers($days = 30, $limit = 50) {
        try {
            $sql = "SELECT id, username, email, name, surname, user_type, last_login
                    FROM {$this->table} 
                    WHERE last_login >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND status = 'active'
                    ORDER BY last_login DESC 
                    LIMIT ?";
            
            $result = $this->query($sql, [$days, $limit]);
            
            if ($result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->logError("Get active users error", $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get users who haven't logged in recently
     */
    public function getInactiveUsers($days = 90) {
        try {
            $sql = "SELECT id, username, email, name, surname, user_type, created_at, last_login
                    FROM {$this->table} 
                    WHERE (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL ? DAY))
                    AND status = 'active'
                    AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY COALESCE(last_login, created_at) ASC";
            
            $result = $this->query($sql, [$days]);
            
            if ($result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
            
        } catch (Exception $e) {
            $this->logError("Get inactive users error", $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update user's last login time
     */
    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user's session token
     */
    public function updateSessionToken($userId, $token) {
        return $this->update($userId, [
            'session_token' => $token
        ]);
    }
    
    /**
     * Clear user's session token
     */
    public function clearSessionToken($userId) {
        return $this->update($userId, [
            'session_token' => null
        ]);
    }
    
    /**
     * Check if email is already taken
     */
    public function emailExists($email, $excludeUserId = null) {
        $conditions = ['email' => $email];
        
        if ($excludeUserId) {
            // For updates - check if email exists for other users
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ? AND id != ?";
            $result = $this->query($sql, [$email, $excludeUserId]);
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Check if username is already taken
     */
    public function usernameExists($username, $excludeUserId = null) {
        $conditions = ['username' => $username];
        
        if ($excludeUserId) {
            // For updates - check if username exists for other users
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ? AND id != ?";
            $result = $this->query($sql, [$username, $excludeUserId]);
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Get user's profile with additional computed fields
     */
    public function getProfile($userId) {
        try {
            $sql = "SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id) as total_attendance,
                        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND signin_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as attendance_last_30_days,
                        CASE 
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Very Active'
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Active'
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Moderate'
                            WHEN u.last_login IS NOT NULL THEN 'Inactive'
                            ELSE 'Never Logged In'
                        END as activity_status
                    FROM {$this->table} u
                    WHERE u.id = ?";
            
            $result = $this->query($sql, [$userId]);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logError("Get profile error", $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get users for admin listing with statistics
     */
    public function getAdminUserList($page = 1, $perPage = 25, $filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['role']) && $filters['role'] !== 'all') {
                $conditions[] = "u.user_type = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $conditions[] = "u.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $conditions[] = "(u.name LIKE ? OR u.surname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} u {$whereClause}";
            $countResult = $this->query($countSql, $params);
            $total = $countResult->fetch_assoc()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id) as total_attendance,
                        (SELECT MAX(signin_date) FROM attendance WHERE user_id = u.id) as last_attendance,
                        CASE 
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'online'
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'recent'
                            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'moderate'
                            ELSE 'inactive'
                        END as activity_level
                    FROM {$this->table} u 
                    {$whereClause}
                    ORDER BY u.created_at DESC
                    LIMIT {$offset}, {$perPage}";
            
            $result = $this->query($sql, $params);
            $items = $result->fetch_all(MYSQLI_ASSOC);
            
            return [
                'items' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_more' => ($page * $perPage) < $total
                ]
            ];
            
        } catch (Exception $e) {
            $this->logError("Get admin user list error", $e->getMessage());
            return [
                'items' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false
                ]
            ];
        }
    }
    
    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus($userIds, $status) {
        try {
            if (empty($userIds) || !in_array($status, ['active', 'inactive', 'suspended'])) {
                return false;
            }
            
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
            
            $params = array_merge([$status], $userIds);
            $result = $this->query($sql, $params);
            
            $this->logAction("bulk_status_update", [
                'user_ids' => $userIds,
                'status' => $status,
                'count' => count($userIds)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError("Bulk update status error", $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users by IDs (useful for bulk operations)
     */
    public function findByIds(array $ids) {
        if (empty($ids)) {
            return [];
        }
        
        return $this->findWhereIn('id', $ids, 'name, surname');
    }
    
    /**
     * Delete inactive users (soft delete by setting status)
     */
    public function deactivateInactiveUsers($days = 180) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = 'inactive', updated_at = NOW()
                    WHERE status = 'active' 
                    AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL ? DAY))
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $result = $this->query($sql, [$days, $days]);
            
            $this->logAction("deactivate_inactive_users", [
                'days_threshold' => $days,
                'affected_rows' => $this->conn->affected_rows
            ]);
            
            return $this->conn->affected_rows;
            
        } catch (Exception $e) {
            $this->logError("Deactivate inactive users error", $e->getMessage());
            return false;
        }
    }
}