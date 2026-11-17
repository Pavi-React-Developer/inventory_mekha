<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Dispatch & Logistics Management</h4>
            <p class="text-secondary small mb-0">Record transport logistics, vehicle registrations, and airway bill / courier tracking numbers.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddDispatch">
            <i class="bi bi-send me-1"></i> New Dispatch Log
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-truck-front me-2 text-primary"></i> Dispatches & Shipments</h6>
            <span class="badge bg-light text-dark border"><?= count($dispatches) ?> Shipments</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Dispatch No</th>
                        <th>Outward Gate Pass</th>
                        <th>Customer</th>
                        <th>Transporter</th>
                        <th>Vehicle No</th>
                        <th>Tracking / LR No</th>
                        <th>Dispatch Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dispatches)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No dispatch logs recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dispatches as $d): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($d['dispatch_no']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($d['outward_no']) ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($d['customer_name']) ?></td>
                                <td><?= htmlspecialchars($d['transporter_name'] ?: '-') ?></td>
                                <td><span class="badge bg-light text-dark font-monospace"><?= htmlspecialchars($d['vehicle_no'] ?: '-') ?></span></td>
                                <td><span class="font-monospace text-primary"><?= htmlspecialchars($d['tracking_no'] ?: '-') ?></span></td>
                                <td><?= date('d M Y', strtotime($d['dispatch_date'])) ?></td>
                                <td>
                                    <span class="badge <?= $d['status'] === 'delivered' ? 'bg-success' : 'bg-primary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $d['status'])) ?>
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

<div class="modal fade" id="modalAddDispatch" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/dispatch/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Record Dispatch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Outward Gate Pass *</label>
                    <select name="outward_id" class="form-select" required>
                        <option value="">Select Prepared Outward Consignment</option>
                        <?php foreach ($outwards as $ow): ?>
                            <option value="<?= $ow['id'] ?>"><?= htmlspecialchars($ow['outward_no']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Transporter / Courier</label>
                        <input type="text" name="transporter_name" class="form-control" placeholder="e.g. VRL Logistics, DTDC">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Vehicle Number</label>
                        <input type="text" name="vehicle_no" class="form-control text-uppercase" placeholder="TN 38 AB 1234">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tracking / LR Number</label>
                        <input type="text" name="tracking_no" class="form-control" placeholder="LR-987654">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Dispatch Date</label>
                        <input type="date" name="dispatch_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Driver phone, contact, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Dispatch</button>
            </div>
        </form>
    </div>
</div>
