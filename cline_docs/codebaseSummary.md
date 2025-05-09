# Codebase Summary

## Key Components and Their Interactions

### `app/Views/course.php` (View)
- Displays detailed information about a specific course.
- Fetches course ID from the URL (`$_GET['id']`).
- Includes `learn-header.php` for common header elements.
- Interacts with `CourseController` to get course details, sections, enrollment status, and user progress.
- Interacts with `LessonController` (though not directly visible in the provided snippet, it's included).
- Handles user enrollment via a GET request parameter (`$_GET['enroll']`).
- Dynamically generates HTML based on fetched data.
- Includes JavaScript for UI interactions (toggling sections, progress bar animation).

### `app/Controllers/CourseController.php` (Controller)
- Manages course-related logic.
- Expected to have methods like:
    - `getCourseDetails($courseId)`: Fetches main course information.
    - `getCourseSections($courseId)`: Fetches sections and their associated lessons for a course.
    - `isUserEnrolled($userId, $courseId)`: Checks if a user is enrolled in a course.
    - `getUserProgress($userId, $courseId)`: Calculates user's progress in a course.
    - `enrollUser($userId, $courseId)`: Handles user enrollment.
- Likely interacts with database models (e.g., `CourseModel`, `EnrollmentModel`, `LessonModel`) to retrieve and manipulate data.

### `app/Controllers/LessonController.php` (Controller)
- Manages lesson-related logic.
- Its specific role in `course.php` is not immediately clear from the snippet but is included. It might be used by `CourseController` or for other lesson-specific actions not shown.

### `server.php` (Database Connection)
- Establishes the connection to the MySQL database.

### `app/Models/LMSUtilities.php` (Utility/Model)
- Contains utility functions, possibly for formatting data (e.g., `formatDuration`, `formatCourseType`, `getLessonIcon` which are used in `course.php` but their definitions are not in the provided snippet).

### `app/Controllers/sessionTimer.php` (Session Management)
- Included to handle auto-logout based on inactivity.

## Data Flow
1. User navigates to `course.php?id=<course_id>`.
2. `course.php` retrieves `courseId` and `userId`.
3. `CourseController` is instantiated.
4. If `enroll=1` is in URL, `CourseController->enrollUser()` is called, and the page reloads.
5. `CourseController->getCourseDetails()` is called to fetch course data.
6. `CourseController->getCourseSections()` is called to fetch section data (which should include lessons for each section).
7. `CourseController->isUserEnrolled()` and `CourseController->getUserProgress()` are called.
8. The fetched data (`$course`, `$sections`, `$isEnrolled`, `$progress`) is used to render the HTML in `course.php`.
9. Variables `$totalDuration` and `$totalLessons` are expected to be available for display but are currently undefined, causing errors.
10. The `lessons` key within each `$section` in the `$sections` array is expected but is `null` for at least one section, causing an error.

## External Dependencies
- **Font Awesome:** For icons.
- **Google Fonts:** For the Poppins font.
- **PHP Session Management:** For user login state.
- **MySQL Database:** For storing all application data.

## Recent Significant Changes
- N/A (This is the initial state for this task).

## User Feedback Integration and Its Impact on Development
- The current task is driven by user-reported errors (PHP warnings and a fatal error). The goal is to resolve these issues to improve the user experience on the course page.
