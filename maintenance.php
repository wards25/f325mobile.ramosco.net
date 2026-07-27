<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: #f8f5fb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        /* Animated background blobs */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }

        .bg-blob-1 {
            width: 500px;
            height: 500px;
            background: #915c83;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .bg-blob-2 {
            width: 400px;
            height: 400px;
            background: #c49ab8;
            bottom: -80px;
            right: -80px;
            animation-delay: 3s;
        }

        .bg-blob-3 {
            width: 300px;
            height: 300px;
            background: #7a4a6e;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 1.5s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-30px) scale(1.05);
            }
        }

        .maintenance-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(145, 92, 131, 0.15);
            padding: 60px 50px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
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

        .icon-wrap {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f3ebf7, #e8d5f0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(145, 92, 131, 0.2);
            }

            50% {
                box-shadow: 0 0 0 16px rgba(145, 92, 131, 0);
            }
        }

        .icon-wrap i {
            font-size: 42px;
            color: #915c83;
        }

        .badge {
            display: inline-block;
            background: #f3ebf7;
            color: #915c83;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d1f2e;
            margin-bottom: 14px;
            line-height: 1.3;
        }

        .module-name {
            display: inline-block;
            background: linear-gradient(135deg, #915c83, #7a4a6e);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 5px 16px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        p {
            font-size: 14px;
            color: #888;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .divider {
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #915c83, #c49ab8);
            border-radius: 2px;
            margin: 0 auto 32px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #915c83, #7a4a6e);
            color: #fff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(145, 92, 131, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(145, 92, 131, 0.4);
            color: #fff;
            text-decoration: none;
        }

        .footer-note {
            margin-top: 28px;
            font-size: 12px;
            color: #bbb;
        }

        .gear-spin {
            display: inline-block;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <!-- Background blobs -->
    <div class="bg-blob bg-blob-1"></div>
    <div class="bg-blob bg-blob-2"></div>
    <div class="bg-blob bg-blob-3"></div>

    <div class="maintenance-card">
        <div class="icon-wrap">
            <i class="fas fa-tools"></i>
        </div>

        <div class="badge">
            <span class="gear-spin"><i class="fas fa-cog"></i></span>
            &nbsp; Under Maintenance
        </div>

        <h1>We'll Be Back Soon</h1>

        <?php if (!empty($moduleName)): ?>
            <div class="module-name">
                <i class="fas fa-cube"></i> <?php echo htmlspecialchars($moduleName); ?>
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <p><?php echo htmlspecialchars($maintenanceMessage ?? 'This module is currently under maintenance. Our team is working hard to get things back up. Please try again later.'); ?>
        </p>

        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="footer-note">
            If you need immediate assistance, please contact your system administrator.
        </div>
    </div>

</body>

</html>