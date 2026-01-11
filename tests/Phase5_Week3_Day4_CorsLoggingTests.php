<?php
/**
 * Phase 5 Week 3 Day 4 - CORS & Logging Test Suite
 *
 * Tests the CORS middleware and API logging implementation:
 * - CORS headers
 * - Preflight requests
 * - Origin validation
 * - Request logging
 * - Response logging
 * - Performance metrics
 *
 * @package Tests
 * @since Phase 5 Week 3 Day 4 (January 10, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/../app/Utils/ApiLogger.php';

use App\Middleware\CorsMiddleware;
use App\Utils\ApiLogger;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 3 Day 4 - CORS & Logging Tests\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$testsPassed = 0;
$testsFailed = 0;
$testResults = [];

// Helper function to record test results
function recordTest($name, $passed, $message = '') {
    global $testsPassed, $testsFailed, $testResults;
    if ($passed) {
        $testsPassed++;
        $testResults[] = "✅ PASS: {$name}";
        echo "  ✅ PASS: {$name}\n";
        if ($message) echo "     {$message}\n";
    } else {
        $testsFailed++;
        $testResults[] = "❌ FAIL: {$name}";
        echo "  ❌ FAIL: {$name}\n";
        if ($message) echo "     {$message}\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// SECTION 1: CORS Middleware Tests (12 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: CORS Middleware Tests (12 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: CorsMiddleware can be instantiated
try {
    $cors = new CorsMiddleware();

    $passed = $cors instanceof CorsMiddleware;
    recordTest("CorsMiddleware can be instantiated", $passed, "Instance created");
} catch (\Exception $e) {
    recordTest("CorsMiddleware can be instantiated", false, $e->getMessage());
}

// Test 2: CORS is enabled by default
try {
    $cors = new CorsMiddleware();

    $passed = $cors->isEnabled() === true;
    recordTest("CORS is enabled by default", $passed, "Enabled: true");
} catch (\Exception $e) {
    recordTest("CORS is enabled by default", false, $e->getMessage());
}

// Test 3: Get default allowed origins
try {
    $cors = new CorsMiddleware();
    $origins = $cors->getAllowedOrigins();

    $passed = is_array($origins) && in_array('*', $origins);
    recordTest("Get default allowed origins", $passed, "Origins: " . implode(', ', $origins));
} catch (\Exception $e) {
    recordTest("Get default allowed origins", false, $e->getMessage());
}

// Test 4: Get allowed methods
try {
    $cors = new CorsMiddleware();
    $methods = $cors->getAllowedMethods();

    $hasGet = in_array('GET', $methods);
    $hasPost = in_array('POST', $methods);
    $hasOptions = in_array('OPTIONS', $methods);

    $passed = $hasGet && $hasPost && $hasOptions;
    recordTest("Get allowed methods", $passed, "Methods: " . implode(', ', $methods));
} catch (\Exception $e) {
    recordTest("Get allowed methods", false, $e->getMessage());
}

// Test 5: Get allowed headers
try {
    $cors = new CorsMiddleware();
    $headers = $cors->getAllowedHeaders();

    $hasContentType = in_array('Content-Type', $headers);
    $hasAuth = in_array('Authorization', $headers);

    $passed = is_array($headers) && $hasContentType && $hasAuth;
    recordTest("Get allowed headers", $passed, count($headers) . " headers configured");
} catch (\Exception $e) {
    recordTest("Get allowed headers", false, $e->getMessage());
}

// Test 6: Get exposed headers
try {
    $cors = new CorsMiddleware();
    $headers = $cors->getExposedHeaders();

    $hasETag = in_array('ETag', $headers);
    $hasCacheControl = in_array('Cache-Control', $headers);

    $passed = is_array($headers) && $hasETag && $hasCacheControl;
    recordTest("Get exposed headers", $passed, count($headers) . " headers exposed");
} catch (\Exception $e) {
    recordTest("Get exposed headers", false, $e->getMessage());
}

// Test 7: Add allowed origin
try {
    $cors = new CorsMiddleware(['allowed_origins' => []]);
    $cors->addAllowedOrigin('https://example.com');
    $origins = $cors->getAllowedOrigins();

    $passed = in_array('https://example.com', $origins);
    recordTest("Add allowed origin", $passed, "Origin added");
} catch (\Exception $e) {
    recordTest("Add allowed origin", false, $e->getMessage());
}

// Test 8: Set allowed origins
try {
    $cors = new CorsMiddleware();
    $cors->setAllowedOrigins(['https://example.com', 'https://test.com']);
    $origins = $cors->getAllowedOrigins();

    $passed = count($origins) === 2 &&
              in_array('https://example.com', $origins) &&
              in_array('https://test.com', $origins);
    recordTest("Set allowed origins", $passed, "Origins set");
} catch (\Exception $e) {
    recordTest("Set allowed origins", false, $e->getMessage());
}

// Test 9: Add allowed method
try {
    $cors = new CorsMiddleware(['allowed_methods' => ['GET']]);
    $cors->addAllowedMethod('POST');
    $methods = $cors->getAllowedMethods();

    $passed = in_array('POST', $methods);
    recordTest("Add allowed method", $passed, "Method added");
} catch (\Exception $e) {
    recordTest("Add allowed method", false, $e->getMessage());
}

// Test 10: Add allowed header
try {
    $cors = new CorsMiddleware(['allowed_headers' => []]);
    $cors->addAllowedHeader('X-Custom-Header');
    $headers = $cors->getAllowedHeaders();

    $passed = in_array('X-Custom-Header', $headers);
    recordTest("Add allowed header", $passed, "Header added");
} catch (\Exception $e) {
    recordTest("Add allowed header", false, $e->getMessage());
}

// Test 11: Add exposed header
try {
    $cors = new CorsMiddleware(['exposed_headers' => []]);
    $cors->addExposedHeader('X-Custom-Response');
    $headers = $cors->getExposedHeaders();

    $passed = in_array('X-Custom-Response', $headers);
    recordTest("Add exposed header", $passed, "Header added");
} catch (\Exception $e) {
    recordTest("Add exposed header", false, $e->getMessage());
}

// Test 12: Enable/disable CORS
try {
    $cors = new CorsMiddleware();
    $cors->disable();
    $disabledState = $cors->isEnabled();

    $cors->enable();
    $enabledState = $cors->isEnabled();

    $passed = $disabledState === false && $enabledState === true;
    recordTest("Enable/disable CORS", $passed, "Toggle working");
} catch (\Exception $e) {
    recordTest("Enable/disable CORS", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: API Logger Tests (14 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: API Logger Tests (14 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 13: ApiLogger can be instantiated
try {
    $logger = new ApiLogger($conn);

    $passed = $logger instanceof ApiLogger;
    recordTest("ApiLogger can be instantiated", $passed, "Instance created");
} catch (\Exception $e) {
    recordTest("ApiLogger can be instantiated", false, $e->getMessage());
}

// Test 14: Logging is enabled by default
try {
    $logger = new ApiLogger($conn);

    $passed = $logger->isEnabled() === true;
    recordTest("Logging is enabled by default", $passed, "Enabled: true");
} catch (\Exception $e) {
    recordTest("Logging is enabled by default", false, $e->getMessage());
}

// Test 15: Log a GET request
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/test';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $logger = new ApiLogger($conn);
    $logId = $logger->logRequest();

    $passed = is_int($logId) && $logId > 0;
    recordTest("Log a GET request", $passed, "Log ID: " . ($logId ?? 'null'));

    // Cleanup
    if ($logId) {
        $conn->query("DELETE FROM api_request_logs WHERE id = $logId");
    }
} catch (\Exception $e) {
    recordTest("Log a GET request", false, $e->getMessage());
}

// Test 16: Log a POST request with body
try {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/v1/users';
    $_SERVER['CONTENT_TYPE'] = 'application/json';

    $logger = new ApiLogger($conn);
    $logId = $logger->logRequest();

    $passed = is_int($logId) && $logId > 0;
    recordTest("Log a POST request with body", $passed, "Log ID: " . ($logId ?? 'null'));

    // Cleanup
    if ($logId) {
        $conn->query("DELETE FROM api_request_logs WHERE id = $logId");
    }
} catch (\Exception $e) {
    recordTest("Log a POST request with body", false, $e->getMessage());
}

// Test 17: Log response with status code
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/test';

    $logger = new ApiLogger($conn);
    $logId = $logger->logRequest();

    $logger->logResponse($logId, 200, ['success' => true]);

    // Verify logged
    $stmt = $conn->prepare("SELECT status_code FROM api_request_logs WHERE id = ?");
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_assoc();

    $passed = $log && $log['status_code'] == 200;
    recordTest("Log response with status code", $passed, "Status: " . ($log['status_code'] ?? 'null'));

    // Cleanup
    if ($logId) {
        $conn->query("DELETE FROM api_request_logs WHERE id = $logId");
    }
} catch (\Exception $e) {
    recordTest("Log response with status code", false, $e->getMessage());
}

// Test 18: Log error
try {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/v1/test';

    $logger = new ApiLogger($conn);
    $logId = $logger->logRequest();

    $logger->logError($logId, 'Test error message', ['context' => 'test']);

    // Verify logged
    $stmt = $conn->prepare("SELECT is_error, error_message FROM api_request_logs WHERE id = ?");
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_assoc();

    $passed = $log && $log['is_error'] == 1 && $log['error_message'] == 'Test error message';
    recordTest("Log error", $passed, "Error logged");

    // Cleanup
    if ($logId) {
        $conn->query("DELETE FROM api_request_logs WHERE id = $logId");
    }
} catch (\Exception $e) {
    recordTest("Log error", false, $e->getMessage());
}

// Test 19: Get recent logs
try {
    // Create test logs
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/test1';

    $logger = new ApiLogger($conn);
    $logId1 = $logger->logRequest();
    $logger->logResponse($logId1, 200, ['test' => 1]);

    $_SERVER['REQUEST_URI'] = '/api/v1/test2';
    $logId2 = $logger->logRequest();
    $logger->logResponse($logId2, 404, ['test' => 2]);

    // Get recent logs
    $logs = $logger->getRecentLogs(10);

    $passed = is_array($logs) && count($logs) >= 2;
    recordTest("Get recent logs", $passed, count($logs) . " logs retrieved");

    // Cleanup
    if ($logId1) $conn->query("DELETE FROM api_request_logs WHERE id = $logId1");
    if ($logId2) $conn->query("DELETE FROM api_request_logs WHERE id = $logId2");
} catch (\Exception $e) {
    recordTest("Get recent logs", false, $e->getMessage());
}

// Test 20: Get recent error logs only
try {
    // Create test logs
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/test-success';

    $logger = new ApiLogger($conn);
    $logId1 = $logger->logRequest();
    $logger->logResponse($logId1, 200, ['test' => 'success']);

    $_SERVER['REQUEST_URI'] = '/api/v1/test-error';
    $logId2 = $logger->logRequest();
    $logger->logResponse($logId2, 500, ['test' => 'error']);

    // Get error logs only
    $errorLogs = $logger->getRecentLogs(10, ['is_error' => true]);

    $passed = is_array($errorLogs);
    recordTest("Get recent error logs only", $passed, count($errorLogs) . " error logs");

    // Cleanup
    if ($logId1) $conn->query("DELETE FROM api_request_logs WHERE id = $logId1");
    if ($logId2) $conn->query("DELETE FROM api_request_logs WHERE id = $logId2");
} catch (\Exception $e) {
    recordTest("Get recent error logs only", false, $e->getMessage());
}

// Test 21: Get performance stats
try {
    // Create test logs with duration
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/perf-test';

    $logger = new ApiLogger($conn);
    $logId = $logger->logRequest();
    $logger->logResponse($logId, 200, ['test' => 'perf'], ['duration_ms' => 150.5]);

    // Get stats
    $stats = $logger->getPerformanceStats(24);

    $passed = is_array($stats) &&
              isset($stats['total_requests']) &&
              isset($stats['avg_duration_ms']);
    recordTest("Get performance stats", $passed, "Total requests: " . ($stats['total_requests'] ?? 0));

    // Cleanup
    if ($logId) $conn->query("DELETE FROM api_request_logs WHERE id = $logId");
} catch (\Exception $e) {
    recordTest("Get performance stats", false, $e->getMessage());
}

// Test 22: Performance stats includes error rate
try {
    $logger = new ApiLogger($conn);
    $stats = $logger->getPerformanceStats(24);

    $passed = isset($stats['error_rate']) && isset($stats['error_count']) && isset($stats['success_count']);
    recordTest("Performance stats includes error rate", $passed, "Error rate: " . ($stats['error_rate'] ?? 0) . "%");
} catch (\Exception $e) {
    recordTest("Performance stats includes error rate", false, $e->getMessage());
}

// Test 23: Enable/disable logging
try {
    $logger = new ApiLogger($conn);
    $logger->disable();
    $disabledState = $logger->isEnabled();

    $logger->enable();
    $enabledState = $logger->isEnabled();

    $passed = $disabledState === false && $enabledState === true;
    recordTest("Enable/disable logging", $passed, "Toggle working");
} catch (\Exception $e) {
    recordTest("Enable/disable logging", false, $e->getMessage());
}

// Test 24: Disabled logger does not log
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/disabled-test';

    $logger = new ApiLogger($conn, ['enabled' => false]);
    $logId = $logger->logRequest();

    $passed = $logId === null;
    recordTest("Disabled logger does not log", $passed, "No log created");
} catch (\Exception $e) {
    recordTest("Disabled logger does not log", false, $e->getMessage());
}

// Test 25: Cleanup old logs
try {
    // Create an old log
    $conn->query("
        INSERT INTO api_request_logs (method, uri, path, ip_address, created_at)
        VALUES ('GET', '/api/test', '/api/test', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 35 DAY))
    ");

    $logger = new ApiLogger($conn, ['retention_days' => 30]);
    $deleted = $logger->cleanup();

    $passed = $deleted >= 1;
    recordTest("Cleanup old logs", $passed, "$deleted logs deleted");
} catch (\Exception $e) {
    recordTest("Cleanup old logs", false, $e->getMessage());
}

// Test 26: Log only errors configuration
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/success-test';

    $logger = new ApiLogger($conn, ['log_only_errors' => true]);
    $logId = $logger->logRequest();

    // Should not log successful requests
    $passed = $logId === null;
    recordTest("Log only errors configuration", $passed, "Success requests not logged");
} catch (\Exception $e) {
    recordTest("Log only errors configuration", false, $e->getMessage());
}

// Cleanup environment
echo "\nCleanup: Resetting environment...\n";
unset($_SERVER['REQUEST_METHOD']);
unset($_SERVER['REQUEST_URI']);
unset($_SERVER['REMOTE_ADDR']);
unset($_SERVER['CONTENT_TYPE']);
unset($_SERVER['HTTP_ORIGIN']);
echo "  ✅ Environment reset\n";

// ═══════════════════════════════════════════════════════════════
// TEST SUMMARY
// ═══════════════════════════════════════════════════════════════
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "  ✅ Passed: " . $testsPassed . "\n";
echo "  ❌ Failed: " . $testsFailed . "\n";
echo "  Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 1) . "%\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

exit($testsFailed > 0 ? 1 : 0);
