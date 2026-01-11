<?php
/**
 * Integration Test Framework
 *
 * Extends BaseTestCase with controller-specific integration testing capabilities
 *
 * Phase 4 Week 4 Day 5: Database Integration Testing
 * Created: January 5, 2026
 *
 * @package Tests
 * @since Phase 4 Week 4 Day 5
 */

namespace Tests;

require_once __DIR__ . '/BaseTestCase.php';

abstract class IntegrationTestFramework extends BaseTestCase
{
    protected $testUser;
    protected $testAdminUser;
    protected $testSession;

    /**
     * Set up test fixtures before each test
     */
    public function setUp()
    {
        parent::setUp();

        // Create test users
        $this->createTestFixtures();

        // Set up test session
        $this->setupTestSession();
    }

    /**
     * Create test data fixtures
     */
    protected function createTestFixtures()
    {
        // Create standard test user
        $this->testUser = [
            'id' => $this->createTestUser([
                'name' => 'Test',
                'surname' => 'User',
                'email' => 'testuser@scibono.test',
                'password' => password_hash('testpass123', PASSWORD_DEFAULT),
                'role' => 'student',
                'status' => 'active'
            ]),
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'testuser@scibono.test',
            'role' => 'student'
        ];

        // Create admin test user
        $this->testAdminUser = [
            'id' => $this->createTestUser([
                'name' => 'Admin',
                'surname' => 'User',
                'email' => 'admin@scibono.test',
                'password' => password_hash('adminpass123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active'
            ]),
            'name' => 'Admin',
            'surname' => 'User',
            'email' => 'admin@scibono.test',
            'role' => 'admin'
        ];
    }

    /**
     * Set up test session simulation
     */
    protected function setupTestSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Set default session to test user
        $_SESSION['user_id'] = $this->testUser['id'];
        $_SESSION['role'] = $this->testUser['role'];
        $_SESSION['email'] = $this->testUser['email'];
        $_SESSION['logged_in'] = true;
    }

    /**
     * Switch session to admin user
     */
    protected function loginAsAdmin()
    {
        $_SESSION['user_id'] = $this->testAdminUser['id'];
        $_SESSION['role'] = $this->testAdminUser['role'];
        $_SESSION['email'] = $this->testAdminUser['email'];
        $_SESSION['logged_in'] = true;
    }

    /**
     * Switch session to standard user
     */
    protected function loginAsUser()
    {
        $_SESSION['user_id'] = $this->testUser['id'];
        $_SESSION['role'] = $this->testUser['role'];
        $_SESSION['email'] = $this->testUser['email'];
        $_SESSION['logged_in'] = true;
    }

    /**
     * Clear session (simulate logout)
     */
    protected function logout()
    {
        $_SESSION = [];
    }

    /**
     * Create a test course
     */
    protected function createTestCourse($data = [])
    {
        $defaultData = [
            'title' => 'Test Course',
            'description' => 'Test course description',
            'instructor_id' => $this->testAdminUser['id'],
            'status' => 'active',
            'duration' => '10 weeks'
        ];

        $courseData = array_merge($defaultData, $data);

        $sql = "INSERT INTO courses (title, description, instructor_id, status, duration, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiss',
            $courseData['title'],
            $courseData['description'],
            $courseData['instructor_id'],
            $courseData['status'],
            $courseData['duration']
        );

        if (!$stmt->execute()) {
            throw new \Exception('Failed to create test course: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Create a test lesson
     */
    protected function createTestLesson($courseId, $data = [])
    {
        $defaultData = [
            'course_id' => $courseId,
            'title' => 'Test Lesson',
            'content' => 'Test lesson content',
            'order' => 1,
            'status' => 'published'
        ];

        $lessonData = array_merge($defaultData, $data);

        $sql = "INSERT INTO lessons (course_id, title, content, `order`, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issis',
            $lessonData['course_id'],
            $lessonData['title'],
            $lessonData['content'],
            $lessonData['order'],
            $lessonData['status']
        );

        if (!$stmt->execute()) {
            throw new \Exception('Failed to create test lesson: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Create a test holiday program
     */
    protected function createTestHolidayProgram($data = [])
    {
        $defaultData = [
            'name' => 'Test Holiday Program',
            'description' => 'Test program description',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date' => date('Y-m-d', strtotime('+14 days')),
            'capacity' => 50,
            'status' => 'active'
        ];

        $programData = array_merge($defaultData, $data);

        $sql = "INSERT INTO holiday_programs (name, description, start_date, end_date, capacity, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssds',
            $programData['name'],
            $programData['description'],
            $programData['start_date'],
            $programData['end_date'],
            $programData['capacity'],
            $programData['status']
        );

        if (!$stmt->execute()) {
            throw new \Exception('Failed to create test holiday program: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Create test attendance record
     */
    protected function createTestAttendance($userId = null, $data = [])
    {
        $userId = $userId ?? $this->testUser['id'];

        $defaultData = [
            'user_id' => $userId,
            'date' => date('Y-m-d'),
            'time_in' => date('H:i:s'),
            'time_out' => null,
            'status' => 'present'
        ];

        $attendanceData = array_merge($defaultData, $data);

        $sql = "INSERT INTO attendance (user_id, date, time_in, time_out, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issss',
            $attendanceData['user_id'],
            $attendanceData['date'],
            $attendanceData['time_in'],
            $attendanceData['time_out'],
            $attendanceData['status']
        );

        if (!$stmt->execute()) {
            throw new \Exception('Failed to create test attendance: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Simulate HTTP GET request
     */
    protected function simulateGetRequest($params = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $params;
        $_POST = [];
    }

    /**
     * Simulate HTTP POST request
     */
    protected function simulatePostRequest($params = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $params;
        $_GET = [];
    }

    /**
     * Capture controller output
     */
    protected function captureOutput($callback)
    {
        ob_start();
        try {
            $result = $callback();
            $output = ob_get_clean();
            return ['result' => $result, 'output' => $output];
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Assert controller redirects
     */
    protected function assertRedirects($callback, $expectedLocation = null)
    {
        $headers = [];

        // Override header() function for testing
        $headerCallback = function($header) use (&$headers) {
            $headers[] = $header;
        };

        try {
            $callback();
        } catch (\Exception $e) {
            // Some redirects may throw exceptions
        }

        // Check if Location header was set
        $locationHeaders = array_filter($headers, function($h) {
            return stripos($h, 'Location:') === 0;
        });

        $this->assertTrue(count($locationHeaders) > 0, 'Expected redirect but none occurred');

        if ($expectedLocation) {
            $hasExpectedLocation = false;
            foreach ($locationHeaders as $header) {
                if (stripos($header, $expectedLocation) !== false) {
                    $hasExpectedLocation = true;
                    break;
                }
            }
            $this->assertTrue($hasExpectedLocation, "Expected redirect to '{$expectedLocation}'");
        }

        return true;
    }

    /**
     * Assert JSON response
     */
    protected function assertJsonResponse($output, $expectedKeys = [])
    {
        $data = json_decode($output, true);

        $this->assertNotNull($data, 'Expected JSON response but got invalid JSON');

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Expected JSON to have key '{$key}'");
        }

        return $data;
    }

    /**
     * Assert controller requires authentication
     */
    protected function assertRequiresAuth($callback)
    {
        // Logout first
        $this->logout();

        $exceptionThrown = false;
        try {
            $callback();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Expected authentication exception but none was thrown');

        // Restore session
        $this->loginAsUser();

        return true;
    }

    /**
     * Assert controller requires specific role
     */
    protected function assertRequiresRole($callback, $requiredRole)
    {
        // Set insufficient role
        $_SESSION['role'] = 'student';

        $exceptionThrown = false;
        try {
            $callback();
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, "Expected role exception for required role '{$requiredRole}'");

        // Restore proper role
        $this->loginAsAdmin();

        return true;
    }

    /**
     * Clean all test data
     */
    protected function cleanAllTestData()
    {
        $tables = [
            'attendance',
            'enrollments',
            'lessons',
            'courses',
            'holiday_programs',
            'program_registrations',
            'activity_logs',
            'users'
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
    }

    /**
     * Get controller test statistics
     */
    protected function getControllerStats($controllerName)
    {
        return [
            'name' => $controllerName,
            'exists' => class_exists($controllerName),
            'extends_base' => $this->extendsBaseController($controllerName),
            'methods' => $this->getControllerMethods($controllerName)
        ];
    }

    /**
     * Check if controller extends BaseController
     */
    protected function extendsBaseController($controllerName)
    {
        if (!class_exists($controllerName)) {
            return false;
        }

        $reflection = new \ReflectionClass($controllerName);
        $parent = $reflection->getParentClass();

        return $parent && $parent->getName() === 'BaseController';
    }

    /**
     * Get controller public methods
     */
    protected function getControllerMethods($controllerName)
    {
        if (!class_exists($controllerName)) {
            return [];
        }

        $reflection = new \ReflectionClass($controllerName);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        return array_map(function($method) {
            return $method->getName();
        }, $methods);
    }
}
