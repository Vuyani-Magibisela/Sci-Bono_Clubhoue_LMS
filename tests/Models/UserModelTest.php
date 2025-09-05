<?php

namespace Tests\Models;

require_once __DIR__ . '/../../app/Models/UserModel.php';

use Tests\BaseTestCase;
use Exception;

class UserModelTest extends BaseTestCase
{
    private $userModel;
    
    public function setUp()
    {
        parent::setUp();
        $this->userModel = new \UserModel($this->db);
    }
    
    // ========== CREATE TESTS ==========
    
    public function testCreateUserWithValidData()
    {
        $userData = [
            'username' => 'testuser123',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePassword123!',
            'user_type' => 'student'
        ];
        
        $userId = $this->userModel->create($userData);
        
        $this->assertNotNull($userId, 'User ID should not be null');
        $this->assertTrue(is_numeric($userId), 'User ID should be numeric');
        $this->assertTrue($userId > 0, 'User ID should be positive');
        
        // Verify user was created in database
        $createdUser = $this->userModel->findById($userId);
        $this->assertNotNull($createdUser, 'Created user should exist in database');
        $this->assertEquals($userData['email'], $createdUser['email']);
        $this->assertEquals($userData['name'], $createdUser['name']);
        $this->assertEquals($userData['user_type'], $createdUser['user_type']);
        
        // Verify password was hashed
        $this->assertNotEquals($userData['password'], $createdUser['password']);
        $this->assertTrue(password_verify($userData['password'], $createdUser['password']));
        
        // Verify defaults were set
        $this->assertEquals('active', $createdUser['status']);
        $this->assertFalse((bool)$createdUser['email_verified']);
    }
    
    public function testCreateUserWithMinimalData()
    {
        $userData = [
            'email' => 'minimal@example.com',
            'password' => 'password123'
        ];
        
        $userId = $this->userModel->create($userData);
        
        $this->assertNotNull($userId);
        
        $user = $this->userModel->findById($userId);
        $this->assertEquals('student', $user['user_type']); // Default user type
        $this->assertEquals('active', $user['status']); // Default status
    }
    
    public function testCreateUserHashesPassword()
    {
        $plainPassword = 'MySecretPassword123';
        
        $userId = $this->userModel->create([
            'email' => 'password.test@example.com',
            'password' => $plainPassword
        ]);
        
        $user = $this->userModel->findById($userId);
        
        // Password should be hashed, not stored as plain text
        $this->assertNotEquals($plainPassword, $user['password']);
        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }
    
    public function testCreateUserSetsTimestamps()
    {
        $beforeCreation = date('Y-m-d H:i:s');
        
        $userId = $this->userModel->create([
            'email' => 'timestamp.test@example.com',
            'password' => 'password123'
        ]);
        
        $afterCreation = date('Y-m-d H:i:s');
        
        $user = $this->userModel->findById($userId);
        
        $this->assertNotNull($user['created_at']);
        $this->assertNotNull($user['updated_at']);
        
        // Check that timestamps are reasonable
        $this->assertTrue($user['created_at'] >= $beforeCreation);
        $this->assertTrue($user['created_at'] <= $afterCreation);
    }
    
    // ========== READ TESTS ==========
    
    public function testFindUserById()
    {
        // Create test user
        $userId = $this->createTestUser([
            'name' => 'Jane',
            'surname' => 'Smith',
            'email' => 'jane.smith@example.com'
        ]);
        
        // Find user by ID
        $user = $this->userModel->findById($userId);
        
        $this->assertNotNull($user, 'User should be found');
        $this->assertEquals($userId, $user['id']);
        $this->assertEquals('Jane', $user['name']);
        $this->assertEquals('jane.smith@example.com', $user['email']);
    }
    
    public function testFindUserByNonExistentId()
    {
        $user = $this->userModel->findById(99999);
        $this->assertNull($user, 'Non-existent user should return null');
    }
    
    public function testFindUserByEmail()
    {
        $email = 'findbyemail@example.com';
        $userId = $this->createTestUser(['email' => $email]);
        
        $user = $this->userModel->findByEmail($email);
        
        $this->assertNotNull($user, 'User should be found by email');
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($userId, $user['id']);
    }
    
    public function testFindUserByNonExistentEmail()
    {
        $user = $this->userModel->findByEmail('nonexistent@example.com');
        $this->assertNull($user, 'Non-existent email should return null');
    }
    
    public function testFindUserByIdentifier()
    {
        // Test with email
        $email = 'identifier.test@example.com';
        $username = 'identifieruser';
        
        $userId = $this->createTestUser([
            'email' => $email,
            'username' => $username
        ]);
        
        // Find by email
        $userByEmail = $this->userModel->findByIdentifier($email);
        $this->assertNotNull($userByEmail);
        $this->assertEquals($userId, $userByEmail['id']);
        
        // Find by username
        $userByUsername = $this->userModel->findByIdentifier($username);
        $this->assertNotNull($userByUsername);
        $this->assertEquals($userId, $userByUsername['id']);
    }
    
    // ========== UPDATE TESTS ==========
    
    public function testUpdateUser()
    {
        // Create test user
        $userId = $this->createTestUser([
            'name' => 'Original',
            'surname' => 'Name',
            'email' => 'original@example.com'
        ]);
        
        // Update user data
        $updateData = [
            'name' => 'Updated',
            'surname' => 'User',
            'user_type' => 'mentor'
        ];
        
        $result = $this->userModel->update($userId, $updateData);
        $this->assertTrue($result, 'Update should return true on success');
        
        // Verify updates
        $updatedUser = $this->userModel->findById($userId);
        $this->assertEquals('Updated', $updatedUser['name']);
        $this->assertEquals('User', $updatedUser['surname']);
        $this->assertEquals('mentor', $updatedUser['user_type']);
        
        // Verify unchanged data
        $this->assertEquals('original@example.com', $updatedUser['email']);
    }
    
    public function testUpdateUserPassword()
    {
        $userId = $this->createTestUser([
            'email' => 'password.update@example.com',
            'password' => 'oldpassword'
        ]);
        
        $newPassword = 'NewSecurePassword123!';
        
        $result = $this->userModel->update($userId, ['password' => $newPassword]);
        $this->assertTrue($result);
        
        $updatedUser = $this->userModel->findById($userId);
        
        // New password should verify
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
        
        // password_changed_at should be set
        $this->assertNotNull($updatedUser['password_changed_at']);
    }
    
    public function testUpdateUserSetsUpdatedTimestamp()
    {
        $userId = $this->createTestUser();
        
        // Wait a moment to ensure timestamp difference
        sleep(1);
        
        $beforeUpdate = date('Y-m-d H:i:s');
        
        $result = $this->userModel->update($userId, ['name' => 'Updated Name']);
        $this->assertTrue($result);
        
        $updatedUser = $this->userModel->findById($userId);
        $this->assertTrue($updatedUser['updated_at'] >= $beforeUpdate);
    }
    
    public function testUpdateNonExistentUser()
    {
        $result = $this->userModel->update(99999, ['name' => 'Test']);
        $this->assertFalse($result, 'Updating non-existent user should return false');
    }
    
    // ========== DELETE TESTS ==========
    
    public function testDeleteUser()
    {
        // Create test user
        $userId = $this->createTestUser([
            'email' => 'delete.test@example.com'
        ]);
        
        // Verify user exists
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user, 'User should exist before deletion');
        
        // Delete user
        $result = $this->userModel->delete($userId);
        $this->assertTrue($result, 'Delete should return true on success');
        
        // Verify user is deleted
        $deletedUser = $this->userModel->findById($userId);
        $this->assertNull($deletedUser, 'User should not exist after deletion');
    }
    
    public function testDeleteNonExistentUser()
    {
        $result = $this->userModel->delete(99999);
        $this->assertFalse($result, 'Deleting non-existent user should return false');
    }
    
    // ========== PAGINATION TESTS ==========
    
    public function testGetPaginatedUsers()
    {
        // Create multiple test users
        $userIds = [];
        for ($i = 1; $i <= 15; $i++) {
            $userIds[] = $this->createTestUser([
                'name' => "User{$i}",
                'email' => "user{$i}@example.com",
                'user_type' => $i <= 5 ? 'student' : 'mentor'
            ]);
        }
        
        // Test pagination
        $result = $this->userModel->getPaginated(1, 10);
        
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(10, count($result['data']));
        $this->assertEquals(15, $result['pagination']['total']);
        $this->assertEquals(2, $result['pagination']['pages']);
        $this->assertEquals(1, $result['pagination']['current_page']);
    }
    
    public function testGetPaginatedUsersWithFilters()
    {
        // Create test users with different types
        for ($i = 1; $i <= 10; $i++) {
            $this->createTestUser([
                'name' => "FilterUser{$i}",
                'email' => "filter{$i}@example.com",
                'user_type' => $i <= 3 ? 'student' : 'mentor'
            ]);
        }
        
        // Test filtering by user type
        $result = $this->userModel->getPaginated(1, 10, ['user_type' => 'student']);
        
        $this->assertEquals(3, count($result['data']));
        $this->assertEquals(3, $result['pagination']['total']);
        
        foreach ($result['data'] as $user) {
            $this->assertEquals('student', $user['user_type']);
        }
    }
    
    public function testGetPaginatedUsersWithSearch()
    {
        // Create test users
        $this->createTestUser(['name' => 'Searchable', 'email' => 'searchable@example.com']);
        $this->createTestUser(['name' => 'NotFound', 'email' => 'notfound@example.com']);
        
        // Test search functionality
        $result = $this->userModel->getPaginated(1, 10, ['search' => 'Searchable']);
        
        $this->assertEquals(1, count($result['data']));
        $this->assertEquals('Searchable', $result['data'][0]['name']);
    }
    
    // ========== VALIDATION TESTS ==========
    
    public function testUserModelConstants()
    {
        $userTypes = $this->userModel::USER_TYPES;
        $this->assertArrayHasKey('admin', $userTypes);
        $this->assertArrayHasKey('mentor', $userTypes);
        $this->assertArrayHasKey('member', $userTypes);
        $this->assertArrayHasKey('student', $userTypes);
        
        $userStatuses = $this->userModel::USER_STATUSES;
        $this->assertArrayHasKey('active', $userStatuses);
        $this->assertArrayHasKey('inactive', $userStatuses);
        $this->assertArrayHasKey('suspended', $userStatuses);
        $this->assertArrayHasKey('pending', $userStatuses);
    }
    
    // ========== EDGE CASE TESTS ==========
    
    public function testCreateUserWithEmptyPassword()
    {
        $userData = [
            'email' => 'empty.password@example.com',
            'password' => ''
        ];
        
        $userId = $this->userModel->create($userData);
        
        // Should still create user but with empty password hash
        $this->assertNotNull($userId);
        
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user['password']);
    }
    
    public function testCreateUserWithLongData()
    {
        $longString = str_repeat('a', 300); // Very long string
        
        $userData = [
            'email' => 'long.data@example.com',
            'name' => $longString,
            'password' => 'password123'
        ];
        
        try {
            $userId = $this->userModel->create($userData);
            
            if ($userId) {
                $user = $this->userModel->findById($userId);
                // Data should be truncated or handled appropriately
                $this->assertNotNull($user);
            }
        } catch (Exception $e) {
            // Should handle long data gracefully
            $this->assertStringContains('too long', strtolower($e->getMessage()));
        }
    }
    
    public function testUpdateWithNullValues()
    {
        $userId = $this->createTestUser();
        
        // Try to update with null values
        $updateData = [
            'name' => null,
            'surname' => null
        ];
        
        $result = $this->userModel->update($userId, $updateData);
        
        // Should handle null values appropriately
        $this->assertTrue($result);
        
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user); // User should still exist
    }
    
    // ========== SPECIAL FUNCTIONALITY TESTS ==========
    
    public function testUserStatusHandling()
    {
        $userId = $this->createTestUser(['status' => 'active']);
        
        // Test status update
        $this->userModel->update($userId, ['status' => 'suspended']);
        
        $user = $this->userModel->findById($userId);
        $this->assertEquals('suspended', $user['status']);
    }
    
    public function testUserTypeHandling()
    {
        $userId = $this->createTestUser(['user_type' => 'student']);
        
        // Test role promotion
        $this->userModel->update($userId, ['user_type' => 'mentor']);
        
        $user = $this->userModel->findById($userId);
        $this->assertEquals('mentor', $user['user_type']);
    }
    
    public function testEmailVerificationHandling()
    {
        $userId = $this->createTestUser(['email_verified' => false]);
        
        // Test email verification
        $this->userModel->update($userId, ['email_verified' => true]);
        
        $user = $this->userModel->findById($userId);
        $this->assertTrue((bool)$user['email_verified']);
    }
    
    // ========== PERFORMANCE TESTS ==========
    
    public function testBulkOperationPerformance()
    {
        $startTime = microtime(true);
        
        // Create multiple users
        $userIds = [];
        for ($i = 1; $i <= 50; $i++) {
            $userIds[] = $this->createTestUser([
                'email' => "bulk{$i}@example.com",
                'name' => "BulkUser{$i}"
            ]);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertTrue($duration < 10, "Bulk operations should complete within 10 seconds, took {$duration}s");
        $this->assertEquals(50, count($userIds));
    }
    
    // ========== DATA INTEGRITY TESTS ==========
    
    public function testDataIntegrityAfterOperations()
    {
        $userId = $this->createTestUser([
            'name' => 'Integrity',
            'email' => 'integrity@example.com'
        ]);
        
        // Perform multiple operations
        $this->userModel->update($userId, ['name' => 'Updated']);
        $user1 = $this->userModel->findById($userId);
        
        $this->userModel->update($userId, ['surname' => 'NewSurname']);
        $user2 = $this->userModel->findById($userId);
        
        // Data should remain consistent
        $this->assertEquals('Updated', $user1['name']);
        $this->assertEquals('Updated', $user2['name']);
        $this->assertEquals('NewSurname', $user2['surname']);
        $this->assertEquals('integrity@example.com', $user2['email']);
    }
}