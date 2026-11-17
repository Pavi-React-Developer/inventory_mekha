<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Supplier Directory</h4>
            <p class="text-secondary small mb-0">Vendors, spinning mills, dye-houses and yarn suppliers registered for this concern.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSupplier">
            <i class="bi bi-plus-circle me-1"></i> Add Supplier
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-truck me-2 text-primary"></i> Registered Suppliers</h6>
            <span class="badge bg-light text-dark border"><?= count($suppliers) ?> Suppliers</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Supplier / Contact Person</th>
                        <th>Company Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GSTIN</th>
                        <th>Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No suppliers registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $sup): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($sup['code'] ?: 'SUP') ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($sup['name']) ?></td>
                                <td><?= htmlspecialchars($sup['company_name'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($sup['phone'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($sup['email'] ?: '-') ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sup['gstin'] ?: '-') ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($sup['address'] ?: '-') ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddSupplier" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/suppliers/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Contact Person / Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Kumar">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Company / Mill Name</label>
                    <input type="text" name="company_name" class="form-control" placeholder="e.g. Sri Balaji Textiles">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="9842100000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">GSTIN</label>
                        <input type="text" name="gstin" class="form-control text-uppercase" placeholder="33AAAAA0000A1Z5">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="vendor@mill.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>
