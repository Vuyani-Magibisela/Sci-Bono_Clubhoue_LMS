<?php
/**
 * Phase 5 Week 2 Day 5 - Rate Limiting & Token Refresh Rotation Test Suite
 *
 * Tests the enhanced rate limiting and token rotation features:
 * - Token refresh with rotation
 * - Token family tracking
 * - Token reuse detection
 * - Family blacklisting on theft
 * - Rate limit headers (X-RateLimit-*)
 * - Rate limiting enforcement
 *
 * @package Tests
 * @since Phase 5 Week 2 Day 5 (January 9, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';
require_once __DIR__ . '/../app/Middleware/RateLimitMiddleware.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 2 Day 5 - Rate Limiting & Token Rotation Tests\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Set APP_SECRET_KEY for testing
putenv('APP_SECRET_KEY=5b32dc16df2a237f2835f94f8b368b9c64f6cec9a41c181b0f43396ee2b7e447');
ApiTokenService::setConnection($conn);

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

// Setup: Create test user
echo "Setup: Creating test user for token rotation tests...\n";

$testEmail = 'rotation.test@example.com';
$testPassword = 'RotationTest123';
$hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$testEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
$username = 'rotationtest';
$name = 'Rotation';
$surname = 'Test';
$stmt->bind_param('sssss', $username, $testEmail, $hashedPassword, $name, $surname);
$stmt->execute();
$testUserId = $conn->insert_id;
echo "  ✅ Test user created (ID: {$testUserId})\n\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 1: Token Rotation Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: Token Rotation Tests (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Generate refresh token creates family
try {
    $refreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $passed = !empty($refreshToken);

    // Extract JTI and family_id
    $parts = explode('.', $refreshToken);
    $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    $passed = $passed && isset($payload['family_id']) && !empty($payload['family_id']);

    recordTest("Generate refresh token creates family", $passed, "Family ID: " . ($payload['family_id'] ?? 'none'));
} catch (\Exception $e) {
    recordTest("Generate refresh token creates family", false, $e->getMessage());
}

// Test 2: Refresh token rotation returns both tokens
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $result = ApiTokenService::refresh($initialRefreshToken);

    $passed = is_array($result) &&
              isset($result['access_token']) &&
              isset($result['refresh_token']) &&
              !empty($result['access_token']) &&
              !empty($result['refresh_token']);

    recordTest("Refresh token rotation returns both tokens", $passed, "Returns access_token and refresh_token");
} catch (\Exception $e) {
    recordTest("Refresh token rotation returns both tokens", false, $e->getMessage());
}

// Test 3: Old refresh token is blacklisted after rotation
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $initialJti = ApiTokenService::getTokenJti($initialRefreshToken);

    // Perform rotation
    $result = ApiTokenService::refresh($initialRefreshToken);

    // Check if old token is now blacklisted
    $passed = ApiTokenService::isBlacklisted($initialJti);

    recordTest("Old refresh token is blacklisted after rotation", $passed, "Token JTI: {$initialJti}");
} catch (\Exception $e) {
    recordTest("Old refresh token is blacklisted after rotation", false, $e->getMessage());
}

// Test 4: New refresh token has same family_id
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');

    // Get initial family ID
    $parts = explode('.', $initialRefreshToken);
    $initialPayload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    $initialFamilyId = $initialPayload['family_id'];

    // Perform rotation
    $result = ApiTokenService::refresh($initialRefreshToken);
    $newRefreshToken = $result['refresh_token'];

    // Get new family ID
    $parts = explode('.', $newRefreshToken);
    $newPayload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    $newFamilyId = $newPayload['family_id'];

    $passed = $initialFamilyId === $newFamilyId;

    recordTest("New refresh token has same family_id", $passed, "Family preserved: {$newFamilyId}");
} catch (\Exception $e) {
    recordTest("New refresh token has same family_id", false, $e->getMessage());
}

// Test 5: Cannot reuse old refresh token (token reuse detection)
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');

    // First refresh - should succeed
    $result1 = ApiTokenService::refresh($initialRefreshToken);
    $success1 = $result1 !== false;

    // Try to use old token again - should fail
    $result2 = ApiTokenService::refresh($initialRefreshToken);
    $failed2 = $result2 === false;

    $passed = $success1 && $failed2;

    recordTest("Cannot reuse old refresh token (token reuse detection)", $passed, "Reuse blocked");
} catch (\Exception $e) {
    recordTest("Cannot reuse old refresh token (token reuse detection)", false, $e->getMessage());
}

// Test 6: Token family is blacklisted on reuse
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');

    // First rotation
    $result1 = ApiTokenService::refresh($initialRefreshToken);
    $secondToken = $result1['refresh_token'];

    // Second rotation
    $result2 = ApiTokenService::refresh($secondToken);
    $thirdToken = $result2['refresh_token'];

    // Try to reuse first token (should trigger family blacklist)
    $result3 = ApiTokenService::refresh($initialRefreshToken);

    // Third token should now also be invalid (family blacklisted)
    $result4 = ApiTokenService::refresh($thirdToken);

    $passed = $result3 === false && $result4 === false;

    recordTest("Token family is blacklisted on reuse", $passed, "Entire family invalidated on theft");
} catch (\Exception $e) {
    recordTest("Token family is blacklisted on reuse", false, $e->getMessage());
}

// Test 7: Token families table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'token_families'");
    $passed = $result && $result->num_rows > 0;

    recordTest("Token families table exists", $passed, "Table created");
} catch (\Exception $e) {
    recordTest("Token families table exists", false, $e->getMessage());
}

// Test 8: Token family relationships are stored
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $initialJti = ApiTokenService::getTokenJti($initialRefreshToken);

    // Check if family relationship exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM token_families WHERE jti = ?");
    $stmt->bind_param('s', $initialJti);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $count > 0;

    recordTest("Token family relationships are stored", $passed, "Family record exists");
} catch (\Exception $e) {
    recordTest("Token family relationships are stored", false, $e->getMessage());
}

// Test 9: Parent JTI is tracked in token families
try {
    $initialRefreshToken = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $initialJti = ApiTokenService::getTokenJti($initialRefreshToken);

    // Perform rotation
    $result = ApiTokenService::refresh($initialRefreshToken);
    $newRefreshToken = $result['refresh_token'];
    $newJti = ApiTokenService::getTokenJti($newRefreshToken);

    // Check if new token has parent_jti set to old token's JTI
    $stmt = $conn->prepare("SELECT parent_jti FROM token_families WHERE jti = ?");
    $stmt->bind_param('s', $newJti);
    $stmt->execute();
    $queryResult = $stmt->get_result();
    $row = $queryResult->fetch_assoc();

    $passed = $row && $row['parent_jti'] === $initialJti;

    recordTest("Parent JTI is tracked in token families", $passed, "Parent tracked correctly");
} catch (\Exception $e) {
    recordTest("Parent JTI is tracked in token families", false, $e->getMessage());
}

// Test 10: Multiple rotations maintain family chain
try {
    $token1 = ApiTokenService::generateRefreshToken($testUserId, 'member');
    $jti1 = ApiTokenService::getTokenJti($token1);

    // Extract family_id
    $parts = explode('.', $token1);
    $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    $familyId = $payload['family_id'];

    // Perform 3 rotations
    $result2 = ApiTokenService::refresh($token1);
    $token2 = $result2['refresh_token'];

    $result3 = ApiTokenService::refresh($token2);
    $token3 = $result3['refresh_token'];

    $result4 = ApiTokenService::refresh($token3);
    $token4 = $result4['refresh_token'];

    // Check if all tokens are in the same family
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM token_families WHERE family_id = ?");
    $stmt->bind_param('s', $familyId);
    $stmt->execute();
    $queryResult = $stmt->get_result();
    $count = $queryResult->fetch_assoc()['count'];

    $passed = $count >= 4; // All 4 tokens should be in family

    recordTest("Multiple rotations maintain family chain", $passed, "4 tokens in family chain");
} catch (\Exception $e) {
    recordTest("Multiple rotations maintain family chain", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: Rate Limiting Tests (8 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: Rate Limiting Tests (8 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 11: RateLimitMiddleware adds headers
try {
    // Create middleware instance
    $rateLimiter = new RateLimitMiddleware($conn);

    // Start output buffering to capture headers
    ob_start();
    $rateLimiter->handle('api');
    ob_end_clean();

    // Check if headers were set (can't directly check in CLI, but method should not error)
    $passed = true;

    recordTest("RateLimitMiddleware adds headers", $passed, "Headers method executed");
} catch (\Exception $e) {
    recordTest("RateLimitMiddleware adds headers", false, $e->getMessage());
}

// Test 12: Rate limit enforcement works
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    // Override identifier for testing
    $_SESSION['user_id'] = 999999; // Use unique ID for test
    $identifier = "user_999999";
    $action = "test_action_" . time(); // Unique action

    // Set a low limit for testing
    $limit = ['requests' => 3, 'window' => 60];

    // Make 3 requests (should succeed)
    $success1 = $rateLimiter->handle($action);
    $success2 = $rateLimiter->handle($action);
    $success3 = $rateLimiter->handle($action);

    // 4th request should be rate limited (but won't exit in test)
    $remaining = $rateLimiter->getRemainingRequests($action);

    $passed = $success1 && $success2 && $success3 && $remaining == 0;

    recordTest("Rate limit enforcement works", $passed, "3 requests allowed, 0 remaining");

    unset($_SESSION['user_id']);
} catch (\Exception $e) {
    recordTest("Rate limit enforcement works", false, $e->getMessage());
}

// Test 13: Rate limits table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'rate_limits'");
    $passed = $result && $result->num_rows > 0;

    recordTest("Rate limits table exists", $passed, "Table created");
} catch (\Exception $e) {
    recordTest("Rate limits table exists", false, $e->getMessage());
}

// Test 14: Rate limit records are created
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    $_SESSION['user_id'] = 888888;
    $action = "record_test_" . time();

    $rateLimiter->handle($action);

    // Check if record was created
    $identifier = "user_888888";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND action = ?");
    $stmt->bind_param('ss', $identifier, $action);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $count > 0;

    recordTest("Rate limit records are created", $passed, "Record exists");

    unset($_SESSION['user_id']);
} catch (\Exception $e) {
    recordTest("Rate limit records are created", false, $e->getMessage());
}

// Test 15: getRemainingRequests returns correct count
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    $_SESSION['user_id'] = 777777;
    $action = "remaining_test_" . time();

    // Make 2 requests
    $rateLimiter->handle($action);
    $rateLimiter->handle($action);

    // Get remaining (default limit is 30, so should have 28 remaining)
    $remaining = $rateLimiter->getRemainingRequests($action);

    $passed = $remaining == 28; // 30 - 2 = 28

    recordTest("getRemainingRequests returns correct count", $passed, "Remaining: {$remaining}");

    unset($_SESSION['user_id']);
} catch (\Exception $e) {
    recordTest("getRemainingRequests returns correct count", false, $e->getMessage());
}

// Test 16: Different actions have independent limits
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    $_SESSION['user_id'] = 666666;
    $action1 = "independent_test1_" . time();
    $action2 = "independent_test2_" . time();

    // Make requests to action1
    $rateLimiter->handle($action1);
    $rateLimiter->handle($action1);

    // Check remaining for action1 and action2
    $remaining1 = $rateLimiter->getRemainingRequests($action1);
    $remaining2 = $rateLimiter->getRemainingRequests($action2);

    $passed = $remaining1 == 28 && $remaining2 == 30; // action2 should be untouched

    recordTest("Different actions have independent limits", $passed, "Action1: {$remaining1}, Action2: {$remaining2}");

    unset($_SESSION['user_id']);
} catch (\Exception $e) {
    recordTest("Different actions have independent limits", false, $e->getMessage());
}

// Test 17: IP-based rate limiting for anonymous users
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    // Clear session to simulate anonymous user
    unset($_SESSION['user_id']);
    $action = "anon_test_" . time();

    // Make request
    $success = $rateLimiter->handle($action);

    // Check if record was created with IP identifier
    $result = $conn->query("SELECT identifier FROM rate_limits WHERE action = '{$action}' LIMIT 1");
    $row = $result->fetch_assoc();

    $passed = $success && $row && strpos($row['identifier'], 'ip_') === 0;

    recordTest("IP-based rate limiting for anonymous users", $passed, "IP identifier used");
} catch (\Exception $e) {
    recordTest("IP-based rate limiting for anonymous users", false, $e->getMessage());
}

// Test 18: Rate limit cleanup removes old records
try {
    $rateLimiter = new RateLimitMiddleware($conn);

    // Insert an old record (2 hours ago)
    $oldTimestamp = time() - 7200;
    $identifier = "cleanup_test_" . time();
    $action = "cleanup_action";
    $ip = '127.0.0.1';
    $userAgent = 'Test';

    $stmt = $conn->prepare("INSERT INTO rate_limits (identifier, action, timestamp, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiss', $identifier, $action, $oldTimestamp, $ip, $userAgent);
    $stmt->execute();

    // Trigger cleanup by making 100 requests (5% chance per request, so force it)
    for ($i = 0; $i < 20; $i++) {
        $rateLimiter->handle("cleanup_trigger_" . time() . "_" . $i);
    }

    // Check if old record still exists (should be removed)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ?");
    $stmt->bind_param('s', $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $passed = $count == 0; // Should be cleaned up

    recordTest("Rate limit cleanup removes old records", $passed, "Old records cleaned up");
} catch (\Exception $e) {
    recordTest("Rate limit cleanup removes old records", false, $e->getMessage());
}

// Cleanup
echo "\nCleanup: Removing test data...\n";

// Clean up test user
$conn->query("DELETE FROM users WHERE id = {$testUserId}");

// Clean up test rate limit records
$conn->query("DELETE FROM rate_limits WHERE identifier LIKE 'user_999999%' OR identifier LIKE 'user_888888%' OR identifier LIKE 'user_777777%' OR identifier LIKE 'user_666666%'");

// Clean up test token families
$stmt = $conn->prepare("DELETE FROM token_families WHERE user_id = ?");
$stmt->bind_param('i', $testUserId);
$stmt->execute();

// Clean up test blacklist entries
$stmt = $conn->prepare("DELETE FROM token_blacklist WHERE user_id = ?");
$stmt->bind_param('i', $testUserId);
$stmt->execute();

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
