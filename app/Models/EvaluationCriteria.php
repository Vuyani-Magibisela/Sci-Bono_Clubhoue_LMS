<?php
/**
 * Evaluation Criteria Model
 * Phase 4 Week 2 Day 2
 *
 * Handles evaluation criteria for projects and programs
 */

require_once __DIR__ . '/BaseModel.php';

class EvaluationCriteria extends BaseModel {
    protected $table = 'evaluation_criteria';
    protected $primaryKey = 'id';

    /**
     * Fields that are mass assignable
     */
    protected $fillable = [
        'name',
        'description',
        'points',
        'category',
        'order_number',
        'is_active'
    ];

    /**
     * Fields that should be guarded from mass assignment
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Enable automatic timestamp management
     */
    protected $timestamps = true;

    /**
     * Get all criteria grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active criteria
     * @return array Array of criteria grouped by category
     */
    public function getByCategory($activeOnly = true) {
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        $criteria = $this->findAll($conditions, 'category ASC, order_number ASC');

        // Group by category
        $grouped = [];
        foreach ($criteria as $criterion) {
            $category = $criterion['category'] ?? 'Uncategorized';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $criterion;
        }

        return $grouped;
    }

    /**
     * Get criteria for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active criteria
     * @return array Array of criteria
     */
    public function getBySpecificCategory($category, $activeOnly = true) {
        $conditions = ['category' => $category];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        return $this->findAll($conditions, 'order_number ASC');
    }

    /**
     * Get all active criteria
     *
     * @return array Array of active criteria
     */
    public function getActive() {
        return $this->findAll(['is_active' => 1], 'category ASC, order_number ASC');
    }

    /**
     * Get total points available
     *
     * @param string|null $category Optional category filter
     * @param bool $activeOnly Whether to count only active criteria
     * @return int Total points
     */
    public function getTotalPoints($category = null, $activeOnly = true) {
        $sql = "SELECT SUM(points) as total FROM {$this->table} WHERE 1=1";
        $params = [];
        $types = "";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        if ($category !== null) {
            $sql .= " AND category = ?";
            $params[] = $category;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Check if a criterion name already exists
     *
     * @param string $name Criterion name
     * @param int|null $excludeId ID to exclude from check (for updates)
     * @return bool True if name exists
     */
    public function nameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?";
        $params = [$name];
        $types = "s";

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['count'] > 0;
    }

    /**
     * Toggle active status
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        $criterion = $this->find($id);

        if (!$criterion) {
            return false;
        }

        $newStatus = $criterion['is_active'] ? 0 : 1;

        return $this->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Reorder criteria within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of criterion IDs in new order
     * @return bool Success status
     */
    public function reorderInCategory($category, array $orderedIds) {
        $this->beginTransaction();

        try {
            foreach ($orderedIds as $index => $id) {
                $orderNumber = $index + 1;
                $this->update($id, ['order_number' => $orderNumber]);
            }

            $this->commit();
            return true;

        } catch (Exception $e) {
            $this->rollback();
            $this->logError("Failed to reorder criteria", $e->getMessage());
            return false;
        }
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active criteria
     * @return array Array of category names
     */
    public function getCategories($activeOnly = true) {
        $sql = "SELECT DISTINCT category FROM {$this->table} WHERE category IS NOT NULL";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY category ASC";

        $result = $this->query($sql);
        $categories = [];

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }

        return $categories;
    }

    /**
     * Update points for a criterion
     *
     * @param int $id Criterion ID
     * @param int $points New points value
     * @return bool Success status
     */
    public function updatePoints($id, $points) {
        if ($points < 0) {
            throw new Exception("Points cannot be negative");
        }

        return $this->update($id, ['points' => $points]);
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
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        if ($category !== null) {
            $conditions['category'] = $category;
        }

        $criteria = $this->findAll($conditions, 'order_number ASC');

        $keyValue = [];
        foreach ($criteria as $criterion) {
            $keyValue[$criterion['name']] = $criterion['description'];
        }

        return $keyValue;
    }

    /**
     * Soft delete (set is_active to 0)
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Restore soft deleted criterion
     *
     * @param int $id Criterion ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->update($id, ['is_active' => 1]);
    }
}
