<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Purchase Billing</h4>
            <p class="text-secondary small mb-0">Record vendor purchase bills and procurement purchase orders with GST calculation.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBill">
            <i class="bi bi-receipt me-1"></i> New Purchase Bill
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i> Purchase Invoices</h6>
            <span class="badge bg-light text-dark border"><?= count($bills) ?> Bills</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Supplier</th>
                        <th>Bill Date</th>
                        <th>Taxable Amt</th>
                        <th>Tax (5%)</th>
                        <th>Net Bill Amount</th>
                        <th>Payment Status</th>
                        <th>Inward Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No purchase bills recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bills as $b): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['bill_no']) ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($b['supplier_name']) ?></td>
                                <td><?= date('d M Y', strtotime($b['bill_date'])) ?></td>
                                <td>₹<?= number_format($b['total_amount'], 2) ?></td>
                                <td>₹<?= number_format($b['tax_amount'], 2) ?></td>
                                <td class="fw-bold text-primary">₹<?= number_format($b['net_amount'], 2) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= ucfirst($b['payment_status']) ?></span></td>
                                <td><span class="badge bg-info text-dark"><?= ucfirst($b['inward_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddBill" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/purchase_billing/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Generate Purchase Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Supplier *</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Quantity *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit Price (₹) *</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" required placeholder="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Bill Date</label>
                    <input type="date" name="bill_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Purchase order references"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Purchase Bill</button>
            </div>
        </form>
    </div>
</div>
