<?php
/**
 * Simple Testing Framework for Phase 4 MVC Architecture
 */

class TestFramework {
    private $tests = [];
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    /**
     * Add a test to the framework
     */
    public function addTest($testName, $testFunction) {
        $this->tests[$testName] = $testFunction;
    }
    
    /**
     * Run all tests
     */
    public function runTests() {
        echo "Running Tests...\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($this->tests as $testName => $testFunction) {
            $this->runTest($testName, $testFunction);
        }
        
        $this->printSummary();
    }
    
    /**
     * Run a single test
     */
    private function runTest($testName, $testFunction) {
        $this->totalTests++;
        
        try {
            echo "Running: $testName ... ";
            
            $result = call_user_func($testFunction);
            
            if ($result === true || $result === null) {
                echo "PASS\n";
                $this->passedTests++;
                $this->results[$testName] = ['status' => 'PASS', 'message' => null];
            } else {
                echo "FAIL - $result\n";
                $this->failedTests++;
                $this->results[$testName] = ['status' => 'FAIL', 'message' => $result];
            }
            
        } catch (Exception $e) {
            echo "FAIL - " . $e->getMessage() . "\n";
            $this->failedTests++;
            $this->results[$testName] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Print test summary
     */
    private function printSummary() {
        echo str_repeat("=", 50) . "\n";
        echo "Test Summary:\n";
        echo "Total Tests: " . $this->totalTests . "\n";
        echo "Passed: " . $this->passedTests . "\n";
        echo "Failed: " . $this->failedTests . "\n";
        echo "Success Rate: " . number_format(($this->passedTests / $this->totalTests) * 100, 2) . "%\n";
        
        if ($this->failedTests > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->results as $testName => $result) {
                if ($result['status'] === 'FAIL') {
                    echo "- $testName: " . $result['message'] . "\n";
                }
            }
        }
        
        echo str_repeat("=", 50) . "\n";
    }
    
    /**
     * Assert that a condition is true
     */
    public static function assertTrue($condition, $message = "Assertion failed") {
        if (!$condition) {
            throw new Exception($message);
        }
    }
    
    /**
     * Assert that two values are equal
     */
    public static function assertEquals($expected, $actual, $message = null) {
        if ($expected !== $actual) {
            $message = $message ?? "Expected '$expected' but got '$actual'";
            throw new Exception($message);
        }
    }
    
    /**
     * Assert that a value is not null
     */
    public static function assertNotNull($value, $message = "Value should not be null") {
        if ($value === null) {
            throw new Exception($message);
        }
    }
    
    /**
     * Assert that an array has a key
     */
    public static function assertArrayHasKey($key, $array, $message = null) {
        if (!array_key_exists($key, $array)) {
            $message = $message ?? "Array does not have key '$key'";
            throw new Exception($message);
        }
    }
    
    /**
     * Assert that an exception is thrown
     */
    public static function assertException($callback, $expectedExceptionClass = null, $message = null) {
        try {
            call_user_func($callback);
            throw new Exception($message ?? "Expected exception was not thrown");
        } catch (Exception $e) {
            if ($expectedExceptionClass && !($e instanceof $expectedExceptionClass)) {
                throw new Exception("Expected $expectedExceptionClass but got " . get_class($e));
            }
        }
    }
}