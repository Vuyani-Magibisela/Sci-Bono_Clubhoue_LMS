# Current Task: Debug `app/Views/course.php`

## Objectives
- Identify the cause of "Undefined variable" warnings for `$totalDuration` and `$totalLessons`.
- Identify the cause of "Undefined array key 'lessons'" warning and the subsequent `count()` TypeError.
- Implement fixes to resolve these errors.
- Ensure the course page displays correctly with accurate duration, lesson counts, and section details.

## Context
The user has provided `app/Views/course.php` and a list of errors occurring in it:
- `Warning: Undefined variable $totalDuration` (line 88)
- `Warning: Undefined variable $totalLessons` (line 96)
- `Warning: Undefined array key "lessons"` (line 130)
- `Fatal error: Uncaught TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given` (line 130)

The file includes `CourseController.php` and `LessonController.php`. The variables `$course` and `$sections` are fetched using `CourseController`.

## Next Steps
1.  **DONE:** Create `cline_docs/techStack.md`.
2.  **DONE:** Create `cline_docs/codebaseSummary.md`.
3.  **DONE:** Read `app/Controllers/CourseController.php` to understand how `$totalDuration`, `$totalLessons`, and section lessons are (or should be) calculated and passed to the view.
4.  **DONE:** Read `app/Controllers/LessonController.php` to identify `getSectionLessons()`.
5.  **DONE:** Read `app/Models/CourseModel.php` to understand how sections are fetched.
6.  **DONE:** Analyzed `app/Views/course.php` in conjunction with the controller/model logic to pinpoint where the variables should be initialized and data fetched.
7.  **DONE:** Proposed and implemented changes:
    *   Modified `app/Controllers/CourseController.php`:
        *   Added `LessonController` dependency.
        *   Created `getCourseDataForView($courseId, $userId)` method to consolidate data fetching:
            *   Fetches course details.
            *   Fetches sections.
            *   For each section, fetches its lessons via `LessonController`.
            *   For each lesson, determines completion status.
            *   Calculates `totalDuration`, `totalLessons`, `completedLessons`.
            *   Gets enrollment status and user progress.
            *   Returns all data in a structured array.
    *   Modified `app/Views/course.php`:
        *   Calls `getCourseDataForView()` to get all page data.
        *   Extracts variables (`$course`, `$sections`, `$isEnrolled`, `$progressPercent`, `$totalDuration`, `$totalLessons`, `$completedLessons`) from the returned data.
        *   Updated display logic for progress and lesson counts.
        *   Added logic for "Continue Learning" button to link to the next appropriate lesson.

## Final Steps
1. Update `cline_docs/projectRoadmap.md`.
2. Attempt completion.
