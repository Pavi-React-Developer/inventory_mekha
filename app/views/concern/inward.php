<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Inward Stock Consignments</h4>
            <p class="text-secondary small mb-0">Record incoming raw materials or vendor receipts. Immediately credits live inventory balance via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddInward">
            <i class="bi bi-box-arrow-in-down me-1"></i> Receive Inward Consignment
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i> Inward Consignments Log</h6>
            <span class="badge bg-light text-dark border"><?= count($inwards) ?> Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Inward No</th>
                        <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                            <th>Concern</th>
                        <?php endif; ?>
                        <th>Supplier</th>
                        <th>Inward Date</th>
                        <th>Warehouse Bay</th>
                        <th>Items Received</th>
                        <th>Total Inward Qty</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inwards)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No inward consignments received yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inwards as $inw): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($inw['inward_no']) ?></span></td>
                                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            <?= htmlspecialchars($inw['concern_name'] ?? 'Concern #' . $inw['concern_id']) ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($inw['supplier_name'] ?? 'Direct Receipt') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($inw['remarks'] ?: '-') ?></small>
                                </td>
                                <td><?= date('d M Y', strtotime($inw['inward_date'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($inw['location']) ?></span></td>
                                <td><?= (int)$inw['items_count'] ?> Line Items</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-2 py-1">
                                        +<?= number_format($inw['total_qty'], 2) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($inw['user_name'] ?? 'Staff') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Inward -->
<div class="modal fade" id="modalAddInward" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/inward/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down me-1 text-primary"></i> Receive Stock Inward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                    <div class="mb-3 p-2 bg-light border rounded">
                        <label class="form-label small fw-bold text-primary">Inward To Concern *</label>
                        <select name="concern_id" class="form-select" required>
                            <option value="">-- Choose Concern --</option>
                            <?php foreach ($allConcerns as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">Direct Internal Consignment (No Supplier)</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">-- Choose Variant to Inward --</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['size'] ?: 'Standard') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Received Quantity *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit Cost Rate (₹)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Inward Date</label>
                        <input type="date" name="inward_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Location / Godown</label>
                        <input type="text" name="location" class="form-control" value="Main Store">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Remarks / Gate Entry Ref</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Delivery challan or truck ref"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Process Inward & Credit Stock</button>
            </div>
        </form>
    </div>
</div>
