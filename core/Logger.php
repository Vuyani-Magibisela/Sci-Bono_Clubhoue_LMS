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