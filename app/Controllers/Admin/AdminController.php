<?php
/**
 * Admin\AdminController
 *
 * Handles admin dashboard and main admin functions
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs implementation
 */

namespace Admin;

require_once __DIR__ . '/../BaseController.php';

class AdminController extends \BaseController {

    /**
     * Display the admin dashboard
     *
     * Route: GET /admin
     * Name: admin.dashboard
     * Middleware: AuthMiddleware, RoleMiddleware:admin
     */
    public function dashboard() {
        // Ensure authentication (middleware should handle this, but double-check)
        $this->requireRole('admin');

        // Get current year and month filters
        $selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;

        // Fetch dashboard statistics
        $stats = $this->getDashboardStats($selectedYear, $selectedMonth);

        // Fetch recent activity
        $recentActivity = $this->getRecentActivity(10);

        // Render dashboard view
        $data = [
            'pageTitle' => 'Admin Dashboard',
            'currentPage' => 'dashboard',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'yearOptions' => $this->getYearOptions(),
            'monthOptions' => $this->getMonthOptions()
        ];

        return $this->view('admin.dashboard.index', $data, 'admin');
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($year, $month) {
        // Total users
        $userQuery = "SELECT COUNT(*) as total FROM users";
        $userResult = $this->conn->query($userQuery);
        $totalUsers = $userResult->fetch_assoc()['total'];

        // Total courses
        $courseQuery = "SELECT COUNT(*) as total FROM courses";
        $courseResult = $this->conn->query($courseQuery);
        $totalCourses = $courseResult->fetch_assoc()['total'];

        // Total programs
        $programQuery = "SELECT COUNT(*) as total FROM holiday_programs WHERE status = 'active'";
        $programResult = $this->conn->query($programQuery);
        $totalPrograms = $programResult->fetch_assoc()['total'];

        // Attendance today
        $today = date('Y-m-d');
        $attendanceQuery = "SELECT COUNT(DISTINCT user_id) as total FROM attendance
                           WHERE DATE(signin_time) = ?";
        $stmt = $this->conn->prepare($attendanceQuery);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $attendanceResult = $stmt->get_result();
        $todayAttendance = $attendanceResult->fetch_assoc()['total'];

        // Monthly unique members
        $memberQuery = $month > 0
            ? "SELECT COUNT(DISTINCT user_id) AS total
               FROM attendance
               WHERE MONTH(signin_time) = ? AND YEAR(signin_time) = ?"
            : "SELECT COUNT(DISTINCT user_id) AS total
               FROM attendance
               WHERE YEAR(signin_time) = ?";

        $stmt = $this->conn->prepare($memberQuery);
        if ($month > 0) {
            $stmt->bind_param("ii", $month, $year);
        } else {
            $stmt->bind_param("i", $year);
        }
        $stmt->execute();
        $memberResult = $stmt->get_result();
        $monthlyMembers = $memberResult->fetch_assoc()['total'];

        // Course enrollments
        $enrollmentQuery = "SELECT COUNT(*) as total FROM enrollments";
        $enrollmentResult = $this->conn->query($enrollmentQuery);
        $totalEnrollments = $enrollmentResult->fetch_assoc()['total'];

        // Program registrations
        $registrationQuery = "SELECT COUNT(*) as total FROM program_registrations
                             WHERE status IN ('approved', 'confirmed')";
        $registrationResult = $this->conn->query($registrationQuery);
        $totalRegistrations = $registrationResult->fetch_assoc()['total'];

        return [
            'totalUsers' => $totalUsers,
            'totalCourses' => $totalCourses,
            'totalPrograms' => $totalPrograms,
            'todayAttendance' => $todayAttendance,
            'monthlyMembers' => $monthlyMembers,
            'totalEnrollments' => $totalEnrollments,
            'totalRegistrations' => $totalRegistrations
        ];
    }

    /**
     * Get recent activity across the system
     */
    private function getRecentActivity($limit = 10) {
        $activities = [];

        // Recent user registrations
        $userQuery = "SELECT id, name, surname, date_of_registration as created_at,
                      'user' as type FROM users
                      ORDER BY date_of_registration DESC LIMIT ?";
        $stmt = $this->conn->prepare($userQuery);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $userResult = $stmt->get_result();
        while ($row = $userResult->fetch_assoc()) {
            $activities[] = [
                'type' => 'user_registered',
                'description' => "New user: {$row['name']} {$row['surname']}",
                'created_at' => $row['created_at'],
                'icon' => 'fa-user-plus',
                'color' => 'success'
            ];
        }

        // Recent course enrollments
        $enrollmentQuery = "SELECT e.id, e.enrollment_date as created_at,
                           u.name, u.surname, c.title as course_title
                           FROM enrollments e
                           JOIN users u ON e.student_id = u.id
                           JOIN courses c ON e.course_id = c.id
                           ORDER BY e.enrollment_date DESC LIMIT ?";
        $stmt = $this->conn->prepare($enrollmentQuery);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $enrollmentResult = $stmt->get_result();
        while ($row = $enrollmentResult->fetch_assoc()) {
            $activities[] = [
                'type' => 'course_enrollment',
                'description' => "{$row['name']} {$row['surname']} enrolled in {$row['course_title']}",
                'created_at' => $row['created_at'],
                'icon' => 'fa-book',
                'color' => 'primary'
            ];
        }

        // Sort all activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get year options for filter
     */
    private function getYearOptions() {
        $currentYear = date('Y');
        $years = [];
        for ($year = $currentYear; $year >= $currentYear - 4; $year--) {
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Get month options for filter
     */
    private function getMonthOptions() {
        return [
            0 => 'All Months',
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
    }
}
