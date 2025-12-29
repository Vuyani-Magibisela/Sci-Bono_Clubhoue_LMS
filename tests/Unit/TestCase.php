<?php
/**
 * Unit Test Base Class
 * Phase 3 Week 9 - Testing Infrastructure
 *
 * Base class for unit tests that test individual classes/methods in isolation
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearGlobals();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearGlobals();
    }

    /**
     * Clear global variables
     */
    protected function clearGlobals(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SESSION = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
    }

    /**
     * Create a mock database connection
     *
     * @return \mysqli|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockDatabase()
    {
        return $this->createMock(\mysqli::class);
    }

    /**
     * Create a mock logger
     *
     * @return \Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockLogger()
    {
        return $this->createMock(\Logger::class);
    }

    /**
     * Assert that an array has specific structure
     *
     * @param array $structure Expected structure
     * @param array $actual Actual array
     * @param string $message Error message
     */
    protected function assertArrayStructure(array $structure, array $actual, string $message = ''): void
    {
        foreach ($structure as $key) {
            $this->assertArrayHasKey($key, $actual, $message ?: "Array missing key: {$key}");
        }
    }

    /**
     * Assert that a string matches a pattern
     *
     * @param string $pattern Regular expression pattern
     * @param string $string String to test
     * @param string $message Error message
     */
    protected function assertMatchesPattern(string $pattern, string $string, string $message = ''): void
    {
        $this->assertMatchesRegularExpression($pattern, $string, $message);
    }
}
