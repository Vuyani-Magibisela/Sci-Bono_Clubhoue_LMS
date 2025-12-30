<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 4 Week 3 implementation.
 * All user management operations are now handled by the modern routing system.
 *
 * **Modern Route:** GET /admin/users
 * **Modern Controller:** app/Controllers/Admin/UserController::index()
 * **Modern View:** app/Views/admin/users/index.php
 *
 * @deprecated Since Phase 4 Week 3
 * @see app/Controllers/Admin/UserController
 * ====================================================================
 */

// Redirect to modern user management page
header('Location: /Sci-Bono_Clubhoue_LMS/admin/users');
exit;
?>
