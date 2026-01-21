<?php
/**
 * Profile Settings - Edit user profile
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
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Profile Settings'); ?> - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/settingsStyle.css">
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
                    <img src="/public/assets/images/TheClubhouse_Logo_White_Large.png" alt="Clubhouse Logo">
                </div>
                <button id="sidebar-toggle" class="toggle-sidebar">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="/dashboard" class="sidebar-link">
                        <div class="sidebar-icon"><i class="fas fa-home"></i></div>
                        <span class="sidebar-text">Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/projects" class="sidebar-link">
                        <div class="sidebar-icon"><i class="fas fa-project-diagram"></i></div>
                        <span class="sidebar-text">Projects</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/members" class="sidebar-link">
                        <div class="sidebar-icon"><i class="fas fa-users"></i></div>
                        <span class="sidebar-text">Members</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/courses" class="sidebar-link">
                        <div class="sidebar-icon"><i class="fas fa-book"></i></div>
                        <span class="sidebar-text">Learn</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/attendance" class="sidebar-link">
                        <div class="sidebar-icon"><i class="fas fa-sign-in-alt"></i></div>
                        <span class="sidebar-text">Daily Register</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="/settings" class="sidebar-link active">
                        <div class="sidebar-icon"><i class="fas fa-cog"></i></div>
                        <span class="sidebar-text">Settings</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="/logout" class="logout-button">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1 class="content-title">Profile Settings</h1>
                <a href="/settings" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Settings
                </a>
            </div>

            <div class="settings-container">
                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <a href="/settings/profile" class="settings-nav-link active">Profile</a>
                    <a href="/settings/password" class="settings-nav-link">Password</a>
                    <a href="/settings/notifications" class="settings-nav-link">Notifications</a>
                </div>

                <!-- Settings Content Area -->
                <div class="settings-content">
                    <!-- Profile header with avatar -->
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile" id="profile-image" class="profile-image">
                            <?php else: ?>
                                <div class="profile-image-placeholder" style="background-color: <?php echo $user['avatar_color'] ?? '#3F51B5'; ?>">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="profile-image-input" accept="image/*" style="display: none;">
                            <button type="button" id="change-image-btn" class="change-image" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-name"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></h2>
                            <p class="profile-role"><?php echo ucfirst($user['user_type']); ?></p>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <form id="settings-form" action="/settings/profile" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <!-- Personal Details Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Personal Details</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first-name" class="form-label">First Name *</label>
                                        <input type="text" id="first-name" name="name" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="surname" class="form-label">Surname *</label>
                                        <input type="text" id="surname" name="surname" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['surname']); ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" id="username" name="username" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                        <div class="error-message"></div>
                                        <span class="form-hint">3-30 characters, letters, numbers, underscores, and hyphens only</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select id="gender" name="gender" class="form-control form-select" required>
                                            <option value="Male" <?php echo ($user['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($user['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="dob" class="form-label">Date of Birth *</label>
                                        <input type="date" id="dob" name="date_of_birth" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="sa-id-number" class="form-label">SA ID Number</label>
                                        <input type="text" id="sa-id-number" name="id_number" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                        <span class="form-hint">13 digits for South African ID</span>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" id="email" name="email" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="cell-number" class="form-label">Phone Number</label>
                                        <input type="text" id="cell-number" name="phone" class="form-control phone-input input-control"
                                               value="<?php echo htmlspecialchars($user['leaner_number'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nationality" class="form-label">Nationality</label>
                                        <select id="nationality" name="nationality" class="form-control form-select">
                                            <option value="">Select nationality</option>
                                            <option value="South African" <?php echo ($user['nationality'] ?? '') == 'South African' ? 'selected' : ''; ?>>South African</option>
                                            <option value="British" <?php echo ($user['nationality'] ?? '') == 'British' ? 'selected' : ''; ?>>British</option>
                                            <option value="Nigerian" <?php echo ($user['nationality'] ?? '') == 'Nigerian' ? 'selected' : ''; ?>>Nigerian</option>
                                            <option value="Zimbabwean" <?php echo ($user['nationality'] ?? '') == 'Zimbabwean' ? 'selected' : ''; ?>>Zimbabwean</option>
                                            <option value="Other" <?php echo !in_array($user['nationality'] ?? '', ['', 'South African', 'British', 'Nigerian', 'Zimbabwean']) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="home-language" class="form-label">Home Language</label>
                                        <select id="home-language" name="home_language" class="form-control form-select">
                                            <option value="">Select language</option>
                                            <option value="English" <?php echo ($user['home_language'] ?? '') == 'English' ? 'selected' : ''; ?>>English</option>
                                            <option value="Afrikaans" <?php echo ($user['home_language'] ?? '') == 'Afrikaans' ? 'selected' : ''; ?>>Afrikaans</option>
                                            <option value="isiZulu" <?php echo ($user['home_language'] ?? '') == 'isiZulu' ? 'selected' : ''; ?>>isiZulu</option>
                                            <option value="isiXhosa" <?php echo ($user['home_language'] ?? '') == 'isiXhosa' ? 'selected' : ''; ?>>isiXhosa</option>
                                            <option value="Sepedi" <?php echo ($user['home_language'] ?? '') == 'Sepedi' ? 'selected' : ''; ?>>Sepedi</option>
                                            <option value="Sesotho" <?php echo ($user['home_language'] ?? '') == 'Sesotho' ? 'selected' : ''; ?>>Sesotho</option>
                                            <option value="Setswana" <?php echo ($user['home_language'] ?? '') == 'Setswana' ? 'selected' : ''; ?>>Setswana</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea id="bio" name="bio" class="form-control input-control" rows="4"
                                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <div class="error-message"></div>
                                    <span class="form-hint">Maximum 500 characters</span>
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="form-section-title">Address Information</h3>
                            </div>
                            <div class="form-section-content">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-street" class="form-label">Street</label>
                                        <input type="text" id="address-street" name="address_street" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['address_street'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="address-suburb" class="form-label">Suburb/Township</label>
                                        <input type="text" id="address-suburb" name="address_suburb" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['address_suburb'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-city" class="form-label">City</label>
                                        <input type="text" id="address-city" name="address_city" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['address_city'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="address-province" class="form-label">Province</label>
                                        <select id="address-province" name="address_province" class="form-control form-select">
                                            <option value="">Select province</option>
                                            <option value="Gauteng" <?php echo ($user['address_province'] ?? '') == 'Gauteng' ? 'selected' : ''; ?>>Gauteng</option>
                                            <option value="Western Cape" <?php echo ($user['address_province'] ?? '') == 'Western Cape' ? 'selected' : ''; ?>>Western Cape</option>
                                            <option value="KwaZulu-Natal" <?php echo ($user['address_province'] ?? '') == 'KwaZulu-Natal' ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                                            <option value="Eastern Cape" <?php echo ($user['address_province'] ?? '') == 'Eastern Cape' ? 'selected' : ''; ?>>Eastern Cape</option>
                                            <option value="Limpopo" <?php echo ($user['address_province'] ?? '') == 'Limpopo' ? 'selected' : ''; ?>>Limpopo</option>
                                            <option value="Mpumalanga" <?php echo ($user['address_province'] ?? '') == 'Mpumalanga' ? 'selected' : ''; ?>>Mpumalanga</option>
                                            <option value="North West" <?php echo ($user['address_province'] ?? '') == 'North West' ? 'selected' : ''; ?>>North West</option>
                                            <option value="Northern Cape" <?php echo ($user['address_province'] ?? '') == 'Northern Cape' ? 'selected' : ''; ?>>Northern Cape</option>
                                            <option value="Free State" <?php echo ($user['address_province'] ?? '') == 'Free State' ? 'selected' : ''; ?>>Free State</option>
                                        </select>
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="address-postal-code" class="form-label">Postal Code</label>
                                        <input type="text" id="address-postal-code" name="address_postal_code" class="form-control input-control"
                                               value="<?php echo htmlspecialchars($user['address_postal_code'] ?? ''); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn-secondary" onclick="window.location.href='/settings'">
                                Cancel
                            </button>
                        </div>
                    </form>
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

        // Avatar upload handling
        document.getElementById('change-image-btn')?.addEventListener('click', function() {
            document.getElementById('profile-image-input').click();
        });

        document.getElementById('profile-image-input')?.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('avatar', this.files[0]);
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                fetch('/settings/avatar', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update avatar preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('profile-image').src = e.target.result;
                        };
                        reader.readAsDataURL(this.files[0]);
                        alert('Avatar updated successfully!');
                    } else {
                        alert('Error uploading avatar: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error uploading avatar');
                });
            }
        });

        // Form submission handling
        document.getElementById('settings-form')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('/settings/profile', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating your profile');
            });
        });
    </script>
</body>
</html>
