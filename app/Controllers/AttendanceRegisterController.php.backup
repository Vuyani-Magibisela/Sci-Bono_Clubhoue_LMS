<?php
require_once __DIR__ . '/../Models/AttendanceRegisterModel.php';

class AttendanceRegisterController {
    private $attendanceModel;
    
    public function __construct($conn) {
        $this->attendanceModel = new AttendanceRegisterModel($conn);
    }
    
    /**
     * Get attendance data for display
     * 
     * @param string $date Date in Y-m-d format
     * @param string $filter User type filter or 'all'
     * @return array Processed attendance data
     */
    public function getAttendanceData($date, $filter = 'all') {
        $attendees = $this->attendanceModel->getAttendanceByDate($date);
        $counts = $this->attendanceModel->getAttendanceCountByType($date);
        
        // Group attendees by user type if filter is 'all'
        $groupedAttendees = [];
        if ($filter === 'all') {
            foreach ($attendees as $attendee) {
                $userType = $attendee['user_type'];
                if (!isset($groupedAttendees[$userType])) {
                    $groupedAttendees[$userType] = [];
                }
                $groupedAttendees[$userType][] = $attendee;
            }
        } else {
            // Filter attendees by user type
            $filteredAttendees = array_filter($attendees, function($attendee) use ($filter) {
                return $attendee['user_type'] === $filter;
            });
            $groupedAttendees[$filter] = $filteredAttendees;
        }
        
        return [
            'groupedAttendees' => $groupedAttendees,
            'counts' => $counts,
            'date' => $date,
            'filter' => $filter
        ];
    }
    
    /**
     * Get a list of active dates with attendance records
     * 
     * @return array Dates with attendance records
     */
    public function getActiveDates() {
        return $this->attendanceModel->getActiveDates();
    }
    
    /**
     * Format attendance time for display
     * 
     * @param string $timestamp MySQL datetime
     * @return string Formatted time (e.g., "14:30")
     */
    public function formatTime($timestamp) {
        if (empty($timestamp)) return '-';
        return date('H:i', strtotime($timestamp));
    }
    
    /**
     * Calculate attendance duration
     * 
     * @param string $checkIn Check-in timestamp
     * @param string $checkOut Check-out timestamp
     * @return string Formatted duration (e.g., "2h 30m")
     */
    public function calculateDuration($checkIn, $checkOut) {
        if (empty($checkIn) || empty($checkOut)) {
            return '-';
        }
        
        $checkInTime = strtotime($checkIn);
        $checkOutTime = strtotime($checkOut);
        $durationMinutes = round(($checkOutTime - $checkInTime) / 60);
        
        if ($durationMinutes < 0) {
            return 'Invalid';
        }
        
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }
}
?>