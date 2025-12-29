<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/users">Users</a>
    <span class="breadcrumb-separator">/</span>
    <span>Edit User</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        Edit User: <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
    </h1>
</div>

<!-- Edit User Form -->
<div class="form-container">
    <form method="POST" action="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>/update" id="editUserForm">
        <?php echo CSRF::field(); ?>
        <input type="hidden" name="_method" value="PUT">

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
                            value="<?php echo htmlspecialchars($user['name']); ?>"
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
                            value="<?php echo htmlspecialchars($user['surname']); ?>"
                            required
                            maxlength="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($user['Gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user['Gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($user['Gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
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
                            value="<?php echo htmlspecialchars($user['username']); ?>"
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
                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                            required
                            maxlength="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="user_type" class="form-label">User Type *</label>
                        <?php
                        $isAdmin = $_SESSION['user_type'] === 'admin';
                        $isMentor = $_SESSION['user_type'] === 'mentor';
                        $canEditType = $isAdmin;
                        ?>
                        <select
                            id="user_type"
                            name="user_type"
                            class="form-control"
                            <?php echo !$canEditType ? 'disabled' : ''; ?>
                            required>
                            <option value="member" <?php echo ($user['user_type'] === 'member') ? 'selected' : ''; ?>>Member</option>
                            <?php if ($isAdmin): ?>
                                <option value="mentor" <?php echo ($user['user_type'] === 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                                <option value="admin" <?php echo ($user['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="parent" <?php echo ($user['user_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                                <option value="project officer" <?php echo ($user['user_type'] === 'project officer') ? 'selected' : ''; ?>>Project Officer</option>
                                <option value="manager" <?php echo ($user['user_type'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                            <?php endif; ?>
                        </select>
                        <?php if (!$canEditType): ?>
                            <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user['user_type']); ?>">
                            <small class="form-hint">Only admins can change user type.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            maxlength="15">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nationality" class="form-label">Nationality</label>
                        <input
                            type="text"
                            id="nationality"
                            name="nationality"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['nationality'] ?? ''); ?>"
                            maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="id_number" class="form-label">ID Number</label>
                        <input
                            type="text"
                            id="id_number"
                            name="id_number"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>"
                            maxlength="20">
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information Section -->
        <div class="form-section">
            <div class="form-section-header">
                <h3 class="form-section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Address Information
                </h3>
            </div>
            <div class="form-section-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_street" class="form-label">Street Address</label>
                        <input
                            type="text"
                            id="address_street"
                            name="address_street"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['address_street'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_suburb" class="form-label">Suburb</label>
                        <input
                            type="text"
                            id="address_suburb"
                            name="address_suburb"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['address_suburb'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address_city" class="form-label">City</label>
                        <input
                            type="text"
                            id="address_city"
                            name="address_city"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['address_city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_province" class="form-label">Province</label>
                        <input
                            type="text"
                            id="address_province"
                            name="address_province"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['address_province'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address_postal_code" class="form-label">Postal Code</label>
                        <input
                            type="text"
                            id="address_postal_code"
                            name="address_postal_code"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['address_postal_code'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <!-- Empty for grid alignment -->
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
                Save Changes
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

    .form-control:disabled {
        background-color: var(--light);
        cursor: not-allowed;
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
