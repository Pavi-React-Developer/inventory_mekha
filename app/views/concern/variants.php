<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Product Variants Catalog</h4>
            <p class="text-secondary small mb-0">Master catalog holding all attributes (size, weight, fabric, GSM, prices) with strict concern isolation.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddVariant">
            <i class="bi bi-plus-circle me-1"></i> Add New Variant
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tags me-2 text-primary"></i> Master Variants Register</h6>
            <span class="badge bg-light text-dark border"><?= count($variants) ?> Variants</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>SKU Code</th>
                        <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                            <th>Concern</th>
                        <?php endif; ?>
                        <th>Variant Details</th>
                        <th>Category / Subcategory</th>
                        <th>Brand / Size</th>
                        <th>Standard Price / MRP</th>
                        <th>Attributes (JSON)</th>
                        <th>Live Stock</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variants)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No variants created yet. Click "Add New Variant" above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($variants as $v): 
                            $attrs = !empty($v['attributes']) ? json_decode($v['attributes'], true) : [];
                            $qrPayload = \App\Services\QrService::generatePayload($v['category_id'], $v['subcategory_id'], $v['id']);
                        ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($v['sku_code']) ?></span>
                                    <div class="text-muted small font-monospace mt-1" style="font-size: 0.68rem;">ID: #<?= (int)$v['id'] ?></div>
                                </td>
                                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            <?= htmlspecialchars($v['concern_name'] ?? 'Concern #' . $v['concern_id']) ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($v['name']) ?></div>
                                    <small class="text-muted"><?= number_format($v['weight'], 3) ?> <?= htmlspecialchars($v['weight_unit']) ?></small>
                                </td>
                                <td>
                                    <div class="text-dark small fw-medium"><?= htmlspecialchars($v['category_name'] ?? 'Uncategorized') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($v['subcategory_name'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($v['brand_name'] ?? '-') ?></div>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($v['size'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">₹<?= number_format($v['price'], 2) ?></div>
                                    <small class="text-muted text-decoration-line-through">₹<?= number_format($v['mrp'], 2) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($attrs)): ?>
                                        <?php foreach ($attrs as $k => $val): ?>
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($k) ?>: <?= htmlspecialchars($val) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-2 py-1">
                                        <?= number_format($v['total_stock'], 2) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= \App\Core\Controller::url('/m/label_generation?variant_id=' . $v['id']) ?>" class="btn btn-sm btn-outline-primary" title="Print Labels">
                                        <i class="bi bi-qr-code"></i> Print Label
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Variant -->
<div class="modal fade" id="modalAddVariant" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="<?= \App\Core\Controller::url('/m/variants/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Create Master Product Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($isSuperAdmin && empty($selectedCid)): ?>
                    <div class="mb-3 p-2 bg-light border rounded">
                        <label class="form-label small fw-bold text-primary">Assign To Concern *</label>
                        <select name="concern_id" class="form-select" required>
                            <option value="">-- Choose Concern --</option>
                            <?php foreach ($allConcerns as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">SKU Code *</label>
                        <input type="text" name="sku_code" class="form-control text-uppercase" placeholder="e.g. SKU-TSH-001" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Product Variant Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bio-Washed Combed Cotton T-Shirt" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Subcategory *</label>
                        <select name="subcategory_id" class="form-select" required>
                            <option value="">Select Subcategory</option>
                            <?php foreach ($subcategories as $sc): ?>
                                <option value="<?= $sc['id'] ?>"><?= htmlspecialchars($sc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">No Brand</option>
                            <?php foreach ($brands as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Size</label>
                        <input type="text" name="size" class="form-control" placeholder="M, L, XL, Free">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Unit Weight</label>
                        <input type="number" step="0.001" name="weight" class="form-control" placeholder="0.000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Weight Unit</label>
                        <select name="weight_unit" class="form-select">
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="meter">Meters</option>
                            <option value="roll">Rolls</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Creation Date</label>
                        <input type="date" name="creation_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Base Price (₹) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">MRP (₹) *</label>
                        <input type="number" step="0.01" name="mrp" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <h6 class="fw-bold small text-secondary mt-3 mb-2">Dynamic JSON Attributes:</h6>
                <div class="row g-2 p-3 bg-light rounded border">
                    <div class="col-md-4">
                        <label class="form-label small">Color</label>
                        <input type="text" name="attr_color" class="form-control form-control-sm" placeholder="e.g. Navy Blue">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Fabric / Material</label>
                        <input type="text" name="attr_fabric" class="form-control form-control-sm" placeholder="e.g. 100% Cotton">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">GSM / Density</label>
                        <input type="text" name="attr_gsm" class="form-control form-control-sm" placeholder="e.g. 180 GSM">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Variant</button>
            </div>
        </form>
    </div>
</div>
