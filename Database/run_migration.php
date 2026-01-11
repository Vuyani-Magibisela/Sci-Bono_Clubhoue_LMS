<?php
/**
 * Migration Runner Script
 *
 * Runs a specific migration file or all pending migrations.
 *
 * Usage:
 *   php database/run_migration.php                           # Run all migrations
 *   php database/run_migration.php 2026_01_06_120000        # Run specific migration
 *   php database/run_migration.php --rollback                # Rollback last migration
 *
 * @package Database
 * @since Phase 5 Week 1 Day 1
 */

// Load database connection
require_once __DIR__ . '/../server.php';

class MigrationRunner {
    private $conn;
    private $migrationsPath;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->migrationsPath = __DIR__ . '/migrations/';
    }

    /**
     * Run all pending migrations
     */
    public function runAll() {
        $migrations = $this->getMigrationFiles();

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Migration Runner - Run All Migrations\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        if (empty($migrations)) {
            echo "⚠️  No migration files found in {$this->migrationsPath}\n\n";
            return;
        }

        echo "Found " . count($migrations) . " migration file(s)\n\n";

        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "  Migration completed successfully!\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Run a specific migration
     */
    public function runSpecific($pattern) {
        $migrations = $this->getMigrationFiles();
        $found = false;

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Migration Runner - Run Specific Migration\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        foreach ($migrations as $migration) {
            if (strpos($migration, $pattern) !== false) {
                $this->runMigration($migration);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo "❌ Migration matching '{$pattern}' not found\n\n";
            exit(1);
        }

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "  Migration completed successfully!\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Rollback a specific migration
     */
    public function rollback($pattern) {
        $migrations = $this->getMigrationFiles();
        $found = false;

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Migration Runner - Rollback Migration\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        foreach ($migrations as $migration) {
            if (strpos($migration, $pattern) !== false) {
                $this->rollbackMigration($migration);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo "❌ Migration matching '{$pattern}' not found\n\n";
            exit(1);
        }

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "  Rollback completed successfully!\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Get all migration files
     */
    private function getMigrationFiles() {
        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Run a single migration file
     */
    private function runMigration($filename) {
        echo "Running migration: {$filename}\n";

        try {
            require_once $this->migrationsPath . $filename;

            // Extract class name from filename
            $className = $this->getClassNameFromFile($filename);

            if (!class_exists($className)) {
                throw new Exception("Class {$className} not found in {$filename}");
            }

            $migration = new $className($this->conn);
            $migration->up();

            echo "  ✅ Migration completed\n\n";

        } catch (Exception $e) {
            echo "  ❌ Migration failed: " . $e->getMessage() . "\n\n";
            exit(1);
        }
    }

    /**
     * Rollback a single migration file
     */
    private function rollbackMigration($filename) {
        echo "Rolling back migration: {$filename}\n";

        try {
            require_once $this->migrationsPath . $filename;

            // Extract class name from filename
            $className = $this->getClassNameFromFile($filename);

            if (!class_exists($className)) {
                throw new Exception("Class {$className} not found in {$filename}");
            }

            $migration = new $className($this->conn);
            $migration->down();

            echo "  ✅ Rollback completed\n\n";

        } catch (Exception $e) {
            echo "  ❌ Rollback failed: " . $e->getMessage() . "\n\n";
            exit(1);
        }
    }

    /**
     * Extract class name from migration filename
     */
    private function getClassNameFromFile($filename) {
        // Example: 2026_01_06_120000_create_token_blacklist_table.php
        // Extract: CreateTokenBlacklistTable

        $parts = explode('_', str_replace('.php', '', $filename));

        // Skip date/time parts (first 4 parts: YYYY, MM, DD, HHMMSS)
        $nameParts = array_slice($parts, 4);

        // Convert to PascalCase
        $className = implode('', array_map('ucfirst', $nameParts));

        return $className;
    }
}

// Parse command line arguments
$runner = new MigrationRunner($conn);

if ($argc === 1) {
    // No arguments - run all migrations
    $runner->runAll();
} elseif ($argv[1] === '--rollback' && isset($argv[2])) {
    // Rollback specific migration
    $runner->rollback($argv[2]);
} elseif ($argv[1] === '--rollback') {
    echo "❌ Error: Please specify a migration pattern for rollback\n";
    echo "Usage: php database/run_migration.php --rollback 2026_01_06_120000\n\n";
    exit(1);
} else {
    // Run specific migration
    $runner->runSpecific($argv[1]);
}

// Close database connection
mysqli_close($conn);
