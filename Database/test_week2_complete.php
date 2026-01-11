<?php
/**
 * Phase 4 Week 2: Complete Test Suite
 * Tests all components created during Week 2 (Data Migration)
 *
 * Tests:
 * - Database migrations
 * - Models (3 new)
 * - Repositories (3 new)
 * - Seeders (4 files)
 * - Cache service
 * - Integration (full stack)
 */

require_once __DIR__ . '/../server.php';

echo "==========================================\n";
echo "Phase 4 Week 2: Complete Test Suite\n";
echo "==========================================\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest($name, $callback) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;

    try {
        $result = $callback();
        if ($result === true || $result === null) {
            $passedTests++;
            echo "✓ $name\n";
            return true;
        } else {
            $failedTests++;
            echo "✗ $name: $result\n";
            return false;
        }
    } catch (Exception $e) {
        $failedTests++;
        echo "✗ $name: " . $e->getMessage() . "\n";
        return false;
    }
}

// ==========================================
// Test Suite 1: Database Schema
// ==========================================
echo "Test Suite 1: Database Schema\n";
echo "-------------------------------------------\n";

runTest("Table 'program_requirements' exists", function() use ($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'program_requirements'");
    return $result && $result->num_rows > 0;
});

runTest("Table 'evaluation_criteria' exists", function() use ($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'evaluation_criteria'");
    return $result && $result->num_rows > 0;
});

runTest("Table 'faqs' exists", function() use ($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'faqs'");
    return $result && $result->num_rows > 0;
});

runTest("Requirements table has correct columns", function() use ($conn) {
    $result = $conn->query("DESCRIBE program_requirements");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $required = ['id', 'category', 'requirement', 'order_number', 'is_active'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Missing column: $col";
        }
    }
    return true;
});

runTest("Criteria table has correct columns", function() use ($conn) {
    $result = $conn->query("DESCRIBE evaluation_criteria");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $required = ['id', 'name', 'description', 'points', 'category', 'is_active'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Missing column: $col";
        }
    }
    return true;
});

runTest("FAQs table has correct columns", function() use ($conn) {
    $result = $conn->query("DESCRIBE faqs");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $required = ['id', 'category', 'question', 'answer', 'is_active'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Missing column: $col";
        }
    }
    return true;
});

echo "\n";

// ==========================================
// Test Suite 2: Data Seeding
// ==========================================
echo "Test Suite 2: Data Seeding\n";
echo "-------------------------------------------\n";

runTest("Requirements table has seeded data", function() use ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM program_requirements");
    $count = $result->fetch_assoc()['count'];
    return $count >= 13 ? true : "Expected >= 13, got $count";
});

runTest("Criteria table has seeded data", function() use ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM evaluation_criteria");
    $count = $result->fetch_assoc()['count'];
    return $count >= 11 ? true : "Expected >= 11, got $count";
});

runTest("FAQs table has seeded data", function() use ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM faqs");
    $count = $result->fetch_assoc()['count'];
    return $count >= 25 ? true : "Expected >= 25, got $count";
});

runTest("Requirements categorized correctly", function() use ($conn) {
    $result = $conn->query("SELECT DISTINCT category FROM program_requirements ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $expected = ['Age Requirements', 'General Requirements', 'Project Guidelines', 'What to Bring'];
    foreach ($expected as $cat) {
        if (!in_array($cat, $categories)) {
            return "Missing category: $cat";
        }
    }
    return true;
});

runTest("Criteria categorized correctly", function() use ($conn) {
    $result = $conn->query("SELECT DISTINCT category FROM evaluation_criteria ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $expected = ['Participation', 'Project Evaluation', 'Teamwork'];
    foreach ($expected as $cat) {
        if (!in_array($cat, $categories)) {
            return "Missing category: $cat";
        }
    }
    return true;
});

runTest("FAQs categorized correctly", function() use ($conn) {
    $result = $conn->query("SELECT DISTINCT category FROM faqs ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $expected = ['General', 'Logistics', 'Programs', 'Registration', 'Technical'];
    foreach ($expected as $cat) {
        if (!in_array($cat, $categories)) {
            return "Missing category: $cat";
        }
    }
    return true;
});

runTest("Criteria points total 160", function() use ($conn) {
    $result = $conn->query("SELECT SUM(points) as total FROM evaluation_criteria WHERE is_active = 1");
    $total = $result->fetch_assoc()['total'];
    return $total == 160 ? true : "Expected 160, got $total";
});

echo "\n";

// ==========================================
// Test Suite 3: Models
// ==========================================
echo "Test Suite 3: Models\n";
echo "-------------------------------------------\n";

require_once __DIR__ . '/../app/Models/ProgramRequirement.php';
require_once __DIR__ . '/../app/Models/EvaluationCriteria.php';
require_once __DIR__ . '/../app/Models/FAQ.php';

runTest("ProgramRequirement model instantiates", function() use ($conn) {
    $model = new ProgramRequirement($conn);
    return $model instanceof ProgramRequirement;
});

runTest("EvaluationCriteria model instantiates", function() use ($conn) {
    $model = new EvaluationCriteria($conn);
    return $model instanceof EvaluationCriteria;
});

runTest("FAQ model instantiates", function() use ($conn) {
    $model = new FAQ($conn);
    return $model instanceof FAQ;
});

runTest("ProgramRequirement::getByCategory() works", function() use ($conn) {
    $model = new ProgramRequirement($conn);
    $requirements = $model->getByCategory(true);
    return is_array($requirements) && count($requirements) > 0;
});

runTest("EvaluationCriteria::getTotalPoints() works", function() use ($conn) {
    $model = new EvaluationCriteria($conn);
    $total = $model->getTotalPoints(null, true);
    return $total == 160 ? true : "Expected 160, got $total";
});

runTest("FAQ::getByCategory() works", function() use ($conn) {
    $model = new FAQ($conn);
    $faqs = $model->getByCategory(true);
    return is_array($faqs) && count($faqs) > 0;
});

echo "\n";

// ==========================================
// Test Suite 4: Repositories
// ==========================================
echo "Test Suite 4: Repositories\n";
echo "-------------------------------------------\n";

require_once __DIR__ . '/../app/Repositories/ProgramRequirementRepository.php';
require_once __DIR__ . '/../app/Repositories/EvaluationCriteriaRepository.php';
require_once __DIR__ . '/../app/Repositories/FAQRepository.php';

runTest("ProgramRequirementRepository instantiates", function() use ($conn) {
    $repo = new ProgramRequirementRepository($conn);
    return $repo instanceof ProgramRequirementRepository;
});

runTest("EvaluationCriteriaRepository instantiates", function() use ($conn) {
    $repo = new EvaluationCriteriaRepository($conn);
    return $repo instanceof EvaluationCriteriaRepository;
});

runTest("FAQRepository instantiates", function() use ($conn) {
    $repo = new FAQRepository($conn);
    return $repo instanceof FAQRepository;
});

runTest("Repository::getByCategory() returns correct data", function() use ($conn) {
    $repo = new ProgramRequirementRepository($conn);
    $requirements = $repo->getByCategory('Project Guidelines', true);
    return is_array($requirements) && count($requirements) == 4;
});

runTest("Repository::getAsKeyValue() returns associative array", function() use ($conn) {
    $repo = new EvaluationCriteriaRepository($conn);
    $criteria = $repo->getAsKeyValue('Project Evaluation', true);
    if (!is_array($criteria)) return "Not an array";
    if (count($criteria) != 5) return "Expected 5, got " . count($criteria);
    $keys = array_keys($criteria);
    return !is_numeric($keys[0]) ? true : "Should be associative array";
});

runTest("Repository::getLegacyFormat() returns correct structure", function() use ($conn) {
    $repo = new FAQRepository($conn);
    $faqs = $repo->getLegacyFormat(null, true);
    if (!is_array($faqs)) return "Not an array";
    if (count($faqs) == 0) return "Empty array";
    $first = $faqs[0];
    if (!isset($first['question']) || !isset($first['answer'])) {
        return "Missing question/answer keys";
    }
    return true;
});

echo "\n";

// ==========================================
// Test Suite 5: Cache Service
// ==========================================
echo "Test Suite 5: Cache Service\n";
echo "-------------------------------------------\n";

require_once __DIR__ . '/../app/Services/CacheService.php';

runTest("CacheService instantiates", function() {
    $cache = new CacheService();
    return $cache instanceof CacheService;
});

runTest("CacheService::set() and get() work", function() {
    $cache = new CacheService();
    $key = 'test_key_' . time();
    $value = ['test' => 'data', 'number' => 123];

    $cache->set($key, $value, 60);
    $retrieved = $cache->get($key);

    $cache->delete($key); // Cleanup

    return $retrieved == $value ? true : "Retrieved data doesn't match";
});

runTest("CacheService::has() works", function() {
    $cache = new CacheService();
    $key = 'test_exists_' . time();

    if ($cache->has($key)) return "Key exists before set";

    $cache->set($key, 'test', 60);
    $exists = $cache->has($key);

    $cache->delete($key); // Cleanup

    return $exists ? true : "Key doesn't exist after set";
});

runTest("CacheService::remember() works", function() {
    $cache = new CacheService();
    $key = 'test_remember_' . time();
    $callCount = 0;

    $value1 = $cache->remember($key, function() use (&$callCount) {
        $callCount++;
        return 'computed_value';
    }, 60);

    $value2 = $cache->remember($key, function() use (&$callCount) {
        $callCount++;
        return 'computed_value';
    }, 60);

    $cache->delete($key); // Cleanup

    if ($callCount != 1) return "Callback called $callCount times, expected 1";
    if ($value1 != $value2) return "Values don't match";
    return true;
});

runTest("Cache directory exists and is writable", function() {
    $cacheDir = __DIR__ . '/../storage/cache';
    if (!is_dir($cacheDir)) return "Cache directory doesn't exist";
    if (!is_writable($cacheDir)) return "Cache directory not writable";
    return true;
});

echo "\n";

// ==========================================
// Test Suite 6: Integration
// ==========================================
echo "Test Suite 6: Integration (Full Stack)\n";
echo "-------------------------------------------\n";

require_once __DIR__ . '/../app/Models/HolidayProgramModel.php';

runTest("HolidayProgramModel loads configuration from database", function() use ($conn) {
    $model = new HolidayProgramModel($conn);
    $program = $model->getProgramById(1);

    if (!$program) return "No program found";
    if (!isset($program['project_requirements'])) return "Missing requirements";
    if (!isset($program['evaluation_criteria'])) return "Missing criteria";
    if (!isset($program['what_to_bring'])) return "Missing what_to_bring";
    if (!isset($program['faq'])) return "Missing faq";

    return true;
});

runTest("Configuration data matches database", function() use ($conn) {
    $model = new HolidayProgramModel($conn);
    $program = $model->getProgramById(1);

    // Count items
    $reqCount = count($program['project_requirements']);
    $critCount = count($program['evaluation_criteria']);
    $itemCount = count($program['what_to_bring']);
    $faqCount = count($program['faq']);

    if ($reqCount != 4) return "Requirements: expected 4, got $reqCount";
    if ($critCount != 5) return "Criteria: expected 5, got $critCount";
    if ($itemCount != 4) return "Items: expected 4, got $itemCount";
    if ($faqCount != 25) return "FAQs: expected 25, got $faqCount";

    return true;
});

runTest("Controller uses repository-driven model", function() use ($conn) {
    require_once __DIR__ . '/../app/Controllers/HolidayProgramController.php';

    $controller = new HolidayProgramController($conn);
    $data = $controller->getProgram(1);

    if (!isset($data['program'])) return "No program data";
    $program = $data['program'];

    if (!isset($program['faq'])) return "No FAQ data from controller";
    if (count($program['faq']) != 25) return "FAQ count mismatch";

    return true;
});

echo "\n";

// ==========================================
// Test Summary
// ==========================================
echo "==========================================\n";
echo "Test Summary\n";
echo "==========================================\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✓\n";
echo "Failed: $failedTests ✗\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

if ($failedTests == 0) {
    echo "🎉 All tests passed!\n\n";
    echo "Week 2 Deliverables Verified:\n";
    echo "✓ Database migrations applied\n";
    echo "✓ 49 configuration records seeded\n";
    echo "✓ 3 models created and functional\n";
    echo "✓ 3 repositories created and functional\n";
    echo "✓ Cache service working correctly\n";
    echo "✓ Full stack integration validated\n";
    echo "✓ Zero hardcoded configuration data\n\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please review errors above.\n\n";
    exit(1);
}
?>
