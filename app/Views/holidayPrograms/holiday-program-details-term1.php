<?php
// Get the program ID from URL parameter
$programId = isset($_GET['id']) ? intval($_GET['id']) : 1;

// If we have a database connection, we'd query the program details here
// For now, we'll simulate fetching program details based on ID
require_once '../../../server.php';

// Initialize program data with default values
$program = [
    'id' => 1,
    'term' => 'Term 1',
    'title' => 'Multi-Media - Digital Design',
    'description' => 'Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.',
    'dates' => 'March 31 - April 4, 2025',
    'time' => '9:00 AM - 4:00 PM',
    'location' => 'Sci-Bono Clubhouse',
    'age_range' => '13-18 years',
    'max_participants' => 30,
    'registration_deadline' => 'March 24, 2025',
    'lunch_included' => true,
    'program_goals' => 'This program introduces participants to various aspects of digital design. Participants will learn essential skills in their chosen workshop track while addressing one or more of the 17 UN Sustainable Development Goals through their projects.',
    'workshops' => [
        [
            'id' => 1,
            'title' => '3D Design',
            'description' => 'Learn the basics of 3D modeling, texturing, and rendering using Blender.',
            'capacity' => 5,
            'mentor' => 'Jabu Khumalo',
            'skills' => ['3D modeling', 'Texturing', 'Lighting', 'Rendering'],
            'software' => ['Blender'],
            'icon' => 'fas fa-cube'
        ],
        [
            'id' => 2,
            'title' => 'Graphic Design',
            'description' => 'Use Gimp, Krita, Blender and AI image generation tools to express your creativity.',
            'capacity' => 10,
            'mentor' => 'Lebo Skhosana',
            'skills' => ['Design principles', 'Color theory', 'Composition', 'Digital illustration'],
            'software' => ['GIMP', 'Krita', 'Blender', 'AI Tools'],
            'icon' => 'fas fa-palette'
        ],
        [
            'id' => 3,
            'title' => 'Music and Video Production',
            'description' => 'Utilize Fruity Loops and CapCut to create music and visually represent it.',
            'capacity' => 10,
            'mentor' => 'Themba Kgakane',
            'skills' => ['Audio production', 'Beat creation', 'Video editing', 'Audio-visual synchronization'],
            'software' => ['Fruity Loops', 'CapCut'],
            'icon' => 'fas fa-music'
        ],
        [
            'id' => 4,
            'title' => 'Animation',
            'description' => 'Learn the foundation in both 2D and 3D animation using Blender and Krita.',
            'capacity' => 5,
            'mentor' => 'Vuyani Magibisela',
            'skills' => ['Animation principles', 'Keyframing', 'Character movement', 'Scene composition'],
            'software' => ['Blender', 'Krita'],
            'icon' => 'fas fa-film'
        ]
    ],
    'daily_schedule' => [
        'Day 1' => [
            'date' => 'Monday, March 31, 2025',
            'theme' => 'Introduction & Fundamentals',
            'morning' => [
                '9:00 - 9:30' => 'Welcome and program overview for all participants',
                '9:30 - 10:00' => 'Introduction to UN Sustainable Development Goals',
                '10:00 - 10:15' => 'Break',
                '10:15 - 12:00' => 'Workshop-specific introductions and skill assessments'
            ],
            'afternoon' => [
                '1:00 - 2:30' => 'Software introduction and basic skills training',
                '2:30 - 2:45' => 'Break',
                '2:45 - 3:45' => 'Brainstorming session for projects addressing SDGs',
                '3:45 - 4:00' => 'Daily reflection and next day preview'
            ]
        ],
        'Day 2' => [
            'date' => 'Tuesday, April 1, 2025',
            'theme' => 'Skill Development',
            'morning' => [
                '9:00 - 9:15' => 'Daily briefing and objectives',
                '9:15 - 10:45' => 'Core skills development session I',
                '10:45 - 11:00' => 'Break',
                '11:00 - 12:00' => 'Core skills development session II'
            ],
            'afternoon' => [
                '1:00 - 2:30' => 'Project planning and initial development',
                '2:30 - 2:45' => 'Break',
                '2:45 - 3:45' => 'Continued skills development and application',
                '3:45 - 4:00' => 'Progress check-in and daily reflection'
            ]
        ],
        'Day 3' => [
            'date' => 'Wednesday, April 2, 2025',
            'theme' => 'Project Development',
            'morning' => [
                '9:00 - 9:15' => 'Daily briefing and objectives',
                '9:15 - 10:45' => 'Advanced techniques instruction',
                '10:45 - 11:00' => 'Break',
                '11:00 - 12:00' => 'Project work with mentor guidance'
            ],
            'afternoon' => [
                '1:00 - 2:30' => 'Continued project development',
                '2:30 - 2:45' => 'Break',
                '2:45 - 3:45' => 'Mid-week project review and feedback',
                '3:45 - 4:00' => 'Progress check-in and daily reflection'
            ]
        ],
        'Day 4' => [
            'date' => 'Thursday, April 3, 2025',
            'theme' => 'Project Refinement',
            'morning' => [
                '9:00 - 9:15' => 'Daily briefing and objectives',
                '9:15 - 10:45' => 'Project refinement and advanced techniques',
                '10:45 - 11:00' => 'Break',
                '11:00 - 12:00' => 'Problem-solving workshop for project challenges'
            ],
            'afternoon' => [
                '1:00 - 2:30' => 'Final project development',
                '2:30 - 2:45' => 'Break',
                '2:45 - 3:45' => 'Project finalization and preparing for showcase',
                '3:45 - 4:00' => 'Showcase preparation instructions and daily reflection'
            ]
        ],
        'Day 5' => [
            'date' => 'Friday, April 4, 2025',
            'theme' => 'Showcase & Celebration',
            'morning' => [
                '9:00 - 9:15' => 'Final day briefing',
                '9:15 - 10:45' => 'Project completion and showcase preparation',
                '10:45 - 11:00' => 'Break',
                '11:00 - 12:00' => 'Project final touches and presentation preparation'
            ],
            'afternoon' => [
                '1:00 - 3:00' => 'Showcase event (open to parents and other Clubhouse members)',
                '3:00 - 3:30' => 'Recognition and certificates',
                '3:30 - 4:00' => 'Program conclusion and future opportunities at the Clubhouse'
            ]
        ]
    ],
    'project_requirements' => [
        'All projects must address at least one UN Sustainable Development Goal',
        'Projects must be completed by the end of the program',
        'Each participant/team must prepare a brief presentation for the showcase',
        'Projects should demonstrate application of skills learned during the program'
    ],
    'evaluation_criteria' => [
        'Technical Execution' => 'Quality of technical skills demonstrated',
        'Creativity' => 'Original ideas and creative approach',
        'Message' => 'Clear connection to SDGs and effective communication of message',
        'Completion' => 'Level of completion and polish',
        'Presentation' => 'Quality of showcase presentation'
    ],
    'what_to_bring' => [
        'Notebook and pen/pencil',
        'Snacks (lunch will be provided)',
        'Water bottle',
        'Enthusiasm and creativity!'
    ],
    'faq' => [
        [
            'question' => 'Do I need prior experience to participate?',
            'answer' => 'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.'
        ],
        [
            'question' => 'Can I switch workshops during the week?',
            'answer' => 'Due to the progressive nature of the workshops, participants are encouraged to stay with their assigned workshop throughout the week. However, mentors may arrange collaborative activities between workshops.'
        ],
        [
            'question' => 'What are the UN Sustainable Development Goals?',
            'answer' => 'The UN Sustainable Development Goals are a collection of 17 interlinked global goals designed to be a "blueprint to achieve a better and more sustainable future for all." You\'ll learn more about these goals on the first day of the program.'
        ],
        [
            'question' => 'Will I need my own laptop or equipment?',
            'answer' => 'No, all necessary equipment will be provided at the Clubhouse. If you have special accessibility needs, please let us know during registration.'
        ],
        [
            'question' => 'What happens if I can\'t attend all five days?',
            'answer' => 'We encourage full attendance to get the most out of the program. If you must miss a day, please notify us in advance, and your mentor will provide materials to help you catch up.'
        ]
    ]
];

// In a real implementation, you would query the database based on the program ID
// $sql = "SELECT * FROM holiday_programs WHERE id = ?";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $programId);
// $stmt->execute();
// $result = $stmt->get_result();
// if ($result->num_rows > 0) {
//     $program = $result->fetch_assoc();
// }

// Check if registration is open
$registrationOpen = true; // This would normally be determined from the database
$program['registration_open'] = $registrationOpen;

// Check if user is already registered
$userIsRegistered = false; // This would normally be determined from the database
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    // Check if user is registered for this program
    // $userId = $_SESSION['id'];
    // $sql = "SELECT id FROM holiday_program_attendees WHERE user_id = ? AND program_id = ?";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("ii", $userId, $programId);
    // $stmt->execute();
    // $result = $stmt->get_result();
    // $userIsRegistered = $result->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($program['title']); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramContentStyles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <main class="program-details-container">
        <!-- Hero Section with Program Overview -->
        <section class="program-hero">
            <div class="container">
                <div class="program-banner" style="background-image: linear-gradient(rgba(41, 41, 91, 0.8), rgba(41, 41, 91, 0.8)), url('https://source.unsplash.com/random/1200x600?multimedia');">
                    <div class="program-badge"><?php echo htmlspecialchars($program['term']); ?></div>
                    <h1><?php echo htmlspecialchars($program['title']); ?></h1>
                    <div class="program-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo htmlspecialchars($program['dates']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($program['time']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($program['location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo htmlspecialchars($program['max_participants']); ?> participants</span>
                        </div>
                    </div>
                    <div class="program-actions">
                        <?php if ($program['registration_open']): ?>
                            <?php if ($userIsRegistered): ?>
                                <a href="holiday-dashboard.php" class="cta-button"><i class="fas fa-check-circle"></i> Already Registered</a>
                            <?php else: ?>
                                <a href="holidayProgramRegistration.php?program_id=<?php echo $program['id']; ?>" class="cta-button"><i class="fas fa-sign-in-alt"></i> Register Now</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="registration-closed"><i class="fas fa-clock"></i> Registration Closed</span>
                        <?php endif; ?>
                        <a href="#workshop-details" class="scroll-button">Learn More <i class="fas fa-chevron-down"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Info (Sticky when scrolling on desktop) -->
        <div class="quick-info-bar">
            <div class="container">
                <ul class="quick-nav">
                    <li><a href="#overview">Overview</a></li>
                    <li><a href="#workshop-details">Workshops</a></li>
                    <li><a href="#schedule">Schedule</a></li>
                    <li><a href="#project-info">Projects</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
                <div class="quick-action">
                    <?php if ($program['registration_open']): ?>
                        <?php if ($userIsRegistered): ?>
                            <a href="holiday-dashboard.php" class="quick-btn">Your Dashboard</a>
                        <?php else: ?>
                            <a href="holidayProgramRegistration.php?program_id=<?php echo $program['id']; ?>" class="quick-btn">Register</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="quick-status">Registration Closed</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container program-content">
            <div class="program-main">
                <!-- Program Overview Section -->
                <section id="overview" class="content-section">
                    <h2 class="section-title">Program Overview</h2>
                    <div class="program-description">
                        <p><?php echo htmlspecialchars($program['description']); ?></p>
                        <p><?php echo htmlspecialchars($program['program_goals']); ?></p>
                    </div>
                    
                    <div class="key-info-cards">
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="info-title">Program Dates</div>
                            <div class="info-content"><?php echo htmlspecialchars($program['dates']); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-child"></i></div>
                            <div class="info-title">Age Range</div>
                            <div class="info-content"><?php echo htmlspecialchars($program['age_range']); ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-utensils"></i></div>
                            <div class="info-title">Lunch</div>
                            <div class="info-content"><?php echo $program['lunch_included'] ? 'Included' : 'Not included'; ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-hourglass-end"></i></div>
                            <div class="info-title">Registration Deadline</div>
                            <div class="info-content"><?php echo htmlspecialchars($program['registration_deadline']); ?></div>
                        </div>
                    </div>
                </section>

                <!-- Workshop Details Section -->
                <section id="workshop-details" class="content-section">
                    <h2 class="section-title">Workshop Tracks</h2>
                    <p class="section-subtitle">Choose one specialized track to focus on during the program</p>
                    
                    <div class="workshops-grid">
                        <?php foreach ($program['workshops'] as $workshop): ?>
                            <div class="workshop-card">
                                <div class="workshop-header">
                                    <div class="workshop-icon"><i class="<?php echo $workshop['icon']; ?>"></i></div>
                                    <h3 class="workshop-title"><?php echo htmlspecialchars($workshop['title']); ?></h3>
                                </div>
                                <div class="workshop-description">
                                    <p><?php echo htmlspecialchars($workshop['description']); ?></p>
                                </div>
                                <div class="workshop-details">
                                    <div class="workshop-detail">
                                        <strong>Mentor:</strong> <?php echo htmlspecialchars($workshop['mentor']); ?>
                                    </div>
                                    <div class="workshop-detail">
                                        <strong>Capacity:</strong> <?php echo htmlspecialchars($workshop['capacity']); ?> participants
                                    </div>
                                    <div class="workshop-detail">
                                        <strong>Skills:</strong> 
                                        <div class="skill-tags">
                                            <?php foreach ($workshop['skills'] as $skill): ?>
                                                <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="workshop-detail">
                                        <strong>Software:</strong> 
                                        <div class="software-list">
                                            <?php foreach ($workshop['software'] as $software): ?>
                                                <span class="software-item"><?php echo htmlspecialchars($software); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Program Schedule Section -->
                <section id="schedule" class="content-section">
                    <h2 class="section-title">Program Schedule</h2>
                    <p class="section-subtitle">Here's what you can expect each day during the program</p>
                    
                    <div class="schedule-tabs">
                        <div class="tab-navigation">
                            <?php $firstDay = true; ?>
                            <?php foreach ($program['daily_schedule'] as $day => $schedule): ?>
                                <button class="tab-button <?php echo $firstDay ? 'active' : ''; ?>" data-tab="<?php echo str_replace(' ', '-', strtolower($day)); ?>">
                                    <?php echo htmlspecialchars($day); ?>
                                </button>
                                <?php $firstDay = false; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="tab-content">
                            <?php $firstDay = true; ?>
                            <?php foreach ($program['daily_schedule'] as $day => $schedule): ?>
                                <div class="tab-pane <?php echo $firstDay ? 'active' : ''; ?>" id="<?php echo str_replace(' ', '-', strtolower($day)); ?>">
                                    <div class="day-overview">
                                        <h3><?php echo htmlspecialchars($schedule['date']); ?></h3>
                                        <div class="day-theme">Theme: <?php echo htmlspecialchars($schedule['theme']); ?></div>
                                    </div>
                                    
                                    <div class="day-schedule">
                                        <div class="schedule-section">
                                            <h4>Morning Session (9:00 AM - 12:00 PM)</h4>
                                            <ul class="timeline">
                                                <?php foreach ($schedule['morning'] as $time => $activity): ?>
                                                    <li class="timeline-item">
                                                        <div class="timeline-time"><?php echo htmlspecialchars($time); ?></div>
                                                        <div class="timeline-content"><?php echo htmlspecialchars($activity); ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="schedule-section">
                                            <h4>Lunch Break (12:00 PM - 1:00 PM)</h4>
                                        </div>
                                        
                                        <div class="schedule-section">
                                            <h4>Afternoon Session (1:00 PM - 4:00 PM)</h4>
                                            <ul class="timeline">
                                                <?php foreach ($schedule['afternoon'] as $time => $activity): ?>
                                                    <li class="timeline-item">
                                                        <div class="timeline-time"><?php echo htmlspecialchars($time); ?></div>
                                                        <div class="timeline-content"><?php echo htmlspecialchars($activity); ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php $firstDay = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Project Information Section -->
                <section id="project-info" class="content-section">
                    <h2 class="section-title">Project Information</h2>
                    
                    <div class="project-section">
                        <h3>Project Requirements</h3>
                        <ul class="requirement-list">
                            <?php foreach ($program['project_requirements'] as $requirement): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($requirement); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="project-section">
                        <h3>Evaluation Criteria</h3>
                        <div class="criteria-grid">
                            <?php foreach ($program['evaluation_criteria'] as $criterion => $description): ?>
                                <div class="criterion-card">
                                    <h4><?php echo htmlspecialchars($criterion); ?></h4>
                                    <p><?php echo htmlspecialchars($description); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="project-section">
                        <h3>Showcase Information</h3>
                        <p>On the final day, all participants will present their projects in a showcase event open to parents, other Clubhouse members, and invited guests. This is an opportunity to demonstrate your skills and creativity!</p>
                        <p>The showcase will feature:</p>
                        <ul>
                            <li>Individual and team presentations (2-3 minutes each)</li>
                            <li>Interactive displays of projects</li>
                            <li>Discussion of how projects address UN Sustainable Development Goals</li>
                            <li>Recognition and certificates for all participants</li>
                        </ul>
                    </div>
                </section>

                <!-- What to Bring Section -->
                <section id="bring" class="content-section">
                    <h2 class="section-title">What to Bring</h2>
                    
                    <div class="items-grid">
                        <?php foreach ($program['what_to_bring'] as $item): ?>
                            <div class="item-card">
                                <div class="item-icon"><i class="fas fa-check"></i></div>
                                <div class="item-text"><?php echo htmlspecialchars($item); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- FAQ Section -->
                <section id="faq" class="content-section">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    
                    <div class="faq-container">
                        <?php foreach ($program['faq'] as $index => $item): ?>
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h3><?php echo htmlspecialchars($item['question']); ?></h3>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo htmlspecialchars($item['answer']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Registration CTA -->
                <section class="cta-section">
                    <div class="cta-content">
                        <h2>Ready to join our <?php echo htmlspecialchars($program['title']); ?> program?</h2>
                        <p>Register now to secure your spot and indicate your workshop preference. Space is limited!</p>
                        <?php if ($program['registration_open']): ?>
                            <?php if ($userIsRegistered): ?>
                                <a href="holiday-dashboard.php" class="cta-button"><i class="fas fa-user-circle"></i> View Your Registration</a>
                            <?php else: ?>
                                <a href="holidayProgramRegistration.php?program_id=<?php echo $program['id']; ?>" class="cta-button"><i class="fas fa-sign-in-alt"></i> Register Now</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="registration-closed"><i class="fas fa-clock"></i> Registration is currently closed</span>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            
            <!-- Sidebar -->
            <div class="program-sidebar">
                <!-- Quick Registration Card -->
                <div class="sidebar-card registration-card">
                    <h3>Registration Info</h3>
                    <div class="card-content">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Dates:</div>
                            <div class="info-value"><?php echo htmlspecialchars($program['dates']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-clock"></i> Time:</div>
                            <div class="info-value"><?php echo htmlspecialchars($program['time']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Location:</div>
                            <div class="info-value"><?php echo htmlspecialchars($program['location']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-user-friends"></i> Age Range:</div>
                            <div class="info-value"><?php echo htmlspecialchars($program['age_range']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-hourglass-end"></i> Deadline:</div>
                            <div class="info-value"><?php echo htmlspecialchars($program['registration_deadline']); ?></div>
                        </div>
                        <div class="registration-status">
                            <?php if ($program['registration_open']): ?>
                                <span class="status-open"><i class="fas fa-unlock"></i> Registration Open</span>
                            <?php else: ?>
                                <span class="status-closed"><i class="fas fa-lock"></i> Registration Closed</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($program['registration_open']): ?>
                            <?php if ($userIsRegistered): ?>
                                <a href="holiday-dashboard.php" class="register-btn-sidebar">View Registration</a>
                            <?php else: ?>
                                <a href="holidayProgramRegistration.php?program_id=<?php echo $program['id']; ?>" class="register-btn-sidebar">Register Now</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="holidayProgramIndex.php" class="view-programs-btn">View Other Programs</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Workshop Spaces Card -->
                <div class="sidebar-card workshop-card">
                    <h3>Workshop Spaces</h3>
                    <div class="card-content">
                        <?php foreach ($program['workshops'] as $workshop): ?>
                            <div class="workshop-space">
                                <div class="workshop-name">
                                    <i class="<?php echo $workshop['icon']; ?>"></i>
                                    <?php echo htmlspecialchars($workshop['title']); ?>
                                </div>
                                <div class="workshop-capacity">
                                    <div class="capacity-bar">
                                        <div class="capacity-fill" style="width: 30%;"></div>
                                    </div>
                                    <div class="capacity-text">
                                        3/<?php echo $workshop['capacity']; ?> spots filled
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="workshop-note">
                            <i class="fas fa-info-circle"></i> Workshop assignments are based on preferences indicated during registration.
                        </div>
                    </div>
                </div>
                
                <!-- Contact Card -->
                <div class="sidebar-card contact-card">
                    <h3>Have Questions?</h3>
                    <div class="card-content">
                        <p>Contact us for more information about this program:</p>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:clubhouse@sci-bono.co.za">clubhouse@sci-bono.co.za</a>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <a href="tel:+27116398400">+27 11 639 8400</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Share Card -->
                <div class="sidebar-card share-card">
                    <h3>Share This Program</h3>
                    <div class="social-sharing">
                        <a href="#" class="social-btn facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-btn whatsapp"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="social-btn email"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Navigation (visible on mobile only) -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="holidayProgramIndex.php" class="mobile-menu-item">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality for schedule
            const tabButtons = document.querySelectorAll('.tab-button');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Get the tab to show
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and tab panes
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('active');
                    });
                    
                    // Add active class to current button and tab pane
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // FAQ accordion functionality
            const faqItems = document.querySelectorAll('.faq-question');
            
            faqItems.forEach(item => {
                item.addEventListener('click', function() {
                    const parent = this.parentNode;
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    // Toggle active class
                    parent.classList.toggle('active');
                    
                    // Toggle icon
                    if (parent.classList.contains('active')) {
                        icon.classList.remove('fa-plus');
                        icon.classList.add('fa-minus');
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                    } else {
                        icon.classList.remove('fa-minus');
                        icon.classList.add('fa-plus');
                        answer.style.maxHeight = null;
                    }
                });
            });
            
            // Sticky quick info bar on scroll
            const quickInfoBar = document.querySelector('.quick-info-bar');
            const heroSection = document.querySelector('.program-hero');
            let sticky = heroSection.offsetHeight;
            
            window.onscroll = function() {
                if (window.pageYOffset >= sticky) {
                    quickInfoBar.classList.add('sticky');
                    document.body.style.paddingTop = quickInfoBar.offsetHeight + 'px';
                } else {
                    quickInfoBar.classList.remove('sticky');
                    document.body.style.paddingTop = '0';
                }
            };
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    const quickInfoBarHeight = quickInfoBar.offsetHeight;
                    
                    if (targetElement) {
                        let targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                        
                        // Subtract the height of the sticky navigation
                        targetPosition -= quickInfoBarHeight;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Social sharing functionality
            const shareButtons = document.querySelectorAll('.social-btn');
            const pageUrl = encodeURIComponent(window.location.href);
            const pageTitle = encodeURIComponent(document.title);
            
            shareButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    let shareUrl = '';
                    
                    if (this.classList.contains('facebook')) {
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}`;
                    } else if (this.classList.contains('twitter')) {
                        shareUrl = `https://twitter.com/intent/tweet?url=${pageUrl}&text=${pageTitle}`;
                    } else if (this.classList.contains('whatsapp')) {
                        shareUrl = `https://wa.me/?text=${pageTitle}%20${pageUrl}`;
                    } else if (this.classList.contains('email')) {
                        shareUrl = `mailto:?subject=${pageTitle}&body=Check%20out%20this%20holiday%20program:%20${pageUrl}`;
                    }
                    
                    if (shareUrl) {
                        window.open(shareUrl, '_blank');
                    }
                });
            });
        });
    </script>
</body>
</html>