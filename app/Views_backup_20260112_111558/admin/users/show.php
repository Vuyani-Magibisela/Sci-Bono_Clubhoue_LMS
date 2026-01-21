<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/users">Users</a>
    <span class="breadcrumb-separator">/</span>
    <span>User Details</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
        </h1>
        <p style="color: var(--gray-medium); margin-top: 5px;">
            <span class="badge badge-<?php
                echo $user['user_type'] === 'admin' ? 'danger' :
                     ($user['user_type'] === 'mentor' ? 'warning' :
                     ($user['user_type'] === 'member' ? 'success' : 'primary'));
            ?>">
                <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?>
            </span>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <?php if ($_SESSION['user_type'] === 'admin' ||
                  ($_SESSION['user_type'] === 'mentor' && $user['user_type'] === 'member') ||
                  $_SESSION['user_id'] == $user['id']): ?>
            <a href="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>/edit" class="btn-primary">
                <i class="fas fa-edit"></i>
                Edit User
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['user_type'] === 'admin' && $_SESSION['user_id'] != $user['id']): ?>
            <form method="POST"
                  action="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>/delete"
                  style="display: inline;"
                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                <?php echo CSRF::field(); ?>
                <button type="submit" class="btn-danger" style="background: var(--danger);">
                    <i class="fas fa-trash"></i>
                    Delete User
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- User Details -->
<div class="details-container">
    <!-- Personal Information Section -->
    <div class="details-section">
        <div class="details-section-header">
            <h3 class="details-section-title">
                <i class="fas fa-user"></i>
                Personal Information
            </h3>
        </div>
        <div class="details-section-content">
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">User ID</div>
                    <div class="detail-value">#<?php echo htmlspecialchars($user['id']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['Gender'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value">
                        <?php
                        if (!empty($user['date_of_birth'])) {
                            echo date('M d, Y', strtotime($user['date_of_birth']));
                            $age = date_diff(date_create($user['date_of_birth']), date_create('today'))->y;
                            echo " <span style='color: var(--gray-medium);'>({$age} years old)</span>";
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Nationality</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['nationality'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">ID Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['id_number'] ?? 'Not specified'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Details Section -->
    <div class="details-section">
        <div class="details-section-header">
            <h3 class="details-section-title">
                <i class="fas fa-key"></i>
                Account Details
            </h3>
        </div>
        <div class="details-section-content">
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Username</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">
                        <?php if (!empty($user['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" style="color: var(--primary);">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </a>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value">
                        <?php if (!empty($user['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($user['phone']); ?>" style="color: var(--primary);">
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </a>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Center</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['Center'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Registration Date</div>
                    <div class="detail-value">
                        <?php
                        if (!empty($user['date_of_registration'])) {
                            echo date('M d, Y', strtotime($user['date_of_registration']));
                        } else {
                            echo 'Unknown';
                        }
                        ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Account Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-active">
                            <i class="fas fa-check-circle"></i>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information Section -->
    <?php if (!empty($user['address_street']) || !empty($user['address_city'])): ?>
    <div class="details-section">
        <div class="details-section-header">
            <h3 class="details-section-title">
                <i class="fas fa-map-marker-alt"></i>
                Address Information
            </h3>
        </div>
        <div class="details-section-content">
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Street Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['address_street'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Suburb</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['address_suburb'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">City</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['address_city'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Province</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['address_province'] ?? 'Not specified'); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Postal Code</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['address_postal_code'] ?? 'Not specified'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Additional Styles -->
<style>
    .details-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-width: 1000px;
    }

    .details-section {
        background: white;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .details-section-header {
        background-color: var(--light);
        padding: 15px 20px;
        border-bottom: 1px solid var(--gray-light);
    }

    .details-section-title {
        margin: 0;
        font-size: 1.1rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .details-section-content {
        padding: 25px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray-medium);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 1rem;
        color: var(--dark);
        font-weight: 500;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-success {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success);
    }

    .badge-danger {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger);
    }

    .badge-warning {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning);
    }

    .badge-primary {
        background-color: rgba(81, 70, 230, 0.1);
        color: var(--primary);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-active {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success);
    }

    .btn-danger {
        background-color: var(--danger);
        color: white;
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

    .btn-danger:hover {
        background-color: #c0392b;
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }

    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .page-header > div:last-child {
            width: 100%;
        }

        .page-header > div:last-child form,
        .page-header > div:last-child a {
            width: 100%;
        }
    }
</style>
