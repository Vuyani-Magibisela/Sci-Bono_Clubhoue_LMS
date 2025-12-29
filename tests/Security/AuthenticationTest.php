<?php
/**
 * Authentication Tests
 * Phase 3 Week 9 Day 3 - Testing Infrastructure
 *
 * Tests for user authentication, login, logout, session management
 */

namespace Tests\Security;

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../../app/Controllers/AuthController.php';
require_once __DIR__ . '/../../app/Services/UserService.php';
require_once __DIR__ . '/../../core/CSRF.php';

use Tests\Security\TestCase;

class AuthenticationTest extends TestCase
{
    private $userService;
    private $authController;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set global connection for services that use global $conn
        $GLOBALS['conn'] = $this->db;

        // Initialize services
        $this->userService = new \UserService($this->db);

        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        // Clear session
        $_SESSION = [];

        parent::tearDown();
    }

    /**
     * Test 1: Successful login with valid credentials
     *
     * @test
     */
    public function test_login_with_valid_credentials()
    {
        // Create test user with known password
        $password = 'TestPassword123!';
        $userId = $this->createUser([
            'email' => 'testuser@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'member',
            'status' => 'active'
        ]);

        // Attempt authentication
        $result = $this->userService->authenticate('testuser@example.com', $password);

        // Assert authentication succeeded
        $this->assertTrue($result['success'], 'Authentication should succeed with valid credentials');
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($userId, $result['user']['id']);
        $this->assertEquals('member', $result['user']['role']);

        // Assert password is not included in returned user data
        $this->assertArrayNotHasKey('password', $result['user'], 'Password should not be in returned user data');
    }

    /**
     * Test 2: Failed login with invalid password
     *
     * @test
     */
    public function test_login_with_invalid_password()
    {
        // Create test user
        $userId = $this->createUser([
            'email' => 'testuser@example.com',
            'password' => password_hash('CorrectPassword123!', PASSWORD_DEFAULT),
            'status' => 'active'
        ]);

        // Attempt authentication with wrong password
        $result = $this->userService->authenticate('testuser@example.com', 'WrongPassword123!');

        // Assert authentication failed
        $this->assertFalse($result['success'], 'Authentication should fail with invalid password');
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('Invalid credentials', $result['message']);
    }

    /**
     * Test 3: Failed login with non-existent user
     *
     * @test
     */
    public function test_login_with_nonexistent_user()
    {
        // Attempt authentication with non-existent email
        $result = $this->userService->authenticate('nonexistent@example.com', 'SomePassword123!');

        // Assert authentication failed
        $this->assertFalse($result['success'], 'Authentication should fail for non-existent user');
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('Invalid credentials', $result['message']);
    }

    /**
     * Test 4: Account lockout after multiple failed attempts
     *
     * @test
     */
    public function test_account_lockout_after_failed_attempts()
    {
        // Create test user
        $userId = $this->createUser([
            'email' => 'lockouttest@example.com',
            'password' => password_hash('CorrectPassword123!', PASSWORD_DEFAULT),
            'status' => 'active'
        ]);

        // Attempt login 5 times with wrong password (max attempts)
        for ($i = 0; $i < 5; $i++) {
            $result = $this->userService->authenticate('lockouttest@example.com', 'WrongPassword!');
            $this->assertFalse($result['success']);
        }

        // 6th attempt should be blocked due to lockout
        $result = $this->userService->authenticate('lockouttest@example.com', 'CorrectPassword123!');

        $this->assertFalse($result['success'], 'Account should be locked after max failed attempts');
        $this->assertStringContainsString('locked', strtolower($result['message']));
        $this->assertArrayHasKey('locked_until', $result);
    }

    /**
     * Test 5: Successful session creation
     *
     * @test
     */
    public function test_session_creation_on_successful_login()
    {
        // Create test user
        $password = 'TestPassword123!';
        $userId = $this->createUser([
            'email' => 'sessiontest@example.com',
            'username' => 'sessionuser',
            'name' => 'Session',
            'surname' => 'Tester',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'member',
            'status' => 'active'
        ]);

        // Authenticate
        $result = $this->userService->authenticate('sessiontest@example.com', $password);
        $this->assertTrue($result['success']);

        // Create session
        $sessionCreated = $this->userService->createSession($result['user']);

        $this->assertTrue($sessionCreated, 'Session should be created successfully');

        // Assert session variables are set
        $this->assertEquals($userId, $_SESSION['user_id']);
        $this->assertEquals('sessionuser', $_SESSION['username']);
        $this->assertEquals('Session', $_SESSION['name']);
        $this->assertEquals('Tester', $_SESSION['surname']);
        $this->assertEquals('member', $_SESSION['user_type']);
        $this->assertArrayHasKey('session_token', $_SESSION);
        $this->assertArrayHasKey('last_activity', $_SESSION);

        // Assert session token is not empty
        $this->assertNotEmpty($_SESSION['session_token']);
    }

    /**
     * Test 6: Session validation
     *
     * @test
     */
    public function test_session_validation()
    {
        // Create test user
        $userId = $this->createUser([
            'email' => 'validationsessiontest@example.com',
            'role' => 'member',
            'status' => 'active'
        ]);

        // Create valid session
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = 'test_token_' . uniqid();
        $_SESSION['last_activity'] = time();

        // Update user's session token in database
        $sql = "UPDATE users SET session_token = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $_SESSION['session_token'], $userId);
        $stmt->execute();

        // Validate session
        $isValid = $this->userService->validateSession();

        $this->assertTrue($isValid, 'Valid session should pass validation');
    }

    /**
     * Test 7: Invalid session detection
     *
     * @test
     */
    public function test_invalid_session_detection()
    {
        // Clear session completely
        $_SESSION = [];

        // Validate session (should fail)
        $isValid = $this->userService->validateSession();

        $this->assertFalse($isValid, 'Empty session should fail validation');

        // Set incomplete session data
        $_SESSION['user_id'] = 999;
        // Missing session_token

        $isValid = $this->userService->validateSession();

        $this->assertFalse($isValid, 'Incomplete session should fail validation');
    }

    /**
     * Test 8: Logout destroys session
     *
     * @test
     */
    public function test_logout_destroys_session()
    {
        // Create test user and session
        $userId = $this->createUser([
            'email' => 'logouttest@example.com',
            'status' => 'active'
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = 'logoutuser';
        $_SESSION['session_token'] = 'test_token_' . uniqid();

        // Verify session exists
        $this->assertArrayHasKey('user_id', $_SESSION);

        // Destroy session
        $this->userService->destroySession();

        // Assert session is cleared
        $this->assertArrayNotHasKey('user_id', $_SESSION, 'user_id should be removed from session');
        $this->assertArrayNotHasKey('username', $_SESSION, 'username should be removed from session');
        $this->assertArrayNotHasKey('session_token', $_SESSION, 'session_token should be removed from session');
    }

    /**
     * Test 9: Inactive user cannot login
     *
     * @test
     */
    public function test_inactive_user_cannot_login()
    {
        // Create inactive user
        $password = 'TestPassword123!';
        $userId = $this->createUser([
            'email' => 'inactive@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'inactive'  // User is inactive
        ]);

        // Attempt authentication
        $result = $this->userService->authenticate('inactive@example.com', $password);

        // Assert authentication failed
        $this->assertFalse($result['success'], 'Inactive user should not be able to login');
        $this->assertStringContainsString('deactivated', strtolower($result['message']));
    }

    /**
     * Test 10: Password hashing uses secure algorithm
     *
     * @test
     */
    public function test_password_hashing_security()
    {
        // Create user
        $password = 'SecurePassword123!';
        $userId = $this->createUser([
            'email' => 'hashtest@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);

        // Get user from database
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Assert password is hashed
        $this->assertPasswordHashed($result['password']);

        // Assert password hash starts with $2y$ (bcrypt)
        $this->assertStringStartsWith('$2y$', $result['password'], 'Password should use bcrypt algorithm');

        // Assert original password can be verified
        $this->assertTrue(
            password_verify($password, $result['password']),
            'Original password should verify against hash'
        );

        // Assert wrong password does not verify
        $this->assertFalse(
            password_verify('WrongPassword123!', $result['password']),
            'Wrong password should not verify'
        );
    }
}
