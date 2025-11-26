<?php
/**
 * Admin\UserController
 *
 * Handles admin user management
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from user_list.php, user_edit.php, etc.
 */

require_once __DIR__ . '/../BaseController.php';

class UserController extends BaseController {

    private function checkAdminAuth() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: /Sci-Bono_Clubhoue_LMS/login');
            exit;
        }
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo 'Access Denied - Admin Only';
            exit;
        }
    }

    /**
     * Display user list
     * Route: GET /admin/users
     */
    public function index() {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'User list under migration',
            'controller' => 'Admin\UserController',
            'method' => 'index',
            'todo' => 'Migrate from /app/Controllers/user_list.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Show create user form
     * Route: GET /admin/users/create
     */
    public function create() {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Create user form under migration',
            'controller' => 'Admin\UserController',
            'method' => 'create'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Store new user
     * Route: POST /admin/users
     */
    public function store() {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'User creation under migration',
            'controller' => 'Admin\UserController',
            'method' => 'store'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Show user details
     * Route: GET /admin/users/{id}
     */
    public function show($id) {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'User details under migration',
            'controller' => 'Admin\UserController',
            'method' => 'show',
            'user_id' => $id
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Show edit user form
     * Route: GET /admin/users/{id}/edit
     */
    public function edit($id) {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'Edit user form under migration',
            'controller' => 'Admin\UserController',
            'method' => 'edit',
            'user_id' => $id,
            'todo' => 'Migrate from /app/Controllers/user_edit.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Update user
     * Route: PUT /admin/users/{id}
     */
    public function update($id) {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'User update under migration',
            'controller' => 'Admin\UserController',
            'method' => 'update',
            'user_id' => $id,
            'todo' => 'Migrate from /app/Controllers/user_update.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Delete user
     * Route: DELETE /admin/users/{id}
     */
    public function destroy($id) {
        $this->checkAdminAuth();
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'User deletion under migration',
            'controller' => 'Admin\UserController',
            'method' => 'destroy',
            'user_id' => $id,
            'todo' => 'Migrate from /app/Controllers/user_delete.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
