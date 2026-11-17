<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Customer Directory</h4>
            <p class="text-secondary small mb-0">Retail chains, wholesalers, dealers, and corporate buyers for this concern.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddCustomer">
            <i class="bi bi-person-plus me-1"></i> Add Customer
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i> Customer List</h6>
            <span class="badge bg-light text-dark border"><?= count($customers) ?> Customers</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Customer / Buyer Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GSTIN</th>
                        <th>City / Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No customers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($c['code'] ?: 'CUST') ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($c['gstin'] ?: 'Unregistered') ?></span></td>
                                <td>
                                    <div><?= htmlspecialchars($c['city'] ?: '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['address'] ?: '') ?></small>
                                </td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddCustomer" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/customers/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Customer / Company Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Trends Retail Group">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="9876543210">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">GSTIN</label>
                        <input type="text" name="gstin" class="form-control text-uppercase" placeholder="33AAAAA0000A1Z5">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="procure@trends.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">City</label>
                        <input type="text" name="city" class="form-control" placeholder="Chennai">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Delivery Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Street, State, PIN"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>
