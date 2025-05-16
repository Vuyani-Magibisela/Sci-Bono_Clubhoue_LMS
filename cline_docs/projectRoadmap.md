# Project Roadmap

## High-Level Goals
- [x] Resolve PHP errors in `app/Views/course.php`.
- [x] Ensure course page displays correctly without warnings or fatal errors.
- [x] Refactor user management (`user_list.php`) into MVC pattern.

## Key Features
- User Management MVC structure (Model, Views, Controller).
- Separate views for user list and user edit form.
- Controller handling logic and permissions.
- Model handling database interactions for users.
- Consistent styling using `settingsStyle.css`.

## Completion Criteria
- `app/Views/course.php` loads without PHP warnings related to `$totalDuration`, `$totalLessons`, or `lessons` array key.
- `app/Views/course.php` loads without the fatal `count()` error.
- Course duration and total lesson count are displayed correctly.
- Section lesson counts are displayed correctly.

## Progress Tracker
### Completed Tasks
- Resolved PHP errors in `app/Views/course.php`:
    - Modified `app/Controllers/CourseController.php` to create a new method `getCourseDataForView` which consolidates data fetching for the course page. This method now correctly calculates total duration, total lessons, completed lessons, and populates lessons within each section, including their completion status.
    - Modified `app/Views/course.php` to use the new `getCourseDataForView` method and correctly display the fetched and calculated data.
- Refactored user management (`user_list.php`) into MVC:
    - Created `app/Models/UserModel.php` to handle user data operations (get all, get members, get by ID, update, delete).
    - Created `app/Controllers/UserController.php` to manage requests, permissions, and interaction between model and views.
    - Created `app/Views/user_list_view.php` to display the user table.
    - Created `app/Views/user_edit_form_view.php` for the user editing form.
    - Created `users.php` as a router/entry point for user management actions.
    - Created `_navigation.php` for reusable side navigation and included it in the new views.
    - Applied styling from `public/assets/css/settingsStyle.css` to the new views.
    - Updated links and prepared for removal of old `user_list.php`.
