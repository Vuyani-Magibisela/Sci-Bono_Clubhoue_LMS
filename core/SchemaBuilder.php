<?php
/**
 * Schema Builder for creating database structures
 * Phase 5 Implementation
 */

class SchemaBuilder {
    private $conn;
    private $table;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    private $engine = 'InnoDB';
    private $charset = 'utf8mb4';
    private $collation = 'utf8mb4_unicode_ci';
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Create new table
     */
    public function create($table, $callback) {
        $this->table = $table;
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        
        // Execute callback to define schema
        $callback($this);
        
        // Generate and execute CREATE TABLE statement
        $sql = $this->generateCreateTableSQL();
        
        return $this->conn->query($sql);
    }
    
    /**
     * Modify existing table
     */
    public function table($table, $callback) {
        $this->table = $table;
        $this->columns = [];
        $this->indexes = [];
        $this->foreignKeys = [];
        
        // Execute callback to define modifications
        $callback($this);
        
        // Generate and execute ALTER TABLE statements
        $statements = $this->generateAlterTableSQL();
        
        foreach ($statements as $sql) {
            $this->conn->query($sql);
        }
        
        return true;
    }
    
    /**
     * Drop table
     */
    public function drop($table) {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        return $this->conn->query($sql);
    }
    
    /**
     * Check if table exists
     */
    public function hasTable($table) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $table);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Add auto-incrementing primary key
     */
    public function id($name = 'id') {
        $this->columns[] = [
            'name' => $name,
            'type' => 'INT',
            'auto_increment' => true,
            'primary' => true,
            'unsigned' => true
        ];
        
        return $this;
    }
    
    /**
     * Add string column
     */
    public function string($name, $length = 255) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'VARCHAR',
            'length' => $length,
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add text column
     */
    public function text($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TEXT',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add long text column
     */
    public function longText($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'LONGTEXT',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add integer column
     */
    public function integer($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'INT',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add big integer column
     */
    public function bigInteger($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'BIGINT',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add small integer column
     */
    public function smallInteger($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'SMALLINT',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add decimal column
     */
    public function decimal($name, $precision = 10, $scale = 2) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale,
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add float column
     */
    public function float($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'FLOAT',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add double column
     */
    public function double($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DOUBLE',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add boolean column
     */
    public function boolean($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'BOOLEAN',
            'default' => false,
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add date column
     */
    public function date($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DATE',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add datetime column
     */
    public function dateTime($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DATETIME',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add time column
     */
    public function time($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TIME',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add timestamp column
     */
    public function timestamp($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TIMESTAMP',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add timestamps (created_at, updated_at)
     */
    public function timestamps() {
        $this->columns[] = [
            'name' => 'created_at',
            'type' => 'TIMESTAMP',
            'default' => 'CURRENT_TIMESTAMP',
            'null' => false
        ];
        
        $this->columns[] = [
            'name' => 'updated_at',
            'type' => 'TIMESTAMP',
            'default' => 'CURRENT_TIMESTAMP',
            'on_update' => 'CURRENT_TIMESTAMP',
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add enum column
     */
    public function enum($name, $values) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'ENUM',
            'values' => $values,
            'null' => false
        ];
        
        return $this;
    }
    
    /**
     * Add JSON column
     */
    public function json($name) {
        $this->columns[] = [
            'name' => $name,
            'type' => 'JSON',
            'null' => true
        ];
        
        return $this;
    }
    
    /**
     * Add foreign key column
     */
    public function foreignId($name) {
        $this->integer($name)->unsigned();
        return $this;
    }
    
    /**
     * Make column nullable
     */
    public function nullable() {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['null'] = true;
        }
        
        return $this;
    }
    
    /**
     * Set default value
     */
    public function default($value) {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['default'] = $value;
        }
        
        return $this;
    }
    
    /**
     * Make column unsigned
     */
    public function unsigned() {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['unsigned'] = true;
        }
        
        return $this;
    }
    
    /**
     * Add unique constraint on the last column
     */
    public function unique($columns = null) {
        if ($columns === null && !empty($this->columns)) {
            // Make the last column unique
            $lastColumn = $this->columns[count($this->columns) - 1];
            $columns = [$lastColumn['name']];
        }
        
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => is_array($columns) ? $columns : [$columns]
        ];
        
        return $this;
    }
    
    /**
     * Add index
     */
    public function index($columns) {
        $this->indexes[] = [
            'type' => 'index',
            'columns' => is_array($columns) ? $columns : [$columns]
        ];
        
        return $this;
    }
    
    /**
     * Add primary key
     */
    public function primary($columns) {
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => is_array($columns) ? $columns : [$columns]
        ];
        
        return $this;
    }
    
    /**
     * Add foreign key constraint
     */
    public function foreign($column) {
        return new ForeignKeyBuilder($this, $column);
    }
    
    /**
     * Set table engine
     */
    public function engine($engine) {
        $this->engine = $engine;
        return $this;
    }
    
    /**
     * Set table charset
     */
    public function charset($charset) {
        $this->charset = $charset;
        return $this;
    }
    
    /**
     * Set table collation
     */
    public function collation($collation) {
        $this->collation = $collation;
        return $this;
    }
    
    /**
     * Generate CREATE TABLE SQL
     */
    private function generateCreateTableSQL() {
        $sql = "CREATE TABLE `{$this->table}` (\n";
        
        // Add columns
        $columnDefinitions = [];
        foreach ($this->columns as $column) {
            $columnDefinitions[] = $this->generateColumnSQL($column);
        }
        
        $sql .= "  " . implode(",\n  ", $columnDefinitions);
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $sql .= ",\n  " . $this->generateIndexSQL($index);
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $sql .= ",\n  " . $this->generateForeignKeySQL($fk);
        }
        
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
        
        return $sql;
    }
    
    /**
     * Generate column SQL
     */
    private function generateColumnSQL($column) {
        $sql = "`{$column['name']}` {$column['type']}";
        
        // Add length/precision/values
        if (isset($column['length'])) {
            $sql .= "({$column['length']})";
        } elseif (isset($column['precision']) && isset($column['scale'])) {
            $sql .= "({$column['precision']},{$column['scale']})";
        } elseif (isset($column['values'])) {
            $values = array_map(function($v) { return "'{$v}'"; }, $column['values']);
            $sql .= "(" . implode(',', $values) . ")";
        }
        
        // Add unsigned
        if (!empty($column['unsigned'])) {
            $sql .= " UNSIGNED";
        }
        
        // Add null/not null
        $sql .= isset($column['null']) && $column['null'] ? " NULL" : " NOT NULL";
        
        // Add default
        if (isset($column['default'])) {
            if (is_string($column['default']) && !in_array($column['default'], ['CURRENT_TIMESTAMP', 'NULL'])) {
                $sql .= " DEFAULT '{$column['default']}'";
            } else {
                $sql .= " DEFAULT {$column['default']}";
            }
        }
        
        // Add on update
        if (isset($column['on_update'])) {
            $sql .= " ON UPDATE {$column['on_update']}";
        }
        
        // Add auto increment
        if (!empty($column['auto_increment'])) {
            $sql .= " AUTO_INCREMENT";
        }
        
        // Add primary key
        if (!empty($column['primary'])) {
            $sql .= " PRIMARY KEY";
        }
        
        return $sql;
    }
    
    /**
     * Generate index SQL
     */
    private function generateIndexSQL($index) {
        $columns = implode('`, `', $index['columns']);
        $indexName = implode('_', $index['columns']);
        
        switch ($index['type']) {
            case 'primary':
                return "PRIMARY KEY (`{$columns}`)";
            case 'unique':
                return "UNIQUE KEY `{$indexName}` (`{$columns}`)";
            default:
                return "KEY `{$indexName}` (`{$columns}`)";
        }
    }
    
    /**
     * Add foreign key constraint
     */
    public function addForeignKey($column, $referencesTable, $referencesColumn = 'id', $onDelete = 'CASCADE', $onUpdate = 'CASCADE') {
        $this->foreignKeys[] = [
            'column' => $column,
            'references_table' => $referencesTable,
            'references_column' => $referencesColumn,
            'on_delete' => $onDelete,
            'on_update' => $onUpdate
        ];
        
        return $this;
    }
    
    /**
     * Generate foreign key SQL
     */
    private function generateForeignKeySQL($fk) {
        $constraintName = "fk_{$this->table}_{$fk['column']}";
        $sql = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['references_table']}` (`{$fk['references_column']}`)";
        
        if (isset($fk['on_delete'])) {
            $sql .= " ON DELETE {$fk['on_delete']}";
        }
        
        if (isset($fk['on_update'])) {
            $sql .= " ON UPDATE {$fk['on_update']}";
        }
        
        return $sql;
    }
    
    /**
     * Generate ALTER TABLE SQL
     */
    private function generateAlterTableSQL() {
        $statements = [];
        
        // Add columns
        foreach ($this->columns as $column) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $this->generateColumnSQL($column);
        }
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD " . $this->generateIndexSQL($index);
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD " . $this->generateForeignKeySQL($fk);
        }
        
        return $statements;
    }
    
    /**
     * Drop column
     */
    public function dropColumn($column) {
        $sql = "ALTER TABLE `{$this->table}` DROP COLUMN `{$column}`";
        return $this->conn->query($sql);
    }
    
    /**
     * Drop index
     */
    public function dropIndex($indexName) {
        $sql = "ALTER TABLE `{$this->table}` DROP INDEX `{$indexName}`";
        return $this->conn->query($sql);
    }
    
    /**
     * Drop foreign key
     */
    public function dropForeignKey($constraintName) {
        $sql = "ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$constraintName}`";
        return $this->conn->query($sql);
    }
}

/**
 * Foreign Key Builder
 */
class ForeignKeyBuilder {
    private $schema;
    private $column;
    private $referencesColumn = 'id';
    private $onDelete = 'CASCADE';
    private $onUpdate = 'CASCADE';
    
    public function __construct($schema, $column) {
        $this->schema = $schema;
        $this->column = $column;
    }
    
    public function references($column) {
        $this->referencesColumn = $column;
        return $this;
    }
    
    public function on($table) {
        $this->schema->addForeignKey($this->column, $table, $this->referencesColumn, $this->onDelete, $this->onUpdate);
        return $this->schema;
    }
    
    public function onDelete($action) {
        $this->onDelete = $action;
        return $this;
    }
    
    public function onUpdate($action) {
        $this->onUpdate = $action;
        return $this;
    }
    
    public function cascadeOnDelete() {
        $this->onDelete = 'CASCADE';
        return $this;
    }
    
    public function restrictOnDelete() {
        $this->onDelete = 'RESTRICT';
        return $this;
    }
    
    public function nullOnDelete() {
        $this->onDelete = 'SET NULL';
        return $this;
    }
}