<?php
/**
 * Test Runner for Phase 4 MVC Architecture
 * Run this file to execute all tests
 */

// Include required files
require_once __DIR__ . '/../server.php'; // Database connection
require_once __DIR__ . '/BaseModelTest.php';
require_once __DIR__ . '/UserServiceTest.php';
require_once __DIR__ . '/TraitsTest.php';

echo "Phase 4 MVC Architecture Test Suite\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed. Tests cannot proceed.");
    }
    
    echo "Database connection: OK\n";
    echo "Running tests...\n\n";
    
    // Run BaseModel tests
    echo "1. BaseModel Tests\n";
    echo str_repeat("-", 30) . "\n";
    $baseModelTests = new BaseModelTest($conn);
    $baseModelTests->runTests();
    echo "\n";
    
    // Run UserService tests
    echo "2. UserService Tests\n";
    echo str_repeat("-", 30) . "\n";
    $userServiceTests = new UserServiceTest($conn);
    $userServiceTests->runTests();
    echo "\n";
    
    // Run Traits tests
    echo "3. Traits Tests\n";
    echo str_repeat("-", 30) . "\n";
    $traitsTests = new TraitsTest();
    $traitsTests->runTests();
    echo "\n";
    
    // Architecture verification tests
    echo "4. Architecture Verification\n";
    echo str_repeat("-", 30) . "\n";
    runArchitectureTests();
    echo "\n";
    
    echo "All test suites completed!\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Run architecture verification tests
 */
function runArchitectureTests() {
    $test = new TestFramework();
    
    $test->addTest('File Structure Verification', function() {
        // Check that all required files exist
        $requiredFiles = [
            __DIR__ . '/../app/Controllers/BaseController.php',
            __DIR__ . '/../app/Models/BaseModel.php',
            __DIR__ . '/../app/Services/BaseService.php',
            __DIR__ . '/../app/Services/UserService.php',
            __DIR__ . '/../app/Services/AttendanceService.php',
            __DIR__ . '/../app/Repositories/RepositoryInterface.php',
            __DIR__ . '/../app/Repositories/BaseRepository.php',
            __DIR__ . '/../app/Repositories/UserRepository.php',
            __DIR__ . '/../app/Traits/HasTimestamps.php',
            __DIR__ . '/../app/Traits/ValidatesData.php',
            __DIR__ . '/../app/Traits/LogsActivity.php',
            __DIR__ . '/../app/Controllers/AuthController.php',
            __DIR__ . '/../app/Controllers/AttendanceController.php',
            __DIR__ . '/../app/Models/UserModel.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                throw new Exception("Required file missing: " . basename($file));
            }
        }
        
        return true;
    });
    
    $test->addTest('Class Loading Verification', function() {
        // Test that classes can be loaded without errors
        $classes = [
            'BaseController',
            'BaseModel', 
            'BaseService',
            'UserService',
            'AttendanceService',
            'UserModel',
            'AuthController',
            'AttendanceController'
        ];
        
        foreach ($classes as $className) {
            if (!class_exists($className)) {
                throw new Exception("Class not loaded: $className");
            }
        }
        
        return true;
    });
    
    $test->addTest('Interface Compliance', function() {
        // Test that repositories implement the interface
        if (!interface_exists('RepositoryInterface')) {
            throw new Exception("RepositoryInterface not found");
        }
        
        if (!class_exists('BaseRepository')) {
            throw new Exception("BaseRepository not found");
        }
        
        $reflection = new ReflectionClass('BaseRepository');
        if (!$reflection->implementsInterface('RepositoryInterface')) {
            throw new Exception("BaseRepository does not implement RepositoryInterface");
        }
        
        return true;
    });
    
    $test->addTest('Service Architecture', function() {
        global $conn;
        
        // Test service instantiation
        $userService = new UserService($conn);
        $attendanceService = new AttendanceService($conn);
        
        if (!($userService instanceof BaseService)) {
            throw new Exception("UserService does not extend BaseService");
        }
        
        if (!($attendanceService instanceof BaseService)) {
            throw new Exception("AttendanceService does not extend BaseService");
        }
        
        return true;
    });
    
    $test->addTest('Controller Architecture', function() {
        global $conn;
        
        // Test controller instantiation
        $authController = new AuthController($conn);
        $attendanceController = new AttendanceController($conn);
        
        if (!($authController instanceof BaseController)) {
            throw new Exception("AuthController does not extend BaseController");
        }
        
        if (!($attendanceController instanceof BaseController)) {
            throw new Exception("AttendanceController does not extend BaseController");
        }
        
        return true;
    });
    
    $test->runTests();
}

echo "Test execution completed.\n";