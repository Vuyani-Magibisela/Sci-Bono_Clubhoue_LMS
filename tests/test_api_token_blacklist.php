<?php
/**
 * Test Script: ApiTokenService Blacklist Functionality
 *
 * Tests the enhanced ApiTokenService with blacklist capabilities.
 *
 * @package Tests
 * @since Phase 5 Week 1 Day 2
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

// Set APP_SECRET_KEY for testing
putenv('APP_SECRET_KEY=5b32dc16df2a237f2835f94f8b368b9c64f6cec9a41c181b0f43396ee2b7e447');

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  ApiTokenService Blacklist Functionality Test\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Set database connection
ApiTokenService::setConnection($conn);

$testUserId = 1;
$testUserRole = 'admin';
$testsPassed = 0;
$testsFailed = 0;

// Test 1: Generate token with JTI
echo "Test 1: Generate token with JTI claim...\n";
try {
    $token = ApiTokenService::generate($testUserId, $testUserRole);
    $jti = ApiTokenService::getTokenJti($token);

    if ($jti && strlen($jti) === 32) { // 16 bytes = 32 hex characters
        echo "  ✅ PASS: Token generated with JTI: {$jti}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token JTI invalid or missing\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 2: Validate token (should pass - not blacklisted)
echo "Test 2: Validate non-blacklisted token...\n";
try {
    $payload = ApiTokenService::validate($token);

    if ($payload && $payload['user_id'] === $testUserId) {
        echo "  ✅ PASS: Token validated successfully\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token validation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 3: Check if token is blacklisted (should be false)
echo "Test 3: Check token blacklist status (should be false)...\n";
try {
    $isBlacklisted = ApiTokenService::isBlacklisted($jti);

    if ($isBlacklisted === false) {
        echo "  ✅ PASS: Token is not blacklisted\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token incorrectly marked as blacklisted\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 4: Blacklist the token
echo "Test 4: Blacklist token...\n";
try {
    $ipAddress = '127.0.0.1';
    $userAgent = 'PHPUnit/Test';
    $success = ApiTokenService::blacklistToken($token, $testUserId, 'test_logout', $ipAddress, $userAgent);

    if ($success) {
        echo "  ✅ PASS: Token blacklisted successfully\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token blacklist operation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 5: Check if token is now blacklisted (should be true)
echo "Test 5: Check token blacklist status (should be true)...\n";
try {
    $isBlacklisted = ApiTokenService::isBlacklisted($jti);

    if ($isBlacklisted === true) {
        echo "  ✅ PASS: Token is blacklisted\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token not found in blacklist\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 6: Validate blacklisted token (should fail)
echo "Test 6: Validate blacklisted token (should fail)...\n";
try {
    $payload = ApiTokenService::validate($token);

    if ($payload === false) {
        echo "  ✅ PASS: Blacklisted token correctly rejected\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Blacklisted token incorrectly accepted\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 7: Token rotation
echo "Test 7: Token rotation...\n";
try {
    $newToken = ApiTokenService::generate($testUserId, $testUserRole);
    $rotatedToken = ApiTokenService::rotateToken($newToken, $testUserId, $testUserRole, 'password_change');

    if ($rotatedToken && $rotatedToken !== $newToken) {
        echo "  ✅ PASS: Token rotated successfully\n\n";
        $testsPassed++;

        // Verify old token is blacklisted
        $oldJti = ApiTokenService::getTokenJti($newToken);
        $isOldBlacklisted = ApiTokenService::isBlacklisted($oldJti);

        if ($isOldBlacklisted) {
            echo "  ✅ PASS: Old token blacklisted after rotation\n\n";
            $testsPassed++;
        } else {
            echo "  ❌ FAIL: Old token not blacklisted after rotation\n\n";
            $testsFailed++;
        }
    } else {
        echo "  ❌ FAIL: Token rotation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 8: Generate fingerprint
echo "Test 8: Generate device fingerprint...\n";
try {
    $fingerprint = ApiTokenService::generateFingerprint([
        'user_agent' => 'Mozilla/5.0',
        'ip_address' => '127.0.0.1'
    ]);

    if ($fingerprint && strlen($fingerprint) === 64) { // SHA256 = 64 hex characters
        echo "  ✅ PASS: Fingerprint generated: {$fingerprint}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Fingerprint invalid\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 9: Validate fingerprint
echo "Test 9: Validate device fingerprint...\n";
try {
    $request = [
        'user_agent' => 'Mozilla/5.0',
        'ip_address' => '127.0.0.1'
    ];

    $storedFingerprint = ApiTokenService::generateFingerprint($request);
    $isValid = ApiTokenService::validateFingerprint($storedFingerprint, $request);

    if ($isValid === true) {
        echo "  ✅ PASS: Fingerprint validated successfully\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Fingerprint validation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 10: Cleanup expired tokens
echo "Test 10: Cleanup expired tokens...\n";
try {
    $removedCount = ApiTokenService::cleanupExpiredTokens();
    echo "  ✅ PASS: Cleanup executed, removed {$removedCount} expired token(s)\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Test Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "✅ Passed: {$testsPassed}\n";
echo "❌ Failed: {$testsFailed}\n";
echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 2) . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 All tests passed!\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Review the output above.\n\n";
    exit(1);
}
