<?php
/**
 * ====================================================================
 * DEPRECATED CONTROLLER - COMPATIBILITY WRAPPER
 * ====================================================================
 *
 * This controller has been deprecated as of Phase 4 Week 3 implementation.
 * All lesson operations are now handled by modern controllers with proper
 * service layer and BaseController architecture.
 *
 * **Modern Controller:**
 * - app/Controllers/Member/LessonController (lesson viewing and progress)
 *
 * **Modern Routes:**
 * - GET /lessons/{id} - View lesson (Member\LessonController::show)
 * - POST /lessons/{id}/complete - Mark complete (Member\LessonController::complete)
 * - POST /lessons/{id}/progress - Update progress (Member\LessonController::updateProgress)
 *
 * **Modern Views:**
 * - app/Views/member/lessons/show.php (lesson viewer)
 *
 * @deprecated Since Phase 4 Week 3
 * @see app/Controllers/Member/LessonController
 * ====================================================================
 */

// Load modern dependencies for compatibility
require_once __DIR__ . '/../Models/LessonModel.php';
require_once __DIR__ . '/../Models/ProgressModel.php';
require_once __DIR__ . '/../Services/LessonService.php';

/**
 * Legacy LessonController - Compatibility Wrapper
 *
 * This class maintains backward compatibility for views still using
 * the old LessonController. It delegates to LessonService and models
 * to provide the same methods, but users should migrate to modern
 * routes and controllers.
 */
class LessonController {
    private $lessonModel;
    private $progressModel;
    private $lessonService;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->lessonModel = new LessonModel($conn);
        $this->progressModel = new ProgressModel($conn);

        // Try to use modern service layer if available
        try {
            $this->lessonService = new LessonService($conn);
        } catch (Exception $e) {
            // Service not available, will use models directly
            $this->lessonService = null;
        }
    }

    /**
     * Get lesson details by ID
     *
     * @deprecated Use LessonService::getLessonDetails() or Member\LessonController::show()
     * @param int $lessonId Lesson ID
     * @return array|null Lesson details
     */
    public function getLessonDetails($lessonId) {
        if ($this->lessonService) {
            $userId = $_SESSION['id'] ?? null;
            return $this->lessonService->getLessonDetails($lessonId, $userId);
        }
        return $this->lessonModel->getLessonDetails($lessonId);
    }

    /**
     * Get all lessons for a section
     *
     * @deprecated Use LessonModel::getSectionLessons()
     * @param int $sectionId Section ID
     * @return array Array of lessons
     */
    public function getSectionLessons($sectionId) {
        return $this->lessonModel->getSectionLessons($sectionId);
    }

    /**
     * Get user progress for a lesson
     *
     * @deprecated Use ProgressModel::getLessonProgress() or LessonService
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return array Progress data
     */
    public function getLessonProgress($userId, $lessonId) {
        return $this->progressModel->getLessonProgress($userId, $lessonId);
    }

    /**
     * Update lesson progress
     *
     * @deprecated Use LessonService::updateProgress() or Member\LessonController::updateProgress()
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @param string $status Progress status
     * @param int $progress Progress percentage
     * @param bool $completed Completion flag
     * @return bool Success status
     */
    public function updateLessonProgress($userId, $lessonId, $status, $progress, $completed) {
        // Validate CSRF token
        require_once __DIR__ . '/../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in deprecated LessonController::updateLessonProgress - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }

        if ($this->lessonService) {
            try {
                return $this->lessonService->updateProgress($userId, $lessonId, $progress, $status);
            } catch (Exception $e) {
                error_log("Progress update failed in deprecated LessonController: " . $e->getMessage());
                // Fall through to use model directly
            }
        }

        return $this->progressModel->updateLessonProgress($userId, $lessonId, $status, $progress, $completed);
    }

    /**
     * Mark lesson as complete
     *
     * @deprecated Use LessonService::markLessonCompleted() or Member\LessonController::complete()
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Success status
     */
    public function markLessonComplete($userId, $lessonId) {
        // Validate CSRF token
        require_once __DIR__ . '/../core/CSRF.php';
        if (!CSRF::validateToken()) {
            error_log("CSRF validation failed in deprecated LessonController::markLessonComplete - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }

        if ($this->lessonService) {
            try {
                return $this->lessonService->markLessonCompleted($userId, $lessonId);
            } catch (Exception $e) {
                error_log("Mark complete failed in deprecated LessonController: " . $e->getMessage());
                // Fall through to use model directly
            }
        }

        return $this->progressModel->updateLessonProgress($userId, $lessonId, 'completed', 100, true);
    }
}

// Log deprecation warning
if (!defined('LESSON_CONTROLLER_DEPRECATION_LOGGED')) {
    error_log("DEPRECATION WARNING: Legacy LessonController is being used. Please migrate to Member\\LessonController. Called from: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    define('LESSON_CONTROLLER_DEPRECATION_LOGGED', true);
}
?>
