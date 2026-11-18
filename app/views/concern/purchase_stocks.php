<div class="container-fluid px-0">
    <!-- Page Header & Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center text-white shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); width: 48px; height: 48px;">
                <i class="bi bi-boxes fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Purchase Stocks & Inventory</h4>
                <p class="text-muted small mb-0">Monitor inwarded fabric rolls, trims/garment supplies, and finished goods inventory with current stock balance.</p>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="<?= \App\Core\Controller::url('/m/purchase_stocks/export?tab=' . $activeTab . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>" class="btn btn-outline-success btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5 rounded-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export <?= match($activeTab) { 'rolls' => 'Rolls', 'garment_other' => 'Trims', 'finished_goods' => 'Finished Goods', default => 'Stocks' } ?> (CSV)
            </a>
            <a href="<?= \App\Core\Controller::url('/m/label_generation?tab=' . match($activeTab) { 'rolls' => 'purchase_raw', 'garment_other' => 'purchase_raw', 'finished_goods' => 'purchase_fg', default => 'purchase_raw' }) ?>" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5 rounded-2">
                <i class="bi bi-qr-code"></i> Print Labels
            </a>
            <a href="<?= \App\Core\Controller::url('/m/purchase_billing/create') ?>" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5 rounded-2">
                <i class="bi bi-bag-plus"></i> Record Purchase
            </a>
        </div>
    </div>

    <!-- Super Admin Concern Switcher Banner (if Super Admin) -->
    <?php if (App\Core\Auth::isSuperAdmin() && !empty($allConcerns)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-building-gear text-primary fs-5"></i>
                    <div>
                        <div class="fw-bold small text-dark">Filter Purchase Inventory by Concern:</div>
                        <div class="text-muted" style="font-size: 0.76rem;">Viewing <?= $selectedCid ? 'specific concern stock ledger' : 'all concerns consolidated stock ledger' ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=' . $activeTab) ?>" class="btn btn-sm <?= empty($selectedCid) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-2 px-3 fw-semibold">
                        All Concerns
                    </a>
                    <?php foreach ($allConcerns as $con): ?>
                        <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=' . $activeTab . '&concern_id=' . $con['id']) ?>" class="btn btn-sm <?= ($selectedCid == $con['id']) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-2 px-3 fw-semibold">
                            <?= htmlspecialchars($con['code']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4 High-Impact KPI Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Metric 1: Fabric Rolls Stock -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">Fabric Rolls Stock</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($metrics['rolls_qty'], 0) ?> <span class="fs-6 fw-normal text-muted">Rolls</span></h4>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-layers text-primary me-1"></i><strong><?= number_format($metrics['rolls_weight'], 2) ?> kg</strong> total fabric
                        </div>
                    </div>
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                        <i class="bi bi-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Trims & Garment Other -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">Trims & Garment Other</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($metrics['other_qty'], 0) ?> <span class="fs-6 fw-normal text-muted">Units</span></h4>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-box text-info me-1"></i><strong><?= $metrics['other_count'] ?></strong> distinct supply items
                        </div>
                    </div>
                    <div class="rounded-3 p-3 bg-info-subtle text-info">
                        <i class="bi bi-scissors fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Finished Goods Stock -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-purple" style="border-left-color: #8b5cf6 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">Purchased Finished Goods</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($metrics['finished_qty'], 0) ?> <span class="fs-6 fw-normal text-muted">Pcs</span></h4>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-tag text-purple me-1" style="color: #8b5cf6;"></i>MRP Val: <strong>₹<?= number_format($metrics['finished_mrp_val'], 2) ?></strong>
                        </div>
                    </div>
                    <div class="rounded-3 p-3 text-purple" style="background: #ede9fe; color: #6d28d9;">
                        <i class="bi bi-bag-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 4: Total Purchased Inventory Value -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">Total Stock Valuation</div>
                        <h4 class="fw-bold mb-0 text-success">₹<?= number_format($metrics['grand_value'], 2) ?></h4>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-check2-circle text-success me-1"></i>Across all 3 purchase categories
                        </div>
                    </div>
                    <div class="rounded-3 p-3 bg-success-subtle text-success">
                        <i class="bi bi-currency-rupee fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Inventory Card with 3 Sections / Tabs -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <!-- Section Navigation Pills (3 Tabs) -->
        <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
            <ul class="nav nav-pills card-header-pills gap-2 pb-3 mb-0" id="stockSectionTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2 rounded-2 <?= $activeTab === 'rolls' ? 'active' : '' ?>" 
                       href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=rolls' . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>">
                        <i class="bi bi-stack"></i>
                        <span>1. Fabric Rolls</span>
                        <span class="badge <?= $activeTab === 'rolls' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> rounded-pill ms-1">
                            <?= count($rolls) ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2 rounded-2 <?= $activeTab === 'garment_other' ? 'active' : '' ?>" 
                       href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=garment_other' . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>">
                        <i class="bi bi-scissors"></i>
                        <span>2. Other Garments & Trims</span>
                        <span class="badge <?= $activeTab === 'garment_other' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> rounded-pill ms-1">
                            <?= count($garmentOther) ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2 rounded-2 <?= $activeTab === 'finished_goods' ? 'active' : '' ?>" 
                       href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=finished_goods' . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>">
                        <i class="bi bi-bag-check"></i>
                        <span>3. Purchased Finished Goods</span>
                        <span class="badge <?= $activeTab === 'finished_goods' ? 'bg-white text-primary' : 'bg-light text-secondary border' ?> rounded-pill ms-1">
                            <?= count($finishedGoods) ?>
                        </span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card-body p-3 border-bottom bg-light">
            <form method="GET" action="<?= \App\Core\Controller::url('/m/purchase_stocks') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                <?php if ($selectedCid): ?>
                    <input type="hidden" name="concern_id" value="<?= (int)$selectedCid ?>">
                <?php endif; ?>

                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search by Code, Name, Pattern, Color, Bill #..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">-- All Suppliers --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>" <?= ($supplierFilter == $sup['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['name']) ?> <?= !empty($sup['company_name']) ? ('(' . htmlspecialchars($sup['company_name']) . ')') : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- All Status --</option>
                        <option value="in_stock" <?= ($statusFilter === 'in_stock') ? 'selected' : '' ?>>In Stock (Completed)</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending Inward</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold shadow-sm">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($supplierFilter) || !empty($statusFilter)): ?>
                        <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=' . $activeTab . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>" class="btn btn-sm btn-outline-secondary px-2 rounded-2">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ============================================== -->
        <!-- TAB SECTION 1: FABRIC ROLLS                    -->
        <!-- ============================================== -->
        <?php if ($activeTab === 'rolls'): ?>
            <div class="card-body p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold small text-dark"><i class="bi bi-stack text-primary me-1"></i> Stock Registry Mode:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn <?= $rollViewMode === 'consolidated' ? 'btn-primary' : 'btn-outline-secondary bg-white' ?> fw-semibold" id="btnRollConsolidated" onclick="switchRollView('consolidated')">
                            <i class="bi bi-collection-fill me-1"></i> Consolidated by Color & Design (Accumulated Stock)
                        </button>
                        <button type="button" class="btn <?= $rollViewMode === 'detailed' ? 'btn-primary' : 'btn-outline-secondary bg-white' ?> fw-semibold" id="btnRollDetailed" onclick="switchRollView('detailed')">
                            <i class="bi bi-list-columns-reverse me-1"></i> Individual Roll Batches
                        </button>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small"><i class="bi bi-info-circle text-primary me-1"></i> Duplicate entries automatically accumulated with previous count.</span>
                    <a href="<?= \App\Core\Controller::url('/m/purchase_stocks/export?tab=rolls&mode=consolidated' . ($selectedCid ? '&concern_id=' . $selectedCid : '')) ?>" class="btn btn-sm btn-outline-success bg-white shadow-sm fw-semibold" title="Export Consolidated Stock by Color & Design">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Consolidated (CSV)
                    </a>
                </div>
            </div>

            <!-- View A: Consolidated Color & Design Stock (Duplicates removed, Qty added to previous count) -->
            <div id="viewRollConsolidated" class="card-body p-0" style="<?= $rollViewMode === 'detailed' ? 'display: none;' : '' ?>">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                <th style="width: 40px;">#</th>
                                <th class="text-start">Design Name / Pattern</th>
                                <th>Color / Shade</th>
                                <th>HSN</th>
                                <th class="table-primary text-primary fw-bold">Accumulated Stock</th>
                                <th>Total Fabric Weight</th>
                                <th class="text-end">Unit Rate</th>
                                <th class="text-end">Total Valuation (₹)</th>
                                <th class="text-start">Inward History</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consolidatedRolls)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-stack display-6 text-secondary d-block mb-2"></i>
                                        <strong>No fabric roll specifications registered yet.</strong>
                                        <div class="small mt-1">Record a purchase bill to automatically populate the Color & Design roll stock registry.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $cIdx = 1; foreach ($consolidatedRolls as $cr): ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $cIdx++ ?></td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark fs-6 font-monospace">
                                                <i class="bi bi-palette text-primary me-1"></i><?= htmlspecialchars($cr['design_name']) ?>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.70rem;">Fabric Pattern Specification</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1 text-uppercase fw-semibold">
                                                <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem; color: #4f46e5;"></i><?= htmlspecialchars($cr['color']) ?>
                                            </span>
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($cr['hsn_code'] ?: '-') ?></td>
                                        <td class="table-primary text-primary fw-bold fs-6">
                                            <?= (float)$cr['qty'] ?> <span class="fs-6 fw-normal text-muted"><?= htmlspecialchars($cr['unit'] ?: 'Rolls') ?></span>
                                            <div>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                    <i class="bi bi-check2-circle me-1"></i>Accumulated Available
                                                </span>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-secondary">
                                            <?= number_format((float)$cr['net_weight'], 2) ?> kg
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ₹<?= number_format((float)$cr['last_unit_price'], 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format((float)$cr['total_value'], 2) ?>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-semibold text-dark">
                                                <i class="bi bi-receipt text-secondary me-1"></i><?= (int)$cr['bills_count'] ?> Inward Batch(es)
                                            </div>
                                            <?php if (!empty($cr['last_bill_no'])): ?>
                                                <div class="text-muted small" style="font-size: 0.70rem;">
                                                    Last Bill: <span class="font-monospace fw-semibold text-primary"><?= htmlspecialchars($cr['last_bill_no']) ?></span>
                                                    <?= !empty($cr['last_supplier_name']) ? (' &bull; ' . htmlspecialchars($cr['last_supplier_name'])) : '' ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.68rem;">IN STOCK</span>
                                        </td>
                                        <td>
                                            <a href="<?= \App\Core\Controller::url('/m/label_generation?tab=purchase_raw&roll=' . urlencode($cr['design_name'])) ?>" class="btn btn-outline-primary btn-sm p-1" title="Print QR Labels for this Color & Design">
                                                <i class="bi bi-qr-code"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View B: Individual Roll Batches (Detailed Roll Breakdown) -->
            <div id="viewRollDetailed" class="card-body p-0" style="<?= $rollViewMode === 'consolidated' ? 'display: none;' : '' ?>">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                <th style="width: 40px;">#</th>
                                <th style="width: 60px;">Photo</th>
                                <th style="width: 110px;">Roll Code</th>
                                <th class="text-start">Fabric / Pattern</th>
                                <th>Color / Shade</th>
                                <th>HSN</th>
                                <th>Weight (kg)</th>
                                <th>Inward Qty</th>
                                <th>Available Stock</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total (₹)</th>
                                <th class="text-start">Bill & Supplier</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rolls)): ?>
                                <tr>
                                    <td colspan="14" class="text-center py-5 text-muted">
                                        <i class="bi bi-stack display-6 text-secondary d-block mb-2"></i>
                                        <strong>No fabric rolls found in purchase stock.</strong>
                                        <div class="small mt-1">Record a purchase bill with fabric rolls to track roll inventory.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $rIdx = 1;
                                foreach ($rolls as $r): 
                                    $isInStock = ($r['inward_status'] ?? '') === 'completed';
                                    $wt = (float)($r['net_weight'] ?? 0);
                                    $qty = (float)($r['qty'] ?? 1);
                                    $uPrice = (float)($r['unit_price'] ?? 0);
                                    $tot = (float)($r['total_price'] ?? 0);

                                    $cleanTitle = htmlspecialchars($r['item_name'] ?: 'Fabric Roll');
                                    if (($r['item_name'] === $r['item_code'] || is_numeric($r['item_name'])) && !empty($r['pattern'])) {
                                        $cleanTitle = htmlspecialchars(ucwords($r['pattern'])) . ' Fabric';
                                    }
                                ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $rIdx++ ?></td>
                                        <td>
                                            <?php if (!empty($r['photo_path'])): ?>
                                                <a href="<?= htmlspecialchars($r['photo_path']) ?>" target="_blank" title="View Full Roll Sample">
                                                    <img src="<?= htmlspecialchars($r['photo_path']) ?>" alt="Roll Photo" class="rounded border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border p-1.5"><i class="bi bi-image"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark badge bg-light text-dark border">
                                                <?= htmlspecialchars($r['item_code'] ?: ('#' . $r['id'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= $cleanTitle ?></div>
                                            <?php if (!empty($r['pattern'])): ?>
                                                <div class="text-muted small" style="font-size: 0.72rem;">Pattern: <?= htmlspecialchars($r['pattern']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($r['color'])): ?>
                                                <span class="badge bg-light text-dark border fw-normal text-capitalize">
                                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem; color: #4f46e5;"></i><?= htmlspecialchars($r['color']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="font-monospace small"><?= htmlspecialchars($r['hsn_code'] ?: '-') ?></td>
                                        <td class="fw-semibold text-secondary">
                                            <?= $wt > 0 ? number_format($wt, 2) . ' kg' : '-' ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= (float)$qty ?> <span class="fw-normal text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($r['unit'] ?: 'Rolls') ?></span>
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-check-circle me-1"></i><?= (float)$qty ?> <?= htmlspecialchars($r['unit'] ?: 'Rolls') ?> (<?= number_format($wt, 2) ?> kg)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i><?= (float)$qty ?> In Transit
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ₹<?= number_format($uPrice, 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format($tot, 2) ?>
                                        </td>
                                        <td class="text-start">
                                            <div>
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $r['purchase_bill_id']) ?>" class="fw-semibold text-primary font-monospace text-decoration-none" title="View Purchase Bill">
                                                    <?= htmlspecialchars($r['bill_no']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.70rem;">
                                                <?= htmlspecialchars($r['supplier_name'] ?: 'Direct Supplier') ?> &bull; <?= date('d M Y', strtotime($r['bill_date'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.68rem;">IN STOCK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.68rem;">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $r['purchase_bill_id']) ?>" class="btn btn-outline-secondary btn-sm p-1" title="View Bill">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                                <a href="<?= \App\Core\Controller::url('/m/label_generation?tab=purchase_raw&roll=' . urlencode($r['item_code'])) ?>" class="btn btn-outline-primary btn-sm p-1" title="Print QR Label">
                                                    <i class="bi bi-qr-code"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- TAB SECTION 2: OTHER GARMENTS & TRIMS          -->
        <!-- ============================================== -->
        <?php if ($activeTab === 'garment_other'): ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                <th style="width: 40px;">#</th>
                                <th style="width: 120px;">Item Code</th>
                                <th class="text-start">Supply Item Name & Description</th>
                                <th>Unit of Measure</th>
                                <th>Inwarded Qty</th>
                                <th>Available Stock</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Stock Value (₹)</th>
                                <th class="text-start">Purchase Bill Ref</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($garmentOther)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-scissors display-6 text-secondary d-block mb-2"></i>
                                        <strong>No garment supplies or trims found in purchase stock.</strong>
                                        <div class="small mt-1">Record buttons, threads, zippers, or labels in purchase billing.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $gIdx = 1;
                                foreach ($garmentOther as $g): 
                                    $isInStock = ($g['inward_status'] ?? '') === 'completed';
                                    $qty = (float)($g['qty'] ?? 1);
                                    $uPrice = (float)($g['unit_price'] ?? 0);
                                    $tot = (float)($g['total_price'] ?? 0);
                                ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $gIdx++ ?></td>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark badge bg-light text-dark border">
                                                <?= htmlspecialchars($g['item_code'] ?: ('SUP-' . $g['id'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($g['item_name']) ?></div>
                                            <?php if (!empty($g['specification']) && $g['specification'] !== $g['item_name']): ?>
                                                <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars($g['specification']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($g['unit'] ?: 'Pcs') ?></span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= (float)$qty ?> <?= htmlspecialchars($g['unit'] ?: 'Pcs') ?>
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-box-seam me-1"></i><?= (float)$qty ?> <?= htmlspecialchars($g['unit'] ?: 'Pcs') ?> Available
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i>Pending Inward
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ₹<?= number_format($uPrice, 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format($tot, 2) ?>
                                        </td>
                                        <td class="text-start">
                                            <div>
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $g['purchase_bill_id']) ?>" class="fw-semibold text-primary font-monospace text-decoration-none">
                                                    <?= htmlspecialchars($g['bill_no']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.70rem;">
                                                <?= htmlspecialchars($g['supplier_name'] ?: 'Vendor') ?> &bull; <?= date('d M Y', strtotime($g['bill_date'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.68rem;">IN STOCK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.68rem;">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $g['purchase_bill_id']) ?>" class="btn btn-outline-secondary btn-sm p-1" title="View Bill">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                                <a href="<?= \App\Core\Controller::url('/m/label_generation?tab=purchase_raw&q=' . urlencode($g['item_name'])) ?>" class="btn btn-outline-primary btn-sm p-1" title="Print QR Label">
                                                    <i class="bi bi-qr-code"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- TAB SECTION 3: PURCHASED FINISHED GOODS        -->
        <!-- ============================================== -->
        <?php if ($activeTab === 'finished_goods'): ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr class="fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                <th style="width: 40px;">#</th>
                                <th style="width: 130px;">SKU / Code</th>
                                <th class="text-start">Garment Product Name</th>
                                <th>Category / Type</th>
                                <th>Size / Spec</th>
                                <th>Color</th>
                                <th class="text-end">Purchase Cost</th>
                                <th class="text-end">MRP</th>
                                <th>Inward Qty</th>
                                <th>Available Stock</th>
                                <th class="text-end">Stock Value (₹)</th>
                                <th class="text-start">Purchase Bill Ref</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($finishedGoods)): ?>
                                <tr>
                                    <td colspan="14" class="text-center py-5 text-muted">
                                        <i class="bi bi-bag-check display-6 text-secondary d-block mb-2"></i>
                                        <strong>No finished goods found in purchase stock.</strong>
                                        <div class="small mt-1">Record ready-made garments or apparel purchases in purchase billing.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $fIdx = 1;
                                foreach ($finishedGoods as $f): 
                                    $isInStock = ($f['inward_status'] ?? '') === 'completed';
                                    $qty = (float)($f['qty'] ?? 1);
                                    $uPrice = (float)($f['unit_price'] ?? 0);
                                    $mrp = (float)($f['mrp'] ?? 0);
                                    $tot = (float)($f['total_price'] ?? 0);
                                    $liveStore = isset($f['live_variant_stock']) ? (float)$f['live_variant_stock'] : $qty;
                                ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $fIdx++ ?></td>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark badge bg-light text-dark border">
                                                <?= htmlspecialchars($f['variant_sku'] ?: ($f['item_code'] ?: ('FG-' . $f['id']))) ?>
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($f['item_name']) ?></div>
                                            <?php if (!empty($f['variant_name']) && $f['variant_name'] !== $f['item_name']): ?>
                                                <div class="text-muted small" style="font-size: 0.70rem;">Catalog: <?= htmlspecialchars($f['variant_name']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($f['category_name'] ?: 'Apparel') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-semibold"><?= htmlspecialchars($f['specification'] ?: ($f['variant_size'] ?: 'Free')) ?></span>
                                        </td>
                                        <td>
                                            <?= !empty($f['color']) ? htmlspecialchars(ucwords($f['color'])) : '-' ?>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ₹<?= number_format($uPrice, 2) ?>
                                        </td>
                                        <td class="text-end fw-semibold text-primary">
                                            <?= $mrp > 0 ? ('₹' . number_format($mrp, 2)) : '-' ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= (float)$qty ?> Pcs
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-box2-heart me-1"></i><?= $liveStore ?> Pcs in Store
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i><?= (float)$qty ?> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format($tot, 2) ?>
                                        </td>
                                        <td class="text-start">
                                            <div>
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $f['purchase_bill_id']) ?>" class="fw-semibold text-primary font-monospace text-decoration-none">
                                                    <?= htmlspecialchars($f['bill_no']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.70rem;">
                                                <?= htmlspecialchars($f['supplier_name'] ?: 'Vendor') ?> &bull; <?= date('d M Y', strtotime($f['bill_date'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($isInStock): ?>
                                                <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.68rem;">IN STOCK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.68rem;">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/view?id=' . $f['purchase_bill_id']) ?>" class="btn btn-outline-secondary btn-sm p-1" title="View Bill">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                                <a href="<?= \App\Core\Controller::url('/m/label_generation?tab=purchase_fg&q=' . urlencode($f['item_name'])) ?>" class="btn btn-outline-primary btn-sm p-1" title="Print QR Label">
                                                    <i class="bi bi-qr-code"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Pagination / Summary Info -->
        <div class="card-footer bg-white border-top p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
            <div>
                Showing <strong><?= count($activeTab === 'rolls' ? $consolidatedRolls : ($activeTab === 'garment_other' ? $garmentOther : $finishedGoods)) ?></strong> <?= $activeTab === 'rolls' ? 'consolidated specification(s)' : 'items' ?> in <strong><?= match($activeTab) { 'rolls' => 'Fabric Rolls', 'garment_other' => 'Other Garments & Trims', 'finished_goods' => 'Purchased Finished Goods', default => 'Stocks' } ?></strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-shield-check text-success me-1"></i> Multi-tenant data isolated</span>
                <span><i class="bi bi-arrow-repeat text-primary me-1"></i> Live inventory synced</span>
            </div>
        </div>
    </div>
</div>

<script>
function switchRollView(mode) {
    const consView = document.getElementById('viewRollConsolidated');
    const detView = document.getElementById('viewRollDetailed');
    const btnCons = document.getElementById('btnRollConsolidated');
    const btnDet = document.getElementById('btnRollDetailed');

    if (!consView || !detView) return;

    if (mode === 'detailed') {
        consView.style.display = 'none';
        detView.style.display = 'block';
        if (btnCons) {
            btnCons.className = 'btn btn-outline-secondary bg-white fw-semibold';
        }
        if (btnDet) {
            btnDet.className = 'btn btn-primary fw-semibold';
        }
    } else {
        consView.style.display = 'block';
        detView.style.display = 'none';
        if (btnCons) {
            btnCons.className = 'btn btn-primary fw-semibold';
        }
        if (btnDet) {
            btnDet.className = 'btn btn-outline-secondary bg-white fw-semibold';
        }
    }
}
</script>
