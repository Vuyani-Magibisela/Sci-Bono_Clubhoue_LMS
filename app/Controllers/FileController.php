<?php
/**
 * FileController
 *
 * Handles file uploads and management
 *
 * Phase 3: Modern Routing System - Stub Controller
 * Created: November 11, 2025
 * Status: STUB - Needs migration from /handlers/secure-upload-handler.php
 */

require_once __DIR__ . '/BaseController.php';

class FileController extends BaseController {

    /**
     * Handle file upload
     *
     * Route: POST /upload
     * Name: files.upload
     * Middleware: AuthMiddleware
     */
    public function upload() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'File upload under migration',
            'controller' => 'FileController',
            'method' => 'upload',
            'todo' => 'Migrate from /handlers/secure-upload-handler.php'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Delete a file
     *
     * Route: DELETE /files/{id}
     * Name: files.delete
     * Middleware: AuthMiddleware
     */
    public function delete($id) {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        http_response_code(501);
        echo json_encode([
            'status' => 'not_implemented',
            'message' => 'File deletion under migration',
            'controller' => 'FileController',
            'method' => 'delete',
            'file_id' => $id
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
