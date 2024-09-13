<?php
session_start();
// Database connection details
require '../../server.php';


// Retrieve data from the database
$sql = "SELECT * FROM clubhouse_programs";
$result = mysqli_query($conn, $sql);

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $learning_outcomes = sanitize_input($_POST['learning_outcomes']);
    $target_age_group = sanitize_input($_POST['target_age_group']);
    $duration = sanitize_input($_POST['duration']);
    $max_participants = sanitize_input($_POST['max_participants']);
    $materials_needed = sanitize_input($_POST['materials_needed']);
    $difficulty_level = sanitize_input($_POST['difficulty_level']);

    $sql = "INSERT INTO clubhouse_programs (title, description, learning_outcomes, target_age_group, duration, max_participants, materials_needed, difficulty_level) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiis", $title, $description, $learning_outcomes, $target_age_group, $duration, $max_participants, $materials_needed, $difficulty_level);

    if ($stmt->execute()) {
        header("Location: ../views/addClubhouseProgram.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>