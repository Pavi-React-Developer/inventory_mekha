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

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Production Programs (Excel & PDF)';
    $exportSubtitle = 'Download seasonal styles, production schedules, planned quantities & fulfillment status';
    $exportUrl = \App\Core\Controller::url('/m/catalog_program/export');
    $prefix = 'prog_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

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
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No production programs registered.</td></tr>
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
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewProgramModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Program Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditProgramModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Program">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/catalog_program/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete production program <?= htmlspecialchars($p['program_no']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Program">
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

<!-- Modal Edit Program -->
<div class="modal fade" id="modalEditProgram" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/catalog_program/update') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_program_id" value="">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Production Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Program No</label>
                    <input type="text" id="edit_program_no" class="form-control font-monospace" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Planned Production Qty *</label>
                    <input type="number" step="0.01" name="planned_qty" id="edit_program_qty" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Start Date *</label>
                        <input type="date" name="start_date" id="edit_program_start" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Completion *</label>
                        <input type="date" name="target_date" id="edit_program_target" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" id="edit_program_status" class="form-select" required>
                        <option value="planned">Planned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View Program -->
<div class="modal fade" id="modalViewProgram" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye text-info me-2"></i> Production Program Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Program No</span>
                    <span class="font-monospace fw-bold fs-6 text-primary" id="view_program_no">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Target Variant</span>
                    <span class="fw-semibold text-dark" id="view_program_variant">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Catalog / Season</span>
                    <span class="fw-semibold text-dark" id="view_program_catalog">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Planned Quantity</span>
                    <span class="fw-bold fs-6 text-success" id="view_program_qty">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="text-muted small">Schedule Window</span>
                    <span class="font-monospace text-secondary small" id="view_program_window">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Status</span>
                    <span class="badge" id="view_program_status">-</span>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openViewProgramModal(p) {
    if (!p) return;
    document.getElementById('view_program_no').innerText = p.program_no || '-';
    document.getElementById('view_program_variant').innerText = (p.sku_code ? p.sku_code + ' - ' : '') + (p.variant_name || '-');
    document.getElementById('view_program_catalog').innerText = p.catalog_name || '-';
    document.getElementById('view_program_qty').innerText = (parseFloat(p.planned_qty || 0).toLocaleString()) + ' Units';
    const sDate = p.start_date ? p.start_date.substring(0, 10) : '-';
    const tDate = p.target_date ? p.target_date.substring(0, 10) : '-';
    document.getElementById('view_program_window').innerText = sDate + ' -> ' + tDate;
    
    const badge = document.getElementById('view_program_status');
    badge.innerText = (p.status || 'draft').toUpperCase();
    badge.className = 'badge ' + (p.status === 'completed' ? 'bg-success' : (p.status === 'in_progress' ? 'bg-primary' : (p.status === 'cancelled' ? 'bg-danger' : 'bg-secondary')));

    const modal = new bootstrap.Modal(document.getElementById('modalViewProgram'));
    modal.show();
}

function openEditProgramModal(p) {
    if (!p) return;
    document.getElementById('edit_program_id').value = p.id || '';
    document.getElementById('edit_program_no').value = p.program_no || '';
    document.getElementById('edit_program_qty').value = parseFloat(p.planned_qty || 0).toFixed(2);
    document.getElementById('edit_program_start').value = p.start_date ? p.start_date.substring(0, 10) : '';
    document.getElementById('edit_program_target').value = p.target_date ? p.target_date.substring(0, 10) : '';
    document.getElementById('edit_program_status').value = p.status || 'draft';

    const modal = new bootstrap.Modal(document.getElementById('modalEditProgram'));
    modal.show();
}
</script>
