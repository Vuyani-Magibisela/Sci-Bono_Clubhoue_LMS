<?php

namespace Tests\API;

require_once __DIR__ . '/../../app/API/UserApiController.php';
require_once __DIR__ . '/../../app/Services/ApiTokenService.php';

use Tests\BaseTestCase;
use App\API\UserApiController;
use App\Services\ApiTokenService;
use App\Models\UserModel;
use Exception;

class UserApiTest extends BaseTestCase
{
    private $apiController;
    private $userModel;
    private $validAdminToken;
    private $validUserToken;
    private $adminUserId;
    private $regularUserId;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->apiController = new UserApiController($this->db);
        $this->userModel = new UserModel($this->db);
        
        // Create test users and tokens
        $this->setupTestUsers();
    }
    
    /**
     * Set up test users and authentication tokens
     */
    private function setupTestUsers()
    {
        // Create admin user
        $this->adminUserId = $this->createTestUser([
            'name' => 'Admin',
            'surname' => 'User',
            'email' => 'admin@test.com',
            'role' => 'admin'
        ]);
        
        // Create regular user
        $this->regularUserId = $this->createTestUser([
            'name' => 'Regular',
            'surname' => 'User', 
            'email' => 'user@test.com',
            'role' => 'student'
        ]);
        
        // Generate tokens
        $this->validAdminToken = ApiTokenService::generate($this->adminUserId, 'admin');
        $this->validUserToken = ApiTokenService::generate($this->regularUserId, 'student');
    }
    
    /**
     * Mock HTTP request environment
     */
    private function mockRequest($method, $data = [], $headers = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        // Mock headers
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        foreach ($allHeaders as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        
        // Mock request data
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            // Simulate php://input
            $tempFile = tmpfile();
            fwrite($tempFile, json_encode($data));
            rewind($tempFile);
        }
        
        $_POST = $method === 'POST' ? $data : [];
        $_GET = [];
    }
    
    /**
     * Capture API response
     */
    private function captureApiResponse($callback)
    {
        ob_start();
        
        try {
            $callback();
        } catch (Exception $e) {
            // Some responses exit(), so we catch that here
            if (strpos($e->getMessage(), 'exit') === false) {
                throw $e;
            }
        }
        
        $output = ob_get_clean();
        
        // Reset HTTP response code for next test
        http_response_code(200);
        
        return json_decode($output, true);
    }
    
    // ========== GET TESTS ==========
    
    public function testGetUserByIdAsAdmin()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals($this->regularUserId, $response['data']['id']);
        $this->assertEquals('user@test.com', $response['data']['email']);
        $this->assertArrayNotHasKey('password', $response['data']); // Password should be removed
    }
    
    public function testGetUserByIdAsSelf()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals($this->regularUserId, $response['data']['id']);
    }
    
    public function testGetUserByIdUnauthorized()
    {
        $this->mockRequest('GET');
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['status_code']);
        $this->assertStringContains('Unauthorized', $response['message']);
    }
    
    public function testGetUserByIdForbidden()
    {
        // Create another user
        $otherUserId = $this->createTestUser([
            'email' => 'other@test.com'
        ]);
        
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $otherUserId; // Regular user trying to access another user
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(403, $response['status_code']);
    }
    
    public function testGetUserByIdNotFound()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = 99999;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(404, $response['status_code']);
    }
    
    public function testGetUsersPaginatedAsAdmin()
    {
        // Create additional test users
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestUser([
                'email' => "test{$i}@example.com",
                'name' => "Test{$i}"
            ]);
        }
        
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['page'] = 1;
        $_GET['limit'] = 10;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('items', $response['data']);
        $this->assertArrayHasKey('pagination', $response['data']);
        $this->assertTrue(count($response['data']['items']) >= 2); // At least our test users
    }
    
    public function testGetUsersAsRegularUser()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(403, $response['status_code']); // Only admins can list all users
    }
    
    // ========== POST TESTS ==========
    
    public function testCreateUserAsAdmin()
    {
        $userData = [
            'name' => 'New',
            'surname' => 'User',
            'email' => 'newuser@test.com',
            'password' => 'SecurePassword123!',
            'role' => 'student'
        ];
        
        $this->mockRequest('POST', $userData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals(201, $response['status_code']);
        $this->assertEquals($userData['email'], $response['data']['email']);
        $this->assertEquals($userData['name'], $response['data']['name']);
        $this->assertArrayNotHasKey('password', $response['data']);
        
        // Verify user was actually created in database
        $createdUser = $this->userModel->findByEmail($userData['email']);
        $this->assertNotNull($createdUser);
        $this->assertTrue(password_verify($userData['password'], $createdUser['password']));
    }
    
    public function testCreateUserAsRegularUser()
    {
        $userData = [
            'name' => 'Unauthorized',
            'email' => 'unauthorized@test.com',
            'password' => 'password123'
        ];
        
        $this->mockRequest('POST', $userData, [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(403, $response['status_code']); // Only admins can create users
    }
    
    public function testCreateUserWithInvalidData()
    {
        $invalidData = [
            'name' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email format
            'password' => '123' // Too short
        ];
        
        $this->mockRequest('POST', $invalidData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(422, $response['status_code']);
        $this->assertArrayHasKey('errors', $response);
    }
    
    public function testCreateUserWithDuplicateEmail()
    {
        $userData = [
            'name' => 'Duplicate',
            'email' => 'user@test.com', // This email already exists
            'password' => 'password123',
            'role' => 'student'
        ];
        
        $this->mockRequest('POST', $userData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(422, $response['status_code']);
    }
    
    // ========== PUT TESTS ==========
    
    public function testUpdateUserAsAdmin()
    {
        $updateData = [
            'name' => 'Updated',
            'surname' => 'Name',
            'role' => 'mentor'
        ];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Updated', $response['data']['name']);
        $this->assertEquals('Name', $response['data']['surname']);
        $this->assertEquals('mentor', $response['data']['role']);
    }
    
    public function testUpdateSelfAsRegularUser()
    {
        $updateData = [
            'name' => 'Self Updated',
            'surname' => 'User'
        ];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Self Updated', $response['data']['name']);
    }
    
    public function testUpdateOtherUserAsRegularUser()
    {
        $updateData = ['name' => 'Unauthorized Update'];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $this->adminUserId; // Regular user trying to update admin
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(403, $response['status_code']);
    }
    
    public function testUpdateUserWithInvalidData()
    {
        $invalidData = [
            'email' => 'invalid-email-format',
            'role' => 'invalid-role'
        ];
        
        $this->mockRequest('PUT', $invalidData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(422, $response['status_code']);
    }
    
    public function testUpdateNonExistentUser()
    {
        $updateData = ['name' => 'Does Not Exist'];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = 99999;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(404, $response['status_code']);
    }
    
    public function testUpdateUserPassword()
    {
        $newPassword = 'NewSecurePassword123!';
        
        $updateData = ['password' => $newPassword];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        
        // Verify password was actually updated
        $updatedUser = $this->userModel->findById($this->regularUserId);
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
    }
    
    // ========== DELETE TESTS ==========
    
    public function testDeleteUserAsAdmin()
    {
        $userToDelete = $this->createTestUser([
            'email' => 'delete@test.com'
        ]);
        
        $this->mockRequest('DELETE', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $userToDelete;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['status_code']);
        
        // Verify user was actually deleted
        $deletedUser = $this->userModel->findById($userToDelete);
        $this->assertNull($deletedUser);
    }
    
    public function testDeleteUserAsRegularUser()
    {
        $this->mockRequest('DELETE', [], [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $_GET['id'] = $this->adminUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(403, $response['status_code']); // Only admins can delete
    }
    
    public function testDeleteSelfAsAdmin()
    {
        $this->mockRequest('DELETE', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $this->adminUserId; // Admin trying to delete themselves
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(400, $response['status_code']); // Cannot delete own account
    }
    
    public function testDeleteNonExistentUser()
    {
        $this->mockRequest('DELETE', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = 99999;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(404, $response['status_code']);
    }
    
    // ========== AUTHENTICATION TESTS ==========
    
    public function testRequestWithoutToken()
    {
        $this->mockRequest('GET');
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['status_code']);
        $this->assertStringContains('token required', $response['message']);
    }
    
    public function testRequestWithInvalidToken()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer invalid-token-here'
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['status_code']);
    }
    
    public function testRequestWithExpiredToken()
    {
        // Create an expired token (set expiration to past)
        $expiredToken = ApiTokenService::generate($this->regularUserId, 'student', time() - 3600);
        
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $expiredToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['status_code']);
    }
    
    public function testRequestWithMalformedAuthHeader()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'InvalidFormat token-here'
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['status_code']);
    }
    
    // ========== PROFILE TESTS ==========
    
    public function testGetProfile()
    {
        // Mock the getProfile method call
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->getProfile();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals($this->regularUserId, $response['data']['id']);
        $this->assertArrayNotHasKey('password', $response['data']);
    }
    
    public function testUpdateProfile()
    {
        $updateData = [
            'name' => 'Updated Profile',
            'bio' => 'This is my updated bio'
        ];
        
        $this->mockRequest('PUT', $updateData, [
            'Authorization' => 'Bearer ' . $this->validUserToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->updateProfile();
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Updated Profile', $response['data']['name']);
    }
    
    // ========== ERROR HANDLING TESTS ==========
    
    public function testInvalidHttpMethod()
    {
        $this->mockRequest('PATCH', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['id'] = $this->regularUserId;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        // PATCH should default to PUT behavior
        $this->assertTrue($response['success']);
    }
    
    public function testLargePayload()
    {
        $largeData = [
            'name' => str_repeat('A', 1000),
            'email' => 'large@test.com',
            'password' => 'password123'
        ];
        
        $this->mockRequest('POST', $largeData, [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        // Should handle large payloads gracefully
        $this->assertArrayHasKey('success', $response);
    }
    
    // ========== PAGINATION TESTS ==========
    
    public function testPaginationParameters()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['page'] = 2;
        $_GET['limit'] = 5;
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('pagination', $response['data']);
        $this->assertEquals(2, $response['data']['pagination']['current_page']);
        $this->assertEquals(5, $response['data']['pagination']['per_page']);
    }
    
    public function testSearchParameters()
    {
        // Create some searchable users
        $this->createTestUser([
            'name' => 'Searchable',
            'email' => 'searchable@test.com'
        ]);
        
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['search'] = 'Searchable';
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        // Should return filtered results
        $this->assertArrayHasKey('items', $response['data']);
    }
    
    public function testRoleFilterParameter()
    {
        $this->mockRequest('GET', [], [
            'Authorization' => 'Bearer ' . $this->validAdminToken
        ]);
        
        $_GET['role'] = 'admin';
        
        $response = $this->captureApiResponse(function() {
            $this->apiController->handleRequest();
        });
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('items', $response['data']);
    }
}