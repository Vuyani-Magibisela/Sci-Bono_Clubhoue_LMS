<?php
/**
 * Performance Monitoring Integration Examples
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * Examples showing how to integrate performance monitoring
 * into existing API controllers and application code
 */

require_once __DIR__ . '/../app/Services/PerformanceMonitor.php';
require_once __DIR__ . '/../app/Middleware/PerformanceMiddleware.php';

use App\Services\PerformanceMonitor;
use App\Middleware\PerformanceMiddleware;

// Example 1: Basic API Controller Integration
class ExampleApiControllerWithPerformanceMonitoring
{
    private $db;
    private $performanceMonitor;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->performanceMonitor = PerformanceMonitor::getInstance($db);
        
        // Initialize performance monitoring for this request
        initPerformanceMonitoring($db);
    }
    
    /**
     * Example GET endpoint with performance monitoring
     */
    public function getUsers()
    {
        // Start timing this operation
        $timerId = $this->performanceMonitor->startTimer('get_users_operation', [
            'endpoint' => '/api/users',
            'method' => 'GET'
        ]);
        
        try {
            // Simulate database query
            $queryStart = microtime(true);
            $result = $this->db->query("SELECT * FROM users LIMIT 100");
            $queryDuration = (microtime(true) - $queryStart) * 1000;
            
            // Monitor the database query
            $this->performanceMonitor->monitorDatabaseQuery(
                "SELECT * FROM users LIMIT 100",
                $queryDuration,
                [
                    'table' => 'users',
                    'operation' => 'SELECT',
                    'limit' => 100
                ]
            );
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            // Record custom metrics
            $this->performanceMonitor->recordCustomMetric(
                'users_retrieved',
                count($users),
                'count',
                ['endpoint' => '/api/users']
            );
            
            // Stop timing the operation
            $operationDuration = $this->performanceMonitor->stopTimer($timerId);
            
            // Log successful operation
            if ($operationDuration > 500) { // Log slow operations
                error_log("Slow operation detected: get_users took {$operationDuration}ms");
            }
            
            return [
                'success' => true,
                'data' => $users,
                'meta' => [
                    'count' => count($users),
                    'duration_ms' => round($operationDuration, 2)
                ]
            ];
            
        } catch (Exception $e) {
            // Record error metrics
            $this->performanceMonitor->recordMetric(
                PerformanceMonitor::METRIC_ERROR_RATE,
                'get_users_error',
                1,
                'count',
                [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage()
                ]
            );
            
            throw $e;
        }
    }
    
    /**
     * Example POST endpoint with validation and performance monitoring
     */
    public function createUser($userData)
    {
        $timerId = $this->performanceMonitor->startTimer('create_user_operation');
        
        try {
            // Validate input (record validation time)
            $validationTimer = $this->performanceMonitor->startTimer('user_validation');
            $this->validateUserData($userData);
            $this->performanceMonitor->stopTimer($validationTimer);
            
            // Database insertion with monitoring
            $insertTimer = $this->performanceMonitor->startTimer('user_database_insert');
            
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('sss', $userData['name'], $userData['email'], $hashedPassword);
            
            $queryStart = microtime(true);
            $success = $stmt->execute();
            $queryDuration = (microtime(true) - $queryStart) * 1000;
            
            $this->performanceMonitor->stopTimer($insertTimer);
            
            // Monitor the insert query
            $this->performanceMonitor->monitorDatabaseQuery(
                "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())",
                $queryDuration,
                [
                    'operation' => 'INSERT',
                    'table' => 'users',
                    'affected_rows' => $stmt->affected_rows
                ]
            );
            
            if ($success) {
                $userId = $this->db->insert_id;
                
                // Record successful user creation
                $this->performanceMonitor->recordCustomMetric(
                    'users_created',
                    1,
                    'count',
                    ['method' => 'api']
                );
                
                $operationDuration = $this->performanceMonitor->stopTimer($timerId);
                
                return [
                    'success' => true,
                    'data' => ['user_id' => $userId],
                    'meta' => ['duration_ms' => round($operationDuration, 2)]
                ];
            } else {
                throw new Exception('Failed to create user');
            }
            
        } catch (Exception $e) {
            $this->performanceMonitor->recordCustomMetric(
                'user_creation_errors',
                1,
                'count',
                ['error' => $e->getMessage()]
            );
            
            throw $e;
        }
    }
    
    private function validateUserData($userData)
    {
        // Simulate validation logic
        if (empty($userData['email']) || empty($userData['name'])) {
            throw new Exception('Missing required fields');
        }
        
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
    }
}

// Example 2: Middleware Integration
class ExampleControllerWithMiddleware
{
    private $middleware;
    
    public function __construct($db)
    {
        $this->middleware = new PerformanceMiddleware($db);
    }
    
    /**
     * Handle API request with automatic performance monitoring
     */
    public function handleRequest()
    {
        try {
            // Middleware automatically starts monitoring
            $this->middleware->before();
            
            // Your API logic here
            $response = $this->processRequest();
            $responseCode = 200;
            
            // Middleware automatically records performance metrics
            $this->middleware->after($responseCode, json_encode($response));
            
            return $response;
            
        } catch (Exception $e) {
            // Middleware handles error monitoring
            $this->middleware->onError($e, $_SERVER['REQUEST_URI'] ?? '/unknown');
            
            throw $e;
        }
    }
    
    private function processRequest()
    {
        // Simulate request processing
        usleep(100000); // 100ms delay
        return ['status' => 'success', 'message' => 'Request processed'];
    }
}

// Example 3: Custom Metrics Collection
class ExampleBusinessLogicWithMetrics
{
    private $performanceMonitor;
    
    public function __construct()
    {
        $this->performanceMonitor = PerformanceMonitor::getInstance();
    }
    
    /**
     * Example business operation with custom metrics
     */
    public function processEnrollment($userId, $courseId)
    {
        $timerId = $this->performanceMonitor->startTimer('enrollment_processing', [
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        
        try {
            // Check enrollment capacity
            $capacityTimer = $this->performanceMonitor->startTimer('capacity_check');
            $availableSpots = $this->checkCourseCapacity($courseId);
            $this->performanceMonitor->stopTimer($capacityTimer);
            
            if ($availableSpots <= 0) {
                $this->performanceMonitor->recordCustomMetric(
                    'enrollment_rejections',
                    1,
                    'count',
                    ['reason' => 'capacity_full', 'course_id' => $courseId]
                );
                throw new Exception('Course is full');
            }
            
            // Process payment (if applicable)
            $paymentTimer = $this->performanceMonitor->startTimer('payment_processing');
            $paymentResult = $this->processPayment($userId, $courseId);
            $paymentDuration = $this->performanceMonitor->stopTimer($paymentTimer);
            
            // Record payment metrics
            $this->performanceMonitor->recordCustomMetric(
                'payment_processing_time',
                $paymentDuration,
                'ms',
                ['payment_method' => $paymentResult['method']]
            );
            
            // Create enrollment record
            $enrollmentTimer = $this->performanceMonitor->startTimer('enrollment_creation');
            $enrollmentId = $this->createEnrollment($userId, $courseId);
            $this->performanceMonitor->stopTimer($enrollmentTimer);
            
            // Send confirmation email
            $emailTimer = $this->performanceMonitor->startTimer('confirmation_email');
            $this->sendConfirmationEmail($userId, $courseId);
            $emailDuration = $this->performanceMonitor->stopTimer($emailTimer);
            
            // Record email delivery metrics
            $this->performanceMonitor->recordCustomMetric(
                'email_delivery_time',
                $emailDuration,
                'ms',
                ['email_type' => 'enrollment_confirmation']
            );
            
            // Record successful enrollment
            $totalDuration = $this->performanceMonitor->stopTimer($timerId);
            
            $this->performanceMonitor->recordCustomMetric(
                'successful_enrollments',
                1,
                'count',
                [
                    'course_id' => $courseId,
                    'processing_time_ms' => $totalDuration
                ]
            );
            
            return [
                'success' => true,
                'enrollment_id' => $enrollmentId,
                'processing_time_ms' => round($totalDuration, 2)
            ];
            
        } catch (Exception $e) {
            $this->performanceMonitor->recordCustomMetric(
                'enrollment_failures',
                1,
                'count',
                [
                    'reason' => $e->getMessage(),
                    'course_id' => $courseId,
                    'user_id' => $userId
                ]
            );
            
            throw $e;
        }
    }
    
    private function checkCourseCapacity($courseId)
    {
        // Simulate capacity check
        return rand(0, 10);
    }
    
    private function processPayment($userId, $courseId)
    {
        // Simulate payment processing
        usleep(200000); // 200ms delay
        return ['method' => 'credit_card', 'success' => true];
    }
    
    private function createEnrollment($userId, $courseId)
    {
        // Simulate enrollment creation
        usleep(50000); // 50ms delay
        return rand(1000, 9999);
    }
    
    private function sendConfirmationEmail($userId, $courseId)
    {
        // Simulate email sending
        usleep(150000); // 150ms delay
    }
}

// Example 4: Performance-Aware Database Operations
class ExampleDatabaseOperations
{
    private $db;
    private $performanceMonitor;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->performanceMonitor = PerformanceMonitor::getInstance($db);
    }
    
    /**
     * Optimized query execution with performance monitoring
     */
    public function executeOptimizedQuery($sql, $params = [])
    {
        $queryHash = md5($sql);
        $timerId = $this->performanceMonitor->startTimer("query_{$queryHash}", [
            'query_type' => $this->getQueryType($sql),
            'param_count' => count($params)
        ]);
        
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($params) {
                $types = str_repeat('s', count($params)); // Assume all strings for simplicity
                $stmt->bind_param($types, ...$params);
            }
            
            $queryStart = microtime(true);
            $result = $stmt->execute();
            $queryDuration = (microtime(true) - $queryStart) * 1000;
            
            // Monitor the query
            $this->performanceMonitor->monitorDatabaseQuery($sql, $queryDuration, [
                'param_count' => count($params),
                'affected_rows' => $stmt->affected_rows,
                'query_hash' => $queryHash
            ]);
            
            $operationDuration = $this->performanceMonitor->stopTimer($timerId);
            
            // Log slow queries
            if ($queryDuration > 500) {
                error_log("Slow query detected: {$sql} took {$queryDuration}ms");
            }
            
            return [
                'result' => $result,
                'affected_rows' => $stmt->affected_rows,
                'duration_ms' => round($operationDuration, 2)
            ];
            
        } catch (Exception $e) {
            $this->performanceMonitor->recordCustomMetric(
                'database_errors',
                1,
                'count',
                [
                    'error' => $e->getMessage(),
                    'query_type' => $this->getQueryType($sql)
                ]
            );
            
            throw $e;
        }
    }
    
    /**
     * Batch operation with performance tracking
     */
    public function batchInsert($table, $records)
    {
        $batchSize = 100;
        $totalRecords = count($records);
        $batches = array_chunk($records, $batchSize);
        
        $timerId = $this->performanceMonitor->startTimer('batch_insert', [
            'table' => $table,
            'total_records' => $totalRecords,
            'batch_count' => count($batches)
        ]);
        
        $insertedRecords = 0;
        
        foreach ($batches as $batchIndex => $batch) {
            $batchTimer = $this->performanceMonitor->startTimer("batch_{$batchIndex}");
            
            try {
                // Build batch insert query
                $columns = array_keys($batch[0]);
                $placeholders = str_repeat('?,', count($columns));
                $placeholders = rtrim($placeholders, ',');
                
                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ";
                $values = [];
                
                for ($i = 0; $i < count($batch); $i++) {
                    $sql .= "({$placeholders}),";
                    $values = array_merge($values, array_values($batch[$i]));
                }
                
                $sql = rtrim($sql, ',');
                
                $queryStart = microtime(true);
                $stmt = $this->db->prepare($sql);
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);
                $result = $stmt->execute();
                $queryDuration = (microtime(true) - $queryStart) * 1000;
                
                if ($result) {
                    $insertedRecords += $stmt->affected_rows;
                }
                
                // Monitor batch performance
                $this->performanceMonitor->recordCustomMetric(
                    'batch_insert_performance',
                    $queryDuration,
                    'ms',
                    [
                        'batch_size' => count($batch),
                        'batch_index' => $batchIndex,
                        'records_inserted' => $stmt->affected_rows
                    ]
                );
                
                $this->performanceMonitor->stopTimer($batchTimer);
                
            } catch (Exception $e) {
                $this->performanceMonitor->recordCustomMetric(
                    'batch_insert_errors',
                    1,
                    'count',
                    [
                        'batch_index' => $batchIndex,
                        'error' => $e->getMessage()
                    ]
                );
                
                throw $e;
            }
        }
        
        $totalDuration = $this->performanceMonitor->stopTimer($timerId);
        
        $this->performanceMonitor->recordCustomMetric(
            'batch_insert_completed',
            1,
            'count',
            [
                'total_records' => $totalRecords,
                'inserted_records' => $insertedRecords,
                'duration_ms' => $totalDuration,
                'records_per_second' => round($insertedRecords / ($totalDuration / 1000), 2)
            ]
        );
        
        return [
            'success' => true,
            'total_records' => $totalRecords,
            'inserted_records' => $insertedRecords,
            'duration_ms' => round($totalDuration, 2),
            'records_per_second' => round($insertedRecords / ($totalDuration / 1000), 2)
        ];
    }
    
    private function getQueryType($sql)
    {
        $sql = trim(strtoupper($sql));
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        return 'OTHER';
    }
}

// Example 5: Memory and Resource Monitoring
class ExampleResourceMonitoring
{
    private $performanceMonitor;
    
    public function __construct()
    {
        $this->performanceMonitor = PerformanceMonitor::getInstance();
    }
    
    /**
     * Process large dataset with memory monitoring
     */
    public function processLargeDataset($data)
    {
        $timerId = $this->performanceMonitor->startTimer('large_dataset_processing', [
            'dataset_size' => count($data)
        ]);
        
        $initialMemory = memory_get_usage(true);
        $peakMemory = $initialMemory;
        $checkInterval = 1000; // Check memory every 1000 iterations
        
        $processedItems = 0;
        $batchResults = [];
        
        foreach ($data as $index => $item) {
            // Process individual item
            $itemResult = $this->processItem($item);
            $batchResults[] = $itemResult;
            $processedItems++;
            
            // Monitor memory usage periodically
            if ($processedItems % $checkInterval === 0) {
                $currentMemory = memory_get_usage(true);
                $peakMemory = max($peakMemory, $currentMemory);
                
                $this->performanceMonitor->recordCustomMetric(
                    'processing_memory_usage',
                    $currentMemory,
                    'bytes',
                    [
                        'items_processed' => $processedItems,
                        'batch_progress' => round(($processedItems / count($data)) * 100, 2)
                    ]
                );
                
                // Check for memory threshold
                $memoryPercent = $this->getMemoryUsagePercent();
                if ($memoryPercent > 80) {
                    $this->performanceMonitor->recordCustomMetric(
                        'memory_warning',
                        $memoryPercent,
                        'percent',
                        ['items_processed' => $processedItems]
                    );
                    
                    // Optionally flush results to reduce memory usage
                    $this->flushResults($batchResults);
                    $batchResults = [];
                    gc_collect_cycles(); // Force garbage collection
                }
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;
        
        $totalDuration = $this->performanceMonitor->stopTimer($timerId);
        
        // Record final metrics
        $this->performanceMonitor->recordCustomMetric(
            'dataset_processing_completed',
            1,
            'count',
            [
                'items_processed' => $processedItems,
                'memory_used_mb' => round($memoryUsed / (1024 * 1024), 2),
                'peak_memory_mb' => round($peakMemory / (1024 * 1024), 2),
                'duration_ms' => $totalDuration,
                'items_per_second' => round($processedItems / ($totalDuration / 1000), 2)
            ]
        );
        
        return [
            'success' => true,
            'items_processed' => $processedItems,
            'duration_ms' => round($totalDuration, 2),
            'memory_used_mb' => round($memoryUsed / (1024 * 1024), 2),
            'items_per_second' => round($processedItems / ($totalDuration / 1000), 2)
        ];
    }
    
    private function processItem($item)
    {
        // Simulate item processing
        usleep(1000); // 1ms processing time
        return ['processed' => true, 'data' => $item];
    }
    
    private function flushResults($results)
    {
        // Simulate flushing results to storage/database
        $this->performanceMonitor->recordCustomMetric(
            'batch_results_flushed',
            count($results),
            'count'
        );
    }
    
    private function getMemoryUsagePercent()
    {
        $usage = memory_get_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return $limit > 0 ? ($usage / $limit) * 100 : 0;
    }
    
    private function parseMemoryLimit($limit)
    {
        if ($limit === '-1') return -1;
        
        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}

// Usage Examples:

// Example: Initialize performance monitoring for an API endpoint
function exampleApiEndpoint()
{
    // Include database connection
    require_once __DIR__ . '/../server.php';
    global $mysqli;
    
    // Initialize performance monitoring
    initPerformanceMonitoring($mysqli);
    
    try {
        $controller = new ExampleApiControllerWithPerformanceMonitoring($mysqli);
        $response = $controller->getUsers();
        
        // Finish performance monitoring
        finishPerformanceMonitoring(200, json_encode($response));
        
        return $response;
        
    } catch (Exception $e) {
        finishPerformanceMonitoring(500, json_encode(['error' => $e->getMessage()]));
        throw $e;
    }
}

// Example: Use performance monitoring in a standalone script
function exampleStandaloneScript()
{
    require_once __DIR__ . '/../server.php';
    global $mysqli;
    
    $resourceMonitor = new ExampleResourceMonitoring();
    
    // Generate test data
    $testData = array_fill(0, 10000, ['id' => 1, 'name' => 'Test Item']);
    
    // Process with monitoring
    $result = $resourceMonitor->processLargeDataset($testData);
    
    echo "Processing completed:\n";
    echo "- Items processed: {$result['items_processed']}\n";
    echo "- Duration: {$result['duration_ms']}ms\n";
    echo "- Memory used: {$result['memory_used_mb']}MB\n";
    echo "- Items per second: {$result['items_per_second']}\n";
}

// Example: Global performance monitoring functions usage
function exampleGlobalFunctions()
{
    // Record custom metric
    recordPerformanceMetric('custom_operation_count', 1, 'count', [
        'operation_type' => 'data_export',
        'user_type' => 'admin'
    ]);
    
    // Use performance timer
    $timerId = startPerformanceTimer('file_processing', [
        'file_type' => 'csv',
        'file_size' => 1024000
    ]);
    
    // Simulate work
    usleep(500000); // 500ms
    
    $duration = stopPerformanceTimer($timerId, [
        'lines_processed' => 1000
    ]);
    
    echo "Operation completed in {$duration}ms\n";
}

echo "Performance Monitoring Integration Examples\n";
echo "==========================================\n";
echo "This file contains examples of how to integrate performance monitoring\n";
echo "into your Sci-Bono LMS application code.\n";
echo "\nTo see examples in action, uncomment and run the example functions below:\n";

// Uncomment to run examples:
// exampleApiEndpoint();
// exampleStandaloneScript();
// exampleGlobalFunctions();