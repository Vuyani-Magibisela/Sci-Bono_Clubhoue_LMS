<?php
/**
 * Visitor Service - Business logic for visitor management and tracking
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseService.php';

class VisitorService extends BaseService {

    public function __construct($conn = null) {
        parent::__construct($conn);
    }

    /**
     * Register a new visitor
     *
     * @param array $data Visitor registration data
     * @return int|bool Visitor ID on success, false on failure
     */
    public function registerVisitor($data) {
        try {
            $this->logAction('register_visitor_attempt', ['name' => $data['name'] ?? 'unknown']);

            // Validate required fields
            $required = ['name', 'surname', 'email', 'phone', 'purpose'];
            $this->validateRequired($data, $required);

            // Validate data
            $this->validateVisitorData($data);

            // Sanitize data
            $name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
            $surname = htmlspecialchars(trim($data['surname']), ENT_QUOTES, 'UTF-8');
            $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
            $phone = preg_replace('/[^0-9+]/', '', $data['phone']);
            $purpose = htmlspecialchars(trim($data['purpose']), ENT_QUOTES, 'UTF-8');
            $company = isset($data['company']) ? htmlspecialchars(trim($data['company']), ENT_QUOTES, 'UTF-8') : '';
            $idNumber = isset($data['id_number']) ? htmlspecialchars(trim($data['id_number']), ENT_QUOTES, 'UTF-8') : '';

            // Check for duplicate recent visit (same email within last hour)
            if ($this->hasRecentVisit($email)) {
                throw new Exception("A visitor with this email has already signed in within the last hour");
            }

            // Insert visitor
            $sql = "INSERT INTO visitors (name, surname, email, phone, purpose, company, id_number, visit_date, check_in_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssssss", $name, $surname, $email, $phone, $purpose, $company, $idNumber);
            $result = $stmt->execute();

            if ($result) {
                $visitorId = $stmt->insert_id;

                $this->logAction('visitor_registered_success', [
                    'visitor_id' => $visitorId,
                    'name' => $name . ' ' . $surname,
                    'email' => $email
                ]);

                return $visitorId;
            }

            throw new Exception("Failed to register visitor");

        } catch (Exception $e) {
            $this->handleError("Visitor registration failed: " . $e->getMessage(), ['data' => $data]);
        }
    }

    /**
     * Check in a visitor (for returning visitors)
     *
     * @param int $visitorId Visitor ID
     * @return bool Success status
     */
    public function checkInVisitor($visitorId) {
        try {
            $this->logAction('check_in_visitor', ['visitor_id' => $visitorId]);

            // Check if visitor exists
            $visitor = $this->getVisitorById($visitorId);
            if (!$visitor) {
                throw new Exception("Visitor not found");
            }

            // Check if already checked in today
            $sql = "SELECT id FROM visitor_logs
                    WHERE visitor_id = ? AND visit_date = CURDATE() AND check_out_time IS NULL";

            $checkStmt = $this->conn->prepare($sql);
            $checkStmt->bind_param("i", $visitorId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                throw new Exception("Visitor is already checked in today");
            }

            // Create log entry
            $insertSql = "INSERT INTO visitor_logs (visitor_id, visit_date, check_in_time)
                          VALUES (?, CURDATE(), NOW())";

            $insertStmt = $this->conn->prepare($insertSql);
            $insertStmt->bind_param("i", $visitorId);
            $result = $insertStmt->execute();

            if ($result) {
                $this->logAction('visitor_checked_in', ['visitor_id' => $visitorId]);
                return true;
            }

            throw new Exception("Failed to check in visitor");

        } catch (Exception $e) {
            $this->handleError("Check-in failed: " . $e->getMessage(), ['visitor_id' => $visitorId]);
        }
    }

    /**
     * Check out a visitor
     *
     * @param int $visitorId Visitor ID
     * @return bool Success status
     */
    public function checkOutVisitor($visitorId) {
        try {
            $this->logAction('check_out_visitor', ['visitor_id' => $visitorId]);

            // Find active check-in for today
            $sql = "UPDATE visitor_logs
                    SET check_out_time = NOW()
                    WHERE visitor_id = ? AND visit_date = CURDATE() AND check_out_time IS NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $visitorId);
            $result = $stmt->execute();

            if ($result && $stmt->affected_rows > 0) {
                $this->logAction('visitor_checked_out', ['visitor_id' => $visitorId]);
                return true;
            }

            throw new Exception("No active check-in found for this visitor today");

        } catch (Exception $e) {
            $this->handleError("Check-out failed: " . $e->getMessage(), ['visitor_id' => $visitorId]);
        }
    }

    /**
     * Get visitor by ID
     *
     * @param int $visitorId Visitor ID
     * @return array|null Visitor data or null if not found
     */
    public function getVisitorById($visitorId) {
        try {
            $sql = "SELECT * FROM visitors WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $visitorId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error("Failed to get visitor", ['visitor_id' => $visitorId]);
            return null;
        }
    }

    /**
     * Get all visitors with optional filters
     *
     * @param array $filters Optional filters (date_from, date_to, purpose, search)
     * @return array List of visitors
     */
    public function getAllVisitors($filters = []) {
        try {
            $sql = "SELECT * FROM visitors WHERE 1=1";
            $params = [];
            $types = "";

            // Apply filters
            if (!empty($filters['date_from'])) {
                $sql .= " AND visit_date >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND visit_date <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }

            if (!empty($filters['purpose'])) {
                $sql .= " AND purpose = ?";
                $params[] = $filters['purpose'];
                $types .= "s";
            }

            if (!empty($filters['search'])) {
                $searchPattern = '%' . $filters['search'] . '%';
                $sql .= " AND (name LIKE ? OR surname LIKE ? OR email LIKE ? OR company LIKE ?)";
                $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
                $types .= "ssss";
            }

            $sql .= " ORDER BY visit_date DESC, check_in_time DESC";

            if (!empty($params)) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
            }

            $visitors = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $visitors[] = $row;
                }
            }

            return $visitors;

        } catch (Exception $e) {
            $this->logger->error("Failed to get visitors", ['filters' => $filters]);
            return [];
        }
    }

    /**
     * Search visitors by query
     *
     * @param string $query Search query
     * @return array Matching visitors
     */
    public function searchVisitors($query) {
        try {
            $searchPattern = '%' . $query . '%';

            $sql = "SELECT * FROM visitors
                    WHERE name LIKE ?
                       OR surname LIKE ?
                       OR email LIKE ?
                       OR company LIKE ?
                       OR phone LIKE ?
                    ORDER BY visit_date DESC
                    LIMIT 50";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();

            $visitors = [];
            while ($row = $result->fetch_assoc()) {
                $visitors[] = $row;
            }

            return $visitors;

        } catch (Exception $e) {
            $this->logger->error("Visitor search failed", ['query' => $query]);
            return [];
        }
    }

    /**
     * Get visitor statistics
     *
     * @param array $filters Optional filters (date_from, date_to)
     * @return array Statistics data
     */
    public function getVisitorStatistics($filters = []) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($filters['date_from'])) {
                $whereClause .= " AND visit_date >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }

            if (!empty($filters['date_to'])) {
                $whereClause .= " AND visit_date <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }

            $sql = "SELECT
                        COUNT(*) as total_visitors,
                        COUNT(DISTINCT email) as unique_visitors,
                        COUNT(DISTINCT DATE(visit_date)) as days_with_visitors,
                        COUNT(DISTINCT company) as unique_companies
                    FROM visitors
                    {$whereClause}";

            if (!empty($params)) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
            }

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            return [
                'total_visitors' => 0,
                'unique_visitors' => 0,
                'days_with_visitors' => 0,
                'unique_companies' => 0
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get visitor statistics", ['filters' => $filters]);
            return [
                'total_visitors' => 0,
                'unique_visitors' => 0,
                'days_with_visitors' => 0,
                'unique_companies' => 0
            ];
        }
    }

    /**
     * Get visitors by purpose
     *
     * @return array Purposes with visitor counts
     */
    public function getVisitorsByPurpose() {
        try {
            $sql = "SELECT
                        purpose,
                        COUNT(*) as visitor_count,
                        MAX(visit_date) as last_visit
                    FROM visitors
                    GROUP BY purpose
                    ORDER BY visitor_count DESC";

            $result = $this->conn->query($sql);

            $purposes = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $purposes[] = $row;
                }
            }

            return $purposes;

        } catch (Exception $e) {
            $this->logger->error("Failed to get visitors by purpose");
            return [];
        }
    }

    /**
     * Get today's visitors
     *
     * @return array List of today's visitors
     */
    public function getTodaysVisitors() {
        try {
            $sql = "SELECT * FROM visitors
                    WHERE visit_date = CURDATE()
                    ORDER BY check_in_time DESC";

            $result = $this->conn->query($sql);

            $visitors = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $visitors[] = $row;
                }
            }

            return $visitors;

        } catch (Exception $e) {
            $this->logger->error("Failed to get today's visitors");
            return [];
        }
    }

    /**
     * Validate visitor data
     *
     * @param array $data Visitor data
     * @throws Exception If validation fails
     */
    private function validateVisitorData($data) {
        // Name validation
        if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
            throw new Exception("Name must be between 2 and 50 characters");
        }

        if (strlen($data['surname']) < 2 || strlen($data['surname']) > 50) {
            throw new Exception("Surname must be between 2 and 50 characters");
        }

        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        // Phone validation
        $cleanPhone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
            throw new Exception("Phone number must be between 10 and 15 digits");
        }

        // Purpose validation
        if (strlen($data['purpose']) < 3 || strlen($data['purpose']) > 200) {
            throw new Exception("Purpose must be between 3 and 200 characters");
        }

        return true;
    }

    /**
     * Check if visitor has recent visit (within last hour)
     *
     * @param string $email Visitor email
     * @return bool True if has recent visit
     */
    private function hasRecentVisit($email) {
        try {
            $sql = "SELECT id FROM visitors
                    WHERE email = ? AND check_in_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->num_rows > 0;

        } catch (Exception $e) {
            $this->logger->error("Failed to check recent visit", ['email' => $email]);
            return false;
        }
    }

    /**
     * Update visitor information
     *
     * @param int $visitorId Visitor ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateVisitor($visitorId, $data) {
        try {
            $this->logAction('update_visitor', ['visitor_id' => $visitorId]);

            // Check if visitor exists
            $visitor = $this->getVisitorById($visitorId);
            if (!$visitor) {
                throw new Exception("Visitor not found");
            }

            $updates = [];
            $params = [];
            $types = "";

            // Build update query
            if (isset($data['name'])) {
                $updates[] = "name = ?";
                $params[] = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (isset($data['surname'])) {
                $updates[] = "surname = ?";
                $params[] = htmlspecialchars(trim($data['surname']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (isset($data['email'])) {
                $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email address");
                }
                $updates[] = "email = ?";
                $params[] = $email;
                $types .= "s";
            }

            if (isset($data['phone'])) {
                $updates[] = "phone = ?";
                $params[] = preg_replace('/[^0-9+]/', '', $data['phone']);
                $types .= "s";
            }

            if (isset($data['company'])) {
                $updates[] = "company = ?";
                $params[] = htmlspecialchars(trim($data['company']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (empty($updates)) {
                return true; // Nothing to update
            }

            $sql = "UPDATE visitors SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $visitorId;
            $types .= "i";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $result = $stmt->execute();

            if ($result) {
                $this->logAction('visitor_updated_success', ['visitor_id' => $visitorId]);
                return true;
            }

            throw new Exception("Failed to update visitor");

        } catch (Exception $e) {
            $this->handleError("Visitor update failed: " . $e->getMessage(), ['visitor_id' => $visitorId]);
        }
    }

    /**
     * Delete visitor record
     *
     * @param int $visitorId Visitor ID
     * @return bool Success status
     */
    public function deleteVisitor($visitorId) {
        try {
            $this->logAction('delete_visitor', ['visitor_id' => $visitorId]);

            // Check if visitor exists
            $visitor = $this->getVisitorById($visitorId);
            if (!$visitor) {
                throw new Exception("Visitor not found");
            }

            // Delete visitor logs first (if table exists)
            $deleteLogsSql = "DELETE FROM visitor_logs WHERE visitor_id = ?";
            $deleteLogsStmt = $this->conn->prepare($deleteLogsSql);
            $deleteLogsStmt->bind_param("i", $visitorId);
            $deleteLogsStmt->execute();

            // Delete visitor
            $sql = "DELETE FROM visitors WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $visitorId);
            $result = $stmt->execute();

            if ($result) {
                $this->logAction('visitor_deleted_success', ['visitor_id' => $visitorId]);
                return true;
            }

            throw new Exception("Failed to delete visitor");

        } catch (Exception $e) {
            $this->handleError("Visitor deletion failed: " . $e->getMessage(), ['visitor_id' => $visitorId]);
        }
    }
}
