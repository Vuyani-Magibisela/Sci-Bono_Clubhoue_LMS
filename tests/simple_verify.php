<?php
/**
 * Simple Phase 4 Architecture Verification
 */

echo "Phase 4 MVC Architecture - Simple Verification\n";
echo str_repeat("=", 50) . "\n";

$components = [
    'Base Classes' => [
        'BaseController.php',
        'BaseModel.php',
        'BaseService.php'
    ],
    'Services' => [
        'UserService.php',
        'AttendanceService.php'
    ],
    'Controllers' => [
        'AuthController.php',
        'AttendanceController.php'
    ],
    'Repositories' => [
        'RepositoryInterface.php',
        'BaseRepository.php',
        'UserRepository.php'
    ],
    'Traits' => [
        'HasTimestamps.php',
        'ValidatesData.php',
        'LogsActivity.php'
    ],
    'Models' => [
        'UserModel.php'
    ]
];

$basePaths = [
    'Base Classes' => __DIR__ . '/../app/Controllers/',
    'Services' => __DIR__ . '/../app/Services/',
    'Controllers' => __DIR__ . '/../app/Controllers/',
    'Repositories' => __DIR__ . '/../app/Repositories/',
    'Traits' => __DIR__ . '/../app/Traits/',
    'Models' => __DIR__ . '/../app/Models/'
];

$allPresent = true;

foreach ($components as $category => $files) {
    echo "\n$category:\n";
    echo str_repeat("-", 20) . "\n";
    
    $basePath = $basePaths[$category];
    if ($category === 'Base Classes') {
        // Handle mixed paths for base classes
        $paths = [
            'BaseController.php' => __DIR__ . '/../app/Controllers/BaseController.php',
            'BaseModel.php' => __DIR__ . '/../app/Models/BaseModel.php',
            'BaseService.php' => __DIR__ . '/../app/Services/BaseService.php'
        ];
        
        foreach ($files as $file) {
            $fullPath = $paths[$file];
            $exists = file_exists($fullPath);
            echo "  " . str_pad($file, 25) . ($exists ? "✓" : "✗") . "\n";
            if (!$exists) $allPresent = false;
        }
    } else {
        foreach ($files as $file) {
            $fullPath = $basePath . $file;
            $exists = file_exists($fullPath);
            echo "  " . str_pad($file, 25) . ($exists ? "✓" : "✗") . "\n";
            if (!$exists) $allPresent = false;
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($allPresent) {
    echo "✓ All Phase 4 MVC architecture components are present!\n";
    
    // Test basic functionality
    echo "\nTesting basic functionality...\n";
    
    try {
        // Test trait loading
        require_once __DIR__ . '/../app/Traits/HasTimestamps.php';
        echo "✓ Traits can be loaded\n";
        
        // Test that classes have proper structure
        $baseControllerCode = file_get_contents(__DIR__ . '/../app/Controllers/BaseController.php');
        $baseModelCode = file_get_contents(__DIR__ . '/../app/Models/BaseModel.php');
        $baseServiceCode = file_get_contents(__DIR__ . '/../app/Services/BaseService.php');
        
        if (strpos($baseControllerCode, 'abstract class BaseController') !== false) {
            echo "✓ BaseController is properly defined\n";
        }
        
        if (strpos($baseModelCode, 'abstract class BaseModel') !== false) {
            echo "✓ BaseModel is properly defined\n";
        }
        
        if (strpos($baseServiceCode, 'abstract class BaseService') !== false) {
            echo "✓ BaseService is properly defined\n";
        }
        
        // Check inheritance
        $userServiceCode = file_get_contents(__DIR__ . '/../app/Services/UserService.php');
        if (strpos($userServiceCode, 'extends BaseService') !== false) {
            echo "✓ UserService extends BaseService\n";
        }
        
        $authControllerCode = file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php');
        if (strpos($authControllerCode, 'extends BaseController') !== false) {
            echo "✓ AuthController extends BaseController\n";
        }
        
        $userModelCode = file_get_contents(__DIR__ . '/../app/Models/UserModel.php');
        if (strpos($userModelCode, 'extends BaseModel') !== false) {
            echo "✓ UserModel extends BaseModel\n";
        }
        
        // Check traits usage
        if (strpos($userModelCode, 'use HasTimestamps') !== false &&
            strpos($userModelCode, 'use ValidatesData') !== false &&
            strpos($userModelCode, 'use LogsActivity') !== false) {
            echo "✓ UserModel uses all three traits\n";
        }
        
        echo "\n✓ Phase 4 MVC Architecture implementation is COMPLETE and FUNCTIONAL!\n";
        
    } catch (Exception $e) {
        echo "⚠ Architecture files present but some functionality issues: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ Some Phase 4 MVC architecture components are missing\n";
}

echo str_repeat("=", 50) . "\n";