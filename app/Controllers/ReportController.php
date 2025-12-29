<?php
/**
 * Report Controller - Clubhouse reports management
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/ReportService.php';
require_once __DIR__ . '/../../core/CSRF.php';

class ReportController extends BaseController {
    private $reportService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->reportService = new ReportService($this->conn);
    }

    /**
     * Display reports listing page
     *
     * Route: GET /reports
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function index() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to'),
                'program_name' => $this->input('program_name')
            ];

            $reports = $this->reportService->getAllReports($filters);
            $statistics = $this->reportService->getReportStatistics($filters);
            $programSummary = $this->reportService->getReportsByProgram();

            $data = [
                'pageTitle' => 'Reports',
                'currentPage' => 'reports',
                'user' => $this->currentUser(),
                'reports' => $reports,
                'statistics' => $statistics,
                'programSummary' => $programSummary,
                'filters' => $filters
            ];

            $this->logAction('reports_view', ['user_id' => $this->currentUser()['id']]);

            return $this->view('admin.reports.index', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Reports page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load reports'], 'error');
        }
    }

    /**
     * Display create report form
     *
     * Route: GET /reports/create
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function create() {
        $this->requireRole(['admin', 'mentor']);

        $data = [
            'pageTitle' => 'Create Report',
            'currentPage' => 'reports',
            'user' => $this->currentUser()
        ];

        return $this->view('admin.reports.create', $data, 'admin');
    }

    /**
     * Store new report
     *
     * Route: POST /reports
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function store() {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $data = $this->input();
            $imageFile = $this->file('image');

            $reportId = $this->reportService->createReport($data, $imageFile);

            if ($reportId) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(['report_id' => $reportId], 'Report created successfully');
                } else {
                    return $this->redirectWithSuccess('/reports', 'Report created successfully');
                }
            }

            throw new Exception("Failed to create report");

        } catch (Exception $e) {
            $this->logger->error("Report creation failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/reports/create', $e->getMessage());
            }
        }
    }

    /**
     * Store batch reports
     *
     * Route: POST /reports/batch
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function storeBatch() {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $programs = $this->input('programs', []);
            $participants = $this->input('participants', []);
            $narratives = $this->input('narratives', []);
            $challenges = $this->input('challenges', []);
            $images = $_FILES['images'] ?? [];

            // Build reports data array
            $reportsData = [];
            for ($i = 0; $i < count($programs); $i++) {
                $reportsData[] = [
                    'program_name' => $programs[$i] ?? '',
                    'participants' => $participants[$i] ?? 0,
                    'narrative' => $narratives[$i] ?? '',
                    'challenges' => $challenges[$i] ?? ''
                ];
            }

            // Build image files array
            $imageFiles = [];
            if (!empty($images['name'])) {
                for ($i = 0; $i < count($images['name']); $i++) {
                    if ($images['error'][$i] === UPLOAD_ERR_OK) {
                        $imageFiles[$i] = [
                            'name' => $images['name'][$i],
                            'type' => $images['type'][$i],
                            'tmp_name' => $images['tmp_name'][$i],
                            'error' => $images['error'][$i],
                            'size' => $images['size'][$i]
                        ];
                    }
                }
            }

            $result = $this->reportService->createBatchReports($reportsData, $imageFiles);

            if ($this->isAjaxRequest()) {
                return $this->jsonSuccess($result, "Created {$result['success_count']} of {$result['total_count']} reports");
            } else {
                $message = "Created {$result['success_count']} of {$result['total_count']} reports";
                return $this->redirectWithSuccess('/reports', $message);
            }

        } catch (Exception $e) {
            $this->logger->error("Batch report creation failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/reports/create', $e->getMessage());
            }
        }
    }

    /**
     * Display single report
     *
     * Route: GET /reports/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function show($reportId) {
        $this->requireRole(['admin', 'mentor']);

        try {
            $report = $this->reportService->getReportById($reportId);

            if (!$report) {
                return $this->view('errors.404', ['error' => 'Report not found'], 'error');
            }

            $data = [
                'pageTitle' => 'Report Details',
                'currentPage' => 'reports',
                'user' => $this->currentUser(),
                'report' => $report
            ];

            return $this->view('admin.reports.show', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Report detail load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load report'], 'error');
        }
    }

    /**
     * Display edit report form
     *
     * Route: GET /reports/{id}/edit
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function edit($reportId) {
        $this->requireRole(['admin', 'mentor']);

        try {
            $report = $this->reportService->getReportById($reportId);

            if (!$report) {
                return $this->view('errors.404', ['error' => 'Report not found'], 'error');
            }

            $data = [
                'pageTitle' => 'Edit Report',
                'currentPage' => 'reports',
                'user' => $this->currentUser(),
                'report' => $report
            ];

            return $this->view('admin.reports.edit', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Report edit page load failed: " . $e->getMessage());
            return $this->redirectWithError('/reports', 'Failed to load report');
        }
    }

    /**
     * Update report
     *
     * Route: PUT /reports/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function update($reportId) {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $data = $this->input();

            $result = $this->reportService->updateReport($reportId, $data);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Report updated successfully');
                } else {
                    return $this->redirectWithSuccess('/reports', 'Report updated successfully');
                }
            }

            throw new Exception("Failed to update report");

        } catch (Exception $e) {
            $this->logger->error("Report update failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/reports/{$reportId}/edit", $e->getMessage());
            }
        }
    }

    /**
     * Delete report
     *
     * Route: DELETE /reports/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin, CSRF
     */
    public function destroy($reportId) {
        $this->requireRole('admin');
        $this->validateCsrfToken();

        try {
            $result = $this->reportService->deleteReport($reportId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Report deleted successfully');
                } else {
                    return $this->redirectWithSuccess('/reports', 'Report deleted successfully');
                }
            }

            throw new Exception("Failed to delete report");

        } catch (Exception $e) {
            $this->logger->error("Report deletion failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/reports', $e->getMessage());
            }
        }
    }

    /**
     * Get all reports via AJAX
     *
     * Route: GET /api/reports
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getReports() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to'),
                'program_name' => $this->input('program_name')
            ];

            $reports = $this->reportService->getAllReports($filters);

            return $this->jsonSuccess($reports, 'Reports retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load reports: ' . $e->getMessage());
        }
    }

    /**
     * Get report statistics via AJAX
     *
     * Route: GET /api/reports/statistics
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getStatistics() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to')
            ];

            $statistics = $this->reportService->getReportStatistics($filters);

            return $this->jsonSuccess($statistics, 'Statistics retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get reports by program via AJAX
     *
     * Route: GET /api/reports/by-program
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getByProgram() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $programSummary = $this->reportService->getReportsByProgram();

            return $this->jsonSuccess($programSummary, 'Program summary retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load program summary: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly report summary via AJAX
     *
     * Route: GET /api/reports/monthly
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getMonthly() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $year = $this->input('year', date('Y'));
            $month = $this->input('month', date('n'));

            $summary = $this->reportService->getMonthlyReportSummary($year, $month);

            return $this->jsonSuccess($summary, 'Monthly summary retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Search reports via AJAX
     *
     * Route: GET /api/reports/search
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function search() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $query = $this->input('q', '');

            if (empty($query)) {
                return $this->jsonSuccess([], 'No search query provided');
            }

            $reports = $this->reportService->searchReports($query);

            return $this->jsonSuccess($reports, 'Search results retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Search failed: ' . $e->getMessage());
        }
    }
}
