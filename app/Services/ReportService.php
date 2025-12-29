<?php
/**
 * Report Service - Business logic for clubhouse reports and analytics
 * Phase 3: Week 6-7 Implementation
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../../core/SecureFileUploader.php';

class ReportService extends BaseService {
    private $fileUploader;

    public function __construct($conn = null) {
        parent::__construct($conn);
        $this->fileUploader = new SecureFileUploader();
    }

    /**
     * Get all clubhouse reports
     *
     * @param array $filters Optional filters (date_from, date_to, program_name)
     * @return array List of reports
     */
    public function getAllReports($filters = []) {
        try {
            $this->logAction('get_all_reports', ['filters' => $filters]);

            $sql = "SELECT * FROM clubhouse_reports WHERE 1=1";
            $params = [];
            $types = "";

            // Apply filters
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }

            if (!empty($filters['program_name'])) {
                $sql .= " AND program_name LIKE ?";
                $params[] = '%' . $filters['program_name'] . '%';
                $types .= "s";
            }

            $sql .= " ORDER BY created_at DESC";

            if (!empty($params)) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
            }

            $reports = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reports[] = $row;
                }
            }

            return $reports;

        } catch (Exception $e) {
            $this->logger->error("Failed to get reports", ['filters' => $filters]);
            return [];
        }
    }

    /**
     * Get report by ID
     *
     * @param int $reportId Report ID
     * @return array|null Report data or null if not found
     */
    public function getReportById($reportId) {
        try {
            $this->logAction('get_report_by_id', ['report_id' => $reportId]);

            $sql = "SELECT * FROM clubhouse_reports WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $reportId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error("Failed to get report", ['report_id' => $reportId]);
            return null;
        }
    }

    /**
     * Create new report entry
     *
     * @param array $data Report data
     * @param array|null $imageFile Uploaded image file
     * @return int|bool Report ID on success, false on failure
     */
    public function createReport($data, $imageFile = null) {
        try {
            $this->logAction('create_report_attempt', ['program' => $data['program_name'] ?? 'unknown']);

            // Validate required fields
            $required = ['program_name', 'participants', 'narrative', 'challenges'];
            $this->validateRequired($data, $required);

            // Sanitize data
            $programName = htmlspecialchars(trim($data['program_name']), ENT_QUOTES, 'UTF-8');
            $participants = (int)$data['participants'];
            $narrative = htmlspecialchars(trim($data['narrative']), ENT_QUOTES, 'UTF-8');
            $challenges = htmlspecialchars(trim($data['challenges']), ENT_QUOTES, 'UTF-8');

            // Validate participants count
            if ($participants < 0 || $participants > 10000) {
                throw new Exception("Participants count must be between 0 and 10,000");
            }

            // Handle image upload
            $imagePath = "";
            if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->fileUploader->upload($imageFile);

                if (!$uploadResult['success']) {
                    throw new Exception("Image upload failed: " . ($uploadResult['error'] ?? 'Unknown error'));
                }

                $imagePath = $uploadResult['path'];
            }

            // Insert report
            $sql = "INSERT INTO clubhouse_reports (program_name, participants, narrative, challenges, image_path, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sisss", $programName, $participants, $narrative, $challenges, $imagePath);
            $result = $stmt->execute();

            if ($result) {
                $reportId = $stmt->insert_id;

                $this->logAction('report_created_success', [
                    'report_id' => $reportId,
                    'program_name' => $programName
                ]);

                return $reportId;
            }

            throw new Exception("Failed to create report");

        } catch (Exception $e) {
            $this->handleError("Report creation failed: " . $e->getMessage(), ['data' => $data]);
        }
    }

    /**
     * Create multiple reports (batch submission)
     *
     * @param array $reportsData Array of report data
     * @param array $imageFiles Array of uploaded files
     * @return array Result with success count and failures
     */
    public function createBatchReports($reportsData, $imageFiles = []) {
        try {
            $this->logAction('create_batch_reports', ['count' => count($reportsData)]);

            $successCount = 0;
            $failures = [];

            foreach ($reportsData as $index => $reportData) {
                $imageFile = isset($imageFiles[$index]) ? $imageFiles[$index] : null;

                try {
                    $reportId = $this->createReport($reportData, $imageFile);

                    if ($reportId) {
                        $successCount++;
                    } else {
                        $failures[] = [
                            'index' => $index,
                            'program' => $reportData['program_name'] ?? 'unknown',
                            'error' => 'Failed to create report'
                        ];
                    }

                } catch (Exception $e) {
                    $failures[] = [
                        'index' => $index,
                        'program' => $reportData['program_name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success_count' => $successCount,
                'total_count' => count($reportsData),
                'failures' => $failures
            ];

        } catch (Exception $e) {
            $this->logger->error("Batch report creation failed", ['error' => $e->getMessage()]);
            return [
                'success_count' => 0,
                'total_count' => count($reportsData),
                'failures' => [['error' => $e->getMessage()]]
            ];
        }
    }

    /**
     * Update existing report
     *
     * @param int $reportId Report ID
     * @param array $data Updated report data
     * @return bool Success status
     */
    public function updateReport($reportId, $data) {
        try {
            $this->logAction('update_report_attempt', ['report_id' => $reportId]);

            // Check if report exists
            $existing = $this->getReportById($reportId);
            if (!$existing) {
                throw new Exception("Report not found");
            }

            // Sanitize data
            $updates = [];
            $params = [];
            $types = "";

            if (isset($data['program_name'])) {
                $updates[] = "program_name = ?";
                $params[] = htmlspecialchars(trim($data['program_name']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (isset($data['participants'])) {
                $participants = (int)$data['participants'];
                if ($participants < 0 || $participants > 10000) {
                    throw new Exception("Participants count must be between 0 and 10,000");
                }
                $updates[] = "participants = ?";
                $params[] = $participants;
                $types .= "i";
            }

            if (isset($data['narrative'])) {
                $updates[] = "narrative = ?";
                $params[] = htmlspecialchars(trim($data['narrative']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (isset($data['challenges'])) {
                $updates[] = "challenges = ?";
                $params[] = htmlspecialchars(trim($data['challenges']), ENT_QUOTES, 'UTF-8');
                $types .= "s";
            }

            if (empty($updates)) {
                return true; // Nothing to update
            }

            // Add updated_at
            $updates[] = "updated_at = NOW()";

            $sql = "UPDATE clubhouse_reports SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $reportId;
            $types .= "i";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $result = $stmt->execute();

            if ($result) {
                $this->logAction('report_updated_success', ['report_id' => $reportId]);
                return true;
            }

            throw new Exception("Failed to update report");

        } catch (Exception $e) {
            $this->handleError("Report update failed: " . $e->getMessage(), ['report_id' => $reportId]);
        }
    }

    /**
     * Delete report
     *
     * @param int $reportId Report ID
     * @return bool Success status
     */
    public function deleteReport($reportId) {
        try {
            $this->logAction('delete_report_attempt', ['report_id' => $reportId]);

            // Get report to delete image file
            $report = $this->getReportById($reportId);
            if (!$report) {
                throw new Exception("Report not found");
            }

            // Delete image file if exists
            if (!empty($report['image_path']) && file_exists($report['image_path'])) {
                @unlink($report['image_path']);
            }

            // Delete report
            $sql = "DELETE FROM clubhouse_reports WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $reportId);
            $result = $stmt->execute();

            if ($result) {
                $this->logAction('report_deleted_success', ['report_id' => $reportId]);
                return true;
            }

            throw new Exception("Failed to delete report");

        } catch (Exception $e) {
            $this->handleError("Report deletion failed: " . $e->getMessage(), ['report_id' => $reportId]);
        }
    }

    /**
     * Get report statistics
     *
     * @param array $filters Optional filters (date_from, date_to)
     * @return array Statistics data
     */
    public function getReportStatistics($filters = []) {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($filters['date_from'])) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }

            if (!empty($filters['date_to'])) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }

            $sql = "SELECT
                        COUNT(*) as total_reports,
                        SUM(participants) as total_participants,
                        AVG(participants) as avg_participants_per_program,
                        COUNT(DISTINCT program_name) as unique_programs
                    FROM clubhouse_reports
                    {$whereClause}";

            if (!empty($params)) {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
            }

            if ($result && $result->num_rows > 0) {
                $stats = $result->fetch_assoc();

                // Round average
                $stats['avg_participants_per_program'] = round($stats['avg_participants_per_program'], 2);

                return $stats;
            }

            return [
                'total_reports' => 0,
                'total_participants' => 0,
                'avg_participants_per_program' => 0,
                'unique_programs' => 0
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get report statistics", ['filters' => $filters]);
            return [
                'total_reports' => 0,
                'total_participants' => 0,
                'avg_participants_per_program' => 0,
                'unique_programs' => 0
            ];
        }
    }

    /**
     * Get reports grouped by program
     *
     * @return array Programs with report counts
     */
    public function getReportsByProgram() {
        try {
            $sql = "SELECT
                        program_name,
                        COUNT(*) as report_count,
                        SUM(participants) as total_participants,
                        MAX(created_at) as last_report_date
                    FROM clubhouse_reports
                    GROUP BY program_name
                    ORDER BY report_count DESC";

            $result = $this->conn->query($sql);

            $programs = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $programs[] = $row;
                }
            }

            return $programs;

        } catch (Exception $e) {
            $this->logger->error("Failed to get reports by program");
            return [];
        }
    }

    /**
     * Get monthly report summary
     *
     * @param int $year Year
     * @param int $month Month (1-12)
     * @return array Monthly summary
     */
    public function getMonthlyReportSummary($year, $month) {
        try {
            $startDate = sprintf("%04d-%02d-01", $year, $month);
            $endDate = date("Y-m-t", strtotime($startDate));

            $sql = "SELECT
                        COUNT(*) as report_count,
                        SUM(participants) as total_participants,
                        COUNT(DISTINCT program_name) as programs_count
                    FROM clubhouse_reports
                    WHERE created_at >= ? AND created_at <= ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $summary = $result->fetch_assoc();
                $summary['month'] = $month;
                $summary['year'] = $year;
                $summary['period'] = date('F Y', strtotime($startDate));
                return $summary;
            }

            return [
                'report_count' => 0,
                'total_participants' => 0,
                'programs_count' => 0,
                'month' => $month,
                'year' => $year,
                'period' => date('F Y', strtotime($startDate))
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to get monthly summary", ['year' => $year, 'month' => $month]);
            return [
                'report_count' => 0,
                'total_participants' => 0,
                'programs_count' => 0,
                'month' => $month,
                'year' => $year
            ];
        }
    }

    /**
     * Search reports
     *
     * @param string $query Search query
     * @return array Matching reports
     */
    public function searchReports($query) {
        try {
            $searchPattern = '%' . $query . '%';

            $sql = "SELECT * FROM clubhouse_reports
                    WHERE program_name LIKE ?
                       OR narrative LIKE ?
                       OR challenges LIKE ?
                    ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();

            $reports = [];
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }

            return $reports;

        } catch (Exception $e) {
            $this->logger->error("Report search failed", ['query' => $query]);
            return [];
        }
    }
}
