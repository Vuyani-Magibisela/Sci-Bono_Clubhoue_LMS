<?php
/**
 * Check if a program has reached its registration capacity
 * 
 * @param mysqli $conn Database connection
 * @param int $programId The program ID to check
 * @return array Associative array with 'member_full' and 'mentor_full' boolean values
 */
function checkProgramCapacity($conn, $programId) {
    $result = [
        'member_full' => false,
        'mentor_full' => false
    ];
    
    // Count regular members
    $memberSql = "SELECT COUNT(*) as count FROM holiday_program_attendees 
                  WHERE program_id = ? AND mentor_registration = 0";
    
    $stmt = $conn->prepare($memberSql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $memberCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // Count mentors
    $mentorSql = "SELECT COUNT(*) as count FROM holiday_program_attendees 
                  WHERE program_id = ? AND mentor_registration = 1";
    
    $stmt = $conn->prepare($mentorSql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $mentorCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // Check against capacity limits
    if ($memberCount >= 30) {
        $result['member_full'] = true;
    }
    
    if ($mentorCount >= 5) {
        $result['mentor_full'] = true;
    }
    
    return $result;
}

// You can add other shared functions here as well
?>