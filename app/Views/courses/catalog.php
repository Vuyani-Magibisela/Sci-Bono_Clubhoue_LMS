<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 6-7 implementation.
 * All requests are automatically redirected to the modern courses index.
 *
 * **Modern Route:** GET /courses
 * **Modern Controller:** app/Controllers/Member/CourseController.php
 * **Modern View:** app/Views/member/courses/index.php
 *
 * @deprecated Since Phase 3 Week 6-7
 * @see app/Controllers/Member/CourseController
 * ====================================================================
 */

// Redirect to modern courses index
header('Location: /Sci-Bono_Clubhoue_LMS/courses');
exit;
?>
