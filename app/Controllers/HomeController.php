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
        // Display home/landing page for all users (logged in or not)
        // The view will show different content based on login status

        $data = [
            'page_title' => 'Welcome - Sci-Bono Clubhouse',
        ];

        // Render the home view
        $this->view('dashboard.home', $data);
    }
}
