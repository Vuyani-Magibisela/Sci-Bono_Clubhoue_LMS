<?php
/**
 * FAQ Model
 * Phase 4 Week 2 Day 2
 *
 * Handles frequently asked questions and answers
 */

require_once __DIR__ . '/BaseModel.php';

class FAQ extends BaseModel {
    protected $table = 'faqs';
    protected $primaryKey = 'id';

    /**
     * Fields that are mass assignable
     */
    protected $fillable = [
        'category',
        'question',
        'answer',
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
     * Get all FAQs grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs grouped by category
     */
    public function getByCategory($activeOnly = true) {
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        $faqs = $this->findAll($conditions, 'category ASC, order_number ASC');

        // Group by category
        $grouped = [];
        foreach ($faqs as $faq) {
            $category = $faq['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $faq;
        }

        return $grouped;
    }

    /**
     * Get FAQs for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs
     */
    public function getBySpecificCategory($category, $activeOnly = true) {
        $conditions = ['category' => $category];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        return $this->findAll($conditions, 'order_number ASC');
    }

    /**
     * Get all active FAQs
     *
     * @return array Array of active FAQs
     */
    public function getActive() {
        return $this->findAll(['is_active' => 1], 'category ASC, order_number ASC');
    }

    /**
     * Search FAQs by keyword (uses FULLTEXT index)
     *
     * @param string $keyword Search keyword
     * @param bool $activeOnly Whether to search only active FAQs
     * @return array Array of matching FAQs
     */
    public function search($keyword, $activeOnly = true) {
        if (empty($keyword)) {
            return $this->getActive();
        }

        $sql = "SELECT *, MATCH(question, answer) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM {$this->table}
                WHERE MATCH(question, answer) AGAINST(? IN NATURAL LANGUAGE MODE)";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY relevance DESC, category ASC, order_number ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Simple search (fallback if FULLTEXT not available or for partial matches)
     *
     * @param string $keyword Search keyword
     * @param bool $activeOnly Whether to search only active FAQs
     * @return array Array of matching FAQs
     */
    public function simpleSearch($keyword, $activeOnly = true) {
        if (empty($keyword)) {
            return $this->getActive();
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE (question LIKE ? OR answer LIKE ?)";

        $params = ["%{$keyword}%", "%{$keyword}%"];
        $types = "ss";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY category ASC, order_number ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active FAQs
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
     * Toggle active status
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        $faq = $this->find($id);

        if (!$faq) {
            return false;
        }

        $newStatus = $faq['is_active'] ? 0 : 1;

        return $this->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Reorder FAQs within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of FAQ IDs in new order
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
            $this->logError("Failed to reorder FAQs", $e->getMessage());
            return false;
        }
    }

    /**
     * Get FAQs in format expected by old views
     * Returns array with 'question' and 'answer' keys
     *
     * @param string|null $category Optional category filter
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs in legacy format
     */
    public function getLegacyFormat($category = null, $activeOnly = true) {
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        if ($category !== null) {
            $conditions['category'] = $category;
        }

        $faqs = $this->findAll($conditions, 'order_number ASC');

        // Convert to legacy format (just question and answer keys)
        $legacyFormat = [];
        foreach ($faqs as $faq) {
            $legacyFormat[] = [
                'question' => $faq['question'],
                'answer' => $faq['answer']
            ];
        }

        return $legacyFormat;
    }

    /**
     * Get FAQ count by category
     *
     * @param bool $activeOnly Whether to count only active FAQs
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

    /**
     * Soft delete (set is_active to 0)
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Restore soft deleted FAQ
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->update($id, ['is_active' => 1]);
    }
}
