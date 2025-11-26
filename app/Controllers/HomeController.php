<?php
/**
 * HomeController
 *
 * Handles the landing/home page
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from legacy home.php
 */

require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController {

    /**
     * Display the home/landing page
     *
     * Route: GET /
     * Name: home
     */
    public function index() {
        // TODO: Migrate functionality from /home.php
        
        // Check if user is logged in
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            // Redirect to appropriate dashboard based on user type
            $userType = $_SESSION['user_type'] ?? 'member';
            
            if ($userType === 'admin') {
                header('Location: /Sci-Bono_Clubhoue_LMS/admin');
                exit;
            } elseif ($userType === 'mentor') {
                header('Location: /Sci-Bono_Clubhoue_LMS/mentor');
                exit;
            } else {
                header('Location: /Sci-Bono_Clubhoue_LMS/dashboard');
                exit;
            }
        }
        
        // Not logged in - show landing page
        // For now, redirect to login
        header('Location: /Sci-Bono_Clubhoue_LMS/login');
        exit;
    }
}
