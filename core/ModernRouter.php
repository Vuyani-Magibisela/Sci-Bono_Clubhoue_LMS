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