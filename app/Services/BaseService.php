<?php
/**
 * Base Service - Common functionality for all services
 * Phase 4 Implementation
 */

require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../config/ConfigLoader.php';

abstract class BaseService {
    protected $conn;
    protected $logger;
    protected $config;
    
    public function __construct($conn = null) {
        global $conn;
        $this->conn = $conn ?? $GLOBALS['conn'] ?? null;
        $this->logger = new Logger();
        $this->config = ConfigLoader::load();
    }
    
    /**
     * Handle service errors consistently
     */
    protected function handleError($message, $context = []) {
        $this->logger->error($message, array_merge($context, [
            'service' => get_class($this)
        ]));
        
        throw new Exception($message);
    }
    
    /**
     * Log service actions
     */
    protected function logAction($action, $context = []) {
        $this->logger->info("Service action: {$action}", array_merge($context, [
            'service' => get_class($this)
        ]));
    }
    
    /**
     * Validate required parameters
     */
    protected function validateRequired($data, $required) {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field missing: {$field}");
            }
        }
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
    }
}