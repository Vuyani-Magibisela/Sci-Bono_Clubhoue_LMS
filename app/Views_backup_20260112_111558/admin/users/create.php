<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/users">Users</a>
    <span class="breadcrumb-separator">/</span>
    <span>Create User</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Create New User</h1>
</div>

<!-- Create User Form -->
<div class="form-container">
    <form method="POST" action="<?php echo BASE_URL; ?>admin/users" id="createUserForm">
        <?php echo CSRF::field(); ?>

        <!-- Personal Information Section -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">
                    <i class="fas fa-user"></i>
                    Personal Information
                </h3>
            </div>
            <div class="form-section-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">First Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['name'] ?? ''); ?>"
                            required
                            maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="surname" class="form-label">Surname *</label>
                        <input
                            type="text"
                            id="surname"
                            name="surname"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['surname'] ?? ''); ?>"
                            required
                            maxlength="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($_SESSION['old_input']['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($_SESSION['old_input']['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($_SESSION['old_input']['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input
                            type="date"
                            id="dob"
                            name="dob"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['dob'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Details Section -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">
                    <i class="fas fa-key"></i>
                    Account Details
                </h3>
            </div>
            <div class="form-section-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['username'] ?? ''); ?>"
                            required
                            maxlength="50">
                        <small class="form-hint">Must be unique. Used for login.</small>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['email'] ?? ''); ?>"
                            required
                            maxlength="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <div style="position: relative;">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                required
                                minlength="6">
                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('password')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gray-medium);">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                        <small class="form-hint">Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            required
                            minlength="6">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="user_type" class="form-label">User Type *</label>
                        <select id="user_type" name="user_type" class="form-control" required>
                            <option value="">Select User Type</option>
                            <?php foreach ($userTypes as $type): ?>
                                <option
                                    value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo ($_SESSION['old_input']['user_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['old_input']['phone'] ?? ''); ?>"
                            maxlength="15">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="<?php echo BASE_URL; ?>admin/users" class="btn-secondary">
                <i class="fas fa-times"></i>
                Cancel
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>
                Create User
            </button>
        </div>
    </form>
</div>

<!-- Additional Styles -->
<style>
    .form-container {
        background: white;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        padding: 30px;
        max-width: 900px;
    }

    .form-section {
        margin-bottom: 30px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-md);
        overflow: hidden;
    }

    .form-section-header {
        background-color: var(--light);
        padding: 15px 20px;
        border-bottom: 1px solid var(--gray-light);
    }

    .form-section-title {
        margin: 0;
        font-size: 1.1rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section-content {
        padding: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-row:last-child {
        margin-bottom: 0;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .form-control {
        padding: 10px 15px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-sm);
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(81, 70, 230, 0.1);
    }

    .form-hint {
        color: var(--gray-medium);
        font-size: 0.85rem;
        margin-top: 5px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-light);
    }

    .btn-secondary {
        background-color: var(--gray-light);
        color: var(--dark);
        padding: 10px 20px;
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background-color: var(--gray-medium);
        color: white;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .form-container {
            padding: 20px;
        }
    }
</style>

<!-- JavaScript -->
<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-icon');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Form validation
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmation = document.getElementById('password_confirmation').value;

        if (password !== confirmation) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
            return false;
        }

        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return false;
        }
    });

    // Clear old input on page load
    <?php unset($_SESSION['old_input']); ?>
</script>
