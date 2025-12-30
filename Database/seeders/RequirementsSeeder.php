<?php
/**
 * Requirements Seeder
 * Phase 4 Week 2 Day 3
 *
 * Populates program_requirements table with default data
 * Migrated from hardcoded arrays in HolidayProgramModel.php
 */

require_once __DIR__ . '/../../server.php';
require_once __DIR__ . '/../../app/Repositories/ProgramRequirementRepository.php';

class RequirementsSeeder {
    private $conn;
    private $repository;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repository = new ProgramRequirementRepository($conn);
    }

    /**
     * Run the seeder
     */
    public function run() {
        echo "Seeding program_requirements table...\n";

        // Clear existing data (optional - comment out to preserve existing data)
        // $this->conn->query("TRUNCATE TABLE program_requirements");

        $requirements = $this->getRequirements();
        $created = 0;
        $skipped = 0;

        foreach ($requirements as $requirement) {
            try {
                $id = $this->repository->createRequirement($requirement);
                echo "  ✓ Created: {$requirement['category']} - " . substr($requirement['requirement'], 0, 50) . "...\n";
                $created++;
            } catch (Exception $e) {
                echo "  ✗ Skipped: {$requirement['category']} - " . $e->getMessage() . "\n";
                $skipped++;
            }
        }

        echo "\nRequirements Seeder Complete:\n";
        echo "  Created: $created\n";
        echo "  Skipped: $skipped\n";
        echo "  Total: " . ($created + $skipped) . "\n\n";

        return $created;
    }

    /**
     * Get requirements data
     * Migrated from HolidayProgramModel::getRequirementsForProgram()
     */
    private function getRequirements() {
        return [
            // Project Guidelines (from HolidayProgramModel lines 122-127)
            [
                'category' => 'Project Guidelines',
                'requirement' => 'All projects must address at least one UN Sustainable Development Goal',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Project Guidelines',
                'requirement' => 'Projects must be completed by the end of the program',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'Project Guidelines',
                'requirement' => 'Each participant/team must prepare a brief presentation for the showcase',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'Project Guidelines',
                'requirement' => 'Projects should demonstrate application of skills learned during the program',
                'order_number' => 4,
                'is_active' => 1
            ],

            // What to Bring (from HolidayProgramModel lines 143-148)
            [
                'category' => 'What to Bring',
                'requirement' => 'Notebook and pen/pencil',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'What to Bring',
                'requirement' => 'Snacks (lunch will be provided)',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'What to Bring',
                'requirement' => 'Water bottle',
                'order_number' => 3,
                'is_active' => 1
            ],
            [
                'category' => 'What to Bring',
                'requirement' => 'Enthusiasm and creativity!',
                'order_number' => 4,
                'is_active' => 1
            ],

            // Age Requirements
            [
                'category' => 'Age Requirements',
                'requirement' => 'Participants must be between 13 and 18 years old',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'Age Requirements',
                'requirement' => 'Parental consent required for participants under 18',
                'order_number' => 2,
                'is_active' => 1
            ],

            // General Requirements
            [
                'category' => 'General Requirements',
                'requirement' => 'Participants must attend all scheduled sessions',
                'order_number' => 1,
                'is_active' => 1
            ],
            [
                'category' => 'General Requirements',
                'requirement' => 'Respect for all participants, mentors, and equipment is mandatory',
                'order_number' => 2,
                'is_active' => 1
            ],
            [
                'category' => 'General Requirements',
                'requirement' => 'No prior technical experience required',
                'order_number' => 3,
                'is_active' => 1
            ]
        ];
    }
}

// Run seeder if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $seeder = new RequirementsSeeder($conn);
        $seeder->run();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
