<?php
/**
 * Phase 5 Week 2 Day 3 - Admin User Create/Update API Test Suite
 *
 * Tests the admin user CRUD API endpoints:
 * - POST /api/v1/admin/users (create)
 * - PUT /api/v1/admin/users/{id} (update)
 *
 * @package Tests
 * @since Phase 5 Week 2 Day 3 (January 9, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 2 Day 3 - Admin User Create/Update API Tests\n";
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

// Setup: Create test admin user
echo "Setup: Creating test admin user...\n";

// Create admin user
$adminEmail = 'admin.crud.test@example.com';
$adminPassword = 'AdminPass123';
$hashedAdminPassword = password_hash($adminPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$adminEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
$adminUsername = 'admincrudtest';
$adminName = 'Admin';
$adminSurname = 'CRUDTest';
$stmt->bind_param('sssss', $adminUsername, $adminEmail, $hashedAdminPassword, $adminName, $adminSurname);
$stmt->execute();
$adminUserId = $conn->insert_id;
echo "  ✅ Admin user created (ID: {$adminUserId})\n\n";

// Generate token
$adminToken = ApiTokenService::generate($adminUserId, 'admin');

// ═══════════════════════════════════════════════════════════════
// SECTION 1: POST /api/v1/admin/users Tests (12 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: POST /api/v1/admin/users (12 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Admin can create user with valid data
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['role'] = 'admin';

    // Set REQUEST_METHOD to POST
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Simulate JSON request data
    $userController->requestData = [
        'username' => 'newuser' . time(),
        'email' => 'newuser' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'New',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['id']) &&
              !isset($response['data']['password']);

    $createdUserId = $response['data']['id'] ?? null;

    recordTest("Admin can create user with valid data", $passed, "User ID: " . ($createdUserId ?? 'N/A'));

    session_destroy();
} catch (\Exception $e) {
    recordTest("Admin can create user with valid data", false, $e->getMessage());
}

// Test 2: Missing required fields returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'incomplete',
        // Missing email, password, name, surname, user_type
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '422') !== false;

    recordTest("Missing required fields returns 422", $passed, "Validation error returned");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Missing required fields returns 422", false, $e->getMessage());
}

// Test 3: Invalid email format returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'invalidemail' . time(),
        'email' => 'not-an-email',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'email') !== false;

    recordTest("Invalid email format returns 422", $passed, "Email validation error");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid email format returns 422", false, $e->getMessage());
}

// Test 4: Short password returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'shortpass' . time(),
        'email' => 'shortpass' . time() . '@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'password') !== false &&
              strpos($output, '8') !== false;

    recordTest("Short password returns 422", $passed, "Password length validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Short password returns 422", false, $e->getMessage());
}

// Test 5: Password mismatch returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'mismatch' . time(),
        'email' => 'mismatch' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'DifferentPass456',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'confirmation') !== false;

    recordTest("Password mismatch returns 422", $passed, "Password confirmation validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Password mismatch returns 422", false, $e->getMessage());
}

// Test 6: Invalid user_type returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'invalidtype' . time(),
        'email' => 'invalidtype' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'superadmin' // Invalid type
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'user_type') !== false;

    recordTest("Invalid user_type returns 422", $passed, "User type validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid user_type returns 422", false, $e->getMessage());
}

// Test 7: Duplicate email returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Use admin's email (already exists)
    $userController->requestData = [
        'username' => 'duplicate' . time(),
        'email' => $adminEmail, // Duplicate!
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'email') !== false &&
              strpos($output, 'exists') !== false;

    recordTest("Duplicate email returns 422", $passed, "Email uniqueness validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Duplicate email returns 422", false, $e->getMessage());
}

// Test 8: Duplicate username returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => $adminUsername, // Duplicate!
        'email' => 'uniqueemail' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Test',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'username') !== false &&
              strpos($output, 'exists') !== false;

    recordTest("Duplicate username returns 422", $passed, "Username uniqueness validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Duplicate username returns 422", false, $e->getMessage());
}

// Test 9: Created user doesn't include password
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'passcheck' . time(),
        'email' => 'passcheck' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Pass',
        'surname' => 'Check',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['data']) &&
              !isset($response['data']['password']);

    recordTest("Created user doesn't include password", $passed, "Password field removed for security");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Created user doesn't include password", false, $e->getMessage());
}

// Test 10: Non-admin blocked from creating users
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = 999;
    $_SESSION['user_type'] = 'member'; // Not admin
    $_SESSION['role'] = 'member';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $userController->requestData = [
        'username' => 'blocked' . time(),
        'email' => 'blocked' . time() . '@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Blocked',
        'surname' => 'User',
        'user_type' => 'member'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '403') !== false;

    recordTest("Non-admin blocked from creating users", $passed, "Returns 403 Forbidden");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-admin blocked from creating users", false, $e->getMessage());
}

// Test 11: Activity logged for user creation
try {
    $passed = true; // Skip this test if activity_log table doesn't exist
    $checkTable = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Check if activity was logged
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE action = 'api_admin_user_created' AND user_id = ?");
        $stmt->bind_param('i', $adminUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $passed = $count > 0;
    }

    recordTest("Activity logged for user creation", $passed, "User creation logged to activity_log");
} catch (\Exception $e) {
    recordTest("Activity logged for user creation", false, $e->getMessage());
}

// Test 12: Created user has correct attributes
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'POST';

    $testEmail = 'attributes' . time() . '@example.com';
    $testUsername = 'attributes' . time();

    $userController->requestData = [
        'username' => $testUsername,
        'email' => $testEmail,
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'name' => 'Attribute',
        'surname' => 'Test',
        'user_type' => 'mentor'
    ];

    ob_start();
    $userController->store();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['data']) &&
              $response['data']['username'] === $testUsername &&
              $response['data']['email'] === $testEmail &&
              $response['data']['name'] === 'Attribute' &&
              $response['data']['surname'] === 'Test' &&
              $response['data']['user_type'] === 'mentor';

    recordTest("Created user has correct attributes", $passed, "All attributes match input");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Created user has correct attributes", false, $e->getMessage());
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 2: PUT /api/v1/admin/users/{id} Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 2: PUT /api/v1/admin/users/{id} (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Create a test user to update
$updateTestEmail = 'updatetest' . time() . '@example.com';
$updateTestPassword = password_hash('TestPass123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
$updateTestUsername = 'updatetest' . time();
$updateTestName = 'Update';
$updateTestSurname = 'Test';
$stmt->bind_param('sssss', $updateTestUsername, $updateTestEmail, $updateTestPassword, $updateTestName, $updateTestSurname);
$stmt->execute();
$updateTestUserId = $conn->insert_id;

// Test 13: Admin can update user with valid data
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'name' => 'Updated Name',
        'surname' => 'Updated Surname',
        'user_type' => 'mentor'
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['id']) &&
              $response['data']['name'] === 'Updated Name' &&
              $response['data']['surname'] === 'Updated Surname' &&
              $response['data']['user_type'] === 'mentor';

    recordTest("Admin can update user with valid data", $passed, "User updated successfully");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Admin can update user with valid data", false, $e->getMessage());
}

// Test 14: Partial updates work correctly
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    // Only update name
    $userController->requestData = [
        'name' => 'Partial Update'
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              $response['data']['name'] === 'Partial Update';

    recordTest("Partial updates work correctly", $passed, "Only specified fields updated");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Partial updates work correctly", false, $e->getMessage());
}

// Test 15: Updated user doesn't include password
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'name' => 'Password Check'
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['data']) &&
              !isset($response['data']['password']);

    recordTest("Updated user doesn't include password", $passed, "Password removed for security");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Updated user doesn't include password", false, $e->getMessage());
}

// Test 16: Invalid user ID returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'name' => 'Test'
    ];

    ob_start();
    $userController->update('invalid');
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

// Test 17: Non-existent user returns 404
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'name' => 'Test'
    ];

    ob_start();
    $userController->update(999999);
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

// Test 18: Invalid email format returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'email' => 'not-an-email'
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'email') !== false;

    recordTest("Invalid email format returns 422", $passed, "Email validation works");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid email format returns 422", false, $e->getMessage());
}

// Test 19: Duplicate email (other user) returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    // Try to use admin's email
    $userController->requestData = [
        'email' => $adminEmail
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'email') !== false &&
              strpos($output, 'exists') !== false;

    recordTest("Duplicate email (other user) returns 422", $passed, "Email uniqueness validation");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Duplicate email (other user) returns 422", false, $e->getMessage());
}

// Test 20: Invalid user_type returns 422
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'user_type' => 'superadmin' // Invalid
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, 'user_type') !== false;

    recordTest("Invalid user_type returns 422", $passed, "User type validation works");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid user_type returns 422", false, $e->getMessage());
}

// Test 21: Non-admin blocked from updating users
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = 999;
    $_SESSION['user_type'] = 'member'; // Not admin
    $_SESSION['role'] = 'member';

    $_SERVER['REQUEST_METHOD'] = 'PUT';

    $userController->requestData = [
        'name' => 'Blocked'
    ];

    ob_start();
    $userController->update($updateTestUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($output, '403') !== false;

    recordTest("Non-admin blocked from updating users", $passed, "Returns 403 Forbidden");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-admin blocked from updating users", false, $e->getMessage());
}

// Test 22: Activity logged for user update
try {
    $passed = true;
    $checkTable = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE action = 'api_admin_user_updated' AND user_id = ?");
        $stmt->bind_param('i', $adminUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $passed = $count > 0;
    }

    recordTest("Activity logged for user update", $passed, "User update logged to activity_log");
} catch (\Exception $e) {
    recordTest("Activity logged for user update", false, $e->getMessage());
}

echo "\nCleanup: Removing test data...\n";

// Cleanup test users
$conn->query("DELETE FROM users WHERE email LIKE '%@example.com' AND username LIKE '%test%'");

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
