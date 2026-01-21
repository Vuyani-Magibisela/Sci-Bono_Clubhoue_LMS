<?php
/**
 * Mentor Attendance Manual Registration View
 *
 * Allows mentors to manually register user attendance
 *
 * Phase 3 Week 4: Modern Routing System - View Implementation
 * Created: November 26, 2025
 *
 * Data available from Mentor\AttendanceController@register:
 * - $todayStats: array with total, signed_in, signed_out counts
 * - $recentSignIns: array of last 10 sign-ins
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
    <title>Attendance Register - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="<?php echo BASE_URL?>/public/assets/css/modern-signin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Mentor manual attendance registration interface">
    <style>
        /* Registration-specific styles */
        .register-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        @media (max-width: 1024px) {
            .register-layout {
                grid-template-columns: 1fr;
            }
        }

        .stats-bar {
            background: white;
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 24px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
        }

        .stat-badge.total {
            background: linear-gradient(135deg, #E0F2FE, #BAE6FD);
            color: #0369A1;
        }

        .stat-badge.active {
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            color: #065F46;
        }

        .stat-badge.completed {
            background: linear-gradient(135deg, #E5E7EB, #D1D5DB);
            color: #374151;
        }

        .stat-number {
            font-size: 1.25rem;
        }

        .registration-panel {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .panel-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }

        .panel-subtitle {
            color: #6B7280;
            margin-bottom: 24px;
        }

        .search-section {
            margin-bottom: 24px;
        }

        .large-search {
            position: relative;
        }

        .large-search-input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 1.125rem;
            transition: all 0.2s ease;
        }

        .large-search-input:focus {
            outline: none;
            border-color: #1E6CB4;
            box-shadow: 0 0 0 4px rgba(30, 108, 180, 0.1);
        }

        .large-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
        }

        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #E5E7EB;
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 100;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .autocomplete-dropdown.show {
            display: block;
        }

        .autocomplete-item {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #F3F4F6;
            transition: background 0.15s ease;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover {
            background: #F9FAFB;
        }

        .autocomplete-item.active {
            background: #EFF6FF;
        }

        .autocomplete-avatar {
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

        .autocomplete-info {
            flex: 1;
        }

        .autocomplete-name {
            font-weight: 600;
            color: #1F2937;
        }

        .autocomplete-details {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .selected-user-card {
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border: 2px solid #3B82F6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            display: none;
        }

        .selected-user-card.show {
            display: block;
        }

        .selected-user-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .selected-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .selected-info {
            flex: 1;
        }

        .selected-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 4px;
        }

        .selected-details {
            color: #6B7280;
            display: flex;
            gap: 16px;
            font-size: 0.875rem;
        }

        .password-section {
            margin-bottom: 24px;
        }

        .password-input-group {
            position: relative;
        }

        .password-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .password-input {
            width: 100%;
            padding: 14px 48px 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .password-input:focus {
            outline: none;
            border-color: #1E6CB4;
            box-shadow: 0 0 0 4px rgba(30, 108, 180, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9CA3AF;
            padding: 4px;
        }

        .submit-register-btn {
            width: 100%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        .submit-register-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .recent-panel {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 24px;
        }

        .recent-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #F9FAFB;
            border-radius: 10px;
            border-left: 3px solid #10B981;
        }

        .recent-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .recent-info {
            flex: 1;
        }

        .recent-name {
            font-weight: 600;
            color: #1F2937;
            font-size: 0.875rem;
        }

        .recent-time {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .empty-recent {
            text-align: center;
            padding: 32px 16px;
            color: #9CA3AF;
        }

        .empty-recent svg {
            margin: 0 auto 12px;
        }

        .clock-widget {
            background: linear-gradient(135deg, #1E6CB4, #3B82F6);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 16px;
        }

        .current-time {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .current-date {
            font-size: 0.875rem;
            opacity: 0.9;
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
            <h1 class="header-title">Attendance Register</h1>
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
                <span id="toast-message">Successfully registered!</span>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-badge total">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="currentColor"/>
                </svg>
                <span class="stat-number"><?php echo htmlspecialchars($todayStats['total']); ?></span>
                <span>Total Today</span>
            </div>
            <div class="stat-badge active">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="currentColor"/>
                    <path d="M6 10L8 12L14 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="stat-number"><?php echo htmlspecialchars($todayStats['signed_in']); ?></span>
                <span>Signed In</span>
            </div>
            <div class="stat-badge completed">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="currentColor"/>
                    <path d="M14 7L8.5 13L6 10.2727" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="stat-number"><?php echo htmlspecialchars($todayStats['signed_out']); ?></span>
                <span>Completed</span>
            </div>
        </div>

        <!-- Registration Layout -->
        <div class="register-layout">
            <!-- Main Registration Panel -->
            <div class="registration-panel">
                <h2 class="panel-title">Manual Registration</h2>
                <p class="panel-subtitle">Search for a user and enter their password to register attendance</p>

                <!-- Search Section -->
                <div class="search-section">
                    <div class="large-search">
                        <svg class="large-search-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input
                            type="text"
                            id="user-search"
                            class="large-search-input"
                            placeholder="Search by name, username, or ID..."
                            autocomplete="off"
                        >
                        <div id="autocomplete-dropdown" class="autocomplete-dropdown"></div>
                    </div>
                </div>

                <!-- Selected User Card -->
                <div id="selected-user-card" class="selected-user-card">
                    <div class="selected-user-header">
                        <div class="selected-avatar" id="selected-avatar">JD</div>
                        <div class="selected-info">
                            <div class="selected-name" id="selected-name">John Doe</div>
                            <div class="selected-details">
                                <span>ID: <strong id="selected-id">-</strong></span>
                                <span>•</span>
                                <span class="user-role" id="selected-role">MEMBER</span>
                            </div>
                        </div>
                    </div>

                    <!-- Password Form -->
                    <form id="registration-form">
                        <?php echo CSRF::field(); ?>
                        <input type="hidden" id="selected-user-id" name="user_id">

                        <div class="password-section">
                            <label for="user-password" class="password-label">Password</label>
                            <div class="password-input-group">
                                <input
                                    type="password"
                                    id="user-password"
                                    name="password"
                                    class="password-input"
                                    placeholder="Enter user password"
                                    required
                                    autocomplete="off"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <svg id="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 10C1 10 4 4 10 4C16 4 19 10 19 10C19 10 16 16 10 16C4 16 1 10 1 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="submit-register-btn">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" fill="white"/>
                                <path d="M6 10L8 12L14 8" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Sign In User
                        </button>
                    </form>
                </div>

                <!-- Help Text -->
                <div style="margin-top: 24px; padding: 16px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 8px;">
                    <strong style="color: #92400E;">Quick Tip:</strong>
                    <p style="color: #92400E; margin: 8px 0 0 0; font-size: 0.875rem;">
                        Start typing to search for users. Select a user from the dropdown, enter their password, and click "Sign In User" to register their attendance.
                    </p>
                </div>
            </div>

            <!-- Recent Sign-Ins Panel -->
            <div class="recent-panel">
                <!-- Clock Widget -->
                <div class="clock-widget">
                    <div class="current-time" id="current-time">--:--</div>
                    <div class="current-date" id="current-date">Loading...</div>
                </div>

                <div class="recent-title">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M10 5V10L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Recent Sign-Ins
                </div>

                <div class="recent-list" id="recent-list">
                    <?php if (!empty($recentSignIns)): ?>
                        <?php foreach ($recentSignIns as $recent): ?>
                            <div class="recent-item">
                                <div class="recent-avatar">
                                    <?php echo strtoupper(substr($recent['name'], 0, 1) . substr($recent['surname'] ?? '', 0, 1)); ?>
                                </div>
                                <div class="recent-info">
                                    <div class="recent-name"><?php echo htmlspecialchars($recent['name'] . ' ' . ($recent['surname'] ?? '')); ?></div>
                                    <div class="recent-time">
                                        <?php
                                        $signinTime = strtotime($recent['signin_time'] ?? 'now');
                                        $diff = time() - $signinTime;
                                        if ($diff < 60) echo "Just now";
                                        elseif ($diff < 3600) echo floor($diff / 60) . " min ago";
                                        else echo floor($diff / 3600) . " hr ago";
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-recent">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 36C28.8366 36 36 28.8366 36 20C36 11.1634 28.8366 4 20 4C11.1634 4 4 11.1634 4 20C4 28.8366 11.1634 36 20 36Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M13 13L27 27M27 13L13 27" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <p>No recent sign-ins</p>
                        </div>
                    <?php endif; ?>
                </div>
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
        // Mock user data for autocomplete (replace with AJAX call to search API)
        const mockUsers = [
            { id: 1, name: 'John Doe', username: 'jdoe', user_type: 'member' },
            { id: 2, name: 'Jane Smith', username: 'jsmith', user_type: 'admin' },
            { id: 3, name: 'Mike Johnson', username: 'mjohnson', user_type: 'mentor' }
        ];

        // Clock update
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            document.getElementById('current-time').textContent = timeStr;
            document.getElementById('current-date').textContent = dateStr;
        }

        updateClock();
        setInterval(updateClock, 1000);

        // Autocomplete functionality
        const userSearch = document.getElementById('user-search');
        const autocompleteDropdown = document.getElementById('autocomplete-dropdown');
        const selectedUserCard = document.getElementById('selected-user-card');
        let searchTimeout;
        let selectedIndex = -1;

        userSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                autocompleteDropdown.classList.remove('show');
                return;
            }

            searchTimeout = setTimeout(() => {
                // TODO: Replace with AJAX call to /api/v1/attendance/search
                const filteredUsers = mockUsers.filter(user =>
                    user.name.toLowerCase().includes(query) ||
                    user.username.toLowerCase().includes(query) ||
                    user.id.toString().includes(query)
                );

                displayAutocomplete(filteredUsers);
            }, 300);
        });

        function displayAutocomplete(users) {
            if (users.length === 0) {
                autocompleteDropdown.innerHTML = '<div style="padding: 16px; text-align: center; color: #9CA3AF;">No users found</div>';
                autocompleteDropdown.classList.add('show');
                return;
            }

            autocompleteDropdown.innerHTML = users.map((user, index) => `
                <div class="autocomplete-item" data-index="${index}" onclick="selectUser(${user.id}, '${user.name}', '${user.username}', '${user.user_type}')">
                    <div class="autocomplete-avatar">${user.name.split(' ').map(n => n[0]).join('').toUpperCase()}</div>
                    <div class="autocomplete-info">
                        <div class="autocomplete-name">${user.name}</div>
                        <div class="autocomplete-details">@${user.username} • ID: ${user.id} • ${user.user_type.toUpperCase()}</div>
                    </div>
                </div>
            `).join('');

            autocompleteDropdown.classList.add('show');
            selectedIndex = -1;
        }

        // Keyboard navigation
        userSearch.addEventListener('keydown', function(e) {
            const items = autocompleteDropdown.querySelectorAll('.autocomplete-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateActiveItem(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex]?.click();
            } else if (e.key === 'Escape') {
                autocompleteDropdown.classList.remove('show');
            }
        });

        function updateActiveItem(items) {
            items.forEach((item, index) => {
                item.classList.toggle('active', index === selectedIndex);
                if (index === selectedIndex) {
                    item.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        // Select user from autocomplete
        function selectUser(id, name, username, userType) {
            document.getElementById('selected-user-id').value = id;
            document.getElementById('selected-name').textContent = name;
            document.getElementById('selected-id').textContent = id;
            document.getElementById('selected-role').textContent = userType.toUpperCase();
            document.getElementById('selected-role').className = 'user-role ' + userType.toLowerCase();

            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('selected-avatar').textContent = initials;

            selectedUserCard.classList.add('show');
            autocompleteDropdown.classList.remove('show');
            userSearch.value = '';

            document.getElementById('user-password').focus();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userSearch.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.classList.remove('show');
            }
        });

        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('user-password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path d="M14.12 14.12C13.8454 14.4148 13.5141 14.6512 13.1462 14.8151C12.7782 14.9791 12.3809 15.0673 11.9781 15.0744C11.5753 15.0815 11.1752 15.0074 10.8016 14.8565C10.4281 14.7056 10.0887 14.4811 9.80385 14.1962C9.51897 13.9113 9.29439 13.572 9.14351 13.1984C8.99262 12.8249 8.91853 12.4247 8.92563 12.0219C8.93274 11.6191 9.02091 11.2219 9.18488 10.8539C9.34884 10.4859 9.58525 10.1547 9.88 9.88M17.94 17.94L2.06 2.06M19.07 19.07L1.93 1.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path d="M1 10C1 10 4 4 10 4C16 4 19 10 19 10C19 10 16 16 10 16C4 16 1 10 1 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            }
        }

        // Form submission
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const userId = document.getElementById('selected-user-id').value;
            const password = document.getElementById('user-password').value;

            if (!userId || !password) {
                alert('Please select a user and enter password');
                return;
            }

            const endpoints = getEndpoints();
            const formData = new FormData(this);

            fetch(endpoints.signin, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast
                    const toast = document.getElementById('success-toast');
                    document.getElementById('toast-message').textContent = 'User signed in successfully!';
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);

                    // Reset form
                    selectedUserCard.classList.remove('show');
                    document.getElementById('user-password').value = '';
                    userSearch.focus();

                    // Reload to update recent list
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert(data.message || 'Sign-in failed');
                    document.getElementById('user-password').value = '';
                    document.getElementById('user-password').focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        });

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
