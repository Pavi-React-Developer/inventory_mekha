<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Staff Register & Directory</h4>
            <p class="text-secondary small mb-0">
                Manage employees, workforce rosters, departments, and payroll profiles isolated by business concern.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSelectConcern">
                <i class="bi bi-building me-1"></i>
                <?= $activeConcern ? 'Concern: ' . htmlspecialchars($activeConcern['name']) : 'Choose Concern' ?>
                <i class="bi bi-chevron-down ms-1 small"></i>
            </button>
            <?php if ($activeConcern): ?>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddStaff">
                    <i class="bi bi-person-plus-fill me-1"></i> Add New Staff
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Concern Status Banner -->
    <?php if ($activeConcern): ?>
        <div class="alert alert-light border shadow-xs rounded-3 d-flex flex-wrap justify-content-between align-items-center py-2 px-3 mb-4" style="background: #f8fafc;">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1.5 font-monospace">
                    <?= htmlspecialchars($activeConcern['code']) ?>
                </span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($activeConcern['name']) ?></span>
                <span class="text-muted small">&bull; Showing <?= count($staffMembers) ?> Staff Member(s)</span>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSelectConcern">
                    <i class="bi bi-arrow-repeat me-1"></i> Switch Concern
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info border shadow-xs rounded-3 d-flex justify-content-between align-items-center py-3 px-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Select a Business Concern</h6>
                    <small class="text-secondary">Please pick an active business unit or concern to display its staff members.</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#modalSelectConcern">
                <i class="bi bi-building me-1"></i> Choose Concern Now
            </button>
        </div>
    <?php endif; ?>

    <!-- Export Intelligence Toolbar -->
    <?php
    $exportTitle = 'Export Staff Register (Excel & PDF)';
    $exportSubtitle = 'Download complete employee roster, department designations, contact numbers & salary structures';
    $exportUrl = \App\Core\Controller::url('/m/staff/export' . ($selectedCid ? '?concern_id=' . $selectedCid : ''));
    $prefix = 'staff_exp';
    include __DIR__ . '/../partials/export_card.php';
    ?>

    <!-- Staff Roster Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-people-fill me-2 text-primary"></i> Staff Members
                <?php if ($activeConcern): ?>
                    <span class="text-muted fw-normal small">for <?= htmlspecialchars($activeConcern['name']) ?></span>
                <?php endif; ?>
            </h6>
            <span class="badge bg-light text-dark border"><?= count($staffMembers) ?> Persons</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th class="ps-3">Staff Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Phone / Mobile</th>
                        <th>Salary Structure</th>
                        <th>Status</th>
                        <th>Joining Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-people display-6 d-block mb-2 text-secondary opacity-50"></i>
                                <?= $activeConcern ? 'No staff members recorded for this concern yet.' : 'Please select a concern above to load staff records.' ?>
                                <?php if ($activeConcern): ?>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddStaff">
                                            <i class="bi bi-person-plus-fill me-1"></i> Add First Staff Member
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staffMembers as $stf): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($stf['staff_code']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($stf['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($stf['email'] ?: 'No email') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        <?= htmlspecialchars($stf['department'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($stf['designation'] ?: '-') ?>
                                </td>
                                <td>
                                    <span class="font-monospace text-dark"><?= htmlspecialchars($stf['phone'] ?: '-') ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold font-monospace text-dark">₹<?= number_format($stf['base_salary'], 2) ?></div>
                                    <small class="text-muted text-uppercase" style="font-size: 0.72rem;"><?= htmlspecialchars(str_replace('_', ' ', $stf['salary_type'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($stf['status'] === 'active'): ?>
                                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-check-circle me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                            <i class="bi bi-dash-circle me-1"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted font-monospace">
                                    <?= $stf['joining_date'] ? date('d M Y', strtotime($stf['joining_date'])) : '-' ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick='openViewStaffModal(<?= json_encode($stf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Staff Profile">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditStaffModal(<?= json_encode($stf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Staff Details">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= \App\Core\Controller::url('/m/staff/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete staff member <?= htmlspecialchars($stf['name']) ?> (<?= htmlspecialchars($stf['staff_code']) ?>)?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= $stf['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Staff">
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

<!-- ========================================== -->
<!-- 1. POPUP CONCERN SELECTION MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="modalSelectConcern" tabindex="-1" aria-labelledby="modalSelectConcernLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-building"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Select Concern for Staff Register</h5>
                        <small class="text-secondary">Choose a concern to display and manage its staff members.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Each concern maintains its own separate staff directory. Select which concern's personnel you wish to view or update:
                </p>

                <div class="row g-3">
                    <?php foreach ($allConcerns as $c): ?>
                        <?php $isSelected = ($selectedCid == $c['id']); ?>
                        <div class="col-md-6">
                            <div class="card h-100 border p-3 rounded-3 shadow-xs position-relative <?= $isSelected ? 'border-primary bg-primary bg-opacity-10' : 'hover-shadow' ?>" 
                                 style="cursor: pointer; transition: all 0.2s;"
                                 onclick="window.location.href = '<?= \App\Core\Controller::url('/m/staff?concern_id=' . $c['id']) ?>'">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge <?= $isSelected ? 'bg-primary' : 'bg-dark' ?> font-monospace">
                                        <?= htmlspecialchars($c['code']) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-people me-1"></i> <?= (int)$c['staff_count'] ?> Staff
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($c['name']) ?></h6>
                                <small class="text-muted d-block mb-3">Status: <span class="text-success fw-semibold">Active Unit</span></small>
                                
                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold <?= $isSelected ? 'text-primary' : 'text-secondary' ?>">
                                        <?= $isSelected ? '<i class="bi bi-check-circle-fill me-1"></i> Currently Viewing' : 'View Staff Roster &rarr;' ?>
                                    </span>
                                    <span class="btn btn-sm <?= $isSelected ? 'btn-primary' : 'btn-outline-primary' ?> px-2.5 py-1">
                                        Select
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. ADD STAFF MODAL (REGISTER FORM) -->
<!-- ========================================== -->
<div class="modal fade" id="modalAddStaff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="<?= \App\Core\Controller::url('/m/staff/store') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header border-bottom px-4 py-3 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Register New Staff Member</h5>
                        <small class="text-white-50">Create staff profile & system login access</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- 1. WHICH CONCERN -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                        <i class="bi bi-building me-1 text-primary"></i> Which Concern *
                    </label>
                    <select name="concern_id" id="add_staff_concern_id" class="form-select" required>
                        <option value="">-- Choose Concern --</option>
                        <?php foreach ($allConcerns as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($selectedCid == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. FULL NAME & DESIGNATION -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-person me-1 text-primary"></i> Full Name *
                        </label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Kumar">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-briefcase me-1 text-primary"></i> Designation *
                        </label>
                        <input type="text" name="designation" class="form-control" required placeholder="e.g. Cutting Master / Tailor / Supervisor">
                    </div>
                </div>

                <!-- 3. EMAIL & PASSWORD -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-envelope me-1 text-primary"></i> Email Address *
                        </label>
                        <input type="email" name="email" class="form-control" required placeholder="e.g. ramesh@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-lock me-1 text-primary"></i> Password *
                        </label>
                        <div class="input-group">
                            <input type="password" name="password" id="add_staff_password" class="form-control border-end-0" required placeholder="Enter login password">
                            <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePasswordVisibility('add_staff_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. PHONE NUMBER & ACTIVE / INACTIVE STATUS -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-telephone me-1 text-primary"></i> Phone Number *
                        </label>
                        <input type="text" name="phone" class="form-control font-monospace" required placeholder="e.g. 9842100000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-toggle-on me-1 text-primary"></i> Active / Inactive Status *
                        </label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Save Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. EDIT STAFF MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditStaff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="<?= \App\Core\Controller::url('/m/staff/update') ?>" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="edit_staff_id" value="">

            <div class="modal-header border-bottom px-4 py-3 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Edit Staff Member</h5>
                        <small class="text-white-50 font-monospace" id="edit_staff_code_display"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- 1. WHICH CONCERN -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                        <i class="bi bi-building me-1 text-primary"></i> Which Concern *
                    </label>
                    <select name="concern_id" id="edit_staff_concern_id" class="form-select" required>
                        <?php foreach ($allConcerns as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. FULL NAME & DESIGNATION -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-person me-1 text-primary"></i> Full Name *
                        </label>
                        <input type="text" name="name" id="edit_staff_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-briefcase me-1 text-primary"></i> Designation *
                        </label>
                        <input type="text" name="designation" id="edit_staff_designation" class="form-control" required>
                    </div>
                </div>

                <!-- 3. EMAIL & PASSWORD -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-envelope me-1 text-primary"></i> Email Address *
                        </label>
                        <input type="email" name="email" id="edit_staff_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-lock me-1 text-primary"></i> Password (Optional)
                        </label>
                        <div class="input-group">
                            <input type="password" name="password" id="edit_staff_password" class="form-control border-end-0" placeholder="Leave blank to keep unchanged">
                            <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePasswordVisibility('edit_staff_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4. PHONE NUMBER & ACTIVE / INACTIVE STATUS -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-telephone me-1 text-primary"></i> Phone Number *
                        </label>
                        <input type="text" name="phone" id="edit_staff_phone" class="form-control font-monospace" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-1">
                            <i class="bi bi-toggle-on me-1 text-primary"></i> Active / Inactive Status *
                        </label>
                        <select name="status" id="edit_staff_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Update Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. VIEW STAFF MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="modalViewStaff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 38px; height: 38px; background: #e0f2fe; color: #0284c7; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="view_staff_name">-</h6>
                        <small class="badge bg-dark font-monospace" id="view_staff_code">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="text-secondary small d-block">Base Salary / Pay</span>
                        <span class="fw-bold fs-5 text-dark font-monospace" id="view_staff_salary">₹0.00</span>
                    </div>
                    <div class="text-end">
                        <span class="text-secondary small d-block">Status</span>
                        <span class="badge" id="view_staff_status_badge">Active</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-building text-primary me-2"></i>Concern:</span>
                        <strong class="text-dark" id="view_staff_concern">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-diagram-3 text-primary me-2"></i>Department:</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border" id="view_staff_department">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-briefcase text-primary me-2"></i>Designation:</span>
                        <strong class="text-dark" id="view_staff_designation">-</strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-telephone text-primary me-2"></i>Mobile / Phone:</span>
                        <span class="font-monospace text-dark fw-semibold" id="view_staff_phone">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-envelope text-primary me-2"></i>Email:</span>
                        <span class="text-dark" id="view_staff_email">-</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-calendar-event text-primary me-2"></i>Joining Date:</span>
                        <span class="font-monospace text-dark" id="view_staff_joining">-</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1"><i class="bi bi-geo-alt text-primary me-2"></i>Address:</span>
                        <div class="text-dark p-2 bg-light rounded" id="view_staff_address">None specified</div>
                    </li>
                </ul>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Profile
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($showConcernModal): ?>
        const modalEl = document.getElementById('modalSelectConcern');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    <?php endif; ?>
});

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}

function openViewStaffModal(stf) {
    if (!stf) return;
    const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
    setTxt('view_staff_name', stf.name || '');
    setTxt('view_staff_code', stf.staff_code || '');
    setTxt('view_staff_concern', (stf.concern_name || 'Concern') + (stf.concern_code ? ' (' + stf.concern_code + ')' : ''));
    setTxt('view_staff_designation', stf.designation || '-');
    setTxt('view_staff_phone', stf.phone || '-');
    setTxt('view_staff_email', stf.email || 'None');

    const badge = document.getElementById('view_staff_status_badge');
    if (badge) {
        if (stf.status === 'active') {
            badge.className = 'badge bg-success';
            badge.innerText = 'Active';
        } else {
            badge.className = 'badge bg-secondary';
            badge.innerText = 'Inactive';
        }
    }

    const modalEl = document.getElementById('modalViewStaff');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openEditStaffModal(stf) {
    if (!stf) return;
    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };

    setVal('edit_staff_id', stf.id || '');
    setTxt('edit_staff_code_display', stf.staff_code || '');
    setVal('edit_staff_concern_id', stf.concern_id || '');
    setVal('edit_staff_name', stf.name || '');
    setVal('edit_staff_email', stf.email || '');
    setVal('edit_staff_password', '');
    setVal('edit_staff_phone', stf.phone || '');
    setVal('edit_staff_designation', stf.designation || '');
    setVal('edit_staff_status', stf.status === 'inactive' ? 'inactive' : 'active');

    const modalEl = document.getElementById('modalEditStaff');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}
</script>
