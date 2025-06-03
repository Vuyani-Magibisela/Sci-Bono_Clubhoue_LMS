<?php
// Check if a session is not already active
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $destination = "home.php";
} else {
    $destination = "login.php";
}

require_once 'config/config.php';

?>

<html lang="en">
<head>
    <title></title>
    <!-- <link rel="stylesheet" href="style.css"> -->
    <!-- <link rel="stylesheet" href="public\assets\css\screenSizes.css"> -->

</head>
<body>
   <header class="header">
        <div class="logo-left">
            <a href="<?php echo $destination; ?>"><img src="<?php echo BASE_URL?>public/assets/images/Sci-Bono logo White.png" alt="Left Logo" width="" height="121"></a>
        </div>
        <div class="title-center">
            <h1>Sci-Bono Clubhouse</h1>
        </div>
        <div class="logo-right">
            <img src="<?php echo BASE_URL?>public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Right Logo" width="" height="110">
        </div>
    </header> 
</body>
</html>