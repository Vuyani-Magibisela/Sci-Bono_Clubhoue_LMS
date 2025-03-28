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
                    <p>The Sci-Bono Clubhouse Holiday Programs offer immersive learning experiences for young minds during school breaks. Each term features different themes, from multimedia and digital design to robotics and AI.</p>
                    <p>Our goal is to provide a supportive environment where participants can:</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Develop technical skills through hands-on projects</li>
                        <li><i class="fas fa-check-circle"></i> Collaborate with peers and experienced mentors</li>
                        <li><i class="fas fa-check-circle"></i> Explore emerging technologies and creative tools</li>
                        <li><i class="fas fa-check-circle"></i> Build confidence in their abilities</li>
                        <li><i class="fas fa-check-circle"></i> Prepare for future opportunities in tech and digital arts</li>
                    </ul>
                    <p>All programs are guided by skilled mentors who provide personalized support throughout the journey.</p>
                </div>
                <div class="about-image">
                    <img src="../../../public/assets/images/clubhouse-students.jpg" alt="Students at Sci-Bono Clubhouse" onerror="this.src=''">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Programs Section -->
    <section class="programs" id="programs">
        <div class="container">
            <div class="section-header">
                <h2>2025 Holiday Programs</h2>
                <div class="underline"></div>
            </div>
            <div class="program-cards">
                <?php
                // Get current year
                $currentYear = date("Y");
                
                // Define the holiday programs
                $holidayPrograms = [
                    [
                        "id" => 1,
                        "term" => "Term 1",
                        "dates" => "March 31 - April 11, 2025",
                        "theme" => "Multi-Media - Digital Design",
                        "description" => "Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.",
                        "icon" => "fas fa-photo-video",
                        "color" => "program-multimedia",
                        "registration_open" => true
                    ],
                    [
                        "id" => 2,
                        "term" => "Term 2",
                        "dates" => "June 21 - July 14, 2025",
                        "theme" => "AI Festival",
                        "description" => "Explore artificial intelligence through interactive workshops, coding exercises, and creative AI applications.",
                        "icon" => "fas fa-robot",
                        "color" => "program-ai",
                        "registration_open" => false
                    ],
                    [
                        "id" => 3,
                        "term" => "Term 3",
                        "dates" => "October 4 - October 12, 2025",
                        "theme" => "Robotics Bootcamp",
                        "description" => "Build and program robots while learning mechanical design, electronics, and problem-solving skills.",
                        "icon" => "fas fa-microchip",
                        "color" => "program-robotics",
                        "registration_open" => false
                    ],
                    [
                        "id" => 4,
                        "term" => "Term 4",
                        "dates" => "December 11 - December 17, 2025",
                        "theme" => "Mixed Reality (VR & AR)",
                        "description" => "Create immersive experiences using virtual and augmented reality technologies.",
                        "icon" => "fas fa-vr-cardboard",
                        "color" => "program-vr",
                        "registration_open" => false
                    ]
                ];
                
                // Display each program
                foreach ($holidayPrograms as $program) {
                    $registrationStatus = $program["registration_open"] ? 
                        "<a href='holidayProgramRegistration.php?program_id={$program['id']}' class='register-btn'>Register Now</a>" : 
                        "<span class='coming-soon'>Registration Opens Soon</span>";
                    
                    echo "
                    <div class='program-card {$program['color']}'>
                        <div class='program-icon'>
                            <i class='{$program['icon']}'></i>
                        </div>
                        <div class='program-info'>
                            <h3>{$program['term']}: {$program['theme']}</h3>
                            <div class='program-dates'>
                                <i class='fas fa-calendar-alt'></i> {$program['dates']}
                            </div>
                            <p>{$program['description']}</p>
                            <div class='program-action'>
                                {$registrationStatus}
                                <a href='./holiday-program-details-term1.php?id={$program['id']}' class='details-link'>View Details</a>
                            </div>
                        </div>
                    </div>";
                }
                ?>
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
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Innovation Focus</h3>
                    <p>Work on cutting-edge technologies and creative approaches that push the boundaries of what's possible.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Recognition</h3>
                    <p>Receive certificates of completion and opportunities to showcase your work to the broader community.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials -->
    <!-- <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>What Previous Participants Say</h2>
                <div class="underline"></div>
            </div>
            <div class="testimonial-slider">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <p>"The Robotics Bootcamp was amazing! I learned how to build and program my own robot, and now I'm joining the FTC competition team."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">TM</div>
                        <div class="author-info">
                            <h4>Thabo Mokoena</h4>
                            <p>Participant, 2024 Robotics Bootcamp</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <p>"I discovered my passion for digital art during the Multimedia program. The mentors were so helpful and I created my first animation!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">LS</div>
                        <div class="author-info">
                            <h4>Lerato Sibiya</h4>
                            <p>Participant, 2024 Multimedia Program</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <p>"The AI Festival opened my eyes to how artificial intelligence works. I built a simple AI model that can recognize images. It was incredible!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">KN</div>
                        <div class="author-info">
                            <h4>Kagiso Ndlovu</h4>
                            <p>Participant, 2024 AI Festival</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-controls">
                <button id="prev-testimonial"><i class="fas fa-chevron-left"></i></button>
                <button id="next-testimonial"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section> -->
    
    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <div class="underline"></div>
            </div>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Who can participate in the holiday programs?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our holiday programs are designed for students aged 9-18 years. Some programs may have specific age requirements based on the content and tools used.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do I need any prior experience to join?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Most of our programs are designed for beginners with no prior experience. We also offer intermediate and advanced options for those with existing skills. Each program description specifies the recommended experience level.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What do I need to bring with me?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>All essential equipment and materials will be provided. Participants should bring their own lunch, a water bottle, and a notebook. If specific items are needed for a program, this will be communicated after registration.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What are the program hours?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Most programs run from 9:00 AM to 3:00 PM, Monday through Friday. Specific hours for each program are provided in the detailed program information after registration.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Is there a cost to participate?</h3>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>The Sci-Bono Clubhouse Holiday Programs are offered free of charge to ensure accessibility for all interested students. Space is limited, so early registration is encouraged.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to embark on a creative technology journey?</h2>
                <p>Register now for our upcoming holiday programs and start building the skills for tomorrow.</p>
                <a href="#programs" class="cta-button">Explore Programs</a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="../../public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
                    <img src="../../public/assets/images/TheClubhouse_Logo_White_Large.png" alt="The Clubhouse Network">
                </div>
                <div class="footer-links">
                    <div class="footer-links-column">
                        <h3>Programs</h3>
                        <ul>
                            <li><a href="#">Holiday Programs</a></li>
                            <li><a href="#">After-School Activities</a></li>
                            <li><a href="#">Workshops</a></li>
                            <li><a href="#">Competitions</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-column">
                        <h3>Resources</h3>
                        <ul>
                            <li><a href="#">Learning Materials</a></li>
                            <li><a href="#">Project Gallery</a></li>
                            <li><a href="#">Tech Tools</a></li>
                            <li><a href="#">Downloads</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-column">
                        <h3>About</h3>
                        <ul>
                            <li><a href="#">Our Mission</a></li>
                            <li><a href="#">The Team</a></li>
                            <li><a href="#">Partners</a></li>
                            <li><a href="#">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Sci-Bono Discovery Centre, Corner of Miriam Makeba & Helen Joseph Streets, Newtown, Johannesburg</p>
                    <p><i class="fas fa-phone"></i> +27 11 639 8463</p>
                    <p><i class="fas fa-envelope"></i> vuyani.magibisela@sci-bono.co.za</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Sci-Bono Clubhouse. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Navigation (visible on mobile only) -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="#programs" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span>Programs</span>
        </a>
        <a href="../../app/Views/learn.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-book"></i>
            </div>
            <span>Learn</span>
        </a>
        <a href="holiday-dashboard.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-user"></i>
            </div>
            <span>Account</span>
        </a>
    </nav>

    <!-- JavaScript -->
    <script>
        // FAQ Toggle
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                const isActive = item.classList.contains('active');
                
                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(faqItem => {
                    faqItem.classList.remove('active');
                    const faqAnswer = faqItem.querySelector('.faq-answer');
                    faqAnswer.style.maxHeight = null;
                    faqItem.querySelector('.fa-plus').classList.remove('fa-minus');
                    faqItem.querySelector('.fa-plus').classList.add('fa-plus');
                });
                
                // If clicked item wasn't already active, open it
                if (!isActive) {
                    item.classList.add('active');
                    const answer = item.querySelector('.faq-answer');
                    answer.style.maxHeight = answer.scrollHeight + "px";
                    question.querySelector('.fa-plus').classList.remove('fa-plus');
                    question.querySelector('i').classList.add('fa-minus');
                }
            });
        });

        // Testimonial Slider
        const testimonialCards = document.querySelectorAll('.testimonial-card');
        let currentTestimonial = 0;

        function showTestimonial(index) {
            testimonialCards.forEach((card, i) => {
                card.style.display = i === index ? 'block' : 'none';
            });
        }

        document.getElementById('next-testimonial').addEventListener('click', () => {
            currentTestimonial = (currentTestimonial + 1) % testimonialCards.length;
            showTestimonial(currentTestimonial);
        });

        document.getElementById('prev-testimonial').addEventListener('click', () => {
            currentTestimonial = (currentTestimonial - 1 + testimonialCards.length) % testimonialCards.length;
            showTestimonial(currentTestimonial);
        });

        // Initialize the testimonial slider
        showTestimonial(currentTestimonial);

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>