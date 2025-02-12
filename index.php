<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landpage</title>
    <!-- <link rel="stylesheet" href="style.css"> -->
    <link rel="stylesheet" href="./public/assets/css/screenSizes.css">
    <!--Google analytics
	================================================-->
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-156064280-1"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', 'UA-156064280-1');
	</script>

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
            <img src="public/assets/images/MobileLoginImg.svg" alt="Large image of a mobile phone, human standing next to it." width="301" height="303" >
        </div>
        
        <div class="log_signup-section">
            <div id="bottom">
                <svg width="0" height="0" style="position: absolute;">
                    <defs>
                        <clipPath id="bottomClip" clipPathUnits="objectBoundingBox">
                        <path d="M0.202236 0C0.202236 0 -13.0081 87.0038 174.108 87.0038C361.225 87.0038 375 139.377 375 139.377V609H0.202236V0Z" />
                        </clipPath>
                    </defs>
                </svg>
            </div>

            <div class="signup">
                <a href="signup.php" class="signupBtn">Sign Up</a>
            </div>
            
            <p>Already a member</p>
            <div >
                <a href="login.php" class="loginBtn" >Log In</a>
            </div>
        </div>

    </main>

</body>
</html>