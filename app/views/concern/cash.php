<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Cash & Bank Accounts Management</h4>
            <p class="text-secondary small mb-0">Petty cash drawers, current accounts, and real-time ledger balance tracking.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddAccount">
            <i class="bi bi-bank me-1"></i> Add Account
        </button>
    </div>

    <!-- Accounts Cards Grid -->
    <div class="row g-3 mb-4">
        <?php foreach ($accounts as $acc): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
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
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cash-stack me-2 text-primary"></i> Account Transactions Log</h6>
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
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
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

<div class="modal fade" id="modalAddAccount" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/cash/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Cash or Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Account Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. HDFC Factory Operations A/c">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account Type</label>
                        <select name="account_type" class="form-select">
                            <option value="cash">Cash Counter</option>
                            <option value="bank">Bank Account</option>
                            <option value="petty_cash">Petty Cash Drawer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Opening Balance (₹)</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Account / Cheque Number</label>
                    <input type="text" name="account_number" class="form-control" placeholder="Bank A/c Number or IFSC">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>
