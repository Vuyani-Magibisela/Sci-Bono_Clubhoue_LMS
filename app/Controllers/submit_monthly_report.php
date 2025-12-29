<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 8 implementation.
 * All report submission operations are now handled by the modern routing system.
 *
 * **Modern Route:** POST /admin/reports
 * **Modern Controller:** app/Controllers/ReportController::store()
 * **Alternative Route:** POST /admin/reports/batch (for batch submissions)
 *
 * @deprecated Since Phase 3 Week 8
 * @see app/Controllers/ReportController
 * ====================================================================
 */

// Check if this is a submission request or form display request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Redirect to modern report creation endpoint
    // Note: POST data will be lost in redirect - should be handled by modern forms
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/reports/create');
} else {
    // GET request - show create form
    header('Location: /Sci-Bono_Clubhoue_LMS/admin/reports/create');
}
exit;
?>
