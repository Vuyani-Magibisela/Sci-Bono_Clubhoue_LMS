<?php 
    require __DIR__ . '/../../../config/config.php'; // Include the config file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Register - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="<?php echo BASE_URL?>/public/assets/css/modern-signin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Sci-Bono Clubhouse daily attendance registration system">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="<?php echo BASE_URL?>public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Logo" class="logo">
            </div>
            <h1 class="header-title">Clubhouse Daily Register</h1>
            <div class="logo-section">
                <img src="<?php echo BASE_URL?>public/assets/images/TheClubhouse_Logo_White_Large.png" alt="The Clubhouse Logo" class="logo">
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Sign-in Modal -->
        <div id="signin-modal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>Sign In Required</h3>
                    <button id="close-signin-modal" class="close-btn" aria-label="Close modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="error-message" id="error-message">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="#EF4444"/>
                            <path d="M10 6V10M10 14H10.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span id="error-text">Incorrect password. Please try again.</span>
                    </div>
                    <form id="signin-form" class="signin-form">
                        <div class="form-group">
                            <label for="userId" class="form-label">User ID</label>
                            <input type="text" id="userId" name="userId" class="form-input" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="submit-btn">
                            <span class="btn-text">Sign In</span>
                            <div class="loading-spinner"></div>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <div id="success-toast" class="toast success-toast">
            <div class="toast-content">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="#10B981"/>
                    <path d="M7 10L9 12L13 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Successfully signed out!</span>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Sign In Section -->
            <section class="signin-section">
                <div class="section-header">
                    <h2>Sign In</h2>
                    <p>Search for your name and select sign in. If you can't find your name, please ask a mentor for assistance.</p>
                </div>
                
                <div class="signin-container">
                    <!-- Search Bar -->
                    <div class="search-container">
                        <div class="search-wrapper">
                            <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input 
                                type="text" 
                                id="search-input" 
                                class="search-input" 
                                placeholder="Search by name or username..."
                                autocomplete="off"
                            >
                            <button id="clear-search" class="clear-btn" type="button" aria-label="Clear search">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- User Cards Grid -->
                    <div class="user-cards-container">
                        <div id="signin-cards" class="user-cards-grid">
                            <?php if (!empty($signedOutUsers)): ?>
                                <?php foreach ($signedOutUsers as $user): ?>
                                    <div class="user-card" data-user-id="<?php echo $user['id']; ?>" data-search-terms="<?php echo htmlspecialchars($user['search_terms']); ?>">
                                        <div class="user-avatar">
                                            <img src="<?php BASE_URL?>public/assets/images/ui-user-profile-negative.svg" alt="<?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?> Avatar" loading="lazy">
                                        </div>
                                        <div class="user-info">
                                            <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                                            <p class="user-fullname"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></p>
                                            <span class="user-role <?php echo $user['role_class']; ?>"><?php echo htmlspecialchars($user['user_type']); ?></span>
                                        </div>
                                        <button class="action-btn signin-btn" onclick="signIn(<?php echo $user['id']; ?>)" aria-label="Sign in <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 2L14 2V14L6 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 8H2M2 8L5 5M2 8L5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Sign In
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-results" style="display: flex;">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M24 28C30.6274 28 36 22.6274 36 16C36 9.37258 30.6274 4 24 4C17.3726 4 12 9.37258 12 16C12 22.6274 17.3726 28 24 28Z" stroke="currentColor" stroke-width="2"/>
                                        <path d="M4 44C4 36.268 10.268 30 18 30H30C37.732 30 44 36.268 44 44" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <h3>No users found</h3>
                                    <p>No users are available for sign-in at this time.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div id="no-results" class="no-results">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 38C30.3888 38 38 30.3888 38 21C38 11.6112 30.3888 4 21 4C11.6112 4 4 11.6112 4 21C4 30.3888 11.6112 38 21 38Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M44 44L32.16 32.16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 16L26 26M26 16L16 26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <h3>No users found</h3>
                            <p>Try adjusting your search terms or check with a mentor if you can't find your name.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Signed In Section -->
            <section class="signedin-section">
                <div class="section-header">
                    <h2>Signed In Members</h2>
                    <div class="member-count">
                        <span id="member-count"><?php echo $signedInCount; ?></span> members present
                    </div>
                </div>
                
                <div class="signedin-container">
                    <div id="signout-cards" class="user-cards-list">
                        <?php if (!empty($signedInUsers)): ?>
                            <?php foreach ($signedInUsers as $user): ?>
                                <div class="user-card signed-in" data-user-id="<?php echo $user['id']; ?>">
                                    <div class="user-avatar">
                                        <img src="<?php echo BASE_URL?>public/assets/images/ui-user-profile-negative.svg" alt="<?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?> Avatar" loading="lazy">
                                        <div class="online-indicator"></div>
                                    </div>
                                    <div class="user-info">
                                        <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                                        <p class="user-fullname"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></p>
                                        <span class="user-role <?php echo $user['role_class']; ?>"><?php echo htmlspecialchars($user['user_type']); ?></span>
                                        <div class="signin-time">
                                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 1V6L8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                <circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/>
                                            </svg>
                                            Signed in at <?php echo date('H:i', strtotime($user['checked_in'])); ?>
                                        </div>
                                    </div>
                                    <button class="action-btn signout-btn" onclick="signOut(<?php echo $user['id']; ?>)" aria-label="Sign out <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6 14H2V2H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 8H6M10 8L7 5M10 8L7 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 2H14V14H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Sign Out
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-members" style="display: flex;">
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 28C30.6274 28 36 22.6274 36 16C36 9.37258 30.6274 4 24 4C17.3726 4 12 9.37258 12 16C12 22.6274 17.3726 28 24 28Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 44C4 36.268 10.268 30 18 30H30C37.732 30 44 36.268 44 44" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <h3>No members signed in</h3>
                                <p>When members sign in, they'll appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="<?php echo BASE_URL?>public/assets/js/script.js"></script>

</body>
</html>