<?php
/**
 * Phase 4 Week 3 Day 5 - Comprehensive Testing Suite
 *
 * Tests all controller migrations from Days 2, 3, and 4:
 * - Day 2: Priority 1 controllers (4 controllers)
 * - Day 3: Priority 2 controllers (5 Holiday Program controllers)
 * - Day 4: Priority 3 controller (1 PerformanceDashboard) + 4 deprecated files
 *
 * @package Tests
 * @since Phase 4 Week 3 Day 5
 */

require_once __DIR__ . '/../server.php';

class Phase4Week3Day5Tests
{
    private $conn;
    private $testResults = [];
    private $testCount = 0;
    private $passCount = 0;
    private $failCount = 0;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Run all test suites
     */
    public function runAllTests()
    {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Phase 4 Week 3 Day 5 - Comprehensive Controller Testing\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        // Day 2 Tests - Priority 1 Controllers
        echo "📦 Day 2: Priority 1 Controller Tests\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testDay2Controllers();
        echo "\n";

        // Day 3 Tests - Priority 2 Controllers (Holiday Program)
        echo "📦 Day 3: Priority 2 Controller Tests (Holiday Program)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testDay3Controllers();
        echo "\n";

        // Day 4 Tests - Priority 3 Controller
        echo "📦 Day 4: Priority 3 Controller Tests (Performance Dashboard)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testDay4Controllers();
        echo "\n";

        // Deprecated Files Tests
        echo "📦 Deprecated File Functionality Tests\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testDeprecatedFiles();
        echo "\n";

        // Security Tests
        echo "📦 Security & Integration Tests\n";
        echo "─────────────────────────────────────────────────────────────\n";
        $this->testSecurity();
        echo "\n";

        // Summary
        $this->printSummary();
    }

    /**
     * Test Day 2 Priority 1 Controllers
     */
    private function testDay2Controllers()
    {
        // Test CourseController wrapper
        $this->test("CourseController wrapper exists", function() {
            return class_exists('CourseController');
        });

        $this->test("CourseController extends BaseController", function() {
            if (!class_exists('CourseController')) return false;
            $reflection = new ReflectionClass('CourseController');
            return $reflection->getParentClass() &&
                   $reflection->getParentClass()->getName() === 'BaseController';
        });

        // Test LessonController wrapper
        $this->test("LessonController wrapper exists", function() {
            return class_exists('LessonController');
        });

        $this->test("LessonController extends BaseController", function() {
            if (!class_exists('LessonController')) return false;
            $reflection = new ReflectionClass('LessonController');
            return $reflection->getParentClass() &&
                   $reflection->getParentClass()->getName() === 'BaseController';
        });

        // Test UserController wrapper
        $this->test("UserController wrapper exists", function() {
            return class_exists('UserController');
        });

        $this->test("UserController extends BaseController", function() {
            if (!class_exists('UserController')) return false;
            $reflection = new ReflectionClass('UserController');
            return $reflection->getParentClass() &&
                   $reflection->getParentClass()->getName() === 'BaseController';
        });

        // Test AttendanceRegisterController
        $this->test("AttendanceRegisterController exists", function() {
            return file_exists(__DIR__ . '/../app/Controllers/AttendanceRegisterController.php');
        });

        $this->test("AttendanceRegisterController syntax valid", function() {
            $file = __DIR__ . '/../app/Controllers/AttendanceRegisterController.php';
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
            return $return === 0;
        });

        // Test backup files exist
        $this->test("CourseController backup exists", function() {
            return file_exists(__DIR__ . '/../app/Controllers/CourseController.php.deprecated');
        });

        $this->test("AttendanceRegisterController backup exists", function() {
            return file_exists(__DIR__ . '/../app/Controllers/AttendanceRegisterController.php.backup');
        });
    }

    /**
     * Test Day 3 Priority 2 Controllers (Holiday Program)
     */
    private function testDay3Controllers()
    {
        $holidayControllers = [
            'HolidayProgramController',
            'HolidayProgramEmailController',
            'HolidayProgramAdminController',
            'HolidayProgramProfileController',
            'HolidayProgramCreationController'
        ];

        foreach ($holidayControllers as $controller) {
            $file = __DIR__ . "/../app/Controllers/{$controller}.php";

            $this->test("{$controller} file exists", function() use ($file) {
                return file_exists($file);
            });

            $this->test("{$controller} syntax valid", function() use ($file) {
                if (!file_exists($file)) return false;
                $output = [];
                $return = 0;
                exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
                return $return === 0;
            });

            $this->test("{$controller} extends BaseController", function() use ($file, $controller) {
                if (!file_exists($file)) return false;
                $content = file_get_contents($file);
                return strpos($content, 'extends BaseController') !== false ||
                       strpos($content, 'extends \BaseController') !== false;
            });

            $this->test("{$controller} backup exists", function() use ($controller) {
                return file_exists(__DIR__ . "/../app/Controllers/{$controller}.php.backup");
            });
        }
    }

    /**
     * Test Day 4 Priority 3 Controller (Performance Dashboard)
     */
    private function testDay4Controllers()
    {
        $file = __DIR__ . '/../app/Controllers/PerformanceDashboardController.php';

        $this->test("PerformanceDashboardController file exists", function() use ($file) {
            return file_exists($file);
        });

        $this->test("PerformanceDashboardController syntax valid", function() use ($file) {
            if (!file_exists($file)) return false;
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
            return $return === 0;
        });

        $this->test("PerformanceDashboardController extends BaseController", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'extends \BaseController') !== false ||
                   strpos($content, 'extends BaseController') !== false;
        });

        $this->test("PerformanceDashboardController backup exists", function() {
            return file_exists(__DIR__ . '/../app/Controllers/PerformanceDashboardController.php.backup');
        });

        $this->test("PerformanceDashboardController has role protection", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'requireRole') !== false;
        });

        $this->test("PerformanceDashboardController has CSRF validation", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'validateCSRF') !== false;
        });

        $this->test("PerformanceDashboardController has activity logging", function() use ($file) {
            if (!file_exists($file)) return false;
            $content = file_get_contents($file);
            return strpos($content, 'logAction') !== false;
        });
    }

    /**
     * Test deprecated file functionality
     */
    private function testDeprecatedFiles()
    {
        $deprecatedFiles = [
            'addPrograms.php',
            'holidayProgramLoginC.php',
            'send-profile-email.php',
            'sessionTimer.php'
        ];

        foreach ($deprecatedFiles as $file) {
            $path = __DIR__ . "/../app/Controllers/{$file}";

            $this->test("{$file} exists", function() use ($path) {
                return file_exists($path);
            });

            $this->test("{$file} syntax valid", function() use ($path) {
                if (!file_exists($path)) return false;
                $output = [];
                $return = 0;
                exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return);
                return $return === 0;
            });

            $this->test("{$file} has deprecation notice", function() use ($path) {
                if (!file_exists($path)) return false;
                $content = file_get_contents($path);
                return strpos($content, 'DEPRECATED') !== false;
            });

            $this->test("{$file} has error_log deprecation", function() use ($path) {
                if (!file_exists($path)) return false;
                $content = file_get_contents($path);
                return strpos($content, 'error_log') !== false &&
                       strpos($content, '[DEPRECATED]') !== false;
            });

            $this->test("{$file} has migration path documented", function() use ($path) {
                if (!file_exists($path)) return false;
                $content = file_get_contents($path);
                return strpos($content, 'Migration Path') !== false;
            });
        }
    }

    /**
     * Test security features
     */
    private function testSecurity()
    {
        // Test BaseController exists and has required methods
        $this->test("BaseController exists", function() {
            return file_exists(__DIR__ . '/../app/Controllers/BaseController.php');
        });

        $baseControllerFile = __DIR__ . '/../app/Controllers/BaseController.php';
        if (file_exists($baseControllerFile)) {
            $content = file_get_contents($baseControllerFile);

            $this->test("BaseController has requireRole method", function() use ($content) {
                return strpos($content, 'function requireRole') !== false ||
                       strpos($content, 'protected function requireRole') !== false ||
                       strpos($content, 'public function requireRole') !== false;
            });

            $this->test("BaseController has validateCSRF method", function() use ($content) {
                return strpos($content, 'function validateCSRF') !== false ||
                       strpos($content, 'protected function validateCSRF') !== false ||
                       strpos($content, 'public function validateCSRF') !== false;
            });

            $this->test("BaseController has logAction method", function() use ($content) {
                return strpos($content, 'function logAction') !== false ||
                       strpos($content, 'protected function logAction') !== false ||
                       strpos($content, 'public function logAction') !== false;
            });

            $this->test("BaseController has jsonResponse method", function() use ($content) {
                return strpos($content, 'function jsonResponse') !== false ||
                       strpos($content, 'protected function jsonResponse') !== false ||
                       strpos($content, 'public function jsonResponse') !== false;
            });

            $this->test("BaseController has input method", function() use ($content) {
                return strpos($content, 'function input') !== false ||
                       strpos($content, 'protected function input') !== false ||
                       strpos($content, 'public function input') !== false;
            });

            $this->test("BaseController has view method", function() use ($content) {
                return strpos($content, 'function view') !== false ||
                       strpos($content, 'protected function view') !== false ||
                       strpos($content, 'public function view') !== false;
            });
        }

        // Test CSRF class exists
        $this->test("CSRF class exists", function() {
            return file_exists(__DIR__ . '/../core/CSRF.php');
        });

        // Test Logger class exists
        $this->test("Logger class exists", function() {
            return file_exists(__DIR__ . '/../app/Utils/Logger.php') ||
                   file_exists(__DIR__ . '/../src/Utils/Logger.php');
        });
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
        } catch (Exception $e) {
            $this->failCount++;
            echo "  ❌ ERROR: {$description} - {$e->getMessage()}\n";
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
        echo "  Test Summary\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Total Tests:  {$this->testCount}\n";
        echo "✅ Passed:     {$this->passCount}\n";
        echo "❌ Failed:     {$this->failCount}\n";
        echo "\nSuccess Rate: " . round(($this->passCount / $this->testCount) * 100, 2) . "%\n\n";

        if ($this->failCount > 0) {
            echo "Failed Tests:\n";
            echo "─────────────────────────────────────────────────────────────\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] !== 'pass') {
                    echo "  ❌ {$result['description']}\n";
                    if (isset($result['error'])) {
                        echo "     Error: {$result['error']}\n";
                    }
                }
            }
            echo "\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n";

        if ($this->failCount === 0) {
            echo "🎉 All tests passed! Ready for production.\n";
        } else {
            echo "⚠️  Some tests failed. Please review and fix issues.\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n";
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
            'success_rate' => round(($this->passCount / $this->testCount) * 100, 2),
            'tests' => $this->testResults
        ];
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'Phase4_Week3_Day5_Tests.php') {
    $tester = new Phase4Week3Day5Tests($mysqli);
    $tester->runAllTests();

    // Save results to JSON for reporting
    $results = $tester->getResults();
    file_put_contents(
        __DIR__ . '/phase4_week3_day5_test_results.json',
        json_encode($results, JSON_PRETTY_PRINT)
    );

    echo "\nTest results saved to: tests/phase4_week3_day5_test_results.json\n\n";

    // Exit with appropriate code
    exit($results['failed'] > 0 ? 1 : 0);
}
