<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 8 implementation.
 * All user update operations are now handled by the modern routing system.
 *
 * **Modern Route:** PUT /admin/users/{id}
 * **Modern Controller:** app/Controllers/Admin/UserController::update()
 * **Alternative:** POST /admin/users/{id} (with _method=PUT)
 *
 * @deprecated Since Phase 3 Week 8
 * @see app/Controllers/Admin/UserController
 * ====================================================================
 */

// Extract user ID from POST data or GET parameter
$userId = $_POST['user_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;

if ($userId) {
    // Redirect to modern user edit page
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users/' . urlencode($userId) . '/edit');
} else {
    // No user ID provided, redirect to user list
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/users');
}
exit;
?>
