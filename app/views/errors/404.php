<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - GRM Inventory</title>
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .error-card {
            max-width: 500px;
            text-align: center;
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
        <i class="bi bi-search fs-1"></i>
    </div>
    <h3 class="fw-bold text-dark">404 - Record or Page Not Found</h3>
    <p class="text-secondary mt-2">
        The requested record or endpoint does not exist, or belongs to another concern under strict tenant isolation.
    </p>
    <div class="mt-4">
        <a href="<?= \App\Core\Controller::url('/') ?>" class="btn btn-primary px-4">
            <i class="bi bi-house me-1"></i> Return Home
        </a>
    </div>
</div>

</body>
</html>
