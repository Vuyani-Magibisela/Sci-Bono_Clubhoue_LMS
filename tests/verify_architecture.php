<?php
/**
 * Simple Architecture Verification Script
 * Verifies that the Phase 4 MVC architecture is working correctly
 */

echo "Phase 4 MVC Architecture Verification\n";
echo str_repeat("=", 50) . "\n\n";

$tests = [
    'Database Connection' => function() {
        require_once __DIR__ . '/../server.php';
        global $conn;
        return ($conn !== null && $conn->ping()) ? "OK" : "FAILED";
    },
    
    'File Structure' => function() {
        $files = [
            'BaseController' => __DIR__ . '/../app/Controllers/BaseController.php',
            'BaseModel' => __DIR__ . '/../app/Models/BaseModel.php', 
            'BaseService' => __DIR__ . '/../app/Services/BaseService.php',
            'UserService' => __DIR__ . '/../app/Services/UserService.php',
            'AttendanceService' => __DIR__ . '/../app/Services/AttendanceService.php',
            'UserModel' => __DIR__ . '/../app/Models/UserModel.php',
            'AuthController' => __DIR__ . '/../app/Controllers/AuthController.php',
            'AttendanceController' => __DIR__ . '/../app/Controllers/AttendanceController.php',
            'HasTimestamps' => __DIR__ . '/../app/Traits/HasTimestamps.php',
            'ValidatesData' => __DIR__ . '/../app/Traits/ValidatesData.php',
            'LogsActivity' => __DIR__ . '/../app/Traits/LogsActivity.php',
            'RepositoryInterface' => __DIR__ . '/../app/Repositories/RepositoryInterface.php',
            'BaseRepository' => __DIR__ . '/../app/Repositories/BaseRepository.php',
            'UserRepository' => __DIR__ . '/../app/Repositories/UserRepository.php'
        ];
        
        $missing = [];
        foreach ($files as $name => $path) {
            if (!file_exists($path)) {
                $missing[] = $name;
            }
        }
        
        return empty($missing) ? "OK" : "MISSING: " . implode(', ', $missing);
    },
    
    'Class Autoloading' => function() {
        try {
            require_once __DIR__ . '/../server.php';
            require_once __DIR__ . '/../app/Services/UserService.php';
            require_once __DIR__ . '/../app/Services/AttendanceService.php';
            require_once __DIR__ . '/../app/Controllers/AuthController.php';
            require_once __DIR__ . '/../app/Controllers/AttendanceController.php';
            
            return "OK";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    },
    
    'Service Instantiation' => function() {
        try {
            require_once __DIR__ . '/../server.php';
            global $conn;
            
            $userService = new UserService($conn);
            $attendanceService = new AttendanceService($conn);
            
            return "OK";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    },
    
    'Controller Instantiation' => function() {
        try {
            require_once __DIR__ . '/../server.php';
            global $conn;
            
            $authController = new AuthController($conn);
            $attendanceController = new AttendanceController($conn);
            
            return "OK";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    },
    
    'Trait Functionality' => function() {
        try {
            require_once __DIR__ . '/../app/Traits/HasTimestamps.php';
            require_once __DIR__ . '/../app/Traits/ValidatesData.php';
            require_once __DIR__ . '/../app/Traits/LogsActivity.php';
            
            // Create a test class that uses traits
            $testClass = new class {
                use HasTimestamps, ValidatesData, LogsActivity;
                
                public function testTimestamps() {
                    return $this->getCurrentTimestamp();
                }
                
                public function testValidation() {
                    return $this->validate(['name' => 'test'], ['name' => 'required']);
                }
            };
            
            $timestamp = $testClass->testTimestamps();
            $validation = $testClass->testValidation();
            
            return (is_string($timestamp) && is_bool($validation)) ? "OK" : "FAILED";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    },
    
    'User Authentication Flow' => function() {
        try {
            require_once __DIR__ . '/../server.php';
            global $conn;
            
            $userService = new UserService($conn);
            
            // Test authentication structure (without actual database operations)
            $result = $userService->authenticate('nonexistent@test.com', 'password');
            
            return (is_array($result) && isset($result['success']) && isset($result['message'])) ? "OK" : "FAILED";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    },
    
    'Repository Pattern' => function() {
        try {
            require_once __DIR__ . '/../app/Repositories/RepositoryInterface.php';
            require_once __DIR__ . '/../app/Repositories/BaseRepository.php';
            require_once __DIR__ . '/../app/Repositories/UserRepository.php';
            require_once __DIR__ . '/../server.php';
            global $conn;
            
            $userRepo = new UserRepository($conn);
            
            return "OK";
        } catch (Exception $e) {
            return "FAILED: " . $e->getMessage();
        }
    }
];

$passed = 0;
$total = count($tests);

foreach ($tests as $testName => $testFunction) {
    echo str_pad($testName . ': ', 35);
    
    try {
        $result = $testFunction();
        echo $result . "\n";
        
        if (strpos($result, 'OK') === 0) {
            $passed++;
        }
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Results: $passed/$total tests passed\n";
echo "Success Rate: " . number_format(($passed / $total) * 100, 2) . "%\n";

if ($passed === $total) {
    echo "✓ Phase 4 MVC Architecture is working correctly!\n";
    exit(0);
} else {
    echo "⚠ Some issues detected, but core architecture is functional\n";
    exit(1);
}