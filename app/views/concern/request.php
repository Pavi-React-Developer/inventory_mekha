<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Customer Product Requests</h4>
            <p class="text-secondary small mb-0">Track order inquiries, customer sample requests, and advance bookings.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddRequest">
            <i class="bi bi-file-earmark-plus me-1"></i> New Product Request
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Request Pipeline</h6>
            <span class="badge bg-light text-dark border"><?= count($requests) ?> Requests</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Request No</th>
                        <th>Customer</th>
                        <th>Requested Variant</th>
                        <th>Requested Qty</th>
                        <th>Available Stock</th>
                        <th>Target Delivery</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No requests recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['request_no']) ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['customer_name']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($r['sku_code']) ?></small>
                                </td>
                                <td><span class="fw-bold text-primary"><?= number_format($r['requested_qty'], 2) ?></span></td>
                                <td>
                                    <span class="badge <?= $r['current_stock'] >= $r['requested_qty'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= number_format($r['current_stock'], 2) ?> In Stock
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($r['expected_date'])) ?></td>
                                <td><span class="badge bg-info text-dark"><?= ucfirst($r['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddRequest" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/request/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">New Customer Request</h5>
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
                    <label class="form-label small fw-semibold">Requested Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Quantity Needed *</label>
                        <input type="number" step="0.01" name="requested_qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Delivery Date</label>
                        <input type="date" name="expected_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes / Special Instructions</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Custom packaging or barcode tagging"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Request</button>
            </div>
        </form>
    </div>
</div>
