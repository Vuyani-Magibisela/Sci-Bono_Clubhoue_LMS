<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 8 implementation.
 * All report data operations are now handled by the modern routing system.
 *
 * **Modern Route:** GET /admin/reports
 * **Modern Controller:** app/Controllers/ReportController::index()
 * **API Route:** GET /admin/reports/api/filter (for filtered data)
 *
 * @deprecated Since Phase 3 Week 8
 * @see app/Controllers/ReportController
 * ====================================================================
 */

// Redirect to modern reports index
header('Location: /Sci-Bono_Clubhoue_LMS/admin/reports');
exit;
?>
