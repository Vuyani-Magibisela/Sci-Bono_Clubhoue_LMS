<?php
/**
 * Phase 5 Database Layer Enhancement Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Migration.php';
require_once __DIR__ . '/../core/QueryBuilder.php';
require_once __DIR__ . '/../core/SchemaBuilder.php';
require_once __DIR__ . '/../database/Seeder.php';

class DatabaseLayerTest {
    private $conn;
    private $testResults = [];
    private $testCount = 0;
    private $passedTests = 0;
    
    public function __construct() {
        echo "Phase 5 Database Layer Enhancement Tests\n";
        echo str_repeat('=', 60) . "\n";
    }
    
    /**
     * Run all tests
     */
    public function run() {
        try {
            $this->setupConnection();
            
            // Test Database class
            $this->testDatabaseClass();
            
            // Test QueryBuilder
            $this->testQueryBuilder();
            
            // Test SchemaBuilder
            $this->testSchemaBuilder();
            
            // Test Migration system
            $this->testMigrationSystem();
            
            // Test Seeder
            $this->testSeeder();
            
            // Test CLI functionality (basic validation)
            $this->testCLIValidation();
            
            $this->printSummary();
            
        } catch (Exception $e) {
            echo "❌ Test setup failed: " . $e->getMessage() . "\n";
            return false;
        }
        
        return $this->passedTests === $this->testCount;
    }
    
    /**
     * Setup database connection
     */
    private function setupConnection() {
        echo "\n🔧 Setting up database connection...\n";
        
        try {
            // Try fallback connection first for testing
            require_once __DIR__ . '/../server.php';
            global $conn;
            
            if ($conn && $conn->ping()) {
                $this->conn = $conn;
                echo "✅ Connected using existing connection\n";
                return;
            }
            
            // Try new Database class as fallback
            if (class_exists('Database')) {
                try {
                    Database::initialize();
                    $this->conn = Database::connection();
                    echo "✅ Connected using new Database class\n";
                } catch (Exception $e) {
                    echo "ℹ️  New Database class connection failed (this is expected in some environments)\n";
                    // Continue with existing connection if available
                    if ($conn) {
                        $this->conn = $conn;
                        echo "✅ Using fallback connection\n";
                    } else {
                        throw $e;
                    }
                }
            }
            
            if (!$this->conn) {
                throw new Exception("Failed to establish database connection");
            }
            
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Test Database class functionality
     */
    private function testDatabaseClass() {
        echo "\n🧪 Testing Database Class...\n";
        
        // Test 1: Connection creation
        $this->test("Database connection creation", function() {
            return $this->conn && $this->conn->ping();
        });
        
        // Test 2: Connection statistics (if available)
        if (class_exists('Database') && method_exists('Database', 'getStats')) {
            $this->test("Database statistics", function() {
                $stats = Database::getStats();
                return isset($stats['active_connections']) && is_numeric($stats['active_connections']);
            });
        }
        
        // Test 3: Health check (if available)
        if (class_exists('Database') && method_exists('Database', 'healthCheck')) {
            $this->test("Database health check", function() {
                $health = Database::healthCheck();
                return is_array($health) && !empty($health);
            });
        }
    }
    
    /**
     * Test QueryBuilder functionality
     */
    private function testQueryBuilder() {
        echo "\n🧪 Testing QueryBuilder...\n";
        
        // Test 1: Basic QueryBuilder instantiation
        $this->test("QueryBuilder instantiation", function() {
            $qb = new QueryBuilder($this->conn, 'users');
            return $qb instanceof QueryBuilder;
        });
        
        // Test 2: SQL generation
        $this->test("SQL generation", function() {
            $qb = new QueryBuilder($this->conn, 'users');
            $sql = $qb->select(['id', 'name'])->where('active', 1)->limit(10)->toSQL();
            return strpos($sql, 'SELECT') !== false && 
                   strpos($sql, 'WHERE') !== false && 
                   strpos($sql, 'LIMIT') !== false;
        });
        
        // Test 3: Complex query building
        $this->test("Complex query building", function() {
            $qb = new QueryBuilder($this->conn, 'users');
            $sql = $qb->select(['u.id', 'u.name', 'c.title'])
                     ->join('courses c', 'c.instructor_id', '=', 'u.id')
                     ->where('u.active', 1)
                     ->orderBy('u.name')
                     ->limit(5)
                     ->toSQL();
            
            return strpos($sql, 'JOIN') !== false && 
                   strpos($sql, 'ORDER BY') !== false;
        });
        
        // Test 4: Check if users table exists for actual query test
        $tableExists = $this->conn->query("SHOW TABLES LIKE 'users'");
        if ($tableExists && $tableExists->num_rows > 0) {
            $this->test("Actual query execution", function() {
                try {
                    $qb = new QueryBuilder($this->conn, 'users');
                    $result = $qb->select(['id', 'username'])->limit(1)->get();
                    return is_array($result);
                } catch (Exception $e) {
                    return false; // Table might not exist or have different structure
                }
            });
        }
    }
    
    /**
     * Test SchemaBuilder functionality
     */
    private function testSchemaBuilder() {
        echo "\n🧪 Testing SchemaBuilder...\n";
        
        // Test 1: SchemaBuilder instantiation
        $this->test("SchemaBuilder instantiation", function() {
            $sb = new SchemaBuilder($this->conn);
            return $sb instanceof SchemaBuilder;
        });
        
        // Test 2: Create test table
        $testTableName = 'test_schema_' . time();
        $this->test("Table creation", function() use ($testTableName) {
            try {
                $sb = new SchemaBuilder($this->conn);
                $result = $sb->create($testTableName, function($table) {
                    $table->id();
                    $table->string('name', 100);
                    $table->integer('age')->nullable();
                    $table->boolean('active')->default(true);
                    $table->timestamps();
                });
                return $result;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test 3: Check table exists
        $this->test("Table existence check", function() use ($testTableName) {
            $sb = new SchemaBuilder($this->conn);
            return $sb->hasTable($testTableName);
        });
        
        // Test 4: Drop test table
        $this->test("Table deletion", function() use ($testTableName) {
            try {
                $sb = new SchemaBuilder($this->conn);
                return $sb->drop($testTableName);
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    /**
     * Test Migration system
     */
    private function testMigrationSystem() {
        echo "\n🧪 Testing Migration System...\n";
        
        // Test 1: Migration instantiation
        $this->test("Migration instantiation", function() {
            $migration = new Migration($this->conn);
            return $migration instanceof Migration;
        });
        
        // Test 2: Migration status (basic check)
        $this->test("Migration status check", function() {
            try {
                $migration = new Migration($this->conn);
                $status = $migration->status();
                return is_array($status);
            } catch (Exception $e) {
                return false; // migrations table might not exist yet
            }
        });
        
        // Test 3: Create test migration
        $this->test("Test migration creation", function() {
            try {
                $migration = new Migration($this->conn);
                $result = $migration->create('test_migration_' . time());
                return isset($result['message']) && $result['message'] === 'Migration file created successfully';
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    /**
     * Test Seeder functionality
     */
    private function testSeeder() {
        echo "\n🧪 Testing Seeder System...\n";
        
        // Test 1: Seeder instantiation
        $this->test("Seeder instantiation", function() {
            $seeder = new Seeder($this->conn);
            return $seeder instanceof Seeder;
        });
        
        // Test 2: Check if users table exists for seeding test
        $tableExists = $this->conn->query("SHOW TABLES LIKE 'users'");
        if ($tableExists && $tableExists->num_rows > 0) {
            // Test specific seeder (users)
            $this->test("Specific seeder execution", function() {
                try {
                    $seeder = new Seeder($this->conn);
                    $result = $seeder->seed('users');
                    return isset($result['success']);
                } catch (Exception $e) {
                    return false; // Table structure might be different
                }
            });
        }
    }
    
    /**
     * Test CLI functionality (basic validation)
     */
    private function testCLIValidation() {
        echo "\n🧪 Testing CLI Validation...\n";
        
        // Test 1: CLI file exists
        $this->test("CLI file exists", function() {
            return file_exists(__DIR__ . '/../cli/database.php');
        });
        
        // Test 2: CLI file is executable
        $this->test("CLI file is executable", function() {
            return is_executable(__DIR__ . '/../cli/database.php');
        });
        
        // Test 3: CLI help command
        $this->test("CLI help command", function() {
            $output = shell_exec('php ' . __DIR__ . '/../cli/database.php help 2>&1');
            return strpos($output, 'Database Management CLI') !== false;
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
        echo "📊 Test Summary\n";
        echo str_repeat('=', 60) . "\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->testCount - $this->passedTests) . "\n";
        echo "Success Rate: " . round(($this->passedTests / $this->testCount) * 100, 2) . "%\n";
        
        if ($this->passedTests === $this->testCount) {
            echo "\n🎉 All tests passed! Phase 5 Database Layer Enhancement is working correctly.\n";
        } else {
            echo "\n⚠️  Some tests failed. Review the implementation and database setup.\n";
        }
        
        echo "\n📝 Detailed Results:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($this->testResults as $result) {
            $status = $result['status'] === 'PASS' ? '✅' : '❌';
            echo "{$status} {$result['name']} ({$result['status']})\n";
            if (isset($result['error'])) {
                echo "    Error: {$result['error']}\n";
            }
        }
    }
}

// Run the tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new DatabaseLayerTest();
    $success = $tester->run();
    exit($success ? 0 : 1);
}