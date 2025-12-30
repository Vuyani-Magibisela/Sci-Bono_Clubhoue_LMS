<?php
/**
 * Program Requirement Repository
 * Phase 4 Week 2 Day 2
 *
 * Data access layer for program requirements
 */

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../Models/ProgramRequirement.php';

class ProgramRequirementRepository extends BaseRepository {
    protected $table = 'program_requirements';
    protected $primaryKey = 'id';

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $model = new ProgramRequirement($conn);
        parent::__construct($conn, $model);
    }

    /**
     * Get all requirements grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active requirements
     * @return array Array of requirements grouped by category
     */
    public function getGroupedByCategory($activeOnly = true) {
        return $this->model->getByCategory($activeOnly);
    }

    /**
     * Get requirements for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active requirements
     * @return array Array of requirements
     */
    public function getByCategory($category, $activeOnly = true) {
        return $this->model->getBySpecificCategory($category, $activeOnly);
    }

    /**
     * Get all active requirements
     *
     * @return array Array of active requirements
     */
    public function getActive() {
        return $this->model->getActive();
    }

    /**
     * Get all inactive requirements
     *
     * @return array Array of inactive requirements
     */
    public function getInactive() {
        return $this->model->getInactive();
    }

    /**
     * Toggle active status
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        return $this->model->toggleActive($id);
    }

    /**
     * Reorder requirements within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of requirement IDs in new order
     * @return bool Success status
     */
    public function reorder($category, array $orderedIds) {
        return $this->model->reorderInCategory($category, $orderedIds);
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active requirements
     * @return array Array of category names
     */
    public function getCategories($activeOnly = true) {
        return $this->model->getCategories($activeOnly);
    }

    /**
     * Soft delete (set is_active to 0)
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->model->softDelete($id);
    }

    /**
     * Restore soft deleted requirement
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->model->restore($id);
    }

    /**
     * Create requirement with validation
     *
     * @param array $data Requirement data
     * @return int Created requirement ID
     * @throws Exception If validation fails
     */
    public function createRequirement(array $data) {
        // Validate required fields
        if (empty($data['category'])) {
            throw new Exception("Category is required");
        }

        if (empty($data['requirement'])) {
            throw new Exception("Requirement text is required");
        }

        // Set defaults
        if (!isset($data['order_number'])) {
            // Get next order number for category
            $data['order_number'] = $this->getNextOrderNumber($data['category']);
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        return $this->create($data);
    }

    /**
     * Update requirement with validation
     *
     * @param int $id Requirement ID
     * @param array $data Requirement data
     * @return bool Success status
     * @throws Exception If validation fails
     */
    public function updateRequirement($id, array $data) {
        // Check if requirement exists
        $requirement = $this->find($id);
        if (!$requirement) {
            throw new Exception("Requirement not found");
        }

        // Validate if category or requirement is being updated
        if (isset($data['category']) && empty($data['category'])) {
            throw new Exception("Category cannot be empty");
        }

        if (isset($data['requirement']) && empty($data['requirement'])) {
            throw new Exception("Requirement text cannot be empty");
        }

        return $this->update($id, $data);
    }

    /**
     * Get next order number for a category
     *
     * @param string $category Category name
     * @return int Next order number
     */
    private function getNextOrderNumber($category) {
        $sql = "SELECT COALESCE(MAX(order_number), 0) + 1 as next_order
                FROM {$this->table}
                WHERE category = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) $row['next_order'];
    }

    /**
     * Bulk create requirements
     *
     * @param array $requirements Array of requirement data arrays
     * @return array Array of created IDs
     */
    public function bulkCreate(array $requirements) {
        $ids = [];

        $this->beginTransaction();

        try {
            foreach ($requirements as $requirement) {
                $ids[] = $this->createRequirement($requirement);
            }

            $this->commit();
            return $ids;

        } catch (Exception $e) {
            $this->rollback();
            $this->logError("Bulk create failed", $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get requirements count by category
     *
     * @param bool $activeOnly Whether to count only active requirements
     * @return array Associative array of category => count
     */
    public function getCountByCategory($activeOnly = true) {
        $sql = "SELECT category, COUNT(*) as count
                FROM {$this->table}";

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " GROUP BY category ORDER BY category ASC";

        $result = $this->query($sql);
        $counts = [];

        while ($row = $result->fetch_assoc()) {
            $counts[$row['category']] = (int) $row['count'];
        }

        return $counts;
    }
}
