# Project Roadmap

## High-Level Goals
- [x] Resolve PHP errors in `app/Views/course.php`.
- [x] Ensure course page displays correctly without warnings or fatal errors.

## Key Features
- N/A for this task.

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
