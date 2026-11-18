<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Inward Stock Consignments</h4>
            <p class="text-secondary small mb-0">Record incoming raw materials or vendor receipts. Immediately credits live inventory balance via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddInward">
            <i class="bi bi-box-arrow-in-down me-1"></i> Receive Inward Consignment
        </button>
    </div>

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Inward Stock Receipts (Excel & PDF)';
    $exportSubtitle = 'Download warehouse arrivals, supplier delivery challans, item quantities & receiver audit trails';
    $exportUrl = \App\Core\Controller::url('/m/inward/export');
    $prefix = 'inw_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i> Inward Consignments Log</h6>
            <span class="badge bg-light text-dark border"><?= count($inwards) ?> Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Inward No</th>
                        <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                            <th>Concern</th>
                        <?php endif; ?>
                        <th>Supplier</th>
                        <th>Inward Date</th>
                        <th>Warehouse Bay</th>
                        <th>Items Received</th>
                        <th>Total Inward Qty</th>
                        <th>Received By</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inwards)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No inward consignments received yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inwards as $inw): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($inw['inward_no']) ?></span></td>
                                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            <?= htmlspecialchars($inw['concern_name'] ?? 'Concern #' . $inw['concern_id']) ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($inw['supplier_name'] ?? 'Direct Receipt') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($inw['remarks'] ?: '-') ?></small>
                                </td>
                                <td><?= date('d M Y', strtotime($inw['inward_date'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($inw['location']) ?></span></td>
                                <td><?= (int)$inw['items_count'] ?> Line Items</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-2 py-1">
                                        +<?= number_format($inw['total_qty'], 2) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($inw['user_name'] ?? 'Staff') ?></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewInwardModal(<?= json_encode($inw, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Inward Consignment">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditInwardModal(<?= json_encode($inw, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Inward Remarks">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/inward/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete inward consignment <?= htmlspecialchars($inw['inward_no']) ?>? This will reverse stock additions.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= $inw['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Inward">
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

<!-- Modal Add Inward -->
<div class="modal fade" id="modalAddInward" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/inward/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down me-1 text-primary"></i> Receive Stock Inward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                    <div class="mb-3 p-2 bg-light border rounded">
                        <label class="form-label small fw-bold text-primary">Inward To Concern *</label>
                        <select name="concern_id" class="form-select" required>
                            <option value="">-- Choose Concern --</option>
                            <?php foreach ($allConcerns as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">Direct Internal Consignment (No Supplier)</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Variant *</label>
                    <select name="variant_id" class="form-select" required>
                        <option value="">-- Choose Variant to Inward --</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['size'] ?: 'Standard') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Received Quantity *</label>
                        <input type="number" step="0.01" name="qty" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit Cost Rate (₹)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Inward Date</label>
                        <input type="date" name="inward_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Location / Godown</label>
                        <input type="text" name="location" class="form-control" value="Main Store">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Remarks / Gate Entry Ref</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Delivery challan or truck ref"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Process Inward & Credit Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Inward -->
<div class="modal fade" id="modalEditInward" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/inward/update') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_inward_id" value="">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Stock Inward Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Inward No</label>
                    <input type="text" id="edit_inward_no" class="form-control font-monospace" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Inward Date *</label>
                    <input type="date" name="inward_date" id="edit_inward_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Remarks / Gate Entry Notes</label>
                    <textarea name="remarks" id="edit_inward_remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Inward</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View Inward -->
<div class="modal fade" id="modalViewInward" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 36px; height: 36px; background: #e0f2fe; color: #0284c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0">Stock Inward Consignment</h6>
                        <small class="badge bg-dark font-monospace" id="view_inward_no"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="text-secondary small d-block">Total Units Credited</span>
                        <span class="fw-bold fs-5 text-success font-monospace" id="view_inward_qty">+0.00</span>
                    </div>
                    <div class="text-end">
                        <span class="text-secondary small d-block">Items Included</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border fs-6" id="view_inward_items_count">0 Lines</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-building text-primary me-2"></i>Supplier:</span>
                        <strong class="text-dark" id="view_inward_supplier">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-geo-alt text-primary me-2"></i>Storage Location:</span>
                        <span class="badge bg-light text-dark border" id="view_inward_loc">Main Store</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-calendar-check text-primary me-2"></i>Inward Date:</span>
                        <span class="font-monospace text-dark" id="view_inward_date">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-person text-primary me-2"></i>Received By:</span>
                        <span class="text-dark" id="view_inward_user">Staff</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1"><i class="bi bi-card-text text-primary me-2"></i>Remarks / Gate Entry Notes:</span>
                        <div class="text-dark p-2 bg-light rounded" id="view_inward_remarks">None</div>
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
function openViewInwardModal(inw) {
    if (!inw) return;
    document.getElementById('view_inward_no').innerText = inw.inward_no || '';
    document.getElementById('view_inward_supplier').innerText = inw.supplier_name || 'Direct Receipt';
    document.getElementById('view_inward_loc').innerText = inw.location || 'Main Store';
    document.getElementById('view_inward_date').innerText = inw.inward_date ? inw.inward_date.substring(0, 10) : '-';
    document.getElementById('view_inward_user').innerText = inw.user_name || 'Staff';
    document.getElementById('view_inward_qty').innerText = '+' + parseFloat(inw.total_qty || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('view_inward_items_count').innerText = (inw.items_count || 1) + ' Items';
    document.getElementById('view_inward_remarks').innerText = inw.remarks || 'None specified';

    const modal = new bootstrap.Modal(document.getElementById('modalViewInward'));
    modal.show();
}
function openEditInwardModal(inw) {
    if (!inw) return;
    document.getElementById('edit_inward_id').value = inw.id || '';
    document.getElementById('edit_inward_no').value = inw.inward_no || '';
    document.getElementById('edit_inward_date').value = inw.inward_date ? inw.inward_date.substring(0, 10) : '';
    document.getElementById('edit_inward_remarks').value = inw.remarks || '';

    const modal = new bootstrap.Modal(document.getElementById('modalEditInward'));
    modal.show();
}
</script>
