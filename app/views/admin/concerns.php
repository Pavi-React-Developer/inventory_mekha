<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Concern & Tenant Management</h4>
            <p class="text-secondary small mb-0">Create new business concerns, toggle licensed modules, and configure tenant isolation.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateConcern">
            <i class="bi bi-plus-circle me-1"></i> Create New Concern
        </button>
    </div>

    <!-- Concerns List Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-buildings me-2 text-primary"></i> Active Concerns Registry</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Concern Information</th>
                        <th>Contact / GSTIN</th>
                        <th>Active Modules</th>
                        <th>Catalog Items</th>
                        <th>Status</th>
                        <th class="text-end">Module Licenses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($concerns as $c): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace fs-6"><?= htmlspecialchars($c['code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($c['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($c['address'] ?: 'No address specified') ?></div>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone text-muted me-1"></i> <?= htmlspecialchars($c['phone'] ?: '-') ?></div>
                                <div class="small text-muted"><i class="bi bi-receipt text-muted me-1"></i> <?= htmlspecialchars($c['gstin'] ?: 'Unregistered') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    <i class="bi bi-check2-circle me-1"></i> <?= (int)$c['active_modules_count'] ?> Active
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <?= (int)$c['variants_count'] ?> Variants
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalModules<?= $c['id'] ?>">
                                    <i class="bi bi-toggles"></i> Manage Modules
                                </button>
                            </td>
                        </tr>

                        <!-- Module Toggle Modal for this concern -->
                        <div class="modal fade" id="modalModules<?= $c['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Manage Modules: <?= htmlspecialchars($c['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-secondary small mb-3">
                                            Toggle modules on/off for this concern. Disabled modules will immediately return 403 Forbidden to concern users and disappear from their sidebar.
                                        </p>
                                        
                                        <?php
                                        $cmRows = \App\Core\DB::fetchAll(
                                            "SELECT m.id, m.code, m.name, m.group_name, m.icon, COALESCE(cm.enabled, 0) as is_enabled
                                             FROM modules m
                                             LEFT JOIN concern_modules cm ON m.id = cm.module_id AND cm.concern_id = ?
                                             WHERE m.is_super_admin_only = 0
                                             ORDER BY m.sort_order ASC",
                                            [$c['id']]
                                        );
                                        $groupedCM = [];
                                        foreach ($cmRows as $r) {
                                            $groupedCM[$r['group_name']][] = $r;
                                        }
                                        ?>

                                        <div class="row g-3">
                                            <?php foreach ($groupedCM as $grp => $items): ?>
                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded bg-light">
                                                        <h6 class="fw-bold text-dark mb-2"><?= htmlspecialchars($grp) ?></h6>
                                                        <?php foreach ($items as $item): ?>
                                                            <div class="form-check form-switch mb-2">
                                                                <input class="form-check-input module-toggle" type="checkbox" 
                                                                       id="mod_<?= $c['id'] ?>_<?= $item['id'] ?>"
                                                                       data-concern-id="<?= $c['id'] ?>"
                                                                       data-module-id="<?= $item['id'] ?>"
                                                                       <?= $item['is_enabled'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label small fw-medium" for="mod_<?= $c['id'] ?>_<?= $item['id'] ?>">
                                                                    <i class="bi <?= htmlspecialchars($item['icon']) ?> me-1 text-primary"></i>
                                                                    <?= htmlspecialchars($item['name']) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CREATE CONCERN GUIDED MODAL -->
<div class="modal fade" id="modalCreateConcern" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form action="<?= \App\Core\Controller::url('/admin/concerns/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-building-add me-2"></i> Create New Concern</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Column 1: Details & Initial Admin -->
                    <div class="col-lg-5 border-end">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-1"></i> Step 1: Concern Profile</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Concern Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Tirupur Knits Pvt Ltd" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Concern Code *</label>
                                <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. CON-TKP" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">GSTIN</label>
                                <input type="text" name="gstin" class="form-control text-uppercase" placeholder="33AAAAA0000A1Z5">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="9842100000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Official Email</label>
                                <input type="email" name="email" class="form-control" placeholder="info@concern.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Registered Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-1"></i> Step 2: Tenant Admin Account</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Admin Name *</label>
                            <input type="text" name="admin_name" class="form-control" placeholder="e.g. Senthil Kumar" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Admin Login Email *</label>
                            <input type="email" name="admin_email" class="form-control" placeholder="admin@concern.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Admin Password *</label>
                            <input type="password" name="admin_password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>
                    </div>

                    <!-- Column 2: Module Picker -->
                    <div class="col-lg-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-primary mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> Step 3: Module Picker Checklist</h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllModules(true)">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllModules(false)">Deselect All</button>
                            </div>
                        </div>
                        <p class="text-muted small">
                            Select the modules this concern gets. Unchecked modules will not appear in their sidebar and will return 403 on direct URL access.
                        </p>

                        <?php
                        $groupedAll = [];
                        foreach ($allModules as $mod) {
                            $groupedAll[$mod['group_name']][] = $mod;
                        }
                        ?>

                        <div class="row g-3" style="max-height: 520px; overflow-y: auto;">
                            <?php foreach ($groupedAll as $grp => $mods): ?>
                                <div class="col-md-6">
                                    <div class="card border h-100">
                                        <div class="card-header bg-light py-2">
                                            <span class="fw-bold small text-dark"><?= htmlspecialchars($grp) ?></span>
                                        </div>
                                        <div class="card-body p-2">
                                            <?php foreach ($mods as $m): ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input module-check" type="checkbox" name="modules[]" value="<?= $m['id'] ?>" id="new_mod_<?= $m['id'] ?>" checked>
                                                    <label class="form-check-label small" for="new_mod_<?= $m['id'] ?>">
                                                        <i class="bi <?= htmlspecialchars($m['icon']) ?> text-muted me-1"></i>
                                                        <?= htmlspecialchars($m['name']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check-lg me-1"></i> Create Concern & Provision Workspace
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAllModules(check) {
    document.querySelectorAll('.module-check').forEach(cb => cb.checked = check);
}

// Live module toggle handler
document.querySelectorAll('.module-toggle').forEach(el => {
    el.addEventListener('change', function() {
        const concernId = this.dataset.concernId;
        const moduleId  = this.dataset.moduleId;
        const enabled   = this.checked ? 1 : 0;

        const formData = new FormData();
        formData.append('concern_id', concernId);
        formData.append('module_id', moduleId);
        formData.append('enabled', enabled);
        formData.append('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');

        fetch('<?= \App\Core\Controller::url('/admin/concerns/toggle-module') ?>', {
            method: 'POST',
            body: formData
        }).then(r => r.json()).then(res => {
            if (!res.success) {
                alert('Failed to update module status: ' + res.message);
            }
        }).catch(err => {
            alert('Error updating module status.');
        });
    });
});
</script>
