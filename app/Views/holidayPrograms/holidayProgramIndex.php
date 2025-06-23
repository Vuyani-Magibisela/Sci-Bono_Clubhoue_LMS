
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and required files
require_once '../../../server.php';

// Try to include the models - with error handling
$holidayProgramModel = null;
$activePrograms = [];
$allPrograms = [];

// First try HolidayProgramCreationModel
if (file_exists('../../Models/HolidayProgramCreationModel.php')) {
    require_once '../../Models/HolidayProgramCreationModel.php';
    
    if (class_exists('HolidayProgramCreationModel')) {
        $holidayProgramModel = new HolidayProgramCreationModel($conn);
        
        // Check if getAllPrograms method exists
        if (method_exists($holidayProgramModel, 'getAllPrograms')) {
            try {
                $allPrograms = $holidayProgramModel->getAllPrograms();
                $activePrograms = $holidayProgramModel->getProgramsByStatus('active');
            } catch (Exception $e) {
                error_log("Error getting programs: " . $e->getMessage());
            }
        } else {
            // Method doesn't exist, get programs manually
            $allPrograms = getHolidayProgramsManually($conn);
            $activePrograms = getActiveHolidayProgramsManually($conn);
        }
    }
} else {
    // Model file doesn't exist, get programs manually
    $allPrograms = getHolidayProgramsManually($conn);
    $activePrograms = getActiveHolidayProgramsManually($conn);
}

// Fallback functions to get programs directly from database
function getHolidayProgramsManually($conn) {
    $programs = [];
    
    $sql = "SELECT 
                id, 
                term, 
                title, 
                description, 
                dates, 
                start_date, 
                end_date, 
                registration_open,
                max_participants,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
            FROM holiday_programs 
            ORDER BY start_date DESC, created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    
    return $programs;
}

function getActiveHolidayProgramsManually($conn) {
    $programs = [];
    
    $sql = "SELECT 
                id, 
                term, 
                title, 
                description, 
                dates, 
                start_date, 
                end_date, 
                registration_open,
                max_participants,
                created_at,
                updated_at,
                (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as registration_count
            FROM holiday_programs 
            WHERE registration_open = 1
            ORDER BY start_date DESC, created_at DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    
    return $programs;
}

// Determine current page for navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse Holiday Programs</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramIndex.css">
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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
<body>
    <!-- Header from main site -->
   <?php include './holidayPrograms-header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Sci-Bono Clubhouse Holiday Programs</h1>
            <p>Explore, Create, Innovate: Join our exciting holiday programs designed to spark creativity and develop technical skills in a fun and collaborative environment.</p>
            <a href="#programs" class="cta-button">Explore Programs</a>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="section-header">
                <h2>About Our Holiday Programs</h2>
                <div class="underline"></div>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <p>The Sci-Bono Clubhouse Holiday Programs offer immersive learning experiences for young minds during school breaks. Our programs combine creativity, technology, and collaboration to provide hands-on learning opportunities that inspire innovation and develop essential 21st-century skills.</p>
                    <p>Whether you're interested in digital design, programming, electronics, or multimedia creation, our expert mentors will guide you through exciting projects that make learning both fun and meaningful.</p>
                </div>
                <div class="about-features">
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Expert Mentorship</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-project-diagram"></i>
                        <span>Real Projects</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-certificate"></i>
                        <span>Certificates</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Programs Section -->
    <section class="programs" id="programs">
        <div class="container">
            <div class="section-header">
                <h2>Current Holiday Programs</h2>
                <div class="underline"></div>
                <p>Choose from our exciting range of holiday programs designed to spark creativity and build technical skills</p>
            </div>
            
            <div class="programs-grid">
                <?php if (!empty($allPrograms)): ?>
                    <?php foreach ($allPrograms as $program): ?>
                        <?php
                        // Determine program icon and color based on title or term
                        $programIcon = "fas fa-laptop-code";
                        $programColor = "program-tech";
                        
                        $title = $program['title'] ?? '';
                        if (stripos($title, 'digital') !== false || stripos($title, 'design') !== false) {
                            $programIcon = "fas fa-paint-brush";
                            $programColor = "program-design";
                        } elseif (stripos($title, 'robotics') !== false || stripos($title, 'electronics') !== false) {
                            $programIcon = "fas fa-robot";
                            $programColor = "program-robotics";
                        } elseif (stripos($title, 'game') !== false || stripos($title, 'vr') !== false) {
                            $programIcon = "fas fa-gamepad";
                            $programColor = "program-gaming";
                        } elseif (stripos($title, 'web') !== false || stripos($title, 'app') !== false) {
                            $programIcon = "fas fa-code";
                            $programColor = "program-web";
                        }
                        
                        // Determine if registration is available and program status
                        $registrationButton = '';
                        $detailsButton = '';
                        $programStatus = '';
                        
                        $isRegistrationOpen = ($program['registration_open'] ?? 0) == 1;
                        
                        if ($isRegistrationOpen) {
                            // Registration is open
                            $registrationButton = "<a href='holidayProgramRegistration.php?program_id={$program['id']}' class='register-btn active'>Register Now</a>";
                            $programStatus = "<span class='status-badge open'>Registration Open</span>";
                        } else {
                            // Registration is closed
                            $registrationButton = "<span class='register-btn disabled'>Registration Closed</span>";
                            $programStatus = "<span class='status-badge closed'>Registration Closed</span>";
                        }
                        
                        // Always show details button if program information is available
                        if (!empty($program['title']) && !empty($program['term'])) {
                            $detailsButton = "<a href='./holiday-program-details-term.php?id={$program['id']}' class='details-link'>View Details</a>";
                        }
                        
                        // Calculate capacity if data is available
                        $capacityHtml = '';
                        if (isset($program['registration_count']) && isset($program['max_participants']) && $program['max_participants'] > 0) {
                            $registrationCount = (int)$program['registration_count'];
                            $maxCapacity = (int)$program['max_participants'];
                            $capacityPercentage = min(($registrationCount / $maxCapacity) * 100, 100);
                            
                            if ($isRegistrationOpen) {
                                $capacityHtml = "
                                <div class='capacity-indicator'>
                                    <div class='capacity-bar'>
                                        <div class='capacity-fill' style='width: {$capacityPercentage}%'></div>
                                    </div>
                                    <span class='capacity-text'>{$registrationCount}/{$maxCapacity} participants</span>
                                </div>";
                            }
                        }
                        ?>
                        
                        <div class="program-card <?php echo $programColor; ?>" data-program-id="<?php echo $program['id']; ?>">
                            <div class="program-status-indicator">
                                <?php echo $programStatus; ?>
                            </div>
                            <div class="program-icon">
                                <i class="<?php echo $programIcon; ?>"></i>
                            </div>
                            <div class="program-info">
                                <h3><?php echo htmlspecialchars($program['term'] ?? 'Holiday Program'); ?>: <?php echo htmlspecialchars($program['title'] ?? 'Program Title'); ?></h3>
                                <div class="program-dates">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo htmlspecialchars($program['dates'] ?? 'Dates TBA'); ?>
                                </div>
                                <p><?php echo htmlspecialchars($program['description'] ?? 'Program description coming soon...'); ?></p>
                                
                                <!-- Program capacity indicator -->
                                <?php echo $capacityHtml; ?>
                                
                                <div class="program-action">
                                    <?php echo $registrationButton; ?>
                                    <?php if ($detailsButton): ?>
                                        <?php echo $detailsButton; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default/fallback programs if none in database -->
                    <div class="program-card program-design">
                        <div class="program-status-indicator">
                            <span class="status-badge coming-soon">Coming Soon</span>
                        </div>
                        <div class="program-icon">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <div class="program-info">
                            <h3>Term 1: Multi-Media - Digital Design</h3>
                            <div class="program-dates">
                                <i class="fas fa-calendar-alt"></i> March 31 - April 4, 2025
                            </div>
                            <p>Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.</p>
                            <div class="program-action">
                                <span class="register-btn disabled">Registration Opens Soon</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="program-card program-tech">
                        <div class="program-status-indicator">
                            <span class="status-badge coming-soon">Coming Soon</span>
                        </div>
                        <div class="program-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div class="program-info">
                            <h3>Term 2: Programming & Web Development</h3>
                            <div class="program-dates">
                                <i class="fas fa-calendar-alt"></i> June 16 - June 20, 2025
                            </div>
                            <p>Learn programming fundamentals and create your own websites and web applications using modern technologies.</p>
                            <div class="program-action">
                                <span class="register-btn disabled">Registration Opens Soon</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="program-card program-robotics">
                        <div class="program-status-indicator">
                            <span class="status-badge coming-soon">Coming Soon</span>
                        </div>
                        <div class="program-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="program-info">
                            <h3>Term 3: Robotics & Electronics</h3>
                            <div class="program-dates">
                                <i class="fas fa-calendar-alt"></i> September 22 - September 26, 2025
                            </div>
                            <p>Build and program robots while learning about electronics, sensors, and automation technologies.</p>
                            <div class="program-action">
                                <span class="register-btn disabled">Registration Opens Soon</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="program-card program-gaming">
                        <div class="program-status-indicator">
                            <span class="status-badge coming-soon">Coming Soon</span>
                        </div>
                        <div class="program-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="program-info">
                            <h3>Term 4: Game Development & VR</h3>
                            <div class="program-dates">
                                <i class="fas fa-calendar-alt"></i> December 15 - December 19, 2025
                            </div>
                            <p>Create interactive games and explore virtual reality experiences using cutting-edge development tools.</p>
                            <div class="program-action">
                                <span class="register-btn disabled">Registration Opens Soon</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2>What Makes Our Holiday Programs Special</h2>
                <div class="underline"></div>
            </div>
            <div class="feature-cards">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Expert Mentors</h3>
                    <p>Learn from experienced professionals and educators who are passionate about technology and creativity.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Hands-On Learning</h3>
                    <p>Build real projects that you can showcase in your portfolio and develop practical skills for the future.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Collaborative Environment</h3>
                    <p>Work alongside peers in a supportive clubhouse environment that encourages collaboration and creativity.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Certificate of Completion</h3>
                    <p>Receive recognition for your achievements and document your learning journey with official certificates.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Learning Journey?</h2>
                <p>Join thousands of young innovators who have discovered their passion for technology through our holiday programs.</p>
                <div class="cta-buttons">
                    <a href="#programs" class="cta-button primary">View Programs</a>
                    <a href="holidayProgramLogin.php" class="cta-button secondary">Login to Dashboard</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="holiday-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Sci-Bono Discovery Centre. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../../../public/assets/js/holidayProgramIndex.js"></script>
    <script>
        // Auto-refresh program status every 30 seconds
        setInterval(function() {
            // Only refresh if page is visible
            if (!document.hidden) {
                // Check for status updates without full page reload
                checkForProgramUpdates();
            }
        }, 30000);
        
        function checkForProgramUpdates() {
            // Simple AJAX call to check for updates
            fetch('./api/get-all-program-status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_updates) {
                        // Reload page if there are updates
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.log('Update check failed:', error);
                });
        }
    </script>
</body>
</html>