# Phase 3: Modern Routing System Implementation Guide
## URL Routing, Middleware Support & Clean URLs

**Duration**: Weeks 3-4  
**Priority**: MEDIUM  
**Dependencies**: Phase 1 (Configuration & Error Handling), Phase 2 (Security)  
**Team Size**: 1-2 developers  

---

## Overview

Phase 3 modernizes the application's routing system, moving from direct file access to centralized URL routing. This phase implements clean URLs, middleware support, and RESTful conventions while maintaining backward compatibility during transition.

### Key Objectives
- ✅ Implement modern routing system with middleware support
- ✅ Create clean, SEO-friendly URLs
- ✅ Establish RESTful URL conventions
- ✅ Implement route caching for performance
- ✅ Add named routes for easy URL generation
- ✅ Maintain backward compatibility during transition

---

## Pre-Implementation Checklist

- [ ] **Phase 1 & 2 Complete**: Configuration and security systems are working
- [ ] **Apache/Nginx Setup**: Verify web server supports URL rewriting
- [ ] **Current URLs Documented**: List all existing entry points and URLs
- [ ] **Backup System**: Create backup before routing modifications
- [ ] **Test Environment**: Set up for testing new routing system

---

## Task 1: Enhanced Router Implementation

### 1.1 Create Advanced Router Class
**File**: `core/Router.php` (Enhanced version)

```php
<?php
/**
 * Advanced Router with Middleware Support
 * Phase 3 Implementation
 */

class Router {
    private $routes = [];
    private $middleware = [];
    private $globalMiddleware = [];
    private $routeGroups = [];
    private $namedRoutes = [];
    private $currentRoute;
    private $routeParams = [];
    private $basePath = '';
    private $logger;
    
    public function __construct($basePath = '') {
        $this->basePath = rtrim($basePath, '/');
        $this->currentRoute = $this->getCurrentRoute();
        $this->logger = new Logger();
    }
    
    /**
     * Set base path for the application
     */
    public function setBasePath($basePath) {
        $this->basePath = rtrim($basePath, '/');
        return $this;
    }
    
    /**
     * Get current route from request URI
     */
    private function getCurrentRoute() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Remove base path if running in subdirectory
        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        return $uri ?: '/';
    }
    
    /**
     * Register GET route
     */
    public function get($path, $handler, $name = null) {
        return $this->addRoute('GET', $path, $handler, $name);
    }
    
    /**
     * Register POST route
     */
    public function post($path, $handler, $name = null) {
        return $this->addRoute('POST', $path, $handler, $name);
    }
    
    /**
     * Register PUT route
     */
    public function put($path, $handler, $name = null) {
        return $this->addRoute('PUT', $path, $handler, $name);
    }
    
    /**
     * Register DELETE route
     */
    public function delete($path, $handler, $name = null) {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    /**
     * Register PATCH route
     */
    public function patch($path, $handler, $name = null) {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }
    
    /**
     * Register route for multiple HTTP methods
     */
    public function match($methods, $path, $handler, $name = null) {
        foreach ((array) $methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $name);
        }
        return $this;
    }
    
    /**
     * Register route for any HTTP method
     */
    public function any($path, $handler, $name = null) {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        return $this->match($methods, $path, $handler, $name);
    }
    
    /**
     * Create route group with shared attributes
     */
    public function group($attributes, $callback) {
        $previousGroupStack = $this->routeGroups;
        
        $this->routeGroups[] = $this->mergeGroup($attributes);
        
        call_user_func($callback, $this);
        
        $this->routeGroups = $previousGroupStack;
        
        return $this;
    }
    
    /**
     * Add middleware to routes
     */
    public function middleware($middleware) {
        if (!empty($this->routes)) {
            // Apply to last added route
            $lastRouteKey = array_key_last($this->routes);
            $this->routes[$lastRouteKey]['middleware'][] = $middleware;
        } else {
            // Global middleware
            $this->globalMiddleware[] = $middleware;
        }
        
        return $this;
    }
    
    /**
     * Add route to routing table
     */
    private function addRoute($method, $path, $handler, $name = null) {
        // Apply group attributes
        $groupAttributes = $this->getGroupAttributes();
        
        $path = $this->applyGroupPrefix($path, $groupAttributes);
        $middleware = $this->mergeMiddleware($groupAttributes);
        
        $routeKey = $method . ':' . $path;
        
        $route = [
            'method' => $method,
            'path' => $path,
            'original_path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'name' => $name,
            'compiled' => $this->compileRoute($path)
        ];
        
        $this->routes[$routeKey] = $route;
        
        // Store named route
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }
    
    /**
     * Compile route pattern to regex
     */
    private function compileRoute($path) {
        // Convert {param} to capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        
        // Convert {param?} to optional capture groups
        $pattern = preg_replace('/\{([^}]+)\?\}/', '([^/]*)', $pattern);
        
        // Escape forward slashes for regex
        $pattern = str_replace('/', '\/', $pattern);
        
        // Add start and end anchors
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Extract parameter names from route path
     */
    private function extractParameterNames($path) {
        preg_match_all('/\{([^}]+)\??\}/', $path, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * Dispatch current request
     */
    public function dispatch() {
        $method = $this->getRequestMethod();
        $path = $this->currentRoute;
        
        // Find matching route
        $matchedRoute = $this->findRoute($method, $path);
        
        if (!$matchedRoute) {
            return $this->handleNotFound();
        }
        
        // Execute middlewares
        if (!$this->executeMiddlewares($matchedRoute)) {
            return false;
        }
        
        // Execute route handler
        return $this->executeHandler($matchedRoute);
    }
    
    /**
     * Get actual request method (handle method override)
     */
    private function getRequestMethod() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Handle method override for forms
        if ($method === 'POST') {
            if (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }
        
        return $method;
    }
    
    /**
     * Find matching route
     */
    private function findRoute($method, $path) {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['compiled'], $path, $matches)) {
                // Extract parameters
                $paramNames = $this->extractParameterNames($route['original_path']);
                $paramValues = array_slice($matches, 1);
                
                $this->routeParams = array_combine($paramNames, $paramValues);
                
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Execute route middlewares
     */
    private function executeMiddlewares($route) {
        // Execute global middleware first
        foreach ($this->globalMiddleware as $middleware) {
            if (!$this->executeMiddleware($middleware)) {
                return false;
            }
        }
        
        // Execute route-specific middleware
        foreach ($route['middleware'] as $middleware) {
            if (!$this->executeMiddleware($middleware)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Execute single middleware
     */
    private function executeMiddleware($middleware) {
        if (is_string($middleware)) {
            // Class-based middleware
            if (class_exists($middleware)) {
                $instance = new $middleware();
                return $instance->handle();
            }
            
            // File-based middleware
            $middlewareFile = __DIR__ . "/../app/Middleware/{$middleware}.php";
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                $className = basename($middleware);
                if (class_exists($className)) {
                    $instance = new $className();
                    return $instance->handle();
                }
            }
            
            throw new Exception("Middleware not found: {$middleware}");
        }
        
        if (is_callable($middleware)) {
            return call_user_func($middleware);
        }
        
        throw new Exception("Invalid middleware type");
    }
    
    /**
     * Execute route handler
     */
    private function executeHandler($route) {
        $handler = $route['handler'];
        $params = array_values($this->routeParams);
        
        if (is_string($handler)) {
            return $this->executeStringHandler($handler, $params);
        }
        
        if (is_array($handler)) {
            return $this->executeArrayHandler($handler, $params);
        }
        
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        throw new Exception("Invalid route handler");
    }
    
    /**
     * Execute string-based handler
     */
    private function executeStringHandler($handler, $params) {
        if (strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler, 2);
            return $this->executeControllerMethod($controller, $method, $params);
        }
        
        // Assume it's a controller class with default method
        return $this->executeControllerMethod($handler, 'index', $params);
    }
    
    /**
     * Execute array-based handler
     */
    private function executeArrayHandler($handler, $params) {
        [$controller, $method] = $handler;
        return $this->executeControllerMethod($controller, $method, $params);
    }
    
    /**
     * Execute controller method
     */
    private function executeControllerMethod($controllerName, $methodName, $params) {
        // Try different controller paths
        $controllerPaths = [
            __DIR__ . "/../app/Controllers/{$controllerName}.php",
            __DIR__ . "/../app/Controllers/Web/{$controllerName}.php",
            __DIR__ . "/../app/Controllers/Api/{$controllerName}.php",
        ];
        
        $controllerFile = null;
        foreach ($controllerPaths as $path) {
            if (file_exists($path)) {
                $controllerFile = $path;
                break;
            }
        }
        
        if (!$controllerFile) {
            throw new Exception("Controller not found: {$controllerName}");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            throw new Exception("Controller class not found: {$controllerName}");
        }
        
        // Instantiate controller with dependencies
        global $conn;
        $config = ConfigLoader::load();
        $controller = new $controllerName($conn, $config);
        
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method not found: {$controllerName}::{$methodName}");
        }
        
        return call_user_func_array([$controller, $methodName], $params);
    }
    
    /**
     * Handle 404 - Not Found
     */
    private function handleNotFound() {
        $this->logger->info('Route not found', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'path' => $this->currentRoute,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        http_response_code(404);
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Route not found']);
            return false;
        }
        
        $errorFile = __DIR__ . '/../app/Views/errors/404.php';
        if (file_exists($errorFile)) {
            require_once $errorFile;
        } else {
            echo '404 - Not Found';
        }
        
        return false;
    }
    
    /**
     * Generate URL for named route
     */
    public function url($name, $params = []) {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Named route not found: {$name}");
        }
        
        $route = $this->namedRoutes[$name];
        $url = $route['original_path'];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
            $url = str_replace('{' . $key . '?}', $value, $url);
        }
        
        // Remove unused optional parameters
        $url = preg_replace('/\{[^}]+\?\}/', '', $url);
        
        // Clean up extra slashes
        $url = preg_replace('/\/+/', '/', $url);
        
        return $this->basePath . $url;
    }
    
    /**
     * Get current route parameter
     */
    public function param($name, $default = null) {
        return $this->routeParams[$name] ?? $default;
    }
    
    /**
     * Get all route parameters
     */
    public function params() {
        return $this->routeParams;
    }
    
    /**
     * Check if current request is AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    /**
     * Helper methods for route groups
     */
    private function getGroupAttributes() {
        if (empty($this->routeGroups)) {
            return [];
        }
        
        return end($this->routeGroups);
    }
    
    private function mergeGroup($attributes) {
        $parent = $this->getGroupAttributes();
        
        return [
            'prefix' => $this->mergePrefix($parent['prefix'] ?? '', $attributes['prefix'] ?? ''),
            'middleware' => array_merge($parent['middleware'] ?? [], $attributes['middleware'] ?? []),
            'namespace' => $this->mergeNamespace($parent['namespace'] ?? '', $attributes['namespace'] ?? ''),
        ];
    }
    
    private function mergePrefix($parent, $current) {
        return trim($parent . '/' . $current, '/');
    }
    
    private function mergeNamespace($parent, $current) {
        return trim($parent . '\\' . $current, '\\');
    }
    
    private function applyGroupPrefix($path, $attributes) {
        $prefix = $attributes['prefix'] ?? '';
        if ($prefix) {
            $path = '/' . trim($prefix, '/') . '/' . ltrim($path, '/');
            $path = rtrim($path, '/') ?: '/';
        }
        return $path;
    }
    
    private function mergeMiddleware($attributes) {
        return $attributes['middleware'] ?? [];
    }
    
    /**
     * Cache routes for performance
     */
    public function cache($cacheFile) {
        $routeData = [
            'routes' => $this->routes,
            'namedRoutes' => $this->namedRoutes
        ];
        
        file_put_contents($cacheFile, '<?php return ' . var_export($routeData, true) . ';');
        return $this;
    }
    
    /**
     * Load routes from cache
     */
    public function loadCache($cacheFile) {
        if (file_exists($cacheFile)) {
            $routeData = require $cacheFile;
            $this->routes = $routeData['routes'] ?? [];
            $this->namedRoutes = $routeData['namedRoutes'] ?? [];
        }
        return $this;
    }
}
```

---

## Task 2: Route Definitions

### 2.1 Create Web Routes
**File**: `routes/web.php`

```php
<?php
/**
 * Web Routes - Define all web-based routes
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../core/Router.php';

$router = new Router('/Sci-Bono_Clubhoue_LMS');

// ====== PUBLIC ROUTES ======

// Home/Landing page
$router->get('/', 'HomeController@index', 'home');

// Authentication routes
$router->get('/login', 'AuthController@showLogin', 'login.show');
$router->post('/login', 'AuthController@login', 'login.process');
$router->get('/signup', 'AuthController@showSignup', 'signup.show');
$router->post('/signup', 'AuthController@signup', 'signup.process');
$router->post('/logout', 'AuthController@logout', 'logout');

// Password reset routes
$router->get('/forgot-password', 'AuthController@showForgotPassword', 'password.forgot');
$router->post('/forgot-password', 'AuthController@sendResetLink', 'password.reset.send');
$router->get('/reset-password/{token}', 'AuthController@showResetForm', 'password.reset.form');
$router->post('/reset-password', 'AuthController@resetPassword', 'password.reset.process');

// Attendance register (public access)
$router->get('/attendance', 'AttendanceController@index', 'attendance.index');

// ====== AUTHENTICATED ROUTES ======

$router->group(['middleware' => ['AuthMiddleware']], function($router) {
    
    // Dashboard
    $router->get('/dashboard', 'DashboardController@index', 'dashboard');
    
    // Profile routes
    $router->get('/profile', 'UserController@profile', 'profile.show');
    $router->post('/profile', 'UserController@updateProfile', 'profile.update');
    $router->get('/profile/edit', 'UserController@editProfile', 'profile.edit');
    
    // Course routes
    $router->get('/courses', 'CourseController@index', 'courses.index');
    $router->get('/courses/{id}', 'CourseController@show', 'courses.show');
    $router->post('/courses/{id}/enroll', 'CourseController@enroll', 'courses.enroll');
    
    // Lesson routes
    $router->get('/lessons/{id}', 'LessonController@show', 'lessons.show');
    $router->post('/lessons/{id}/complete', 'LessonController@markComplete', 'lessons.complete');
    
    // Holiday Programs
    $router->group(['prefix' => 'programs'], function($router) {
        $router->get('/', 'HolidayProgramController@index', 'programs.index');
        $router->get('/{id}', 'HolidayProgramController@show', 'programs.show');
        $router->post('/{id}/register', 'HolidayProgramController@register', 'programs.register');
        $router->get('/{id}/workshops', 'HolidayProgramController@workshops', 'programs.workshops');
    });
    
    // Settings
    $router->get('/settings', 'SettingsController@index', 'settings.index');
    $router->post('/settings', 'SettingsController@update', 'settings.update');
    
    // File uploads
    $router->post('/upload', 'FileController@upload', 'files.upload');
    $router->delete('/files/{id}', 'FileController@delete', 'files.delete');
});

// ====== MENTOR ROUTES ======

$router->group(['prefix' => 'mentor', 'middleware' => ['AuthMiddleware', 'RoleMiddleware:mentor,admin']], function($router) {
    
    // Mentor dashboard
    $router->get('/', 'Mentor\\MentorController@dashboard', 'mentor.dashboard');
    
    // Attendance management
    $router->get('/attendance', 'Mentor\\AttendanceController@index', 'mentor.attendance.index');
    $router->get('/attendance/register', 'Mentor\\AttendanceController@register', 'mentor.attendance.register');
    $router->post('/attendance/bulk-signout', 'Mentor\\AttendanceController@bulkSignout', 'mentor.attendance.bulk_signout');
    
    // Member management
    $router->get('/members', 'Mentor\\MemberController@index', 'mentor.members.index');
    $router->get('/members/{id}', 'Mentor\\MemberController@show', 'mentor.members.show');
    $router->get('/members/{id}/progress', 'Mentor\\MemberController@progress', 'mentor.members.progress');
    
    // Reports
    $router->get('/reports', 'Mentor\\ReportController@index', 'mentor.reports.index');
    $router->get('/reports/attendance', 'Mentor\\ReportController@attendance', 'mentor.reports.attendance');
    $router->get('/reports/programs', 'Mentor\\ReportController@programs', 'mentor.reports.programs');
});

// ====== ADMIN ROUTES ======

$router->group(['prefix' => 'admin', 'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']], function($router) {
    
    // Admin dashboard
    $router->get('/', 'Admin\\AdminController@dashboard', 'admin.dashboard');
    
    // User management
    $router->group(['prefix' => 'users'], function($router) {
        $router->get('/', 'Admin\\UserController@index', 'admin.users.index');
        $router->get('/create', 'Admin\\UserController@create', 'admin.users.create');
        $router->post('/', 'Admin\\UserController@store', 'admin.users.store');
        $router->get('/{id}', 'Admin\\UserController@show', 'admin.users.show');
        $router->get('/{id}/edit', 'Admin\\UserController@edit', 'admin.users.edit');
        $router->put('/{id}', 'Admin\\UserController@update', 'admin.users.update');
        $router->delete('/{id}', 'Admin\\UserController@destroy', 'admin.users.destroy');
    });
    
    // Course management
    $router->group(['prefix' => 'courses'], function($router) {
        $router->get('/', 'Admin\\CourseController@index', 'admin.courses.index');
        $router->get('/create', 'Admin\\CourseController@create', 'admin.courses.create');
        $router->post('/', 'Admin\\CourseController@store', 'admin.courses.store');
        $router->get('/{id}', 'Admin\\CourseController@show', 'admin.courses.show');
        $router->get('/{id}/edit', 'Admin\\CourseController@edit', 'admin.courses.edit');
        $router->put('/{id}', 'Admin\\CourseController@update', 'admin.courses.update');
        $router->delete('/{id}', 'Admin\\CourseController@destroy', 'admin.courses.destroy');
        
        // Lesson management within courses
        $router->get('/{courseId}/lessons', 'Admin\\LessonController@index', 'admin.courses.lessons.index');
        $router->get('/{courseId}/lessons/create', 'Admin\\LessonController@create', 'admin.courses.lessons.create');
        $router->post('/{courseId}/lessons', 'Admin\\LessonController@store', 'admin.courses.lessons.store');
        $router->get('/{courseId}/lessons/{id}/edit', 'Admin\\LessonController@edit', 'admin.courses.lessons.edit');
        $router->put('/{courseId}/lessons/{id}', 'Admin\\LessonController@update', 'admin.courses.lessons.update');
        $router->delete('/{courseId}/lessons/{id}', 'Admin\\LessonController@destroy', 'admin.courses.lessons.destroy');
    });
    
    // Holiday program management
    $router->group(['prefix' => 'programs'], function($router) {
        $router->get('/', 'Admin\\ProgramController@index', 'admin.programs.index');
        $router->get('/create', 'Admin\\ProgramController@create', 'admin.programs.create');
        $router->post('/', 'Admin\\ProgramController@store', 'admin.programs.store');
        $router->get('/{id}', 'Admin\\ProgramController@show', 'admin.programs.show');
        $router->get('/{id}/edit', 'Admin\\ProgramController@edit', 'admin.programs.edit');
        $router->put('/{id}', 'Admin\\ProgramController@update', 'admin.programs.update');
        $router->delete('/{id}', 'Admin\\ProgramController@destroy', 'admin.programs.destroy');
        
        // Registration management
        $router->get('/{id}/registrations', 'Admin\\ProgramController@registrations', 'admin.programs.registrations');
        $router->post('/{id}/registrations/export', 'Admin\\ProgramController@exportRegistrations', 'admin.programs.registrations.export');
    });
    
    // System settings
    $router->get('/settings', 'Admin\\SettingsController@index', 'admin.settings.index');
    $router->post('/settings', 'Admin\\SettingsController@update', 'admin.settings.update');
    
    // System logs
    $router->get('/logs', 'Admin\\LogController@index', 'admin.logs.index');
    $router->get('/logs/{date}', 'Admin\\LogController@show', 'admin.logs.show');
    
    // Analytics & Reports
    $router->group(['prefix' => 'analytics'], function($router) {
        $router->get('/', 'Admin\\AnalyticsController@index', 'admin.analytics.index');
        $router->get('/users', 'Admin\\AnalyticsController@users', 'admin.analytics.users');
        $router->get('/courses', 'Admin\\AnalyticsController@courses', 'admin.analytics.courses');
        $router->get('/programs', 'Admin\\AnalyticsController@programs', 'admin.analytics.programs');
        $router->get('/attendance', 'Admin\\AnalyticsController@attendance', 'admin.analytics.attendance');
    });
});

return $router;
```

### 2.2 Create API Routes
**File**: `routes/api.php`

```php
<?php
/**
 * API Routes - Define all API endpoints
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../core/Router.php';

$router = new Router('/Sci-Bono_Clubhoue_LMS');

// ====== PUBLIC API ROUTES ======

$router->group(['prefix' => 'api/v1', 'middleware' => ['ApiMiddleware']], function($router) {
    
    // Health check
    $router->get('/health', 'Api\\HealthController@check', 'api.health');
    
    // Authentication
    $router->post('/auth/login', 'Api\\AuthController@login', 'api.auth.login');
    $router->post('/auth/logout', 'Api\\AuthController@logout', 'api.auth.logout');
    $router->post('/auth/refresh', 'Api\\AuthController@refresh', 'api.auth.refresh');
    
    // Password reset
    $router->post('/auth/forgot-password', 'Api\\AuthController@forgotPassword', 'api.auth.forgot');
    $router->post('/auth/reset-password', 'Api\\AuthController@resetPassword', 'api.auth.reset');
    
    // Public attendance (with authentication)
    $router->post('/attendance/signin', 'Api\\AttendanceController@signin', 'api.attendance.signin');
    $router->post('/attendance/signout', 'Api\\AttendanceController@signout', 'api.attendance.signout');
    $router->get('/attendance/search', 'Api\\AttendanceController@searchUsers', 'api.attendance.search');
    $router->get('/attendance/stats', 'Api\\AttendanceController@stats', 'api.attendance.stats');
});

// ====== AUTHENTICATED API ROUTES ======

$router->group(['prefix' => 'api/v1', 'middleware' => ['ApiMiddleware', 'AuthMiddleware']], function($router) {
    
    // User profile
    $router->get('/profile', 'Api\\UserController@profile', 'api.profile.show');
    $router->put('/profile', 'Api\\UserController@updateProfile', 'api.profile.update');
    
    // Courses
    $router->get('/courses', 'Api\\CourseController@index', 'api.courses.index');
    $router->get('/courses/{id}', 'Api\\CourseController@show', 'api.courses.show');
    $router->post('/courses/{id}/enroll', 'Api\\CourseController@enroll', 'api.courses.enroll');
    $router->get('/courses/{id}/progress', 'Api\\CourseController@progress', 'api.courses.progress');
    
    // Lessons
    $router->get('/lessons/{id}', 'Api\\LessonController@show', 'api.lessons.show');
    $router->post('/lessons/{id}/complete', 'Api\\LessonController@markComplete', 'api.lessons.complete');
    
    // Holiday programs
    $router->get('/programs', 'Api\\ProgramController@index', 'api.programs.index');
    $router->get('/programs/{id}', 'Api\\ProgramController@show', 'api.programs.show');
    $router->post('/programs/{id}/register', 'Api\\ProgramController@register', 'api.programs.register');
    $router->get('/programs/{id}/workshops', 'Api\\ProgramController@workshops', 'api.programs.workshops');
    
    // File uploads
    $router->post('/files/upload', 'Api\\FileController@upload', 'api.files.upload');
    $router->delete('/files/{id}', 'Api\\FileController@delete', 'api.files.delete');
    
    // Dashboard data
    $router->get('/dashboard/stats', 'Api\\DashboardController@stats', 'api.dashboard.stats');
    $router->get('/dashboard/activities', 'Api\\DashboardController@activities', 'api.dashboard.activities');
});

// ====== MENTOR API ROUTES ======

$router->group(['prefix' => 'api/v1/mentor', 'middleware' => ['ApiMiddleware', 'AuthMiddleware', 'RoleMiddleware:mentor,admin']], function($router) {
    
    // Member management
    $router->get('/members', 'Api\\Mentor\\MemberController@index', 'api.mentor.members.index');
    $router->get('/members/{id}', 'Api\\Mentor\\MemberController@show', 'api.mentor.members.show');
    $router->get('/members/{id}/progress', 'Api\\Mentor\\MemberController@progress', 'api.mentor.members.progress');
    
    // Attendance management
    $router->get('/attendance/recent', 'Api\\Mentor\\AttendanceController@recent', 'api.mentor.attendance.recent');
    $router->post('/attendance/bulk-signout', 'Api\\Mentor\\AttendanceController@bulkSignout', 'api.mentor.attendance.bulk_signout');
    
    // Reports
    $router->get('/reports/attendance', 'Api\\Mentor\\ReportController@attendance', 'api.mentor.reports.attendance');
    $router->get('/reports/programs', 'Api\\Mentor\\ReportController@programs', 'api.mentor.reports.programs');
});

// ====== ADMIN API ROUTES ======

$router->group(['prefix' => 'api/v1/admin', 'middleware' => ['ApiMiddleware', 'AuthMiddleware', 'RoleMiddleware:admin']], function($router) {
    
    // User management
    $router->get('/users', 'Api\\Admin\\UserController@index', 'api.admin.users.index');
    $router->post('/users', 'Api\\Admin\\UserController@store', 'api.admin.users.store');
    $router->get('/users/{id}', 'Api\\Admin\\UserController@show', 'api.admin.users.show');
    $router->put('/users/{id}', 'Api\\Admin\\UserController@update', 'api.admin.users.update');
    $router->delete('/users/{id}', 'Api\\Admin\\UserController@destroy', 'api.admin.users.destroy');
    
    // Course management
    $router->get('/courses', 'Api\\Admin\\CourseController@index', 'api.admin.courses.index');
    $router->post('/courses', 'Api\\Admin\\CourseController@store', 'api.admin.courses.store');
    $router->get('/courses/{id}', 'Api\\Admin\\CourseController@show', 'api.admin.courses.show');
    $router->put('/courses/{id}', 'Api\\Admin\\CourseController@update', 'api.admin.courses.update');
    $router->delete('/courses/{id}', 'Api\\Admin\\CourseController@destroy', 'api.admin.courses.destroy');
    
    // Program management
    $router->get('/programs', 'Api\\Admin\\ProgramController@index', 'api.admin.programs.index');
    $router->post('/programs', 'Api\\Admin\\ProgramController@store', 'api.admin.programs.store');
    $router->get('/programs/{id}', 'Api\\Admin\\ProgramController@show', 'api.admin.programs.show');
    $router->put('/programs/{id}', 'Api\\Admin\\ProgramController@update', 'api.admin.programs.update');
    $router->delete('/programs/{id}', 'Api\\Admin\\ProgramController@destroy', 'api.admin.programs.destroy');
    
    // Analytics
    $router->get('/analytics/overview', 'Api\\Admin\\AnalyticsController@overview', 'api.admin.analytics.overview');
    $router->get('/analytics/users', 'Api\\Admin\\AnalyticsController@users', 'api.admin.analytics.users');
    $router->get('/analytics/courses', 'Api\\Admin\\AnalyticsController@courses', 'api.admin.analytics.courses');
    $router->get('/analytics/programs', 'Api\\Admin\\AnalyticsController@programs', 'api.admin.analytics.programs');
    
    // System management
    $router->get('/system/logs', 'Api\\Admin\\SystemController@logs', 'api.admin.system.logs');
    $router->post('/system/cache/clear', 'Api\\Admin\\SystemController@clearCache', 'api.admin.system.cache.clear');
    $router->get('/system/stats', 'Api\\Admin\\SystemController@stats', 'api.admin.system.stats');
});

return $router;
```

---

## Task 3: Middleware Implementation

### 3.1 Create Authentication Middleware
**File**: `app/Middleware/AuthMiddleware.php`

```php
<?php
/**
 * Authentication Middleware
 * Phase 3 Implementation
 */

class AuthMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $this->handleUnauthenticated();
            return false;
        }
        
        // Optional: Verify session is still valid
        if (!$this->verifySession()) {
            $this->handleInvalidSession();
            return false;
        }
        
        // Extend session timeout
        $this->extendSession();
        
        return true;
    }
    
    private function verifySession() {
        // Check session timeout
        $sessionTimeout = ConfigLoader::get('app.security.session_timeout', 7200); // 2 hours default
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $sessionTimeout) {
                return false;
            }
        }
        
        // Optional: Verify user still exists and is active
        if (isset($_SESSION['user_id'])) {
            global $conn;
            
            $sql = "SELECT id, user_type, active FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false; // User no longer exists
            }
            
            $user = $result->fetch_assoc();
            if (isset($user['active']) && !$user['active']) {
                return false; // User account is deactivated
            }
        }
        
        return true;
    }
    
    private function extendSession() {
        $_SESSION['last_activity'] = time();
    }
    
    private function handleUnauthenticated() {
        $this->logger->info('Unauthenticated access attempt', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ]);
        
        $this->redirectToLogin();
    }
    
    private function handleInvalidSession() {
        $this->logger->warning('Invalid session detected', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'last_activity' => $_SESSION['last_activity'] ?? null
        ]);
        
        // Clear session
        session_destroy();
        session_start();
        
        $this->redirectToLogin('session_expired');
    }
    
    private function redirectToLogin($reason = 'authentication_required') {
        if ($this->isAjaxRequest() || $this->isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Authentication required',
                'code' => 'AUTHENTICATION_REQUIRED',
                'reason' => $reason
            ]);
        } else {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $loginUrl = '/login';
            
            if (!empty($currentUrl) && $currentUrl !== '/logout') {
                $loginUrl .= '?redirect=' . urlencode($currentUrl);
            }
            
            if ($reason !== 'authentication_required') {
                $loginUrl .= (strpos($loginUrl, '?') !== false ? '&' : '?') . 'reason=' . $reason;
            }
            
            header('Location: ' . $loginUrl);
        }
        
        exit;
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    private function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }
}
```

### 3.2 Create Role-based Authorization Middleware
**File**: `app/Middleware/RoleMiddleware.php`

```php
<?php
/**
 * Role-based Authorization Middleware
 * Phase 3 Implementation
 */

class RoleMiddleware {
    private $requiredRoles;
    private $logger;
    
    public function __construct($roles = null) {
        $this->requiredRoles = is_string($roles) ? explode(',', $roles) : (array) $roles;
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Ensure user is authenticated
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            $this->handleUnauthorized('not_authenticated');
            return false;
        }
        
        $userRole = $_SESSION['user_type'];
        
        // Check if user has required role
        if (!empty($this->requiredRoles) && !in_array($userRole, $this->requiredRoles)) {
            $this->handleUnauthorized('insufficient_permissions');
            return false;
        }
        
        return true;
    }
    
    private function handleUnauthorized($reason) {
        $this->logger->warning('Authorization failed', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_type'] ?? null,
            'required_roles' => $this->requiredRoles,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        if ($this->isAjaxRequest() || $this->isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'Insufficient permissions',
                'code' => 'AUTHORIZATION_FAILED',
                'reason' => $reason
            ]);
        } else {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
        }
        
        exit;
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    private function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }
}
```

### 3.3 Create API Middleware
**File**: `app/Middleware/ApiMiddleware.php`

```php
<?php
/**
 * API Middleware - Handle API-specific concerns
 * Phase 3 Implementation
 */

class ApiMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function handle() {
        // Set API headers
        $this->setApiHeaders();
        
        // Handle preflight requests
        if ($this->isPreflightRequest()) {
            $this->handlePreflight();
            return false; // Stop further processing
        }
        
        // Validate content type for non-GET requests
        if (!$this->validateContentType()) {
            $this->respondWithError('Invalid content type', 415);
            return false;
        }
        
        // Rate limiting (reuse existing middleware)
        $rateLimiter = new RateLimitMiddleware(Database::connection());
        if (!$rateLimiter->handle('api')) {
            return false; // Rate limit response handled by middleware
        }
        
        return true;
    }
    
    private function setApiHeaders() {
        // CORS headers
        header('Access-Control-Allow-Origin: *'); // Configure based on your needs
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        header('Access-Control-Max-Age: 86400'); // 24 hours
        
        // API headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
    
    private function isPreflightRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
    }
    
    private function handlePreflight() {
        http_response_code(200);
        echo json_encode(['status' => 'OK']);
        exit;
    }
    
    private function validateContentType() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only validate content type for requests that should have a body
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return true;
        }
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Allow these content types
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data' // For file uploads
        ];
        
        foreach ($allowedTypes as $type) {
            if (strpos($contentType, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function respondWithError($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 'API_ERROR'
        ]);
        exit;
    }
}
```

---

## Task 4: URL Rewriting Configuration

### 4.1 Apache Configuration
**File**: `public/.htaccess`

```apache
RewriteEngine On

# Redirect to HTTPS (if available)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove index.php from URLs
RewriteCond %{THE_REQUEST} /index\.php[?\s] [NC]
RewriteRule ^(.*)index\.php$ /$1 [R=301,L]

# Handle API routes
RewriteRule ^api/(.*)$ api.php [QSA,L]

# Handle all other routes through main routing file
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    
    # Cache static assets
    <FilesMatch "\\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
</IfModule>

# Prevent access to sensitive files
<FilesMatch "\\.(env|log|sql|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent access to configuration and system directories
RedirectMatch 404 /\\.
RedirectMatch 404 /config/
RedirectMatch 404 /core/
RedirectMatch 404 /storage/logs/

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

### 4.2 Nginx Configuration (Alternative)
**File**: `nginx.conf` (example configuration)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/Sci-Bono_Clubhoue_LMS/public;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # API routes
    location /api/ {
        try_files $uri $uri/ /api.php?$query_string;
    }
    
    # Main application routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP processing
    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static assets caching
    location ~* \\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Prevent access to sensitive files
    location ~ /\\. {
        deny all;
    }
    
    location ~ /(config|core|storage|vendor)/ {
        deny all;
    }
    
    location ~ \\.(env|log|sql|md)$ {
        deny all;
    }
}
```

---

## Task 5: New Entry Points

### 5.1 Update Main Entry Point
**File**: `index.php` (Updated for routing)

```php
<?php
/**
 * Main Application Entry Point
 * Phase 3 Implementation - Updated for routing
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

// Load database connection
require_once __DIR__ . '/server.php';

try {
    // Load and configure router
    $router = require_once __DIR__ . '/routes/web.php';
    
    // Set base path for subdirectory installations
    $basePath = '/Sci-Bono_Clubhoue_LMS';
    $router->setBasePath($basePath);
    
    // Check if route caching is enabled and available
    $cacheFile = __DIR__ . '/storage/cache/routes.php';
    
    if (ConfigLoader::get('app.env') === 'production' && file_exists($cacheFile)) {
        $router->loadCache($cacheFile);
    }
    
    // Dispatch the request
    $router->dispatch();
    
} catch (Exception $e) {
    // Log the error
    $logger = new Logger();
    $logger->error('Routing error: ' . $e->getMessage(), [
        'exception' => $e,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
    ]);
    
    // Show error page
    http_response_code(500);
    if (ConfigLoader::get('app.debug')) {
        echo '<h1>Routing Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        require_once __DIR__ . '/app/Views/errors/500.php';
    }
}
?>
```

### 5.2 Create API Entry Point
**File**: `api.php`

```php
<?php
/**
 * API Entry Point
 * Phase 3 Implementation
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

// Load database connection
require_once __DIR__ . '/server.php';

try {
    // Load API routes
    $router = require_once __DIR__ . '/routes/api.php';
    
    // Set base path
    $basePath = '/Sci-Bono_Clubhoue_LMS';
    $router->setBasePath($basePath);
    
    // Dispatch API request
    $router->dispatch();
    
} catch (Exception $e) {
    // Log API error
    $logger = new Logger();
    $logger->error('API error: ' . $e->getMessage(), [
        'exception' => $e,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'input' => file_get_contents('php://input')
    ]);
    
    // Return JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    
    if (ConfigLoader::get('app.debug')) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode([
            'error' => true,
            'message' => 'Internal server error',
            'code' => 'INTERNAL_ERROR'
        ]);
    }
}
?>
```

---

## Task 6: Backward Compatibility

### 6.1 Create Compatibility Layer
**File**: `core/LegacyRouteHandler.php`

```php
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
        'core/Router.php' => '/attendance', // Your current attendance entry
        
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
```

### 6.2 Update Legacy Files for Redirect
**Example Update**: `login.php`

```php
<?php
/**
 * Legacy login.php - Redirect to new routing system
 * Phase 3 Implementation
 */

// Check if this is being accessed directly
if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') {
    // Redirect to new route
    $newUrl = '/login';
    
    // Preserve query parameters
    if (!empty($_SERVER['QUERY_STRING'])) {
        $newUrl .= '?' . $_SERVER['QUERY_STRING'];
    }
    
    header('Location: ' . $newUrl, true, 301);
    exit;
}

// If included by the router, continue with original functionality
// ... existing login.php code ...
?>
```

---

## Task 7: URL Helper Functions

### 7.1 Create URL Helper Class
**File**: `core/UrlHelper.php`

```php
<?php
/**
 * URL Helper - Generate URLs and manage routing utilities
 * Phase 3 Implementation
 */

class UrlHelper {
    private static $router;
    private static $basePath = '';
    
    /**
     * Set router instance
     */
    public static function setRouter($router) {
        self::$router = $router;
    }
    
    /**
     * Set base path
     */
    public static function setBasePath($basePath) {
        self::$basePath = rtrim($basePath, '/');
    }
    
    /**
     * Generate URL for named route
     */
    public static function route($name, $params = []) {
        if (self::$router) {
            return self::$router->url($name, $params);
        }
        
        throw new Exception('Router not initialized');
    }
    
    /**
     * Generate URL with base path
     */
    public static function to($path) {
        $path = ltrim($path, '/');
        return self::$basePath . '/' . $path;
    }
    
    /**
     * Generate asset URL
     */
    public static function asset($path) {
        $path = ltrim($path, '/');
        return self::$basePath . '/public/assets/' . $path;
    }
    
    /**
     * Get current URL
     */
    public static function current() {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    /**
     * Check if current URL matches pattern
     */
    public static function is($pattern) {
        $current = self::current();
        
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['.*', '\\/'], $pattern);
        $regex = '/^' . $regex . '$/';
        
        return preg_match($regex, $current);
    }
    
    /**
     * Generate URL with query parameters
     */
    public static function withQuery($url, $params) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }
    
    /**
     * Get previous URL from referrer
     */
    public static function previous($default = '/') {
        return $_SERVER['HTTP_REFERER'] ?? $default;
    }
    
    /**
     * Generate secure URL (HTTPS)
     */
    public static function secure($path) {
        $url = self::to($path);
        return str_replace('http://', 'https://', $url);
    }
    
    /**
     * Check if URL is external
     */
    public static function isExternal($url) {
        return filter_var($url, FILTER_VALIDATE_URL) && 
               parse_url($url, PHP_URL_HOST) !== $_SERVER['HTTP_HOST'];
    }
}
```

### 7.2 Create View Helper Functions
**File**: `app/Helpers/ViewHelpers.php`

```php
<?php
/**
 * View Helper Functions for Templates
 * Phase 3 Implementation
 */

/**
 * Generate URL for named route
 */
function route($name, $params = []) {
    return UrlHelper::route($name, $params);
}

/**
 * Generate URL with base path
 */
function url($path) {
    return UrlHelper::to($path);
}

/**
 * Generate asset URL
 */
function asset($path) {
    return UrlHelper::asset($path);
}

/**
 * Check if current route matches pattern
 */
function is_active($pattern, $class = 'active') {
    return UrlHelper::is($pattern) ? $class : '';
}

/**
 * Generate CSRF token field
 */
function csrf_field() {
    require_once __DIR__ . '/../../core/CSRF.php';
    return CSRF::field();
}

/**
 * Generate CSRF meta tag
 */
function csrf_meta() {
    require_once __DIR__ . '/../../core/CSRF.php';
    return CSRF::metaTag();
}

/**
 * Get configuration value
 */
function config($key, $default = null) {
    return ConfigLoader::get($key, $default);
}

/**
 * Escape HTML output
 */
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user has role
 */
function user_has_role($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Get current user data
 */
function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'name' => $_SESSION['name'] ?? '',
        'user_type' => $_SESSION['user_type'] ?? ''
    ];
}

/**
 * Format date for display
 */
function format_date($date, $format = 'Y-m-d H:i') {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Generate pagination links
 */
function paginate($currentPage, $totalPages, $routeName, $params = []) {
    $links = [];
    
    // Previous page
    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $links[] = '<a href="' . route($routeName, $params) . '" class="page-link">Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $class = $i === $currentPage ? 'page-link active' : 'page-link';
        $links[] = '<a href="' . route($routeName, $params) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Next page
    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $links[] = '<a href="' . route($routeName, $params) . '" class="page-link">Next</a>';
    }
    
    return '<div class="pagination">' . implode(' ', $links) . '</div>';
}
```

---

## Task 8: Update Templates for New Routing

### 8.1 Update Navigation Template
**Example Update**: `header.php`

```php
<?php
// Include view helpers
require_once __DIR__ . '/app/Helpers/ViewHelpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo config('app.name'); ?></title>
    <?php echo csrf_meta(); ?>
    <link rel="stylesheet" href="<?php echo asset('css/header.css'); ?>">
</head>
<body>
    <nav class="main-nav">
        <div class="nav-brand">
            <a href="<?php echo route('home'); ?>">
                <img src="<?php echo asset('images/TheClubhouse_Logo_White_Large.png'); ?>" alt="Clubhouse Logo">
            </a>
        </div>
        
        <div class="nav-links">
            <?php if (current_user()): ?>
                <!-- Authenticated navigation -->
                <a href="<?php echo route('dashboard'); ?>" class="<?php echo is_active('/dashboard'); ?>">Dashboard</a>
                <a href="<?php echo route('courses.index'); ?>" class="<?php echo is_active('/courses*'); ?>">Courses</a>
                <a href="<?php echo route('programs.index'); ?>" class="<?php echo is_active('/programs*'); ?>">Programs</a>
                
                <?php if (user_has_role('mentor') || user_has_role('admin')): ?>
                    <a href="<?php echo route('mentor.dashboard'); ?>" class="<?php echo is_active('/mentor*'); ?>">Mentor</a>
                <?php endif; ?>
                
                <?php if (user_has_role('admin')): ?>
                    <a href="<?php echo route('admin.dashboard'); ?>" class="<?php echo is_active('/admin*'); ?>">Admin</a>
                <?php endif; ?>
                
                <div class="nav-user">
                    <span>Welcome, <?php echo e(current_user()['name']); ?></span>
                    <a href="<?php echo route('profile.show'); ?>">Profile</a>
                    <form action="<?php echo route('logout'); ?>" method="POST" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Guest navigation -->
                <a href="<?php echo route('login.show'); ?>">Login</a>
                <a href="<?php echo route('signup.show'); ?>">Sign Up</a>
                <a href="<?php echo route('attendance.index'); ?>">Attendance</a>
            <?php endif; ?>
        </div>
    </nav>
```

### 8.2 Create Route Caching Command
**File**: `scripts/cache-routes.php`

```php
#!/usr/bin/env php
<?php
/**
 * Route Caching Script
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../bootstrap.php';

// Ensure cache directory exists
$cacheDir = __DIR__ . '/../storage/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Load routes and cache them
echo "Caching web routes...\n";
$webRouter = require __DIR__ . '/../routes/web.php';
$webRouter->cache($cacheDir . '/web-routes.php');

echo "Caching API routes...\n";
$apiRouter = require __DIR__ . '/../routes/api.php';
$apiRouter->cache($cacheDir . '/api-routes.php');

echo "Route caching completed!\n";
?>
```

---

## Phase 3 Testing & Integration

### Task 9: Testing Checklist

#### 9.1 Route Testing
- [ ] Test all defined routes respond correctly
- [ ] Verify middleware execution order
- [ ] Test parameter extraction from URLs
- [ ] Validate named route URL generation
- [ ] Test route group functionality

#### 9.2 Backward Compatibility Testing
- [ ] Legacy URLs redirect to new routes
- [ ] Existing functionality remains intact
- [ ] Forms submit to correct new endpoints
- [ ] AJAX calls work with new routing

#### 9.3 Middleware Testing
- [ ] Authentication middleware blocks unauthenticated users
- [ ] Role middleware enforces authorization
- [ ] API middleware sets correct headers
- [ ] Security middleware prevents unauthorized access

#### 9.4 Performance Testing
- [ ] Route caching improves performance
- [ ] URL generation is fast
- [ ] Middleware execution is efficient
- [ ] Memory usage is reasonable

---

## Phase 3 Completion Checklist

### Core Routing System
- [ ] Enhanced Router class implemented
- [ ] Web routes defined with proper grouping
- [ ] API routes separated and organized
- [ ] Middleware system working correctly
- [ ] Named routes and URL generation functional

### URL Rewriting
- [ ] Apache .htaccess configuration active
- [ ] Clean URLs working without index.php
- [ ] Static asset serving optimized
- [ ] Security headers implemented

### Backward Compatibility
- [ ] Legacy route handler implemented
- [ ] Old URLs redirect to new routes
- [ ] Existing forms updated for new routing
- [ ] No broken functionality from routing changes

### Helper Systems
- [ ] URL helper functions available
- [ ] View helpers integrated
- [ ] Route caching system implemented
- [ ] Error handling for routing failures

---

## Next Phase Preparation

Before proceeding to Phase 4 (MVC Refinement):
1. **Thorough Testing**: Test all routes and functionality
2. **Performance Verification**: Ensure routing doesn't slow down the application
3. **User Acceptance**: Verify all features work from user perspective
4. **Documentation**: Document new URL structure and routing conventions
5. **Cache Optimization**: Implement route caching for production

**Phase 3 establishes modern URL routing that will be used throughout all future phases. Ensure it's solid before proceeding.**