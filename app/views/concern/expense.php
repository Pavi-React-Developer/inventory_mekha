<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <div class="d-flex align-items-center gap-1 text-secondary small mb-2">
        <a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a>
        <span class="text-muted">/</span>
        <span class="text-muted">Warehouse</span>
        <span class="text-muted">/</span>
        <span class="text-dark fw-semibold">Operational Expenses</span>
    </div>

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
        <div>
            <div class="mb-2">
                <span class="badge rounded-pill px-3 py-1 text-uppercase fw-bold" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; font-size: 0.72rem; letter-spacing: 0.04em;">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Warehouse Operations
                </span>
            </div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; background: #e0e7ff; color: #4338ca; border-radius: 8px; font-size: 1.15rem;">
                    <i class="bi bi-wallet2"></i>
                </span>
                Warehouse Operational Expenses & Petty Cash
            </h4>
            <p class="text-secondary small mb-0">Manage carton boxes, freight transport, forklift maintenance, utility bills, and petty cash vouchers</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Record Expense Button -->
            <button class="btn text-white fw-bold shadow-sm d-flex align-items-center gap-2 px-3 py-2" style="background: #4f46e5; border-radius: 8px; font-size: 0.88rem;" data-bs-toggle="modal" data-bs-target="#modalAddExpense">
                <i class="bi bi-plus-circle"></i> Record Warehouse Expense
            </button>

            <!-- Reports Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary shadow-sm fw-semibold d-flex align-items-center gap-2 px-3 py-2 bg-white" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 8px; font-size: 0.88rem; border-color: #cbd5e1;">
                    <i class="bi bi-bar-chart-line text-secondary"></i> Warehouse Reports
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; font-size: 0.85rem; min-width: 230px;">
                    <li class="dropdown-header text-uppercase small fw-bold text-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel Spreadsheets
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/expense/export?format=excel&from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&category=' . urlencode($category) . '&payment_mode=' . urlencode($payMode) . '&search=' . urlencode($search) . '&concern_id=' . urlencode($filterConcernId ?? '')) ?>">
                            <span>Export Current Filter</span>
                            <span class="badge bg-success bg-opacity-10 text-success border">.xls</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header text-uppercase small fw-bold text-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Printable Documents
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/expense/export?format=pdf&from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&category=' . urlencode($category) . '&payment_mode=' . urlencode($payMode) . '&search=' . urlencode($search) . '&concern_id=' . urlencode($filterConcernId ?? '')) ?>">
                            <span>Print Voucher Register</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border">.pdf</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/expense/export?format=csv&from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&category=' . urlencode($category) . '&payment_mode=' . urlencode($payMode) . '&search=' . urlencode($search) . '&concern_id=' . urlencode($filterConcernId ?? '')) ?>">
                            <span>Raw Data CSV</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">.csv</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Warehouse Outflow -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border: 1px solid #bbf7d0 !important; border-left: 4px solid #10b981 !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase fw-bold small mb-1" style="color: #059669; font-size: 0.74rem; letter-spacing: 0.04em;">
                            Total Warehouse Outflow
                        </div>
                        <h3 class="fw-bold text-dark mb-1 font-monospace" style="letter-spacing: -0.02em;">
                            ₹<?= number_format($totalOutflow, 2) ?>
                        </h3>
                        <small class="text-muted d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <i class="bi bi-journal-text text-secondary"></i> <?= count($expenses) ?> Expense Vouchers
                        </small>
                    </div>
                    <div style="width: 42px; height: 42px; background: #d1fae5; color: #059669; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: bold; flex-shrink: 0;">
                        ₹
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Cash & Petty Disbursed -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border: 1px solid #bfdbfe !important; border-left: 4px solid #3b82f6 !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase fw-bold small mb-1" style="color: #2563eb; font-size: 0.74rem; letter-spacing: 0.04em;">
                            Cash & Petty Disbursed
                        </div>
                        <h3 class="fw-bold text-dark mb-1 font-monospace" style="letter-spacing: -0.02em;">
                            ₹<?= number_format($cashDisbursed, 2) ?>
                        </h3>
                        <small class="text-muted d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <i class="bi bi-wallet2 text-secondary"></i> Physical Cash Drawer
                        </small>
                    </div>
                    <div style="width: 42px; height: 42px; background: #dbeafe; color: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: UPI & Bank Transfers -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #ecfeff 0%, #ffffff 100%); border: 1px solid #a5f3fc !important; border-left: 4px solid #06b6d4 !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase fw-bold small mb-1" style="color: #0891b2; font-size: 0.74rem; letter-spacing: 0.04em;">
                            UPI & Bank Transfers
                        </div>
                        <h3 class="fw-bold text-dark mb-1 font-monospace" style="letter-spacing: -0.02em;">
                            ₹<?= number_format($bankDisbursed, 2) ?>
                        </h3>
                        <small class="text-muted d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <i class="bi bi-bank text-secondary"></i> Direct Vendor NEFT/UPI
                        </small>
                    </div>
                    <div style="width: 42px; height: 42px; background: #cffafe; color: #0891b2; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Pending Payables -->
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border: 1px solid #fde68a !important; border-left: 4px solid #f59e0b !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-uppercase fw-bold small mb-1" style="color: #d97706; font-size: 0.74rem; letter-spacing: 0.04em;">
                            Pending Payables
                        </div>
                        <h3 class="fw-bold text-dark mb-1 font-monospace" style="letter-spacing: -0.02em;">
                            ₹<?= number_format($pendingPayables, 2) ?>
                        </h3>
                        <small class="text-muted d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <i class="bi bi-hourglass-split text-secondary"></i> Unsettled Invoices
                        </small>
                    </div>
                    <div style="width: 42px; height: 42px; background: #fef3c7; color: #d97706; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar Card -->
    <div class="card border-0 shadow-sm p-3 bg-white mb-4" style="border: 1px solid #e2e8f0 !important; border-radius: 12px;">
        <form method="GET" action="<?= \App\Core\Controller::url('/m/expense') ?>" class="row g-2 align-items-end">
            <!-- Warehouse / Concern Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">
                    <i class="bi bi-building me-1"></i> Warehouse
                </label>
                <select name="concern_id" class="form-select form-select-sm" style="border-radius: 8px; font-size: 0.84rem;">
                    <option value="" <?= empty($filterConcernId) ? 'selected' : '' ?>>All Warehouses / Concerns</option>
                    <?php foreach ($allConcerns as $con): ?>
                        <option value="<?= $con['id'] ?>" <?= ($filterConcernId == $con['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($con['name']) ?> (<?= htmlspecialchars($con['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- From Date -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">
                    From Date
                </label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate) ?>" style="border-radius: 8px; font-size: 0.84rem;">
            </div>

            <!-- To Date -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">
                    To Date
                </label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate) ?>" style="border-radius: 8px; font-size: 0.84rem;">
            </div>

            <!-- Category -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">
                    Category
                </label>
                <select name="category" class="form-select form-select-sm" style="border-radius: 8px; font-size: 0.84rem;">
                    <option value="" <?= empty($category) ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Payment Mode -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">
                    Payment Mode
                </label>
                <select name="payment_mode" class="form-select form-select-sm" style="border-radius: 8px; font-size: 0.84rem;">
                    <option value="" <?= empty($payMode) ? 'selected' : '' ?>>All Modes</option>
                    <?php foreach ($paymentModes as $pm): ?>
                        <option value="<?= htmlspecialchars($pm) ?>" <?= ($payMode === $pm) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pm) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Search payee / bill -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="d-flex gap-1">
                    <div class="flex-grow-1">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search payee/bill..." value="<?= htmlspecialchars($search) ?>" style="border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <button type="submit" class="btn btn-sm text-white px-3 fw-bold" style="background: #4f46e5; border-radius: 8px;" title="Filter Expenses">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (!empty($search) || !empty($category) || !empty($payMode) || !empty($filterConcernId)): ?>
                        <a href="<?= \App\Core\Controller::url('/m/expense') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;" title="Reset Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Warehouse Expenses Register Table Card -->
    <div class="card border-0 shadow-sm bg-white overflow-hidden mb-4" style="border: 1px solid #e2e8f0 !important; border-radius: 12px;">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; background: #e0e7ff; color: #4338ca; border-radius: 6px; font-size: 0.95rem;">
                    <i class="bi bi-card-checklist"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Warehouse Expenses Register</h6>
                    <small class="text-secondary" style="font-size: 0.78rem;">
                        Showing <?= count($expenses) ?> records from <?= date('d M Y', strtotime($fromDate)) ?> to <?= date('d M Y', strtotime($toDate)) ?>
                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill px-3 py-2 fw-bold font-monospace" style="background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; font-size: 0.82rem;">
                    Total: ₹<?= number_format($totalOutflow, 2) ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <tr class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <th class="ps-4">Voucher #</th>
                        <th>Warehouse</th>
                        <th>Category</th>
                        <th>Particulars / Item</th>
                        <th>Paid To / Vendor</th>
                        <th>Payment Mode</th>
                        <th>Status</th>
                        <th class="text-end">Amount (INR)</th>
                        <th>Date</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="mb-3 text-muted" style="opacity: 0.45;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto">
                                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                                        <path d="m3.3 7 8.7 5 8.7-5"></path>
                                        <path d="M12 22V12"></path>
                                    </svg>
                                </div>
                                <p class="text-secondary small mb-0 fw-medium">No warehouse expenses found for the selected period.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-dark">
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($e['expense_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($e['concern_name'] ?? 'WH-01') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($e['concern_code'] ?? 'WH-01') ?></small>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-2.5 py-1 text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25" style="font-size: 0.74rem;">
                                        <?= htmlspecialchars($e['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-dark fw-medium"><?= htmlspecialchars($e['item_particulars'] ?: $e['description'] ?: 'General Expense') ?></div>
                                    <?php if (!empty($e['description']) && !empty($e['item_particulars'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($e['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-dark fw-semibold"><?= htmlspecialchars($e['paid_to'] ?: 'Counter Payee') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= htmlspecialchars($e['payment_mode']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (strtolower($e['status'] ?? 'paid') === 'pending'): ?>
                                        <span class="badge rounded-pill px-2.5 py-1 bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size: 0.74rem;">
                                            <i class="bi bi-hourglass me-1"></i> Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill px-2.5 py-1 bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.74rem;">
                                            <i class="bi bi-check-circle me-1"></i> Paid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold fs-6 <?= (strtolower($e['status'] ?? 'paid') === 'pending') ? 'text-warning' : 'text-danger' ?>">
                                    ₹<?= number_format($e['amount'], 2) ?>
                                </td>
                                <td class="text-muted font-monospace" style="font-size: 0.82rem;">
                                    <?= date('d M Y', strtotime($e['expense_date'])) ?>
                                </td>
                                <td class="text-center pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" onclick='showVoucherDetails(<?= json_encode($e, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Voucher Details" style="border-radius: 6px;">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- RECORD WAREHOUSE EXPENSE MODAL -->
<div class="modal fade" id="modalAddExpense" tabindex="-1" aria-labelledby="modalAddExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="<?= \App\Core\Controller::url('/m/expense/store') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="modal-header border-bottom px-4 py-3" style="background: linear-gradient(135deg, #eff6ff 0%, #f5f3ff 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; background: #4f46e5; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalAddExpenseLabel">Record Warehouse Expense Voucher</h5>
                        <small class="text-muted">Issue a petty cash or bank disbursal voucher for warehouse operations</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <!-- Warehouse / Concern -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Warehouse / Concern <span class="text-danger">*</span>
                        </label>
                        <select name="concern_id" class="form-select form-select-sm" required style="border-radius: 8px;">
                            <?php foreach ($allConcerns as $con): ?>
                                <option value="<?= $con['id'] ?>" <?= ($selectedCid == $con['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($con['name']) ?> (<?= htmlspecialchars($con['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Expense Date -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Expense Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="expense_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required style="border-radius: 8px;">
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Category <span class="text-danger">*</span>
                        </label>
                        <select name="category" class="form-select form-select-sm" required style="border-radius: 8px;">
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Particulars / Item -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Particulars / Items <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="item_particulars" class="form-control form-control-sm" placeholder="e.g. 100 pcs 5-ply carton boxes" required style="border-radius: 8px;">
                    </div>

                    <!-- Paid To / Vendor -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Paid To / Vendor / Payee
                        </label>
                        <input type="text" name="paid_to" class="form-control form-control-sm" placeholder="e.g. Sri Balaji Packaging Corp" style="border-radius: 8px;">
                    </div>

                    <!-- Amount -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Amount (₹) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold">₹</span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required style="border-radius: 0 8px 8px 0; font-weight: 600;">
                        </div>
                    </div>

                    <!-- Payment Mode -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Payment Mode <span class="text-danger">*</span>
                        </label>
                        <select name="payment_mode" class="form-select form-select-sm" required style="border-radius: 8px;">
                            <?php foreach ($paymentModes as $pm): ?>
                                <option value="<?= htmlspecialchars($pm) ?>"><?= htmlspecialchars($pm) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Debited Account -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Debited Account (Optional)
                        </label>
                        <select name="account_id" class="form-select form-select-sm" style="border-radius: 8px;">
                            <option value="">-- Direct Physical Cash / None --</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= htmlspecialchars($acc['name']) ?> (₹<?= number_format($acc['balance'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Voucher Status -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-select form-select-sm" required style="border-radius: 8px;">
                            <option value="Paid" selected>Paid / Disbursed</option>
                            <option value="Pending">Pending / Unsettled</option>
                        </select>
                    </div>

                    <!-- Description / Notes -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            Description / Bill Reference Notes
                        </label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="e.g. Bill #PKG-4029 approved by warehouse manager" style="border-radius: 8px;"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="submit" class="btn btn-sm text-white px-4 fw-bold shadow-sm" style="background: #4f46e5; border-radius: 8px;">
                    <i class="bi bi-check2-circle me-1"></i> Save Expense Voucher
                </button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW VOUCHER MODAL -->
<div class="modal fade" id="modalViewVoucher" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt fs-4 text-primary"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="view_voucher_title">Warehouse Expense Voucher</h6>
                        <small class="text-muted" id="view_voucher_no"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small">Amount Disbursed:</span>
                        <span class="fw-bold fs-4 text-danger font-monospace" id="view_amount"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small">Status:</span>
                        <span class="badge bg-success" id="view_status">Paid</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Warehouse:</span>
                        <strong class="text-dark" id="view_warehouse"></strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Category:</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary" id="view_category"></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Particulars:</span>
                        <strong class="text-dark" id="view_particulars"></strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Paid To / Payee:</span>
                        <strong class="text-dark" id="view_paid_to"></strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Payment Mode:</span>
                        <span class="badge bg-light text-dark border" id="view_pay_mode"></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Date:</span>
                        <span class="font-monospace text-dark" id="view_date"></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Notes / Memo:</span>
                        <span class="text-muted" id="view_description"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Voucher
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showVoucherDetails(voucher) {
    if (!voucher) return;
    document.getElementById('view_voucher_title').innerText = voucher.category || 'Operational Expense';
    document.getElementById('view_voucher_no').innerText = voucher.expense_no || '';
    document.getElementById('view_amount').innerText = '₹' + parseFloat(voucher.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('view_status').innerText = voucher.status || 'Paid';
    document.getElementById('view_warehouse').innerText = (voucher.concern_name || 'Warehouse (WH-01)') + ' (' + (voucher.concern_code || 'WH-01') + ')';
    document.getElementById('view_category').innerText = voucher.category || '-';
    document.getElementById('view_particulars').innerText = voucher.item_particulars || voucher.description || '-';
    document.getElementById('view_paid_to').innerText = voucher.paid_to || 'Counter Payee';
    document.getElementById('view_pay_mode').innerText = voucher.payment_mode || 'Cash';
    document.getElementById('view_date').innerText = voucher.expense_date || '';
    document.getElementById('view_description').innerText = voucher.description || '-';

    const modal = new bootstrap.Modal(document.getElementById('modalViewVoucher'));
    modal.show();
}
</script>
