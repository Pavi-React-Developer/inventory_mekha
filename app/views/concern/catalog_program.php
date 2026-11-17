<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Production Catalog & Scheduling Programs</h4>
            <p class="text-secondary small mb-0">Seasonal garment catalogs and planned production orders scheduled across cutting and stitching lines.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddProgram">
            <i class="bi bi-calendar-plus me-1"></i> New Production Program
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-bookmark me-2 text-primary"></i> Production Programs Register</h6>
            <span class="badge bg-light text-dark border"><?= count($programs) ?> Programs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Program No</th>
                        <th>Catalog</th>
                        <th>Target Garment Variant</th>
                        <th>Planned Production Qty</th>
                        <th>Start Date</th>
                        <th>Target Completion</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No production programs registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($programs as $p): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($p['program_no']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['catalog_name'] ?? 'General') ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['target_variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($p['sku_code']) ?></small>
                                </td>
                                <td><span class="fw-bold text-primary fs-6"><?= number_format($p['planned_qty'], 2) ?> Units</span></td>
                                <td><?= date('d M Y', strtotime($p['start_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($p['target_date'])) ?></td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'completed' ? 'bg-success' : 'bg-primary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddProgram" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/catalog_program/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Schedule Production Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Finished Variant *</label>
                    <select name="target_variant_id" class="form-select" required>
                        <option value="">Select Variant to Produce</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Catalog / Season Name</label>
                    <input type="text" name="catalog_name" class="form-control" value="Winter Collection 2026">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Planned Quantity (Units) *</label>
                    <input type="number" step="0.01" name="planned_qty" class="form-control" required placeholder="500.00">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Completion Date</label>
                        <input type="date" name="target_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule Program</button>
            </div>
        </form>
    </div>
</div>
