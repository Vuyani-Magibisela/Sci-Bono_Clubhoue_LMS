<?php
/**
 * Phase 5 Week 2 Day 4 - Admin User Delete API Test Suite
 *
 * Tests the admin user delete API endpoint:
 * - DELETE /api/v1/admin/users/{id}
 *
 * @package Tests
 * @since Phase 5 Week 2 Day 4 (January 9, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 2 Day 4 - Admin User Delete API Tests\n";
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

// Setup: Create test users
echo "Setup: Creating test users for delete API tests...\n";

// Create admin user
$adminEmail = 'admin.delete.test@example.com';
$adminPassword = 'AdminPass123';
$hashedAdminPassword = password_hash($adminPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$adminEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
$adminUsername = 'admindeltest';
$adminName = 'Admin';
$adminSurname = 'DeleteTest';
$stmt->bind_param('sssss', $adminUsername, $adminEmail, $hashedAdminPassword, $adminName, $adminSurname);
$stmt->execute();
$adminUserId = $conn->insert_id;
echo "  ✅ Admin user created (ID: {$adminUserId})\n";

// Create second admin user (to test last admin prevention)
$admin2Email = 'admin2.delete.test@example.com';
$conn->query("DELETE FROM users WHERE email = '{$admin2Email}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
$admin2Username = 'admin2deltest';
$admin2Name = 'Admin2';
$admin2Surname = 'DeleteTest';
$stmt->bind_param('sssss', $admin2Username, $admin2Email, $hashedAdminPassword, $admin2Name, $admin2Surname);
$stmt->execute();
$admin2UserId = $conn->insert_id;
echo "  ✅ Second admin user created (ID: {$admin2UserId})\n";

// Create member user to delete
$memberEmail = 'member.delete.test@example.com';
$conn->query("DELETE FROM users WHERE email = '{$memberEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
$memberUsername = 'memberdeltest';
$memberName = 'Member';
$memberSurname = 'DeleteTest';
$stmt->bind_param('sssss', $memberUsername, $memberEmail, $hashedAdminPassword, $memberName, $memberSurname);
$stmt->execute();
$memberUserId = $conn->insert_id;
echo "  ✅ Member user created (ID: {$memberUserId})\n\n";

// Generate token
$adminToken = ApiTokenService::generate($adminUserId, 'admin');

// ═══════════════════════════════════════════════════════════════
// SECTION 1: DELETE /api/v1/admin/users/{id} Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: DELETE /api/v1/admin/users/{id} (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Admin can delete user
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['role'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy($memberUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true;

    recordTest("Admin can delete user", $passed, "User deactivated successfully");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Admin can delete user", false, $e->getMessage());
}

// Test 2: Deleted user is soft deleted (active=0)
try {
    $stmt = $conn->prepare("SELECT active FROM users WHERE id = ?");
    $stmt->bind_param('i', $memberUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $passed = isset($user['active']) && $user['active'] == 0;

    recordTest("Deleted user is soft deleted (active=0)", $passed, "User record still exists but inactive");
} catch (\Exception $e) {
    recordTest("Deleted user is soft deleted (active=0)", false, $e->getMessage());
}

// Test 3: Invalid user ID returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy('invalid');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '422') !== false;

    recordTest("Invalid user ID returns 422", $passed, "ID validation works");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid user ID returns 422", false, $e->getMessage());
}

// Test 4: Non-existent user returns 404
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy(999999);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '404') !== false;

    recordTest("Non-existent user returns 404", $passed, "User not found");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-existent user returns 404", false, $e->getMessage());
}

// Test 5: Cannot delete self
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy($adminUserId); // Try to delete self
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'Cannot delete your own account') !== false;

    recordTest("Cannot delete self", $passed, "Self-deletion prevented");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Cannot delete self", false, $e->getMessage());
}

// Test 6: Cannot delete last admin
try {
    // First, deactivate the second admin to leave only one active
    $conn->query("UPDATE users SET active = 0 WHERE id = {$admin2UserId}");

    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $admin2UserId; // Use second admin to try delete first
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy($adminUserId); // Try to delete last active admin
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'last admin') !== false;

    recordTest("Cannot delete last admin", $passed, "Last admin deletion prevented");

    // Restore second admin
    $conn->query("UPDATE users SET active = 1 WHERE id = {$admin2UserId}");

    session_destroy();
} catch (\Exception $e) {
    // Restore second admin in case of error
    $conn->query("UPDATE users SET active = 1 WHERE id = {$admin2UserId}");
    recordTest("Cannot delete last admin", false, $e->getMessage());
}

// Test 7: Non-admin blocked from deleting users
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = 999;
    $_SESSION['user_type'] = 'member'; // Not admin
    $_SESSION['role'] = 'member';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    // Create a test user to try delete
    $testEmail = 'test.delete@example.com';
    $conn->query("DELETE FROM users WHERE email = '{$testEmail}'");
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
    $testUsername = 'testdelete' . time();
    $testName = 'Test';
    $testSurname = 'Delete';
    $stmt->bind_param('sssss', $testUsername, $testEmail, $hashedAdminPassword, $testName, $testSurname);
    $stmt->execute();
    $testUserId = $conn->insert_id;

    ob_start();
    $userController->destroy($testUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '403') !== false;

    recordTest("Non-admin blocked from deleting users", $passed, "Returns 403 Forbidden");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-admin blocked from deleting users", false, $e->getMessage());
}

// Test 8: Activity logged for user deletion
try {
    $passed = true;
    $checkTable = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE action = 'api_admin_user_deleted' AND user_id = ?");
        $stmt->bind_param('i', $adminUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $passed = $count > 0;
    }

    recordTest("Activity logged for user deletion", $passed, "User deletion logged to activity_log");
} catch (\Exception $e) {
    recordTest("Activity logged for user deletion", false, $e->getMessage());
}

// Test 9: Soft delete preserves user data
try {
    // Create a user to delete
    $preserveEmail = 'preserve.test@example.com';
    $conn->query("DELETE FROM users WHERE email = '{$preserveEmail}'");
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
    $preserveUsername = 'preservetest' . time();
    $preserveName = 'Preserve';
    $preserveSurname = 'Test';
    $stmt->bind_param('sssss', $preserveUsername, $preserveEmail, $hashedAdminPassword, $preserveName, $preserveSurname);
    $stmt->execute();
    $preserveUserId = $conn->insert_id;

    // Delete the user
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy($preserveUserId);
    $output = ob_get_clean();

    // Check data is preserved
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $preserveUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $passed = $user !== null &&
              $user['email'] === $preserveEmail &&
              $user['username'] === $preserveUsername &&
              $user['name'] === $preserveName &&
              $user['active'] == 0;

    recordTest("Soft delete preserves user data", $passed, "User data intact, only active=0");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Soft delete preserves user data", false, $e->getMessage());
}

// Test 10: updated_at timestamp is set on deletion
try {
    // Create a user to delete
    $timestampEmail = 'timestamp.test@example.com';
    $conn->query("DELETE FROM users WHERE email = '{$timestampEmail}'");
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active, updated_at) VALUES (?, ?, ?, ?, ?, 'member', 1, '2020-01-01 00:00:00')");
    $timestampUsername = 'timestamptest' . time();
    $timestampName = 'Timestamp';
    $timestampSurname = 'Test';
    $stmt->bind_param('sssss', $timestampUsername, $timestampEmail, $hashedAdminPassword, $timestampName, $timestampSurname);
    $stmt->execute();
    $timestampUserId = $conn->insert_id;

    // Delete the user
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'DELETE';

    ob_start();
    $userController->destroy($timestampUserId);
    $output = ob_get_clean();

    // Check updated_at was changed
    $stmt = $conn->prepare("SELECT updated_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $timestampUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $passed = $user !== null &&
              $user['updated_at'] !== '2020-01-01 00:00:00' &&
              strtotime($user['updated_at']) > strtotime('2020-01-01');

    recordTest("updated_at timestamp is set on deletion", $passed, "Timestamp updated on deletion");

    session_destroy();
} catch (\Exception $e) {
    recordTest("updated_at timestamp is set on deletion", false, $e->getMessage());
}

echo "\nCleanup: Removing test data...\n";

// Cleanup test users
$conn->query("DELETE FROM users WHERE email LIKE '%delete.test@example.com' OR email LIKE '%timestamp.test@example.com' OR email LIKE '%preserve.test@example.com'");

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
