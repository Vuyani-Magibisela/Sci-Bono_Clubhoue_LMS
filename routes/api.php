<?php
/**
 * API Routes - Define all API endpoints
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../core/ModernRouter.php';

$router = new Router('/Sci-Bono_Clubhoue_LMS');

// ====== PUBLIC API ROUTES ======

$router->group(['prefix' => 'api/v1', 'middleware' => ['ApiMiddleware']], function($router) {

    // Health check
    $router->get('/health', 'Api\\HealthController@check', 'api.health');

    // API Documentation (OpenAPI/Swagger)
    $router->get('/openapi.json', 'Api\\DocsController@serveJson', 'api.docs.json');
    $router->get('/openapi.yaml', 'Api\\DocsController@serveYaml', 'api.docs.yaml');
    $router->get('/docs', 'Api\\DocsController@swaggerUi', 'api.docs.swagger');
    $router->get('/redoc', 'Api\\DocsController@redoc', 'api.docs.redoc');
    $router->get('/docs/info', 'Api\\DocsController@info', 'api.docs.info');

    // Version information
    $router->get('/versions', 'Api\\VersionController@index', 'api.versions.index');
    $router->get('/versions/{version}', 'Api\\VersionController@show', 'api.versions.show');

    // Authentication
    $router->post('/auth/login', 'Api\\AuthController@login', 'api.auth.login');
    $router->post('/auth/logout', 'Api\\AuthController@logout', 'api.auth.logout');
    $router->post('/auth/refresh', 'Api\\AuthController@refresh', 'api.auth.refresh');

    // Password reset
    $router->post('/auth/forgot-password', 'Api\\AuthController@forgotPassword', 'api.auth.forgot');
    $router->post('/auth/reset-password', 'Api\\AuthController@resetPassword', 'api.auth.reset');

    // Public attendance (with authentication)
    $router->post('/attendance/signin', 'Api\\AttendanceController@signin', 'api.attendance.signin');
    $router->post('/attendance/signout', 'Api\\AttendanceController@signout', 'api.attendance.signout');
    $router->get('/attendance/search', 'Api\\AttendanceController@searchUsers', 'api.attendance.search');
    $router->get('/attendance/stats', 'Api\\AttendanceController@stats', 'api.attendance.stats');

    // Public course browsing (no authentication required)
    $router->get('/courses/featured', 'Api\\CourseController@featured', 'api.courses.featured');

    // Global search and filters (no authentication required)
    $router->get('/search', 'Api\\SearchController@search', 'api.search');
    $router->get('/categories', 'Api\\SearchController@categories', 'api.categories');
    $router->get('/filters/options', 'Api\\SearchController@filterOptions', 'api.filters.options');
});

// ====== AUTHENTICATED API ROUTES ======

$router->group(['prefix' => 'api/v1', 'middleware' => ['ApiMiddleware', 'AuthMiddleware']], function($router) {
    
    // User profile
    $router->get('/profile', 'Api\\UserController@profile', 'api.profile.show');
    $router->put('/profile', 'Api\\UserController@updateProfile', 'api.profile.update');
    $router->put('/password', 'Api\\UserController@updatePassword', 'api.profile.password');

    // Public course browsing and search
    $router->get('/courses', 'Api\\CourseController@index', 'api.courses.index');
    $router->get('/courses/search', 'Api\\CourseController@search', 'api.courses.search');
    $router->get('/courses/{id}', 'Api\\CourseController@show', 'api.courses.show');

    // Course enrollment (requires authentication)
    $router->post('/courses/{id}/enroll', 'Api\\EnrollmentController@enroll', 'api.courses.enroll');
    $router->delete('/courses/{id}/enroll', 'Api\\EnrollmentController@unenroll', 'api.courses.unenroll');
    $router->get('/user/courses', 'Api\\EnrollmentController@userCourses', 'api.user.courses');
    $router->get('/user/courses/{id}/progress', 'Api\\EnrollmentController@courseProgress', 'api.user.courses.progress');

    // Lesson viewing and progress tracking
    $router->get('/courses/{courseId}/lessons', 'Api\\LessonController@getCourseLessons', 'api.courses.lessons');
    $router->get('/lessons/{id}', 'Api\\LessonController@show', 'api.lessons.show');
    $router->post('/lessons/{id}/complete', 'Api\\LessonController@markComplete', 'api.lessons.complete');
    $router->get('/lessons/{id}/progress', 'Api\\LessonController@getLessonProgress', 'api.lessons.progress');
    
    // Holiday programs
    $router->get('/programs', 'Api\\ProgramController@index', 'api.programs.index');
    $router->get('/programs/{id}', 'Api\\ProgramController@show', 'api.programs.show');
    $router->post('/programs/{id}/register', 'Api\\ProgramController@register', 'api.programs.register');
    $router->get('/programs/{id}/workshops', 'Api\\ProgramController@workshops', 'api.programs.workshops');
    
    // File uploads
    $router->post('/files/upload', 'Api\\FileController@upload', 'api.files.upload');
    $router->delete('/files/{id}', 'Api\\FileController@delete', 'api.files.delete');
    
    // Dashboard data
    $router->get('/dashboard/stats', 'Api\\DashboardController@stats', 'api.dashboard.stats');
    $router->get('/dashboard/activities', 'Api\\DashboardController@activities', 'api.dashboard.activities');
});

// ====== MENTOR API ROUTES ======

$router->group(['prefix' => 'api/v1/mentor', 'middleware' => ['ApiMiddleware', 'AuthMiddleware', 'RoleMiddleware:mentor,admin']], function($router) {
    
    // Member management
    $router->get('/members', 'Api\\Mentor\\MemberController@index', 'api.mentor.members.index');
    $router->get('/members/{id}', 'Api\\Mentor\\MemberController@show', 'api.mentor.members.show');
    $router->get('/members/{id}/progress', 'Api\\Mentor\\MemberController@progress', 'api.mentor.members.progress');
    
    // Attendance management
    $router->get('/attendance/recent', 'Api\\Mentor\\AttendanceController@recent', 'api.mentor.attendance.recent');
    $router->post('/attendance/bulk-signout', 'Api\\Mentor\\AttendanceController@bulkSignout', 'api.mentor.attendance.bulk_signout');
    
    // Reports
    $router->get('/reports/attendance', 'Api\\Mentor\\ReportController@attendance', 'api.mentor.reports.attendance');
    $router->get('/reports/programs', 'Api\\Mentor\\ReportController@programs', 'api.mentor.reports.programs');
});

// ====== ADMIN API ROUTES ======

$router->group(['prefix' => 'api/v1/admin', 'middleware' => ['ApiMiddleware', 'AuthMiddleware', 'RoleMiddleware:admin']], function($router) {
    
    // User management
    $router->get('/users', 'Api\\Admin\\UserController@index', 'api.admin.users.index');
    $router->post('/users', 'Api\\Admin\\UserController@store', 'api.admin.users.store');
    $router->get('/users/{id}', 'Api\\Admin\\UserController@show', 'api.admin.users.show');
    $router->put('/users/{id}', 'Api\\Admin\\UserController@update', 'api.admin.users.update');
    $router->delete('/users/{id}', 'Api\\Admin\\UserController@destroy', 'api.admin.users.destroy');
    
    // Course management
    $router->get('/courses', 'Api\\Admin\\CourseController@index', 'api.admin.courses.index');
    $router->post('/courses', 'Api\\Admin\\CourseController@store', 'api.admin.courses.store');
    $router->get('/courses/{id}', 'Api\\Admin\\CourseController@show', 'api.admin.courses.show');
    $router->put('/courses/{id}', 'Api\\Admin\\CourseController@update', 'api.admin.courses.update');
    $router->delete('/courses/{id}', 'Api\\Admin\\CourseController@destroy', 'api.admin.courses.destroy');

    // Course management actions (AJAX)
    $router->post('/courses/{id}/status', 'Api\\Admin\\CourseController@updateStatus', 'api.admin.courses.status.update');
    $router->post('/courses/{id}/featured', 'Api\\Admin\\CourseController@toggleFeatured', 'api.admin.courses.featured.toggle');
    $router->post('/courses/{id}/image', 'Api\\Admin\\CourseController@uploadImage', 'api.admin.courses.image.upload');

    // Course hierarchy management (AJAX)
    $router->get('/courses/{id}/modules', 'Api\\Admin\\CourseController@getModules', 'api.admin.courses.modules.get');
    $router->post('/courses/{id}/modules', 'Api\\Admin\\CourseController@createModule', 'api.admin.courses.modules.create');
    $router->get('/courses/{id}/sections', 'Api\\Admin\\CourseController@getSections', 'api.admin.courses.sections.get');
    $router->post('/courses/{id}/sections', 'Api\\Admin\\CourseController@createSection', 'api.admin.courses.sections.create');

    // Lesson management
    $router->post('/courses/{id}/lessons', 'Api\\Admin\\LessonController@store', 'api.admin.lessons.store');
    $router->get('/lessons/{id}', 'Api\\Admin\\LessonController@show', 'api.admin.lessons.show');
    $router->put('/lessons/{id}', 'Api\\Admin\\LessonController@update', 'api.admin.lessons.update');
    $router->delete('/lessons/{id}', 'Api\\Admin\\LessonController@destroy', 'api.admin.lessons.destroy');
    $router->post('/lessons/{id}/content', 'Api\\Admin\\LessonController@uploadContent', 'api.admin.lessons.content.upload');
    $router->get('/sections/{id}/lessons', 'Api\\Admin\\LessonController@getSectionLessons', 'api.admin.sections.lessons.get');

    // Program management
    $router->get('/programs', 'Api\\Admin\\ProgramController@index', 'api.admin.programs.index');
    $router->post('/programs', 'Api\\Admin\\ProgramController@store', 'api.admin.programs.store');
    $router->get('/programs/{id}', 'Api\\Admin\\ProgramController@show', 'api.admin.programs.show');
    $router->put('/programs/{id}', 'Api\\Admin\\ProgramController@update', 'api.admin.programs.update');
    $router->delete('/programs/{id}', 'Api\\Admin\\ProgramController@destroy', 'api.admin.programs.destroy');
    
    // Analytics
    $router->get('/analytics/overview', 'Api\\Admin\\AnalyticsController@overview', 'api.admin.analytics.overview');
    $router->get('/analytics/users', 'Api\\Admin\\AnalyticsController@users', 'api.admin.analytics.users');
    $router->get('/analytics/courses', 'Api\\Admin\\AnalyticsController@courses', 'api.admin.analytics.courses');
    $router->get('/analytics/programs', 'Api\\Admin\\AnalyticsController@programs', 'api.admin.analytics.programs');
    
    // System management
    $router->get('/system/logs', 'Api\\Admin\\SystemController@logs', 'api.admin.system.logs');
    $router->post('/system/cache/clear', 'Api\\Admin\\SystemController@clearCache', 'api.admin.system.cache.clear');
    $router->get('/system/stats', 'Api\\Admin\\SystemController@stats', 'api.admin.system.stats');
});

return $router;