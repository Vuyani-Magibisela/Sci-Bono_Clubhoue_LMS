# Course Controller Consolidation Plan
**Date:** November 27, 2025
**Status:** Analysis Complete - Ready for Implementation

---

## Executive Summary

**Problem:** Three CourseController files exist with overlapping functionality, creating confusion and maintenance issues.

**Solution:** Consolidate into a single modern `Admin\CourseController` extending `BaseController` with clear separation of concerns.

**Impact:** ~1,875 lines of code to analyze, consolidate into ~600-700 lines for admin functionality.

---

## Current State Analysis

### File Inventory

| File | Lines | Purpose | Status | Namespace |
|------|-------|---------|--------|-----------|
| `/app/Controllers/Admin/CourseController.php` | 1,291 | Full course hierarchy management | Active | None (legacy) |
| `/app/Controllers/Admin/AdminCourseController.php` | 328 | Simplified admin interface | Active | None |
| `/app/Controllers/CourseController.php` | 256 | Student-facing course operations | Active | None |
| **TOTAL** | **1,875** | - | - | - |

---

## Method Comparison Matrix

### Admin/CourseController.php (1,291 lines) - COMPREHENSIVE

**Core CRUD (5 methods):**
- `getAllCourses($filters)` - Get all courses with filtering
- `getCourseDetails($courseId, $includeHierarchy)` - Get detailed course info
- `createCourse($courseData)` - Create course with validation
- `updateCourse($courseId, $courseData)` - Update course
- `deleteCourse($courseId)` - Delete course (checks enrollments)

**Module Management (8 methods):**
- `getCourseModules($courseId)` - Get all modules
- `createModule($courseId, $moduleData)` - Create module
- `updateModule($moduleId, $moduleData)` - Update module
- `deleteModule($moduleId, $userId)` - Delete module with checks
- `softDeleteModule($moduleId, $userId)` - Soft delete
- `deleteMultipleModules($moduleIds, $userId)` - Bulk delete
- `getModuleDetails($moduleId)` - Get module details
- `updateModuleOrder($moduleOrders)` - Reorder modules

**Lesson Management (6 methods):**
- `createLesson($lessonData)` - Create lesson
- `createLessonSection($lessonId, $sectionData)` - Create section
- `updateLesson($lessonId, $lessonData)` - Update lesson
- `deleteLesson($lessonId)` - Delete lesson
- `getLessonDetails($lessonId)` - Get lesson details
- `updateLessonOrder($lessonOrders)` - Reorder lessons

**Activity Management (8 methods):**
- `createActivity($activityData)` - Create activity
- `updateActivity($activityId, $activityData)` - Update activity
- `deleteActivity($activityId)` - Delete activity
- `getActivities($filters)` - Get activities
- `getActivityDetails($activityId)` - Get activity details
- `updateActivityOrder($activityOrders)` - Reorder activities
- `createSkillActivity($skillActivityData)` - Create skill activity
- `getAllSkillActivities($filters)` - Get all skill activities
- `getSkillActivityDetails($skillActivityId)` - Get skill activity
- `updateSkillActivity($skillActivityId, $skillActivityData)` - Update skill activity
- `deleteSkillActivity($skillActivityId)` - Delete skill activity

**Enrollment Management (3 methods):**
- `enrollUser($userId, $courseId)` - Enroll user
- `getUserProgress($userId, $courseId)` - Get progress
- `updateUserProgress($progressData)` - Update progress

**Validation (5+ private methods):**
- `validateCourseData($data, $courseId)` - Validate course
- `validateModuleData($data)` - Validate module
- `validateLessonData($data)` - Validate lesson
- `canUserDeleteModule($userId, $module)` - Permission check
- `checkModuleDependencies($moduleId)` - Check dependencies
- `logModuleDeletion($moduleId, $module, $userId)` - Logging

**Helper Methods:**
- `generateCourseCode($title, $type)` - Generate code
- `setDefaultCourseValues($courseData)` - Set defaults
- `createDefaultCourseStructure($courseId, $type)` - Create structure
- `getEnrollmentCount($courseId)` - Count enrollments

**Total Methods:** ~43+ methods

---

### Admin/AdminCourseController.php (328 lines) - SIMPLIFIED

**Core CRUD (5 methods):**
- `getAllCourses()` - Get all courses (no filters)
- `getCourseDetails($courseId)` - Get course details
- `createCourse($courseData)` - Create with CSRF validation
- `updateCourse($courseId, $courseData)` - Update with CSRF validation
- `deleteCourse($courseId)` - Delete course

**Status Management (2 methods):**
- `updateCourseStatus($courseId, $status)` - Update status (draft/published)
- `toggleFeatured($courseId, $featured)` - Toggle featured flag

**Section Management (5 methods):**
- `getCourseSections($courseId)` - Get sections
- `createSection($courseId, $sectionData)` - Create section
- `updateSection($sectionId, $sectionData)` - Update section
- `deleteSection($sectionId)` - Delete section
- `updateSectionOrder($sectionOrders)` - Reorder sections

**Utilities (3 methods):**
- `sanitizeCourseData($courseData)` - Sanitize input
- `formatCourseType($type)` - Format course type
- `getDifficultyClass($level)` - Get difficulty CSS class

**Total Methods:** 15 methods

**Key Features:**
- ✅ CSRF validation on create/update
- ✅ Data sanitization
- ✅ Error logging
- ❌ No namespace
- ❌ Doesn't extend BaseController

---

### CourseController.php (256 lines) - STUDENT-FACING

**Core Operations (3 methods):**
- `getAllCourses()` - Get all courses
- `getCourseDetails($courseId)` - Get course details
- `getCourseSections($courseId)` - Get sections

**Enrollment (4 methods):**
- `isUserEnrolled($userId, $courseId)` - Check enrollment
- `enrollUser($userId, $courseId)` - Enroll user
- `getUserProgress($userId, $courseId)` - Get progress
- `getUserEnrollments($userId)` - Get user's enrollments

**Progress Tracking (2 methods):**
- `isLessonCompleted($userId, $lessonId)` - Check completion
- `calculateTotalDuration($courseId)` - Calculate duration

**Discovery (2 methods):**
- `getFeaturedCourses()` - Get featured
- `getRecommendedCourses($userId)` - Get recommendations

**Utilities (4 methods):**
- `countLessons($courseId, $userId)` - Count lessons
- `formatCourseType($type)` - Format type
- `getDifficultyClass($level)` - Get CSS class
- `getCourseDataForView($courseId, $userId)` - Prepare view data

**Total Methods:** 15 methods

**Purpose:** Student-facing operations (enrollment, progress, discovery)

---

## Consolidation Strategy

### Decision: Create Two Controllers

**1. `Admin\CourseController`** (Admin operations)
- Extends `BaseController`
- Namespace: `Admin`
- Purpose: Admin course management (CRUD, hierarchy)
- Source: Consolidate from Admin/CourseController.php + AdminCourseController.php
- Estimated Lines: 600-700 lines

**2. Keep `CourseController.php`** (Student operations)
- Separate concern: Student-facing operations
- Purpose: Enrollment, progress, discovery
- No changes needed
- Lines: 256 (unchanged)

### Admin\CourseController - Consolidation Plan

**Core CRUD Methods (7 RESTful methods):**

1. **`index()`** - List courses
   - Source: `getAllCourses()` from Admin/CourseController.php
   - Add: Search, filter, pagination
   - Return: `view('admin.courses.index', $data, 'admin')`

2. **`create()`** - Show create form
   - New method
   - Return: `view('admin.courses.create', $data, 'admin')`

3. **`store()`** - Process creation
   - Source: `createCourse()` from Admin/CourseController.php
   - Add: CSRF validation from AdminCourseController
   - Add: BaseController helpers
   - Return: `redirectWithSuccess()`

4. **`show($id)`** - View course details
   - Source: `getCourseDetails()` from Admin/CourseController.php
   - Return: `view('admin.courses.show', $data, 'admin')`

5. **`edit($id)`** - Show edit form
   - New method
   - Load course data
   - Return: `view('admin.courses.edit', $data, 'admin')`

6. **`update($id)`** - Process update
   - Source: `updateCourse()` from Admin/CourseController.php
   - Add: CSRF validation
   - Return: `redirectWithSuccess()`

7. **`destroy($id)`** - Delete course
   - Source: `deleteCourse()` from Admin/CourseController.php
   - Add: CSRF validation
   - Return: `redirectWithSuccess()`

**Status Management Methods (2 methods):**

8. **`updateStatus($id)`** - Update status
   - Source: `updateCourseStatus()` from AdminCourseController
   - For AJAX calls from enhanced view

9. **`toggleFeatured($id)`** - Toggle featured
   - Source: `toggleFeatured()` from AdminCourseController
   - For AJAX calls

**Module Management Methods (keep from Admin/CourseController.php):**
- `getCourseModules($courseId)`
- `createModule($courseId, $moduleData)`
- `updateModule($moduleId, $moduleData)`
- `deleteModule($moduleId)`
- `updateModuleOrder($moduleOrders)`

**Lesson Management Methods (keep from Admin/CourseController.php):**
- `createLesson($lessonData)`
- `updateLesson($lessonId, $lessonData)`
- `deleteLesson($lessonId)`
- `updateLessonOrder($lessonOrders)`

**Activity Management Methods (keep from Admin/CourseController.php):**
- `createActivity($activityData)`
- `updateActivity($activityId, $activityData)`
- `deleteActivity($activityId)`
- `updateActivityOrder($activityOrders)`

**Private Helper Methods:**
- `validateCourseData()` - Merge validation from both controllers
- `sanitizeCourseData()` - From AdminCourseController
- `generateCourseCode()` - From Admin/CourseController
- `checkEnrollmentCount()` - From Admin/CourseController

---

## Implementation Steps

### Step 1: Backup Existing Files
```bash
cp /app/Controllers/Admin/CourseController.php /app/Controllers/Admin/CourseController.backup.php
cp /app/Controllers/Admin/AdminCourseController.php /app/Controllers/Admin/AdminCourseController.backup.php
```

### Step 2: Create New Admin\CourseController

**File:** `/app/Controllers/Admin/CourseController.php`

**Structure:**
```php
<?php
/**
 * Admin\CourseController
 *
 * Modern MVC course management controller
 * Phase 3 Week 5 - Consolidated from legacy controllers
 */

namespace Admin;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Admin/CourseModel.php';
require_once __DIR__ . '/../../../core/CSRF.php';

class CourseController extends \BaseController {
    private $courseModel;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->courseModel = new CourseModel($conn);
    }

    // =================== RESTful CRUD Methods ===================

    public function index() { }
    public function create() { }
    public function store() { }
    public function show($id) { }
    public function edit($id) { }
    public function update($id) { }
    public function destroy($id) { }

    // =================== AJAX Methods ===================

    public function updateStatus($id) { }
    public function toggleFeatured($id) { }

    // =================== Hierarchy Management ===================

    // Modules
    public function getCourseModules($courseId) { }
    public function createModule($courseId, $moduleData) { }
    // ... more methods

    // Lessons
    public function createLesson($lessonData) { }
    // ... more methods

    // Activities
    public function createActivity($activityData) { }
    // ... more methods

    // =================== Private Helpers ===================

    private function validateCourseData($data, $courseId = null) { }
    private function sanitizeCourseData($data) { }
    private function generateCourseCode($title, $type) { }
}
```

### Step 3: Implement Core CRUD Methods

**Priority:** Implement 7 RESTful methods first
**Estimated Time:** 3-4 hours
**Lines:** ~300-400 lines

### Step 4: Port Hierarchy Methods

**Priority:** Port module/lesson/activity methods
**Estimated Time:** 2-3 hours
**Lines:** ~200-300 lines

### Step 5: Add Deprecation Handlers

**AdminCourseController.php becomes a proxy:**
```php
<?php
/**
 * @deprecated Use Admin\CourseController instead
 */
class AdminCourseController {
    private $newController;

    public function __construct($conn) {
        trigger_error('AdminCourseController is deprecated. Use Admin\CourseController', E_USER_DEPRECATED);
        error_log('DEPRECATED: AdminCourseController called from ' . debug_backtrace()[1]['file']);

        // Forward to new controller
        $this->newController = new \Admin\CourseController($conn);
    }

    public function __call($method, $args) {
        return $this->newController->$method(...$args);
    }
}
```

### Step 6: Update Routes

**File:** `/routes/web.php`

Ensure routes point to proper namespace:
```php
// Admin Course Routes (lines 126-142)
$router->get('/admin/courses', 'Admin\\CourseController@index');
$router->get('/admin/courses/create', 'Admin\\CourseController@create');
$router->post('/admin/courses', 'Admin\\CourseController@store');
$router->get('/admin/courses/{id}', 'Admin\\CourseController@show');
$router->get('/admin/courses/{id}/edit', 'Admin\\CourseController@edit');
$router->post('/admin/courses/{id}/update', 'Admin\\CourseController@update');
$router->post('/admin/courses/{id}/delete', 'Admin\\CourseController@destroy');
```

### Step 7: Test All Operations

**Test Checklist:**
- [ ] List courses (index)
- [ ] Create course (store)
- [ ] View course (show)
- [ ] Edit course (update)
- [ ] Delete course (destroy)
- [ ] Module CRUD
- [ ] Lesson CRUD
- [ ] Activity CRUD
- [ ] Status updates
- [ ] Featured toggle

---

## Method Mapping Table

| Operation | Old Location | New Method | Lines | Priority |
|-----------|--------------|------------|-------|----------|
| List courses | Admin/CourseController@getAllCourses | index() | ~40 | High |
| Create form | N/A | create() | ~20 | High |
| Create course | Admin/CourseController@createCourse | store() | ~60 | High |
| View course | Admin/CourseController@getCourseDetails | show($id) | ~30 | High |
| Edit form | N/A | edit($id) | ~25 | High |
| Update course | Admin/CourseController@updateCourse | update($id) | ~50 | High |
| Delete course | Admin/CourseController@deleteCourse | destroy($id) | ~30 | High |
| Update status | AdminCourseController@updateCourseStatus | updateStatus($id) | ~25 | Medium |
| Toggle featured | AdminCourseController@toggleFeatured | toggleFeatured($id) | ~20 | Medium |
| Get modules | Admin/CourseController@getCourseModules | getCourseModules($id) | ~10 | Medium |
| Create module | Admin/CourseController@createModule | createModule() | ~30 | Medium |
| Update module | Admin/CourseController@updateModule | updateModule($id) | ~25 | Medium |
| Delete module | Admin/CourseController@deleteModule | deleteModule($id) | ~40 | Medium |
| Create lesson | Admin/CourseController@createLesson | createLesson() | ~30 | Medium |
| Update lesson | Admin/CourseController@updateLesson | updateLesson($id) | ~25 | Medium |
| Delete lesson | Admin/CourseController@deleteLesson | deleteLesson($id) | ~25 | Medium |
| Create activity | Admin/CourseController@createActivity | createActivity() | ~30 | Low |
| Update activity | Admin/CourseController@updateActivity | updateActivity($id) | ~25 | Low |
| Delete activity | Admin/CourseController@deleteActivity | deleteActivity($id) | ~25 | Low |

---

## Estimated Effort

| Task | Hours | Complexity |
|------|-------|------------|
| Analysis (Done) | 1 | Medium |
| Create new controller structure | 0.5 | Low |
| Implement 7 RESTful methods | 3-4 | Medium |
| Port hierarchy methods | 2-3 | Medium |
| Add validation/helpers | 1-2 | Medium |
| Create deprecation handlers | 0.5 | Low |
| Update routes | 0.5 | Low |
| Testing | 2-3 | Medium |
| **TOTAL** | **10-15 hours** | **Medium-High** |

---

## Success Criteria

- [x] Analysis complete
- [ ] New Admin\CourseController created with proper namespace
- [ ] Extends BaseController
- [ ] 7 RESTful methods implemented
- [ ] CSRF protection on all mutations
- [ ] Role-based access (admin only)
- [ ] Hierarchy methods ported
- [ ] Deprecation handlers in place
- [ ] Routes updated
- [ ] All CRUD operations tested
- [ ] Zero breaking changes

---

## Risk Mitigation

**Risk 1: Breaking Existing Functionality**
- **Mitigation:** Keep backups, add deprecation proxies, test thoroughly

**Risk 2: Missing Dependencies**
- **Mitigation:** Careful analysis of all method calls, dependency injection

**Risk 3: Complex Hierarchy Logic**
- **Mitigation:** Port methods as-is first, refactor later if needed

---

## Next Steps

1. ✅ Analysis complete (this document)
2. ⏳ Create new consolidated Admin\CourseController
3. ⏳ Implement 7 RESTful methods
4. ⏳ Port hierarchy management methods
5. ⏳ Add deprecation handlers
6. ⏳ Test all operations
7. ⏳ Update views to use new controller

**Recommended Approach:** Implement in phases (Core CRUD first, then hierarchy)

---

**Document Status:** Complete - Ready for Implementation
**Next Action:** Begin implementation of new Admin\CourseController
**Estimated Completion:** 10-15 hours of focused work
