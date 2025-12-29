<?php
/**
 * Feature Test Base Class
 * Phase 3 Week 9 - Testing Infrastructure
 *
 * Base class for feature/integration tests that test full workflows with database
 */

namespace Tests\Feature;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Database connection
     *
     * @var \mysqli
     */
    protected $db;

    /**
     * Transaction started flag
     *
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Get test database connection
        $this->db = getTestDbConnection();

        if (!$this->db) {
            $this->fail('Test database connection not available');
        }

        // Start transaction for test isolation
        $this->beginTransaction();

        // Clear global variables
        $this->clearGlobals();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->inTransaction) {
            $this->rollbackTransaction();
        }

        parent::tearDown();
    }

    /**
     * Begin database transaction
     */
    protected function beginTransaction(): void
    {
        if (!$this->inTransaction && $this->db) {
            $this->db->autocommit(false);
            $this->db->begin_transaction();
            $this->inTransaction = true;
        }
    }

    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction(): void
    {
        if ($this->inTransaction && $this->db) {
            $this->db->rollback();
            $this->db->autocommit(true);
            $this->inTransaction = false;
        }
    }

    /**
     * Commit database transaction
     */
    protected function commitTransaction(): void
    {
        if ($this->inTransaction && $this->db) {
            $this->db->commit();
            $this->db->autocommit(true);
            $this->inTransaction = false;
        }
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
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit Test'
        ]);
    }

    /**
     * Create a test user
     *
     * @param array $data User data override
     * @return int User ID
     */
    protected function createUser(array $data = []): int
    {
        return createTestUser($data);
    }

    /**
     * Create a test admin user
     *
     * @return int User ID
     */
    protected function createAdminUser(): int
    {
        return createTestAdminUser();
    }

    /**
     * Create a test mentor user
     *
     * @return int User ID
     */
    protected function createMentorUser(): int
    {
        return createTestMentorUser();
    }

    /**
     * Mock authenticated session
     *
     * @param int $userId User ID
     * @param string $role User role
     */
    protected function actingAs(int $userId, string $role = 'member'): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
    }

    /**
     * Clear authentication session
     */
    protected function clearAuth(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['authenticated']);
        unset($_SESSION['login_time']);
    }

    /**
     * Assert that user is authenticated in session
     */
    protected function assertAuthenticated(): void
    {
        $this->assertTrue(
            isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true,
            'User is not authenticated'
        );
    }

    /**
     * Assert that user is not authenticated
     */
    protected function assertGuest(): void
    {
        $this->assertFalse(
            isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true,
            'User is authenticated but should be guest'
        );
    }

    /**
     * Assert that user has specific role
     *
     * @param string $role Expected role
     */
    protected function assertUserHasRole(string $role): void
    {
        $this->assertEquals(
            $role,
            $_SESSION['user_role'] ?? null,
            "User does not have role: {$role}"
        );
    }

    /**
     * Assert database has record
     *
     * @param string $table Table name
     * @param array $conditions Where conditions
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $where = [];
        $params = [];
        $types = '';

        foreach ($conditions as $column => $value) {
            $where[] = "`{$column}` = ?";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $this->assertGreaterThan(
            0,
            $result['count'],
            "Failed asserting that table '{$table}' has matching record"
        );
    }

    /**
     * Assert database missing record
     *
     * @param string $table Table name
     * @param array $conditions Where conditions
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $where = [];
        $params = [];
        $types = '';

        foreach ($conditions as $column => $value) {
            $where[] = "`{$column}` = ?";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $this->assertEquals(
            0,
            $result['count'],
            "Failed asserting that table '{$table}' does not have matching record"
        );
    }

    /**
     * Assert database count
     *
     * @param string $table Table name
     * @param int $expected Expected count
     * @param array $conditions Where conditions (optional)
     */
    protected function assertDatabaseCount(string $table, int $expected, array $conditions = []): void
    {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as count FROM `{$table}`";
            $result = $this->db->query($sql);
            $count = $result->fetch_assoc()['count'];
        } else {
            $where = [];
            $params = [];
            $types = '';

            foreach ($conditions as $column => $value) {
                $where[] = "`{$column}` = ?";
                $params[] = $value;
                $types .= is_int($value) ? 'i' : 's';
            }

            $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE " . implode(' AND ', $where);
            $stmt = $this->db->prepare($sql);

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
        }

        $this->assertEquals(
            $expected,
            $count,
            "Failed asserting that table '{$table}' has {$expected} records. Found: {$count}"
        );
    }

    /**
     * Execute raw SQL query
     *
     * @param string $sql SQL query
     * @return \mysqli_result|bool
     */
    protected function query(string $sql)
    {
        return $this->db->query($sql);
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    protected function lastInsertId(): int
    {
        return $this->db->insert_id;
    }
}
