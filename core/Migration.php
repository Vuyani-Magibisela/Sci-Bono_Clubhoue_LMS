<?php
/**
 * Database Migration System
 * Phase 5 Implementation
 */

require_once __DIR__ . '/Logger.php';

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