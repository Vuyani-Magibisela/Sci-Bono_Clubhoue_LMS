<?php
session_start();
require 'server.php';

// Check if the user is an admin
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: home.php');
    exit;
}

// Fetch user data
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    header('Location: user_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Edit User</h1>
    <form action="update_user.php" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
        
        <label for="user_type">User Type:</label>
        <select id="user_type" name="user_type" required>
            <option value="member" <?php if ($user['user_type'] == 'member') echo 'selected'; ?>>Member</option>
            <option value="mentor" <?php if ($user['user_type'] == 'mentor') echo 'selected'; ?>>Mentor</option>
            <option value="admin" <?php if ($user['user_type'] == 'admin') echo 'selected'; ?>>Admin</option>
        </select>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
        
        <button type="submit">Update User</button>
    </form>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
