<?php
/**
 * Visitor Model
 * 
 * Handles all data operations for visitors and visits
 */
class VisitorModel {
    private $conn;
    
    /**
     * Constructor - initializes database connection
     * 
     * @param mysqli $conn The database connection object
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Register a new visitor
     * 
     * @param array $visitorData Visitor registration data
     * @return array Result with success status and message
     */
    public function registerVisitor($visitorData) {
        // Check if visitor already exists
        $stmt = $this->conn->prepare("SELECT id FROM visitors WHERE email = ?");
        $stmt->bind_param("s", $visitorData['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'A visitor with this email already exists'
            ];
        }
        
        // Insert new visitor
        $stmt = $this->conn->prepare("
            INSERT INTO visitors (
                name, 
                surname, 
                age, 
                grade_school, 
                student_number, 
                parent_name, 
                parent_surname, 
                email, 
                phone_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssissssss", 
            $visitorData['name'],
            $visitorData['surname'],
            $visitorData['age'],
            $visitorData['grade_school'],
            $visitorData['student_number'],
            $visitorData['parent_name'],
            $visitorData['parent_surname'],
            $visitorData['email'],
            $visitorData['phone']
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Visitor registered successfully',
                'visitor_id' => $this->conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to register visitor: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Check if a visitor exists
     * 
     * @param string $email The visitor's email
     * @return array|false Visitor data if found, false if not
     */
    public function getVisitorByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, name, surname, email FROM visitors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Sign in a visitor
     * 
     * @param int $visitorId The visitor's ID
     * @return array Result with success status and message
     */
    public function signInVisitor($visitorId) {
        // Check if visitor already has an active visit
        $stmt = $this->conn->prepare("SELECT id FROM visits WHERE visitor_id = ? AND sign_out_time IS NULL");
        $stmt->bind_param("i", $visitorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'You are already signed in. Please sign out first.'
            ];
        }
        
        // Create new visit record
        $stmt = $this->conn->prepare("INSERT INTO visits (visitor_id) VALUES (?)");
        $stmt->bind_param("i", $visitorId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Sign in successful',
                'visit_id' => $this->conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to sign in: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Sign out a visitor
     * 
     * @param int $visitorId The visitor's ID
     * @param string|null $comment Optional comment
     * @return array Result with success status and message
     */
    public function signOutVisitor($visitorId, $comment = null) {
        // Check if visitor has an active visit
        $stmt = $this->conn->prepare("SELECT id FROM visits WHERE visitor_id = ? AND sign_out_time IS NULL");
        $stmt->bind_param("i", $visitorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'No active visit found. Please sign in first.'
            ];
        }
        
        $visit = $result->fetch_assoc();
        $visitId = $visit['id'];
        
        // Update visit record with sign out time and comment
        $stmt = $this->conn->prepare("UPDATE visits SET sign_out_time = NOW(), comment = ? WHERE id = ?");
        $stmt->bind_param("si", $comment, $visitId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Sign out successful',
                'visit_id' => $visitId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to sign out: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Get visitors list with pagination, filtering, and search
     * 
     * @param int $page Page number
     * @param string $filter Filter option (all, active, completed)
     * @param string $search Search term
     * @param int $recordsPerPage Number of records per page
     * @return array Visitors data with pagination info
     */
    public function getVisitorsList($page = 1, $filter = 'all', $search = '', $recordsPerPage = 10) {
        // Calculate offset for pagination
        $offset = ($page - 1) * $recordsPerPage;
        
        // Build the base query
        $query = "
            SELECT v.id, v.name, v.surname, v.email, visit.sign_in_time, visit.sign_out_time
            FROM visitors v
            LEFT JOIN (
                SELECT visitor_id, sign_in_time, sign_out_time
                FROM visits
                ORDER BY sign_in_time DESC
            ) AS visit ON v.id = visit.visitor_id
            WHERE 1=1
        ";
        
        // Add filter conditions
        if ($filter === 'active') {
            $query .= " AND visit.sign_out_time IS NULL";
        } elseif ($filter === 'completed') {
            $query .= " AND visit.sign_out_time IS NOT NULL";
        }
        
        // Add search condition
        $searchParams = [];
        if (!empty($search)) {
            $search = "%$search%";
            $query .= " AND (v.name LIKE ? OR v.surname LIKE ? OR v.email LIKE ?)";
            $searchParams = [$search, $search, $search];
        }
        
        // Add group by to avoid duplicate rows
        $query .= " GROUP BY v.id";
        
        // Add sorting
        $query .= " ORDER BY visit.sign_in_time DESC";
        
        // Add pagination
        $query .= " LIMIT ? OFFSET ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters based on search and filter
        if (!empty($searchParams)) {
            $paramTypes = str_repeat('s', count($searchParams)) . 'ii';
            $bindParams = [...$searchParams, $recordsPerPage, $offset];
            $stmt->bind_param($paramTypes, ...$bindParams);
        } else {
            $stmt->bind_param("ii", $recordsPerPage, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $visitors = [];
        
        while ($row = $result->fetch_assoc()) {
            $visitors[] = $row;
        }
        
        // Get total count for pagination
        $totalRecords = $this->getTotalVisitorsCount($filter, $search);
        $totalPages = ceil($totalRecords / $recordsPerPage);
        
        return [
            'visitors' => $visitors,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
    }
    
    /**
     * Get total count of visitors for pagination
     * 
     * @param string $filter Filter option
     * @param string $search Search term
     * @return int Total number of records
     */
    private function getTotalVisitorsCount($filter = 'all', $search = '') {
        // Build count query
        $countQuery = "
            SELECT COUNT(DISTINCT v.id) as total
            FROM visitors v
            LEFT JOIN (
                SELECT visitor_id, sign_in_time, sign_out_time
                FROM visits
                ORDER BY sign_in_time DESC
            ) AS visit ON v.id = visit.visitor_id
            WHERE 1=1
        ";
        
        // Add filter conditions
        if ($filter === 'active') {
            $countQuery .= " AND visit.sign_out_time IS NULL";
        } elseif ($filter === 'completed') {
            $countQuery .= " AND visit.sign_out_time IS NOT NULL";
        }
        
        // Add search condition
        $searchParams = [];
        if (!empty($search)) {
            $search = "%$search%";
            $countQuery .= " AND (v.name LIKE ? OR v.surname LIKE ? OR v.email LIKE ?)";
            $searchParams = [$search, $search, $search];
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($countQuery);
        
        // Bind parameters if needed
        if (!empty($searchParams)) {
            $paramTypes = str_repeat('s', count($searchParams));
            $stmt->bind_param($paramTypes, ...$searchParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['total'];
    }
}