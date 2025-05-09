# Technology Stack

## Backend
- **Language:** PHP
- **Web Server:** XAMPP (Apache)
- **Database:** MySQL (assumed, as `server.php` is included and is typical for XAMPP setups)
- **Session Management:** PHP Sessions (`session_start()`)

## Frontend
- **HTML:** Standard HTML5
- **CSS:** Custom CSS (`public/assets/css/course.css`)
- **JavaScript:** Vanilla JavaScript (for `toggleSection` and progress bar animation)
- **Libraries:**
    - Font Awesome (for icons)
    - Google Fonts (Poppins)

## Key Architectural Decisions
- **MVC-like Structure:** The use of `Controllers` (e.g., `CourseController`, `LessonController`) and `Views` (e.g., `course.php`) suggests an attempt at a Model-View-Controller pattern, though Models are also present (`LMSUtilities.php` might be a utility class or part of a model layer).
- **Direct Database Connection:** `require_once '../../server.php';` likely establishes a direct database connection.
- **URL-based Routing:** Course ID is retrieved via `$_GET['id']`.
- **User Authentication:** Basic session-based login check.
