<?php
session_start();
require_once '../../../server.php';

/**
 * Enhanced Holiday Program Registration Processor
 * Handles cohort assignments and prerequisite validation
 */

function validatePrerequisites($conn, $attendeeData, $workshopIds) {
    $validationResults = [];
    
    foreach ($workshopIds as $workshopId) {
        // Get workshop prerequisites
        $sql = "SELECT pr.*, w.title as workshop_title
                FROM holiday_program_prerequisites pr
                JOIN holiday_program_workshops w ON pr.workshop_id = w.id
                WHERE pr.workshop_id = ? AND pr.is_mandatory = TRUE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $workshopId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $prerequisites = [];
        while ($row = $result->fetch_assoc()) {
            $prerequisites[] = $row;
        }
        
        $validationResults[$workshopId] = [
            'workshop_title' => $prerequisites[0]['workshop_title'] ?? 'Unknown Workshop',
            'prerequisites' => $prerequisites,
            'met' => true, // Default to true, will check each prerequisite
            'failed_requirements' => []
        ];
        
        // Check each prerequisite
        foreach ($prerequisites as $prereq) {
            $met = checkSinglePrerequisite($attendeeData, $prereq);
            
            if (!$met) {
                $validationResults[$workshopId]['met'] = false;
                $validationResults[$workshopId]['failed_requirements'][] = $prereq['description'];
            }
        }
    }
    
    return $validationResults;
}

function checkSinglePrerequisite($attendeeData, $prerequisite) {
    switch ($prerequisite['prerequisite_type']) {
        case 'age':
            $birthDate = new DateTime($attendeeData['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            return $age >= intval($prerequisite['requirement_value']);
            
        case 'skill':
            // For now, assume all skill prerequisites are met
            // In a real system, this would check against user profile or assessment results
            return true;
            
        case 'workshop':
            // Check if user has completed required workshop
            // This would require a workshop completion tracking system
            return true;
            
        case 'equipment':
            // Assume equipment requirements are informational only
            return true;
            
        default:
            return true;
    }
}

function assignToCohort($conn, $attendeeId, $cohortId) {
    // Update attendee with cohort assignment
    $sql = "UPDATE holiday_program_attendees SET cohort_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cohortId, $attendeeId);
    
    if ($stmt->execute()) {
        // Update cohort participant count
        $updateCohortSql = "UPDATE holiday_program_cohorts 
                           SET current_participants = current_participants + 1 
                           WHERE id = ?";
        $updateStmt = $conn->prepare($updateCohortSql);
        $updateStmt->bind_param("i", $cohortId);
        $updateStmt->execute();
        
        return true;
    }
    
    return false;
}

function processWorkshopAssignment($conn, $attendeeId, $workshopPreferences, $validationResults) {
    $assignments = [];
    
    // Try to assign to first preference first
    foreach ($workshopPreferences as $index => $workshopId) {
        // Check if prerequisites are met
        if (!$validationResults[$workshopId]['met']) {
            continue; // Skip workshops where prerequisites aren't met
        }
        
        // Check workshop capacity
        $sql = "SELECT w.max_participants,
                       COUNT(DISTINCT a.id) as current_enrolled
                FROM holiday_program_workshops w
                LEFT JOIN holiday_program_attendees a ON w.program_id = a.program_id 
                    AND JSON_CONTAINS(a.workshop_preference, CAST(w.id AS JSON))
                WHERE w.id = ?
                GROUP BY w.id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $workshopId);
        $stmt->execute();
        $result = $stmt->get_result();
        $capacity = $result->fetch_assoc();
        
        if ($capacity && $capacity['current_enrolled'] < $capacity['max_participants']) {
            // Workshop has space, assign it
            $assignments[] = [
                'workshop_id' => $workshopId,
                'preference_order' => $index + 1,
                'assigned' => true
            ];
            break; // Only assign to one workshop for now
        }
    }
    
    return $assignments;
}

// Main registration processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->autocommit(false); // Start transaction
        
        // Extract form data
        $programId = intval($_POST['program_id']);
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $cohortId = isset($_POST['cohort_id']) ? intval($_POST['cohort_id']) : null;
        $workshopPreferences = json_decode($_POST['workshop_preferences'], true) ?? [];
        
        // Validate required fields
        if (empty($name) || empty($surname) || empty($email) || empty($workshopPreferences)) {
            throw new Exception('Please fill in all required fields and select at least one workshop.');
        }
        
        // Check if email already exists for this program
        $checkEmailSql = "SELECT id FROM holiday_program_attendees WHERE email = ? AND program_id = ?";
        $checkStmt = $conn->prepare($checkEmailSql);
        $checkStmt->bind_param("si", $email, $programId);
        $checkStmt->execute();
        $existingResult = $checkStmt->get_result();
        
        if ($existingResult->num_rows > 0) {
            throw new Exception('An account with this email address already exists for this program.');
        }
        
        // Validate prerequisites for selected workshops
        $attendeeData = [
            'date_of_birth' => $dateOfBirth,
            'email' => $email
        ];
        
        $validationResults = validatePrerequisites($conn, $attendeeData, $workshopPreferences);
        
        // Check if any mandatory prerequisites are not met
        $prerequisiteFailures = [];
        foreach ($validationResults as $workshopId => $result) {
            if (!$result['met']) {
                $prerequisiteFailures[] = $result['workshop_title'] . ': ' . implode(', ', $result['failed_requirements']);
            }
        }
        
        if (!empty($prerequisiteFailures)) {
            throw new Exception('Prerequisites not met for the following workshops: ' . implode('; ', $prerequisiteFailures));
        }
        
        // Insert attendee record
        $insertSql = "INSERT INTO holiday_program_attendees 
                     (program_id, name, surname, email, phone, date_of_birth, gender, 
                      workshop_preference, cohort_id, registration_status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($insertSql);
        $workshopPreferenceJson = json_encode($workshopPreferences);
        $stmt->bind_param("issssssi", $programId, $name, $surname, $email, $phone, 
                         $dateOfBirth, $gender, $workshopPreferenceJson, $cohortId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create registration record.');
        }
        
        $attendeeId = $conn->insert_id;
        
        // Assign to cohort if specified
        if ($cohortId) {
            if (!assignToCohort($conn, $attendeeId, $cohortId)) {
                throw new Exception('Failed to assign to selected cohort.');
            }
        }
        
        // Process workshop assignments
        $assignments = processWorkshopAssignment($conn, $attendeeId, $workshopPreferences, $validationResults);
        
        // Store prerequisite validation results
        $prerequisitesMet = [];
        foreach ($validationResults as $workshopId => $result) {
            $prerequisitesMet[$workshopId] = $result['met'];
        }
        
        $updatePrereqSql = "UPDATE holiday_program_attendees SET prerequisites_met = ? WHERE id = ?";
        $prereqStmt = $conn->prepare($updatePrereqSql);
        $prerequisitesJson = json_encode($prerequisitesMet);
        $prereqStmt->bind_param("si", $prerequisitesJson, $attendeeId);
        $prereqStmt->execute();
        
        // Generate confirmation details
        $confirmationCode = 'HP' . $programId . '-' . str_pad($attendeeId, 4, '0', STR_PAD_LEFT);
        
        // Update with confirmation code
        $updateCodeSql = "UPDATE holiday_program_attendees SET confirmation_code = ? WHERE id = ?";
        $codeStmt = $conn->prepare($updateCodeSql);
        $codeStmt->bind_param("si", $confirmationCode, $attendeeId);
        $codeStmt->execute();
        
        $conn->commit(); // Commit transaction
        
        // Set session variables for confirmation page
        $_SESSION['registration_success'] = true;
        $_SESSION['confirmation_code'] = $confirmationCode;
        $_SESSION['attendee_name'] = $name . ' ' . $surname;
        $_SESSION['workshop_assignments'] = $assignments;
        
        // Redirect to confirmation page
        header('Location: registration_confirmation.php?code=' . urlencode($confirmationCode));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        
        // Set error message and redirect back to form
        $_SESSION['registration_error'] = $e->getMessage();
        header('Location: holidayProgramRegistration.php?program_id=' . $programId . '&error=1');
        exit();
    }
} else {
    // Redirect if accessed directly
    header('Location: holidayProgramRegistration.php');
    exit();
}
?>