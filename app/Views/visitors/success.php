<?php
/**
 * Registration Success Page
 * Phase 3: Week 6-7 Implementation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .container { background: white; max-width: 500px; width: 100%; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 3rem; text-align: center; }
        .success-icon { width: 100px; height: 100px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; }
        .success-icon i { color: white; font-size: 3rem; }
        h1 { color: #1c1e21; font-size: 2rem; margin-bottom: 1rem; }
        p { color: #65676b; line-height: 1.6; margin-bottom: 2rem; }
        .info-box { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; text-align: left; }
        .info-box h3 { color: #1c1e21; margin-bottom: 1rem; font-size: 1rem; }
        .info-box ul { list-style: none; }
        .info-box li { padding: 0.5rem 0; color: #65676b; display: flex; align-items: center; gap: 0.75rem; }
        .info-box li i { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Registration Successful!</h1>
        <p>Thank you for registering your visit to Sci-Bono Clubhouse. You are now checked in.</p>

        <div class="info-box">
            <h3>What's Next?</h3>
            <ul>
                <li><i class="fas fa-check-circle"></i> Proceed to reception for assistance</li>
                <li><i class="fas fa-check-circle"></i> Your visit has been recorded</li>
                <li><i class="fas fa-check-circle"></i> Please check out when you leave</li>
            </ul>
        </div>

        <p style="font-size: 0.875rem; color: #65676b;">
            Enjoy your visit! If you need any assistance, please don't hesitate to ask our staff.
        </p>

        <div style="margin-top: 2rem;">
            <a href="/visitor/register" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
                <i class="fas fa-arrow-left"></i> Register Another Visitor
            </a>
        </div>
    </div>
</body>
</html>
