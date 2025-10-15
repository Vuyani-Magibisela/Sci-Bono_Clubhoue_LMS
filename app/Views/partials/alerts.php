<?php
/**
 * Flash Messages Partial
 * Displays success and error messages from session
 */
?>

<?php if (isset($error) && $error): ?>
<div class="alert alert-error" role="alert">
    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
        <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    <span><?php echo htmlspecialchars($error); ?></span>
</div>
<?php endif; ?>

<?php if (isset($success) && $success): ?>
<div class="alert alert-success" role="alert">
    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
        <path d="M8 12L11 15L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span><?php echo htmlspecialchars($success); ?></span>
</div>
<?php endif; ?>

<style>
.alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.5;
}

.alert-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.alert-error {
    background-color: #FEE2E2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
}

.alert-error .alert-icon {
    stroke: #DC2626;
}

.alert-success {
    background-color: #D1FAE5;
    border: 1px solid #6EE7B7;
    color: #065F46;
}

.alert-success .alert-icon {
    stroke: #10B981;
}

@media (max-width: 768px) {
    .alert {
        font-size: 13px;
        padding: 12px;
    }

    .alert-icon {
        width: 20px;
        height: 20px;
    }
}
</style>
