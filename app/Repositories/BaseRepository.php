<?php
/**
 * Base Repository - Abstract implementation of repository pattern
 * Phase 4 Implementation
 */

require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../../core/Logger.php';

abstract class BaseRepository implements RepositoryInterface {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $model;
    protected $logger;
    
    public function __construct($conn, $model = null) {
        $this->conn = $conn;
        $this->model = $model;
        $this->logger = new Logger();
        
        if (empty($this->table)) {
            $this->table = $this->getTableName();
        }
    }
    
    /**
     * Find entity by primary key
     */
    public function find($id) {
        try {
            if ($this->model) {
                return $this->model->find($id);
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->logError("Database error in find()", $this->conn->error);
                return null;
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logError("Repository find() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Find all entities matching conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        try {
            if ($this->model) {
                return $this->model->findAll($conditions, $orderBy, $limit);
            }
            
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            $types = "";
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    if (strpos($field, ' ') !== false) {
                        // Handle operators like 'field >', 'field LIKE', etc.
                        $whereClause[] = $field . " ?";
                    } else {
                        $whereClause[] = "{$field} = ?";
                    }
                    $params[] = $value;
                    $types .= $this->getParamType($value);
                }
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit}";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Repository findAll() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Find first entity matching conditions
     */
    public function findFirst($conditions = [], $orderBy = null) {
        $results = $this->findAll($conditions, $orderBy, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Create new entity
     */
    public function create(array $data) {
        try {
            if ($this->model) {
                return $this->model->create($data);
            }
            
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO {$this->table} (";
            $sql .= implode(', ', $fields);
            $sql .= ") VALUES (";
            $sql .= implode(', ', $placeholders);
            $sql .= ")";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->logError("Database error in create()", $this->conn->error);
                throw new Exception("Failed to prepare create statement");
            }
            
            $types = "";
            $values = [];
            foreach ($data as $value) {
                $types .= $this->getParamType($value);
                $values[] = $value;
            }
            
            $stmt->bind_param($types, ...$values);
            $success = $stmt->execute();
            
            if ($success) {
                $id = $this->conn->insert_id;
                $this->logAction("create", ['id' => $id, 'table' => $this->table]);
                return $id;
            }
            
            $this->logError("Failed to create record", $stmt->error);
            throw new Exception("Failed to create record: " . $stmt->error);
            
        } catch (Exception $e) {
            $this->logError("Repository create() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update entity by ID
     */
    public function update($id, array $data) {
        try {
            if ($this->model) {
                return $this->model->update($id, $data);
            }
            
            $fields = array_keys($data);
            $setClause = array_map(function($field) {
                return "{$field} = ?";
            }, $fields);
            
            $sql = "UPDATE {$this->table} SET ";
            $sql .= implode(', ', $setClause);
            $sql .= " WHERE {$this->primaryKey} = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->logError("Database error in update()", $this->conn->error);
                throw new Exception("Failed to prepare update statement");
            }
            
            $types = "";
            $values = [];
            foreach ($data as $value) {
                $types .= $this->getParamType($value);
                $values[] = $value;
            }
            $types .= "i";
            $values[] = $id;
            
            $stmt->bind_param($types, ...$values);
            $success = $stmt->execute();
            
            if ($success) {
                $this->logAction("update", ['id' => $id, 'table' => $this->table]);
                return $stmt->affected_rows > 0;
            }
            
            $this->logError("Failed to update record", $stmt->error);
            throw new Exception("Failed to update record: " . $stmt->error);
            
        } catch (Exception $e) {
            $this->logError("Repository update() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete entity by ID
     */
    public function delete($id) {
        try {
            if ($this->model) {
                return $this->model->delete($id);
            }
            
            // Check if record exists
            $record = $this->find($id);
            if (!$record) {
                return false;
            }
            
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->logError("Database error in delete()", $this->conn->error);
                throw new Exception("Failed to prepare delete statement");
            }
            
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            
            if ($success) {
                $this->logAction("delete", ['id' => $id, 'table' => $this->table, 'record' => $record]);
                return $stmt->affected_rows > 0;
            }
            
            $this->logError("Failed to delete record", $stmt->error);
            throw new Exception("Failed to delete record: " . $stmt->error);
            
        } catch (Exception $e) {
            $this->logError("Repository delete() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if entity exists
     */
    public function exists($conditions) {
        if ($this->model) {
            return $this->model->exists($conditions);
        }
        
        return $this->count($conditions) > 0;
    }
    
    /**
     * Count entities matching conditions
     */
    public function count($conditions = []) {
        try {
            if ($this->model) {
                return $this->model->count($conditions);
            }
            
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $params = [];
            $types = "";
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    if (strpos($field, ' ') !== false) {
                        $whereClause[] = $field . " ?";
                    } else {
                        $whereClause[] = "{$field} = ?";
                    }
                    $params[] = $value;
                    $types .= $this->getParamType($value);
                }
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return (int) $row['count'];
            
        } catch (Exception $e) {
            $this->logError("Repository count() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []) {
        try {
            if ($this->model) {
                return $this->model->query($sql, $params);
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $this->logError("Database error in query()", $this->conn->error);
                throw new Exception("Failed to prepare query");
            }
            
            if (!empty($params)) {
                $types = "";
                foreach ($params as $param) {
                    $types .= $this->getParamType($param);
                }
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            return $stmt->get_result();
            
        } catch (Exception $e) {
            $this->logError("Repository query() error", $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        if ($this->model) {
            return $this->model->beginTransaction();
        }
        
        return $this->conn->begin_transaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        if ($this->model) {
            return $this->model->commit();
        }
        
        return $this->conn->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        if ($this->model) {
            return $this->model->rollback();
        }
        
        return $this->conn->rollback();
    }
    
    /**
     * Find entities with pagination
     */
    public function paginate($conditions = [], $page = 1, $perPage = 25, $orderBy = null) {
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $total = $this->count($conditions);
        
        // Build query for paginated results
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $types = "";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                if (strpos($field, ' ') !== false) {
                    $whereClause[] = $field . " ?";
                } else {
                    $whereClause[] = "{$field} = ?";
                }
                $params[] = $value;
                $types .= $this->getParamType($value);
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$offset}, {$perPage}";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
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
    }
    
    /**
     * Find entities where field is in array of values
     */
    public function findWhereIn($field, array $values, $orderBy = null, $limit = null) {
        if (empty($values)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE {$field} IN ({$placeholders})";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->query($sql, $values)->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get table name from class name
     */
    protected function getTableName() {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        $className = str_replace('Repository', '', $className);
        
        // Convert CamelCase to snake_case and make plural
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        // Simple pluralization
        if (substr($table, -1) === 'y') {
            $table = substr($table, 0, -1) . 'ies';
        } elseif (!in_array(substr($table, -1), ['s', 'x', 'z'])) {
            $table .= 's';
        }
        
        return $table;
    }
    
    /**
     * Get parameter type for binding
     */
    protected function getParamType($value) {
        if (is_int($value)) {
            return 'i';
        } elseif (is_float($value)) {
            return 'd';
        } elseif (is_string($value)) {
            return 's';
        } else {
            return 'b'; // blob
        }
    }
    
    /**
     * Log repository actions
     */
    protected function logAction($action, $data = []) {
        $this->logger->info("Repository action: {$action}", array_merge($data, [
            'repository' => get_class($this),
            'table' => $this->table
        ]));
    }
    
    /**
     * Log repository errors
     */
    protected function logError($message, $error) {
        $this->logger->error($message, [
            'repository' => get_class($this),
            'table' => $this->table,
            'database_error' => $error
        ]);
    }
}