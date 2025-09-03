<?php
/**
 * Legacy Route Handler - Maintain backward compatibility
 * Phase 3 Implementation
 */

class LegacyRouteHandler {
    private static $legacyRoutes = [
        'login.php' => '/login',
        'signup.php' => '/signup',
        'home.php' => '/dashboard',
        'profile.php' => '/profile',
        'courses.php' => '/courses',
        'attendance.php' => '/attendance',
        'core/Router.php' => '/attendance', // Current attendance entry
        
        // Admin routes
        'admin/dashboard.php' => '/admin',
        'admin/users.php' => '/admin/users',
        'admin/courses.php' => '/admin/courses',
        
        // Holiday program routes
        'holidayPrograms/index.php' => '/programs',
        'holidayPrograms/registration.php' => '/programs',
        'holidayPrograms/admin.php' => '/admin/programs',
    ];
    
    /**
     * Handle legacy route request
     */
    public static function handle($requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = ltrim($path, '/');
        
        // Remove base path if present
        $basePath = 'Sci-Bono_Clubhoue_LMS/';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        // Check if it's a legacy route
        if (isset(self::$legacyRoutes[$path])) {
            $newPath = self::$legacyRoutes[$path];
            
            // Preserve query parameters
            $query = parse_url($requestUri, PHP_URL_QUERY);
            if ($query) {
                $newPath .= '?' . $query;
            }
            
            // Permanent redirect
            header('Location: ' . $newPath, true, 301);
            exit;
        }
        
        return false; // Not a legacy route
    }
    
    /**
     * Add legacy route mapping
     */
    public static function addLegacyRoute($oldPath, $newPath) {
        self::$legacyRoutes[$oldPath] = $newPath;
    }
    
    /**
     * Check if file is a legacy entry point
     */
    public static function isLegacyEntryPoint($filename) {
        $legacyFiles = [
            'login.php', 'signup.php', 'home.php', 'profile.php',
            'courses.php', 'attendance.php', 'settings.php'
        ];
        
        return in_array($filename, $legacyFiles);
    }
}