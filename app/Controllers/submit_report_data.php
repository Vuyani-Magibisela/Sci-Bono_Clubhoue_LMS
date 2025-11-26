<?php
session_start();
// Database connection details
require '../../server.php';

// Retrieve data from the database
$sql = "SELECT * FROM clubhouse_reports";
$result = mysqli_query($conn, $sql);


// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../../core/CSRF.php';

    if (!CSRF::validateToken()) {
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: ../views/statsdashboard.php");
        exit();
    }

    $programs = $_POST['programs'];
    $participants = $_POST['participants'];
    $narratives = $_POST['narratives'];
    $challenges = $_POST['challenges'];
    
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO clubhouse_reports (program_name, participants, narrative, challenges, image_path) VALUES (?, ?, ?, ?, ?)");
 
    for ($i = 0; $i < count($programs); $i++) {
        $program = sanitize_input($programs[$i]);
        $participant = sanitize_input($participants[$i]);
        $narrative = sanitize_input($narratives[$i]);
        $challenges = sanitize_input($challenges[$i]);
        
        // Handle file upload
        $image_path = "";
        if (isset($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] == 0) {
            // Set the base upload directory
            $base_upload_dir = "../../public/assets/uploads/images/";
            
            // Create a folder for the current month
            $current_month = date('Y-m');
            $target_dir = $base_upload_dir . $current_month . '/';
            
            // Create the monthly directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate a unique filename
            $filename = uniqid() . '_' . basename($_FILES["images"]["name"][$i]);
            $target_file = $target_dir . $filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            
            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["images"]["tmp_name"][$i]);
            if($check !== false) {
                if (move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                    // Store the relative path in the database
                    $image_path = $current_month . '/' . $filename;
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            } else {
                echo "File is not an image.";
            }
        }
        
        $stmt->bind_param("sisss", $program, $participant, $narrative, $challenges, $image_path);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect back to the dashboard
    header("Location: ../views/statsdashboard.php");
    exit();
}
?>