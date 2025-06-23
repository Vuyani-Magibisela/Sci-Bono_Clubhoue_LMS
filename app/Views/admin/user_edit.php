<?php
// This view is included by UserController.php (showEditForm action).
// Variables available: $user (array of user data to edit), $pageTitle (string)
// $_SESSION is also available for user_type checks.

// Base path for assets, assuming this view is in app/Views/
$basePath = '../../../'; // Points from app/Views/ to the project root

$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$is_mentor = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'mentor';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Edit User'); ?> | Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>public/assets/css/settingsStyle.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>public/assets/css/header.css"> 
    <link rel="stylesheet" href="<?php echo $basePath; ?>public/assets/css/screenSizes.css"> 

    <style>
        /* Styles specific to the edit form, can be merged into settingsStyle.css */
        .edit-user-form-container {
            padding: 20px;
            background-color: var(--white, #ffffff);
            border-radius: var(--border-radius-md, 10px);
            box-shadow: var(--shadow-sm, 0 2px 4px rgba(0,0,0,0.05));
        }
        .edit-user-title {
            color: #2F2E41;
            font-size: 24px;
            margin-bottom: 25px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light, #d0d0db);
        }
        .cancel-button {
             background-color: var(--gray-medium, #6e6e80);
             color: var(--white, #ffffff);
             padding: 10px 20px;
             border: none;
             border-radius: var(--border-radius-sm, 6px);
             text-decoration: none;
             font-size: 0.95rem;
             font-weight: 500;
             cursor: pointer;
             transition: background-color 0.3s ease;
        }
        .cancel-button:hover {
             background-color: var(--gray-dark, #414153);
        }
         .message-area { /* Copied from list view for consistency */
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius-sm);
            font-size: 0.95rem;
        }
        .message-area.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-area.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body id="useredit-page">
    <main id="container-settings" class="container">
        
        <?php 
        // Include the main site header and the navigation sidebar
        include __DIR__ . '/../../header.php'; 
        // Adjust the path to _navigation.php relative to this file (app/Views/)
        include __DIR__ . '/../../_navigation.php'; 
        ?>

        <section id="settings_main_section">
            <div class="content_section_settings">
                <h1>Settings</h1>
                <div class="settingsContainer">
                    <div class="settingsNav">
                        <a href="<?php echo $basePath; ?>settings.php">Profile</a>
                        <a id="SettigsNav_active" href="<?php echo $basePath; ?>users.php?action=list">Manage Members</a>
                        <a href="#">Approve Members</a> <!-- Placeholder -->
                    </div>

                    <div class="settings-content"> 
                        <h1 class="edit-user-title">Edit User: <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></h1>

                         <?php if (isset($_SESSION['message'])): ?>
                            <div class="message-area <?php echo htmlspecialchars($_SESSION['message']['type']); ?>">
                                <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                            </div>
                            <?php unset($_SESSION['message']); // Clear message ?>
                        <?php endif; ?>

                        <div class="edit-user-form-container">
                            <form action="<?php echo $basePath; ?>users.php?action=update&id=<?php echo $user['id']; ?>" method="POST">
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <h3 class="form-section-title">Personal Information</h3>
                                    </div>
                                    <div class="form-section-content">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="name" class="form-label">First Name</label>
                                                <input type="text" id="name" name="name" class="form-control input-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="surname" class="form-label">Surname</label>
                                                <input type="text" id="surname" name="surname" class="form-control input-control" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                             <div class="form-group">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" id="username" name="username" class="form-control input-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" id="email" name="email" class="form-control input-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-header">
                                        <h3 class="form-section-title">Account Details</h3>
                                    </div>
                                    <div class="form-section-content">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="user_type" class="form-label">User Type</label>
                                                <select id="user_type" name="user_type" class="form-control form-select input-control" <?php echo $is_mentor ? 'disabled' : ''; ?> required>
                                                    <option value="member" <?php echo ($user['user_type'] === 'member') ? 'selected' : ''; ?>>Member</option>
                                                    <?php if ($is_admin): // Only admin can see/set admin/mentor types ?>
                                                        <option value="mentor" <?php echo ($user['user_type'] === 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                                                        <option value="admin" <?php echo ($user['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                    <?php endif; ?>
                                                </select>
                                                <?php if ($is_mentor): ?>
                                                    <!-- Hidden input to submit the value when disabled -->
                                                    <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user['user_type']); ?>"> 
                                                    <span class="form-hint">Mentors cannot change user type.</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="form-group">
                                                <label for="Center" class="form-label">Center</label>
                                                <input type="text" id="Center" name="Center" class="form-control input-control" value="<?php echo htmlspecialchars($user['Center']); ?>" required>
                                            </div>
                                        </div>
                                        <!-- Add password change fields here if needed -->
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <a href="<?php echo $basePath; ?>users.php?action=list" class="cancel-button">Cancel</a>
                                    <button type="submit" class="form-button">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
