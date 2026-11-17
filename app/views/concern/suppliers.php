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
                        <?php if (empty($selectedCid)): ?><th>Concern</th><?php endif; ?>
                        <th>Code</th>
                        <th>Supplier / Contact Person</th>
                        <th>Company / Mill Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GSTIN</th>
                        <th>District</th>
                        <th>State</th>
                        <th>Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="<?= empty($selectedCid) ? 11 : 10 ?>" class="text-center py-4 text-muted">No suppliers registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $sup): ?>
                            <tr>
                                <?php if (empty($selectedCid)): ?>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= htmlspecialchars($sup['concern_name'] ?? ('Concern #' . $sup['concern_id'])) ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($sup['code'] ?: 'SUP') ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($sup['name']) ?></td>
                                <td><?= htmlspecialchars($sup['company_name'] ?: '-') ?></td>
                                <td>
                                    <?php if (!empty($sup['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($sup['phone']) ?>" class="text-decoration-none text-dark">
                                            <i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($sup['phone']) ?>
                                        </a>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($sup['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($sup['email']) ?>" class="text-decoration-none text-secondary small">
                                            <?= htmlspecialchars($sup['email']) ?>
                                        </a>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sup['gstin'] ?: '-') ?></span></td>
                                <td><span class="fw-semibold text-dark"><?= htmlspecialchars($sup['district'] ?: ($sup['city'] ?: '-')) ?></span></td>
                                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($sup['state'] ?: 'Tamil Nadu') ?></span></td>
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

<!-- Modal: Register New Supplier -->
<div class="modal fade" id="modalAddSupplier" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/m/suppliers/store') ?>" method="POST" class="modal-content needs-validation" id="formAddSupplier" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0">Register New Supplier</h5>
                    <small class="text-muted">Fill in all required details to onboard a new vendor</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($selectedCid)): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Concern <span class="text-danger">*</span></label>
                        <select name="concern_id" id="supplierConcernId" class="form-select" required>
                            <option value="">-- Choose Target Concern --</option>
                            <?php foreach ($allConcerns ?? [] as $con): ?>
                                <option value="<?= $con['id'] ?>"><?= htmlspecialchars($con['name']) ?> (<?= htmlspecialchars($con['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please choose the target business concern.</div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Contact Person / Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="supplierName" class="form-control" required minlength="2" maxlength="150" placeholder="e.g. Ramesh Kumar">
                    <div class="invalid-feedback">Contact person name is required (at least 2 letters).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Company / Mill Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" id="supplierCompany" class="form-control" required minlength="2" maxlength="150" placeholder="e.g. VIJA ENTERPRISES / Sri Balaji Textiles">
                    <div class="invalid-feedback">Company or Mill name is required.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text small bg-light text-muted">+91</span>
                            <input type="tel" name="phone" id="supplierPhone" class="form-control" required pattern="^[6-9][0-9]{9}$" maxlength="10" placeholder="9842100000">
                            <div class="invalid-feedback">10-digit Indian mobile (starts with 6-9).</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">GSTIN</label>
                        <input type="text" name="gstin" id="supplierGstin" class="form-control text-uppercase font-monospace" pattern="^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[1-9A-Za-z]{1}[Zz][0-9A-Za-z]{1}$" maxlength="15" placeholder="33AAAAA0000A1Z5">
                        <div class="invalid-feedback">15-char GST format (e.g. 33AAAAA0000A1Z5).</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="supplierEmail" class="form-control" required placeholder="vendor@mill.com">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Address / Street <span class="text-danger">*</span></label>
                    <textarea name="address" id="supplierAddress" class="form-control" rows="2" required minlength="3" placeholder="Door No, Street Name, Area (e.g. Valvarmangalam)"></textarea>
                    <div class="invalid-feedback">Address is required (at least 3 characters).</div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                        <input type="text" name="district" id="supplierDistrict" class="form-control" required minlength="2" placeholder="e.g. Karur / Tirupur">
                        <div class="invalid-feedback">District name is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state" id="supplierState" class="form-select" required>
                            <option value="Tamil Nadu" selected>Tamil Nadu</option>
                            <option value="Kerala">Kerala</option>
                            <option value="Karnataka">Karnataka</option>
                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                            <option value="Telangana">Telangana</option>
                            <option value="Maharashtra">Maharashtra</option>
                            <option value="Gujarat">Gujarat</option>
                            <option value="Delhi">Delhi</option>
                            <option value="Rajasthan">Rajasthan</option>
                            <option value="West Bengal">West Bengal</option>
                            <option value="Other">Other State</option>
                        </select>
                        <div class="invalid-feedback">Please select the state.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSubmitSupplier">
                    <i class="bi bi-save me-1"></i> Save Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formAddSupplier');
    const phoneInput = document.getElementById('supplierPhone');
    const gstinInput = document.getElementById('supplierGstin');

    if (phoneInput) {
        // Enforce digits only and max 10 digits
        phoneInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    if (gstinInput) {
        // Enforce uppercase and trim for GSTIN
        gstinInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 15);
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            // Trim inputs
            ['supplierName', 'supplierCompany', 'supplierEmail', 'supplierAddress', 'supplierDistrict'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el) el.value = el.value.trim();
            });

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                // Focus on first invalid input
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add('was-validated');
        }, false);
    }
});
</script>
