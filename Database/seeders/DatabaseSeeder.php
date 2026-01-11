<?php
/**
 * Database Seeder (Master)
 * Phase 4 Week 2 Day 3
 *
 * Runs all seeders in the correct order
 */

require_once __DIR__ . '/../../server.php';
require_once __DIR__ . '/RequirementsSeeder.php';
require_once __DIR__ . '/CriteriaSeeder.php';
require_once __DIR__ . '/FAQSeeder.php';

class DatabaseSeeder {
    private $conn;
    private $totalCreated = 0;
    private $totalSkipped = 0;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Run all seeders
     */
    public function run() {
        echo "==========================================\n";
        echo "Database Seeder - Starting\n";
        echo "==========================================\n\n";

        $startTime = microtime(true);

        // Run seeders in order
        $this->runSeeder('RequirementsSeeder', new RequirementsSeeder($this->conn));
        $this->runSeeder('CriteriaSeeder', new CriteriaSeeder($this->conn));
        $this->runSeeder('FAQSeeder', new FAQSeeder($this->conn));

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        echo "==========================================\n";
        echo "Database Seeder - Complete\n";
        echo "==========================================\n";
        echo "Total Created: {$this->totalCreated}\n";
        echo "Total Skipped: {$this->totalSkipped}\n";
        echo "Duration: {$duration}s\n";
        echo "==========================================\n\n";
    }

    /**
     * Run a single seeder
     */
    private function runSeeder($name, $seeder) {
        echo "Running {$name}...\n";
        echo "-------------------------------------------\n";

        try {
            $created = $seeder->run();
            $this->totalCreated += $created;
            echo "✓ {$name} completed successfully\n\n";
        } catch (Exception $e) {
            echo "✗ {$name} failed: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Truncate all tables (use with caution!)
     */
    public function truncateAll() {
        echo "Truncating all tables...\n";

        $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->conn->query("TRUNCATE TABLE faqs");
        $this->conn->query("TRUNCATE TABLE evaluation_criteria");
        $this->conn->query("TRUNCATE TABLE program_requirements");
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");

        echo "✓ All tables truncated\n\n";
    }

    /**
     * Fresh seed (truncate then seed)
     */
    public function fresh() {
        echo "==========================================\n";
        echo "Fresh Database Seed\n";
        echo "==========================================\n\n";

        $this->truncateAll();
        $this->run();
    }
}

// Run seeder if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $seeder = new DatabaseSeeder($conn);

        // Check command line arguments
        $args = $_SERVER['argv'] ?? [];

        if (in_array('--fresh', $args) || in_array('-f', $args)) {
            // Fresh seed (truncate then seed)
            $seeder->fresh();
        } else {
            // Normal seed (append to existing data)
            $seeder->run();
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}
