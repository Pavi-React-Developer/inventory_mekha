<?php
// Group operational modules to match Images 1 & 2 hierarchy exactly
$groupOrder = ['Dashboard', 'Purchase', 'Production', 'Stock', 'Sales', 'Shop', 'Finance', 'Staff'];
$groupedModules = [];
foreach ($modules as $m) {
    if ($m['code'] === 'cash') continue; // Unified under 'Cash & Cash Flow' (cash_flow)
    if ($m['code'] === 'variants' || $m['group_name'] === 'Masters') continue;
    $grp = $m['group_name'] ?: 'General';
    $groupedModules[$grp][] = $m;
}
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item text-muted">Staff</li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/m/staff') ?>" class="text-decoration-none text-muted">Staff Register</a></li>
            <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Add New Staff</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 46px; height: 46px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; border: 1px solid #bfdbfe;">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Add New Staff Member</h4>
                <p class="text-secondary small mb-0">Register employee profile, business concern assignment, role permissions & system login credentials.</p>
            </div>
        </div>
        <div>
            <a href="<?= \App\Core\Controller::url('/m/staff' . ($selectedCid ? '?concern_id=' . $selectedCid : '')) ?>" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
                <i class="bi bi-arrow-left"></i> Back to All Staff
            </a>
        </div>
    </div>

    <!-- Main Registration Form Card -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11 col-xl-10">
            <div class="card border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-vcard text-primary fs-5"></i> Employee Details & Account Credentials
                    </h6>
                    <a href="<?= \App\Core\Controller::url('/m/staff/create' . ($selectedCid ? '?concern_id=' . $selectedCid : '')) ?>" 
                       class="btn btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" 
                       style="font-size: 0.78rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; text-decoration: none;">
                        <i class="bi bi-plus-lg"></i> Add New
                    </a>
                </div>

                <form action="<?= \App\Core\Controller::url('/m/staff/store') ?>" method="POST" class="card-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <!-- 1. BUSINESS CONCERN ASSIGNMENT -->
                    <div class="mb-4 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <label class="form-label small fw-bold text-dark text-uppercase mb-1" style="font-size: 0.76rem; letter-spacing: 0.05em;">
                            <i class="bi bi-building me-1 text-primary"></i> 1. Assigned Primary Business Concern / Location *
                        </label>
                        <select name="concern_id" id="staff_concern_id" class="form-select mb-2" required style="border-radius: 8px;" onchange="onPrimaryConcernChanged(this.value)">
                            <option value="">-- Choose Primary Concern / Base Branch --</option>
                            <?php foreach ($allConcerns as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ($selectedCid == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mb-3" style="font-size: 0.74rem;">The staff member's primary home unit and payroll reporting entity.</small>

                        <!-- Additional Authorized Concerns Option -->
                        <div class="pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <label class="small fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                                    <i class="bi bi-buildings-fill text-primary"></i> Authorized Business Concerns (Multi-Location Access Option)
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 0.72rem;" onclick="selectAllCreateConcerns(true)">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0.5 px-2" style="font-size: 0.72rem;" onclick="selectOnlyPrimaryConcern()">Only Primary</button>
                                </div>
                            </div>
                            <p class="text-secondary small mb-2" style="font-size: 0.73rem;">
                                Choose which business units or retail outlets this staff member can switch between upon login:
                            </p>
                            <div class="row g-2" id="create_concern_checkboxes_grid">
                                <?php foreach ($allConcerns as $c): ?>
                                    <?php $isPrimary = ($selectedCid == $c['id']); ?>
                                    <div class="col-md-6">
                                        <div class="p-2.5 border rounded-3 bg-white d-flex align-items-center gap-2.5 concern-check-card" id="box_cid_<?= $c['id'] ?>" style="transition: all 0.18s ease; cursor: pointer;">
                                            <input class="form-check-input create-concern-check m-0" type="checkbox" name="concern_ids[]" value="<?= (int)$c['id'] ?>" id="create_cid_<?= $c['id'] ?>" <?= $isPrimary ? 'checked' : '' ?> style="width: 17px; height: 17px; cursor: pointer;">
                                            <label class="form-check-label flex-grow-1 cursor-pointer mb-0 small d-flex justify-content-between align-items-center" for="create_cid_<?= $c['id'] ?>" style="cursor: pointer;">
                                                <span class="fw-semibold text-dark" style="font-size: 0.82rem;"><?= htmlspecialchars($c['name']) ?></span>
                                                <div class="d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.68rem;"><?= htmlspecialchars($c['code']) ?></span>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-0.5 px-1.5 primary-label-<?= $c['id'] ?>" style="font-size: 0.62rem; display: <?= $isPrimary ? 'inline-block' : 'none' ?>;">Home</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 2. FULL NAME & DESIGNATION -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-person me-1 text-primary"></i> Full Name *
                            </label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Kumar" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-briefcase me-1 text-primary"></i> Designation / Title *
                            </label>
                            <input type="text" name="designation" class="form-control" required placeholder="e.g. Cashier / Cutting Master / Manager" style="border-radius: 8px;">
                        </div>
                    </div>

                    <!-- 3. DEPARTMENT & ROLE -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-diagram-2 me-1 text-primary"></i> Department *
                            </label>
                            <select name="department" class="form-select" required style="border-radius: 8px;">
                                <option value="General" selected>General / Administration</option>
                                <option value="Sales / Retail Counter">Sales / Retail Counter</option>
                                <option value="Production & Cutting">Production & Cutting</option>
                                <option value="Stitching & Assembly">Stitching & Assembly</option>
                                <option value="Warehouse & Inventory">Warehouse & Inventory</option>
                                <option value="Quality Control (QC)">Quality Control (QC)</option>
                                <option value="Finance & Accounts">Finance & Accounts</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> System Access Role
                            </label>
                            <select name="role_id" id="staff_role_id" class="form-select" style="border-radius: 8px;" onchange="onRoleChangedInCreate(this.value)">
                                <option value="">-- Choose Role / Custom Permissions --</option>
                                <?php foreach ($roles as $r): ?>
                                    <?php if ($r['id'] == 1) continue; // Skip super admin ?>
                                    <option value="<?= (int)$r['id'] ?>">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 4. EMAIL & LOGIN PASSWORD -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-envelope me-1 text-primary"></i> Email Address (Login ID) *
                            </label>
                            <input type="email" name="email" class="form-control" required placeholder="staff@example.com" style="border-radius: 8px;">
                            <small class="text-muted" style="font-size: 0.72rem;">Used for user authentication into the ERP.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-lock me-1 text-primary"></i> Password *
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="staff_create_password" class="form-control border-end-0" required placeholder="Enter login password" style="border-radius: 8px 0 0 8px;">
                                <button class="btn btn-outline-secondary border-start-0" type="button" onclick="toggleCreatePassword(this)" style="border-radius: 0 8px 8px 0;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Minimum 6 characters recommended.</small>
                        </div>
                    </div>

                    <!-- 5. PHONE NUMBER & STATUS -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-telephone me-1 text-primary"></i> Mobile / Phone Number *
                            </label>
                            <input type="tel" name="phone" maxlength="10" class="form-control font-monospace" required placeholder="10-digit mobile number" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.74rem;">
                                <i class="bi bi-toggle-on me-1 text-primary"></i> Employment Status *
                            </label>
                            <select name="status" class="form-select" required style="border-radius: 8px;">
                                <option value="active" selected>Active (Can login & operate)</option>
                                <option value="inactive">Inactive (Disabled)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4 opacity-50">

<style>
/* Module CRUD Permissions Matrix - Neatly Aligned & Separated Checkboxes */
.perm-matrix-table th {
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    padding: 10px 8px;
    vertical-align: middle;
}
.perm-matrix-table td {
    padding: 7px 8px;
    vertical-align: middle;
}
.perm-matrix-col-view {
    background: rgba(59, 130, 246, 0.025);
    border-left: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
}
.perm-matrix-col-add {
    background: rgba(16, 185, 129, 0.025);
    border-right: 1px solid #f1f5f9;
}
.perm-matrix-col-edit {
    background: rgba(245, 158, 11, 0.025);
    border-right: 1px solid #f1f5f9;
}
.perm-matrix-col-delete {
    background: rgba(239, 68, 68, 0.025);
    border-right: 1px solid #f1f5f9;
}

/* Clean Standalone Checkboxes */
.matrix-perm-check {
    width: 20px !important;
    height: 20px !important;
    cursor: pointer !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    margin: 0 !important;
}
.matrix-perm-view:checked {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
}
.matrix-perm-add:checked {
    background-color: #059669 !important;
    border-color: #059669 !important;
}
.matrix-perm-edit:checked {
    background-color: #d97706 !important;
    border-color: #d97706 !important;
}
.matrix-perm-delete:checked {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
}

/* Row Hover */
.matrix-mod-row:hover {
    background-color: #f8fafc !important;
}

/* Concern Selection Card Hover & Active */
.concern-check-card:hover {
    border-color: #93c5fd !important;
    background-color: #f8fafc !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}
.concern-check-card:has(input:checked) {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
}
</style>

                    <!-- 6. MODULE ACCESS & CRUD PERMISSIONS MATRIX -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-check text-primary fs-5"></i> 6. Module Access & CRUD Permissions (View, Create, Edit, Delete)
                                </h6>
                                <small class="text-secondary">Configure specific View, Create, Edit, and Delete operational rights across all project modules.</small>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setAllMatrixPerms(true)">
                                    <i class="bi bi-check-square me-1"></i> Full Access (All)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setAllMatrixReadOnly()">
                                    <i class="bi bi-eye me-1"></i> Read-Only (View All)
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-xs py-1 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setAllMatrixPerms(false)">
                                    <i class="bi bi-x-square me-1"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Column Quick Toggles -->
                        <div class="d-flex align-items-center justify-content-between p-2.5 bg-light rounded-3 mb-3 border flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold text-secondary text-uppercase d-flex align-items-center gap-1" style="font-size: 0.71rem;">
                                    <i class="bi bi-toggles text-primary"></i> Column Toggles:
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setColumnMatrixPerm('view', true)">All View</button>
                                <button type="button" class="btn btn-sm btn-outline-success py-0.5 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setColumnMatrixPerm('add', true)">All Create</button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0.5 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setColumnMatrixPerm('edit', true)">All Edit</button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2.5 rounded-pill fw-semibold" style="font-size: 0.74rem;" onclick="setColumnMatrixPerm('delete', true)">All Delete</button>
                            </div>
                            <div>
                                <span class="text-muted small" id="perm_sync_role_text" style="font-size: 0.74rem;">
                                    <i class="bi bi-info-circle me-1"></i> Selecting a System Role will auto-populate defaults.
                                </span>
                            </div>
                        </div>

                        <!-- Matrix Table with Separated Checkboxes -->
                        <div class="table-responsive rounded-3 border">
                            <table class="table table-bordered table-sm align-middle mb-0 perm-matrix-table" id="createStaffMatrixTable" style="font-size: 0.82rem;">
                                <thead class="bg-light text-secondary text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th style="width: 40%; vertical-align: middle;" class="ps-3">
                                            <span class="fw-bold">Module / Feature</span>
                                        </th>
                                        <th style="width: 15%;" class="text-center text-primary bg-primary bg-opacity-10 border-start border-end py-2">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                                <div class="fw-bold">
                                                    <i class="bi bi-eye-fill me-1"></i> View
                                                </div>
                                                <input type="checkbox" id="header_check_view" class="form-check-input m-0 header-col-check" style="width: 18px; height: 18px; cursor: pointer;" onchange="toggleHeaderColumn('view', this.checked)" title="Select all View">
                                            </div>
                                        </th>
                                        <th style="width: 15%;" class="text-center text-success bg-success bg-opacity-10 border-end py-2">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                                <div class="fw-bold">
                                                    <i class="bi bi-plus-circle-fill me-1"></i> Create (Add)
                                                </div>
                                                <input type="checkbox" id="header_check_add" class="form-check-input m-0 header-col-check" style="width: 18px; height: 18px; cursor: pointer;" onchange="toggleHeaderColumn('add', this.checked)" title="Select all Create">
                                            </div>
                                        </th>
                                        <th style="width: 15%;" class="text-center text-warning bg-warning bg-opacity-10 text-dark border-end py-2">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                                <div class="fw-bold">
                                                    <i class="bi bi-pencil-fill me-1"></i> Edit
                                                </div>
                                                <input type="checkbox" id="header_check_edit" class="form-check-input m-0 header-col-check" style="width: 18px; height: 18px; cursor: pointer;" onchange="toggleHeaderColumn('edit', this.checked)" title="Select all Edit">
                                            </div>
                                        </th>
                                        <th style="width: 15%;" class="text-center text-danger bg-danger bg-opacity-10 border-end py-2">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                                <div class="fw-bold">
                                                    <i class="bi bi-trash-fill me-1"></i> Delete
                                                </div>
                                                <input type="checkbox" id="header_check_delete" class="form-check-input m-0 header-col-check" style="width: 18px; height: 18px; cursor: pointer;" onchange="toggleHeaderColumn('delete', this.checked)" title="Select all Delete">
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupOrder as $grpName): ?>
                                        <?php if (empty($groupedModules[$grpName])) continue; ?>
                                        <tr class="table-secondary">
                                            <td colspan="5" class="fw-bold text-dark py-1.5 px-3 text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.05em; background: #e2e8f0;">
                                                <i class="bi bi-folder2-open me-1.5 text-primary"></i> <?= htmlspecialchars($grpName) ?>
                                            </td>
                                        </tr>
                                        <?php foreach ($groupedModules[$grpName] as $m): $mid = (int)$m['id']; ?>
                                            <tr id="matrix_row_<?= $mid ?>" class="matrix-mod-row" data-mid="<?= $mid ?>">
                                                <td class="ps-3 py-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi <?= htmlspecialchars($m['icon'] ?: 'bi-app') ?> text-primary fs-6"></i>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.84rem;"><?= htmlspecialchars($m['name']) ?></span>
                                                    </div>
                                                </td>
                                                <!-- View -->
                                                <td class="text-center perm-matrix-col-view">
                                                    <div class="d-flex justify-content-center align-items-center m-0">
                                                        <input class="form-check-input matrix-perm-check matrix-perm-view matrix-row-<?= $mid ?>" 
                                                               type="checkbox" name="perms[<?= $mid ?>][view]" value="1" id="m_view_<?= $mid ?>" checked
                                                               onchange="updateHeaderCheckboxesState()">
                                                    </div>
                                                </td>
                                                <!-- Create -->
                                                <td class="text-center perm-matrix-col-add">
                                                    <div class="d-flex justify-content-center align-items-center m-0">
                                                        <input class="form-check-input matrix-perm-check matrix-perm-add matrix-row-<?= $mid ?>" 
                                                               type="checkbox" name="perms[<?= $mid ?>][add]" value="1" id="m_add_<?= $mid ?>"
                                                               onchange="updateHeaderCheckboxesState()">
                                                    </div>
                                                </td>
                                                <!-- Edit -->
                                                <td class="text-center perm-matrix-col-edit">
                                                    <div class="d-flex justify-content-center align-items-center m-0">
                                                        <input class="form-check-input matrix-perm-check matrix-perm-edit matrix-row-<?= $mid ?>" 
                                                               type="checkbox" name="perms[<?= $mid ?>][edit]" value="1" id="m_edit_<?= $mid ?>"
                                                               onchange="updateHeaderCheckboxesState()">
                                                    </div>
                                                </td>
                                                <!-- Delete -->
                                                <td class="text-center perm-matrix-col-delete">
                                                    <div class="d-flex justify-content-center align-items-center m-0">
                                                        <input class="form-check-input matrix-perm-check matrix-perm-delete matrix-row-<?= $mid ?>" 
                                                               type="checkbox" name="perms[<?= $mid ?>][delete]" value="1" id="m_delete_<?= $mid ?>"
                                                               onchange="updateHeaderCheckboxesState()">
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= \App\Core\Controller::url('/m/staff' . ($selectedCid ? '?concern_id=' . $selectedCid : '')) ?>" class="btn btn-light rounded-pill px-4 text-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: #4f46e5; border-color: #4f46e5;">
                            <i class="bi bi-check-circle-fill me-1"></i> Register Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.allRolePerms = <?= json_encode($allRolePerms ?? []) ?>;
window.concernModules = <?= json_encode($concernModules ?? []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const primaryId = document.getElementById('staff_concern_id')?.value;
    if (primaryId) {
        onPrimaryConcernChanged(primaryId);
    }
    const roleId = document.getElementById('staff_role_id')?.value;
    if (roleId) {
        onRoleChangedInCreate(roleId);
    }
    updateHeaderCheckboxesState();
});

function toggleHeaderColumn(action, checked) {
    document.querySelectorAll('.matrix-perm-' + action).forEach(cb => {
        cb.checked = checked;
    });
    updateHeaderCheckboxesState();
}

function toggleMasterSelectAll(checked) {
    document.querySelectorAll('.matrix-perm-check').forEach(cb => {
        cb.checked = checked;
    });
    updateHeaderCheckboxesState();
}

function updateHeaderCheckboxesState() {
    const actions = ['view', 'add', 'edit', 'delete'];
    let allCheckedCount = 0;
    let totalAllCount = 0;

    actions.forEach(act => {
        const cbs = document.querySelectorAll('.matrix-perm-' + act);
        const headerCb = document.getElementById('header_check_' + act);
        if (!cbs.length || !headerCb) return;

        let checkedCount = 0;
        cbs.forEach(cb => {
            if (cb.checked) checkedCount++;
        });

        totalAllCount += cbs.length;
        allCheckedCount += checkedCount;

        if (checkedCount === cbs.length && cbs.length > 0) {
            headerCb.checked = true;
            headerCb.indeterminate = false;
        } else {
            headerCb.checked = false;
            headerCb.indeterminate = false;
        }
    });

    const masterCb = document.getElementById('header_master_check_all');
    if (masterCb && totalAllCount > 0) {
        masterCb.checked = (allCheckedCount === totalAllCount && totalAllCount > 0);
        masterCb.indeterminate = false;
    }
}

function onPrimaryConcernChanged(selectedCid) {
    // Hide all 'Home' badges
    document.querySelectorAll('[class*="primary-label-"]').forEach(badge => badge.style.display = 'none');
    document.querySelectorAll('[id*="box_cid_"]').forEach(box => box.classList.remove('border-primary', 'bg-light'));

    if (selectedCid) {
        // Auto-check the primary concern
        const cb = document.getElementById('create_cid_' + selectedCid);
        if (cb) {
            cb.checked = true;
        }
        const badge = document.querySelector('.primary-label-' + selectedCid);
        if (badge) {
            badge.style.display = 'inline-block';
        }
        const box = document.getElementById('box_cid_' + selectedCid);
        if (box) {
            box.classList.add('border-primary', 'bg-light');
        }
    }
}

function onRoleChangedInCreate(roleId) {
    if (!roleId || !window.allRolePerms) return;
    const rolePerms = window.allRolePerms[roleId] || {};
    const textNotice = document.getElementById('perm_sync_role_text');
    if (textNotice) {
        textNotice.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Permissions auto-populated from selected role.';
    }

    Object.keys(rolePerms).forEach(mid => {
        const rp = rolePerms[mid];
        const v = document.getElementById('m_view_' + mid);
        const a = document.getElementById('m_add_' + mid);
        const e = document.getElementById('m_edit_' + mid);
        const d = document.getElementById('m_delete_' + mid);
        if (v) v.checked = (rp.view === 1);
        if (a) a.checked = (rp.add === 1);
        if (e) e.checked = (rp.edit === 1);
        if (d) d.checked = (rp.delete === 1);
    });
    updateHeaderCheckboxesState();
}

function setAllMatrixPerms(checked) {
    document.querySelectorAll('.matrix-perm-check').forEach(cb => cb.checked = checked);
    updateHeaderCheckboxesState();
}

function setAllMatrixReadOnly() {
    document.querySelectorAll('.matrix-perm-check').forEach(cb => {
        cb.checked = cb.classList.contains('matrix-perm-view');
    });
    updateHeaderCheckboxesState();
}

function setColumnMatrixPerm(action, checked) {
    document.querySelectorAll('.matrix-perm-' + action).forEach(cb => cb.checked = checked);
    updateHeaderCheckboxesState();
}

function setRowMatrixPerm(mid, checked) {
    document.querySelectorAll('.matrix-row-' + mid).forEach(cb => cb.checked = checked);
    updateHeaderCheckboxesState();
}

function setRowMatrixReadOnly(mid) {
    document.querySelectorAll('.matrix-row-' + mid).forEach(cb => {
        cb.checked = cb.classList.contains('matrix-perm-view');
    });
    updateHeaderCheckboxesState();
}

function selectAllCreateConcerns(check) {
    document.querySelectorAll('.create-concern-check').forEach(cb => cb.checked = check);
    const primaryId = document.getElementById('staff_concern_id')?.value;
    if (primaryId && !check) {
        const cb = document.getElementById('create_cid_' + primaryId);
        if (cb) cb.checked = true;
    }
}

function selectOnlyPrimaryConcern() {
    document.querySelectorAll('.create-concern-check').forEach(cb => cb.checked = false);
    const primaryId = document.getElementById('staff_concern_id')?.value;
    if (primaryId) {
        const cb = document.getElementById('create_cid_' + primaryId);
        if (cb) cb.checked = true;
    }
}

function toggleCreatePassword(btn) {
    const input = document.getElementById('staff_create_password');
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.className = 'bi bi-eye-slash';
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.className = 'bi bi-eye';
        }
    }
}
</script>
