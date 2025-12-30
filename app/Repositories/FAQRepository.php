<?php
/**
 * FAQ Repository
 * Phase 4 Week 2 Day 2
 *
 * Data access layer for FAQs
 */

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../Models/FAQ.php';

class FAQRepository extends BaseRepository {
    protected $table = 'faqs';
    protected $primaryKey = 'id';

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $model = new FAQ($conn);
        parent::__construct($conn, $model);
    }

    /**
     * Get all FAQs grouped by category
     *
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs grouped by category
     */
    public function getGroupedByCategory($activeOnly = true) {
        return $this->model->getByCategory($activeOnly);
    }

    /**
     * Get FAQs for a specific category
     *
     * @param string $category Category name
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs
     */
    public function getByCategory($category, $activeOnly = true) {
        return $this->model->getBySpecificCategory($category, $activeOnly);
    }

    /**
     * Get all active FAQs
     *
     * @return array Array of active FAQs
     */
    public function getActive() {
        return $this->model->getActive();
    }

    /**
     * Search FAQs by keyword (uses FULLTEXT index)
     *
     * @param string $keyword Search keyword
     * @param bool $activeOnly Whether to search only active FAQs
     * @return array Array of matching FAQs
     */
    public function search($keyword, $activeOnly = true) {
        return $this->model->search($keyword, $activeOnly);
    }

    /**
     * Simple search (fallback if FULLTEXT not available or for partial matches)
     *
     * @param string $keyword Search keyword
     * @param bool $activeOnly Whether to search only active FAQs
     * @return array Array of matching FAQs
     */
    public function simpleSearch($keyword, $activeOnly = true) {
        return $this->model->simpleSearch($keyword, $activeOnly);
    }

    /**
     * Get all unique categories
     *
     * @param bool $activeOnly Whether to fetch only categories with active FAQs
     * @return array Array of category names
     */
    public function getCategories($activeOnly = true) {
        return $this->model->getCategories($activeOnly);
    }

    /**
     * Toggle active status
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function toggleActive($id) {
        return $this->model->toggleActive($id);
    }

    /**
     * Reorder FAQs within a category
     *
     * @param string $category Category name
     * @param array $orderedIds Array of FAQ IDs in new order
     * @return bool Success status
     */
    public function reorder($category, array $orderedIds) {
        return $this->model->reorderInCategory($category, $orderedIds);
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
        return $this->model->getLegacyFormat($category, $activeOnly);
    }

    /**
     * Get FAQ count by category
     *
     * @param bool $activeOnly Whether to count only active FAQs
     * @return array Associative array of category => count
     */
    public function getCountByCategory($activeOnly = true) {
        return $this->model->getCountByCategory($activeOnly);
    }

    /**
     * Soft delete (set is_active to 0)
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function softDelete($id) {
        return $this->model->softDelete($id);
    }

    /**
     * Restore soft deleted FAQ
     *
     * @param int $id FAQ ID
     * @return bool Success status
     */
    public function restore($id) {
        return $this->model->restore($id);
    }

    /**
     * Create FAQ with validation
     *
     * @param array $data FAQ data
     * @return int Created FAQ ID
     * @throws Exception If validation fails
     */
    public function createFAQ(array $data) {
        // Validate required fields
        if (empty($data['category'])) {
            throw new Exception("Category is required");
        }

        if (empty($data['question'])) {
            throw new Exception("Question is required");
        }

        if (empty($data['answer'])) {
            throw new Exception("Answer is required");
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
     * Update FAQ with validation
     *
     * @param int $id FAQ ID
     * @param array $data FAQ data
     * @return bool Success status
     * @throws Exception If validation fails
     */
    public function updateFAQ($id, array $data) {
        // Check if FAQ exists
        $faq = $this->find($id);
        if (!$faq) {
            throw new Exception("FAQ not found");
        }

        // Validate if fields are being updated
        if (isset($data['category']) && empty($data['category'])) {
            throw new Exception("Category cannot be empty");
        }

        if (isset($data['question']) && empty($data['question'])) {
            throw new Exception("Question cannot be empty");
        }

        if (isset($data['answer']) && empty($data['answer'])) {
            throw new Exception("Answer cannot be empty");
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
     * Bulk create FAQs
     *
     * @param array $faqs Array of FAQ data arrays
     * @return array Array of created IDs
     */
    public function bulkCreate(array $faqs) {
        $ids = [];

        $this->beginTransaction();

        try {
            foreach ($faqs as $faq) {
                $ids[] = $this->createFAQ($faq);
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
     * Get most popular FAQs
     * Can be extended later to track view counts
     *
     * @param int $limit Number of FAQs to return
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs
     */
    public function getPopular($limit = 10, $activeOnly = true) {
        // For now, just return the first N FAQs
        // In future, can track view_count column
        $conditions = [];

        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        return $this->findAll($conditions, 'order_number ASC', $limit);
    }

    /**
     * Get FAQs for a specific program
     * This can be extended to support program-specific FAQs
     *
     * @param int $programId Program ID
     * @param bool $activeOnly Whether to fetch only active FAQs
     * @return array Array of FAQs
     */
    public function getForProgram($programId, $activeOnly = true) {
        // For now, return all general FAQs
        // In future, can add program_id column to faqs table
        return $this->getActive();
    }

    /**
     * Search FAQs with highlighted results
     * Returns FAQs with search term highlighted in question/answer
     *
     * @param string $keyword Search keyword
     * @param bool $activeOnly Whether to search only active FAQs
     * @return array Array of FAQs with highlighted content
     */
    public function searchWithHighlight($keyword, $activeOnly = true) {
        $results = $this->search($keyword, $activeOnly);

        // Add highlighting
        foreach ($results as &$result) {
            $result['question_highlighted'] = $this->highlightKeyword($result['question'], $keyword);
            $result['answer_highlighted'] = $this->highlightKeyword($result['answer'], $keyword);
        }

        return $results;
    }

    /**
     * Highlight keyword in text
     *
     * @param string $text Text to highlight
     * @param string $keyword Keyword to highlight
     * @return string Highlighted text
     */
    private function highlightKeyword($text, $keyword) {
        if (empty($keyword)) {
            return $text;
        }

        return preg_replace(
            '/(' . preg_quote($keyword, '/') . ')/i',
            '<mark>$1</mark>',
            $text
        );
    }
}
