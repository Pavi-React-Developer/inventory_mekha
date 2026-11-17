<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Outward Consignments & Gate Passes</h4>
            <p class="text-secondary small mb-0">Packaging and warehouse dispatch clearance. Automatically deducts live inventory stock via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddOutward">
            <i class="bi bi-box-arrow-up-right me-1"></i> New Outward Consignment
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-box-arrow-up-right me-2 text-danger"></i> Outward Records</h6>
            <span class="badge bg-light text-dark border"><?= count($outwards) ?> Consignments</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Outward No</th>
                        <th>Customer</th>
                        <th>Invoice Ref</th>
                        <th>Outward Date</th>
                        <th>Location</th>
                        <th>Quantity Deducted</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outwards)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No outward consignments generated.</td></tr>
                    <?php else: ?>
                        <?php foreach ($outwards as $o): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($o['outward_no']) ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($o['customer_name']) ?></td>
                                <td><?= htmlspecialchars($o['invoice_no'] ?: '-') ?></td>
                                <td><?= date('d M Y', strtotime($o['outward_date'])) ?></td>
                                <td><?= htmlspecialchars($o['location']) ?></td>
                                <td>
                                    <span class="text-danger fw-bold fs-6">
                                        -<?= number_format($o['total_qty'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $o['status'] === 'dispatched' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= ucfirst($o['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($o['status'] === 'prepared'): ?>
                                        <a href="<?= \App\Core\Controller::url('/m/dispatch') ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-send"></i> Dispatch
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-check2-all text-success"></i> Dispatched</span>
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

<div class="modal fade" id="modalAddOutward" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/outward/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Create Outward Gate Pass</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <label class="form-label small fw-semibold">Sales Invoice Ref</label>
                    <select name="sales_invoice_id" class="form-select">
                        <option value="">None (Sample / Non-billed delivery)</option>
                        <?php foreach ($invoices as $inv): ?>
                            <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['invoice_no']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant to Outward</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>">
                                <?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?> (On Hand: <?= $v['stock_qty'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Quantity to Outward *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Warehouse Bay / Location</label>
                        <input type="text" name="location" class="form-control" value="Main Store">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Outward Date</label>
                    <input type="date" name="outward_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Deduct Stock & Issue Gate Pass</button>
            </div>
        </form>
    </div>
</div>
