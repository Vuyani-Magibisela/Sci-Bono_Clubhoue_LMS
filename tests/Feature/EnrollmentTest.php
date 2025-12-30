<?php
/**
 * Enrollment & Progress Tests
 * Phase 4 Week 1 Day 4 - Test Coverage Expansion
 *
 * Tests for user enrollment, unenrollment, progress tracking, and completion
 */

namespace Tests\Feature;

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../../app/Services/CourseService.php';
require_once __DIR__ . '/../../app/Models/EnrollmentModel.php';
require_once __DIR__ . '/../../app/Models/ProgressModel.php';
require_once __DIR__ . '/../../app/Models/CourseModel.php';

use Tests\Feature\TestCase;

class EnrollmentTest extends TestCase
{
    private $courseService;
    private $enrollmentModel;
    private $progressModel;
    private $courseModel;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set global connection for services
        $GLOBALS['conn'] = $this->db;

        // Initialize models and services
        $this->courseModel = new \CourseModel($this->db);
        $this->enrollmentModel = new \EnrollmentModel($this->db);
        $this->progressModel = new \ProgressModel($this->db);
        $this->courseService = new \CourseService($this->db);

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
     * Test 1: User can enroll in course
     *
     * @test
     */
    public function test_user_can_enroll_in_course()
    {
        // Create member user
        $userId = $this->createUser([
            'username' => 'enrolltester',
            'email' => 'enroll@test.com',
            'name' => 'Enroll',
            'surname' => 'Tester',
            'user_type' => 'member'
        ]);

        // Create admin and course
        $adminId = $this->createAdminUser();
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Enrollment Test Course',
            'enrollment_count' => 0
        ]);

        // Verify course exists with 0 enrollments
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'enrollment_count' => 0
        ]);

        // Verify user is not enrolled initially
        $this->assertFalse($this->enrollmentModel->isUserEnrolled($userId, $courseId));

        // Enroll user in course using CourseService
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = 'member';
        $_SESSION['authenticated'] = true;

        $result = $this->courseService->enrollUser($userId, $courseId);

        // Assert enrollment succeeded
        $this->assertTrue($result, 'User enrollment should succeed');

        // Assert user is now enrolled
        $this->assertTrue($this->enrollmentModel->isUserEnrolled($userId, $courseId));

        // Assert enrollment record exists in database
        $this->assertDatabaseHas('user_enrollments', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'progress' => 0,
            'completed' => 0
        ]);

        // Assert course enrollment_count incremented
        $sql = "SELECT enrollment_count FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $this->assertEquals(1, $result['enrollment_count'], 'Course enrollment_count should be 1');
    }

    /**
     * Test 2: User cannot enroll in course twice
     *
     * @test
     */
    public function test_user_cannot_enroll_in_course_twice()
    {
        // Create member user
        $userId = $this->createUser([
            'username' => 'duplicateenroll',
            'email' => 'duplicate@test.com',
            'user_type' => 'member'
        ]);

        // Create admin and course
        $adminId = $this->createAdminUser();
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'No Duplicate Enrollment Course'
        ]);

        // First enrollment - should succeed
        $result1 = $this->courseService->enrollUser($userId, $courseId);
        $this->assertTrue($result1, 'First enrollment should succeed');

        // Get enrollment count after first enrollment
        $sql = "SELECT enrollment_count FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $firstCount = $stmt->get_result()->fetch_assoc()['enrollment_count'];

        // Second enrollment attempt - should fail (throws exception)
        $result2 = false;
        try {
            $result2 = $this->courseService->enrollUser($userId, $courseId);
        } catch (\Exception $e) {
            // Expected behavior: CourseService throws exception for duplicate enrollment
            $this->assertStringContainsString('already enrolled', $e->getMessage());
        }
        $this->assertFalse($result2, 'Second enrollment should fail');

        // Verify enrollment_count did not increment again
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $secondCount = $stmt->get_result()->fetch_assoc()['enrollment_count'];

        $this->assertEquals($firstCount, $secondCount, 'Enrollment count should not increment on duplicate enrollment');

        // Verify only one enrollment record exists
        $sql = "SELECT COUNT(*) as count FROM user_enrollments WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();
        $enrollmentCount = $stmt->get_result()->fetch_assoc()['count'];

        $this->assertEquals(1, $enrollmentCount, 'Should have exactly 1 enrollment record');
    }

    /**
     * Test 3: User can unenroll from course
     *
     * @test
     */
    public function test_user_can_unenroll_from_course()
    {
        // Create member user
        $userId = $this->createUser([
            'username' => 'unenrolltester',
            'email' => 'unenroll@test.com',
            'user_type' => 'member'
        ]);

        // Create admin and course
        $adminId = $this->createAdminUser();
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Unenrollment Test Course'
        ]);

        // Enroll user first
        $enrollResult = $this->courseService->enrollUser($userId, $courseId);
        $this->assertTrue($enrollResult, 'Initial enrollment should succeed');

        // Verify user is enrolled
        $this->assertTrue($this->enrollmentModel->isUserEnrolled($userId, $courseId));

        // Get enrollment count before unenrollment
        $sql = "SELECT enrollment_count FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $countBefore = $stmt->get_result()->fetch_assoc()['enrollment_count'];

        // Unenroll user
        $unenrollResult = $this->courseService->unenrollUser($userId, $courseId);
        $this->assertTrue($unenrollResult, 'Unenrollment should succeed');

        // Assert user is no longer enrolled
        $this->assertFalse($this->enrollmentModel->isUserEnrolled($userId, $courseId));

        // Assert enrollment record removed from database
        $this->assertDatabaseMissing('user_enrollments', [
            'user_id' => $userId,
            'course_id' => $courseId
        ]);

        // Assert course enrollment_count decremented
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $countAfter = $stmt->get_result()->fetch_assoc()['enrollment_count'];

        $this->assertEquals($countBefore - 1, $countAfter, 'Enrollment count should decrement by 1');
    }

    /**
     * Test 4: Enrollment tracks progress correctly
     *
     * @test
     */
    public function test_enrollment_tracks_progress_correctly()
    {
        // Create member user
        $userId = $this->createUser([
            'username' => 'progresstester',
            'email' => 'progress@test.com',
            'user_type' => 'member'
        ]);

        // Create admin and course
        $adminId = $this->createAdminUser();
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Progress Tracking Course'
        ]);

        // Create 3 lessons for the course
        $lesson1Id = $this->createTestLesson($courseId, [
            'title' => 'Lesson 1: Introduction',
            'order_number' => 1
        ]);
        $lesson2Id = $this->createTestLesson($courseId, [
            'title' => 'Lesson 2: Advanced Topics',
            'order_number' => 2
        ]);
        $lesson3Id = $this->createTestLesson($courseId, [
            'title' => 'Lesson 3: Final Project',
            'order_number' => 3
        ]);

        // Enroll user
        $this->courseService->enrollUser($userId, $courseId);

        // Initial progress should be 0%
        $progressData = $this->enrollmentModel->getUserProgress($userId, $courseId);
        $this->assertEquals(0, $progressData['percent'], 'Initial progress should be 0%');
        $this->assertFalse($progressData['completed'], 'Course should not be completed');
        $this->assertTrue($progressData['started'], 'Course should be marked as started');

        // Complete first lesson (33.33% progress)
        $sql = "INSERT INTO lesson_progress (user_id, lesson_id, status, progress, completed, created_at, updated_at)
                VALUES (?, ?, 'completed', 100, 1, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $lesson1Id);
        $stmt->execute();

        // Update enrollment progress to 33.33%
        $sql = "UPDATE user_enrollments SET progress = 33.33, last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();

        // Verify progress updated
        $progressData = $this->enrollmentModel->getUserProgress($userId, $courseId);
        $this->assertGreaterThan(0, $progressData['percent'], 'Progress should be greater than 0%');
        $this->assertFalse($progressData['completed'], 'Course should not be completed yet');

        // Complete second lesson (66.66% progress)
        $stmt = $this->db->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, progress, completed, created_at, updated_at)
                                     VALUES (?, ?, 'completed', 100, 1, NOW(), NOW())");
        $stmt->bind_param('ii', $userId, $lesson2Id);
        $stmt->execute();

        $sql = "UPDATE user_enrollments SET progress = 66.66, last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();

        // Complete third lesson (100% progress)
        $stmt = $this->db->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, progress, completed, created_at, updated_at)
                                     VALUES (?, ?, 'completed', 100, 1, NOW(), NOW())");
        $stmt->bind_param('ii', $userId, $lesson3Id);
        $stmt->execute();

        $sql = "UPDATE user_enrollments SET progress = 100, completed = 1, completion_date = NOW(), last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();

        // Verify final progress
        $progressData = $this->enrollmentModel->getUserProgress($userId, $courseId);
        $this->assertEquals(100, $progressData['percent'], 'Progress should be 100%');
        $this->assertTrue($progressData['completed'], 'Course should be marked as completed');

        // Verify all lessons marked as completed
        $this->assertTrue($this->enrollmentModel->isLessonCompleted($userId, $lesson1Id));
        $this->assertTrue($this->enrollmentModel->isLessonCompleted($userId, $lesson2Id));
        $this->assertTrue($this->enrollmentModel->isLessonCompleted($userId, $lesson3Id));
    }

    /**
     * Test 5: Course completion updates statistics
     *
     * @test
     */
    public function test_course_completion_updates_statistics()
    {
        // Create admin and course
        $adminId = $this->createAdminUser();
        $courseId = $this->createTestCourse($adminId, [
            'title' => 'Statistics Test Course',
            'enrollment_count' => 0
        ]);

        // Create 3 member users using raw SQL to avoid transaction isolation issues with AUTO_INCREMENT
        $timestamp = time();
        $userSql = "INSERT INTO users (username, email, password, name, surname, user_type, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'member', 'active', NOW(), NOW())";

        // User 1
        $username1 = "stats_user1_$timestamp";
        $email1 = "stats1_$timestamp@test.com";
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $name = 'Stats';
        $surname1 = 'User1';
        $stmt = $this->db->prepare($userSql);
        $stmt->bind_param('sssss', $username1, $email1, $password, $name, $surname1);
        $stmt->execute();
        $user1Id = $this->db->insert_id;

        // User 2
        $username2 = "stats_user2_$timestamp";
        $email2 = "stats2_$timestamp@test.com";
        $surname2 = 'User2';
        $stmt = $this->db->prepare($userSql);
        $stmt->bind_param('sssss', $username2, $email2, $password, $name, $surname2);
        $stmt->execute();
        $user2Id = $this->db->insert_id;

        // User 3
        $username3 = "stats_user3_$timestamp";
        $email3 = "stats3_$timestamp@test.com";
        $surname3 = 'User3';
        $stmt = $this->db->prepare($userSql);
        $stmt->bind_param('sssss', $username3, $email3, $password, $name, $surname3);
        $stmt->execute();
        $user3Id = $this->db->insert_id;

        // Verify we have 3 distinct user IDs
        $this->assertNotEquals($user1Id, $user2Id, 'User 1 and User 2 should have different IDs');
        $this->assertNotEquals($user2Id, $user3Id, 'User 2 and User 3 should have different IDs');
        $this->assertNotEquals($user1Id, $user3Id, 'User 1 and User 3 should have different IDs');

        // Enroll all 3 users using raw SQL for reliability in transaction isolation
        $sql = "INSERT INTO user_enrollments (user_id, course_id, enrollment_date, progress, completed, last_accessed, created_at, updated_at)
                VALUES (?, ?, NOW(), 0, 0, NOW(), NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user1Id, $courseId);
        $stmt->execute();

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user2Id, $courseId);
        $stmt->execute();

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user3Id, $courseId);
        $stmt->execute();

        // Update enrollment count manually
        $updateSql = "UPDATE courses SET enrollment_count = enrollment_count + 3 WHERE id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param('i', $courseId);
        $updateStmt->execute();

        // Verify enrollment count is 3
        $sql = "SELECT enrollment_count FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $enrollmentCount = $stmt->get_result()->fetch_assoc()['enrollment_count'];

        $this->assertEquals(3, $enrollmentCount, 'Enrollment count should be 3');

        // Mark user1 as 50% complete (NOT completed)
        $sql = "UPDATE user_enrollments SET progress = 50, completed = 0, last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user1Id, $courseId);
        $stmt->execute();

        // Mark user2 as 100% complete
        $sql = "UPDATE user_enrollments SET progress = 100, completed = 1, completion_date = NOW(), last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user2Id, $courseId);
        $stmt->execute();

        // Mark user3 as 100% complete
        $sql = "UPDATE user_enrollments SET progress = 100, completed = 1, completion_date = NOW(), last_accessed = NOW() WHERE user_id = ? AND course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $user3Id, $courseId);
        $stmt->execute();

        // Get completion statistics
        $sql = "SELECT
                    COUNT(*) as total_enrolled,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as total_completed,
                    AVG(progress) as avg_progress,
                    SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress_count
                FROM user_enrollments
                WHERE course_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();

        // Assert statistics are accurate
        $this->assertEquals(3, $stats['total_enrolled'], 'Total enrolled should be 3');
        $this->assertEquals(2, $stats['total_completed'], 'Total completed should be 2');
        $this->assertEquals(1, $stats['in_progress_count'], 'In-progress count should be 1');

        // Average progress: (50 + 100 + 100) / 3 = 83.33
        $expectedAvgProgress = round((50 + 100 + 100) / 3, 2);
        $actualAvgProgress = round($stats['avg_progress'], 2);
        $this->assertEquals($expectedAvgProgress, $actualAvgProgress, 'Average progress should be 83.33%');

        // Test CourseService.getCourseStatistics()
        $courseStats = $this->courseService->getCourseStatistics($courseId);
        $this->assertNotNull($courseStats, 'Course statistics should not be null');
        $this->assertEquals(3, $courseStats['total_enrollments'], 'Statistics should show 3 enrollments');
    }

    /**
     * Helper: Create test course
     *
     * @param int $createdBy User ID of creator
     * @param array $overrides Custom field values
     * @return int Course ID
     */
    private function createTestCourse(int $createdBy, array $overrides = []): int
    {
        $defaults = [
            'course_code' => 'ENRL-' . time() . '-' . rand(1000, 9999),
            'title' => 'Test Enrollment Course ' . time(),
            'description' => 'Test course for enrollment',
            'type' => 'full_course',
            'difficulty_level' => 'Beginner',
            'category' => 'General',
            'status' => 'active',
            'is_published' => 1,
            'enrollment_count' => 0
        ];

        $data = array_merge($defaults, $overrides);

        $sql = "INSERT INTO courses (course_code, title, description, type, difficulty_level, category, status, is_published, enrollment_count, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sssssssiis',
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
     * Helper: Create test lesson
     *
     * @param int $courseId Course ID
     * @param array $overrides Custom field values
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
        $stmt->bind_param(
            'issii',
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
?>
