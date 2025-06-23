<?php
class HolidayProgramModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
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
    
    private function getRequirementsForProgram($programId) {
        // Example hardcoded data
        return [
            'All projects must address at least one UN Sustainable Development Goal',
            'Projects must be completed by the end of the program',
            'Each participant/team must prepare a brief presentation for the showcase',
            'Projects should demonstrate application of skills learned during the program'
        ];
    }
    
    private function getCriteriaForProgram($programId) {
        // Example hardcoded data
        return [
            'Technical Execution' => 'Quality of technical skills demonstrated',
            'Creativity' => 'Original ideas and creative approach',
            'Message' => 'Clear connection to SDGs and effective communication of message',
            'Completion' => 'Level of completion and polish',
            'Presentation' => 'Quality of showcase presentation'
        ];
    }
    
    private function getItemsForProgram($programId) {
        // Example hardcoded data
        return [
            'Notebook and pen/pencil',
            'Snacks (lunch will be provided)',
            'Water bottle',
            'Enthusiasm and creativity!'
        ];
    }
    
    private function getFaqsForProgram($programId) {
        // Example hardcoded data
        return [
            [
                'question' => 'Do I need prior experience to participate?',
                'answer' => 'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.'
            ],
            // Add more FAQs
        ];
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