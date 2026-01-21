<?php
/**
 * DeprecationMonitorController
 *
 * Admin controller for monitoring deprecated file usage
 *
 * Phase 4 Week 4 Day 4: Deprecated File Monitoring Dashboard
 * Created: January 5, 2026
 */

namespace App\Controllers\Admin;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Services/DeprecationMonitorService.php';

class DeprecationMonitorController extends \BaseController {

    private $deprecationService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->deprecationService = new \DeprecationMonitorService($conn);
    }

    /**
     * Show deprecation monitoring dashboard
     *
     * GET /admin/deprecation-monitor
     */
    public function index() {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Get time range from query params (default: 30 days)
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $days = max(1, min(365, $days)); // Limit between 1 and 365 days

            // Get deprecation statistics
            $stats = $this->deprecationService->getDeprecationStats($days);
            $statsByDate = $this->deprecationService->getStatsByDate($days);
            $recommendations = $this->deprecationService->getRecommendations();

            // Log dashboard access
            $this->logAction('view_deprecation_monitor', [
                'days' => $days,
                'total_hits' => $stats['total_hits']
            ]);

            // Render dashboard view
            $this->view('admin.system.deprecation-monitor', [
                'stats' => $stats,
                'statsByDate' => $statsByDate,
                'recommendations' => $recommendations,
                'days' => $days
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Failed to load deprecation monitor", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->view('errors.500', [
                'error' => 'Failed to load deprecation monitoring dashboard'
            ]);
        }
    }

    /**
     * Export deprecation statistics to CSV
     *
     * GET /admin/deprecation-monitor/export
     */
    public function export() {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Get time range from query params (default: 30 days)
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $days = max(1, min(365, $days));

            // Generate CSV
            $csv = $this->deprecationService->exportToCsv($days);

            // Log export action
            $this->logAction('export_deprecation_stats', [
                'days' => $days,
                'format' => 'csv'
            ]);

            // Send CSV file
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="deprecation_stats_' . date('Y-m-d') . '.csv"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');

            echo $csv;
            exit;

        } catch (\Exception $e) {
            $this->logger->error("Failed to export deprecation stats", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Failed to export statistics'
            ], 500);
        }
    }

    /**
     * Get deprecation statistics as JSON (for AJAX requests)
     *
     * GET /admin/deprecation-monitor/stats
     */
    public function getStats() {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            // Get time range from query params (default: 30 days)
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $days = max(1, min(365, $days));

            // Get statistics
            $stats = $this->deprecationService->getDeprecationStats($days);
            $statsByDate = $this->deprecationService->getStatsByDate($days);

            $this->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'by_date' => $statsByDate
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Failed to get deprecation stats", [
                'error' => $e->getMessage()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Get recommendations as JSON
     *
     * GET /admin/deprecation-monitor/recommendations
     */
    public function getRecommendations() {
        // Require admin role
        $this->requireRole(['admin']);

        try {
            $recommendations = $this->deprecationService->getRecommendations();

            $this->json([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Failed to get recommendations", [
                'error' => $e->getMessage()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Failed to retrieve recommendations'
            ], 500);
        }
    }
}
