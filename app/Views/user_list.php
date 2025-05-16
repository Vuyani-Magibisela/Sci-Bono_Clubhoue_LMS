<?php
// Include header and sidebar navigation
include './admin/admin_header.php';
?>

<main id="main-content" class="main-content">
    <div class="content-header">
        <h1 class="content-title">Edit User</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="settings-container">
        <!-- Settings Navigation -->
        <div class="settings-nav">
            <a href="../Views/settings.php" class="settings-nav-link">Profile</a>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="./user_list.php" class="settings-nav-link active">Manage Members</a>
                <a href="#" class="settings-nav-link">Approve Members</a>
            <?php endif; ?>
        </div>
        
        <!-- Settings Content Area -->
        <div class="settings-content">
            <!-- Profile header with image -->
            <div class="profile-header">
                <div class="profile-image-container">
                    <img src="../../public/assets/images/ui-user-profile-negative.svg" alt="Profile" id="profile-image" class="profile-image" style="display: none;">
                    <div class="profile-image-placeholder"><?php echo substr($user['name'], 0, 1); ?></div>
                    <input type="file" id="profile-image-input" accept="image/*" style="display: none;">
                    <button type="button" id="change-image-btn" class="change-image" title="Change profile picture">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?php echo $user['name'] . ' ' . $user['surname']; ?></h2>
                    <p class="profile-role"><?php echo ucfirst($user['user_type']); ?></p>
                </div>
            </div>
            
            <!-- Edit Profile Form -->
            <form id="settings-form" action="../Controllers/user_update.php" method="post">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                
                <!-- Personal Details Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">Personal Details</h3>
                    </div>
                    <div class="form-section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first-name" class="form-label">First Name</label>
                                <input type="text" id="first-name" name="name" class="form-control input-control" value="<?php echo $user['name']; ?>" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="surname" class="form-label">Surname</label>
                                <input type="text" id="surname" name="surname" class="form-control input-control" value="<?php echo $user['surname']; ?>" required>
                                <div class="error-message"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control input-control" value="<?php echo $user['username']; ?>" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="nationality" class="form-label">Nationality</label>
                                <select id="nationality" name="nationality" class="form-control form-select" data-other-field="other_nationality_div">
                                    <option value="South African" <?php echo (isset($user['nationality']) && $user['nationality'] == 'South African') ? 'selected' : ''; ?>>South African</option>
                                    <option value="Afghan" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Afghan') ? 'selected' : ''; ?>>Afghan</option>
                                    <option value="Albanian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Albanian') ? 'selected' : ''; ?>>Albanian</option>
                                    <option value="Algerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Algerian') ? 'selected' : ''; ?>>Algerian</option>
                                    <option value="British" <?php echo (isset($user['nationality']) && $user['nationality'] == 'British') ? 'selected' : ''; ?>>British</option>
                                    <option value="Chinese" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                                    <option value="Egyptian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Egyptian') ? 'selected' : ''; ?>>Egyptian</option>
                                    <option value="French" <?php echo (isset($user['nationality']) && $user['nationality'] == 'French') ? 'selected' : ''; ?>>French</option>
                                    <option value="German" <?php echo (isset($user['nationality']) && $user['nationality'] == 'German') ? 'selected' : ''; ?>>German</option>
                                    <option value="Indian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Indian') ? 'selected' : ''; ?>>Indian</option>
                                    <option value="Nigerian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Nigerian') ? 'selected' : ''; ?>>Nigerian</option>
                                    <option value="Zambian" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zambian') ? 'selected' : ''; ?>>Zambian</option>
                                    <option value="Zimbabwean" <?php echo (isset($user['nationality']) && $user['nationality'] == 'Zimbabwean') ? 'selected' : ''; ?>>Zimbabwean</option>
                                    <option value="Other" <?php echo (isset($user['nationality']) && !in_array($user['nationality'], ['South African', 'Afghan', 'Albanian', 'Algerian', 'British', 'Chinese', 'Egyptian', 'French', 'German', 'Indian', 'Nigerian', 'Zambian', 'Zimbabwean']) && !empty($user['nationality'])) ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="error-message"></div>
                            </div>
                        </div>
                        
                        <!-- Rest of the form fields similar to settings.php -->
                        <!-- ... (Include all form fields from settings.php) ... -->
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <a href="./user_list.php" class="form-button form-button-secondary">Cancel</a>
                            <button type="submit" class="form-button">Update User</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include './admin/admin_footer.php'; ?>