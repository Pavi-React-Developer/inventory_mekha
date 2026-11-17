<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Stitching & Assembly Tracking</h4>
            <p class="text-secondary small mb-0">Record garment assembly, sewing line output, and needle breakage / stitching reject wastage.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddStitching">
            <i class="bi bi-layers me-1"></i> Log Stitching Batch
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-layers me-2 text-primary"></i> Sewing Floor Output</h6>
            <span class="badge bg-light text-dark border"><?= count($logs) ?> Runs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Log No</th>
                        <th>Program Ref</th>
                        <th>Target Garment</th>
                        <th>Input Cut Panels</th>
                        <th>Stitched Garments</th>
                        <th>Line Rejects (Wastage)</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No stitching batches logged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $sl): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($sl['log_no']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sl['program_no'] ?? 'Direct') ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($sl['produced_variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($sl['sku_code']) ?></small>
                                </td>
                                <td><?= number_format($sl['input_pieces']) ?> Panels</td>
                                <td><span class="fw-bold text-success fs-6"><?= number_format($sl['stitched_pieces']) ?> Pcs</span></td>
                                <td>
                                    <?php if ($sl['wastage_pieces'] > 0): ?>
                                        <span class="text-danger fw-semibold"><?= number_format($sl['wastage_pieces']) ?> Defective</span>
                                    <?php else: ?>
                                        <span class="text-success small">Zero Defect</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted font-monospace"><?= date('d M Y H:i', strtotime($sl['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddStitching" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/stitching_wastage/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Log Stitching Assembly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Production Program</label>
                    <select name="program_id" class="form-select">
                        <option value="">Ad-hoc Line Production</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= $prog['id'] ?>"><?= htmlspecialchars($prog['program_no']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Finished Garment Variant *</label>
                    <select name="produced_variant_id" class="form-select" required>
                        <option value="">Select Target Garment</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Input Cut Panels *</label>
                        <input type="number" name="input_pieces" class="form-control" required placeholder="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Stitched Units *</label>
                        <input type="number" name="stitched_pieces" class="form-control" required placeholder="98">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Reject Wastage</label>
                        <input type="number" name="wastage_pieces" class="form-control" value="0" placeholder="2">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes / Stitching Line</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Line 1 / Machine operator ref"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Stitching Output</button>
            </div>
        </form>
    </div>
</div>
