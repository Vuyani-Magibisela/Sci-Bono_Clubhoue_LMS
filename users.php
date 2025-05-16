
<?php
// Redirect to the new controller
// header("Location: app/Controllers/user_list.php");
// exit;
?>
<?php
// Ensure session is started at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/server.php'; // Establishes $conn
require_once __DIR__ . '/app/Controllers/UserController.php';
// UserModel is included by UserController, so no need to include it directly here.

// Basic error reporting (consider more robust logging for production)
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Check if $conn is available from server.php
if (!isset($conn) || !$conn) {
    // Log this critical error
    error_log("Database connection (\$conn) not established in server.php or not available in users.php.");
    // Display a user-friendly error message
    die("A critical error occurred: Database connection failed. Please contact support.");
}

// Instantiate the UserController, passing the database connection
$userController = new UserController($conn);

// Determine the action from the URL, default to 'list'
$action = $_GET['action'] ?? 'list'; // Default action
$id = isset($_GET['id']) ? (int)$_GET['id'] : null; // Get ID if present

// Route the request to the appropriate controller method
switch ($action) {
    case 'list':
        $userController->listUsers();
        break;
    case 'edit':
        if ($id !== null) {
            $userController->showEditForm($id);
        } else {
            // Handle missing ID for edit action - redirect or show error
            $_SESSION['message'] = ['type' => 'error', 'text' => 'User ID is required for edit action.'];
            header("Location: users.php?action=list");
            exit;
        }
        break;
    case 'update':
        if ($id !== null) {
            // Update logic is handled within the controller method, including checking for POST
            $userController->update($id);
        } else {
            // Handle missing ID for update action
            $_SESSION['message'] = ['type' => 'error', 'text' => 'User ID is required for update action.'];
            header("Location: users.php?action=list");
            exit;
        }
        break;
    case 'delete':
        if ($id !== null) {
            $userController->delete($id);
        } else {
            // Handle missing ID for delete action
            $_SESSION['message'] = ['type' => 'error', 'text' => 'User ID is required for delete action.'];
            header("Location: users.php?action=list");
            exit;
        }
        break;
    default:
        // Handle unknown action - redirect to list or show a 404-like error
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid action specified.'];
        header("Location: users.php?action=list");
        exit;
}

// Close the database connection if it's open and no longer needed by included views
// Note: Views might still need it if they do direct DB calls, which is not ideal MVC.
// For now, assuming UserController and UserModel handle all DB interactions before view inclusion.
// if ($conn) {
//    $conn->close();
// }
?>
