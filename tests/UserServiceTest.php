<?php
/**
 * Tests for UserService functionality
 */

require_once __DIR__ . '/TestFramework.php';
require_once __DIR__ . '/../app/Services/UserService.php';

class UserServiceTest {
    private $mockConnection;
    private $userService;
    
    public function __construct($conn) {
        $this->mockConnection = $conn;
        $this->userService = new UserService($conn);
    }
    
    public function runTests() {
        $test = new TestFramework();
        
        $test->addTest('UserService Constructor', [$this, 'testConstructor']);
        $test->addTest('UserService Authentication Structure', [$this, 'testAuthenticationStructure']);
        $test->addTest('UserService Session Management', [$this, 'testSessionManagement']);
        $test->addTest('UserService Password Validation', [$this, 'testPasswordValidation']);
        
        $test->runTests();
    }
    
    public function testConstructor() {
        TestFramework::assertNotNull($this->userService, 'UserService should be created');
    }
    
    public function testAuthenticationStructure() {
        // Test that authentication returns expected structure
        $result = $this->userService->authenticate('nonexistent@test.com', 'password');
        
        TestFramework::assertTrue(is_array($result), 'Authentication should return array');
        TestFramework::assertArrayHasKey('success', $result, 'Result should have success key');
        TestFramework::assertArrayHasKey('message', $result, 'Result should have message key');
        TestFramework::assertEquals(false, $result['success'], 'Non-existent user should fail authentication');
    }
    
    public function testSessionManagement() {
        // Test session validation without active session
        $isValid = $this->userService->validateSession();
        TestFramework::assertEquals(false, $isValid, 'Session should be invalid when no session exists');
        
        // Test session destruction
        $destroyed = $this->userService->destroySession();
        TestFramework::assertEquals(true, $destroyed, 'Session destruction should succeed');
    }
    
    public function testPasswordValidation() {
        // Test user creation structure
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test',
            'surname' => 'User'
        ];
        
        $result = $this->userService->createUser($userData);
        
        TestFramework::assertTrue(is_array($result), 'createUser should return array');
        TestFramework::assertArrayHasKey('success', $result, 'Result should have success key');
        TestFramework::assertArrayHasKey('message', $result, 'Result should have message key');
    }
}