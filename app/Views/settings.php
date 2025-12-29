<?php
/**
 * ====================================================================
 * DEPRECATED FILE - AUTOMATIC REDIRECT
 * ====================================================================
 *
 * This file has been deprecated as of Phase 3 Week 6-7 implementation.
 * All requests are automatically redirected to the modern settings page.
 *
 * **Modern Route:** GET /settings
 * **Modern Controller:** app/Controllers/SettingsController.php
 * **Modern View:** app/Views/member/settings/index.php
 *
 * @deprecated Since Phase 3 Week 6-7
 * @see app/Controllers/SettingsController
 * ====================================================================
 */

// Redirect to modern settings page
header('Location: /Sci-Bono_Clubhoue_LMS/settings');
exit;
?>
