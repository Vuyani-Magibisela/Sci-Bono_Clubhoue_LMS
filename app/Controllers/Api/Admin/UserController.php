<?php
/**
 * Api\Admin\UserController - API Admin User Management
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 */

class UserController {
    private function checkAdmin() {
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
    }
    
    public function index() { $this->checkAdmin(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'GET /api/v1/admin/users']); exit; }
    public function store() { $this->checkAdmin(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'POST /api/v1/admin/users']); exit; }
    public function show($id) { $this->checkAdmin(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'GET /api/v1/admin/users/'.$id]); exit; }
    public function update($id) { $this->checkAdmin(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'PUT /api/v1/admin/users/'.$id]); exit; }
    public function destroy($id) { $this->checkAdmin(); http_response_code(501); echo json_encode(['status' => 'not_implemented', 'endpoint' => 'DELETE /api/v1/admin/users/'.$id]); exit; }
}
