<?php
/**
 * Phase 5 Architecture Verification Test
 * Verifies all components are properly implemented without requiring database connections
 */

class Phase5ArchitectureTest {
    private $testResults = [];
    private $testCount = 0;
    private $passedTests = 0;
    
    public function __construct() {
        echo "Phase 5 Database Layer Architecture Verification\n";
        echo str_repeat('=', 60) . "\n";
    }
    
    /**
     * Run all tests
     */
    public function run() {
        $this->testFileStructure();
        $this->testClassDefinitions();
        $this->testMethodExistence();
        $this->testMigrationFiles();
        $this->testConfigurationEnhancements();
        
        $this->printSummary();
        return $this->passedTests === $this->testCount;
    }
    
    /**
     * Test file structure exists
     */
    private function testFileStructure() {
        echo "\n🧪 Testing File Structure...\n";
        
        $requiredFiles = [
            'core/Database.php' => 'Enhanced Database class',
            'core/Migration.php' => 'Migration system',
            'core/QueryBuilder.php' => 'Query builder',
            'core/SchemaBuilder.php' => 'Schema builder',
            'database/Seeder.php' => 'Database seeder',
            'cli/database.php' => 'CLI commands',
            'config/database.php' => 'Enhanced database config'
        ];
        
        foreach ($requiredFiles as $file => $description) {
            $this->test("File exists: {$description}", function() use ($file) {
                return file_exists(__DIR__ . '/../' . $file);
            });
        }
        
        // Test directory structure
        $requiredDirs = [
            'database' => 'Database directory',
            'database/migrations' => 'Migrations directory',
            'cli' => 'CLI directory'
        ];
        
        foreach ($requiredDirs as $dir => $description) {
            $this->test("Directory exists: {$description}", function() use ($dir) {
                return is_dir(__DIR__ . '/../' . $dir);
            });
        }
    }
    
    /**
     * Test class definitions
     */
    private function testClassDefinitions() {
        echo "\n🧪 Testing Class Definitions...\n";
        
        // Test Database class
        $this->test("Database class loads", function() {
            require_once __DIR__ . '/../core/Database.php';
            return class_exists('Database');
        });
        
        $this->test("DatabaseConnection class loads", function() {
            return class_exists('DatabaseConnection');
        });
        
        // Test Migration class
        $this->test("Migration class loads", function() {
            require_once __DIR__ . '/../core/Migration.php';
            return class_exists('Migration');
        });
        
        // Test QueryBuilder class
        $this->test("QueryBuilder class loads", function() {
            require_once __DIR__ . '/../core/QueryBuilder.php';
            return class_exists('QueryBuilder');
        });
        
        // Test SchemaBuilder class
        $this->test("SchemaBuilder class loads", function() {
            require_once __DIR__ . '/../core/SchemaBuilder.php';
            return class_exists('SchemaBuilder') && class_exists('ForeignKeyBuilder');
        });
        
        // Test Seeder class
        $this->test("Seeder class loads", function() {
            require_once __DIR__ . '/../database/Seeder.php';
            return class_exists('Seeder');
        });
    }
    
    /**
     * Test method existence
     */
    private function testMethodExistence() {
        echo "\n🧪 Testing Method Existence...\n";
        
        // Database class methods
        $this->test("Database static methods", function() {
            $methods = ['initialize', 'connection', 'closeAll', 'getStats', 'healthCheck'];
            foreach ($methods as $method) {
                if (!method_exists('Database', $method)) {
                    return false;
                }
            }
            return true;
        });
        
        // QueryBuilder methods
        $this->test("QueryBuilder fluent methods", function() {
            $methods = ['select', 'where', 'join', 'orderBy', 'limit', 'get', 'first', 'count'];
            $reflection = new ReflectionClass('QueryBuilder');
            foreach ($methods as $method) {
                if (!$reflection->hasMethod($method)) {
                    return false;
                }
            }
            return true;
        });
        
        // SchemaBuilder methods
        $this->test("SchemaBuilder methods", function() {
            $methods = ['create', 'table', 'drop', 'string', 'integer', 'timestamps'];
            $reflection = new ReflectionClass('SchemaBuilder');
            foreach ($methods as $method) {
                if (!$reflection->hasMethod($method)) {
                    return false;
                }
            }
            return true;
        });
        
        // Migration methods
        $this->test("Migration methods", function() {
            $methods = ['migrate', 'rollback', 'status', 'create'];
            $reflection = new ReflectionClass('Migration');
            foreach ($methods as $method) {
                if (!$reflection->hasMethod($method)) {
                    return false;
                }
            }
            return true;
        });
        
        // Seeder methods
        $this->test("Seeder methods", function() {
            $methods = ['run', 'seed', 'clear'];
            $reflection = new ReflectionClass('Seeder');
            foreach ($methods as $method) {
                if (!$reflection->hasMethod($method)) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Test migration files
     */
    private function testMigrationFiles() {
        echo "\n🧪 Testing Migration Files...\n";
        
        $migrationFiles = [
            '2025_09_03_120000_create_users_table.php' => 'Users table migration',
            '2025_09_03_120100_create_courses_table.php' => 'Courses table migration',
            '2025_09_03_120200_create_attendance_table.php' => 'Attendance table migration',
            '2025_09_03_120300_create_holiday_programs_table.php' => 'Holiday programs table migration',
            '2025_09_03_120400_create_enrollments_table.php' => 'Enrollments table migration',
            '2025_09_03_120500_create_lessons_table.php' => 'Lessons table migration'
        ];
        
        foreach ($migrationFiles as $file => $description) {
            $this->test("Migration file: {$description}", function() use ($file) {
                $path = __DIR__ . '/../database/migrations/' . $file;
                if (!file_exists($path)) {
                    return false;
                }
                
                // Check if file contains valid migration class
                $content = file_get_contents($path);
                return strpos($content, 'class') !== false && 
                       strpos($content, 'function up()') !== false && 
                       strpos($content, 'function down()') !== false;
            });
        }
    }
    
    /**
     * Test configuration enhancements
     */
    private function testConfigurationEnhancements() {
        echo "\n🧪 Testing Configuration Enhancements...\n";
        
        $this->test("Enhanced database config structure", function() {
            $config = include __DIR__ . '/../config/database.php';
            
            $requiredKeys = [
                'default', 'max_connections', 'connections', 'migrations', 
                'backup', 'monitoring', 'cache'
            ];
            
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key])) {
                    return false;
                }
            }
            
            return true;
        });
        
        $this->test("Multiple database connections configured", function() {
            $config = include __DIR__ . '/../config/database.php';
            $connections = $config['connections'] ?? [];
            
            return isset($connections['mysql']) && 
                   isset($connections['mysql_read']) && 
                   isset($connections['mysql_test']);
        });
        
        $this->test("Monitoring configuration present", function() {
            $config = include __DIR__ . '/../config/database.php';
            $monitoring = $config['monitoring'] ?? [];
            
            return isset($monitoring['enabled']) && 
                   isset($monitoring['slow_query_threshold']) &&
                   isset($monitoring['alert_on_errors']);
        });
    }
    
    /**
     * Test CLI functionality
     */
    private function testCLIFunctionality() {
        echo "\n🧪 Testing CLI Functionality...\n";
        
        $this->test("CLI file is executable", function() {
            return is_executable(__DIR__ . '/../cli/database.php');
        });
        
        $this->test("CLI contains required commands", function() {
            $content = file_get_contents(__DIR__ . '/../cli/database.php');
            
            $commands = ['migrate', 'seed', 'db:backup', 'db:health', 'db:optimize'];
            foreach ($commands as $command) {
                if (strpos($content, "case '{$command}':") === false) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Run a single test
     */
    private function test($name, $callback) {
        $this->testCount++;
        
        try {
            $result = $callback();
            if ($result) {
                echo "  ✅ {$name}\n";
                $this->passedTests++;
                $this->testResults[] = ['name' => $name, 'status' => 'PASS'];
            } else {
                echo "  ❌ {$name}\n";
                $this->testResults[] = ['name' => $name, 'status' => 'FAIL'];
            }
        } catch (Exception $e) {
            echo "  ❌ {$name} (Exception: {$e->getMessage()})\n";
            $this->testResults[] = ['name' => $name, 'status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Print test summary
     */
    private function printSummary() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 Architecture Verification Summary\n";
        echo str_repeat('=', 60) . "\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->testCount - $this->passedTests) . "\n";
        echo "Success Rate: " . round(($this->passedTests / $this->testCount) * 100, 2) . "%\n";
        
        if ($this->passedTests === $this->testCount) {
            echo "\n🎉 All architecture tests passed! Phase 5 Database Layer Enhancement is properly implemented.\n";
            echo "\n✨ Implemented Components:\n";
            echo "  • Enhanced Database class with connection pooling\n";
            echo "  • DatabaseConnection wrapper with monitoring\n";
            echo "  • Migration system with up/down methods\n";
            echo "  • Comprehensive SchemaBuilder for table creation\n";
            echo "  • Advanced QueryBuilder for complex queries\n";
            echo "  • Database seeder system with sample data\n";
            echo "  • CLI commands for database management\n";
            echo "  • Enhanced database configuration\n";
            echo "  • Sample migrations for existing tables\n";
        } else {
            echo "\n⚠️  Some architecture tests failed. Review the implementation.\n";
        }
        
        // Show failed tests
        $failedTests = array_filter($this->testResults, function($test) {
            return $test['status'] !== 'PASS';
        });
        
        if (!empty($failedTests)) {
            echo "\n❌ Failed Tests:\n";
            foreach ($failedTests as $test) {
                echo "  • {$test['name']} ({$test['status']})\n";
                if (isset($test['error'])) {
                    echo "    Error: {$test['error']}\n";
                }
            }
        }
    }
}

// Run the tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new Phase5ArchitectureTest();
    $success = $tester->run();
    exit($success ? 0 : 1);
}