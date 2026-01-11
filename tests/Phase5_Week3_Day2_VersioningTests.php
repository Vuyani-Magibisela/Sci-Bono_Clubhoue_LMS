<?php
/**
 * Phase 5 Week 3 Day 2 - API Versioning Test Suite
 *
 * Tests the API versioning implementation:
 * - Version parsing from URL
 * - Version parsing from Accept-Version header
 * - Version validation
 * - Deprecation warnings
 * - Version info endpoints
 * - Default version handling
 *
 * @package Tests
 * @since Phase 5 Week 3 Day 2 (January 10, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../app/Middleware/ApiVersionMiddleware.php';

use App\Middleware\ApiVersionMiddleware;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 3 Day 2 - API Versioning Tests\n";
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
// SECTION 1: Version Parsing Tests (8 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: Version Parsing Tests (8 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Parse version from URL (v1)
try {
    $_SERVER['REQUEST_URI'] = '/api/v1/users';
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('parseVersionFromUrl');
    $method->setAccessible(true);
    $version = $method->invoke($middleware);

    $passed = $version === 'v1';
    recordTest("Parse version from URL (v1)", $passed, "Parsed: {$version}");
} catch (\Exception $e) {
    recordTest("Parse version from URL (v1)", false, $e->getMessage());
}

// Test 2: Parse version from URL (v2)
try {
    $_SERVER['REQUEST_URI'] = '/api/v2/courses';
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('parseVersionFromUrl');
    $method->setAccessible(true);
    $version = $method->invoke($middleware);

    $passed = $version === 'v2';
    recordTest("Parse version from URL (v2)", $passed, "Parsed: {$version}");
} catch (\Exception $e) {
    recordTest("Parse version from URL (v2)", false, $e->getMessage());
}

// Test 3: Parse version from Accept-Version header
try {
    unset($_SERVER['REQUEST_URI']);
    $_SERVER['HTTP_ACCEPT_VERSION'] = 'v1';
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('parseVersionFromHeader');
    $method->setAccessible(true);
    $version = $method->invoke($middleware);

    $passed = $version === 'v1';
    recordTest("Parse version from Accept-Version header", $passed, "Parsed: {$version}");
} catch (\Exception $e) {
    recordTest("Parse version from Accept-Version header", false, $e->getMessage());
}

// Test 4: Normalize numeric version to v-format
try {
    $_SERVER['HTTP_ACCEPT_VERSION'] = '2';
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('parseVersionFromHeader');
    $method->setAccessible(true);
    $version = $method->invoke($middleware);

    $passed = $version === 'v2';
    recordTest("Normalize numeric version to v-format", $passed, "Normalized: {$version}");
} catch (\Exception $e) {
    recordTest("Normalize numeric version to v-format", false, $e->getMessage());
}

// Test 5: URL version takes precedence over header
try {
    $_SERVER['REQUEST_URI'] = '/api/v1/users';
    $_SERVER['HTTP_ACCEPT_VERSION'] = 'v2';
    $middleware = new ApiVersionMiddleware();
    $version = $middleware->parseVersion();

    $passed = $version === 'v1';
    recordTest("URL version takes precedence over header", $passed, "Used URL version");
} catch (\Exception $e) {
    recordTest("URL version takes precedence over header", false, $e->getMessage());
}

// Test 6: Default version when none specified
try {
    unset($_SERVER['REQUEST_URI']);
    unset($_SERVER['HTTP_ACCEPT_VERSION']);
    $_SERVER['REQUEST_URI'] = '/api/users';
    $middleware = new ApiVersionMiddleware();
    $version = $middleware->parseVersion();

    $defaultVersion = $middleware->getDefaultVersion();
    $passed = $version === $defaultVersion;
    recordTest("Default version when none specified", $passed, "Default: {$defaultVersion}");
} catch (\Exception $e) {
    recordTest("Default version when none specified", false, $e->getMessage());
}

// Test 7: Validate valid version
try {
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isValidVersion');
    $method->setAccessible(true);
    $isValid = $method->invokeArgs($middleware, ['v1']);

    $passed = $isValid === true;
    recordTest("Validate valid version", $passed, "v1 is valid");
} catch (\Exception $e) {
    recordTest("Validate valid version", false, $e->getMessage());
}

// Test 8: Reject invalid version
try {
    $middleware = new ApiVersionMiddleware();

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isValidVersion');
    $method->setAccessible(true);
    $isValid = $method->invokeArgs($middleware, ['v99']);

    $passed = $isValid === false;
    recordTest("Reject invalid version", $passed, "v99 is invalid");
} catch (\Exception $e) {
    recordTest("Reject invalid version", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: Version Management Tests (8 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: Version Management Tests (8 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 9: Get supported versions
try {
    $middleware = new ApiVersionMiddleware();
    $versions = $middleware->getSupportedVersions();

    $passed = is_array($versions) && in_array('v1', $versions);
    recordTest("Get supported versions", $passed, "Versions: " . implode(', ', $versions));
} catch (\Exception $e) {
    recordTest("Get supported versions", false, $e->getMessage());
}

// Test 10: Add new supported version
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->addSupportedVersion('v3');
    $versions = $middleware->getSupportedVersions();

    $passed = in_array('v3', $versions);
    recordTest("Add new supported version", $passed, "v3 added");
} catch (\Exception $e) {
    recordTest("Add new supported version", false, $e->getMessage());
}

// Test 11: Set default version
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->setDefaultVersion('v2');
    $defaultVersion = $middleware->getDefaultVersion();

    $passed = $defaultVersion === 'v2';
    recordTest("Set default version", $passed, "Default set to v2");
} catch (\Exception $e) {
    recordTest("Set default version", false, $e->getMessage());
}

// Test 12: Cannot set invalid version as default
try {
    $middleware = new ApiVersionMiddleware();
    $result = $middleware->setDefaultVersion('v99');

    $passed = $result === false;
    recordTest("Cannot set invalid version as default", $passed, "Invalid version rejected");
} catch (\Exception $e) {
    recordTest("Cannot set invalid version as default", false, $e->getMessage());
}

// Test 13: Deprecate version
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->deprecateVersion('v1', '2026-12-31');

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isDeprecated');
    $method->setAccessible(true);
    $isDeprecated = $method->invokeArgs($middleware, ['v1']);

    $passed = $isDeprecated === true;
    recordTest("Deprecate version", $passed, "v1 deprecated");
} catch (\Exception $e) {
    recordTest("Deprecate version", false, $e->getMessage());
}

// Test 14: Get sunset date for deprecated version
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->deprecateVersion('v1', '2026-12-31');

    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('getSunsetDate');
    $method->setAccessible(true);
    $sunsetDate = $method->invokeArgs($middleware, ['v1']);

    $passed = $sunsetDate === '2026-12-31';
    recordTest("Get sunset date for deprecated version", $passed, "Sunset: {$sunsetDate}");
} catch (\Exception $e) {
    recordTest("Get sunset date for deprecated version", false, $e->getMessage());
}

// Test 15: Remove version
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->addSupportedVersion('v3');
    $removed = $middleware->removeVersion('v3');
    $versions = $middleware->getSupportedVersions();

    $passed = $removed && !in_array('v3', $versions);
    recordTest("Remove version", $passed, "v3 removed");
} catch (\Exception $e) {
    recordTest("Remove version", false, $e->getMessage());
}

// Test 16: Cannot remove default version
try {
    $middleware = new ApiVersionMiddleware();
    $defaultVersion = $middleware->getDefaultVersion();
    $result = $middleware->removeVersion($defaultVersion);

    $passed = $result === false;
    recordTest("Cannot remove default version", $passed, "Default version protected");
} catch (\Exception $e) {
    recordTest("Cannot remove default version", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 3: Version Information Tests (6 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 3: Version Information Tests (6 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 17: Get version information
try {
    $middleware = new ApiVersionMiddleware();
    $versionInfo = $middleware->getVersionInfo();

    $passed = is_array($versionInfo) &&
              isset($versionInfo['current_version']) &&
              isset($versionInfo['supported_versions']);
    recordTest("Get version information", $passed, "Version info retrieved");
} catch (\Exception $e) {
    recordTest("Get version information", false, $e->getMessage());
}

// Test 18: Version info includes deprecation policy
try {
    $middleware = new ApiVersionMiddleware();
    $versionInfo = $middleware->getVersionInfo();

    $passed = isset($versionInfo['deprecation_policy']);
    recordTest("Version info includes deprecation policy", $passed, "Policy included");
} catch (\Exception $e) {
    recordTest("Version info includes deprecation policy", false, $e->getMessage());
}

// Test 19: Version info includes upgrade guide
try {
    $middleware = new ApiVersionMiddleware();
    $versionInfo = $middleware->getVersionInfo();

    $passed = isset($versionInfo['upgrade_guide']);
    recordTest("Version info includes upgrade guide", $passed, "Guide included");
} catch (\Exception $e) {
    recordTest("Version info includes upgrade guide", false, $e->getMessage());
}

// Test 20: Supported versions list contains details
try {
    $middleware = new ApiVersionMiddleware();
    $versionInfo = $middleware->getVersionInfo();
    $versions = $versionInfo['supported_versions'] ?? [];

    $passed = !empty($versions) && isset($versions[0]['version']) && isset($versions[0]['status']);
    recordTest("Supported versions list contains details", $passed, "Details present");
} catch (\Exception $e) {
    recordTest("Supported versions list contains details", false, $e->getMessage());
}

// Test 21: Deprecated versions marked in info
try {
    $middleware = new ApiVersionMiddleware();
    $middleware->deprecateVersion('v1', '2026-12-31');
    $versionInfo = $middleware->getVersionInfo();
    $versions = $versionInfo['supported_versions'] ?? [];

    // Find v1 in versions
    $v1Info = null;
    foreach ($versions as $v) {
        if ($v['version'] === 'v1') {
            $v1Info = $v;
            break;
        }
    }

    $passed = $v1Info && $v1Info['status'] === 'deprecated';
    recordTest("Deprecated versions marked in info", $passed, "Deprecation marked");
} catch (\Exception $e) {
    recordTest("Deprecated versions marked in info", false, $e->getMessage());
}

// Test 22: Get raw version (unvalidated)
try {
    $_SERVER['REQUEST_URI'] = '/api/v1/users';
    $middleware = new ApiVersionMiddleware();
    $rawVersion = $middleware->getRawVersion();

    $passed = $rawVersion === 'v1';
    recordTest("Get raw version (unvalidated)", $passed, "Raw version: {$rawVersion}");
} catch (\Exception $e) {
    recordTest("Get raw version (unvalidated)", false, $e->getMessage());
}

// Cleanup
echo "\nCleanup: Resetting environment...\n";
unset($_SERVER['REQUEST_URI']);
unset($_SERVER['HTTP_ACCEPT_VERSION']);
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
