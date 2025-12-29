<?php
/**
 * Mentor Attendance Dashboard View
 *
 * Displays and manages today's attendance records for mentors/admins
 *
 * Phase 3 Week 4: Modern Routing System - View Implementation
 * Created: November 26, 2025
 *
 * Data available from Mentor\AttendanceController@index:
 * - $attendanceData: array with date, signed_in, signed_out, counts
 * - $stats: additional statistics
 * - $csrfToken: CSRF protection token
 * - $currentDate: current date
 * - $mentorName: logged-in mentor's name
 */

require __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/CSRF.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo CSRF::metaTag(); ?>
    <title>Attendance Dashboard - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="<?php echo BASE_URL?>/public/assets/css/modern-signin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Mentor attendance dashboard for managing daily attendance">
    <style>
        /* Dashboard-specific styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #1E6CB4, #3B82F6); }
        .stat-icon.green { background: linear-gradient(135deg, #10B981, #34D399); }
        .stat-icon.gray { background: linear-gradient(135deg, #6B7280, #9CA3AF); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1F2937;
            margin: 8px 0;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6B7280;
            font-weight: 500;
        }

        .action-bar {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
        }

        .action-bar-left {
            display: flex;
            gap: 16px;
            flex: 1;
            min-width: 300px;
        }

        .bulk-actions {
            display: flex;
            gap: 12px;
        }

        .btn-bulk-signout {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-bulk-signout:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-bulk-signout:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3);
        }

        .btn-refresh {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .tabs {
            background: white;
            border-radius: 16px 16px 0 0;
            padding: 0 24px;
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #E5E7EB;
        }

        .tab {
            padding: 16px 24px;
            font-weight: 600;
            color: #6B7280;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            position: relative;
            top: 2px;
        }

        .tab.active {
            color: #1E6CB4;
            border-bottom-color: #1E6CB4;
        }

        .tab-content {
            background: white;
            border-radius: 0 0 16px 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table thead {
            background: #F9FAFB;
        }

        .attendance-table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            border-bottom: 2px solid #E5E7EB;
        }

        .attendance-table td {
            padding: 16px;
            border-bottom: 1px solid #E5E7EB;
        }

        .attendance-table tbody tr:hover {
            background: #F9FAFB;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info-table {
            display: flex;
            flex-direction: column;
        }

        .user-name-table {
            font-weight: 600;
            color: #1F2937;
        }

        .time-text {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .duration-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .duration-badge.short {
            background: #FEE2E2;
            color: #991B1B;
        }

        .duration-badge.medium {
            background: #FEF3C7;
            color: #92400E;
        }

        .duration-badge.long {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            background: #D1FAE5;
            color: #065F46;
        }

        .checkbox-cell {
            width: 40px;
        }

        .bulk-signout-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .bulk-signout-modal.show {
            display: flex;
        }

        .bulk-modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .bulk-modal-header {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 16px;
        }

        .bulk-modal-body {
            margin-bottom: 24px;
        }

        .selected-users-list {
            max-height: 200px;
            overflow-y: auto;
            background: #F9FAFB;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }

        .selected-user-item {
            padding: 8px 12px;
            border-bottom: 1px solid #E5E7EB;
        }

        .selected-user-item:last-child {
            border-bottom: none;
        }

        .bulk-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #E5E7EB;
            color: #374151;
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6B7280;
        }

        .empty-state svg {
            margin: 0 auto 16px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="<?php echo BASE_URL?>public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Logo" class="logo">
            </div>
            <h1 class="header-title">Attendance Dashboard</h1>
            <div class="logo-section">
                <img src="<?php echo BASE_URL?>public/assets/images/TheClubhouse_Logo_White_Large.png" alt="The Clubhouse Logo" class="logo">
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Success Toast -->
        <div id="success-toast" class="toast success-toast">
            <div class="toast-content">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="#10B981"/>
                    <path d="M7 10L9 12L13 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span id="toast-message">Operation successful!</span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($attendanceData['counts']['total']); ?></div>
                <div class="stat-label">Total Attendance Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($attendanceData['counts']['signed_in']); ?></div>
                <div class="stat-label">Currently Signed In</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon gray">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 21V19C3 17.9391 3.42143 16.9217 4.17157 16.1716C4.92172 15.4214 5.93913 15 7 15H11C12.0609 15 13.0783 15.4214 13.8284 16.1716C14.5786 16.9217 15 17.9391 15 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 11H22M19 8V14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($attendanceData['counts']['signed_out']); ?></div>
                <div class="stat-label">Signed Out</div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="action-bar-left">
                <div class="search-container" style="flex: 1;">
                    <div class="search-wrapper">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input
                            type="text"
                            id="table-search"
                            class="search-input"
                            placeholder="Search users..."
                            autocomplete="off"
                        >
                        <button id="clear-table-search" class="clear-btn" type="button" aria-label="Clear search" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="bulk-actions">
                <button id="bulk-signout-btn" class="btn-bulk-signout" disabled>
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2L2 2V14L10 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 8H14M14 8L11 5M14 8L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Bulk Signout (<span id="selected-count">0</span>)
                </button>
                <button class="btn-refresh" onclick="location.reload()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C10.0947 2 11.941 3.05 13.0291 4.63158M14 2V6H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" data-tab="signed-in">
                Currently Signed In (<?php echo count($attendanceData['signed_in']); ?>)
            </div>
            <div class="tab" data-tab="signed-out">
                Signed Out (<?php echo count($attendanceData['signed_out']); ?>)
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Signed In Tab -->
            <div class="tab-pane active" id="signed-in-pane">
                <?php if (!empty($attendanceData['signed_in'])): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="select-all-signed-in">
                                </th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Sign-In Time</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="signed-in-tbody">
                            <?php foreach ($attendanceData['signed_in'] as $record): ?>
                                <tr data-user-id="<?php echo htmlspecialchars($record['user_id']); ?>" class="attendance-row">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" class="user-checkbox" value="<?php echo htmlspecialchars($record['user_id']); ?>">
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="table-avatar">
                                                <?php echo strtoupper(substr($record['name'], 0, 1) . substr($record['surname'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div class="user-info-table">
                                                <div class="user-name-table"><?php echo htmlspecialchars($record['name'] . ' ' . ($record['surname'] ?? '')); ?></div>
                                                <small class="time-text">ID: <?php echo htmlspecialchars($record['user_id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-role <?php echo strtolower($record['user_type'] ?? 'member'); ?>">
                                            <?php echo htmlspecialchars(strtoupper($record['user_type'] ?? 'MEMBER')); ?>
                                        </span>
                                    </td>
                                    <td class="time-text">
                                        <?php echo htmlspecialchars(date('h:i A', strtotime($record['signin_time'] ?? 'now'))); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $duration = isset($record['signin_time']) ? (time() - strtotime($record['signin_time'])) / 60 : 0;
                                        $hours = floor($duration / 60);
                                        $minutes = $duration % 60;
                                        $durationClass = $duration < 60 ? 'short' : ($duration < 180 ? 'medium' : 'long');
                                        ?>
                                        <span class="duration-badge <?php echo $durationClass; ?>">
                                            <?php echo $hours > 0 ? "{$hours}h " : ""; ?><?php echo round($minutes); ?>m
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn signout-btn" onclick="signOutUser(<?php echo $record['user_id']; ?>)">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 2L2 2V14L10 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M6 8H14M14 8L11 5M14 8L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Sign Out
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24 44C35.0457 44 44 35.0457 44 24C44 12.9543 35.0457 4 24 4C12.9543 4 4 12.9543 4 24C4 35.0457 12.9543 44 24 44Z" stroke="#D1D5DB" stroke-width="2"/>
                            <path d="M16 16L32 32M32 16L16 32" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <p>No users currently signed in</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Signed Out Tab -->
            <div class="tab-pane" id="signed-out-pane">
                <?php if (!empty($attendanceData['signed_out'])): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Sign-In</th>
                                <th>Sign-Out</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="signed-out-tbody">
                            <?php foreach ($attendanceData['signed_out'] as $record): ?>
                                <tr class="attendance-row">
                                    <td>
                                        <div class="user-cell">
                                            <div class="table-avatar">
                                                <?php echo strtoupper(substr($record['name'], 0, 1) . substr($record['surname'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div class="user-info-table">
                                                <div class="user-name-table"><?php echo htmlspecialchars($record['name'] . ' ' . ($record['surname'] ?? '')); ?></div>
                                                <small class="time-text">ID: <?php echo htmlspecialchars($record['user_id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-role <?php echo strtolower($record['user_type'] ?? 'member'); ?>">
                                            <?php echo htmlspecialchars(strtoupper($record['user_type'] ?? 'MEMBER')); ?>
                                        </span>
                                    </td>
                                    <td class="time-text">
                                        <?php echo htmlspecialchars(date('h:i A', strtotime($record['signin_time'] ?? 'now'))); ?>
                                    </td>
                                    <td class="time-text">
                                        <?php echo htmlspecialchars(date('h:i A', strtotime($record['signout_time'] ?? 'now'))); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $duration = isset($record['duration_minutes']) ? $record['duration_minutes'] : 0;
                                        $hours = floor($duration / 60);
                                        $minutes = $duration % 60;
                                        $durationClass = $duration < 60 ? 'short' : ($duration < 180 ? 'medium' : 'long');
                                        ?>
                                        <span class="duration-badge <?php echo $durationClass; ?>">
                                            <?php echo $hours > 0 ? "{$hours}h " : ""; ?><?php echo round($minutes); ?>m
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge">Completed</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24 44C35.0457 44 44 35.0457 44 24C44 12.9543 35.0457 4 24 4C12.9543 4 4 12.9543 4 24C4 35.0457 12.9543 44 24 44Z" stroke="#D1D5DB" stroke-width="2"/>
                            <path d="M16 16L32 32M32 16L16 32" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <p>No sign-outs recorded today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bulk Signout Confirmation Modal -->
        <div id="bulk-signout-modal" class="bulk-signout-modal">
            <div class="bulk-modal-content">
                <div class="bulk-modal-header">Confirm Bulk Signout</div>
                <div class="bulk-modal-body">
                    <p>You are about to sign out <strong><span id="bulk-count">0</span> user(s)</strong>. This action cannot be undone.</p>
                    <div id="selected-users-list" class="selected-users-list"></div>
                </div>
                <form id="bulk-signout-form" method="POST" action="<?php echo BASE_URL; ?>mentor/attendance/bulk-signout">
                    <?php echo CSRF::field(); ?>
                    <input type="hidden" name="user_ids" id="bulk-user-ids">
                    <div class="bulk-modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeBulkModal()">Cancel</button>
                        <button type="submit" class="btn-confirm">Confirm Signout</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Pass BASE_URL to JavaScript -->
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        window.USE_MODERN_ROUTING = <?php echo defined('USE_MODERN_ROUTING') && USE_MODERN_ROUTING ? 'true' : 'true'; ?>;
    </script>
    <script src="<?php echo BASE_URL?>/public/assets/js/script.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and panes
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

                // Add active class to clicked tab and corresponding pane
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-pane').classList.add('active');
            });
        });

        // Table search functionality
        const tableSearch = document.getElementById('table-search');
        const clearTableSearch = document.getElementById('clear-table-search');

        tableSearch?.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            clearTableSearch.style.display = query ? 'flex' : 'none';

            document.querySelectorAll('.attendance-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        clearTableSearch?.addEventListener('click', function() {
            tableSearch.value = '';
            this.style.display = 'none';
            document.querySelectorAll('.attendance-row').forEach(row => {
                row.style.display = '';
            });
        });

        // Checkbox selection
        const selectAllCheckbox = document.getElementById('select-all-signed-in');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const bulkSignoutBtn = document.getElementById('bulk-signout-btn');
        const selectedCountSpan = document.getElementById('selected-count');

        function updateBulkButton() {
            const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
            selectedCountSpan.textContent = selectedCount;
            bulkSignoutBtn.disabled = selectedCount === 0;
        }

        selectAllCheckbox?.addEventListener('change', function() {
            userCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkButton();
        });

        userCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkButton);
        });

        // Bulk signout modal
        bulkSignoutBtn?.addEventListener('click', function() {
            const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'));
            if (selectedUsers.length === 0) return;

            const userIds = selectedUsers.map(cb => cb.value);
            const userNames = selectedUsers.map(cb => {
                const row = cb.closest('tr');
                return row.querySelector('.user-name-table').textContent;
            });

            document.getElementById('bulk-count').textContent = userIds.length;
            document.getElementById('bulk-user-ids').value = userIds.join(',');

            const usersList = document.getElementById('selected-users-list');
            usersList.innerHTML = userNames.map(name =>
                `<div class="selected-user-item">${name}</div>`
            ).join('');

            document.getElementById('bulk-signout-modal').classList.add('show');
        });

        function closeBulkModal() {
            document.getElementById('bulk-signout-modal').classList.remove('show');
        }

        // Individual signout (placeholder - implement with AJAX)
        function signOutUser(userId) {
            if (confirm('Sign out this user?')) {
                // TODO: Implement AJAX signout
                console.log('Signing out user:', userId);
                location.reload();
            }
        }

        // Show success message if present
        <?php if (isset($_SESSION['success'])): ?>
            const toast = document.getElementById('success-toast');
            document.getElementById('toast-message').textContent = '<?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        <?php endif; ?>
    </script>
</body>
</html>
