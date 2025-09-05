<?php
/**
 * OPcache Preload Script for Sci-Bono LMS
 * 
 * This script preloads frequently used classes and functions
 * to improve performance in production environments
 */

// Only run preloading in production
if (getenv('APP_ENV') !== 'production') {
    return;
}

// Set error reporting for preload
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
$basePath = dirname(__DIR__);

try {
    // Preload core utility classes
    $coreFiles = [
        '/app/Utils/Logger.php',
        '/app/Utils/ResponseHelper.php',
        '/app/Services/PerformanceMonitor.php',
        '/app/Services/ApiTokenService.php',
        '/app/Middleware/PerformanceMiddleware.php',
        '/app/Middleware/ApiRateLimitMiddleware.php'
    ];

    foreach ($coreFiles as $file) {
        $fullPath = $basePath . $file;
        if (file_exists($fullPath)) {
            opcache_compile_file($fullPath);
            echo "Preloaded: $file\n";
        }
    }

    // Preload base controllers
    $controllerFiles = [
        '/app/API/BaseApiController.php',
        '/app/API/UserApiController.php',
        '/app/Controllers/PerformanceDashboardController.php'
    ];

    foreach ($controllerFiles as $file) {
        $fullPath = $basePath . $file;
        if (file_exists($fullPath)) {
            opcache_compile_file($fullPath);
            echo "Preloaded: $file\n";
        }
    }

    // Preload model classes
    $modelFiles = [
        '/app/Models/UserModel.php'
    ];

    foreach ($modelFiles as $file) {
        $fullPath = $basePath . $file;
        if (file_exists($fullPath)) {
            opcache_compile_file($fullPath);
            echo "Preloaded: $file\n";
        }
    }

    // Preload frequently used functions and classes from vendor
    if (file_exists($basePath . '/vendor/autoload.php')) {
        // Only preload if composer autoload exists
        require_once $basePath . '/vendor/autoload.php';
        
        // Preload commonly used Composer packages
        $vendorClasses = [
            // Add commonly used classes here
        ];

        foreach ($vendorClasses as $class) {
            if (class_exists($class)) {
                opcache_compile_file((new ReflectionClass($class))->getFileName());
                echo "Preloaded class: $class\n";
            }
        }
    }

    echo "OPcache preload completed successfully\n";

} catch (Throwable $e) {
    error_log("OPcache preload error: " . $e->getMessage());
    echo "OPcache preload failed: " . $e->getMessage() . "\n";
}

/**
 * Preload files by pattern
 */
function preloadByPattern($pattern, $basePath) {
    $files = glob($basePath . $pattern);
    $count = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            try {
                opcache_compile_file($file);
                $count++;
            } catch (Throwable $e) {
                error_log("Failed to preload $file: " . $e->getMessage());
            }
        }
    }
    
    return $count;
}

// Preload all PHP files in specific directories (optional)
// $preloadedCount = preloadByPattern('/app/Utils/*.php', $basePath);
// echo "Preloaded $preloadedCount utility files\n";

// Preload configuration files
$configFiles = [
    '/config/performance.php'
];

foreach ($configFiles as $file) {
    $fullPath = $basePath . $file;
    if (file_exists($fullPath)) {
        opcache_compile_file($fullPath);
        echo "Preloaded config: $file\n";
    }
}