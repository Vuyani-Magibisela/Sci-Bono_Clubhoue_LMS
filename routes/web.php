<?php
/**
 * Web Routes - Define all web-based routes
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../core/ModernRouter.php';

$router = new Router('/Sci-Bono_Clubhoue_LMS');

// ====== PUBLIC ROUTES ======

// Home/Landing page
$router->get('/', 'HomeController@index', 'home');

// Authentication routes
$router->get('/login', 'AuthController@showLogin', 'login.show');
$router->post('/login', 'AuthController@login', 'login.process')
       ->middleware('ModernRateLimitMiddleware:login');
$router->get('/signup', 'AuthController@showSignup', 'signup.show');
$router->post('/signup', 'AuthController@signup', 'signup.process')
       ->middleware('ModernRateLimitMiddleware:signup');
$router->post('/logout', 'AuthController@logout', 'logout');

// Password reset routes
$router->get('/forgot-password', 'AuthController@showForgotPassword', 'password.forgot');
$router->post('/forgot-password', 'AuthController@sendResetLink', 'password.reset.send')
       ->middleware('ModernRateLimitMiddleware:forgot');
$router->get('/reset-password/{token}', 'AuthController@showResetForm', 'password.reset.form');
$router->post('/reset-password', 'AuthController@resetPassword', 'password.reset.process')
       ->middleware('ModernRateLimitMiddleware:reset');

// Attendance register (public access)
$router->get('/attendance', 'AttendanceController@index', 'attendance.index');

// Holiday Program Authentication (public access - separate from main auth)
$router->get('/holiday-login', 'ProfileController@login', 'holiday.login.show');
$router->post('/holiday-login', 'ProfileController@authenticate', 'holiday.login.process')
       ->middleware('ModernRateLimitMiddleware:holiday');
$router->post('/holiday-logout', 'ProfileController@logout', 'holiday.logout');
$router->get('/holiday-verify-email/{token}', 'ProfileController@verifyEmail', 'holiday.verify.email');
$router->get('/holiday-create-password', 'ProfileController@createPassword', 'holiday.password.create');
$router->post('/holiday-create-password', 'ProfileController@storePassword', 'holiday.password.store');

// Holiday Program User Dashboard (requires holiday program login)
$router->get('/holiday-dashboard', 'ProfileController@dashboard', 'holiday.dashboard');
$router->get('/holiday-profile', 'ProfileController@show', 'holiday.profile.show');
$router->get('/holiday-profile/edit', 'ProfileController@edit', 'holiday.profile.edit');
$router->post('/holiday-profile/edit', 'ProfileController@update', 'holiday.profile.update');

// Holiday Programs (Public Access)
$router->get('/programs', 'ProgramController@index', 'programs.public.index');
$router->get('/programs/{id}', 'ProgramController@show', 'programs.public.show');
$router->post('/programs/{id}/register', 'ProgramController@register', 'programs.public.register');
$router->get('/programs/{id}/workshops', 'ProgramController@workshops', 'programs.public.workshops');
$router->get('/programs/{id}/registration-confirmation', 'ProgramController@registrationConfirmation', 'programs.public.confirmation');
$router->get('/programs/my-programs', 'ProgramController@myPrograms', 'programs.public.my-programs');

// Visitor Registration (Public Access)
$router->get('/visitor/register', 'VisitorController@showRegistration', 'visitor.register.show');
$router->post('/visitor/register', 'VisitorController@register', 'visitor.register.process')
       ->middleware('ModernRateLimitMiddleware:visitor');
$router->get('/visitor/success', 'VisitorController@success', 'visitor.success');

// ====== AUTHENTICATED ROUTES ======

$router->group(['middleware' => ['AuthMiddleware']], function($router) {

    // ====== Dashboard Routes ======
    $router->get('/dashboard', 'DashboardController@index', 'dashboard');
    $router->get('/api/dashboard/stats', 'DashboardController@getStats', 'dashboard.api.stats');
    $router->get('/api/dashboard/activity', 'DashboardController@getActivityFeed', 'dashboard.api.activity');
    $router->get('/api/dashboard/progress', 'DashboardController@getProgress', 'dashboard.api.progress');
    $router->get('/api/dashboard/courses', 'DashboardController@getCourses', 'dashboard.api.courses');
    $router->get('/api/dashboard/lessons', 'DashboardController@getLessons', 'dashboard.api.lessons');
    $router->get('/api/dashboard/events', 'DashboardController@getEvents', 'dashboard.api.events');
    $router->get('/api/dashboard/programs', 'DashboardController@getPrograms', 'dashboard.api.programs');
    $router->get('/api/dashboard/birthdays', 'DashboardController@getBirthdays', 'dashboard.api.birthdays');
    $router->get('/api/dashboard/chats', 'DashboardController@getChats', 'dashboard.api.chats');
    $router->get('/api/dashboard/contacts', 'DashboardController@getOnlineContacts', 'dashboard.api.contacts');
    $router->get('/api/dashboard/notifications', 'DashboardController@getNotifications', 'dashboard.api.notifications');
    $router->post('/api/dashboard/notifications/{id}/read', 'DashboardController@markNotificationRead', 'dashboard.api.notifications.read');

    // ====== Settings Routes ======
    $router->get('/settings', 'SettingsController@index', 'settings.index');
    $router->get('/settings/profile', 'SettingsController@profile', 'settings.profile');
    $router->post('/settings/profile', 'SettingsController@updateProfile', 'settings.profile.update');
    $router->get('/settings/password', 'SettingsController@password', 'settings.password');
    $router->post('/settings/password', 'SettingsController@updatePassword', 'settings.password.update');
    $router->get('/settings/notifications', 'SettingsController@notifications', 'settings.notifications');
    $router->post('/settings/notifications', 'SettingsController@updateNotifications', 'settings.notifications.update');
    $router->get('/settings/delete-account', 'SettingsController@deleteAccount', 'settings.delete-account');
    $router->post('/settings/delete-account', 'SettingsController@confirmDelete', 'settings.delete-account.confirm');
    $router->post('/settings/avatar', 'SettingsController@updateAvatar', 'settings.avatar.update');
    $router->get('/api/settings/profile', 'SettingsController@getProfile', 'settings.api.profile');
    $router->get('/api/settings/notifications', 'SettingsController@getNotificationSettings', 'settings.api.notifications');
    $router->post('/api/settings/2fa/enable', 'SettingsController@enable2FA', 'settings.api.2fa.enable');
    $router->post('/api/settings/2fa/disable', 'SettingsController@disable2FA', 'settings.api.2fa.disable');

    // ====== Profile Routes (Legacy compatibility) ======
    $router->get('/profile', 'UserController@profile', 'profile.show');
    $router->post('/profile', 'UserController@updateProfile', 'profile.update');
    $router->get('/profile/edit', 'UserController@editProfile', 'profile.edit');

    // ====== Course Routes (Member) ======
    $router->get('/courses', 'Member\\CourseController@index', 'courses.index');
    $router->get('/courses/my-courses', 'Member\\CourseController@myCourses', 'courses.my-courses');
    $router->get('/courses/{id}', 'Member\\CourseController@show', 'courses.show');
    $router->post('/courses/{id}/enroll', 'Member\\CourseController@enroll', 'courses.enroll');
    $router->post('/courses/{id}/unenroll', 'Member\\CourseController@unenroll', 'courses.unenroll');
    $router->get('/api/courses/search', 'Member\\CourseController@search', 'courses.api.search');
    $router->get('/api/courses/{id}/progress', 'Member\\CourseController@getProgress', 'courses.api.progress');
    $router->get('/api/courses/{id}/curriculum', 'Member\\CourseController@getCurriculum', 'courses.api.curriculum');
    $router->get('/api/courses/filter', 'Member\\CourseController@filter', 'courses.api.filter');
    $router->get('/api/courses/categories', 'Member\\CourseController@getCategories', 'courses.api.categories');
    $router->get('/api/courses/recommended', 'Member\\CourseController@getRecommended', 'courses.api.recommended');

    // ====== Lesson Routes (Member) ======
    $router->get('/lessons/{id}', 'Member\\LessonController@show', 'lessons.show');
    $router->post('/lessons/{id}/complete', 'Member\\LessonController@markComplete', 'lessons.complete');
    $router->post('/api/lessons/{id}/notes', 'Member\\LessonController@saveNotes', 'lessons.api.notes.save');
    $router->get('/api/lessons/{id}/notes', 'Member\\LessonController@getNotes', 'lessons.api.notes.get');
    $router->get('/api/lessons/{id}/resources', 'Member\\LessonController@getResources', 'lessons.api.resources');
    $router->post('/api/lessons/{id}/progress', 'Member\\LessonController@updateProgress', 'lessons.api.progress');
    $router->get('/api/lessons/{id}/next', 'Member\\LessonController@getNextLesson', 'lessons.api.next');

    // ====== File Uploads ======
    $router->post('/upload', 'FileController@upload', 'files.upload');
    $router->delete('/files/{id}', 'FileController@delete', 'files.delete');
});

// ====== MENTOR ROUTES ======

$router->group(['prefix' => 'mentor', 'middleware' => ['AuthMiddleware', 'RoleMiddleware:mentor,admin']], function($router) {
    
    // Mentor dashboard
    $router->get('/', 'Mentor\\MentorController@dashboard', 'mentor.dashboard');
    
    // Attendance management
    $router->get('/attendance', 'Mentor\\AttendanceController@index', 'mentor.attendance.index');
    $router->get('/attendance/register', 'Mentor\\AttendanceController@register', 'mentor.attendance.register');
    $router->post('/attendance/bulk-signout', 'Mentor\\AttendanceController@bulkSignout', 'mentor.attendance.bulk_signout');
    
    // Member management
    $router->get('/members', 'Mentor\\MemberController@index', 'mentor.members.index');
    $router->get('/members/{id}', 'Mentor\\MemberController@show', 'mentor.members.show');
    $router->get('/members/{id}/progress', 'Mentor\\MemberController@progress', 'mentor.members.progress');
    
    // Reports
    $router->get('/reports', 'Mentor\\ReportController@index', 'mentor.reports.index');
    $router->get('/reports/attendance', 'Mentor\\ReportController@attendance', 'mentor.reports.attendance');
    $router->get('/reports/programs', 'Mentor\\ReportController@programs', 'mentor.reports.programs');
});

// ====== ADMIN ROUTES ======

$router->group(['prefix' => 'admin', 'middleware' => ['AuthMiddleware', 'RoleMiddleware:admin']], function($router) {
    
    // Admin dashboard
    $router->get('/', 'Admin\\AdminController@dashboard', 'admin.dashboard');
    
    // User management
    $router->group(['prefix' => 'users'], function($router) {
        $router->get('/', 'Admin\\UserController@index', 'admin.users.index');
        $router->get('/create', 'Admin\\UserController@create', 'admin.users.create');
        $router->post('/', 'Admin\\UserController@store', 'admin.users.store');
        $router->get('/{id}', 'Admin\\UserController@show', 'admin.users.show');
        $router->get('/{id}/edit', 'Admin\\UserController@edit', 'admin.users.edit');
        $router->put('/{id}', 'Admin\\UserController@update', 'admin.users.update');
        $router->delete('/{id}', 'Admin\\UserController@destroy', 'admin.users.destroy');
    });
    
    // Course management
    $router->group(['prefix' => 'courses'], function($router) {
        $router->get('/', 'Admin\\CourseController@index', 'admin.courses.index');
        $router->get('/create', 'Admin\\CourseController@create', 'admin.courses.create');
        $router->post('/', 'Admin\\CourseController@store', 'admin.courses.store');
        $router->get('/{id}', 'Admin\\CourseController@show', 'admin.courses.show');
        $router->get('/{id}/edit', 'Admin\\CourseController@edit', 'admin.courses.edit');
        $router->put('/{id}', 'Admin\\CourseController@update', 'admin.courses.update');
        $router->post('/{id}/update', 'Admin\\CourseController@update', 'admin.courses.update.post'); // Form POST support
        $router->delete('/{id}', 'Admin\\CourseController@destroy', 'admin.courses.destroy');

        // Course management actions (AJAX)
        $router->post('/{id}/status', 'Admin\\CourseController@updateStatus', 'admin.courses.status.update');
        $router->post('/{id}/featured', 'Admin\\CourseController@toggleFeatured', 'admin.courses.featured.toggle');

        // Hierarchy management (AJAX)
        $router->get('/{id}/modules', 'Admin\\CourseController@getModules', 'admin.courses.modules.get');
        $router->post('/{id}/modules', 'Admin\\CourseController@createModule', 'admin.courses.modules.create');
        $router->get('/{id}/sections', 'Admin\\CourseController@getSections', 'admin.courses.sections.get');
        $router->post('/{id}/sections', 'Admin\\CourseController@createSection', 'admin.courses.sections.create');

        // Lesson management within courses
        $router->get('/{courseId}/lessons', 'Admin\\LessonController@index', 'admin.courses.lessons.index');
        $router->get('/{courseId}/lessons/create', 'Admin\\LessonController@create', 'admin.courses.lessons.create');
        $router->post('/{courseId}/lessons', 'Admin\\LessonController@store', 'admin.courses.lessons.store');
        $router->get('/{courseId}/lessons/{id}/edit', 'Admin\\LessonController@edit', 'admin.courses.lessons.edit');
        $router->put('/{courseId}/lessons/{id}', 'Admin\\LessonController@update', 'admin.courses.lessons.update');
        $router->delete('/{courseId}/lessons/{id}', 'Admin\\LessonController@destroy', 'admin.courses.lessons.destroy');
    });

    // Deprecation monitoring
    $router->group(['prefix' => 'deprecation-monitor'], function($router) {
        $router->get('/', 'Admin\\DeprecationMonitorController@index', 'admin.deprecation.index');
        $router->get('/export', 'Admin\\DeprecationMonitorController@export', 'admin.deprecation.export');
        $router->get('/stats', 'Admin\\DeprecationMonitorController@getStats', 'admin.deprecation.stats');
        $router->get('/recommendations', 'Admin\\DeprecationMonitorController@getRecommendations', 'admin.deprecation.recommendations');
    });

    // Holiday program management
    $router->group(['prefix' => 'programs'], function($router) {
        $router->get('/', 'Admin\\ProgramController@index', 'admin.programs.index');
        $router->get('/create', 'Admin\\ProgramController@create', 'admin.programs.create');
        $router->post('/', 'Admin\\ProgramController@store', 'admin.programs.store');
        $router->get('/{id}', 'Admin\\ProgramController@show', 'admin.programs.show');
        $router->get('/{id}/edit', 'Admin\\ProgramController@edit', 'admin.programs.edit');
        $router->put('/{id}', 'Admin\\ProgramController@update', 'admin.programs.update');
        $router->delete('/{id}', 'Admin\\ProgramController@destroy', 'admin.programs.destroy');

        // Program management actions (AJAX)
        $router->put('/{id}/status', 'Admin\\ProgramController@updateStatus', 'admin.programs.status.update');
        $router->post('/{id}/duplicate', 'Admin\\ProgramController@duplicate', 'admin.programs.duplicate');

        // Registration management
        $router->get('/{id}/registrations', 'Admin\\ProgramController@registrations', 'admin.programs.registrations');
        $router->get('/{id}/registrations/export', 'Admin\\ProgramController@exportRegistrations', 'admin.programs.registrations.export');

        // Registration actions (AJAX)
        $router->put('/{id}/registrations/{attendeeId}/status', 'Admin\\ProgramController@updateRegistrationStatus', 'admin.programs.registrations.status.update');
        $router->put('/{id}/registrations/{attendeeId}/mentor-status', 'Admin\\ProgramController@updateMentorStatus', 'admin.programs.registrations.mentor-status.update');
        $router->post('/{id}/registrations/{attendeeId}/assign-workshop', 'Admin\\ProgramController@assignWorkshop', 'admin.programs.registrations.assign-workshop');
    });
    
    // System settings
    $router->get('/settings', 'Admin\\SettingsController@index', 'admin.settings.index');
    $router->post('/settings', 'Admin\\SettingsController@update', 'admin.settings.update');
    
    // System logs
    $router->get('/logs', 'Admin\\LogController@index', 'admin.logs.index');
    $router->get('/logs/{date}', 'Admin\\LogController@show', 'admin.logs.show');
    
    // Analytics & Reports
    $router->group(['prefix' => 'analytics'], function($router) {
        $router->get('/', 'Admin\\AnalyticsController@index', 'admin.analytics.index');
        $router->get('/users', 'Admin\\AnalyticsController@users', 'admin.analytics.users');
        $router->get('/courses', 'Admin\\AnalyticsController@courses', 'admin.analytics.courses');
        $router->get('/programs', 'Admin\\AnalyticsController@programs', 'admin.analytics.programs');
        $router->get('/attendance', 'Admin\\AnalyticsController@attendance', 'admin.analytics.attendance');
    });

    // Report Management (Admin/Mentor)
    $router->group(['prefix' => 'reports'], function($router) {
        $router->get('/', 'ReportController@index', 'reports.index');
        $router->get('/create', 'ReportController@create', 'reports.create');
        $router->post('/', 'ReportController@store', 'reports.store');
        $router->post('/batch', 'ReportController@batchStore', 'reports.batch.store');
        $router->get('/{id}', 'ReportController@show', 'reports.show');
        $router->get('/{id}/edit', 'ReportController@edit', 'reports.edit');
        $router->put('/{id}', 'ReportController@update', 'reports.update');
        $router->post('/{id}', 'ReportController@update', 'reports.update.post');
        $router->delete('/{id}', 'ReportController@destroy', 'reports.destroy');
        $router->get('/export/pdf', 'ReportController@exportPDF', 'reports.export.pdf');
        $router->get('/export/excel', 'ReportController@exportExcel', 'reports.export.excel');
        $router->get('/api/statistics', 'ReportController@getStatistics', 'reports.api.statistics');
        $router->get('/api/filter', 'ReportController@filter', 'reports.api.filter');
    });

    // Visitor Management (Admin/Mentor)
    $router->group(['prefix' => 'visitors'], function($router) {
        $router->get('/', 'VisitorController@index', 'visitors.index');
        $router->get('/{id}', 'VisitorController@show', 'visitors.show');
        $router->get('/{id}/edit', 'VisitorController@edit', 'visitors.edit');
        $router->put('/{id}', 'VisitorController@update', 'visitors.update');
        $router->post('/{id}', 'VisitorController@update', 'visitors.update.post');
        $router->delete('/{id}', 'VisitorController@destroy', 'visitors.destroy');
        $router->post('/{id}/checkin', 'VisitorController@checkIn', 'visitors.checkin');
        $router->post('/{id}/checkout', 'VisitorController@checkOut', 'visitors.checkout');
        $router->get('/api/statistics', 'VisitorController@getStatistics', 'visitors.api.statistics');
        $router->get('/api/filter', 'VisitorController@filter', 'visitors.api.filter');
        $router->get('/api/search', 'VisitorController@search', 'visitors.api.search');
        $router->get('/export/pdf', 'VisitorController@exportPDF', 'visitors.export.pdf');
        $router->get('/export/excel', 'VisitorController@exportExcel', 'visitors.export.excel');
    });
});

return $router;