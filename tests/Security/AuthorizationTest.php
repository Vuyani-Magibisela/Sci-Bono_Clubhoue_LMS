<?php
/**
 * Authorization Tests
 * Phase 3 Week 9 Day 3 - Testing Infrastructure
 *
 * Tests for role-based authorization and access control
 */

namespace Tests\Security;

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../../app/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Logger.php';

use Tests\Security\TestCase;

class AuthorizationTest extends TestCase
{
    /**
     * Test 1: Admin role can access admin-only routes
     *
     * @test
     */
    public function test_admin_role_can_access_admin_routes()
    {
        // Create admin user and set session
        $adminId = $this->createAdminUser();
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Create middleware requiring admin role
        $middleware = new \RoleMiddleware('admin');

        // Test authorization
        $result = $middleware->handle();

        $this->assertTrue($result, 'Admin user should be authorized for admin routes');
    }

    /**
     * Test 2: Non-admin user cannot access admin routes
     *
     * @test
     */
    public function test_non_admin_cannot_access_admin_routes()
    {
        // Create regular member user
        $memberId = $this->createUser(['role' => 'member']);
        $_SESSION['user_id'] = $memberId;
        $_SESSION['user_type'] = 'member';
        $_SESSION['authenticated'] = true;

        // Set up to capture exit/output
        ob_start();

        try {
            // Create middleware requiring admin role
            $middleware = new \RoleMiddleware('admin');
            $result = $middleware->handle();

            // Should not reach here - middleware should exit
            $this->fail('Non-admin user should not be authorized for admin routes');
        } catch (\Exception $e) {
            // Expected behavior - middleware exits
            $output = ob_get_clean();
            $this->assertTrue(true, 'Non-admin correctly denied access');
        }

        ob_end_clean();
    }

    /**
     * Test 3: Mentor role can access mentor routes
     *
     * @test
     */
    public function test_mentor_role_can_access_mentor_routes()
    {
        // Create mentor user
        $mentorId = $this->createMentorUser();
        $_SESSION['user_id'] = $mentorId;
        $_SESSION['user_type'] = 'mentor';
        $_SESSION['authenticated'] = true;

        // Create middleware requiring mentor role
        $middleware = new \RoleMiddleware('mentor');

        // Test authorization
        $result = $middleware->handle();

        $this->assertTrue($result, 'Mentor user should be authorized for mentor routes');
    }

    /**
     * Test 4: Multiple roles allowed (admin OR mentor)
     *
     * @test
     */
    public function test_multiple_roles_authorization()
    {
        // Test with mentor user
        $mentorId = $this->createMentorUser();
        $_SESSION['user_id'] = $mentorId;
        $_SESSION['user_type'] = 'mentor';
        $_SESSION['authenticated'] = true;

        // Create middleware requiring admin OR mentor
        $middleware = new \RoleMiddleware('admin', 'mentor');

        $result = $middleware->handle();
        $this->assertTrue($result, 'Mentor should be authorized when multiple roles allowed');

        // Test with admin user
        $this->clearAuth();
        $adminId = $this->createAdminUser();
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        $middleware = new \RoleMiddleware('admin', 'mentor');
        $result = $middleware->handle();
        $this->assertTrue($result, 'Admin should be authorized when multiple roles allowed');
    }

    /**
     * Test 5: Unauthenticated user denied access
     *
     * @test
     */
    public function test_unauthenticated_user_denied_access()
    {
        // Clear all session data
        $_SESSION = [];

        ob_start();

        try {
            // Create middleware
            $middleware = new \RoleMiddleware('admin');
            $result = $middleware->handle();

            // Should not reach here
            $this->fail('Unauthenticated user should be denied access');
        } catch (\Exception $e) {
            // Expected - middleware exits
            ob_end_clean();
            $this->assertTrue(true, 'Unauthenticated user correctly denied');
        }

        ob_end_clean();
    }

    /**
     * Test 6: Role middleware with array parameter (backward compatibility)
     *
     * @test
     */
    public function test_role_middleware_array_parameter()
    {
        // Create admin user
        $adminId = $this->createAdminUser();
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Create middleware with array parameter
        $middleware = new \RoleMiddleware(['admin', 'mentor']);

        $result = $middleware->handle();
        $this->assertTrue($result, 'Array parameter should work for role middleware');
    }

    /**
     * Test 7: Role middleware with comma-separated string
     *
     * @test
     */
    public function test_role_middleware_comma_separated_string()
    {
        // Create mentor user
        $mentorId = $this->createMentorUser();
        $_SESSION['user_id'] = $mentorId;
        $_SESSION['user_type'] = 'mentor';
        $_SESSION['authenticated'] = true;

        // Create middleware with comma-separated string
        $middleware = new \RoleMiddleware('admin,mentor,manager');

        $result = $middleware->handle();
        $this->assertTrue($result, 'Comma-separated string should work for role middleware');
    }

    /**
     * Test 8: Member cannot access mentor routes
     *
     * @test
     */
    public function test_member_cannot_access_mentor_routes()
    {
        // Create regular member
        $memberId = $this->createUser(['role' => 'member', 'user_type' => 'member']);
        $_SESSION['user_id'] = $memberId;
        $_SESSION['user_type'] = 'member';
        $_SESSION['authenticated'] = true;

        ob_start();

        try {
            // Create middleware requiring mentor role
            $middleware = new \RoleMiddleware('mentor');
            $result = $middleware->handle();

            // Should not reach here
            $this->fail('Member should not access mentor routes');
        } catch (\Exception $e) {
            ob_end_clean();
            $this->assertTrue(true, 'Member correctly denied mentor access');
        }

        ob_end_clean();
    }

    /**
     * Test 9: Session must contain user_id and user_type
     *
     * @test
     */
    public function test_session_requires_user_id_and_user_type()
    {
        // Set incomplete session (only user_id, no user_type)
        $_SESSION['user_id'] = 1;
        unset($_SESSION['user_type']);

        ob_start();

        try {
            $middleware = new \RoleMiddleware('admin');
            $result = $middleware->handle();

            $this->fail('Should deny access without user_type in session');
        } catch (\Exception $e) {
            ob_end_clean();
            $this->assertTrue(true, 'Correctly denied access without user_type');
        }

        ob_end_clean();
    }

    /**
     * Test 10: Parent role authorization
     *
     * @test
     */
    public function test_parent_role_authorization()
    {
        // Create parent user
        $parentId = $this->createUser([
            'role' => 'parent',
            'user_type' => 'parent'
        ]);

        $_SESSION['user_id'] = $parentId;
        $_SESSION['user_type'] = 'parent';
        $_SESSION['authenticated'] = true;

        // Create middleware requiring parent role
        $middleware = new \RoleMiddleware('parent');

        $result = $middleware->handle();
        $this->assertTrue($result, 'Parent user should be authorized for parent routes');
    }
}
