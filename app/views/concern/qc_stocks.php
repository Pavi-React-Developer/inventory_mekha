<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Quality Control & Stock Approval</h4>
            <p class="text-secondary small mb-0">Inspect sewn garments. Approved / passed quantities are automatically added into live warehouse stock via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddQc">
            <i class="bi bi-shield-check me-1"></i> New QC Inspection
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i> Inspection Register</h6>
            <span class="badge bg-light text-dark border"><?= count($inspections) ?> Audits</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>QC No</th>
                        <th>Inspected Variant</th>
                        <th>Inspected Total</th>
                        <th>Passed (+Stock)</th>
                        <th>Rejected</th>
                        <th>Location</th>
                        <th>Defect Observations</th>
                        <th>Inspector</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inspections)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No QC inspection records.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inspections as $qc): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($qc['qc_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($qc['variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($qc['sku_code']) ?></small>
                                </td>
                                <td><?= number_format($qc['inspected_qty'], 2) ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-2 py-1">
                                        +<?= number_format($qc['passed_qty'], 2) ?> Passed
                                    </span>
                                </td>
                                <td>
                                    <?php if ($qc['rejected_qty'] > 0): ?>
                                        <span class="text-danger fw-bold">-<?= number_format($qc['rejected_qty'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($qc['location']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($qc['defect_notes'] ?: 'No defects') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($qc['inspector_name'] ?? 'QC Lead') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddQc" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/qc_stocks/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Record Quality Inspection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Variant Inspected *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Inspected Qty *</label>
                        <input type="number" step="0.01" name="inspected_qty" class="form-control" required placeholder="100.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Passed (Moves to Stock) *</label>
                        <input type="number" step="0.01" name="passed_qty" class="form-control" required placeholder="95.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Rejected Qty</label>
                        <input type="number" step="0.01" name="rejected_qty" class="form-control" value="0.00" placeholder="5.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Stock Godown / Bay</label>
                    <input type="text" name="location" class="form-control" value="Main Store">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Defect Notes / Quality Feedback</label>
                    <textarea name="defect_notes" class="form-control" rows="2" placeholder="Stitching skip, measurements deviation"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Approve Passed Qty into Live Stock</button>
            </div>
        </form>
    </div>
</div>
