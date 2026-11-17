<div class="container-fluid px-0">
    <!-- Super Admin Header Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Active Concerns</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($concernsCount) ?></h3>
                    <small class="text-success"><i class="bi bi-shield-check"></i> Multi-Tenant Isolated</small>
                </div>
                <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-buildings"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Enterprise Assets</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($assetsCount) ?></h3>
                    <small class="text-primary">₹<?= number_format($assetsValuation, 2) ?> Book Value</small>
                </div>
                <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-building-gear"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Users</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($usersCount) ?></h3>
                    <small class="text-info">Platform & Tenant Staff</small>
                </div>
                <div class="stat-icon-box bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Architecture Status</span>
                    <h4 class="fw-bold text-dark mt-1 mb-0">Production</h4>
                    <small class="text-muted">Strict Tenant Scoping</small>
                </div>
                <div class="stat-icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-cpu"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Concerns Summary Table -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i> Registered Concerns Overview</h6>
                    <a href="<?= \App\Core\Controller::url('/admin/concerns') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Concern
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Concern Name</th>
                                <th>Active Modules</th>
                                <th>Staff Users</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concerns as $c): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($c['code']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($c['gstin'] ?: 'No GSTIN') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                            <?= (int)$c['active_modules_count'] ?> / 22 Modules Active
                                        </span>
                                    </td>
                                    <td><?= (int)$c['users_count'] ?> Staff</td>
                                    <td>
                                        <span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= \App\Core\Controller::url('/admin/concerns') ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-gear"></i> Configure
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Audit Log Feed -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-shaded me-2 text-danger"></i> System Audit Log</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php if (empty($recentAudits)): ?>
                            <li class="list-group-item text-muted py-3 text-center">No recent audit logs.</li>
                        <?php else: ?>
                            <?php foreach ($recentAudits as $a): ?>
                                <li class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-dark"><?= htmlspecialchars($a['action']) ?></span>
                                        <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($a['created_at']) ?></span>
                                    </div>
                                    <div class="text-dark fw-medium">
                                        Table: <code><?= htmlspecialchars($a['table_name']) ?></code> #<?= (int)$a['row_id'] ?>
                                    </div>
                                    <div class="text-muted small">
                                        By: <?= htmlspecialchars($a['user_name'] ?? 'System') ?> 
                                        <?= !empty($a['concern_name']) ? "(".htmlspecialchars($a['concern_name']).")" : "" ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
