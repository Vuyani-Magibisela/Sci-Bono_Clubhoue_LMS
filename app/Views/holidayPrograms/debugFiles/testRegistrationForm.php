<?php
// Minimal Registration Debug Form - Place this as minimal_registration_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../server.php'; // Adjust path as needed

echo "<h2>🔧 Minimal Registration Debug Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0;'>";
    echo "<h3>✅ POST REQUEST RECEIVED</h3>";
    
    try {
        // Get program ID
        $programId = 2; // Set your program ID here
        
        // Get basic form data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        echo "<p><strong>Form Data:</strong></p>";
        echo "<ul>";
        echo "<li>First Name: " . htmlspecialchars($firstName) . "</li>";
        echo "<li>Last Name: " . htmlspecialchars($lastName) . "</li>";
        echo "<li>Email: " . htmlspecialchars($email) . "</li>";
        echo "<li>Phone: " . htmlspecialchars($phone) . "</li>";
        echo "</ul>";
        
        // Check database connection
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        echo "<p>✅ Database connection: Working</p>";
        
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'holiday_program_attendees'");
        if ($tableCheck->num_rows == 0) {
            throw new Exception("Table 'holiday_program_attendees' does not exist");
        }
        
        echo "<p>✅ Database table: Found</p>";
        
        // Try a simple insert (without all the complex validation)
        $sql = "INSERT INTO holiday_program_attendees (program_id, first_name, last_name, email, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("issss", $programId, $firstName, $lastName, $email, $phone);
        
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            echo "<p>✅ Database insert: SUCCESS (ID: $newId)</p>";
            echo "<div style='background: #28a745; color: white; padding: 10px; margin: 10px 0;'>";
            echo "<strong>🎉 REGISTRATION SUCCESSFUL!</strong><br>";
            echo "The basic registration worked. The issue is in the complex validation logic.";
            echo "</div>";
        } else {
            throw new Exception("SQL execute failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #dc3545; color: white; padding: 10px; margin: 10px 0;'>";
        echo "<strong>❌ ERROR:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Minimal Registration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST" action="">
        <h3>Minimal Registration Test</h3>
        <p>This tests basic database insertion without complex validation.</p>
        
        <div class="form-group">
            <label>First Name:</label>
            <input type="text" name="first_name" value="Test" required>
        </div>
        
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" name="last_name" value="User" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="test@example.com" required>
        </div>
        
        <div class="form-group">
            <label>Phone:</label>
            <input type="tel" name="phone" value="0123456789" required>
        </div>
        
        <button type="submit">Test Registration</button>
    </form>
    
    <hr>
    <h4>Instructions:</h4>
    <ol>
        <li>Upload this file as <code>minimal_registration_test.php</code></li>
        <li>Access it in your browser</li>
        <li>Submit the form</li>
        <li>If this works, the issue is in your complex validation logic</li>
        <li>If this fails, check the error message for clues</li>
    </ol>
</body>
</html>