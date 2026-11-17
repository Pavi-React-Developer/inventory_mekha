<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Live Inventory Stocks</h4>
            <p class="text-secondary small mb-0">Consolidated live balance by warehouse location, backed by append-only ledger audit trail.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= \App\Core\Controller::url('/m/stock_ledger') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i> Stock Ledger
            </a>
            <a href="<?= \App\Core\Controller::url('/m/inward') ?>" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-down me-1"></i> Inward Entry
            </a>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-stat d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Physical Inventory Units</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($totalUnits, 2) ?></h3>
                </div>
                <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-stat d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Stock Valuation</span>
                    <h3 class="fw-bold text-success mt-1 mb-0">₹<?= number_format($totalValuation, 2) ?></h3>
                </div>
                <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-box-seam me-2 text-primary"></i> Current Stock by Location</h6>
            <span class="badge bg-light text-dark border"><?= count($stocks) ?> Stock Lines</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>SKU Code</th>
                        <th>Variant Description</th>
                        <th>Category / Specs</th>
                        <th>Storage Location</th>
                        <th>Live On-Hand Qty</th>
                        <th>Unit Rate</th>
                        <th>Line Valuation</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stocks)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No stock balances in warehouse. Record Inward or QC Passed items.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stocks as $s): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($s['sku_code']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['variant_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['size'] ?: 'Free') ?> &bull; <?= number_format($s['weight'], 3) ?> <?= htmlspecialchars($s['weight_unit']) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($s['category_name'] ?? 'General') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['subcategory_name'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-geo-alt me-1 text-primary"></i> <?= htmlspecialchars($s['location']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fs-6 px-2 py-1">
                                        <?= number_format($s['qty'], 2) ?> <?= htmlspecialchars($s['weight_unit']) ?>
                                    </span>
                                </td>
                                <td>₹<?= number_format($s['price'], 2) ?></td>
                                <td class="fw-bold text-dark">₹<?= number_format($s['qty'] * $s['price'], 2) ?></td>
                                <td class="text-end">
                                    <a href="<?= \App\Core\Controller::url('/m/label_generation?variant_id=' . $s['variant_id']) ?>" class="btn btn-sm btn-outline-primary" title="Print QR Stickers">
                                        <i class="bi bi-qr-code"></i> Print Labels
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
