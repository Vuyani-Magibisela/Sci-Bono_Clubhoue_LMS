<?php
/**
 * API Endpoint for Holiday Program Status Updates
 */

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../../../../server.php';
require_once '../../../Models/HolidayProgramCreationModel.php';

try {
    // Initialize model
    $holidayProgramModel = new HolidayProgramCreationModel($conn);
    
    // Get the last check timestamp from the client
    $lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Get all programs that have been updated since last check
    $sql = "SELECT id, term, title, registration_open, updated_at,
                   (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id AND status = 'confirmed') as confirmed_count,
                   (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id) as total_registrations,
                   max_participants
            FROM holiday_programs 
            WHERE updated_at > ? 
            ORDER BY updated_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updates = [];
    while ($row = $result->fetch_assoc()) {
        $updates[] = [
            'program_id' => $row['id'],
            'registration_open' => (bool)$row['registration_open'],
            'status_changed' => true,
            'capacity_info' => [
                'current' => $row['confirmed_count'],
                'max' => $row['max_participants'],
                'percentage' => $row['max_participants'] > 0 ? round(($row['confirmed_count'] / $row['max_participants']) * 100, 1) : 0
            ],
            'has_details' => !empty($row['title']) && !empty($row['term']),
            'updated_at' => $row['updated_at']
        ];
    }
    
    // Also check for programs approaching capacity
    $capacityWarningsSql = "SELECT id, term, title, registration_open,
                                  (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id AND status = 'confirmed') as confirmed_count,
                                  max_participants
                           FROM holiday_programs 
                           WHERE registration_open = 1 
                           AND max_participants > 0 
                           AND (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = holiday_programs.id AND status = 'confirmed') >= (max_participants * 0.9)";
    
    $capacityResult = $conn->query($capacityWarningsSql);
    $capacityWarnings = [];
    
    while ($row = $capacityResult->fetch_assoc()) {
        $capacityWarnings[] = [
            'program_id' => $row['id'],
            'warning_type' => 'capacity_warning',
            'message' => 'Program is nearly full',
            'capacity_info' => [
                'current' => $row['confirmed_count'],
                'max' => $row['max_participants'],
                'percentage' => round(($row['confirmed_count'] / $row['max_participants']) * 100, 1)
            ]
        ];
    }
    
    // Get system-wide statistics
    $systemStats = [
        'total_active_programs' => 0,
        'total_registrations_today' => 0,
        'programs_near_capacity' => count($capacityWarnings)
    ];
    
    // Count active programs
    $activeCountResult = $conn->query("SELECT COUNT(*) as count FROM holiday_programs WHERE registration_open = 1");
    if ($activeCountResult) {
        $systemStats['total_active_programs'] = $activeCountResult->fetch_assoc()['count'];
    }
    
    // Count registrations today
    $todayRegistrationsResult = $conn->query("SELECT COUNT(*) as count FROM holiday_program_attendees WHERE DATE(created_at) = CURDATE()");
    if ($todayRegistrationsResult) {
        $systemStats['total_registrations_today'] = $todayRegistrationsResult->fetch_assoc()['count'];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'updates' => $updates,
        'capacity_warnings' => $capacityWarnings,
        'system_stats' => $systemStats,
        'has_updates' => count($updates) > 0 || count($capacityWarnings) > 0
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    // Handle errors gracefully
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'Unable to fetch program status updates',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
<?php
/**
 * API Endpoint for Individual Program Capacity Check
 * Place this file in: app/Views/holidayPrograms/api/get-program-capacity.php
 */

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include database connection
require_once '../../../../server.php';

try {
    $programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
    
    if ($programId <= 0) {
        throw new Exception('Invalid program ID');
    }
    
    // Get program details and capacity info
    $sql = "SELECT p.id, p.registration_open, p.max_participants, p.updated_at,
                   COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_count,
                   COUNT(a.id) as total_registrations
            FROM holiday_programs p
            LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
            WHERE p.id = ?
            GROUP BY p.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();
    
    if (!$program) {
        throw new Exception('Program not found');
    }
    
    $capacity_info = [
        'current' => $program['confirmed_count'],
        'max' => $program['max_participants'],
        'percentage' => $program['max_participants'] > 0 ? round(($program['confirmed_count'] / $program['max_participants']) * 100, 1) : 0
    ];
    
    $response = [
        'success' => true,
        'program_id' => $programId,
        'registration_open' => (bool)$program['registration_open'],
        'capacity_info' => $capacity_info,
        'status_changed' => false, // Would need to track previous state to determine this
        'timestamp' => date('Y-m-d H:i:s'),
        'last_updated' => $program['updated_at']
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>