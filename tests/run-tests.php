<?php
/**
 * Automated Test Runner Script
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * This script provides automated test execution with various options
 * for local development and CI/CD environments
 */

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Set error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to project root directory
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Include required files
require_once __DIR__ . '/TestRunner.php';

class AutomatedTestRunner
{
    private $options = [];
    private $testSuites = [
        'all' => 'Run all tests',
        'models' => 'Run model tests only',
        'api' => 'Run API tests only',
        'unit' => 'Run unit tests only',
        'integration' => 'Run integration tests only'
    ];
    
    private $exitCode = 0;
    
    public function __construct($argv)
    {
        $this->parseArguments($argv);
        $this->validateEnvironment();
    }
    
    /**
     * Parse command line arguments
     */
    private function parseArguments($argv)
    {
        $this->options = [
            'verbose' => false,
            'stop_on_failure' => false,
            'coverage' => false,
            'suite' => 'all',
            'filter' => null,
            'output' => null,
            'parallel' => false,
            'config' => null,
            'help' => false
        ];
        
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            switch ($arg) {
                case '--help':
                case '-h':
                    $this->options['help'] = true;
                    break;
                    
                case '--verbose':
                case '-v':
                    $this->options['verbose'] = true;
                    break;
                    
                case '--stop-on-failure':
                case '-s':
                    $this->options['stop_on_failure'] = true;
                    break;
                    
                case '--coverage':
                case '-c':
                    $this->options['coverage'] = true;
                    break;
                    
                case '--parallel':
                case '-p':
                    $this->options['parallel'] = true;
                    break;
                    
                default:
                    if (strpos($arg, '--suite=') === 0) {
                        $this->options['suite'] = substr($arg, 8);
                    } elseif (strpos($arg, '--filter=') === 0) {
                        $this->options['filter'] = substr($arg, 9);
                    } elseif (strpos($arg, '--output=') === 0) {
                        $this->options['output'] = substr($arg, 9);
                    } elseif (strpos($arg, '--config=') === 0) {
                        $this->options['config'] = substr($arg, 9);
                    }
                    break;
            }
        }
    }
    
    /**
     * Validate test environment
     */
    private function validateEnvironment()
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->error("PHP 7.4 or higher is required. Current version: " . PHP_VERSION);
        }
        
        // Check required extensions
        $requiredExtensions = ['mysqli', 'json', 'mbstring'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->error("Required PHP extension '{$extension}' is not loaded.");
            }
        }
        
        // Check test database configuration
        $this->validateDatabaseConfig();
        
        // Check write permissions for test outputs
        if (!is_writable(__DIR__)) {
            $this->warning("Test directory is not writable. Output files may not be generated.");
        }
    }
    
    /**
     * Validate database configuration for tests
     */
    private function validateDatabaseConfig()
    {
        $requiredEnvVars = ['TEST_DB_HOST', 'TEST_DB_USERNAME', 'TEST_DB_NAME'];
        
        foreach ($requiredEnvVars as $envVar) {
            if (!getenv($envVar) && !isset($_ENV[$envVar])) {
                $this->warning("Environment variable '{$envVar}' is not set. Using defaults.");
            }
        }
        
        // Test database connection
        try {
            $host = getenv('TEST_DB_HOST') ?: 'localhost';
            $username = getenv('TEST_DB_USERNAME') ?: 'root';
            $password = getenv('TEST_DB_PASSWORD') ?: '';
            
            $connection = new mysqli($host, $username, $password);
            if ($connection->connect_error) {
                $this->error("Cannot connect to test database: " . $connection->connect_error);
            }
            $connection->close();
        } catch (Exception $e) {
            $this->error("Database connection test failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run the test suite
     */
    public function run()
    {
        if ($this->options['help']) {
            $this->showHelp();
            return 0;
        }
        
        $this->showHeader();
        
        // Load configuration if specified
        if ($this->options['config']) {
            $this->loadConfiguration($this->options['config']);
        }
        
        // Set up test environment
        $this->setupTestEnvironment();
        
        // Start test execution
        $startTime = microtime(true);
        
        if ($this->options['coverage']) {
            $this->startCodeCoverage();
        }
        
        // Run tests based on suite selection
        $results = $this->runTestSuite($this->options['suite']);
        
        if ($this->options['coverage']) {
            $this->generateCoverageReport();
        }
        
        $duration = microtime(true) - $startTime;
        
        // Generate reports
        $this->generateReports($results, $duration);
        
        // Clean up test environment
        $this->cleanupTestEnvironment();
        
        return $this->exitCode;
    }
    
    /**
     * Run specific test suite
     */
    private function runTestSuite($suite)
    {
        $testRunnerOptions = [
            'verbose' => $this->options['verbose'],
            'stop_on_failure' => $this->options['stop_on_failure'],
            'filter' => $this->options['filter']
        ];
        
        switch ($suite) {
            case 'models':
                $testRunnerOptions['filter'] = 'Models';
                break;
            case 'api':
                $testRunnerOptions['filter'] = 'API';
                break;
            case 'unit':
                $testRunnerOptions['filter'] = 'Unit';
                break;
            case 'integration':
                $testRunnerOptions['filter'] = 'Integration';
                break;
            case 'all':
            default:
                // Run all tests
                break;
        }
        
        $runner = new Tests\TestRunner($testRunnerOptions);
        $success = $runner->run();
        
        if (!$success) {
            $this->exitCode = 1;
        }
        
        return $runner->getResults();
    }
    
    /**
     * Set up test environment
     */
    private function setupTestEnvironment()
    {
        echo "Setting up test environment...\n";
        
        // Set environment variables
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');
        
        // Create test directories if they don't exist
        $directories = [
            __DIR__ . '/reports',
            __DIR__ . '/coverage',
            __DIR__ . '/logs'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        echo "Test environment ready.\n";
    }
    
    /**
     * Clean up test environment
     */
    private function cleanupTestEnvironment()
    {
        echo "\nCleaning up test environment...\n";
        
        // Clean up temporary test files
        $tempFiles = glob(__DIR__ . '/temp_*');
        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Clean up test database (if configured)
        $this->cleanupTestDatabase();
        
        echo "Cleanup complete.\n";
    }
    
    /**
     * Clean up test database
     */
    private function cleanupTestDatabase()
    {
        try {
            $host = getenv('TEST_DB_HOST') ?: 'localhost';
            $username = getenv('TEST_DB_USERNAME') ?: 'root';
            $password = getenv('TEST_DB_PASSWORD') ?: '';
            $dbname = getenv('TEST_DB_NAME') ?: 'sci_bono_lms_test';
            
            $connection = new mysqli($host, $username, $password);
            if (!$connection->connect_error) {
                $connection->query("DROP DATABASE IF EXISTS `{$dbname}_temp`");
                $connection->close();
            }
        } catch (Exception $e) {
            $this->warning("Could not clean up test database: " . $e->getMessage());
        }
    }
    
    /**
     * Start code coverage collection
     */
    private function startCodeCoverage()
    {
        if (!extension_loaded('xdebug')) {
            $this->warning("Xdebug extension not found. Code coverage disabled.");
            $this->options['coverage'] = false;
            return;
        }
        
        echo "Starting code coverage collection...\n";
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }
    
    /**
     * Generate code coverage report
     */
    private function generateCoverageReport()
    {
        if (!$this->options['coverage']) {
            return;
        }
        
        echo "Generating code coverage report...\n";
        
        $coverage = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();
        
        // Generate HTML coverage report
        $this->generateHtmlCoverageReport($coverage);
        
        // Generate text coverage summary
        $this->generateTextCoverageSummary($coverage);
    }
    
    /**
     * Generate HTML coverage report
     */
    private function generateHtmlCoverageReport($coverage)
    {
        $coverageDir = __DIR__ . '/coverage';
        $htmlFile = $coverageDir . '/index.html';
        
        $html = $this->buildCoverageHtml($coverage);
        file_put_contents($htmlFile, $html);
        
        echo "HTML coverage report generated: {$htmlFile}\n";
    }
    
    /**
     * Build HTML coverage report
     */
    private function buildCoverageHtml($coverage)
    {
        $totalLines = 0;
        $coveredLines = 0;
        
        $fileReports = [];
        
        foreach ($coverage as $file => $lines) {
            if (strpos($file, '/app/') === false) {
                continue; // Only include application files
            }
            
            $fileTotal = count($lines);
            $fileCovered = count(array_filter($lines, function($line) { return $line > 0; }));
            
            $totalLines += $fileTotal;
            $coveredLines += $fileCovered;
            
            $percentage = $fileTotal > 0 ? round(($fileCovered / $fileTotal) * 100, 2) : 0;
            
            $fileReports[] = [
                'file' => $file,
                'covered' => $fileCovered,
                'total' => $fileTotal,
                'percentage' => $percentage
            ];
        }
        
        $overallPercentage = $totalLines > 0 ? round(($coveredLines / $totalLines) * 100, 2) : 0;
        
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<title>Code Coverage Report - Sci-Bono LMS</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{width:100%;border-collapse:collapse;}th,td{padding:8px;text-align:left;border-bottom:1px solid #ddd;}.high{color:green;}.medium{color:orange;}.low{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Code Coverage Report</h1>\n";
        $html .= "<p><strong>Overall Coverage: {$overallPercentage}%</strong> ({$coveredLines}/{$totalLines} lines)</p>\n";
        $html .= "<table>\n<thead>\n<tr><th>File</th><th>Coverage</th><th>Lines Covered</th></tr>\n</thead>\n<tbody>\n";
        
        foreach ($fileReports as $report) {
            $class = $report['percentage'] >= 80 ? 'high' : ($report['percentage'] >= 60 ? 'medium' : 'low');
            $html .= "<tr><td>" . basename($report['file']) . "</td><td class='{$class}'>{$report['percentage']}%</td><td>{$report['covered']}/{$report['total']}</td></tr>\n";
        }
        
        $html .= "</tbody>\n</table>\n</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate text coverage summary
     */
    private function generateTextCoverageSummary($coverage)
    {
        $summaryFile = __DIR__ . '/coverage/summary.txt';
        
        $totalLines = 0;
        $coveredLines = 0;
        
        foreach ($coverage as $file => $lines) {
            if (strpos($file, '/app/') !== false) {
                $totalLines += count($lines);
                $coveredLines += count(array_filter($lines, function($line) { return $line > 0; }));
            }
        }
        
        $percentage = $totalLines > 0 ? round(($coveredLines / $totalLines) * 100, 2) : 0;
        
        $summary = "Code Coverage Summary\n";
        $summary .= "====================\n";
        $summary .= "Overall Coverage: {$percentage}%\n";
        $summary .= "Lines Covered: {$coveredLines}/{$totalLines}\n";
        $summary .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        
        file_put_contents($summaryFile, $summary);
        
        echo "Coverage summary: {$summaryFile}\n";
    }
    
    /**
     * Generate test reports
     */
    private function generateReports($results, $duration)
    {
        if (!$this->options['output']) {
            return;
        }
        
        $reportFile = $this->options['output'];
        $format = pathinfo($reportFile, PATHINFO_EXTENSION);
        
        switch ($format) {
            case 'json':
                $this->generateJsonReport($results, $duration, $reportFile);
                break;
            case 'xml':
                $this->generateXmlReport($results, $duration, $reportFile);
                break;
            case 'html':
                $this->generateHtmlReport($results, $duration, $reportFile);
                break;
            default:
                $this->generateTextReport($results, $duration, $reportFile);
                break;
        }
    }
    
    /**
     * Generate JSON report
     */
    private function generateJsonReport($results, $duration, $file)
    {
        $report = [
            'timestamp' => date('c'),
            'duration' => $duration,
            'results' => $results,
            'environment' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_usage' => memory_get_peak_usage(true)
            ]
        ];
        
        file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT));
        echo "JSON report generated: {$file}\n";
    }
    
    /**
     * Load configuration file
     */
    private function loadConfiguration($configFile)
    {
        if (!file_exists($configFile)) {
            $this->error("Configuration file not found: {$configFile}");
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in configuration file: " . json_last_error_msg());
        }
        
        // Merge configuration with options
        $this->options = array_merge($this->options, $config);
    }
    
    /**
     * Show help information
     */
    private function showHelp()
    {
        echo "Sci-Bono LMS Automated Test Runner\n";
        echo "Usage: php run-tests.php [options]\n\n";
        echo "Options:\n";
        echo "  --help, -h              Show this help message\n";
        echo "  --verbose, -v           Enable verbose output\n";
        echo "  --stop-on-failure, -s   Stop execution on first failure\n";
        echo "  --coverage, -c          Generate code coverage report (requires Xdebug)\n";
        echo "  --parallel, -p          Run tests in parallel (experimental)\n";
        echo "  --suite=<suite>         Run specific test suite: " . implode(', ', array_keys($this->testSuites)) . "\n";
        echo "  --filter=<pattern>      Filter tests by pattern\n";
        echo "  --output=<file>         Generate report to file (json, xml, html, txt)\n";
        echo "  --config=<file>         Load configuration from JSON file\n\n";
        echo "Test Suites:\n";
        foreach ($this->testSuites as $suite => $description) {
            echo "  {$suite}: {$description}\n";
        }
        echo "\nEnvironment Variables:\n";
        echo "  TEST_DB_HOST            Test database host (default: localhost)\n";
        echo "  TEST_DB_USERNAME        Test database username (default: root)\n";
        echo "  TEST_DB_PASSWORD        Test database password (default: empty)\n";
        echo "  TEST_DB_NAME            Test database name (default: sci_bono_lms_test)\n";
    }
    
    /**
     * Show header information
     */
    private function showHeader()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                    Sci-Bono LMS Automated Test Runner                       ║\n";
        echo "║                       Phase 7: API Development & Testing                    ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Test Suite: " . $this->options['suite'] . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";
    }
    
    /**
     * Output error message and exit
     */
    private function error($message)
    {
        fwrite(STDERR, "ERROR: {$message}\n");
        exit(1);
    }
    
    /**
     * Output warning message
     */
    private function warning($message)
    {
        fwrite(STDERR, "WARNING: {$message}\n");
    }
}

// Run the automated test runner
$runner = new AutomatedTestRunner($argv);
exit($runner->run());