<?php
/**
 * Tests for trait functionality
 */

require_once __DIR__ . '/TestFramework.php';
require_once __DIR__ . '/../app/Traits/HasTimestamps.php';
require_once __DIR__ . '/../app/Traits/ValidatesData.php';
require_once __DIR__ . '/../app/Traits/LogsActivity.php';

class TraitsTest {
    public function runTests() {
        $test = new TestFramework();
        
        $test->addTest('HasTimestamps Functionality', [$this, 'testHasTimestamps']);
        $test->addTest('ValidatesData Functionality', [$this, 'testValidatesData']);
        $test->addTest('LogsActivity Functionality', [$this, 'testLogsActivity']);
        
        $test->runTests();
    }
    
    public function testHasTimestamps() {
        $obj = new TimestampTestClass();
        
        // Test timestamp addition
        $data = ['name' => 'test'];
        $result = $obj->testAddCreateTimestamps($data);
        
        TestFramework::assertArrayHasKey('created_at', $result, 'Should add created_at timestamp');
        TestFramework::assertArrayHasKey('updated_at', $result, 'Should add updated_at timestamp');
        
        // Test time formatting
        $formatted = $obj->testFormatTimestamp('2023-01-01 12:00:00');
        TestFramework::assertNotNull($formatted, 'Should format timestamp');
        
        // Test time difference
        $diff = $obj->testGetTimeDifference('2023-01-01 12:00:00');
        TestFramework::assertTrue(is_string($diff), 'Should return time difference as string');
    }
    
    public function testValidatesData() {
        $obj = new ValidationTestClass();
        
        // Test validation with valid data
        $data = ['email' => 'test@example.com', 'name' => 'Test User'];
        $rules = ['email' => 'required|email', 'name' => 'required|min:3'];
        
        $result = $obj->validate($data, $rules);
        TestFramework::assertEquals(true, $result, 'Valid data should pass validation');
        
        // Test validation with invalid data
        $invalidData = ['email' => 'invalid-email', 'name' => ''];
        $invalidResult = $obj->validate($invalidData, $rules);
        TestFramework::assertEquals(false, $invalidResult, 'Invalid data should fail validation');
        
        $errors = $obj->getValidationErrors();
        TestFramework::assertTrue(is_array($errors), 'Should return array of errors');
        TestFramework::assertTrue(count($errors) > 0, 'Should have validation errors');
    }
    
    public function testLogsActivity() {
        $obj = new LoggingTestClass();
        
        // Test activity logging setup
        $obj->enableActivityLogging();
        TestFramework::assertTrue(true, 'Should enable activity logging without errors');
        
        $obj->disableActivityLogging();
        TestFramework::assertTrue(true, 'Should disable activity logging without errors');
        
        // Test log methods exist and don't throw errors
        $obj->logActivity('test_activity', ['data' => 'test']);
        $obj->logSuccess('test_success');
        $obj->logFailure('test_failure', [], 'Test error');
        
        TestFramework::assertTrue(true, 'Should log activities without errors');
    }
}

// Test classes for trait testing
class TimestampTestClass {
    use HasTimestamps;
    
    public function testAddCreateTimestamps($data) {
        return $this->addCreateTimestamps($data);
    }
    
    public function testFormatTimestamp($timestamp) {
        return $this->formatTimestamp($timestamp);
    }
    
    public function testGetTimeDifference($timestamp) {
        return $this->getTimeDifference($timestamp);
    }
}

class ValidationTestClass {
    use ValidatesData;
    
    // Public wrapper methods for testing protected methods
    public function getValidationErrors() {
        return $this->validationErrors;
    }
}

class LoggingTestClass {
    use LogsActivity;
    
    // LogsActivity trait methods are already public
}