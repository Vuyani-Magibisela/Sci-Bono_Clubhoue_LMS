<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 6-7 implementation.
 * All requests are automatically redirected to the modern dashboard.
 *
 * **Modern Route:** GET /dashboard
 * **Modern Controller:** app/Controllers/DashboardController.php
 * **Modern View:** app/Views/member/dashboard/index.php
 *
 * @deprecated Since Phase 3 Week 6-7
 * @see app/Controllers/DashboardController
 * ====================================================================
 */

// Redirect to modern dashboard route
header('Location: /Sci-Bono_Clubhoue_LMS/dashboard');
exit;
?>
