<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limit Exceeded - Sci-Bono Clubhouse LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 50px 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .code {
            font-size: 100px;
            font-weight: bold;
            color: #ffc107;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .message {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin: 20px 0;
        }

        .description {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin: 25px 0;
        }

        .wait-time {
            font-size: 18px;
            color: #F29A2E;
            font-weight: 600;
            background: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #F29A2E;
        }

        .tips {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .tips h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .tips ul {
            list-style: none;
            padding-left: 0;
        }

        .tips li {
            padding: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
        }

        .tips li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }

        .back-link {
            display: inline-block;
            background: linear-gradient(135deg, #F29A2E 0%, #e08916 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(242, 154, 46, 0.3);
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(242, 154, 46, 0.4);
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .code {
                font-size: 70px;
            }

            .message {
                font-size: 22px;
            }

            .error-icon {
                font-size: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">⏳</div>
        <h1 class="code">429</h1>
        <h2 class="message">Rate Limit Exceeded</h2>

        <div class="wait-time">
            ⏱ Please wait <?php echo isset($minutes) ? $minutes : 1; ?> minute<?php echo ($minutes ?? 1) > 1 ? 's' : ''; ?> before trying again
        </div>

        <p class="description">
            You have made too many requests in a short period of time.
            This is a security measure to protect our system from abuse and ensure fair access for all users.
        </p>

        <div class="tips">
            <h3>💡 Helpful Tips:</h3>
            <ul>
                <li>Wait for the specified time before retrying</li>
                <li>Check that you're not submitting forms multiple times</li>
                <li>If using automation, slow down your request rate</li>
                <li>Contact support if you believe this is an error</li>
            </ul>
        </div>

        <a href="/Sci-Bono_Clubhoue_LMS/" class="back-link">← Return to Homepage</a>

        <div class="footer">
            Sci-Bono Clubhouse LMS &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>
