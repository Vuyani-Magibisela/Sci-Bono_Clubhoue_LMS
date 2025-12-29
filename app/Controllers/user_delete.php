<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 8 implementation.
 * All user deletion operations are now handled by the modern routing system.
 *
 * **Modern Route:** DELETE /admin/users/{id}
 * **Modern Controller:** app/Controllers/Admin/UserController::destroy()
 * **Alternative:** POST /admin/users/{id} (with _method=DELETE)
 *
 * @deprecated Since Phase 3 Week 8
 * @see app/Controllers/Admin/UserController
 * ====================================================================
 */

// Extract user ID from POST data or GET parameter
$userId = $_POST['user_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;

if ($userId) {
    // Redirect to modern user management page
    // Note: Actual deletion requires DELETE method via modern routing
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users/' . urlencode($userId));
} else {
    // No user ID provided, redirect to user list
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users');
}
exit;
?>
