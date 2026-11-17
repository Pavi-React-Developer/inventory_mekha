<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Supplier Directory</h4>
            <p class="text-secondary small mb-0">Vendors, spinning mills, dye-houses and yarn suppliers registered for this concern.</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-2 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddSupplier">
            <i class="bi bi-plus-circle fs-6"></i> Add Supplier
        </button>
    </div>

    <!-- Quick Stats Summary Cards -->
    <?php
    $totalCount = count($suppliers);
    $activeCount = 0;
    $tnCount = 0;
    $otherStateCount = 0;
    foreach ($suppliers as $s) {
        if (($s['status'] ?? 'active') === 'active') $activeCount++;
        if (stripos($s['state'] ?? '', 'Tamil') !== false) {
            $tnCount++;
        } else {
            $otherStateCount++;
        }
    }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Suppliers</span>
                        <h4 class="fw-bold mb-0 text-dark mt-1"><?= $totalCount ?></h4>
                    </div>
                    <div class="rounded-circle p-3 bg-primary-subtle text-primary">
                        <i class="bi bi-truck fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Active Vendors</span>
                        <h4 class="fw-bold mb-0 text-success mt-1"><?= $activeCount ?></h4>
                    </div>
                    <div class="rounded-circle p-3 bg-success-subtle text-success">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">In-State (TN)</span>
                        <h4 class="fw-bold mb-0 text-primary mt-1"><?= $tnCount ?></h4>
                    </div>
                    <div class="rounded-circle p-3 bg-info-subtle text-info">
                        <i class="bi bi-geo-alt fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Inter-State</span>
                        <h4 class="fw-bold mb-0 text-secondary mt-1"><?= $otherStateCount ?></h4>
                    </div>
                    <div class="rounded-circle p-3 bg-secondary-subtle text-secondary">
                        <i class="bi bi-globe fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table Card with Live Filter -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Registered Suppliers Directory</h6>
                <span class="badge bg-light text-dark border ms-1" id="supplierBadgeCount"><?= $totalCount ?> Suppliers</span>
            </div>
            <!-- Search & Filter Controls -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="supplierSearchInput" class="form-control border-start-0" placeholder="Search suppliers, phone, GST...">
                </div>
                <select id="supplierDistrictFilter" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">All Districts</option>
                    <?php
                    $districts = array_unique(array_filter(array_column($suppliers, 'district')));
                    sort($districts);
                    foreach ($districts as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom" id="tableSuppliers">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 40px;">#</th>
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
                        <th class="text-end pe-3" style="min-width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="supplierTableBody">
                    <?php if (empty($suppliers)): ?>
                        <tr id="noSuppliersRow"><td colspan="<?= empty($selectedCid) ? 13 : 12 ?>" class="text-center py-4 text-muted">No suppliers registered. Click "Add Supplier" to register one.</td></tr>
                    <?php else: ?>
                        <?php $idx = 1; foreach ($suppliers as $sup): ?>
                            <tr class="supplier-row"
                                data-name="<?= strtolower(htmlspecialchars($sup['name'])) ?>"
                                data-company="<?= strtolower(htmlspecialchars($sup['company_name'] ?? '')) ?>"
                                data-phone="<?= htmlspecialchars($sup['phone'] ?? '') ?>"
                                data-email="<?= strtolower(htmlspecialchars($sup['email'] ?? '')) ?>"
                                data-gstin="<?= strtolower(htmlspecialchars($sup['gstin'] ?? '')) ?>"
                                data-district="<?= strtolower(htmlspecialchars($sup['district'] ?? ($sup['city'] ?? ''))) ?>">
                                <td class="text-muted small"><?= $idx++ ?></td>
                                <?php if (empty($selectedCid)): ?>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= htmlspecialchars($sup['concern_name'] ?? ('Concern #' . $sup['concern_id'])) ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($sup['code'] ?: 'SUP') ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($sup['name']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 text-dark">
                                        <i class="bi bi-building text-muted small"></i>
                                        <span><?= htmlspecialchars($sup['company_name'] ?: '-') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($sup['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($sup['phone']) ?>" class="text-decoration-none text-dark fw-semibold small">
                                            <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($sup['phone']) ?>
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
                                <td class="small text-muted" title="<?= htmlspecialchars($sup['address'] ?: '') ?>" style="max-width: 180px;">
                                    <div class="text-truncate"><?= htmlspecialchars($sup['address'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <?php if (($sup['status'] ?? 'active') === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <!-- CRUD Action Buttons -->
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary py-1 px-2"
                                                title="Edit Supplier"
                                                onclick="openEditSupplierModal(<?= htmlspecialchars(json_encode($sup)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger py-1 px-2"
                                                title="Delete Supplier"
                                                onclick="openDeleteSupplierModal(<?= (int)$sup['id'] ?>, '<?= htmlspecialchars(addslashes($sup['name'])) ?>', '<?= htmlspecialchars(addslashes($sup['company_name'] ?? '')) ?>')">
                                            <i class="bi bi-trash3"></i>
                                        </button>
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

<!-- Modal 1: Register New Supplier (Create) -->
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

<!-- Modal 2: Edit Supplier (Update) -->
<div class="modal fade" id="modalEditSupplier" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/m/suppliers/update') ?>" method="POST" class="modal-content needs-validation" id="formEditSupplier" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="supplier_id" id="editSupplierId" value="">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-pencil-square text-primary me-1"></i> Edit Supplier Details
                    </h5>
                    <small class="text-muted" id="editSupplierCodeBadge"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Contact Person / Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editSupplierName" class="form-control" required minlength="2" maxlength="150">
                    <div class="invalid-feedback">Contact person name is required.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Company / Mill Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" id="editSupplierCompany" class="form-control" required minlength="2" maxlength="150">
                    <div class="invalid-feedback">Company or Mill name is required.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text small bg-light text-muted">+91</span>
                            <input type="tel" name="phone" id="editSupplierPhone" class="form-control" required pattern="^[6-9][0-9]{9}$" maxlength="10">
                            <div class="invalid-feedback">10-digit Indian mobile (starts with 6-9).</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">GSTIN</label>
                        <input type="text" name="gstin" id="editSupplierGstin" class="form-control text-uppercase font-monospace" pattern="^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[1-9A-Za-z]{1}[Zz][0-9A-Za-z]{1}$" maxlength="15">
                        <div class="invalid-feedback">15-char GST format.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="editSupplierEmail" class="form-control" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Address / Street <span class="text-danger">*</span></label>
                    <textarea name="address" id="editSupplierAddress" class="form-control" rows="2" required minlength="3"></textarea>
                    <div class="invalid-feedback">Address is required.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">District <span class="text-danger">*</span></label>
                        <input type="text" name="district" id="editSupplierDistrict" class="form-control" required minlength="2">
                        <div class="invalid-feedback">District is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="state" id="editSupplierState" class="form-select" required>
                            <option value="Tamil Nadu">Tamil Nadu</option>
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
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" id="editSupplierStatus" class="form-select">
                        <option value="active">Active (Available for Purchase Billing)</option>
                        <option value="inactive">Inactive (Archived)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Update Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Delete Supplier Confirmation (Delete) -->
<div class="modal fade" id="modalDeleteSupplier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/m/suppliers/delete') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="supplier_id" id="deleteSupplierId" value="">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i> Delete Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-1 text-dark">Are you sure you want to delete supplier <strong id="deleteSupplierName" class="text-dark"></strong>?</p>
                <small class="text-muted d-block">
                    If this supplier has recorded purchase bills, their status will be safely changed to <strong>Inactive</strong> to preserve financial audit history without deleting accounting records.
                </small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger px-4 fw-semibold">
                    <i class="bi bi-trash3 me-1"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter Suppliers Table
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('supplierSearchInput');
    const districtFilter = document.getElementById('supplierDistrictFilter');
    const rows = document.querySelectorAll('.supplier-row');
    const badgeCount = document.getElementById('supplierBadgeCount');

    function filterTable() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const district = (districtFilter.value || '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(function (row) {
            const name = row.dataset.name || '';
            const company = row.dataset.company || '';
            const phone = row.dataset.phone || '';
            const email = row.dataset.email || '';
            const gstin = row.dataset.gstin || '';
            const rowDistrict = row.dataset.district || '';

            const matchesQuery = !query || name.includes(query) || company.includes(query) || phone.includes(query) || email.includes(query) || gstin.includes(query) || rowDistrict.includes(query);
            const matchesDistrict = !district || rowDistrict === district;

            if (matchesQuery && matchesDistrict) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (badgeCount) {
            badgeCount.textContent = visibleCount + ' Suppliers';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (districtFilter) districtFilter.addEventListener('change', filterTable);

    // Enforce phone and GSTIN input masks in Add Form
    const phoneInput = document.getElementById('supplierPhone');
    const gstinInput = document.getElementById('supplierGstin');
    if (phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        });
    }
    if (gstinInput) {
        gstinInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 15);
        });
    }

    // Enforce phone and GSTIN input masks in Edit Form
    const editPhone = document.getElementById('editSupplierPhone');
    const editGstin = document.getElementById('editSupplierGstin');
    if (editPhone) {
        editPhone.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        });
    }
    if (editGstin) {
        editGstin.addEventListener('input', function (e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 15);
        });
    }
});

// Edit Supplier Modal Handler
function openEditSupplierModal(sup) {
    document.getElementById('editSupplierId').value = sup.id || '';
    document.getElementById('editSupplierCodeBadge').textContent = 'Code: ' + (sup.code || 'SUP');
    document.getElementById('editSupplierName').value = sup.name || '';
    document.getElementById('editSupplierCompany').value = sup.company_name || '';
    document.getElementById('editSupplierPhone').value = (sup.phone || '').replace(/\D/g, '');
    document.getElementById('editSupplierGstin').value = sup.gstin || '';
    document.getElementById('editSupplierEmail').value = sup.email || '';
    document.getElementById('editSupplierAddress').value = sup.address || '';
    document.getElementById('editSupplierDistrict').value = sup.district || sup.city || '';
    document.getElementById('editSupplierState').value = sup.state || 'Tamil Nadu';
    document.getElementById('editSupplierStatus').value = sup.status || 'active';

    const modal = new bootstrap.Modal(document.getElementById('modalEditSupplier'));
    modal.show();
}

// Delete Supplier Modal Handler
function openDeleteSupplierModal(id, name, company) {
    document.getElementById('deleteSupplierId').value = id;
    document.getElementById('deleteSupplierName').textContent = name + (company ? ' (' + company + ')' : '');
    const modal = new bootstrap.Modal(document.getElementById('modalDeleteSupplier'));
    modal.show();
}
</script>
