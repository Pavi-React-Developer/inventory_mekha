<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Enterprise Asset Management</h4>
            <p class="text-secondary small mb-0">Track fixed equipment, machinery, factory fixtures, and company assets across all concerns.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddAsset">
            <i class="bi bi-plus-circle me-1"></i> Add Company Asset
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building-gear me-2 text-primary"></i> Fixed Assets Register</h6>
            <span class="badge bg-light text-dark border"><?= count($assets) ?> Assets Listed</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Name & Description</th>
                        <th>Category</th>
                        <th>Assigned Concern</th>
                        <th>Purchase Cost</th>
                        <th>Current Valuation</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No company assets registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assets as $a): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($a['asset_code']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($a['notes'] ?: '-') ?></small>
                                </td>
                                <td><?= htmlspecialchars($a['category'] ?: 'General') ?></td>
                                <td>
                                    <?php if ($a['concern_name']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            <?= htmlspecialchars($a['concern_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">Corporate HQ</span>
                                    <?php endif; ?>
                                </td>
                                <td>₹<?= number_format($a['purchase_cost'], 2) ?></td>
                                <td class="fw-bold text-dark">₹<?= number_format($a['current_value'], 2) ?></td>
                                <td><?= htmlspecialchars($a['location'] ?: '-') ?></td>
                                <td>
                                    <span class="badge <?= $a['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= ucfirst($a['status']) ?>
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

<!-- Modal Add Asset -->
<div class="modal fade" id="modalAddAsset" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/admin/assets/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-lg me-1"></i> Register Company Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Asset Code *</label>
                    <input type="text" name="asset_code" class="form-control text-uppercase" placeholder="e.g. AST-MCH-001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Asset Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Brother Automatic Overlock Machine" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="Machinery">Machinery</option>
                            <option value="IT Equipment">IT Equipment</option>
                            <option value="Vehicles">Vehicles</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Building">Building & Fixtures</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Assign Concern</label>
                        <select name="concern_id" class="form-select">
                            <option value="">HQ Corporate (Unassigned)</option>
                            <?php foreach ($concerns as $con): ?>
                                <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Purchase Cost (₹)</label>
                        <input type="number" step="0.01" name="purchase_cost" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Current Value (₹)</label>
                        <input type="number" step="0.01" name="current_value" class="form-control" required placeholder="0.00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Location / Room</label>
                        <input type="text" name="location" class="form-control" placeholder="Floor 1, Bay B">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Maintenance Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Warranty or AMC details"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>
