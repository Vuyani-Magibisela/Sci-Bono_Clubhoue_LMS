<?php
/**
 * Database Seeder - Populate database with sample data
 * Phase 5 Implementation
 */

require_once __DIR__ . '/../core/Logger.php';

class Seeder {
    protected $conn;
    protected $logger;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = new Logger();
    }
    
    /**
     * Run all seeders
     */
    public function run() {
        $this->logger->info("Starting database seeding");
        
        try {
            $this->conn->begin_transaction();
            
            $this->seedUsers();
            $this->seedCourses();
            $this->seedHolidayPrograms();
            $this->seedLessons();
            $this->seedAttendance();
            $this->seedEnrollments();
            
            $this->conn->commit();
            
            $this->logger->info("Database seeding completed successfully");
            
            return [
                'success' => true,
                'message' => 'Database seeding completed successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logger->error("Database seeding failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Database seeding failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run specific seeder
     */
    public function seed($seederName) {
        $methodName = 'seed' . ucfirst($seederName);
        
        if (!method_exists($this, $methodName)) {
            throw new Exception("Seeder '{$seederName}' not found");
        }
        
        $this->logger->info("Running specific seeder", ['seeder' => $seederName]);
        
        try {
            $this->conn->begin_transaction();
            
            $this->$methodName();
            
            $this->conn->commit();
            
            $this->logger->info("Seeder completed successfully", ['seeder' => $seederName]);
            
            return [
                'success' => true,
                'message' => "Seeder '{$seederName}' completed successfully"
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logger->error("Seeder failed", [
                'seeder' => $seederName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Seeder '{$seederName}' failed: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Seed users table
     */
    protected function seedUsers() {
        $this->logger->info("Seeding users table");
        
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@scibono.ac.za',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'name' => 'System',
                'surname' => 'Administrator',
                'user_type' => 'admin',
                'active' => 1,
                'phone' => '011-123-4567',
                'address' => 'Sci-Bono Discovery Centre'
            ],
            [
                'username' => 'mentor1',
                'email' => 'mentor1@scibono.ac.za',
                'password' => password_hash('mentor123', PASSWORD_DEFAULT),
                'name' => 'John',
                'surname' => 'Smith',
                'user_type' => 'mentor',
                'active' => 1,
                'phone' => '011-234-5678',
                'address' => 'Johannesburg, South Africa',
                'bio' => 'Experienced programming mentor with 5+ years in education'
            ],
            [
                'username' => 'mentor2',
                'email' => 'mentor2@scibono.ac.za',
                'password' => password_hash('mentor123', PASSWORD_DEFAULT),
                'name' => 'Sarah',
                'surname' => 'Johnson',
                'user_type' => 'mentor',
                'active' => 1,
                'phone' => '011-345-6789',
                'address' => 'Pretoria, South Africa',
                'bio' => 'Web development specialist and digital literacy advocate'
            ],
            [
                'username' => 'member1',
                'email' => 'member1@example.com',
                'password' => password_hash('member123', PASSWORD_DEFAULT),
                'name' => 'Jane',
                'surname' => 'Doe',
                'user_type' => 'member',
                'active' => 1,
                'school' => 'Example High School',
                'grade' => 11,
                'phone' => '011-456-7890',
                'address' => 'Johannesburg, South Africa'
            ],
            [
                'username' => 'member2',
                'email' => 'member2@example.com',
                'password' => password_hash('member123', PASSWORD_DEFAULT),
                'name' => 'Michael',
                'surname' => 'Brown',
                'user_type' => 'member',
                'active' => 1,
                'school' => 'Central High School',
                'grade' => 10,
                'phone' => '011-567-8901',
                'address' => 'Soweto, South Africa'
            ],
            [
                'username' => 'student1',
                'email' => 'student1@example.com',
                'password' => password_hash('student123', PASSWORD_DEFAULT),
                'name' => 'Emily',
                'surname' => 'Davis',
                'user_type' => 'student',
                'active' => 1,
                'school' => 'Tech High School',
                'grade' => 12,
                'phone' => '011-678-9012',
                'address' => 'Sandton, South Africa'
            ]
        ];
        
        foreach ($users as $user) {
            // Check if user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $user['username'], $user['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->insertUser($user);
            }
        }
    }
    
    /**
     * Seed courses table
     */
    protected function seedCourses() {
        $this->logger->info("Seeding courses table");
        
        // Get mentor IDs
        $mentorIds = $this->getMentorIds();
        
        if (empty($mentorIds)) {
            $this->logger->warning("No mentors found, skipping course seeding");
            return;
        }
        
        $courses = [
            [
                'title' => 'Introduction to Programming',
                'description' => 'Learn the basics of programming with Python. This course covers variables, data types, control structures, functions, and basic object-oriented programming concepts.',
                'instructor_id' => $mentorIds[0],
                'duration' => 40,
                'level' => 'beginner',
                'category' => 'programming',
                'active' => 1,
                'max_participants' => 25,
                'price' => 0.00
            ],
            [
                'title' => 'Web Development Fundamentals',
                'description' => 'Master the fundamentals of web development including HTML5, CSS3, and JavaScript. Build responsive websites and understand modern web standards.',
                'instructor_id' => $mentorIds[1] ?? $mentorIds[0],
                'duration' => 60,
                'level' => 'beginner',
                'category' => 'web-development',
                'active' => 1,
                'max_participants' => 20,
                'price' => 0.00
            ],
            [
                'title' => 'Digital Design Essentials',
                'description' => 'Learn digital design principles using modern tools. Covers graphic design, user interface design, and basic animation techniques.',
                'instructor_id' => $mentorIds[0],
                'duration' => 30,
                'level' => 'intermediate',
                'category' => 'design',
                'active' => 1,
                'max_participants' => 15,
                'price' => 0.00
            ],
            [
                'title' => 'Data Science with Python',
                'description' => 'Introduction to data science using Python. Learn data analysis, visualization, and basic machine learning concepts.',
                'instructor_id' => $mentorIds[1] ?? $mentorIds[0],
                'duration' => 50,
                'level' => 'intermediate',
                'category' => 'data-science',
                'active' => 1,
                'max_participants' => 20,
                'price' => 0.00
            ],
            [
                'title' => 'Mobile App Development',
                'description' => 'Build mobile applications using modern frameworks. Learn app design, development, and deployment strategies.',
                'instructor_id' => $mentorIds[0],
                'duration' => 45,
                'level' => 'advanced',
                'category' => 'mobile-development',
                'active' => 1,
                'max_participants' => 15,
                'price' => 0.00
            ]
        ];
        
        foreach ($courses as $course) {
            $this->insertCourse($course);
        }
    }
    
    /**
     * Seed holiday programs
     */
    protected function seedHolidayPrograms() {
        $this->logger->info("Seeding holiday programs table");
        
        $programs = [
            [
                'name' => 'Summer Tech Camp 2025',
                'description' => 'Intensive summer technology program focusing on coding, robotics, and digital creativity. Perfect for students looking to explore STEM careers.',
                'start_date' => '2025-12-01',
                'end_date' => '2025-12-15',
                'registration_deadline' => '2025-11-15',
                'max_participants' => 50,
                'current_participants' => 23,
                'status' => 'active',
                'age_min' => 12,
                'age_max' => 18,
                'fee' => 0.00
            ],
            [
                'name' => 'Winter Coding Bootcamp',
                'description' => 'Learn to code during winter holidays with hands-on projects and expert mentorship. Build real applications and gain practical skills.',
                'start_date' => '2025-07-01',
                'end_date' => '2025-07-21',
                'registration_deadline' => '2025-06-15',
                'max_participants' => 30,
                'current_participants' => 18,
                'status' => 'active',
                'age_min' => 14,
                'age_max' => 20,
                'fee' => 0.00
            ],
            [
                'name' => 'Digital Arts Workshop',
                'description' => 'Creative program combining technology and art. Learn digital illustration, animation, and multimedia design.',
                'start_date' => '2025-04-05',
                'end_date' => '2025-04-12',
                'registration_deadline' => '2025-03-20',
                'max_participants' => 20,
                'current_participants' => 15,
                'status' => 'active',
                'age_min' => 10,
                'age_max' => 16,
                'fee' => 0.00
            ]
        ];
        
        foreach ($programs as $program) {
            $this->insertHolidayProgram($program);
        }
    }
    
    /**
     * Seed lessons table
     */
    protected function seedLessons() {
        $this->logger->info("Seeding lessons table");
        
        // Get course IDs
        $courseIds = $this->getCourseIds();
        
        if (empty($courseIds)) {
            $this->logger->warning("No courses found, skipping lesson seeding");
            return;
        }
        
        $lessons = [
            // Introduction to Programming lessons
            [
                'course_id' => $courseIds['Introduction to Programming'] ?? $courseIds[0],
                'title' => 'Getting Started with Python',
                'description' => 'Introduction to Python programming language, installation, and basic syntax.',
                'content' => 'In this lesson, we will cover the basics of Python programming...',
                'order_number' => 1,
                'duration' => 60,
                'is_active' => 1
            ],
            [
                'course_id' => $courseIds['Introduction to Programming'] ?? $courseIds[0],
                'title' => 'Variables and Data Types',
                'description' => 'Understanding different data types and how to work with variables.',
                'content' => 'Learn about integers, strings, floats, and boolean values...',
                'order_number' => 2,
                'duration' => 45,
                'is_active' => 1
            ],
            [
                'course_id' => $courseIds['Introduction to Programming'] ?? $courseIds[0],
                'title' => 'Control Structures',
                'description' => 'If statements, loops, and conditional logic in Python.',
                'content' => 'Control structures allow us to control the flow of our programs...',
                'order_number' => 3,
                'duration' => 50,
                'is_active' => 1
            ],
            
            // Web Development lessons
            [
                'course_id' => $courseIds['Web Development Fundamentals'] ?? $courseIds[1] ?? $courseIds[0],
                'title' => 'HTML Basics',
                'description' => 'Introduction to HTML structure and common tags.',
                'content' => 'HTML is the foundation of all web pages...',
                'order_number' => 1,
                'duration' => 55,
                'is_active' => 1
            ],
            [
                'course_id' => $courseIds['Web Development Fundamentals'] ?? $courseIds[1] ?? $courseIds[0],
                'title' => 'CSS Styling',
                'description' => 'Learn to style web pages with CSS.',
                'content' => 'CSS allows us to make our web pages beautiful and responsive...',
                'order_number' => 2,
                'duration' => 60,
                'is_active' => 1
            ],
            [
                'course_id' => $courseIds['Web Development Fundamentals'] ?? $courseIds[1] ?? $courseIds[0],
                'title' => 'JavaScript Basics',
                'description' => 'Adding interactivity to web pages with JavaScript.',
                'content' => 'JavaScript brings web pages to life with dynamic functionality...',
                'order_number' => 3,
                'duration' => 65,
                'is_active' => 1
            ]
        ];
        
        foreach ($lessons as $lesson) {
            $this->insertLesson($lesson);
        }
    }
    
    /**
     * Seed sample attendance data
     */
    protected function seedAttendance() {
        $this->logger->info("Seeding attendance table");
        
        // Get user IDs (excluding admin)
        $userIds = $this->getNonAdminUserIds();
        
        if (empty($userIds)) {
            $this->logger->warning("No users found, skipping attendance seeding");
            return;
        }
        
        // Create attendance records for the past 2 weeks
        for ($i = 14; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Skip weekends
            if (date('w', strtotime($date)) == 0 || date('w', strtotime($date)) == 6) {
                continue;
            }
            
            foreach ($userIds as $userId) {
                // Random chance of attendance (80% chance)
                if (rand(1, 10) > 2) {
                    $signInHour = rand(8, 10);
                    $signInMinute = rand(0, 59);
                    $signOutHour = rand(15, 17);
                    $signOutMinute = rand(0, 59);
                    
                    $signInTime = $date . ' ' . sprintf('%02d:%02d:00', $signInHour, $signInMinute);
                    $signOutTime = $date . ' ' . sprintf('%02d:%02d:00', $signOutHour, $signOutMinute);
                    
                    $this->insertAttendance([
                        'user_id' => $userId,
                        'sign_in_time' => $signInTime,
                        'sign_out_time' => $signOutTime,
                        'sign_in_status' => 'signedOut',
                        'date' => $date
                    ]);
                }
            }
        }
    }
    
    /**
     * Seed enrollments
     */
    protected function seedEnrollments() {
        $this->logger->info("Seeding enrollments table");
        
        $courseIds = $this->getCourseIds();
        $memberIds = $this->getMemberIds();
        
        if (empty($courseIds) || empty($memberIds)) {
            $this->logger->warning("No courses or members found, skipping enrollment seeding");
            return;
        }
        
        // Enroll each member in 1-3 random courses
        foreach ($memberIds as $memberId) {
            $numCourses = rand(1, min(3, count($courseIds)));
            $selectedCourses = array_rand(array_values($courseIds), $numCourses);
            
            if (!is_array($selectedCourses)) {
                $selectedCourses = [$selectedCourses];
            }
            
            foreach ($selectedCourses as $courseIndex) {
                $courseId = array_values($courseIds)[$courseIndex];
                
                $this->insertEnrollment([
                    'user_id' => $memberId,
                    'course_id' => $courseId,
                    'enrollment_date' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                    'status' => rand(1, 10) > 2 ? 'active' : 'completed', // 80% active, 20% completed
                    'progress' => rand(0, 100)
                ]);
            }
        }
    }
    
    /**
     * Clear all seeded data
     */
    public function clear() {
        $this->logger->info("Clearing seeded data");
        
        try {
            $this->conn->begin_transaction();
            
            // Clear in reverse dependency order
            $this->conn->query("DELETE FROM enrollments WHERE user_id > 1"); // Keep admin enrollments
            $this->conn->query("DELETE FROM attendance WHERE user_id > 1"); // Keep admin attendance
            $this->conn->query("DELETE FROM lessons WHERE course_id IN (SELECT id FROM courses WHERE instructor_id > 1)");
            $this->conn->query("DELETE FROM courses WHERE instructor_id > 1"); // Keep admin courses
            $this->conn->query("DELETE FROM holiday_programs WHERE id > 0"); // Clear all holiday programs
            $this->conn->query("DELETE FROM users WHERE id > 1"); // Keep admin user
            
            $this->conn->commit();
            
            $this->logger->info("Seeded data cleared successfully");
            
            return [
                'success' => true,
                'message' => 'Seeded data cleared successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logger->error("Failed to clear seeded data", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Failed to clear seeded data: ' . $e->getMessage()
            ];
        }
    }
    
    // Private helper methods
    
    /**
     * Insert user
     */
    private function insertUser($user) {
        $sql = "INSERT INTO users (username, email, password, name, surname, user_type, active, school, grade, phone, address, bio, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssisssss", 
            $user['username'],
            $user['email'],
            $user['password'],
            $user['name'],
            $user['surname'],
            $user['user_type'],
            $user['active'],
            $user['school'] ?? null,
            $user['grade'] ?? null,
            $user['phone'] ?? null,
            $user['address'] ?? null,
            $user['bio'] ?? null
        );
        
        $stmt->execute();
        
        $this->logger->info("User seeded", ['username' => $user['username']]);
    }
    
    /**
     * Insert course
     */
    private function insertCourse($course) {
        // Check if course exists
        $stmt = $this->conn->prepare("SELECT id FROM courses WHERE title = ?");
        $stmt->bind_param("s", $course['title']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO courses (title, description, instructor_id, duration, level, category, active, max_participants, price, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssiisssiid", 
                $course['title'],
                $course['description'],
                $course['instructor_id'],
                $course['duration'],
                $course['level'],
                $course['category'],
                $course['active'],
                $course['max_participants'],
                $course['price']
            );
            
            $stmt->execute();
            
            $this->logger->info("Course seeded", ['title' => $course['title']]);
        }
    }
    
    /**
     * Insert holiday program
     */
    private function insertHolidayProgram($program) {
        // Check if program exists
        $stmt = $this->conn->prepare("SELECT id FROM holiday_programs WHERE name = ?");
        $stmt->bind_param("s", $program['name']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO holiday_programs (name, description, start_date, end_date, registration_deadline, max_participants, current_participants, status, age_min, age_max, fee, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssssiiisiid", 
                $program['name'],
                $program['description'],
                $program['start_date'],
                $program['end_date'],
                $program['registration_deadline'],
                $program['max_participants'],
                $program['current_participants'],
                $program['status'],
                $program['age_min'],
                $program['age_max'],
                $program['fee']
            );
            
            $stmt->execute();
            
            $this->logger->info("Holiday program seeded", ['name' => $program['name']]);
        }
    }
    
    /**
     * Insert lesson
     */
    private function insertLesson($lesson) {
        $sql = "INSERT INTO lessons (course_id, title, description, content, order_number, duration, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssiis", 
            $lesson['course_id'],
            $lesson['title'],
            $lesson['description'],
            $lesson['content'],
            $lesson['order_number'],
            $lesson['duration'],
            $lesson['is_active']
        );
        
        $stmt->execute();
        
        $this->logger->info("Lesson seeded", ['title' => $lesson['title']]);
    }
    
    /**
     * Insert attendance record
     */
    private function insertAttendance($attendance) {
        // Check if attendance already exists for this user on this date
        $stmt = $this->conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(sign_in_time) = ?");
        $stmt->bind_param("is", $attendance['user_id'], $attendance['date']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO attendance (user_id, sign_in_time, sign_out_time, sign_in_status) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isss", 
                $attendance['user_id'],
                $attendance['sign_in_time'],
                $attendance['sign_out_time'],
                $attendance['sign_in_status']
            );
            
            $stmt->execute();
        }
    }
    
    /**
     * Insert enrollment
     */
    private function insertEnrollment($enrollment) {
        // Check if enrollment already exists
        $stmt = $this->conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $enrollment['user_id'], $enrollment['course_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $sql = "INSERT INTO enrollments (user_id, course_id, enrollment_date, status, progress, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iissi", 
                $enrollment['user_id'],
                $enrollment['course_id'],
                $enrollment['enrollment_date'],
                $enrollment['status'],
                $enrollment['progress']
            );
            
            $stmt->execute();
        }
    }
    
    /**
     * Get mentor IDs
     */
    private function getMentorIds() {
        $sql = "SELECT id FROM users WHERE user_type = 'mentor' ORDER BY id ASC";
        $result = $this->conn->query($sql);
        
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
        
        return $ids;
    }
    
    /**
     * Get member IDs
     */
    private function getMemberIds() {
        $sql = "SELECT id FROM users WHERE user_type IN ('member', 'student') ORDER BY id ASC";
        $result = $this->conn->query($sql);
        
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
        
        return $ids;
    }
    
    /**
     * Get non-admin user IDs
     */
    private function getNonAdminUserIds() {
        $sql = "SELECT id FROM users WHERE user_type != 'admin' ORDER BY id ASC";
        $result = $this->conn->query($sql);
        
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
        
        return $ids;
    }
    
    /**
     * Get course IDs
     */
    private function getCourseIds() {
        $sql = "SELECT id, title FROM courses ORDER BY id ASC";
        $result = $this->conn->query($sql);
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[$row['title']] = $row['id'];
            $courses[] = $row['id']; // Also add by index for fallback
        }
        
        return $courses;
    }
}