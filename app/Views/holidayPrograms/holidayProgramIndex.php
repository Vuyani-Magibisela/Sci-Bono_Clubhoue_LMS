<?php
// Fixed holidayProgramIndex.php - Use manual queries instead of broken model
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../server.php';

// Force manual queries instead of using the broken model
$allPrograms = [];
$activePrograms = [];

// Function to get all programs manually (WORKING)
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
                location,
                age_range,
                time,
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

// Function to get active programs manually (WORKING)
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
                location,
                age_range,
                time,
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

// Get programs using manual queries (bypassing broken model)
try {
    $allPrograms = getHolidayProgramsManually($conn);
    $activePrograms = getActiveHolidayProgramsManually($conn);
    
    // Debug output (remove after testing)
    error_log("holidayProgramIndex: Found " . count($allPrograms) . " total programs");
    error_log("holidayProgramIndex: Found " . count($activePrograms) . " active programs");
    
} catch (Exception $e) {
    error_log("Error getting programs manually: " . $e->getMessage());
    $allPrograms = [];
    $activePrograms = [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-156064280-1"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-156064280-1');
    </script>
</head>
<body>
    <!-- Header -->
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
                        
                        $title = $program['title'] ?? $program['term'] ?? 'Holiday Program';
                        $titleLower = strtolower($title);
                        
                        if (strpos($titleLower, 'ai') !== false || strpos($titleLower, 'artificial') !== false) {
                            $programIcon = "fas fa-brain";
                            $programColor = "program-ai";
                        } elseif (strpos($titleLower, 'robotics') !== false || strpos($titleLower, 'robot') !== false) {
                            $programIcon = "fas fa-robot";
                            $programColor = "program-robotics";
                        } elseif (strpos($titleLower, 'web') !== false || strpos($titleLower, 'website') !== false) {
                            $programIcon = "fas fa-globe";
                            $programColor = "program-web";
                        } elseif (strpos($titleLower, 'game') !== false || strpos($titleLower, 'gaming') !== false) {
                            $programIcon = "fas fa-gamepad";
                            $programColor = "program-gaming";
                        } elseif (strpos($titleLower, 'design') !== false || strpos($titleLower, 'graphics') !== false || strpos($titleLower, 'media') !== false) {
                            $programIcon = "fas fa-paint-brush";
                            $programColor = "program-design";
                        }
                        
                        // Format registration button
                        $registrationButton = '';
                        if ($program['registration_open']) {
                            $registrationButton = "<a href='simple_registration.php?program_id={$program['id']}' class='register-btn'>Register Now</a>";
                        } else {
                            $registrationButton = "<span class='coming-soon'>Registration Closed</span>";
                        }
                        
                        // Safely get dates
                        $dates = $program['dates'] ?? ($program['start_date'] . ' - ' . $program['end_date']);
                        
                        // Get registration count
                        $registrationCount = $program['registration_count'] ?? 0;
                        $maxParticipants = $program['max_participants'] ?? 30;
                        ?>
                        
                        <div class="program-card <?php echo $programColor; ?>">
                            <div class="program-icon">
                                <i class="<?php echo $programIcon; ?>"></i>
                            </div>
                            <div class="program-info">
                                <h3><?php echo htmlspecialchars($program['term'] ?? 'Term'); ?>: <?php echo htmlspecialchars($program['title']); ?></h3>
                                <div class="program-dates">
                                    <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($dates); ?>
                                </div>
                                <div class="program-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-users"></i> 
                                        <?php echo $registrationCount; ?>/<?php echo $maxParticipants; ?> registered
                                    </span>
                                    <?php if ($program['location']): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($program['location']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo htmlspecialchars(substr($program['description'] ?? '', 0, 150)) . (strlen($program['description'] ?? '') > 150 ? '...' : ''); ?></p>
                                <div class="program-action">
                                    <?php echo $registrationButton; ?>
                                    <a href="holiday-program-details-term.php?id=<?php echo $program['id']; ?>" class="details-link">View Details</a>
                                </div>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- Fallback when no programs found -->
                    <div class="no-programs-message">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                            <h3>No Programs Available</h3>
                            <p>There are currently no holiday programs scheduled. Please check back later!</p>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                                <div style="margin-top: 20px;">
                                    <a href="holidayProgramCreationForm.php" class="cta-button">
                                        <i class="fas fa-plus"></i> Create First Program
                                    </a>
                                </div>
                            <?php endif; ?>
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
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Industry Recognition</h3>
                    <p>Receive certificates of completion and gain recognition for your achievements in technology and innovation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Sci-Bono Clubhouse</h3>
                    <p>Inspiring the next generation of innovators through hands-on learning and creative exploration.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Sci-Bono Discovery Centre</p>
                    <p><i class="fas fa-phone"></i> +27 11 639 8400</p>
                    <p><i class="fas fa-envelope"></i> info@sci-bono.co.za</p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Sci-Bono Discovery Centre. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
    .no-programs-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
    }

    .empty-state {
        background: white;
        padding: 3rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .program-meta {
        margin: 10px 0;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #666;
        font-size: 0.85rem;
    }

    .program-ai { border-top: 4px solid #8B5CF6; }
    .program-design { border-top: 4px solid #F59E0B; }
    .program-tech { border-top: 4px solid #3B82F6; }
    .program-robotics { border-top: 4px solid #EF4444; }
    .program-web { border-top: 4px solid #10B981; }
    .program-gaming { border-top: 4px solid #EC4899; }
    </style>
</body>
</html>