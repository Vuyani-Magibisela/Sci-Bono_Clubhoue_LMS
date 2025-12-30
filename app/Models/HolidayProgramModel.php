<?php
/**
 * Holiday Program Model
 * Phase 4 Week 2 Day 4: Updated to use repository pattern
 */

require_once __DIR__ . '/../Repositories/ProgramRequirementRepository.php';
require_once __DIR__ . '/../Repositories/EvaluationCriteriaRepository.php';
require_once __DIR__ . '/../Repositories/FAQRepository.php';
require_once __DIR__ . '/../Services/CacheService.php';

class HolidayProgramModel {
    private $conn;
    private $requirementRepo;
    private $criteriaRepo;
    private $faqRepo;
    private $cache;

    public function __construct($conn) {
        $this->conn = $conn;

        // Initialize repositories
        $this->requirementRepo = new ProgramRequirementRepository($conn);
        $this->criteriaRepo = new EvaluationCriteriaRepository($conn);
        $this->faqRepo = new FAQRepository($conn);

        // Initialize cache service (1 hour TTL for configuration data)
        $this->cache = new CacheService();
    }
    
    public function getProgramById($programId) {
        // Basic program data
        $sql = "SELECT * FROM holiday_programs WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $program = $result->fetch_assoc();
            
            // Add workshops
            $program['workshops'] = $this->getWorkshopsForProgram($programId);
            
            // Add other complex structures
            $program['daily_schedule'] = $this->getScheduleForProgram($programId);
            $program['project_requirements'] = $this->getRequirementsForProgram($programId);
            $program['evaluation_criteria'] = $this->getCriteriaForProgram($programId);
            $program['what_to_bring'] = $this->getItemsForProgram($programId);
            $program['faq'] = $this->getFaqsForProgram($programId);
            
            return $program;
        }
        
        return null;
    }
    
    private function getWorkshopsForProgram($programId) {
        $workshops = [];
        
        $sql = "SELECT * FROM holiday_program_workshops WHERE program_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($workshop = $result->fetch_assoc()) {
            // Map database column names to the ones expected by the view
            $workshop['mentor'] = $workshop['instructor']; // Map instructor to mentor
            $workshop['capacity'] = $workshop['max_participants']; // Map max_participants to capacity
            
            // These would ideally come from database tables
            // For now, hardcoding as examples
            $workshop['skills'] = ['3D modeling', 'Texturing', 'Lighting', 'Rendering'];
            $workshop['software'] = ['Blender'];
            $workshop['icon'] = 'fas fa-cube';
            
            $workshops[] = $workshop;
        }
        
        return $workshops;
    }
    
    // Implement similar methods for other complex structures
    private function getScheduleForProgram($programId) {
        $schedule = [];
        
        // Get schedule days from database
        $sql = "SELECT * FROM holiday_program_schedules WHERE program_id = ? ORDER BY day_number";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($day = $result->fetch_assoc()) {
            $dayName = $day['day_name']; // e.g., "Day 1"
            $schedule[$dayName] = [
                'date' => date('l, F j, Y', strtotime($day['date'])),
                'theme' => $day['theme'],
                'morning' => [],
                'afternoon' => []
            ];
            
            // Get schedule items for this day
            $itemsSql = "SELECT * FROM holiday_program_schedule_items WHERE schedule_id = ? ORDER BY id";
            $itemsStmt = $this->conn->prepare($itemsSql);
            $itemsStmt->bind_param("i", $day['id']);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
            
            while ($item = $itemsResult->fetch_assoc()) {
                if ($item['session_type'] == 'morning') {
                    $schedule[$dayName]['morning'][$item['time_slot']] = $item['activity'];
                } else { // afternoon
                    $schedule[$dayName]['afternoon'][$item['time_slot']] = $item['activity'];
                }
            }
        }
        
        // If no schedule is found, return a default one
        if (empty($schedule)) {
            $schedule = [
                'Day 1' => [
                    'date' => 'Monday, March 31, 2025',
                    'theme' => 'Introduction & Fundamentals',
                    'morning' => [
                        '9:00 - 9:30' => 'Welcome and program overview for all participants',
                        // Add more schedule items
                    ],
                    'afternoon' => [
                        '1:00 - 2:30' => 'Software introduction and basic skills training',
                        // Add more schedule items
                    ]
                ],
                // Add more default days if needed
            ];
        }
        
        return $schedule;
    }
    
    /**
     * Get project requirements from database
     * Migrated from hardcoded array to use ProgramRequirementRepository
     *
     * @param int $programId (not currently used, but kept for backward compatibility)
     * @return array List of requirement strings in 'Project Guidelines' category
     */
    private function getRequirementsForProgram($programId) {
        // Use cache for frequently accessed configuration data (1 hour TTL)
        return $this->cache->remember('program_requirements_project_guidelines', function() {
            try {
                // Get requirements from 'Project Guidelines' category
                $requirements = $this->requirementRepo->getByCategory('Project Guidelines', true);

                // Return as simple array of strings (backward compatible format)
                return array_map(function($req) {
                    return $req['requirement'];
                }, $requirements);

            } catch (Exception $e) {
                // Fallback to empty array if database query fails
                error_log("Failed to get requirements: " . $e->getMessage());
                return [];
            }
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get evaluation criteria from database
     * Migrated from hardcoded array to use EvaluationCriteriaRepository
     *
     * @param int $programId (not currently used, but kept for backward compatibility)
     * @return array Associative array of [name => description] (backward compatible format)
     */
    private function getCriteriaForProgram($programId) {
        // Use cache for frequently accessed configuration data (1 hour TTL)
        return $this->cache->remember('evaluation_criteria_project_evaluation', function() {
            try {
                // Get criteria from 'Project Evaluation' category
                // Uses getAsKeyValue() method for backward compatibility
                $criteria = $this->criteriaRepo->getAsKeyValue('Project Evaluation', true);

                return $criteria;

            } catch (Exception $e) {
                // Fallback to empty array if database query fails
                error_log("Failed to get criteria: " . $e->getMessage());
                return [];
            }
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get "What to Bring" items from database
     * Migrated from hardcoded array to use ProgramRequirementRepository
     *
     * @param int $programId (not currently used, but kept for backward compatibility)
     * @return array List of item strings in 'What to Bring' category
     */
    private function getItemsForProgram($programId) {
        // Use cache for frequently accessed configuration data (1 hour TTL)
        return $this->cache->remember('program_requirements_what_to_bring', function() {
            try {
                // Get requirements from 'What to Bring' category
                $items = $this->requirementRepo->getByCategory('What to Bring', true);

                // Return as simple array of strings (backward compatible format)
                return array_map(function($item) {
                    return $item['requirement'];
                }, $items);

            } catch (Exception $e) {
                // Fallback to empty array if database query fails
                error_log("Failed to get items: " . $e->getMessage());
                return [];
            }
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get FAQs from database
     * Migrated from hardcoded array to use FAQRepository
     *
     * @param int $programId (not currently used, but kept for backward compatibility)
     * @return array Array of arrays with 'question' and 'answer' keys (backward compatible format)
     */
    private function getFaqsForProgram($programId) {
        // Use cache for frequently accessed configuration data (1 hour TTL)
        return $this->cache->remember('faqs_all_categories', function() {
            try {
                // Get all active FAQs (all categories)
                // Uses getLegacyFormat() method for backward compatibility
                $faqs = $this->faqRepo->getLegacyFormat(null, true);

                return $faqs;

            } catch (Exception $e) {
                // Fallback to empty array if database query fails
                error_log("Failed to get FAQs: " . $e->getMessage());
                return [];
            }
        }, 3600); // Cache for 1 hour
    }
    
    public function checkProgramCapacity($programId) {
        // Check capacity status
        $memberCapacity = 30;
        $mentorCapacity = 5;
        
        // Count current enrollments
        $memberSql = "SELECT COUNT(*) as count FROM holiday_program_attendees 
                      WHERE program_id = ? AND mentor_registration = 0";
        $stmt = $this->conn->prepare($memberSql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $memberCount = $stmt->get_result()->fetch_assoc()['count'];
        
        $mentorSql = "SELECT COUNT(*) as count FROM holiday_program_attendees 
                      WHERE program_id = ? AND mentor_registration = 1";
        $stmt = $this->conn->prepare($mentorSql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $mentorCount = $stmt->get_result()->fetch_assoc()['count'];
        
        return [
            'member_count' => $memberCount,
            'mentor_count' => $mentorCount,
            'member_capacity' => $memberCapacity,
            'mentor_capacity' => $mentorCapacity,
            'member_full' => $memberCount >= $memberCapacity,
            'mentor_full' => $mentorCount >= $mentorCapacity,
            'member_percentage' => min(($memberCount / $memberCapacity) * 100, 100),
            'mentor_percentage' => min(($mentorCount / $mentorCapacity) * 100, 100)
        ];
    }
}
?>