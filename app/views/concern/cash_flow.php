<div class="container-fluid px-0">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="text-secondary small mb-1">
                <a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a> 
                <span class="mx-1">/</span> 
                <span class="text-muted">Finance</span> 
                <span class="mx-1">/</span> 
                <span class="text-dark fw-semibold">Cash & Cash Flow</span>
            </div>
            <h4 class="fw-bold mb-1 text-dark">Cash & Cash Flow Overview</h4>
            <p class="text-secondary small mb-0">Unified cashier shift registers, cash/bank accounts, and consolidated cash flow intelligence.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Cash Flow Export Excel Dropdown -->
            <div class="dropdown">
                <button class="btn btn-success shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 8px; background: #16a34a; border: none;">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; font-size: 0.85rem; min-width: 250px;">
                    <li class="dropdown-header text-uppercase small fw-bold text-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Cash Flow Excel (.xls)
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=single_day') ?>"><span>Single Day (Today)</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=this_month') ?>"><span>This Month</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=last_month') ?>"><span>Last Month</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=3_months') ?>"><span>Particular 3 Months</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=6_months') ?>"><span>Six Months</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=excel&period=all') ?>"><span>All Time (Consolidated)</span><span class="badge bg-success bg-opacity-10 text-success border">Excel</span></a></li>
                </ul>
            </div>

            <!-- Cash Flow Export PDF Dropdown -->
            <div class="dropdown">
                <button class="btn btn-danger shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 8px; background: #dc2626; border: none;">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; font-size: 0.85rem; min-width: 250px;">
                    <li class="dropdown-header text-uppercase small fw-bold text-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Cash Flow PDF (.pdf)
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=single_day') ?>"><span>Single Day (Today)</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=this_month') ?>"><span>This Month</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=last_month') ?>"><span>Last Month</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=3_months') ?>"><span>Particular 3 Months</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=6_months') ?>"><span>Six Months</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                    <li><a class="dropdown-item d-flex align-items-center justify-content-between" target="_blank" href="<?= \App\Core\Controller::url('/m/cash_flow/export?format=pdf&period=all') ?>"><span>All Time (Consolidated)</span><span class="badge bg-danger bg-opacity-10 text-danger border">PDF</span></a></li>
                </ul>
            </div>

            <?php if ($activeShift): ?>
                <button class="btn btn-warning shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCloseShift">
                    <i class="bi bi-lock-fill"></i> Close Shift (#<?= htmlspecialchars($activeShift['shift_no']) ?>)
                </button>
            <?php else: ?>
                <button class="btn text-white shadow-sm fw-semibold d-flex align-items-center gap-2" style="background: #4f46e5; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalOpenShift">
                    <i class="bi bi-door-open"></i> Open Shift Register
                </button>
            <?php endif; ?>
            
            <button class="btn btn-outline-primary shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAddAccount">
                <i class="bi bi-bank"></i> Add Account
            </button>
            <button class="btn btn-outline-secondary shadow-sm d-flex align-items-center gap-1" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Statement
            </button>
        </div>
    </div>

    <!-- Active Shift Status Banner -->
    <?php if ($activeShift): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 5px solid #16a34a !important;">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; background: #dcfce7; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #15803d; font-size: 1.5rem;">
                            <i class="bi bi-door-open-fill"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.72rem;">ACTIVE SHIFT OPEN</span>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($activeShift['shift_no']) ?></h6>
                            </div>
                            <div class="small text-muted mt-1">
                                <strong>Cashier:</strong> <?= htmlspecialchars($activeShift['cashier_name']) ?> &bull; 
                                <strong>Opened:</strong> <?= date('d M Y, h:i A', strtotime($activeShift['opened_at'])) ?>
                                <?php if (!empty($activeShift['opening_notes'])): ?>
                                    &bull; <em>"<?= htmlspecialchars($activeShift['opening_notes']) ?>"</em>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <span class="text-secondary small d-block">Starting Float</span>
                            <span class="fw-bold text-dark font-monospace fs-6">₹<?= number_format($activeShift['starting_float'], 2) ?></span>
                        </div>
                        <div class="text-end border-start ps-3">
                            <span class="text-secondary small d-block">Shift Cash In</span>
                            <span class="fw-bold text-success font-monospace fs-6">+₹<?= number_format($shiftInflows, 2) ?></span>
                        </div>
                        <div class="text-end border-start ps-3">
                            <span class="text-secondary small d-block">Shift Cash Out</span>
                            <span class="fw-bold text-danger font-monospace fs-6">-₹<?= number_format($shiftOutflows, 2) ?></span>
                        </div>
                        <div class="text-end border-start ps-3">
                            <span class="text-secondary small d-block">Expected Till Cash</span>
                            <span class="fw-bold text-primary font-monospace fs-5">₹<?= number_format($shiftExpectedFloat, 2) ?></span>
                        </div>
                        <button class="btn btn-sm btn-outline-danger ms-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCloseShift">
                            <i class="bi bi-lock me-1"></i> Close Shift
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light border shadow-sm rounded-3 mb-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon-box bg-secondary bg-opacity-10 text-secondary" style="width: 42px; height: 42px; border-radius: 8px;">
                    <i class="bi bi-lock"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Cashier Till Drawer is Currently Closed</div>
                    <small class="text-muted">No active shift is open. Open a cashier shift to record starting cash float and count denominations.</small>
                </div>
            </div>
            <button class="btn btn-sm text-white px-3 py-2 fw-semibold" style="background: #4f46e5; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#modalOpenShift">
                <i class="bi bi-door-open me-1"></i> Open Cashier Shift Register
            </button>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Till Drawer Status</span>
                    <?php if ($activeShift): ?>
                        <h4 class="fw-bold text-success mt-1 mb-0"><i class="bi bi-check-circle-fill me-1"></i> OPEN</h4>
                        <small class="text-muted"><?= htmlspecialchars($activeShift['cashier_name']) ?></small>
                    <?php else: ?>
                        <h4 class="fw-bold text-secondary mt-1 mb-0"><i class="bi bi-dash-circle me-1"></i> CLOSED</h4>
                        <small class="text-muted">Till locked</small>
                    <?php endif; ?>
                </div>
                <div class="stat-icon-box <?= $activeShift ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?>">
                    <i class="bi <?= $activeShift ? 'bi-door-open' : 'bi-door-closed' ?>"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Cash Inflows</span>
                    <h4 class="fw-bold text-success mt-1 mb-0">+₹<?= number_format($totalInflow, 2) ?></h4>
                    <small class="text-muted">Sales Billing Receipts</small>
                </div>
                <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Cash Outflows</span>
                    <h4 class="fw-bold text-danger mt-1 mb-0">-₹<?= number_format($totalOutflow, 2) ?></h4>
                    <small class="text-muted">Expenses & Purchases</small>
                </div>
                <div class="stat-icon-box bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Net Liquid Position</span>
                    <h4 class="fw-bold <?= $netFlow >= 0 ? 'text-primary' : 'text-danger' ?> mt-1 mb-0">
                        ₹<?= number_format($netFlow, 2) ?>
                    </h4>
                    <small class="text-muted">Total Cash: ₹<?= number_format($totalCashInHand, 2) ?></small>
                </div>
                <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm border" id="financeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-flow-btn" data-bs-toggle="pill" data-bs-target="#tab-flow" type="button" role="tab">
                <i class="bi bi-journal-check me-2"></i> Cash Flow Statement & Ledger
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-accounts-btn" data-bs-toggle="pill" data-bs-target="#tab-accounts" type="button" role="tab">
                <i class="bi bi-bank me-2"></i> Cash & Bank Accounts (<?= count($accounts) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-shift-btn" data-bs-toggle="pill" data-bs-target="#tab-shift" type="button" role="tab">
                <i class="bi bi-door-open me-2"></i> Cashier Shift Register & Logs
                <?php if ($activeShift): ?>
                    <span class="badge bg-success ms-1">Active</span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="financeTabContent">
        <!-- 1. CASH FLOW STATEMENT & LEDGER TAB -->
        <div class="tab-pane fade show active" id="tab-flow" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-text me-2 text-primary"></i> Consolidated Cash Flow Ledger</h6>
                    <span class="badge bg-light text-dark border"><?= count($feed) ?> Total Entries</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Flow Stream</th>
                                <th>Classification</th>
                                <th>Transaction Particulars</th>
                                <th class="text-end">Cash In (+)</th>
                                <th class="text-end">Cash Out (-)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($feed)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No cash flow movements recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($feed as $row): ?>
                                    <tr>
                                        <td class="small text-muted font-monospace"><?= date('d M Y', strtotime($row['txn_date'])) ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['account_name']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['ref_module']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($row['type'] === 'credit'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Operating Inflow</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Disbursement</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                                        <td class="text-end font-monospace">
                                            <?php if ($row['type'] === 'credit'): ?>
                                                <span class="text-success fw-bold">+₹<?= number_format($row['amount'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end font-monospace">
                                            <?php if ($row['type'] === 'debit'): ?>
                                                <span class="text-danger fw-bold">-₹<?= number_format($row['amount'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. CASH & BANK ACCOUNTS TAB -->
        <div class="tab-pane fade" id="tab-accounts" role="tabpanel">
            <div class="row g-3 mb-4">
                <?php foreach ($accounts as $acc): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge <?= $acc['account_type'] === 'bank' ? 'bg-primary' : 'bg-warning text-dark' ?> text-uppercase mb-1">
                                        <?= htmlspecialchars($acc['account_type']) ?>
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($acc['name']) ?></h5>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($acc['account_number'] ?: 'Internal Counter Drawer') ?></small>
                                </div>
                                <div class="stat-icon-box bg-light text-primary">
                                    <i class="bi <?= $acc['account_type'] === 'bank' ? 'bi-bank' : 'bi-cash-coin' ?>"></i>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <span class="text-secondary small">Live Balance:</span>
                                <h4 class="fw-bold <?= $acc['balance'] >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                    ₹<?= number_format($acc['balance'], 2) ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Transactions Table -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Account Transactions Log</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddAccount">
                        <i class="bi bi-plus-lg me-1"></i> Add Account
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th>Module Ref</th>
                                <th>Amount (₹)</th>
                                <th>Description</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accountTransactions)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No transactions recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($accountTransactions as $t): ?>
                                    <tr>
                                        <td class="small text-muted font-monospace"><?= date('d M Y', strtotime($t['txn_date'])) ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($t['account_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $t['type'] === 'credit' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= strtoupper($t['type']) ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['ref_module']) ?></span></td>
                                        <td>
                                            <span class="fw-bold <?= $t['type'] === 'credit' ? 'text-success' : 'text-danger' ?> fs-6">
                                                <?= $t['type'] === 'credit' ? '+' : '-' ?>₹<?= number_format($t['amount'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($t['description'] ?: '-') ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($t['user_name'] ?? 'System') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. CASHIER SHIFTS & LOGS TAB -->
        <div class="tab-pane fade" id="tab-shift" role="tabpanel">
            <?php if ($activeShift): 
                $denoms = json_decode($activeShift['denominations_json'] ?? '{}', true) ?: [];
            ?>
                <!-- Active Shift Breakdown Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-door-open-fill text-success me-2"></i> Active Shift Summary: <?= htmlspecialchars($activeShift['shift_no']) ?>
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">LIVE OPEN SHIFT</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4 border-end">
                                <h6 class="text-uppercase text-secondary small fw-bold mb-3">Shift Metadata</h6>
                                <p class="mb-2 text-dark"><strong>Cashier:</strong> <?= htmlspecialchars($activeShift['cashier_name']) ?></p>
                                <p class="mb-2 text-dark"><strong>Opened At:</strong> <?= date('d M Y, h:i A', strtotime($activeShift['opened_at'])) ?></p>
                                <p class="mb-2 text-dark"><strong>Starting Float:</strong> <span class="font-monospace fw-bold text-primary">₹<?= number_format($activeShift['starting_float'], 2) ?></span></p>
                                <p class="mb-0 text-muted small"><strong>Notes:</strong> <?= htmlspecialchars($activeShift['opening_notes'] ?: 'None') ?></p>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-uppercase text-secondary small fw-bold mb-3">Starting Denominations Breakdown</h6>
                                <div class="row g-2">
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹500</small><strong><?= (int)($denoms['500'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['500'] ?? 0) * 500 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹200</small><strong><?= (int)($denoms['200'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['200'] ?? 0) * 200 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹100</small><strong><?= (int)($denoms['100'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['100'] ?? 0) * 100 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹50</small><strong><?= (int)($denoms['50'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['50'] ?? 0) * 50 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹20</small><strong><?= (int)($denoms['20'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['20'] ?? 0) * 20 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹10</small><strong><?= (int)($denoms['10'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['10'] ?? 0) * 10 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">₹5</small><strong><?= (int)($denoms['5'] ?? 0) ?></strong> <small class="text-muted">(₹<?= (int)($denoms['5'] ?? 0) * 5 ?>)</small></div></div>
                                    <div class="col-3"><div class="border rounded p-2 text-center bg-light"><small class="text-muted d-block">Coins</small><strong>₹<?= number_format((float)($denoms['coins'] ?? 0), 2) ?></strong></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Shift History Table -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Shift Register Audit History</h6>
                    <span class="badge bg-light text-dark border"><?= count($shiftsHistory) ?> Shifts Logged</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Shift #</th>
                                <th>Cashier</th>
                                <th>Opened At</th>
                                <th>Closed At</th>
                                <th class="text-end">Starting Float (₹)</th>
                                <th class="text-end">Closing Float (₹)</th>
                                <th class="text-end">Variance (₹)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shiftsHistory)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No cashier shift records logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shiftsHistory as $sh): ?>
                                    <tr>
                                        <td class="fw-bold text-dark font-monospace"><?= htmlspecialchars($sh['shift_no']) ?></td>
                                        <td><?= htmlspecialchars($sh['cashier_name']) ?></td>
                                        <td class="small text-muted font-monospace"><?= date('d M Y, h:i A', strtotime($sh['opened_at'])) ?></td>
                                        <td class="small text-muted font-monospace">
                                            <?= $sh['closed_at'] ? date('d M Y, h:i A', strtotime($sh['closed_at'])) : '<span class="badge bg-success">In Progress</span>' ?>
                                        </td>
                                        <td class="text-end font-monospace">₹<?= number_format($sh['starting_float'], 2) ?></td>
                                        <td class="text-end font-monospace">
                                            <?= $sh['closing_float'] !== null ? '₹' . number_format($sh['closing_float'], 2) : '-' ?>
                                        </td>
                                        <td class="text-end font-monospace">
                                            <?php if ($sh['variance'] !== null): ?>
                                                <?php if ((float)$sh['variance'] == 0): ?>
                                                    <span class="text-success fw-bold">₹0.00 (Match)</span>
                                                <?php elseif ((float)$sh['variance'] > 0): ?>
                                                    <span class="text-primary fw-bold">+₹<?= number_format($sh['variance'], 2) ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger fw-bold">-₹<?= number_format(abs($sh['variance']), 2) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sh['status'] === 'open'): ?>
                                                <span class="badge bg-success">OPEN</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">CLOSED</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: OPEN CASHIER SHIFT REGISTER (IDENTICAL TO IMAGE 2)  -->
<!-- ======================================================== -->
<div class="modal fade" id="modalOpenShift" tabindex="-1" aria-labelledby="modalOpenShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
        <form action="<?= \App\Core\Controller::url('/m/cash_flow/shift/open') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="is_float_modified" id="is_float_modified" value="0">

            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0 pt-4 px-4 align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 46px; height: 46px; background: #e0f2fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0284c7; font-size: 1.45rem; flex-shrink: 0;">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalOpenShiftLabel" style="font-size: 1.18rem; letter-spacing: -0.01em;">Open Cashier Shift Register</h5>
                        <p class="text-muted mb-0" style="font-size: 0.84rem;">Count and verify starting cash float before opening till drawer.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.85rem;"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-4 py-3">
                <?php if ($isSuperAdmin && empty($selectedCid) && empty($currentConcern)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                            <i class="bi bi-building text-primary me-1"></i> Business Concern <span class="text-danger">*</span>
                        </label>
                        <select name="concern_id" id="modal_concern_id" class="form-select" required style="border-radius: 10px; border-color: #cbd5e1; padding: 0.6rem 0.85rem; font-size: 0.92rem;">
                            <?php foreach ($allConcerns as $con): ?>
                                <option value="<?= $con['id'] ?>" <?= ($con['id'] == 1 ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($con['name']) ?> (<?= htmlspecialchars($con['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.77rem;">Operating register will open under this business concern.</small>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="concern_id" value="<?= (int)($selectedCid ?: ($currentConcern['id'] ?? 1)) ?>">
                <?php endif; ?>

                <!-- Info Alert Banner -->
                <div class="border rounded-4 p-3 mb-3 d-flex align-items-center gap-3" style="background: #ffffff; border-color: #e2e8f0 !important;">
                    <div style="width: 36px; height: 36px; border: 1.5px solid #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6366f1; font-weight: bold; font-size: 1.15rem; flex-shrink: 0;">
                        <i class="bi bi-info-lg"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.88rem;">Initial Register Shift Setup</div>
                        <div class="text-muted" style="font-size: 0.81rem;">
                            <?php if ($prevClosedShift): ?>
                                Previous closed shift balance: <strong>₹<?= number_format($prevClosedShift['closing_float'], 2) ?></strong>. Specify initial starting cash float.
                            <?php else: ?>
                                No previous closed shift found. Specify initial starting cash float.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cashier & Remarks Inputs (2 Columns) -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                            <i class="bi bi-person-badge text-primary me-1"></i> Cashier Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="cashier_name" class="form-control" value="<?= htmlspecialchars($currentCashierName) ?>" required style="border-radius: 10px; border-color: #cbd5e1; padding: 0.6rem 0.85rem; font-size: 0.92rem;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                            <i class="bi bi-card-text text-primary me-1"></i> Opening Notes / Shift Remarks
                        </label>
                        <input type="text" name="opening_notes" class="form-control" placeholder="e.g. Morning Shift - Counter 1" style="border-radius: 10px; border-color: #cbd5e1; padding: 0.6rem 0.85rem; font-size: 0.92rem;">
                    </div>
                </div>

                <!-- Starting Float & Match Status Grouped Card -->
                <div class="border rounded-4 p-3 mb-3 bg-white" style="border-color: #e2e8f0 !important;">
                    <div class="row align-items-center g-3">
                        <!-- Left Column: Starting Cash Float -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark mb-0" style="font-size: 0.84rem;">
                                <i class="bi bi-cash-stack text-success me-1"></i> Total Starting Cash Float (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="text-muted mb-2" style="font-size: 0.77rem;">Unmodified closing balance from previous shift.</div>
                            
                            <div class="d-flex align-items-stretch" style="border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden;">
                                <div style="padding: 0.55rem 0.95rem; font-weight: 700; font-size: 1.25rem; color: #1e293b; background: #ffffff; border-right: 1px solid #e2e8f0; display: flex; align-items: center;">
                                    ₹
                                </div>
                                <input type="number" step="0.01" min="0" name="starting_float" id="starting_float" 
                                       class="form-control form-control-lg border-0 shadow-none px-2 fw-bold" 
                                       value="<?= number_format($defaultStartingFloat, 2, '.', '') ?>" 
                                       style="font-size: 1.45rem; color: #4338ca; border-radius: 0;" required>
                                <button type="button" class="btn btn-light d-flex align-items-center gap-1 border-start px-3" id="btnToggleLock" style="border-radius: 0; background: #f8fafc; font-size: 0.82rem; font-weight: 600; color: #475569; white-space: nowrap;">
                                    <i class="bi bi-lock-fill"></i> <span id="lockText">Unmodified</span>
                                </button>
                            </div>
                        </div>

                        <!-- Right Column: Match Status & Variance -->
                        <div class="col-md-6 ps-md-3">
                            <div class="fw-bold text-dark mb-0" style="font-size: 0.84rem;">Float Match Status & Variance</div>
                            <div class="text-muted mb-2" style="font-size: 0.77rem;">Denomination count vs starting float</div>
                            
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white" id="matchStatusBox" style="border-color: #e2e8f0 !important; min-height: 48px;">
                                <div class="fw-bold text-success d-flex align-items-center gap-1" id="matchText" style="font-size: 0.88rem;">
                                    <i class="bi bi-check-circle-fill"></i> <span>₹0.00 (Exact Match)</span>
                                </div>
                                <span class="badge rounded-pill px-3 py-1 fw-bold" id="matchBadge" style="background: #dcfce7; color: #15803d; font-size: 0.75rem;">
                                    Matched
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Starting Denominations Count (Optional) -->
                <div class="border rounded-4 p-3 bg-white" style="border-color: #e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold text-dark mb-0" style="font-size: 0.84rem;">
                            <i class="bi bi-calculator text-primary me-1"></i> Starting Denominations Count (Optional)
                        </label>
                        <span class="badge border font-monospace fw-bold px-3 py-2" id="denomTotalBadge" style="background: #f1f5f9; color: #475569; font-size: 0.78rem; border-radius: 8px;">
                            Denom Total: ₹0.00
                        </span>
                    </div>

                    <!-- 4x2 Denominations Grid -->
                    <div class="row g-2">
                        <!-- Row 1: ₹500, ₹200, ₹100, ₹50 -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹500
                                </div>
                                <input type="number" min="0" step="1" name="denom_500" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="500" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹200
                                </div>
                                <input type="number" min="0" step="1" name="denom_200" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="200" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹100
                                </div>
                                <input type="number" min="0" step="1" name="denom_100" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="100" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹50
                                </div>
                                <input type="number" min="0" step="1" name="denom_50" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="50" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <!-- Row 2: ₹20, ₹10, ₹5, Coins -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹20
                                </div>
                                <input type="number" min="0" step="1" name="denom_20" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="20" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹10
                                </div>
                                <input type="number" min="0" step="1" name="denom_10" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="10" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    ₹5
                                </div>
                                <input type="number" min="0" step="1" name="denom_5" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="5" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div style="padding: 0.45rem 0.65rem; background: #f8fafc; font-size: 0.82rem; font-weight: 700; color: #1e293b; border-right: 1px solid #cbd5e1; min-width: 58px; text-align: center;">
                                    Coins
                                </div>
                                <input type="number" min="0" step="0.5" name="denom_coins" class="form-control border-0 shadow-none text-center denom-input fw-semibold" value="0" data-value="1" style="padding: 0.45rem 0.5rem; font-size: 0.92rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Right-aligned Cancel & Start Shift Register) -->
            <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex justify-content-end align-items-center gap-2">
                <button type="button" class="btn px-4 py-2" data-bs-dismiss="modal" style="background: #475569; color: #ffffff; border-radius: 9999px; font-weight: 600; font-size: 0.88rem; border: none;">
                    Cancel
                </button>
                <button type="submit" class="btn text-white px-4 py-2 d-inline-flex align-items-center gap-2" style="background: #4f46e5; border-radius: 9999px; font-weight: 600; font-size: 0.88rem; border: none; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);">
                    <i class="bi bi-check2-circle"></i> Start Shift Register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: CLOSE CASHIER SHIFT REGISTER                        -->
<!-- ======================================================== -->
<?php if ($activeShift): ?>
<div class="modal fade" id="modalCloseShift" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <form action="<?= \App\Core\Controller::url('/m/cash_flow/shift/close') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="concern_id" value="<?= (int)($activeShift ? $activeShift['concern_id'] : ($selectedCid ?: ($currentConcern['id'] ?? 1))) ?>">

            <div class="modal-header border-0 pb-0 pt-4 px-4 align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 1.3rem;">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Close Cashier Shift Register</h5>
                        <p class="text-muted small mb-0">Count physical cash in drawer and reconcile with system float.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary small">Starting Float:</span>
                        <span class="fw-bold text-dark font-monospace">₹<?= number_format($activeShift['starting_float'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary small">Shift Cash Inflows:</span>
                        <span class="fw-bold text-success font-monospace">+₹<?= number_format($shiftInflows, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary small">Shift Cash Outflows:</span>
                        <span class="fw-bold text-danger font-monospace">-₹<?= number_format($shiftOutflows, 2) ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Expected Closing Cash:</span>
                        <span class="fw-bold text-primary font-monospace fs-5">₹<?= number_format($shiftExpectedFloat, 2) ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Physical Counted Cash in Drawer (₹) *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold">₹</span>
                        <input type="number" step="0.01" min="0" name="closing_float" id="closing_float" class="form-control form-control-lg fw-bold text-dark" value="<?= number_format($shiftExpectedFloat, 2, '.', '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Shift Handover / Closing Notes</label>
                    <textarea name="closing_notes" class="form-control" rows="2" placeholder="e.g. End of morning shift. Till balanced with counter 2 handover."></textarea>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 9999px;">Cancel</button>
                <button type="submit" class="btn btn-danger px-4 fw-semibold" style="border-radius: 9999px;">
                    <i class="bi bi-lock me-1"></i> Confirm & Close Shift
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ======================================================== -->
<!-- MODAL: ADD CASH OR BANK ACCOUNT                           -->
<!-- ======================================================== -->
<div class="modal fade" id="modalAddAccount" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/m/cash_flow/store_account') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="concern_id" value="<?= (int)($activeShift ? $activeShift['concern_id'] : ($selectedCid ?: ($currentConcern['id'] ?? 1))) ?>">

            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">Add Cash or Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Account Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Counter 1 Till Drawer or HDFC Current A/c">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Account Type *</label>
                        <select name="account_type" class="form-select" required>
                            <option value="cash">Cash Counter / Till</option>
                            <option value="petty_cash">Petty Cash</option>
                            <option value="bank">Bank Account</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Opening Balance (₹)</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0.00">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Account / Drawer Identifier (Optional)</label>
                    <input type="text" name="account_number" class="form-control" placeholder="e.g. TILL-01 or 50200012345678">
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 9999px;">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold" style="border-radius: 9999px;">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- INTERACTIVE JAVASCRIPT LOGIC                             -->
<!-- ======================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tab restoration via URL hash / query param (?tab=accounts / ?tab=shift)
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabParam = urlParams.get('tab');
    if (activeTabParam === 'accounts') {
        const btn = document.getElementById('tab-accounts-btn');
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    } else if (activeTabParam === 'shift') {
        const btn = document.getElementById('tab-shift-btn');
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    // 2. Open Cashier Shift Register Real-Time Calculator
    const startingFloatInput = document.getElementById('starting_float');
    const denomInputs = document.querySelectorAll('.denom-input');
    const denomTotalBadge = document.getElementById('denomTotalBadge');
    const matchText = document.getElementById('matchText');
    const matchBadge = document.getElementById('matchBadge');
    const matchStatusBox = document.getElementById('matchStatusBox');
    const btnToggleLock = document.getElementById('btnToggleLock');
    const lockText = document.getElementById('lockText');
    const isFloatModified = document.getElementById('is_float_modified');

    function calculateDenomTotal() {
        let total = 0;
        denomInputs.forEach(input => {
            const count = parseFloat(input.value) || 0;
            const multiplier = parseFloat(input.getAttribute('data-value')) || 1;
            total += count * multiplier;
        });
        return total;
    }

    function updateFloatStatus() {
        if (!startingFloatInput) return;

        const startingFloat = parseFloat(startingFloatInput.value) || 0;
        const denomTotal = calculateDenomTotal();

        // Update badge text
        if (denomTotalBadge) {
            denomTotalBadge.textContent = 'Denom Total: ₹' + denomTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        const variance = denomTotal - startingFloat;

        if (Math.abs(variance) < 0.01) {
            // Exact Match
            if (matchText) {
                matchText.className = 'fw-bold text-success d-flex align-items-center gap-1';
                matchText.innerHTML = '<i class="bi bi-check-circle-fill"></i> <span>₹0.00 (Exact Match)</span>';
            }
            if (matchBadge) {
                matchBadge.textContent = 'Matched';
                matchBadge.style.background = '#dcfce7';
                matchBadge.style.color = '#15803d';
            }
        } else {
            // Variance / Mismatch
            const varFormatted = (variance > 0 ? '+' : '') + '₹' + variance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (matchText) {
                matchText.className = 'fw-bold text-danger d-flex align-items-center gap-1';
                matchText.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> <span>Variance: ' + varFormatted + '</span>';
            }
            if (matchBadge) {
                matchBadge.textContent = 'Mismatch';
                matchBadge.style.background = '#fee2e2';
                matchBadge.style.color = '#b91c1c';
            }
        }
    }

    // Attach listeners
    denomInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateFloatStatus();
        });
    });

    if (startingFloatInput) {
        startingFloatInput.addEventListener('input', function() {
            if (isFloatModified) isFloatModified.value = "1";
            if (lockText) lockText.textContent = "Modified";
            updateFloatStatus();
        });
    }

    if (btnToggleLock) {
        btnToggleLock.addEventListener('click', function() {
            const denomTotal = calculateDenomTotal();
            if (denomTotal > 0) {
                startingFloatInput.value = denomTotal.toFixed(2);
                if (isFloatModified) isFloatModified.value = "1";
                if (lockText) lockText.textContent = "Synced";
                updateFloatStatus();
            }
        });
    }

    // Initialize on load
    updateFloatStatus();
});
</script>
