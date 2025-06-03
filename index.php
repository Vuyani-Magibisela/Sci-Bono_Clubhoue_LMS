<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse - Welcome</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./public/assets/css/screenSizes.css">
    <link rel="stylesheet" href="./public/assets/css/header.css">
    
    <!-- Enhanced styles for better landing page -->
    <style>
        /* Enhanced button styles for the attendance register link */
        .attendance-btn {
            background: linear-gradient(135deg, #F29A2E 0%, #E28A26 100%);
            color: #ffffff;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(242, 154, 46, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .attendance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(242, 154, 46, 0.4);
            background: linear-gradient(135deg, #E28A26 0%, #D67A1E 100%);
        }
        
        .attendance-btn:active {
            transform: translateY(0);
        }
        
        .attendance-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .attendance-btn:hover::before {
            left: 100%;
        }
        
        /* Icon styling */
        .attendance-btn svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        
        /* Enhanced existing button styles */
        .loginBtn {
            position: relative;
            overflow: hidden;
        }
        
        /* Status indicator for live attendance */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #10B981;
            margin-top: 0.5rem;
        }
        
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .attendance-btn {
                font-size: 1rem;
                padding: 0.875rem 1.5rem;
            }
        }
    </style>
    
    <!--Google analytics-->
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
    <div class="header">
        <?php include 'header.php'; ?>                           
    </div>
    
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
            <img src="public/assets/images/Login_img.png" alt="Illustrations of youth using technology">
            <img src="public/assets/images/MobileLoginImg.svg" alt="Large image of a mobile phone, human standing next to it." width="301" height="303">
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
            
            <div>
                <a href="login.php" class="loginBtn">Log In</a>
            </div>
            
            <!-- Updated Attendance Register Button -->
            <div>
                <a href="core/Router.php" class="attendance-btn" title="Daily Attendance Register">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Sign Attendance Register
                </a>

            </div>
            <div class="live-indicator">
                <div class="pulse-dot"></div>
                <span>Live attendance tracking</span>
            </div>
        </div>
    </main>

    <!-- Optional: Add some JavaScript for enhanced UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click tracking for analytics
            const attendanceBtn = document.querySelector('.attendance-btn');
            if (attendanceBtn) {
                attendanceBtn.addEventListener('click', function() {
                    // Google Analytics event tracking
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'click', {
                            'event_category': 'Navigation',
                            'event_label': 'Attendance Register'
                        });
                    }
                    
                    console.log('Navigating to attendance register...');
                });
            }
            
            // Add some hover effects
            const buttons = document.querySelectorAll('.attendance-btn, .loginBtn, .signupBtn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>