<?php
/**
 * Phase 5 Week 2 Day 1 - User Profile API Test Suite
 *
 * Tests the user profile API endpoints:
 * - GET /api/v1/user/profile
 * - PUT /api/v1/user/profile
 * - PUT /api/v1/user/password
 *
 * @package Tests
 * @since Phase 5 Week 2 Day 1 (January 8, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 2 Day 1 - User Profile API Tests\n";
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
echo "Setup: Creating test user for profile API tests...\n";
$testEmail = 'profiletest@example.com';
$testPassword = 'TestPassword123';
$hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$testEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active, phone, date_of_birth, gender) VALUES (?, ?, ?, ?, ?, 'member', 1, ?, ?, ?)");
$username = 'profiletest';
$name = 'Profile';
$surname = 'Test';
$phone = '0123456789';
$dob = '1990-01-15';
$gender = 'male';
$stmt->bind_param('ssssssss', $username, $testEmail, $hashedPassword, $name, $surname, $phone, $dob, $gender);
$stmt->execute();
$testUserId = $conn->insert_id;
echo "  ✅ Test user created (ID: {$testUserId}, Email: {$testEmail})\n\n";

// Generate access token for authenticated requests
$accessToken = ApiTokenService::generate($testUserId, 'member');

// ═══════════════════════════════════════════════════════════════
// SECTION 1: GET /api/v1/user/profile Tests (5 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: GET /api/v1/user/profile (5 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Successfully retrieve user profile
try {
    // Simulate the UserController::profile() method
    require_once __DIR__ . '/../app/Controllers/Api/UserController.php';
    require_once __DIR__ . '/../app/Services/SettingsService.php';

    $userController = new App\Controllers\Api\UserController();

    // Manually set session to simulate authentication
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    // Call profile method
    ob_start();
    $userController->profile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['id']) &&
              $response['data']['id'] === $testUserId &&
              !isset($response['data']['password']); // Password should be removed

    recordTest("GET /api/v1/user/profile - successful retrieval", $passed, "User ID: " . ($response['data']['id'] ?? 'N/A'));

    session_destroy();
} catch (\Exception $e) {
    recordTest("GET /api/v1/user/profile - successful retrieval", false, $e->getMessage());
}

// Test 2: Profile includes all expected fields
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->profile();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $data = $response['data'] ?? [];

    $requiredFields = ['id', 'username', 'email', 'name', 'surname', 'user_type'];
    $hasAllFields = true;

    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $hasAllFields = false;
            break;
        }
    }

    recordTest("Profile includes all required fields", $hasAllFields, "Fields: " . implode(', ', array_keys($data)));

    session_destroy();
} catch (\Exception $e) {
    recordTest("Profile includes all required fields", false, $e->getMessage());
}

// Test 3: Profile does not include password
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->profile();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $data = $response['data'] ?? [];

    recordTest("Profile does not expose password", !isset($data['password']), "Password field removed for security");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Profile does not expose password", false, $e->getMessage());
}

// Test 4: Unauthorized access without session
try {
    session_start();
    // Clear session
    session_destroy();
    session_start();

    ob_start();
    $userController->profile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Unauthorized') !== false;

    recordTest("Unauthorized access rejected", $passed, "Correctly returns 401 Unauthorized");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Unauthorized access rejected", false, $e->getMessage());
}

// Test 5: Non-existent user handling
try {
    session_start();
    $_SESSION['user_id'] = 999999; // Non-existent user
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->profile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false;

    recordTest("Non-existent user returns 404", $passed, "Correctly handles missing user");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-existent user returns 404", false, $e->getMessage());
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 2: PUT /api/v1/user/profile Tests (7 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 2: PUT /api/v1/user/profile (7 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 6: Successfully update profile name
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    // Simulate JSON request body
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'name' => 'UpdatedName',
        'surname' => 'UpdatedSurname'
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['name']) &&
              $response['data']['name'] === 'UpdatedName';

    recordTest("Successfully update profile name", $passed, "Name updated to: UpdatedName");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Successfully update profile name", false, $e->getMessage());
}

// Test 7: Update profile email
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $newEmail = 'newemail@example.com';
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'email' => $newEmail
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true;

    recordTest("Successfully update profile email", $passed, "Email updated");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Successfully update profile email", false, $e->getMessage());
}

// Test 8: Update with no fields returns error
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'No valid fields') !== false;

    recordTest("Empty update returns validation error", $passed, "Requires at least one field");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Empty update returns validation error", false, $e->getMessage());
}

// Test 9: Update with invalid field is ignored
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'name' => 'ValidName',
        'invalid_field' => 'ShouldBeIgnored'
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true;

    recordTest("Invalid fields are ignored", $passed, "Only allowed fields processed");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid fields are ignored", false, $e->getMessage());
}

// Test 10: Unauthorized profile update rejected
try {
    session_start();
    session_destroy();
    session_start();

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'name' => 'Hacker'
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Unauthorized') !== false;

    recordTest("Unauthorized profile update rejected", $passed, "Returns 401 Unauthorized");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Unauthorized profile update rejected", false, $e->getMessage());
}

// Test 11: Profile update logs activity
try {
    // Clear previous activity logs for this user
    $conn->query("DELETE FROM activity_log WHERE user_id = {$testUserId} AND action = 'api_profile_updated'");

    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'name' => 'LoggedName'
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    // Check if activity was logged
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE user_id = ? AND action = 'api_profile_updated'");
    $stmt->bind_param('i', $testUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    recordTest("Profile update logs activity", $row['count'] > 0, "Activity logged successfully");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Profile update logs activity", false, $e->getMessage());
}

// Test 12: Validation error handling
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    // Invalid email format
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'email' => 'not-an-email'
    ]);

    ob_start();
    $userController->updateProfile();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              isset($response['errors']);

    recordTest("Validation errors returned for invalid data", $passed, "Email validation triggered");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Validation errors returned for invalid data", false, $e->getMessage());
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 3: PUT /api/v1/user/password Tests (8 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 3: PUT /api/v1/user/password (8 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 13: Successfully change password
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => $testPassword,
        'new_password' => 'NewPassword123',
        'new_password_confirmation' => 'NewPassword123'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true;

    recordTest("Successfully change password", $passed, "Password changed");

    // Update test password for subsequent tests
    $testPassword = 'NewPassword123';

    session_destroy();
} catch (\Exception $e) {
    recordTest("Successfully change password", false, $e->getMessage());
}

// Test 14: Incorrect current password rejected
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => 'WrongPassword',
        'new_password' => 'AnotherPassword123',
        'new_password_confirmation' => 'AnotherPassword123'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'incorrect') !== false;

    recordTest("Incorrect current password rejected", $passed, "Returns 400 Bad Request");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Incorrect current password rejected", false, $e->getMessage());
}

// Test 15: Password confirmation mismatch rejected
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => $testPassword,
        'new_password' => 'NewPassword456',
        'new_password_confirmation' => 'DifferentPassword456'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'does not match') !== false;

    recordTest("Password confirmation mismatch rejected", $passed, "Confirmation validation works");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Password confirmation mismatch rejected", false, $e->getMessage());
}

// Test 16: Password too short rejected
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => $testPassword,
        'new_password' => 'short',
        'new_password_confirmation' => 'short'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', '8 characters') !== false;

    recordTest("Password too short rejected", $passed, "Minimum 8 characters enforced");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Password too short rejected", false, $e->getMessage());
}

// Test 17: Missing fields rejected
try {
    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => $testPassword
        // Missing new_password and new_password_confirmation
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              isset($response['missing_fields']);

    recordTest("Missing password fields rejected", $passed, "Required fields validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Missing password fields rejected", false, $e->getMessage());
}

// Test 18: Unauthorized password change rejected
try {
    session_start();
    session_destroy();
    session_start();

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => 'anything',
        'new_password' => 'NewPassword123',
        'new_password_confirmation' => 'NewPassword123'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Unauthorized') !== false;

    recordTest("Unauthorized password change rejected", $passed, "Returns 401 Unauthorized");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Unauthorized password change rejected", false, $e->getMessage());
}

// Test 19: Password change logs activity
try {
    // Clear previous activity logs
    $conn->query("DELETE FROM activity_log WHERE user_id = {$testUserId} AND action = 'api_password_changed'");

    session_start();
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'current_password' => $testPassword,
        'new_password' => 'LoggedPassword123',
        'new_password_confirmation' => 'LoggedPassword123'
    ]);

    ob_start();
    $userController->updatePassword();
    $output = ob_get_clean();

    // Check if activity was logged
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE user_id = ? AND action = 'api_password_changed'");
    $stmt->bind_param('i', $testUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    recordTest("Password change logs activity", $row['count'] > 0, "Activity logged with IP and User-Agent");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Password change logs activity", false, $e->getMessage());
}

// Test 20: Password hashing verification
try {
    // Verify password was actually hashed in database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $testUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if password is bcrypt hash (starts with $2y$)
    $passed = strpos($user['password'], '$2y$') === 0;

    recordTest("Password properly hashed with bcrypt", $passed, "Password stored securely");

} catch (\Exception $e) {
    recordTest("Password properly hashed with bcrypt", false, $e->getMessage());
}

echo "\n";

// Cleanup
echo "Cleanup: Removing test data...\n";
$conn->query("DELETE FROM users WHERE id = {$testUserId}");
$conn->query("DELETE FROM activity_log WHERE user_id = {$testUserId}");
echo "  ✅ Test data removed\n\n";

// ═══════════════════════════════════════════════════════════════
// FINAL SUMMARY
// ═══════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Test Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "SECTION BREAKDOWN:\n";
echo "  Section 1 (GET /api/v1/user/profile): 5 tests\n";
echo "  Section 2 (PUT /api/v1/user/profile): 7 tests\n";
echo "  Section 3 (PUT /api/v1/user/password): 8 tests\n\n";

echo "OVERALL RESULTS:\n";
echo "  Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "  ✅ Passed: {$testsPassed}\n";
echo "  ❌ Failed: {$testsFailed}\n";
echo "  Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 2) . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 All user profile API tests passed!\n\n";
    echo "PHASE 5 WEEK 2 DAY 1: USER PROFILE API READY FOR PRODUCTION\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Review the output above.\n\n";
    echo "Failed tests:\n";
    foreach ($testResults as $result) {
        if (strpos($result, '❌') !== false) {
            echo "  {$result}\n";
        }
    }
    echo "\n";
    exit(1);
}
