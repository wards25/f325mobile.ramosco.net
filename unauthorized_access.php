<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            height: 100vh;
            background: linear-gradient(135deg, #f8f9fa, #dee2e6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .unauth-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            padding: 40px;
            max-width: 400px;
        }

        .unauth-logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .unauth-title {
            font-size: 1.5rem;
            font-weight: 500;
            color: #dc3545;
        }

        .unauth-text {
            color: #6c757d;
            margin-bottom: 25px;
        }

        .btn-home {
            border-radius: 30px;
            font-weight: 500;
            padding: 10px 25px;
        }
    </style>
</head>
<body>

    <div class="unauth-card">
        <!-- Replace logo.png with your actual logo file -->
        <img src="img/rgc.png" alt="Logo" class="unauth-logo">
        
        <h1 class="unauth-title"><i class="bi bi-shield-lock-fill me-2"></i> Unauthorized Access</h1>
        <p class="unauth-text">Sorry, you don’t have permission to view this page.<br>Please contact your administrator if you believe this is a mistake.</p>

        <a href="dashboard.php" class="btn btn-warning btn-home">
            <i class="bi bi-house-door-fill me-2"></i> Back to Dashboard
        </a>
    </div>

</body>
</html>
