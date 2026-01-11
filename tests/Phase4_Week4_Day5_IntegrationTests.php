<?php
/**
 * Phase 4 Week 4 Day 5 - Comprehensive Integration Tests
 *
 * Tests all controller migrations with file-level validation:
 * - Week 3 Day 2: CourseController, LessonController, UserController, AttendanceRegisterController (4)
 * - Week 3 Day 3: 5 Holiday Program controllers
 * - Week 3 Day 4: PerformanceDashboardController (1)
 * - Week 4 Day 2: AdminLessonController (1)
 *
 * Total: 11 controllers tested
 *
 * @package Tests
 * @since Phase 4 Week 4 Day 5
 */

require_once __DIR__ . '/../server.php';

class Phase4Week4Day5IntegrationTests
{
    private $db;
    private $testResults = [];
    private $testCount = 0;
    private $passCount = 0;
    private $failCount = 0;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Run all integration tests
     */
    public function runAllTests()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Phase 4 Week 4 Day 5 - Integration Tests\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Testing 11 controllers with file-level validation\n\n";

        // Week 3 Day 2 Tests
        echo "📦 Week 3 Day 2 Controllers (4 controllers)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testController('CourseController', __DIR__ . '/../app/Controllers/CourseController.php', true);
        $this->testController('LessonController', __DIR__ . '/../app/Controllers/LessonController.php', true);
        $this->testController('UserController', __DIR__ . '/../app/Controllers/UserController.php', true);
        $this->testController('AttendanceRegisterController', __DIR__ . '/../app/Controllers/AttendanceRegisterController.php', false);
        echo "\n";

        // Week 3 Day 3 Tests
        echo "📦 Week 3 Day 3 Controllers (5 Holiday Program controllers)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testController('HolidayProgramController', __DIR__ . '/../app/Controllers/HolidayProgramController.php', false);
        $this->testController('HolidayProgramAdminController', __DIR__ . '/../app/Controllers/HolidayProgramAdminController.php', false);
        $this->testController('HolidayProgramCreationController', __DIR__ . '/../app/Controllers/HolidayProgramCreationController.php', false);
        $this->testController('HolidayProgramEmailController', __DIR__ . '/../app/Controllers/HolidayProgramEmailController.php', false);
        $this->testController('HolidayProgramProfileController', __DIR__ . '/../app/Controllers/HolidayProgramProfileController.php', false);
        echo "\n";

        // Week 3 Day 4 Tests
        echo "📦 Week 3 Day 4 Controller (1 controller)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testController('PerformanceDashboardController', __DIR__ . '/../app/Controllers/PerformanceDashboardController.php', false);
        echo "\n";

        // Week 4 Day 2 Tests
        echo "📦 Week 4 Day 2 Controller (1 controller)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testAdminLessonController();
        echo "\n";

        // Summary
        $this->printSummary();

        return $this->getResults();
    }

    /**
     * Test a controller file
     *
     * @param string $name Controller name
     * @param string $file File path
     * @param bool $isDeprecated Whether controller should have deprecation notice
     */
    private function testController($name, $file, $isDeprecated = false)
    {
        $this->test("{$name} file exists", function() use ($file) {
            return file_exists($file);
        });

        $this->test("{$name} syntax valid", function() use ($file) {
            if (!file_exists($file)) return false;
            return $this->validateSyntax($file);
        });

        $this->test("{$name} extends BaseController", function() use ($file) {
            if (!file_exists($file)) return false;
            return $this->fileExtendsBaseController($file);
        });

        if ($isDeprecated) {
            $this->test("{$name} has deprecation notice", function() use ($file) {
                if (!file_exists($file)) return false;
                $content = file_get_contents($file);
                return strpos($content, 'DEPRECATED') !== false;
            });
        }

        $this->test("{$name} has RBAC protection", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'requireRole') !== false || strpos($content, 'DEPRECATED') !== false;
        });

        $this->test("{$name} has backup file", function() use ($file) {
            return file_exists($file . '.backup') || file_exists($file . '.deprecated');
        });
    }

    /**
     * Test AdminLessonController (special case - namespaced)
     */
    private function testAdminLessonController()
    {
        $file = __DIR__ . '/../app/Controllers/Admin/AdminLessonController.php';

        $this->test("AdminLessonController file exists", function() use ($file) {
            return file_exists($file);
        });

        $this->test("AdminLessonController syntax valid", function() use ($file) {
            if (!file_exists($file)) return false;
            return $this->validateSyntax($file);
        });

        $this->test("AdminLessonController extends BaseController", function() use ($file) {
            if (!file_exists($file)) return false;
            return $this->fileExtendsBaseController($file);
        });

        $this->test("AdminLessonController has namespace", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'namespace App\\Controllers\\Admin') !== false;
        });

        $this->test("AdminLessonController has RBAC protection", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'requireRole') !== false;
        });

        $this->test("AdminLessonController has CSRF protection", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'validateCSRF') !== false;
        });

        $this->test("AdminLessonController has activity logging", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'logAction') !== false;
        });

        $this->test("AdminLessonController has backup file", function() use ($file) {
            return file_exists($file . '.backup');
        });
    }

    /**
     * Validate PHP file syntax
     */
    private function validateSyntax($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
        return $return === 0;
    }

    /**
     * Check if file extends BaseController
     */
    private function fileExtendsBaseController($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        return strpos($content, 'extends BaseController') !== false ||
               strpos($content, 'extends \BaseController') !== false;
    }

    /**
     * Helper method to run a test
     */
    private function test($description, $callback)
    {
        $this->testCount++;

        try {
            $result = $callback();

            if ($result) {
                $this->passCount++;
                echo "  ✅ PASS: {$description}\n";
                $this->testResults[] = [
                    'status' => 'pass',
                    'description' => $description
                ];
            } else {
                $this->failCount++;
                echo "  ❌ FAIL: {$description}\n";
                $this->testResults[] = [
                    'status' => 'fail',
                    'description' => $description
                ];
            }
        } catch (\Exception $e) {
            $this->failCount++;
            echo "  ❌ ERROR: {$description}\n";
            echo "     └─ {$e->getMessage()}\n";
            $this->testResults[] = [
                'status' => 'error',
                'description' => $description,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Print test summary
     */
    private function printSummary()
    {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Integration Test Summary\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Total Tests:  {$this->testCount}\n";
        echo "✅ Passed:     {$this->passCount}\n";
        echo "❌ Failed:     {$this->failCount}\n";

        if ($this->testCount > 0) {
            $successRate = round(($this->passCount / $this->testCount) * 100, 2);
            echo "\nSuccess Rate: {$successRate}%\n\n";
        }

        if ($this->failCount > 0) {
            echo "Failed Tests:\n";
            echo "─────────────────────────────────────────────────────────────\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] !== 'pass') {
                    echo "  ❌ {$result['description']}\n";
                    if (isset($result['error'])) {
                        echo "     └─ {$result['error']}\n";
                    }
                }
            }
            echo "\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n";

        if ($this->failCount === 0) {
            echo "🎉 All integration tests passed!\n";
        } else {
            echo "⚠️  Some tests failed. Review and fix issues.\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Get test results as array
     */
    public function getResults()
    {
        return [
            'total' => $this->testCount,
            'passed' => $this->passCount,
            'failed' => $this->failCount,
            'success_rate' => $this->testCount > 0 ? round(($this->passCount / $this->testCount) * 100, 2) : 0,
            'tests' => $this->testResults,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'Phase4_Week4_Day5_IntegrationTests.php') {
    echo "Initializing integration tests...\n\n";

    try {
        $tester = new Phase4Week4Day5IntegrationTests($mysqli);
        $results = $tester->runAllTests();

        // Save results to JSON
        file_put_contents(
            __DIR__ . '/phase4_week4_day5_integration_results.json',
            json_encode($results, JSON_PRETTY_PRINT)
        );

        echo "Integration test results saved to: tests/phase4_week4_day5_integration_results.json\n\n";

        // Exit with appropriate code
        exit($results['failed'] > 0 ? 1 : 0);

    } catch (\Exception $e) {
        echo "❌ ERROR: Failed to run integration tests\n";
        echo "   {$e->getMessage()}\n\n";
        exit(1);
    }
}
