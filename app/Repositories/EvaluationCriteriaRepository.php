<?php
/**
 * Evaluation Criteria Repository
 * Phase 4 Week 2 Day 2
 *
 * Data access layer for evaluation criteria
 */

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../Models/EvaluationCriteria.php';

class EvaluationCriteriaRepository extends BaseRepository {
    protected $table = 'evaluation_criteria';
    protected $primaryKey = 'id';

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $model = new EvaluationCriteria($conn);
        parent::__construct($conn, $model);
    }

    /**
     * Get all criteria grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active criteria
     * @return array Array of criteria grouped by category
     */
    public function getGroupedByCategory($activeOnly = true) {
        return $this->model->getByCategory($activeOnly);
    }

    /**
     * Get criteria for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active criteria
     * @return array Array of criteria
     */
    public function getByCategory($category, $activeOnly = true) {
        return $this->model->getBySpecificCategory($category, $activeOnly);
    }

    /**
     * Get all active criteria
     *
     * @return array Array of active criteria
     */
    public function getActive() {
        return $this->model->getActive();
    }

    /**
     * Get total points available
     *
     * @param string|null $category Optional category filter
     * @param bool $activeOnly Whether to count only active criteria
     * @return int Total points
     */
    public function getTotalPoints($category = null, $activeOnly = true) {
        return $this->model->getTotalPoints($category, $activeOnly);
    }

    /**
     * Check if a criterion name already exists
     *
     * @param string $name Criterion name
     * @param int|null $excludeId ID to exclude from check (for updates)
     * @return bool True if name exists
     */
    public function nameExists($name, $excludeId = null) {
        return $this->model->nameExists($name, $excludeId);
    }

    /**
     * Toggle active status
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        return $this->model->toggleActive($id);
    }

    /**
     * Reorder criteria within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of criterion IDs in new order
     * @return bool Success status
     */
    public function reorder($category, array $orderedIds) {
        return $this->model->reorderInCategory($category, $orderedIds);
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active criteria
     * @return array Array of category names
     */
    public function getCategories($activeOnly = true) {
        return $this->model->getCategories($activeOnly);
    }

    /**
     * Update points for a criterion
     *
     * @param int $id Criterion ID
     * @param int $points New points value
     * @return bool Success status
     */
    public function updatePoints($id, $points) {
        return $this->model->updatePoints($id, $points);
    }

    /**
     * Get criteria as an associative array (name => description)
     * Useful for maintaining backward compatibility with old hardcoded format
     *
     * @param string|null $category Optional category filter
     * @param bool $activeOnly Whether to fetch only active criteria
     * @return array Associative array of name => description
     */
    public function getAsKeyValue($category = null, $activeOnly = true) {
        return $this->model->getAsKeyValue($category, $activeOnly);
    }

    /**
     * Soft delete (set is_active to 0)
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->model->softDelete($id);
    }

    /**
     * Restore soft deleted criterion
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->model->restore($id);
    }

    /**
     * Create criterion with validation
     *
     * @param array $data Criterion data
     * @return int Created criterion ID
     * @throws Exception If validation fails
     */
    public function createCriterion(array $data) {
        // Validate required fields
        if (empty($data['name'])) {
            throw new Exception("Criterion name is required");
        }

        // Check if name already exists
        if ($this->nameExists($data['name'])) {
            throw new Exception("A criterion with this name already exists");
        }

        // Set defaults
        if (!isset($data['points'])) {
            $data['points'] = 0;
        }

        if (!isset($data['order_number'])) {
            // Get next order number for category
            $category = $data['category'] ?? 'Uncategorized';
            $data['order_number'] = $this->getNextOrderNumber($category);
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        return $this->create($data);
    }

    /**
     * Update criterion with validation
     *
     * @param int $id Criterion ID
     * @param array $data Criterion data
     * @return bool Success status
     * @throws Exception If validation fails
     */
    public function updateCriterion($id, array $data) {
        // Check if criterion exists
        $criterion = $this->find($id);
        if (!$criterion) {
            throw new Exception("Criterion not found");
        }

        // Validate name if being updated
        if (isset($data['name'])) {
            if (empty($data['name'])) {
                throw new Exception("Criterion name cannot be empty");
            }

            // Check if new name already exists (excluding this criterion)
            if ($this->nameExists($data['name'], $id)) {
                throw new Exception("A criterion with this name already exists");
            }
        }

        // Validate points if being updated
        if (isset($data['points']) && $data['points'] < 0) {
            throw new Exception("Points cannot be negative");
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
                WHERE category = ? OR (category IS NULL AND ? IS NULL)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $category, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) $row['next_order'];
    }

    /**
     * Bulk create criteria
     *
     * @param array $criteria Array of criterion data arrays
     * @return array Array of created IDs
     */
    public function bulkCreate(array $criteria) {
        $ids = [];

        $this->beginTransaction();

        try {
            foreach ($criteria as $criterion) {
                $ids[] = $this->createCriterion($criterion);
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
     * Get criteria count by category
     *
     * @param bool $activeOnly Whether to count only active criteria
     * @return array Associative array of category => count
     */
    public function getCountByCategory($activeOnly = true) {
        $sql = "SELECT category, COUNT(*) as count
                FROM {$this->table}
                WHERE category IS NOT NULL";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " GROUP BY category ORDER BY category ASC";

        $result = $this->query($sql);
        $counts = [];

        while ($row = $result->fetch_assoc()) {
            $counts[$row['category']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Normalize points distribution
     * Adjusts all criterion points to total a specific value
     *
     * @param int $totalPoints Target total points (default 100)
     * @param string|null $category Optional category filter
     * @return bool Success status
     */
    public function normalizePoints($totalPoints = 100, $category = null) {
        $criteria = $this->getByCategory($category, true);

        if (empty($criteria)) {
            return false;
        }

        $currentTotal = array_sum(array_column($criteria, 'points'));

        if ($currentTotal == 0) {
            // Equal distribution
            $pointsEach = floor($totalPoints / count($criteria));
            $remainder = $totalPoints % count($criteria);

            $this->beginTransaction();

            try {
                foreach ($criteria as $index => $criterion) {
                    $points = $pointsEach + ($index < $remainder ? 1 : 0);
                    $this->updatePoints($criterion['id'], $points);
                }

                $this->commit();
                return true;

            } catch (Exception $e) {
                $this->rollback();
                $this->logError("Failed to normalize points", $e->getMessage());
                return false;
            }
        } else {
            // Proportional distribution
            $this->beginTransaction();

            try {
                foreach ($criteria as $criterion) {
                    $proportion = $criterion['points'] / $currentTotal;
                    $newPoints = round($proportion * $totalPoints);
                    $this->updatePoints($criterion['id'], $newPoints);
                }

                $this->commit();
                return true;

            } catch (Exception $e) {
                $this->rollback();
                $this->logError("Failed to normalize points", $e->getMessage());
                return false;
            }
        }
    }
}
