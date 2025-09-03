# Phase 5: Database Layer Enhancement Implementation Guide
## Connection Management, Migrations & Query Builder

**Duration**: Weeks 5-6  
**Priority**: LOW  
**Dependencies**: Phase 1-4 (Configuration, Security, Routing, MVC)  
**Team Size**: 1-2 developers  

---

## Overview

Phase 5 enhances the database layer with modern connection management, migration system, and query builder capabilities. This phase improves database performance, maintainability, and provides better development workflows.

### Key Objectives
- ✅ Implement advanced database connection management
- ✅ Create database migration system
- ✅ Build query builder for complex queries
- ✅ Add database seeding capabilities
- ✅ Implement connection pooling and optimization
- ✅ Create database backup and restore utilities

---

## Pre-Implementation Checklist

- [ ] **Previous Phases Complete**: Phases 1-4 are fully implemented and tested
- [ ] **Database Backup**: Create full database backup before changes
- [ ] **Performance Baseline**: Document current database performance
- [ ] **Migration Strategy**: Plan for zero-downtime migrations
- [ ] **Test Environment**: Set up separate database for testing migrations

---

## Task 1: Advanced Database Connection Management

### 1.1 Enhanced Database Manager
**File**: `core/Database.php` (Enhanced)

```php
<?php
/**
 * Enhanced Database Manager with Connection Pooling
 * Phase 5 Implementation
 */

class Database {
    private static $connections = [];
    private static $config = null;
    private static $logger = null;
    private static $connectionPool = [];
    private static $maxConnections = 10;
    private static $currentConnections = 0;
    
    /**
     * Initialize database manager
     */
    public static function initialize($config = null) {
        self::$config = $config ?? ConfigLoader::get('database');
        self::$logger = new Logger();
        
        // Set max connections from config
        self::$maxConnections = self::$config['max_connections'] ?? 10;
    }
    
    /**
     * Get database connection
     */
    public static function connection($name = null) {
        $name = $name ?? self::$config['default'] ?? 'mysql';
        
        // Try to get from pool first
        if ($pooledConnection = self::getFromPool($name)) {
            return $pooledConnection;
        }
        
        // Create new connection if under limit
        if (self::$currentConnections < self::$maxConnections) {
            $connection = self::createConnection($name);
            self::$connections[$name] = $connection;
            self::$currentConnections++;
            
            self::$logger->info("Database connection created", [
                'connection' => $name,
                'total_connections' => self::$currentConnections
            ]);
            
            return $connection;
        }
        
        // Wait for available connection
        throw new Exception("Maximum database connections reached");
    }
    
    /**
     * Create new database connection
     */
    private static function createConnection($name) {
        $config = self::$config['connections'][$name] ?? null;
        
        if (!$config) {
            throw new Exception("Database connection '{$name}' not configured");
        }
        
        // Create connection with retry logic
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $connection = new mysqli(
                    $config['host'],
                    $config['username'],
                    $config['password'],
                    $config['database'],
                    $config['port'] ?? 3306
                );
                
                if ($connection->connect_error) {
                    throw new Exception("Connection failed: " . $connection->connect_error);
                }
                
                // Configure connection
                self::configureConnection($connection, $config);
                
                // Add connection wrapper for monitoring
                return new DatabaseConnection($connection, $name, self::$logger);
                
            } catch (Exception $e) {
                self::$logger->warning("Database connection attempt {$attempt} failed", [
                    'connection' => $name,
                    'error' => $e->getMessage()
                ]);
                
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                
                sleep($retryDelay);
            }
        }
    }
    
    /**
     * Configure database connection
     */
    private static function configureConnection($connection, $config) {
        // Set charset
        $connection->set_charset($config['charset'] ?? 'utf8mb4');
        
        // Set SQL mode
        $sqlMode = $config['sql_mode'] ?? "ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
        $connection->query("SET sql_mode = '{$sqlMode}'");
        
        // Set timezone
        $timezone = $config['timezone'] ?? '+00:00';
        $connection->query("SET time_zone = '{$timezone}'");
        
        // Set wait timeout
        $waitTimeout = $config['wait_timeout'] ?? 28800; // 8 hours
        $connection->query("SET wait_timeout = {$waitTimeout}");
        
        // Enable query logging if configured
        if ($config['log_queries'] ?? false) {
            $connection->query("SET general_log = 'ON'");
        }
    }
    
    /**
     * Get connection from pool
     */
    private static function getFromPool($name) {
        if (isset(self::$connectionPool[$name]) && !empty(self::$connectionPool[$name])) {
            $connection = array_shift(self::$connectionPool[$name]);
            
            // Test connection is still alive
            if ($connection->ping()) {
                return $connection;
            }
        }
        
        return null;
    }
    
    /**
     * Return connection to pool
     */
    public static function releaseConnection($connection, $name) {
        if (!isset(self::$connectionPool[$name])) {
            self::$connectionPool[$name] = [];
        }
        
        // Don't pool too many connections
        if (count(self::$connectionPool[$name]) < 5) {
            self::$connectionPool[$name][] = $connection;
        } else {
            $connection->close();
            self::$currentConnections--;
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAll() {
        // Close active connections
        foreach (self::$connections as $name => $connection) {
            $connection->close();
        }
        
        // Close pooled connections
        foreach (self::$connectionPool as $name => $connections) {
            foreach ($connections as $connection) {
                $connection->close();
            }
        }
        
        self::$connections = [];
        self::$connectionPool = [];
        self::$currentConnections = 0;
        
        self::$logger->info("All database connections closed");
    }
    
    /**
     * Get connection statistics
     */
    public static function getStats() {
        return [
            'active_connections' => self::$currentConnections,
            'max_connections' => self::$maxConnections,
            'pooled_connections' => array_sum(array_map('count', self::$connectionPool)),
            'connection_names' => array_keys(self::$connections)
        ];
    }
    
    /**
     * Health check for database connections
     */
    public static function healthCheck() {
        $results = [];
        
        foreach (self::$config['connections'] as $name => $config) {
            try {
                $connection = self::connection($name);
                $result = $connection->query("SELECT 1 as test");
                
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => null, // Could implement timing
                    'last_checked' => date('Y-m-d H:i:s')
                ];
                
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'last_checked' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $results;
    }
}

/**
 * Database Connection Wrapper for Monitoring
 */
class DatabaseConnection {
    private $connection;
    private $name;
    private $logger;
    private $queryCount = 0;
    private $totalTime = 0;
    
    public function __construct($connection, $name, $logger) {
        $this->connection = $connection;
        $this->name = $name;
        $this->logger = $logger;
    }
    
    public function query($sql, $resultmode = MYSQLI_STORE_RESULT) {
        $startTime = microtime(true);
        
        try {
            $result = $this->connection->query($sql, $resultmode);
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            $this->queryCount++;
            $this->totalTime += $duration;
            
            // Log slow queries
            $slowQueryThreshold = 1.0; // 1 second
            if ($duration > $slowQueryThreshold) {
                $this->logger->warning("Slow query detected", [
                    'connection' => $this->name,
                    'duration' => $duration,
                    'query' => substr($sql, 0, 500) // Log first 500 chars
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("Query error", [
                'connection' => $this->name,
                'error' => $e->getMessage(),
                'query' => substr($sql, 0, 500)
            ]);
            
            throw $e;
        }
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function begin_transaction($flags = 0, $name = null) {
        return $this->connection->begin_transaction($flags, $name);
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function close() {
        $this->logger->info("Database connection closed", [
            'connection' => $this->name,
            'query_count' => $this->queryCount,
            'total_time' => $this->totalTime,
            'avg_query_time' => $this->queryCount > 0 ? ($this->totalTime / $this->queryCount) : 0
        ]);
        
        return $this->connection->close();
    }
    
    // Delegate other methods to the underlying connection
    public function __call($method, $args) {
        return call_user_func_array([$this->connection, $method], $args);
    }
    
    public function __get($property) {
        return $this->connection->$property;
    }
}
```

---

## Task 2: Database Migration System

### 2.1 Migration Manager
**File**: `core/Migration.php`

```php
<?php
/**
 * Database Migration System
 * Phase 5 Implementation
 */

class Migration {
    private $conn;
    private $logger;
    private $migrationsPath;
    private $migrationsTable = 'migrations';
    
    public function __construct($conn, $migrationsPath = null) {
        $this->conn = $conn;
        $this->logger = new Logger();
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../database/migrations/';
        
        $this->createMigrationsTable();
    }
    
    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB";
        
        $this->conn->query($sql);
    }
    
    /**
     * Run pending migrations
     */
    public function migrate() {
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            $this->logger->info("No pending migrations");
            return ['message' => 'No pending migrations', 'count' => 0];
        }
        
        $batch = $this->getNextBatchNumber();
        $migrated = [];
        
        foreach ($pendingMigrations as $migration) {
            try {
                $this->conn->begin_transaction();
                
                $this->executeMigration($migration);
                $this->recordMigration($migration, $batch);
                
                $this->conn->commit();
                
                $migrated[] = $migration;
                
                $this->logger->info("Migration executed successfully", [
                    'migration' => $migration,
                    'batch' => $batch
                ]);
                
            } catch (Exception $e) {
                $this->conn->rollback();
                
                $this->logger->error("Migration failed", [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ]);
                
                throw new Exception("Migration '{$migration}' failed: " . $e->getMessage());
            }
        }
        
        return [
            'message' => 'Migrations executed successfully',
            'count' => count($migrated),
            'migrations' => $migrated
        ];
    }
    
    /**
     * Rollback migrations
     */
    public function rollback($steps = 1) {
        $migrationsToRollback = $this->getMigrationsToRollback($steps);
        
        if (empty($migrationsToRollback)) {
            return ['message' => 'No migrations to rollback', 'count' => 0];
        }
        
        $rolledBack = [];
        
        foreach ($migrationsToRollback as $migration) {
            try {
                $this->conn->begin_transaction();
                
                $this->rollbackMigration($migration);
                $this->removeMigrationRecord($migration);
                
                $this->conn->commit();
                
                $rolledBack[] = $migration;
                
                $this->logger->info("Migration rolled back successfully", [
                    'migration' => $migration
                ]);
                
            } catch (Exception $e) {
                $this->conn->rollback();
                
                $this->logger->error("Migration rollback failed", [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ]);
                
                throw new Exception("Rollback of '{$migration}' failed: " . $e->getMessage());
            }
        }
        
        return [
            'message' => 'Migrations rolled back successfully',
            'count' => count($rolledBack),
            'migrations' => $rolledBack
        ];
    }
    
    /**
     * Get migration status
     */
    public function status() {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $status = [];
        
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'executed' => in_array($migration, $executedMigrations),
                'batch' => $this->getMigrationBatch($migration)
            ];
        }
        
        return $status;
    }
    
    /**
     * Create new migration file
     */
    public function create($name, $table = null) {
        $timestamp = date('Y_m_d_His');
        $className = $this->formatClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . $filename;
        
        $template = $this->getMigrationTemplate($className, $table);
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        file_put_contents($filepath, $template);
        
        $this->logger->info("Migration file created", ['file' => $filename]);
        
        return [
            'message' => 'Migration file created successfully',
            'file' => $filename,
            'path' => $filepath
        ];
    }
    
    /**
     * Execute single migration
     */
    private function executeMigration($migrationName) {
        $migrationFile = $this->migrationsPath . $migrationName . '.php';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: {$migrationFile}");
        }
        
        require_once $migrationFile;
        
        $className = $this->getMigrationClassName($migrationName);
        
        if (!class_exists($className)) {
            throw new Exception("Migration class not found: {$className}");
        }
        
        $migration = new $className($this->conn);
        
        if (!method_exists($migration, 'up')) {
            throw new Exception("Migration must have 'up' method: {$className}");
        }
        
        $migration->up();
    }
    
    /**
     * Rollback single migration
     */
    private function rollbackMigration($migrationName) {
        $migrationFile = $this->migrationsPath . $migrationName . '.php';
        
        require_once $migrationFile;
        
        $className = $this->getMigrationClassName($migrationName);
        $migration = new $className($this->conn);
        
        if (!method_exists($migration, 'down')) {
            throw new Exception("Migration must have 'down' method for rollback: {$className}");
        }
        
        $migration->down();
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations() {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        return array_diff($allMigrations, $executedMigrations);
    }
    
    /**
     * Get all migration files
     */
    private function getAllMigrationFiles() {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = scandir($this->migrationsPath);
        $migrations = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get executed migrations
     */
    private function getExecutedMigrations() {
        $sql = "SELECT migration FROM {$this->migrationsTable} ORDER BY migration ASC";
        $result = $this->conn->query($sql);
        
        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }
    
    /**
     * Record migration execution
     */
    private function recordMigration($migration, $batch) {
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $migration, $batch);
        $stmt->execute();
    }
    
    /**
     * Remove migration record
     */
    private function removeMigrationRecord($migration) {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $migration);
        $stmt->execute();
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatchNumber() {
        $sql = "SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return ($row['max_batch'] ?? 0) + 1;
    }
    
    /**
     * Get migrations to rollback
     */
    private function getMigrationsToRollback($steps) {
        $sql = "SELECT migration FROM {$this->migrationsTable} 
                ORDER BY batch DESC, migration DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $steps);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }
    
    /**
     * Get migration batch number
     */
    private function getMigrationBatch($migration) {
        $sql = "SELECT batch FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $migration);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['batch'];
        }
        
        return null;
    }
    
    /**
     * Get migration class name
     */
    private function getMigrationClassName($migrationName) {
        // Remove timestamp prefix and convert to class name
        $parts = explode('_', $migrationName);
        if (count($parts) > 4) {
            $nameParts = array_slice($parts, 4); // Skip timestamp parts
            return $this->formatClassName(implode('_', $nameParts));
        }
        
        return $this->formatClassName($migrationName);
    }
    
    /**
     * Format class name
     */
    private function formatClassName($name) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
    
    /**
     * Get migration template
     */
    private function getMigrationTemplate($className, $table) {
        $tableOperations = '';
        
        if ($table) {
            if (strpos($className, 'Create') === 0) {
                $tableOperations = "
        // Create table
        \$sql = \"CREATE TABLE {$table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB\";
        
        \$this->conn->query(\$sql);";
            } else {
                $tableOperations = "
        // Modify table {$table}
        // Add your table modifications here";
            }
        }
        
        return "<?php
/**
 * Migration: {$className}
 * Created: " . date('Y-m-d H:i:s') . "
 */

class {$className} {
    private \$conn;
    
    public function __construct(\$conn) {
        \$this->conn = \$conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        {$tableOperations}
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        // Add rollback operations here
    }
}
";
    }
}
```

### 2.2 Schema Builder
**File**: `core/SchemaBuilder.php`

```php
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
     * Add unique constraint
     */
    public function unique($columns) {
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
     * Add foreign key constraint
     */
    public function foreign($column) {
        return new ForeignKeyBuilder($this, $column);
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
        
        // Add length/values
        if (isset($column['length'])) {
            $sql .= "({$column['length']})";
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
            if (is_string($column['default']) && $column['default'] !== 'CURRENT_TIMESTAMP') {
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
        
        if ($index['type'] === 'unique') {
            return "UNIQUE KEY `" . implode('_', $index['columns']) . "` (`{$columns}`)";
        } else {
            return "KEY `" . implode('_', $index['columns']) . "` (`{$columns}`)";
        }
    }
    
    /**
     * Add foreign key constraint
     */
    public function addForeignKey($column, $referencesTable, $referencesColumn = 'id', $onDelete = 'CASCADE') {
        $this->foreignKeys[] = [
            'column' => $column,
            'references_table' => $referencesTable,
            'references_column' => $referencesColumn,
            'on_delete' => $onDelete
        ];
        
        return $this;
    }
    
    /**
     * Generate foreign key SQL
     */
    private function generateForeignKeySQL($fk) {
        return "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['references_table']}` (`{$fk['references_column']}`) ON DELETE {$fk['on_delete']}";
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
}

/**
 * Foreign Key Builder
 */
class ForeignKeyBuilder {
    private $schema;
    private $column;
    
    public function __construct($schema, $column) {
        $this->schema = $schema;
        $this->column = $column;
    }
    
    public function references($column) {
        $this->referencesColumn = $column;
        return $this;
    }
    
    public function on($table) {
        $this->schema->addForeignKey($this->column, $table, $this->referencesColumn ?? 'id');
        return $this->schema;
    }
    
    public function onDelete($action) {
        // This would need to be implemented to modify the last foreign key
        return $this;
    }
}
```

---

## Task 3: Query Builder Implementation

### 3.1 Query Builder Class
**File**: `core/QueryBuilder.php`

```php
<?php
/**
 * Query Builder for complex database queries
 * Phase 5 Implementation
 */

class QueryBuilder {
    private $conn;
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $wheres = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit;
    private $offset;
    private $bindings = [];
    
    public function __construct($conn, $table = null) {
        $this->conn = $conn;
        $this->table = $table;
    }
    
    /**
     * Set table for query
     */
    public function table($table) {
        $this->table = $table;
        return $this;
    }
    
    /**
     * Set select columns
     */
    public function select($columns = ['*']) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add where condition
     */
    public function where($column, $operator = '=', $value = null) {
        // Handle single parameter (column = value)
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add OR where condition
     */
    public function orWhere($column, $operator = '=', $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add where in condition
     */
    public function whereIn($column, $values) {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add where not in condition
     */
    public function whereNotIn($column, $values) {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add where null condition
     */
    public function whereNull($column) {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add where not null condition
     */
    public function whereNotNull($column) {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add where between condition
     */
    public function whereBetween($column, $values) {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        
        return $this;
    }
    
    /**
     * Add where like condition
     */
    public function whereLike($column, $value) {
        return $this->where($column, 'LIKE', $value);
    }
    
    /**
     * Add join
     */
    public function join($table, $first, $operator = '=', $second = null) {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * Add left join
     */
    public function leftJoin($table, $first, $operator = '=', $second = null) {
        $this->joins[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * Add right join
     */
    public function rightJoin($table, $first, $operator = '=', $second = null) {
        $this->joins[] = [
            'type' => 'right',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * Add order by
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Add group by
     */
    public function groupBy($columns) {
        $this->groupBy = array_merge($this->groupBy, is_array($columns) ? $columns : func_get_args());
        return $this;
    }
    
    /**
     * Add having condition
     */
    public function having($column, $operator = '=', $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Set limit
     */
    public function limit($count) {
        $this->limit = $count;
        return $this;
    }
    
    /**
     * Set offset
     */
    public function offset($count) {
        $this->offset = $count;
        return $this;
    }
    
    /**
     * Take (alias for limit)
     */
    public function take($count) {
        return $this->limit($count);
    }
    
    /**
     * Skip (alias for offset)
     */
    public function skip($count) {
        return $this->offset($count);
    }
    
    /**
     * Get results
     */
    public function get() {
        $sql = $this->toSQL();
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = str_repeat('s', count($this->bindings)); // Assume all strings for simplicity
            $stmt->bind_param($types, ...$this->bindings);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get first result
     */
    public function first() {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
    
    /**
     * Count results
     */
    public function count() {
        // Create a copy for count query
        $countQuery = clone $this;
        $countQuery->select = ['COUNT(*) as count'];
        $countQuery->orderBy = [];
        $countQuery->limit = null;
        $countQuery->offset = null;
        
        $result = $countQuery->first();
        return $result['count'] ?? 0;
    }
    
    /**
     * Check if any results exist
     */
    public function exists() {
        return $this->count() > 0;
    }
    
    /**
     * Insert data
     */
    public function insert($data) {
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        
        $types = "";
        $values = [];
        foreach ($data as $value) {
            $types .= is_int($value) ? "i" : "s";
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        
        return $result ? $this->conn->insert_id : false;
    }
    
    /**
     * Update data
     */
    public function update($data) {
        if (empty($data)) {
            return false;
        }
        
        $setParts = [];
        $values = [];
        $types = "";
        
        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $values[] = $value;
            $types .= is_int($value) ? "i" : "s";
        }
        
        // Add where bindings
        foreach ($this->bindings as $binding) {
            $values[] = $binding;
            $types .= is_int($binding) ? "i" : "s";
        }
        
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setParts) . $this->compileWheres();
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Delete records
     */
    public function delete() {
        $sql = "DELETE FROM `{$this->table}`" . $this->compileWheres();
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = str_repeat('s', count($this->bindings));
            $stmt->bind_param($types, ...$this->bindings);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Convert to SQL string
     */
    public function toSQL() {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM `{$this->table}`";
        
        // Add joins
        $sql .= $this->compileJoins();
        
        // Add where conditions
        $sql .= $this->compileWheres();
        
        // Add group by
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        // Add having
        if (!empty($this->having)) {
            $sql .= $this->compileHaving();
        }
        
        // Add order by
        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                $orderParts[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }
        
        // Add limit and offset
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= " OFFSET " . $this->offset;
            }
        }
        
        return $sql;
    }
    
    /**
     * Compile joins
     */
    private function compileJoins() {
        $sql = '';
        
        foreach ($this->joins as $join) {
            $type = strtoupper($join['type']);
            $sql .= " {$type} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        return $sql;
    }
    
    /**
     * Compile where conditions
     */
    private function compileWheres() {
        if (empty($this->wheres)) {
            return '';
        }
        
        $sql = ' WHERE ';
        $conditions = [];
        
        foreach ($this->wheres as $i => $where) {
            $boolean = $i === 0 ? '' : " {$where['boolean']} ";
            
            switch ($where['type']) {
                case 'basic':
                    $conditions[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                    
                case 'in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $conditions[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                    
                case 'not_in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $conditions[] = $boolean . "{$where['column']} NOT IN ({$placeholders})";
                    break;
                    
                case 'null':
                    $conditions[] = $boolean . "{$where['column']} IS NULL";
                    break;
                    
                case 'not_null':
                    $conditions[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;
                    
                case 'between':
                    $conditions[] = $boolean . "{$where['column']} BETWEEN ? AND ?";
                    break;
            }
        }
        
        return $sql . implode('', $conditions);
    }
    
    /**
     * Compile having conditions
     */
    private function compileHaving() {
        if (empty($this->having)) {
            return '';
        }
        
        $conditions = [];
        foreach ($this->having as $having) {
            $conditions[] = "{$having['column']} {$having['operator']} ?";
        }
        
        return " HAVING " . implode(' AND ', $conditions);
    }
}
```

---

## Task 4: Database Seeding System

### 4.1 Database Seeder
**File**: `database/Seeder.php`

```php
<?php
/**
 * Database Seeder - Populate database with sample data
 * Phase 5 Implementation
 */

class Seeder {
    protected $conn;
    protected $logger;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
    }
    
    /**
     * Run all seeders
     */
    public function run() {
        $this->logger->info("Starting database seeding");
        
        try {
            $this->seedUsers();
            $this->seedCourses();
            $this->seedHolidayPrograms();
            $this->seedAttendance();
            
            $this->logger->info("Database seeding completed successfully");
            
        } catch (Exception $e) {
            $this->logger->error("Database seeding failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Seed users table
     */
    protected function seedUsers() {
        $this->logger->info("Seeding users table");
        
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@scibono.ac.za',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'name' => 'System',
                'surname' => 'Administrator',
                'user_type' => 'admin',
                'active' => 1
            ],
            [
                'username' => 'mentor1',
                'email' => 'mentor1@scibono.ac.za',
                'password' => password_hash('mentor123', PASSWORD_DEFAULT),
                'name' => 'John',
                'surname' => 'Smith',
                'user_type' => 'mentor',
                'active' => 1
            ],
            [
                'username' => 'member1',
                'email' => 'member1@example.com',
                'password' => password_hash('member123', PASSWORD_DEFAULT),
                'name' => 'Jane',
                'surname' => 'Doe',
                'user_type' => 'member',
                'active' => 1,
                'school' => 'Example High School',
                'grade' => 11
            ]
        ];
        
        foreach ($users as $user) {
            // Check if user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $user['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->insertUser($user);
            }
        }
    }
    
    /**
     * Seed courses table
     */
    protected function seedCourses() {
        $this->logger->info("Seeding courses table");
        
        $courses = [
            [
                'title' => 'Introduction to Programming',
                'description' => 'Learn the basics of programming with Python',
                'instructor_id' => 2, // mentor1
                'duration' => 40,
                'level' => 'beginner',
                'category' => 'programming',
                'active' => 1
            ],
            [
                'title' => 'Web Development Fundamentals',
                'description' => 'HTML, CSS, and JavaScript basics',
                'instructor_id' => 2,
                'duration' => 60,
                'level' => 'beginner',
                'category' => 'web-development',
                'active' => 1
            ],
            [
                'title' => 'Digital Design',
                'description' => 'Graphics design using digital tools',
                'instructor_id' => 2,
                'duration' => 30,
                'level' => 'intermediate',
                'category' => 'design',
                'active' => 1
            ]
        ];
        
        foreach ($courses as $course) {
            $this->insertCourse($course);
        }
    }
    
    /**
     * Seed holiday programs
     */
    protected function seedHolidayPrograms() {
        $this->logger->info("Seeding holiday programs table");
        
        $programs = [
            [
                'name' => 'Summer Tech Camp 2024',
                'description' => 'Intensive summer technology program',
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-15',
                'registration_deadline' => '2024-11-15',
                'max_participants' => 50,
                'status' => 'active'
            ],
            [
                'name' => 'Winter Coding Bootcamp',
                'description' => 'Learn to code during winter holidays',
                'start_date' => '2024-07-01',
                'end_date' => '2024-07-21',
                'registration_deadline' => '2024-06-15',
                'max_participants' => 30,
                'status' => 'active'
            ]
        ];
        
        foreach ($programs as $program) {
            $this->insertHolidayProgram($program);
        }
    }
    
    /**
     * Seed sample attendance data
     */
    protected function seedAttendance() {
        $this->logger->info("Seeding attendance table");
        
        // Create some sample attendance records for the past week
        $userIds = [2, 3]; // mentor1 and member1
        
        for ($i = 7; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            foreach ($userIds as $userId) {
                // Random chance of attendance
                if (rand(1, 10) > 3) { // 70% chance
                    $signInTime = $date . ' ' . sprintf('%02d:%02d:00', rand(8, 10), rand(0, 59));
                    $signOutTime = $date . ' ' . sprintf('%02d:%02d:00', rand(15, 17), rand(0, 59));
                    
                    $this->insertAttendance([
                        'user_id' => $userId,
                        'sign_in_time' => $signInTime,
                        'sign_out_time' => $signOutTime,
                        'sign_in_status' => 'signedOut'
                    ]);
                }
            }
        }
    }
    
    /**
     * Insert user
     */
    private function insertUser($user) {
        $sql = "INSERT INTO users (username, email, password, name, surname, user_type, active, school, grade, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssissi", 
            $user['username'],
            $user['email'],
            $user['password'],
            $user['name'],
            $user['surname'],
            $user['user_type'],
            $user['active'],
            $user['school'] ?? null,
            $user['grade'] ?? null
        );
        
        $stmt->execute();
        
        $this->logger->info("User seeded", ['username' => $user['username']]);
    }
    
    /**
     * Insert course
     */
    private function insertCourse($course) {
        // Check if course exists
        $stmt = $this->conn->prepare("SELECT id FROM courses WHERE title = ?");
        $stmt->bind_param("s", $course['title']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO courses (title, description, instructor_id, duration, level, category, active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssiissi", 
                $course['title'],
                $course['description'],
                $course['instructor_id'],
                $course['duration'],
                $course['level'],
                $course['category'],
                $course['active']
            );
            
            $stmt->execute();
            
            $this->logger->info("Course seeded", ['title' => $course['title']]);
        }
    }
    
    /**
     * Insert holiday program
     */
    private function insertHolidayProgram($program) {
        // Check if program exists
        $stmt = $this->conn->prepare("SELECT id FROM holiday_programs WHERE name = ?");
        $stmt->bind_param("s", $program['name']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO holiday_programs (name, description, start_date, end_date, registration_deadline, max_participants, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssssss", 
                $program['name'],
                $program['description'],
                $program['start_date'],
                $program['end_date'],
                $program['registration_deadline'],
                $program['max_participants'],
                $program['status']
            );
            
            $stmt->execute();
            
            $this->logger->info("Holiday program seeded", ['name' => $program['name']]);
        }
    }
    
    /**
     * Insert attendance record
     */
    private function insertAttendance($attendance) {
        $sql = "INSERT INTO attendance (user_id, sign_in_time, sign_out_time, sign_in_status) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isss", 
            $attendance['user_id'],
            $attendance['sign_in_time'],
            $attendance['sign_out_time'],
            $attendance['sign_in_status']
        );
        
        $stmt->execute();
    }
}
```

---

## Task 5: CLI Commands for Database Management

### 5.1 Database CLI Commands
**File**: `cli/database.php`

```php
#!/usr/bin/env php
<?php
/**
 * Database CLI Commands
 * Phase 5 Implementation
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Migration.php';
require_once __DIR__ . '/../database/Seeder.php';

// Initialize database
Database::initialize();
$conn = Database::connection();

// Parse command line arguments
$command = $argv[1] ?? '';

switch ($command) {
    case 'migrate':
        handleMigrate();
        break;
        
    case 'migrate:rollback':
        handleRollback();
        break;
        
    case 'migrate:status':
        handleMigrationStatus();
        break;
        
    case 'migrate:create':
        handleCreateMigration();
        break;
        
    case 'seed':
        handleSeed();
        break;
        
    case 'db:backup':
        handleBackup();
        break;
        
    case 'db:restore':
        handleRestore();
        break;
        
    case 'db:health':
        handleHealthCheck();
        break;
        
    default:
        showHelp();
        break;
}

function handleMigrate() {
    global $conn;
    
    echo "Running migrations...\n";
    
    try {
        $migration = new Migration($conn);
        $result = $migration->migrate();
        
        echo "✅ {$result['message']}\n";
        echo "Migrations executed: {$result['count']}\n";
        
        if (!empty($result['migrations'])) {
            foreach ($result['migrations'] as $migrationName) {
                echo "  - {$migrationName}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleRollback() {
    global $conn, $argv;
    
    $steps = isset($argv[2]) ? intval($argv[2]) : 1;
    
    echo "Rolling back {$steps} migration(s)...\n";
    
    try {
        $migration = new Migration($conn);
        $result = $migration->rollback($steps);
        
        echo "✅ {$result['message']}\n";
        echo "Migrations rolled back: {$result['count']}\n";
        
        if (!empty($result['migrations'])) {
            foreach ($result['migrations'] as $migrationName) {
                echo "  - {$migrationName}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Rollback failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleMigrationStatus() {
    global $conn;
    
    try {
        $migration = new Migration($conn);
        $status = $migration->status();
        
        echo "Migration Status:\n";
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-50s %-10s %-10s\n", "Migration", "Status", "Batch");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($status as $item) {
            $statusText = $item['executed'] ? '✅ Executed' : '⏳ Pending';
            $batch = $item['batch'] ?? '-';
            
            echo sprintf("%-50s %-10s %-10s\n", $item['migration'], $statusText, $batch);
        }
        
    } catch (Exception $e) {
        echo "❌ Failed to get migration status: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleCreateMigration() {
    global $conn, $argv;
    
    $name = $argv[2] ?? null;
    $table = $argv[3] ?? null;
    
    if (!$name) {
        echo "❌ Migration name is required\n";
        echo "Usage: php cli/database.php migrate:create migration_name [table_name]\n";
        exit(1);
    }
    
    try {
        $migration = new Migration($conn);
        $result = $migration->create($name, $table);
        
        echo "✅ {$result['message']}\n";
        echo "File: {$result['file']}\n";
        echo "Path: {$result['path']}\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to create migration: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleSeed() {
    global $conn;
    
    echo "Seeding database...\n";
    
    try {
        $seeder = new Seeder($conn);
        $seeder->run();
        
        echo "✅ Database seeded successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Seeding failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleBackup() {
    $config = ConfigLoader::get('database.connections.mysql');
    $backupPath = __DIR__ . '/../storage/backups/';
    
    if (!is_dir($backupPath)) {
        mkdir($backupPath, 0755, true);
    }
    
    $filename = 'backup_' . date('Y_m_d_His') . '.sql';
    $filepath = $backupPath . $filename;
    
    $command = sprintf(
        'mysqldump -h%s -u%s -p%s %s > %s',
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $filepath
    );
    
    echo "Creating database backup...\n";
    
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ Database backup created successfully\n";
        echo "File: {$filename}\n";
        echo "Path: {$filepath}\n";
        echo "Size: " . formatBytes(filesize($filepath)) . "\n";
    } else {
        echo "❌ Backup failed\n";
        exit(1);
    }
}

function handleRestore() {
    global $argv;
    
    $backupFile = $argv[2] ?? null;
    
    if (!$backupFile) {
        echo "❌ Backup file is required\n";
        echo "Usage: php cli/database.php db:restore backup_file.sql\n";
        exit(1);
    }
    
    if (!file_exists($backupFile)) {
        echo "❌ Backup file not found: {$backupFile}\n";
        exit(1);
    }
    
    $config = ConfigLoader::get('database.connections.mysql');
    
    $command = sprintf(
        'mysql -h%s -u%s -p%s %s < %s',
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $backupFile
    );
    
    echo "⚠️  WARNING: This will overwrite the current database!\n";
    echo "Are you sure you want to continue? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    
    if (strtolower($confirmation) !== 'y') {
        echo "Operation cancelled.\n";
        exit(0);
    }
    
    echo "Restoring database from backup...\n";
    
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ Database restored successfully\n";
    } else {
        echo "❌ Restore failed\n";
        exit(1);
    }
}

function handleHealthCheck() {
    echo "Checking database health...\n";
    
    try {
        $results = Database::healthCheck();
        
        echo "\nDatabase Health Check Results:\n";
        echo str_repeat('-', 50) . "\n";
        
        foreach ($results as $connection => $result) {
            $status = $result['status'] === 'healthy' ? '✅' : '❌';
            echo sprintf("%-20s %s %s\n", $connection, $status, $result['status']);
            
            if ($result['status'] !== 'healthy') {
                echo "  Error: {$result['error']}\n";
            }
            
            echo "  Last checked: {$result['last_checked']}\n";
        }
        
        // Connection statistics
        $stats = Database::getStats();
        echo "\nConnection Statistics:\n";
        echo "Active connections: {$stats['active_connections']}/{$stats['max_connections']}\n";
        echo "Pooled connections: {$stats['pooled_connections']}\n";
        
    } catch (Exception $e) {
        echo "❌ Health check failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function showHelp() {
    echo "Database Management CLI\n";
    echo "=======================\n\n";
    echo "Available commands:\n\n";
    echo "Migration commands:\n";
    echo "  migrate                    Run all pending migrations\n";
    echo "  migrate:rollback [steps]   Rollback migrations (default: 1 step)\n";
    echo "  migrate:status             Show migration status\n";
    echo "  migrate:create <name>      Create new migration file\n\n";
    echo "Seeding commands:\n";
    echo "  seed                       Run database seeders\n\n";
    echo "Backup/Restore commands:\n";
    echo "  db:backup                  Create database backup\n";
    echo "  db:restore <file>          Restore from backup file\n\n";
    echo "Utility commands:\n";
    echo "  db:health                  Check database connection health\n\n";
    echo "Examples:\n";
    echo "  php cli/database.php migrate\n";
    echo "  php cli/database.php migrate:create create_users_table\n";
    echo "  php cli/database.php migrate:rollback 2\n";
    echo "  php cli/database.php seed\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>
```

---

## Phase 5 Completion Checklist

### Database Connection Management
- [ ] Enhanced Database class with connection pooling implemented
- [ ] Connection monitoring and health checks working
- [ ] Configuration-based connection management active
- [ ] Connection retry logic and error handling implemented

### Migration System
- [ ] Migration class with up/down methods created
- [ ] Migration tracking table implemented
- [ ] CLI commands for migration management working
- [ ] Schema builder for table creation implemented

### Query Builder
- [ ] QueryBuilder class with method chaining implemented
- [ ] Support for complex queries (joins, where conditions, etc.)
- [ ] Insert, update, delete operations working
- [ ] Query compilation and execution tested

### Database Seeding
- [ ] Seeder class for sample data implemented
- [ ] CLI command for running seeders working
- [ ] Sample data for all major tables created
- [ ] Seeding is idempotent (can run multiple times safely)

### CLI Tools
- [ ] Database backup and restore commands working
- [ ] Migration management commands functional
- [ ] Health check command implemented
- [ ] Clear help documentation provided

### Performance & Monitoring
- [ ] Query performance logging implemented
- [ ] Slow query detection working
- [ ] Connection statistics tracking active
- [ ] Database health monitoring functional

---

## Benefits Achieved

1. **Better Connection Management**: Connection pooling and retry logic improve reliability
2. **Version Control for Database**: Migration system provides database versioning
3. **Development Workflow**: CLI tools streamline database management tasks
4. **Query Optimization**: Query builder provides better query construction
5. **Data Management**: Seeding system enables consistent sample data
6. **Monitoring**: Health checks and performance logging improve observability

---

## Next Phase Preparation

Before proceeding to Phase 6 (Frontend Improvements):
1. **Migration Testing**: Test migration system thoroughly with sample migrations
2. **Performance Testing**: Verify query builder performance vs direct queries
3. **Backup Strategy**: Implement regular backup procedures
4. **Team Training**: Train team on new CLI tools and migration workflow
5. **Documentation**: Document database management procedures

**Phase 5 provides advanced database capabilities that support scalable development and production operations.**