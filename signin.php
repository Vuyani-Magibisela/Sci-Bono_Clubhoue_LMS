<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Register</title>
    <link rel="sytlesheet" href="style.css">
    <link rel="stylesheet" href="screenSizes.css">

</head>
<body>
    <?php 
    include 'header.php';
    include 'display_users.php'
    ?>

    <main id="signin-container">
        <div class="dailyRegTitle">
            <h1>Clubhouse Daily Register</h1>
        </div>

        <div id="signin-modal" class="modal">
            <div class="modal-content">
                <span id="close-signin-modal" class="close">&times;</span>
                <p class="incorrectPassword">INCORRECT PASSWOARD TRY AGAIN!!!</p>
                <form id="signin-form">
                    <label for="userId">User ID:</label>
                    <input type="text" id="userId" name="userId">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password">
                    <button type="submit" >Sign In</button>
                </form>
            </div>
        </div> 
        
        <div id="signOut-modal">
            <p>successfully Signed Out</p>
        </div>


        <div id="userSign-in_OutContainer">
            
            <div class="userSignin">
                
                <p>Search For your name and select sign in, if you can not find your name please ask a mentor for assistance</p><br>
                
                <div class="userSignin-box">
                    <div class="search_users">
                        <label for="search" class="search-label">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.2502 22.1668C17.727 22.1668 22.1668 17.727 22.1668 12.2502C22.1668 6.77336 17.727 2.3335 12.2502 2.3335C6.77336 2.3335 2.3335 6.77336 2.3335 12.2502C2.3335 17.727 6.77336 22.1668 12.2502 22.1668Z" fill="#6C63FF" stroke="black" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M15.5499 8.36681C14.7054 7.52232 13.5387 7 12.25 7C10.9614 7 9.79469 7.52232 8.9502 8.36681" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19.3794 19.3794L24.3292 24.3292" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </label>
                        <input type="text" id="search" class="search-input" name="search_users" onfocus="hideLabel()" onblur="showLabel()">  
                    </div>

                    <div class="signInUserCards user-cards">
                        <?php echo   $signInUserCards; ?>
                    </div> 
                </div>
            </div>

            <div class="userSignedin">
                <p>Signed in Members</p>
                <div class="userSingedin-box">
                    <!--Add condition if checked in field is selected dont show if not show-->

                        <div class="signOutUserCards ">
                        <?php echo $signOutUserCards; ?>
                        </div>
                  
                </div> 
            </div>
        </div>
            
        </div>

    </main>
    
    <script src="script.js"></script>
</body>
</html>