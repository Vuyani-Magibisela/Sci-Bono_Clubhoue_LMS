<?php
/**
 * Session Test Script
 */

// Start session
session_start();

// Check if this is a reload
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    $message = "Session started. Counter initialized to 1.";
} else {
    $_SESSION['test_counter']++;
    $message = "Session exists! Counter is now: " . $_SESSION['test_counter'];
}

// Display session info
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 18px; font-weight: bold; }
        .error { color: #dc3545; font-size: 18px; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        table td { padding: 10px; border: 1px solid #ddd; }
        table td:first-child { font-weight: bold; background: #f8f9fa; width: 40%; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Session Test</h1>

        <div class="info">
            <p class="<?php echo isset($_SESSION['test_counter']) && $_SESSION['test_counter'] > 1 ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </p>
        </div>

        <h2>Session Information</h2>
        <table>
            <tr>
                <td>Session ID</td>
                <td><?php echo session_id(); ?></td>
            </tr>
            <tr>
                <td>Session Status</td>
                <td><?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></td>
            </tr>
            <tr>
                <td>Session Counter</td>
                <td><?php echo $_SESSION['test_counter'] ?? 'Not set'; ?></td>
            </tr>
            <tr>
                <td>HTTPS Enabled</td>
                <td><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <td>Cookie Secure Setting</td>
                <td><?php echo ini_get('session.cookie_secure') ? 'Enabled (requires HTTPS)' : 'Disabled (works on HTTP)'; ?></td>
            </tr>
            <tr>
                <td>Cookie SameSite</td>
                <td><?php echo ini_get('session.cookie_samesite') ?: 'Not set'; ?></td>
            </tr>
            <tr>
                <td>Session Save Path</td>
                <td><?php echo session_save_path(); ?></td>
            </tr>
        </table>

        <h2>Instructions</h2>
        <p>Refresh this page. If the counter increases, sessions are working correctly.</p>
        <p>If the counter stays at 1 after refresh, there's a session configuration problem.</p>

        <a href="test_session.php" class="btn">Refresh Page</a>
        <a href="test_session.php?clear=1" class="btn" style="background: #dc3545;">Clear Session</a>

        <?php if (isset($_GET['clear'])): ?>
            <?php session_destroy(); ?>
            <p style="color: #dc3545; margin-top: 20px;">Session cleared! Refresh to start a new session.</p>
        <?php endif; ?>
    </div>
</body>
</html>
