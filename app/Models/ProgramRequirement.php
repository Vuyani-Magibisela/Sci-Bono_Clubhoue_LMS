<?php
/**
 * Program Requirement Model
 * Phase 4 Week 2 Day 2
 *
 * Handles program and project requirements that were previously hardcoded
 */

require_once __DIR__ . '/BaseModel.php';

class ProgramRequirement extends BaseModel {
    protected $table = 'program_requirements';
    protected $primaryKey = 'id';

    /**
     * Fields that are mass assignable
     */
    protected $fillable = [
        'category',
        'requirement',
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
     * Get all requirements grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active requirements
     * @return array Array of requirements grouped by category
     */
    public function getByCategory($activeOnly = true) {
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        $requirements = $this->findAll($conditions, 'category ASC, order_number ASC');

        // Group by category
        $grouped = [];
        foreach ($requirements as $requirement) {
            $category = $requirement['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $requirement;
        }

        return $grouped;
    }

    /**
     * Get requirements for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active requirements
     * @return array Array of requirements
     */
    public function getBySpecificCategory($category, $activeOnly = true) {
        $conditions = ['category' => $category];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        return $this->findAll($conditions, 'order_number ASC');
    }

    /**
     * Get all active requirements
     *
     * @return array Array of active requirements
     */
    public function getActive() {
        return $this->findAll(['is_active' => 1], 'category ASC, order_number ASC');
    }

    /**
     * Get all inactive requirements
     *
     * @return array Array of inactive requirements
     */
    public function getInactive() {
        return $this->findAll(['is_active' => 0], 'category ASC, order_number ASC');
    }

    /**
     * Toggle active status
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        $requirement = $this->find($id);

        if (!$requirement) {
            return false;
        }

        $newStatus = $requirement['is_active'] ? 0 : 1;

        return $this->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Reorder requirements within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of requirement IDs in new order
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
            $this->logError("Failed to reorder requirements", $e->getMessage());
            return false;
        }
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active requirements
     * @return array Array of category names
     */
    public function getCategories($activeOnly = true) {
        $sql = "SELECT DISTINCT category FROM {$this->table}";

        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
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
     * Soft delete (set is_active to 0)
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Restore soft deleted requirement
     *
     * @param int $id Requirement ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->update($id, ['is_active' => 1]);
    }
}
