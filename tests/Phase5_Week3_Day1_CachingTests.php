<?php
/**
 * Phase 5 Week 3 Day 1 - HTTP Caching with ETags Test Suite
 *
 * Tests the HTTP caching implementation:
 * - ETag generation
 * - Cache-Control headers
 * - Conditional requests (If-None-Match, If-Modified-Since)
 * - 304 Not Modified responses
 * - Cache invalidation
 *
 * @package Tests
 * @since Phase 5 Week 3 Day 1 (January 9, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Utils/CacheHelper.php';
require_once __DIR__ . '/../app/Middleware/CacheMiddleware.php';

use App\Utils\CacheHelper;
use App\Middleware\CacheMiddleware;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 3 Day 1 - HTTP Caching Tests\n";
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
// SECTION 1: CacheHelper Tests (12 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: CacheHelper Utility Tests (12 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Generate strong ETag
try {
    $content = "Hello World";
    $etag = CacheHelper::generateETag($content);
    $passed = !empty($etag) && strpos($etag, '"') !== false && strpos($etag, 'W/') === false;
    recordTest("Generate strong ETag", $passed, "ETag: {$etag}");
} catch (\Exception $e) {
    recordTest("Generate strong ETag", false, $e->getMessage());
}

// Test 2: Generate weak ETag
try {
    $content = "Hello World";
    $etag = CacheHelper::generateETag($content, true);
    $passed = !empty($etag) && strpos($etag, 'W/"') === 0;
    recordTest("Generate weak ETag", $passed, "Weak ETag: {$etag}");
} catch (\Exception $e) {
    recordTest("Generate weak ETag", false, $e->getMessage());
}

// Test 3: ETag generation is consistent
try {
    $content = "Test Content";
    $etag1 = CacheHelper::generateETag($content);
    $etag2 = CacheHelper::generateETag($content);
    $passed = $etag1 === $etag2;
    recordTest("ETag generation is consistent", $passed, "ETags match");
} catch (\Exception $e) {
    recordTest("ETag generation is consistent", false, $e->getMessage());
}

// Test 4: Different content produces different ETags
try {
    $etag1 = CacheHelper::generateETag("Content A");
    $etag2 = CacheHelper::generateETag("Content B");
    $passed = $etag1 !== $etag2;
    recordTest("Different content produces different ETags", $passed, "ETags differ");
} catch (\Exception $e) {
    recordTest("Different content produces different ETags", false, $e->getMessage());
}

// Test 5: Parse ETag from header
try {
    $headerValue = '"abc123"';
    $parsed = CacheHelper::parseETag($headerValue);
    $passed = $parsed === 'abc123';
    recordTest("Parse ETag from header", $passed, "Parsed: {$parsed}");
} catch (\Exception $e) {
    recordTest("Parse ETag from header", false, $e->getMessage());
}

// Test 6: Parse weak ETag from header
try {
    $headerValue = 'W/"abc123"';
    $parsed = CacheHelper::parseETag($headerValue);
    $passed = $parsed === 'abc123';
    recordTest("Parse weak ETag from header", $passed, "Parsed weak: {$parsed}");
} catch (\Exception $e) {
    recordTest("Parse weak ETag from header", false, $e->getMessage());
}

// Test 7: ETags match correctly
try {
    $etag1 = '"abc123"';
    $etag2 = 'W/"abc123"';
    $passed = CacheHelper::etagsMatch($etag1, $etag2);
    recordTest("ETags match correctly", $passed, "Strong and weak ETags match");
} catch (\Exception $e) {
    recordTest("ETags match correctly", false, $e->getMessage());
}

// Test 8: Generate Cache-Control header
try {
    $config = ['public' => true, 'max_age' => 3600];
    $cacheControl = CacheHelper::generateCacheControl($config);
    $passed = strpos($cacheControl, 'public') !== false && strpos($cacheControl, 'max-age=3600') !== false;
    recordTest("Generate Cache-Control header", $passed, "Cache-Control: {$cacheControl}");
} catch (\Exception $e) {
    recordTest("Generate Cache-Control header", false, $e->getMessage());
}

// Test 9: Generate no-cache header
try {
    $config = ['no_cache' => true, 'no_store' => true];
    $cacheControl = CacheHelper::generateCacheControl($config);
    $passed = strpos($cacheControl, 'no-cache') !== false && strpos($cacheControl, 'no-store') !== false;
    recordTest("Generate no-cache header", $passed, "No-cache: {$cacheControl}");
} catch (\Exception $e) {
    recordTest("Generate no-cache header", false, $e->getMessage());
}

// Test 10: Format Last-Modified timestamp
try {
    $timestamp = 1704816000; // Jan 9, 2024 12:00:00
    $formatted = CacheHelper::formatLastModified($timestamp);
    $passed = strpos($formatted, 'GMT') !== false;
    recordTest("Format Last-Modified timestamp", $passed, "Formatted: {$formatted}");
} catch (\Exception $e) {
    recordTest("Format Last-Modified timestamp", false, $e->getMessage());
}

// Test 11: Check resource was modified
try {
    $lastModified = time();
    $ifModifiedSince = time() - 3600; // 1 hour ago
    $wasModified = CacheHelper::wasModifiedSince($lastModified, $ifModifiedSince);
    $passed = $wasModified === true;
    recordTest("Check resource was modified", $passed, "Resource was modified");
} catch (\Exception $e) {
    recordTest("Check resource was modified", false, $e->getMessage());
}

// Test 12: Check resource was not modified
try {
    $lastModified = time() - 3600; // 1 hour ago
    $ifModifiedSince = time(); // Now
    $wasModified = CacheHelper::wasModifiedSince($lastModified, $ifModifiedSince);
    $passed = $wasModified === false;
    recordTest("Check resource was not modified", $passed, "Resource not modified");
} catch (\Exception $e) {
    recordTest("Check resource was not modified", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: CacheMiddleware Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: CacheMiddleware Tests (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 13: Cache table exists
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $result = $conn->query("SHOW TABLES LIKE 'api_cache_info'");
    $passed = $result && $result->num_rows > 0;
    recordTest("Cache table exists", $passed, "Table created");
} catch (\Exception $e) {
    recordTest("Cache table exists", false, $e->getMessage());
}

// Test 14: Store resource info
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $endpoint = "/test/endpoint/" . time();
    $etag = '"test123"';
    $lastModified = time();

    // Use reflection to access private method
    $reflection = new ReflectionClass($cacheMiddleware);
    $method = $reflection->getMethod('storeResourceInfo');
    $method->setAccessible(true);
    $method->invoke($cacheMiddleware, $endpoint, $etag, $lastModified);

    // Check if stored
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM api_cache_info WHERE endpoint = ?");
    $stmt->bind_param('s', $endpoint);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $count > 0;
    recordTest("Store resource info", $passed, "Resource info stored");
} catch (\Exception $e) {
    recordTest("Store resource info", false, $e->getMessage());
}

// Test 15: Retrieve resource info
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $endpoint = "/test/endpoint2/" . time();
    $etag = '"retrieve123"';
    $lastModified = time();

    // Store resource info
    $reflection = new ReflectionClass($cacheMiddleware);
    $storeMethod = $reflection->getMethod('storeResourceInfo');
    $storeMethod->setAccessible(true);
    $storeMethod->invoke($cacheMiddleware, $endpoint, $etag, $lastModified);

    // Retrieve resource info
    $getMethod = $reflection->getMethod('getResourceInfo');
    $getMethod->setAccessible(true);
    $resourceInfo = $getMethod->invoke($cacheMiddleware, $endpoint);

    $passed = $resourceInfo !== null && isset($resourceInfo['etag']);
    recordTest("Retrieve resource info", $passed, "Resource info retrieved");
} catch (\Exception $e) {
    recordTest("Retrieve resource info", false, $e->getMessage());
}

// Test 16: Invalidate endpoint cache
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $endpoint = "/test/invalidate/" . time();
    $etag = '"invalidate123"';
    $lastModified = time();

    // Store resource info
    $reflection = new ReflectionClass($cacheMiddleware);
    $storeMethod = $reflection->getMethod('storeResourceInfo');
    $storeMethod->setAccessible(true);
    $storeMethod->invoke($cacheMiddleware, $endpoint, $etag, $lastModified);

    // Invalidate
    $success = $cacheMiddleware->invalidateEndpoint($endpoint);

    // Check if removed
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM api_cache_info WHERE endpoint = ?");
    $stmt->bind_param('s', $endpoint);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $success && $count == 0;
    recordTest("Invalidate endpoint cache", $passed, "Cache invalidated");
} catch (\Exception $e) {
    recordTest("Invalidate endpoint cache", false, $e->getMessage());
}

// Test 17: Invalidate cache by pattern
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $timestamp = time();
    $endpoint1 = "/api/users/{$timestamp}/profile";
    $endpoint2 = "/api/users/{$timestamp}/settings";

    // Store multiple endpoints
    $reflection = new ReflectionClass($cacheMiddleware);
    $storeMethod = $reflection->getMethod('storeResourceInfo');
    $storeMethod->setAccessible(true);
    $storeMethod->invoke($cacheMiddleware, $endpoint1, '"test1"', time());
    $storeMethod->invoke($cacheMiddleware, $endpoint2, '"test2"', time());

    // Invalidate by pattern
    $pattern = "/api/users/{$timestamp}/%";
    $success = $cacheMiddleware->invalidatePattern($pattern);

    // Check if removed
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM api_cache_info WHERE endpoint LIKE ?");
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $success && $count == 0;
    recordTest("Invalidate cache by pattern", $passed, "Pattern invalidated");
} catch (\Exception $e) {
    recordTest("Invalidate cache by pattern", false, $e->getMessage());
}

// Test 18: Clear expired cache entries
try {
    $cacheMiddleware = new CacheMiddleware($conn);

    // Insert expired entry
    $endpoint = "/test/expired/" . time();
    $etag = '"expired123"';
    $lastModified = date('Y-m-d H:i:s', time() - 7200); // 2 hours ago
    $expiresAt = date('Y-m-d H:i:s', time() - 3600); // Expired 1 hour ago

    $stmt = $conn->prepare("INSERT INTO api_cache_info (endpoint, etag, last_modified, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $endpoint, $etag, $lastModified, $expiresAt);
    $stmt->execute();

    // Clear expired
    $deleted = $cacheMiddleware->clearExpired();

    $passed = $deleted > 0;
    recordTest("Clear expired cache entries", $passed, "Deleted: {$deleted}");
} catch (\Exception $e) {
    recordTest("Clear expired cache entries", false, $e->getMessage());
}

// Test 19: Get cache statistics
try {
    $cacheMiddleware = new CacheMiddleware($conn);
    $stats = $cacheMiddleware->getStatistics();

    $passed = is_array($stats) && isset($stats['total_endpoints']);
    recordTest("Get cache statistics", $passed, "Stats retrieved");
} catch (\Exception $e) {
    recordTest("Get cache statistics", false, $e->getMessage());
}

// Test 20: shouldCache returns false for non-GET
try {
    $passed = !CacheHelper::shouldCache('/api/users', 'POST');
    recordTest("shouldCache returns false for non-GET", $passed, "POST not cached");
} catch (\Exception $e) {
    recordTest("shouldCache returns false for non-GET", false, $e->getMessage());
}

// Test 21: shouldCache returns false for auth endpoints
try {
    $passed = !CacheHelper::shouldCache('/api/v1/auth/login', 'GET');
    recordTest("shouldCache returns false for auth endpoints", $passed, "Auth not cached");
} catch (\Exception $e) {
    recordTest("shouldCache returns false for auth endpoints", false, $e->getMessage());
}

// Test 22: shouldCache returns true for GET user list
try {
    $passed = CacheHelper::shouldCache('/api/v1/users', 'GET');
    recordTest("shouldCache returns true for GET user list", $passed, "User list cacheable");
} catch (\Exception $e) {
    recordTest("shouldCache returns true for GET user list", false, $e->getMessage());
}

// Cleanup
echo "\nCleanup: Removing test data...\n";
$conn->query("DELETE FROM api_cache_info WHERE endpoint LIKE '/test/%' OR endpoint LIKE '/api/users/%'");
echo "  ✅ Test data cleaned up\n";

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
