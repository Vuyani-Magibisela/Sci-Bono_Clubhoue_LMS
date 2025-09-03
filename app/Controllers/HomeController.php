<?php
/**
 * Home Controller - Landing page
 * Phase 3 Implementation
 */

class HomeController {
    private $conn;
    private $config;
    
    public function __construct($conn = null, $config = null) {
        $this->conn = $conn;
        $this->config = $config;
    }
    
    /**
     * Display the home/landing page
     */
    public function index() {
        require_once __DIR__ . '/../Views/home.php';
    }
}