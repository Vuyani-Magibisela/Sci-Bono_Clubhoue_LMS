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
    $userName = $row['name'];
    $userSurname = $row['surname'];
    

    // Check if the user is signed in
    $attendanceSql = "SELECT * FROM attendance WHERE user_id = $userId AND sign_in_status = 'signedIn'";
   // $attendanceSatus = "SELECT * FROM attendance WHERE user_id = $userId AND DATE(checked_out) = CURDATE() AND sign_in_status = 'signedOut'";
    //$attendanceStatusResults = mysqli_query($conn, $attendanceSatus);
    $attendanceResult = mysqli_query($conn, $attendanceSql);
    $isSignedIn = mysqli_num_rows($attendanceResult) > 0;
   // $statusIsSignedOut = mysqli_num_rows($attendanceStatusResults) > 0;

    if ($isSignedIn) {
        // User is signed in, display the card in the signOutUserCards section
        // Generate HTML for user card
        $userCardHTML = '
            <div id="userCard'.$userId.'" class="userCard userSignin_card">
                <div class="proImg" alt="User Profile Picture">
                    <img src="" alt="">
                </div>
                <div class="userName">
                    <h3>'.$username.'</h3>
                    <h6>'.$userName.' '.$userSurname.'</h6>
                </div>
                <div class="userRole">
                    <p>'.$userType.'</p>
                </div>
                <div class="actionBtn signBtn">
                    <button class="signOutBtn" onclick="signOut('.$userId.')">Sign Out</button>
                </div>
            </div>';

        $signOutUserCards .= $userCardHTML;
    } else {
        // User is signed out, display the card in the signInUserCards section
        // Generate HTML for user card
        $userCardHTML = '
            <div id="userCard'.$userId.'" class="userCard userSignin_card">
                <div class="proImg" alt="User Profile Picture">
                    <img src="" alt="">
                </div>
                <div class="userName">
                    <h3>'.$username.'</h3>
                    <h6>'.$userName.' '.$userSurname.'</h6>
                </div>
                <div class="userRole">
                    <p>'.$userType.'</p>
                </div>
                <div class="actionBtn signBtn">
                    <button class="signInBtn" onclick="signIn('.$userId.')">Sign In</button>
                </div>
            </div>';

        $signInUserCards .= $userCardHTML;
    }
}

// Close the database connection
mysqli_close($conn);
