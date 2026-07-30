<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Page Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand: #4f46e5;
            --brand-light: #eef0fe;
            --page-bg: #f4f5fb;
            --text-muted: #8a8fa3;
            --line: #eceef5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--page-bg);
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 1.5rem;
        }

        .notfound-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
            max-width: 480px;
            width: 100%;
            padding: 3rem 2.5rem;
            text-align: center;
        }

        .notfound-icon {
            width: 84px;
            height: 84px;
            margin: 0 auto 1.5rem;
            border-radius: 1.1rem;
            background: var(--brand-light);
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.1rem;
        }

        .notfound-code {
            font-weight: 800;
            font-size: 3rem;
            letter-spacing: -0.03em;
            color: #1f2130;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .notfound-title {
            font-weight: 700;
            font-size: 1.15rem;
            color: #1f2130;
            margin-bottom: 0.5rem;
        }

        .notfound-message {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .notfound-actions {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-notfound {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 0.6rem;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.65rem 1.4rem;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-notfound-primary {
            background-color: var(--brand);
            color: #fff;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }

        .btn-notfound-primary:hover {
            background-color: #4338ca;
            color: #fff;
        }

        .btn-notfound-secondary {
            background: #fff;
            border-color: #e3e5ef;
            color: #4a4c5a;
        }

        .btn-notfound-secondary:hover {
            background-color: #f6f7fb;
            color: #2d2f3a;
        }
    </style>
</head>
<body>

    <div class="notfound-card">
        <div class="notfound-icon">
            <i class="bi bi-signpost-split"></i>
        </div>
        <div class="notfound-code">404</div>
        <div class="notfound-title">Page not found</div>
        <p class="notfound-message">
            The page you're looking for doesn't exist, may have moved,
            or you don't have access to it.
        </p>
        <div class="notfound-actions">
            <a href="javascript:history.back()" class="btn-notfound btn-notfound-secondary">
                <i class="bi bi-arrow-left"></i> Go back
            </a>
        </div>
    </div>

</body>
</html>