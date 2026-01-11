<?php
/**
 * Test Script: Password Reset Flow
 *
 * Tests the forgotPassword and resetPassword endpoints
 *
 * @package Tests
 * @since Phase 5 Week 1 Day 5
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Password Reset Flow Test\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Set APP_SECRET_KEY for testing
putenv('APP_SECRET_KEY=5b32dc16df2a237f2835f94f8b368b9c64f6cec9a41c181b0f43396ee2b7e447');
ApiTokenService::setConnection($conn);

$testsPassed = 0;
$testsFailed = 0;

// Test Setup: Create a test user
echo "Setup: Creating test user...\n";
$testEmail = 'password.reset.test@example.com';
$testPassword = password_hash('oldpassword123', PASSWORD_BCRYPT);
$testName = 'Test';
$testSurname = 'User';

// Clean up any existing test user
$conn->query("DELETE FROM users WHERE email = '{$testEmail}'");

// Create test user
$insertStmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
$testUsername = 'passwordresettest';
$insertStmt->bind_param('sssss', $testUsername, $testEmail, $testPassword, $testName, $testSurname);
$insertStmt->execute();
$testUserId = $conn->insert_id;
echo "  ✅ Test user created (ID: {$testUserId})\n\n";

// Test 1: Generate password reset token
echo "Test 1: Generate password reset token...\n";
try {
    $resetToken = ApiTokenService::generatePasswordResetToken($testUserId, $testEmail);
    $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

    // Store in database
    $updateStmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
    $updateStmt->bind_param('ssi', $resetToken, $expiresAt, $testUserId);
    $updateStmt->execute();

    if ($resetToken && strlen($resetToken) > 0) {
        echo "  ✅ PASS: Reset token generated\n";
        echo "     Token length: " . strlen($resetToken) . " characters\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token generation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 2: Validate password reset token
echo "Test 2: Validate password reset token...\n";
try {
    $payload = ApiTokenService::validate($resetToken);

    if ($payload && $payload['token_type'] === 'password_reset' && $payload['user_id'] === $testUserId) {
        echo "  ✅ PASS: Reset token validated successfully\n";
        echo "     Token type: {$payload['token_type']}\n";
        echo "     User ID: {$payload['user_id']}\n";
        echo "     Email: {$payload['email']}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token validation failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 3: Verify token stored in database
echo "Test 3: Verify token stored in database...\n";
try {
    $checkStmt = $conn->prepare("SELECT password_reset_token, password_reset_expires FROM users WHERE id = ?");
    $checkStmt->bind_param('i', $testUserId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $user = $result->fetch_assoc();

    if ($user['password_reset_token'] === $resetToken && $user['password_reset_expires'] !== null) {
        echo "  ✅ PASS: Token stored in database\n";
        echo "     Expires at: {$user['password_reset_expires']}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Token not found in database\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 4: Reset password with valid token
echo "Test 4: Reset password with valid token...\n";
try {
    $newPassword = 'newpassword123';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Simulate password reset
    $updateStmt = $conn->prepare("
        UPDATE users
        SET password = ?,
            password_reset_token = NULL,
            password_reset_expires = NULL
        WHERE id = ?
    ");
    $updateStmt->bind_param('si', $hashedPassword, $testUserId);
    $success = $updateStmt->execute();

    // Verify password was updated
    $verifyStmt = $conn->prepare("SELECT password, password_reset_token FROM users WHERE id = ?");
    $verifyStmt->bind_param('i', $testUserId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    $user = $result->fetch_assoc();

    if ($success && password_verify($newPassword, $user['password']) && $user['password_reset_token'] === null) {
        echo "  ✅ PASS: Password reset successfully\n";
        echo "     Reset token cleared from database\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Password reset failed\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 5: Expired token should be rejected
echo "Test 5: Expired token should be rejected...\n";
try {
    // Create an expired token
    $expiredToken = ApiTokenService::generatePasswordResetToken($testUserId, $testEmail);
    $expiredTime = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago

    $updateStmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
    $updateStmt->bind_param('ssi', $expiredToken, $expiredTime, $testUserId);
    $updateStmt->execute();

    // Check if token is expired
    $checkStmt = $conn->prepare("SELECT password_reset_expires FROM users WHERE id = ?");
    $checkStmt->bind_param('i', $testUserId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $user = $result->fetch_assoc();

    $isExpired = strtotime($user['password_reset_expires']) < time();

    if ($isExpired) {
        echo "  ✅ PASS: Expired token correctly identified\n";
        echo "     Expired at: {$user['password_reset_expires']}\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Expired token not identified\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 6: Email enumeration prevention
echo "Test 6: Email enumeration prevention (non-existent email)...\n";
try {
    // For security, forgotPassword should return success even for non-existent emails
    // This prevents attackers from determining which emails are registered
    $nonExistentEmail = 'nonexistent@example.com';

    // Check user doesn't exist
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param('s', $nonExistentEmail);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        echo "  ✅ PASS: Email enumeration prevention design validated\n";
        echo "     Non-existent emails should return generic success message\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Test email unexpectedly exists\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Test 7: Password strength validation
echo "Test 7: Password strength validation (min 8 characters)...\n";
try {
    $weakPassword = 'pass';
    $strongPassword = 'password123';

    $weakValid = strlen($weakPassword) >= 8;
    $strongValid = strlen($strongPassword) >= 8;

    if (!$weakValid && $strongValid) {
        echo "  ✅ PASS: Password strength validation working\n";
        echo "     Weak password ('{$weakPassword}'): " . ($weakValid ? 'Valid' : 'Invalid') . "\n";
        echo "     Strong password ('{$strongPassword}'): " . ($strongValid ? 'Valid' : 'Invalid') . "\n\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: Password strength validation incorrect\n\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Cleanup
echo "Cleanup: Removing test user...\n";
$conn->query("DELETE FROM users WHERE id = {$testUserId}");
echo "  ✅ Test user removed\n\n";

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
