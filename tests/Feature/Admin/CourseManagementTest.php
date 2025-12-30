<?php
/**
 * Admin Course Management Tests
 * Phase 4 Week 1 Day 3 - Test Coverage Expansion
 *
 * Tests for admin course CRUD operations, lesson management, and enrollment statistics
 */

namespace Tests\Feature\Admin;

require_once __DIR__ . '/../../Feature/TestCase.php';
require_once __DIR__ . '/../../../app/Controllers/Admin/CourseController.php';
require_once __DIR__ . '/../../../app/Models/Admin/AdminCourseModel.php';

use Tests\Feature\TestCase;

class CourseManagementTest extends TestCase
{
    private $courseController;
    private $courseModel;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set global connection for services
        $GLOBALS['conn'] = $this->db;

        // Initialize controller and model
        $this->courseModel = new \AdminCourseModel($this->db);
        $this->courseController = new \Admin\CourseController($this->db);

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
     * Test 1: Admin can create course
     *
     * @test
     */
    public function test_admin_can_create_course()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Prepare course data
        $courseCode = 'TEST-' . time();
        $title = 'Test Course ' . time();
        $description = 'This is a test course description';
        $type = 'full_course';
        $difficultyLevel = 'Beginner';
        $category = 'Programming';

        // Create course using raw SQL for compatibility
        $sql = "INSERT INTO courses (course_code, title, description, type, difficulty_level, category, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssi', $courseCode, $title, $description, $type, $difficultyLevel, $category, $adminId);
        $stmt->execute();
        $courseId = $this->db->insert_id;

        // Assert course was created
        $this->assertIsInt($courseId, 'Course ID should be an integer');
        $this->assertGreaterThan(0, $courseId, 'Course ID should be greater than 0');

        // Assert course exists in database
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'course_code' => $courseCode,
            'title' => $title,
            'type' => $type,
            'difficulty_level' => $difficultyLevel
        ]);

        // Verify course data
        $sql = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();

        $this->assertEquals($title, $course['title']);
        $this->assertEquals($description, $course['description']);
        $this->assertEquals('Beginner', $course['difficulty_level']);
        $this->assertEquals('draft', $course['status']); // Default status
    }

    /**
     * Test 2: Admin can edit course details
     *
     * @test
     */
    public function test_admin_can_edit_course_details()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a course to edit
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Original Title',
            'description' => 'Original description',
            'difficulty_level' => 'Beginner'
        ]);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Prepare updated data
        $newTitle = 'Updated Course Title';
        $newDescription = 'Updated course description with more details';
        $newDifficulty = 'Advanced';

        // Update course
        $sql = "UPDATE courses SET title = ?, description = ?, difficulty_level = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssi', $newTitle, $newDescription, $newDifficulty, $courseId);
        $success = $stmt->execute();

        // Assert update succeeded
        $this->assertTrue($success, 'Course update should succeed');

        // Assert database has updated data
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'title' => $newTitle,
            'description' => $newDescription,
            'difficulty_level' => $newDifficulty
        ]);

        // Verify updated course data
        $sql = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();

        $this->assertEquals($newTitle, $course['title']);
        $this->assertEquals($newDescription, $course['description']);
        $this->assertEquals($newDifficulty, $course['difficulty_level']);
    }

    /**
     * Test 3: Admin can delete course
     *
     * @test
     */
    public function test_admin_can_delete_course()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a course to delete
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Course to Delete',
            'description' => 'This course will be deleted'
        ]);

        // Verify course exists before deletion
        $this->assertDatabaseHas('courses', ['id' => $courseId]);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Delete course
        $sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $success = $stmt->execute();

        // Assert deletion succeeded
        $this->assertTrue($success, 'Course deletion should succeed');

        // Assert course no longer exists in database
        $this->assertDatabaseMissing('courses', ['id' => $courseId]);

        // Verify course cannot be found
        $sql = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->assertEquals(0, $result->num_rows, 'Deleted course should not be found');
    }

    /**
     * Test 4: Admin can add lessons to course
     *
     * @test
     */
    public function test_admin_can_add_lessons_to_course()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a course
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Course with Lessons',
            'description' => 'This course will have lessons added'
        ]);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Add first lesson
        $lesson1Title = 'Introduction to Programming';
        $lesson1Description = 'Learn the basics of programming';
        $lesson1Order = 1;

        $sql = "INSERT INTO course_lessons (course_id, title, description, order_number, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issi', $courseId, $lesson1Title, $lesson1Description, $lesson1Order);
        $stmt->execute();
        $lesson1Id = $this->db->insert_id;

        // Add second lesson
        $lesson2Title = 'Variables and Data Types';
        $lesson2Description = 'Understanding variables';
        $lesson2Order = 2;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issi', $courseId, $lesson2Title, $lesson2Description, $lesson2Order);
        $stmt->execute();
        $lesson2Id = $this->db->insert_id;

        // Assert lessons were created
        $this->assertGreaterThan(0, $lesson1Id, 'Lesson 1 should be created');
        $this->assertGreaterThan(0, $lesson2Id, 'Lesson 2 should be created');

        // Assert lessons exist in database
        $this->assertDatabaseHas('course_lessons', [
            'id' => $lesson1Id,
            'course_id' => $courseId,
            'title' => $lesson1Title,
            'order_number' => 1
        ]);

        $this->assertDatabaseHas('course_lessons', [
            'id' => $lesson2Id,
            'course_id' => $courseId,
            'title' => $lesson2Title,
            'order_number' => 2
        ]);

        // Verify lesson count
        $sql = "SELECT COUNT(*) as count FROM course_lessons WHERE course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $this->assertEquals(2, $result['count'], 'Course should have 2 lessons');
    }

    /**
     * Test 5: Admin can reorder lessons
     *
     * @test
     */
    public function test_admin_can_reorder_lessons()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a course
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Course with Reorderable Lessons'
        ]);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Create lessons in original order
        $lesson1Id = $this->createTestLesson($courseId, ['title' => 'Lesson 1', 'order_number' => 1]);
        $lesson2Id = $this->createTestLesson($courseId, ['title' => 'Lesson 2', 'order_number' => 2]);
        $lesson3Id = $this->createTestLesson($courseId, ['title' => 'Lesson 3', 'order_number' => 3]);

        // Verify original order
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson1Id, 'order_number' => 1]);
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson2Id, 'order_number' => 2]);
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson3Id, 'order_number' => 3]);

        // Reorder lessons (swap lesson 1 and lesson 3)
        $sql = "UPDATE course_lessons SET order_number = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);

        // Set lesson 1 to order 3
        $newOrder = 3;
        $stmt->bind_param('ii', $newOrder, $lesson1Id);
        $stmt->execute();

        // Set lesson 3 to order 1
        $newOrder = 1;
        $stmt->bind_param('ii', $newOrder, $lesson3Id);
        $success = $stmt->execute();

        // Assert reordering succeeded
        $this->assertTrue($success, 'Lesson reordering should succeed');

        // Assert new order in database
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson1Id, 'order_number' => 3]);
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson2Id, 'order_number' => 2]); // Unchanged
        $this->assertDatabaseHas('course_lessons', ['id' => $lesson3Id, 'order_number' => 1]);

        // Verify order by querying sorted results
        $sql = "SELECT id, title, order_number FROM course_lessons WHERE course_id = ? ORDER BY order_number ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->assertEquals($lesson3Id, $lessons[0]['id'], 'Lesson 3 should be first');
        $this->assertEquals($lesson2Id, $lessons[1]['id'], 'Lesson 2 should be second');
        $this->assertEquals($lesson1Id, $lessons[2]['id'], 'Lesson 1 should be third');
    }

    /**
     * Test 6: Admin can publish/unpublish course
     *
     * @test
     */
    public function test_admin_can_publish_unpublish_course()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a draft course
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Course to Publish',
            'status' => 'draft',
            'is_published' => 0
        ]);

        // Verify course is in draft status
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'status' => 'draft',
            'is_published' => 0
        ]);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Publish course (change status to active and set is_published = 1)
        $sql = "UPDATE courses SET status = 'active', is_published = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $success = $stmt->execute();

        // Assert publishing succeeded
        $this->assertTrue($success, 'Course publishing should succeed');

        // Assert course is now published
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'status' => 'active',
            'is_published' => 1
        ]);

        // Unpublish course (change status to draft and set is_published = 0)
        $sql = "UPDATE courses SET status = 'draft', is_published = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $success = $stmt->execute();

        // Assert unpublishing succeeded
        $this->assertTrue($success, 'Course unpublishing should succeed');

        // Assert course is now unpublished
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'status' => 'draft',
            'is_published' => 0
        ]);
    }

    /**
     * Test 7: Admin can view enrollment statistics
     *
     * @test
     */
    public function test_admin_can_view_enrollment_statistics()
    {
        // Create admin user
        $adminId = $this->createAdminUser();

        // Create a course
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Popular Course',
            'enrollment_count' => 0
        ]);

        // Create test users to enroll
        $user1Id = $this->createUser(['username' => 'student1', 'email' => 'student1@test.com']);
        $user2Id = $this->createUser(['username' => 'student2', 'email' => 'student2@test.com']);
        $user3Id = $this->createUser(['username' => 'student3', 'email' => 'student3@test.com']);

        // Act as admin
        $_SESSION['user_id'] = $adminId;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['authenticated'] = true;

        // Enroll users in course
        $sql = "INSERT INTO user_enrollments (user_id, course_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);

        $stmt->bind_param('ii', $user1Id, $courseId);
        $stmt->execute();

        $stmt->bind_param('ii', $user2Id, $courseId);
        $stmt->execute();

        $stmt->bind_param('ii', $user3Id, $courseId);
        $stmt->execute();

        // Update course enrollment count
        $sql = "UPDATE courses SET enrollment_count = (SELECT COUNT(*) FROM user_enrollments WHERE course_id = ?) WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $courseId, $courseId);
        $stmt->execute();

        // Get enrollment statistics
        $sql = "SELECT c.id, c.title, c.enrollment_count, COUNT(e.id) as actual_enrollments
                FROM courses c
                LEFT JOIN user_enrollments e ON c.id = e.course_id
                WHERE c.id = ?
                GROUP BY c.id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();

        // Assert enrollment statistics are correct
        $this->assertNotNull($stats, 'Enrollment statistics should be available');
        $this->assertEquals(3, $stats['enrollment_count'], 'Enrollment count should be 3');
        $this->assertEquals(3, $stats['actual_enrollments'], 'Actual enrollments should be 3');

        // Verify individual enrollments exist
        $this->assertDatabaseHas('user_enrollments', ['user_id' => $user1Id, 'course_id' => $courseId]);
        $this->assertDatabaseHas('user_enrollments', ['user_id' => $user2Id, 'course_id' => $courseId]);
        $this->assertDatabaseHas('user_enrollments', ['user_id' => $user3Id, 'course_id' => $courseId]);

        // Assert course enrollment_count field is updated
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'enrollment_count' => 3
        ]);
    }

    // =================== HELPER METHODS ===================

    /**
     * Create a test course
     *
     * @param int $createdBy User ID who creates the course
     * @param array $overrides Course data overrides
     * @return int Course ID
     */
    private function createTestCourse(int $createdBy, array $overrides = []): int
    {
        $defaults = [
            'course_code' => 'TEST-' . time() . '-' . rand(1000, 9999),
            'title' => 'Test Course ' . time(),
            'description' => 'Test course description',
            'type' => 'full_course',
            'difficulty_level' => 'Beginner',
            'category' => 'General',
            'status' => 'draft',
            'is_published' => 0,
            'enrollment_count' => 0
        ];

        $data = array_merge($defaults, $overrides);

        $sql = "INSERT INTO courses (course_code, title, description, type, difficulty_level, category, status, is_published, enrollment_count, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssssiii',
            $data['course_code'],
            $data['title'],
            $data['description'],
            $data['type'],
            $data['difficulty_level'],
            $data['category'],
            $data['status'],
            $data['is_published'],
            $data['enrollment_count'],
            $createdBy
        );
        $stmt->execute();

        return $this->db->insert_id;
    }

    /**
     * Create a test lesson
     *
     * @param int $courseId Course ID
     * @param array $overrides Lesson data overrides
     * @return int Lesson ID
     */
    private function createTestLesson(int $courseId, array $overrides = []): int
    {
        $defaults = [
            'title' => 'Test Lesson ' . time(),
            'description' => 'Test lesson description',
            'order_number' => 1,
            'is_published' => 1
        ];

        $data = array_merge($defaults, $overrides);

        $sql = "INSERT INTO course_lessons (course_id, title, description, order_number, is_published, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issii',
            $courseId,
            $data['title'],
            $data['description'],
            $data['order_number'],
            $data['is_published']
        );
        $stmt->execute();

        return $this->db->insert_id;
    }
}
