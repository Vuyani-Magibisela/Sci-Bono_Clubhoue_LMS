<?php
/**
 * Performance Benchmark Framework
 *
 * Benchmarks controller performance metrics:
 * - Execution time
 * - Memory usage
 * - Database query count
 * - Response size
 *
 * Phase 4 Week 4 Day 5: Performance Benchmarking
 * Created: January 5, 2026
 *
 * @package Tests
 * @since Phase 4 Week 4 Day 5
 */

namespace Tests;

class PerformanceBenchmark
{
    private $results = [];
    private $db;
    private $queryCount = 0;
    private $startTime;
    private $startMemory;

    /**
     * Constructor
     */
    public function __construct($db = null)
    {
        $this->db = $db;
    }

    /**
     * Start benchmarking
     */
    public function start($benchmarkName)
    {
        $this->queryCount = 0;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        // Enable query logging if database provided
        if ($this->db) {
            $this->enableQueryLogging();
        }

        return $benchmarkName;
    }

    /**
     * Stop benchmarking and record results
     */
    public function stop($benchmarkName, $metadata = [])
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $executionTime = ($endTime - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $this->startMemory;
        $peakMemory = memory_get_peak_usage(true);

        $this->results[$benchmarkName] = [
            'execution_time_ms' => round($executionTime, 3),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 3),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 3),
            'query_count' => $this->queryCount,
            'metadata' => $metadata,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->results[$benchmarkName];
    }

    /**
     * Benchmark a callback function
     */
    public function benchmark($benchmarkName, $callback, $metadata = [])
    {
        $this->start($benchmarkName);

        try {
            $result = $callback();
            $this->stop($benchmarkName, array_merge($metadata, ['status' => 'success']));
            return $result;
        } catch (\Exception $e) {
            $this->stop($benchmarkName, array_merge($metadata, [
                'status' => 'error',
                'error' => $e->getMessage()
            ]));
            throw $e;
        }
    }

    /**
     * Benchmark controller method
     */
    public function benchmarkController($controllerName, $methodName, $args = [], $metadata = [])
    {
        $benchmarkName = "{$controllerName}::{$methodName}";

        return $this->benchmark($benchmarkName, function() use ($controllerName, $methodName, $args) {
            // Instantiate controller
            if (!class_exists($controllerName)) {
                throw new \Exception("Controller {$controllerName} does not exist");
            }

            $controller = new $controllerName($this->db);

            if (!method_exists($controller, $methodName)) {
                throw new \Exception("Method {$methodName} does not exist on {$controllerName}");
            }

            // Execute method
            ob_start();
            $result = call_user_func_array([$controller, $methodName], $args);
            $output = ob_get_clean();

            return [
                'result' => $result,
                'output' => $output,
                'output_size' => strlen($output)
            ];
        }, array_merge($metadata, [
            'controller' => $controllerName,
            'method' => $methodName,
            'args_count' => count($args)
        ]));
    }

    /**
     * Benchmark multiple iterations
     */
    public function benchmarkIterations($benchmarkName, $callback, $iterations = 10, $metadata = [])
    {
        $results = [];
        $totalTime = 0;
        $totalMemory = 0;
        $totalQueries = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $iterationName = "{$benchmarkName}_iteration_{$i}";
            $this->start($iterationName);

            try {
                $callback();
                $iterationResult = $this->stop($iterationName, $metadata);

                $results[] = $iterationResult;
                $totalTime += $iterationResult['execution_time_ms'];
                $totalMemory += $iterationResult['memory_used_bytes'];
                $totalQueries += $iterationResult['query_count'];

            } catch (\Exception $e) {
                // Continue with other iterations
                $this->stop($iterationName, array_merge($metadata, ['error' => $e->getMessage()]));
            }
        }

        // Calculate averages
        $avgTime = $totalTime / $iterations;
        $avgMemory = $totalMemory / $iterations;
        $avgQueries = $totalQueries / $iterations;

        // Calculate standard deviation for execution time
        $variance = 0;
        foreach ($results as $result) {
            $variance += pow($result['execution_time_ms'] - $avgTime, 2);
        }
        $stdDev = sqrt($variance / $iterations);

        $summary = [
            'iterations' => $iterations,
            'avg_execution_time_ms' => round($avgTime, 3),
            'min_execution_time_ms' => round(min(array_column($results, 'execution_time_ms')), 3),
            'max_execution_time_ms' => round(max(array_column($results, 'execution_time_ms')), 3),
            'std_dev_ms' => round($stdDev, 3),
            'avg_memory_mb' => round($avgMemory / 1024 / 1024, 3),
            'avg_query_count' => round($avgQueries, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->results["{$benchmarkName}_summary"] = $summary;

        return $summary;
    }

    /**
     * Compare two benchmark results
     */
    public function compare($benchmark1, $benchmark2)
    {
        if (!isset($this->results[$benchmark1]) || !isset($this->results[$benchmark2])) {
            throw new \Exception("One or both benchmarks not found");
        }

        $b1 = $this->results[$benchmark1];
        $b2 = $this->results[$benchmark2];

        $timeDiff = $b2['execution_time_ms'] - $b1['execution_time_ms'];
        $timePercent = ($timeDiff / $b1['execution_time_ms']) * 100;

        $memoryDiff = $b2['memory_used_bytes'] - $b1['memory_used_bytes'];
        $memoryPercent = ($memoryDiff / $b1['memory_used_bytes']) * 100;

        $queryDiff = $b2['query_count'] - $b1['query_count'];

        return [
            'benchmark_1' => $benchmark1,
            'benchmark_2' => $benchmark2,
            'time_difference_ms' => round($timeDiff, 3),
            'time_difference_percent' => round($timePercent, 2),
            'memory_difference_mb' => round($memoryDiff / 1024 / 1024, 3),
            'memory_difference_percent' => round($memoryPercent, 2),
            'query_difference' => $queryDiff,
            'faster' => $timeDiff < 0 ? $benchmark1 : $benchmark2,
            'more_efficient_memory' => $memoryDiff < 0 ? $benchmark1 : $benchmark2
        ];
    }

    /**
     * Get all results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get specific result
     */
    public function getResult($benchmarkName)
    {
        return $this->results[$benchmarkName] ?? null;
    }

    /**
     * Export results to JSON
     */
    public function exportToJson($filename = null)
    {
        $filename = $filename ?? 'benchmark_results_' . date('Y-m-d_H-i-s') . '.json';

        $export = [
            'generated_at' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'results' => $this->results
        ];

        file_put_contents($filename, json_encode($export, JSON_PRETTY_PRINT));

        return $filename;
    }

    /**
     * Export results to CSV
     */
    public function exportToCsv($filename = null)
    {
        $filename = $filename ?? 'benchmark_results_' . date('Y-m-d_H-i-s') . '.csv';

        $csv = "Benchmark Name,Execution Time (ms),Memory Used (MB),Peak Memory (MB),Query Count,Status\n";

        foreach ($this->results as $name => $result) {
            $status = $result['metadata']['status'] ?? 'success';
            $csv .= sprintf(
                "\"%s\",%.3f,%.3f,%.3f,%d,\"%s\"\n",
                $name,
                $result['execution_time_ms'],
                $result['memory_used_mb'],
                $result['peak_memory_mb'],
                $result['query_count'],
                $status
            );
        }

        file_put_contents($filename, $csv);

        return $filename;
    }

    /**
     * Print summary report
     */
    public function printReport()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  Performance Benchmark Report\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "Total Benchmarks: " . count($this->results) . "\n\n";

        echo "─────────────────────────────────────────────────────────────\n";
        printf("%-40s %12s %12s %10s\n", "Benchmark", "Time (ms)", "Memory (MB)", "Queries");
        echo "─────────────────────────────────────────────────────────────\n";

        foreach ($this->results as $name => $result) {
            printf(
                "%-40s %12.3f %12.3f %10d\n",
                substr($name, 0, 40),
                $result['execution_time_ms'],
                $result['memory_used_mb'],
                $result['query_count']
            );
        }

        echo "─────────────────────────────────────────────────────────────\n\n";

        // Performance recommendations
        echo "Performance Recommendations:\n";
        echo "─────────────────────────────────────────────────────────────\n";

        $slowBenchmarks = array_filter($this->results, function($r) {
            return $r['execution_time_ms'] > 100;
        });

        $highMemoryBenchmarks = array_filter($this->results, function($r) {
            return $r['memory_used_mb'] > 5;
        });

        $highQueryBenchmarks = array_filter($this->results, function($r) {
            return $r['query_count'] > 10;
        });

        if (count($slowBenchmarks) > 0) {
            echo "⚠️  Slow Benchmarks (>100ms):\n";
            foreach ($slowBenchmarks as $name => $result) {
                echo "   - {$name}: {$result['execution_time_ms']}ms\n";
            }
            echo "\n";
        }

        if (count($highMemoryBenchmarks) > 0) {
            echo "⚠️  High Memory Usage (>5MB):\n";
            foreach ($highMemoryBenchmarks as $name => $result) {
                echo "   - {$name}: {$result['memory_used_mb']}MB\n";
            }
            echo "\n";
        }

        if (count($highQueryBenchmarks) > 0) {
            echo "⚠️  High Query Count (>10):\n";
            foreach ($highQueryBenchmarks as $name => $result) {
                echo "   - {$name}: {$result['query_count']} queries\n";
            }
            echo "\n";
        }

        if (empty($slowBenchmarks) && empty($highMemoryBenchmarks) && empty($highQueryBenchmarks)) {
            echo "✅ All benchmarks within acceptable performance thresholds\n\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Enable query logging
     */
    private function enableQueryLogging()
    {
        // This is a placeholder - actual implementation would depend on database wrapper
        // For mysqli, we could use mysqlnd_qc or custom wrapper
        // For now, this is a stub that can be extended
    }

    /**
     * Increment query counter (to be called by database wrapper)
     */
    public function incrementQueryCount()
    {
        $this->queryCount++;
    }

    /**
     * Reset all results
     */
    public function reset()
    {
        $this->results = [];
        $this->queryCount = 0;
    }
}
