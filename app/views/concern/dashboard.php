<div class="container-fluid px-0">
    <!-- Concern KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Variants</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($variantsCount) ?></h3>
                    <small class="text-primary"><a href="<?= \App\Core\Controller::url('/m/variants') ?>" class="text-decoration-none">View Catalog</a></small>
                </div>
                <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Live Stock Units</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($totalStockQty, 2) ?></h3>
                    <small class="text-success"><a href="<?= \App\Core\Controller::url('/m/stocks') ?>" class="text-decoration-none text-success">Warehouse Balance</a></small>
                </div>
                <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Inventory Valuation</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0">₹<?= number_format($stockValuation, 2) ?></h3>
                    <small class="text-muted">At Cost / Standard Price</small>
                </div>
                <div class="stat-icon-box bg-info bg-opacity-10 text-info">
                    <i class="bi bi-currency-rupee"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Liquid Cash / Bank</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0">₹<?= number_format($cashBalance, 2) ?></h3>
                    <small class="text-muted"><?= $totalCustomers ?> Customers &bull; <?= $totalSuppliers ?> Suppliers</small>
                </div>
                <div class="stat-icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="fw-bold text-dark"><i class="bi bi-lightning-charge text-warning me-1"></i> Operations Fast Track:</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= \App\Core\Controller::url('/m/inward') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-in-down"></i> Receive Inward</a>
                <a href="<?= \App\Core\Controller::url('/m/label_generation') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-qr-code"></i> Generate Labels</a>
                <a href="<?= \App\Core\Controller::url('/m/billing') ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-credit-card-2-front"></i> New Invoice</a>
                <a href="<?= \App\Core\Controller::url('/m/outward') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-up-right"></i> Outward Consignment</a>
                <a href="<?= \App\Core\Controller::url('/scan') ?>" class="btn btn-sm btn-primary"><i class="bi bi-qr-code-scan"></i> Scan Barcode</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Stock Movement Ledger -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Stock Ledger Movements</h6>
                    <a href="<?= \App\Core\Controller::url('/m/stock_ledger') ?>" class="btn btn-sm btn-outline-secondary">View Full Ledger</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Variant</th>
                                <th>Module Ref</th>
                                <th>In (+Qty)</th>
                                <th>Out (-Qty)</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLedger)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No stock movements recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLedger as $sl): ?>
                                    <tr>
                                        <td class="small text-muted font-monospace"><?= date('d M H:i', strtotime($sl['created_at'])) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($sl['variant_name']) ?></div>
                                            <small class="badge bg-light text-dark border"><?= htmlspecialchars($sl['sku_code']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($sl['ref_module']) ?></span>
                                            <?php if ($sl['note']): ?>
                                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($sl['note']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sl['qty_in'] > 0): ?>
                                                <span class="text-success fw-bold">+<?= number_format($sl['qty_in'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sl['qty_out'] > 0): ?>
                                                <span class="text-danger fw-bold">-<?= number_format($sl['qty_out'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($sl['user_name'] ?? 'System') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Low Stock Threshold Alerts</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php if (empty($lowStocks)): ?>
                            <li class="list-group-item text-success text-center py-4">
                                <i class="bi bi-check-circle fs-3 d-block mb-1"></i>
                                All inventory levels are healthy!
                            </li>
                        <?php else: ?>
                            <?php foreach ($lowStocks as $ls): ?>
                                <li class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($ls['variant_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($ls['sku_code']) ?> &bull; <?= htmlspecialchars($ls['size'] ?: 'Standard') ?></small>
                                    </div>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 fs-6">
                                        <?= number_format($ls['qty'], 2) ?> <?= htmlspecialchars($ls['weight_unit']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
