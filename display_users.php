<?php
require 'server.php';

// Retrieve user data from the database
$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

// Generate HTML for user cards
$userCards = '';
while ($row = mysqli_fetch_assoc($result)) {
    $username = $row['username'];
    $userType = $row['user_type'];

    // Generate HTML for each user card
    $userCards .= '
        <div class="userSignin_card">
            <div class="proImg" alt="User Profile Picture">
                <img src="" alt="">
            </div>
            <div class="userName">
                <h3>'.$username.'</h3>
            </div>

            <div class="userRole">
                <p>'.$userType.'</p>
            </div>

            <div class="signBtn">
                <button>Sign in</button>
            </div>
        </div>
    ';
}

// Close the database connection
mysqli_close($conn);
?>

<!-- Display the user cards -->
<?php echo $userCards; ?>
<!-- <div class="user-cards">
    
</div> -->
