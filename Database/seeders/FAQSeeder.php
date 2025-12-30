<?php
/**
 * FAQ Seeder
 * Phase 4 Week 2 Day 3
 *
 * Populates faqs table with default data
 * Migrated from hardcoded arrays in HolidayProgramModel.php
 */

require_once __DIR__ . '/../../server.php';
require_once __DIR__ . '/../../app/Repositories/FAQRepository.php';

class FAQSeeder {
    private $conn;
    private $repository;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repository = new FAQRepository($conn);
    }

    /**
     * Run the seeder
     */
    public function run() {
        echo "Seeding faqs table...\n";

        // Clear existing data (optional - comment out to preserve existing data)
        // $this->conn->query("TRUNCATE TABLE faqs");

        $faqs = $this->getFAQs();
        $created = 0;
        $skipped = 0;

        foreach ($faqs as $faq) {
            try {
                $id = $this->repository->createFAQ($faq);
                $questionPreview = substr($faq['question'], 0, 50);
                echo "  ✓ Created: [{$faq['category']}] {$questionPreview}...\n";
                $created++;
            } catch (Exception $e) {
                echo "  ✗ Skipped: {$faq['question']} - " . $e->getMessage() . "\n";
                $skipped++;
            }
        }

        echo "\nFAQ Seeder Complete:\n";
        echo "  Created: $created\n";
        echo "  Skipped: $skipped\n";
        echo "  Total: " . ($created + $skipped) . "\n\n";

        return $created;
    }

    /**
     * Get FAQs data
     * Migrated from HolidayProgramModel::getFaqsForProgram()
     */
    private function getFAQs() {
        return [
            // General FAQs (from HolidayProgramModel lines 153-159)
            [
                'category' => 'General',
                'question' => 'Do I need prior experience to participate?',
                'answer' => 'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'General',
                'question' => 'What is the age requirement?',
                'answer' => 'Programs are designed for ages 13-18, though specific programs may have different age ranges. Check the program details for specific requirements.',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'General',
                'question' => 'What should I bring to the program?',
                'answer' => 'Please bring a notebook, pen/pencil, water bottle, and your enthusiasm! Lunch will be provided. All equipment and software will be provided on-site.',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'General',
                'question' => 'How long is the program?',
                'answer' => 'Most holiday programs run for 5 days (one week) from 9:00 AM to 3:00 PM. Specific program durations are listed in the program details.',
                'order_number' => 4,
                'is_active' => 1
            ],
            [
                'category' => 'General',
                'question' => 'Will lunch be provided?',
                'answer' => 'Yes, lunch is provided daily for all participants. Please inform us of any dietary restrictions or allergies during registration.',
                'order_number' => 5,
                'is_active' => 1
            ],

            // Registration FAQs
            [
                'category' => 'Registration',
                'question' => 'How do I register for a program?',
                'answer' => 'You can register through our online portal. Simply create an account, browse available programs, and complete the registration form. You will receive a confirmation email upon successful registration.',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Registration',
                'question' => 'Is there a registration fee?',
                'answer' => 'Most programs are free of charge, thanks to our sponsors. Some specialized workshops may have a nominal fee to cover materials. Check the specific program details for fee information.',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'Registration',
                'question' => 'When does registration open?',
                'answer' => 'Registration typically opens 4-6 weeks before the program start date. You will be notified via email when registration opens for upcoming programs.',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'Registration',
                'question' => 'Can I register for multiple programs?',
                'answer' => 'Yes, you can register for multiple programs, provided they do not overlap in dates. Spaces are limited, so early registration is recommended.',
                'order_number' => 4,
                'is_active' => 1
            ],
            [
                'category' => 'Registration',
                'question' => 'What if the program is full?',
                'answer' => 'If a program reaches capacity, you can join the waiting list. You will be notified if a space becomes available. We also run similar programs throughout the year.',
                'order_number' => 5,
                'is_active' => 1
            ],
            [
                'category' => 'Registration',
                'question' => 'Do I need parental consent?',
                'answer' => 'Yes, participants under 18 require parental or guardian consent. The consent form is included in the registration process and must be signed before attendance.',
                'order_number' => 6,
                'is_active' => 1
            ],

            // Programs FAQs
            [
                'category' => 'Programs',
                'question' => 'What types of programs are available?',
                'answer' => 'We offer a variety of programs including 3D modeling, robotics, coding, game development, digital art, and more. Check our program catalog for current offerings.',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Programs',
                'question' => 'Will I receive a certificate upon completion?',
                'answer' => 'Yes, all participants who complete the program will receive a certificate of completion. Certificates are awarded at the showcase event on the final day.',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'Programs',
                'question' => 'Can I work on my own project?',
                'answer' => 'Yes! While we provide structured lessons and activities, you are encouraged to work on your own creative projects. Mentors will be available to provide guidance and support.',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'Programs',
                'question' => 'What is the student-to-mentor ratio?',
                'answer' => 'We maintain a low ratio of approximately 6-8 students per mentor to ensure personalized attention and support. Some programs may have additional mentors for specialized topics.',
                'order_number' => 4,
                'is_active' => 1
            ],
            [
                'category' => 'Programs',
                'question' => 'What happens on the final day?',
                'answer' => 'The final day features a showcase event where participants present their projects to family, friends, and mentors. This is followed by a certificate ceremony and celebration.',
                'order_number' => 5,
                'is_active' => 1
            ],

            // Technical FAQs
            [
                'category' => 'Technical',
                'question' => 'Do I need to bring my own computer?',
                'answer' => 'No, all computers and equipment are provided. However, you are welcome to bring your own laptop if you prefer to work on familiar equipment.',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Technical',
                'question' => 'What software will we use?',
                'answer' => 'We use industry-standard software such as Blender (3D), Unity (game development), Python/Scratch (coding), and Adobe Creative Suite (digital art). All software is provided.',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'Technical',
                'question' => 'Can I take my project files home?',
                'answer' => 'Yes, you will be able to save your project files to a USB drive or cloud storage. We recommend bringing a USB drive on the final day to save your work.',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'Technical',
                'question' => 'Is there internet access?',
                'answer' => 'Yes, Wi-Fi is available for all participants. However, please note that some websites and services may be restricted to ensure a focused learning environment.',
                'order_number' => 4,
                'is_active' => 1
            ],

            // Logistics FAQs
            [
                'category' => 'Logistics',
                'question' => 'Where is the program located?',
                'answer' => 'Programs are held at the Sci-Bono Clubhouse, located at [address]. The venue is accessible by public transport, and parking is available for drop-offs.',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Logistics',
                'question' => 'What are the daily hours?',
                'answer' => 'Programs typically run from 9:00 AM to 3:00 PM, Monday through Friday. Drop-off is available from 8:30 AM, and pick-up can be as late as 3:30 PM.',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'Logistics',
                'question' => 'Is transportation provided?',
                'answer' => 'Transportation is not provided. Participants must arrange their own transport to and from the venue. We are easily accessible via public transport.',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'Logistics',
                'question' => 'What if I need to miss a day?',
                'answer' => 'Regular attendance is important for continuity. If you must miss a day, please notify us in advance. Mentors can provide materials to help you catch up.',
                'order_number' => 4,
                'is_active' => 1
            ],
            [
                'category' => 'Logistics',
                'question' => 'Can parents observe the sessions?',
                'answer' => 'Parents are welcome to observe during the showcase on the final day. During regular sessions, we ask that parents drop off and pick up to allow students to focus and engage independently.',
                'order_number' => 5,
                'is_active' => 1
            ]
        ];
    }
}

// Run seeder if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $seeder = new FAQSeeder($conn);
        $seeder->run();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
