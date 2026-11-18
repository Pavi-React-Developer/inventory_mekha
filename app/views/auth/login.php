<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - INVENTO Multi-Concern Inventory</title>
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 1rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }
        .login-header {
            background: #020617;
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #ffffff;
        }
        .demo-pill {
            cursor: pointer;
            font-size: 0.78rem;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            transition: all 0.2s;
        }
        .demo-pill:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2">
            <i class="bi bi-box-seam-fill fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1 tracking-tight">INVENTO</h4>
        <p class="text-secondary small mb-0">Multi-Concern Enterprise Inventory System</p>
    </div>

    <div class="p-4 p-md-4">
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div><?= htmlspecialchars($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success py-2 small d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= \App\Core\Controller::url('/login') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="name@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" required placeholder="Enter password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePasswordVisibility" aria-label="Show password" aria-controls="password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                Sign In to Workspace <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <div class="text-muted small fw-semibold mb-2 text-center text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                Quick Demo Accounts (Click to Fill)
            </div>
            
            <div class="d-flex flex-column gap-2">
                <div class="demo-pill d-flex justify-content-between align-items-center" onclick="fillLogin('admin@invento.com', 'password123')">
                    <div>
                        <strong class="text-danger"><i class="bi bi-shield-lock"></i> Super Admin</strong>
                        <div class="text-muted" style="font-size: 0.72rem;">All Concerns & Assets</div>
                    </div>
                    <span class="badge bg-light text-dark border">admin@invento.com</span>
                </div>

                <div class="demo-pill d-flex justify-content-between align-items-center" onclick="fillLogin('grm.admin@invento.com', 'password123')">
                    <div>
                        <strong class="text-primary"><i class="bi bi-building"></i> Concern 1: GRM Garments</strong>
                        <div class="text-muted" style="font-size: 0.72rem;">All 22 Modules Active</div>
                    </div>
                    <span class="badge bg-light text-dark border">grm.admin@invento.com</span>
                </div>

                <div class="demo-pill d-flex justify-content-between align-items-center" onclick="fillLogin('apex.admin@invento.com', 'password123')">
                    <div>
                        <strong class="text-success"><i class="bi bi-building"></i> Concern 2: Apex Fashion</strong>
                        <div class="text-muted" style="font-size: 0.72rem;">Limited Modules (Isolation demo)</div>
                    </div>
                    <span class="badge bg-light text-dark border">apex.admin@invento.com</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}

document.getElementById('togglePasswordVisibility').addEventListener('click', function () {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    const isVisible = password.type === 'password';

    password.type = isVisible ? 'text' : 'password';
    this.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    this.setAttribute('aria-pressed', String(isVisible));
    icon.classList.toggle('bi-eye', !isVisible);
    icon.classList.toggle('bi-eye-slash', isVisible);
    password.focus();
});
</script>

</body>
</html>
