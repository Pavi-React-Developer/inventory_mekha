<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> - INVENTO Multi-Concern</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/reports.css') ?>">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --sidebar-width: 260px;
        }
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }
        #sidebar .brand-header {
            padding: 1.25rem 1.5rem;
            background: #020617;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        #sidebar .nav-group-title {
            padding: 0.75rem 1.5rem 0.25rem;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            font-weight: 700;
        }
        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.55rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
        }
        #sidebar .nav-link:hover {
            color: #ffffff;
            background: var(--sidebar-hover);
        }
        #sidebar .nav-link.active {
            color: #ffffff;
            background: var(--sidebar-active);
            font-weight: 600;
        }
        #sidebar .nav-link i {
            font-size: 1.1rem;
        }
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-navbar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .content-body {
            padding: 1.75rem;
            flex: 1;
        }
        .concern-pill {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .super-pill {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .card-stat {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 1.25rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .table-custom th {
            background: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <nav id="sidebar">
        <div class="brand-header d-flex align-items-center justify-content-between">
            <a href="<?= \App\Core\Controller::url('/') ?>" class="text-white text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary fs-4"></i>
                <div>
                    <div class="fw-bold tracking-tight lh-1 text-white">INVENTO</div>
                    <small class="text-secondary" style="font-size: 0.68rem; letter-spacing: 0.05em;">MULTI-CONCERN ERP</small>
                </div>
            </a>
        </div>

        <div class="py-2">
            <?php
            $activeRoute = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $grouped = [];
            foreach ($sidebarModules as $m) {
                $grouped[$m['group_name']][] = $m;
            }

            foreach ($grouped as $groupName => $mods):
            ?>
                <div class="nav-group-title"><?= htmlspecialchars($groupName) ?></div>
                <?php foreach ($mods as $m): 
                    $href = \App\Core\Controller::url('/m/' . $m['code']);
                    if ($m['code'] === 'admin_dashboard') $href = \App\Core\Controller::url('/admin/dashboard');
                    if ($m['code'] === 'admin_concerns') $href = \App\Core\Controller::url('/admin/concerns');
                    if ($m['code'] === 'admin_assets') $href = \App\Core\Controller::url('/admin/assets');
                    if ($m['code'] === 'admin_reports') $href = \App\Core\Controller::url('/admin/reports');
                    if ($m['code'] === 'concern_dashboard') $href = \App\Core\Controller::url('/dashboard');

                    $isActive = (isset($currentModule) && $currentModule === $m['code']) || 
                                (str_contains($activeRoute, $m['code']));
                ?>
                    <a href="<?= $href ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                        <i class="bi <?= htmlspecialchars($m['icon']) ?>"></i>
                        <span><?= htmlspecialchars($m['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if (!$isSuperAdmin): ?>
                <div class="nav-group-title">Masters</div>
                <a href="<?= \App\Core\Controller::url('/m/variants') ?>" class="nav-link <?= (isset($currentModule) && $currentModule === 'variants') ? 'active' : '' ?>">
                    <i class="bi bi-tags"></i>
                    <span>Product Variants</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- MAIN WRAPPER -->
    <div id="main-content">
        <!-- TOP NAVBAR -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-5 fw-semibold text-dark"><?= htmlspecialchars($pageTitle ?? 'Inventory') ?></span>
                
                <?php if ($isSuperAdmin): ?>
                    <span class="super-pill">
                        <i class="bi bi-shield-check"></i> Super Administrator
                    </span>
                <?php elseif ($currentConcern): ?>
                    <span class="concern-pill" title="Concern GSTIN: <?= htmlspecialchars($currentConcern['code']) ?>">
                        <i class="bi bi-building"></i> <?= htmlspecialchars($currentConcern['name']) ?> (<?= htmlspecialchars($currentConcern['code']) ?>)
                    </span>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-3">
                <?php if (!$isSuperAdmin): ?>
                    <a href="<?= \App\Core\Controller::url('/m/label_generation') ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                        <i class="bi bi-qr-code-scan"></i> Scan / Labels
                    </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-6"></i>
                        <span class="fw-medium"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header text-muted small"><?= htmlspecialchars($currentUser['email'] ?? '') ?></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= \App\Core\Controller::url('/logout') ?>">
                                <i class="bi bi-box-arrow-right"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- FLASH NOTIFICATIONS -->
        <div class="px-4 pt-3">
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($flashSuccess) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($flashError) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTENT BODY -->
        <main class="content-body">
            <?php require $contentView; ?>
        </main>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="<?= \App\Core\Controller::url('/assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= \App\Core\Controller::url('/assets/js/qrcode.min.js') ?>"></script>
    <script src="<?= \App\Core\Controller::url('/assets/js/html5-qrcode.min.js') ?>"></script>
</body>
</html>
