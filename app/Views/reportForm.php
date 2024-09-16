<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
	header("Location: ../../login.php");
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Data Input</title>
    <link rel="Stylesheet" href="../../public/assets/css/statsDashboardStyle.css">

</head>
<body id="reportForm">
    <h1>Program Data Input</h1>
    <form id="programForm" action="../Controllers/submit_report_data.php" method="post" enctype="multipart/form-data">
        <div id="programForms">
            <div class="program-form">
            <label for="program0">Program:</label>
                <select name="programs[]" id="program0" required>
                    <option value="">Select a program</option>
                    <?php
                    // Fetch programs from the database
                    // Database connection details
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "accounts";

                    $conn = new mysqli($servername, $username, $password, $dbname);
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    $sql = "SELECT id, title FROM clubhouse_programs";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["title"]) . "</option>";
                        }
                    }
           
                    ?>
                </select>

                <label for="participants0">Number of Participants:</label>
                <input type="number" name="participants[]" id="participants0" required>

                <label for="narrative0">Narrative:</label>
                <textarea name="narratives[]" id="narrative0" rows="4" required></textarea>
                
                <label for="challenges0">Challenges:</label>
                <textarea name="challenges[]" id="challenges0" rows="4" required></textarea>

                <label for="image0">Upload Image:</label>
                <input type="file" name="images[]" id="image0" accept="image/*" multiple required>
            </div>
        </div>
        <button type="button" id="addProgram">Add New Program</button>
        <button type="submit">Submit</button>
    </form>

    <script>
        let programCount = 1;

        document.getElementById('addProgram').addEventListener('click', function() {
            const programForms = document.getElementById('programForms');
            const newForm = document.createElement('div');
            newForm.className = 'program-form';
            newForm.innerHTML = `
                <label for="program${programCount}">Program:</label>
                <select name="programs[]" id="program${programCount}" required>
                    <option value="">Select a program</option>
                    <?php
                    // Fetch programs from the database
                    // Database connection details
                    // Reopen database connection
                    $conn = new mysqli($servername, $username, $password, $dbname);
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["title"]) . "</option>";
                        }
                    }
                    $conn->close();
           
                    ?>
                </select>

                <label for="participants${programCount}">Number of Participants:</label>
                <input type="number" name="participants[]" id="participants${programCount}" required>

                <label for="narrative${programCount}">Narrative:</label>
                <textarea name="narratives[]" id="narrative${programCount}" rows="4" required></textarea>
                
                <label for="challenges${programCount}">Challenges:</label>
                <textarea name="challenges[]" id="challenges0" rows="4" required></textarea>

                <label for="image${programCount}">Upload Image:</label>
                <input type="file" name="images[]" id="image${programCount}" accept="image/*">
            `;
            programForms.appendChild(newForm);
            programCount++;
        });
    </script>
</body>
</html>