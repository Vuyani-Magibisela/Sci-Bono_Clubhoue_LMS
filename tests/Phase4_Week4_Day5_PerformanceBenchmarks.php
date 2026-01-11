<?php
/**
 * Phase 4 Week 4 Day 5 - Performance Benchmarks
 *
 * Benchmarks all migrated controllers for:
 * - File load time
 * - Syntax validation time
 * - File size
 * - Code complexity metrics
 *
 * Controllers tested (11 total):
 * - Week 3 Day 2: CourseController, LessonController, UserController, AttendanceRegisterController
 * - Week 3 Day 3: 5 Holiday Program controllers
 * - Week 3 Day 4: PerformanceDashboardController
 * - Week 4 Day 2: AdminLessonController
 *
 * @package Tests
 * @since Phase 4 Week 4 Day 5
 */

require_once __DIR__ . '/PerformanceBenchmark.php';
require_once __DIR__ . '/../server.php';

use Tests\PerformanceBenchmark;

class Phase4Week4Day5PerformanceBenchmarks
{
    private $benchmark;
    private $controllers = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->benchmark = new PerformanceBenchmark();
        $this->initializeControllers();
    }

    /**
     * Initialize controller list
     */
    private function initializeControllers()
    {
        $this->controllers = [
            // Week 3 Day 2
            'CourseController' => __DIR__ . '/../app/Controllers/CourseController.php',
            'LessonController' => __DIR__ . '/../app/Controllers/LessonController.php',
            'UserController' => __DIR__ . '/../app/Controllers/UserController.php',
            'AttendanceRegisterController' => __DIR__ . '/../app/Controllers/AttendanceRegisterController.php',

            // Week 3 Day 3
            'HolidayProgramController' => __DIR__ . '/../app/Controllers/HolidayProgramController.php',
            'HolidayProgramAdminController' => __DIR__ . '/../app/Controllers/HolidayProgramAdminController.php',
            'HolidayProgramCreationController' => __DIR__ . '/../app/Controllers/HolidayProgramCreationController.php',
            'HolidayProgramEmailController' => __DIR__ . '/../app/Controllers/HolidayProgramEmailController.php',
            'HolidayProgramProfileController' => __DIR__ . '/../app/Controllers/HolidayProgramProfileController.php',

            // Week 3 Day 4
            'PerformanceDashboardController' => __DIR__ . '/../app/Controllers/PerformanceDashboardController.php',

            // Week 4 Day 2
            'AdminLessonController' => __DIR__ . '/../app/Controllers/Admin/AdminLessonController.php'
        ];
    }

    /**
     * Run all benchmarks
     */
    public function runAllBenchmarks()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Phase 4 Week 4 Day 5 - Performance Benchmarks\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Benchmarking 11 controllers...\n\n";

        foreach ($this->controllers as $name => $file) {
            $this->benchmarkController($name, $file);
        }

        // Print report
        $this->benchmark->printReport();

        return $this->benchmark->getResults();
    }

    /**
     * Benchmark a single controller
     */
    private function benchmarkController($name, $file)
    {
        echo "Benchmarking {$name}...\n";

        // Benchmark file read
        $this->benchmark->benchmark("{$name}::file_read", function() use ($file) {
            if (!file_exists($file)) {
                throw new \Exception("File not found");
            }
            $content = file_get_contents($file);
            return strlen($content);
        });

        // Benchmark syntax validation
        $this->benchmark->benchmark("{$name}::syntax_check", function() use ($file) {
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
            if ($return !== 0) {
                throw new \Exception("Syntax error");
            }
            return true;
        });

        // Calculate file metrics
        if (file_exists($file)) {
            $this->calculateMetrics($name, $file);
        }

        echo "  ✅ {$name} benchmarked\n";
    }

    /**
     * Calculate code metrics
     */
    private function calculateMetrics($name, $file)
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        $metrics = [
            'file_size_bytes' => filesize($file),
            'file_size_kb' => round(filesize($file) / 1024, 2),
            'total_lines' => count($lines),
            'code_lines' => $this->countCodeLines($lines),
            'comment_lines' => $this->countCommentLines($lines),
            'blank_lines' => $this->countBlankLines($lines),
            'methods' => $this->countMethods($content),
            'classes' => substr_count($content, 'class ')
        ];

        // Store metrics as benchmark result
        $this->benchmark->benchmark("{$name}::metrics", function() use ($metrics) {
            return $metrics;
        }, $metrics);
    }

    /**
     * Count code lines (non-comment, non-blank)
     */
    private function countCodeLines($lines)
    {
        $count = 0;
        $inBlockComment = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Check for block comment start
            if (strpos($trimmed, '/*') !== false) {
                $inBlockComment = true;
            }

            // Skip blank lines, single-line comments, and block comments
            if (!empty($trimmed) &&
                !$inBlockComment &&
                strpos($trimmed, '//') !== 0 &&
                strpos($trimmed, '#') !== 0 &&
                strpos($trimmed, '*') !== 0) {
                $count++;
            }

            // Check for block comment end
            if (strpos($trimmed, '*/') !== false) {
                $inBlockComment = false;
            }
        }

        return $count;
    }

    /**
     * Count comment lines
     */
    private function countCommentLines($lines)
    {
        $count = 0;
        $inBlockComment = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Block comment start
            if (strpos($trimmed, '/*') !== false) {
                $inBlockComment = true;
                $count++;
                continue;
            }

            // Inside block comment
            if ($inBlockComment) {
                $count++;
                if (strpos($trimmed, '*/') !== false) {
                    $inBlockComment = false;
                }
                continue;
            }

            // Single-line comments
            if (strpos($trimmed, '//') === 0 || strpos($trimmed, '#') === 0 || strpos($trimmed, '*') === 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count blank lines
     */
    private function countBlankLines($lines)
    {
        $count = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count methods
     */
    private function countMethods($content)
    {
        // Count public, protected, and private functions
        return preg_match_all('/\b(public|protected|private)\s+function\s+\w+/', $content, $matches);
    }

    /**
     * Export results
     */
    public function exportResults($directory = __DIR__)
    {
        $jsonFile = $directory . '/phase4_week4_day5_benchmark_results.json';
        $csvFile = $directory . '/phase4_week4_day5_benchmark_results.csv';

        $this->benchmark->exportToJson($jsonFile);
        $this->benchmark->exportToCsv($csvFile);

        echo "\nBenchmark results exported:\n";
        echo "  - JSON: {$jsonFile}\n";
        echo "  - CSV: {$csvFile}\n\n";

        return [
            'json' => $jsonFile,
            'csv' => $csvFile
        ];
    }
}

// Run benchmarks if executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === 'Phase4_Week4_Day5_PerformanceBenchmarks.php') {
    echo "Initializing performance benchmarks...\n\n";

    try {
        $benchmarks = new Phase4Week4Day5PerformanceBenchmarks();
        $results = $benchmarks->runAllBenchmarks();

        // Export results
        $benchmarks->exportResults(__DIR__);

        echo "Performance benchmarking complete!\n\n";
        exit(0);

    } catch (\Exception $e) {
        echo "❌ ERROR: Failed to run performance benchmarks\n";
        echo "   {$e->getMessage()}\n\n";
        exit(1);
    }
}
