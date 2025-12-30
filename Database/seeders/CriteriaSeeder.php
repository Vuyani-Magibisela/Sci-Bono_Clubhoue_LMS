<?php
/**
 * Evaluation Criteria Seeder
 * Phase 4 Week 2 Day 3
 *
 * Populates evaluation_criteria table with default data
 * Migrated from hardcoded arrays in HolidayProgramModel.php
 */

require_once __DIR__ . '/../../server.php';
require_once __DIR__ . '/../../app/Repositories/EvaluationCriteriaRepository.php';

class CriteriaSeeder {
    private $conn;
    private $repository;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repository = new EvaluationCriteriaRepository($conn);
    }

    /**
     * Run the seeder
     */
    public function run() {
        echo "Seeding evaluation_criteria table...\n";

        // Clear existing data (optional - comment out to preserve existing data)
        // $this->conn->query("TRUNCATE TABLE evaluation_criteria");

        $criteria = $this->getCriteria();
        $created = 0;
        $skipped = 0;

        foreach ($criteria as $criterion) {
            try {
                $id = $this->repository->createCriterion($criterion);
                echo "  ✓ Created: {$criterion['name']} ({$criterion['points']} points)\n";
                $created++;
            } catch (Exception $e) {
                echo "  ✗ Skipped: {$criterion['name']} - " . $e->getMessage() . "\n";
                $skipped++;
            }
        }

        // Verify total points
        $totalPoints = $this->repository->getTotalPoints();
        echo "\nTotal Points: $totalPoints\n";

        echo "\nCriteria Seeder Complete:\n";
        echo "  Created: $created\n";
        echo "  Skipped: $skipped\n";
        echo "  Total: " . ($created + $skipped) . "\n\n";

        return $created;
    }

    /**
     * Get evaluation criteria data
     * Migrated from HolidayProgramModel::getCriteriaForProgram()
     */
    private function getCriteria() {
        return [
            // Project Evaluation Criteria (from HolidayProgramModel lines 132-138)
            [
                'name' => 'Technical Execution',
                'description' => 'Quality of technical skills demonstrated',
                'points' => 20,
                'category' => 'Project Evaluation',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'name' => 'Creativity',
                'description' => 'Original ideas and creative approach',
                'points' => 20,
                'category' => 'Project Evaluation',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'name' => 'Message',
                'description' => 'Clear connection to SDGs and effective communication of message',
                'points' => 20,
                'category' => 'Project Evaluation',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'name' => 'Completion',
                'description' => 'Level of completion and polish',
                'points' => 20,
                'category' => 'Project Evaluation',
                'order_number' => 4,
                'is_active' => 1
            ],
            [
                'name' => 'Presentation',
                'description' => 'Quality of showcase presentation',
                'points' => 20,
                'category' => 'Project Evaluation',
                'order_number' => 5,
                'is_active' => 1
            ],

            // Teamwork Criteria
            [
                'name' => 'Collaboration',
                'description' => 'Ability to work effectively with team members',
                'points' => 15,
                'category' => 'Teamwork',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'name' => 'Communication',
                'description' => 'Clear and effective communication within the team',
                'points' => 10,
                'category' => 'Teamwork',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'name' => 'Leadership',
                'description' => 'Taking initiative and guiding team efforts',
                'points' => 10,
                'category' => 'Teamwork',
                'order_number' => 3,
                'is_active' => 1
            ],

            // Participation Criteria
            [
                'name' => 'Attendance',
                'description' => 'Consistent participation in all sessions',
                'points' => 10,
                'category' => 'Participation',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'name' => 'Engagement',
                'description' => 'Active involvement in activities and discussions',
                'points' => 10,
                'category' => 'Participation',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'name' => 'Initiative',
                'description' => 'Going beyond minimum requirements and showing enthusiasm',
                'points' => 5,
                'category' => 'Participation',
                'order_number' => 3,
                'is_active' => 1
            ]
        ];
    }
}

// Run seeder if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $seeder = new CriteriaSeeder($conn);
        $seeder->run();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
