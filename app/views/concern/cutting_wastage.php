<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Fabric Cutting & Wastage Tracking</h4>
            <p class="text-secondary small mb-0">Record fabric lay cutting, cut piece generation, and scrap / edge wastage. Consumes raw fabric from live stock via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddCutting">
            <i class="bi bi-scissors me-1"></i> Log Cutting Job
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-scissors me-2 text-primary"></i> Cutting Operations Register</h6>
            <span class="badge bg-light text-dark border"><?= count($logs) ?> Jobs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Log No</th>
                        <th>Program Ref</th>
                        <th>Raw Fabric Consumed</th>
                        <th>Cut Pieces Generated</th>
                        <th>Wastage Scrap</th>
                        <th>Wastage %</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No cutting jobs recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $cl): 
                            $pct = $cl['consumed_qty'] > 0 ? ($cl['wastage_qty'] / $cl['consumed_qty']) * 100 : 0;
                        ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($cl['log_no']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cl['program_no'] ?? 'Unscheduled') ?></span></td>
                                <td>
                                    <div class="fw-bold text-danger">-<?= number_format($cl['consumed_qty'], 2) ?> <?= htmlspecialchars($cl['wastage_unit']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($cl['raw_variant_name']) ?></small>
                                </td>
                                <td><span class="fw-bold text-success fs-6"><?= number_format($cl['cut_pieces']) ?> Cut Panels</span></td>
                                <td><span class="text-danger fw-semibold"><?= number_format($cl['wastage_qty'], 2) ?> <?= htmlspecialchars($cl['wastage_unit']) ?></span></td>
                                <td>
                                    <span class="badge <?= $pct > 5 ? 'bg-danger' : 'bg-success' ?>">
                                        <?= number_format($pct, 1) ?>%
                                    </span>
                                </td>
                                <td class="small text-muted font-monospace"><?= date('d M Y H:i', strtotime($cl['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddCutting" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/cutting_wastage/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Log Fabric Cutting Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Production Program</label>
                    <select name="program_id" class="form-select">
                        <option value="">Ad-hoc / Manual Cutting Job</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= $prog['id'] ?>"><?= htmlspecialchars($prog['program_no']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Raw Fabric Variant (Deducts Stock) *</label>
                    <select name="raw_variant_id" class="form-select" required>
                        <option value="">Select Fabric Variant</option>
                        <?php foreach ($rawVariants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Fabric Consumed (kg/m) *</label>
                        <input type="number" step="0.01" name="consumed_qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Panels / Cut Pieces Output *</label>
                        <input type="number" name="cut_pieces" class="form-control" required placeholder="e.g. 250">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Fabric Wastage Scrap (kg)</label>
                    <input type="number" step="0.01" name="wastage_qty" class="form-control" value="0.00" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Cutting Bay Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Lay height, marker efficiency"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Deduct Fabric & Save Cutting Log</button>
            </div>
        </form>
    </div>
</div>
