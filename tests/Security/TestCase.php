<?php
/**
 * Security Test Base Class
 * Phase 3 Week 9 - Testing Infrastructure
 *
 * Base class for security-focused tests (authentication, authorization, XSS, SQL injection, etc.)
 */

namespace Tests\Security;

use Tests\Feature\TestCase as FeatureTestCase;

abstract class TestCase extends FeatureTestCase
{
    /**
     * Common SQL injection payloads for testing
     *
     * @var array
     */
    protected $sqlInjectionPayloads = [
        "' OR '1'='1",
        "'; DROP TABLE users--",
        "1' UNION SELECT NULL--",
        "admin'--",
        "' OR 1=1--",
        "') OR ('1'='1",
    ];

    /**
     * Common XSS payloads for testing
     *
     * @var array
     */
    protected $xssPayloads = [
        "<script>alert('XSS')</script>",
        "<img src=x onerror=alert('XSS')>",
        "<svg onload=alert('XSS')>",
        "javascript:alert('XSS')",
        "<iframe src='javascript:alert(\"XSS\")'></iframe>",
    ];

    /**
     * Common path traversal payloads
     *
     * @var array
     */
    protected $pathTraversalPayloads = [
        "../../../etc/passwd",
        "..\\..\\..\\windows\\system32\\config\\sam",
        "....//....//....//etc/passwd",
    ];

    /**
     * Assert that output is properly HTML escaped
     *
     * @param string $output Output to check
     * @param string $message Error message
     */
    protected function assertHtmlEscaped(string $output, string $message = ''): void
    {
        foreach ($this->xssPayloads as $payload) {
            $this->assertStringNotContainsString(
                $payload,
                $output,
                $message ?: "Output contains unescaped XSS payload: {$payload}"
            );
        }
    }

    /**
     * Assert that SQL query is parameterized (no injection)
     *
     * @param string $sql SQL query
     * @param string $message Error message
     */
    protected function assertQueryIsParameterized(string $sql, string $message = ''): void
    {
        // Check for common SQL injection patterns
        $dangerousPatterns = [
            '/\'\s*OR\s*\'/i',
            '/--\s*$/m',
            '/UNION\s+SELECT/i',
            '/;\s*DROP\s+TABLE/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $sql,
                $message ?: "SQL query appears vulnerable to injection"
            );
        }

        // Verify parameterization (should have ? or :param)
        $this->assertMatchesRegularExpression(
            '/\?|:\w+/',
            $sql,
            $message ?: "SQL query does not appear to use prepared statements"
        );
    }

    /**
     * Assert that path is safe from traversal attacks
     *
     * @param string $path Path to check
     * @param string $message Error message
     */
    protected function assertSafePath(string $path, string $message = ''): void
    {
        foreach ($this->pathTraversalPayloads as $payload) {
            $this->assertStringNotContainsString(
                $payload,
                $path,
                $message ?: "Path contains traversal payload: {$payload}"
            );
        }

        // Should not contain ../ or ..\
        $this->assertDoesNotMatchRegularExpression(
            '/\.\.[\\/\\\\]/',
            $path,
            $message ?: "Path contains parent directory traversal"
        );
    }

    /**
     * Assert that password meets security requirements
     *
     * @param string $password Password to check
     * @param int $minLength Minimum length
     */
    protected function assertSecurePassword(string $password, int $minLength = 8): void
    {
        $this->assertGreaterThanOrEqual(
            $minLength,
            strlen($password),
            "Password must be at least {$minLength} characters"
        );

        $this->assertMatchesRegularExpression(
            '/[A-Z]/',
            $password,
            "Password must contain at least one uppercase letter"
        );

        $this->assertMatchesRegularExpression(
            '/[a-z]/',
            $password,
            "Password must contain at least one lowercase letter"
        );

        $this->assertMatchesRegularExpression(
            '/[0-9]/',
            $password,
            "Password must contain at least one number"
        );
    }

    /**
     * Assert that password is properly hashed
     *
     * @param string $hash Password hash
     */
    protected function assertPasswordHashed(string $hash): void
    {
        $this->assertTrue(
            password_get_info($hash)['algo'] !== null && password_get_info($hash)['algo'] !== 0,
            "Password does not appear to be properly hashed"
        );

        $this->assertStringStartsWith(
            '$2y$',
            $hash,
            "Password hash does not use bcrypt algorithm"
        );
    }

    /**
     * Assert that session has CSRF token
     */
    protected function assertCsrfTokenPresent(): void
    {
        $this->assertArrayHasKey(
            'csrf_token',
            $_SESSION,
            "Session does not contain CSRF token"
        );

        $this->assertNotEmpty(
            $_SESSION['csrf_token'],
            "CSRF token is empty"
        );
    }

    /**
     * Assert that response requires authentication
     *
     * @param callable $action Action to test
     */
    protected function assertRequiresAuthentication(callable $action): void
    {
        // Clear authentication
        $this->clearAuth();

        // Execute action
        $result = $action();

        // Should redirect or return unauthorized
        $this->assertTrue(
            $result === false || (is_array($result) && isset($result['error'])),
            "Action does not require authentication"
        );
    }

    /**
     * Assert that action requires specific role
     *
     * @param string $requiredRole Required role
     * @param callable $action Action to test
     */
    protected function assertRequiresRole(string $requiredRole, callable $action): void
    {
        // Create user with different role
        $userId = $this->createUser(['role' => 'member']);
        $this->actingAs($userId, 'member');

        // Execute action
        $result = $action();

        // Should fail for non-authorized role
        $this->assertTrue(
            $result === false || (is_array($result) && isset($result['error'])),
            "Action does not properly check for role: {$requiredRole}"
        );

        // Now test with correct role
        $adminId = $this->createUser(['role' => $requiredRole]);
        $this->actingAs($adminId, $requiredRole);

        // Should succeed
        $result = $action();
        $this->assertNotFalse($result, "Action failed with correct role");
    }

    /**
     * Test for SQL injection vulnerability
     *
     * @param callable $action Action that accepts user input
     * @param string $inputParam Input parameter name
     */
    protected function testSqlInjection(callable $action, string $inputParam): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            try {
                $result = $action([$inputParam => $payload]);

                // Should not execute SQL injection
                $this->assertNotNull($result, "SQL injection payload caused null result");

                // Result should not indicate successful injection
                if (is_array($result)) {
                    $this->assertArrayNotHasKey(
                        'injected',
                        $result,
                        "SQL injection payload appears successful: {$payload}"
                    );
                }
            } catch (\Exception $e) {
                // Exception is acceptable (indicates rejection)
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * Test for XSS vulnerability
     *
     * @param callable $action Action that outputs user input
     * @param string $inputParam Input parameter name
     */
    protected function testXssProtection(callable $action, string $inputParam): void
    {
        foreach ($this->xssPayloads as $payload) {
            $output = $action([$inputParam => $payload]);

            if (is_string($output)) {
                $this->assertHtmlEscaped($output, "XSS payload not properly escaped: {$payload}");
            }
        }
    }

    /**
     * Test rate limiting
     *
     * @param callable $action Action to rate limit
     * @param int $maxAttempts Maximum attempts allowed
     */
    protected function testRateLimiting(callable $action, int $maxAttempts = 5): void
    {
        // Attempt multiple times
        for ($i = 0; $i < $maxAttempts + 2; $i++) {
            $result = $action();

            if ($i >= $maxAttempts) {
                // Should be rate limited
                $this->assertTrue(
                    $result === false || (is_array($result) && isset($result['rate_limited'])),
                    "Rate limiting not enforced after {$maxAttempts} attempts"
                );
            }
        }
    }
}
