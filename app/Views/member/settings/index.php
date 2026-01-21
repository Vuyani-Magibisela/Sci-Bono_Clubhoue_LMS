<?php
/**
 * Settings Index - Main settings navigation page
 * Phase 3: Week 6-7 Implementation
 *
 * Data from SettingsController:
 * - $user: Current user data
 * - $pageTitle: Page title
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Settings'); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/settingsStyle.css">
</head>
<body>
    <!-- Mobile navigation toggle -->
    <button id="mobile-nav-toggle" class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container">
        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/Sci-Bono_Clubhoue_LMS/public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="/Sci-Bono_Clubhoue_LMS/dashboard" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <span class="sidebar-text">Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" onclick="alert('Projects feature coming soon!'); return false;" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <span class="sidebar-text">Projects</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" onclick="alert('Members directory coming soon!'); return false;" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="sidebar-text">Members</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/Sci-Bono_Clubhoue_LMS/courses" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sidebar-text">Learn</span>
                    </a>
                </li>
                <?php if (isset($user['user_type']) && $user['user_type'] === 'admin'): ?>
                <li class="sidebar-item">
                    <a href="/Sci-Bono_Clubhoue_LMS/admin/reports" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="sidebar-text">Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="sidebar-item">
                    <a href="/Sci-Bono_Clubhoue_LMS/attendance" class="sidebar-link">
                        <div class="sidebar-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <span class="sidebar-text">Daily Register</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/Sci-Bono_Clubhoue_LMS/settings" class="sidebar-link active">
                        <div class="sidebar-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="sidebar-text">Settings</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <form method="POST" action="/Sci-Bono_Clubhoue_LMS/logout" style="margin: 0;">
                    <?php
                    require_once __DIR__ . '/../../../../core/CSRF.php';
                    echo CSRF::field();
                    ?>
                    <button type="submit" class="logout-button" style="border: none; background: none; width: 100%; cursor: pointer; text-align: left;">
                        <i class="fas fa-sign-out-alt logout-icon"></i>
                        <span class="logout-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1 class="content-title">Settings</h1>
                <p class="content-subtitle">Manage your account settings and preferences</p>
            </div>

            <!-- Settings Grid -->
            <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <!-- Profile Settings Card -->
                <div class="settings-card" onclick="window.location.href='/Sci-Bono_Clubhoue_LMS/settings/profile'" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-user" style="font-size: 1.5rem; color: var(--primary);"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #1c1e21;">Profile</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Manage your personal information</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        Update your name, username, email, phone, and other personal details.
                    </div>
                </div>

                <!-- Password Settings Card -->
                <div class="settings-card" onclick="window.location.href='/Sci-Bono_Clubhoue_LMS/settings/password'" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-lock" style="font-size: 1.5rem; color: #ff6b6b;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #1c1e21;">Password & Security</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Change your password</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        Update your password to keep your account secure.
                    </div>
                </div>

                <!-- Notification Settings Card -->
                <div class="settings-card" onclick="window.location.href='/Sci-Bono_Clubhoue_LMS/settings/notifications'" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-bell" style="font-size: 1.5rem; color: #ffc107;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #1c1e21;">Notifications</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Manage notification preferences</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        Control how and when you receive notifications.
                    </div>
                </div>

                <!-- Privacy & Data Settings Card -->
                <div class="settings-card" onclick="alert('Data export feature coming soon!'); return false;" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-shield-alt" style="font-size: 1.5rem; color: #28a745;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #1c1e21;">Privacy & Data</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Download your data</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        Export your personal data and activity history (GDPR compliance).
                    </div>
                </div>

                <?php if (isset($user['user_type']) && $user['user_type'] === 'admin'): ?>
                <!-- Admin - Manage Users Card -->
                <div class="settings-card" onclick="window.location.href='/Sci-Bono_Clubhoue_LMS/admin/users'" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-users-cog" style="font-size: 1.5rem; color: #6c63ff;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #1c1e21;">Manage Users</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Admin only</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        View and manage all user accounts and permissions.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Delete Account Card (Danger Zone) -->
                <div class="settings-card" onclick="window.location.href='/Sci-Bono_Clubhoue_LMS/settings/delete-account'" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s; border: 1px solid #ff6b6b;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 60px; height: 60px; background: #fff0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: #dc3545;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.125rem; color: #dc3545;">Delete Account</h3>
                            <p style="margin: 0.25rem 0 0; color: #65676b; font-size: 0.875rem;">Danger zone</p>
                        </div>
                    </div>
                    <div style="color: #65676b; font-size: 0.875rem; line-height: 1.5;">
                        Permanently delete your account and all associated data.
                    </div>
                </div>
            </div>

            <!-- Account Summary Section -->
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
                <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #1c1e21;">Account Summary</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Full Name</div>
                        <div style="color: #1c1e21; font-weight: 600;">
                            <?php
                            $fullName = $user['name'];
                            if (!empty($user['surname'])) {
                                $fullName .= ' ' . $user['surname'];
                            }
                            echo htmlspecialchars($fullName);
                            ?>
                        </div>
                    </div>

                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Username</div>
                        <div style="color: #1c1e21; font-weight: 600;">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                    </div>

                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Email</div>
                        <div style="color: #1c1e21; font-weight: 600;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>

                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Account Type</div>
                        <div style="color: #1c1e21; font-weight: 600; text-transform: capitalize;">
                            <?php echo htmlspecialchars($user['user_type']); ?>
                        </div>
                    </div>

                    <?php if (!empty($user['leaner_number'])): ?>
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Phone Number</div>
                        <div style="color: #1c1e21; font-weight: 600;">
                            <?php echo htmlspecialchars($user['leaner_number']); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($user['date_of_birth'])): ?>
                    <div>
                        <div style="color: #65676b; font-size: 0.875rem; margin-bottom: 0.25rem;">Date of Birth</div>
                        <div style="color: #1c1e21; font-weight: 600;">
                            <?php echo date('d M Y', strtotime($user['date_of_birth'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile nav toggle
        document.getElementById('mobile-nav-toggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Sidebar toggle
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });

        // Settings card hover effects
        document.querySelectorAll('.settings-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            });
        });
    </script>
</body>
</html>
