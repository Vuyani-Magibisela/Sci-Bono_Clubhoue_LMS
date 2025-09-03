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
$router->post('/login', 'AuthController@login', 'login.process');
$router->get('/signup', 'AuthController@showSignup', 'signup.show');
$router->post('/signup', 'AuthController@signup', 'signup.process');
$router->post('/logout', 'AuthController@logout', 'logout');

// Password reset routes
$router->get('/forgot-password', 'AuthController@showForgotPassword', 'password.forgot');
$router->post('/forgot-password', 'AuthController@sendResetLink', 'password.reset.send');
$router->get('/reset-password/{token}', 'AuthController@showResetForm', 'password.reset.form');
$router->post('/reset-password', 'AuthController@resetPassword', 'password.reset.process');

// Attendance register (public access)
$router->get('/attendance', 'AttendanceController@index', 'attendance.index');

// ====== AUTHENTICATED ROUTES ======

$router->group(['middleware' => ['AuthMiddleware']], function($router) {
    
    // Dashboard
    $router->get('/dashboard', 'DashboardController@index', 'dashboard');
    
    // Profile routes
    $router->get('/profile', 'UserController@profile', 'profile.show');
    $router->post('/profile', 'UserController@updateProfile', 'profile.update');
    $router->get('/profile/edit', 'UserController@editProfile', 'profile.edit');
    
    // Course routes
    $router->get('/courses', 'CourseController@index', 'courses.index');
    $router->get('/courses/{id}', 'CourseController@show', 'courses.show');
    $router->post('/courses/{id}/enroll', 'CourseController@enroll', 'courses.enroll');
    
    // Lesson routes
    $router->get('/lessons/{id}', 'LessonController@show', 'lessons.show');
    $router->post('/lessons/{id}/complete', 'LessonController@markComplete', 'lessons.complete');
    
    // Holiday Programs
    $router->group(['prefix' => 'programs'], function($router) {
        $router->get('/', 'HolidayProgramController@index', 'programs.index');
        $router->get('/{id}', 'HolidayProgramController@show', 'programs.show');
        $router->post('/{id}/register', 'HolidayProgramController@register', 'programs.register');
        $router->get('/{id}/workshops', 'HolidayProgramController@workshops', 'programs.workshops');
    });
    
    // Settings
    $router->get('/settings', 'SettingsController@index', 'settings.index');
    $router->post('/settings', 'SettingsController@update', 'settings.update');
    
    // File uploads
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
        $router->delete('/{id}', 'Admin\\CourseController@destroy', 'admin.courses.destroy');
        
        // Lesson management within courses
        $router->get('/{courseId}/lessons', 'Admin\\LessonController@index', 'admin.courses.lessons.index');
        $router->get('/{courseId}/lessons/create', 'Admin\\LessonController@create', 'admin.courses.lessons.create');
        $router->post('/{courseId}/lessons', 'Admin\\LessonController@store', 'admin.courses.lessons.store');
        $router->get('/{courseId}/lessons/{id}/edit', 'Admin\\LessonController@edit', 'admin.courses.lessons.edit');
        $router->put('/{courseId}/lessons/{id}', 'Admin\\LessonController@update', 'admin.courses.lessons.update');
        $router->delete('/{courseId}/lessons/{id}', 'Admin\\LessonController@destroy', 'admin.courses.lessons.destroy');
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
        
        // Registration management
        $router->get('/{id}/registrations', 'Admin\\ProgramController@registrations', 'admin.programs.registrations');
        $router->post('/{id}/registrations/export', 'Admin\\ProgramController@exportRegistrations', 'admin.programs.registrations.export');
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
});

return $router;