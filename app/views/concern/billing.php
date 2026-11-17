<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Sales Invoicing & Billing</h4>
            <p class="text-secondary small mb-0">Generate GST sales invoices and update customer receivable ledger and cash accounts.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddInvoice">
            <i class="bi bi-credit-card-2-front me-1"></i> New Sales Invoice
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt-cutoff me-2 text-primary"></i> Generated Sales Invoices</h6>
            <span class="badge bg-light text-dark border"><?= count($invoices) ?> Invoices</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Subtotal</th>
                        <th>Tax (5%)</th>
                        <th>Net Total (₹)</th>
                        <th>Payment Mode</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No sales invoices generated.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($inv['invoice_no']) ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($inv['customer_name']) ?></td>
                                <td><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                                <td>₹<?= number_format($inv['total_amount'], 2) ?></td>
                                <td>₹<?= number_format($inv['tax_amount'], 2) ?></td>
                                <td class="fw-bold text-success fs-6">₹<?= number_format($inv['net_amount'], 2) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= strtoupper($inv['payment_method']) ?></span></td>
                                <td><span class="badge bg-success"><?= ucfirst($inv['payment_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddInvoice" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/billing/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Generate Sales Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Customer *</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Variant *</label>
                    <select name="variant_id" class="form-select" required onchange="fillInvoiceRate(this)">
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>" data-price="<?= $v['price'] ?>">
                                <?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?> (Stock: <?= $v['available_qty'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Billed Qty *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit Price (₹) *</label>
                        <input type="number" step="0.01" name="unit_price" id="inv_unit_price" class="form-control" required placeholder="0.00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Mode</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="credit">Credit (On Account)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Invoice & Post Revenue</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillInvoiceRate(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.price) {
        document.getElementById('inv_unit_price').value = opt.dataset.price;
    }
}
</script>
