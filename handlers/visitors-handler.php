<?php
/**
 * Visitor Management System - Request Handler
 * 
 * This file acts as the entry point for all AJAX requests and uses the MVC pattern
 */

// Include required files
require_once '../server.php'; // Database connection
require_once '../app/models/VisitorModel.php';
require_once '../app/controllers/VisitorController.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize model and controller
$visitorModel = new VisitorModel($conn);
$visitorController = new VisitorController($visitorModel);

// Handle requests based on HTTP method and parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST requests (registration, sign-in, sign-out)
    if (isset($_POST['name']) && isset($_POST['email'])) {
        // Process registration
        $response = $visitorController->processRegistration($_POST);
        echo json_encode($response);
    } 
    elseif (isset($_POST['action']) && $_POST['action'] === 'signin') {
        // Process sign-in
        $response = $visitorController->processSignIn($_POST);
        echo json_encode($response);
    } 
    elseif (isset($_POST['action']) && $_POST['action'] === 'signout') {
        // Process sign-out
        $response = $visitorController->processSignOut($_POST);
        echo json_encode($response);
    } 
    else {
        // Invalid request
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request'
        ]);
    }
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET requests (listing visitors)
    if (isset($_GET['action']) && $_GET['action'] === 'list') {
        $response = $visitorController->processVisitorsList($_GET);
        echo json_encode($response);
    } 
    else {
        // Invalid request
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request'
        ]);
    }
} 
else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}