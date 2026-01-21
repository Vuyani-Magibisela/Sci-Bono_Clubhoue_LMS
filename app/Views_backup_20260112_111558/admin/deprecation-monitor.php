<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deprecated File Monitor - Sci-Bono LMS</title>
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .deprecation-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .dashboard-header h1 {
            margin: 0;
            color: #333;
        }

        .time-range-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .time-range-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-trend {
            font-size: 12px;
            color: #28a745;
            margin-top: 5px;
        }

        .stat-trend.down {
            color: #dc3545;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
        }

        .files-table {
            width: 100%;
            border-collapse: collapse;
        }

        .files-table th,
        .files-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .files-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .files-table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .chart-container {
            margin-top: 20px;
            height: 300px;
        }

        .recommendations-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .recommendation-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .recommendation-item.warning {
            border-left-color: #ffc107;
        }

        .recommendation-item.danger {
            border-left-color: #dc3545;
        }

        .recommendation-item h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .recommendation-item p {
            margin: 0;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/admin-header.php'; ?>

    <div class="deprecation-dashboard">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-exclamation-triangle"></i> Deprecated File Monitor</h1>
            <div class="time-range-selector">
                <label for="days">Time Range:</label>
                <select id="days" name="days" onchange="changeDays(this.value)">
                    <option value="7" <?= $days == 7 ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="60" <?= $days == 60 ? 'selected' : '' ?>>Last 60 Days</option>
                    <option value="90" <?= $days == 90 ? 'selected' : '' ?>>Last 90 Days</option>
                </select>
                <a href="/Sci-Bono_Clubhoue_LMS/admin/deprecation-monitor/export?days=<?= $days ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Hits</h3>
                <div class="stat-value"><?= number_format($stats['total_hits']) ?></div>
                <div class="stat-trend <?= $stats['total_hits'] > 0 ? '' : 'down' ?>">
                    Last <?= $days ?> days
                </div>
            </div>

            <div class="stat-card">
                <h3>Active Files</h3>
                <div class="stat-value">
                    <?php
                        $activeFiles = 0;
                        foreach ($stats['files'] as $file) {
                            if ($file['hit_count'] > 0) $activeFiles++;
                        }
                        echo $activeFiles;
                    ?>
                </div>
                <div class="stat-trend">
                    Out of <?= count($stats['files']) ?> total
                </div>
            </div>

            <div class="stat-card">
                <h3>Safe to Remove</h3>
                <div class="stat-value">
                    <?php
                        $safeToRemove = 0;
                        foreach ($stats['files'] as $file) {
                            if ($file['hit_count'] === 0) $safeToRemove++;
                        }
                        echo $safeToRemove;
                    ?>
                </div>
                <div class="stat-trend down">
                    <?= $safeToRemove > 0 ? 'Zero usage detected' : 'All files in use' ?>
                </div>
            </div>

            <div class="stat-card">
                <h3>Log Status</h3>
                <div class="stat-value">
                    <?php if (isset($stats['error'])): ?>
                        <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                    <?php else: ?>
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <?php endif; ?>
                </div>
                <div class="stat-trend" style="font-size: 10px;">
                    <?= isset($stats['error']) ? $stats['error'] : 'Monitoring active' ?>
                </div>
            </div>
        </div>

        <?php if (isset($stats['error'])): ?>
            <!-- Error State -->
            <div class="section">
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
                    <h3>Unable to Access Error Logs</h3>
                    <p><?= htmlspecialchars($stats['error']) ?></p>
                    <p style="margin-top: 10px; font-size: 14px;">
                        The monitoring dashboard requires read access to PHP error logs.<br>
                        Please check file permissions or configure error logging.
                    </p>
                </div>
            </div>
        <?php else: ?>

            <!-- Deprecated Files Table -->
            <div class="section">
                <h2>Deprecated Files Usage</h2>
                <?php if (count($stats['files']) > 0): ?>
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Hit Count</th>
                                <th>Last Accessed</th>
                                <th>Unique URLs</th>
                                <th>Status</th>
                                <th>Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['files'] as $file): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($file['name']) ?></strong>
                                    </td>
                                    <td><?= number_format($file['hit_count']) ?></td>
                                    <td>
                                        <?php if ($file['last_accessed']): ?>
                                            <?= date('M d, Y H:i', $file['last_accessed']) ?>
                                        <?php else: ?>
                                            <span style="color: #999;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $file['unique_url_count'] ?></td>
                                    <td>
                                        <?php if ($file['hit_count'] === 0): ?>
                                            <span class="badge badge-success">Safe to Remove</span>
                                        <?php elseif ($file['hit_count'] < 10): ?>
                                            <span class="badge badge-warning">Low Usage</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $maxHits = max(array_column($stats['files'], 'hit_count'));
                                            $percentage = $maxHits > 0 ? ($file['hit_count'] / $maxHits) * 100 : 0;
                                        ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>No Deprecated Files Found</h3>
                        <p>All deprecated files have been removed or are not configured.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recommendations -->
            <div class="section">
                <h2>Recommendations</h2>
                <?php if (count($recommendations) > 0): ?>
                    <ul class="recommendations-list">
                        <?php foreach ($recommendations as $rec): ?>
                            <li class="recommendation-item <?= $rec['status'] === 'safe_to_remove' ? '' : ($rec['status'] === 'low_usage' ? 'warning' : 'danger') ?>">
                                <h4>
                                    <?= htmlspecialchars($rec['file']) ?>
                                    <?php if ($rec['priority'] === 'high'): ?>
                                        <span class="badge badge-danger">High Priority</span>
                                    <?php elseif ($rec['priority'] === 'medium'): ?>
                                        <span class="badge badge-warning">Medium Priority</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Low Priority</span>
                                    <?php endif; ?>
                                </h4>
                                <p><?= htmlspecialchars($rec['message']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <h3>No Recommendations</h3>
                        <p>All deprecated files are being monitored.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <?php if (count($stats['recent_hits']) > 0): ?>
                <div class="section">
                    <h2>Recent Activity (Last 100 Hits)</h2>
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>File</th>
                                <th>URL</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice(array_reverse($stats['recent_hits']), 0, 20) as $hit): ?>
                                <tr>
                                    <td><?= htmlspecialchars($hit['date']) ?></td>
                                    <td><strong><?= htmlspecialchars($hit['file']) ?></strong></td>
                                    <td style="font-size: 12px;"><?= htmlspecialchars($hit['url']) ?></td>
                                    <td><?= htmlspecialchars($hit['ip']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($stats['recent_hits']) > 20): ?>
                        <p style="text-align: center; margin-top: 10px; color: #666;">
                            Showing 20 of <?= count($stats['recent_hits']) ?> recent hits
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        function changeDays(days) {
            window.location.href = '/Sci-Bono_Clubhoue_LMS/admin/deprecation-monitor?days=' + days;
        }
    </script>
</body>
</html>
