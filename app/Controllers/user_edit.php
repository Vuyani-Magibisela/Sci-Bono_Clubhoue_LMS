<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 4 Week 3 implementation.
 * All user editing operations are now handled by the modern routing system.
 *
 * **Modern Route:** GET /admin/users/{id}/edit
 * **Modern Controller:** app/Controllers/Admin/UserController::edit()
 * **Modern View:** app/Views/admin/users/edit.php
 *
 * @deprecated Since Phase 4 Week 3
 * @see app/Controllers/Admin/UserController
 * ====================================================================
 */

// Extract user ID from GET parameter
$userId = $_GET['id'] ?? null;

if ($userId) {
    // Redirect to modern user edit page
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users/' . urlencode($userId) . '/edit');
} else {
    // No user ID provided, redirect to user list
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users');
}
exit;
?>
