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
    <div id="top">
        <svg width="0" height="0" style="position: absolute;">
            <defs>
                <clipPath id="topClip" clipPathUnits="objectBoundingBox">
                <path d="M1 0.46c0 0-0.118-0.15-0.562-0.15C-0.005 0.31-0.005 0.23-0.005 0.23L-0.005 -0.98L1 -0.98L1 0.46Z" />
                </clipPath>
            </defs>
        </svg>
        
        <div class="title-center">
                <h1>Sci-Bono Clubhouse</h1>
        </div>
    </div>
    <main id="container-index">
        
        <div class="hero-img">
            <img src="public/assets/images/Login_img.png" alt="Illustrations of youth using technology" >
            <img src="public/assets/images/MobileLoginImg.svg" alt="Large image of a mobile phone, human standing next to it."  width="301" height="303">
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