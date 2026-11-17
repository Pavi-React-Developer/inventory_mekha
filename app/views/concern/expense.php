<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Operational Expense Registry</h4>
            <p class="text-secondary small mb-0">Record factory overheads, electricity, logistics freight, tea/petty cash and consumables.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddExpense">
            <i class="bi bi-wallet2 me-1"></i> Log Expense
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-primary"></i> Expense Vouchers</h6>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fs-6 px-3 py-1">
                Total Expenses: ₹<?= number_format($totalExpense, 2) ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Expense No</th>
                        <th>Category</th>
                        <th>Amount (₹)</th>
                        <th>Payment Mode</th>
                        <th>Debited Account</th>
                        <th>Description / Notes</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No expenses recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($e['expense_no']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($e['category']) ?></td>
                                <td class="fw-bold text-danger fs-6">₹<?= number_format($e['amount'], 2) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($e['payment_mode']) ?></span></td>
                                <td><?= htmlspecialchars($e['account_name'] ?? 'Direct Cash') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($e['description'] ?: '-') ?></td>
                                <td><?= date('d M Y', strtotime($e['expense_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddExpense" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/expense/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Record Expense Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Expense Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="Electricity & Power">Electricity & Power</option>
                            <option value="Factory Rent">Factory Rent</option>
                            <option value="Packaging Materials">Packaging Materials</option>
                            <option value="Logistics & Freight">Logistics & Freight</option>
                            <option value="Maintenance & Spares">Maintenance & Spares</option>
                            <option value="Tea & Refreshments">Tea & Refreshments</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Amount (₹) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Mode</label>
                        <select name="payment_mode" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI / GPay</option>
                            <option value="Bank NEFT/RTGS">Bank NEFT/RTGS</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Debit From Account</label>
                        <select name="account_id" class="form-select">
                            <option value="">Unlinked (Do not debit ledger)</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?> (₹<?= number_format($acc['balance'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Expense Date</label>
                    <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description / Voucher Ref</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Details of expense"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Expense Voucher</button>
            </div>
        </form>
    </div>
</div>
