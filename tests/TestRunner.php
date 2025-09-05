<?php

namespace Tests;

require_once __DIR__ . '/../app/Utils/Logger.php';
require_once __DIR__ . '/BaseTestCase.php';

use App\Utils\Logger;
use Exception;

class TestRunner
{
    private $testClasses = [];
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $skippedTests = 0;
    private $incompleteTests = 0;
    private $startTime;
    private $verbose = false;
    private $stopOnFailure = false;
    private $filter = null;
    
    public function __construct($options = [])
    {
        $this->verbose = $options['verbose'] ?? false;
        $this->stopOnFailure = $options['stop_on_failure'] ?? false;
        $this->filter = $options['filter'] ?? null;
        
        $this->discoverTestClasses();
    }
    
    /**
     * Discover test classes
     */
    private function discoverTestClasses()
    {
        $testFiles = $this->getTestFiles(__DIR__);
        
        foreach ($testFiles as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className && $this->isValidTestClass($className)) {
                $this->testClasses[] = $className;
            }
        }
        
        if ($this->verbose) {
            echo "Discovered " . count($this->testClasses) . " test classes\n";
        }
    }
    
    /**
     * Get test files recursively
     */
    private function getTestFiles($directory)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                strpos($file->getFilename(), 'Test.php') !== false &&
                $file->getFilename() !== 'TestRunner.php' &&
                $file->getFilename() !== 'BaseTestCase.php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Extract class name from file
     */
    private function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? 'Tests';
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? '';
        
        if (!$className) {
            return null;
        }
        
        $fullClassName = $namespace . '\\' . $className;
        
        // Try to include the file
        try {
            require_once $file;
        } catch (Exception $e) {
            if ($this->verbose) {
                echo "Warning: Could not load test file {$file}: " . $e->getMessage() . "\n";
            }
            return null;
        }
        
        return $fullClassName;
    }
    
    /**
     * Check if class is a valid test class
     */
    private function isValidTestClass($className)
    {
        try {
            if (!class_exists($className)) {
                return false;
            }
            
            $reflection = new \ReflectionClass($className);
            
            // Must extend BaseTestCase
            if (!$reflection->isSubclassOf('Tests\BaseTestCase')) {
                return false;
            }
            
            // Must have at least one test method
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            if ($this->verbose) {
                echo "Warning: Error checking test class {$className}: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }
    
    /**
     * Run all tests
     */
    public function run()
    {
        $this->startTime = microtime(true);
        
        echo $this->getHeader();
        
        foreach ($this->testClasses as $className) {
            if ($this->filter && strpos($className, $this->filter) === false) {
                continue;
            }
            
            $this->runTestClass($className);
            
            if ($this->stopOnFailure && $this->failedTests > 0) {
                echo "\nStopping on first failure as requested.\n";
                break;
            }
        }
        
        $duration = microtime(true) - $this->startTime;
        $this->printSummary($duration);
        
        return $this->failedTests === 0;
    }
    
    /**
     * Get test runner header
     */
    private function getHeader()
    {
        return "
╔══════════════════════════════════════════════════════════════════════════════╗
║                         Sci-Bono LMS Test Suite                             ║
║                          Phase 7 - API Testing                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

Starting test execution...\n\n";
    }
    
    /**
     * Run tests in a class
     */
    private function runTestClass($className)
    {
        if ($this->verbose) {
            echo "Running {$className}:\n";
            echo str_repeat("-", 80) . "\n";
        }
        
        try {
            $reflection = new \ReflectionClass($className);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            // Run setUpBeforeClass if exists
            if ($reflection->hasMethod('setUpBeforeClass')) {
                $reflection->getMethod('setUpBeforeClass')->invoke(null);
            }
            
            $classPassed = 0;
            $classFailed = 0;
            $classSkipped = 0;
            $classIncomplete = 0;
            
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    $result = $this->runTestMethod($className, $method->getName());
                    
                    switch ($result['status']) {
                        case 'passed':
                            $classPassed++;
                            break;
                        case 'failed':
                            $classFailed++;
                            if ($this->stopOnFailure) {
                                break 2;
                            }
                            break;
                        case 'skipped':
                            $classSkipped++;
                            break;
                        case 'incomplete':
                            $classIncomplete++;
                            break;
                    }
                }
            }
            
            // Run tearDownAfterClass if exists
            if ($reflection->hasMethod('tearDownAfterClass')) {
                $reflection->getMethod('tearDownAfterClass')->invoke(null);
            }
            
            if ($this->verbose) {
                echo "Class summary: {$classPassed} passed, {$classFailed} failed, {$classSkipped} skipped, {$classIncomplete} incomplete\n\n";
            }
            
        } catch (Exception $e) {
            echo "Error running test class {$className}: " . $e->getMessage() . "\n\n";
            $this->failedTests++;
        }
    }
    
    /**
     * Run individual test method
     */
    private function runTestMethod($className, $methodName)
    {
        $this->totalTests++;
        $testStart = microtime(true);
        
        try {
            $instance = new $className();
            
            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }
            
            $instance->$methodName();
            
            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown();
            }
            
            $duration = microtime(true) - $testStart;
            $this->passedTests++;
            
            if ($this->verbose) {
                echo "  ✓ {$methodName} (" . number_format($duration * 1000, 2) . " ms)\n";
            } else {
                echo ".";
            }
            
            return ['status' => 'passed', 'duration' => $duration];
            
        } catch (SkipTestException $e) {
            $duration = microtime(true) - $testStart;
            $this->skippedTests++;
            
            if ($this->verbose) {
                echo "  ⊖ {$methodName} (SKIPPED: " . $e->getMessage() . ")\n";
            } else {
                echo "S";
            }
            
            return ['status' => 'skipped', 'duration' => $duration, 'message' => $e->getMessage()];
            
        } catch (IncompleteTestException $e) {
            $duration = microtime(true) - $testStart;
            $this->incompleteTests++;
            
            if ($this->verbose) {
                echo "  ⊗ {$methodName} (INCOMPLETE: " . $e->getMessage() . ")\n";
            } else {
                echo "I";
            }
            
            return ['status' => 'incomplete', 'duration' => $duration, 'message' => $e->getMessage()];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $testStart;
            $this->failedTests++;
            
            $failure = [
                'class' => $className,
                'method' => $methodName,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'duration' => $duration
            ];
            
            $this->results[] = $failure;
            
            if ($this->verbose) {
                echo "  ✗ {$methodName} (FAILED)\n";
                echo "    Error: " . $e->getMessage() . "\n";
                echo "    Location: " . $e->getFile() . ":" . $e->getLine() . "\n";
            } else {
                echo "F";
            }
            
            // Log test failure
            Logger::error('Test failed', $failure);
            
            return ['status' => 'failed', 'duration' => $duration, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Print test summary
     */
    private function printSummary($duration)
    {
        if (!$this->verbose) {
            echo "\n";
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST EXECUTION SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        
        echo sprintf("Tests run: %d\n", $this->totalTests);
        echo sprintf("✓ Passed: %d\n", $this->passedTests);
        echo sprintf("✗ Failed: %d\n", $this->failedTests);
        echo sprintf("⊖ Skipped: %d\n", $this->skippedTests);
        echo sprintf("⊗ Incomplete: %d\n", $this->incompleteTests);
        echo sprintf("Duration: %.2f seconds\n", $duration);
        echo sprintf("Memory usage: %s\n", $this->formatBytes(memory_get_peak_usage()));
        
        if ($this->totalTests > 0) {
            $successRate = ($this->passedTests / $this->totalTests) * 100;
            echo sprintf("Success rate: %.1f%%\n", $successRate);
        }
        
        // Print failures
        if ($this->failedTests > 0) {
            echo "\n" . str_repeat("-", 80) . "\n";
            echo "FAILURES:\n";
            echo str_repeat("-", 80) . "\n";
            
            foreach ($this->results as $i => $failure) {
                echo sprintf("\n%d) %s::%s\n", $i + 1, $failure['class'], $failure['method']);
                echo "   Error: " . $failure['error'] . "\n";
                echo "   Location: " . $failure['file'] . ":" . $failure['line'] . "\n";
                
                if ($this->verbose && !empty($failure['trace'])) {
                    echo "   Stack trace:\n";
                    $traceLines = explode("\n", $failure['trace']);
                    foreach (array_slice($traceLines, 0, 5) as $line) {
                        echo "   " . $line . "\n";
                    }
                    if (count($traceLines) > 5) {
                        echo "   ... (truncated)\n";
                    }
                }
            }
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        
        $status = $this->failedTests === 0 ? 'PASSED' : 'FAILED';
        $statusColor = $this->failedTests === 0 ? "\033[32m" : "\033[31m";
        $resetColor = "\033[0m";
        
        echo "Overall Status: {$statusColor}{$status}{$resetColor}\n";
        
        if ($this->failedTests === 0) {
            echo "🎉 All tests passed! Great work!\n";
        } else {
            echo "❌ Some tests failed. Please review and fix the issues.\n";
        }
    }
    
    /**
     * Format bytes for display
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get test results
     */
    public function getResults()
    {
        return [
            'total' => $this->totalTests,
            'passed' => $this->passedTests,
            'failed' => $this->failedTests,
            'skipped' => $this->skippedTests,
            'incomplete' => $this->incompleteTests,
            'failures' => $this->results,
            'success_rate' => $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0
        ];
    }
    
    /**
     * Run specific test class
     */
    public function runClass($className)
    {
        if (!in_array($className, $this->testClasses)) {
            echo "Error: Test class '{$className}' not found.\n";
            return false;
        }
        
        $this->testClasses = [$className];
        return $this->run();
    }
    
    /**
     * Run specific test method
     */
    public function runMethod($className, $methodName)
    {
        try {
            echo "Running {$className}::{$methodName}...\n\n";
            
            $reflection = new \ReflectionClass($className);
            
            // Run setUpBeforeClass if exists
            if ($reflection->hasMethod('setUpBeforeClass')) {
                $reflection->getMethod('setUpBeforeClass')->invoke(null);
            }
            
            $result = $this->runTestMethod($className, $methodName);
            
            // Run tearDownAfterClass if exists
            if ($reflection->hasMethod('tearDownAfterClass')) {
                $reflection->getMethod('tearDownAfterClass')->invoke(null);
            }
            
            echo "\nTest completed: " . $result['status'] . "\n";
            return $result['status'] === 'passed';
            
        } catch (Exception $e) {
            echo "Error running test: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $options = [];
    
    // Parse command line arguments
    $args = array_slice($argv, 1);
    foreach ($args as $arg) {
        switch ($arg) {
            case '--verbose':
            case '-v':
                $options['verbose'] = true;
                break;
            case '--stop-on-failure':
            case '-s':
                $options['stop_on_failure'] = true;
                break;
            default:
                if (strpos($arg, '--filter=') === 0) {
                    $options['filter'] = substr($arg, 9);
                }
                break;
        }
    }
    
    $runner = new TestRunner($options);
    $success = $runner->run();
    exit($success ? 0 : 1);
}