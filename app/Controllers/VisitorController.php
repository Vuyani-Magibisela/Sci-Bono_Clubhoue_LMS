<?php
/**
 * Visitor Controller - Visitor management and tracking
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/VisitorService.php';
require_once __DIR__ . '/../../core/CSRF.php';

class VisitorController extends BaseController {
    private $visitorService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->visitorService = new VisitorService($this->conn);
    }

    /**
     * Display visitors listing page
     *
     * Route: GET /visitors
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function index() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to'),
                'purpose' => $this->input('purpose'),
                'search' => $this->input('search')
            ];

            $visitors = $this->visitorService->getAllVisitors($filters);
            $statistics = $this->visitorService->getVisitorStatistics($filters);
            $purposeSummary = $this->visitorService->getVisitorsByPurpose();
            $todaysVisitors = $this->visitorService->getTodaysVisitors();

            $data = [
                'pageTitle' => 'Visitors',
                'currentPage' => 'visitors',
                'user' => $this->currentUser(),
                'visitors' => $visitors,
                'statistics' => $statistics,
                'purposeSummary' => $purposeSummary,
                'todaysVisitors' => $todaysVisitors,
                'filters' => $filters
            ];

            $this->logAction('visitors_view', ['user_id' => $this->currentUser()['id']]);

            return $this->view('admin.visitors.index', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Visitors page load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load visitors'], 'error');
        }
    }

    /**
     * Display visitor registration form (public)
     *
     * Route: GET /visitor/register
     */
    public function showRegistrationForm() {
        $data = [
            'pageTitle' => 'Visitor Registration',
            'currentPage' => 'visitor-registration'
        ];

        return $this->view('visitors.register', $data, 'public');
    }

    /**
     * Register a new visitor (public)
     *
     * Route: POST /visitor/register
     * Middleware: CSRF
     */
    public function register() {
        $this->validateCsrfToken();

        try {
            $data = $this->input();

            $visitorId = $this->visitorService->registerVisitor($data);

            if ($visitorId) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess([
                        'visitor_id' => $visitorId,
                        'message' => 'Registration successful'
                    ], 'Thank you for registering!');
                } else {
                    return $this->redirectWithSuccess('/visitor/success', 'Registration successful! Welcome to Sci-Bono Clubhouse');
                }
            }

            throw new Exception("Failed to register visitor");

        } catch (Exception $e) {
            $this->logger->error("Visitor registration failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/visitor/register', $e->getMessage());
            }
        }
    }

    /**
     * Display registration success page
     *
     * Route: GET /visitor/success
     */
    public function showSuccess() {
        $data = [
            'pageTitle' => 'Registration Successful',
            'currentPage' => 'visitor-success'
        ];

        return $this->view('visitors.success', $data, 'public');
    }

    /**
     * Check in a visitor
     *
     * Route: POST /visitors/{id}/checkin
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function checkIn($visitorId) {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $result = $this->visitorService->checkInVisitor($visitorId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Visitor checked in successfully');
                } else {
                    return $this->redirectWithSuccess('/visitors', 'Visitor checked in successfully');
                }
            }

            throw new Exception("Failed to check in visitor");

        } catch (Exception $e) {
            $this->logger->error("Visitor check-in failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/visitors', $e->getMessage());
            }
        }
    }

    /**
     * Check out a visitor
     *
     * Route: POST /visitors/{id}/checkout
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function checkOut($visitorId) {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $result = $this->visitorService->checkOutVisitor($visitorId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Visitor checked out successfully');
                } else {
                    return $this->redirectWithSuccess('/visitors', 'Visitor checked out successfully');
                }
            }

            throw new Exception("Failed to check out visitor");

        } catch (Exception $e) {
            $this->logger->error("Visitor check-out failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/visitors', $e->getMessage());
            }
        }
    }

    /**
     * Display single visitor details
     *
     * Route: GET /visitors/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function show($visitorId) {
        $this->requireRole(['admin', 'mentor']);

        try {
            $visitor = $this->visitorService->getVisitorById($visitorId);

            if (!$visitor) {
                return $this->view('errors.404', ['error' => 'Visitor not found'], 'error');
            }

            $data = [
                'pageTitle' => 'Visitor Details',
                'currentPage' => 'visitors',
                'user' => $this->currentUser(),
                'visitor' => $visitor
            ];

            return $this->view('admin.visitors.show', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Visitor detail load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load visitor details'], 'error');
        }
    }

    /**
     * Display edit visitor form
     *
     * Route: GET /visitors/{id}/edit
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function edit($visitorId) {
        $this->requireRole(['admin', 'mentor']);

        try {
            $visitor = $this->visitorService->getVisitorById($visitorId);

            if (!$visitor) {
                return $this->view('errors.404', ['error' => 'Visitor not found'], 'error');
            }

            $data = [
                'pageTitle' => 'Edit Visitor',
                'currentPage' => 'visitors',
                'user' => $this->currentUser(),
                'visitor' => $visitor
            ];

            return $this->view('admin.visitors.edit', $data, 'admin');

        } catch (Exception $e) {
            $this->logger->error("Visitor edit page load failed: " . $e->getMessage());
            return $this->redirectWithError('/visitors', 'Failed to load visitor');
        }
    }

    /**
     * Update visitor
     *
     * Route: PUT /visitors/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor, CSRF
     */
    public function update($visitorId) {
        $this->requireRole(['admin', 'mentor']);
        $this->validateCsrfToken();

        try {
            $data = $this->input();

            $result = $this->visitorService->updateVisitor($visitorId, $data);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Visitor updated successfully');
                } else {
                    return $this->redirectWithSuccess('/visitors', 'Visitor updated successfully');
                }
            }

            throw new Exception("Failed to update visitor");

        } catch (Exception $e) {
            $this->logger->error("Visitor update failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError("/visitors/{$visitorId}/edit", $e->getMessage());
            }
        }
    }

    /**
     * Delete visitor
     *
     * Route: DELETE /visitors/{id}
     * Middleware: AuthMiddleware, RoleMiddleware:admin, CSRF
     */
    public function destroy($visitorId) {
        $this->requireRole('admin');
        $this->validateCsrfToken();

        try {
            $result = $this->visitorService->deleteVisitor($visitorId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    return $this->jsonSuccess(null, 'Visitor deleted successfully');
                } else {
                    return $this->redirectWithSuccess('/visitors', 'Visitor deleted successfully');
                }
            }

            throw new Exception("Failed to delete visitor");

        } catch (Exception $e) {
            $this->logger->error("Visitor deletion failed: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                return $this->jsonError($e->getMessage());
            } else {
                return $this->redirectWithError('/visitors', $e->getMessage());
            }
        }
    }

    /**
     * Get all visitors via AJAX
     *
     * Route: GET /api/visitors
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getVisitors() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to'),
                'purpose' => $this->input('purpose'),
                'search' => $this->input('search')
            ];

            $visitors = $this->visitorService->getAllVisitors($filters);

            return $this->jsonSuccess($visitors, 'Visitors retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load visitors: ' . $e->getMessage());
        }
    }

    /**
     * Get today's visitors via AJAX
     *
     * Route: GET /api/visitors/today
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getTodaysVisitors() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $visitors = $this->visitorService->getTodaysVisitors();

            return $this->jsonSuccess($visitors, 'Today\'s visitors retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load today\'s visitors: ' . $e->getMessage());
        }
    }

    /**
     * Get visitor statistics via AJAX
     *
     * Route: GET /api/visitors/statistics
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getStatistics() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $filters = [
                'date_from' => $this->input('date_from'),
                'date_to' => $this->input('date_to')
            ];

            $statistics = $this->visitorService->getVisitorStatistics($filters);

            return $this->jsonSuccess($statistics, 'Statistics retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get visitors by purpose via AJAX
     *
     * Route: GET /api/visitors/by-purpose
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function getByPurpose() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $purposeSummary = $this->visitorService->getVisitorsByPurpose();

            return $this->jsonSuccess($purposeSummary, 'Purpose summary retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load purpose summary: ' . $e->getMessage());
        }
    }

    /**
     * Search visitors via AJAX
     *
     * Route: GET /api/visitors/search
     * Middleware: AuthMiddleware, RoleMiddleware:admin,mentor
     */
    public function search() {
        $this->requireRole(['admin', 'mentor']);

        try {
            $query = $this->input('q', '');

            if (empty($query)) {
                return $this->jsonSuccess([], 'No search query provided');
            }

            $visitors = $this->visitorService->searchVisitors($query);

            return $this->jsonSuccess($visitors, 'Search results retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Search failed: ' . $e->getMessage());
        }
    }
}
