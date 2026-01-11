<?php
/**
 * Phase 5 Week 2 Day 2 - Admin User Management API Test Suite
 *
 * Tests the admin user management API endpoints:
 * - GET /api/v1/admin/users
 * - GET /api/v1/admin/users/{id}
 *
 * @package Tests
 * @since Phase 5 Week 2 Day 2 (January 8, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Services/ApiTokenService.php';

use App\Services\ApiTokenService;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 2 Day 2 - Admin User Management API Tests\n";
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

// Setup: Create test admin and regular users
echo "Setup: Creating test users for admin API tests...\n";

// Create admin user
$adminEmail = 'admin.test@example.com';
$adminPassword = 'AdminPass123';
$hashedAdminPassword = password_hash($adminPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$adminEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
$adminUsername = 'admintest';
$adminName = 'Admin';
$adminSurname = 'Test';
$stmt->bind_param('sssss', $adminUsername, $adminEmail, $hashedAdminPassword, $adminName, $adminSurname);
$stmt->execute();
$adminUserId = $conn->insert_id;
echo "  ✅ Admin user created (ID: {$adminUserId})\n";

// Create regular member user
$memberEmail = 'member.test@example.com';
$memberPassword = 'MemberPass123';
$hashedMemberPassword = password_hash($memberPassword, PASSWORD_BCRYPT);

$conn->query("DELETE FROM users WHERE email = '{$memberEmail}'");
$stmt = $conn->prepare("INSERT INTO users (username, email, password, name, surname, user_type, active) VALUES (?, ?, ?, ?, ?, 'member', 1)");
$memberUsername = 'membertest';
$memberName = 'Member';
$memberSurname = 'Test';
$stmt->bind_param('sssss', $memberUsername, $memberEmail, $hashedMemberPassword, $memberName, $memberSurname);
$stmt->execute();
$memberUserId = $conn->insert_id;
echo "  ✅ Member user created (ID: {$memberUserId})\n\n";

// Generate tokens
$adminToken = ApiTokenService::generate($adminUserId, 'admin');
$memberToken = ApiTokenService::generate($memberUserId, 'member');

// ═══════════════════════════════════════════════════════════════
// SECTION 1: GET /api/v1/admin/users Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: GET /api/v1/admin/users (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Admin can list users
try {
    require_once __DIR__ . '/../app/Controllers/Api/Admin/UserController.php';
    $userController = new App\Controllers\Api\Admin\UserController();

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['users']) &&
              is_array($response['data']['users']) &&
              isset($response['data']['pagination']);

    recordTest("Admin can list users", $passed, "Users: " . count($response['data']['users'] ?? []));

    session_destroy();
} catch (\Exception $e) {
    recordTest("Admin can list users", false, $e->getMessage());
}

// Test 2: Pagination metadata included
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $pagination = $response['data']['pagination'] ?? [];

    $requiredKeys = ['current_page', 'per_page', 'total', 'total_pages', 'has_more'];
    $hasAllKeys = true;

    foreach ($requiredKeys as $key) {
        if (!isset($pagination[$key])) {
            $hasAllKeys = false;
            break;
        }
    }

    recordTest("Pagination metadata included", $hasAllKeys, "Page {$pagination['current_page']}/{$pagination['total_pages']}");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Pagination metadata included", false, $e->getMessage());
}

// Test 3: Users don't include passwords
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $users = $response['data']['users'] ?? [];

    $hasPassword = false;
    foreach ($users as $user) {
        if (isset($user['password'])) {
            $hasPassword = true;
            break;
        }
    }

    recordTest("Users don't include passwords", !$hasPassword, "Password field removed for security");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Users don't include passwords", false, $e->getMessage());
}

// Test 4: Non-admin member blocked from listing users
try {
    session_start();
    $_SESSION['user_id'] = $memberUserId;
    $_SESSION['user_type'] = 'member';
    $_SESSION['role'] = 'member';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Admin access required') !== false;

    recordTest("Non-admin blocked from listing users", $passed, "Returns 403 Forbidden");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-admin blocked from listing users", false, $e->getMessage());
}

// Test 5: Pagination page parameter works
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    // Set page parameter
    $_GET['page'] = 2;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $pagination = $response['data']['pagination'] ?? [];

    recordTest("Pagination page parameter works", $pagination['current_page'] === 2, "Current page: 2");

    unset($_GET['page']);
    session_destroy();
} catch (\Exception $e) {
    recordTest("Pagination page parameter works", false, $e->getMessage());
}

// Test 6: Per page parameter works (max 100)
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    // Try setting per_page to 150 (should cap at 100)
    $_GET['per_page'] = 150;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $pagination = $response['data']['pagination'] ?? [];

    recordTest("Per page capped at 100", $pagination['per_page'] === 100, "Per page: 100 (capped from 150)");

    unset($_GET['per_page']);
    session_destroy();
} catch (\Exception $e) {
    recordTest("Per page capped at 100", false, $e->getMessage());
}

// Test 7: Role filter works
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    $_GET['role'] = 'admin';

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $users = $response['data']['users'] ?? [];

    // Check that all returned users are admins
    $allAdmins = true;
    foreach ($users as $user) {
        if ($user['user_type'] !== 'admin') {
            $allAdmins = false;
            break;
        }
    }

    recordTest("Role filter works", $allAdmins, "Filtered to admin users only");

    unset($_GET['role']);
    session_destroy();
} catch (\Exception $e) {
    recordTest("Role filter works", false, $e->getMessage());
}

// Test 8: Search filter works
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    $_GET['search'] = 'admintest';

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $users = $response['data']['users'] ?? [];

    // Should find the admin test user
    $foundAdminTest = false;
    foreach ($users as $user) {
        if ($user['username'] === 'admintest') {
            $foundAdminTest = true;
            break;
        }
    }

    recordTest("Search filter works", $foundAdminTest, "Found 'admintest' user");

    unset($_GET['search']);
    session_destroy();
} catch (\Exception $e) {
    recordTest("Search filter works", false, $e->getMessage());
}

// Test 9: Activity logging for list action
try {
    // Clear previous activity logs
    $conn->query("DELETE FROM activity_log WHERE user_id = {$adminUserId} AND action = 'api_admin_users_list'");

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    // Check if activity was logged
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE user_id = ? AND action = 'api_admin_users_list'");
    $stmt->bind_param('i', $adminUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    recordTest("Activity logged for list action", $row['count'] > 0, "api_admin_users_list logged");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Activity logged for list action", false, $e->getMessage());
}

// Test 10: Unauthorized access rejected
try {
    session_start();
    session_destroy();
    session_start(); // Clear session

    ob_start();
    $userController->index();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false;

    recordTest("Unauthorized access rejected", $passed, "Returns error response");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Unauthorized access rejected", false, $e->getMessage());
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 2: GET /api/v1/admin/users/{id} Tests (8 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 2: GET /api/v1/admin/users/{id} (8 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 11: Admin can view user details
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show($memberUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === true &&
              isset($response['data']['id']) &&
              $response['data']['id'] === $memberUserId;

    recordTest("Admin can view user details", $passed, "User ID: {$memberUserId}");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Admin can view user details", false, $e->getMessage());
}

// Test 12: User details include stats
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show($memberUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $stats = $response['data']['stats'] ?? [];

    $requiredStats = ['courses_enrolled', 'courses_completed', 'programs_registered', 'total_activity_hours'];
    $hasAllStats = true;

    foreach ($requiredStats as $stat) {
        if (!isset($stats[$stat])) {
            $hasAllStats = false;
            break;
        }
    }

    recordTest("User details include stats", $hasAllStats, "Stats: " . implode(', ', $requiredStats));

    session_destroy();
} catch (\Exception $e) {
    recordTest("User details include stats", false, $e->getMessage());
}

// Test 13: User details don't include password
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show($memberUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);
    $user = $response['data'] ?? [];

    recordTest("User details don't include password", !isset($user['password']), "Password removed for security");

    session_destroy();
} catch (\Exception $e) {
    recordTest("User details don't include password", false, $e->getMessage());
}

// Test 14: Non-admin blocked from viewing user details
try {
    session_start();
    $_SESSION['user_id'] = $memberUserId;
    $_SESSION['user_type'] = 'member';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show($adminUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Admin access required') !== false;

    recordTest("Non-admin blocked from viewing details", $passed, "Returns 403 Forbidden");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-admin blocked from viewing details", false, $e->getMessage());
}

// Test 15: Invalid user ID rejected
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show('invalid');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'Invalid user ID') !== false;

    recordTest("Invalid user ID rejected", $passed, "Returns 422 validation error");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Invalid user ID rejected", false, $e->getMessage());
}

// Test 16: Non-existent user returns 404
try {
    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show(999999);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false &&
              strpos($response['error'] ?? '', 'not found') !== false;

    recordTest("Non-existent user returns 404", $passed, "User not found");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Non-existent user returns 404", false, $e->getMessage());
}

// Test 17: Activity logged for view action
try {
    // Clear previous activity logs
    $conn->query("DELETE FROM activity_log WHERE user_id = {$adminUserId} AND action = 'api_admin_user_viewed'");

    session_start();
    $_SESSION['user_id'] = $adminUserId;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['loggedin'] = true;

    ob_start();
    $userController->show($memberUserId);
    $output = ob_get_clean();

    // Check if activity was logged
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_log WHERE user_id = ? AND action = 'api_admin_user_viewed'");
    $stmt->bind_param('i', $adminUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    recordTest("Activity logged for view action", $row['count'] > 0, "api_admin_user_viewed logged");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Activity logged for view action", false, $e->getMessage());
}

// Test 18: Unauthorized access to show rejected
try {
    session_start();
    session_destroy();
    session_start(); // Clear session

    ob_start();
    $userController->show($memberUserId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    $passed = isset($response['success']) &&
              $response['success'] === false;

    recordTest("Unauthorized access to show rejected", $passed, "Returns error response");

    session_destroy();
} catch (\Exception $e) {
    recordTest("Unauthorized access to show rejected", false, $e->getMessage());
}

echo "\n";

// Cleanup
echo "Cleanup: Removing test data...\n";
$conn->query("DELETE FROM users WHERE id IN ({$adminUserId}, {$memberUserId})");
$conn->query("DELETE FROM activity_log WHERE user_id IN ({$adminUserId}, {$memberUserId})");
echo "  ✅ Test data removed\n\n";

// ═══════════════════════════════════════════════════════════════
// FINAL SUMMARY
// ═══════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Test Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "SECTION BREAKDOWN:\n";
echo "  Section 1 (GET /api/v1/admin/users): 10 tests\n";
echo "  Section 2 (GET /api/v1/admin/users/{id}): 8 tests\n\n";

echo "OVERALL RESULTS:\n";
echo "  Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "  ✅ Passed: {$testsPassed}\n";
echo "  ❌ Failed: {$testsFailed}\n";
echo "  Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 2) . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 All admin user API tests passed!\n\n";
    echo "PHASE 5 WEEK 2 DAY 2: ADMIN USER MANAGEMENT API READY FOR USE\n\n";
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
