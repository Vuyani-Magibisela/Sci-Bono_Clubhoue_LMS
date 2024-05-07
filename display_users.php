<?php
require 'server.php';

// Retrieve user data from the database
$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

// Initialize variables to store HTML for sign-in and sign-out user cards
$signInUserCards = '';
$signOutUserCards = '';

while ($row = mysqli_fetch_assoc($result)) {
    $userId = $row['id']; // Get user ID
    $username = $row['username'];
    $userType = $row['user_type'];

    // Check if the user is signed in
    $attendanceSql = "SELECT * FROM attendance WHERE user_id = $userId AND DATE(checked_in) = CURDATE()";
    $attendanceResult = mysqli_query($conn, $attendanceSql);
    $isSignedIn = mysqli_num_rows($attendanceResult) > 0;

    // Generate HTML for user card based on sign-in status
    $userCardHTML = '
        <div class="userCard userSignin_card">
            <div class="proImg" alt="User Profile Picture">
                <img src="" alt="">
            </div>
            <div class="userName">
                <h3>'.$username.'</h3>
            </div>
            <div class="userRole">
                <p>'.$userType.'</p>
            </div>
            <div class="actionBtn signBtn">
    ';

    if ($isSignedIn) {
        // User is signed in, display sign-out button
        $userCardHTML .= '<button class="signOutBtn" onclick="signOut('.$userId.')">Sign Out</button>';
        $signOutUserCards .= $userCardHTML.'</div></div>';
    } else {
        // User is not signed in, display sign-in button
        $userCardHTML .= '<button class="signInBtn" onclick="signIn('.$userId.')">Sign In</button>';
        $signInUserCards .= $userCardHTML.'</div></div>';
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!-- Display the user cards -->

<!-- <div class="user-cards">
    
</div> -->
