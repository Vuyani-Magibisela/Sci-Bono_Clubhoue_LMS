<?php
/**
 * Holiday Programs Index View
 *
 * Displays all available holiday programs
 * Data passed from ProgramController@index():
 * - $programs: Array of available programs
 * - $isLoggedIn: Boolean indicating if user is logged in
 * - $userEmail: User's email if logged in
 *
 * Phase 3 Week 3: Updated to use ProgramController
 */

// Determine current page for navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse Holiday Programs</title>
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/holidayProgramIndex.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/holidayProgramStyles.css">
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
    <?php include __DIR__ . '/shared/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Sci-Bono Clubhouse Holiday Programs</h1>
            <p>Explore, Create, Innovate: Join our exciting holiday programs designed to spark creativity and develop technical skills in a fun and collaborative environment.</p>
            <?php if ($isLoggedIn): ?>
                <div class="user-welcome">
                    <p>Welcome back, <?php echo htmlspecialchars($userEmail); ?>!
                    <a href="/Sci-Bono_Clubhoue_LMS/programs/my-programs" class="text-link">View My Programs</a></p>
                </div>
            <?php endif; ?>
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
                <?php if (!empty($programs)): ?>
                    <?php foreach ($programs as $program): ?>
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
                        $registrationOpen = ($program['registration_status'] ?? 'closed') === 'open_registration';
                        $registrationButton = '';

                        if ($registrationOpen) {
                            $registrationButton = "<a href='/Sci-Bono_Clubhoue_LMS/programs/{$program['id']}#register' class='register-btn'>Register Now</a>";
                        } else {
                            $registrationButton = "<span class='coming-soon'>Registration Closed</span>";
                        }

                        // Safely get dates
                        $dates = $program['dates'] ?? ($program['start_date'] . ' - ' . $program['end_date']);

                        // Get registration count
                        $registrationCount = $program['current_registrations'] ?? 0;
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
                                    <?php if (!empty($program['location'])): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($program['location']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($program['age_range'])): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-child"></i>
                                        <?php echo htmlspecialchars($program['age_range']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo htmlspecialchars(substr($program['description'] ?? '', 0, 150)) . (strlen($program['description'] ?? '') > 150 ? '...' : ''); ?></p>
                                <div class="program-action">
                                    <?php echo $registrationButton; ?>
                                    <a href="/Sci-Bono_Clubhoue_LMS/programs/<?php echo $program['id']; ?>" class="details-link">View Details</a>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="no-programs">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Programs Available</h3>
                        <p>Check back soon for upcoming holiday programs!</p>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits">
        <div class="container">
            <div class="section-header">
                <h2>Why Join Our Programs?</h2>
                <div class="underline"></div>
            </div>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <i class="fas fa-hands-helping"></i>
                    <h3>Hands-On Learning</h3>
                    <p>Work on real projects with cutting-edge technology and tools</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Expert Mentors</h3>
                    <p>Learn from experienced professionals passionate about education</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-trophy"></i>
                    <h3>Certificates</h3>
                    <p>Receive certificates of completion to showcase your skills</p>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-globe-africa"></i>
                    <h3>SDG Alignment</h3>
                    <p>Projects aligned with UN Sustainable Development Goals</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Your Journey?</h2>
            <p>Join thousands of students who have discovered their passion through our programs</p>
            <?php if ($isLoggedIn): ?>
                <a href="/Sci-Bono_Clubhoue_LMS/programs/my-programs" class="cta-button-alt">View My Programs</a>
            <?php else: ?>
                <a href="/Sci-Bono_Clubhoue_LMS/holiday-login" class="cta-button-alt">Login</a>
                <a href="#programs" class="cta-button-secondary">Browse Programs</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Sci-Bono Clubhouse</h4>
                    <p>Empowering young minds through technology and innovation</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#programs">Programs</a></li>
                        <li><a href="/Sci-Bono_Clubhoue_LMS/holiday-login">Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p><i class="fas fa-envelope"></i> info@sci-bono.co.za</p>
                    <p><i class="fas fa-phone"></i> +27 11 639 8400</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Sci-Bono Clubhouse. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
