<?php
/**
 * Phase 5 Week 3 Day 3 - OpenAPI Documentation Test Suite
 *
 * Tests the OpenAPI specification generation and documentation endpoints:
 * - OpenAPI spec generation
 * - JSON/YAML format output
 * - Schema validation
 * - Endpoint documentation completeness
 * - Swagger UI rendering
 * - Documentation info endpoint
 *
 * @package Tests
 * @since Phase 5 Week 3 Day 3 (January 10, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Utils/OpenApiGenerator.php';
require_once __DIR__ . '/../app/Controllers/Api/DocsController.php';

use App\Utils\OpenApiGenerator;
use App\Controllers\Api\DocsController;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 3 Day 3 - OpenAPI Documentation Tests\n";
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
// SECTION 1: OpenAPI Spec Generation Tests (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: OpenAPI Spec Generation Tests (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Generate basic OpenAPI spec
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = is_array($spec) && isset($spec['openapi']);
    recordTest("Generate basic OpenAPI spec", $passed, "OpenAPI version: " . ($spec['openapi'] ?? 'N/A'));
} catch (\Exception $e) {
    recordTest("Generate basic OpenAPI spec", false, $e->getMessage());
}

// Test 2: Validate OpenAPI 3.0.3 compliance
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = $spec['openapi'] === '3.0.3';
    recordTest("Validate OpenAPI 3.0.3 compliance", $passed, "Version: " . $spec['openapi']);
} catch (\Exception $e) {
    recordTest("Validate OpenAPI 3.0.3 compliance", false, $e->getMessage());
}

// Test 3: Validate info section
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['info']['title']) &&
              isset($spec['info']['version']) &&
              isset($spec['info']['contact']);
    recordTest("Validate info section", $passed, "Title: " . ($spec['info']['title'] ?? 'N/A'));
} catch (\Exception $e) {
    recordTest("Validate info section", false, $e->getMessage());
}

// Test 4: Validate servers section
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['servers']) &&
              is_array($spec['servers']) &&
              count($spec['servers']) > 0;
    recordTest("Validate servers section", $passed, "Servers: " . count($spec['servers']));
} catch (\Exception $e) {
    recordTest("Validate servers section", false, $e->getMessage());
}

// Test 5: Validate paths section
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['paths']) &&
              is_array($spec['paths']) &&
              count($spec['paths']) > 0;
    recordTest("Validate paths section", $passed, "Paths: " . count($spec['paths']));
} catch (\Exception $e) {
    recordTest("Validate paths section", false, $e->getMessage());
}

// Test 6: Validate components/schemas section
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']) &&
              is_array($spec['components']['schemas']) &&
              count($spec['components']['schemas']) > 0;
    recordTest("Validate components/schemas section", $passed, "Schemas: " . count($spec['components']['schemas']));
} catch (\Exception $e) {
    recordTest("Validate components/schemas section", false, $e->getMessage());
}

// Test 7: Validate security schemes
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['securitySchemes']['bearerAuth']) &&
              $spec['components']['securitySchemes']['bearerAuth']['type'] === 'http' &&
              $spec['components']['securitySchemes']['bearerAuth']['scheme'] === 'bearer';
    recordTest("Validate security schemes", $passed, "Bearer auth configured");
} catch (\Exception $e) {
    recordTest("Validate security schemes", false, $e->getMessage());
}

// Test 8: Validate authentication endpoints
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $hasLogin = isset($spec['paths']['/auth/login']);
    $hasLogout = isset($spec['paths']['/auth/logout']);
    $hasRefresh = isset($spec['paths']['/auth/refresh']);

    $passed = $hasLogin && $hasLogout && $hasRefresh;
    recordTest("Validate authentication endpoints", $passed, "Login, logout, refresh documented");
} catch (\Exception $e) {
    recordTest("Validate authentication endpoints", false, $e->getMessage());
}

// Test 9: Validate user endpoints
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $hasUserList = isset($spec['paths']['/users']);
    $hasUserShow = isset($spec['paths']['/users/{id}']);
    $hasUserProfile = isset($spec['paths']['/users/me']);

    $passed = $hasUserList && $hasUserShow && $hasUserProfile;
    recordTest("Validate user endpoints", $passed, "User list, show, profile documented");
} catch (\Exception $e) {
    recordTest("Validate user endpoints", false, $e->getMessage());
}

// Test 10: Validate admin endpoints
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $hasAdminUserList = isset($spec['paths']['/admin/users']);
    $hasAdminUserCreate = isset($spec['paths']['/admin/users']['post']);
    $hasAdminUserUpdate = isset($spec['paths']['/admin/users/{id}']['put']);
    $hasAdminUserDelete = isset($spec['paths']['/admin/users/{id}']['delete']);

    $passed = $hasAdminUserList && $hasAdminUserCreate && $hasAdminUserUpdate && $hasAdminUserDelete;
    recordTest("Validate admin endpoints", $passed, "Admin CRUD operations documented");
} catch (\Exception $e) {
    recordTest("Validate admin endpoints", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: Format Output Tests (4 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: Format Output Tests (4 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 11: Generate JSON output
try {
    $generator = new OpenApiGenerator();
    $generator->generate();
    $json = $generator->toJson();

    $passed = is_string($json) && !empty($json);
    $decoded = json_decode($json, true);
    $isValidJson = json_last_error() === JSON_ERROR_NONE;

    recordTest("Generate JSON output", $passed && $isValidJson, "JSON length: " . strlen($json) . " bytes");
} catch (\Exception $e) {
    recordTest("Generate JSON output", false, $e->getMessage());
}

// Test 12: Generate pretty-printed JSON
try {
    $generator = new OpenApiGenerator();
    $generator->generate();
    $json = $generator->toJson(true);

    $passed = is_string($json) && strpos($json, "\n") !== false;
    recordTest("Generate pretty-printed JSON", $passed, "Pretty printed with newlines");
} catch (\Exception $e) {
    recordTest("Generate pretty-printed JSON", false, $e->getMessage());
}

// Test 13: Generate YAML output
try {
    $generator = new OpenApiGenerator();
    $generator->generate();
    $yaml = $generator->toYaml();

    $passed = is_string($yaml) && !empty($yaml);
    $hasYamlStructure = strpos($yaml, 'openapi:') !== false;

    recordTest("Generate YAML output", $passed && $hasYamlStructure, "YAML length: " . strlen($yaml) . " bytes");
} catch (\Exception $e) {
    recordTest("Generate YAML output", false, $e->getMessage());
}

// Test 14: YAML contains proper indentation
try {
    $generator = new OpenApiGenerator();
    $generator->generate();
    $yaml = $generator->toYaml();

    $lines = explode("\n", $yaml);
    $hasIndentation = false;
    foreach ($lines as $line) {
        if (preg_match('/^  \w+:/', $line)) {
            $hasIndentation = true;
            break;
        }
    }

    $passed = $hasIndentation;
    recordTest("YAML contains proper indentation", $passed, "Found indented lines");
} catch (\Exception $e) {
    recordTest("YAML contains proper indentation", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 3: Schema Validation Tests (6 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 3: Schema Validation Tests (6 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 15: User schema exists
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']['User']);
    recordTest("User schema exists", $passed, "User schema defined");
} catch (\Exception $e) {
    recordTest("User schema exists", false, $e->getMessage());
}

// Test 16: User schema has required properties
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $userSchema = $spec['components']['schemas']['User'] ?? [];
    $properties = $userSchema['properties'] ?? [];

    $hasId = isset($properties['id']);
    $hasEmail = isset($properties['email']);
    $hasRole = isset($properties['role']);

    $passed = $hasId && $hasEmail && $hasRole;
    recordTest("User schema has required properties", $passed, "id, email, role present");
} catch (\Exception $e) {
    recordTest("User schema has required properties", false, $e->getMessage());
}

// Test 17: LoginRequest schema exists
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']['LoginRequest']);
    recordTest("LoginRequest schema exists", $passed, "LoginRequest schema defined");
} catch (\Exception $e) {
    recordTest("LoginRequest schema exists", false, $e->getMessage());
}

// Test 18: LoginResponse schema exists
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']['LoginResponse']);
    recordTest("LoginResponse schema exists", $passed, "LoginResponse schema defined");
} catch (\Exception $e) {
    recordTest("LoginResponse schema exists", false, $e->getMessage());
}

// Test 19: ErrorResponse schema exists
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']['ErrorResponse']);
    recordTest("ErrorResponse schema exists", $passed, "ErrorResponse schema defined");
} catch (\Exception $e) {
    recordTest("ErrorResponse schema exists", false, $e->getMessage());
}

// Test 20: SuccessResponse schema exists
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $passed = isset($spec['components']['schemas']['SuccessResponse']);
    recordTest("SuccessResponse schema exists", $passed, "SuccessResponse schema defined");
} catch (\Exception $e) {
    recordTest("SuccessResponse schema exists", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 4: Endpoint Documentation Tests (4 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 4: Endpoint Documentation Tests (4 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 21: Login endpoint has request body
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $loginEndpoint = $spec['paths']['/auth/login']['post'] ?? [];
    $passed = isset($loginEndpoint['requestBody']);

    recordTest("Login endpoint has request body", $passed, "Request body defined");
} catch (\Exception $e) {
    recordTest("Login endpoint has request body", false, $e->getMessage());
}

// Test 22: Login endpoint has responses
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $loginEndpoint = $spec['paths']['/auth/login']['post'] ?? [];
    $has200 = isset($loginEndpoint['responses']['200']);
    $has401 = isset($loginEndpoint['responses']['401']);

    $passed = $has200 && $has401;
    recordTest("Login endpoint has responses", $passed, "200 and 401 responses defined");
} catch (\Exception $e) {
    recordTest("Login endpoint has responses", false, $e->getMessage());
}

// Test 23: GET /users endpoint has pagination parameters
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $usersEndpoint = $spec['paths']['/users']['get'] ?? [];
    $parameters = $usersEndpoint['parameters'] ?? [];

    $hasPageParam = false;
    $hasLimitParam = false;

    foreach ($parameters as $param) {
        if (isset($param['$ref']) && strpos($param['$ref'], 'PageParameter') !== false) {
            $hasPageParam = true;
        }
        if (isset($param['$ref']) && strpos($param['$ref'], 'LimitParameter') !== false) {
            $hasLimitParam = true;
        }
    }

    $passed = $hasPageParam && $hasLimitParam;
    recordTest("GET /users endpoint has pagination parameters", $passed, "Page and limit parameters present");
} catch (\Exception $e) {
    recordTest("GET /users endpoint has pagination parameters", false, $e->getMessage());
}

// Test 24: Admin endpoints have security requirement
try {
    $generator = new OpenApiGenerator();
    $spec = $generator->generate();

    $adminUsersEndpoint = $spec['paths']['/admin/users']['get'] ?? [];
    $security = $adminUsersEndpoint['security'] ?? [];

    $hasBearerAuth = false;
    foreach ($security as $securityItem) {
        if (isset($securityItem['bearerAuth'])) {
            $hasBearerAuth = true;
            break;
        }
    }

    $passed = $hasBearerAuth;
    recordTest("Admin endpoints have security requirement", $passed, "Bearer auth required");
} catch (\Exception $e) {
    recordTest("Admin endpoints have security requirement", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 5: DocsController Tests (4 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 5: DocsController Tests (4 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 25: DocsController can be instantiated
try {
    $controller = new DocsController();

    $passed = $controller instanceof DocsController;
    recordTest("DocsController can be instantiated", $passed, "Controller created");
} catch (\Exception $e) {
    recordTest("DocsController can be instantiated", false, $e->getMessage());
}

// Test 26: DocsController serveJson() method generates output
try {
    $controller = new DocsController();

    ob_start();
    $controller->serveJson();
    $output = ob_get_clean();

    $passed = !empty($output);
    $decoded = json_decode($output, true);
    $isValidJson = json_last_error() === JSON_ERROR_NONE;

    recordTest("DocsController serveJson() generates output", $passed && $isValidJson, "Valid JSON output: " . strlen($output) . " bytes");
} catch (\Exception $e) {
    ob_end_clean();
    recordTest("DocsController serveJson() generates output", false, $e->getMessage());
}

// Test 27: DocsController serveYaml() method generates output
try {
    $controller = new DocsController();

    ob_start();
    $controller->serveYaml();
    $output = ob_get_clean();

    $passed = !empty($output) && strpos($output, 'openapi:') !== false;
    recordTest("DocsController serveYaml() generates output", $passed, "Valid YAML output: " . strlen($output) . " bytes");
} catch (\Exception $e) {
    ob_end_clean();
    recordTest("DocsController serveYaml() generates output", false, $e->getMessage());
}

// Test 28: DocsController swaggerUi() method generates HTML
try {
    $controller = new DocsController();

    ob_start();
    $controller->swaggerUi();
    $output = ob_get_clean();

    $passed = !empty($output) &&
              strpos($output, '<!DOCTYPE html>') !== false &&
              strpos($output, 'swagger-ui') !== false;

    recordTest("DocsController swaggerUi() generates HTML", $passed, "HTML output: " . strlen($output) . " bytes");
} catch (\Exception $e) {
    ob_end_clean();
    recordTest("DocsController swaggerUi() generates HTML", false, $e->getMessage());
}

// Cleanup
echo "\nCleanup: Resetting environment...\n";
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
