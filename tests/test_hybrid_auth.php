<?php
/**
 * Test Script: Hybrid Authentication (JWT + Sessions)
 *
 * Tests the enhanced AuthMiddleware with both JWT and session-based authentication.
 *
 * @package Tests
 * @since Phase 5 Week 1 Day 3
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Hybrid Authentication Test (JWT + Sessions)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Set APP_SECRET_KEY for testing
putenv('APP_SECRET_KEY=5b32dc16df2a237f2835f94f8b368b9c64f6cec9a41c181b0f43396ee2b7e447');
ApiTokenService::setConnection($conn);

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Session-based authentication (existing functionality)
echo "Test 1: Session-based authentication...\\n";
try {
    // Simulate session-based login
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['last_activity'] = time();

    $middleware = new AuthMiddleware();
    $authenticated = $middleware->handle();

    if ($authenticated && $middleware->getAuthMethod() === 'session') {
        echo "  ✅ PASS: Session authentication successful\n";
        echo "     Auth method: {$middleware->getAuthMethod()}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Session authentication failed\n\n";
        $testsFailed++;
    }

    // Clean up session
    session_destroy();
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 2: JWT authentication (new functionality)
echo "Test 2: JWT authentication...\\n";
try {
    session_start();

    // Generate JWT token
    $testUserId = 2;
    $testUserRole = 'mentor';
    $token = ApiTokenService::generate($testUserId, $testUserRole);

    // Simulate Authorization header
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

    $middleware = new AuthMiddleware();
    $authenticated = $middleware->handle();

    if ($authenticated && $middleware->getAuthMethod() === 'jwt') {
        $user = $middleware->getAuthenticatedUser();
        if ($user['user_id'] === $testUserId && $user['role'] === $testUserRole) {
            echo "  ✅ PASS: JWT authentication successful\n";
            echo "     Auth method: {$middleware->getAuthMethod()}\n";
            echo "     User ID: {$user['user_id']}, Role: {$user['role']}\n\n";
            $testsPassed++;
        } else {
            echo "  ❌ FAIL: JWT payload mismatch\n\n";
            $testsFailed++;
        }
    } else {
        echo "  ❌ FAIL: JWT authentication failed\n\n";
        $testsFailed++;
    }

    // Clean up
    unset($_SERVER['HTTP_AUTHORIZATION']);
    session_destroy();
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 3: Invalid JWT token (should fail)
echo "Test 3: Invalid JWT token (should fail)...\\n";
try {
    session_start();

    // Invalid token
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer invalid.jwt.token";

    $middleware = new AuthMiddleware();
    $authenticated = $middleware->handle();

    if (!$authenticated) {
        echo "  ✅ PASS: Invalid JWT correctly rejected\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Invalid JWT was accepted\n\n";
        $testsFailed++;
    }

    unset($_SERVER['HTTP_AUTHORIZATION']);
    session_destroy();
} catch (Exception $e) {
    echo "  ✅ PASS: Exception thrown for invalid token\n\n";
    $testsPassed++;
}

// Test 4: JWT fallback to session
echo "Test 4: JWT fallback to session authentication...\\n";
try {
    session_start();

    // Set up session auth
    $_SESSION['user_id'] = 3;
    $_SESSION['role'] = 'member';
    $_SESSION['last_activity'] = time();

    // Provide invalid JWT (should fall back to session)
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer expired.jwt.token";

    $middleware = new AuthMiddleware();
    $authenticated = $middleware->handle();

    if ($authenticated && $middleware->getAuthMethod() === 'session') {
        echo "  ✅ PASS: Fallback to session authentication successful\n";
        echo "     Auth method: {$middleware->getAuthMethod()}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Fallback to session failed\n\n";
        $testsFailed++;
    }

    unset($_SERVER['HTTP_AUTHORIZATION']);
    session_destroy();
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 5: Blacklisted JWT token (should fail)
echo "Test 5: Blacklisted JWT token (should fail)...\\n";
try {
    session_start();

    // Generate and blacklist a token
    $testUserId = 4;
    $token = ApiTokenService::generate($testUserId, 'admin');
    ApiTokenService::blacklistToken($token, $testUserId, 'test');

    // Try to authenticate with blacklisted token
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

    $middleware = new AuthMiddleware();
    $authenticated = $middleware->handle();

    if (!$authenticated) {
        echo "  ✅ PASS: Blacklisted JWT correctly rejected\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Blacklisted JWT was accepted\n\n";
        $testsFailed++;
    }

    unset($_SERVER['HTTP_AUTHORIZATION']);
    session_destroy();
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
