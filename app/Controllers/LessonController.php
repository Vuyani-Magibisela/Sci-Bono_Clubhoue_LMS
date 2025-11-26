<?php
require_once __DIR__ . '/../Models/LessonModel.php';
require_once __DIR__ . '/../Models/ProgressModel.php';

class LessonController {
    private $lessonModel;
    private $progressModel;
    
    public function __construct($conn) {
        $this->lessonModel = new LessonModel($conn);
        $this->progressModel = new ProgressModel($conn);
    }
    
    public function getLessonDetails($lessonId) {
        return $this->lessonModel->getLessonDetails($lessonId);
    }
    
    public function getSectionLessons($sectionId) {
        return $this->lessonModel->getSectionLessons($sectionId);
    }
    
    public function getLessonProgress($userId, $lessonId) {
        return $this->progressModel->getLessonProgress($userId, $lessonId);
    }
    
    public function updateLessonProgress($userId, $lessonId, $status, $progress, $completed) {
        // Validate CSRF token
        require_once __DIR__ . '/../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in LessonController::updateLessonProgress - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }

        return $this->progressModel->updateLessonProgress($userId, $lessonId, $status, $progress, $completed);
    }
    
    public function markLessonComplete($userId, $lessonId) {
        // Validate CSRF token
        require_once __DIR__ . '/../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in LessonController::markLessonComplete - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }

        return $this->progressModel->updateLessonProgress($userId, $lessonId, 'completed', 100, true);
    }
    
    // Add other lesson controller methods here
}
?>