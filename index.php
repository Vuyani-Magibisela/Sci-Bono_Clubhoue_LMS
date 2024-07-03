<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landpage</title>
    <link rel="stylesheet" href="style.css">

</head>
<body id="index">

    <?php
    include 'header.php';
    ?>

    <main id="container-index">

        <div class="hero-img">
            <img src="public/assets/images/Login_img.png" alt="Illustrations of youth using technology" >
        </div>

        <div class="log_signup-section">
            <div >
                <a href="signup.php"><button class="signup" >Sign Up</button></a>
            </div>
            
            <p>Already a member</p>
            <div>
                <a href="login.php" ><button class="login" >Log In</button></a>
            </div>
        </div>

    </main>

</body>
</html>