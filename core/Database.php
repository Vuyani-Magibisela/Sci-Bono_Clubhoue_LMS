<?php
/**
 * Enhanced Database Manager with Connection Pooling
 * Phase 5 Implementation
 */

require_once __DIR__ . '/../config/ConfigLoader.php';
require_once __DIR__ . '/Logger.php';

class Database {
    private static $connections = [];
    private static $config = null;
    private static $logger = null;
    private static $connectionPool = [];
    private static $maxConnections = 10;
    private static $currentConnections = 0;
    
    /**
     * Initialize database manager
     */
    public static function initialize($config = null) {
        self::$config = $config ?? ConfigLoader::get('database');
        self::$logger = new Logger();
        
        // Set max connections from config
        self::$maxConnections = self::$config['max_connections'] ?? 10;
    }
    
    /**
     * Get database connection
     */
    public static function connection($name = null) {
        $name = $name ?? self::$config['default'] ?? 'mysql';
        
        // Try to get from pool first
        if ($pooledConnection = self::getFromPool($name)) {
            return $pooledConnection;
        }
        
        // Create new connection if under limit
        if (self::$currentConnections < self::$maxConnections) {
            $connection = self::createConnection($name);
            self::$connections[$name] = $connection;
            self::$currentConnections++;
            
            self::$logger->info("Database connection created", [
                'connection' => $name,
                'total_connections' => self::$currentConnections
            ]);
            
            return $connection;
        }
        
        // Wait for available connection
        throw new Exception("Maximum database connections reached");
    }
    
    /**
     * Create new database connection
     */
    private static function createConnection($name) {
        $config = self::$config['connections'][$name] ?? null;
        
        if (!$config) {
            throw new Exception("Database connection '{$name}' not configured");
        }
        
        // Create connection with retry logic
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $connection = new mysqli(
                    $config['host'],
                    $config['username'],
                    $config['password'],
                    $config['database'],
                    $config['port'] ?? 3306
                );
                
                if ($connection->connect_error) {
                    throw new Exception("Connection failed: " . $connection->connect_error);
                }
                
                // Configure connection
                self::configureConnection($connection, $config);
                
                // Add connection wrapper for monitoring
                return new DatabaseConnection($connection, $name, self::$logger);
                
            } catch (Exception $e) {
                self::$logger->warning("Database connection attempt {$attempt} failed", [
                    'connection' => $name,
                    'error' => $e->getMessage()
                ]);
                
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                
                sleep($retryDelay);
            }
        }
    }
    
    /**
     * Configure database connection
     */
    private static function configureConnection($connection, $config) {
        // Set charset
        $connection->set_charset($config['charset'] ?? 'utf8mb4');
        
        // Set SQL mode
        $sqlMode = $config['sql_mode'] ?? "ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
        $connection->query("SET sql_mode = '{$sqlMode}'");
        
        // Set timezone
        $timezone = $config['timezone'] ?? '+00:00';
        $connection->query("SET time_zone = '{$timezone}'");
        
        // Set wait timeout
        $waitTimeout = $config['wait_timeout'] ?? 28800; // 8 hours
        $connection->query("SET wait_timeout = {$waitTimeout}");
        
        // Enable query logging if configured
        if ($config['log_queries'] ?? false) {
            $connection->query("SET general_log = 'ON'");
        }
    }
    
    /**
     * Get connection from pool
     */
    private static function getFromPool($name) {
        if (isset(self::$connectionPool[$name]) && !empty(self::$connectionPool[$name])) {
            $connection = array_shift(self::$connectionPool[$name]);
            
            // Test connection is still alive
            if ($connection->ping()) {
                return $connection;
            }
        }
        
        return null;
    }
    
    /**
     * Return connection to pool
     */
    public static function releaseConnection($connection, $name) {
        if (!isset(self::$connectionPool[$name])) {
            self::$connectionPool[$name] = [];
        }
        
        // Don't pool too many connections
        if (count(self::$connectionPool[$name]) < 5) {
            self::$connectionPool[$name][] = $connection;
        } else {
            $connection->close();
            self::$currentConnections--;
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAll() {
        // Close active connections
        foreach (self::$connections as $name => $connection) {
            $connection->close();
        }
        
        // Close pooled connections
        foreach (self::$connectionPool as $name => $connections) {
            foreach ($connections as $connection) {
                $connection->close();
            }
        }
        
        self::$connections = [];
        self::$connectionPool = [];
        self::$currentConnections = 0;
        
        self::$logger->info("All database connections closed");
    }
    
    /**
     * Get connection statistics
     */
    public static function getStats() {
        return [
            'active_connections' => self::$currentConnections,
            'max_connections' => self::$maxConnections,
            'pooled_connections' => array_sum(array_map('count', self::$connectionPool)),
            'connection_names' => array_keys(self::$connections)
        ];
    }
    
    /**
     * Health check for database connections
     */
    public static function healthCheck() {
        $results = [];
        
        foreach (self::$config['connections'] as $name => $config) {
            try {
                $connection = self::connection($name);
                $result = $connection->query("SELECT 1 as test");
                
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => null, // Could implement timing
                    'last_checked' => date('Y-m-d H:i:s')
                ];
                
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'last_checked' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $results;
    }
}

/**
 * Database Connection Wrapper for Monitoring
 */
class DatabaseConnection {
    private $connection;
    private $name;
    private $logger;
    private $queryCount = 0;
    private $totalTime = 0;
    
    public function __construct($connection, $name, $logger) {
        $this->connection = $connection;
        $this->name = $name;
        $this->logger = $logger;
    }
    
    public function query($sql, $resultmode = MYSQLI_STORE_RESULT) {
        $startTime = microtime(true);
        
        try {
            $result = $this->connection->query($sql, $resultmode);
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            $this->queryCount++;
            $this->totalTime += $duration;
            
            // Log slow queries
            $slowQueryThreshold = 1.0; // 1 second
            if ($duration > $slowQueryThreshold) {
                $this->logger->warning("Slow query detected", [
                    'connection' => $this->name,
                    'duration' => $duration,
                    'query' => substr($sql, 0, 500) // Log first 500 chars
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("Query error", [
                'connection' => $this->name,
                'error' => $e->getMessage(),
                'query' => substr($sql, 0, 500)
            ]);
            
            throw $e;
        }
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function begin_transaction($flags = 0, $name = null) {
        return $this->connection->begin_transaction($flags, $name);
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function ping() {
        return $this->connection->ping();
    }
    
    public function close() {
        $this->logger->info("Database connection closed", [
            'connection' => $this->name,
            'query_count' => $this->queryCount,
            'total_time' => $this->totalTime,
            'avg_query_time' => $this->queryCount > 0 ? ($this->totalTime / $this->queryCount) : 0
        ]);
        
        return $this->connection->close();
    }
    
    /**
     * Get query statistics
     */
    public function getQueryStats() {
        return [
            'query_count' => $this->queryCount,
            'total_time' => $this->totalTime,
            'avg_query_time' => $this->queryCount > 0 ? ($this->totalTime / $this->queryCount) : 0
        ];
    }
    
    // Delegate other methods to the underlying connection
    public function __call($method, $args) {
        return call_user_func_array([$this->connection, $method], $args);
    }
    
    public function __get($property) {
        return $this->connection->$property;
    }
}