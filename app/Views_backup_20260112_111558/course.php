<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 6-7 implementation.
 * All requests are automatically redirected to the modern course view.
 *
 * **Modern Route:** GET /courses/{id}
 * **Modern Controller:** app/Controllers/Member/CourseController.php
 * **Modern View:** app/Views/member/courses/show.php
 *
 * @deprecated Since Phase 3 Week 6-7
 * @see app/Controllers/Member/CourseController
 * ====================================================================
 */

// Extract course ID if provided
$courseId = $_GET['id'] ?? $_GET['course_id'] ?? null;

if ($courseId) {
    header('Location: /Sci-Bono_Clubhoue_LMS/courses/' . urlencode($courseId));
} else {
    // No course ID, redirect to courses index
    header('Location: /Sci-Bono_Clubhoue_LMS/courses');
}
exit;
?>
