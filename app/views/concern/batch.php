<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Production Batch & Lot Tracking</h4>
            <p class="text-secondary small mb-0">Manage production lots, manufacturing and expiry dates per variant.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBatch">
            <i class="bi bi-collection me-1"></i> Initialize Batch
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-collection me-2 text-primary"></i> Active Batches</h6>
            <span class="badge bg-light text-dark border"><?= count($batches) ?> Batches</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Batch No</th>
                        <th>Variant</th>
                        <th>Batch Size / Qty</th>
                        <th>Manufacturing Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No batches registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['batch_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($b['variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($b['sku_code']) ?></small>
                                </td>
                                <td><span class="fw-bold text-primary"><?= number_format($b['batch_qty'], 2) ?> <?= htmlspecialchars($b['weight_unit']) ?></span></td>
                                <td><?= date('d M Y', strtotime($b['mfg_date'])) ?></td>
                                <td><?= $b['exp_date'] ? date('d M Y', strtotime($b['exp_date'])) : '<span class="text-muted">None</span>' ?></td>
                                <td><span class="badge bg-success"><?= ucfirst($b['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddBatch" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/batch/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Initialize New Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">Select Variant</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Batch Planned Qty *</label>
                    <input type="number" step="0.01" name="batch_qty" class="form-control" required placeholder="0.00">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mfg Date</label>
                        <input type="date" name="mfg_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Exp Date</label>
                        <input type="date" name="exp_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Batch</button>
            </div>
        </form>
    </div>
</div>
