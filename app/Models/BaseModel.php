<?php
/**
 * Base Model - Common functionality for all models
 * Phase 4 Implementation
 */

require_once __DIR__ . '/../../core/Logger.php';

abstract class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    protected $timestamps = true;
    protected $logger;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
        
        if (empty($this->table)) {
            $this->table = $this->getTableName();
        }
    }
    
    /**
     * Get table name from class name
     */
    protected function getTableName() {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        $className = str_replace('Model', '', $className);
        
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
     * Find record by primary key
     */
    public function find($id) {
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
    }
    
    /**
     * Find multiple records
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $types = "";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
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
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
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
            $this->logAction("create", ['id' => $id, 'data' => $data]);
            return $id;
        }
        
        $this->logError("Failed to create record", $stmt->error);
        throw new Exception("Failed to create record: " . $stmt->error);
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "{$field} = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . 
               " WHERE {$this->primaryKey} = ?";
        
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
            $this->logAction("update", ['id' => $id, 'data' => $data]);
            return $stmt->affected_rows > 0;
        }
        
        $this->logError("Failed to update record", $stmt->error);
        throw new Exception("Failed to update record: " . $stmt->error);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
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
            $this->logAction("delete", ['id' => $id, 'record' => $record]);
            return $stmt->affected_rows > 0;
        }
        
        $this->logError("Failed to delete record", $stmt->error);
        throw new Exception("Failed to delete record: " . $stmt->error);
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $types = "";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
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
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []) {
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
    }
    
    /**
     * Check if record exists
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Filter data based on fillable/guarded fields
     */
    protected function filterFillable($data) {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }
        
        return $data;
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
     * Log database actions
     */
    protected function logAction($action, $data = []) {
        $this->logger->info("Model action: {$action}", array_merge($data, [
            'model' => get_class($this),
            'table' => $this->table
        ]));
    }
    
    /**
     * Log database errors
     */
    protected function logError($message, $error) {
        $this->logger->error($message, [
            'model' => get_class($this),
            'table' => $this->table,
            'database_error' => $error
        ]);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
}