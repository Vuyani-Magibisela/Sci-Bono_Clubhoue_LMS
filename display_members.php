<?php
require 'server.php';

// Retrieve user data from the database
$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

// Generate HTML for user cards
$memberCards = '';
while ($row = mysqli_fetch_assoc($result)) {
    $userId = $row['id']; // Get user ID
    $username = $row['username'];
    $userType = $row['user_type'];

    // Generate HTML for each user card
    $memberCards .= '
        <div class="member-cards">
            <div class="memberImg" alt="User Profile Picture">
                <img src="public/images/memberImage.png" alt="Image of member">
            </div>
            <div class="memberName">
                <h3>'.$username.'</h3>
            </div>

            <div class="memberRole">
                <p>'.$userType.'</p>
            </div>

            <div class="viewProfile_Btn">
                <button onclick="signIn('.$userId.')">View Profile</button> <!-- Pass user ID to signIn function -->
            </div>
        </div>
    ';
}


// Close the database connection
mysqli_close($conn);
?>

<!-- Display the user cards -->
<?php echo $memberCards; ?>
<!-- <div class="user-cards">
    
</div> -->
