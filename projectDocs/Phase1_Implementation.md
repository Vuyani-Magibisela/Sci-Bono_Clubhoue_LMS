# Phase 1: Foundation Implementation Guide
## Configuration Management & Error Handling

**Duration**: Weeks 1-2  
**Priority**: HIGH  
**Team Size**: 1-2 developers  

---

## Overview

Phase 1 establishes the foundational infrastructure for the modernized LMS system. This phase focuses on implementing environment-based configuration management and comprehensive error handling with logging capabilities.

### Key Objectives
- ✅ Replace hardcoded configuration with environment-based system
- ✅ Implement comprehensive error handling and logging
- ✅ Set up structured configuration management
- ✅ Create user-friendly error pages
- ✅ Establish logging infrastructure with rotation

---

## Pre-Implementation Checklist

- [ ] **Backup Current System**: Create full backup of existing codebase and database
- [ ] **Development Environment**: Ensure local development environment is set up
- [ ] **Access Verification**: Confirm write permissions for new directories
- [ ] **Database Access**: Verify database connection and permissions
- [ ] **Version Control**: Ensure all current changes are committed to Git

---

## Task 1: Environment Configuration System

### 1.1 Create Configuration Directory Structure
```bash
# Create configuration directories
mkdir -p config
mkdir -p storage/logs
chmod 755 storage/logs
```

### 1.2 Create Environment Template File
**File**: `.env.example`
```bash
# Application Configuration
APP_NAME="Sci-Bono Clubhouse LMS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/Sci-Bono_Clubhoue_LMS

# Database Configuration
DB_HOST=localhost
DB_NAME=accounts
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4

# Session Configuration
SESSION_LIFETIME=120
SESSION_SECURE=false
SESSION_HTTP_ONLY=true

# Mail Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@scibono.ac.za
MAIL_FROM_NAME="Sci-Bono Clubhouse"

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=storage/logs/app.log

# Security Configuration
CSRF_TOKEN_NAME=_token
BCRYPT_ROUNDS=12

# File Upload Configuration
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=public/assets/uploads/
```

### 1.3 Create Actual Environment File
```bash
# Copy template and customize
cp .env.example .env
# Edit .env with actual values from server.php
```

### 1.4 Implement ConfigLoader Class
**File**: `config/ConfigLoader.php`

```php
<?php
/**
 * Configuration Loader - Handles environment variables and configuration files
 * Phase 1 Implementation
 */

class ConfigLoader {
    private static $config = [];
    private static $envLoaded = false;
    
    /**
     * Load all configuration files
     */
    public static function load() {
        if (empty(self::$config)) {
            self::loadEnv();
            self::$config = [
                'app' => require __DIR__ . '/app.php',
                'database' => require __DIR__ . '/database.php',
                'mail' => require __DIR__ . '/mail.php',
                'session' => require __DIR__ . '/session.php',
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Get configuration value by key
     */
    public static function get($key, $default = null) {
        $config = self::load();
        
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $config;
            
            foreach ($keys as $k) {
                if (is_array($value) && array_key_exists($k, $value)) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }
            
            return $value;
        }
        
        return $config[$key] ?? $default;
    }
    
    /**
     * Load environment variables from .env file
     */
    private static function loadEnv() {
        if (self::$envLoaded) {
            return;
        }
        
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new Exception('.env file not found');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$envLoaded = true;
    }
    
    /**
     * Get environment variable
     */
    public static function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key) ?? $default;
        
        // Convert boolean strings
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'null':
                case '(null)':
                    return null;
            }
        }
        
        return $value;
    }
}
```

### 1.5 Create Configuration Files

**File**: `config/app.php`
```php
<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'name' => ConfigLoader::env('APP_NAME', 'Sci-Bono Clubhouse LMS'),
    'env' => ConfigLoader::env('APP_ENV', 'production'),
    'debug' => ConfigLoader::env('APP_DEBUG', false),
    'url' => ConfigLoader::env('APP_URL', 'http://localhost'),
    
    'timezone' => 'Africa/Johannesburg',
    'locale' => 'en',
    
    'uploads' => [
        'path' => ConfigLoader::env('UPLOAD_PATH', 'public/assets/uploads/'),
        'max_size' => ConfigLoader::env('UPLOAD_MAX_SIZE', 10485760),
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    ],
    
    'security' => [
        'csrf_token_name' => ConfigLoader::env('CSRF_TOKEN_NAME', '_token'),
        'bcrypt_rounds' => ConfigLoader::env('BCRYPT_ROUNDS', 12),
    ],
];
```

**File**: `config/database.php`
```php
<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysqli',
            'host' => ConfigLoader::env('DB_HOST', 'localhost'),
            'database' => ConfigLoader::env('DB_NAME', 'accounts'),
            'username' => ConfigLoader::env('DB_USERNAME', 'root'),
            'password' => ConfigLoader::env('DB_PASSWORD', ''),
            'charset' => ConfigLoader::env('DB_CHARSET', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

**File**: `config/mail.php`
```php
<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'driver' => ConfigLoader::env('MAIL_DRIVER', 'smtp'),
    'host' => ConfigLoader::env('MAIL_HOST', 'localhost'),
    'port' => ConfigLoader::env('MAIL_PORT', 587),
    'username' => ConfigLoader::env('MAIL_USERNAME', ''),
    'password' => ConfigLoader::env('MAIL_PASSWORD', ''),
    'encryption' => ConfigLoader::env('MAIL_ENCRYPTION', 'tls'),
    
    'from' => [
        'address' => ConfigLoader::env('MAIL_FROM_ADDRESS', 'noreply@localhost'),
        'name' => ConfigLoader::env('MAIL_FROM_NAME', 'LMS System'),
    ],
];
```

### 1.6 Update server.php (Gradual Migration)
**File**: `server.php` (Modified)
```php
<?php
// Load new configuration system
require_once __DIR__ . '/config/ConfigLoader.php';

try {
    // Use new configuration system
    $config = ConfigLoader::get('database.connections.mysql');
    
    $host = $config['host'];
    $user = $config['username'];
    $password = $config['password'];
    $dbname = $config['database'];
} catch (Exception $e) {
    // Fallback to hardcoded values during transition
    $host = "localhost";
    $user = "vuksDev";
    $password = "Vu13#k*s3D3V";
    $dbname = "accounts";
    
    error_log("Configuration system not available, using fallback values: " . $e->getMessage());
}

// Connect to MySQL database
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

---

## Task 2: Logging System Implementation

### 2.1 Create Logger Class
**File**: `core/Logger.php`
```php
<?php
/**
 * Logger Class - Comprehensive logging system with rotation
 * Phase 1 Implementation
 */

class Logger {
    const EMERGENCY = 0;
    const ALERT = 1;
    const CRITICAL = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;
    
    private static $levels = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG'
    ];
    
    private $logPath;
    private $maxFileSize;
    private $maxFiles;
    
    public function __construct($logPath = null) {
        $this->logPath = $logPath ?? __DIR__ . '/../storage/logs/';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 5;
        
        $this->ensureLogDirectoryExists();
    }
    
    public function log($level, $message, $context = []) {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $levelName = self::$levels[$level];
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        
        $logLine = "[{$timestamp}] {$levelName}: {$message}";
        if ($contextStr) {
            $logLine .= " Context: {$contextStr}";
        }
        $logLine .= PHP_EOL;
        
        $this->writeToFile($logLine);
    }
    
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    private function writeToFile($logLine) {
        $logFile = $this->getLogFile();
        
        // Rotate logs if needed
        if (file_exists($logFile) && filesize($logFile) > $this->maxFileSize) {
            $this->rotateLogs();
        }
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    private function getLogFile() {
        return $this->logPath . 'app-' . date('Y-m-d') . '.log';
    }
    
    private function rotateLogs() {
        $currentLog = $this->getLogFile();
        $timestamp = date('Y-m-d-H-i-s');
        $rotatedLog = $this->logPath . "app-{$timestamp}.log";
        
        if (file_exists($currentLog)) {
            rename($currentLog, $rotatedLog);
        }
        
        $this->cleanupOldLogs();
    }
    
    private function cleanupOldLogs() {
        $files = glob($this->logPath . 'app-*.log');
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        if (count($files) > $this->maxFiles) {
            $filesToDelete = array_slice($files, $this->maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
    
    private function ensureLogDirectoryExists() {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
}
```

---

## Task 3: Error Handling System

### 3.1 Create Error Handler Class
**File**: `core/ErrorHandler.php`
```php
<?php
/**
 * Error Handler - Comprehensive error handling with logging
 * Phase 1 Implementation
 */

require_once __DIR__ . '/Logger.php';

class ErrorHandler {
    private $logger;
    private $config;
    
    public function __construct($config = null) {
        require_once __DIR__ . '/../config/ConfigLoader.php';
        $this->config = $config ?? ConfigLoader::get('app');
        $this->logger = new Logger();
        
        $this->registerHandlers();
    }
    
    private function registerHandlers() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    public function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'PHP Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ];
        
        $this->logError($error);
        
        if ($this->shouldDisplayError($severity)) {
            $this->displayError($error);
        }
        
        return true;
    }
    
    public function handleException($exception) {
        $error = [
            'type' => 'Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        ];
        
        $this->logError($error);
        $this->displayException($exception);
    }
    
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private function logError($error) {
        $level = $this->getLogLevel($error);
        $context = array_merge($error, [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'memory_usage' => memory_get_usage(true),
        ]);
        
        $this->logger->log($level, $error['message'], $context);
    }
    
    private function getLogLevel($error) {
        if (isset($error['severity'])) {
            switch ($error['severity']) {
                case E_ERROR:
                case E_CORE_ERROR:
                    return Logger::ERROR;
                case E_WARNING:
                    return Logger::WARNING;
                case E_NOTICE:
                    return Logger::NOTICE;
                default:
                    return Logger::ERROR;
            }
        }
        
        return Logger::ERROR;
    }
    
    private function shouldDisplayError($severity) {
        return $this->config['debug'] && $severity >= E_WARNING;
    }
    
    private function displayError($error) {
        if ($this->isAjaxRequest()) {
            $this->jsonErrorResponse($error);
        } else {
            $this->htmlErrorResponse($error);
        }
    }
    
    private function displayException($exception) {
        http_response_code(500);
        
        if ($this->isAjaxRequest()) {
            $this->jsonExceptionResponse($exception);
        } else {
            $this->htmlExceptionResponse($exception);
        }
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    
    private function jsonErrorResponse($error) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $this->config['debug'] ? $error['message'] : 'An error occurred',
            'type' => $error['type']
        ]);
        exit;
    }
    
    private function jsonExceptionResponse($exception) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $this->config['debug'] ? $exception->getMessage() : 'An error occurred',
            'type' => get_class($exception)
        ]);
        exit;
    }
    
    private function htmlErrorResponse($error) {
        if ($this->config['debug']) {
            $this->showDebugError($error);
        } else {
            require_once __DIR__ . '/../app/Views/errors/500.php';
        }
        exit;
    }
    
    private function htmlExceptionResponse($exception) {
        if ($this->config['debug']) {
            $this->showDebugException($exception);
        } else {
            require_once __DIR__ . '/../app/Views/errors/500.php';
        }
        exit;
    }
    
    private function showDebugError($error) {
        echo "<div style='background: #f8f8f8; padding: 20px; font-family: monospace;'>";
        echo "<h2>Debug Error Information</h2>";
        echo "<p><strong>Type:</strong> {$error['type']}</p>";
        echo "<p><strong>Message:</strong> {$error['message']}</p>";
        echo "<p><strong>File:</strong> {$error['file']}:{$error['line']}</p>";
        echo "<p><strong>Time:</strong> {$error['timestamp']}</p>";
        echo "</div>";
    }
    
    private function showDebugException($exception) {
        echo "<div style='background: #f8f8f8; padding: 20px; font-family: monospace;'>";
        echo "<h2>Debug Exception Information</h2>";
        echo "<p><strong>Type:</strong> " . get_class($exception) . "</p>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . ":" . $exception->getLine() . "</p>";
        echo "<pre><strong>Stack Trace:</strong>\n" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    }
}
```

### 3.2 Create Error View Pages

**File**: `app/Views/errors/404.php`
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Sci-Bono Clubhouse LMS</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-container { max-width: 600px; margin: 0 auto; }
        .error-code { font-size: 120px; color: #F29A2E; margin: 0; }
        .error-message { font-size: 24px; color: #333; margin: 20px 0; }
        .error-description { color: #666; margin: 20px 0; }
        .back-link { display: inline-block; background: #F29A2E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-message">Page Not Found</h2>
        <p class="error-description">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <a href="/" class="back-link">Return Home</a>
    </div>
</body>
</html>
```

**File**: `app/Views/errors/500.php`
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Sci-Bono Clubhouse LMS</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-container { max-width: 600px; margin: 0 auto; }
        .error-code { font-size: 120px; color: #e74c3c; margin: 0; }
        .error-message { font-size: 24px; color: #333; margin: 20px 0; }
        .error-description { color: #666; margin: 20px 0; }
        .back-link { display: inline-block; background: #F29A2E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <h2 class="error-message">Internal Server Error</h2>
        <p class="error-description">
            Something went wrong on our end. We've been notified and are working to fix it.
        </p>
        <a href="/" class="back-link">Return Home</a>
    </div>
</body>
</html>
```

---

## Task 4: Integration and Testing

### 4.1 Create Bootstrap File
**File**: `bootstrap.php`
```php
<?php
/**
 * Bootstrap file - Initialize core systems
 * Phase 1 Implementation
 */

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Load configuration
require_once __DIR__ . '/config/ConfigLoader.php';

// Initialize error handler
require_once __DIR__ . '/core/ErrorHandler.php';
$errorHandler = new ErrorHandler();

// Load core classes
require_once __DIR__ . '/core/Logger.php';

// Set configuration-based error reporting
$config = ConfigLoader::get('app');
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
```

### 4.2 Update Key Entry Points

**Update**: `index.php`
```php
<?php
// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

// Continue with existing index.php code...
?>
```

### 4.3 Test Configuration System
**File**: `test_config.php` (Temporary testing file)
```php
<?php
require_once __DIR__ . '/config/ConfigLoader.php';

try {
    echo "Testing configuration system...\n";
    
    // Test environment loading
    $appName = ConfigLoader::env('APP_NAME');
    echo "App Name: " . $appName . "\n";
    
    // Test configuration access
    $dbConfig = ConfigLoader::get('database.connections.mysql');
    echo "Database Host: " . $dbConfig['host'] . "\n";
    
    // Test logging
    require_once __DIR__ . '/core/Logger.php';
    $logger = new Logger();
    $logger->info('Configuration system test successful');
    
    echo "✅ Configuration system working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
```

---

## Phase 1 Completion Checklist

### Configuration Management
- [ ] Created `.env` file with all environment variables
- [ ] Implemented `ConfigLoader` class
- [ ] Created all configuration files (`app.php`, `database.php`, `mail.php`)
- [ ] Updated `server.php` to use new configuration system
- [ ] Verified database connection works with new config

### Error Handling & Logging
- [ ] Implemented `Logger` class with rotation
- [ ] Created `ErrorHandler` class with comprehensive error capture
- [ ] Created error view pages (404.php, 500.php)
- [ ] Set up `storage/logs/` directory with proper permissions
- [ ] Tested error handling in development mode

### Integration
- [ ] Created `bootstrap.php` file
- [ ] Updated main entry points to use bootstrap
- [ ] Tested configuration system with `test_config.php`
- [ ] Verified logging system creates and rotates files
- [ ] Confirmed no breaking changes to existing functionality

### Testing Verification
- [ ] All existing functionality still works
- [ ] New error pages display correctly
- [ ] Log files are created and contain proper entries
- [ ] Configuration values are loaded from `.env` file
- [ ] Database connection works with new configuration

---

## Quick Wins Achieved

1. **Immediate Security**: Database credentials no longer hardcoded
2. **Better Debugging**: Comprehensive error logging with context
3. **Environment Flexibility**: Easy configuration for different environments
4. **Professional Error Pages**: User-friendly error displays
5. **Maintenance Ready**: Log rotation prevents disk space issues

---

## Known Issues & Troubleshooting

### Issue: ".env file not found"
**Solution**: Ensure `.env` file is created and readable
```bash
cp .env.example .env
chmod 644 .env
```

### Issue: Log directory permissions
**Solution**: Set proper permissions
```bash
chmod 755 storage/logs
chown www-data:www-data storage/logs  # On Linux
```

### Issue: Configuration not loading
**Solution**: Check file paths and require statements
```php
// Debug configuration loading
var_dump(ConfigLoader::env('APP_NAME'));
```

---

## Next Phase Preparation

Before proceeding to Phase 2 (Security Hardening):
1. Verify all Phase 1 tasks are completed
2. Test system thoroughly with new configuration
3. Backup system with Phase 1 changes
4. Document any customizations made during implementation
5. Review error logs for any issues

**Phase 1 establishes the foundation for all subsequent improvements. Take time to verify everything works correctly before moving forward.**