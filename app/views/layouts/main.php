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
            height: 60px;
            min-height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            position: sticky;
            top: 0;
            z-index: 999;
            gap: 0.75rem;
        }
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
            flex-shrink: 1;
        }
        .navbar-page-title {
            font-size: 1.12rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .content-body {
            padding: 1.75rem;
            flex: 1;
        }
        .concern-pill, .super-pill {
            padding: 0.28rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
            flex-shrink: 0;
            line-height: 1.2;
        }
        .concern-pill {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .super-pill {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .concern-switcher-btn {
            font-size: 0.82rem;
            padding: 0.28rem 0.65rem;
            border-radius: 8px;
            white-space: nowrap;
            max-width: 220px;
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
                // If Finance group, merge 'cash' and 'cash_flow' into a single 'Cash & Cash Flow' module
                if ($groupName === 'Finance') {
                    $hasCashOrFlow = false;
                    $mergedMods = [];
                    foreach ($mods as $m) {
                        if ($m['code'] === 'cash') {
                            $hasCashOrFlow = true;
                            continue; // skip separate cash entry
                        }
                        if ($m['code'] === 'cash_flow') {
                            $hasCashOrFlow = true;
                            $m['name'] = 'Cash & Cash Flow';
                            $m['icon'] = 'bi-cash-coin';
                            $mergedMods[] = $m;
                            continue;
                        }
                        $mergedMods[] = $m;
                    }
                    if ($hasCashOrFlow && empty(array_filter($mergedMods, fn($item) => $item['code'] === 'cash_flow'))) {
                        $mergedMods[] = [
                            'code' => 'cash_flow',
                            'name' => 'Cash & Cash Flow',
                            'icon' => 'bi-cash-coin',
                            'group_name' => 'Finance'
                        ];
                    }
                    $mods = $mergedMods;
                }
            ?>
                <div class="nav-group-title"><?= htmlspecialchars($groupName) ?></div>
                <?php foreach ($mods as $m): 
                    $href = \App\Core\Controller::url('/m/' . $m['code']);
                    if ($m['code'] === 'admin_dashboard') $href = \App\Core\Controller::url('/admin/dashboard');
                    if ($m['code'] === 'admin_concerns') $href = \App\Core\Controller::url('/admin/concerns');
                    if ($m['code'] === 'admin_assets') $href = \App\Core\Controller::url('/admin/assets');
                    if ($m['code'] === 'admin_reports') $href = \App\Core\Controller::url('/admin/reports');
                    if ($m['code'] === 'concern_dashboard') $href = \App\Core\Controller::url('/dashboard');

                    if ($m['code'] === 'cash_flow') {
                        $isActive = (isset($currentModule) && in_array($currentModule, ['cash_flow', 'cash'])) || 
                                    (str_contains($activeRoute, 'cash'));
                    } else {
                        $isActive = (isset($currentModule) && $currentModule === $m['code']) || 
                                    (str_contains($activeRoute, $m['code']));
                    }
                ?>
                    <a href="<?= $href ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                        <i class="bi <?= htmlspecialchars($m['icon']) ?>"></i>
                        <span><?= htmlspecialchars($m['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="nav-group-title">Masters</div>
            <a href="<?= \App\Core\Controller::url('/m/variants') ?>" class="nav-link <?= (isset($currentModule) && $currentModule === 'variants') ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>
                <span>Product Variants</span>
            </a>
        </div>
    </nav>

    <!-- MAIN WRAPPER -->
    <div id="main-content">
        <!-- TOP NAVBAR -->
        <header class="top-navbar">
            <div class="navbar-left">
                <span class="navbar-page-title"><?= htmlspecialchars($pageTitle ?? 'Inventory') ?></span>
                
                <?php if ($isSuperAdmin): ?>
                    <span class="super-pill">
                        <i class="bi bi-shield-check"></i> Super Admin
                    </span>

                    <!-- CONCERN SWITCHER FOR SUPER ADMIN -->
                    <div class="dropdown">
                        <button class="btn btn-sm <?= $currentConcern ? 'btn-primary' : 'btn-outline-dark' ?> dropdown-toggle d-flex align-items-center gap-1.5 fw-semibold shadow-sm concern-switcher-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= $currentConcern ? htmlspecialchars($currentConcern['name']) . ' (' . htmlspecialchars($currentConcern['code']) . ')' : 'All Concerns (Consolidated View)' ?>">
                            <i class="bi bi-building"></i>
                            <span class="text-truncate" style="max-width: 155px;"><?= $currentConcern ? htmlspecialchars($currentConcern['name']) : 'All Concerns' ?></span>
                        </button>
                        <ul class="dropdown-menu shadow">
                            <li class="dropdown-header text-uppercase small fw-bold">Select Active Concern Context</li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= !$currentConcern ? 'active fw-bold' : '' ?>" href="<?= \App\Core\Controller::url('/admin/switch-concern?id=0') ?>">
                                    <span><i class="bi bi-globe me-2 text-primary"></i> All Concerns (Consolidated)</span>
                                    <?php if (!$currentConcern): ?><i class="bi bi-check-lg"></i><?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($allConcerns as $con): 
                                $isSel = ($currentConcern && $currentConcern['id'] == $con['id']);
                            ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between <?= $isSel ? 'active fw-bold' : '' ?>" href="<?= \App\Core\Controller::url('/admin/switch-concern?id=' . $con['id']) ?>">
                                        <span><i class="bi bi-building me-2 text-secondary"></i> <?= htmlspecialchars($con['name']) ?> (<?= htmlspecialchars($con['code']) ?>)</span>
                                        <?php if ($isSel): ?><i class="bi bi-check-lg"></i><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($currentConcern): ?>
                    <span class="concern-pill" title="Concern GSTIN: <?= htmlspecialchars($currentConcern['code']) ?>">
                        <i class="bi bi-building"></i> <span class="text-truncate" style="max-width: 160px;"><?= htmlspecialchars($currentConcern['name']) ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <div class="navbar-right">
                <!-- Global Export Reports Button & Dropdown -->
                <div class="btn-group">
                    <button class="btn btn-sm btn-success fw-bold d-flex align-items-center gap-1 shadow-sm px-2.5" type="button" data-bs-toggle="modal" data-bs-target="#globalExportModal" style="background: #16a34a; border: none; border-radius: 8px 0 0 8px; height: 32px; font-size: 0.82rem;">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </button>
                    <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split shadow-sm" data-bs-toggle="dropdown" aria-expanded="false" style="background: #15803d; border: none; border-radius: 0 8px 8px 0; height: 32px; font-size: 0.82rem;">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; font-size: 0.85rem; min-width: 275px;">
                        <li class="dropdown-header text-uppercase small fw-bold text-success">
                            <i class="bi bi-file-earmark-excel me-1"></i> Quick Excel Downloads (.xls)
                        </li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/admin/reports/export?format=excel&period=single_day') ?>"><span>Single Day (Today)</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/admin/reports/export?format=excel&period=this_month') ?>"><span>This Month</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/admin/reports/export?format=excel&period=3_months') ?>"><span>Particular 3 Months</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/admin/reports/export?format=excel&period=6_months') ?>"><span>Six Months</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/admin/reports/export?format=excel&period=all') ?>"><span>All Time (Consolidated)</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase small fw-bold text-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Quick PDF Printable Reports (.pdf)
                        </li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/admin/reports/export?format=pdf&period=single_day') ?>"><span>Single Day (Today)</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/admin/reports/export?format=pdf&period=this_month') ?>"><span>This Month</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/admin/reports/export?format=pdf&period=3_months') ?>"><span>Particular 3 Months</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/admin/reports/export?format=pdf&period=6_months') ?>"><span>Six Months</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                        <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/admin/reports/export?format=pdf&period=all') ?>"><span>All Time (Consolidated)</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-primary fw-semibold" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#globalExportModal"><i class="bi bi-sliders me-1"></i> Custom Range & Module Dialog &rarr;</a></li>
                    </ul>
                </div>

                <a href="<?= \App\Core\Controller::url('/m/label_generation') ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm px-2.5" style="height: 32px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="bi bi-qr-code-scan"></i> <span class="d-none d-sm-inline">Scan / Labels</span>
                </a>

                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-1.5 shadow-sm px-2.5" type="button" data-bs-toggle="dropdown" style="height: 32px; font-size: 0.82rem; border-radius: 8px;">
                        <i class="bi bi-person-circle fs-6 text-secondary"></i>
                        <span class="fw-medium text-truncate" style="max-width: 140px;"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
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
                    <div><?= nl2br(htmlspecialchars($flashSuccess)) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= nl2br(htmlspecialchars($flashError)) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTENT BODY -->
        <main class="content-body">
            <?php require $contentView; ?>
        </main>
    </div>

    <!-- GLOBAL EXPORT MODAL -->
    <div class="modal fade" id="globalExportModal" tabindex="-1" aria-labelledby="globalExportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-bottom px-4 py-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 38px; height: 38px; background: #16a34a; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="bi bi-file-earmark-arrow-down"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="globalExportModalLabel">Export Enterprise Report</h5>
                            <small class="text-muted">Download Excel (.xls) or PDF (.pdf) as per date scope</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="globalExportForm" onsubmit="event.preventDefault(); runGlobalModalExport();">
                        <!-- Module Selection -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Select Module / Report</label>
                            <select id="modal_export_module" class="form-select form-select-sm" style="border-radius: 8px; font-weight: 500;">
                                <option value="admin_reports" selected>📊 Consolidated Enterprise Performance Report</option>
                                <option value="cash_flow">💰 Cash & Cash Flow Statement & Ledger</option>
                                <option value="customers">👥 Customer Directory & CRM Profiles</option>
                                <option value="stock_ledger">📦 Operations & Stock Movement Ledger</option>
                            </select>
                        </div>

                        <!-- Date Scope / Period -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Date Period Scope</label>
                            <select id="modal_export_period" class="form-select form-select-sm" style="border-radius: 8px; font-weight: 500;">
                                <option value="single_day">Single Day</option>
                                <option value="this_month" selected>This Month (<?= date('F Y') ?>)</option>
                                <option value="last_month">Last Month</option>
                                <option value="3_months">Particular 3 Months</option>
                                <option value="6_months">Six Months</option>
                                <option value="custom">Custom Date Range</option>
                                <option value="all">All Time (Full Historical Data)</option>
                            </select>
                        </div>

                        <!-- Single Day Date Picker -->
                        <div class="mb-3 d-none" id="modal_single_date_box">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Select Single Date</label>
                            <input type="date" id="modal_single_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="border-radius: 8px;">
                        </div>

                        <!-- Custom Date Range Pickers -->
                        <div class="mb-3 d-none" id="modal_custom_date_box">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1">Custom Range (From - To)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="date" id="modal_from_date" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" style="border-radius: 8px;">
                                <span class="text-muted small">to</span>
                                <input type="date" id="modal_to_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" style="border-radius: 8px;">
                            </div>
                        </div>

                        <!-- Format Selection -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-2">Export Format</label>
                            <div class="d-flex gap-2">
                                <label class="btn btn-outline-success flex-fill d-flex align-items-center justify-content-center gap-2 py-2" style="border-radius: 10px; cursor: pointer;">
                                    <input type="radio" name="modal_export_format" value="excel" checked class="form-check-input me-1">
                                    <i class="bi bi-file-earmark-excel fs-5"></i> <strong>Excel (.xls)</strong>
                                </label>
                                <label class="btn btn-outline-danger flex-fill d-flex align-items-center justify-content-center gap-2 py-2" style="border-radius: 10px; cursor: pointer;">
                                    <input type="radio" name="modal_export_format" value="pdf" class="form-check-input me-1">
                                    <i class="bi bi-file-earmark-pdf fs-5"></i> <strong>PDF</strong>
                                </label>
                                <label class="btn btn-outline-secondary flex-fill d-flex align-items-center justify-content-center gap-2 py-2" style="border-radius: 10px; cursor: pointer;">
                                    <input type="radio" name="modal_export_format" value="csv" class="form-check-input me-1">
                                    <i class="bi bi-file-earmark-text fs-5"></i> <strong>CSV</strong>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" style="border-radius: 8px; background: #16a34a; border: none;">
                                <i class="bi bi-download me-1"></i> Download Export
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="<?= \App\Core\Controller::url('/assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= \App\Core\Controller::url('/assets/js/qrcode.min.js') ?>"></script>
    <script src="<?= \App\Core\Controller::url('/assets/js/html5-qrcode.min.js') ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodSelect = document.getElementById('modal_export_period');
        const singleBox = document.getElementById('modal_single_date_box');
        const customBox = document.getElementById('modal_custom_date_box');

        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                const val = this.value;
                if (val === 'single_day') {
                    if (singleBox) singleBox.classList.remove('d-none');
                    if (customBox) customBox.classList.add('d-none');
                } else if (val === 'custom') {
                    if (customBox) customBox.classList.remove('d-none');
                    if (singleBox) singleBox.classList.add('d-none');
                } else {
                    if (singleBox) singleBox.classList.add('d-none');
                    if (customBox) customBox.classList.add('d-none');
                }
            });
        }
    });

    function runGlobalModalExport() {
        const moduleVal = document.getElementById('modal_export_module').value;
        const period = document.getElementById('modal_export_period').value;
        const singleDate = document.getElementById('modal_single_date') ? document.getElementById('modal_single_date').value : '';
        const fromDate = document.getElementById('modal_from_date') ? document.getElementById('modal_from_date').value : '';
        const toDate = document.getElementById('modal_to_date') ? document.getElementById('modal_to_date').value : '';
        const formatRadio = document.querySelector('input[name="modal_export_format"]:checked');
        const format = formatRadio ? formatRadio.value : 'excel';

        let baseUrl = '';
        if (moduleVal === 'cash_flow') {
            baseUrl = '<?= \App\Core\Controller::url('/m/cash_flow/export') ?>';
        } else if (moduleVal === 'customers') {
            baseUrl = '<?= \App\Core\Controller::url('/m/customers/export') ?>';
        } else if (moduleVal === 'stock_ledger') {
            baseUrl = '<?= \App\Core\Controller::url('/dashboard/export') ?>';
        } else {
            baseUrl = '<?= \App\Core\Controller::url('/admin/reports/export') ?>';
        }

        const url = baseUrl +
                    '?format=' + encodeURIComponent(format) +
                    '&period=' + encodeURIComponent(period) +
                    '&single_date=' + encodeURIComponent(singleDate) +
                    '&from_date=' + encodeURIComponent(fromDate) +
                    '&to_date=' + encodeURIComponent(toDate);

        // Hide modal
        const modalEl = document.getElementById('globalExportModal');
        if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        }

        if (format === 'pdf') {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }
    </script>
</body>
</html>
