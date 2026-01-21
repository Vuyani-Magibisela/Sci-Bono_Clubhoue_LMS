<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Users</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p style="color: var(--gray-medium); margin-top: 5px;">
            Manage all system users - Total: <?php echo number_format($totalUsers); ?> users
        </p>
    </div>
    <?php if ($_SESSION['user_type'] === 'admin'): ?>
        <a href="<?php echo BASE_URL; ?>admin/users/create" class="btn-primary">
            <i class="fas fa-user-plus"></i>
            Add User
        </a>
    <?php endif; ?>
</div>

<!-- Search and Filter Bar -->
<div class="filter-bar" style="background: white; padding: 20px; border-radius: var(--border-radius-md); box-shadow: var(--shadow-sm); margin-bottom: 20px;">
    <form method="GET" action="<?php echo BASE_URL; ?>admin/users" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <input
                type="text"
                name="search"
                placeholder="Search by name, username, or email..."
                value="<?php echo htmlspecialchars($search); ?>"
                class="form-control"
                style="width: 100%; padding: 10px 15px; border: 1px solid var(--gray-light); border-radius: var(--border-radius-sm); font-size: 0.95rem;">
        </div>

        <div style="min-width: 150px;">
            <select name="role" class="form-control" style="width: 100%; padding: 10px 15px; border: 1px solid var(--gray-light); border-radius: var(--border-radius-sm); font-size: 0.95rem;">
                <option value="">All Roles</option>
                <option value="member" <?php echo $roleFilter === 'member' ? 'selected' : ''; ?>>Member</option>
                <option value="mentor" <?php echo $roleFilter === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="parent" <?php echo $roleFilter === 'parent' ? 'selected' : ''; ?>>Parent</option>
                <option value="project officer" <?php echo $roleFilter === 'project officer' ? 'selected' : ''; ?>>Project Officer</option>
                <option value="manager" <?php echo $roleFilter === 'manager' ? 'selected' : ''; ?>>Manager</option>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="white-space: nowrap;">
            <i class="fas fa-search"></i>
            Search
        </button>

        <?php if (!empty($search) || !empty($roleFilter)): ?>
            <a href="<?php echo BASE_URL; ?>admin/users" class="btn-secondary" style="background: var(--gray-light); color: var(--dark); padding: 10px 15px; border-radius: var(--border-radius-sm); text-decoration: none; white-space: nowrap;">
                <i class="fas fa-times"></i>
                Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<?php if (!empty($users)): ?>
    <div class="user-list-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Center</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-<?php
                                echo $user['user_type'] === 'admin' ? 'danger' :
                                     ($user['user_type'] === 'mentor' ? 'warning' :
                                     ($user['user_type'] === 'member' ? 'success' : 'primary'));
                            ?>">
                                <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['Center'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            if (isset($user['date_of_registration'])) {
                                echo date('M d, Y', strtotime($user['date_of_registration']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>"
                                   class="action-btn view-btn"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>

                                <?php if ($_SESSION['user_type'] === 'admin' ||
                                          ($_SESSION['user_type'] === 'mentor' && $user['user_type'] === 'member') ||
                                          $_SESSION['user_id'] == $user['id']): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>/edit"
                                       class="action-btn edit-btn"
                                       title="Edit User">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                <?php endif; ?>

                                <?php if ($_SESSION['user_type'] === 'admin' && $_SESSION['user_id'] != $user['id']): ?>
                                    <form method="POST"
                                          action="<?php echo BASE_URL; ?>admin/users/<?php echo $user['id']; ?>/delete"
                                          style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <?php echo CSRF::field(); ?>
                                        <button type="submit"
                                                class="action-btn delete-btn"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
            <?php if ($currentPageNum > 1): ?>
                <a href="<?php echo BASE_URL; ?>admin/users?page=<?php echo $currentPageNum - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?>"
                   class="pagination-btn">
                    <i class="fas fa-chevron-left"></i>
                    Previous
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $currentPageNum - 2); $i <= min($totalPages, $currentPageNum + 2); $i++): ?>
                <a href="<?php echo BASE_URL; ?>admin/users?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?>"
                   class="pagination-btn <?php echo $i === $currentPageNum ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPageNum < $totalPages): ?>
                <a href="<?php echo BASE_URL; ?>admin/users?page=<?php echo $currentPageNum + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?>"
                   class="pagination-btn">
                    Next
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fas fa-users" style="font-size: 4rem; color: var(--gray-light); margin-bottom: 15px;"></i>
        <h3 style="color: var(--gray-medium); margin-bottom: 10px;">No Users Found</h3>
        <?php if (!empty($search) || !empty($roleFilter)): ?>
            <p style="color: var(--gray-medium); margin-bottom: 20px;">
                Try adjusting your search criteria
            </p>
            <a href="<?php echo BASE_URL; ?>admin/users" class="btn-primary">
                <i class="fas fa-redo"></i>
                Clear Filters
            </a>
        <?php else: ?>
            <p style="color: var(--gray-medium); margin-bottom: 20px;">
                Get started by creating your first user
            </p>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/users/create" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Add User
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Additional Styles -->
<style>
    .filter-bar .form-control {
        background-color: white;
        cursor: pointer;
    }

    .filter-bar .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(81, 70, 230, 0.1);
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
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

    .pagination-btn {
        padding: 8px 14px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        color: var(--dark);
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .pagination-btn:hover {
        background-color: var(--light);
        border-color: var(--primary);
        color: var(--primary);
    }

    .pagination-btn.active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .btn-secondary {
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background-color: var(--gray-medium) !important;
        color: white !important;
    }
</style>
