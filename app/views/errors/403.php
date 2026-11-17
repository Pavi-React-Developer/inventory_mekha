<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - INVENTO Multi-Concern</title>
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
    <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3">
        <i class="bi bi-shield-slash fs-1"></i>
    </div>
    <h3 class="fw-bold text-dark">403 - Access Forbidden</h3>
    <p class="text-secondary mt-2">
        <?= htmlspecialchars($message ?? 'This module is disabled for your organization, or your account does not have sufficient role permissions.') ?>
    </p>
    <div class="alert alert-warning py-2 small text-start mt-3">
        <i class="bi bi-info-circle-fill me-1"></i>
        <strong>Multi-Concern Policy:</strong> Each concern only has access to explicitly licensed modules assigned by the Super Administrator.
    </div>
    <div class="mt-4">
        <a href="<?= \App\Core\Controller::url('/dashboard') ?>" class="btn btn-primary px-4">
            <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
        </a>
    </div>
</div>

</body>
</html>
