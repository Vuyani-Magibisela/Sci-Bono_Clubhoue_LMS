<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 8 implementation.
 * All visitor operations are now handled by the modern routing system.
 *
 * **Public Routes:**
 * - GET /visitor/register - Registration form
 * - POST /visitor/register - Process registration
 * - GET /visitor/success - Registration confirmation
 *
 * **Admin Routes:**
 * - GET /admin/visitors - Visitor management
 * - POST /admin/visitors/{id}/checkin - Check-in
 * - POST /admin/visitors/{id}/checkout - Check-out
 *
 * **Modern Controller:** app/Controllers/VisitorController
 *
 * @deprecated Since Phase 3 Week 8
 * @see app/Controllers/VisitorController
 * ====================================================================
 */

// Determine action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'index';

switch ($action) {
    case 'register':
        header('Location: /Sci-Bono_Clubhoue_LMS/visitor/register');
        break;
    case 'checkin':
    case 'checkout':
        $visitorId = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($visitorId) {
            header('Location: /Sci-Bono_Clubhoue_LMS/admin/visitors/' . urlencode($visitorId));
        } else {
            header('Location: /Sci-Bono_Clubhoue_LMS/admin/visitors');
        }
        break;
    default:
        header('Location: /Sci-Bono_Clubhoue_LMS/admin/visitors');
        break;
}
exit;
?>
