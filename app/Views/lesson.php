<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 6-7 implementation.
 * All requests are automatically redirected to the modern lesson view.
 *
 * **Modern Route:** GET /lessons/{id}
 * **Modern Controller:** app/Controllers/Member/LessonController.php
 * **Modern View:** app/Views/member/lessons/show.php
 *
 * @deprecated Since Phase 3 Week 6-7
 * @see app/Controllers/Member/LessonController
 * ====================================================================
 */

// Extract lesson ID if provided
$lessonId = $_GET['id'] ?? $_GET['lesson_id'] ?? null;

if ($lessonId) {
    header('Location: /Sci-Bono_Clubhoue_LMS/lessons/' . urlencode($lessonId));
} else {
    // No lesson ID, redirect to courses
    header('Location: /Sci-Bono_Clubhoue_LMS/courses');
}
exit;
?>
