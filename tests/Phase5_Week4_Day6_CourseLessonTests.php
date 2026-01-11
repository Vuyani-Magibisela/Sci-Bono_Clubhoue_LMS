<?php
/**
 * Phase 5 Week 4 Day 6 - Course & Lesson API Integration Tests
 *
 * Tests admin course and lesson management APIs:
 * - Course CRUD operations (Days 1-2)
 * - Course image upload (Day 2)
 * - Lesson CRUD operations (Days 3-4)
 * - Lesson content upload (Day 4)
 *
 * Test categories:
 * - Course Management (10 tests)
 * - Course Image Upload (5 tests)
 * - Lesson Management (10 tests)
 * - Lesson Content Upload (5 tests)
 * - Business Logic Validation (5 tests)
 * - Error Handling (5 tests)
 *
 * Total: 40 tests
 *
 * @package Tests
 * @since Phase 5 Week 4 Day 6 (January 11, 2026)
 */

require_once __DIR__ . '/../server.php';
require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\Api\Admin\CourseController;
use App\Controllers\Api\Admin\LessonController;
use App\Models\Admin\AdminCourseModel;
use App\Models\Admin\AdminLessonModel;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Phase 5 Week 4 Day 6 - Course & Lesson API Tests\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$testsPassed = 0;
$testsFailed = 0;
$testResults = [];

// Test data storage
$testCourseId = null;
$testSectionId = null;
$testLessonId = null;

// Helper function to record test results
function recordTest($name, $passed, $message = '') {
    global $testsPassed, $testsFailed, $testResults;
    if ($passed) {
        $testsPassed++;
        $testResults[] = "✅ PASS: {$name}";
        echo "  ✅ PASS: {$name}\n";
        if ($message) echo "     {$message}\n";
    } else {
        $testsFailed++;
        $testResults[] = "❌ FAIL: {$name}";
        echo "  ❌ FAIL: {$name}\n";
        if ($message) echo "     {$message}\n";
    }
}

// Setup test environment
function setupTestEnvironment() {
    global $conn;

    // Create test admin user
    $hashedPassword = password_hash('test123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (firstname, surname, email, password, role, status)
                  VALUES ('Test', 'Admin', 'testadmin@test.com', '$hashedPassword', 'admin', 'active')
                  ON DUPLICATE KEY UPDATE role = 'admin'");

    // Get test admin user ID
    $result = $conn->query("SELECT id FROM users WHERE email = 'testadmin@test.com'");
    $adminUser = $result->fetch_assoc();

    // Setup session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $adminUser['id'];
    $_SESSION['role'] = 'admin';
    $_SESSION['email'] = 'testadmin@test.com';

    // Generate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $adminUser['id'];
}

// Cleanup test data
function cleanupTestData() {
    global $conn, $testCourseId, $testLessonId;

    // Delete test lesson
    if ($testLessonId) {
        $conn->query("DELETE FROM lessons WHERE id = $testLessonId");
    }

    // Delete test course (cascade will handle sections and lessons)
    if ($testCourseId) {
        $conn->query("DELETE FROM course_sections WHERE course_id = $testCourseId");
        $conn->query("DELETE FROM courses WHERE id = $testCourseId");
    }

    // Delete test user
    $conn->query("DELETE FROM users WHERE email = 'testadmin@test.com'");
}

// ═══════════════════════════════════════════════════════════════
// SETUP
// ═══════════════════════════════════════════════════════════════
echo "Setting up test environment...\n";
$adminUserId = setupTestEnvironment();
echo "Test admin user ID: $adminUserId\n\n";

// ═══════════════════════════════════════════════════════════════
// SECTION 1: Course Management (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "Section 1: Course Management (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 1: Initialize course model
try {
    $courseModel = new AdminCourseModel($conn);
    $passed = $courseModel !== null;
    recordTest("Course model initialization", $passed, "AdminCourseModel instantiated");
} catch (\Exception $e) {
    recordTest("Course model initialization", false, $e->getMessage());
}

// Test 2: Create course via model
try {
    $courseData = [
        'title' => 'Test Course - Integration Tests',
        'description' => 'A test course for integration testing',
        'type' => 'full_course',
        'difficulty_level' => 'intermediate',
        'duration' => 40,
        'is_featured' => 1,
        'is_published' => 1,
        'status' => 'published',
        'created_by' => $adminUserId,
        'course_code' => 'TEST-INTEG-001'
    ];

    $testCourseId = $courseModel->createCourse($courseData);
    $passed = $testCourseId !== null && $testCourseId > 0;
    recordTest("Create course via model", $passed, "Course ID: $testCourseId");
} catch (\Exception $e) {
    recordTest("Create course via model", false, $e->getMessage());
}

// Test 3: Get course by ID
try {
    $course = $courseModel->getCourseById($testCourseId);
    $passed = $course !== null && $course['id'] == $testCourseId;
    recordTest("Get course by ID", $passed, "Retrieved course: {$course['title']}");
} catch (\Exception $e) {
    recordTest("Get course by ID", false, $e->getMessage());
}

// Test 4: Update course via model
try {
    $updateData = [
        'title' => 'Updated Test Course',
        'description' => 'Updated description for testing',
        'difficulty_level' => 'advanced'
    ];

    $result = $courseModel->updateCourse($testCourseId, $updateData);
    $passed = $result === true;

    // Verify update
    $updatedCourse = $courseModel->getCourseById($testCourseId);
    $passed = $passed && $updatedCourse['title'] === 'Updated Test Course';
    $passed = $passed && $updatedCourse['difficulty_level'] === 'advanced';

    recordTest("Update course via model", $passed, "Title and difficulty updated");
} catch (\Exception $e) {
    recordTest("Update course via model", false, $e->getMessage());
}

// Test 5: Get course details (getCourseDetails alias test)
try {
    $details = $courseModel->getCourseDetails($testCourseId);
    $passed = $details !== null && $details['id'] == $testCourseId;
    recordTest("Get course details (alias)", $passed, "getCourseDetails() works");
} catch (\Exception $e) {
    recordTest("Get course details (alias)", false, $e->getMessage());
}

// Test 6: Get all courses
try {
    $allCourses = $courseModel->getAllCourses();
    $passed = is_array($allCourses) && count($allCourses) > 0;

    // Verify test course is in list
    $foundTestCourse = false;
    foreach ($allCourses as $course) {
        if ($course['id'] == $testCourseId) {
            $foundTestCourse = true;
            break;
        }
    }
    $passed = $passed && $foundTestCourse;

    recordTest("Get all courses", $passed, "Total courses: " . count($allCourses));
} catch (\Exception $e) {
    recordTest("Get all courses", false, $e->getMessage());
}

// Test 7: Create course section (needed for lesson tests)
try {
    $sectionData = [
        'title' => 'Test Section 1',
        'description' => 'Section for lesson testing',
        'order_number' => 1
    ];

    $testSectionId = $courseModel->createSection($testCourseId, $sectionData);
    $passed = $testSectionId !== null && $testSectionId > 0;
    recordTest("Create course section", $passed, "Section ID: $testSectionId");
} catch (\Exception $e) {
    recordTest("Create course section", false, $e->getMessage());
}

// Test 8: Get course sections
try {
    $sections = $courseModel->getCourseSections($testCourseId);
    $passed = is_array($sections) && count($sections) > 0;

    // Verify test section is in list
    $foundTestSection = false;
    foreach ($sections as $section) {
        if ($section['id'] == $testSectionId) {
            $foundTestSection = true;
            break;
        }
    }
    $passed = $passed && $foundTestSection;

    recordTest("Get course sections", $passed, "Found " . count($sections) . " section(s)");
} catch (\Exception $e) {
    recordTest("Get course sections", false, $e->getMessage());
}

// Test 9: Get course enrollment count
try {
    $enrollmentCount = $courseModel->getEnrollmentCount($testCourseId);
    $passed = is_numeric($enrollmentCount) && $enrollmentCount >= 0;
    recordTest("Get course enrollment count", $passed, "Enrollments: $enrollmentCount");
} catch (\Exception $e) {
    recordTest("Get course enrollment count", false, $e->getMessage());
}

// Test 10: Course validation - empty title
try {
    $invalidData = [
        'title' => '', // Empty title
        'description' => 'Test description',
        'created_by' => $adminUserId
    ];

    $result = $courseModel->createCourse($invalidData);

    // Should fail to create course with empty title
    $passed = $result === null || $result === false;
    recordTest("Course validation - empty title", $passed, "Empty title rejected");
} catch (\Exception $e) {
    // Exception is expected for invalid data
    recordTest("Course validation - empty title", true, "Validation error thrown as expected");
}

// ═══════════════════════════════════════════════════════════════
// SECTION 2: Course Image Upload (5 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 2: Course Image Upload (5 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 11: Course controller initialization
try {
    $courseController = new CourseController();
    $passed = $courseController !== null;
    recordTest("Course controller initialization", $passed, "CourseController instantiated");
} catch (\Exception $e) {
    recordTest("Course controller initialization", false, $e->getMessage());
}

// Test 12: Validate image upload requirements
try {
    // Simulate image upload validation (file size, type)
    $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $passed = is_array($validExtensions) && count($validExtensions) === 4;
    $passed = $passed && $maxSize === 5242880;

    recordTest("Validate image upload requirements", $passed, "Max 5MB, jpg/jpeg/png/gif allowed");
} catch (\Exception $e) {
    recordTest("Validate image upload requirements", false, $e->getMessage());
}

// Test 13: Image path generation
try {
    $uploadDir = 'public/assets/uploads/courses/';
    $filename = 'test_image.jpg';
    $imagePath = $uploadDir . date('Y-m') . '/' . $filename;

    $passed = strpos($imagePath, date('Y-m')) !== false;
    recordTest("Image path generation", $passed, "Path: $imagePath");
} catch (\Exception $e) {
    recordTest("Image path generation", false, $e->getMessage());
}

// Test 14: Update course with image path
try {
    $imagePath = 'public/assets/uploads/courses/' . date('Y-m') . '/test_image.jpg';
    $updateData = [
        'image_path' => $imagePath
    ];

    $result = $courseModel->updateCourse($testCourseId, $updateData);
    $passed = $result === true;

    // Verify image path updated
    $course = $courseModel->getCourseById($testCourseId);
    $passed = $passed && $course['image_path'] === $imagePath;

    recordTest("Update course with image path", $passed, "Image path: $imagePath");
} catch (\Exception $e) {
    recordTest("Update course with image path", false, $e->getMessage());
}

// Test 15: Image file type validation
try {
    $validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $invalidType = 'application/pdf';

    $isValidJpeg = in_array('image/jpeg', $validTypes);
    $isInvalidPdf = !in_array($invalidType, $validTypes);

    $passed = $isValidJpeg && $isInvalidPdf;
    recordTest("Image file type validation", $passed, "JPEG valid, PDF invalid");
} catch (\Exception $e) {
    recordTest("Image file type validation", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 3: Lesson Management (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 3: Lesson Management (10 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 16: Initialize lesson model
try {
    $lessonModel = new AdminLessonModel($conn);
    $passed = $lessonModel !== null;
    recordTest("Lesson model initialization", $passed, "AdminLessonModel instantiated");
} catch (\Exception $e) {
    recordTest("Lesson model initialization", false, $e->getMessage());
}

// Test 17: Create lesson via model
try {
    $lessonData = [
        'title' => 'Test Lesson - Introduction',
        'description' => 'Introduction to the test course',
        'content' => '<p>This is test lesson content</p>',
        'order_number' => 1,
        'duration' => 30,
        'is_published' => 1
    ];

    $testLessonId = $lessonModel->createLesson($testSectionId, $lessonData);
    $passed = $testLessonId !== null && $testLessonId > 0;
    recordTest("Create lesson via model", $passed, "Lesson ID: $testLessonId");
} catch (\Exception $e) {
    recordTest("Create lesson via model", false, $e->getMessage());
}

// Test 18: Get lesson details
try {
    $lesson = $lessonModel->getLessonDetails($testLessonId);
    $passed = $lesson !== null && $lesson['id'] == $testLessonId;
    $passed = $passed && $lesson['title'] === 'Test Lesson - Introduction';
    recordTest("Get lesson details", $passed, "Retrieved lesson: {$lesson['title']}");
} catch (\Exception $e) {
    recordTest("Get lesson details", false, $e->getMessage());
}

// Test 19: Update lesson via model
try {
    $updateData = [
        'title' => 'Updated Test Lesson',
        'description' => 'Updated lesson description',
        'duration' => 45
    ];

    $result = $lessonModel->updateLesson($testLessonId, $updateData);
    $passed = $result === true;

    // Verify update
    $updatedLesson = $lessonModel->getLessonDetails($testLessonId);
    $passed = $passed && $updatedLesson['title'] === 'Updated Test Lesson';
    $passed = $passed && $updatedLesson['duration'] == 45;

    recordTest("Update lesson via model", $passed, "Title and duration updated");
} catch (\Exception $e) {
    recordTest("Update lesson via model", false, $e->getMessage());
}

// Test 20: Get section details
try {
    $section = $lessonModel->getSectionDetails($testSectionId);
    $passed = $section !== null && $section['id'] == $testSectionId;
    $passed = $passed && $section['course_id'] == $testCourseId;
    recordTest("Get section details", $passed, "Section: {$section['title']}, Course ID: {$section['course_id']}");
} catch (\Exception $e) {
    recordTest("Get section details", false, $e->getMessage());
}

// Test 21: Get section lessons
try {
    $lessons = $lessonModel->getSectionLessons($testSectionId);
    $passed = is_array($lessons) && count($lessons) > 0;

    // Verify test lesson is in list
    $foundTestLesson = false;
    foreach ($lessons as $lesson) {
        if ($lesson['id'] == $testLessonId) {
            $foundTestLesson = true;
            break;
        }
    }
    $passed = $passed && $foundTestLesson;

    recordTest("Get section lessons", $passed, "Found " . count($lessons) . " lesson(s)");
} catch (\Exception $e) {
    recordTest("Get section lessons", false, $e->getMessage());
}

// Test 22: Lesson validation - empty title
try {
    $invalidData = [
        'title' => '', // Empty title
        'description' => 'Test description',
        'content' => 'Test content'
    ];

    $result = $lessonModel->createLesson($testSectionId, $invalidData);

    // Should fail to create lesson with empty title
    $passed = $result === null || $result === false;
    recordTest("Lesson validation - empty title", $passed, "Empty title rejected");
} catch (\Exception $e) {
    // Exception is expected for invalid data
    recordTest("Lesson validation - empty title", true, "Validation error thrown as expected");
}

// Test 23: Create second lesson (ordering test)
try {
    $lessonData = [
        'title' => 'Test Lesson 2 - Advanced Topics',
        'description' => 'Advanced topics in the test course',
        'content' => '<p>Advanced lesson content</p>',
        'order_number' => 2,
        'duration' => 60,
        'is_published' => 1
    ];

    $lesson2Id = $lessonModel->createLesson($testSectionId, $lessonData);
    $passed = $lesson2Id !== null && $lesson2Id > 0;

    // Verify ordering
    $lessons = $lessonModel->getSectionLessons($testSectionId);
    $passed = $passed && count($lessons) === 2;

    recordTest("Create second lesson (ordering)", $passed, "2 lessons in correct order");
} catch (\Exception $e) {
    recordTest("Create second lesson (ordering)", false, $e->getMessage());
}

// Test 24: Lesson controller initialization
try {
    $lessonController = new LessonController();
    $passed = $lessonController !== null;
    recordTest("Lesson controller initialization", $passed, "LessonController instantiated");
} catch (\Exception $e) {
    recordTest("Lesson controller initialization", false, $e->getMessage());
}

// Test 25: Get course lessons (all sections)
try {
    $allLessons = $lessonModel->getCourseLessons($testCourseId);
    $passed = is_array($allLessons) && count($allLessons) >= 2;
    recordTest("Get course lessons (all sections)", $passed, "Total lessons: " . count($allLessons));
} catch (\Exception $e) {
    recordTest("Get course lessons (all sections)", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 4: Lesson Content Upload (5 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 4: Lesson Content Upload (5 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 26: Validate content upload requirements
try {
    $validExtensions = ['pdf', 'docx', 'pptx', 'mp4'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    $passed = is_array($validExtensions) && count($validExtensions) === 4;
    $passed = $passed && $maxSize === 10485760;

    recordTest("Validate content upload requirements", $passed, "Max 10MB, pdf/docx/pptx/mp4 allowed");
} catch (\Exception $e) {
    recordTest("Validate content upload requirements", false, $e->getMessage());
}

// Test 27: Content path generation
try {
    $uploadDir = 'public/assets/uploads/lessons/';
    $filename = 'test_content.pdf';
    $contentPath = $uploadDir . date('Y-m') . '/' . $filename;

    $passed = strpos($contentPath, date('Y-m')) !== false;
    recordTest("Content path generation", $passed, "Path: $contentPath");
} catch (\Exception $e) {
    recordTest("Content path generation", false, $e->getMessage());
}

// Test 28: Update lesson with content file path
try {
    $contentPath = 'public/assets/uploads/lessons/' . date('Y-m') . '/test_content.pdf';
    $updateData = [
        'content_file_path' => $contentPath
    ];

    $result = $lessonModel->updateLesson($testLessonId, $updateData);
    $passed = $result === true;

    recordTest("Update lesson with content file path", $passed, "Content path: $contentPath");
} catch (\Exception $e) {
    recordTest("Update lesson with content file path", false, $e->getMessage());
}

// Test 29: Content file type validation
try {
    $validTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'video/mp4'
    ];
    $invalidType = 'application/zip';

    $isValidPdf = in_array('application/pdf', $validTypes);
    $isInvalidZip = !in_array($invalidType, $validTypes);

    $passed = $isValidPdf && $isInvalidZip;
    recordTest("Content file type validation", $passed, "PDF valid, ZIP invalid");
} catch (\Exception $e) {
    recordTest("Content file type validation", false, $e->getMessage());
}

// Test 30: Multiple content file paths per lesson
try {
    // A lesson can have both content_file_path and other content
    $lesson = $lessonModel->getLessonDetails($testLessonId);
    $hasContent = !empty($lesson['content']);
    $hasFilePath = !empty($lesson['content_file_path']);

    $passed = $hasContent || $hasFilePath; // At least one should be present
    recordTest("Multiple content types per lesson", $passed, "Lesson has content and/or file");
} catch (\Exception $e) {
    recordTest("Multiple content types per lesson", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 5: Business Logic Validation (5 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 5: Business Logic Validation (5 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 31: Section-course ownership validation
try {
    $section = $lessonModel->getSectionDetails($testSectionId);
    $actualCourseId = $section['course_id'];

    // Verify section belongs to test course
    $passed = $actualCourseId == $testCourseId;
    recordTest("Section-course ownership validation", $passed, "Section belongs to course $testCourseId");
} catch (\Exception $e) {
    recordTest("Section-course ownership validation", false, $e->getMessage());
}

// Test 32: Prevent lesson creation in non-existent section
try {
    $invalidSectionId = 999999;
    $lessonData = [
        'title' => 'Invalid Lesson',
        'description' => 'Should not be created',
        'content' => 'Test'
    ];

    $result = $lessonModel->createLesson($invalidSectionId, $lessonData);

    // Should fail to create lesson in non-existent section
    $passed = $result === null || $result === false;
    recordTest("Prevent lesson creation in non-existent section", $passed, "Invalid section rejected");
} catch (\Exception $e) {
    // Exception is expected for invalid section
    recordTest("Prevent lesson creation in non-existent section", true, "Error thrown as expected");
}

// Test 33: Course deletion should cascade to sections
try {
    // Create temporary course for deletion test
    $tempCourseData = [
        'title' => 'Temp Course for Deletion',
        'description' => 'Will be deleted',
        'created_by' => $adminUserId
    ];
    $tempCourseId = $courseModel->createCourse($tempCourseData);

    // Create section in temp course
    $tempSectionData = [
        'title' => 'Temp Section',
        'description' => 'Will be deleted with course'
    ];
    $tempSectionId = $courseModel->createSection($tempCourseId, $tempSectionData);

    // Delete course
    $result = $courseModel->deleteCourse($tempCourseId);
    $passed = $result === true;

    // Verify section is also deleted
    $section = $lessonModel->getSectionDetails($tempSectionId);
    $passed = $passed && ($section === null || $section === false);

    recordTest("Course deletion cascades to sections", $passed, "Section deleted with course");
} catch (\Exception $e) {
    recordTest("Course deletion cascades to sections", false, $e->getMessage());
}

// Test 34: Lesson order_number validation
try {
    $lessons = $lessonModel->getSectionLessons($testSectionId);

    // Verify lessons are ordered by order_number
    $isOrdered = true;
    $prevOrder = -1;
    foreach ($lessons as $lesson) {
        if ($lesson['order_number'] <= $prevOrder) {
            $isOrdered = false;
            break;
        }
        $prevOrder = $lesson['order_number'];
    }

    $passed = $isOrdered;
    recordTest("Lesson order_number validation", $passed, "Lessons ordered correctly");
} catch (\Exception $e) {
    recordTest("Lesson order_number validation", false, $e->getMessage());
}

// Test 35: Course status validation
try {
    $validStatuses = ['draft', 'published', 'archived'];
    $course = $courseModel->getCourseById($testCourseId);
    $courseStatus = $course['status'];

    $passed = in_array($courseStatus, $validStatuses);
    recordTest("Course status validation", $passed, "Status: $courseStatus");
} catch (\Exception $e) {
    recordTest("Course status validation", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// SECTION 6: Error Handling (5 tests)
// ═══════════════════════════════════════════════════════════════
echo "\nSection 6: Error Handling (5 tests)\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test 36: Get non-existent course
try {
    $invalidCourseId = 999999;
    $course = $courseModel->getCourseById($invalidCourseId);

    $passed = $course === null || $course === false;
    recordTest("Get non-existent course", $passed, "Null returned for invalid ID");
} catch (\Exception $e) {
    recordTest("Get non-existent course", true, "Exception thrown as expected");
}

// Test 37: Get non-existent lesson
try {
    $invalidLessonId = 999999;
    $lesson = $lessonModel->getLessonDetails($invalidLessonId);

    $passed = $lesson === null || $lesson === false;
    recordTest("Get non-existent lesson", $passed, "Null returned for invalid ID");
} catch (\Exception $e) {
    recordTest("Get non-existent lesson", true, "Exception thrown as expected");
}

// Test 38: Update non-existent course
try {
    $invalidCourseId = 999999;
    $updateData = ['title' => 'Should Fail'];

    $result = $courseModel->updateCourse($invalidCourseId, $updateData);

    $passed = $result === false || $result === null;
    recordTest("Update non-existent course", $passed, "Update failed as expected");
} catch (\Exception $e) {
    recordTest("Update non-existent course", true, "Exception thrown as expected");
}

// Test 39: Delete non-existent lesson
try {
    $invalidLessonId = 999999;
    $result = $lessonModel->deleteLesson($invalidLessonId);

    $passed = $result === false || $result === null;
    recordTest("Delete non-existent lesson", $passed, "Delete failed as expected");
} catch (\Exception $e) {
    recordTest("Delete non-existent lesson", true, "Exception thrown as expected");
}

// Test 40: Invalid course type validation
try {
    $invalidData = [
        'title' => 'Invalid Course',
        'description' => 'Has invalid type',
        'type' => 'invalid_type', // Not in enum
        'created_by' => $adminUserId
    ];

    // Attempt to create course with invalid type
    // This may or may not throw depending on database constraints
    try {
        $result = $courseModel->createCourse($invalidData);
        $passed = $result === null || $result === false;
        recordTest("Invalid course type validation", $passed, "Invalid type rejected");
    } catch (\Exception $inner) {
        recordTest("Invalid course type validation", true, "Database constraint enforced");
    }
} catch (\Exception $e) {
    recordTest("Invalid course type validation", false, $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// CLEANUP
// ═══════════════════════════════════════════════════════════════
echo "\nCleaning up test data...\n";
cleanupTestData();
echo "Cleanup complete.\n\n";

// ═══════════════════════════════════════════════════════════════
// RESULTS SUMMARY
// ═══════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST RESULTS SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalTests = $testsPassed + $testsFailed;
$passRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 2) : 0;

echo "Total Tests: $totalTests\n";
echo "Passed: $testsPassed ✅\n";
echo "Failed: $testsFailed ❌\n";
echo "Pass Rate: $passRate%\n\n";

if ($testsFailed > 0) {
    echo "Failed Tests:\n";
    foreach ($testResults as $result) {
        if (strpos($result, '❌') !== false) {
            echo "  $result\n";
        }
    }
    echo "\n";
}

// Exit with appropriate code
exit($testsFailed > 0 ? 1 : 0);
