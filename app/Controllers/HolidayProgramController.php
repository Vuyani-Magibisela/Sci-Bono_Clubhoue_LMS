<?php
require_once __DIR__ . '/../Models/HolidayProgramModel.php';

class HolidayProgramController {
    private $conn;
    private $model;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new HolidayProgramModel($conn);
    }
    
    public function getProgram($programId) {
        $program = $this->model->getProgramById($programId);
        
        if (!$program) {
            // Return default program data if not found
            return $this->getDefaultProgram($programId);
        }
        
        // Check if user is registered
        $userIsRegistered = false;
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
            // Logic to check if user is registered
            // ...
        }
        
        // Get capacity information
        $capacityStatus = $this->model->checkProgramCapacity($programId);
        
        return [
            'program' => $program,
            'user_is_registered' => $userIsRegistered,
            'capacity_status' => $capacityStatus
        ];
    }
    
    private function getDefaultProgram($programId) {
        // Return default program data if database doesn't have it
        return [
            'id' => $programId,
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
            'registration_open' => true,
            // Add other fields as needed for the default program
            // ...
        ];
    }
}
?>