<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Cash Flow Statement</h4>
            <p class="text-secondary small mb-0">Read-only consolidated fiscal intelligence unified across Sales Billing receipts, Purchase payments, and operational Expenses.</p>
        </div>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
    </div>

    <!-- Flow Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Cash Inflows</span>
                    <h3 class="fw-bold text-success mt-1 mb-0">+₹<?= number_format($totalInflow, 2) ?></h3>
                    <small class="text-muted">Sales Invoices & Receipts</small>
                </div>
                <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Cash Outflows</span>
                    <h3 class="fw-bold text-danger mt-1 mb-0">-₹<?= number_format($totalOutflow, 2) ?></h3>
                    <small class="text-muted">Expenses & Vendor Payments</small>
                </div>
                <div class="stat-icon-box bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Net Liquid Position</span>
                    <h3 class="fw-bold <?= $netFlow >= 0 ? 'text-primary' : 'text-danger' ?> mt-1 mb-0">
                        ₹<?= number_format($netFlow, 2) ?>
                    </h3>
                    <small class="text-muted">Net Operating Surplus</small>
                </div>
                <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Unified Cash Ledger Feed -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-check me-2 text-primary"></i> Consolidated Cash Flow Ledger</h6>
            <span class="badge bg-light text-dark border"><?= count($feed) ?> Transactions</span>
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
