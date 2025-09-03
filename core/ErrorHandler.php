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