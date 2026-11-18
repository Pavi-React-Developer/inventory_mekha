<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Concern Internal Damage & Write-offs</h4>
            <p class="text-secondary small mb-0">Record internal shop-floor spoilage, water damage, or warehouse shelf breakage. Deducts stock via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddConcernDamage">
            <i class="bi bi-x-octagon me-1"></i> Log Internal Damage
        </button>
    </div>

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Internal Concern Damage (Excel & PDF)';
    $exportSubtitle = 'Download warehouse write-offs, scrap explanations, stock reductions & financial loss valuations';
    $exportUrl = \App\Core\Controller::url('/m/concern_damage/export');
    $prefix = 'cdmg_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-x-octagon me-2 text-danger"></i> Internal Damage Logs</h6>
            <span class="badge bg-light text-dark border"><?= count($damages) ?> Incidents</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Damage No</th>
                        <th>Variant</th>
                        <th>Location</th>
                        <th>Write-off Quantity</th>
                        <th>Damage Explanation</th>
                        <th>Logged By</th>
                        <th>Timestamp</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($damages)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No internal damages logged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($damages as $d): ?>
                            <tr>
                                <td><span class="badge bg-danger font-monospace"><?= htmlspecialchars($d['damage_no']) ?></span></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($d['variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($d['sku_code']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($d['location']) ?></td>
                                <td><span class="text-danger fw-bold fs-6">-<?= number_format($d['qty'], 2) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($d['reason'] ?: '-') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($d['user_name'] ?? 'Staff') ?></td>
                                <td class="small text-muted font-monospace"><?= date('d M Y H:i', strtotime($d['created_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewConcernDamageModal(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Write-off Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditConcernDamageModal(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Explanation">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/concern_damage/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete internal damage <?= htmlspecialchars($d['damage_no']) ?>? Stock will be restored.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Damage">
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

<div class="modal fade" id="modalAddConcernDamage" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/concern_damage/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Log Internal Stock Write-off</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                        <label class="form-label small fw-semibold">Quantity to Write-off *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Storage Location</label>
                        <input type="text" name="location" class="form-control" value="Main Store">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Internal Damage *</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Storage leak, rodent bite, handling fall" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Write-off & Deduct Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Concern Damage -->
<div class="modal fade" id="modalEditConcernDamage" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/concern_damage/update') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_cdamage_id" value="">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Write-off Explanation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Damage No</label>
                    <input type="text" id="edit_cdamage_no" class="form-control font-monospace" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Internal Damage *</label>
                    <textarea name="reason" id="edit_cdamage_reason" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Damage Explanation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View Concern Damage -->
<div class="modal fade" id="modalViewConcernDamage" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 36px; height: 36px; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="bi bi-x-circle"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0">Internal Stock Write-off Details</h6>
                        <small class="badge bg-danger font-monospace" id="view_cdamage_no"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="text-secondary small d-block">Quantity Deducted</span>
                        <span class="fw-bold fs-5 text-danger font-monospace" id="view_cdamage_qty">-0.00</span>
                    </div>
                    <div class="text-end">
                        <span class="text-secondary small d-block">Storage Location</span>
                        <span class="badge bg-light text-dark border" id="view_cdamage_loc">Main Store</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-tag text-primary me-2"></i>Variant / Item:</span>
                        <strong class="text-dark" id="view_cdamage_variant">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-upc text-primary me-2"></i>SKU Code:</span>
                        <span class="badge bg-light text-dark border font-monospace" id="view_cdamage_sku">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-person text-primary me-2"></i>Logged By:</span>
                        <span class="text-dark" id="view_cdamage_user">Staff</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-clock-history text-primary me-2"></i>Logged At:</span>
                        <span class="font-monospace text-dark" id="view_cdamage_date">-</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1"><i class="bi bi-chat-left-dots text-primary me-2"></i>Damage Explanation:</span>
                        <div class="text-dark p-2 bg-light rounded" id="view_cdamage_reason">-</div>
                    </li>
                </ul>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openViewConcernDamageModal(d) {
    if (!d) return;
    document.getElementById('view_cdamage_no').innerText = d.damage_no || '';
    document.getElementById('view_cdamage_variant').innerText = d.variant_name || '-';
    document.getElementById('view_cdamage_sku').innerText = d.sku_code || '-';
    document.getElementById('view_cdamage_qty').innerText = '-' + parseFloat(d.qty || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('view_cdamage_loc').innerText = d.location || 'Main Store';
    document.getElementById('view_cdamage_user').innerText = d.user_name || 'Staff';
    document.getElementById('view_cdamage_date').innerText = d.created_at || '-';
    document.getElementById('view_cdamage_reason').innerText = d.reason || 'None specified';

    const modal = new bootstrap.Modal(document.getElementById('modalViewConcernDamage'));
    modal.show();
}
function openEditConcernDamageModal(d) {
    if (!d) return;
    document.getElementById('edit_cdamage_id').value = d.id || '';
    document.getElementById('edit_cdamage_no').value = d.damage_no || '';
    document.getElementById('edit_cdamage_reason').value = d.reason || '';

    const modal = new bootstrap.Modal(document.getElementById('modalEditConcernDamage'));
    modal.show();
}
</script>
