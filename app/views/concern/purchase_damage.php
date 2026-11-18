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

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Purchase Damage Records (Excel & PDF)';
    $exportSubtitle = 'Download damaged material arrivals, supplier rejections, stock write-downs & claim audits';
    $exportUrl = \App\Core\Controller::url('/m/purchase_damage/export');
    $prefix = 'pdamage_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

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
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($damages)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No purchase damage records.</td></tr>
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
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewPurchaseDamageModal(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Defect Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditPurchaseDamageModal(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Defect Log">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/purchase_damage/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete damage record <?= htmlspecialchars($d['damage_no']) ?>? Stock will be restored.');">
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

<!-- Modal Edit Purchase Damage -->
<div class="modal fade" id="modalEditPurchaseDamage" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/purchase_damage/update') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_pdamage_id" value="">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Purchase Defect Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Damage No</label>
                    <input type="text" id="edit_pdamage_no" class="form-control font-monospace" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Defect Reason *</label>
                    <textarea name="reason" id="edit_pdamage_reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Resolution Action *</label>
                    <select name="action_taken" id="edit_pdamage_action" class="form-select" required>
                        <option value="Debit Note / Return to Supplier">Debit Note / Return to Supplier</option>
                        <option value="Replacement Requested">Replacement Requested</option>
                        <option value="Scrapped at Site">Scrapped at Site</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Damage Log</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View Purchase Damage -->
<div class="modal fade" id="modalViewPurchaseDamage" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 36px; height: 36px; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="bi bi-shield-x"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0">Purchase Defect Particulars</h6>
                        <small class="badge bg-danger font-monospace" id="view_pdamage_no"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="text-secondary small d-block">Quantity Written-off</span>
                        <span class="fw-bold fs-5 text-danger font-monospace" id="view_pdamage_qty">-0.00</span>
                    </div>
                    <div class="text-end">
                        <span class="text-secondary small d-block">Resolution Action</span>
                        <span class="badge bg-warning text-dark" id="view_pdamage_action">Debit Note</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-building text-primary me-2"></i>Supplier:</span>
                        <strong class="text-dark" id="view_pdamage_supplier">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-tag text-primary me-2"></i>Damaged Variant:</span>
                        <strong class="text-dark" id="view_pdamage_variant">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-upc text-primary me-2"></i>SKU Code:</span>
                        <span class="badge bg-light text-dark border font-monospace" id="view_pdamage_sku">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-geo-alt text-primary me-2"></i>Location:</span>
                        <span class="text-dark fw-semibold" id="view_pdamage_loc">Main Store</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-calendar-check text-primary me-2"></i>Timestamp:</span>
                        <span class="font-monospace text-dark" id="view_pdamage_date">-</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1"><i class="bi bi-chat-left-dots text-primary me-2"></i>Defect Reason:</span>
                        <div class="text-dark p-2 bg-light rounded" id="view_pdamage_reason">-</div>
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
function openViewPurchaseDamageModal(d) {
    if (!d) return;
    document.getElementById('view_pdamage_no').innerText = d.damage_no || '';
    document.getElementById('view_pdamage_supplier').innerText = d.supplier_name || '-';
    document.getElementById('view_pdamage_variant').innerText = d.variant_name || '-';
    document.getElementById('view_pdamage_sku').innerText = d.sku_code || '-';
    document.getElementById('view_pdamage_qty').innerText = '-' + parseFloat(d.qty || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('view_pdamage_loc').innerText = d.location || 'Main Store';
    document.getElementById('view_pdamage_action').innerText = d.action_taken || 'Debit Note';
    document.getElementById('view_pdamage_date').innerText = d.created_at || '-';
    document.getElementById('view_pdamage_reason').innerText = d.reason || 'None specified';

    const modal = new bootstrap.Modal(document.getElementById('modalViewPurchaseDamage'));
    modal.show();
}
function openEditPurchaseDamageModal(d) {
    if (!d) return;
    document.getElementById('edit_pdamage_id').value = d.id || '';
    document.getElementById('edit_pdamage_no').value = d.damage_no || '';
    document.getElementById('edit_pdamage_reason').value = d.reason || '';
    document.getElementById('edit_pdamage_action').value = d.action_taken || 'Debit Note / Return to Supplier';

    const modal = new bootstrap.Modal(document.getElementById('modalEditPurchaseDamage'));
    modal.show();
}
</script>
