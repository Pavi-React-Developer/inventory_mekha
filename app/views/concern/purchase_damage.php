<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Purchase Damage & Vendor Rejections</h4>
            <p class="text-secondary small mb-0">Record raw materials or goods found defective on arrival. Automatically deducts stock via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddDamage">
            <i class="bi bi-shield-x me-1"></i> Log Purchase Damage
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-exclamation-triangle me-2 text-danger"></i> Recorded Purchase Damages</h6>
            <span class="badge bg-light text-dark border"><?= count($damages) ?> Incidents</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Damage No</th>
                        <th>Supplier</th>
                        <th>Damaged Variant</th>
                        <th>Quantity Deducted</th>
                        <th>Location</th>
                        <th>Defect Reason</th>
                        <th>Resolution Action</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($damages)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No purchase damage records.</td></tr>
                    <?php else: ?>
                        <?php foreach ($damages as $d): ?>
                            <tr>
                                <td><span class="badge bg-danger font-monospace"><?= htmlspecialchars($d['damage_no']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($d['supplier_name']) ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($d['variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($d['sku_code']) ?></small>
                                </td>
                                <td><span class="text-danger fw-bold fs-6">-<?= number_format($d['qty'], 2) ?></span></td>
                                <td><?= htmlspecialchars($d['location']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($d['reason'] ?: 'Unspecified defect') ?></td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($d['action_taken']) ?></span></td>
                                <td class="small text-muted font-monospace"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddDamage" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/purchase_damage/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Log Vendor Purchase Damage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <label class="form-label small fw-semibold">Damaged Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Damaged Qty *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Godown / Location</label>
                        <input type="text" name="location" class="form-control" value="Main Store">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Defect Reason</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Fabric holes, shade mismatch, GSM low, etc." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Resolution Action</label>
                    <select name="action_taken" class="form-select">
                        <option value="Debit Note / Return to Supplier">Debit Note / Return to Supplier</option>
                        <option value="Replacement Requested">Replacement Requested</option>
                        <option value="Scrapped at Site">Scrapped at Site</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Deduct Stock & Save</button>
            </div>
        </form>
    </div>
</div>
