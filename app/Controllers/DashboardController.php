<?php
/**
 * Dashboard Controller - Member dashboard and home functionality
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/DashboardService.php';
require_once __DIR__ . '/../../core/CSRF.php';

class DashboardController extends BaseController {
    private $dashboardService;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->dashboardService = new DashboardService($this->conn);
    }

    /**
     * Display the user dashboard (home page)
     *
     * Route: GET /dashboard
     * Route: GET /home
     * Middleware: AuthMiddleware
     */
    public function index() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            // Get comprehensive dashboard data
            $dashboardData = $this->dashboardService->getUserDashboardData($userId);

            $data = [
                'pageTitle' => 'Dashboard',
                'currentPage' => 'dashboard',
                'user' => $this->currentUser(),
                'stats' => $dashboardData['user_stats'],
                'activityFeed' => $dashboardData['activity_feed'],
                'learningProgress' => $dashboardData['learning_progress'],
                'upcomingEvents' => $dashboardData['upcoming_events'],
                'clubhousePrograms' => $dashboardData['clubhouse_programs'],
                'birthdays' => $dashboardData['birthdays'],
                'continueLearning' => $dashboardData['continue_learning'],
                'badges' => $dashboardData['badges'],
                'communityChats' => $dashboardData['community_chats'],
                'onlineContacts' => $dashboardData['online_contacts']
            ];

            $this->logAction('dashboard_view', ['user_id' => $userId]);

            return $this->view('member.dashboard.index', $data, 'app');

        } catch (Exception $e) {
            $this->logger->error("Dashboard load failed: " . $e->getMessage());
            return $this->view('errors.500', ['error' => 'Failed to load dashboard'], 'error');
        }
    }

    /**
     * Get dashboard data via AJAX (for refresh)
     *
     * Route: GET /dashboard/data
     * Middleware: AuthMiddleware
     */
    public function getData() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $dashboardData = $this->dashboardService->getUserDashboardData($userId);

            return $this->jsonSuccess($dashboardData, 'Dashboard data retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics only
     *
     * Route: GET /dashboard/stats
     * Middleware: AuthMiddleware
     */
    public function getStats() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $stats = $this->dashboardService->getUserStats($userId);

            return $this->jsonSuccess($stats, 'User stats retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load stats: ' . $e->getMessage());
        }
    }

    /**
     * Get activity feed only
     *
     * Route: GET /dashboard/activity
     * Middleware: AuthMiddleware
     */
    public function getActivityFeed() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $limit = $this->input('limit', 10);

            $activityFeed = $this->dashboardService->getActivityFeed($userId, $limit);

            return $this->jsonSuccess($activityFeed, 'Activity feed retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load activity feed: ' . $e->getMessage());
        }
    }

    /**
     * Get upcoming events only
     *
     * Route: GET /dashboard/events
     * Middleware: AuthMiddleware
     */
    public function getUpcomingEvents() {
        $this->requireAuth();

        try {
            $limit = $this->input('limit', 4);

            $events = $this->dashboardService->getUpcomingEvents($limit);

            return $this->jsonSuccess($events, 'Upcoming events retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load events: ' . $e->getMessage());
        }
    }

    /**
     * Get clubhouse programs only
     *
     * Route: GET /dashboard/programs
     * Middleware: AuthMiddleware
     */
    public function getPrograms() {
        $this->requireAuth();

        try {
            $limit = $this->input('limit', 5);

            $programs = $this->dashboardService->getClubhousePrograms($limit);

            return $this->jsonSuccess($programs, 'Programs retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load programs: ' . $e->getMessage());
        }
    }

    /**
     * Get learning progress only
     *
     * Route: GET /dashboard/learning-progress
     * Middleware: AuthMiddleware
     */
    public function getLearningProgress() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $progress = $this->dashboardService->getUserLearningProgress($userId);

            return $this->jsonSuccess($progress, 'Learning progress retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load learning progress: ' . $e->getMessage());
        }
    }

    /**
     * Get continue learning section
     *
     * Route: GET /dashboard/continue-learning
     * Middleware: AuthMiddleware
     */
    public function getContinueLearning() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];
            $limit = $this->input('limit', 3);

            $courses = $this->dashboardService->getContinueLearning($userId, $limit);

            return $this->jsonSuccess($courses, 'Continue learning retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load continue learning: ' . $e->getMessage());
        }
    }

    /**
     * Get user badges
     *
     * Route: GET /dashboard/badges
     * Middleware: AuthMiddleware
     */
    public function getBadges() {
        $this->requireAuth();

        try {
            $userId = $this->currentUser()['id'];

            $badges = $this->dashboardService->getUserBadges($userId);

            return $this->jsonSuccess($badges, 'Badges retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load badges: ' . $e->getMessage());
        }
    }

    /**
     * Get birthdays
     *
     * Route: GET /dashboard/birthdays
     * Middleware: AuthMiddleware
     */
    public function getBirthdays() {
        $this->requireAuth();

        try {
            $limit = $this->input('limit', 3);

            $birthdays = $this->dashboardService->getBirthdays($limit);

            return $this->jsonSuccess($birthdays, 'Birthdays retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load birthdays: ' . $e->getMessage());
        }
    }

    /**
     * Get community chats
     *
     * Route: GET /dashboard/chats
     * Middleware: AuthMiddleware
     */
    public function getChats() {
        $this->requireAuth();

        try {
            $chats = $this->dashboardService->getCommunityChats();

            return $this->jsonSuccess($chats, 'Community chats retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load chats: ' . $e->getMessage());
        }
    }

    /**
     * Get online contacts
     *
     * Route: GET /dashboard/online-contacts
     * Middleware: AuthMiddleware
     */
    public function getOnlineContacts() {
        $this->requireAuth();

        try {
            $limit = $this->input('limit', 5);

            $contacts = $this->dashboardService->getOnlineContacts($limit);

            return $this->jsonSuccess($contacts, 'Online contacts retrieved');

        } catch (Exception $e) {
            return $this->jsonError('Failed to load online contacts: ' . $e->getMessage());
        }
    }

    /**
     * Redirect legacy home.php to dashboard
     *
     * Route: GET /
     * Route: GET /home.php
     */
    public function home() {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }
}
