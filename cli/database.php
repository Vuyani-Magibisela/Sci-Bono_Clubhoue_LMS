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

// Initialize with error handling
try {
    // Initialize database with fallback to existing connection
    if (class_exists('Database')) {
        Database::initialize();
        $conn = Database::connection();
    } else {
        // Fallback to existing connection method
        require_once __DIR__ . '/../server.php';
        global $conn;
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
    }
} catch (Exception $e) {
    // Fallback connection
    require_once __DIR__ . '/../server.php';
    global $conn;
    if (!$conn) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

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
        
    case 'seed:clear':
        handleSeedClear();
        break;
        
    case 'seed:specific':
        handleSeedSpecific();
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
        
    case 'db:optimize':
        handleOptimize();
        break;
        
    case 'db:analyze':
        handleAnalyze();
        break;
        
    case 'db:tables':
        handleListTables();
        break;
        
    case 'db:size':
        handleDatabaseSize();
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
        echo sprintf("%-50s %-12s %-10s\n", "Migration", "Status", "Batch");
        echo str_repeat('-', 80) . "\n";
        
        if (empty($status)) {
            echo "No migrations found.\n";
            return;
        }
        
        foreach ($status as $item) {
            $statusText = $item['executed'] ? '✅ Executed' : '⏳ Pending';
            $batch = $item['batch'] ?? '-';
            
            echo sprintf("%-50s %-12s %-10s\n", 
                substr($item['migration'], 0, 49), 
                $statusText, 
                $batch
            );
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
        $result = $seeder->run();
        
        if ($result['success']) {
            echo "✅ {$result['message']}\n";
        } else {
            echo "❌ {$result['message']}\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "❌ Seeding failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleSeedClear() {
    global $conn;
    
    echo "⚠️  WARNING: This will clear all seeded data!\n";
    echo "Are you sure you want to continue? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    
    if (strtolower($confirmation) !== 'y') {
        echo "Operation cancelled.\n";
        exit(0);
    }
    
    echo "Clearing seeded data...\n";
    
    try {
        $seeder = new Seeder($conn);
        $result = $seeder->clear();
        
        if ($result['success']) {
            echo "✅ {$result['message']}\n";
        } else {
            echo "❌ {$result['message']}\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "❌ Failed to clear seeded data: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleSeedSpecific() {
    global $conn, $argv;
    
    $seederName = $argv[2] ?? null;
    
    if (!$seederName) {
        echo "❌ Seeder name is required\n";
        echo "Usage: php cli/database.php seed:specific seeder_name\n";
        echo "Available seeders: users, courses, holidayPrograms, lessons, attendance, enrollments\n";
        exit(1);
    }
    
    echo "Running {$seederName} seeder...\n";
    
    try {
        $seeder = new Seeder($conn);
        $result = $seeder->seed($seederName);
        
        if ($result['success']) {
            echo "✅ {$result['message']}\n";
        } else {
            echo "❌ {$result['message']}\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "❌ Seeder failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleBackup() {
    echo "Creating database backup...\n";
    
    try {
        $config = [
            'host' => DB_HOST ?? 'localhost',
            'username' => DB_USER ?? 'root',
            'password' => DB_PASS ?? '',
            'database' => DB_NAME ?? 'accounts'
        ];
        
        $backupPath = __DIR__ . '/../storage/backups/';
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $filename = 'backup_' . date('Y_m_d_His') . '.sql';
        $filepath = $backupPath . $filename;
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($filepath)
        );
        
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
        
    } catch (Exception $e) {
        echo "❌ Backup failed: " . $e->getMessage() . "\n";
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
    
    $config = [
        'host' => DB_HOST ?? 'localhost',
        'username' => DB_USER ?? 'root',
        'password' => DB_PASS ?? '',
        'database' => DB_NAME ?? 'accounts'
    ];
    
    echo "⚠️  WARNING: This will overwrite the current database!\n";
    echo "Database: {$config['database']}\n";
    echo "Are you sure you want to continue? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    
    if (strtolower($confirmation) !== 'y') {
        echo "Operation cancelled.\n";
        exit(0);
    }
    
    echo "Restoring database from backup...\n";
    
    $command = sprintf(
        'mysql -h%s -u%s -p%s %s < %s',
        escapeshellarg($config['host']),
        escapeshellarg($config['username']),
        escapeshellarg($config['password']),
        escapeshellarg($config['database']),
        escapeshellarg($backupFile)
    );
    
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ Database restored successfully\n";
    } else {
        echo "❌ Restore failed\n";
        exit(1);
    }
}

function handleHealthCheck() {
    global $conn;
    
    echo "Checking database health...\n";
    
    try {
        $results = [];
        
        // Test basic connection
        $testQuery = $conn->query("SELECT 1 as test");
        if ($testQuery) {
            $results['connection'] = ['status' => 'healthy', 'message' => 'Connection successful'];
        } else {
            $results['connection'] = ['status' => 'unhealthy', 'message' => 'Connection failed'];
        }
        
        // Check table accessibility
        $tables = ['users', 'courses', 'attendance', 'holiday_programs'];
        foreach ($tables as $table) {
            $query = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            if ($query) {
                $row = $query->fetch_assoc();
                $results[$table] = [
                    'status' => 'healthy', 
                    'message' => "Table accessible ({$row['count']} records)"
                ];
            } else {
                $results[$table] = ['status' => 'unhealthy', 'message' => 'Table not accessible'];
            }
        }
        
        // Display results
        echo "\nDatabase Health Check Results:\n";
        echo str_repeat('-', 60) . "\n";
        
        foreach ($results as $component => $result) {
            $status = $result['status'] === 'healthy' ? '✅' : '❌';
            echo sprintf("%-20s %s %s\n", ucfirst($component), $status, $result['status']);
            echo sprintf("%-20s   %s\n", "", $result['message']);
        }
        
        // Connection statistics if Database class is available
        if (class_exists('Database') && method_exists('Database', 'getStats')) {
            $stats = Database::getStats();
            echo "\nConnection Statistics:\n";
            echo "Active connections: {$stats['active_connections']}/{$stats['max_connections']}\n";
            echo "Pooled connections: {$stats['pooled_connections']}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Health check failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleOptimize() {
    global $conn;
    
    echo "Optimizing database...\n";
    
    try {
        // Get all tables
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "Optimizing " . count($tables) . " tables...\n";
        
        foreach ($tables as $table) {
            echo "Optimizing table: {$table}... ";
            $conn->query("OPTIMIZE TABLE `{$table}`");
            echo "✅\n";
        }
        
        echo "✅ Database optimization completed\n";
        
    } catch (Exception $e) {
        echo "❌ Optimization failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleAnalyze() {
    global $conn;
    
    echo "Analyzing database...\n";
    
    try {
        // Database size
        $sizeQuery = $conn->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size_mb'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $sizeResult = $sizeQuery->fetch_assoc();
        
        // Table information
        $tablesQuery = $conn->query("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                ROUND((data_length / 1024 / 1024), 2) AS 'data_mb',
                ROUND((index_length / 1024 / 1024), 2) AS 'index_mb'
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
        
        echo "\nDatabase Analysis Results:\n";
        echo str_repeat('=', 80) . "\n";
        echo "Database Size: {$sizeResult['db_size_mb']} MB\n";
        echo str_repeat('=', 80) . "\n";
        
        echo sprintf("%-20s %-10s %-10s %-10s %-10s\n", 
            "Table", "Rows", "Size(MB)", "Data(MB)", "Index(MB)");
        echo str_repeat('-', 80) . "\n";
        
        while ($row = $tablesQuery->fetch_assoc()) {
            echo sprintf("%-20s %-10s %-10s %-10s %-10s\n",
                $row['table_name'],
                number_format($row['table_rows']),
                $row['size_mb'],
                $row['data_mb'],
                $row['index_mb']
            );
        }
        
    } catch (Exception $e) {
        echo "❌ Analysis failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleListTables() {
    global $conn;
    
    try {
        $result = $conn->query("SHOW TABLES");
        
        echo "Database Tables:\n";
        echo str_repeat('-', 30) . "\n";
        
        while ($row = $result->fetch_array()) {
            echo $row[0] . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Failed to list tables: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function handleDatabaseSize() {
    global $conn;
    
    try {
        $query = $conn->query("
            SELECT 
                table_schema AS 'database',
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            GROUP BY table_schema
        ");
        
        $result = $query->fetch_assoc();
        
        echo "Database Size Information:\n";
        echo str_repeat('-', 40) . "\n";
        echo "Database: {$result['database']}\n";
        echo "Size: {$result['size_mb']} MB\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to get database size: " . $e->getMessage() . "\n";
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
    echo "  seed                       Run all database seeders\n";
    echo "  seed:clear                 Clear all seeded data\n";
    echo "  seed:specific <name>       Run specific seeder\n\n";
    
    echo "Backup/Restore commands:\n";
    echo "  db:backup                  Create database backup\n";
    echo "  db:restore <file>          Restore from backup file\n\n";
    
    echo "Utility commands:\n";
    echo "  db:health                  Check database connection health\n";
    echo "  db:optimize                Optimize all database tables\n";
    echo "  db:analyze                 Analyze database size and structure\n";
    echo "  db:tables                  List all database tables\n";
    echo "  db:size                    Show database size information\n\n";
    
    echo "Examples:\n";
    echo "  php cli/database.php migrate\n";
    echo "  php cli/database.php migrate:create create_users_table\n";
    echo "  php cli/database.php migrate:rollback 2\n";
    echo "  php cli/database.php seed\n";
    echo "  php cli/database.php seed:specific users\n";
    echo "  php cli/database.php db:backup\n";
    echo "  php cli/database.php db:health\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>