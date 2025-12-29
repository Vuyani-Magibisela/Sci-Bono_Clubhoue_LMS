# Admin Panel Testing Checklist
**Phase 3 Week 5: Admin Panel Migration - Course Management**
**Created:** November 27, 2025
**Status:** Ready for Manual Testing

## Overview

This document provides a comprehensive testing checklist for the admin panel, specifically focusing on the Week 5 Course Management implementation. All tests should be performed with admin-level access.

---

## Prerequisites

### Environment Setup
- [ ] XAMPP/LAMP stack running
- [ ] Database `accounts` exists and is populated
- [ ] PHP version 7.4+ confirmed
- [ ] MySQL version 5.7+ confirmed
- [ ] All Composer dependencies installed
- [ ] BASE_URL configured correctly in `config/config.php`

### Test User Accounts
- [ ] Admin account available (user_type: 'admin')
- [ ] Mentor account available (user_type: 'mentor')
- [ ] Member account available (user_type: 'member')
- [ ] Test passwords documented and working

### Browser Requirements
- [ ] Modern browser (Chrome, Firefox, Safari, Edge)
- [ ] Browser console open for error monitoring
- [ ] Network tab open for API request monitoring

---

## 1. Authentication & Authorization Tests

### 1.1 Admin Login
- [ ] Can login with valid admin credentials
- [ ] Cannot login with invalid credentials
- [ ] Session persists across page reloads
- [ ] User redirected to `/admin` dashboard after login

### 1.2 Role-Based Access Control
- [ ] Admin can access `/admin` routes
- [ ] Mentor cannot access `/admin` routes (403 error)
- [ ] Member cannot access `/admin` routes (403 error)
- [ ] Unauthenticated users redirected to login

### 1.3 CSRF Protection
- [ ] All POST requests include CSRF token
- [ ] Requests without valid CSRF token are rejected (403 error)
- [ ] CSRF token refreshes on page reload
- [ ] Form submissions fail with expired/invalid token

---

## 2. Admin Dashboard Tests

### 2.1 Dashboard Access
- [ ] Navigate to `/admin` successfully
- [ ] Dashboard layout loads with admin header
- [ ] Navigation menu visible and functional
- [ ] User info displayed correctly (name, role)

### 2.2 Dashboard Statistics
- [ ] Total users count displays correctly
- [ ] Total courses count displays correctly
- [ ] Active programs count displays correctly
- [ ] Today's attendance count displays correctly

### 2.3 Dashboard Navigation
- [ ] "Users" link navigates to `/admin/users`
- [ ] "Courses" link navigates to `/admin/courses`
- [ ] "Programs" link navigates to `/admin/programs`
- [ ] "Settings" link navigates to `/admin/settings`

---

## 3. User Management Tests (Week 4 Implementation)

### 3.1 User List (`/admin/users`)
- [ ] User list page loads successfully
- [ ] All users displayed in table format
- [ ] User statistics cards show correct counts
- [ ] Search functionality works (by name, email, ID)
- [ ] Filter by role works (admin, mentor, member, etc.)
- [ ] Pagination works (if > 25 users)
- [ ] "Create User" button visible and functional

### 3.2 User Creation (`/admin/users/create`)
- [ ] Form loads with all required fields
- [ ] Client-side validation works (required fields)
- [ ] Server-side validation works (duplicate email)
- [ ] Password minimum length enforced (8 characters)
- [ ] Email format validation works
- [ ] Role dropdown shows all options
- [ ] User created successfully with valid data
- [ ] Redirect to user list after creation
- [ ] Success message displayed

### 3.3 User Details (`/admin/users/{id}`)
- [ ] User details page loads successfully
- [ ] All user information displayed correctly
- [ ] Profile image displays (if available)
- [ ] Role badge shows correct color
- [ ] "Edit User" button functional
- [ ] "Delete User" button visible (if allowed)
- [ ] Breadcrumb navigation works

### 3.4 User Edit (`/admin/users/{id}/edit`)
- [ ] Edit form loads with pre-populated data
- [ ] All fields editable except user ID
- [ ] Password field optional (only change if filled)
- [ ] Email uniqueness validated (excluding current user)
- [ ] Role can be changed
- [ ] User updated successfully
- [ ] Redirect to user details after update
- [ ] Success message displayed

### 3.5 User Deletion (`/admin/users/{id}`)
- [ ] Delete confirmation dialog appears
- [ ] User deleted successfully with confirmation
- [ ] Deletion cancelled if user declines
- [ ] Cannot delete own admin account
- [ ] Redirect to user list after deletion
- [ ] Success message displayed

---

## 4. Course Management Tests (Week 5 Implementation)

### 4.1 Course List (`/admin/courses`)
- [ ] Course list page loads successfully
- [ ] All courses displayed in table format
- [ ] Course statistics cards show correct counts:
  - [ ] Total courses
  - [ ] Active courses
  - [ ] Total enrollments
  - [ ] Featured courses
- [ ] Search functionality works (by title, code, description)
- [ ] Filter by type works (full_course, short_course, lesson, skill_activity)
- [ ] Filter by status works (draft, active, archived)
- [ ] Filter by difficulty works (Beginner, Intermediate, Advanced)
- [ ] Multiple filters work together
- [ ] Pagination works (25 courses per page)
- [ ] "Create Course" button visible and functional
- [ ] Course cards show:
  - [ ] Course title
  - [ ] Course code
  - [ ] Type badge
  - [ ] Status badge
  - [ ] Difficulty level
  - [ ] Enrollment count
  - [ ] Featured badge (if applicable)
  - [ ] Cover image (if available)
- [ ] "View" button on each card works
- [ ] "Edit" button on each card works
- [ ] Empty state displays when no courses match filters

### 4.2 Course Creation (`/admin/courses/create`)
- [ ] Create form loads successfully
- [ ] All form sections visible:
  - [ ] Basic Information
  - [ ] Cover Image
  - [ ] Publication Settings
- [ ] Required fields marked with *
- [ ] Course title field validates (min 3 characters)
- [ ] Type dropdown shows all options:
  - [ ] Full Course
  - [ ] Short Course
  - [ ] Lesson
  - [ ] Skill Activity
- [ ] Difficulty dropdown shows all levels:
  - [ ] Beginner
  - [ ] Intermediate
  - [ ] Advanced
- [ ] Duration field accepts text input
- [ ] Description textarea validates (min 10 characters)
- [ ] Image upload field accepts only images (JPEG, PNG, GIF, WebP)
- [ ] Image size validation works (max 2MB)
- [ ] Image preview displays after selection
- [ ] Invalid image file rejected with error message
- [ ] Oversized image rejected with error message
- [ ] Status dropdown defaults to "draft"
- [ ] "Publish immediately" checkbox works
- [ ] Checking "publish immediately" sets status to "active" and disables dropdown
- [ ] "Featured" checkbox works
- [ ] Form validation messages clear and helpful
- [ ] Course code auto-generated from title
- [ ] "Create Course" button submits form
- [ ] Loading state shows during submission ("Creating...")
- [ ] Course created successfully with valid data
- [ ] Redirect to course list after creation
- [ ] Success message displayed
- [ ] "Cancel" button returns to course list

### 4.3 Course Details (`/admin/courses/{id}`)
- [ ] Course details page loads successfully
- [ ] Course statistics displayed:
  - [ ] Total enrollments
  - [ ] Total lessons
  - [ ] Total modules
  - [ ] Average rating (if available)
- [ ] Course information sidebar shows:
  - [ ] Course code
  - [ ] Type
  - [ ] Difficulty level
  - [ ] Duration
  - [ ] Status badge
  - [ ] Featured badge (if applicable)
  - [ ] Created by
  - [ ] Created date
  - [ ] Last updated date
- [ ] Course description displayed fully
- [ ] Cover image displays (if available)
- [ ] Course structure/hierarchy displayed:
  - [ ] Modules listed
  - [ ] Lessons within modules listed
  - [ ] Empty state if no structure
- [ ] Quick action buttons functional:
  - [ ] "Edit Course" navigates to edit page
  - [ ] "Change Status" button works
  - [ ] "Feature/Unfeature" button works
  - [ ] "Delete Course" button visible (if allowed)
- [ ] Breadcrumb navigation works
- [ ] "Back to Courses" button returns to list

### 4.4 Course Edit (`/admin/courses/{id}/edit`)
- [ ] Edit form loads successfully
- [ ] All fields pre-populated with current data
- [ ] Title field shows current title
- [ ] Current course code displayed (not editable via form)
- [ ] Type dropdown shows current selection
- [ ] Difficulty dropdown shows current selection
- [ ] Duration field shows current duration
- [ ] Description textarea shows current description
- [ ] Current image displays (if exists)
- [ ] Image overlay shows "Remove Image" on hover
- [ ] "Remove Image" button works
- [ ] New image upload replaces current image
- [ ] New image preview displays
- [ ] Status dropdown shows current status
- [ ] "Published" checkbox reflects current state
- [ ] "Featured" checkbox reflects current state
- [ ] Course metadata section displays:
  - [ ] Created by
  - [ ] Created on date
  - [ ] Last updated date
  - [ ] Total enrollments
- [ ] All validation rules work same as create form
- [ ] "Update Course" button submits form
- [ ] Loading state shows during submission ("Updating...")
- [ ] Course updated successfully
- [ ] Redirect to course details after update
- [ ] Success message displayed
- [ ] "Cancel" button returns to course details

### 4.5 Course Deletion (`/admin/courses/{id}`)
- [ ] Delete button visible on course details page
- [ ] Confirmation dialog appears
- [ ] Dialog warns about enrollments (if any)
- [ ] Cannot delete course with active enrollments (error message)
- [ ] Can delete course without enrollments
- [ ] Deletion cancelled if user declines
- [ ] Course deleted successfully with confirmation
- [ ] Redirect to course list after deletion
- [ ] Success message displayed

### 4.6 Course AJAX Operations

#### Status Update
- [ ] Status button visible on course details page
- [ ] Click opens confirmation dialog
- [ ] Dialog shows current and new status
- [ ] Status cycles: draft → active → archived → draft
- [ ] AJAX request sent to `/api/v1/admin/courses/{id}/status`
- [ ] CSRF token included in request
- [ ] Request payload contains new status
- [ ] Response received successfully
- [ ] Page reloads after successful update
- [ ] New status reflected in UI
- [ ] Error message displayed on failure
- [ ] Network error handled gracefully

#### Featured Toggle
- [ ] Featured button visible on course details page
- [ ] Button text reflects current state ("Feature" or "Unfeature")
- [ ] Click opens confirmation dialog
- [ ] Dialog text appropriate ("Feature this course?" or "Remove from featured?")
- [ ] AJAX request sent to `/api/v1/admin/courses/{id}/featured`
- [ ] CSRF token included in request
- [ ] Response received successfully
- [ ] Page reloads after successful toggle
- [ ] Featured badge appears/disappears correctly
- [ ] Error message displayed on failure
- [ ] Network error handled gracefully

### 4.7 Course Hierarchy (Future Feature)
- [ ] "Add Module" button visible (if implemented)
- [ ] "Add Section" button visible (if implemented)
- [ ] Module creation modal works
- [ ] Section creation modal works
- [ ] Modules/sections display in correct order
- [ ] Drag-and-drop reordering works (if implemented)

---

## 5. View & Layout Tests

### 5.1 Admin Layout (`layouts/admin.php`)
- [ ] Layout file loads successfully
- [ ] Header displays correctly
- [ ] Logo/branding visible
- [ ] Navigation menu accessible
- [ ] User dropdown functional
- [ ] Logout link works
- [ ] Content area renders correctly
- [ ] Footer displays (if present)
- [ ] Responsive on mobile devices
- [ ] Sidebar navigation works (if present)

### 5.2 Course Views Consistency
- [ ] All course views use same layout
- [ ] Form cards have gradient headers
- [ ] Color scheme consistent across views
- [ ] Typography consistent
- [ ] Button styles consistent
- [ ] Icon usage consistent (Font Awesome)
- [ ] Spacing and padding consistent
- [ ] Border radius consistent
- [ ] Shadow effects consistent

### 5.3 Responsive Design
- [ ] Desktop view (>1200px) displays correctly
- [ ] Tablet view (768px-1199px) displays correctly
- [ ] Mobile view (<768px) displays correctly
- [ ] Forms stack vertically on mobile
- [ ] Tables scroll horizontally on mobile (if needed)
- [ ] Navigation collapses appropriately
- [ ] Buttons stack on mobile
- [ ] Images resize appropriately
- [ ] Text remains readable at all sizes

---

## 6. Routing Tests

### 6.1 Web Routes (`routes/web.php`)
- [ ] GET `/admin` → Admin\AdminController@dashboard
- [ ] GET `/admin/courses` → Admin\CourseController@index
- [ ] GET `/admin/courses/create` → Admin\CourseController@create
- [ ] POST `/admin/courses` → Admin\CourseController@store
- [ ] GET `/admin/courses/{id}` → Admin\CourseController@show
- [ ] GET `/admin/courses/{id}/edit` → Admin\CourseController@edit
- [ ] PUT `/admin/courses/{id}` → Admin\CourseController@update
- [ ] POST `/admin/courses/{id}/update` → Admin\CourseController@update (form fallback)
- [ ] DELETE `/admin/courses/{id}` → Admin\CourseController@destroy
- [ ] All routes require admin authentication
- [ ] 404 error for non-existent routes
- [ ] Proper HTTP method enforcement

### 6.2 API Routes (`routes/api.php`)
- [ ] POST `/api/v1/admin/courses/{id}/status` → Api\Admin\CourseController@updateStatus
- [ ] POST `/api/v1/admin/courses/{id}/featured` → Api\Admin\CourseController@toggleFeatured
- [ ] GET `/api/v1/admin/courses/{id}/modules` → Api\Admin\CourseController@getModules
- [ ] POST `/api/v1/admin/courses/{id}/modules` → Api\Admin\CourseController@createModule
- [ ] GET `/api/v1/admin/courses/{id}/sections` → Api\Admin\CourseController@getSections
- [ ] POST `/api/v1/admin/courses/{id}/sections` → Api\Admin\CourseController@createSection
- [ ] All API routes require admin authentication
- [ ] All API routes return JSON responses
- [ ] Proper HTTP status codes returned
- [ ] CORS headers present (if needed)

---

## 7. Security Tests

### 7.1 SQL Injection Prevention
- [ ] All database queries use prepared statements
- [ ] User input sanitized before database operations
- [ ] Special characters escaped properly
- [ ] No raw SQL concatenation with user input
- [ ] Test with malicious input: `'; DROP TABLE courses; --`

### 7.2 XSS Prevention
- [ ] All output uses `htmlspecialchars()`
- [ ] User-generated content escaped in views
- [ ] HTML tags in input sanitized
- [ ] Test with: `<script>alert('XSS')</script>`
- [ ] Test with: `<img src=x onerror=alert('XSS')>`

### 7.3 CSRF Protection
- [ ] CSRF token generated on page load
- [ ] Token included in all mutation forms
- [ ] Token validated on POST/PUT/DELETE requests
- [ ] Invalid token rejected with 403 error
- [ ] Token rotation after use (if implemented)

### 7.4 File Upload Security
- [ ] File type validation enforced
- [ ] File size limits enforced
- [ ] Uploaded files stored outside web root (or properly secured)
- [ ] File names sanitized
- [ ] MIME type validation
- [ ] Test with executable files (.php, .exe)
- [ ] Test with oversized files

### 7.5 Session Security
- [ ] Sessions use httpOnly flag
- [ ] Sessions use secure flag (if HTTPS)
- [ ] Session timeout configured
- [ ] Session hijacking prevented
- [ ] Session fixation prevented

### 7.6 Authorization Checks
- [ ] Every admin route checks user role
- [ ] Direct URL access blocked for non-admins
- [ ] API endpoints verify admin role
- [ ] Cannot modify other admin's data without permission

---

## 8. Database Tests

### 8.1 Course CRUD Operations
- [ ] `AdminCourseModel->createCourse()` inserts record
- [ ] `AdminCourseModel->getCourseById()` retrieves record
- [ ] `AdminCourseModel->getAllCourses()` retrieves all records
- [ ] `AdminCourseModel->updateCourse()` updates record
- [ ] `AdminCourseModel->deleteCourse()` deletes record
- [ ] Foreign key constraints enforced
- [ ] Default values set correctly
- [ ] Timestamps auto-populated

### 8.2 Data Integrity
- [ ] Course code uniqueness enforced
- [ ] Valid status values enforced (draft|active|archived)
- [ ] Valid type values enforced
- [ ] Valid difficulty values enforced
- [ ] Created_by references valid user
- [ ] Database transactions work (if used)

### 8.3 Query Performance
- [ ] Course list query executes in <1 second
- [ ] Search query with filters executes in <1 second
- [ ] Course details query executes in <500ms
- [ ] Pagination doesn't load all records
- [ ] Indexes exist on frequently queried columns

---

## 9. Error Handling Tests

### 9.1 User Errors
- [ ] Form validation errors display clearly
- [ ] Missing required field error messages helpful
- [ ] Invalid input format errors specific
- [ ] Duplicate course code error handled
- [ ] Friendly error pages (no raw PHP errors)

### 9.2 System Errors
- [ ] Database connection errors caught
- [ ] Database query errors logged
- [ ] File system errors handled
- [ ] Missing file errors handled
- [ ] 500 errors logged with details
- [ ] Generic error message shown to user

### 9.3 API Errors
- [ ] 400 Bad Request for invalid input
- [ ] 401 Unauthorized for missing auth
- [ ] 403 Forbidden for insufficient permissions
- [ ] 404 Not Found for non-existent resources
- [ ] 500 Internal Server Error for system errors
- [ ] Error response includes message field
- [ ] Error details not exposed to unauthorized users

---

## 10. Browser Console Tests

### 10.1 JavaScript Errors
- [ ] No JavaScript errors in console
- [ ] No undefined variable errors
- [ ] No function not found errors
- [ ] No CORS errors
- [ ] No mixed content warnings (HTTP/HTTPS)

### 10.2 Network Requests
- [ ] All AJAX requests succeed (200/201 responses)
- [ ] No 404 errors for assets (CSS, JS, images)
- [ ] No 500 errors for API calls
- [ ] Request payloads formatted correctly
- [ ] Response payloads parsed correctly

### 10.3 Performance
- [ ] Page load time <3 seconds
- [ ] Time to interactive <5 seconds
- [ ] No memory leaks
- [ ] No excessive DOM manipulation
- [ ] Images optimized and load quickly

---

## 11. Backward Compatibility Tests

### 11.1 Deprecated Controllers
- [ ] `AdminCourseController` still functional (proxy)
- [ ] Deprecation warnings logged
- [ ] Old method calls forwarded to new controller
- [ ] No breaking changes for existing code
- [ ] Migration path documented

### 11.2 Existing Features
- [ ] User management still works
- [ ] Dashboard still works
- [ ] Attendance system still works
- [ ] Holiday programs still work
- [ ] All existing routes still accessible

---

## 12. Documentation Tests

### 12.1 Code Documentation
- [ ] All controller methods have PHPDoc comments
- [ ] Model methods documented
- [ ] Complex logic explained with inline comments
- [ ] API endpoints documented with @param and @return tags

### 12.2 User Documentation
- [ ] CLAUDE.md updated with Week 5 changes
- [ ] ImplementationProgress.md reflects current state
- [ ] Testing checklist (this document) comprehensive
- [ ] Completion documentation created

---

## 13. Accessibility Tests

### 13.1 Keyboard Navigation
- [ ] All forms navigable with Tab key
- [ ] All buttons accessible via keyboard
- [ ] Focus indicators visible
- [ ] Logical tab order

### 13.2 Screen Reader Compatibility
- [ ] Form labels associated with inputs
- [ ] Alt text on images
- [ ] ARIA labels where appropriate
- [ ] Semantic HTML used

### 13.3 Visual Accessibility
- [ ] Sufficient color contrast (WCAG AA)
- [ ] Text readable without zooming
- [ ] No reliance on color alone for meaning
- [ ] Clear visual hierarchy

---

## Test Execution Summary

### Automated Checks Completed
✅ PHP syntax validation - All files passed
✅ File structure verification - Complete
✅ Route configuration - Complete
✅ Code quality review - Complete

### Manual Tests Required
⏳ Authentication & Authorization (13 tests)
⏳ Admin Dashboard (9 tests)
⏳ User Management (20 tests)
⏳ Course Management (80+ tests)
⏳ View & Layout (18 tests)
⏳ Routing (16 tests)
⏳ Security (24 tests)
⏳ Database (13 tests)
⏳ Error Handling (15 tests)
⏳ Browser Console (10 tests)
⏳ Backward Compatibility (7 tests)
⏳ Documentation (4 tests)
⏳ Accessibility (10 tests)

**Total Manual Test Cases:** 239

---

## Critical Bugs Found

*None during automated testing*

---

## Known Issues

1. **Sections Feature** - Not yet implemented in AdminCourseModel (gracefully handled)
2. **Course Hierarchy** - Module/section management UI not yet built
3. **Image Upload Directory** - Verify permissions on `public/assets/uploads/images/courses/`

---

## Testing Notes

### Setup Instructions
1. Start XAMPP/LAMP stack
2. Import fresh database from `Database/` folder
3. Create admin test account if needed
4. Clear browser cache
5. Open browser developer tools
6. Begin testing from section 1

### Reporting Bugs
When reporting bugs, include:
- Test case number/description
- Expected behavior
- Actual behavior
- Steps to reproduce
- Browser and version
- Screenshots (if applicable)
- Console errors (if applicable)

### Sign-off
- [ ] All critical tests passed
- [ ] All high priority tests passed
- [ ] Known issues documented
- [ ] Testing completed by: _____________
- [ ] Date: _____________

---

**End of Testing Checklist**
