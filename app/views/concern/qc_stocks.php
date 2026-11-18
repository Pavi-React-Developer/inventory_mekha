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

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Quality Control Audits (Excel & PDF)';
    $exportSubtitle = 'Download QC inspections, pass/reject rates, approved inventory additions & quality benchmarks';
    $exportUrl = \App\Core\Controller::url('/m/qc_stocks/export');
    $prefix = 'qc_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

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
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inspections)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No QC inspection records.</td></tr>
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
                                <td><small class="text-muted"><?= htmlspecialchars($qc['defect_notes'] ?: 'No defects') ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars($qc['inspector_name'] ?? 'QC Lead') ?></small></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewQcModal(<?= json_encode($qc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View QC Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditQcModal(<?= json_encode($qc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Notes">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/qc_stocks/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete QC inspection <?= htmlspecialchars($qc['qc_no']) ?>? Passed stock will be deducted from inventory.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= $qc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete QC Record">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
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

<!-- Modal Edit QC -->
<div class="modal fade" id="modalEditQc" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/qc_stocks/update') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_qc_id" value="">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit QC Defect Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">QC Inspection No</label>
                    <input type="text" id="edit_qc_no" class="form-control font-monospace" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Defect Notes / Quality Feedback *</label>
                    <textarea name="defect_notes" id="edit_qc_defect_notes" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update QC Notes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View QC -->
<div class="modal fade" id="modalViewQc" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye text-info me-2"></i> QC Inspection Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">QC Inspection No</span>
                    <span class="font-monospace fw-bold fs-6 text-primary" id="view_qc_no">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Program / Job</span>
                    <span class="badge bg-light text-dark border" id="view_qc_program">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Product SKU</span>
                    <span class="fw-semibold text-dark" id="view_qc_product">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Inspected Total</span>
                    <span class="fw-bold fs-6 text-secondary" id="view_qc_inspected">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">QC Passed (Good Stock)</span>
                    <span class="fw-bold fs-6 text-success" id="view_qc_passed">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Rejected Defect Stock</span>
                    <span class="fw-bold fs-6 text-danger" id="view_qc_rejected">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Storage Location</span>
                    <span class="fw-semibold text-dark" id="view_qc_location">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Inspector</span>
                    <span class="fw-semibold text-dark" id="view_qc_inspector">-</span>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block mb-1">Defect Notes & Observations</span>
                    <div class="p-2 bg-light rounded text-dark small" id="view_qc_notes">-</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openViewQcModal(qc) {
    if (!qc) return;
    document.getElementById('view_qc_no').innerText = qc.qc_no || '-';
    document.getElementById('view_qc_program').innerText = qc.program_no || 'Direct Finished Inspection';
    document.getElementById('view_qc_product').innerText = (qc.sku_code ? qc.sku_code + ' - ' : '') + (qc.variant_name || '-');
    document.getElementById('view_qc_inspected').innerText = parseFloat(qc.inspected_qty || 0).toLocaleString();
    document.getElementById('view_qc_passed').innerText = '+' + parseFloat(qc.passed_qty || 0).toLocaleString();
    document.getElementById('view_qc_rejected').innerText = '-' + parseFloat(qc.rejected_qty || 0).toLocaleString();
    document.getElementById('view_qc_location').innerText = qc.location || '-';
    document.getElementById('view_qc_inspector').innerText = qc.inspector_name || 'QC Lead';
    document.getElementById('view_qc_notes').innerText = qc.defect_notes || 'No defect notes recorded.';

    const modal = new bootstrap.Modal(document.getElementById('modalViewQc'));
    modal.show();
}

function openEditQcModal(qc) {
    if (!qc) return;
    document.getElementById('edit_qc_id').value = qc.id || '';
    document.getElementById('edit_qc_no').value = qc.qc_no || '';
    document.getElementById('edit_qc_defect_notes').value = qc.defect_notes || '';

    const modal = new bootstrap.Modal(document.getElementById('modalEditQc'));
    modal.show();
}
</script>
