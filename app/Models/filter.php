<?php
require '../../server.php'; // Your database connection file

// Collect filter parameters
$search = isset($_POST['search']) ? $_POST['search'] : '';
// $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
// $grade = isset($_POST['grade']) ? $_POST['grade'] : '';

// Start building the query
$query = "SELECT * FROM users WHERE 1=1";

// Add search condition
if (!empty($search)) {
    $query .= " AND (name LIKE ? OR surname LIKE ? OR username LIKE ?)";
}

// Add gender filter
// if (!empty($gender)) {
//     $query .= " AND gender = ?";
// }

// Add grade filter
// if (!empty($grade)) {
//     $query .= " AND grade = ?";
// }

// Prepare and execute the statement
$stmt = mysqli_prepare($conn, $query);

// Bind parameters
if (!empty($search)) {
    $searchParam = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $searchParam, $searchParam, $searchParam);
}
// if (!empty($gender)) {
//     mysqli_stmt_bind_param($stmt, "s", $gender);
// }
// if (!empty($grade)) {
//     mysqli_stmt_bind_param($stmt, "i", $grade);
// }

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Output results
if (mysqli_num_rows($result) > 0) {

    
    echo "<table>
            <tr>
                <th>Name</th>
                <th>Surname</th>
                <th>Username</th>
                <th>Gender</th>
                <th>Grade</th>
            </tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>".$row['name']."</td>
                <td>".$row['surname']."</td>
                <td>".$row['username']."</td>
                <td>".$row['gender']."</td>
                <td>".$row['grade']."</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No results found.";
}

mysqli_close($conn);
